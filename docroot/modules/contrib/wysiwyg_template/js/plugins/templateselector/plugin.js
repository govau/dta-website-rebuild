/**
 * @file
 * Drupal WYSIWYG template selector.
 *
 * @ignore
 */

(function ($, Drupal, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('templateselector', {
    requires: 'templates',
    icons: 'templateselector,templateselector-rtl',
    hidpi: true,

    beforeInit: function (editor) {

    },

    init: function (editor) {
      // Register the toolbar button.
      if (editor.ui.addButton) {
        editor.ui.addButton('TemplateSelector', {
          label: Drupal.t('Insert template'),
          command: 'templates'
        });
      }
    }
  });

})(jQuery, Drupal, CKEDITOR);

// Specify path to templates.
CKEDITOR.config.templates_files = [
  CKEDITOR.getUrl('/wysiwyg-templates/js')
];
