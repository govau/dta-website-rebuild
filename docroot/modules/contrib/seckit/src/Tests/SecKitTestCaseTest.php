<?php
/**
 * @file
 * Tests for Security Kit module.
 */

namespace Drupal\seckit\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\seckit\EventSubscriber\SecKitEventSubscriber;

/**
 * Functional tests for Security Kit.
 *
 * @group seckit
 */
class SecKitTestCaseTest extends WebTestBase {
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
      'name' => t('Security Kit functionality'),
      'description' => t('Tests functionality and settings page of Security Kit module.'),
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
   * Tests disabled Content Security Policy.
   */
  public function testDisabledCSP() {
    $form['seckit_xss[csp][checkbox]'] = FALSE;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertFalse($this->drupalGetHeader('Content-Security-Policy'),
      t('Content Security Policy is disabled (Official).'));
    $this->assertFalse($this->drupalGetHeader('X-Content-Security-Policy'),
      t('Content Security Policy is disabled (Mozilla and IE10).'));
    $this->assertFalse($this->drupalGetHeader('X-WebKit-CSP'),
      t('Content Security Policy is disabled (Chrome and Safari).'));
  }

  /**
   * Tests Content Security Policy with all enabled directives.
   */
  public function testCSPHasAllDirectives() {
    $form = array(
      'seckit_xss[csp][checkbox]' => TRUE,
      'seckit_xss[csp][default-src]' => '*',
      'seckit_xss[csp][script-src]' => '*',
      'seckit_xss[csp][object-src]' => '*',
      'seckit_xss[csp][style-src]' => '*',
      'seckit_xss[csp][img-src]' => '*',
      'seckit_xss[csp][media-src]' => '*',
      'seckit_xss[csp][frame-src]' => '*',
      'seckit_xss[csp][child-src]' => '*',
      'seckit_xss[csp][font-src]' => '*',
      'seckit_xss[csp][connect-src]' => '*',
      'seckit_xss[csp][report-uri]' => $this->report_path,
    );
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $expected = 'default-src *; script-src *; object-src *; style-src *; img-src *; media-src *; frame-src *; child-src *; font-src *; connect-src *; report-uri ' . base_path() . $this->report_path;
    $this->assertEqual($expected, $this->drupalGetHeader('Content-Security-Policy'),
      t('Content-Security-Policy has all the directives (Official).'));
    $this->assertEqual($expected, $this->drupalGetHeader('X-Content-Security-Policy'),
      t('X-Content-Security-Policy has all the directives (Mozilla and IE10).'));
    $this->assertEqual($expected, $this->drupalGetHeader('X-WebKit-CSP'),
      t('X-WebKit-CSP has all the directives (Chrome and Safari).'));
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
  }*/

