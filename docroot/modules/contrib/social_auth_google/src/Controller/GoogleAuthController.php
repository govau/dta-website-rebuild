<?php

namespace Drupal\social_auth_google\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_google\GoogleAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Social Auth Google module routes.
 */
class GoogleAuthController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The user manager.
   *
   * @var \Drupal\social_auth\SocialAuthUserManager
   */
  private $userManager;

  /**
   * The google authentication manager.
   *
   * @var \Drupal\social_auth_google\GoogleAuthManager
   */
  private $googleManager;

  /**
   * Used to access GET parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

  /**
   * The Social Auth Data Handler.
   *
   * @var \Drupal\social_auth\SocialAuthDataHandler
   */
  private $dataHandler;

  /**
   * GoogleAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_google network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_auth_google\GoogleAuthManager $google_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $data_handler
   *   SocialAuthDataHandler object.
   */
  public function __construct(NetworkManager $network_manager,
                              SocialAuthUserManager $user_manager,
                              GoogleAuthManager $google_manager,
                              RequestStack $request,
                              SocialAuthDataHandler $data_handler) {

    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->googleManager = $google_manager;
    $this->request = $request;
    $this->dataHandler = $data_handler;

    // Sets the plugin id.
    $this->userManager->setPluginId('social_auth_google');

    // Sets the session keys to nullify if user could not logged in.
    $this->userManager->setSessionKeysToNullify(['access_token', 'oauth2state']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_manager'),
      $container->get('social_auth_google.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler')
    );
  }

  /**
   * Response for path 'user/login/google'.
   *
   * Redirects the user to Google for authentication.
   */
  public function redirectToGoogle() {
    /* @var \League\OAuth2\Client\Provider\Google false $google */
    $google = $this->networkManager->createInstance('social_auth_google')->getSdk();

    // If google client could not be obtained.
    if (!$google) {
      drupal_set_message($this->t('Social Auth Google not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Destination parameter specified in url.
    $destination = $this->request->getCurrentRequest()->get('destination');
    // If destination parameter is set, save it.
    if ($destination) {
      $this->userManager->setDestination($destination);
    }

    // Google service was returned, inject it to $googleManager.
    $this->googleManager->setClient($google);

    // Generates the URL where the user will be redirected for Google login.
    // If the user did not have email permission granted on previous attempt,
    // we use the re-request URL requesting only the email address.
    $google_login_url = $this->googleManager->getAuthorizationUrl();

    $state = $this->googleManager->getState();

    $this->dataHandler->set('oauth2state', $state);

    return new TrustedRedirectResponse($google_login_url);
  }

  /**
   * Response for path 'user/login/google/callback'.
   *
   * Google returns the user here after user has authenticated in Google.
   */
  public function callback() {
    // Checks if user cancel login via Google.
    $error = $this->request->getCurrentRequest()->get('error');
    if ($error == 'access_denied') {
      drupal_set_message($this->t('You could not be authenticated.'), 'error');
      return $this->redirect('user.login');
    }

    /* @var \League\OAuth2\Client\Provider\Google|false $google */
    $google = $this->networkManager->createInstance('social_auth_google')->getSdk();

    // If Google client could not be obtained.
    if (!$google) {
      drupal_set_message($this->t('Social Auth Google not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    $state = $this->dataHandler->get('oauth2state');

    // Retrieves $_GET['state'].
    $retrievedState = $this->request->getCurrentRequest()->query->get('state');
    if (empty($retrievedState) || ($retrievedState !== $state)) {
      $this->userManager->nullifySessionKeys();
      drupal_set_message($this->t('Google login failed. Unvalid OAuth2 state.'), 'error');
      return $this->redirect('user.login');
    }

    // Saves access token to session.
    $this->dataHandler->set('access_token', $this->googleManager->getAccessToken());

    $this->googleManager->setClient($google)->authenticate();

    // Gets user's info from Google API.
    if (!$google_profile = $this->googleManager->getUserInfo()) {
      drupal_set_message($this->t('Google login failed, could not load Google profile. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Gets (or not) extra initial data.
    $data = $this->userManager->checkIfUserExists($google_profile->getId()) ? NULL : $this->googleManager->getExtraDetails();

    // If user information could be retrieved.
    return $this->userManager->authenticateUser($google_profile->getName(), $google_profile->getEmail(), $google_profile->getId(), $this->googleManager->getAccessToken(), $google_profile->getAvatar(), $data);
  }

}
