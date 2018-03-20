<?php

namespace Drupal\migrate_tools\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate_tools\Drush9LogMigrateMessage;
use Drupal\migrate_tools\MigrateExecutable;
use Drush\Commands\DrushCommands;

/**
 * Migrate Tools drush commands.
 */
class MigrateToolsCommands extends DrushCommands {

  /**
   * Migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Key-value store service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * Migrate message logger.
   *
   * @var \Drupal\migrate_tools\Drush9LogMigrateMessage
   */
  protected $migrateMessage;

  /**
   * MigrateToolsCommands constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $migrationPluginManager
   *   Migration Plugin Manager service.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   Date formatter service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValue
   *   Key-value store service.
   */
  public function __construct(
    MigrationPluginManager $migrationPluginManager,
    DateFormatter $dateFormatter,
    EntityTypeManagerInterface $entityTypeManager,
    KeyValueFactoryInterface $keyValue
  ) {
    parent::__construct();
    $this->migrationPluginManager = $migrationPluginManager;
    $this->dateFormatter = $dateFormatter;
    $this->entityTypeManager = $entityTypeManager;
    $this->keyValue = $keyValue;
  }

  /**
   * List all migrations with current status.
   *
   * @param string $migration_names
   *   Restrict to a comma-separated list of migrations (Optional).
   * @param array $options
   *   Additional options for the command.
   *
   * @command migrate:status
   *
   * @option group A comma-separated list of migration groups to list
   * @option tag Name of the migration tag to list
   * @option names-only Only return names, not all the details (faster)
   *
   * @usage migrate:status
   *   Retrieve status for all migrations
   * @usage migrate:status --group=beer
   *   Retrieve status for all migrations in a given group
   * @usage migrate:status --tag=user
   *   Retrieve status for all migrations with a given tag
   * @usage migrate:status --group=beer --tag=user
   *   Retrieve status for all migrations in the beer group
   *   and with the user tag.
   * @usage migrate:status beer_term,beer_node
   *   Retrieve status for specific migrations
   *
   * @validate-module-enabled migrate_tools
   *
   * @aliases ms, migrate-status
   *
   * @field-labels
   *   group: Group
   *   id: Migration ID
   *   status: Status
   *   total: Total
   *   imported: Imported
   *   unprocessed: Unprocessed
   *   last_imported: Last Imported
   * @default-fields group,id,status,total,imported,unprocessed,last_imported
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Migrations status formatted as table.
   */
  public function status(
    $migration_names = '',
    array $options = [
      'group' => NULL,
      'tag' => NULL,
      'names-only' => NULL,
    ]
  ) {
    $names_only = $options['names-only'];

    $migrations = $this->migrationsList($migration_names, $options);

    $table = [];
    // Take it one group at a time, listing the migrations within each group.
    foreach ($migrations as $group_id => $migration_list) {
      /** @var \Drupal\migrate_plus\Entity\MigrationGroup $group */
      $group = $this->entityTypeManager->getStorage('migration_group')->load($group_id);
      $group_name = !empty($group) ? "{$group->label()} ({$group->id()})" : $group_id;

      foreach ($migration_list as $migration_id => $migration) {
        if ($names_only) {
          $table[] = [
            'group' => dt('Group: @name', ['@name' => $group_name]),
            'id' => $migration_id,
          ];
        }
        else {
          try {
            $map = $migration->getIdMap();
            $imported = $map->importedCount();
            $source_plugin = $migration->getSourcePlugin();
          }
          catch (\Exception $e) {
            $this->logger()->error(
              dt(
                'Failure retrieving information on @migration: @message',
                ['@migration' => $migration_id, '@message' => $e->getMessage()]
              )
            );
            continue;
          }

          try {
            $source_rows = $source_plugin->count();
            // -1 indicates uncountable sources.
            if ($source_rows == -1) {
              $source_rows = dt('N/A');
              $unprocessed = dt('N/A');
            }
            else {
              $unprocessed = $source_rows - $map->processedCount();
            }
          }
          catch (\Exception $e) {
            $this->logger()->error(
              dt(
                'Could not retrieve source count from @migration: @message',
                ['@migration' => $migration_id, '@message' => $e->getMessage()]
              )
            );
            $source_rows = dt('N/A');
            $unprocessed = dt('N/A');
          }

          $status = $migration->getStatusLabel();
          $migrate_last_imported_store = $this->keyValue->get(
            'migrate_last_imported'
          );
          $last_imported = $migrate_last_imported_store->get(
            $migration->id(),
            FALSE
          );
          if ($last_imported) {
            $last_imported = $this->dateFormatter->format(
              $last_imported / 1000,
              'custom',
              'Y-m-d H:i:s'
            );
          }
          else {
            $last_imported = '';
          }
          $table[] = [
            'group' => $group_name,
            'id' => $migration_id,
            'status' => $status,
            'total' => $source_rows,
            'imported' => $imported,
            'unprocessed' => $unprocessed,
            'last_imported' => $last_imported,
          ];
        }
      }

      // Add empty row to separate groups, for readability.
      end($migrations);
      if ($group_id !== key($migrations)) {
        $table[] = [];
      }
    }

    return new RowsOfFields($table);
  }

