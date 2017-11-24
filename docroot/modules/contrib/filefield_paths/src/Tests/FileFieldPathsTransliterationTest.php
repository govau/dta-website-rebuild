<?php

/**
 * @file
 * Contains \Drupal\filefield_paths\Tests\FileFieldPathsTransliterationTest.
 */

namespace Drupal\filefield_paths\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\node\Entity\Node;

/**
 * Test transliteration functionality.
 *
 * @group File (Field) Paths
 */
class FileFieldPathsTransliterationTest extends FileFieldPathsTestBase {
  /**
   * Test File (Field) Paths Transliteration UI.
   */
  public function testUi() {
    // Create a File field.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $this->contentType);

    // Ensure File (Field) Paths Transliteration settings are present and
    // available.
    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}");
    foreach (['path', 'name'] as $field) {
      $this->assertField("third_party_settings[filefield_paths][file_{$field}][options][transliterate]", $this->t('Transliteration checkbox is present in File @field settings.', ['@field' => Unicode::ucfirst($field)]));

      $element = $this->xpath('//input[@name=:name]/@disabled', [':name' => "third_party_settings[filefield_paths][file_{$field}][options][transliterate]"]);
      $this->assert(empty($element), $this->t('Transliteration checkbox is not disabled in File @field settings.', ['@field' => Unicode::ucfirst($field)]));
    }
  }

  /**
   * Test Transliteration cleanup in File (Field) Paths.
   */
  public function testTransliteration() {
    // Create a File field.
    $field_name = Unicode::strtolower($this->randomMachineName());

    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:title]';
    $third_party_settings['filefield_paths']['file_path']['options']['transliterate'] = TRUE;
    $third_party_settings['filefield_paths']['file_name']['value'] = '[node:title].[file:ffp-extension-original]';
    $third_party_settings['filefield_paths']['file_name']['options']['transliterate'] = TRUE;

    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');
    $edit['title[0][value]'] = 'тест';

    $edit['files[' . $field_name . '_0]'] = \Drupal::service('file_system')
      ->realpath($test_file->getFileUri());
    $this->drupalPostForm("node/add/{$this->contentType}", $edit, $this->t('Save and publish'));

    // Get created Node ID.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    $nid = $matches[1];

    // Ensure that file path/name have been processed correctly by
    // Transliteration.
    $node = Node::load($nid);
    $this->assertEqual($node->{$field_name}[0]->entity->getFileUri(), "public://node/test/test.txt", $this->t('File path/name has been processed correctly by Transliteration'));
  }

}
