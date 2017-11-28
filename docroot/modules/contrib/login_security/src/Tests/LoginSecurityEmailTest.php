<?php

namespace Drupal\login_security\Tests;

use Drupal\Core\Form\FormState;

/**
 * Test that emails are properly sent when configured.
 *
 * @group login_security
 */
class LoginSecurityEmailTest extends LoginSecurityTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'login_security'];

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create some users.
    $this->admin = $this->drupalCreateUser();
    $this->account = $this->drupalCreateUser();
    $this->drupalLoginLite($this->account);

    // Setup emails to be sent.
    \Drupal::configFactory()->getEditable('login_security.settings')
      ->set('user_blocked_email_user', $this->admin->getDisplayName())
      ->set('login_activity_email_user', $this->admin->getDisplayName())
      ->save();
  }

  /**
   * Test that email is sent when users are blocked.
   */
  public function testBlockedEmail() {
    $variables = ['@uid' => $this->account->id()];
    $form_state = new FormState();
    login_user_block_user_name($variables, $form_state);
    $this->assertMail('to', $this->admin->getEmail(), 'Mail sent when a user is blocked.');
  }

  /**
   * Test that email is sent when activity exceeds configured threshold.
   */
  public function testActivityThresholdEmail() {
    // @todo.
  }

}
