/**
 * @file
 * Module filter behaviors.
 */

(function($, Drupal) {

  'use strict';

  /**
   * Filter enhancements.
   */
  Drupal.behaviors.moduleFilterModulesUninstall = {
    attach: function(context, settings) {
      var $input = $('input.table-filter-text', context).once('module-filter');
      if ($input.length) {
        var wrapperId = $input.attr('data-table');
        var $wrapper = $(wrapperId);
        var selector = 'tbody tr';

        $wrapper.children('details').wrapAll('<div class="modules-uninstall-wrapper"></div>');
        var $modulesWrapper = $('.modules-uninstall-wrapper', $wrapper);

        $input.winnow(wrapperId + ' ' + selector, {
          textSelector: 'td .module-name',
          emptyMessage: Drupal.t('No results'),
          clearLabel: Drupal.t('clear'),
          wrapper: $modulesWrapper,
          additionalOperators: {
            description: function(string, item) {
              if (item.description == undefined) {
                // Soft cache.
                item.description = $('.module-description', item.element).text().toLowerCase();
              }

              if (item.description.indexOf(string) >= 0) {
                return true;
              }
            }
          }
        }).focus();

        $input.bind('winnow:finish', function() {
          Drupal.announce(
            Drupal.formatPlural(
              $modulesWrapper.find(selector + ':visible').length,
              '1 module is available in the modified list.',
              '@count modules are available in the modified list.'
            )
          );
        });
      }
    }
  };

})(jQuery, Drupal);
