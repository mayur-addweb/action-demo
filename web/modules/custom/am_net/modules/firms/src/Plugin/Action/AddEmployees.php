<?php

namespace Drupal\am_net_firms\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\taxonomy\TermInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add Employees.
 *
 * @Action(
 *   id = "am_net_firms_add_employees_action",
 *   label = @Translation("Add employees to the Firm."),
 *   type = "user",
 *   confirm_form_route_name = "am_net_firms.employee_management_tool.manage_employees.add_employees_confirm"
 * )
 */
class AddEmployees extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a DeleteNode object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user) {
    $this->currentUser = $current_user;
    $this->tempStoreFactory = $temp_store_factory;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.private_tempstore'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $firm = NULL;
    foreach (\Drupal::routeMatch()->getParameters() as $param) {
      if ($param instanceof TermInterface) {
        $firm = $param;
        break;
      }
    }
    $params = [
      'accounts' => $entities,
      'firm' => $firm,
    ];
    $this->tempStoreFactory->get('user_operations_add_employees')->set($this->currentUser->id(), $params);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)->andIf($object->access('update', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

}