  /**
   * Perform one or more migration processes.
   *
   * @param string $migration_names
   *   ID of migration(s) to import. Delimit multiple using commas.
   * @param array $options
   *   Additional options for the command.
   *
   * @command migrate:import
   *
   * @option all Process all migrations.
   * @option group A comma-separated list of migration groups to import
   * @option tag Name of the migration tag to import
   * @option limit Limit on the number of items to process in each migration
   * @option feedback Frequency of progress messages, in items processed
   * @option idlist Comma-separated list of IDs to import
   * @option update  In addition to processing unprocessed items from the
   *   source, update previously-imported items with the current data
   * @option force Force an operation to run, even if all dependencies are not
   *   satisfied
   * @option execute-dependencies Execute all dependent migrations first.
   *
   * @usage migrate:import --all
   *   Perform all migrations
   * @usage migrate:import --group=beer
   *   Import all migrations in the beer group
   * @usage migrate:import --tag=user
   *   Import all migrations with the user tag
   * @usage migrate:import --group=beer --tag=user
   *   Import all migrations in the beer group and with the user tag
   * @usage migrate:import beer_term,beer_node
   *   Import new terms and nodes
   * @usage migrate:import beer_user --limit=2
   *   Import no more than 2 users
   * @usage migrate:import beer_user --idlist=5
   *   Import the user record with source ID 5
   *
   * @validate-module-enabled migrate_tools
   *
   * @aliases mim, migrate-import
   *
   * @throws \Exception
   *   If there are not enough parameters to the command.
   */
  public function import(
    $migration_names = '',
    array $options = [
      'all' => NULL,
      'group' => NULL,
      'tag' => NULL,
      'limit' => NULL,
      'feedback' => NULL,
      'idlist' => NULL,
      'update' => NULL,
      'force' => NULL,
      'execute-dependencies' => NULL,
    ]
  ) {
    $group_names = $options['group'];
    $tag_names = $options['tag'];
    $all = $options['all'];
    $additional_options = [];
    if (!$all && !$group_names && !$migration_names && !$tag_names) {
      throw new \Exception(dt('You must specify --all, --group, --tag or one or more migration names separated by commas'));
    }

    foreach (['limit', 'feedback', 'idlist', 'update', 'force'] as $option) {
      if ($options[$option]) {
        $additional_options[$option] = $options[$option];
      }
    }

    $migrations = $this->migrationsList($migration_names, $options);
    if (empty($migrations)) {
      $this->logger->error(dt('No migrations found.'));
    }

    // Take it one group at a time, importing the migrations within each group.
    foreach ($migrations as $group_id => $migration_list) {
      array_walk(
        $migration_list,
        [$this, 'executeMigration'],
        $additional_options
      );
    }
  }

