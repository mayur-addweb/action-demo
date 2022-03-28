<?php

namespace Drupal\rss_list\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drupal\rss_list\Entity\RssPageInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for RSS Feed routes.
 */
class RssFeedController extends ControllerBase {

  /**
   * Displays and given RSS feed.
   *
   * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Render XML response, otherwise a redirect.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function render(RssPageInterface $feed = NULL) {
    $response = new Response();
    $response->headers->set('Content-Type', 'application/xml; charset=utf-8');
    /** @var \Drupal\rss_list\RssFeedHelper $helper */
    $helper = \Drupal::service('rss_list.helper');
    // Get Articles.
    $articles = $helper->getFeedItems($feed);
    // Get the site host.
    $host = \Drupal::request()->getSchemeAndHttpHost();
    // Gather Channel Info.
    $channel_title = $feed->label();
    $channel_link = $feed->toUrl()->setAbsolute(TRUE)->toString();
    $channel_feed_link = $host . $feed->getFeedPath();
    $channel_description = $feed->getFeedChannelDescription();
    $channel_lenguage = 'en-us';
    // Format the RSS feed content.
    $rss_feed = $helper->formatFeedItems($articles, $channel_title, $channel_lenguage, $channel_description, $channel_link, $channel_feed_link);
    // Set the Content.
    $response->setContent($rss_feed);
    // Return the feed.
    return $response;
  }

}
