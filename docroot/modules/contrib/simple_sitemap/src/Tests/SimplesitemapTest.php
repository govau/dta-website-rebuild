<?php

namespace Drupal\simple_sitemap\Tests;

/**
 * Tests Simple XML sitemap functional integration.
 *
 * @package Drupal\simple_sitemap\Tests
 * @group simple_sitemap
 */
class SimplesitemapTest extends SimplesitemapTestBase {

  /**
   * Verify sitemap.xml has the link to the front page after first generation.
   */
  public function testInitialGeneration() {
    $this->generator->generateSitemap('nobatch');
    $this->drupalGet('sitemap.xml');
    $this->assertRaw('urlset');
    $this->assertText($GLOBALS['base_url']);
    $this->assertText('1.0');
    $this->assertText('daily');
  }

  public function testAddCustomLink() {
    $this->generator->addCustomLink('/node/' . $this->node->id(), ['priority' => 0.2, 'changefreq' => 'monthly'])
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('0.2');
    $this->assertText('monthly');

    $this->drupalLogin($this->createPrivilegedUser());

    $this->drupalGet('admin/config/search/simplesitemap/custom');
    $this->assertText('/node/' . $this->node->id() . ' 0.2 monthly');

    $this->generator->addCustomLink('/node/' . $this->node->id(), ['changefreq' => 'yearly'])
      ->generateSitemap('nobatch');

    $this->drupalGet('admin/config/search/simplesitemap/custom');
    $this->assertText('/node/' . $this->node->id() . ' yearly');
  }

  /**
   * Test default settings of custom links.
   */
  public function testAddCustomLinkDefaults() {
    $this->generator->removeCustomLinks()
      ->addCustomLink('/node/' . $this->node->id())
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('0.5');
    $this->assertNoRaw('changefreq');
  }

  /**
   * Test removing custom links from the sitemap.
   */
  public function testRemoveCustomLink() {
    $this->generator->addCustomLink('/node/' . $this->node->id())
      ->removeCustomLink('/node/' . $this->node->id())
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertNoText('node/' . $this->node->id());
  }

  /**
   * Test removing all custom paths from the sitemap settings.
   */
  public function testRemoveCustomLinks() {
    $this->generator->removeCustomLinks()
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertNoText($GLOBALS['base_url']);
  }

  /**
   * Tests setting bundle settings.
   *
   * @todo Add form tests
   */
  public function testSetBundleSettings() {

    $this->assertFalse($this->generator->bundleIsIndexed('node', 'page'));

    // Index new bundle.
    $this->generator->removeCustomLinks()
      ->setBundleSettings('node', 'page', ['index' => TRUE, 'priority' => 0.5, 'changefreq' => 'hourly'])
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('0.5');
    $this->assertText('hourly');

    $this->assertTrue($this->generator->bundleIsIndexed('node', 'page'));

    // Only change bundle priority.
    $this->generator->setBundleSettings('node', 'page', ['priority' => 0.9])
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertNoText('0.5');
    $this->assertText('0.9');

    // Only change bundle changefreq.
    $this->generator->setBundleSettings('node', 'page', ['changefreq' => 'daily'])
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertNoText('hourly');
    $this->assertText('daily');

    // Remove changefreq setting.
    $this->generator->setBundleSettings('node', 'page', ['changefreq' => ''])
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertNoRaw('changefreq');
    $this->assertNoText('daily');

    // Index two bundles.
    $this->drupalCreateContentType(['type' => 'blog']);

    $node3 = $this->createNode(['title' => 'Node3', 'type' => 'blog']);
    $this->generator->setBundleSettings('node', 'page', ['index' => TRUE])
      ->setBundleSettings('node', 'blog', ['index' => TRUE])
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('node/' . $node3->id());

    // Set bundle 'index' setting to false.
    $this->generator->setBundleSettings('node', 'page', ['index' => FALSE])
      ->setBundleSettings('node', 'blog', ['index' => FALSE])
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertNoText('node/' . $this->node->id());
    $this->assertNoText('node/' . $node3->id());
  }

  /**
   * Test default settings of bundles.
   */
  public function testSetBundleSettingsDefaults() {

    $this->generator->setBundleSettings('node', 'page')
      ->removeCustomLinks()
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('0.5');
    $this->assertNoRaw('changefreq');
  }

