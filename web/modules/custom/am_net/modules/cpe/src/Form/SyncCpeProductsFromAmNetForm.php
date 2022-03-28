<?php

namespace Drupal\am_net_cpe\Form;

use Drupal\am_net\AmNetRecordExcludedException;
use Drupal\am_net\AmNetRecordNotFoundException;
use Drupal\am_net_cpe\CpeProductManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for syncing CPE products from AM.net.
 */
class SyncCpeProductsFromAmNetForm extends FormBase {

  /**
   * The AM.net CPE product manager.
   *
   * @var \Drupal\am_net_cpe\CpeProductManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_cpe_products_sync_form';
  }

  /**
   * Constructs a new SyncEventsFromAmNetForm.
   *
   * @param \Drupal\am_net_cpe\CpeProductManagerInterface $product_manager
   *   The AM.net events manager.
   */
  public function __construct(CpeProductManagerInterface $product_manager) {
    $this->manager = $product_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_cpe.product_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['sync_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Sync range'),
      '#options' => [
        'all' => $this->t('All'),
        'code' => $this->t('By product code'),
      ],
      '#default_value' => 'code',
      '#required' => TRUE,
    ];

    $select_by_id_condition = [
      'select[name="sync_option"]' => ['value' => 'code'],
    ];
    $form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product code'),
      '#description' => $this->t('Enter the product code'),
      '#states' => [
        'visible' => $select_by_id_condition,
        'required' => $select_by_id_condition,
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'sync_products',
      '#value' => $this->t('Fetch and sync AM.net CPE product(s)'),
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
    $sync_option = $form_state->getValue('sync_option');
    $code = $form_state->getValue('code');
    switch ($sync_option) {
      case 'code':
        $products = [$code];
        break;

      case 'all':
        try {
          $products = $this->manager->getAmNetProductCodes();
        }
        catch (AmNetRecordNotFoundException $e) {
          $this->logger('am_net_cpe')->error($e->getMessage());
        }
        break;
    }
    $operations = [];
    if (!empty($products)) {
      foreach ($products as $key => $code) {
        $operations[] = [
          [$this, 'syncAmNetCpeProduct'],
          [$code],
        ];
      }
    }
    if (!empty($operations)) {
      $batch = [
        'title' => t('Syncing CPE products from AM.net...'),
        'operations' => $operations,
        'finished' => [$this, 'syncFinishedCallback'],
      ];
      batch_set($batch);
    }
    else {
      $message = t('There are no pending CPE products to be processed!.');
      drupal_set_message($message, 'warning');
    }
  }

  /**
   * Syncs a Self-study CPE product from AM.net.
   *
   * @param string $code
   *   The AM.net product code.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function syncAmNetCpeProduct($code, array &$context = []) {
    $message = '<h3>Processing Product: ' . $code . '...</h3>';
    if (!empty($code)) {
      $code = trim($code);
      try {
        /** @var \Drupal\am_net_cpe\CpeProductManagerInterface $manager */
        $manager = \Drupal::service('am_net_cpe.product_manager');
        $product = $manager->syncAmNetCpeSelfStudyProduct($code);
        if (!is_null($product)) {
          $context['results']['success'][] = [
            'code' => $code,
            'link' => $product->toUrl()->toString(),
            'title' => $product->label(),
          ];
          // Save the ID on the state.
          $state = \Drupal::state();
          $key = "amnet.synchronized.cpe_product";
          $values = $state->get($key, []);
          $values[] = $product->id();
          $state->set($key, $values);
        }
      }
      catch (AmNetRecordExcludedException $e) {
        $context['results']['excluded'][] = [
          'code' => $code,
          'message' => $e->getMessage(),
        ];
      }
      catch (\Exception $e) {
        \Drupal::logger('am_net_cpe')->debug($e->getMessage());
      }
    }
    $context['message'] = $message;
  }

  /**
   * Sync AM.net CPE events finished callback.
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
        drupal_set_message(t('Successfully synced <a href="@link">:title (:code)</a>', [
          ':code' => $succeeded['code'],
          ':title' => $succeeded['title'],
          '@link' => $succeeded['link'],
        ]));
      }
    }

    if (!empty($results['excluded'])) {
      foreach ($results['excluded'] as $excluded) {
        drupal_set_message(t('Could not sync :code (:message)', [
          ':message' => $excluded['message'],
          ':code' => $excluded['code'],
        ]), 'warning');
      }
    }
  }

}
