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

    // Submitted form values should be nested.
    $form['#tree'] = TRUE;

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
      '#id' => 'credentials',
      '#type' => 'details',
      '#title' => $this->t('Credentials'),
      '#open' => TRUE,
    );

    $credential_provider = $shield_config->get('credential_provider');
    $credential_provider = ($form_state->hasValue(['credentials', 'credential_provider'])) ? $form_state->getValue(['credentials', 'credential_provider']) : $credential_provider;

    $form['credentials']['credential_provider'] = array(
      '#type' => 'select',
      '#title' => $this->t('Credential provider'),
      '#options' => [
        'shield' => 'Shield',
      ],
      '#default_value' => $credential_provider,
      '#ajax' => array(
        'callback' => array($this, 'ajaxCallback'),
        'wrapper' => 'credentials_configuration',
        'method' => 'replace',
        'effect' => 'fade',
      ),
    );

    $form['credentials']['providers'] = [
      '#type' => 'item',
      '#id' => 'credentials_configuration',
    ];

    if (\Drupal::moduleHandler()->moduleExists('key')) {
      $form['credentials']['credential_provider']['#options']['key'] = 'Key Module';

      /** @var \Drupal\key\Plugin\KeyPluginManager $key_type */
      $key_type = \Drupal::service('plugin.manager.key.key_type');
      if ($key_type->hasDefinition('user_password')) {
        $form['credentials']['credential_provider']['#options']['multikey'] = 'Key Module (user/password)';
      }
    }

    if ($credential_provider == 'shield') {
      $form['credentials']['providers']['shield']['user'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('User'),
        '#default_value' => $shield_config->get('credentials.shield.user'),
        '#description' => $this->t('Leave blank to disable authentication.'),
      );
      $form['credentials']['providers']['shield']['pass'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Password'),
        '#default_value' => $shield_config->get('credentials.shield.pass'),
      );
    }
    elseif ($credential_provider == 'key') {
      $form['credentials']['providers']['key']['user'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('User'),
        '#default_value' => $shield_config->get('credentials.key.user'),
        '#required' => TRUE,
      );
      $form['credentials']['providers']['key']['pass_key'] = array(
        '#type' => 'key_select',
        '#title' => $this->t('Password'),
        '#default_value' => $shield_config->get('credentials.key.pass_key'),
        '#empty_option' => $this->t('- Please select -'),
        '#key_filters' => ['type' => 'authentication'],
        '#required' => TRUE,
      );
    }
    elseif ($credential_provider == 'multikey') {
      $form['credentials']['providers']['multikey']['user_pass_key'] = array(
        '#type' => 'key_select',
        '#title' => $this->t('User/password'),
        '#default_value' => $shield_config->get('credentials.multikey.user_pass_key'),
        '#empty_option' => $this->t('- Please select -'),
        '#key_filters' => ['type' => 'user_password'],
        '#required' => TRUE,
      );
    }

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

    $shield_config = $this->config('shield.settings');
    $credential_provider = $form_state->getValue(['credentials', 'credential_provider']);
    $shield_config
      ->set('allow_cli', $form_state->getValue(['general', 'shield_allow_cli']))
      ->set('print', $form_state->getValue('shield_print'))
      ->set('credential_provider', $credential_provider);
    $credentials = $form_state->getValue(['credentials', 'providers', $credential_provider]);
    $shield_config->set('credentials', [$credential_provider => $credentials]);
    $shield_config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Ajax callback for the credential dependent configuration options.
   *
   * @return array
   *   The form element containing the configuration options.
   */
  public static function ajaxCallback($form, FormStateInterface $form_state) {
    return $form['credentials']['providers'];
  }

}
