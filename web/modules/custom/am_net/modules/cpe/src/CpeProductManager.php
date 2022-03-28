<?php

namespace Drupal\am_net_cpe;

use Drupal\am_net\AMNetEntityTypeContext;
use Drupal\am_net\AMNetEntityTypesInterface;
use Drupal\am_net\AmNetRecordExcludedException;
use Drupal\am_net\AmNetRecordNotFoundException;
use Drupal\am_net\AssociationManagementClient;
use Drupal\am_net\PersonManager;
use Drupal\am_net_cpe\EventHelper as Helper;
use Drupal\am_net_firms\FirmManager;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\vscpa_commerce\Entity\EventSessionInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * AM.net CPE product manager.
 *
 * @package Drupal\am_net_cpe
 */
class CpeProductManager implements CpeProductManagerInterface {

  use MyCpeTrait;

  /**
   * The event pricing currency code.
   *
   * @var string
   */
  protected $eventCurrencyCode = 'USD';

  /**
   * The event timezone.
   *
   * @var string
   */
  protected $eventTimezone = 'America/New_York';

  /**
   * The Drupal date storage timezone.
   *
   * @var string
   */
  protected $storageTimezone = 'UTC';

  /**
   * The AM.net REST API client.
   *
   * @var \Drupal\am_net\AssociationManagementClient
   */
  protected $client;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The AM.net firm manager.
   *
   * @var \Drupal\am_net_firms\FirmManager
   */
  protected $firmManager;

  /**
   * The 'am_net_cpe' logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The paragraph storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $paragraphStorage;

  /**
   * The AM.net person manager.
   *
   * @var \Drupal\am_net\PersonManager
   */
  protected $personManager;

  /**
   * The product storage.
   *
   * @var \Drupal\commerce\CommerceContentEntityStorage
   */
  protected $productStorage;

  /**
   * The event session storage.
   *
   * @var \Drupal\vscpa_commerce\EventSessionStorageInterface
   */
  protected $sessionStorage;

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The product variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorageInterface
   */
  protected $variationStorage;

  /**
   * The document manager.
   *
   * @var \Drupal\am_net_cpe\DocumentManagerInterface
   */
  protected $documentManager;

