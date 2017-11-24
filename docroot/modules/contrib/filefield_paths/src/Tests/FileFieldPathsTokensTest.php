<?php

/**
 * @file
 * Contains \Drupal\filefield_paths\Tests\FileFieldPathsUpdateTest.
 */

namespace Drupal\filefield_paths\Tests;

/**
 * Test token functionality.
 *
 * @group File (Field) Paths
 */
class FileFieldPathsTokensTest extends FileFieldPathsTestBase {
  /**
   * Assert that the provided token matches the provided value.
   *
   * @param string $token
   *   The token to test.
   * @param string $value
   *   The value to check against the token.
   * @param array $data
   *   The data to process the token with.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  public function assertToken($token, $value, $data) {
    $result = \Drupal::token()->replace($token, $data);

    return $this->assertEqual($result, $value, $this->t('Token @token equals @value', [
      '@token' => $token,
      '@value' => $value,
    ]));
  }

  /**
   * Test token values with a text file.
   */
  public function testTokensBasic() {
    // Prepare a test text file.
    /** @var \Drupal\file\Entity\File $text_file */
    $text_file = $this->getTestFile('text');
    $text_file->save();

    // Ensure tokens are processed correctly.
    $data = ['file' => $text_file];
    $this->assertToken('[file:ffp-name-only]', 'text-0', $data);
    $this->assertToken('[file:ffp-name-only-original]', 'text-0', $data);
    $this->assertToken('[file:ffp-extension-original]', 'txt', $data);
  }

  /**
   * Test token values with a moved text file.
   */
  public function testTokensMoved() {
    // Prepare a test text file.
    /** @var \Drupal\file\Entity\File $text_file */
    $text_file = $this->getTestFile('text');
    $text_file->save();

    // Move the text file.
    $moved_file = file_move($text_file, 'public://moved.diff');

    // Ensure tokens are processed correctly.
    $data = ['file' => $moved_file];
    $this->assertToken('[file:ffp-name-only]', 'moved', $data);
    $this->assertToken('[file:ffp-name-only-original]', 'text-0', $data);
    $this->assertToken('[file:ffp-extension-original]', 'txt', $data);
  }

  /**
   * Test token values with a multi-extension text file.
   */
  public function testTokensMultiExtension() {
    // Prepare a test text file.
    /** @var \Drupal\file\Entity\File $text_file */
    $text_file = $this->getTestFile('text');
    file_unmanaged_copy($text_file->getFileUri(), 'public://text.multiext.txt');
    $files = file_scan_directory('public://', '/text\.multiext\.txt/');
    $multiext_file = current($files);
    /** @var \Drupal\file\Entity\File $multiext_file */
    $multiext_file = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->create((array) $multiext_file);
    $multiext_file->save();

    // Ensure tokens are processed correctly.
    $data = ['file' => $multiext_file];
    $this->assertToken('[file:ffp-name-only]', 'text.multiext', $data);
    $this->assertToken('[file:ffp-name-only-original]', 'text.multiext', $data);
    $this->assertToken('[file:ffp-extension-original]', 'txt', $data);
  }

  /**
   * Test token value with a UTF file.
   *
   * @see https://www.drupal.org/node/1292436
   */
  public function testTokensUtf() {
    // Prepare a test text file.
    /** @var \Drupal\file\Entity\File $text_file */
    $text_file = $this->getTestFile('text');
    file_unmanaged_copy($text_file->getFileUri(), 'public://тест.txt');
    $files = file_scan_directory('public://', '/тест\.txt/');
    $utf_file = current($files);
    /** @var \Drupal\file\Entity\File $utf_file */
    $utf_file = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->create((array) $utf_file);
    $utf_file->save();

    // Ensure tokens are processed correctly.
    $data = ['file' => $utf_file];
    $this->assertToken('[file:ffp-name-only]', 'тест', $data);
  }

}
