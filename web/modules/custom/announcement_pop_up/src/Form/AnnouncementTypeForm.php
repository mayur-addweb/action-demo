<?php

namespace Drupal\announcement_pop_up\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AnnouncementTypeForm.
 */
class AnnouncementTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $announcement_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $announcement_type->label(),
      '#description' => $this->t("Label for the Announcement type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $announcement_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\announcement_pop_up\Entity\AnnouncementType::load',
      ],
      '#disabled' => !$announcement_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $announcement_type = $this->entity;
    $status = $announcement_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Announcement type.', [
          '%label' => $announcement_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Announcement type.', [
          '%label' => $announcement_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($announcement_type->toUrl('collection'));
  }

}
