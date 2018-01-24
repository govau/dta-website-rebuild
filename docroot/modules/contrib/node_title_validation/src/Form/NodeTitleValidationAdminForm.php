<?php

/**
 * @file
 * Contains \Drupal\node_title_validation\Form\NodeTitleValidationAdminForm.
 */

namespace Drupal\node_title_validation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\node\Entity\NodeType;

class NodeTitleValidationAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_title_validation_admin_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get configuration value.
    $node_title_validation_config = \Drupal::config('node_title_validation_config.node_title_validation_settings')
      ->get('node_title_validation_config');

    // Get available content types.
    $node_types = NodeType::loadMultiple();
    // Variable to display 1st fieldset collapse open.
    $i = 0;
    // Generate fieldset for each content type along with exclude, min,
    // max and unique form elements.
    foreach ($node_types as $type) {

      // Display First fieldset collapsed open.
      if ($i == 0) {
        $form[$type->get('type')] = [
          '#type' => 'fieldset',
          '#title' => $type->get('name'),
          '#collapsible' => TRUE,
          '#collapsed' => FALSE,
        ];
      }
      else {
        $form[$type->get('type')] = [
          '#type' => 'fieldset',
          '#title' => $type->get('name'),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        ];
      }
      // Increment $i for other fieldsets in collapsed closed.
      $i++;

      $form[$type->get('type')]['exclude-' . $type->get('type')] = [
        '#type' => 'textarea',
        '#default_value' => isset($node_title_validation_config['exclude-' . $type->get('type')]) ? $node_title_validation_config['exclude-' . $type->get('type')] : '',
        '#title' => t('Blacklist Characters/Words'),
        '#description' => '<p>' . t("Comma separated characters or words to avoided while saving node title. Ex: !,@,#,$,%,^,&,*,(,),1,2,3,4,5,6,7,8,9,0,have,has,were,aren't.") . '</p>' . '<p>' . t('If any of the blacklisted characters/words found in node title,would return validation error on node save.') . '</p>',
      ];

      $form[$type->get('type')]['comma-' . $type->get('type')] = [
        '#type' => 'checkbox',
        '#default_value' => isset($node_title_validation_config['comma-' . $type->get('type')]) ? $node_title_validation_config['comma-' . $type->get('type')] : '',
        '#title' => t('Add comma to blacklist.'),
      ];

      $form[$type->get('type')]['min-' . $type->get('type')] = [
        '#type' => 'textfield',
        '#title' => t("Minimum characters"),
        '#required' => TRUE,
        '#description' => t("Minimum number of characters node title should contain"),
        '#size' => 12,
        '#maxlength' => 3,
        '#default_value' => isset($node_title_validation_config['min-' . $type->get('type')]) ? $node_title_validation_config['min-' . $type->get('type')] : 1,
      ];

      $form[$type->get('type')]['max-' . $type->get('type')] = [
        '#type' => 'textfield',
        '#title' => t("Maximum characters"),
        '#required' => TRUE,
        '#description' => t("Maximum number of characters node title should contain"),
        '#size' => 12,
        '#maxlength' => 3,
        '#default_value' => isset($node_title_validation_config['max-' . $type->get('type')]) ? $node_title_validation_config['max-' . $type->get('type')] : 255,
      ];

      $form[$type->get('type')]['min-wc-' . $type->get('type')] = [
        '#type' => 'textfield',
        '#title' => t("Minimum Word Count"),
        '#required' => TRUE,
        '#description' => t("Minimum number of words node title should contain"),
        '#size' => 12,
        '#maxlength' => 3,
        '#default_value' => isset($node_title_validation_config['min-wc-' . $type->get('type')]) ? $node_title_validation_config['min-wc-' . $type->get('type')] : 1,
      ];

      $form[$type->get('type')]['max-wc-' . $type->get('type')] = [
        '#type' => 'textfield',
        '#title' => t("Maximum Word Count"),
        '#description' => t("Maximum number of words node title should contain"),
        '#size' => 12,
        '#maxlength' => 3,
        '#default_value' => isset($node_title_validation_config['max-wc-' . $type->get('type')]) ? $node_title_validation_config['max-wc-' . $type->get('type')] : 25,
      ];

      $form[$type->get('type')]['unique-' . $type->get('type')] = [
        '#type' => 'checkbox',
        '#title' => t("Unique node title for @type content type", [
          '@type' => $type->get('type')
        ]),
        '#default_value' => isset($node_title_validation_config['unique-' . $type->get('type')]) ? $node_title_validation_config['unique-' . $type->get('type')] : 0,
      ];
    }

    $form['unique'] = [
      '#type' => 'checkbox',
      '#title' => t('Unique node title for all content types'),
      '#default_value' => isset($node_title_validation_config['unique']) ? $node_title_validation_config['unique'] : 0,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    // Get available content types.
    $node_types = NodeType::loadMultiple();

    // Loop for each content type & validate min, max values.
    foreach ($node_types as $type) {

      $max = $form_state->getValue(['max-' . $type->get('type')]);

      // Validate field is numerical.
      if (!is_numeric($max)) {
        $form_state->setErrorByName('max-' . $type->get('type'), t("Value should be Numerical"));
      }

      // Validate field should be between 0 and 256.
      if ($max <= 0 || $max > 255) {
        $form_state->setErrorByName('max-' . $type->get('type'), t("Value should be between 0 and 256"));
      }

      $min = $form_state->getValue(['min-' . $type->get('type')]);

      // Validate field is numerical.
      if (!is_numeric($min)) {
        $form_state->setErrorByName('min-' . $type->get('type'), t("Value should be Numerical"));
      }

      // Validate field is greater than 1.
      if ($min < 1) {
        $form_state->setErrorByName('min-' . $type->get('type'), t("Value should be more than 1"));
      }

      // Validate min is less than max value.
      if ($min > $max) {
        $form_state->setErrorByName('min-' . $type->get('type'), t("Minimum length should not be more than Max length"));
      }

      // Validate field is numerical.
      $min_wc = $form_state->getValue([
        'min-wc-' . $type->get('type')
      ]);
      if (!empty($min_wc) && !is_numeric($min_wc)) {
        $form_state->setErrorByName('min-wc-' . $type->get('type'), t("Value should be Numerical"));
      }
      $max_wc = $form_state->getValue(['max-wc-' . $type->get('type')]);
      if (!empty($max_wc) && !is_numeric($max_wc)) {
        $form_state->setErrorByName('max-wc-' . $type->get('type'), t("Value should be Numerical"));
      }

      // Validate min is less than max value.
      if (!empty($min_wc) && !empty($max_wc) && $min_wc > $max_wc) {
        $form_state->setErrorByName('max-wc-' . $type->get('type'), t("Minimum word count of title should not be more than Maximum word count"));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = [];
    // Get available content types.
    $node_types = NodeType::loadMultiple();
    // Store Form values in node_title_validation_config variable.
    foreach ($node_types as $type) {
      $values['comma-' . $type->get('type')] = $form_state->getValue(['comma-' . $type->get('type')]);
      $values['exclude-' . $type->get('type')] = $form_state->getValue(['exclude-' . $type->get('type')]);
      $values['min-' . $type->get('type')] = $form_state->getValue(['min-' . $type->get('type')]);
      $values['max-' . $type->get('type')] = $form_state->getValue(['max-' . $type->get('type')]);
      $values['min-wc-' . $type->get('type')] = $form_state->getValue(['min-wc-' . $type->get('type')]);
      $values['max-wc-' . $type->get('type')] = $form_state->getValue(['max-wc-' . $type->get('type')]);
      $values['unique-' . $type->get('type')] = $form_state->getValue(['unique-' . $type->get('type')]);
    }
    $values['unique'] = $form_state->getValue(['unique']);

    // Set node_title_validation_config variable.
    \Drupal::configFactory()
      ->getEditable('node_title_validation_config.node_title_validation_settings')
      ->set('node_title_validation_config', $values)
      ->save();

    drupal_set_message(t('Node Title Validation Configurations saved successfully!'));
  }
}
