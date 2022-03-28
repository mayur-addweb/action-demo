<?php

namespace Drupal\announcement_pop_up\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Announcement type entity.
 *
 * @ConfigEntityType(
 *   id = "announcement_type",
 *   label = @Translation("Announcement type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\announcement_pop_up\AnnouncementTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\announcement_pop_up\Form\AnnouncementTypeForm",
 *       "edit" = "Drupal\announcement_pop_up\Form\AnnouncementTypeForm",
 *       "delete" = "Drupal\announcement_pop_up\Form\AnnouncementTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\announcement_pop_up\AnnouncementTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "announcement_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "announcement",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/announcement_type/{announcement_type}",
 *     "add-form" = "/admin/structure/announcement_type/add",
 *     "edit-form" = "/admin/structure/announcement_type/{announcement_type}/edit",
 *     "delete-form" = "/admin/structure/announcement_type/{announcement_type}/delete",
 *     "collection" = "/admin/structure/announcement_type"
 *   }
 * )
 */
class AnnouncementType extends ConfigEntityBundleBase implements AnnouncementTypeInterface {

  /**
   * The Announcement type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Announcement type label.
   *
   * @var string
   */
  protected $label;

}
