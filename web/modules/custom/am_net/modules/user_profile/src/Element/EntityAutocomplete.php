<?php

namespace Drupal\am_net_user_profile\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete as EntityAutocompleteBase;

/**
 * Provides an entity autocomplete form element.
 */
class EntityAutocomplete extends EntityAutocompleteBase {

  /**
   * Finds an entity from an autocomplete input without an explicit ID.
   *
   * The method will return an entity ID if one single entity unambuguously
   * matches the incoming input, and sill assign form errors otherwise.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler
   *   Entity reference selection plugin.
   * @param string $input
   *   Single string from autocomplete element.
   * @param array $element
   *   The form element to set a form error.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param bool $strict
   *   Whether to trigger a form error if an element from $input (eg. an entity)
   *   is not found.
   *
   * @return int|null
   *   Value of a matching entity ID, or NULL if none.
   */
  protected static function matchEntityByTitle(SelectionInterface $handler, $input, array &$element, FormStateInterface $form_state, $strict) {

    $entities_by_bundle = $handler->getReferenceableEntities($input, '=', 6);
    $entities = array_reduce($entities_by_bundle, function ($flattened, $bundle_entities) {
      return $flattened + $bundle_entities;
    }, []);
    $params = [
      '%value' => $input,
      '@value' => $input,
    ];
    if (empty($entities)) {
      if ($strict) {
        // Error if there are no entities available for a required field.
        $form_state->setError($element, t('There are no entities matching "%value".', $params));
      }
    }
    elseif (count($entities) > 5) {
      $params['@id'] = key($entities);
      $message = t('Many entities are called %value. Specify the one you want by appending the id in parentheses, like "@value (@id)".', $params);
      if (isset($entities_by_bundle['firm'])) {
        $message = t('Select a firm by typing a few letters into the field and waiting 1–2 seconds for a list of matching options to appear, from which you can select the appropriate firm.');
      }
      // Error if there are more than 5 matching entities.
      $form_state->setError($element, $message);
    }
    elseif (count($entities) > 1) {
      // More helpful error if there are only a few matching entities.
      $multiples = [];
      foreach ($entities as $id => $name) {
        $multiples[] = $name . ' (' . $id . ')';
      }
      $params['@id'] = $id;
      $form_state->setError($element, t('Multiple entities match this reference; "%multiple". Specify the one you want by appending the id in parentheses, like "@value (@id)".', ['%multiple' => implode('", "', $multiples)] + $params));
    }
    else {
      // Take the one and only matching entity.
      return key($entities);
    }
  }

}
