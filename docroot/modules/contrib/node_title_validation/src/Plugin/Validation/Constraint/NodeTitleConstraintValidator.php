<?php

/**
 * @file
 * Contains \Drupal\node_title_validation\Plugin\Validation\Constraint\ForumLeafConstraintValidator.
 */

namespace Drupal\node_title_validation\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Url;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the NodeTitleValidate constraint.
 */
class NodeTitleConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $value_title = $items->first()->value;
    $title = explode(' ', $value_title);
    $node = \Drupal::routeMatch()->getParameter('node');
    if (isset($node)) {
      $node_type = $node->getType();
    }
    else {
      $url = Url::fromRoute('<current>', $route_parameters = array(), $options = array());
      $node_url_array = explode("node/add/", $url->toString());
      $node_type = $node_url_array[1];
    }
    $node_title_validation_config = \Drupal::config('node_title_validation.node_title_validation_settings')
      ->get('node_title_validation_config');
    foreach ($node_title_validation_config as $config_key => $config_value) {
      if ($config_value && $config_key == 'comma-' . $node_type) {
        $include_comma[] = ',';
      }
      if ($config_key == 'exclude-' . $node_type) {
        if (!empty($config_value)) {
          $config_values = array_map('trim', explode(',', $config_value));
          $config_values = array_merge($config_values, $include_comma);
          $findings = array();
          foreach ($title as $key => $title_value) {
            if (in_array(trim($title_value), $config_values)) {
                $findings[] = $title_value;
            }
          }
          $config_values = $include_comma = [];
          if($findings){
            $this->context->addViolation("This characters/words are not allowed to enter in the title. - " . implode(', ', $findings));
          }
        }
      }
      if ($config_key == 'min-' . $node_type) {
        if (strlen($value_title) < $config_value) {
          $this->context->addViolation("Title should have minimum $config_value characters");
        }
      }
      if ($config_key == 'max-' . $node_type) {
        if (strlen($value_title) > $config_value) {
          $this->context->addViolation("Title should not exceed $config_value characters");
        }
      }
      if ($config_key == 'min-wc-' . $node_type) {
        if (str_word_count($value_title) < $config_value) {
          $this->context->addViolation("Title should have minimum word count of $config_value");
        }
      }
      if ($config_key == 'max-wc-' . $node_type) {
        if (str_word_count($value_title) > $config_value) {
          $this->context->addViolation("Title should not exceed word count of $config_value");
        }
      }
      if ($config_key == 'unique-' . $node_type || $config_key == 'unique') {
        if ($config_value == 1) {
          $nodes = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(array('title' => $value_title));
          if (isset($node)) {
            unset($nodes[$node->id()]);
          }
          if (!empty($nodes)) {
            $this->context->addViolation("There is already a node exist with title -  $value_title");
          }
        }
      }
    }
  }
}
