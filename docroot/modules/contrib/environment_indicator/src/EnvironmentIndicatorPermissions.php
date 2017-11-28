<?php

namespace Drupal\environment_indicator;

class EnvironmentIndicatorPermissions {

  /**
   * Returns the dynamic permissions array.
   *
   * @return array
   *   The permissions configuration array.
   */
  public function permissions() {
    $permissions = [];
    // TODO: Learn how to inject the EntityConfig loader.
    // $environments = environment_indicator_get_all();
    $environments = [];
    foreach ($environments as $machine => $environment) {
      $permissions['access environment indicator ' . $environment->machine] = [
        'title' => t('See environment indicator for %name', ['%name' => $environment->name]),
        'description' => t('See the environment indicator if the user is in the %name environment.', ['%name' => $environment->name]),
      ];
    }
    return $permissions;
  }
}
