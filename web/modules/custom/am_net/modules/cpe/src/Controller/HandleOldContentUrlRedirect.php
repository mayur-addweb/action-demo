<?php

namespace Drupal\am_net_cpe\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for handle Old content URLs Redirects.
 */
class HandleOldContentUrlRedirect extends ControllerBase {

  /**
   * Redirect old event url to the new event URL.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function redirectEvent(Request $request) {
    // Define catalog Route.
    $cpe_catalog_route = 'entity.node.canonical';
    $cpe_catalog_route_arguments = ['node' => '16'];
    // Items.
    $items = [
      'courseID',
      'courseid',
      'CourseID',
    ];
    // Get the Course ID from the request parameters.
    foreach ($items as $delta => $item) {
      $course_id = $request->query->get($item);
      if (!empty($course_id)) {
        break;
      }
    }
    if (empty($course_id)) {
      // Redirect user to the 'CPE Catalog' Page.
      return $this->redirect($cpe_catalog_route, $cpe_catalog_route_arguments);
    }
    $event_year = substr($course_id, 0, 2);
    $event_code = str_replace($event_year, '', $course_id);
    if (empty($event_year) || empty($event_code)) {
      // Redirect user to the 'CPE Catalog' Page.
      return $this->redirect($cpe_catalog_route, $cpe_catalog_route_arguments);
    }
    $event_year = trim($event_year);
    $event_code = trim($event_code);
    $product = $this->getDrupalCpeEventProduct($event_code, $event_year);
    if (!$product) {
      // Redirect user to the 'CPE Catalog' Page.
      return $this->redirect($cpe_catalog_route, $cpe_catalog_route_arguments);
    }
    return $this->redirect('entity.commerce_product.canonical', ['commerce_product' => $product->id()]);
  }

  /**
   * Gets a Drupal CPE event product for the given event code and year.
   *
   * @param string $event_code
   *   The AM.net event code.
   * @param string $event_year
   *   The AM.net event year (two digits).
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The product entity, or NULL if not found.
   */
  public function getDrupalCpeEventProduct($event_code, $event_year) {
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_amnet_event_id', 'amnet_event_id');
    $query->fields('amnet_event_id', ['entity_id']);
    $query->condition('field_amnet_event_id_code', $event_code);
    $query->condition('field_amnet_event_id_year', $event_year);
    $entity_id = $query->execute()->fetchField();
    $event = NULL;
    /** @var \Drupal\commerce_product\Entity\ProductInterface $event */
    if (!empty($entity_id)) {
      $event = Product::load($entity_id);
    }
    return $event;
  }

  /**
   * Redirect old product url to the new event URL.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function redirectProduct(Request $request) {
    // Define catalog Route.
    $cpe_catalog_route = 'entity.node.canonical';
    $cpe_catalog_route_arguments = ['node' => '16'];
    // Items.
    $items = [
      'ProductID',
      'productid',
      'ProductId',
      'Productid',
      'ProductId',
    ];
    // Get the Product ID from the request parameters.
    foreach ($items as $delta => $item) {
      $product_id = $request->query->get($item);
      if (!empty($product_id)) {
        break;
      }
    }
    if (empty($product_id)) {
      // Redirect user to the 'CPE Catalog' Page.
      return $this->redirect($cpe_catalog_route, $cpe_catalog_route_arguments);
    }
    if (empty($product_id)) {
      // Redirect user to the 'CPE Catalog' Page.
      return $this->redirect($cpe_catalog_route, $cpe_catalog_route_arguments);
    }
    $product_id = trim($product_id);
    $product = $this->getDrupalCpeSelfStudyProduct($product_id);
    if (!$product) {
      // Redirect user to the 'CPE Catalog' Page.
      return $this->redirect($cpe_catalog_route, $cpe_catalog_route_arguments);
    }
    return $this->redirect('entity.commerce_product.canonical', ['commerce_product' => $product->id()]);
  }

  /**
   * Gets a Drupal Self-study CPE product for the given product code.
   *
   * @param string $product_code
   *   The AM.net product code.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The product entity, or NULL if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDrupalCpeSelfStudyProduct($product_code) {
    $products = \Drupal::entityManager()
      ->getStorage('commerce_product')
      ->loadByProperties([
        'field_course_prodcode' => $product_code,
      ]);
    return $products ? current($products) : NULL;
  }

}
