(function($, Drupal) {

  'use strict';

  Drupal.ModuleFilter = Drupal.ModuleFilter || {};

  Drupal.ModuleFilter.localStorage = {
    getItem: function(key) {
      if (typeof Storage !== 'undefined') {
        return localStorage.getItem('moduleFilter.' + key);
      }

      return null;
    },
    getBoolean: function(key) {
      var item = Drupal.ModuleFilter.localStorage.getItem(key);

      if (item != null) {
        return (item == 'true');
      }

      return null;
    },
    setItem: function(key, data) {
      if (typeof Storage !== 'undefined') {
        localStorage.setItem('moduleFilter.' + key, data)
      }
    },
    removeItem: function(key) {
      if (typeof Storage !== 'undefined') {
        localStorage.removeItem('moduleFilter.' + key);
      }
    }
  };

  /**
   * Filter enhancements.
   */
  Drupal.behaviors.moduleFilter = {
    attach: function(context) {

    }
  };

})(jQuery, Drupal);
