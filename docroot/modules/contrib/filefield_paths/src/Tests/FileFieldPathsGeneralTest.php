<?php

/**
 * @file
 * Contains \Drupal\filefield_paths\Tests\FileFieldPathsGeneralTest.
 */

namespace Drupal\filefield_paths\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\node\Entity\Node;

/**
 * Test general functionality.
 *
 * @group File (Field) Paths
 */
class FileFieldPathsGeneralTest extends FileFieldPathsTestBase {
  /**
   * Test that the File (Field) Paths UI works as expected.
   */
  public function testAddField() {
    // Create a File field.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_settings = ['file_directory' => "fields/{$field_name}"];
    $this->createFileField($field_name, 'node', $this->contentType, [], $field_settings);

    // Ensure File (Field) Paths settings are present.
    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}");
    $this->assertText('Enable File (Field) Paths?', $this->t('File (Field) Path settings are present.'));

    // Ensure that 'Enable File (Field) Paths?' is a direct sibling of
    // 'File (Field) Path settings'.
    $element = $this->xpath('//div[contains(@class, :class)]/following-sibling::*[1]/@id', [':class' => 'form-item-third-party-settings-filefield-paths-enabled']);
    $this->assert(isset($element[0]) && 'edit-third-party-settings-filefield-paths--2' == (string) $element[0], t('Enable checkbox is next to settings fieldset.'));

