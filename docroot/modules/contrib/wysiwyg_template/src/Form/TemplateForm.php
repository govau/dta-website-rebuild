<?php

/**
 * @file
 * Contains \Drupal\wysiwyg_template\Form\TemplateForm.
 */

namespace Drupal\wysiwyg_template\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Class TemplateForm.
 *
 * @package Drupal\wysiwyg_template\Form
 */
class TemplateForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\wysiwyg_template_core\TemplateInterface $wysiwyg_template */
    $wysiwyg_template = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $wysiwyg_template->label(),
      '#description' => $this->t('Select a name for this template.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $wysiwyg_template->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\wysiwyg_template\Entity\Template::load',
      ),
      '#disabled' => !$wysiwyg_template->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#default_value' => $wysiwyg_template->getDescription(),
      '#title' => $this->t('Description'),
      '#description' => $this->t('A description to be shown with the template.'),
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#format' => $wysiwyg_template->getFormat(),
      '#default_value' => $wysiwyg_template->getBody(),
      '#title' => $this->t('HTML template'),
      '#rows' => 10,
      '#required' => TRUE,
    ];

    $node_types = array_map(function ($item) {
      return $item->label();
    }, NodeType::loadMultiple());

    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#default_value' => $wysiwyg_template->getNodeTypes(),
      '#title' => $this->t('Available for content types'),
      '#description' => $this->t('If you select no content type, this template will be available for all content types.'),
      '#access' => (bool) count($node_types),
      '#options' => $node_types,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\wysiwyg_template_core\TemplateInterface $wysiwyg_template */
    $wysiwyg_template = $this->entity;
    $status = $wysiwyg_template->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label template.', [
          '%label' => $wysiwyg_template->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label template.', [
          '%label' => $wysiwyg_template->label(),
        ]));
    }
    $form_state->setRedirectUrl($wysiwyg_template->urlInfo('collection'));
  }

}
