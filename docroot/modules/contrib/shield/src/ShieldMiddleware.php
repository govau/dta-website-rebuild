<?php

namespace Drupal\shield;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Middleware for the shield module.
 */
class ShieldMiddleware implements HttpKernelInterface {

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a BanMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(HttpKernelInterface $http_kernel, ConfigFactoryInterface $config_factory) {
    $this->httpKernel = $http_kernel;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $config = $this->configFactory->get('shield.settings');
    $allow_cli = $config->get('allow_cli');

    switch ($config->get('credential_provider')) {
      case 'shield':
        $user = $config->get('credentials.shield.user');
        $pass = $config->get('credentials.shield.pass');
        break;
      case 'key':
        $user = $config->get('credentials.key.user');

        /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
        $storage = \Drupal::entityTypeManager()->getStorage('key');
        /** @var \Drupal\key\KeyInterface $pass_key */
        $pass_key = $storage->load($config->get('credentials.key.pass_key'));
        if ($pass_key) {
          $pass = $pass_key->getKeyValue();
        }
        break;
      case 'multikey':
        /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
        $storage = \Drupal::entityTypeManager()->getStorage('key');
        /** @var \Drupal\key\KeyInterface $user_pass_key */
        $user_pass_key = $storage->load($config->get('credentials.multikey.user_pass_key'));
        if ($user_pass_key) {
          $values = $user_pass_key->getKeyValues();
          $user = $values['username'];
          $pass = $values['password'];
        }
        break;
    }

    if ($type != self::MASTER_REQUEST || !$user || (PHP_SAPI === 'cli' && $allow_cli)) {
      // Bypass:
      // 1. Subrequests
      // 2. Empty username
      // 3. CLI requests if CLI is allowed.
      return $this->httpKernel->handle($request, $type, $catch);
    }
    else {
      if ($request->server->has('PHP_AUTH_USER') && $request->server->has('PHP_AUTH_PW')) {
        $input_user = $request->server->get('PHP_AUTH_USER');
        $input_pass = $request->server->get('PHP_AUTH_PW');
      }
      elseif (!empty($request->server->get('HTTP_AUTHORIZATION'))) {
        list($input_user, $input_pass) = explode(':', base64_decode(substr($request->server->get('HTTP_AUTHORIZATION'), 6)), 2);
      }
      elseif (!empty($request->server->get('REDIRECT_HTTP_AUTHORIZATION'))) {
        list($input_user, $input_pass) = explode(':', base64_decode(substr($request->server->get('REDIRECT_HTTP_AUTHORIZATION'), 6)), 2);
      }

      if (isset($input_user) && $input_user === $user && Crypt::hashEquals($pass, $input_pass)) {
        return $this->httpKernel->handle($request, $type, $catch);
      }
    }

    $response = new Response();
    $response->headers->add([
      'WWW-Authenticate' => 'Basic realm="' . strtr($config->get('print'), [
          '[user]' => $user,
          '[pass]' => $pass,
        ]) . '"',
    ]);
    $response->setStatusCode(401);
    return $response;
  }

}
