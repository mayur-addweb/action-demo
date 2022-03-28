<?php

namespace Drupal\am_net_firms\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\user\UserStorageInterface;
use Drupal\am_net_firms\EmployeeManagementTool;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for adding multiple Employees into a Firm.
 */
class AddEmployeesConfirm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The Employee Management Tool service.
   *
   * @var \Drupal\am_net_firms\EmployeeManagementTool
   */
  protected $employeeManagementTool;

  /**
   * The list of accounts.
   *
   * @var \Drupal\user\Entity\User[]
   */
  protected $accounts = [];

  /**
   * The Firm.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $firm;

  /**
   * The current destination.
   *
   * @var string
   */
  protected $destination;

  /**
   * Constructs a new UserMultipleAddConfirm.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\am_net_firms\EmployeeManagementTool $employee_management_tool
   *   The Employee Management Tool service.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, UserStorageInterface $user_storage, EmployeeManagementTool $employee_management_tool) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->userStorage = $user_storage;
    $this->employeeManagementTool = $employee_management_tool;
    $current_user_id = $this->currentUser()->id();
    $params = $this->tempStoreFactory->get('user_operations_add_employees')->get($current_user_id);
    $this->firm = isset($params['firm']) ? $params['firm'] : NULL;
    $this->accounts = isset($params['accounts']) ? $params['accounts'] : [];
    $this->destination = \Drupal::destination()->get();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('am_net_firms.employee_management_tool')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_firms.employee_management_tool.manage_employees.add_employees_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to add these employees into the Firm?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.user.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Add Employee');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the accounts to be canceled from the temp store.
    $current_user_id = $this->currentUser()->id();
    /* @var \Drupal\taxonomy\TermInterface $firm */
    $firm = $this->firm;
    /* @var \Drupal\user\Entity\User[] $accounts */
    $accounts = $this->accounts;
    if (!$accounts) {
      return $this->redirect($this->destination);
    }
    // Header.
    $title = $this->t('Manage Employees/Pay Dues');
    $title .= ' <small>' . $this->t('Confirm Addition of Employee(s)') . '</small>';
    $description = '<br><p>' . $this->t('Are you sure you want to add the following employee(s) to the firm/company?') . '<p>';
    $form['header'] = [
      '#markup' => '<div class="page-header"><h1 class="accent-left purple">' . $title . '</h1></div>' . $description,
    ];
    $root = NULL;
    $names = [];
    $form['accounts'] = ['#tree' => TRUE];
    $delta = 1;
    foreach ($accounts as $account) {
      $uid = $account->id();
      $delta += 1;
      $names[$uid] = [
        '#markup' => $this->employeeManagementTool->getUserSummary($account),
        '#wrapper_attributes' => [
          'class' => [
            'list-group-item',
          ],
        ],
      ];
      // Prevent current user of change their linked firm.
      if ($uid == $current_user_id) {
        $root = $account;
        continue;
      }
      $form['accounts'][$uid] = [
        '#type' => 'hidden',
        '#value' => $uid,
      ];
    }
    $form['account']['names'] = [
      '#theme' => 'item_list',
      '#items' => $names,
      '#list_type' => 'ul',
      '#attributes' => [
        'class' => [
          'list-group',
        ],
      ],
    ];
    // Output a notice that the current user can not change their linked firm.
    if (isset($root)) {
      $redirect = (count($accounts) == 1);
      $message = $this->t('You(%name) cannot change your linked Firm from here.', ['%name' => $root->label()]);
      drupal_set_message($message, $redirect ? 'error' : 'warning');
      // If only user 1 was selected, redirect to the overview.
      if ($redirect) {
        $keywords = $this->getRequest()->get('keywords');
        $options = [];
        if (!empty($keywords)) {
          $options = [
            'query' => ['keywords' => $keywords],
            'absolute' => TRUE,
          ];
        }
        return $this->redirect('am_net_firms.employee_management_tool.find_employees', ['user' => $current_user_id, 'firm' => $firm->id()], $options);
      }
    }
    $form['operation'] = ['#type' => 'hidden', '#value' => 'cancel'];
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user_id = $this->currentUser()->id();
    /* @var \Drupal\user\Entity\User[] $accounts */
    $accounts = $this->accounts;
    if (!empty($accounts)) {
      // Clear out the accounts from the temp store.
      $this->tempStoreFactory->get('user_operations_add_employees')->delete($current_user_id);
      if ($form_state->getValue('confirm')) {
        foreach ($accounts as $delta => $account) {
          // Prevent user administrators from changing themselves.
          if ($account->id() == $current_user_id) {
            continue;
          }
          // Link a given user to a given Firm.
          $this->employeeManagementTool->linkUserToFirm($this->firm, $account);
          // Show confirm message.
          $message = $this->t('The employee (@employee) has been linked to the firm.', ['@employee' => $account->getEmail()]);
          drupal_set_message($message);
        }
      }
    }
    // Redirect.
    $form_state->setRedirectUrl(Url::fromRoute('am_net_firms.employee_management_tool.manage_employees', ['user' => $current_user_id, 'firm' => $this->firm->id()], []));
  }

}
