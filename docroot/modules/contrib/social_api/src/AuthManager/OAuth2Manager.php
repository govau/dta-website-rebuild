<?php

namespace Drupal\social_api\AuthManager;

/**
 * Defines basic OAuth2Manager to be used by social auth and social post.
 *
 * @package Drupal\social_api
 */
abstract class OAuth2Manager implements OAuth2ManagerInterface {

  /**
   * The service client.
   *
   * @var \League\OAuth2\Client\Provider\AbstractProvider|mixed
   */
  protected $client;

  /**
   * Access token for OAuth2 authentication.
   *
   * @var \League\OAuth2\Client\Token\AccessToken|string|mixed
   */
  protected $accessToken;

  /**
   * The user returned by the provider.
   *
   * @var \League\OAuth2\Client\Provider\GenericResourceOwner|array|mixed
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setClient($client) {
    $this->client = $client;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken() {
    return $this->accessToken;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessToken($access_token) {
    $this->accessToken = $access_token;
  }

}
