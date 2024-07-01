<?php

namespace Drupal\bulk_user_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

/**
 * Configure locale settings for this site.
 *
 * @package Drupal\bulk_user_registration
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_user_registration_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bulk_user_registration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bulk_user_registration.settings');

    $form['allowed_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles for import'),
      '#default_value' => $config->get('allowed_roles'),
      '#options' => user_role_names(TRUE),
      '#description' => $this->t('These roles can be assigned with Bulk user registration. The authenticated role will always be set.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Force set the authenticated role.
    $roles = $form_state->getValue('allowed_roles');
    $roles[UserInterface::AUTHENTICATED_ROLE] = UserInterface::AUTHENTICATED_ROLE;
    $form_state->setValue('allowed_roles', $roles);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = $this->config('bulk_user_registration.settings');
    $config->set('allowed_roles', $values['allowed_roles'])->save();

    parent::submitForm($form, $form_state);
  }

}
