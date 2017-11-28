<?php

namespace Drupal\simple_sitemap\Batch;

/**
 * Interface UrlGeneratorInterface
 * @package Drupal\simple_sitemap\Batch
 */
interface UrlGeneratorInterface {

  /**
   * @param mixed $data
   */
  public function generate($data);

}
