<?php

namespace Drupal\am_net_cpe;

use Drupal\am_net\AssociationManagementClient;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Class DocumentManager.
 *
 * @package Drupal\am_net_cpe
 */
class DocumentManager implements DocumentManagerInterface {

  /**
   * The AM.net REST API client.
   *
   * @var \Drupal\am_net\AssociationManagementClient
   */
  protected $client;

  /**
   * The file storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The media storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $mediaStorage;

  /**
   * The session storage.
   *
   * @var \Drupal\vscpa_commerce\EventSessionStorageInterface
   */
  protected $sessionStorage;

  /**
   * The 'am_net_cpe' logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The extension mime type guesser.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $extensionMimeTypeGuesser;

  /**
   * DocumentManager constructor.
   *
   * @param \Drupal\am_net\AssociationManagementClient $am_net_client
   *   The AM.net API client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The 'am_net_cpe' logger channel.
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mime_type_guesser
   *   The mime type guesser.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(AssociationManagementClient $am_net_client, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger, MimeTypeGuesserInterface $mime_type_guesser) {
    $this->client = $am_net_client;
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->mediaStorage = $entity_type_manager->getStorage('media');
    $this->sessionStorage = $entity_type_manager->getStorage('event_session');
    $this->extensionMimeTypeGuesser = $mime_type_guesser;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function syncEventDocuments(ContentEntityInterface $event) {
    $event_code = $event->field_amnet_event_id->code;
    $event_year = $event->field_amnet_event_id->year;
    $event_id = "{$event_year}{$event_code}";

    try {
      $response = $this->client->get('/Event/' . $event_id . '/documents');
      if ($error_message = $response->getErrorMessage()) {
        $this->logger->error($error_message);
      }
      foreach ($response->getResult() as $document) {
        $this->syncEventDocument($event, $document);
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Syncs an AM.net document with a Drupal CPE event product.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $event
   *   The Drupal CPE event product entity.
   * @param array $document
   *   The AM.net Document record.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function syncEventDocument(ContentEntityInterface $event, array $document) {
    $document_id = $document['DocumentID'];
    $description = $document['Note'];
    $created_date = new DrupalDateTime($document['RecordAdded']);
    $session_code = !empty($document['EventSessionLink']) ? $document['EventSessionLink']['SessionCode'] : NULL;
    $extension = $document['FilenameExtension'];
    $filename = $document['DocName'];

    // Attach the filename extension if it doesn't already exist.
    if (strpos($filename, '.') === FALSE) {
      $filename .= $extension;
    }

    $uri = 'public://resources/' . $filename;
    $mime_type = $this->extensionMimeTypeGuesser->guess($filename);

    // Nothing to do if the media item already exists.
    if (!$media = $this->mediaStorage->loadByProperties([
      'bundle' => 'document',
      'field_amnet_document_id' => $document_id,
    ])) {

      // Get the remote file (stream, not text content).
      $remote_file = $this->client->get('/documents', ['docid' => $document_id], 'stream');
      if ($error_message = $remote_file->getErrorMessage()) {
        $this->logger->error($error_message);
        return;
      }

      // Create the local file.
      /** @var \Drupal\file\FileInterface $local_file */
      $local_file = $this->fileStorage->create([
        'uid' => 1,
        'filename' => $filename,
        'uri' => $uri,
        'filemime' => $mime_type,
        'status' => FILE_STATUS_PERMANENT,
        'created' => $created_date->getTimestamp(),
        'changed' => (new DrupalDateTime())->getTimestamp(),
      ]);
      file_put_contents($local_file->getFileUri(), $remote_file->getResult());
      $local_file->save();

      // Create the local media item.
      $media = $this->mediaStorage->create([
        'bundle' => 'document',
        'uid' => '1',
        'name' => $filename,
        'status' => 1,
        'field_res_files' => [
          'target_id' => $local_file->id(),
          'display' => 1,
          'description' => $description,
        ],
        'field_amnet_document_id' => $document_id,
        'created' => $created_date->getTimestamp(),
        'changed' => (new DrupalDateTime())->getTimestamp(),
      ]);
      $media->save();

      // Attach the media to the session.
      if ($session_code && $session = $this->getEventSession($event, $session_code)) {
        $session
          ->get('field_electronic_materials')->appendItem($media);
        $session->save();
      }
    }
  }

  /**
   * Gets the session for the given CPE entity and event session code.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $product
   *   The CPE product.
   * @param string $session_code
   *   The session code.
   *
   * @return \Drupal\vscpa_commerce\Entity\EventSessionInterface|null
   *   The event session entity, or NULL if not found.
   */
  protected function getEventSession(ContentEntityInterface $product, $session_code) {
    if ($sessions = $this->sessionStorage->loadByProperties([
      'field_session_code' => $session_code,
      'field_session_cpe_parent' => $product->id(),
    ])) {
      return current($sessions);
    }
  }

}
