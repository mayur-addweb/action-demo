<?php

namespace Drupal\am_net_firms\Controller;

use Drupal\am_net_firms\EntityAutocompleteMatcher;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\system\Controller\EntityAutocompleteController as EntityAutocompleteBaseController;

/**
 * Defines a route controller for entity auto-complete form elements.
 */
class EntityAutocompleteController extends EntityAutocompleteBaseController {

  /**
   * The auto-complete matcher for entity references.
   *
   * @var \Drupal\am_net_firms\EntityAutocompleteMatcher
   */
  protected $matcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityAutocompleteMatcher $matcher, KeyValueStoreInterface $key_value) {
    $this->matcher = $matcher;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_firms.autocomplete_matcher'),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

}
