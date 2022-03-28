<?php

namespace Drupal\vscpa_commerce\Element;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * AM.net Order Items Submit Handlers Trait.
 */
trait AmNetOrderItemSubmitHandlersTrait {

  /**
   * Gets the form element that triggered submission.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|null
   *   The form element that triggered submission, of NULL if there is none.
   */
  public static function getTriggeringElement(array $form, FormStateInterface $form_state) {
    $element = [];
    $triggering_element = $form_state->getTriggeringElement();
    // Remove the action and the actions container.
    $array_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    while (!isset($element['#element_root'])) {
      $element = NestedArray::getValue($form, $array_parents);
      array_pop($array_parents);
    }
    return $element;
  }

  /**
   * Gets the values from the form element that triggered submission.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|null
   *   The form element that triggered submission, of NULL if there is none.
   */
  public static function getTriggeringElementValues(array $form, FormStateInterface $form_state) {
    $element = self::getTriggeringElement($form, $form_state);
    $parents = $element['#parents'];
    $form_values = NestedArray::getValue($form_state->getUserInput(), $parents);
    return $form_values;
  }

  /**
   * Push and Sync specific AM.net Self Study Registration Record.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of ajax commands to execute on submit of the form.
   */
  public static function submitSelfStudyRegistrationRecordChanges(array $form, FormStateInterface $form_state) {
    $url = $current_path = \Drupal::service('path.current')->getPath();
    $form_values = self::getTriggeringElementValues($form, $form_state);
    if ($form_values) {
      $service_id = 'vscpa_commerce.am_net_sync_manager';
      $response = \Drupal::service($service_id)->syncOrderItemSelfStudyRegistrationRecord($form_values);
      $message = $response['messages'];
      if (isset($message['error_message']) && !empty($message['error_message'])) {
        $_message = $message['error_message'];
        drupal_set_message($_message, 'warning');
      }
      else {
        $_message = 'Self Study Registration synchronized correctly.';
        drupal_set_message($_message);
      }
    }
    else {
      drupal_set_message(t('Self Study Registration records not Valid.'));
    }
    // Refresh The Page.
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($url));
    return $response;
  }

  /**
   * Push and Sync specific AM.net Event Registration Record.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of ajax commands to execute on submit of the form.
   */
  public static function submitEventRegistrationRecordChanges(array $form, FormStateInterface $form_state) {
    $url = $current_path = \Drupal::service('path.current')->getPath();
    $form_values = self::getTriggeringElementValues($form, $form_state);
    if ($form_values) {
      $service_id = 'vscpa_commerce.am_net_sync_manager';
      $response = \Drupal::service($service_id)->syncOrderItemEventRegistrationRecord($form_values);
      $message = $response['messages'];
      if (isset($message['error_message']) && !empty($message['error_message'])) {
        $_message = $message['error_message'];
        drupal_set_message($_message, 'warning');
      }
      else {
        $_message = 'Event Registration synchronized correctly.';
        drupal_set_message($_message);
      }
    }
    else {
      drupal_set_message(t('Event registration records not Valid.'));
    }
    // Refresh The Page.
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($url));
    return $response;
  }

  /**
   * Push and Sync specific AM.net Membership Payment Record.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of ajax commands to execute on submit of the form.
   */
  public static function submitMembershipPaymentRecordChanges(array $form, FormStateInterface $form_state) {
    $url = $current_path = \Drupal::service('path.current')->getPath();
    $form_values = self::getTriggeringElementValues($form, $form_state);
    if ($form_values) {
      $service_id = 'vscpa_commerce.am_net_sync_manager';
      $response = \Drupal::service($service_id)->syncOrderItemMembershipPaymentRecord($form_values);
      $message = $response['messages'];
      if (isset($message['error_message']) && !empty($message['error_message'])) {
        $_message = $message['error_message'];
        drupal_set_message($_message, 'warning');
      }
      else {
        $_message = 'Membership Payment synchronized correctly.';
        drupal_set_message($_message);
      }
    }
    else {
      drupal_set_message(t('Membership Payment record not Valid.'));
    }
    // Refresh The Page.
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($url));
    return $response;
  }

  /**
   * Push and Sync specific AM.net Peer Review Payment Record.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of ajax commands to execute on submit of the form.
   */
  public static function submitPeerReviewPaymentRecordChanges(array $form, FormStateInterface $form_state) {
    $url = $current_path = \Drupal::service('path.current')->getPath();
    $form_values = self::getTriggeringElementValues($form, $form_state);
    if ($form_values) {
      $service_id = 'vscpa_commerce.am_net_sync_manager';
      $response = \Drupal::service($service_id)->syncOrderItemPeerReviewPaymentRecord($form_values);
      $message = $response['messages'];
      if (isset($message['error_message']) && !empty($message['error_message'])) {
        $_message = $message['error_message'];
        drupal_set_message($_message, 'warning');
      }
      else {
        $_message = 'Peer Review Payment synchronized correctly.';
        drupal_set_message($_message);
      }
    }
    else {
      drupal_set_message(t('Peer Review Payment records not Valid.'));
    }
    // Refresh The Page.
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($url));
    return $response;
  }

  /**
   * Submit for the action: Mark order as completed for Sync.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of ajax commands to execute on submit of the form.
   */
  public static function submitSetOrderSyncAsCompleted(array $form, FormStateInterface $form_state) {
    $url = $current_path = \Drupal::service('path.current')->getPath();
    $form_values = self::getTriggeringElementValues($form, $form_state);
    $order_id = $form_values['order_id'] ?? FALSE;
    if (!empty($order_id)) {
      $service_id = 'vscpa_commerce.am_net_sync_manager';
      \Drupal::service($service_id)->setOrderSyncAsCompleted($order_id);
      drupal_set_message(t('The order was successfully marked as synchronized.'));
    }
    else {
      drupal_set_message(t('An error has occurred marking the order as completed, please try again.'));
    }
    // Refresh The Page.
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($url));
    return $response;
  }

  /**
   * Push and Sync specific AM.net Donation Record.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of ajax commands to execute on submit of the form.
   */
  public static function submitDonationRecordChanges(array $form, FormStateInterface $form_state) {
    $url = $current_path = \Drupal::service('path.current')->getPath();
    $form_values = self::getTriggeringElementValues($form, $form_state);
    if ($form_values) {
      $service_id = 'vscpa_commerce.am_net_sync_manager';
      $response = \Drupal::service($service_id)->syncOrderItemDonationRecord($form_values);
      $message = $response['messages'];
      if (isset($message['error_message']) && !empty($message['error_message'])) {
        $_message = $message['error_message'];
        drupal_set_message($_message, 'warning');
      }
      else {
        $_message = 'Donation synchronized correctly.';
        drupal_set_message($_message);
      }
    }
    else {
      drupal_set_message(t('Donation records not Valid.'));
    }
    // Refresh The Page.
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($url));
    return $response;
  }

}
