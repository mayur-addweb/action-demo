<?php

namespace Drupal\rss_list\Routing;

use Symfony\Component\Routing\Route;
use Drupal\rss_list\Entity\RssPage;

/**
 * Defines a dynamic RSS feed routes.
 */
class FeedRoutes {

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $query = \Drupal::entityQuery('rss_page');
    $query->condition('status', 1);
    $query->condition('enable_feed', 1);
    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }
    $feeds = RssPage::loadMultiple($ids);
    $routes = [];
    /** @var \Drupal\rss_list\Entity\RssPage $feed */
    foreach ($feeds as $delta => $feed) {
      $path = $feed->getFeedPath();
      $id = $feed->id();
      if (empty($path)) {
        continue;
      }
      $routes["rss_feed.$id.xml"] = new Route(
        $path,
        [
          '_controller' => '\Drupal\rss_list\Controller\RssFeedController::render',
          'feed' => $id,
        ],
        ['_access' => 'TRUE'],
        ['parameters' => ['feed' => ['type' => 'entity:rss_page']]]
      );
    }
    return $routes;
  }

}
