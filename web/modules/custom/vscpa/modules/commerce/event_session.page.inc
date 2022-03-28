<?php

/**
 * @file
 * Contains event_session.page.inc.
 *
 * Page callback for Event session entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Event session templates.
 *
 * Default template: event_session.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_event_session(array &$variables) {
  // Provide templates access to the full event session entity.
  $variables['session'] = $variables['elements']['#event_session'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
  if (isset($variables['content']['name']['0']['#context']['value'])) {
    $title = $variables['content']['name']['0']['#context']['value'];
    $title = strtolower($title);
    if (strpos($title, 'materials') !== FALSE) {
      unset($variables['content']['field_session_time']);
    }
    if (strpos($title, 'sponsors') !== FALSE) {
      unset($variables['content']['field_session_time']);
    }
  }
}
