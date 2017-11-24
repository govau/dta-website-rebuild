<?php

namespace Drupal\simple_sitemap\Batch;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Cache\Cache;

/**
 * Class Batch
 * @package Drupal\simple_sitemap\Batch
 *
 * The services of this class are not injected, as this class looses its state
 * on every method call because of how the batch APi works.
 */
class Batch {

  use StringTranslationTrait;

  /**
   * @var array
   */
  protected $batch;

  /**
   * @var array
   */
  protected $batchInfo;

  const BATCH_TITLE = 'Generating XML sitemap';
  const BATCH_INIT_MESSAGE = 'Initializing batch...';
  const BATCH_ERROR_MESSAGE = 'An error has occurred. This may result in an incomplete XML sitemap.';
  const BATCH_PROGRESS_MESSAGE = 'Processing @current out of @total link types.';
  const REGENERATION_FINISHED_MESSAGE = "The <a href='@url' target='_blank'>XML sitemap</a> has been regenerated for all languages.";
  const REGENERATION_FINISHED_ERROR_MESSAGE = 'The sitemap generation finished with an error.';

  /**
   * Batch constructor.
   */
  public function __construct() {
    $this->batch = [
      'title' => $this->t(self::BATCH_TITLE),
      'init_message' => $this->t(self::BATCH_INIT_MESSAGE),
      'error_message' => $this->t(self::BATCH_ERROR_MESSAGE),
      'progress_message' => $this->t(self::BATCH_PROGRESS_MESSAGE),
      'operations' => [],
      'finished' => [__CLASS__, 'finishGeneration'],
    ];
  }

  /**
   * @param array $batch_info
   */
  public function setBatchInfo(array $batch_info) {
    $this->batchInfo = $batch_info;
  }

  /**
   * Starts the batch process depending on where it was requested from.
   */
  public function start() {
    switch ($this->batchInfo['from']) {

      case 'form':
        // Start batch process.
        batch_set($this->batch);
        break;

      case 'drush':
        // Start drush batch process.
        batch_set($this->batch);

        // See https://www.drupal.org/node/638712
        $this->batch =& batch_get();
        $this->batch['progressive'] = FALSE;

        drush_log($this->t(self::BATCH_INIT_MESSAGE), 'status');
        drush_backend_batch_process();
        break;

      case 'backend':
        // Start backend batch process.
        batch_set($this->batch);

        // See https://www.drupal.org/node/638712
        $this->batch =& batch_get();
        $this->batch['progressive'] = FALSE;

        // todo: Does not take advantage of batch API and eventually runs out of memory on very large sites. Use queue API instead?
        batch_process();
        break;

      case 'nobatch':
        // Call each batch operation the way the Drupal batch API would do, but
        // within one process (so in fact not using batch API here, just
        // mimicking it to avoid code duplication).
        $context = [];
        foreach ($this->batch['operations'] as $i => $operation) {
          $operation[1][] = &$context;
          call_user_func_array($operation[0], $operation[1]);
        }
        $this->finishGeneration(TRUE, $context['results'], []);
        break;
    }
  }

  /**
   * Adds an operation to the batch.
   *
   * @param string $processing_service
   * @param array $data
   */
  public function addOperation($processing_service, array $data) {
    $this->batch['operations'][] = [
      __CLASS__ . '::generate', [$processing_service, $data, $this->batchInfo],
    ];
  }

  /**
   * Batch callback function which generates urls to entity paths.
   *
   * @param array $entity_info
   * @param array $batch_info
   * @param array &$context
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */

  /**
   * Batch callback function which generates URLs.
   *
   * @param $processing_service
   * @param array $data
   * @param array $batch_info
   * @param $context
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public static function generate($processing_service, array $data, array $batch_info, &$context) {
    \Drupal::service($processing_service)
      ->setContext($context)
      ->setBatchInfo($batch_info)
      ->generate($data);
  }

  /**
   * Callback function called by the batch API when all operations are finished.
   *
   * @param $success
   * @param $results
   * @param $operations
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public static function finishGeneration($success, $results, $operations) {
    if ($success) {
      $remove_sitemap = empty($results['chunk_count']);
      if (!empty($results['generate']) || $remove_sitemap) {
        \Drupal::service('simple_sitemap.sitemap_generator')
          ->generateSitemap($results['generate'], $remove_sitemap);
      }
      Cache::invalidateTags(['simple_sitemap']);
      \Drupal::service('simple_sitemap.logger')->m(self::REGENERATION_FINISHED_MESSAGE,
        ['@url' => $GLOBALS['base_url'] . '/sitemap.xml'])
//        ['@url' => $this->sitemapGenerator->getCustomBaseUrl() . '/sitemap.xml']) //todo: Use actual base URL for message.
        ->display('status')
        ->log('info');
    }
    else {
      \Drupal::service('simple_sitemap.logger')->m(self::REGENERATION_FINISHED_ERROR_MESSAGE)
        ->display('error', 'administer sitemap settings')
        ->log('error');
    }
  }

}