  /**
   * Tests Content Security Policy with all directives empty.
   * In this case, we should revert back to default values.
   */
  public function testCSPAllDirectivesEmpty() {
    $form = array(
      'seckit_xss[csp][checkbox]' => TRUE,
      'seckit_xss[csp][default-src]' => 'self',
      'seckit_xss[csp][script-src]' => '',
      'seckit_xss[csp][object-src]' => '',
      'seckit_xss[csp][img-src]' => '',
      'seckit_xss[csp][media-src]' => '',
      'seckit_xss[csp][style-src]' => '',
      'seckit_xss[csp][frame-src]' => '',
      'seckit_xss[csp][child-src]' => '',
      'seckit_xss[csp][font-src]' => '',
      'seckit_xss[csp][connect-src]' => '',
      'seckit_xss[csp][report-uri]' => $this->report_path,
      'seckit_xss[csp][policy-uri]' => '',
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

  /**
   * Tests Content Security Policy in report-only mode.
   *//*
  public function testReportOnlyCSP() {
    $form['seckit_xss[csp][checkbox]'] = TRUE;
    $form['seckit_xss[csp][report-only]'] = TRUE;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertTrue($this->drupalGetHeader('Content-Security-Policy-Report-Only'),
      t('Content Security Policy is in report-only mode (Official).'));
    $this->assertTrue($this->drupalGetHeader('X-Content-Security-Policy-Report-Only'),
      t('Content Security Policy is in report-only mode (Mozilla and IE10).'));
    $this->assertTrue($this->drupalGetHeader('X-WebKit-CSP-Report-Only'),
      t('Content Security Policy is in report-only mode (Chrome and Safari).'));
  }
*/
  /**
   * Tests disabled X-XSS-Protection HTTP response header.
   */
  public function testXXSSProtectionIsDisabled() {
    $form['seckit_xss[x_xss][select]'] = SECKIT_X_XSS_DISABLE;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertFalse($this->drupalGetHeader('X-XSS-Protection'),
      t('X-XSS-Protection is disabled.'));
  }

  /**
   * Tests set to 0 X-XSS-Protection HTTP response header.
   */
  public function testXXSSProtectionIs0() {
    $form['seckit_xss[x_xss][select]'] = SECKIT_X_XSS_0;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertEqual(0, $this->drupalGetHeader('X-XSS-Protection'),
      t('X-XSS-Protection is set to 0.'));
  }

  /**
   * Tests set to 1 X-XSS-Protection HTTP response header.
   */
  public function testXXSSProtectionIs1() {
    $form['seckit_xss[x_xss][select]'] = SECKIT_X_XSS_1;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertEqual('1', $this->drupalGetHeader('X-XSS-Protection'),
      t('X-XSS-Protection is set to 1.'));
  }

  /**
   * Tests set to 1; mode=block X-XSS-Protection HTTP response header.
   */
  public function testXXSSProtectionIs1Block() {
    $form['seckit_xss[x_xss][select]'] = SECKIT_X_XSS_1_BLOCK;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertEqual('1; mode=block', $this->drupalGetHeader('X-XSS-Protection'),
      t('X-XSS-Protection is set to 1; mode=block.'));
  }

  /**
   * Tests disabled X-Content-Type-Options HTTP response header.
   */
  public function testDisabledXContentTypeOptions() {
    $form['seckit_xss[x_content_type][checkbox]'] = FALSE;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertFalse($this->drupalGetHeader('X-Content-Type-Options'),
      t('X-Content-Type-Options is disabled.'));
  }

  /**
   * Tests enabled X-Content-Type-Options HTTP response header.
   */
  public function testEnabledXContentTypeOptions() {
    $form['seckit_xss[x_content_type][checkbox]'] = TRUE;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertEqual('nosniff', $this->drupalGetHeader('X-Content-Type-Options'),
      t('X-Content-Type-Options is enabled and set to nosniff.'));
  }

  /**
   * Tests HTTP Origin allows requests from the site.
   */
  public function testOriginAllowsSite() {
    global $base_url;
    $form['seckit_csrf[origin]'] = TRUE;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'),
      array(), array('Origin: ' . $base_url));
    $this->assertResponse(200,
      t('Request is allowed.'));
  }

  /**
   * Tests HTTP Origin allows requests from the specified source.
   */
  public function testOriginAllowsSpecifiedSource() {
    $form = array(
      'seckit_csrf[origin]' => TRUE,
      'seckit_csrf[origin_whitelist]' => 'http://www.example.com',
    );
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'),
      array(), array('Origin: http://www.example.com'));
    $this->assertResponse(200,
      t('Whitelisted request is allowed.'));
  }

