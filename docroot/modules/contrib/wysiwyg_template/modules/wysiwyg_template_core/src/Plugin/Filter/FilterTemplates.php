<?php
/**
 * @file
 * Contains \Drupal\wysiwyg_template_core\Plugin\Filter\FilterTemplates.php
 */

namespace Drupal\wysiwyg_template_core\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to remove WYSIWYG-specific markup from rendering.
 *
 * @Filter(
 *   id = "filter_wysiwyg_cleanup",
 *   title = @Translation("Cleanup WYSIWYG templates"),
 *   description = @Translation("Wysiwyg templates can contain code and attributes that are important for editing but should be removed on public pages."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 *   weight = 10
 * )
 */
class FilterTemplates extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // @todo While this was how the filter worked in 7.x, this should be ported
    // to use the attribute filtering logic in core.
    // @see \Drupal\filter\Plugin\Filter\FilterHtml
    $text = str_replace(['contenteditable="true"', 'contenteditable="false"'], '', $text);
    return new FilterProcessResult($text);
  }

}
