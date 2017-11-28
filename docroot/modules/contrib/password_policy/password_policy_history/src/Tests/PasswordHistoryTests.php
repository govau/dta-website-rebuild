<?php

namespace Drupal\password_policy_history\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests password history.
 *
 * @group password_policy_history
 */
class PasswordHistoryTests extends WebTestBase {

  public static $modules = ['password_policy', 'password_policy_history'];

  /**
   * Test history constraint.
   */
  public function testHistoryConstraint() {
    // Create user with permission to create policy.
    $user1 = $this->drupalCreateUser([
      'administer site configuration',
      'administer users',
      'administer permissions',
    ]);
    $this->drupalLogin($user1);

    $user2 = $this->drupalCreateUser();

    // Create role.
    $rid = $this->drupalCreateRole([]);

    // Set role for user.  Also manually update password.  The user insert hook
    // does not add a password hash in the password_policy_history table for
    // users on initial creation via drupalCreateUser(), but this password
    // update will register an entry since the password is updated in the
    // form instead.
    $edit = [
      'roles[' . $rid . ']' => $rid,
      'pass[pass1]' => $user2->pass_raw,
      'pass[pass2]' => $user2->pass_raw,
    ];
    $user_path = 'user/' . $user2->id() . '/edit';
    $this->drupalPostForm($user_path, $edit, t('Save'));

    // Create new password reset policy for role.
    $this->drupalGet("admin/config/security/password-policy/add");
    $edit = [
      'id' => 'test',
      'label' => 'test',
      'password_reset' => '1',
    ];
    // Set reset and policy info.
    $this->drupalPostForm(NULL, $edit, 'Next');

    $this->assertText('No constraints have been configured.');

    // Fill out length constraint for test policy.
    $edit = [
      'history_repeats' => '1',
    ];
    $this->drupalPostForm('admin/config/system/password_policy/constraint/add/test/password_policy_history_constraint', $edit, 'Save');

    $this->assertText('password_policy_history_constraint');
    $this->assertText('Number of allowed repeated passwords: 1');

    // Go to the next page.
    $this->drupalPostForm(NULL, [], 'Next');
    // Set the roles for the policy.
    $edit = [
      'roles[' . $rid . ']' => $rid,
    ];
    $this->drupalPostForm(NULL, $edit, 'Finish');

    $this->assertText('Saved the test Password Policy.');

    // Login as user2.
    $this->drupalLogin($user2);

    // Change password to the same thing.
    $edit = [
      'current_pass' => $user2->pass_raw,
      'pass[pass1]' => $user2->pass_raw,
      'pass[pass2]' => $user2->pass_raw,
    ];
    $this->drupalPostAjaxForm($user_path, $edit, 'pass[pass1]');
    $this->assertNoText('Password has been reused too many times.  Choose a different password.');

    // Save the form so the password history updates.
    $this->drupalPostForm($user_path, $edit, t('Save'));
    $this->assertText('The changes have been saved.');

    // Change password to the same thing again.
    $this->drupalPostAjaxForm($user_path, $edit, 'pass[pass1]');
    $this->assertText('Password has been reused too many times.  Choose a different password.');

    // Attempt to save the form.  Should not succeed.
    $this->drupalPostForm($user_path, $edit, t('Save'));
    $this->assertText('The password does not satisfy the password policies');

    $this->drupalLogout();
  }

}
