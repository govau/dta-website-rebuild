<?php

namespace Drupal\social_auth_google\Settings;

/**
 * Defines an interface for Social Auth Google settings.
 */
interface GoogleAuthSettingsInterface {

  /**
   * Gets the client ID.
   *
   * @return string
   *   The client ID.
   */
  public function getClientId();

  /**
   * Gets the client secret.
   *
   * @return string
   *   The client secret.
   */
  public function getClientSecret();

  /**
   * Gets the restricted domain.
   *
   * @return string
   *   The restricted domain.
   */
  public function getRestrictedDomain();

}