  /**
   * Rollback one or more migrations.
   *
   * @param string $migration_names
   *   Name of migration(s) to rollback. Delimit multiple using commas.
   * @param array $options
   *   Additional options for the command.
   *
   * @command migrate:rollback
   *
   * @option all Process all migrations.
   * @option group A comma-separated list of migration groups to rollback
   * @option tag ID of the migration tag to rollback
   * @option feedback Frequency of progress messages, in items processed
   *
   * @usage migrate:rollback --all
   *   Perform all migrations
   * @usage migrate:rollback --group=beer
   *   Rollback all migrations in the beer group
   * @usage migrate:rollback --tag=user
   *   Rollback all migrations with the user tag
   * @usage migrate:rollback --group=beer --tag=user
   *   Rollback all migrations in the beer group and with the user tag
   * @usage migrate:rollback beer_term,beer_node
   *   Rollback imported terms and nodes
   * @validate-module-enabled migrate_tools
   *
   * @aliases mr, migrate-rollback
   *
   * @throws \Exception
   *   If there are not enough parameters to the command.
   */
  public function rollback(
    $migration_names = '',
    array $options = [
      'all' => NULL,
      'group' => NULL,
      'tag' => NULL,
      'feedback' => NULL,
    ]
  ) {
    $group_names = $options['group'];
    $tag_names = $options['tag'];
    $all = $options['all'];
    $additional_options = [];
    if (!$all && !$group_names && !$migration_names && !$tag_names) {
      throw new \Exception(dt('You must specify --all, --group, --tag, or one or more migration names separated by commas'));
    }

    if ($options['feedback']) {
      $additional_options['feedback'] = $options['feedback'];
    }

    $migrations = $this->migrationsList($migration_names, $options);
    if (empty($migrations)) {
      $this->logger()->error(dt('No migrations found.'));
    }

    // Take it one group at a time,
    // rolling back the migrations within each group.
    foreach ($migrations as $group_id => $migration_list) {
      // Roll back in reverse order.
      $migration_list = array_reverse($migration_list);
      foreach ($migration_list as $migration_id => $migration) {
        $executable = new MigrateExecutable(
          $migration,
          $this->getMigrateMessage(),
          $additional_options
        );
        // drush_op() provides --simulate support.
        drush_op([$executable, 'rollback']);
      }
    }
  }

  /**
   * Stop an active migration operation.
   *
   * @param string $migration_id
   *   ID of migration to stop.
   *
   * @command migrate:stop
   *
   * @validate-module-enabled migrate_tools
   * @aliases mst, migrate-stop
   */
  public function stop($migration_id = '') {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance(
      $migration_id
    );
    if ($migration) {
      $status = $migration->getStatus();
      switch ($status) {
        case MigrationInterface::STATUS_IDLE:
          $this->logger()->warning(
            dt('Migration @id is idle', ['@id' => $migration_id])
          );
          break;

        case MigrationInterface::STATUS_DISABLED:
          $this->logger()->warning(
            dt('Migration @id is disabled', ['@id' => $migration_id])
          );
          break;

        case MigrationInterface::STATUS_STOPPING:
          $this->logger()->warning(
            dt('Migration @id is already stopping', ['@id' => $migration_id])
          );
          break;

        default:
          $migration->interruptMigration(MigrationInterface::RESULT_STOPPED);
          $this->logger()->notice(
            dt('Migration @id requested to stop', ['@id' => $migration_id])
          );
          break;
      }
    }
    else {
      $this->logger()->error(
        dt('Migration @id does not exist', ['@id' => $migration_id])
      );
    }
  }

