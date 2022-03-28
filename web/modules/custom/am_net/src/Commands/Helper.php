<?php

namespace Drupal\am_net\Commands;

/**
 * Helper Class for customs Commands.
 */
class Helper {

  /**
   * Prints array messages.
   *
   * @param array $messages
   *   An associative array containing the message list.
   */
  public static function printMessages(array $messages = []) {
    if (!empty($messages)) {
      $delta = 1;
      $pad_length = 50;
      foreach ($messages as $msg) {
        $increment = TRUE;
        $label = isset($msg[0]) ? $msg[0] : NULL;
        $values = isset($msg[1]) ? $msg[1] : NULL;
        if (!is_null($label)) {
          $prefix = ($delta) < 10 ? '0' . $delta : $delta;
          $line = str_pad($prefix . '. ' . $label, $pad_length);
          if (is_array($values)) {
            drush_print($line, 1);
            foreach ($values as $key => $value) {
              if (!empty($value)) {
                $output = str_pad($key, $pad_length - 4) . strval($value);
                drush_print($output, 5);
              }
            }
          }
          else {
            $str_values = strval($values);
            if (!empty($str_values)) {
              drush_print($line . $str_values, 1);
            }
            else {
              $increment = FALSE;
            }
          }
          if ($increment) {
            $delta += 1;
          }
        }
      }
    }
  }

}
