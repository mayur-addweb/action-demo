<?php

namespace Drupal\vscpa_commerce\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Event session type entity.
 *
 * @ConfigEntityType(
 *   id = "event_session_type",
 *   label = @Translation("Event session type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\vscpa_commerce\EventSessionTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\vscpa_commerce\Form\EventSessionTypeForm",
 *       "edit" = "Drupal\vscpa_commerce\Form\EventSessionTypeForm",
 *       "delete" = "Drupal\vscpa_commerce\Form\EventSessionTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\vscpa_commerce\EventSessionTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "event_session_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "event_session",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/event_session_type/{event_session_type}",
 *     "add-form" = "/admin/structure/event_session_type/add",
 *     "edit-form" = "/admin/structure/event_session_type/{event_session_type}/edit",
 *     "delete-form" = "/admin/structure/event_session_type/{event_session_type}/delete",
 *     "collection" = "/admin/structure/event_session_type"
 *   }
 * )
 */
class EventSessionType extends ConfigEntityBundleBase implements EventSessionTypeInterface {

  /**
   * The Event session type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Event session type label.
   *
   * @var string
   */
  protected $label;

}
