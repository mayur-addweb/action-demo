<?php

namespace Drupal\vscpa_commerce\PeerReview\Controller;

use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\vscpa_commerce\PeerReview\PeerReviewRatesTrait;

/**
 * Controller Peer Review.
 */
class PeerReviewController extends ControllerBase {

  use PeerReviewRatesTrait;

  /**
   * Access Checker validation.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|true
   *   TRUE if the users has access otherwise a Redirect Response.
   */
  public function accessChecker() {
    $current_user = $this->currentUser();
    $peer_review_administrative_fees_node_id = $this->rates->getPeerReviewAdministrativeFeesNodeId();
    if ($current_user->isAnonymous()) {
      $login_link = Link::createFromRoute($this->t('Log in'), 'user.login');
      $message = $this->t('Please @link to complete the Peer Review Payment!. Questions?, please contact the VSCPA Peer Review Team at (800) 733-8272 or <a href="mailto:peerreview@vscpa.com">peerreview@vscpa.com</a>.', ['@link' => $login_link->toString()]);
      $this->messenger()->addMessage($message);
      return $this->redirect('entity.node.canonical', ['node' => $peer_review_administrative_fees_node_id]);
    }
    return TRUE;
  }

  /**
   * Generates an overview table Peer Review process.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   An array as expected by drupal_render().
   */
  public function render() {
    $access = $this->accessChecker();
    if ($access instanceof RedirectResponse) {
      return $access;
    }
    $build['#title'] = $this->t('Peer Review Payment.');
    $form_class = 'Drupal\vscpa_commerce\PeerReview\Form\PeerReviewForm';
    $build['form'] = $this->formBuilder()->getForm($form_class);
    return $build;
  }

  /**
   * Generates an overview table Peer Review process.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   An array as expected by drupal_render().
   */
  public function billingInfo(Request $request) {
    $access = $this->accessChecker();
    if ($access instanceof RedirectResponse) {
      return $access;
    }
    $aicpa_number = $request->query->get('aicpa-number');
    if (empty($aicpa_number)) {
      $message = $this->t('Please provide a valid VSCPA AICPA Firm ID.');
      $this->messenger()->addWarning($message);
      return $this->redirect('vscpa_commerce.peer_review');
    }
    $build['#title'] = $this->t('Peer Review Payment | Billing Info.');
    $form_class = 'Drupal\vscpa_commerce\PeerReview\Form\PeerReviewBillingInfoForm';
    $build['form'] = $this->formBuilder()->getForm($form_class);
    return $build;
  }

}
