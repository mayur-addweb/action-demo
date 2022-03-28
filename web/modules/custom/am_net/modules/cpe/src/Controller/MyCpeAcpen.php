<?php

namespace Drupal\am_net_cpe\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for ACPEN course access.
 */
class MyCpeAcpen extends ControllerBase {

  /**
   * Redirect to the Login or ACPEN course access.
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
    $user = $this->currentUser();
    $email = $user->getEmail();
    $return_url = Url::fromUri('internal:/MyCPE', ['absolute' => TRUE])->toString();
    $query = [
      'agent' => '81',
      'certificate' => '3ce191f47c38317c727cc89c5a',
      'email' => $email,
      'return_url' => $return_url,
    ];
    // Get ProductCode - GET parameter.
    $product_code = $request->query->get('product_code');
    if (!empty($product_code)) {
      $query['product_code'] = $product_code;
    }
    // Get Event Year.
    $event_year = $request->query->get('event_year');
    if (!empty($event_year)) {
      $query['event_year'] = $event_year;
    }
    // Get Event Code.
    $event_code = $request->query->get('event_code');
    if (!empty($event_code)) {
      $query['event_code'] = $event_code;
    }
    $options = ['query' => $query];
    $base_url = 'https://vscpa.acpen.com/remote/redirect';
    $url = Url::fromUri($base_url, $options)->toString();
    // Redirect to ACPEN.
    return new TrustedRedirectResponse($url);
  }

}
