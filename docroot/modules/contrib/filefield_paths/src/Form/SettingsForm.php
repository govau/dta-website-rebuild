<?php

/**
 * @file
 * Contains \Drupal\filefield_paths\Form\SettingsForm.
 */

namespace Drupal\filefield_paths\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Administration settings form for File (Field) Paths.
 *
 * @package Drupal\filefield_paths\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'filefield_paths_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'filefield_paths.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form['temp_location'] = array(
      '#title'         => t('Temporary file location'),
      '#type'          => 'textfield',
      '#default_value' => $this->config('filefield_paths.settings')
        ->get('temp_location'),
      '#description'   => t('The location that unprocessed files will be uploaded priot to being processed by File (Field) Paths.<br />It is recommended that you use the temporary file system (temporary://) if your server configuration allows for that.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $scheme = file_uri_scheme($values['temp_location']);
    if (!$scheme) {
      $form_state->setErrorByName('temp_location', t('Invalid file location. You must include a file stream wrapper (e.g., public://).'));

      return FALSE;
    }

    $file_system = \Drupal::service('file_system');
    if (!$file_system->validScheme($scheme)) {
      $form_state->setErrorByName('temp_location', t('Invalid file stream wrapper.'));

      return FALSE;
    }

    if ((!is_dir($values['temp_location']) || !is_writable($values['temp_location'])) && !file_prepare_directory($values['temp_location'], FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      $form_state->setErrorByName('temp_location', t('File location can not be created or is not writable.'));

      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('filefield_paths.settings')
      ->set('temp_location', $values['temp_location'])
      ->save();
  }

}
