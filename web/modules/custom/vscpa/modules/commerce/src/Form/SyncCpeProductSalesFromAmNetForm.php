<?php

namespace Drupal\vscpa_commerce\Form;

use Drupal\am_net_cpe\CpeRegistrationManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vscpa_commerce\AmNetSyncManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for syncing CPE product sales from AM.net.
 */
class SyncCpeProductSalesFromAmNetForm extends FormBase {

  /**
   * The AM.net CPE registration manager.
   *
   * @var \Drupal\am_net_cpe\CpeRegistrationManagerInterface
   */
  protected $registrationManager;

  /**
   * The AM.net sync manager.
   *
   * @var \Drupal\vscpa_commerce\AmNetSyncManagerInterface
   */
  protected $syncManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vscpa_commerce_cpe_product_sales_sync_form';
  }

  /**
   * Constructs a new SyncCpeRegistrationsFromAmNetForm.
   *
   * @param \Drupal\am_net_cpe\CpeRegistrationManagerInterface $registration_manager
   *   The AM.net CPE registration manager.
   * @param \Drupal\vscpa_commerce\AmNetSyncManagerInterface $am_net_sync_manager
   *   The AM.net sync manager.
   */
  public function __construct(CpeRegistrationManagerInterface $registration_manager, AmNetSyncManagerInterface $am_net_sync_manager) {
    $this->registrationManager = $registration_manager;
    $this->syncManager = $am_net_sync_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_cpe.registration_manager'),
      $container->get('vscpa_commerce.am_net_sync_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['member'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Member ID'),
      '#description' => $this->t('Enter a member ID number.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'sync_product_sales',
      '#value' => $this->t('Fetch and sync AM.net product sales'),
      '#attributes' => [
        'class' => [
          'button--primary button--small',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $member = $form_state->getValue('member');
    $sales = $this->syncManager->pullProductSales($member);
    $operations = [];
    if (!empty($sales)) {
      foreach ($sales as $sale) {
        $operations[] = [
          [$this, 'syncAmNetCpeProductSale'],
          [$sale],
        ];
      }
    }
    if (!empty($operations)) {
      $batch = [
        'title' => t('Syncing CPE product sales from AM.net...'),
        'operations' => $operations,
        'finished' => [$this, 'syncFinishedCallback'],
      ];
      batch_set($batch);
    }
    else {
      $message = t('There are no product sales to be processed.');
      drupal_set_message($message, 'warning');
    }
  }

  /**
   * Syncs an Event registration from AM.net to Drupal.
   *
   * @param array $order
   *   The AM.net event registration record.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function syncAmNetCpeProductSale(array $order, array &$context = []) {
    $order_number = $order['OrderNumber'];
    $message = '<h3>Processing AM.net Product Sales Order #' . $order_number . '...</h3>';
    if (!empty($order)) {
      try {
        /** @var \Drupal\vscpa_commerce\AmNetSyncManagerInterface $manager */
        $manager = \Drupal::service('vscpa_commerce.am_net_sync_manager');
        if ($order = $manager->syncAmNetCpeProductSale($order)) {
          $context['results']['success'][] = [
            'number' => $order->getOrderNumber(),
            'link' => $order->toUrl()->toString(),
          ];
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('am_net_cpe')->debug($e->getMessage());
      }
    }
    $context['message'] = $message;
  }

  /**
   * Sync AM.net CPE product sales finished callback.
   *
   * @param bool $success
   *   Required param, The batch $success.
   * @param array $results
   *   Required param, The batch $results.
   * @param array $operations
   *   Required param, The batch $operations.
   */
  public static function syncFinishedCallback($success, array $results = [], array $operations = []) {
    if (!empty($results['success'])) {
      foreach ($results['success'] as $succeeded) {
        drupal_set_message(t('Successfully synced <a href="@link">Order #:number</a>', [
          ':number' => $succeeded['number'],
          '@link' => $succeeded['link'],
        ]));
      }
    }
  }

}
