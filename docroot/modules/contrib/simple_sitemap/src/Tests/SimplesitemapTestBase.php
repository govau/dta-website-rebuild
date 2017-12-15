<?php

namespace Drupal\simple_sitemap\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Class SimplesitemapTestBase
 * @package Drupal\simple_sitemap\Tests
 */
abstract class SimplesitemapTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['simple_sitemap', 'node', 'content_translation'];

  /**
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * @var \Drupal\node\Entity\Node
   */
  protected $node2;

  // Uncomment to disable strict schema checks.
//  protected $strictConfigSchema = FALSE;

  /**
   * @inheritdoc
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);
    $this->node = $this->createNode(['title' => 'Node', 'type' => 'page']);
    $this->node2 = $this->createNode(['title' => 'Node2', 'type' => 'page']);

    $this->generator = \Drupal::service('simple_sitemap.generator');
  }

  protected function createPrivilegedUser() {
    $permissions = array_keys(\Drupal::service('user.permissions')->getPermissions());
    return $this->drupalCreateUser($permissions);
  }
}
