<?php

namespace Drupal\am_net_cpe\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for INXPO course access.
 */
class MyCpeInxpo extends ControllerBase {

  /**
   * The INXPO API.
   *
   * @var string
   */
  protected $litApiUrl = "http://vts.inxpo.com/scripts/Server.nxp?LASCmd=AI:4;F:APIUTILS!50500&APIUserCredentials=tredru4awep8pr3was3evumupachen&APIUserAuthCode=far7keje2rabeh8capuhuy3s6aheza&OpCodeList=T&OutputFormat=T&LookupByExternalUserID=1&ExternalUserID={0}&ShowKey={1}";

  /**
   * The INXPO redirect old url.
   *
   * @var string
   */
  protected $litRedirect = "http://vts.inxpo.com/scripts/Server.nxp?LASCmd=AI:4;F:APIUTILS!50505&LoginTicketKey={0}";

  /**
   * The INXPO redirect url.
   *
   * @var string
   */
  protected $redirectUrl = 'https://onlinexperiences.com/Launch/Event.htm';

  /**
   * Redirect to the Login or INXPO course access.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function redirectToVendorPortal(Request $request) {
    // Check if is Anonymous user.
    if ($this->currentUser()->isAnonymous()) {
      $url = Url::fromUserInput('/join-vscpa-today', [])->toString();
      return new RedirectResponse($url);
    }
    $return_url = Url::fromUri('internal:/MyCPE', ['absolute' => TRUE])->toString();
    // Define an global error message.
    $error_message = t('There was an error launching the webcast, Please try again.');
    // Get user AMNet ID.
    $uid = $this->currentUser()->id();
    $am_net_uid = $this->getCurrentUserAmNetId($uid);
    if (empty($am_net_uid)) {
      // Show user-friendly message.
      drupal_set_message($error_message, 'warning');
      return new RedirectResponse($return_url);
    }
    // Get Product Code - GET parameter.
    $product_code = $request->query->get('product_code');
    $is_product = !empty($product_code);
    // Get Event Code - GET parameters.
    $event_year = $request->query->get('event_year');
    $event_code = $request->query->get('event_code');
    $is_event = !empty($event_year) && !empty($event_code);
    if (!$is_event && !$is_product) {
      // Show user-friendly message.
      drupal_set_message($error_message, 'warning');
      return new RedirectResponse($return_url);
    }
    // Get Product ID - GET parameter.
    $product_id = $request->query->get('product_id');
    $external_product_codes = $this->getExternalProductCodes($product_id, $is_event);
    if (empty($external_product_codes)) {
      // Show user-friendly message.
      drupal_set_message($error_message, 'warning');
      return new RedirectResponse($return_url);
    }
    // Get the Login Show Key.
    $show_key = current($external_product_codes);
    $query = [
      'ShowKey' => $show_key,
      'v' => time(),
    ];
    $options = ['query' => $query];
    $url = Url::fromUri($this->redirectUrl, $options);
    $headers = ['Cache-Control' => 'no-cache'];
    // Redirect to INXPO.
    return new TrustedRedirectResponse($url->toString(), 302, $headers);
  }

  /**
   * Get External Product Codes.
   *
   * @param string $product_id
   *   The Drupal product id.
   * @param bool $is_event
   *   The flag: is event.
   *
   * @return array|null
   *   The array of Product coded, otherwise NULL.
   */
  public function getExternalProductCodes($product_id = '', $is_event = FALSE) {
    if (empty($product_id)) {
      return [];
    }
    $database = \Drupal::database();
    if ($is_event) {
      $query = $database->select('commerce_product__field_external_event_codes', 'event_codes');
      $query->fields('event_codes', ['field_external_event_codes_value']);
      $query->condition('event_codes.entity_id', $product_id);
    }
    else {
      $query = $database->select('commerce_product__field_external_product_codes', 'product_codes');
      $query->fields('product_codes', ['field_external_product_codes_value']);
      $query->condition('product_codes.entity_id', $product_id);
    }
    $result = $query->execute();
    return $result->fetchAllKeyed(0, 0);
  }

  /**
   * Get Current User AMNet Id.
   *
   * @param string $uid
   *   The Drupal uid.
   *
   * @return string|null
   *   The AMNet of the user, otherwise NULL.
   */
  public function getCurrentUserAmNetId($uid = NULL) {
    if (empty($uid)) {
      return NULL;
    }
    $database = \Drupal::database();
    $query = $database->select('user__field_amnet_id', 'field_amnet');
    $query->fields('field_amnet', ['field_amnet_id_value']);
    $query->condition('field_amnet.entity_id', $uid);
    $result = $query->execute();
    return $result->fetchField(0);
  }

  /**
   * Get Login Ticket Key.
   *
   * @param string $response
   *   The response.
   *
   * @return array
   *   The array with the ticket key info, otherwise NULL.
   */
  public function getLoginTicketKey($response = NULL) {
    if (empty($response)) {
      return [
        'success' => FALSE,
        'ticket_key' => NULL,
        'error_msg' => t('It was not possible to obtain a Ticket Key, Please try again.'),
      ];
    }
    $items = explode('LoginTicketKey', $response);
    if (!(count($items) > 1)) {
      // Fail response.
      $base_string = str_replace("APICallDiagnostic=", "|", $response);
      $base_string = str_replace("OpCodesProcessed", "|", $base_string);
      $details = explode('|', $base_string);
      $error_msg = $details[1] ?? NULL;
      return [
        'success' => FALSE,
        'ticket_key' => NULL,
        'error_msg' => $error_msg,
      ];
    }
    else {
      // Sucesss response.
      $ticket_key = $items[1] ?? NULL;
      $ticket_key = !empty($ticket_key) ? trim($ticket_key) : NULL;
      return [
        'success' => TRUE,
        'ticket_key' => $ticket_key,
        'error_msg' => NULL,
      ];
    }
  }

}