  /**
   * AmNetEventsManager constructor.
   *
   * @param \Drupal\am_net\AssociationManagementClient $client
   *   The AM.net REST API client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The 'am_net_cpe' logger channel.
   * @param \Drupal\am_net_cpe\DocumentManagerInterface $document_manager
   *   The document manager.
   * @param \Drupal\am_net\PersonManager $person_manager
   *   The AM.net person manager.
   * @param \Drupal\am_net_firms\FirmManager $firm_manager
   *   The AM.net firm manager.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(AssociationManagementClient $client, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger, DocumentManagerInterface $document_manager, PersonManager $person_manager, FirmManager $firm_manager, CurrentStoreInterface $current_store) {
    $this->client = $client;
    $this->currentStore = $current_store;
    $this->firmManager = $firm_manager;
    $this->logger = $logger;
    $this->productStorage = $entity_type_manager->getStorage('commerce_product');
    $this->paragraphStorage = $entity_type_manager->getStorage('paragraph');
    $this->personManager = $person_manager;
    $this->sessionStorage = $entity_type_manager->getStorage('event_session');
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
    $this->variationStorage = $entity_type_manager->getStorage('commerce_product_variation');
    $this->documentManager = $document_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function syncAmNetCpeEventProduct($event_code, $event_year) {
    try {
      $am_net_event = $this->getAmNetEvent($event_code, $event_year);
      $drupal_event = $this->getDrupalCpeEventProduct($event_code, $event_year, FALSE);
      if (!$drupal_event) {
        // Create event on Drupal.
        $event = $this->syncDrupalCpeEventProduct($am_net_event);
      }
      else {
        // Update event on Drupal.
        $event = $this->syncDrupalCpeEventProduct($am_net_event, $drupal_event);
        if ($event) {
          $event->setChangedTime(\Drupal::time()->getRequestTime());
          $event->save();
        }
        // Clear Local cache.
        $repository = \Drupal::service('am_net.entity.repository');
        $repository->clearEvenRegistrationsCache($event_code, $event_year);
      }
    }
    catch (AmNetRecordNotFoundException $e) {
      // UnPublish Drupal CPE event product if apply.
      $this->doUnpublishDrupalCpeEventProduct($e->getMessage(), $event_code, $event_year);
      throw $e;
    }
    return $event;
  }

  /**
   * {@inheritdoc}
   */
  public function syncAmNetCpeSelfStudyProduct($code, $allow_excluded = FALSE) {
    try {
      $am_net_product = $this->getAmNetProduct($code);
      $availability_code = $am_net_product['AvailabilityCode'] ?? NULL;
      $is_available = ($availability_code != 'N') && ($availability_code != 'T');
      $drupal_product = $this->getDrupalCpeSelfStudyProduct($code, FALSE, $allow_excluded);
      if (!$is_available) {
        // Un-publish the product if exits.
        if ($drupal_product) {
          $drupal_product->setPublished(FALSE);
          $drupal_product->set('field_exclude_from_web_catalog', TRUE);
          $drupal_product->save();
        }
        // Stop here.
        return NULL;
      }

      if (!$drupal_product) {
        // Skip or create a new product.
        $event = $this->syncDrupalCpeSelfStudyProduct($am_net_product);
      }
      else {
        // Update existing product on Drupal.
        $event = $this->syncDrupalCpeSelfStudyProduct($am_net_product, $drupal_product);
        $event->setChangedTime(\Drupal::time()->getRequestTime());
        $event->save();
      }
    }
    catch (AmNetRecordNotFoundException $e) {
      // UnPublish Drupal Self-Study product if apply.
      $this->doUnpublishDrupalStudyProduct($e->getMessage(), $code);
      throw $e;
    }

    return $event;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalCpeEventProduct($event_code, $event_year, $try_sync = TRUE) {
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_amnet_event_id', 'amnet_event_id');
    $query->fields('amnet_event_id', ['entity_id']);
    $query->condition('field_amnet_event_id_code', $event_code);
    $query->condition('field_amnet_event_id_year', $event_year);

    $entity_id = $query->execute()->fetchField();
    $event = NULL;
    if (!empty($entity_id)) {
      $event = $this->productStorage->load($entity_id);
    }
    // Try once to sync the event if it is not found.
    if (!$event && $try_sync) {
      if ($event = $this->syncAmNetCpeEventProduct($event_code, $event_year)) {
        return $event;
      }
    }
    return $event;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalCpeSelfStudyProduct($product_code, $try_sync = TRUE, $allow_excluded = FALSE) {
    $products = $this->productStorage->loadByProperties([
      'field_course_prodcode' => $product_code,
    ]);

    // Try once to sync the product if it is not found.
    if (!$products && $try_sync) {
      try {
        if ($product = $this->syncAmNetCpeSelfStudyProduct($product_code, $allow_excluded)) {
          return $product;
        }
      }
      catch (AmNetRecordExcludedException $e) {
        throw $e;
      }
      catch (AmNetRecordNotFoundException $e) {
        throw $e;
      }
    }

    return $products ? current($products) : NULL;
  }

  /**
   * Check if a given AM.net event record is an "InHouse" event.
   *
   * @param array $am_net_event
   *   An AM.net event record.
   *
   * @return bool
   *   TRUE if the event is "InHouse", otherwise FALSE.
   */
  protected function isInHouseEvent(array $am_net_event) {
    $acronym = $am_net_event['Code2'] ?? NULL;
    if (empty($acronym)) {
      return FALSE;
    }
    $acronym = trim($acronym);
    $acronym = strtolower($acronym);
    return ($acronym == 'inhouse');
  }

  /**
   * Check if a given AM.net event record is "Excluded" from the website.
   *
   * @param array $am_net_event
   *   An AM.net event record.
   *
   * @return bool
   *   TRUE if the event is "Excluded" from the website, otherwise FALSE.
   */
  protected function isExcludedEvent(array $am_net_event) {
    if ($this->isExcludeFromInternalCatalog($am_net_event)) {
      // Flag Checked: 'Do not send to the website'.
      return TRUE;
    }
    if ($this->isInHouseEvent($am_net_event)) {
      return FALSE;
    }
    return FALSE;
  }

  /**
   * Check if a given AM.net event record is "ExcludeFromInternalCatalog".
   *
   * @param array $am_net_event
   *   An AM.net event record.
   *
   * @return bool
   *   TRUE if the event is "ExcludeFromInternalCatalog", otherwise FALSE.
   */
  protected function isExcludeFromInternalCatalog(array $am_net_event) {
    $excluded = $am_net_event['ExcludeFromInternalCatalog'] ?? FALSE;
    return boolval($excluded);
  }

  /**
   * Check if a given AM.net event record is "ExcludeFromWebsite".
   *
   * @param array $am_net_event
   *   An AM.net event record.
   *
   * @return bool
   *   TRUE if the event is "ExcludeFromWebsite", otherwise FALSE.
   */
  protected function isExcludeFromWebsite(array $am_net_event) {
    $excluded = $am_net_event['ExcludeFromWebsite'] ?? FALSE;
    return boolval($excluded);
  }

  /**
   * Creates a new CPE Event product from an AM.net event record.
   *
   * @param array $am_net_event
   *   An AM.net event record.
   * @param \Drupal\commerce_product\Entity\ProductInterface|null $drupal_product
   *   The Drupal event product, if one already exists.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The new CPE event product.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   * @throws \Exception
   */
  protected function syncDrupalCpeEventProduct(array $am_net_event, $drupal_product = NULL) {
    // Check if the event should be excluded from the Website.
    if ($this->isExcludedEvent($am_net_event)) {
      if ($drupal_product) {
        // Un-publish the event.
        $drupal_product->set('status', FALSE);
        $drupal_product->set('field_exclude_from_web_catalog', TRUE);
        // Save the product.
        $drupal_product->save();
        // Return the processed product.
        return $drupal_product;
      }
      else {
        // Nothing to do.
        return NULL;
      }
    }
    // Proceed with the normal syncing.
    try {
      $event_dates = Helper::getDrupalEventDateTimeRanges($am_net_event, $this->eventTimezone);
      if (empty($event_dates)) {
        $event_dates = Helper::getDrupalEventDateRanges($am_net_event, $this->eventTimezone);
      }
      $event_registration_cutoff = Helper::getDrupalEventRegistrationCutoff($am_net_event, $this->eventTimezone);
      // Create a new product if needed.
      $new_product = (!$drupal_product);
      if ($new_product) {
        $drupal_product = Product::create([
          'type' => 'cpe_event',
          'stores' => [$this->currentStore->getStore()],
          'field_amnet_event_id' => [
            'code' => trim($am_net_event['Code']),
            'year' => trim($am_net_event['Year']),
          ],
        ]);
      }
      // Set or update base product fields.
      $drupal_product
        ->set('title', $am_net_event['Title'])
        ->set('field_cpe_type', $this->getDrupalCpeType($am_net_event))
        ->set('field_cpe_format', $this->getDrupalCpeFormat($am_net_event))
        ->set('field_field_of_interest', $this->getDrupalFieldOfInterestFieldItems($am_net_event))
        ->set('field_field_of_study', $this->getDrupalFieldOfStudyFieldItems($am_net_event))
        ->set('field_course_level', !empty($am_net_event['LevelCode']) ? $this->getDrupalCourseLevel($am_net_event['LevelCode']) : NULL)
        ->set('field_dates_times', Helper::convertDrupalEventDateTimeRangesToFieldItems($event_dates, $this->storageTimezone))
        ->set('field_event_expiry', $event_registration_cutoff ? $event_registration_cutoff->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => $this->storageTimezone]) : NULL)
        ->set('field_course_link', Helper::getDrupalCourseLink($am_net_event))
        ->set('field_event_external', Helper::getDrupalExternalRegistrationLink($am_net_event))
        ->set('rng_registration_type', Helper::getDrupalEventRegistrationType($am_net_event))
        ->set('field_leaders', array_map(function ($leader) {
          return ['target_id' => $leader->id()];
        }, $this->getDrupalSessionLeaders($am_net_event)))
        ->set('field_city_area', $this->getDrupalEventCityArea($am_net_event))
        ->set('field_firm', !empty($am_net_event['FacilityLink']) ? $this->getDrupalFirm($am_net_event['FacilityLink']) : NULL)
        ->set('field_course_vendors', $this->getDrupalEventVendorFieldItems($am_net_event))
        ->set('rng_status', 1)
        ->set('rng_registrants_duplicate', 0)
        ->set('rng_capacity', $am_net_event['MaximumRegistrations'] ?? 0)
        ->set('field_credit_hours', $am_net_event['CreditHours'] ?? NULL)
        ->set('field_alt_description', filter_var(trim($am_net_event['Description']), FILTER_SANITIZE_STRING))
        ->set('body', [
          'value' => $am_net_event['MarketingDescription'],
          'format' => 'basic_html',
        ])
        ->set('field_courses_objectives', [
          'value' => $am_net_event['Objectives'],
          'format' => 'basic_html',
        ])
        ->set('field_course_designedfor', [
          'value' => $am_net_event['DesignedFor'],
          'format' => 'basic_html',
        ])
        ->set('field_course_prereqs', [
          'value' => $am_net_event['Prerequisties'],
          'format' => 'basic_html',
        ])
        ->set('field_course_prep', [
          'value' => $am_net_event['AdvancedPreparation'],
          'format' => 'basic_html',
        ])
        ->set('status', TRUE);

      // Handle sync of the field Sponsors.
      $sponsors = $am_net_event['ConfirmationMemo'] ?? NULL;
      if (empty($sponsors)) {
        $drupal_product->set('field_sponsors', NULL);
      }
      else {
        $drupal_product->set('field_sponsors', [
          'value' => $sponsors,
          'format' => 'full_html',
        ]);
      }
      $is_self_study_event = isset($am_net_event['StatusCode']) && ($am_net_event['StatusCode'] == 'U');
      $drupal_product->set('field_search_index_is_self_study', $is_self_study_event);
      // Handle Field acronym.
      $acronym = $am_net_event['Code2'] ?? NULL;
      $drupal_product->set('field_acronym', $acronym);
      // Un-publish canceled events.
      $event_canceled = isset($am_net_event['StatusCode']) && ($am_net_event['StatusCode'] == 'C');
      if ($event_canceled) {
        $excluded = TRUE;
        $published = FALSE;
      }
      elseif ($this->isExcludeFromInternalCatalog($am_net_event)) {
        $excluded = TRUE;
        $published = FALSE;
      }
      elseif ($this->isExcludeFromWebsite($am_net_event)) {
        $excluded = TRUE;
        $published = TRUE;
      }
      else {
        $excluded = FALSE;
        $published = TRUE;
      }
      $code = trim($am_net_event['Code']);
      $year = trim($am_net_event['Year']);
      // Change the excluded and published flag for grouped events.
      if (!empty($acronym)) {
        $is_parent_event = EventHelper::isParentEventByAcronym($acronym, $code, $year);
        // Only the parent event from the group is not excluded.
        $excluded = !$is_parent_event;
        $published = TRUE;
      }
      $drupal_product->set('field_exclude_from_web_catalog', $excluded);
      $drupal_product->set('status', $published);
      // Field Division.
      $field_name = 'field_division';
      $property_key = 'DivisionCode';
      $property_value = $am_net_event[$property_key] ?? NULL;
      $drupal_product->set($field_name, $property_value);
      // Field CompanyCode.
      $company_code = NULL;
      if (!empty($am_net_event['CompanyCode'])) {
        $company_code = $am_net_event['CompanyCode'];
      }
      $drupal_product->set('field_company_code', $company_code);
      // Field Search Index - Yellow Book.
      $field_name = 'field_search_index_yellow_book';
      $property_key = 'YellowBook';
      $property_value = isset($am_net_event[$property_key]) ? boolval($am_net_event[$property_key]) : NULL;
      $drupal_product->set($field_name, $property_value);
      // Search Index - Attest & Compilation.
      $field_name = 'field_search_index_attest_compil';
      $property_key = 'AttestAndComp';
      $property_value = isset($am_net_event[$property_key]) ? boolval($am_net_event[$property_key]) : NULL;
      $drupal_product->set($field_name, $property_value);
      // Search Index - CFP.
      $field_name = 'field_search_index_cfp';
      $property_key = 'Cfp';
      $property_value = isset($am_net_event[$property_key]) ? boolval($am_net_event[$property_key]) : NULL;
      $drupal_product->set($field_name, $property_value);
      // Search Index - Free: Free would correspond to events that don't have
      // any fees (or sessions w/fees) in them.
      $drupal_product->set('field_search_index_free', Helper::isFreeEvent($am_net_event, $this->eventCurrencyCode));
      // Search Index - Early Registration.
      $drupal_product->set('field_search_index_early_regis', Helper::getDrupalEventEarlyRegistration($am_net_event));
      // Search Index - AICPA.
      $drupal_product->set('field_search_index_aicpa', Helper::getDrupalEventAicpa($am_net_event));
      // Related Events.
      $drupal_product->set('field_am_net_related_events', Helper::getAmNetRelatedEvents($am_net_event));
      // Field External Product Codes.
      $external_event_codes = [];
      $external_event_code_1 = $this->getAmNetFieldValue($am_net_event, 'ExternalEventCode1');
      if (!empty($external_event_code_1)) {
        $external_event_codes[] = $external_event_code_1;
      }
      $external_event_code_2 = $this->getAmNetFieldValue($am_net_event, 'ExternalEventCode2');
      if (!empty($external_event_code_2)) {
        $external_event_codes[] = $external_event_code_2;
      }
      if (!empty($external_event_codes)) {
        $drupal_product->field_external_event_codes = $external_event_codes;
      }
      else {
        // Empty the field.
        $drupal_product->set('field_external_event_codes', NULL);
      }
      // Handle Search User Defined Fields.
      $this->syncEventUserDefinedFields($drupal_product, $am_net_event);
      // Handle Search Index Keyword.
      $this->syncMarketingKeywords($drupal_product, $am_net_event);
      // @todo: Electronic Materials ([doc, docx, pdf, ppt, pptx, txt])
      // @todo: Related content (content entity reference[])
      // @todo: Handle "Simulcast" related product option/link
      // (ADMINISTRATIVE FIELDS)
      // @todo: Member Only Content
      // - Firm Admin Access (bool)
      // - Member Access (bool)
      // - Non-Member Access (bool)
      // - Public Access (bool)
      $this->syncEventVariations($drupal_product, $am_net_event);
      $this->syncEventSpecialFees($drupal_product, $am_net_event);
      $this->documentManager->syncEventDocuments($drupal_product);
      // Handle Rating info.
      $this->syncRatingInfo($drupal_product, $am_net_event);
      // Handle bundle items.
      $this->syncEventBundleItems($drupal_product, $am_net_event);
      // Update hot_trending filed.
      $hot_trending = $this->getEventBadgeClass($code, $year);
      if (!is_null($hot_trending)) {
        $drupal_product->set('field_trending_event', 1);
      }
      else {
        $drupal_product->set('field_trending_event', 0);
      }
      // Save node before nesting, grouping and checking paragraph sub-elements.
      $drupal_product->save();

      try {
        $this->syncDrupalSessions($drupal_product, $am_net_event);
      }
      catch (AmNetRecordNotFoundException $e) {
        $this->logger->error($e->getMessage());
      }
      catch (EntityStorageException $e) {
        $this->logger->error($e->getMessage());
      }
    }
    catch (AmNetRecordNotFoundException $e) {
      throw $e;
    }
    // Save the product.
    $drupal_product->save();
    // If this is a multi-event then update keywords.
    if (!empty($acronym)) {
      EventHelper::multiEventUpdateParentKeywords($acronym, $year);
    }
    // Return the processed product.
    return $drupal_product;
  }

