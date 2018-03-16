<?php

namespace Drupal\simple_sitemap\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a UrlGenerator item annotation object.
 *
 * @package Drupal\simple_sitemap\Annotation
 *
 * @see Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager
 * @see plugin_api
 *
 * @Annotation
 */
class UrlGenerator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * @var int
   */
  public $weight;

  /**
   * @var bool
   */
  public $instantiateForEachDataSet;
}
