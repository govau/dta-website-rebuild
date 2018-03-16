<?php

namespace Drupal\s3fs\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests whether access_key & secret_key is set at the admin configuration page,
 * and whether the access_key & secret_key is stored successfully or not.
 *
 * @group s3fs
 */
class ApiKeyConfigurationTest extends WebTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['s3fs'];

  /**
   * A user with permission to administer site configuration and administer s3fs.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Creates administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer s3fs',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test to implement a runtime requirement checking if API key is not set.
   */
  public function testKeyConfiguration() {
    $this->drupalGet(Url::fromRoute('s3fs.admin_settings'));
    $this->assertResponse(200);
    $this->assertFieldByName('access_key', '', 'The Access API key is not set');
    $this->assertFieldByName('secret_key', '', 'The Secret API key is not set');
    $this->drupalGet(Url::fromRoute('system.status'));
    $this->assertText('S3 File System access key or secret key is not set and it is required for some functionalities to work.', 'The content exists on the report page');
    $this->assertLink('S3 File System module settings page', 0, 'Link to the api key configuration page found');
    $this->assertLinkByHref(Url::fromRoute('s3fs.admin_settings')->toString());
  }

  /**
   * Test to verify that it is stored successfully.
   */
  public function testKeyStorage() {
    $this->drupalGet(Url::fromRoute('s3fs.admin_settings'));
    $this->assertResponse(200);
    $access_key = $this->randomString(40);
    $secret_key = $this->randomString(40);
    $edit = [
      'access_key' => $access_key,
      'secret_key' => $secret_key,
      'bucket' => $this->randomString(8),
    ];
    $this->drupalPostForm(Url::fromRoute('s3fs.admin_settings'), $edit, $this->t('Save configuration'));
    $this->assertText($this->t('The configuration options have been saved.'), $this->t('Saved configuration'));
  }
}
