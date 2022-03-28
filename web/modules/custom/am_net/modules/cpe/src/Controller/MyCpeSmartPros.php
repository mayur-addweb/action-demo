<?php

namespace Drupal\am_net_cpe\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for SmartPros course access.
 */
class MyCpeSmartPros extends ControllerBase {

  /**
   * The SmartPros XML Key.
   *
   * @var string
   */
  protected $litXMLKey = '<RSAKeyValue><Modulus>zIYjysaeI0JHNkC2hcC+m4HPKNlXKB26jfmDRD3/GStKP7nCtrRXYTSne00vtO7elTaiYjXlJbwNl6JI9g53zTPiFjnDO8QlT8HKmWMurZG9MwxH+uQrqPXaFUpIW+g6y72pFQKsSaf0AbxtfEPtejGv8fM2/kaO8VUkdZSdoF8=</Modulus><Exponent>AQAB</Exponent></RSAKeyValue>';

  /**
   * The SmartPros Template Url.
   *
   * @var string
   */
  protected $litTemplateUrl = "http://vscpa.smartpros.com/vscpasso.aspx";

  /**
   * Redirect to the Login or SmartPros course access.
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
    $query = [
      'returnUrl' => $return_url,
    ];
    $options = ['query' => $query];
    $base_url = $this->litTemplateUrl;
    $url = Url::fromUri($base_url, $options)->toString();
    // Redirect to SmartPros.
    return new TrustedRedirectResponse($url);
  }

}
