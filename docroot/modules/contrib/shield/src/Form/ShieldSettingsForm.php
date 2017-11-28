<?php

namespace Drupal\shield\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure site information settings for this site.
 */
class ShieldSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['shield.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shield_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $shield_config = $this->config('shield.settings');

    $form['description'] = array(
      '#type' => 'item',
      '#title' => $this->t('Shield settings'),
      '#description' => $this->t('Set up credentials for an authenticated user. You can also decide whether you want to print out the credentials or not.'),
    );

    $form['general'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
    );

    $form['general']['shield_allow_cli'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow command line access'),
      '#description' => $this->t('When the site is accessed from command line (e.g. from Drush, cron), the shield should not work.'),
      '#default_value' => $shield_config->get('allow_cli'),
    );

    $form['credentials'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Credentials'),
    );

    $form['credentials']['shield_user'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('User'),
      '#default_value' => $shield_config->get('user'),
      '#description' => $this->t('Leave it blank to disable authentication.'),
    );

    $form['credentials']['shield_pass'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $shield_config->get('pass'),
    );

    $form['shield_print'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Authentication message'),
      '#description' => $this->t("The message to print in the authentication request popup. You can use [user] and [pass] to print the user and the password respectively. You can leave it empty, if you don't want to print out any special message to the users."),
      '#default_value' => $shield_config->get('print'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('shield.settings')
      ->set('allow_cli', $form_state->getValue('shield_allow_cli'))
      ->set('user', $form_state->getValue('shield_user'))
      ->set('pass', $form_state->getValue('shield_pass'))
      ->set('print', $form_state->getValue('shield_print'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
