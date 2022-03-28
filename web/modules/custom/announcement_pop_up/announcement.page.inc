<?php

/**
 * @file
 * Contains announcement.page.inc.
 *
 * Page callback for Announcement entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Announcement templates.
 *
 * Default template: announcement.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_announcement(array &$variables) {
  // Fetch Announcement Entity Object.
  $announcement = $variables['elements']['#announcement'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
