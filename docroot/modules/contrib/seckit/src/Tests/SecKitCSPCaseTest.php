<?php
/**
 * @file
 * Tests 2 for Security Kit module.
 */

namespace Drupal\seckit\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\seckit\EventSubscriber\SecKitEventSubscriber;

/**
 * Functional tests for Security Kit.
 *
 * @group seckit
 */
class SecKitCSPCaseTest extends WebTestBase {
  /**
   * Admin user for tests
   * @var object
   */
  private $admin;

  /**
   * CSP report url.
   *
   * @var string
   */
  private $report_path;

  public static $modules = array('seckit');
  /**
   * Implements getInfo().
   * @see DrupalWebTestCase::getInfo()
   */
  public static function getInfo() {
    return array(
      'name' => t('Security Kit CSP functionality'),
      'description' => t('Tests CSP functionality and settings page of Security Kit module.'),
      'group' => t('Security Kit'),
    );
  }

  /**
   * Implements setUp().
   * @see DrupalWebTestCase::setUp()
   */
  public function setUp() {
    parent::setUp();
    $this->admin = $this->drupalCreateUser(array('administer seckit'));
    $this->drupalLogin($this->admin);

    $route_provider = \Drupal::service('router.route_provider');
    $route = $route_provider->getRouteByName('seckit.report');
    // Need to remove trailing slash so it is not escapted in string.
    $path = $route->getPath();
    $this->report_path = ltrim($path, '/');
  }

  /**
   * Tests Content Security Policy with all enabled directives.
   */
  public function testCSPHasAllDirectives() {
    $form = array(
      'seckit_xss[csp][checkbox]'    => TRUE,
      'seckit_xss[csp][default-src]' => '*',
      'seckit_xss[csp][script-src]'  => '*',
      'seckit_xss[csp][object-src]'  => '*',
      'seckit_xss[csp][style-src]'   => '*',
      'seckit_xss[csp][img-src]'     => '*',
      'seckit_xss[csp][media-src]'   => '*',
      'seckit_xss[csp][frame-src]'   => '*',
      'seckit_xss[csp][child-src]'   => '*',
      'seckit_xss[csp][font-src]'    => '*',
      'seckit_xss[csp][connect-src]' => '*',
      'seckit_xss[csp][report-uri]'  => $this->report_path,
    );
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $expected = 'default-src *; script-src *; object-src *; style-src *; img-src *; media-src *; frame-src *; child-src *; font-src *; connect-src *; report-uri ' . base_path() . $this->report_path;
    $this->assertEqual($expected, $this->drupalGetHeader('Content-Security-Policy'),
      t('Content-Security-Policy has all the directves (Official).'));
    $this->assertEqual($expected, $this->drupalGetHeader('X-Content-Security-Policy'),
      t('X-Content-Security-Policy has all the directves (Mozilla and IE10).'));
    $this->assertEqual($expected, $this->drupalGetHeader('X-WebKit-CSP'),
      t('X-WebKit-CSP has all the directves (Chrome and Safari).'));
  }

  /**
   * Tests Content Security Policy with policy-uri directive.
   * In this case, only policy-uri directive should be present.
   *//*
  public function testCSPPolicyUriDirectiveOnly() {
    $form = array(
      'seckit_xss[csp][checkbox]'    => TRUE,
      'seckit_xss[csp][default-src]' => '*',
      'seckit_xss[csp][script-src]'  => '*',
      'seckit_xss[csp][object-src]'  => '*',
      'seckit_xss[csp][style-src]'   => '*',
      'seckit_xss[csp][img-src]'     => '*',
      'seckit_xss[csp][media-src]'   => '*',
      'seckit_xss[csp][frame-src]'   => '*',
      'seckit_xss[csp][child-src]'   => '*',
      'seckit_xss[csp][font-src]'    => '*',
      'seckit_xss[csp][connect-src]' => '*',
      'seckit_xss[csp][report-uri]'  => SECKIT_CSP_REPORT_URL,
      'seckit_xss[csp][policy-uri]'  => 'http://mysite.com/csp.xml',
    );
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $expected = 'policy-uri http://mysite.com/csp.xml';
    $this->assertEqual($expected, $this->drupalGetHeader('Content-Security-Policy'),
      t('Content-Security-Policy has only policy-uri (Official).'));
    $this->assertEqual($expected, $this->drupalGetHeader('X-Content-Security-Policy'),
      t('X-Content-Security-Policy has only policy-uri (Mozilla and IE10).'));
    $this->assertEqual($expected, $this->drupalGetHeader('X-WebKit-CSP'),
      t('X-WebKit-CSP has only policy-uri(Chrome and Safari).'));
  }
*/
  /**
   * Tests Content Security Policy with all directives empty.
   * In this case, we should revert back to default values.
   */
  public function testCSPAllDirectivesEmpty() {
    $form = array(
      'seckit_xss[csp][checkbox]'    => TRUE,
      'seckit_xss[csp][default-src]' => 'self',
      'seckit_xss[csp][script-src]'  => '',
      'seckit_xss[csp][object-src]'  => '',
      'seckit_xss[csp][img-src]'     => '',
      'seckit_xss[csp][media-src]'   => '',
      'seckit_xss[csp][style-src]'   => '',
      'seckit_xss[csp][frame-src]'   => '',
      'seckit_xss[csp][child-src]'   => '',
      'seckit_xss[csp][font-src]'    => '',
      'seckit_xss[csp][connect-src]' => '',
      'seckit_xss[csp][report-uri]'  => $this->report_path,
      'seckit_xss[csp][policy-uri]'  => '',
    );
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $expected = "default-src self; report-uri " . base_path() . $this->report_path;
    $this->assertEqual($expected, $this->drupalGetHeader('Content-Security-Policy'),
      t('Content-Security-Policy has default directive (Official).'));
    $this->assertEqual($expected, $this->drupalGetHeader('X-Content-Security-Policy'),
      t('X-Content-Security-Policy has default directive (Mozilla and IE10).'));
    $this->assertEqual($expected, $this->drupalGetHeader('X-WebKit-CSP'),
      t('X-WebKit-CSP has default directive (Chrome and Safari).'));
  }

}