  /**
   * Tests HTTP Origin denies request.
   */
  public function testOriginDeny() {
    $form['seckit_csrf[origin]'] = TRUE;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'),
      array(), array('Origin: http://www.example.com'));
    $this->assertEqual(array(), $_POST,
      t('POST is empty.'));
    $this->assertResponse(403,
      t('Request is denied.'));
  }

  /**
   * Tests disabled X-Frame-Options HTTP response header.
   */
  public function testXFrameOptionsIsDisabled() {
    $form['seckit_clickjacking[x_frame]'] = SECKIT_X_FRAME_DISABLE;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertFalse($this->drupalGetHeader('X-Frame-Options'),
      t('X-Frame-Options is disabled.'));
  }

  /**
   * Tests set to SameOrigin X-Frame-Options HTTP response header.
   */
  public function testXFrameOptionsIsSameOrigin() {
    $form['seckit_clickjacking[x_frame]'] = SECKIT_X_FRAME_SAMEORIGIN;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertEqual('SameOrigin', $this->drupalGetHeader('X-Frame-Options'),
      t('X-Frame-Options is set to SameOrigin.'));
  }

  /**
   * Tests set to Deny X-Frame-Options HTTP response header.
   */
  public function testXFrameOptionsIsDeny() {
    $form['seckit_clickjacking[x_frame]'] = SECKIT_X_FRAME_DENY;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertEqual('Deny', $this->drupalGetHeader('X-Frame-Options'),
      t('X-Frame-Options is set to Deny.'));
  }

  /**
   * Tests set to Allow-From X-Frame-Options HTTP response header.
   */
  public function testXFrameOptionsIsAllowFrom() {
    $form['seckit_clickjacking[x_frame]'] = SECKIT_X_FRAME_ALLOW_FROM;
    $form['seckit_clickjacking[x_frame_allow_from]'] = 'http://www.google.com';
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertEqual('Allow-From: http://www.google.com', $this->drupalGetHeader('X-Frame-Options'),
      t('X-Frame-Options is set to Allow-From.'));
  }

  /**
   * Tests JS + CSS + Noscript protection.
   */
  public function testJSCSSNoscript() {
    $form['seckit_clickjacking[js_css_noscript]'] = TRUE;
    $form['seckit_clickjacking[noscript_message]'] = 'Sorry, your JavaScript is disabled.';
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $config = \Drupal::config('seckit.settings');
    $noscript_message = $config->get('seckit_clickjacking.noscript_message');
    // @TODO this was duplicated from the Event subscriber, move to function
    // in .module file?
    $noscript_message = $noscript_message ?
      $noscript_message :
      $this->config->get('seckit_clickjacking.noscript_message');
    $path = base_path() . drupal_get_path('module', 'seckit');
    $code = <<< EOT
        <script type="text/javascript" src="$path/js/seckit.document_write.js"></script>
        <link type="text/css" rel="stylesheet" id="seckit-clickjacking-no-body" media="all" href="$path/css/seckit.no_body.css" />
        <!-- stop SecKit protection -->
        <noscript>
        <link type="text/css" rel="stylesheet" id="seckit-clickjacking-noscript-tag" media="all" href="$path/css/seckit.noscript_tag.css" />
        <div id="seckit-noscript-tag">
          $noscript_message
        </div>
        </noscript>
EOT;
    $this->assertRaw($code,
      t('JavaScript + CSS + Noscript protection is loaded.'));
  }

  /**
   * Tests disabled HTTP Strict Transport Security.
   */
  public function testDisabledHSTS() {
    $form['seckit_ssl[hsts]'] = FALSE;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertFalse($this->drupalGetHeader('Strict-Transport-Security'),
      t('HTTP Strict Transport Security is disabled.'));
  }

  /**
   * Tests HTTP Strict Transport Security has all directives.
   */
  public function testHSTSAllDirectves() {
    $form = array(
      'seckit_ssl[hsts]' => TRUE,
      'seckit_ssl[hsts_max_age]' => 1000,
      'seckit_ssl[hsts_subdomains]' => 1,
    );
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $expected = 'max-age=1000; includeSubDomains';
    $this->assertEqual($expected, $this->drupalGetHeader('Strict-Transport-Security'),
      t('HTTP Strict Transport Security has all the directives.'));
  }

  /**
   * Tests disabled From-Origin.
   */
  public function testDisabledFromOrigin() {
    $form['seckit_various[from_origin]'] = FALSE;
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertFalse($this->drupalGetHeader('From-Origin'),
      t('From-Origin is disabled.'));
  }

  /**
   * Tests enabled From-Origin.
   */
  public function testEnabledFromOrigin() {
    $form = array(
      'seckit_various[from_origin]' => TRUE,
      'seckit_various[from_origin_destination]' => 'same',
    );
    $this->drupalPostForm('admin/config/system/seckit', $form, t('Save configuration'));
    $this->assertEqual('same', $this->drupalGetHeader('From-Origin'),
      t('From-Origin is enabled and set to same.'));
  }

}
