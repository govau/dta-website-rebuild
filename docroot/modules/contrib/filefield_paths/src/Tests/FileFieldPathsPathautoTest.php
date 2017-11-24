<?php

/**
 * @file
 * Contains \Drupal\filefield_paths\Tests\FileFieldPathsPathautoTest.
 */

namespace Drupal\filefield_paths\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\node\Entity\Node;

/**
 * Test pathauto integration.
 *
 * @group File (Field) Paths
 */
class FileFieldPathsPathautoTest extends FileFieldPathsTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'filefield_paths_test',
    'file_test',
    'image',
    'pathauto',
    'token',
  ];

  /**
   * Test File (Field) Paths Pathauto UI.
   */
  public function testUi() {
    // Create a File field.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $this->contentType);

    // Ensure File (Field) Paths Pathauto settings are present and available.
    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}");
    foreach (['path', 'name'] as $field) {
      $this->assertField("third_party_settings[filefield_paths][file_{$field}][options][pathauto]", t('Pathauto checkbox is present in File @field settings.', ['@field' => Unicode::ucfirst($field)]));

      $element = $this->xpath('//input[@name=:name]/@disabled', [':name' => "third_party_settings[filefield_paths][file_{$field}][options][pathauto]"]);
      $this->assert(empty($element), t('Pathauto checkbox is not disabled in File @field settings.', ['@field' => Unicode::ucfirst($field)]));
    }
  }

  /**
   * Test Pathauto cleanup in File (Field) Paths.
   */
  public function testPathauto() {
    // Create a File field.
    $field_name = Unicode::strtolower($this->randomMachineName());

    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:title]';
    $third_party_settings['filefield_paths']['file_path']['options']['pathauto'] = TRUE;
    $third_party_settings['filefield_paths']['file_name']['value'] = '[node:title].[file:ffp-extension-original]';
    $third_party_settings['filefield_paths']['file_name']['options']['pathauto'] = TRUE;

    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');
    $edit['title[0][value]'] = $this->randomString() . ' ' . $this->randomString();

    $edit['files[' . $field_name . '_0]'] = \Drupal::service('file_system')
      ->realpath($test_file->getFileUri());
    $this->drupalPostForm("node/add/{$this->contentType}", $edit, $this->t('Save and publish'));

    // Ensure that file path/name have been processed correctly by Pathauto.
    /** @var \Drupal\node\Entity\Node $node */
    $node = Node::load(1);

    $parts = explode('/', $node->getTitle());
    foreach ($parts as &$part) {
      $part = \Drupal::service('pathauto.alias_cleaner')->cleanString($part);
    }
    $title = implode('/', $parts);

    $this->assertEqual($node->{$field_name}[0]->entity->getFileUri(), "public://node/{$title}/{$title}.txt", $this->t('File path/name has been processed correctly by Pathauto'));
  }

}
