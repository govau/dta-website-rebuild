<?php

namespace Drupal\responsive_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the HorizontalMenu block.
 *
 * @Block(
 *   id = "responsive_menu_horizontal_menu",
 *   admin_label = @Translation("Horizontal menu")
 * )
 */
class HorizontalMenu extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $menu_active_trail) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuTree = $menu_tree;
    $this->menuActiveTrail = $menu_active_trail;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('menu.active_trail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $depth = \Drupal::config('responsive_menu.settings')->get('horizontal_depth');
    $menu_name = \Drupal::config('responsive_menu.settings')->get('horizontal_menu');

    // Allow other modules to modify the menu name.
    \Drupal::ModuleHandler()->alter('responsive_menu_horizontal_menu_name', $menu_name);

    $menu_tree = \Drupal::menuTree();
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setMaxDepth($depth);
    // Force the entire tree to be build be setting expandParents to an
    // empty array.
    $parameters->expandedParents = [];
    $tree = $menu_tree->load($menu_name, $parameters);
    $manipulators = [
      // Show links to nodes that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      // Only show links that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      // Use the default sorting of menu links.
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    $menu = $menu_tree->build($tree);

    // Allow other modules to manipulate the built tree data.
    \Drupal::ModuleHandler()->alter('responsive_menu_horizontal_tree', $menu);

    $menu['#theme'] = 'responsive_menu_horizontal';
    $output = [
      '#theme' => 'responsive_menu_block_wrapper',
      '#element_type' => \Drupal::config('responsive_menu.settings')->get('horizontal_wrapping_element'),
      '#content' => $menu,
    ];

    // Add the superfish library if the user has requested it.
    $superfish_setting = \Drupal::config('responsive_menu.settings')->get('horizontal_superfish');
    if ($superfish_setting) {
      $output['#attached']['library'][] = 'responsive_menu/responsive_menu.superfish';
    }

    // Add superfish's hoverIntent library if the user has requested it.
    if ($superfish_setting && \Drupal::config('responsive_menu.settings')->get('horizontal_superfish_hoverintent')) {
      $output['#attached']['library'][] = 'responsive_menu/responsive_menu.superfish_hoverintent';
    }

    $media_query = \Drupal::config('responsive_menu.settings')->get('horizontal_media_query');
    // Attempt to clean up a media query in case it isn't properly enclosed in
    // brackets.
    $media_query = preg_replace('/^(min|max)(.+?)$/', '($1$2)', $media_query);
    $output['#attached']['drupalSettings']['responsive_menu']['mediaQuery'] = $media_query;

    // Add a contextual link to edit the menu.
    $output['#contextual_links']['menu'] = [
      'route_parameters' => [
        'menu' => $menu_name,
      ],
    ];

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Even when the menu block renders to the empty string for a user, we want
    // the cache tag for this menu to be set: whenever the menu is changed, this
    // menu block must also be re-rendered for that user, because maybe a menu
    // link that is accessible for that user has been added.
    $cache_tags = parent::getCacheTags();
    $cache_tags[] = 'config:block.block.horizontalmenu';
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // ::build() uses MenuLinkTreeInterface::getCurrentRouteMenuTreeParameters()
    // to generate menu tree parameters, and those take the active menu trail
    // into account. Therefore, we must vary the rendered menu by the active
    // trail of the rendered menu.
    // Additional cache contexts, e.g. those that determine link text or
    // accessibility of a menu, will be bubbled automatically.
    $menu_name = \Drupal::config('responsive_menu.settings')->get('horizontal_menu');
    // Allow other modules to modify the menu name.
    \Drupal::ModuleHandler()->alter('responsive_menu_horizontal_menu_name', $menu_name);

    return Cache::mergeContexts(parent::getCacheContexts(), ['route.menu_active_trails:' . $menu_name]);
  }

}
