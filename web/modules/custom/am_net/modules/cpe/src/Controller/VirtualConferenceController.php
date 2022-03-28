<?php

namespace Drupal\am_net_cpe\Controller;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Controller\ControllerBase;
use Drupal\am_net_cpe\EventHelper;
use Drupal\node\Entity\Node;

/**
 * Virtual Conference Controller.
 */
class VirtualConferenceController extends ControllerBase {

  /**
   * Render the 'Virtual Conference' edit form.
   *
   * @param \Drupal\commerce_product\Entity\Product $commerce_product
   *   The product event entity.
   *
   * @return array
   *   The render array with the edit form Otherwise an access denied exception.
   */
  public function renderForm(Product $commerce_product = NULL) {
    if (!$commerce_product) {
      throw new AccessDeniedHttpException();
    }
    $event = $commerce_product;
    $event_code = $event->field_amnet_event_id->code ?? NULL;
    $event_year = $event->field_amnet_event_id->year ?? NULL;
    $node = EventHelper::getVirtualConferenceByEventId($event_code, $event_year);
    if (!$node) {
      $id = EventHelper::getVirtualConferenceBaseTemplateNid();
      if (!empty($id)) {
        $base_template = Node::load($id);
        $new_node = $base_template->createDuplicate();
        // Clone all translations of a node.
        foreach ($new_node->getTranslationLanguages() as $langcode => $language) {
          /** @var \Drupal\node\Entity\Node $translated_node */
          $translated_node = $new_node->getTranslation($langcode);
          $node = $this->cloneParagraphs($translated_node);
        }
      }
      if (!$node) {
        // Create a new node.
        $node = Node::create(['type' => 'digital_rewind_page']);
      }
      // Set required fields.
      $node->setTitle($event->label());
      $node->set('field_digital_rewind_event_id', [
        'year' => $event_year,
        'code' => $event_code,
      ]);
      $node->set('uid', \Drupal::currentUser()->id());
      $time = time();
      $node->set('created', $time);
      $node->set('changed', $time);
      $node->set('revision_timestamp', $time);
    }
    $form = $this->entityTypeManager()->getFormObject('node', 'default')->setEntity($node);
    $build = $this->formBuilder()->getForm($form);
    return $build;
  }

  /**
   * Clone the paragraphs of a node.
   *
   * If we do not clone the paragraphs attached to the node, the linked
   * paragraphs would be linked to two nodes which is not ideal.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node to clone.
   *
   * @return \Drupal\node\Entity\Node
   *   The node with cloned paragraph fields.
   */
  public function cloneParagraphs(Node $node) {
    foreach ($node->getFieldDefinitions() as $field_definition) {
      $field_storage_definition = $field_definition->getFieldStorageDefinition();
      $field_settings = $field_storage_definition->getSettings();
      $field_name = $field_storage_definition->getName();
      if (isset($field_settings['target_type']) && $field_settings['target_type'] == "paragraph") {
        if (!$node->get($field_name)->isEmpty()) {
          foreach ($node->get($field_name) as $value) {
            if ($value->entity) {
              $value->entity = $value->entity->createDuplicate();
            }
          }
        }
      }
    }
    return $node;
  }

}
