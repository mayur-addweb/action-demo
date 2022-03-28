<?php

namespace Drupal\rss_list\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\link\LinkItemInterface;
use Drupal\user\UserInterface;

/**
 * Defines the RSS Page entity.
 *
 * @ingroup rss_list
 *
 * @ContentEntityType(
 *   id = "rss_page",
 *   label = @Translation("RSS Page"),
 *   label_collection = @Translation("RSS Pages"),
 *   label_singular = @Translation("RSS page"),
 *   label_plural = @Translation("RSS pages"),
 *   label_count = @PluralTranslation(
 *     singular = "@count RSS page",
 *     plural = "@count RSS pages",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\rss_list\RssPageListBuilder",
 *     "views_data" = "Drupal\rss_list\Entity\RssPageViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\rss_list\Form\RssPageForm",
 *       "add" = "Drupal\rss_list\Form\RssPageForm",
 *       "edit" = "Drupal\rss_list\Form\RssPageForm",
 *       "delete" = "Drupal\rss_list\Form\RssPageDeleteForm",
 *     },
 *     "access" = "Drupal\rss_list\RssPageAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\rss_list\RssPageHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "rss_page",
 *   admin_permission = "administer rss page entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/rss/{rss_page}",
 *     "add-form" = "/rss/add",
 *     "edit-form" = "/rss/{rss_page}/edit",
 *     "delete-form" = "/rss/{rss_page}/delete",
 *     "collection" = "/admin/content/rss-pages",
 *   },
 *   field_ui_base_route = "rss_page.settings"
 * )
 */
class RssPage extends ContentEntityBase implements RssPageInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getFeedPath() {
    $path = $this->get('feed_path')->uri;
    if (!empty($path)) {
      $path = str_replace('internal:/', '/', $path);
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getRssLength() {
    $length = $this->get('rss_length')->value;
    if (!is_numeric($length)) {
      $length = 25;
    }
    return $length;
  }

  /**
   * {@inheritdoc}
   */
  public function getFeedChannelDescription() {
    if (!$this->hasField('field_rss_channel_description')) {
      return NULL;
    }
    return $this->get('field_rss_channel_description')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isFeedEnable() {
    $enable_feed = $this->get('enable_feed')->value;
    return boolval($enable_feed);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the RSS Page entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the RSS Page entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the RSS Page is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['path'] = BaseFieldDefinition::create('path')
      ->setLabel(t('RSS Page path'))
      ->setDescription(t('The RSS Page path.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'path',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setComputed(TRUE);

    $fields['feed_path'] = BaseFieldDefinition::create('link')
      ->setLabel(t('RSS Feed URL path'))
      ->setDescription(t('The fully-qualified URL of the feed.'))
      ->setRequired(FALSE)
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_INTERNAL,
        'title' => DRUPAL_DISABLED,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['enable_feed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enable RSS Feed'))
      ->setDescription(t('A boolean indicating whether the RSS Feed associated with this page is enabled.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['rss_length'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('RSS Length'))
      ->setDescription(t('RSS Length'))
      ->setDefaultValue(100)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 20,
      ]);
    return $fields;
  }

}
