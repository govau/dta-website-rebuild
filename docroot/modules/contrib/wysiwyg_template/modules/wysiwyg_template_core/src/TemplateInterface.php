<?php

/**
 * @file
 * Contains \Drupal\wysiwyg_template_core\TemplateInterface.
 */

namespace Drupal\wysiwyg_template_core;

use Drupal\node\NodeTypeInterface;

/**
 * Provides an interface for defining Template entities.
 */
interface TemplateInterface  {

  /**
   * Gets the template description.
   *
   * @return string
   *   The template description.
   */
  public function getDescription();

  /**
   * Gets the template body.
   *
   * @return string
   *   The template HTML body.
   */
  public function getBody();

  /**
   * Gets the text format.
   *
   * @return string
   *   The text format for the body.
   */
  public function getFormat();

  /**
   * Gets the template weight.
   *
   * @return int
   *   The template weight.
   */
  public function getWeight();

  /**
   * Gets the list of allowed node types.
   *
   * @return string[]
   */
  public function getNodeTypes();

  /**
   * Loads templates filtered by node type.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   (optional) The node type to filter by. If this is not passed, only
   *   templates that specify *no* types will be returned.
   *
   * @return \Drupal\wysiwyg_template\TemplateInterface[]
   *   The list of available templates filtered by node type.
   */
  public static function loadByNodeType(NodeTypeInterface $node_type = NULL);

}
