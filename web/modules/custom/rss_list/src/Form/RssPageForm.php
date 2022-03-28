<?php

namespace Drupal\rss_list\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for RSS Page edit forms.
 *
 * @ingroup rss_list
 */
class RssPageForm extends ContentEntityForm {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new ProductForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\rss_list\Entity\RssPage */
    $entity = $this->entity;
    $form = parent::form($form, $form_state);
    $form['#tree'] = TRUE;
    $form['#theme'] = ['rss_list_form'];
    $form['#attached']['library'][] = 'rss_list/form';
    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $entity->getChangedTime(),
    ];
    $form['status']['#group'] = 'footer';
    $last_saved = t('Not saved yet');
    if (!$entity->isNew()) {
      $last_saved = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    }
    $form['meta'] = [
      '#attributes' => ['class' => ['entity-meta__header']],
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -100,
      'published' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $entity->isPublished() ? $this->t('Published') : $this->t('Not published'),
        '#access' => !$entity->isNew(),
        '#attributes' => [
          'class' => ['entity-meta__title'],
        ],
      ],
      'changed' => [
        '#type' => 'item',
        '#wrapper_attributes' => [
          'class' => ['entity-meta__last-saved', 'container-inline'],
        ],
        '#markup' => '<h4 class="label inline">' . $this->t('Last saved') . '</h4> ' . $last_saved,
      ],
      'author' => [
        '#type' => 'item',
        '#wrapper_attributes' => [
          'class' => ['author', 'container-inline'],
        ],
        '#markup' => '<h4 class="label inline">' . $this->t('Author') . '</h4> ' . $entity->getOwner()->getDisplayName(),
      ],
    ];
    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form['path_settings'] = [
      '#type' => 'details',
      '#title' => t('URL path settings'),
      '#open' => !empty($form['path']['widget'][0]['alias']['#default_value']),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['path-form'],
      ],
      '#attached' => [
        'library' => ['path/drupal.path'],
      ],
      '#weight' => 60,
    ];
    $form['rss_settings'] = [
      '#type' => 'details',
      '#title' => t('RSS Settings'),
      '#open' => !empty($form['feed_path']['widget'][0]['uri']['#default_value']),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['rss-setting-form'],
      ],
      '#weight' => 61,
    ];
    $form['author'] = [
      '#type' => 'details',
      '#title' => t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['entity-form-author'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];
    $form['name']['widget']['0']['#description'] = $this->t('The name of the RSS Page.');
    if (isset($form['feed_path'])) {
      $form['feed_path']['#group'] = 'rss_settings';
      $form['feed_path']['widget']['0']['uri']['#description'] = $this->t('Specify an alternative path by which this RSS feed can be accessed. For example, type "/news.xml" when writing an news page.');
      unset($form['feed_path']['widget']['0']['uri']['#field_prefix']);
    }
    if (isset($form['rss_length'])) {
      $form['rss_length']['#group'] = 'rss_settings';
      $form['rss_length']['widget']['0']['value']['#description'] = $this->t('The RSS feed max number of Items.');
    }
    if (isset($form['enable_feed'])) {
      $form['enable_feed']['#group'] = 'rss_settings';
    }
    if (isset($form['field_rss_channel_description'])) {
      $form['field_rss_channel_description']['#group'] = 'rss_settings';
    }
    if (isset($form['path'])) {
      $form['path']['#group'] = 'path_settings';
    }
    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }
    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label RSS Page.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label RSS Page.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.rss_page.canonical', ['rss_page' => $entity->id()]);
  }

}
