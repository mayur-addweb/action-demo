<?php

namespace Drupal\vscpa_commerce\PaymentHistory\Controller;

use Psr\Http\Message\ResponseInterface;
use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\TransferException;
use Drupal\am_net\AssociationManagementClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;

/**
 * CPE Certificate Controller.
 */
class CpeCertificateController extends ControllerBase {

  /**
   * The AM.net REST API client.
   *
   * @var \Drupal\am_net\AssociationManagementClient
   */
  protected $client;

  /**
   * The HttpFoundationFactoryInterface.
   *
   * @var \Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface
   */
  private $httpFoundationFactory;

  /**
   * Constructs a new CPE Certificate Controller object.
   *
   * @param \Drupal\am_net\AssociationManagementClient $client
   *   The AM.net REST API client.
   * @param \Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface $httpFoundationFactory
   *   The HttpFoundationInterface.
   */
  public function __construct(AssociationManagementClient $client, HttpFoundationFactoryInterface $httpFoundationFactory) {
    $this->client = $client;
    $this->httpFoundationFactory = $httpFoundationFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net.client'),
      $container->get('psr7.http_foundation_factory')
    );
  }

  /**
   * Get Payment History Redirect.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response pointing to the redirect page.
   */
  public function getPaymentHistoryRedirect() {
    $current_user = $this->currentUser();
    return $this->redirect('vscpa_commerce.payment_history', ['user' => $current_user->id()]);
  }

  /**
   * Access Checker validation.
   *
   * @param string $id
   *   The user's AM.net ID.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|true
   *   TRUE if the users has access otherwise a redirect response.
   */
  public function accessChecker($id = NULL) {
    if (empty($id)) {
      return $this->getPaymentHistoryRedirect();
    }
    $current_user = $this->currentUser();
    $is_administrator = in_array('administrator', $current_user->getRoles());
    $is_firm_administrator = in_array('firm_administrator', $current_user->getRoles());
    $is_admin = $is_administrator || $is_firm_administrator;
    if ($is_admin) {
      return TRUE;
    }
    // @todo complete access checker validation.
    return TRUE;
  }

  /**
   * Trigger the download of the CPE Certificate.
   *
   * @param string $id
   *   The user's AM.net ID.
   * @param string $event_code
   *   The event code.
   * @param string $event_year
   *   The event year.
   *
   * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\RedirectResponse
   *   An array as expected by drupal_render().
   */
  public function download($id = NULL, $event_code = NULL, $event_year = NULL) {
    $id = trim($id);
    $event_code = trim($event_code);
    $event_year = trim($event_year);
    $access = $this->accessChecker($id);
    if ($access instanceof RedirectResponse) {
      return $access;
    }
    $certificate_not_available = FALSE;
    // Request the binary Certificate from the AM.net API.
    try {
      $response = $this->getCertificateResponse($id, $event_code, $event_year);
    }
    catch (TransferException $e) {
      $certificate_not_available = TRUE;
    }
    if (!($response instanceof ResponseInterface)) {
      $certificate_not_available = TRUE;
    }
    if ($certificate_not_available) {
      $message_params = [
        '@event-code' => $event_code,
        '@event-year' => $event_year,
      ];
      $message = $this->t('The certificate related to the event <strong>@event-code/@event-year</strong> is not available. Your certificate will be ready within 3 weeks of the event.', $message_params);
      $this->messenger()->addWarning($message);
      $error_message = $response->getErrorMessage();
      if (!empty($error_message)) {
        $this->getLogger('vscpa_commerce')->warning($error_message);
      }
      return $this->getPaymentHistoryRedirect();
    }
    // Determine the filename from the GET parameter.
    $filename = 'cpe-certificate' . $id . '-' . $event_code . '-' . $event_year . '.pdf';
    // Make the drawing automatically download instead of opening in a tab.
    $httpResponse = $this->httpFoundationFactory->createResponse($response);
    $httpResponse->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

    return $httpResponse;

  }

  /**
   * Get the CPE Certificate Response.
   *
   * @param string $id
   *   The user's AM.net ID.
   * @param string $event_code
   *   The event code.
   * @param string $event_year
   *   The event year.
   *
   * @return mixed
   *   The results of the request.
   */
  public function getCertificateResponse($id = NULL, $event_code = NULL, $event_year = NULL) {
    $params = [
      'id' => $id,
      'eventCode' => $event_code,
      'eventYear' => $event_year,
      'output' => 'PDF',
    ];
    return $this->client->get('/report/events/cpecertificate', $params, 'raw');
  }

}
