<?php

/**
 * @file
 * Hooks provided by the Views Menu Children Filter module.
 */


/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the prefixes to use when JOINing the `menu_link_content_data` table via the `link__uri` column.
 * @param array $prefixes
 */
function hook_menu_children_filter_join_prefix_alter(array &$prefixes) {
  $prefixes[] = 'entity:node/';
}

/**
 * @} End of "addtogroup hooks".
 */
