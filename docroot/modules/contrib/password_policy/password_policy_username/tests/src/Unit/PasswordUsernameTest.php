<?php

namespace Drupal\Tests\password_policy_username\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the password username constraint.
 *
 * @group password_policy_username
 */
class PasswordUsernameTest extends UnitTestCase {

  /**
   * Tests the password to make sure it doesn't contain the user's username.
   *
   * @dataProvider passwordUsernameDataProvider
   */
  public function testPasswordUsername($disallow_username, $user_context, $password, $result) {
    $username_test = $this->getMockBuilder('Drupal\password_policy_username\Plugin\PasswordConstraint\PasswordUsername')
      ->disableOriginalConstructor()
      ->setMethods(['getConfiguration', 't'])
      ->getMock();

    $username_test
      ->method('getConfiguration')
      ->willReturn(['disallow_username' => $disallow_username]);

    $this->assertEquals($username_test->validate($password, $user_context)->isValid(), $result);
  }

  /**
   * Provides data for the testPasswordUsername method.
   */
  public function passwordUsernameDataProvider() {
    $user_context = [
      'mail' => 'test@example.com',
      'name' => 'username',
      'uid' => 10,
    ];

    return [
      // Passing conditions.
      [
        TRUE,
        $user_context,
        'password',
        TRUE,
      ],
      [
        FALSE,
        $user_context,
        'username',
        TRUE,
      ],
      // Failing conditions.
      [
        TRUE,
        $user_context,
        'username',
        FALSE,
      ],
      [
        TRUE,
        $user_context,
        'my_username',
        FALSE,
      ],
    ];
  }

}
