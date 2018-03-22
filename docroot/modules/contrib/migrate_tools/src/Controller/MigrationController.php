<?php

namespace Drupal\migrate_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\Html;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\migrate\MigrateMessage;

/**
 * Returns responses for migrate_tools migration view routes.
 */
class MigrationController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new MigrationController object.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The plugin manager for config entity-based migrations.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match.
   */
  public function __construct(MigrationPluginManagerInterface $migration_plugin_manager, CurrentRouteMatch $currentRouteMatch) {
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migration'),
      $container->get('current_route_match')
    );
  }

  /**
   * Displays an overview of a migration entity.
   *
   * @param string $migration_group
   *   Machine name of the migration's group.
   * @param string $migration
   *   Machine name of the migration.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function overview($migration_group, $migration) {

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance($migration);

    $build['overview'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Overview'),
    ];

    $build['overview']['group'] = [
      '#title' => $this->t('Group:'),
      '#markup' => Xss::filterAdmin($migration_group),
      '#type' => 'item',
    ];

    $build['overview']['description'] = [
      '#title' => $this->t('Description:'),
      '#markup' => Xss::filterAdmin($migration->label()),
      '#type' => 'item',
    ];

    $migration_dependencies = $migration->getMigrationDependencies();
    if (!empty($migration_dependencies['required'])) {
      $build['overview']['dependencies'] = [
        '#title' => $this->t('Migration Dependencies') ,
        '#markup' => Xss::filterAdmin(implode(', ', $migration_dependencies['required'])),
        '#type' => 'item',
      ];
    }
    if (!empty($migration_dependencies['optional'])) {
      $build['overview']['soft_dependencies'] = [
        '#title' => $this->t('Soft Migration Dependencies'),
        '#markup' => Xss::filterAdmin(implode(', ', $migration_dependencies['optional'])),
        '#type' => 'item',
      ];
    }

    return $build;
  }

  /**
   * Display source information of a migration entity.
   *
   * @param string $migration_group
   *   Machine name of the migration's group.
   * @param string $migration
   *   Machine name of the migration.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function source($migration_group, $migration) {

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance($migration);

    // Source field information.
    $build['source'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Source'),
      '#group' => 'detail',
      '#description' => $this->t('<p>These are the fields available from the source of this migration task. The machine names listed here may be used as sources in the process pipeline.</p>'),
      '#attributes' => [
        'id' => 'migration-detail-source',
      ],
    ];

    $source = $migration->getSourcePlugin();
    $build['source']['query'] = [
      '#type' => 'item',
      '#title' => $this->t('Query'),
      '#markup' => '<pre>' . Xss::filterAdmin($source) . '</pre>',
    ];
    $header = [$this->t('Machine name'), $this->t('Description')];
    $rows = [];
    foreach ($source->fields($migration) as $machine_name => $description) {
      $rows[] = [
        ['data' => Html::escape($machine_name)],
        ['data' => Xss::filterAdmin($description)],
      ];
    }

    $build['source']['fields'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No fields'),
    ];

    return $build;
  }

  /**
   * Run a migration.
   *
   * @param string $migration_group
   *   Machine name of the migration's group.
   * @param string $migration
   *   Machine name of the migration.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function run($migration_group, $migration) {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance($migration);

    $migrateMessage = new MigrateMessage();
    $options = [];

    $executable = new MigrateBatchExecutable($migration, $migrateMessage, $options);
    $executable->batchImport();

    $migration_group = $this->currentRouteMatch->getParameter('migration_group');
    $route_parameters = [
      'migration_group' => $migration_group,
      'migration' => $migration->id(),
    ];
    return batch_process(Url::fromRoute('entity.migration.process', $route_parameters));
  }

  /**
   * Display process information of a migration entity.
   *
   * @param string $migration_group
   *   Machine name of the migration's group.
   * @param string $migration
   *   Machine name of the migration.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function process($migration_group, $migration) {

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance($migration);

    // Process information.
    $build['process'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Process'),
    ];

    $header = [
      $this->t('Destination'),
      $this->t('Source'),
      $this->t('Process plugin'),
      $this->t('Default'),
    ];
    $rows = [];
    foreach ($migration->getProcess() as $destination_id => $process_line) {
      $row = [];
      $row[] = ['data' => Html::escape($destination_id)];
      if (isset($process_line[0]['source'])) {
        $row[] = ['data' => Xss::filterAdmin($process_line[0]['source'])];
      }
      else {
        $row[] = '';
      }
      if (isset($process_line[0]['plugin'])) {
        $row[] = ['data' => Xss::filterAdmin($process_line[0]['plugin'])];
      }
      else {
        $row[] = '';
      }
      if (isset($process_line[0]['default_value'])) {
        $row[] = ['data' => Xss::filterAdmin($process_line[0]['default_value'])];
      }
      else {
        $row[] = '';
      }
      $rows[] = $row;
    }

    $build['process']['fields'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No process defined.'),
    ];

    $build['process']['run'] = [
      '#type' => 'link',
      '#title' => $this->t('Run'),
      '#url' => Url::fromRoute('entity.migration.process.run', ['migration_group' => $migration_group, 'migration' => $migration->id()]),
    ];

    return $build;
  }

  /**
   * Displays destination information of a migration entity.
   *
   * @param string $migration_group
   *   Machine name of the migration's group.
   * @param string $migration
   *   Machine name of the migration.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function destination($migration_group, $migration) {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance($migration);

    // Destination field information.
    $build['destination'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Destination'),
      '#group' => 'detail',
      '#description' => $this->t('<p>These are the fields available in the destination plugin of this migration task. The machine names are those available to be used as the keys in the process pipeline.</p>'),
      '#attributes' => [
        'id' => 'migration-detail-destination',
      ],
    ];
    $destination = $migration->getDestinationPlugin();
    $build['destination']['type'] = [
      '#type' => 'item',
      '#title' => $this->t('Type'),
      '#markup' => Xss::filterAdmin($destination->getPluginId()),
    ];
    $header = [$this->t('Machine name'), $this->t('Description')];
    $rows = [];
    $destination_fields = $destination->fields() ?: [];
    foreach ($destination_fields as $machine_name => $description) {
      $rows[] = [
        ['data' => Html::escape($machine_name)],
        ['data' => Xss::filterAdmin($description)],
      ];
    }

    $build['destination']['fields'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No fields'),
    ];

    return $build;
  }

}
