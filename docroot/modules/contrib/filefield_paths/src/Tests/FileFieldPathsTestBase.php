<?php

/**
 * @file
 * Contains \Drupal\filefield_paths\Tests\FileFieldPathsTestBase.
 */

namespace Drupal\filefield_paths\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Tests\FileFieldTestBase;

/**
 * Base class for File (Field) Paths tests.
 */
abstract class FileFieldPathsTestBase extends FileFieldTestBase {
  use StringTranslationTrait;

  public $contentType = NULL;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'filefield_paths_test',
    'file_test',
    'image',
    'token',
  ];

  /**
   * Sets up a Drupal site for running functional and integration tests.
   */
  protected function setUp() {
    parent::setUp();

    // Create a content type.
    $content_type = $this->drupalCreateContentType();
    $this->contentType = $content_type->get('name');
  }

  /**
   * Creates a new file field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   */
  public function createFileField($name, $entity_type, $bundle, $storage_settings = [], $field_settings = [], $third_party_settings = [], $widget_settings = []) {
    $field_storage = entity_create('field_storage_config', [
      'entity_type' => $entity_type,
      'field_name'  => $name,
      'type'        => 'file',
      'settings'    => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ]);
    $field_storage->save();

    $field = [
      'field_name'           => $name,
      'label'                => $name,
      'entity_type'          => $entity_type,
      'bundle'               => $bundle,
      'required'             => !empty($field_settings['required']),
      'settings'             => $field_settings,
      'third_party_settings' => $third_party_settings,
    ];
    entity_create('field_config', $field)->save();

    entity_get_form_display($entity_type, $bundle, 'default')
      ->setComponent($name, [
        'type'     => 'file_generic',
        'settings' => $widget_settings,
      ])
      ->save();
    // Assign display settings.
    entity_get_display($entity_type, $bundle, 'default')
      ->setComponent($name, [
        'label' => 'hidden',
        'type'  => 'file_default',
      ])
      ->save();

    $this->drupalPostForm("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$name}", [], $this->t('Save settings'));

    // Clear field cache in order to avoid stale cache values.
    \Drupal::entityManager()->clearCachedFieldDefinitions();
  }

  /**
   * Create a new image field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $type_name
   *   The node type that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A image field configuration entity.
   */
  public function createImageField($name, $type_name, $storage_settings = [], $field_settings = [], $third_party_settings = [], $widget_settings = []) {
    entity_create('field_storage_config', [
      'field_name'  => $name,
      'entity_type' => 'node',
      'type'        => 'image',
      'settings'    => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ])->save();

    $field_config = entity_create('field_config', [
      'field_name'           => $name,
      'label'                => $name,
      'entity_type'          => 'node',
      'bundle'               => $type_name,
      'required'             => !empty($field_settings['required']),
      'settings'             => $field_settings,
      'third_party_settings' => $third_party_settings,
    ]);
    $field_config->save();

    entity_get_form_display('node', $type_name, 'default')
      ->setComponent($name, [
        'type'     => 'image_image',
        'settings' => $widget_settings,
      ])
      ->save();

    entity_get_display('node', $type_name, 'default')
      ->setComponent($name)
      ->save();

    $this->drupalPostForm("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$name}", [], $this->t('Save settings'));

    return $field_config;
  }

}