    // Ensure that the File path used the File directory as it's default value.
    $this->assertFieldByName('third_party_settings[filefield_paths][file_path][value]', "fields/{$field_name}");
  }

  /**
   * Test File (Field) Paths works as normal when no file uploaded.
   *
   * This test is simply to prove that there are no exceptions/errors when
   * submitting a form with no File (Field) Paths affected files attached.
   */
  public function testNoFile() {
    // Create a File field.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:nid]';
    $third_party_settings['filefield_paths']['file_name']['value'] = '[node:nid].[file:ffp-extension-original]';
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node without a file attached.
    $this->drupalPostForm("node/add/{$this->contentType}", [
      'title[0][value]' => $this->randomMachineName(8),
    ], $this->t('Save and publish'));
  }

  /**
   * Test a basic file upload with File (Field) Paths.
   */
  public function testUploadFile() {
    $file_system = \Drupal::service('file_system');

    // Create a File field with 'node/[node:nid]' as the File path and
    // '[node:nid].[file:ffp-extension-original]' as the File name.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:nid]';
    $third_party_settings['filefield_paths']['file_name']['value'] = '[node:nid].[file:ffp-extension-original]';
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');
    $this->drupalGet("node/add/{$this->contentType}");
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit["files[{$field_name}_0]"] = $file_system->realpath($test_file->getFileUri());
    $this->drupalPostForm(NULL, $edit, t('Upload'));

    // Ensure that the file was put into the Temporary file location.
    $config = \Drupal::config('filefield_paths.settings');
    $this->assertRaw(file_create_url("{$config->get('temp_location')}/{$test_file->getFilename()}"), $this->t('File has been uploaded to the temporary file location.'));

    // Save the node.
    $this->drupalPostForm(NULL, [], t('Save and publish'));

    // Get created Node ID.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    $nid = $matches[1];

    // Ensure that the File path has been processed correctly.
    $this->assertRaw("{$this->publicFilesDirectory}/node/{$nid}/{$nid}.txt", $this->t('The File path has been processed correctly.'));
  }

  /**
   * Tests a multivalue file upload with File (Field) Paths.
   */
  public function testUploadFileMultivalue() {
    $file_system = \Drupal::service('file_system');

    // Create a multivalue File field with 'node/[node:nid]' as the File path
    // and '[file:fid].txt' as the File name.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $storage_settings['cardinality'] = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:nid]';
    $third_party_settings['filefield_paths']['file_name']['value'] = '[file:fid].txt';
    $this->createFileField($field_name, 'node', $this->contentType, $storage_settings, [], $third_party_settings);

    // Create a node with three (3) test files.
    $text_files = $this->drupalGetTestFiles('text');
    $this->drupalGet("node/add/{$this->contentType}");
    $this->drupalPostForm(NULL, ["files[{$field_name}_0][]" => $file_system->realpath($text_files[0]->uri)], t('Upload'));
    $this->drupalPostForm(NULL, ["files[{$field_name}_1][]" => $file_system->realpath($text_files[1]->uri)], t('Upload'));
    $edit = [
      'title[0][value]'          => $this->randomMachineName(),
      "files[{$field_name}_2][]" => $file_system->realpath($text_files[1]->uri),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));

    // Get created Node ID.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    $nid = $matches[1];

    // Ensure that the File path has been processed correctly.
    $this->assertRaw("{$this->publicFilesDirectory}/node/{$nid}/1.txt", $this->t('The first File path has been processed correctly.'));
    $this->assertRaw("{$this->publicFilesDirectory}/node/{$nid}/2.txt", $this->t('The second File path has been processed correctly.'));
    $this->assertRaw("{$this->publicFilesDirectory}/node/{$nid}/3.txt", $this->t('The third File path has been processed correctly.'));
  }

  /**
   * Test File (Field) Paths with a very long path.
   */
  public function testLongPath() {
    // Create a File field with 'node/[random:hash:sha256]' as the File path.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[random:hash:sha512]/[random:hash:sha512]';
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');
    $nid = $this->uploadNodeFile($test_file, $field_name, $this->contentType);

    // Ensure file path is no more than 255 characters.
    $node = Node::load($nid);
    $this->assert(Unicode::strlen($node->{$field_name}[0]['uri']) <= 255, $this->t('File path is no more than 255 characters'));
  }

  /**
   * Test File (Field) Paths on a programmatically added file.
   */
  public function testProgrammaticAttach() {
    // Create a File field with 'node/[node:nid]' as the File path and
    // '[node:nid].[file:ffp-extension-original]' as the File name.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:nid]';
    $third_party_settings['filefield_paths']['file_name']['value'] = '[node:nid].[file:ffp-extension-original]';
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node without an attached file.
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->drupalCreateNode(['type' => $this->contentType]);

    // Create a file object.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');
    $test_file->setPermanent();
    $test_file->save();

    // Attach the file to the node.
    $node->{$field_name}->setValue([
      'target_id' => $test_file->id(),
    ]);
    $node->save();

    // Ensure that the File path has been processed correctly.
    $node = Node::load($node->id());
    $this->assertEqual("public://node/{$node->id()}/{$node->id()}.txt", $node->{$field_name}[0]->entity->getFileUri(), $this->t('The File path has been processed correctly.'));
  }

  /**
   * Test File (Field) Paths slashes cleanup functionality.
   */
  public function testSlashes() {
    $file_system = \Drupal::service('file_system');

    // Create a File field with 'node/[node:title]' as the File path and
    // '[node:title].[file:ffp-extension-original]' as the File name.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:title]';
    $third_party_settings['filefield_paths']['file_name']['value'] = '[node:title].[file:ffp-extension-original]';
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');

    $title = "{$this->randomMachineName()}/{$this->randomMachineName()}";
    $edit['title[0][value]'] = $title;
    $edit["body[0][value]"] = '';
    $edit["files[{$field_name}_0]"] = $file_system->realpath($test_file->getFileUri());
    $this->drupalPostForm("node/add/{$this->contentType}", $edit, $this->t('Save and publish'));

    // Get created Node ID.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    $nid = $matches[1];

    // Ensure slashes are present in file path and name.
    $node = Node::load($nid);
    $this->assertEqual("public://node/{$title}/{$title}.txt", $node->{$field_name}[0]->entity->getFileUri());

    // Remove slashes.
    $edit = [
      'third_party_settings[filefield_paths][file_path][options][slashes]' => TRUE,
      'third_party_settings[filefield_paths][file_name][options][slashes]' => TRUE,
      'third_party_settings[filefield_paths][retroactive_update]'          => TRUE,
    ];
    $this->drupalPostForm("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}", $edit, $this->t('Save settings'));
    \Drupal::entityTypeManager()
      ->getStorage('file')
      ->resetCache([$node->{$field_name}->target_id]);
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$nid]);

    // Ensure slashes are not present in file path and name.
    $node = Node::load($nid);
    $title = str_replace('/', '', $title);
    $this->assertEqual("public://node/{$title}/{$title}.txt", $node->{$field_name}[0]->entity->getFileUri());
  }

  /**
   * Test a file usage of a basic file upload with File (Field) Paths.
   */
  public function testFileUsage() {
    /** @var \Drupal\node\NodeStorage $node_storage */
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $file_usage = \Drupal::service('file.usage');

    // Create a File field with 'node/[node:nid]' as the File path.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:nid]';
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');
    $nid = $this->uploadNodeFile($test_file, $field_name, $this->contentType);

    // Get file usage for uploaded file.
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $file = $node->{$field_name}->entity;
    $usage = $file_usage->listUsage($file);

    // Ensure file usage count for new node is correct.
    $this->assert(isset($usage['file']['node'][$nid]) && $usage['file']['node'][$nid] == 1, t('File usage count for new node is correct.'));

    // Update node.
    $this->drupalPostForm("node/{$nid}/edit", [], t('Save and keep published'));
    $usage = $file_usage->listUsage($file);

    // Ensure file usage count for updated node is correct.
    $this->assert(isset($usage['file']['node'][$nid]) && $usage['file']['node'][$nid] == 1, t('File usage count for updated node is correct.'));

    // Update node with revision.
    $this->drupalPostForm("node/{$nid}/edit", ['revision' => TRUE], t('Save and keep published'));
    $usage = $file_usage->listUsage($file);

    // Ensure file usage count for updated node with revision is correct.
    $this->assert(isset($usage['file']['node'][$nid]) && $usage['file']['node'][$nid] == 2, t('File usage count for updated node with revision is correct.'));
  }

  /**
   * Test File (Field) Paths works with read-only stream wrappers.
   */
  public function testReadOnly() {
    // Create a File field.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_settings = ['uri_scheme' => 'ffp-dummy-readonly'];
    $instance_settings = ['file_directory' => "fields/{$field_name}"];
    $this->createFileField($field_name, 'node', $this->contentType, $field_settings, $instance_settings);

    // Get a test file.
    /** @var \Drupal\file\Entity\File $file */
    $file = $this->getTestFile('image');

    // Prepare the file for the test 'ffp-dummy-readonly://' read-only stream
    // wrapper.
    $file->setFileUri(str_replace('public', 'ffp-dummy-readonly', $file->getFileUri()));
    $file->save();

    // Attach the file to a node.
    $node['type'] = $this->contentType;
    $node[$field_name][0]['target_id'] = $file->id();

    $node = $this->drupalCreateNode($node);

    // Ensure file has been attached to a node.
    $this->assert(isset($node->{$field_name}[0]) && !empty($node->{$field_name}[0]), $this->t('Read-only file is correctly attached to a node.'));

    $edit['third_party_settings[filefield_paths][retroactive_update]'] = TRUE;
    $edit['third_party_settings[filefield_paths][file_path][value]'] = 'node/[node:nid]';
    $this->drupalPostForm("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}", $edit, $this->t('Save settings'));

    // Ensure file is still in original location.
    $this->drupalGet("node/{$node->id()}");
    $this->assertRaw("{$this->publicFilesDirectory}/{$file->getFilename()}", $this->t('Read-only file not affected by Retroactive updates.'));
  }

}
