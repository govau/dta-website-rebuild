<?php

namespace Drupal\Tests\migrate_plus\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\migrate_plus\Entity\MigrationGroup;

/**
 * Test migration groups.
 *
 * @group migrate_plus
 */
class MigrationGroupTest extends KernelTestBase {

  public static $modules = ['migrate', 'migrate_plus'];

  /**
   * Test that group configuration is properly merged into specific migrations.
   */
  public function testConfigurationMerge() {
    $group_id = 'test_group';

    /** @var \Drupal\migrate_plus\Entity\MigrationGroupInterface $migration_group */
    $group_configuration = [
      'id' => $group_id,
      'shared_configuration' => [
        // In migration, so will be overridden.
        'migration_tags' => ['Drupal 6'],
        'source' => [
          'constants' => [
            // Not in migration, so will be added.
            'type' => 'image',
            // In migration, so will be overridden.
            'cardinality' => '1',
          ],
        ],
        // Not in migration, so will be added.
        'destination' => ['plugin' => 'field_storage_config'],
      ],
    ];
    $this->container->get('entity_type.manager')->getStorage('migration_group')
      ->create($group_configuration)->save();

    /** @var \Drupal\migrate_plus\Entity\MigrationInterface $migration */
    $migration = $this->container->get('entity_type.manager')
      ->getStorage('migration')->create([
        'id' => 'specific_migration',
        'load' => [],
        'migration_group' => $group_id,
        'label' => 'Unaffected by the group',
          // Overrides group.
        'migration_tags' => ['Drupal 7'],
        'destination' => [],
        'source' => [],
        'process' => [],
        'migration_dependencies' => [],
      ]);
    $migration->set('source', [
      // Not in group, persists.
      'plugin' => 'empty',
      'constants' => [
        // Not in group, persists.
        'entity_type' => 'user',
        // Overrides group.
        'cardinality' => '3',
      ],
    ]);
    $migration->save();

    $expected_config = [
      'migration_group' => $group_id,
      'label' => 'Unaffected by the group',
      'migration_tags' => ['Drupal 7'],
      'source' => [
        'plugin' => 'empty',
        'constants' => [
          'entity_type' => 'user',
          'type' => 'image',
          'cardinality' => '3',
        ],
      ],
      'destination' => ['plugin' => 'field_storage_config'],
    ];
    /** @var \Drupal\migrate\Plugin\MigrationInterface $loaded_migration */
    $loaded_migration = $this->container->get('plugin.manager.migration')
      ->createInstance('specific_migration');
    foreach ($expected_config as $key => $expected_value) {
      $actual_value = $loaded_migration->get($key);
      $this->assertEquals($expected_value, $actual_value);
    }
  }

  /**
   * Test that deleting a group deletes its migrations.
   */
  public function testDelete() {
    /** @var \Drupal\migrate_plus\Entity\MigrationGroupInterface $migration_group */
    $group_configuration = [
      'id' => 'test_group',
    ];
    $migration_group = $this->container->get('entity_type.manager')
      ->getStorage('migration_group')->create($group_configuration);
    $migration_group->save();

    /** @var \Drupal\migrate_plus\Entity\MigrationInterface $migration */
    $migration = $this->container->get('entity_type.manager')
      ->getStorage('migration')->create([
        'id' => 'specific_migration',
        'migration_group' => 'test_group',
        'migration_tags' => [],
        'load' => [],
        'destination' => [],
        'source' => [],
        'migration_dependencies' => [],
      ]);
    $migration->save();

    /** @var \Drupal\migrate_plus\Entity\MigrationGroupInterface $loaded_migration_group */
    $loaded_migration_group = MigrationGroup::load('test_group');
    $loaded_migration_group->delete();

    /** @var \Drupal\migrate_plus\Entity\MigrationInterface $loaded_migration */
    $loaded_migration = Migration::load('specific_migration');
    $this->assertNull($loaded_migration);
  }

}