  /**
   * Creates a new Self-study CPE product from an AM.net product record.
   *
   * @param array $am_net_product
   *   An AM.net product record.
   * @param \Drupal\commerce_product\Entity\ProductInterface|null $drupal_product
   *   The Drupal Self-study CPE product, if one already exists.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The new Self-study CPE product.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   * @throws \Exception
   */
  protected function syncDrupalCpeSelfStudyProduct(array $am_net_product, $drupal_product = NULL) {
    try {
      // Create a new product if needed.
      $new_product = (!$drupal_product);
      if ($new_product) {
        $drupal_product = Product::create([
          'type' => 'cpe_self_study',
          'stores' => [$this->currentStore->getStore()],
          'field_course_prodcode' => trim($am_net_product['ItemCode']),
        ]);
      }
      // Set or update base product fields.
      $drupal_product
        ->set('title', $am_net_product['Description'])
        ->set('field_cpe_type', $this->getDrupalCpeType($am_net_product, TRUE))
        ->set('field_cpe_format', $this->getDrupalCpeFormat($am_net_product, TRUE))
        ->set('field_field_of_interest', $this->getDrupalFieldOfInterestFieldItems($am_net_product))
        ->set('field_field_of_study', $this->getDrupalFieldOfStudyFieldItems($am_net_product))
        ->set('field_course_level', !empty($am_net_product['LevelCode']) ? $this->getDrupalCourseLevel($am_net_product['LevelCode']) : NULL)
        ->set('field_course_link', Helper::getDrupalCourseLink($am_net_product))
        ->set('field_event_external', Helper::getDrupalExternalRegistrationLink($am_net_product))
        ->set('rng_registration_type', 'self_study_online')
        ->set('field_course_vendors', !empty($am_net_product['Vendor']) ? [$this->getDrupalFirm($am_net_product['Vendor'])] : NULL)
        ->set('rng_status', 1)
        ->set('rng_registrants_duplicate', 0)
        ->set('rng_capacity', 0)
        ->set('field_search_index_is_self_study', TRUE)
        ->set('field_credits', $this->createDrupalCpeCredits($am_net_product))
        ->set('body', [
          'value' => $am_net_product['MarketingDescription'],
          'format' => 'basic_html',
        ])
        ->set('field_courses_objectives', [
          'value' => $am_net_product['MarketingObjectives'],
          'format' => 'basic_html',
        ])
        ->set('field_course_designedfor', [
          'value' => $am_net_product['MarketingDesign'],
          'format' => 'basic_html',
        ])
        ->set('field_course_prereqs', [
          'value' => $am_net_product['MarketingPrerequisites'],
          'format' => 'basic_html',
        ])
        ->set('field_course_prep', [
          'value' => $am_net_product['MarketingAdvancedPrep'],
          'format' => 'basic_html',
        ])
        ->set('status', 1);
      // Field CompanyCode.
      $company_code = NULL;
      if (!empty($am_net_product['CompanyCode'])) {
        $company_code = $am_net_product['CompanyCode'];
      }
      $drupal_product->set('field_company_code', $company_code);
      // Field Exclude From Web Catalog.
      $exclude_from_web_catalog = FALSE;
      if (!empty($am_net_product['ExcludeFromWebCatalog'])) {
        $exclude_from_web_catalog = $am_net_product['ExcludeFromWebCatalog'];
      }
      $exclude_from_web_sales = FALSE;
      if (!empty($am_net_product['ExcludeFromWebSales'])) {
        $exclude_from_web_sales = $am_net_product['ExcludeFromWebSales'];
      }
      $is_excluded = ($exclude_from_web_catalog || $exclude_from_web_sales);
      $drupal_product->set('field_exclude_from_web_catalog', $is_excluded);
      $drupal_product->set('status', !$is_excluded);
      // Field External Product Codes.
      $external_product_codes = [];
      $external_product_code_1 = $this->getAmNetFieldValue($am_net_product, 'ExternalProductCode1');
      if (!empty($external_product_code_1)) {
        $external_product_codes[] = $external_product_code_1;
      }
      $external_product_code_2 = $this->getAmNetFieldValue($am_net_product, 'ExternalProductCode2');
      if (!empty($external_product_code_2)) {
        $external_product_codes[] = $external_product_code_2;
      }
      if (!empty($external_product_codes)) {
        $drupal_product->field_external_product_codes = $external_product_codes;
      }
      else {
        // Empty the field.
        $drupal_product->set('field_external_product_codes', NULL);
      }
      // Handle Search Index Keyword.
      $this->syncMarketingKeywords($drupal_product, $am_net_product);
      // Field Division.
      $field_name = 'field_division';
      $property_key = 'DivisionCode';
      $property_value = $am_net_product[$property_key] ?? NULL;
      $drupal_product->set($field_name, $property_value);
      // @todo: Electronic Materials ([doc, docx, pdf, ppt, pptx, txt])
      // @todo: Related content (content entity reference[])
      // @todo: Handle "Simulcast" related product option/link
      // (ADMINISTRATIVE FIELDS)
      // @todo: Member Only Content
      // - Firm Admin Access (bool)
      // - Member Access (bool)
      // - Non-Member Access (bool)
      // - Public Access (bool)
      $this->syncSelfStudyVariation($drupal_product, $am_net_product);
    }
    catch (AmNetRecordNotFoundException $e) {
      throw $e;
    }

    $drupal_product->save();

    return Product::load($drupal_product->id());
  }

  /**
   * Adds all variations to a CPE Event product from an AM.net event record.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   A CPE Event product.
   * @param array $event
   *   An AM.net event record.
   *
   * @throws \Exception
   */
  protected function syncEventVariations(ProductInterface $product, array $event) {
    $dates = Helper::getDrupalEventDateTimeRanges($event, $this->eventTimezone);
    if (empty($dates)) {
      $dates = Helper::getDrupalEventDateRanges($event, $this->eventTimezone);
    }
    if (!empty($dates)) {
      $prices = Helper::getDrupalEventPrices($event, $this->eventCurrencyCode);
      $overrides = $prices['overrides'] ?? [];
      $one_day_fee = $prices['one_day_fee'];
      $early_bird_expiry = Helper::getDrupalEventEarlyBirdExpiration($event, $this->eventTimezone);
      $type = 'event_registration';

      // Add ALL-day variation.
      $first_day = current($dates)['start_date'];
      $last_day = end($dates)['end_date'];
      $count = count($dates);
      // ("Monday - Tuesday" if multi-day, or "Monday" if single-day).
      $day_range = ($count > 1) ? "{$first_day->format('l')} - {$last_day->format('l')}" : $first_day->format('l');
      $all_day_label = "{$count} Day Registration ({$day_range})";
      $sku = Helper::getDrupalEventVariationSku($event, 'all');
      $is_multi_day = ($count > 1) ? FALSE : $one_day_fee;
      $this->syncEventProductVariation($product, $all_day_label, $type, $sku, $first_day, $last_day, $early_bird_expiry, $prices['multi_day'], $is_multi_day, $overrides);

      if ($count > 1 && !empty($prices['single_day'])) {
        // Add each single day variation.
        $day = 1;
        foreach ($dates as $date) {
          $start_date = $date['start_date'];
          $end_date = $date['end_date'];
          $single_day_label = "1 Day Registration ({$start_date->format('l')})";
          $single_day_sku = Helper::getDrupalEventVariationSku($event, $day);
          $this->syncEventProductVariation($product, $single_day_label, $type, $single_day_sku, $start_date, $end_date, $early_bird_expiry, $prices['single_day'], $one_day_fee, $overrides);
          $day++;
        }
      }
    }
  }

  /**
   * Sync Rating info.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   A CPE Event product.
   * @param array $event
   *   An AM.net event record.
   */
  protected function syncRatingInfo(ProductInterface &$product, array $event) {
    // Check if this event has rating information in the UDF: RatingOverall.
    $rating_overall = $event['RatingOverall'] ?? NULL;
    $has_valid_rating_overall = is_numeric($rating_overall) && ($rating_overall > 0);
    $field_name = 'field_search_index_rating';
    if ($has_valid_rating_overall) {
      $product->set($field_name, $rating_overall);
      return;
    }
    // Check the rating overall from the parent event.
    $udfs = $event['UserDefinedFields'] ?? NULL;
    if (empty($udfs)) {
      $product->set($field_name, NULL);
      return;
    }
    $source_for_rating_id = NULL;
    foreach ($udfs as $delta => $fields) {
      $field = $fields['Field'] ?? NULL;
      if ($field != 'ud9') {
        continue;
      }
      $value = $fields['Value'] ?? NULL;
      if (empty($value)) {
        continue;
      }
      $source_for_rating_id = $value;
      break;
    }
    if (empty($source_for_rating_id)) {
      $product->set($field_name, NULL);
      return;
    }
    $items = explode(' ', $source_for_rating_id);
    $items = array_filter($items);
    $event_code = current($items);
    $event_year = end($items);
    $rating_overall = EventHelper::getEventRatingInfo($event_code, $event_year);
    $product->set($field_name, $rating_overall);
  }