  /**
   * Reset a active migration's status to idle.
   *
   * @param string $migration_id
   *   ID of migration to reset.
   *
   * @command migrate:reset-status
   *
   * @validate-module-enabled migrate_tools
   * @aliases mrs, migrate-reset-status
   */
  public function resetStatus($migration_id = '') {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance(
      $migration_id
    );
    if ($migration) {
      $status = $migration->getStatus();
      if ($status == MigrationInterface::STATUS_IDLE) {
        $this->logger()->warning(
          dt('Migration @id is already Idle', ['@id' => $migration_id])
        );
      }
      else {
        $migration->setStatus(MigrationInterface::STATUS_IDLE);
        $this->logger()->notice(
          dt('Migration @id reset to Idle', ['@id' => $migration_id])
        );
      }
    }
    else {
      $this->logger()->error(
        dt('Migration @id does not exist', ['@id' => $migration_id])
      );
    }
  }

  /**
   * View any messages associated with a migration.
   *
   * @param string $migration_id
   *   ID of the migration.
   * @param array $options
   *   Additional options for the command.
   *
   * @command migrate:messages
   *
   * @option csv Export messages as a CSV
   *
   * @usage migrate:messages MyNode
   *   Show all messages for the MyNode migration
   *
   * @validate-module-enabled migrate_tools
   *
   * @aliases mmsg,migrate-messages
   */
  public function messages($migration_id, array $options = ['csv' => NULL]) {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance(
      $migration_id
    );
    if ($migration) {
      $map = $migration->getIdMap();
      $first = TRUE;
      $table = [];
      foreach ($map->getMessageIterator() as $row) {
        unset($row->msgid);
        if ($first) {
          // @todo: Ideally, replace sourceid* with source key names. Or, should
          // getMessageIterator() do that?
          foreach ($row as $column => $value) {
            $table[0][] = $column;
          }
          $first = FALSE;
        }
        $table[] = (array) $row;
      }
      if (empty($table)) {
        $this->logger()->notice(dt('No messages for this migration'));
      }
      else {
        if ($options['csv']) {
          foreach ($table as $row) {
            fputcsv(STDOUT, $row);
          }
        }
        else {
          $widths = [];
          foreach ($table[0] as $header) {
            $widths[] = strlen($header) + 1;
          }

          drush_print_table($table);
        }
      }
    }
    else {
      $this->logger()->error(
        dt('Migration @id does not exist', ['@id' => $migration_id])
      );
    }
  }

  /**
   * List the fields available for mapping in a source.
   *
   * @param string $migration_id
   *   ID of the migration.
   *
   * @command migrate:fields-source
   *
   * @usage migrate:fields-source my_node
   *   List fields for the source in the my_node migration
   *
   * @validate-module-enabled migrate_tools
   *
   * @aliases mfs, migrate-fields-source
   */
  public function fieldsSource($migration_id) {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance(
      $migration_id
    );
    if ($migration) {
      $source = $migration->getSourcePlugin();
      $table = [];
      foreach ($source->fields() as $machine_name => $description) {
        $table[] = [strip_tags($description), $machine_name];
      }
      drush_print_table($table);
    }
    else {
      $this->logger()->error(
        dt('Migration @id does not exist', ['@id' => $migration_id])
      );
    }
  }

