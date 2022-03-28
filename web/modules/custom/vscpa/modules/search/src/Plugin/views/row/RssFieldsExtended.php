<?php

namespace Drupal\vscpa_search\Plugin\views\row;

use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Renders an RSS item based on fields.
 *
 * @ViewsRow(
 *   id = "rss_fields_extended",
 *   title = @Translation("RSS Fields Extended"),
 *   help = @Translation("Display fields as RSS items extended."),
 *   theme = "views_view_row_rss",
 *   display_types = {"feed"}
 * )
 */
class RssFieldsExtended extends RowPluginBase {

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['title_field'] = ['default' => ''];
    $options['link_field'] = ['default' => ''];
    $options['description_field'] = ['default' => ''];
    $options['creator_field'] = ['default' => ''];
    $options['date_field'] = ['default' => ''];
    $options['rss_category_field_1'] = ['default' => ''];
    $options['rss_category_field_2'] = ['default' => ''];
    $options['rss_category_field_3'] = ['default' => ''];
    $options['rss_image'] = ['default' => ''];
    $options['guid_field_options']['contains']['guid_field'] = ['default' => ''];
    $options['guid_field_options']['contains']['guid_field_is_permalink'] = ['default' => TRUE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $initial_labels = ['' => $this->t('- None -')];
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);

    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#description' => $this->t('The field that is going to be used as the RSS item title for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['title_field'],
      '#required' => TRUE,
    ];
    $form['link_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Link field'),
      '#description' => $this->t('The field that is going to be used as the RSS item link for each row. This must be a drupal relative path.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['link_field'],
      '#required' => TRUE,
    ];
    $form['description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Description field'),
      '#description' => $this->t('The field that is going to be used as the RSS item description for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['description_field'],
      '#required' => TRUE,
    ];
    $form['creator_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Creator field'),
      '#description' => $this->t('The field that is going to be used as the RSS item creator for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['creator_field'],
      '#required' => TRUE,
    ];
    $form['date_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Publication date field'),
      '#description' => $this->t('The field that is going to be used as the RSS item pubDate for each row. It needs to be in RFC 2822 format.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['date_field'],
      '#required' => TRUE,
    ];
    $form['rss_image'] = [
      '#type' => 'select',
      '#title' => $this->t('RSS Image'),
      '#description' => $this->t('The field that is going to be used as the RSS Image.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['rss_image'],
      '#required' => TRUE,
    ];
    $form['rss_category_field_1'] = [
      '#type' => 'select',
      '#title' => $this->t('RSS Category 1'),
      '#description' => $this->t('The field that is going to be used as the RSS category.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['rss_category_field_1'],
      '#required' => TRUE,
    ];
    $form['rss_category_field_2'] = [
      '#type' => 'select',
      '#title' => $this->t('RSS Category 2'),
      '#description' => $this->t('The field that is going to be used as the RSS category.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['rss_category_field_2'],
      '#required' => TRUE,
    ];
    $form['rss_category_field_3'] = [
      '#type' => 'select',
      '#title' => $this->t('RSS Category 3'),
      '#description' => $this->t('The field that is going to be used as the RSS category.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['rss_category_field_3'],
      '#required' => TRUE,
    ];
    $form['guid_field_options'] = [
      '#type' => 'details',
      '#title' => $this->t('GUID settings'),
      '#open' => TRUE,
    ];
    $form['guid_field_options']['guid_field'] = [
      '#type' => 'select',
      '#title' => $this->t('GUID field'),
      '#description' => $this->t('The globally unique identifier of the RSS item.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['guid_field_options']['guid_field'],
      '#required' => TRUE,
    ];
    $form['guid_field_options']['guid_field_is_permalink'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('GUID is permalink'),
      '#description' => $this->t('The RSS item GUID is a permalink.'),
      '#default_value' => $this->options['guid_field_options']['guid_field_is_permalink'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    $required_options = [
      'title_field',
      'link_field',
      'description_field',
      'creator_field',
      'date_field',
    ];
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $this->t('Row style plugin requires specifying which views fields to use for RSS item.');
        break;
      }
    }
    // Once more for guid.
    if (empty($this->options['guid_field_options']['guid_field'])) {
      $errors[] = $this->t('Row style plugin requires specifying which views fields to use for RSS item.');
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    static $row_index;
    if (!isset($row_index)) {
      $row_index = 0;
    }
    if (function_exists('rdf_get_namespaces')) {
      // Merge RDF namespaces in the XML namespaces in case they are used
      // further in the RSS content.
      $xml_rdf_namespaces = [];
      foreach (rdf_get_namespaces() as $prefix => $uri) {
        $xml_rdf_namespaces['xmlns:' . $prefix] = $uri;
      }
      $this->view->style_plugin->namespaces += $xml_rdf_namespaces;
    }
    // Create the RSS item object.
    $item = new \stdClass();
    $item->title = $this->getField($row_index, $this->options['title_field']);
    $item->link = $this->getFieldUrl($this->getField($row_index, $this->options['link_field']))->setAbsolute()->toString();
    $field = $this->getField($row_index, $this->options['description_field']);
    $item->description = is_array($field) ? $field : ['#markup' => $field];
    $item->elements = [
      [
        'key' => 'pubDate',
        'value' => $this->getField($row_index, $this->options['date_field']),
      ],
      [
        'key' => 'dc:creator',
        'value' => $this->getField($row_index, $this->options['creator_field']),
        'namespace' => ['xmlns:dc' => 'http://purl.org/dc/elements/1.1/'],
      ],
    ];
    $guid_is_permalink_string = 'false';
    $item_guid = $this->getField($row_index, $this->options['guid_field_options']['guid_field']);
    if ($this->options['guid_field_options']['guid_field_is_permalink']) {
      $guid_is_permalink_string = 'true';
      $item_guid = $this->getFieldUrl($item_guid)->setAbsolute()->toString();
    }
    $item->elements[] = [
      'key' => 'guid',
      'value' => $item_guid,
      'attributes' => ['isPermaLink' => $guid_is_permalink_string],
    ];
    // Add RSS category.
    $value = [];
    $field1 = $this->getField($row_index, $this->options['rss_category_field_1']);
    if (!empty($field1)) {
      $value[] = $field1;
    }
    $field2 = $this->getField($row_index, $this->options['rss_category_field_2']);
    if (!empty($field2)) {
      $value[] = $field2;
    }
    $field3 = $this->getField($row_index, $this->options['rss_category_field_3']);
    if (!empty($field3)) {
      $value[] = $field3;
    }
    $value = implode(', ', $value);
    $item->elements[] = [
      'key' => 'category',
      'value' => $value,
    ];
    // Add RSS Image.
    $field = $this->getField($row_index, $this->options['rss_image']);
    $value = is_array($field) ? $field : ['#markup' => $field];
    $item->elements[] = [
      'key' => 'image',
      'value' => $value,
    ];

    $row_index++;
    foreach ($item->elements as $element) {
      if (isset($element['namespace'])) {
        $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $element['namespace']);
      }
    }
    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
    ];
    return $build;
  }

  /**
   * Retrieves a views field value from the style plugin.
   *
   * @param string $index
   *   The index count of the row as expected by views_plugin_style::getField().
   * @param string $field_id
   *   The ID assigned to the required field in the display.
   *
   * @return string|null|\Drupal\Component\Render\MarkupInterface
   *   An empty string if there is no style plugin, or the field ID is empty.
   *   NULL if the field value is empty. If neither of these conditions apply,
   *   a MarkupInterface object containing the rendered field value.
   */
  public function getField($index, $field_id) {
    if (empty($this->view->style_plugin) || !is_object($this->view->style_plugin) || empty($field_id)) {
      return '';
    }
    return $this->view->style_plugin->getField($index, $field_id);
  }

  /**
   * Retrieves a URL from a field value.
   *
   * @param string $field_value
   *   The field value retrieved with RssFields::getField().
   *
   * @return \Drupal\Core\Url
   *   The URL object built from the field value.
   */
  private function getFieldUrl($field_value) {
    global $base_path;
    $value = rawurldecode($field_value);
    // Url::fromUserInput expects the argument to be an internal path, so the
    // base path should be stripped if it's there.
    if (substr($value, 0, strlen($base_path)) === $base_path) {
      $value = substr($value, strlen($base_path));
    }
    // @todo Views should expect and store a leading /. See:
    //   https://www.drupal.org/node/2423913
    return Url::fromUserInput('/' . $value);
  }

}
