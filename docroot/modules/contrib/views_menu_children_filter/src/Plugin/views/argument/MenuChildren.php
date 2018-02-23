<?php

namespace Drupal\views_menu_children_filter\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views_menu_children_filter\MenuOptionsHelper;
use Drupal\views_menu_children_filter\Plugin\views\join\MenuChildrenNodeJoin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A filter to show menu children of a parent menu item
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("menu_children")
 */
class MenuChildren extends NumericArgument implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['target_menus'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    unset($form['not']);
    unset($form['break_phrase']);

    $form['target_menus'] = MenuOptionsHelper::getSelectField($this->options['target_menus']);
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $page_identifier = reset($this->value);
    // If we aren't going to filter by a parent, just depend on the
    // join that is established in $this::setRelationship().
    if (!empty($page_identifier)) {
      $menus = $this->options['target_menus'];

      // Filter results to children nodes of the node found for the provided page_identifier.
      self::filterChildrenNodeByParent($this->query, $page_identifier, $menus);
    }
  }

  /**
   * Filter results to child nodes of a MenuLink found by the $parent_page_identifier.
   *
   * @param \Drupal\views\Plugin\views\query\Sql $query
   * @param string $parent_page_identifier String representation of a parent node to lookup. This could be a node ID or a relative URL to a parent page.
   * @param array $menus The menu names to constrain the results to.
   */
  public static function filterChildrenNodeByParent(Sql $query, $parent_page_identifier, array $menus) {
    $url = self::getUrlFromFilterInput($parent_page_identifier);
    $link = self::getMenuLinkFromTargetUrl($menus, $url);

    // Filter child nodes by parent page via its MenuLink.
    self::filterByPage($query, $link);
  }

  /**
   * Takes a path (url) and finds the matching MenuLink within the provided menus.
   *
   * @param array|NULL $menus The menus to search for the parent within
   * @param Url $url The argument, usually a path or a NID
   * @param bool $reset_cache Resets the static caching.
   * @return \Drupal\Core\Menu\MenuLinkInterface|null
   */
  public static function getMenuLinkFromTargetUrl($menus, Url $url = null, $reset_cache = false) {

    static $route_links = array();

    if($reset_cache) {
      $route_links = array();
    }

    $routeParameters = array();

    // Get route details for the provided $url.
    // If one is not found, use current route.
    if (isset($url)) {
      $route = $url->getRouteName();
      $routeParameters = $url->getRouteParameters();
      // There is a strange behavior in looking up a link by route if a route parameter only has a key, and no value.
      // Check of the route parameters has any keys with null values.
      foreach($routeParameters as $key => $value) {
        if(is_null($value)) {
          unset($routeParameters[$key]);
        }
      }
    }
    //else {
    //  $route = \Drupal::service('current_route_match')->getRouteName();
    //}

    foreach((array) $menus as $menu) {
      // Build an identifier of our values for static caching lookups.
      $path_identifier = self::buildRouteIdentifier($menu, $route, $routeParameters);

      // Has this lookup been performed and cached already?
      if(isset($route_links[$path_identifier])) {
        return $route_links[$path_identifier];
      }

      // Retrieve a list of menu names, ordered by preference.
      //TODO: Use dependency injection to load the MenuLinkManager service.
      $menu_links = \Drupal::service('plugin.manager.menu.link')->loadLinksByRoute($route, $routeParameters, $menu);

      if(!empty($menu_links)) {
        // Ideally, only one result is returned.
        // If multiple links returned, maybe I should add some kind of reporting?
        //TODO: Add alerting, throw exception, or log the fact that more than one result was found.

        $target_menu_link = reset($menu_links);

        $route_links[$path_identifier] = $target_menu_link;

        return $target_menu_link;
      }
    }
  }

  /**
   * Creates a string representing menu link for static caching.
   * Example output: menu_name:route_name:route_param1_key:route_param1_value
   *
   * @param string $menu_name
   * @param string $route_name
   * @param array $route_parameters Example: [ 'node': 1 ]
   * @return string
   */
  public static function buildRouteIdentifier($menu_name, $route_name, array $route_parameters) {
    // Merge the keys and values of the $route_parameters array into a zipper like fashion.
    $zipped_arrays = array_map(null, array_keys($route_parameters), array_values($route_parameters));

    return "$menu_name:$route_name:" . implode(":", reset($zipped_arrays));
  }

  /**
   * @inheritdoc
   */
  public function setRelationship() {

    $this->joinHandler->joinToNodeTable($this->query);

    $menus = $this->options['target_menus'];

    $this->query->addWhereExpression(
      0,
      "menu_link_content_data.menu_name in (:menus[])",
      array(':menus[]' => array_keys($menus))
    );
  }


  /**
   * Filter the query by either a: parent node, page page via its link_path, or null and limit to root nodes.
   *
   * @param \Drupal\views\Plugin\views\query\Sql $query The $query The query we're going to alter.
   * @param \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $link The by parent link.
   */
  public static function filterByPage(Sql $query, $link) {

    $parent = isset($link)
      ? $link->getPluginId()
      : 0;

    $query->addWhereExpression(
      0,
      'menu_link_content_data.parent = :parent_lid',
      array(':parent_lid' => $parent)
    );
  }

  /**
   * Gets a Url based on the provided user input.
   * Could be either a Node Id, or a menu entry path.
   *
   * @param string|integer $input The routable url path, or node ID. I.e.: node/100 (Supports an integer to default to node/%)
   * @return Url The Url object representing the parent entity
   */
  protected static function getUrlFromFilterInput($input) {
    if (is_numeric($input)) {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $input]);
    } else {
      $url = Url::fromUserInput('/' . trim($input, '/'));
    }

    return $url;
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
}
