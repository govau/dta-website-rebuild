<?php

namespace Drupal\password_policy\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use \Drupal\user\Entity\User;

/**
 * Enforces password reset functionality.
 */
class PasswordPolicyEventSubscriber implements EventSubscriberInterface {

  /**
   * Event callback to look for users expired password.
   */
  public function checkForUserPasswordExpiration(GetResponseEvent $event) {
    $account = \Drupal::currentUser();
    // There needs to be an explicit check for non-anonymous or else
    // this will be tripped and a forced redirect will occur.
    if ($account->id() > 0) {
      /* @var $user \Drupal\user\UserInterface */
      $user = User::load($account->id());
      $route_name = \Drupal::request()->attributes->get(RouteObjectInterface::ROUTE_NAME);

      // system/ajax.
      $ignored_routes = [
        'entity.user.edit_form',
        'system.ajax',
        'user.logout',
      ];

      $user_expired = FALSE;
      if ($user->get('field_password_expiration')->get(0)) {
        $user_expired = $user->get('field_password_expiration')
          ->get(0)
          ->getValue();
        $user_expired = $user_expired['value'];
      }

      // TODO - Consider excluding admins here.
      if ($user_expired and !in_array($route_name, $ignored_routes)) {
        $url = new Url('entity.user.edit_form', ['user' => $user->id()]);
        $url = $url->setAbsolute(TRUE)->toString();
        $event->setResponse(new RedirectResponse($url));
        drupal_set_message('Your password has expired, please update it', 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    // TODO - Evaluate if there is a better place to add this check.
    $events[KernelEvents::REQUEST][] = ['checkForUserPasswordExpiration'];
    return $events;
  }

}
