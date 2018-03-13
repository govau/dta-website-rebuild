<?php

namespace Drupal\ckeditor_tabletoolstoolbar\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Table Tools Toolbar" plugin.
 *
 * @CKEditorPlugin(
 *   id = "tabletoolstoolbar",
 *   label = @Translation("Table Tools Toolbar")
 * )
 */
class CkeditorTableToolsToolbar extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getPluginPath() . '/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'tableinsert' => [
        'label' => $this->t('Add new table'),
        'image' => $this->getPluginPath() . '/icons/tableinsert.png',
      ],
      'tabledelete' => [
        'label' => $this->t('Delete current table'),
        'image' => $this->getPluginPath() . '/icons/tabledelete.png',
      ],
      'tableproperties' => [
        'label' => $this->t('Show dialog with table properties'),
        'image' => $this->getPluginPath() . '/icons/tableproperties.png',
      ],
      'tablerowinsertbefore' => [
        'label' => $this->t('Add row above current cell'),
        'image' => $this->getPluginPath() . '/icons/tablerowinsertbefore.png',
      ],
      'tablerowinsertafter' => [
        'label' => $this->t('Add row under current cell'),
        'image' => $this->getPluginPath() . '/icons/tablerowinsertafter.png',
      ],
      'tablerowdelete' => [
        'label' => $this->t('Delete row with current cell'),
        'image' => $this->getPluginPath() . '/icons/tablerowdelete.png',
      ],
      'tablecolumninsertbefore' => [
        'label' => $this->t('Add column before current cell'),
        'image' => $this->getPluginPath() . '/icons/tablecolumninsertbefore.png',
      ],
      'tablecolumninsertafter' => [
        'label' => $this->t('Add column after current cell'),
        'image' => $this->getPluginPath() . '/icons/tablecolumninsertafter.png',
      ],
      'tablecolumndelete' => [
        'label' => $this->t('Delete column with current cell'),
        'image' => $this->getPluginPath() . '/icons/tablecolumndelete.png',
      ],
      'tablecellinsertbefore' => [
        'label' => $this->t('Add cell before current cell'),
        'image' => $this->getPluginPath() . '/icons/tablecellinsertbefore.png',
      ],
      'tablecellinsertafter' => [
        'label' => $this->t('Add cell after current cell'),
        'image' => $this->getPluginPath() . '/icons/tablecellinsertafter.png',
      ],
      'tablecelldelete' => [
        'label' => $this->t('Delete current cell'),
        'image' => $this->getPluginPath() . '/icons/tablecelldelete.png',
      ],
      'tablecellproperties' => [
        'label' => $this->t('Show dialog with current cell properties'),
        'image' => $this->getPluginPath() . '/icons/tablecellproperties.png',
      ],
      'tablecellsmerge' => [
        'label' => $this->t('Merge selected cells'),
        'image' => $this->getPluginPath() . '/icons/tablecellsmerge.png',
      ],
      'tablecellmergeright' => [
        'label' => $this->t('Merge current cell with right cell'),
        'image' => $this->getPluginPath() . '/icons/tablecellmergeright.png',
      ],
      'tablecellmergedown' => [
        'label' => $this->t('Merge current cell with down cell'),
        'image' => $this->getPluginPath() . '/icons/tablecellmergedown.png',
      ],
      'tablecellsplithorizontal' => [
        'label' => $this->t('Split horizontal current cell'),
        'image' => $this->getPluginPath() . '/icons/tablecellsplithorizontal.png',
      ],
      'tablecellsplitvertical' => [
        'label' => $this->t('Split vertical current cell'),
        'image' => $this->getPluginPath() . '/icons/tablecellsplitvertical.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * Return ckeditor tabletoolstoolbar plugin path relative to drupal root.
   *
   * @return string
   *   Relative path to the ckeditor plugin folder
   */
  private function getPluginPath() {
    return 'libraries/tabletoolstoolbar';
  }

}
