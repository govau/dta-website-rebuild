(function($) {

  // 'use strict';

  Drupal.ModuleFilter = Drupal.ModuleFilter || {};
  var ModuleFilter = Drupal.ModuleFilter;

  var Tabs = function(tabs, $pane) {
    var $tabs = $('<ul class="modules-tabs__menu"></ul>');

    // Add our three special tabs.
    var all = new Tab(Drupal.t('All modules'), 'all');
    var recentModules = new Tab(Drupal.t('Recently enabled'), 'recent');
    var newModules = new Tab(Drupal.t('Newly available'), 'new');
    tabs = $.extend({
      "all": all,
      "recent": recentModules,
      "new": newModules
    }, tabs);

    for (var i in tabs) {
      $tabs.append(tabs[i].element);
    }

    $pane.wrap('<div class="modules-tabs clearfix"></div>');
    $pane.before($tabs);
    $pane.addClass('modules-tabs__pane');

    this.tabs = tabs;
    this.element = $tabs;
    this.activeTab = null;

    // Handle "recent" and "new" tabs when they contain no items.
    // Todo: move this somewhere else.
    var $rows = $(ModuleFilter.selector, ModuleFilter.wrapper);
    if (!$rows.filter('.recent').length) {
      var recentModules = this.tabs['recent'];
      if (recentModules) {
        recentModules.element.addClass('disabled');
        recentModules.setSummary(Drupal.t('No modules installed or uninstalled within the last week.'));
        recentModules.showSummary();
      }
    }
    if (!$rows.filter('.new').length) {
      var newModules = this.tabs['new'];
      if (newModules) {
        newModules.element.addClass('disabled');
        newModules.setSummary(Drupal.t('No modules added within the last week.'));
        newModules.showSummary();
      }
    }

    // Add counts of how many modules are enabled out of the total for the tab.
    // Todo: move this somewhere else.
    var $elements = $(ModuleFilter.selector, ModuleFilter.wrapper);
    var enabled;
    var total;
    for (var i in this.tabs) {
      if (this.tabs[i].element.hasClass('disabled')) {
        continue;
      }

      switch (i) {
        case 'all':
          var $all = $elements.find('td.checkbox :checkbox');
          enabled = $all.filter(':checked').length;
          total = $all.length;
          break;

        case 'recent':
          var $recent = $elements.filter('.recent').find('td.checkbox :checkbox');
          enabled = $recent.filter(':checked').length;
          total = $recent.length;
          break;

        case 'new':
          var $new = $elements.filter('.new').find('td.checkbox :checkbox');
          enabled = $new.filter(':checked').length;
          total = $new.length;
          break;

        default:
          var $package = $elements.filter('.package__' + i).find(' td.checkbox :checkbox');
          enabled = $package.filter(':checked').length;
          total = $package.length;
          break;
      }

      if (total) {
        var enabledCount = Drupal.t('@enabled of @total', { '@enabled': enabled, '@total': total });
        this.tabs[i].setSummary(enabledCount, 'enabledCount');
      }
    }
  };

  Tabs.prototype.getActive = function() {
    if (this.activeTab) {
      return this.activeTab;
    }
  };

  Tabs.prototype.setActive = function(tab) {
    if (this.activeTab) {
      this.activeTab.hideSummary();
      this.activeTab.element.removeClass('is-selected');
    }

    this.activeTab = tab;
    this.activeTab.element.addClass('is-selected');
    this.activeTab.showSummary();

    return this.activeTab;
  };

  Tabs.prototype.get = function(packageId) {
    if (this.tabs[packageId]) {
      return this.tabs[packageId];
    }
  };

  Tabs.prototype.resetResults = function() {
    for (var i in this.tabs) {
      this.tabs[i].resetResults();
    }
  };

  Tabs.prototype.showResults = function() {
    var staticTabs = [ 'all', 'recent', 'new' ];

    for (var i in this.tabs) {
      var count = this.tabs[i].results.length;

      if (count > 0 || i == this.activeTab.packageId || staticTabs.indexOf(i) >= 0) {
        this.tabs[i].showResults();
        this.tabs[i].element.show();
      }
      else {
        this.tabs[i].element.hide();
      }
    }
  };

  Tabs.prototype.hideResults = function() {
    for (var i in this.tabs) {
      this.tabs[i].hideResults();
      this.tabs[i].element.show();
    }
  };

  var Tab = function(name, packageId) {
    this.name = name;
    this.packageId = packageId;
    this.element = $('<li class="modules-tabs__menu-item tab__' + this.packageId + '"></li>');
    this.results = [];
    this.link = $('<a href="#' + this.packageId + '"><strong>' + this.name + '</strong></a>');
    this.element.append(this.link);
    this.link.append('<span class="result"></span>');
    this.summary = null;
  };

  Tab.prototype.select = function() {
    ModuleFilter.tabs.setActive(this);

    if (ModuleFilter.winnow) {
      ModuleFilter.winnow.filter();
    }
  };

  Tab.prototype.resetResults = function() {
    this.results = [];
  };

  Tab.prototype.showResults = function() {
    $('span.result', this.element).text(this.results.length);
  };

  Tab.prototype.hideResults = function() {
    $('span.result', this.element).empty();
  };

  Tab.prototype.setSummary = function(summary, key, persistent) {
    if (!this.summary) {
      this.summary = new Summary();
      this.link.append(this.summary.element);
    }

    this.summary.set(summary, key, persistent);
  };

  Tab.prototype.showSummary = function() {
    this.toggleSummary(true);
  };

  Tab.prototype.hideSummary = function() {
    this.toggleSummary(false);
  };

  Tab.prototype.toggleSummary = function(display) {
    if (this.summary) {
      this.summary.toggle(Boolean(display));
    }
  };

  Tab.prototype.toggleEnabling = function(name) {
    this.enabling = this.enabling || {};
    if (this.enabling[name] != undefined) {
      delete this.enabling[name];
    }
    else {
      this.enabling[name] = name;
    }

    var enabling = [];
    for (var i in this.enabling) {
      enabling.push(this.enabling[i]);
    }

    $('ul.enabling', this.element).remove();
    if (enabling.length) {
      enabling.sort();

      var $list = $('<ul class="item-list__comma-list enabling"></ul>');
      $list.append('<li>' + enabling.join('</li><li>') + '</li>');
      this.setSummary($list, 'enabling', true);
    }
    else {
      this.setSummary('', 'enabling');
    }
  };

  var Summary = function() {
    this.element = $('<div class="summary"></div>');
    this.element.hide();
    this.items = {};
  };

  Summary.prototype.show = function() {
    this.toggle(true);
  };

  Summary.prototype.hide = function() {
    this.toggle(false);
  };

  Summary.prototype.toggle = function(display) {
    display = Boolean(display);

    this.element.children(':not(.persistent)').toggle(display);

    if (!display && !this.element.children('.persistent').length) {
      this.element.hide();
    }
    else {
      this.element.show();
    }
  };

  Summary.prototype.set = function(summary, key, persistent) {
    if (!key) {
      key = 'default';
    }

    var empty = false;
    if (typeof summary == 'string' || typeof summary == 'boolean') {
      if (!summary) {
        empty = true;
      }
    }
    else if (typeof summary == 'object') {
      // Assume the object is a jQuery object.
      if (!summary.length) {
        empty = true;
      }
    }
    if (empty) {
      if (this.items[key] != undefined) {
        this.items[key].remove();
        delete this.items[key];
      }

      if (!Object.keys(this.items).length) {
        // Hide the summary when there are no items.
        this.hide();
      }
      return;
    }

    if (persistent == undefined) {
      persistent = false;
    }

    if (this.items[key] === undefined) {
      var $element = $('<span></span>');
      this.element.append($element);
      this.items[key] = $element;
    }

    this.items[key].empty().append(summary);
    this.items[key].toggleClass('persistent', persistent);

    if (persistent) {
      // Make sure the summary element is visible.
      this.show();
    }
  };

  Drupal.behaviors.moduleFilterModulesTabs = {
    attach: function(context) {
      if (ModuleFilter.input != undefined) {
        var tabs = {};

        function buildTable() {
          // Build our unified table.
          var $originalTable = $('table', ModuleFilter.wrapper).first();
          var $table = $('<table></table>');
          if ($originalTable.hasClass('responsive-enabled')) {
            $table.addClass('responsive-enabled');
          }
          var striping = $originalTable.attr('data-striping')
          if (striping) {
            $table.attr('data-striping', striping);
          }

          // Because the table headers are visually hidden, we use col to set
          // the column widths.
          var $colgroup = $('<colgroup></colgroup>');
          $('thead th', $originalTable).each(function() {
            $colgroup.append('<col class="' + $(this).attr('class') + '">');
          });
          $('col', $colgroup).removeClass('visually-hidden');
          $table.append($colgroup);
          $table.append($('thead', $originalTable));
          $table.append('<tbody></tbody>');

          ModuleFilter.modulesWrapper.children('details').each(function() {
            var $details = $(this);
            var packageName = $details.children('summary').text();
            var packageId = packageName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

            if (tabs[packageId] == undefined) {
              tabs[packageId] = new Tab(packageName, packageId);
            }

            $('.details-wrapper tbody tr', $details).each(function() {
              var $row = $(this);
              $row.addClass('package__' + packageId);
              $row.data('moduleFilter.packageId', packageId);

              $row.hover(function() {
                tabs[packageId].element.addClass('suggest');
              }, function() {
                tabs[packageId].element.removeClass('suggest');
              });

              $('td.checkbox input', $row).change(function() {
                $row.toggleClass('enabling', $(this).is(':checked'));

                var packageId = $row.data('moduleFilter.packageId');
                if (packageId && tabs[packageId]) {
                  tabs[packageId].toggleEnabling($('td.module label', $row).text());
                }
              });

              $('tbody', $table).append($row);
            });
          });

          // Remove package detail elements.
          ModuleFilter.modulesWrapper.children('details').remove();

          // Sort rows by module name.
          var $rows = $('tbody tr', $table);
          $rows.sort(function(a, b) {
            var aname = $('td.module label', a).text();
            var bname = $('td.module label', b).text();

            if (aname == bname) {
              return 0;
            }

            return aname > bname ? 1 : -1;
          });
          $rows.detach().appendTo($('tbody', $table));

          // Add the unified table.
          ModuleFilter.modulesWrapper.append($table);
        }

        function selectTabByHash() {
          var hash = window.location.hash;
          hash = hash.substring(hash.indexOf('#') + 1);

          var tab = ModuleFilter.tabs.get(hash);
          if (tab) {
            tab.select();
          }
          else {
            tab = ModuleFilter.tabs.get('all');
            if (tab) {
              tab.select();
            }
          }

          ModuleFilter.input.focus();
        }

        buildTable();
        ModuleFilter.tabs = new Tabs(tabs, ModuleFilter.wrapper);

        ModuleFilter.winnow.options.rules.push(function(item) {
          var activeTab = ModuleFilter.tabs.getActive();

          // Update tab results. The results are updated prior to hiding the
          // items not visible in the active tab.
          var allTab = ModuleFilter.tabs.get('all');
          allTab.results.push(item);
          if (item.element.hasClass('recent')) {
            var recentTab = ModuleFilter.tabs.get('recent');
            recentTab.results.push(item);
          }
          if (item.element.hasClass('new')) {
            var newTab = ModuleFilter.tabs.get('new');
            newTab.results.push(item);
          }
          if (item.tab != undefined && item.tab) {
            item.tab.results.push(item);
          }

          // For tabs other than "all", evaluate whether the item should
          // be shown.
          if (activeTab && activeTab.packageId != 'all') {
            switch (activeTab.packageId) {
              case 'recent':
                if (item.element.hasClass('recent')) {
                  return true;
                }
                break;

              case 'new':
                if (item.element.hasClass('new')) {
                  return true;
                }
                break;

              default:
                if (item.element.hasClass('package__' + activeTab.packageId)) {
                  return true;
                }
                break;
            }

            return false;
          }
        });
        ModuleFilter.winnow.bind('finishIndexing', function(e, winnow) {
          $.each(winnow.index, function(key, item) {
            var packageId = item.element.data('moduleFilter.packageId');
            if (packageId) {
              item.tab = ModuleFilter.tabs.get(packageId);
            }
          });
        });
        ModuleFilter.winnow.bind('start', function() {
          ModuleFilter.tabs.resetResults();
        });
        ModuleFilter.winnow.bind('finish', function() {
          if (ModuleFilter.input.val() != '') {
            ModuleFilter.tabs.showResults();
          }
          else {
            ModuleFilter.tabs.hideResults();
          }
        });

        $(window).bind('hashchange.moduleFilter', selectTabByHash).triggerHandler('hashchange.moduleFilter');
      }
    }
  };

})(jQuery);
