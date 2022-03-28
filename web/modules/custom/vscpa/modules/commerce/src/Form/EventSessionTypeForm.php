<?php

namespace Drupal\vscpa_commerce\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EventSessionTypeForm.
 */
class EventSessionTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $event_session_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $event_session_type->label(),
      '#description' => $this->t("Label for the Event session type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $event_session_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\vscpa_commerce\Entity\EventSessionType::load',
      ],
      '#disabled' => !$event_session_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $event_session_type = $this->entity;
    $status = $event_session_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Event session type.', [
          '%label' => $event_session_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Event session type.', [
          '%label' => $event_session_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($event_session_type->toUrl('collection'));
  }

}
