<?php

namespace Drupal\vscpa_content_migration\Commands;

use Drush\Commands\DrushCommands;
use Drupal\node\Entity\Node;

/**
 * Class Strip H1 Tags.
 *
 * This is the Drush 9 command.
 *
 * @package Drupal\am_net_user_profile\Commands
 */
class StripH1Tags extends DrushCommands {

  /**
   * Strip h1 tags from Publications.
   *
   * @command strip-h1-tags
   *
   * @usage drush strip-h1-tags
   *   Strip h1 tags from Publications.
   *
   * @aliases sht
   */
  public function commandStripH1Tags() {
    // Get the Nodes that will to be processed.
    $processed_items = [];
    $tag = '<h1>';
    $database = \Drupal::database();
    $query = $database->select('node__body', 'n');
    $query->fields('n', ['entity_id']);
    $query->condition('bundle', ['publication', 'page'], 'IN');
    $query->condition('body_value', "%" . $database->escapeLike($tag) . "%", 'LIKE');
    $ids = $query->execute()->fetchCol();
    $type = 'success';
    $total = count($ids);
    foreach ($ids as $delta => $id) {
      $node = Node::load($id);
      $field_name = 'body';
      $field_value = $node->get($field_name)->getValue();
      $field_value = current($field_value);
      $html = $field_value['value'];
      preg_match_all('|<h1>(.*)</h1>|iU', $html, $headings);
      if (empty($headings)) {
        $headings = "NO FOUND.";
      }
      else {
        while (is_array($headings)) {
          $headings = current($headings);
        }
      }
      $final = preg_replace('#<h1>(.*?)</h1>#', '', $html, 1);
      $node->body->value = $final;
      $node->body->format = 'full_html';
      $url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
      $processed_items[] = [
        'node_id' => $id,
        'node_url' => $url,
        'old_content' => $html,
        'new_content' => $final,
      ];
      $node->save();
      $message = t('Removing H1 tags on node: @node_id.', ['@node_id' => $id]);
      drush_log($message, $type);
    }
    $message = t('Total Numbers of Nodes: @total.', ['@total' => $total]);
    drush_log($message, $type);
    $data = json_encode($processed_items);
    $destination = 'public://remove-h1-tags.json';
    $file = file_unmanaged_save_data($data, $destination, $replace = FILE_EXISTS_REPLACE);
    $file = !empty($file) ? file_create_url($file) : NULL;
    drush_print(t('Log File Path: @file', ['@file' => $file]), 1);
  }

}
