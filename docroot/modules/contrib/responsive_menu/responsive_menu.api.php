<?php

/**
 * @file
 * Responsive menu module APIs.
 */

/**
 * Alter the menu names used by the off-canvas responsive menu.
 *
 * @param string $menus
 *   Contains the machine names of all menus, separated by commas, to be
 *   concatenated into a single menu structure for the off-canvas menu.
 */
function hook_responsive_menu_off_canvas_menu_names_alter(&$menus) {
  // Display a different menu on the front page.
  if (\Drupal::service('path.matcher')->isFrontPage()) {
    $menus = 'frontpage-menu';
  }
}

/**
 * Alter the menu name used by the horizontal responsive menu.
 *
 * @param string $menu_name
 *   The machine name of the menu configured for the horizontal menu.
 */
function hook_responsive_menu_horizontal_menu_name_alter(&$menu_name) {
  // Display a different horizontal menu for node/1.
  $current_path = \Drupal::service('path.current')->getPath();
  if (\Drupal::service('path.matcher')->matchPath($current_path, '/node/1')) {
    $menu_name = 'node-1-menu';
  }
}

/**
 * Alter the off-canvas menu tree.
 *
 * @param array $rendered_tree
 *   The built menu tree to be altered. This is provided as a render array.
 */
function hook_responsive_menu_off_canvas_tree_alter(array &$rendered_tree) {
  // Modify the off-canvas mobile menu tree and change the title of the
  // first item.
  $first = key($rendered_tree['#items']);
  $render_array['#items'][$first]['title'] = 'first';
}

/**
 * Alter the horizontal menu tree.
 *
 * @param array $rendered_tree
 *   The built menu tree to be altered. This is provided as a render array.
 */
function hook_responsive_menu_horizontal_tree_alter(array &$rendered_tree) {
  // Modify the horizontal menu tree and change the title of the first item.
  $first = key($rendered_tree['#items']);
  $render_array['#items'][$first]['title'] = 'first';
}
