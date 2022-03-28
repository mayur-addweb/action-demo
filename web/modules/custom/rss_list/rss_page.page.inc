<?php

/**
 * @file
 * Contains rss_page.page.inc.
 *
 * Page callback for RSS Page entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for RSS Page templates.
 *
 * Default template: rss-page.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_rss_page(array &$variables) {
  // Fetch RssPage Entity Object.
  $rss_page = $variables['elements']['#rss_page'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
