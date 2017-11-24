<?php

namespace Drupal\password_policy;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * An interface to define the expected operations of a password constraint.
 */
interface PasswordConstraintInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns a true/false status if the password meets the constraint.
   *
   * @param string $password
   *   The password entered by the end user.
   *
   * @return PasswordPolicyValidation
   *   Whether or not the password meets the constraint in the plugin.
   */
  public function validate($password, $user_context);

  /**
   * Returns a translated string for the constraint title.
   *
   * @return string
   *   Title of the constraint.
   */
  public function getTitle();

  /**
   * Returns a translated description for the constraint description.
   *
   * @return string
   *   Description of the constraint.
   */
  public function getDescription();

  /**
   * Returns a translated error message for the constraint.
   *
   * @return string
   *   Error message if the constraint fails.
   */
  public function getErrorMessage();

  /**
   * Returns a human-readable summary of the constraint.
   *
   * @return string
   *   Summary of the constraint behaviors or restriction.
   */
  public function getSummary();

}