  /**
   * Sync event - Bundle Items.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   A CPE Event product.
   * @param array $event
   *   An AM.net event record.
   */
  protected function syncEventBundleItems(ProductInterface &$product, array $event) {
    $items = $event['BundleItems'] ?? [];
    if (empty($items)) {
      $product->set('field_bundle_items', []);
      $product->set('field_search_index_is_bundle', FALSE);
      // Stop here.
      return;
    }
    $product->set('field_search_index_is_bundle', TRUE);
    $bundle_items = [];
    $is_on_demand = FALSE;
    foreach ($items as $delta => $item) {
      $event_code = $item['EventCode'] ?? NULL;
      $event_year = $item['EventYear'] ?? NULL;
      if (empty($event_code) || empty($event_year)) {
        continue;
      }
      $event_code = trim($event_code);
      $event_year = trim($event_year);
      $drupal_event = $this->getDrupalCpeEventProduct($event_code, $event_year, TRUE);
      if (!$drupal_event) {
        continue;
      }
      $bundle_items[] = ['target_id' => $drupal_event->id()];
      // Check if the bundle contains "Individual on-demand" events.
      if (!$is_on_demand) {
        $division_value = $drupal_event->get('field_division')->getString();
        $is_on_demand = am_net_is_self_study($division_value);
      }
    }
    // Set the bundle items.
    $product->set('field_bundle_items', $bundle_items);
    // "Format" filter should recognize self-study events: Include bundles
    // in the search results IF they happen to include self-study events.
    $product->set('field_search_index_on_demand', $is_on_demand);
    // Configure field_event_expiry.
    $date = EventHelper::getDrupalBundleEventExpirationDate($event, $this->eventTimezone);
    if (!$date) {
      $expiration_date = NULL;
    }
    else {
      $expiration_date = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => $this->storageTimezone]);
    }
    $product->set('field_event_expiry', $expiration_date);
    // Configure variations.
    $date = Helper::getDrupalBundleEventDateTimeRange($event, $this->eventTimezone);
    if (!empty($date)) {
      $prices = Helper::getDrupalEventPrices($event, $this->eventCurrencyCode);
      $first_day = $date['start_date'];
      $last_day = $date['end_date'];
      $all_day_label = "All Days Registration";
      $type = 'event_registration';
      $sku = Helper::getDrupalEventVariationSku($event, 'all');
      $early_bird_expiry = Helper::getDrupalEventEarlyBirdExpiration($event, $this->eventTimezone);
      try {
        $this->syncEventProductVariation($product, $all_day_label, $type, $sku, $first_day, $last_day, $early_bird_expiry, $prices['multi_day'], FALSE);
      }
      catch (EntityStorageException $e) {
        // Unlink the variations tie to this bundle event product.
        $product->setVariations([]);
      }
    }
    else {
      // Unlink the variations tie to this bundle event product.
      $product->setVariations([]);
    }
  }

  /**
   * Sync Marketing Keywords.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   A CPE Event product.
   * @param array $event
   *   An AM.net event record.
   */
  protected function syncMarketingKeywords(ProductInterface &$product, array $event) {
    if (!isset($event['MarketingKeywords'])) {
      return;
    }
    $value = $event['MarketingKeywords'] ?? NULL;
    $product->set('field_search_keywords', strip_tags($value));
  }

  /**
   * Adds all Special Fees to a CPE Event product from an AM.net event record.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   A CPE Event product.
   * @param array $event
   *   An AM.net event record.
   */
  protected function syncEventUserDefinedFields(ProductInterface &$product, array $event) {
    if (!isset($event['UserDefinedFields'])) {
      return;
    }
    // Handle Search Index Keyword.
    $keywords = NULL;
    foreach ($event['UserDefinedFields'] as $delta => $fields) {
      $field = $fields['Field'] ?? NULL;
      if ($field != 'ud8') {
        continue;
      }
      $value = $fields['Value'] ?? NULL;
      if (empty($value)) {
        continue;
      }
      $keywords = $value;
      break;
    }
    if (empty($keywords)) {
      $product->set('field_search_index_keyword', NULL);
      return;
    }
    $terms = [];
    $items = explode('|', $keywords);
    foreach ($items as $item) {
      $label = trim($item);
      $token_name = $this->tokenizeName($label);
      if (empty($label) || empty($token_name)) {
        continue;
      }
      $target_id = $this->getMarketingKeywordTidByTokenName($label, $token_name);
      if (empty($target_id)) {
        continue;
      }
      $terms[] = ['target_id' => $target_id];
    }
    $product->set('field_search_index_keyword', $terms);
  }

  /**
   * Get the AM.net ID related to a firm term ID.
   *
   * @param string $label
   *   The Keyword Label.
   * @param string $token_name
   *   The Token Name.
   *
   * @return string|null
   *   The firm AM.net ID otherwise NULL.
   */
  public function getMarketingKeywordTidByTokenName($label = NULL, $token_name = NULL) {
    if (empty($token_name)) {
      return NULL;
    }
    $term_id = $this->findMarketingKeywordTidByTokenName($token_name);
    if (empty($term_id)) {
      // Create the term.
      $term_id = $this->addMarketingKeyword($label, $token_name);
    }
    return $term_id;
  }

  /**
   * Add Marketing Keyword.
   *
   * @param string $label
   *   The Keyword Label.
   * @param string $token_name
   *   The Token Name.
   *
   * @return string|null
   *   The Keyword TID.
   */
  public function addMarketingKeyword($label, $token_name) {
    $term = Term::create([
      'parent' => [],
      'name' => $label,
      'vid' => 'marketing_keywords',
      'field_tokenized_name' => $token_name,
    ]);
    try {
      $term->save();
    }
    catch (EntityStorageException $e) {
      return NULL;
    }
    return $term->id();
  }

  /**
   * Find Marketing Keyword tid by Token Name.
   *
   * @param string $token_name
   *   The Token Name.
   *
   * @return string|null
   *   The firm AM.net ID otherwise NULL.
   */
  public function findMarketingKeywordTidByTokenName($token_name = NULL) {
    $database = \Drupal::database();
    $query = $database->select('taxonomy_term__field_tokenized_name', 'field_tokenized');
    $query->fields('field_tokenized', ['entity_id']);
    $query->condition('field_tokenized_name_value', $token_name);
    $result = $query->execute();
    return $result->fetchField(0);
  }

  /**
   * Transform a given string into a machine name.
   *
   * @param string $value
   *   The value to be transformed.
   *
   * @return string|null
   *   The newly transformed value.
   */
  public function tokenizeName($value = NULL) {
    if (empty($value)) {
      return NULL;
    }
    $new_value = strtolower($value);
    $new_value = preg_replace('/[^a-z0-9_]+/', '_', $new_value);
    return preg_replace('/_+/', '_', $new_value);
  }

  /**
   * Adds all Special Fees to a CPE Event product from an AM.net event record.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   A CPE Event product.
   * @param array $event
   *   An AM.net event record.
   */
  protected function syncEventSpecialFees(ProductInterface &$product, array $event) {
    if (!isset($event['Fees'])) {
      return;
    }
    $adjustments = [];
    foreach ($event['Fees'] as $fee) {
      if ($fee['Ty2'] == 'SV') {
        $adjustments[] = $fee;
      }
      elseif ($fee['Ty2'] == 'AD') {
        // Description: AICPA Discount.
        $adjustments[] = $fee;
      }
      elseif ($fee['Ty2'] == 'DP') {
        // Description: 'XYZ% off goodwill discount'.
        $adjustments[] = $fee;
      }
    }
    $adjustments = !empty($adjustments) ? json_encode($adjustments) : $adjustments;
    $product->field_am_net_adjustment = $adjustments;
  }

  /**
   * Adds all variations to a Self-study CPE product from an AM.net product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   A Self-study CPE product.
   * @param array $am_net_product
   *   An AM.net product record.
   *
   * @throws \Exception
   */
  protected function syncSelfStudyVariation(ProductInterface $product, array $am_net_product) {
    if (!$variation = $this->variationStorage->loadBySku(trim($am_net_product['ItemCode']))) {
      $variation = $this->variationStorage->create([
        'type' => 'self_study_registration',
        'sku' => trim($am_net_product['ItemCode']),
      ]);
    }

    $variation
      ->set('title', $product->label())
      ->set('field_price_member', new Price((string) $am_net_product['MemberPrice'], $this->eventCurrencyCode))
      ->set('price', new Price((string) $am_net_product['NonmemberPrice'], $this->eventCurrencyCode))
      ->save();

    if (!$product->hasVariation($variation)) {
      $product->addVariation($variation);
    }
  }

  /**
   * Adds or updates an event product variation of a product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   A CPE Event product.
   * @param string $title
   *   The title to use for this variation.
   * @param string $type
   *   The product variation type.
   * @param string $sku
   *   The product variation SKU.
   * @param \Drupal\Core\DateTime\DrupalDateTime $start_date
   *   The start date/time to which this variation applies.
   * @param \Drupal\Core\DateTime\DrupalDateTime $end_date
   *   The end date/time to which this variation applies.
   * @param \Drupal\Core\DateTime\DrupalDateTime|null $early_bird_expiry
   *   The early bird expiration date, if one exists.
   * @param array $pricing
   *   The pricing values.
   * @param bool $one_day_fee
   *   The one day fee.
   * @param array $overrides
   *   List of field to override.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function syncEventProductVariation(ProductInterface $product, $title, $type, $sku, DrupalDateTime $start_date, DrupalDateTime $end_date, $early_bird_expiry, array $pricing, $one_day_fee = NULL, array $overrides = []) {
    if (!$variation = $this->variationStorage->loadBySku($sku)) {
      $variation = $this->variationStorage->create([
        'type' => $type,
        'sku' => $sku,
      ]);
    }

    $variation
      ->set('title', $title)
      ->set('field_applies_to_date_range', [
        'value' => $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => $this->storageTimezone]),
        'end_value' => $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => $this->storageTimezone]),
      ])
      ->set('field_price_member_early', $pricing['price_member_early'])
      ->set('field_price_member', $pricing['price_member'])
      ->set('field_price_early', $pricing['price_early'])
      ->set('price', $pricing['price']);

    $override_fields = $overrides['fields'] ?? NULL;
    if (!empty($override_fields)) {
      foreach ($override_fields as $field_name => $field_value) {
        $variation->set($field_name, $field_value);
      }
    }

    $variation->set('field_one_day_registration', $one_day_fee);

    if ($early_bird_expiry) {
      $variation->set('field_early_bird_expiry', $early_bird_expiry->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => $this->storageTimezone]));
    }

    $variation->save();

    if (!$product->hasVariation($variation)) {
      $product->addVariation($variation);
    }
  }

  /**
   * Adds and/or updates event sessions to a CPE Event as nested paragraphs.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   A CPE Event product entity.
   * @param array $event
   *   An AM.net event record.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  protected function syncDrupalSessions(ProductInterface $product, array $event) {
    foreach ($event['Sessions'] as $session) {
      try {
        if ($event_session = $this->sessionStorage->loadByProperties([
          'field_session_code' => $session['SessionCode'],
          'field_session_cpe_parent' => $product->id(),
        ])) {
          $event_session = current($event_session);
        }
        else {
          $event_session = $this->sessionStorage->create([
            'type' => 'default',
          ]);
        }
        $update = !$event_session->isNew();
        $event_session_start = Helper::getDrupalSessionTime($session, $this->eventTimezone, 'start');
        $event_session_end = Helper::getDrupalSessionTime($session, $this->eventTimezone, 'end');
        /** @var \Drupal\vscpa_commerce\Entity\EventSessionInterface $event_session */
        $event_session
          ->set('name', $session['Description'])
          ->set('type', 'default')
          ->set('field_amnet_sort_sequence', $session['SortSequence'])
          ->set('field_credits', $this->createDrupalCpeCredits($session))
          // @todo: Get from AM.net
          // 'field_electronic_materials' => '',
          // @todo: Get from AM.net
          // (Out of scope for first release).
          // 'field_leader_edit_desc' => FALSE,
          // @todo: Get from AM.net
          // (Out of scope for first release).
          // 'field_leader_edit_title' => FALSE,
          ->set('field_marketing_copy', [
            'value' => $session['MarketingCopy'],
            'format' => 'basic_html',
          ])
          // @todo Get from AM.net
          // (Out of scope for first release).
          // 'field_session_administrator' => [],
          ->set('field_session_code', $session['SessionCode'])
          ->set('field_session_day', $session['Day'])
          ->set('field_session_excluded_catalog', $session['ExcludeFromWebsite'])
          ->set('field_session_general', ($session['SessionTypeCode'] === 'Y'))
          ->set('field_session_guest_only', $session['GuestsOnly'])
          ->set('field_session_registrants_only', $session['RegistrantsOnly'])
          ->set('field_session_status', $session['StatusCode'] === 'S' ? 'scheduled' : 'cancelled')
          ->set('field_session_time', [
            'value' => $event_session_start->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => $this->storageTimezone]),
            'end_value' => $event_session_end->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => $this->storageTimezone]),
          ])
          ->set('field_session_track', $session['SessionTrackCode'] ?? NULL)
          ->set('field_sessions_concurrent', (array) $session['ConcurrentSesssions'])
          ->set('field_speakers', array_map(function ($speaker) {
            return ['target_id' => $speaker->id()];
          }, $this->getDrupalSessionLeaders($session)))
          ->set('rng_capacity', $session['MaxRegistrations'] == 0 ? -1 : $session['MaxRegistrations'])
          ->set('rng_registrants_duplicate', FALSE)
          ->set('rng_registrants_maximum', 1)
          ->set('rng_registrants_minimum', 1)
          ->set('rng_registration_groups', [])
          // If session description includes 'cast' (webcast/simulcast)
          // *guess* that it is an online session.
          // (There is no other way to tell if the session is online).
          ->set('rng_registration_type', strpos($session['Description'], 'cast') !== FALSE ? 'session_online' : 'session_in_person')
          ->set('rng_reply_to', NULL)
          ->set('rng_status', TRUE)
          ->set('field_session_cpe_parent', $product->id());

        // Sync session registration product, if paid session.
        if (!empty($session['MemberFee']) || !empty($session['GuestFee'])) {
          $this->attachDrupalSessionProduct($event_session, $session);
        }
        else {
          // Clear products linked to this event session.
          $event_session->set('field_session_product', NULL);
        }

        $event_session->save();

        // Add new sessions to timeslots.
        if (!$update) {
          $timeslot = $this->getDrupalSessionTimeslot($event_session, $product, $session);
          $timeslot->get('field_sessions')->appendItem($event_session);
          $timeslot->save();
          $timeslot_group = $this->getDrupalSessionTimeslotGroup($event_session, $product);
          if (!$this->timeslotGroupHasTimeslot($timeslot_group, $timeslot)) {
            $timeslot_group->get('field_timeslots')->appendItem($timeslot);
            $timeslot_group->save();
            if (!$this->eventHasTimeslotGroup($product, $timeslot_group)) {
              $product->get('field_event_timeslot_groups')->appendItem($timeslot_group);
            }
          }
        }
      }
      catch (AmNetRecordNotFoundException $e) {
        throw $e;
      }
      catch (EntityStorageException $e) {
        throw $e;
      }
      $this->sortEventTimeslotGroups($product);
    }
  }

  /**
   * Attaches a session registration product to an event session.
   *
   * @param \Drupal\vscpa_commerce\Entity\EventSessionInterface $event_session
   *   The event session entity.
   * @param array $session
   *   The AM.net event session record.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function attachDrupalSessionProduct(EventSessionInterface &$event_session, array $session) {
    // Check event session product relations.
    $items = $event_session->get('field_session_product')->getValue();
    $current_values = [];
    if (!empty($items)) {
      foreach ($items as $delta => $item) {
        $target_id = $item['target_id'] ?? NULL;
        if (!empty($target_id) && $this->productExist($target_id)) {
          $current_values[$target_id] = $target_id;
        }
      }
    }
    // Update session.
    $session_code = trim($session['SessionCode']);
    $event_code = trim($session['EventCode']);
    $event_year = trim($session['EventYear']);
    $member_fee = new Price((string) $session['MemberFee'], $this->eventCurrencyCode);
    $non_member_fee = new Price((string) $session['NonmemberFee'], $this->eventCurrencyCode);
    $guest_fee = !empty($session['GuestFee']) ? new Price((string) $session['GuestFee'], $this->eventCurrencyCode) : NULL;
    $addl_guest_fee = !empty($session['AdditionalGuestFee']) ? new Price((string) $session['AdditionalGuestFee'], $this->eventCurrencyCode) : NULL;

    $session_product_variation_sku = "{$event_year}-{$event_code}-S-{$session_code}";
    if ($session_product_variation = $this->variationStorage->loadBySku($session_product_variation_sku)) {
      $update = TRUE;
    }
    else {
      $update = FALSE;
      $session_product_variation = $this->variationStorage->create([
        'type' => 'session_registration',
      ]);
    }

    $session_product_variation
      ->set('title', $session['Description'])
      ->set('price', $member_fee)
      ->set('field_price_nonmember', $non_member_fee)
      ->set('field_price_guest', $guest_fee)
      ->set('field_price_guest_addl', $addl_guest_fee)
      ->set('sku', $session_product_variation_sku)
      ->save();
    // Update/Add Session product.
    if ($update) {
      $session_product = $session_product_variation->getProduct();
      $session_product
        ->set('title', $session['Description'])
        ->save();
    }
    else {
      $session_product = Product::create([
        'type' => 'session_registration',
        'title' => $session['Description'],
        'variations' => [$session_product_variation],
      ]);
      $session_product->save();
    }
    // Update session References.
    $product_id = $session_product->id();
    if (!isset($current_values[$product_id])) {
      $current_values[$product_id] = $product_id;
    }
    $event_session->field_session_product = $current_values;
  }

  /**
   * Resets timeslot groups in a CPE Event product after sorting them.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $event
   *   The event product.
   * @param bool $recurse
   *   TRUE if the sessions in the timeslot should be sorted.
   */
  protected function sortEventTimeslotGroups(ProductInterface $event, $recurse = TRUE) {
    $timeslot_groups = $event->get('field_event_timeslot_groups')->referencedEntities();
    uasort($timeslot_groups, [$this, 'sortTimeslotGroups']);
    if ($recurse) {
      foreach ($timeslot_groups as &$group) {
        try {
          $this->sortTimeslotGroupTimeslots($group, $recurse);
        }
        catch (EntityStorageException $e) {
          $this->logger->warning($e->getMessage());
        }
      }
    }
    $event->set('field_event_timeslot_groups', $timeslot_groups);
  }

  /**
   * Resets timeslots in a timeslot group after sorting them.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $timeslot_group
   *   A timeslot group.
   * @param bool $recurse
   *   TRUE if sessions in each timeslot should be sorted.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function sortTimeslotGroupTimeslots(ParagraphInterface $timeslot_group, $recurse = TRUE) {
    $timeslots = $timeslot_group->get('field_timeslots')->referencedEntities();
    uasort($timeslots, [$this, 'sortTimeslots']);
    if ($recurse) {
      foreach ($timeslots as &$timeslot) {
        try {
          $this->sortTimeslotSessions($timeslot);
        }
        catch (EntityStorageException $e) {
          $this->logger->warning($e->getMessage());
        }
      }
    }
    $timeslot_group->set('field_timeslots', $timeslots);
    $timeslot_group->save();
  }

  /**
   * Resets sessions in a timeslot after sorting them.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $timeslot
   *   A timeslot paragraph.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function sortTimeslotSessions(ParagraphInterface $timeslot) {
    $timeslots = $timeslot->get('field_sessions')->referencedEntities();
    uasort($timeslots, [$this, 'sortSessions']);
    $timeslot->set('field_sessions', $timeslots);
    $timeslot->save();
  }

  /**
   * Compares the start timestamps of two timeslot groups.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $a
   *   The first timeslot group.
   * @param \Drupal\paragraphs\ParagraphInterface $b
   *   The second timeslot group.
   *
   * @return int
   *   0, -1, or 1.
   */
  protected function sortTimeslotGroups(ParagraphInterface $a, ParagraphInterface $b) {
    /** @var \Drupal\Core\Datetime\DrupalDateTime $a_start */
    /** @var \Drupal\Core\Datetime\DrupalDateTime $b_start */
    $a_start = $a->get('field_timeslot_group_time')->start_date ?? NULL;
    if (!$a_start) {
      return -1;
    }
    $b_start = $b->get('field_timeslot_group_time')->start_date ?? NULL;
    if (!$b_start) {
      return 1;
    }
    if ($a_start->getTimestamp() == $b_start->getTimestamp()) {
      return 0;
    }

    return ($a_start->getTimestamp() < $b_start->getTimestamp()) ? -1 : 1;
  }

  /**
   * Compares the start timestamps of two timeslots.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $a
   *   The first timeslot.
   * @param \Drupal\paragraphs\ParagraphInterface $b
   *   The second timeslot.
   *
   * @return int
   *   0, -1, or 1.
   */
  protected function sortTimeslots(ParagraphInterface $a, ParagraphInterface $b) {
    /** @var \Drupal\Core\Datetime\DrupalDateTime $a_start */
    /** @var \Drupal\Core\Datetime\DrupalDateTime $b_start */
    $a_start = $a->get('field_timeslot_time')->start_date ?? NULL;
    if (!$a_start) {
      return -1;
    }
    $b_start = $b->get('field_timeslot_time')->start_date ?? NULL;
    if (!$b_start) {
      return 1;
    }
    if ($a_start->getTimestamp() == $b_start->getTimestamp()) {
      return 0;
    }

    return ($a_start->getTimestamp() < $b_start->getTimestamp()) ? -1 : 1;
  }

  /**
   * Compares the sort sequence or session code of two sessions.
   *
   * @param \Drupal\vscpa_commerce\Entity\EventSessionInterface $a
   *   The first event session.
   * @param \Drupal\vscpa_commerce\Entity\EventSessionInterface $b
   *   The second event session.
   *
   * @return int
   *   0, -1, or 1.
   */
  protected function sortSessions(EventSessionInterface $a, EventSessionInterface $b) {
    if ($a->get('field_amnet_sort_sequence')->value || $b->get('field_amnet_sort_sequence')->value) {
      $a_sequence = $a->get('field_amnet_sort_sequence')->value;
      $b_sequence = $b->get('field_amnet_sort_sequence')->value;
      if ($a_sequence === $b_sequence) {
        return 0;
      }

      return ($a_sequence < $b_sequence) ? -1 : 1;
    }

    return strcmp($a->get('field_session_code')->value, $b->get('field_session_code')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function timeslotGroupHasTimeslot(ParagraphInterface $timeslot_group, ParagraphInterface $timeslot) {
    return $this->getTimeslotGroupTimeslotIndex($timeslot_group, $timeslot) !== FALSE;
  }

  /**
   * Gets the index of a timeslot in a timeslot group.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $timeslot_group
   *   The timeslot group paragraph.
   * @param \Drupal\paragraphs\ParagraphInterface $timeslot
   *   The timeslot paragraph.
   *
   * @return int|bool
   *   The index of the timeslot group, or FALSE if not found.
   */
  protected function getTimeslotGroupTimeslotIndex(ParagraphInterface $timeslot_group, ParagraphInterface $timeslot) {
    $values = $timeslot_group->get('field_timeslots')->getValue();
    $timeslot_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($timeslot->id(), $timeslot_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function eventHasTimeslotGroup(ProductInterface $product, ParagraphInterface $timeslot_group) {
    return $this->getEventTimeslotGroupIndex($product, $timeslot_group) !== FALSE;
  }

  /**
   * Gets the index of a timeslot group in an event product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The CPE Event product.
   * @param \Drupal\paragraphs\ParagraphInterface $timeslot_group
   *   The timeslot group paragraph.
   *
   * @return int|bool
   *   The index of the timeslot group, or FALSE if not found.
   */
  protected function getEventTimeslotGroupIndex(ProductInterface $product, ParagraphInterface $timeslot_group) {
    $values = $product->get('field_event_timeslot_groups')->getValue();
    $timeslot_group_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($timeslot_group->id(), $timeslot_group_ids);
  }

  /**
   * Creates credit paragraphs for the given CPE product or session.
   *
   * @param array $cpe
   *   An AM.net CPE product or event session record.
   *
   * @return array
   *   An array of 'cpe_credit' paragraph entities.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  protected function createDrupalCpeCredits(array $cpe) {
    $credit_paragraphs = [];
    foreach ($cpe['Credits'] as $credit) {
      try {
        $credit_type = $this->getDrupalCreditType($credit['CreditCategoryCode']);
        $credit = Paragraph::create([
          'type' => 'cpe_credit',
          'field_credit_amount' => (int) $credit['Credits'],
          'field_credit_type' => $credit_type,
        ]);
        $credit->save();
        $credit_paragraphs[] = $credit;
      }
      catch (AmNetRecordNotFoundException $e) {
        throw $e;
      }
    }

    return $credit_paragraphs;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetCourseLevels() {
    $levels = $this->client->get('/Lists', [
      'listKey' => 'EVLV',
    ]);
    if ($levels->hasError()) {
      throw new AmNetRecordNotFoundException($levels->getErrorMessage());
    }
    if ($result = $levels->getResult()) {
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetCreditCategories() {
    $categories = $this->client->get('/Lists', [
      'listKey' => 'EVCC',
    ]);
    if ($categories->hasError()) {
      throw new AmNetRecordNotFoundException($categories->getErrorMessage());
    }
    if ($result = $categories->getResult()) {
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetCityAreas() {
    $categories = $this->client->get('/Lists', [
      'listKey' => 'EVLO',
    ]);
    if ($categories->hasError()) {
      throw new AmNetRecordNotFoundException($categories->getErrorMessage());
    }
    if ($result = $categories->getResult()) {
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetCpeFormats() {
    return [
      // 'LI' (Live) is a Drupal-only format, used for CPE Event products.
      [
        'Code' => 'LI',
        'Description' => 'Live',
      ],
      // These codes come from AM.net and are only for CPE Self-study products.
      [
        'Code' => 'WB',
        'Description' => 'Webinar',
      ],
      [
        'Code' => 'WE',
        'Description' => 'Webcast',
      ],
      [
        'Code' => 'ON',
        'Description' => 'Online',
      ],
      [
        'Code' => 'DO',
        'Description' => 'Download',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetCpeTypes() {
    return [
      [
        'Code' => 'C',
        'Description' => 'Conference',
      ],
      [
        'Code' => 'S',
        'Description' => 'Course',
      ],
      [
        'Code' => 'O',
        'Description' => 'On-Demand',
      ],
      [
        'Code' => 'W',
        'Description' => 'Webinar',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetFieldsOfInterest() {
    $interests = $this->client->get('/Lists', [
      'listKey' => 'NAFI',
    ]);
    if ($interests->hasError()) {
      throw new AmNetRecordNotFoundException($interests->getErrorMessage());
    }
    if ($result = $interests->getResult()) {
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetFieldsOfStudy() {
    $interests = $this->client->get('/Lists', [
      'listKey' => 'EVAR',
    ]);
    if ($interests->hasError()) {
      throw new AmNetRecordNotFoundException($interests->getErrorMessage());
    }
    if ($result = $interests->getResult()) {
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetEvent($event_code, $event_year) {
    $event = $this->client->get('/Event', [
      'yr' => $event_year,
      'Code' => $event_code,
    ]);
    if ($event->hasError()) {
      throw new AmNetRecordNotFoundException($event->getErrorMessage());
    }
    if ($result = $event->getResult()) {
      return $result;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventInfo($event_code, $event_year) {
    $stored_sync_entities = &drupal_static(__METHOD__, []);
    $key = "{$event_code}.{$event_year}";
    if (!isset($stored_sync_entities[$key])) {
      // Get the event from the API.
      $event = $this->getAmNetEvent($event_code, $event_year);
      $stored_sync_entities[$key] = $event;
    }
    return $stored_sync_entities[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function getEventRegistrationsInfo($event_code, $event_year) {
    try {
      $event = $this->getEventInfo($event_code, $event_year);
    }
    catch (\Exception $e) {
      return NULL;
    }
    if (empty($event)) {
      return NULL;
    }
    $keys = [
      'MinimumRegistrations',
      'MaximumRegistrations',
      'CurrentRegistrations',
      'BudgetedRegistrations',
    ];
    $info = NULL;
    foreach ($keys as $delta => $key) {
      if (isset($event[$key])) {
        $info[$key] = $event[$key];
      }
    }
    return $info;
  }

  /**
   * Get the trend badge class associated with an event product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product interface.
   *
   * @return string|null
   *   The badge class, otherwise NULL.
   */
  public function getEventBadgeClassByProduct(ProductInterface $product) {
    if (!$product) {
      return NULL;
    }
    $field_name = 'field_amnet_event_id';
    if (!$product->hasField($field_name)) {
      return NULL;
    }
    $am_net_event_id = $product->get($field_name)->getValue();
    $am_net_event_id = is_array($am_net_event_id) ? current($am_net_event_id) : NULL;
    $event_code = $am_net_event_id['code'] ?? NULL;
    $event_year = $am_net_event_id['year'] ?? NULL;
    if (empty($event_code) || empty($event_year)) {
      return NULL;
    }
    return $this->getEventBadgeClass($event_code, $event_year);
  }

  /**
   * Get the trend badge class associated with an event.
   *
   * @param string $event_code
   *   The AM.net event code.
   * @param string $event_year
   *   The AM.net event year.
   *
   * @return string|null
   *   The badge class, otherwise NULL.
   */
  public function getEventBadgeClass($event_code, $event_year) {
    $info = $this->getEventRegistrationsInfo($event_code, $event_year);
    if (empty($info)) {
      return NULL;
    }
    $budgeted_registrations = $info['BudgetedRegistrations'] ?? 0;
    $budgeted_registrations = intval($budgeted_registrations);
    if ($budgeted_registrations == 0) {
      return NULL;
    }
    $current_registrations = $info['CurrentRegistrations'] ?? 0;
    $current_registrations = intval($current_registrations);
    if ($current_registrations == 0) {
      return NULL;
    }
    // Rule:40-59% of the budgeted registrations for "Trending" and 60%+
    // of budgeted registrations for "HOT".
    $percentage = ($current_registrations / $budgeted_registrations) * 100;
    if (($percentage >= 40) && ($percentage <= 59)) {
      return 'trending';
    }
    if ($percentage >= 60) {
      return 'hot';
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   */
  public function eventHasSeatsAvailable(ProductInterface $product = NULL) {
    if (!$product) {
      return NULL;
    }
    $field_name = 'field_amnet_event_id';
    if (!$product->hasField($field_name)) {
      return NULL;
    }
    $am_net_event_id = $product->get($field_name)->getValue();
    $am_net_event_id = is_array($am_net_event_id) ? current($am_net_event_id) : NULL;
    $event_code = $am_net_event_id['code'] ?? NULL;
    $event_year = $am_net_event_id['year'] ?? NULL;
    if (empty($event_code) || empty($event_year)) {
      return NULL;
    }
    $registrations_info = $this->getEventRegistrationsInfo($event_code, $event_year);
    if (empty($registrations_info)) {
      // Since that is possible that Drupal was to able to reach AM.net at this
      // point we are going to assume that there are seat available for this
      // Event registration.
      return TRUE;
    }
    $maximumRegistrations = $registrations_info['MaximumRegistrations'] ?? 0;
    $maximumRegistrations = intval($maximumRegistrations);
    $currentRegistrations = $registrations_info['CurrentRegistrations'] ?? 0;
    $currentRegistrations = intval($currentRegistrations);
    if (!($maximumRegistrations > 0)) {
      return TRUE;
    }
    return (($maximumRegistrations - $currentRegistrations) > 2);
  }

  /**
   * {@inheritdoc}
   */
  public function isSessionOpenForRegistration(ProductInterface $product = NULL, EventSessionInterface $session = NULL) {
    if (!$product || !$session) {
      return FALSE;
    }
    if (!$session->hasField('field_session_code')) {
      return FALSE;
    }
    if (!$session->hasField('rng_capacity')) {
      // Capacity: Unlimited registration.
      return TRUE;
    }
    $capacity = $session->get('rng_capacity')->getString();
    $capacity = empty($capacity) ? -1 : intval($capacity);
    if (!($capacity > 0)) {
      // Capacity: Unlimited registration.
      return TRUE;
    }
    $session_code = $session->get('field_session_code')->getString();
    if (empty($session_code)) {
      return FALSE;
    }
    // Check the event field.
    if (!$product->hasField('field_amnet_event_id')) {
      return FALSE;
    }
    $am_net_event_id = $product->get('field_amnet_event_id')->getValue();
    $am_net_event_id = is_array($am_net_event_id) ? current($am_net_event_id) : NULL;
    $event_code = $am_net_event_id['code'] ?? NULL;
    $event_year = $am_net_event_id['year'] ?? NULL;
    if (empty($event_code) || empty($event_year)) {
      return FALSE;
    }
    $event = $this->getEventInfo($event_code, $event_year);
    if (empty($event)) {
      return FALSE;
    }
    $event_sessions = $event['Sessions'] ?? [];
    if (empty($event_sessions)) {
      return FALSE;
    }
    $registration_count = 0;
    foreach ($event_sessions as $delta => $item) {
      if (isset($item['SessionCode']) && ($item['SessionCode'] == $session_code)) {
        $registration_count = $item['RegistrationCount'] ?? 0;
        break;
      }
    }
    $registration_count = intval($registration_count);
    return (($capacity - $registration_count) > 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalEvents() {
    // Load events from Drupal DB.
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_amnet_event_id', 'events');
    $query->fields('events', ['field_amnet_event_id_code', 'field_amnet_event_id_year']);
    $result = $query->execute();
    return $result->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetEvents($since = '2015-01-01') {
    $events = $this->client->get('/Event', [
      'fromdt' => $since,
    ]);
    if ($events->hasError()) {
      throw new AmNetRecordNotFoundException($events->getErrorMessage());
    }
    if ($result = $events->getResult()) {
      return $result;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetProduct($product_code) {
    $event = $this->client->get('/Product', [
      'code' => $product_code,
    ]);
    if ($event->hasError()) {
      throw new AmNetRecordNotFoundException($event->getErrorMessage());
    }
    if ($result = $event->getResult()) {
      return $result;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetProductCodes() {
    $products = $this->client->get('/Product', [
      'all' => 'true',
    ]);
    if ($products->hasError()) {
      throw new AmNetRecordNotFoundException($products->getErrorMessage());
    }
    if ($result = $products->getResult()) {
      return array_map(function ($product) {
        return $product['ProductCode'];
      }, $result);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetEventLocations() {
    $locations = $this->client->get('/Lists', [
      'listKey' => 'EVLO',
    ]);
    if ($locations->hasError()) {
      throw new AmNetRecordNotFoundException($locations->getErrorMessage());
    }
    if ($result = $locations->getResult()) {
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalCreditType($credit_category_code, $sync = TRUE) {
    $terms = $this->termStorage->getQuery()
      ->condition('vid', 'credit_type')
      ->condition('field_amnet_credit_category_code', $credit_category_code)
      ->execute();
    if (!$terms) {
      if ($sync) {
        $this->syncDrupalCreditTypes();
        return $this->getDrupalCreditType($credit_category_code, FALSE);
      }
      throw new AmNetRecordNotFoundException('Could not find Credit Type term for AM.net credit category code ' . $credit_category_code);
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->termStorage->load(current($terms));

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalCourseLevel($course_level_code, $sync = TRUE) {
    $terms = $this->termStorage->getQuery()
      ->condition('vid', 'course_level')
      ->condition('field_amnet_code', $course_level_code)
      ->execute();
    if (!$terms) {
      if ($sync) {
        $this->syncDrupalCourseLevels();
        return $this->getDrupalCourseLevel($course_level_code, FALSE);
      }
      throw new AmNetRecordNotFoundException('Could not find Course Level term for AM.net course level code ' . $course_level_code);
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->termStorage->load(current($terms));

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalEventCityArea(array $event, $product = NULL, $sync = TRUE) {
    if (!$product) {
      $city_or_area_code = $event['FacilityLocationFirmCode'];
    }
    else {
      if ($event['FormatCode'] === 'ON') {
        $city_or_area_code = 'OL';
      }
    }
    if (isset($city_or_area_code) && !empty($city_or_area_code)) {
      $terms = $this->termStorage->getQuery()
        ->condition('vid', 'city_area')
        ->condition('field_amnet_code', $city_or_area_code)
        ->execute();
      if (!$terms) {
        if ($sync) {
          $this->syncDrupalCityAreas();
          return $this->getDrupalEventCityArea($event, $product, FALSE);
        }
        throw new AmNetRecordNotFoundException('Could not find City or Area for AM.net City / Area code ' . $city_or_area_code);
      }

      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $this->termStorage->load(current($terms));

      return $term;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalCpeType(array $am_net_record, $product = FALSE, $sync = TRUE) {
    if (!$product) {
      // Webinar.
      if ($am_net_record['FacilityLocationFirmCode'] === 'OL' || substr($am_net_record['Code'], -1, 1) === 'W') {
        $type_code = 'W';
      }
      // Conference.
      elseif ($am_net_record['TypeCode'] === 'C') {
        $type_code = 'C';
      }
      // Course.
      elseif ($am_net_record['TypeCode'] === 'S') {
        $type_code = 'S';
      }
    }
    else {
      // On-Demand.
      if ($am_net_record['FormatCode'] === 'ON' && $am_net_record['CategoryCode'] === 'SS') {
        $type_code = 'O';
      }
      else {
        // This record does not have a CPE Type.
        return NULL;
      }
    }
    $terms = $this->termStorage->getQuery()
      ->condition('vid', 'cpe_type')
      ->condition('field_amnet_code', $type_code)
      ->execute();
    if (!$terms) {
      if ($sync) {
        $this->syncDrupalCpeTypes();
        return $this->getDrupalCpeType($am_net_record, $product, FALSE);
      }
      $am_net_type_code = $am_net_record['TypeCode'] ?? '(NULL)';
      throw new AmNetRecordNotFoundException('Could not find CPE Type for AM.net CPE type code ' . $am_net_type_code);
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->termStorage->load(current($terms));

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalCpeFormat(array $am_net_record, $product = FALSE, $sync = TRUE) {
    if (!$product) {
      // AM.net does not have a Format for (Live) events, but Drupal will
      // use the code 'LI'.
      $format_code = 'LI';
    }
    else {
      if ($am_net_record['CategoryCode'] === 'SS' && !empty($am_net_record['FormatCode'])) {
        // Either: Webinar (WB), Webcast (WE), Download (DO), or Online (ON).
        $format_code = $am_net_record['FormatCode'];
      }
      else {
        // This record has no CPE format.
        return NULL;
      }
    }
    $terms = $this->termStorage->getQuery()
      ->condition('vid', 'cpe_format')
      ->condition('field_amnet_code', $format_code)
      ->execute();
    if (!$terms) {
      if ($sync) {
        $this->syncDrupalCpeFormats();
        return $this->getDrupalCpeFormat($am_net_record, $product, FALSE);
      }
      throw new AmNetRecordNotFoundException('Could not find CPE Type for AM.net CPE type code ' . $am_net_record['FormatCode']);
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->termStorage->load(current($terms));

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalEventVendorFieldItems(array $event) {
    $terms = [];
    foreach (['Vendor1', 'Vendor2', 'Vendor3'] as $vendor) {
      if (!empty($event[$vendor])) {
        try {
          $firm = $this->getDrupalFirm(trim($event[$vendor]));
          if (!empty($firm)) {
            $terms[] = $firm;
          }
        }
        catch (AmNetRecordNotFoundException $e) {
          $this->logger->warning($e->getMessage());
        }
      }
    }

    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalFieldOfInterestFieldItems(array $event) {
    if (empty($event['FieldsOfInterestCodes'])) {
      return [];
    }

    $terms = [];
    foreach (explode(',', $event['FieldsOfInterestCodes']) as $code) {
      try {
        $terms[] = $this->getDrupalFieldOfInterestTerm(trim($code));
      }
      catch (AmNetRecordNotFoundException $e) {
        $this->logger->warning($e->getMessage());
      }
    }

    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalFieldOfStudyFieldItems(array $event) {
    if (empty($event['FieldsOfStudyCodes'])) {
      return [];
    }

    $terms = [];
    foreach (explode(',', $event['FieldsOfStudyCodes']) as $code) {
      try {
        $terms[] = $this->getDrupalFieldOfStudyTerm(trim($code));
      }
      catch (AmNetRecordNotFoundException $e) {
        $this->logger->warning($e->getMessage());
      }
    }

    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalFieldOfInterestTerm($interest_code, $sync = TRUE) {
    $terms = $this->termStorage->getQuery()
      ->condition('vid', 'interest')
      ->condition('field_amnet_interest_code', $interest_code)
      ->execute();
    if (!$terms) {
      if ($sync) {
        // @todo Sync via Adrian's module(s)?
        $this->syncDrupalFieldsOfInterest();
        return $this->getDrupalFieldOfInterestTerm($interest_code, FALSE);
      }
      throw new AmNetRecordNotFoundException('Could not find Field of Interest term for AM.net interest code ' . $interest_code);
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->termStorage->load(current($terms));

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalFieldOfStudyTerm($code, $sync = TRUE) {
    $terms = $this->termStorage->getQuery()
      ->condition('vid', 'field_of_study')
      ->condition('field_amnet_code', $code)
      ->execute();
    if (!$terms) {
      if ($sync) {
        $this->syncDrupalFieldsOfStudy();
        return $this->getDrupalFieldOfStudyTerm($code, FALSE);
      }
      throw new AmNetRecordNotFoundException('Could not find Field of Study term for AM.net interest code ' . $code);
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->termStorage->load(current($terms));

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalFirm($amnet_firm_code = NULL, $sync = TRUE) {
    $amnet_firm_code = trim($amnet_firm_code);
    if (empty($amnet_firm_code)) {
      return NULL;
    }
    $firms = $this->termStorage->getQuery()
      ->condition('vid', 'firm')
      ->condition('field_amnet_id', $amnet_firm_code)
      ->execute();
    if (!$firms) {
      if ($sync) {
        $this->firmManager->syncFirmRecord($amnet_firm_code);
        return $this->getDrupalFirm($amnet_firm_code, FALSE);
      }
      throw new AmNetRecordNotFoundException('Could not find Firm term for AM.net firm code ' . $amnet_firm_code);
    }

    /** @var \Drupal\taxonomy\TermInterface $firm */
    $firm = $this->termStorage->load(current($firms));

    return $firm;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalSessionLeaders(array $record) {
    $speakers = [];
    $name_ids = [];
    foreach ($record['Leaders'] as $leader) {
      // Load names that haven't been loaded already.
      if (empty($name_ids[$leader['NamesId']])) {
        try {
          $person = $this->personManager->getDrupalPerson((int) $leader['NamesId']);
          $speakers[$person->id()] = $person;
        }
        catch (AmNetRecordNotFoundException $e) {
          $this->logger->error($e->getMessage());
        }
      }
      // Mark the name id as seen.
      $name_ids[$leader['NamesId']] = TRUE;
    }

    return $speakers;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalSessionTimeslot(EventSessionInterface $session, ProductInterface $product, array $session_record) {
    $timeslotKey = Helper::getAmNetSessionTimeslotKey($session_record);
    $timeslot = $this->paragraphStorage->loadByProperties([
      'type' => 'timeslot',
      'parent_type' => 'paragraph',
      'parent_field_name' => 'field_timeslots',
      'parent_id' => $this->getDrupalSessionTimeslotGroup($session, $product)->id(),
      'field_timeslot_group_key' => $timeslotKey,
    ]);
    if ($timeslot) {
      return current($timeslot);
    }
    $date_settings = ['timezone' => $this->eventTimezone];
    $new_timeslot = Paragraph::create([
      'type' => 'timeslot',
      'field_label' => sprintf('%s - %s Sessions',
        $session->get('field_session_time')->start_date->format('g:ia', $date_settings),
        $session->get('field_session_time')->end_date->format('g:ia', $date_settings)
      ),
      'field_timeslot_group_key' => $timeslotKey,
      'field_timeslot_time' => [
        'value' => $session->get('field_session_time')->value,
        'end_value' => $session->get('field_session_time')->end_value,
      ],
    ]);
    $new_timeslot->save();

    return $new_timeslot;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalSessionTimeslotGroup(EventSessionInterface $session, ProductInterface $product) {
    $date_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    $date_settings = ['timezone' => $this->eventTimezone];
    $session_start = $session->get('field_session_time')->start_date;
    $session_end = $session->get('field_session_time')->end_date;
    $start_datetime = $session_start;
    $end_datetime = $session_end;
    foreach ($product->get('field_dates_times')->getIterator() as $date_time) {
      if ($session_start->getTimestamp() >= $date_time->start_date->getTimestamp() &&
        $session_end->getTimestamp() <= $date_time->end_date->getTimestamp()) {
        $start_datetime = $date_time->start_date;
        $end_datetime = $date_time->end_date;
        continue;
      }
    }
    $existing = $this->paragraphStorage->loadByProperties([
      'type' => 'timeslot_group',
      'parent_type' => 'commerce_product',
      'parent_field_name' => 'field_event_timeslot_groups',
      'parent_id' => $product->id(),
      'field_timeslot_group_time' => [
        'value' => $start_datetime->format($date_format, $date_settings),
        'end_value' => $end_datetime->format($date_format, $date_settings),
      ],
    ]);
    if ($existing) {
      return current($existing);
    }
    $new = Paragraph::create([
      'type' => 'timeslot_group',
      'field_label' => $start_datetime->format('l, M j, Y', $date_settings),
      'parent_type' => 'commerce_product',
      'parent_field_name' => 'field_event_timeslot_groups',
      'parent_id' => $product->id(),
      'field_timeslot_group_time' => [
        'value' => $start_datetime->format($date_format, $date_settings),
        'end_value' => $end_datetime->format($date_format, $date_settings),
      ],
    ]);
    $new->save();

    return $new;
  }

  /**
   * {@inheritdoc}
   */
  public function syncDrupalCourseLevels() {
    try {
      foreach ($this->getAmNetCourseLevels() as $level) {
        $term = $this->termStorage->loadByProperties([
          'vid' => 'course_level',
          'field_amnet_code' => $level['Code'],
        ]);
        if (empty($term)) {
          try {
            $term = $this->termStorage->create([
              'vid' => 'course_level',
              'name' => $level['Description'],
              'field_amnet_code' => $level['Code'],
            ]);
            $term->save();
          }
          catch (EntityStorageException $e) {
            $this->logger->error($e->getMessage());
          }
        }
      }
    }
    catch (AmNetRecordNotFoundException $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncDrupalCreditTypes() {
    try {
      foreach ($this->getAmNetCreditCategories() as $category) {
        $term = $this->termStorage->loadByProperties([
          'vid' => 'credit_type',
          'field_amnet_credit_category_code' => $category['Code'],
        ]);
        if (empty($term)) {
          try {
            $term = $this->termStorage->create([
              'vid' => 'credit_type',
              'name' => $category['Description'],
              'field_amnet_credit_category_code' => $category['Code'],
            ]);
            $term->save();
          }
          catch (EntityStorageException $e) {
            $this->logger->error($e->getMessage());
          }
        }
      }
    }
    catch (AmNetRecordNotFoundException $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncDrupalCityAreas() {
    try {
      foreach ($this->getAmNetCityAreas() as $category) {
        $term = $this->termStorage->loadByProperties([
          'vid' => 'city_area',
          'field_amnet_code' => $category['Code'],
        ]);
        if (empty($term)) {
          try {
            $term = $this->termStorage->create([
              'vid' => 'city_area',
              'name' => $category['Description'],
              'field_amnet_code' => $category['Code'],
            ]);
            $term->save();
          }
          catch (EntityStorageException $e) {
            $this->logger->error($e->getMessage());
          }
        }
      }
    }
    catch (AmNetRecordNotFoundException $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncDrupalCpeFormats() {
    foreach ($this->getAmNetCpeFormats() as $format) {
      $term = $this->termStorage->loadByProperties([
        'vid' => 'cpe_format',
        'field_amnet_code' => $format['Code'],
      ]);
      if (empty($term)) {
        try {
          $term = $this->termStorage->create([
            'vid' => 'cpe_format',
            'name' => $format['Description'],
            'field_amnet_code' => $format['Code'],
          ]);
          $term->save();
        }
        catch (EntityStorageException $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncDrupalCpeTypes() {
    foreach ($this->getAmNetCpeTypes() as $type) {
      $term = $this->termStorage->loadByProperties([
        'vid' => 'cpe_type',
        'field_amnet_code' => $type['Code'],
      ]);
      if (empty($term)) {
        try {
          $term = $this->termStorage->create([
            'vid' => 'cpe_type',
            'name' => $type['Description'],
            'field_amnet_code' => $type['Code'],
          ]);
          $term->save();
        }
        catch (EntityStorageException $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncDrupalFieldsOfInterest() {
    try {
      foreach ($this->getAmNetFieldsOfInterest() as $interest) {
        $term = $this->termStorage->loadByProperties([
          'vid' => 'interest',
          'field_amnet_interest_code' => $interest['Code'],
        ]);
        if (empty($term)) {
          try {
            $term = $this->termStorage->create([
              'vid' => 'interest',
              'name' => $interest['Description'],
              'field_amnet_interest_code' => $interest['Code'],
            ]);
            $term->save();
          }
          catch (EntityStorageException $e) {
            $this->logger->error($e->getMessage());
          }
        }
      }
    }
    catch (AmNetRecordNotFoundException $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncDrupalFieldsOfStudy() {
    try {
      foreach ($this->getAmNetFieldsOfStudy() as $field_of_study) {
        $term = $this->termStorage->loadByProperties([
          'vid' => 'field_of_study',
          'field_amnet_code' => $field_of_study['Code'],
        ]);
        if (empty($term)) {
          try {
            $term = $this->termStorage->create([
              'vid' => 'field_of_study',
              'name' => $field_of_study['Description'],
              'field_amnet_code' => $field_of_study['Code'],
            ]);
            $term->save();
          }
          catch (EntityStorageException $e) {
            $this->logger->error($e->getMessage());
          }
        }
      }
    }
    catch (AmNetRecordNotFoundException $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Get AmNet field value.
   *
   * @param array $am_net_record
   *   The AMNet record.
   * @param string $field_name
   *   The field name.
   *
   * @return bool|string
   *   The field value, otherwise NULL.
   */
  public function getAmNetFieldValue(array $am_net_record = [], $field_name = NULL) {
    if (empty($am_net_record) || empty($field_name)) {
      return NULL;
    }
    return $am_net_record[$field_name] ?? NULL;
  }

  /**
   * Check if a given product Exist.
   *
   * @param string $product_id
   *   The product product ID.
   *
   * @return bool
   *   Return TRUE if the product exist, otherwise FALSE.
   */
  public function productExist($product_id = NULL) {
    if (empty($product_id)) {
      return FALSE;
    }
    $database = \Drupal::database();
    $query = $database->select('commerce_product', 'product');
    $query->fields('product', ['product_id']);
    $query->condition('product_id', $product_id);
    $entity_id = $query->execute()->fetchField();
    return !empty($entity_id);
  }

  /**
   * Do UnPublish Drupal CPE event product.
   *
   * @param string $message
   *   The error code message.
   * @param string $product_code
   *   The product code.
   *
   * @return bool
   *   TRUE operation is complete, otherwise FALSE.
   */
  public function doUnpublishDrupalStudyProduct($message = NULL, $product_code = NULL) {
    if (empty($message)) {
      return FALSE;
    }
    if ($message != 'SyncErrorCode: 99 | No data') {
      return FALSE;
    }
    try {
      $drupal_product = $this->getDrupalCpeSelfStudyProduct($product_code, FALSE);
    }
    catch (AmNetRecordExcludedException $e) {
      return FALSE;
    }
    catch (AmNetRecordNotFoundException $e) {
      return FALSE;
    }
    if (!$drupal_product) {
      return FALSE;
    }
    // Un-publish the event.
    $drupal_product->setUnpublished();
    try {
      $drupal_product->save();
      return TRUE;
    }
    catch (EntityStorageException $e) {
      return FALSE;
    }
  }

  /**
   * Do UnPublish Drupal CPE event product.
   *
   * @param string $message
   *   The error code message.
   * @param string $event_code
   *   The event code.
   * @param string $event_year
   *   The event year.
   *
   * @return bool
   *   TRUE operation is complete, otherwise FALSE.
   */
  public function doUnpublishDrupalCpeEventProduct($message = NULL, $event_code = NULL, $event_year = NULL) {
    if (empty($message)) {
      return FALSE;
    }
    if ($message != 'SyncErrorCode: 99 | No data') {
      return FALSE;
    }
    $drupal_event = $this->getDrupalCpeEventProduct($event_code, $event_year, FALSE);
    if (!$drupal_event) {
      return FALSE;
    }
    // Un-publish the event.
    $drupal_event->set('field_am_net_deleted', TRUE);
    $drupal_event->setUnpublished();
    try {
      $drupal_event->save();
      return TRUE;
    }
    catch (EntityStorageException $e) {
      return FALSE;
    }
  }

  /**
   * Gets Active events.
   *
   * @return array
   *   The array list of events, otherwise FALSE.
   */
  public function getActiveEvents() {
    // Define Starts Date.
    $timezone = drupal_get_user_timezone();
    $start = new \DateTime('now', new \DateTimezone($timezone));
    $start->setTime(16, 0);
    $start->setTimezone(new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));
    $start = DrupalDateTime::createFromDateTime($start);
    // Create the query object.
    $query = \Drupal::entityQuery('commerce_product');
    $query->condition('type', 'cpe_event');
    $query->condition('field_search_index_date', $start->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>=');
    $entity_ids = $query->execute();
    return $entity_ids;
  }

  /**
   * Gets Active Self Study products.
   *
   * @return array
   *   The array list of products, otherwise FALSE.
   */
  public function getActiveSelfStudyProducts() {
    $database = \Drupal::database();
    $key = 'field_course_prodcode_value';
    $query = $database->select('commerce_product__field_course_prodcode', 'product');
    $query->join('commerce_product_field_data', 'data', 'product.entity_id = data.product_id');
    $query->fields('product', [$key]);
    $query->condition('data.status', '1');
    $result = $query->execute();
    return $result->fetchAllAssoc($key);
  }

  /**
   * Gets Events AM.net IDs.
   *
   * @param array $ids
   *   The CPE events IDs.
   *
   * @return array|bool
   *   The array list of events codes, otherwise FALSE.
   */
  public function getEventAmNetIds(array $ids = []) {
    if (empty($ids)) {
      return FALSE;
    }
    // Load events from Drupal DB.
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_amnet_event_id', 'events');
    $query->condition('entity_id', $ids, 'IN');
    $fields = [
      'field_amnet_event_id_code',
      'field_amnet_event_id_year',
    ];
    $query->fields('events', $fields);
    $result = $query->execute();
    return $result->fetchAll();
  }

}
