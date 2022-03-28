<?php

namespace Drupal\rss_list;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\smart_trim\Truncate\TruncateHTML;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\rss_list\Entity\RssPageInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Html;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;

/**
 * RSS Feed Helper.
 */
class RssFeedHelper {

  /**
   * The Prefix CDATA.
   *
   * @var string
   */
  protected $prefixCdata = '<![CDATA[';

  /**
   * The suffix CDATA.
   *
   * @var string
   */
  protected $suffixCdata = ']]>';

  /**
   * Formats a given list of node id's into RSS items.
   *
   * @param \Drupal\node\Entity\Node[] $articles
   *   The base list of nodes to be displayed as RSS items.
   * @param string $channel_title
   *   The channel title.
   * @param string $channel_language
   *   The channel language.
   * @param string $channel_description
   *   The channel description.
   * @param string $channel_link
   *   The channel link.
   * @param string $channel_feed_link
   *   The channel feed link.
   *
   * @return string
   *   The formatted RSS items.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function formatFeedItems(array $articles = [], $channel_title = 'VSCPA RSS Feed', $channel_language = 'en-us', $channel_description = NULL, $channel_link = NULL, $channel_feed_link = NULL) {
    // Namespaces.
    $namespaces = [
      'xmlns:atom' => 'http://www.w3.org/2005/Atom',
    ];
    $items = '';
    foreach ($articles as $article) {
      $node_title = $article->label();
      $node_url = $article->toUrl()->setAbsolute(TRUE)->toString();
      $rss_elements = [
        [
          'key' => 'pubDate',
          'value' => $this->getNodePubDate($article, 'r'),
        ],
        [
          'key' => 'guid',
          'value' => $node_url,
          'attributes' => ['isPermaLink' => 'true'],
        ],
      ];
      $article_teaser = $this->getArticleTeaser($article);
      $excerpt = NULL;
      if ($article_teaser) {
        // Alter the title.
        $article_teaser_title = $article_teaser->label();
        if (!empty($article_teaser_title)) {
          $node_title = $article_teaser_title;
        }
        // Generate item summary.
        $excerpt = $article_teaser->get('field_summary')->getString();
        // Get Featured Image.
        $image_url = $this->getArticleTeaserFeaturedImageUrl($article_teaser);
        if (!empty($image_url)) {
          $rss_elements[] = [
            'key' => 'image',
            'value' => $this->wrapperCdata($image_url),
            'encoded' => TRUE,
          ];
        }
      }
      // Handle AM.Net Categories.
      $categories = [];
      // 1. Field of Interest.
      $codes = $this->getAmNetCategories('field_field_of_interest', 'field_amnet_interest_code', $article);
      if (!empty($codes)) {
        $categories = array_merge($categories, $codes);
      }
      if (!empty($categories)) {
        $rss_elements[] = [
          'key' => 'category',
          'value' => $this->wrapperCdata(implode(',', $categories)),
          'encoded' => TRUE,
        ];
      }
      if (empty($excerpt)) {
        $excerpt = $this->extractSummaryDescription('body', $article);
      }
      $items .= $this->formatRssItem($node_title, $node_url, $excerpt, $rss_elements);
    }
    // Define Channel arguments.
    $channel = [
      'version' => '2.0',
      'title' => $channel_title,
      'link' => $channel_link,
      'description' => $channel_description,
      'language' => $channel_language,
    ];
    /*
     * - Channel extras: Associative array with fields:
     *   - 'key': element name
     *   - 'value': element contents
     *   - 'attributes': associative array of element attributes
     */
    $channel_extras = [
      [
        'key' => 'atom:link',
        'attributes' => [
          'href' => $channel_feed_link,
          'rel' => 'self',
          'type' => 'application/rss+xml',
        ],
      ],
      [
        'key' => 'ttl',
        'value' => 60,
      ],
    ];
    $output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
    $output .= "<rss version=\"" . $channel["version"] . "\" xml:base=\"" . $channel_link . "\" " . new Attribute($namespaces) . ">\n";
    $output .= $this->formatRssChannel($channel['title'], $channel['link'], $channel['description'], $items, $channel['language'], $channel_extras);
    $output .= "</rss>\n";
    return $output;
  }

  /**
   * Get the node pub date.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node object.
   * @param string $format
   *   The format of the outputted date string.
   *
   * @return string
   *   The pub date.
   */
  public function getNodePubDate(Node $node = NULL, $format = NULL) {
    $time = $node->getChangedTime();
    if (!empty($time)) {
      return gmdate($format, $time);
    }
    return '';
  }

  /**
   * Get the Article Teaser attached to a publication article.
   *
   * @param \Drupal\node\Entity\Node $article
   *   The article node.
   *
   * @return \Drupal\node\Entity\Node|bool
   *   The Article Teaser.
   */
  public function getArticleTeaser(Node $article = NULL) {
    if (!$article) {
      return FALSE;
    }
    $field = $article->get('field_article_teaser');
    if ($field->isEmpty()) {
      return FALSE;
    }
    $teasers = $field->referencedEntities();
    return current($teasers);
  }

  /**
   * Get the Article Teaser featured image URL.
   *
   * @param \Drupal\node\Entity\Node $article_teaser
   *   The article node.
   *
   * @return string|false
   *   The Article Teaser featured image URL.
   */
  public function getArticleTeaserFeaturedImageUrl(Node $article_teaser = NULL) {
    if (!$article_teaser) {
      return FALSE;
    }
    $field = $article_teaser->get('field_featured_image');
    if ($field->isEmpty()) {
      return FALSE;
    }
    $files = $field->referencedEntities();
    /** @var \Drupal\file\FileInterface $file */
    $file = current($files);
    $uri = $file->getFileUri();
    if (empty($uri)) {
      return FALSE;
    }
    return file_create_url($uri);
  }

  /**
   * Wrapper a given text in a CDATA tag.
   *
   * @param string $content
   *   The given content.
   *
   * @return string
   *   The formatted content
   */
  public function wrapperCdata($content = NULL) {
    return "<![CDATA[$content]]>";
  }

  /**
   * Get AM.Net Categories from publication.
   *
   * @param string $field_name
   *   The field name.
   * @param string $field_amnet_code
   *   The field for the Am.Net code.
   * @param \Drupal\node\NodeInterface $entity
   *   The Publication node.
   *
   * @return array
   *   The AM.Net Categories.
   */
  public function getAmNetCategories($field_name = NULL, $field_amnet_code = NULL, NodeInterface $entity = NULL) {
    $field = $entity->get($field_name);
    if ($field->isEmpty()) {
      return [];
    }
    $ids = [];
    /** @var \Drupal\taxonomy\TermInterface $term */
    $terms = $field->referencedEntities();
    foreach ($terms as $delta => $term) {
      if (!$term->hasField($field_amnet_code)) {
        continue;
      }
      $code = $term->get($field_amnet_code)->getString();
      if (empty($code)) {
        continue;
      }
      $ids[] = $code;
    }
    return $ids;
  }

  /**
   * Extract Summary Description.
   *
   * @param string $field_name
   *   The body field name.
   * @param \Drupal\node\NodeInterface $entity
   *   The RSS page.
   *
   * @return string
   *   The Summary Description.
   */
  public function extractSummaryDescription($field_name = NULL, NodeInterface $entity = NULL) {
    $field = $entity->get($field_name);
    if ($field->isEmpty()) {
      return NULL;
    }
    $body = $entity->get('body');
    if ($body->isEmpty()) {
      return NULL;
    }
    try {
      $value = $body->first()->getValue();
    }
    catch (MissingDataException $e) {
      return NULL;
    }
    $body_summary = $value['summary'] ?? NULL;
    $body_value = $value['value'] ?? NULL;
    $summary = empty($body_summary) ? $body_value : $body_summary;
    $truncate = new TruncateHTML();
    $summary = $truncate->truncateChars(PlainTextOutput::renderFromHtml($summary), 241);
    return $summary;
  }

  /**
   * Formats a single RSS item.
   *
   * Arbitrary elements may be added using the $args associative array.
   *
   * @param string $title
   *   The title of the RSS Item.
   * @param string $link
   *   The link of the RSS Item.
   * @param string $description
   *   The description of the RSS Item.
   * @param array $args
   *   The additional attributes associated with the RSS item.
   *
   * @return string
   *   The formatted RSS item.
   */
  public function formatRssItem($title, $link, $description, array $args = []) {
    $output = "<item>\n";
    $output .= ' <title>' . $this->wrapperCdata(Html::escape($title)) . "</title>\n";
    $output .= ' <link>' . $this->wrapperCdata(Html::escape(UrlHelper::stripDangerousProtocols($link))) . "</link>\n";
    $output .= ' <description>' . $this->wrapperCdata($this->formatDescription($description)) . "</description>\n";
    $output .= $this->formatXmlElements($args);
    $output .= "</item>\n";
    return $output;
  }

  /**
   * Format Description.
   *
   * @param string $description
   *   The description.
   *
   * @return string
   *   The formatted description.
   */
  public function formatDescription($description = NULL) {
    if (empty($description)) {
      return NULL;
    }
    $description = PlainTextOutput::renderFromHtml($description);
    $description = str_ireplace(["\r", "\n", '\r', '\n'], '', $description);
    return $description;
  }

  /**
   * Formats XML elements.
   *
   * @param array $array
   *   An array where each item represents an element and is either a:
   *   - (key => value) pair (<key>value</key>)
   *   - Associative array with fields:
   *     - 'key': element name
   *     - 'value': element contents
   *     - 'attributes': associative array of element attributes
   *     - 'encoded': TRUE if 'value' is already encoded
   *
   *   In both cases, 'value' can be a simple string, or it can be another array
   *   with the same format as $array itself for nesting.
   *
   *   If 'encoded' is TRUE it is up to the caller to ensure that 'value' is
   *   either entity-encoded or CDATA-escaped. Using this option is not
   *   recommended when working with untrusted user input, since failing to
   *   escape the data correctly has security implications.
   *
   * @return string
   *   The formatted Xml elements.
   */
  public function formatXmlElements(array $array = []) {
    $output = '';
    foreach ($array as $key => $value) {
      if (is_numeric($key)) {
        if ($value['key']) {
          $output .= ' <' . $value['key'];
          if (isset($value['attributes']) && is_array($value['attributes'])) {
            $output .= new Attribute($value['attributes']);
          }

          if (isset($value['value']) && $value['value'] != '') {
            $output .= '>' . (is_array($value['value']) ? $this->formatXmlElements($value['value']) : (!empty($value['encoded']) ? $value['value'] : Html::escape($value['value']))) . '</' . $value['key'] . ">\n";
          }
          else {
            $output .= " />\n";
          }
        }
      }
      else {
        $output .= ' <' . $key . '>' . (is_array($value) ? $this->formatXmlElements($value) : Html::escape($value)) . "</$key>\n";
      }
    }
    return $output;
  }

  /**
   * Formats an RSS channel.
   *
   * Arbitrary elements may be added using the $args associative array.
   *
   * @param string $title
   *   The title of the RSS Channel.
   * @param string $link
   *   The link of the RSS Channel.
   * @param string $description
   *   The description of the RSS Channel.
   * @param string $items
   *   The array of items.
   * @param string $langcode
   *   The langcode of the RSS Channel.
   * @param array $args
   *   The additional attributes associated with the RSS Channel.
   *
   * @return string
   *   The formatted RSS channel.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function formatRssChannel($title, $link, $description, $items, $langcode = 'en', array $args = []) {
    $output = "<channel>\n";
    $output .= ' <title>' . $this->wrapperCdata(Html::escape($title)) . "</title>\n";
    $output .= ' <link>' . $this->wrapperCdata(Html::escape(UrlHelper::stripDangerousProtocols($link))) . "</link>\n";
    // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
    // We strip all HTML tags, but need to prevent double encoding from properly
    // escaped source data (such as &amp becoming &amp;amp;).
    $output .= ' <description>' . $this->wrapperCdata($this->formatDescription($description)) . "</description>\n";
    $output .= ' <language>' . Html::escape($langcode) . "</language>\n";
    $output .= $this->formatXmlElements($args);
    $output .= $items;
    $output .= "</channel>\n";

    return $output;
  }

  /**
   * Gets RSS feed items and handle query parameters.
   *
   * @param \Drupal\rss_list\Entity\RssPageInterface $feed
   *   The RSS Page.
   * @param int $limit
   *   The RSS items limit.
   *
   * @return array
   *   The array of nodes to be displayed on the RSS Feed.
   */
  public function getFeedItems(RssPageInterface $feed = NULL, $limit = NULL) {
    if (empty($limit)) {
      $limit = $feed->getRssLength();
    }
    // Base query.
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'publication');
    $query->condition('status', 1);
    // Filter by taxonomy 'Web Experience - Custom'.
    $ids = $this->getTaxonomyReferenceIds('field_filter_custom', $feed);
    if (!empty($ids)) {
      $query->condition('field_custom', $ids, 'IN');
    }
    // Filter by taxonomy 'VSCPA Action'.
    $ids = $this->getTaxonomyReferenceIds('field_filter_vscpa_action', $feed);
    if (!empty($ids)) {
      $query->condition('field_vscpa_action', $ids, 'IN');
    }
    // Filter by taxonomy 'Job Position'.
    $ids = $this->getTaxonomyReferenceIds('field_filter_position', $feed);
    if (!empty($ids)) {
      $query->condition('field_position', $ids, 'IN');
    }
    // Filter by taxonomy 'General Business'.
    $ids = $this->getTaxonomyReferenceIds('field_filter_general_business', $feed);
    if (!empty($ids)) {
      $query->condition('field_general_business', $ids, 'IN');
    }
    // Filter by taxonomy 'Interest'.
    $ids = $this->getTaxonomyReferenceIds('field_filter_field_of_interest', $feed);
    if (!empty($ids)) {
      $query->condition('field_field_of_interest', $ids, 'IN');
    }
    // Sort Options.
    $query->sort('changed', 'DESC');
    // Limit options.
    $query->range(0, $limit);
    // Get the result.
    $articles_ids = $query->execute();
    if (empty($articles_ids)) {
      return [];
    }
    $items = Node::loadMultiple($articles_ids);
    return $items;
  }

  /**
   * Get taxonomy reference Ids.
   *
   * Arbitrary elements may be added using the $args associative array.
   *
   * @param string $field_name
   *   The title of the RSS Item.
   * @param \Drupal\rss_list\Entity\RssPageInterface $entity
   *   The RSS page.
   *
   * @return array
   *   The Taxonomy Reference Ids.
   */
  public function getTaxonomyReferenceIds($field_name = NULL, RssPageInterface $entity = NULL) {
    $field = $entity->get($field_name);
    if ($field->isEmpty()) {
      return [];
    }
    $ids = [];
    $values = $field->getValue();
    foreach ($values as $delta => $value) {
      $target_id = $value['target_id'] ?? NULL;
      if (empty($target_id)) {
        continue;
      }
      $ids[] = $target_id;
    }
    return $ids;
  }

}
