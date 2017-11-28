<?php

namespace Drupal\linkit_media_test\Plugin\MediaEntity\Type;

use Drupal\media_entity\Plugin\MediaEntity\Type\Generic;

/**
 * Provides generic media type.
 *
 * @MediaType(
 *   id = "test_type",
 *   label = @Translation("Test type"),
 *   description = @Translation("Test media type.")
 * )
 */
class TestType extends Generic {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => 'field_media_file',
    ];
  }

}
