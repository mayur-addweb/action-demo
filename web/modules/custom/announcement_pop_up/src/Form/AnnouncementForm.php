<?php

namespace Drupal\announcement_pop_up\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Announcement edit forms.
 *
 * @ingroup announcement_pop_up
 */
class AnnouncementForm extends ContentEntityForm {

  use StringTranslationTrait;

  /**
   * This is the condition.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $condition;

  /**
   * This is a protected entity.
   *
   * @var \Drupal\announcement_pop_up\Entity\Announcement
   */
  protected $entity;

  /**
   * AnnouncementForm constructor.
   *
   * Entity Manager Interface.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity Type Bundle Info Interface.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface|null $entity_type_bundle_info
   *   Time Interface.
   * @param \Drupal\Component\Datetime\TimeInterface|null $time
   *   Factory Interface.
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $plugin_factory
   *   Plugin factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, FactoryInterface $plugin_factory) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->condition = $plugin_factory->createInstance('request_path');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* Set the default condition configuration. */
    /** @var \Drupal\announcement_pop_up\Entity\Announcement $announcement */
    $announcement = $this->entity;
    $requestPath = $announcement->getRequestPath();
    $requestPath = empty($requestPath) ? [] : reset($requestPath);
    $this->condition->setConfiguration($requestPath);

    /* @var $entity \Drupal\announcement_pop_up\Entity\Announcement */
    $form = parent::buildForm($form, $form_state);
    $form += $this->condition->buildConfigurationForm($form, $form_state);
    $form['pages']['#weight'] = 98;
    $form['negate']['#weight'] = 99;
    $form['pages']['#title'] = $this->t('Show Announcement on the Listed Pages');
    $form['pages']['#description'] = $this->t('Enter path(s) to display announcements (one per line). &lt;front&gt; is the homepage and the * character is a wildcard. An example entry would be /tests/* for all Tests.');
    $form['negate']['#title'] = $this->t('Negate Condition (hide Announcement on the listed pages)');

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Announcement.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Announcement.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.announcement.canonical', ['announcement' => $entity->id()]);
  }

  /**
   * Submit Form function.
   *
   * @param array $form
   *   Array form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->condition->getConfiguration();
    $this->condition->submitConfigurationForm($form, $form_state);
    $this->entity->setRequestPath($this->condition->getConfiguration());

    parent::submitForm($form, $form_state);

  }

}
