<?php

namespace Drupal\social_api\Settings;

use Drupal\Core\Config\ImmutableConfig;

/**
 * Class SettingsInterface.
 *
 * @package Drupal\social_api\Settings
 */
interface SettingsInterface {

  /**
   * Gets the configuration object.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration object associated with the settings.
   */
  public function getConfig();

  /**
   * Factory method to create a new settings object.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The configuration object.
   */
  public static function factory(ImmutableConfig $config);

}
