<?php

namespace Drupal\am_net_firms\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * Form for Handle Edit Firm Information.
 */
class EditFirmInformationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_firms.employee_management_tool.edit.firm.information.form';
  }

  /**
   * Get the current firm from the route match.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The Firm object.
   */
  public function getFirm() {
    return $this->getRouteMatch()->getParameter('firm');
  }

  /**
   * {@inheritdoc}
   */
  public function getGeneralBusinessOptions() {
    try {
      $terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadTree('general_business');
    }
    catch (InvalidPluginDefinitionException $e) {
      return [];
    }
    catch (PluginNotFoundException $e) {
      return [];
    }
    $items = [];
    $items['_none'] = "- None -";
    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($terms as $term) {
      $items[$term->tid] = $term->name;
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $firm = $this->getFirm();
    $form['#attributes']['class'][] = 'taxonomy-term-firm-form';
    // The Firm Name.
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $firm->label(),
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Firm name')],
      '#prefix' => '',
      '#suffix' => '',
    ];
    // The Firm Name 2.
    $form['field_firm_name2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Firm Name 2'),
      '#default_value' => $firm->get('field_firm_name2')->getString(),
      '#required' => FALSE,
      '#attributes' => ['placeholder' => $this->t('Firm name 2')],
      '#prefix' => '',
      '#suffix' => '',
    ];
    // General Business.
    $general_business = NULL;
    if (!$firm->get('field_general_business')->isEmpty()) {
      $general_business = $firm->get('field_general_business')->first()->getValue();
      $general_business = $general_business['target_id'] ?? NULL;
    }
    $form['field_general_business'] = [
      '#type' => 'select',
      '#title' => $this->t('Business Type'),
      '#default_value' => $general_business,
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Firm General Business')],
      '#prefix' => '',
      '#suffix' => '',
      '#options' => $this->getGeneralBusinessOptions(),
    ];
    // Firm Phone.
    $form['field_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone'),
      '#default_value' => $firm->get('field_fax')->getString(),
      '#required' => FALSE,
      '#attributes' => ['placeholder' => $this->t('###-###-####')],
      '#size' => 30,
      '#maxlength' => 128,
    ];
    // Firm Fax.
    $form['field_fax'] = [
      '#type' => 'tel',
      '#title' => $this->t('Fax'),
      '#default_value' => $firm->get('field_phone')->getString(),
      '#required' => FALSE,
      '#attributes' => ['placeholder' => $this->t('###-###-####')],
      '#size' => 30,
      '#maxlength' => 128,
    ];
    // Business URL (web address).
    $business_url_web_address = NULL;
    if (!$firm->get('field_websites')->isEmpty()) {
      $field_websites = $firm->get('field_websites')->first()->getValue();
      $business_url_web_address = $field_websites['uri'] ?? NULL;
    }
    $form['field_websites'] = [
      '#size' => 30,
      '#type' => 'url',
      '#title' => $this->t('Business URL (web address)'),
      '#description' => $this->t('This must be an external URL such as https://example.com.'),
      '#attributes' => ['placeholder' => $this->t('https://example.com')],
      '#default_value' => $business_url_web_address,
    ];
    // Field Address.
    $form['field_address_container'] = [
      '#title' => $this->t('Address'),
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#collapsible' => FALSE,
      '#panel_type' => 'default',
      '#description' => $this->t('Physical Location of the Firm.'),
      '#attributes' => [],
      '#prefix' => '<div class="field--name-field-address">',
      '#suffix' => '</div>',
    ];
    $address_value = [
      'langcode' => NULL,
      'country_code' => 'US',
    ];
    if (!$firm->get('field_address')->isEmpty()) {
      $address_value = $firm->get('field_address')->first()->getValue();
    }
    $form['field_address_container']['field_address'] = [
      '#type' => 'address',
      '#title' => $this->t('Address'),
      '#default_value' => $address_value,
      '#field_overrides' => [
        'organization' => 'hidden',
        'givenName' => 'hidden',
        'additionalName' => 'hidden',
        'familyName' => 'hidden',
      ],
      '#required' => TRUE,
      '#size' => 30,
    ];
    // Actions.
    $form['actions'] = [
      '#type' => 'actions',
    ];
    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('<span class="icon glyphicon glyphicon-floppy-disk" aria-hidden="true"></span> Update Firm Info'),
      '#attributes' => [
        'class' => [
          'btn-purple button--small',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $firm = $this->getFirm();
    if (!$firm) {
      $this->messenger()->addWarning($this->t('It is not possible to update your Firm information at this time, please try again.'));
      return;
    }
    // Update values.
    $values = $form_state->getValues();
    // Update Name.
    $name = $values['name'] ?? $firm->getName();
    if (!empty($name)) {
      $firm->setName($name);
    }
    // Update Name 2.
    $name_2 = $values['field_firm_name2'] ?? NULL;
    $firm->set('field_firm_name2', $name_2);
    // Update General Business.
    $general_business = $values['field_general_business'] ?? NULL;
    if (!empty($general_business)) {
      $allowed_options = $this->getGeneralBusinessOptions();
      if (!isset($allowed_options[$general_business])) {
        $general_business = NULL;
      }
    }
    $firm->set('field_general_business', $general_business);
    // Update Phone.
    $phone = $values['field_phone'] ?? NULL;
    $firm->set('field_phone', $phone);
    // Update Fax.
    $fax = $values['field_fax'] ?? NULL;
    $firm->set('field_fax', $fax);
    // Update Address.
    $address = $values['field_address_container']['field_address'] ?? NULL;
    $firm->set('field_address', $address);
    // Update Business URL (web address).
    $uri = $values['field_websites'] ?? NULL;
    if (!empty($uri)) {
      $uri = [['uri' => $uri]];
    }
    $firm->set('field_websites', $uri);
    // Save the changes.
    try {
      $firm->save();
      $this->messenger()->addMessage($this->t('Successfully updated —<strong>@name</strong>— firm information!.', ['@name' => $name]));
    }
    catch (EntityStorageException $e) {
      $this->messenger()->addWarning($this->t('It is not possible to update your Firm information at this time, please try again.'));
    }
  }

}