  /**
   * Retrieve a list of active migrations.
   *
   * @param string $migration_ids
   *   Comma-separated list of migrations -
   *   if present, return only these migrations.
   * @param array $options
   *   Command options.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface[][]
   *   An array keyed by migration group, each value containing an array of
   *   migrations or an empty array if no migrations match the input criteria.
   */
  protected function migrationsList($migration_ids = '', array $options = []) {
    // Filter keys must match the migration configuration property name.
    $filter['migration_group'] = $options['group'] ? explode(
      ',',
      $options['group']
    ) : [];
    $filter['migration_tags'] = $options['tag'] ? explode(
      ',',
      $options['tag']
    ) : [];

    $manager = $this->migrationPluginManager;
    $plugins = $manager->createInstances([]);
    $matched_migrations = [];

    // Get the set of migrations that may be filtered.
    if (empty($migration_ids)) {
      $matched_migrations = $plugins;
    }
    else {
      // Get the requested migrations.
      $migration_ids = explode(',', Unicode::strtolower($migration_ids));
      foreach ($plugins as $id => $migration) {
        if (in_array(Unicode::strtolower($id), $migration_ids)) {
          $matched_migrations[$id] = $migration;
        }
      }
    }

    // Do not return any migrations which fail to meet requirements.
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    foreach ($matched_migrations as $id => $migration) {
      if ($migration->getSourcePlugin() instanceof RequirementsInterface) {
        try {
          $migration->getSourcePlugin()->checkRequirements();
        }
        catch (RequirementsException $e) {
          unset($matched_migrations[$id]);
        }
      }
    }

    // Filters the matched migrations if a group or a tag has been input.
    if (!empty($filter['migration_group']) || !empty($filter['migration_tags'])) {
      // Get migrations in any of the specified groups and with any of the
      // specified tags.
      foreach ($filter as $property => $values) {
        if (!empty($values)) {
          $filtered_migrations = [];
          foreach ($values as $search_value) {
            foreach ($matched_migrations as $id => $migration) {
              // Cast to array because migration_tags can be an array.
              $configured_values = (array) $migration->get($property);
              $configured_id = (in_array(
                $search_value,
                $configured_values
              )) ? $search_value : 'default';
              if (empty($search_value) || $search_value == $configured_id) {
                if (empty($migration_ids) || in_array(
                    Unicode::strtolower($id),
                    $migration_ids
                  )) {
                  $filtered_migrations[$id] = $migration;
                }
              }
            }
          }
          $matched_migrations = $filtered_migrations;
        }
      }
    }

    // Sort the matched migrations by group.
    if (!empty($matched_migrations)) {
      foreach ($matched_migrations as $id => $migration) {
        $configured_group_id = empty($migration->get('migration_group')) ? 'default' : $migration->get('migration_group');
        $migrations[$configured_group_id][$id] = $migration;
      }
    }
    return isset($migrations) ? $migrations : [];
  }

  /**
   * Executes a single migration.
   *
   * If the --execute-dependencies option was given,
   * the migration's dependencies will also be executed first.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to execute.
   * @param string $migration_id
   *   The migration ID (not used, just an artifact of array_walk()).
   * @param array $options
   *   Additional options of the command.
   *
   * @throws \Exception
   *   If some migrations failed during execution.
   */
  protected function executeMigration(
    MigrationInterface $migration,
    $migration_id,
    array $options = []
  ) {
    if (isset($options['execute-dependencies'])) {
      if ($required_IDS = $migration->get('requirements')) {
        $manager = $this->migrationPluginManager;
        $required_migrations = $manager->createInstances($required_IDS);
        $dependency_options = array_merge($options, ['is_dependency' => TRUE]);
        array_walk($required_migrations, __FUNCTION__, $dependency_options);
      }
    }
    if (!empty($options['force'])) {
      $migration->set('requirements', []);
    }
    if (!empty($options['update'])) {
      $migration->getIdMap()->prepareUpdate();
    }
    $executable = new MigrateExecutable($migration, $this->getMigrateMessage(), $options);
    // drush_op() provides --simulate support.
    drush_op([$executable, 'import']);
    if ($count = $executable->getFailedCount()) {
      // Nudge Drush to use a non-zero exit code.
      throw new \Exception(
        dt(
          '!name Migration - !count failed.',
          ['!name' => $migration_id, '!count' => $count]
        )
      );
    }
  }

  /**
   * Gets the migrate message logger.
   *
   * @return \Drupal\migrate\MigrateMessageInterface
   *   The migrate message service.
   */
  protected function getMigrateMessage() {
    if (!isset($this->migrateMessage)) {
      $this->migrateMessage = new Drush9LogMigrateMessage($this->logger());
    }
    return $this->migrateMessage;
  }

}
