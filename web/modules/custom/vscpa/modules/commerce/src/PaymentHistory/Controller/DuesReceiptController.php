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
 * CPE Receipt Controller.
 */
class DuesReceiptController extends ControllerBase {

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
   * Constructs a new CPE Receipt Controller object.
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
   * Trigger the download of the CPE Receipt.
   *
   * @param string $id
   *   The user's AM.net ID.
   * @param string $event_code
   *   The event code.
   * @param string $event_year
   *   The event year.
   * @param string $transaction_date
   *   The transaction date
   *
   * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\RedirectResponse
   *   An array as expected by drupal_render().
   */
  public function download($id = NULL, $transaction_date = NULL) {
    $transaction_date = trim($transaction_date);
    $access = $this->accessChecker($id);
    if ($access instanceof RedirectResponse) {
      return $access;
    }
    $receipt_not_available = FALSE;
    // Request the binary Receipt from the AM.net API.
    try {
      $response = $this->getReceiptResponse($id, $transaction_date);
    }
    catch (TransferException $e) {
      $receipt_not_available = TRUE;
    }
    if (!($response instanceof ResponseInterface)) {
      $receipt_not_available = TRUE;
    }
    if ($receipt_not_available) {
      $message_params = [
        '@transaction-date' => $transaction_date,
        '@id' => $id,
      ];
      $message = $this->t('The Receipt related to the dues <strong>@id/@transaction-date</strong> is not available. Your Receipt will be ready within 3 weeks of the event.', $message_params);
      $this->messenger()->addWarning($message);
      $error_message = $response->getErrorMessage();
      if (!empty($error_message)) {
        $this->getLogger('vscpa_commerce')->warning($error_message);
      }
      return $this->getPaymentHistoryRedirect();
    }
    // Determine the filename from the GET parameter.
    $filename = 'dues-Receipt' . $id . '-' . $transaction_date . '.pdf';
    // Make the drawing automatically download instead of opening in a tab.
    $httpResponse = $this->httpFoundationFactory->createResponse($response);
    $httpResponse->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    return $httpResponse;
  }

  /**
   * Get the CPE Receipt Response.
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
  public function getReceiptResponse($id = NULL, $transaction_date = NULL) {
    $params = [
      'id' => $id,
      'beginTxDt' => $transaction_date,
      'endTxDt' => $transaction_date,
      'output' => 'PDF',
    ];
    return $this->client->get('/report/dues/paymentacknowledgement', $params, 'raw');
  }

}
