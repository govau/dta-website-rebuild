<?php

/**
 * @file
 * Definition of \Drupal\editor_experience\Plugin\CKEditorPlugin\Dialog.
 */

namespace Drupal\editor_experience\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Dialog" plugin.
 *
 * @CKEditorPlugin(
 *   id = "dialog",
 *   label = @Translation("Dialog")
 * )
 */
class Dialog extends CKEditorPluginBase {
  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditoryPluginInterface::getDependencies().
   */
  function getDependencies(Editor $editor) {
    return array('dialogui');
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
    return drupal_get_path('module', 'editor_experience') . '/js/plugins/dialog/plugin.js';
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  function getButtons() {
    return array();
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditoryPluginInterface::getConfig().
   */
  function getConfig(Editor $editor) {
    return array();
  }
}
