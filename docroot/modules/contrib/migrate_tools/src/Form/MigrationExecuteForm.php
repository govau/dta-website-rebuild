<?php

namespace Drupal\migrate_tools\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This form is specifically for configuring process pipelines.
 */
class MigrationExecuteForm extends FormBase {

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs a new MigrationExecuteForm object.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The plugin manager for config entity-based migrations.
   */
  public function __construct(MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migration_execute_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];

    $form['operations'] = $this->migrateMigrateOperations();

    return $form;
  }


  /**
   * Get Operations.
   */
  private function migrateMigrateOperations() {
    // Build the 'Update options' form.
    $form = [
      '#type' => 'fieldset',
      '#title' => t('Operations'),
    ];
    $options = [
      'import' => t('Import'),
      'rollback' => t('Rollback'),
      'stop' => t('Stop'),
      'reset' => t('Reset'),
    ];
    $form['operation'] = [
      '#type' => 'select',
      '#title' => t('Choose an operation to run'),
      '#options' => $options,
      '#default_value' => 'import',
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Execute'),
    ];
    $definitions = [];
    $definitions[] = $this->t('Import: Imports all previously unprocessed records from the source, plus any records marked for update, into destination Drupal objects.');
    $definitions[] = $this->t('Rollback: Deletes all Drupal objects created by the import.');
    $definitions[] = $this->t('Stop: Cleanly interrupts any import or rollback processes that may currently be running.');
    $definitions[] = $this->t('Reset: Sometimes a process may fail to stop cleanly, and be left stuck in an Importing or Rolling Back status. Choose Reset to clear the status and permit other operations to proceed.');
    $form['definitions'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('Definitions'),
      '#list_type' => 'ul',
      '#items' => $definitions,
    ];

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => t('Options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['options']['update'] = [
      '#type' => 'checkbox',
      '#title' => t('Update'),
      '#description' => t('Check this box to update all previously-imported content
      in addition to importing new content. Leave unchecked to only import
      new content'),
    ];
    $form['options']['force'] = [
      '#type' => 'checkbox',
      '#title' => t('Ignore dependencies'),
      '#description' => t('Check this box to ignore dependencies when running imports
      - all tasks will run whether or not their dependent tasks have
      completed.'),
    ];
    // @TODO: Limit is not working. Perhaps because of batch? See
    // https://www.drupal.org/project/migrate_tools/issues/2924298.
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('operation'))) {
      $form_state->setErrorByName('operation', $this->t('Please select an operation.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $operation = $form_state->getValue('operation');

    if ($form_state->getValue('limit')) {
      $limit = $form_state->getValue('limit');
    }
    else {
      $limit = 0;
    }

    if ($form_state->getValue('update')) {
      $update = $form_state->getValue('update');
    }
    else {
      $update = 0;
    }
    if ($form_state->getValue('force')) {
      $force = $form_state->getValue('force');
    }
    else {
      $force = 0;
    }

    $migration_name = \Drupal::routeMatch()->getParameter('migration');

    if ($migration_name) {

      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migration = $this->migrationPluginManager->createInstance($migration_name);
      $migrateMessage = new MigrateMessage();

      switch ($operation) {
        case 'import':

          $options = [
            'limit' => $limit,
            'update' => $update,
            'force' => $force,
          ];

          $executable = new MigrateBatchExecutable($migration, $migrateMessage, $options);
          $executable->batchImport();

          break;

        case 'rollback':

          $options = [
            'limit' => $limit,
            'update' => $update,
            'force' => $force,
          ];

          $executable = new MigrateBatchExecutable($migration, $migrateMessage, $options);
          $executable->rollback();

          break;

        case 'stop':

          $migration->interruptMigration(MigrationInterface::RESULT_STOPPED);

          break;

        case 'reset':

          $migration->setStatus(MigrationInterface::STATUS_IDLE);

          break;

      }
    }
  }

}
