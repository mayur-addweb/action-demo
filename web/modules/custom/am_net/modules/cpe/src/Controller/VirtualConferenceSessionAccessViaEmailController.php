<?php

namespace Drupal\am_net_cpe\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;

/**
 * Event Registration 'Add to Cart' Controller.
 */
class VirtualConferenceSessionAccessViaEmailController extends ControllerBase {

  /**
   * Ajax display status messages.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax Response.
   */
  public function displayAjaxStatusMessages() {
    $messenger = $this->messenger();
    // Show the result.
    $messages = $messenger->all();
    $messenger->deleteAll();
    $message = [
      '#theme' => 'status_messages',
      '#message_list' => $messages,
    ];
    $messages = \Drupal::service('renderer')->render($message);
    $response = [
      'success' => FALSE,
      'messages' => $messages,
    ];
    return new JsonResponse($response);
  }

  /**
   * Ajax get Session access.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\JsonResponse
   *   The Ajax Response.
   */
  public function ajaxGetAccess(Request $request) {
    $params = Json::decode($request->getContent());
    $messenger = \Drupal::messenger();
    $missing_info_warning = $this->t('<strong>Email address</strong> field is required.');
    if (empty($params)) {
      $messenger->addWarning($missing_info_warning);
      return $this->displayAjaxStatusMessages();
    }
    $event_code = $params['event_code'] ?? NULL;
    $event_year = $params['event_year'] ?? NULL;
    $email_address = $params['email_address'] ?? NULL;
    if (empty($event_code) || empty($event_year) || empty($email_address)) {
      $messenger->addWarning($missing_info_warning);
      return $this->displayAjaxStatusMessages();
    }
    // Check if exist any Name ID attributed to the given email.
    $name_id = am_net_user_profile_get_amnet_id_by_email($email_address);
    if (empty($name_id)) {
      $warning = $this->t("The provided email address is not associated with any account on the site, please verify your email address.");
      $messenger->addWarning($warning);
      return $this->displayAjaxStatusMessages();
    }
    // Get the Sessions.
    $manager = \Drupal::service('am_net_cpe.virtual_conference_manager');
    $cpe_info = $manager->loadVirtualConferenceInfoSingleSignOnDownTime($name_id, $event_year, $event_code);
    $registrations = $cpe_info['registrations'] ?? NULL;
    if (empty($registrations)) {
      $warning = $this->t('The provided email is not registered in this Virtual Conference, Please verify the email address.');
      $messenger->addWarning($warning);
      return $this->displayAjaxStatusMessages();
    }
    // Get the Product basic info.
    $product_id = am_net_cpe_get_cpe_product_id_by_code_and_year($event_code, $event_year);
    $product_url = NULL;
    if (!empty($product_id)) {
      $options = ['absolute' => TRUE];
      $url = Url::fromRoute('entity.commerce_product.canonical', ['commerce_product' => $product_id], $options);
      $product_url = $url->toString();
    }
    // Build the element.
    $element = [
      '#theme' => 'my_cpe_digital_rewind',
      '#registrations' => $registrations,
      '#am_net_name_id' => $name_id,
      '#product_url' => $product_url,
      '#cache' => [
        'max-age' => 0,
        'tags' => ['sso_downtime_tag'],
        'contexts' => ['user'],
      ],
      '#weight' => 4,
    ];
    $renderer = \Drupal::service('renderer');
    $response = [
      'success' => TRUE,
      'sessions' => $renderer->render($element),
    ];
    return new JsonResponse($response);
  }

}
