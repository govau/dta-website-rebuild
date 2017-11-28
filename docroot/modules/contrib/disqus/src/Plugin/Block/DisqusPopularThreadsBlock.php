<?php

namespace Drupal\disqus\Plugin\Block;

/**
 * Plugin implementation of the 'disqus_popular_threads'.
 *
 * @Block(
 *   id = "disqus_popular_threads",
 *   admin_label = @Translation("Disqus: Popular Threads"),
 *   module = "disqus"
 * )
 */
class DisqusPopularThreadsBlock extends DisqusBaseBlock {

  /**
   * {@inheritdoc}
   */
  public function functionId() {
    return 'popular_threads_widget';
  }

}
