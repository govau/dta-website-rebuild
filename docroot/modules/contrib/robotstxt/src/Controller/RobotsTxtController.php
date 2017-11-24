<?php

namespace Drupal\robotstxt\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides output robots.txt output.
 */
class RobotsTxtController extends ControllerBase {

  /**
   * Serves the configured robots.txt file.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The robots.txt file as a response object with 'text/plain' content type.
   */
  public function content() {
    $content = [];
    $content[] = \Drupal::config('robotstxt.settings')->get('content');

    // Hook other modules for adding additional lines.
    if ($additions = \Drupal::moduleHandler()->invokeAll('robotstxt')) {
      $content = array_merge($content, $additions);
    }

    // Trim any extra whitespace and filter out empty strings.
    $content = array_map('trim', $content);
    $content = array_filter($content);
    $content = implode("\n", $content);

    return new Response($content, 200, ['Content-Type' => 'text/plain']);
  }

}
