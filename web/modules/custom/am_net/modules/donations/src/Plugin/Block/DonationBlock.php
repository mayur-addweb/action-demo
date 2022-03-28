<?php

namespace Drupal\am_net_donations\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides the 'Donation' block.
 *
 * @Block(
 *   id = "donation_block",
 *   admin_label = @Translation("Donation EF - Educational Foundation"),
 *   category = @Translation("Forms")
 * )
 */
class DonationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    // Body.
    $default_value = isset($config['body']) ? $config['body'] : '';
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#format' => "basic_html",
      '#rows' => 9,
      '#default_value' => $default_value,
    ];
    // Note.
    $default_value = isset($config['note']) ? $config['note'] : '';
    $form['note'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Note'),
      '#format' => "basic_html",
      '#rows' => 9,
      '#default_value' => $default_value,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form_path = '\Drupal\am_net_donations\Form\DonationForm';
    $info = [
      'body' => ($this->configuration['body'] ?? NULL),
      'note' => ($this->configuration['note'] ?? NULL),
    ];
    $contribution_form = \Drupal::formBuilder()->getForm($form_path, $info);
    // Disable Cache on this block.
    $contribution_form['#cache']['max-age'] = 0;
    $contribution_form['#action'] = '/donations/ef-donation';
    return [
      'contribution_form' => $contribution_form,
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $body = $values['body']['value'] ?? NULL;
    $this->configuration['body'] = $body;
    $note = $values['note']['value'] ?? NULL;
    $this->configuration['note'] = $note;
  }

}
