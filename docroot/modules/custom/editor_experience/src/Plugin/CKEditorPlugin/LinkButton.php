<?php

/**
 * @file
 * Definition of \Drupal\editor_experience\Plugin\CKEditorPlugin\LinkButton.
 */

namespace Drupal\editor_experience\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "LinkButton" plugin.
 *
 * @CKEditorPlugin(
 *   id = "link",
 *   label = @Translation("Link")
 * )
 */
class LinkButton extends CKEditorPluginBase {
  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditoryPluginInterface::getDependencies().
   */
  function getDependencies(Editor $editor) {
    return array('dialog', 'fakeobjects');
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditoryPluginInterface::getLibraries().
   */
  function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::isInternal().
   */
  function isInternal() {
    return FALSE;
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  function getFile() {
    return drupal_get_path('module', 'editor_experience') . '/js/plugins/link/plugin.js';
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditoryPluginButtonsInterface::getButtons().
   */
  function getButtons() {
    $path = drupal_get_path('module', 'editor_experience') . '/js/plugins/link/icons';
    return array(
      'Anchor' => array(
        'label' => t('Add Anchor'),
        'image' => $path . '/anchor.png',
      ),
    );
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditoryPluginInterface::getConfig().
   */
  function getConfig(Editor $editor) {
    return array();
  }
}
