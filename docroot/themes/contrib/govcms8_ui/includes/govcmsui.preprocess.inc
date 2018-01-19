<?php

/**
 * @file
 * Functions to support theming in the govCMS theme.
 */

use Drupal\file\Entity\File;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Implements hook_preprocess_HOOK() for page templates.
 */
function govcmsui_preprocess_page(&$variables) {
  // Load the site name out of configuration.
  $config = \Drupal::config('system.site');
  $variables['site_name'] = $config->get('name');
  $variables['site_slogan'] = $config->get('slogan');

  // This is a cheeky workaround to make sure the sidebar classes get set correctly, I don't think it's fool proof though.. e.g. empty views.
  if (!empty($variables['page']['sidebar_second'])) {
    $variables['sidebar_second_exists'] = TRUE;
  }
  // This is a cheeky workaround to make sure the sidebar classes get set correctly, I don't think it's fool proof though.. e.g. empty views.
  if (!empty($variables['page']['sidebar_first'])) {
    $variables['sidebar_first_exists'] = TRUE;
  }

  $coa_footer = theme_get_setting('govcmsui_coa_footer');
  if (!empty($coa_footer) && $coa_footer == 1) {
    $variables['coa_bottom'] = '/' . drupal_get_path('theme',
        'govcmsui') . '/img/coas-dark.svg';
  }

  $footer_copyright = theme_get_setting('govcmsui_footer_copyright');
  if (!empty($footer_copyright)) {
    $variables['footer_copyright'] = $footer_copyright;
  }
}

function govcmsui_preprocess_block(&$variables) {
  if (isset($variables['attributes']['id']) && !empty($variables['attributes']['id'])) {
    $variables['content']['#attributes']['block'] = $variables['attributes']['id'];
  }
  if (isset($variables['elements']['#id']) && !empty($variables['elements']['#id'])) {
    $block_id = $variables['elements']['#id'];
    $block = \Drupal\block\Entity\Block::load($block_id);

    if (!empty($block)) {
      $variables['content']['#attributes']['region'] = $block->getRegion();
    }
  }

  $coa = theme_get_setting('govcmsui_coa');
  if (!empty($coa)) {
    $variables['govlogo'] = '/' . drupal_get_path('theme',
        'govcmsui') . '/img/' . $coa;
  }
  if (!empty($variables['elements']['#id']) && $variables['base_plugin_id'] == 'views_exposed_filter_block') {
    $variables['attributes']['id'] = 'search';
  }
}

function govcmsui_preprocess_menu(&$variables) {
  if (isset($variables['attributes']['region'])) {
    $variables['content']['#attributes']['region'] = $variables['attributes']['region'];
  }
}

function govcmsui_preprocess_menu__main(&$variables) {
  if (isset($variables['attributes']['region'])) {
    $variables['content']['#attributes']['region'] = $variables['attributes']['region'];
  }

  $nav_justified = theme_get_setting('govcmsui_navbar_justified');
  if (!empty($nav_justified) && $nav_justified == 1) {
    $variables['attributes']['class'][] = 'nav-justified';
  }
}

function govcmsui_preprocess_node(&$variables) {
  if (isset($variables['view_mode'])) {
    $variables['attributes']['class'][] = 'view-mode-' . $variables['view_mode'];
  }
  $date = $variables['node']->getCreatedTime();
  $updated = $variables['node']->getChangedTime();
  $variables['date'] = \Drupal::service('date.formatter')
    ->formatInterval(REQUEST_TIME - $date);
  $variables['date_timestamp'] = date("Y-m-d\TH:i:s", $date);
  $variables['date_readable'] = date("j F Y g:i A", $date);

  if ($date != $updated) {
    $variables['date_updated'] = \Drupal::service('date.formatter')
      ->formatInterval(REQUEST_TIME - $updated);
    $variables['date_updated_timestamp'] = date("Y-m-d\TH:i:s", $updated);
    $variables['date_updated_readable'] = date("j F Y g:i A", $updated);
  }
}

function govcmsui_preprocess_file_link(&$variables) {
  $file = $variables['file'];
  $options = [];

  $file_entity = ($file instanceof File) ? $file : File::load($file->fid);
  $url = file_create_url($file_entity->getFileUri());
  $variables['#cache']['contexts'][] = 'url.site';

  $mime_type = $file->getMimeType();
  // Set options as per anchor format described at
  // http://microformats.org/wiki/file-format-examples
  $options['attributes']['type'] = $mime_type . '; length=' . $file->getSize();

  // Use the description as the link text if available.
  if (empty($variables['description'])) {
    $link_text = $file_entity->getFilename();
  }
  else {
    $link_text = $variables['description'];
    $options['attributes']['title'] = $file_entity->getFilename();
  }

  $icon_class = _govcmsui_file_icon_map($mime_type);

  // Set file classes to the options array.
  $variables['attributes'] = new Attribute($variables['attributes']);
  $variables['link'] = \Drupal::l($link_text, Url::fromUri($url, $options));

  $variables['icon'] = [
    '#type' => 'container',
    '#prefix' => '<i class="fa fa-' . $icon_class . '-o">',
    '#suffix' => '</i>',
  ];
  $file_size = $file_entity->getSize();
  if (isset($file_size) && $file_size > 0) {
    $file_size = format_size($file_size);
    $variables['size'] = [
      '#type' => 'container',
      '#prefix' => '<small class="meta">(' . $file_size . ')',
      '#suffix' => '</small>',
    ];
  }
}

function _govcmsui_file_icon_map($mime_type) {
  // List of official MIME Types: http://www.iana.org/assignments/media-types/media-types.xhtml
  static $font_awesome_file_icon_classes = [
    // Images
    'image' => 'file-image',
    // Audio
    'audio' => 'file-audio',
    // Video
    'video' => 'file-video',
    // Documents
    'application/pdf' => 'file-pdf',
    'text/plain' => 'file-text',
    'text/html' => 'file-code',
    'application/json' => 'file-code',
    // Archives
    'application/gzip' => 'file-archive',
    'application/zip' => 'file-archive',
    // Misc
    'application/octet-stream' => 'file',
  ];
  if (isset($font_awesome_file_icon_classes[$mime_type])) {
    return $font_awesome_file_icon_classes[$mime_type];
  }
  $mime_parts = explode('/', $mime_type, 2);
  $mime_group = $mime_parts[0];
  if (isset($font_awesome_file_icon_classes[$mime_group])) {
    return $font_awesome_file_icon_classes[$mime_group];
  }

  return "file";
}
