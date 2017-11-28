<?php

namespace Drupal\robotstxt\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests basic functionality of configured robots.txt files.
 *
 * @group Robots.txt
 */
class RobotsTxtBasicTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['robotstxt', 'node', 'robotstxt_test'];

  /**
   * Checks that an administrator can view the configuration page.
   */
  public function testRobotsTxtAdminAccess() {
    // Create user.
    $this->admin_user = $this->drupalCreateUser(['administer robots.txt']);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/search/robotstxt');

    $this->assertFieldById('edit-robotstxt-content', NULL, 'The textarea for configuring robots.txt is shown.');
  }

  /**
   * Checks that a non-administrative user cannot use the configuration page.
   */
  public function testRobotsTxtUserNoAccess() {
    // Create user.
    $this->normal_user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($this->normal_user);
    $this->drupalGet('admin/config/search/robotstxt');

    $this->assertResponse(403);
    $this->assertNoFieldById('edit-robotstxt-content', NULL, 'The textarea for configuring robots.txt is not shown for users without appropriate permissions.');
  }

  /**
   * Test that the robots.txt path delivers content with an appropriate header.
   */
  public function testRobotsTxtPath() {
    $this->drupalGet('robots-test.txt');
    $this->assertResponse(200, 'No local robots.txt file was detected, and an anonymous user is delivered content at the /robots.txt path.');
    $this->assertHeader('Content-Type', 'text/plain; charset=UTF-8', 'The robots.txt file was served with header Content-Type: "text/plain; charset=UTF-8"');
  }

  /**
   * Checks that a configured robots.txt file is delivered as configured.
   */
  public function testRobotsTxtConfigureRobotsTxt() {
    // Create an admin user, log in and access settings form.
    $this->admin_user = $this->drupalCreateUser(['administer robots.txt']);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/search/robotstxt');

    $test_string = "# SimpleTest {$this->randomMachineName()}";
    $this->drupalPostForm('admin/config/search/robotstxt', ['robotstxt_content' => $test_string], t('Save configuration'));

    $this->drupalLogout();
    $this->drupalGet('robots-test.txt');
    $this->assertResponse(200, 'No local robots.txt file was detected, and an anonymous user is delivered content at the /robots.txt path.');
    $this->assertHeader('Content-Type', 'text/plain; charset=UTF-8', 'The robots.txt file was served with header Content-Type: "text/plain; charset=UTF-8"');
    $content = $this->getRawContent();
    $this->assertTrue($content == $test_string, sprintf('Test string [%s] is displayed in the configured robots.txt file [%s].', $test_string, $content));
  }

}