  /**
   * Test the lastmod parameter in different scenarios.
   */
  public function testLastmod() {

    // Entity links should have 'lastmod'.
    $this->generator->setBundleSettings('node', 'page')
      ->removeCustomLinks()
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertRaw('lastmod');

    // Entity custom links should have 'lastmod'.
    $this->generator->setBundleSettings('node', 'page', ['index' => FALSE])
      ->addCustomLink('/node/' . $this->node->id())
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertRaw('lastmod');

    // Non-entity custom links should not have 'lastmod'.
    $this->generator->removeCustomLinks()
      ->addCustomLink('/')
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertNoRaw('lastmod');
  }

  /**
   * Tests the duplicate setting.
   *
   * @todo On second generation too many links in XML output here?
   */
  public function testRemoveDuplicatesSetting() {
    $this->generator->setBundleSettings('node', 'page', ['index' => TRUE])
      ->addCustomLink('/node/1')
      ->saveSetting('remove_duplicates', TRUE)
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertUniqueText('node/' . $this->node->id());

    $this->generator->saveSetting('remove_duplicates', FALSE)
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertNoUniqueText('node/' . $this->node->id());
  }

  /**
   * Test max links setting and the sitemap index.
   */
  public function testMaxLinksSetting() {
    $this->generator->setBundleSettings('node', 'page')
      ->saveSetting('max_links', 1)
      ->removeCustomLinks()
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('sitemaps/1/sitemap.xml');
    $this->assertText('sitemaps/2/sitemap.xml');

    $this->drupalGet('sitemaps/1/sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('0.5');
    $this->assertNoText('node/' . $this->node2->id());

    $this->drupalGet('sitemaps/2/sitemap.xml');
    $this->assertText('node/' . $this->node2->id());
    $this->assertText('0.5');
    $this->assertNoText('node/' . $this->node->id());
  }

  public function testBatchProcessLimitSetting() {

    // Create some nodes.
    for ($i = 3; $i <= 50; $i++) {
      $this->createNode(['title' => "Node{$i}", 'type' => 'page']);
    }

    // Test batch_process_limit setting.
    $sitemap = $this->generator->setBundleSettings('node', 'page')
      ->generateSitemap('nobatch')
      ->getSitemap();

    $sitemap2 = $this->generator->saveSetting('batch_process_limit', 1)
      ->generateSitemap('nobatch')
      ->getSitemap();

    $sitemap3 = $this->generator->saveSetting('batch_process_limit', 10)
      ->generateSitemap('nobatch')
      ->getSitemap();

    $this->assertEqual($sitemap, $sitemap2);
    $this->assertEqual($sitemap, $sitemap3);

    // Test batch_process_limit setting in combination with max_links setting.
    $sitemap_index = $this->generator->setBundleSettings('node', 'page')
      ->saveSetting('batch_process_limit', 1500)
      ->saveSetting('max_links', 30)
      ->generateSitemap('nobatch')
      ->getSitemap();

    $sitemap_chunk = $this->generator->getSitemap(1);

    $sitemap_index2 = $this->generator->saveSetting('batch_process_limit', 1)
      ->generateSitemap('nobatch')
      ->getSitemap();

    $sitemap_chunk2 = $this->generator->getSitemap(1);

    $sitemap_index3 = $this->generator->saveSetting('batch_process_limit', 10)
      ->generateSitemap('nobatch')
      ->getSitemap();

    $sitemap_chunk3 = $this->generator->getSitemap(1);

    $this->assertIdentical($sitemap_index, $sitemap_index2);
    $this->assertIdentical($sitemap_chunk, $sitemap_chunk2);
    $this->assertIdentical($sitemap_index, $sitemap_index3);
    $this->assertIdentical($sitemap_chunk, $sitemap_chunk3);

  }

  /**
   * Test setting the base URL.
   */
  public function testBaseUrlSetting() {
    $this->generator->setBundleSettings('node', 'page')
      ->saveSetting('base_url', 'http://base_url_test')
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('http://base_url_test');

    // Set base URL in the sitemap index.
    $this->generator->saveSetting('max_links', 1)
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('http://base_url_test/sitemaps/1/sitemap.xml');
  }

  /**
   * @todo testSkipUntranslatedSetting
   */

  /**
   * @todo testSkipNonExistentTranslations
   */

  /**
   * Test cacheability of the response.
   */
  public function testCacheability() {
    $this->generator->setBundleSettings('node', 'page')
      ->generateSitemap('nobatch');

    // Verify the cache was flushed and node is in the sitemap.
    $this->drupalGet('sitemap.xml');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertText('node/' . $this->node->id());

    // Verify the sitemap is taken from cache on second call and node is in the sitemap.
    $this->drupalGet('sitemap.xml');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $this->assertText('node/' . $this->node->id());
  }

  /**
   * Test overriding of bundle settings for a single entity.
   *
   * @todo: Use form testing instead of assertRaw().
   */
  public function testSetEntityInstanceSettings() {
    $this->generator->setBundleSettings('node', 'page')
      ->removeCustomLinks()
      ->setEntityInstanceSettings('node', $this->node->id(), ['priority' => 0.1, 'changefreq' => 'never'])
      ->setEntityInstanceSettings('node', $this->node2->id(), ['index' => FALSE])
      ->generateSitemap('nobatch');

    // Test sitemap result.
    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('0.1');
    $this->assertText('never');
    $this->assertNoText('node/' . $this->node2->id());
    $this->assertNoText('0.5');

    $this->drupalLogin($this->createPrivilegedUser());

    // Test UI changes.
    $this->drupalGet('node/' . $this->node->id() . '/edit');
    $this->assertRaw('<option value="0.1" selected="selected">0.1</option>');
    $this->assertRaw('<option value="never" selected="selected">never</option>');

    // Test database changes.
    $result = \Drupal::database()->select('simple_sitemap_entity_overrides', 'o')
      ->fields('o', ['inclusion_settings'])
      ->condition('o.entity_type', 'node')
      ->condition('o.entity_id', $this->node->id())
      ->execute()
      ->fetchField();
    $this->assertFalse(empty($result));

    $this->generator->setBundleSettings('node', 'page', ['priority' => 0.1, 'changefreq' => 'never'])
      ->generateSitemap('nobatch');

    // Test sitemap result.
    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('0.1');
    $this->assertText('never');
    $this->assertNoText('node/' . $this->node2->id());
    $this->assertNoText('0.5');

    // Test UI changes.
    $this->drupalGet('node/' . $this->node->id() . '/edit');
    $this->assertRaw('<option value="0.1" selected="selected">0.1 (default)</option>');
    $this->assertRaw('<option value="never" selected="selected">never (default)</option>');

    // Test if entity override has been removed from database after its equal to its bundle settings.
    $result = \Drupal::database()->select('simple_sitemap_entity_overrides', 'o')
      ->fields('o', ['inclusion_settings'])
      ->condition('o.entity_type', 'node')
      ->condition('o.entity_id', $this->node->id())
      ->execute()
      ->fetchField();
    $this->assertTrue(empty($result));
  }

  /**
   * Test indexing an atomic entity (here: a user)
   * @todo Not working
   */
/*  public function testAtomicEntityIndexation() {
    $user = $this->createPrivilegedUser();
    $this->generator->setBundleSettings('user')
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertNoText('user/' . $user->id());

    user_role_grant_permissions(0, ['access user profiles']);
    $this->generator->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('user/' . $user->id());
  }*/

  /**
   * @todo Test indexing menu.
   */

  /**
   * @todo Test deleting a bundle.
   */

  /**
   * Test disabling sitemap support for an entity type.
   */
  public function testDisableEntityType() {
    $this->generator->setBundleSettings('node', 'page')
      ->disableEntityType('node');

    $this->drupalLogin($this->createPrivilegedUser());
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertNoText('Simple XML sitemap');

    $this->generator->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertNoText('node/' . $this->node->id());

    $this->assertFalse($this->generator->entityTypeIsEnabled('node'));
  }

  /**
   * Test enabling sitemap support for an entity type.
   *
   * @todo Test admin/config/search/simplesitemap/entities form.
   */
  public function testEnableEntityType() {
    $this->generator->disableEntityType('node')
      ->enableEntityType('node')
      ->setBundleSettings('node', 'page');

    $this->drupalLogin($this->createPrivilegedUser());
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertText('Simple XML sitemap');

    $this->generator->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());

    $this->assertTrue($this->generator->entityTypeIsEnabled('node'));
  }
}
