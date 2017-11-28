<?php

/**
 * @file
 * Contains \Drupal\filefield_paths\Tests\FileFieldPathsRedirectTest.
 */

namespace Drupal\filefield_paths\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\node\Entity\Node;

/**
 * Test redirect module integration.
 *
 * @group File (Field) Paths
 */
class FileFieldPathsRedirectTest extends FileFieldPathsTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'filefield_paths_test',
    'file_test',
    'image',
    'redirect',
    'token',
  ];

  /**
   * Test File (Field) Paths Redirect UI.
   */
  public function testUi() {
    // Create a File field.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $this->contentType);

    // Ensure File (Field) Paths Pathauto settings are present and available.
    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}");

    $this->assertField('third_party_settings[filefield_paths][redirect]', $this->t('Redirect checkbox is present in File (Field) Path settings.'));

    $element = $this->xpath('//input[@name=:name]/@disabled', [':name' => 'third_party_settings[filefield_paths][redirect]']);
    $this->assert(empty($element), $this->t('Redirect checkbox is not disabled.'));
  }

  /**
   * Test File (Field) Paths Redirect functionality.
   */
  public function testRedirect() {
    global $base_path;

    // Create a File field with a random File path.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = $this->randomMachineName();
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');
    $nid = $this->uploadNodeFile($test_file, $field_name, $this->contentType);

    // Get processed source file uri.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$nid]);
    $node = Node::load($nid);
    $source = $node->{$field_name}[0]->entity->getFileUri();

    // Update file path and create redirect.
    $edit = [
      'third_party_settings[filefield_paths][file_path][value]'   => $this->randomMachineName(),
      'third_party_settings[filefield_paths][redirect]'           => TRUE,
      'third_party_settings[filefield_paths][retroactive_update]' => TRUE,
    ];
    $this->drupalPostForm("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}", $edit, $this->t('Save settings'));

    // Get processed destination file uri.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$nid]);
    $node = Node::load($nid);
    $destination = $node->{$field_name}[0]->entity->getFileUri();

    // Ensure that the source uri redirects to the destination uri.
    $parsed_source = parse_url(file_create_url($source), PHP_URL_PATH);
    $redirect_source = Unicode::substr(urldecode($parsed_source), Unicode::strlen($base_path));

    $parsed_destination = parse_url(file_create_url($destination), PHP_URL_PATH);
    $redirect_destination = Unicode::substr(urldecode($parsed_destination), Unicode::strlen($base_path));

    $redirects = redirect_repository()->findBySourcePath($redirect_source);
    $this->assert(!empty($redirects) && reset($redirects)->getSource()['path'] == $redirect_destination, $this->t('Redirect created for relocated file.'));
  }

}
