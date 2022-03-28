<?php

namespace Drupal\announcement_pop_up\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Announcement entity.
 *
 * @ingroup announcement_pop_up
 *
 * @ContentEntityType(
 *   id = "announcement",
 *   label = @Translation("Announcement"),
 *   bundle_label = @Translation("Announcement type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\announcement_pop_up\AnnouncementListBuilder",
 *     "views_data" = "Drupal\announcement_pop_up\Entity\AnnouncementViewsData",
 *     "translation" = "Drupal\announcement_pop_up\AnnouncementTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\announcement_pop_up\Form\AnnouncementForm",
 *       "add" = "Drupal\announcement_pop_up\Form\AnnouncementForm",
 *       "edit" = "Drupal\announcement_pop_up\Form\AnnouncementForm",
 *       "delete" = "Drupal\announcement_pop_up\Form\AnnouncementDeleteForm",
 *     },
 *     "access" = "Drupal\announcement_pop_up\AnnouncementAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\announcement_pop_up\AnnouncementHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "announcement",
 *   data_table = "announcement_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer announcement entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/announcement/{announcement}",
 *     "add-page" = "/admin/structure/announcement/add",
 *     "add-form" = "/admin/structure/announcement/add/{announcement_type}",
 *     "edit-form" = "/admin/structure/announcement/{announcement}/edit",
 *     "delete-form" = "/admin/structure/announcement/{announcement}/delete",
 *     "collection" = "/admin/structure/announcement",
 *   },
 *   bundle_entity_type = "announcement_type",
 *   field_ui_base_route = "entity.announcement_type.edit_form"
 * )
 */
class Announcement extends ContentEntityBase implements AnnouncementInterface {

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
  public function getRequestPath() {
    return $this->get('request_path')->getValue() ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestPath($requestPath) {
    $this->set('request_path', $requestPath);
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Announcement entity.'))
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
      ->setDescription(t('The name of the Announcement entity.'))
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
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Announcement is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['request_path'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Request Path'))
      ->setDescription(t('Announcement page visibility.'))
      ->setDefaultValue([]);

    return $fields;
  }

  /**
   * Return boolean value.
   *
   * @return bool
   *
   *   Return description.
   */
  public function showAnnouncement() {

    /* @var \Drupal\system\Plugin\Condition\RequestPath $condition */
    $condition = \Drupal::service('plugin.manager.condition')->createInstance('request_path');

    $requestPath = $this->getRequestPath();
    $requestPath = reset($requestPath);
    $condition->setConfiguration($requestPath);

    return $condition->evaluate();
  }

}
