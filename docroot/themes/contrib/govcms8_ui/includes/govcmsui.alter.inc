<?php

function govcmsui_js_alter(&$javascript, \Drupal\Core\Asset\AttachedAssetsInterface $assets) {
    // Swap out jQuery to use an updated version of the library.
    if(theme_get_setting('govcmsui_use_bootstrap') || theme_get_setting('govcmsui_use_jquery')) {
        $javascript['//cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js'] = array(
            'weight' => -95,
            'group' => -99,
            'version' => '3.1.1',
            'type' => 'external',
            'cache' => true,
            'preprocess' => true,
            'browsers' => array(),
            'scope' => 'footer',
            'attributes' => array(),
            'data' => '//cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js',
        );

        $theme_path = \Drupal::theme()->getActiveTheme()->getPath();

        $javascript[$theme_path.'/js/scripts.js']['group'] = -99;
        $javascript[$theme_path.'/js/scripts.js']['weight'] = -85;
        $javascript[$theme_path.'/js/scripts.js']['minified'] = true;
    }
    if(theme_get_setting('govcmsui_use_bootstrap')) {
        $javascript['//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js'] = array(
            'weight' => -90,
            'group' => -99,
            'version' => '3.3.7',
            'type' => 'external',
            'cache' => true,
            'preprocess' => true,
            'browsers' => array(),
            'scope' => 'footer',
            'attributes' => array(),
            'data' => '//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js',

        );
    }
}

function govcmsui_css_alter(&$css, \Drupal\Core\Asset\AttachedAssetsInterface $assets) {
    if(theme_get_setting('govcmsui_use_bootstrap')) {
        $css['//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css'] = array(
            'weight' => 0,
            'group' => -99,
            'version' => '3.3.7',
            'type' => 'external',
            'preprocess' => true,
            'browsers' => array(),
            'media' => 'all',
            'data' => '//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css',
        );
    }

    if(theme_get_setting('govcmsui_use_fontawesome')) {
        $css['//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'] = array(
            'weight' => 0,
            'group' => -99,
            'version' => '4.7.0',
            'type' => 'external',
            'preprocess' => true,
            'browsers' => array(),
            'media' => 'all',
            'data' => 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css',

        );
    }
}


/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function govcmsui_theme_suggestions_menu_alter(array &$suggestions, array $variables) {
    if (isset($variables['attributes']['region']) && !empty($variables['attributes']['region'])) {
        $suggestions[] = 'menu__region_' . $variables['attributes']['region'];
    }
    $suggestions = array_unique($suggestions);
}

function govcmsui_theme_suggestions_block_alter(array &$suggestions, array $variables) {
    $original_theme_hook = $variables['theme_hook_original'];
    if (!empty($variables['elements']['#id'])) {
        $suggestions[] = $original_theme_hook.'__' . $variables['elements']['#id'];

        $block = \Drupal\block\Entity\Block::load($variables['elements']['#id']);
        $region = $block->getRegion();

        if(isset($region)) {
            $suggestions[] = $original_theme_hook.'__'.$variables['elements']['#id'].'__region_' . $region;

            if(!empty($variables['elements']['#base_plugin_id'])) {
                $suggestions[] = $original_theme_hook.'__region_' . $region . '__' . $variables['elements']['#base_plugin_id'];
            }
        }
    }
    $suggestions = array_unique($suggestions);
}

function govcmsui_theme_suggestions_page_alter(array &$suggestions, array $variables) {
    if (($node = \Drupal::request()->attributes->get('node')) && (strpos($_SERVER['REQUEST_URI'], "revisions") == false)) {
        array_splice($suggestions, 1, 0, 'page__node__' . $node->getType());
    }
    $suggestions = array_unique($suggestions);
}

function govcmsui_theme_suggestions_views_exposed_form_alter(array &$suggestions, array $variables) {
    if (isset($variables['form']) && isset($variables['form']['#id'])) {
        $original_theme_hook = $variables['theme_hook_original'];
        $suggestions[] = $original_theme_hook . '__' . str_replace('-', '_', $variables['form']['#id']);
    }
    $suggestions = array_unique($suggestions);
}

function govcmsui_theme_suggestions_alter(array &$suggestions, array $variables) {
    if (isset($variables['element']) && isset($variables['element']['#id'])) {
        $original_theme_hook = $variables['theme_hook_original'];
        $suggestions[] = $original_theme_hook . '__' . str_replace('-', '_', $variables['element']['#id']);
        if(isset($variables['element']['#type'])) {
            $suggestions[] = $original_theme_hook . '__' . str_replace('-', '_', $variables['element']['#id']) . '_' . str_replace('-', '_', $variables['element']['#type']);
        }
    }
    $suggestions =  array_unique($suggestions);
}

function govcmsui_theme_suggestions_field_alter(array &$suggestions, array $variables) {
    if (isset($variables['element'])) {
        $original_theme_hook = $variables['theme_hook_original'];
        $suggestions[] = $original_theme_hook . '__' . str_replace('-', '_', $variables['element']['#field_name']) . '__'  . str_replace('-', '_', $variables['element']['#view_mode']);
        $suggestions[] = $original_theme_hook . '__' . str_replace('-', '_', $variables['element']['#bundle']) . '__' . str_replace('-', '_', $variables['element']['#field_name']) . '__'  . str_replace('-', '_', $variables['element']['#view_mode']);
    }
    $suggestions =  array_unique($suggestions);
}

function govcmsui_theme_suggestions_form_element_alter(array &$suggestions, array $variables) {
    if (isset($variables['element']) && isset($variables['element']['#id']) && isset($variables['element']['#theme'])) {
        $original_theme_hook = $variables['theme_hook_original'];
        $suggestions[] = $original_theme_hook . '__' . str_replace('-', '_', $variables['element']['#id']) . '_' . str_replace('-', '_', $variables['element']['#theme']) ;
        if(isset($variables['element']['#type'])) {
            $suggestions[] = $original_theme_hook . '__' . str_replace('-', '_', $variables['element']['#id']) . '_' . str_replace('-', '_', $variables['element']['#theme']) . '_' . str_replace('-', '_', $variables['element']['#type']);
        }
    }
    $suggestions = array_unique($suggestions);
}
