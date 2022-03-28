<?php

namespace Drupal\am_net_membership\DuesPaymentPlan;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * The Dues Payment Manage implementation.
 */
class DuesPaymentPlanManager implements DuesPaymentPlanManagerInterface {

  /**
   * The memory cache.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $memoryCache;

  /**
   * The array of plans.
   *
   * @var array
   */
  protected $plans = [];

  /**
   * Constructs an Entity Storage Base instance.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface|null $cache_factory
   *   The memory cache factory.
   */
  public function __construct(KeyValueFactoryInterface $cache_factory = NULL) {
    $this->memoryCache = $cache_factory->get('dues.payment.plan.repository');
  }

  /**
   * {@inheritdoc}
   */
  public function get($uid = NULL) {
    return $this->getFromStaticCache($uid);
  }

  /**
   * Gets Plan from the static cache.
   *
   * @param string $uid
   *   The user ID.
   *
   * @return \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfoInterface|null
   *   Array of plan from the plan cache.
   */
  protected function getFromStaticCache($uid = NULL) {
    $values = NULL;
    $cache_id = $this->buildCacheId($uid);
    if (isset($this->plans[$cache_id])) {
      $values = $this->plans[$cache_id];
    }
    elseif ($cached = $this->memoryCache->get($cache_id, FALSE)) {
      $values = $cached;
      $this->plans[$cache_id] = $values;
    }
    if (!is_array($values)) {
      return NULL;
    }
    return $this->parse($values);
  }

  /**
   * Builds the cache ID for the passed in plan ID.
   *
   * @param string $uid
   *   The user ID.
   *
   * @return string|null
   *   The unique Key related to the given plan id .
   */
  public function buildCacheId($uid = NULL) {
    if (empty($uid)) {
      return NULL;
    }
    $key[] = 'payment_plan';
    $key[] = $uid;
    return implode('.', $key);
  }

  /**
   * Set the Data.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   *
   * @return \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfoInterface
   *   The 'Dues Payment Plan Info' object.
   */
  public function parse(array $values = []) {
    return DuesPaymentPlanInfo::create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($uid = NULL) {
    if (!isset($uid)) {
      return;
    }
    $cache_id = $this->buildCacheId($uid);
    $this->memoryCache->delete($cache_id);
    unset($this->plans[$cache_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function save(DuesPaymentPlanInfoInterface $plan = NULL, $uid = NULL) {
    $cache_id = $this->buildCacheId($uid);
    $values = $plan->toArray();
    $this->memoryCache->set($cache_id, $values);
    $this->plans[$cache_id] = $values;
  }

}
