/**
 * @file
 * Module filter behaviors.
 */

(function($, Drupal) {

  'use strict';

  Drupal.ModuleFilter = Drupal.ModuleFilter || {};

  /**
   * Filter enhancements.
   */
  Drupal.behaviors.moduleFilterUpdateStatus = {
    attach: function(context, settings) {
      var $input = $('input.table-filter-text').once('module-filter');
      if ($input.length) {
        var selector = 'tbody tr';
        var wrapperId = $input.attr('data-table');
        var $wrapper = $(wrapperId);

        var $show = $('.table-filter input[name="show"]', $wrapper);
        var show = Drupal.ModuleFilter.localStorage.getItem('updateStatus.show') || 'all';

        $input.winnow(wrapperId + ' ' + selector, {
          textSelector: 'td .project-update__title a',
          emptyMessage: Drupal.t('No results'),
          clearLabel: Drupal.t('clear'),
          wrapper: $wrapper,
          buildIndex: [
            function(item) {
              if (item.element.is('.color-success')) {
                item.state = 'ok';
              }
              else if (item.element.is('.color-warning')) {
                item.state = 'warning';
              }
              else if (item.element.is('.color-error')) {
                item.state = 'error';
              }

              return item;
            }
          ],
          rules: [
            function(item) {
              switch (show) {
                case 'all':
                  return true;

                case 'updates':
                  if (item.state == 'warning' || item.state == 'error') {
                    return true;
                  }
                  break;

                case 'ignore':
                  if (item.state == 'ignored') {
                    return true;
                  }
                  break;
              }

              return false;
            }
          ]
        }).focus();
        Drupal.ModuleFilter.winnow = $input.data('winnow');

        var $titles = $('h3', $wrapper);
        $input.bind('winnow:finish', function() {
          $titles.each(function(index, element) {
            var $title = $(element);
            var $table = $title.next();
            if ($table.is('table')) {
              var $visibleRows = $table.find(selector + ':visible');
              $title.toggle($visibleRows.length > 0);
            }
          });

          Drupal.announce(
            Drupal.formatPlural(
              $wrapper.find(selector + ':visible').length,
              '1 project is available in the modified list.',
              '@count projects are available in the modified list.'
            )
          );
        });

        $show.change(function() {
          show = $(this).val();
          Drupal.ModuleFilter.localStorage.setItem('updateStatus.show', show);
          Drupal.ModuleFilter.winnow.filter();
        });
        $show.filter('[value="' + show + '"]').prop('checked', true).trigger('change');
      }
    }
  };

})(jQuery, Drupal);
