<?php

namespace Drupal\views_menu_children_filter\Plugin\views\sort;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\views_menu_children_filter\Plugin\views\join\MenuChildrenNodeJoin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default implementation of the base sort plugin.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("menu_children")
 */
class MenuChildren extends SortPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\views_menu_children_filter\Plugin\views\join\MenuChildrenNodeJoin
   */
  protected $joinHandler;

  /**
   * MenuChildren constructor.
   * @param \Drupal\views_menu_children_filter\Plugin\views\join\MenuChildrenNodeJoin $join_handler
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   */
  function __construct(MenuChildrenNodeJoin $join_handler, array $configuration, $plugin_id, $plugin_definition) {
    $this->joinHandler = $join_handler;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }
  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('views_menu_children_filter.join_handler'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  public function query() {

    $this->joinHandler->joinToNodeTable($this->query);

    $tables = $this->query->tables['node_field_data'];

    $this->query->addOrderBy($tables['menu_link_content_data']['alias'], 'weight', $this->options['order']);
    $this->query->addOrderBy($tables['node_field_data']['alias'], 'title', $this->options['order']);
    $this->query->addOrderBy($tables['menu_link_content_data']['alias'], 'id', $this->options['order']);
  }
}
