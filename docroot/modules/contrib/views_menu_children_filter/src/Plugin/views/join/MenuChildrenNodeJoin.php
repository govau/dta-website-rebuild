<?php

namespace Drupal\views_menu_children_filter\Plugin\views\join;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\views\Plugin\views\join\JoinPluginInterface;
use Drupal\views\Plugin\views\query\Sql;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Views Join plugin to join the Node table to the menu_tree table.
 *
 * @package Drupal\views_menu_children_filter
 * @ingroup views_join_handlers
 * @ViewsJoin("menu_children_node_join")
 */
class MenuChildrenNodeJoin extends JoinPluginBase implements JoinPluginInterface {


  /**
   * The values to concat with node.nid in to join on the menu_tree's route_param_key column.
   *
   * @var array Defaults to: [ 'entity:node/' ].
   */
  public $prefixes = array( 'entity:node/' );


  /**
   * MenuChildrenNodeJoin constructor.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler Required to setup hook_TYPE_alter hooks so module's can alter this join.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   */
  public function __construct(ModuleHandlerInterface $module_handler, array $configuration, $plugin_id, $plugin_definition ) {
    // Define a hook_TYPE_alter hook for other modules to change the "prefixes" used in the SQL join.
    $module_handler->alter('menu_children_filter_join_prefix', $this->prefixes);

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
    // Setup some defaults.
    $configuration = array_merge(array(
      'type' => 'INNER',
      'table' => 'menu_link_content_data',
      'field' => false, // Formula value handled in the "buildJoin" function.
      'left_table' => false,
      'left_field' => false,
      'operator' => '=',
    ), $configuration);

    // TODO: Remove this after dev work completed.
    // $test = $container->get('views_menu_children_filter.join_handler');

    $plugin_id = empty($plugin_id)
      ? "menu_children_node_join"
      : $plugin_id;

    return new static(
      $container->get('module_handler'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }


  /**
   * {@inheritdoc}
   */
  function buildJoin($select_query, $table, $view_query) {
    $values = array();

    $node_table_alias = $select_query->getTables()['node_field_data']['alias'];

    $condition = "( $this->table.enabled = 1 ) AND ( ";
    // Loop over the prefixes to use while join on the menu_tree.route_param_key and construct the JOIN condition SQL.
    for($i = 0; $i < count($this->prefixes); $i++) {
      // Use MySQL's CONCAT func to concatenate the prefix with the node.nid.
      // Provide the prefix as a parametrized value.
      $placeholder = ':views_join_condition_' . $select_query->nextPlaceholder();
      $values[$placeholder] = $this->prefixes[$i];

      $condition .= "( CONCAT($placeholder, $node_table_alias.nid) = $this->table.link__uri)";
      // Each additional prefix to use in the join condition is added as a "OR" statement.
      // Keep using the OR statement unless we're on the last iteration or there is only one prefix to use.
      if($i < (count($this->prefixes) -1)) {
        $condition .= " OR ";
      }
    }
    $condition .= " )";

    $select_query->addJoin($this->type, $this->table, $table['alias'], $condition, $values);
  }

  /**
   * @param Sql $query The query that the join will be added to.
   * @param bool $allow_duplicate_join If "false", prevents this join from joining more than once if this function is called repeatedly.
   */
  public function joinToNodeTable(Sql $query, $allow_duplicate_join = false) {
    // Because this can be called from the argument and sort handlers,
    // first check to see if the join as already been applied.
    if(!$allow_duplicate_join && isset($query->tables['node_field_data']['menu_link_content_data'])) {
      return;
    }

    //drupal_alter('views_menu_children_filter_join', $join->prefixes, $menu_name, $query); /* Not D8 compatible. */
    $query->queueTable("menu_link_content_data", "node_field_data", $this);
  }

  /**
   * Filter the query by either a: parent node, page page via its link_path, or null and limit to root nodes.
   *
   * @param \Drupal\views\Plugin\views\query\Sql $query The $query The query we're going to alter.
   * @param \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $link The by parent link.
   */
  public static function filterByPage(Sql $query, $link) {
    $parent = $link->getPluginId();

    $query->addWhereExpression(
      0,
      'menu_link_content_data.parent = :parent_lid',
      array(':parent_lid' => $parent)
    );
  }
}
