(function ($) {

  "use strict";

  Drupal.behaviors.environmentIndicatorSwitcher = {
    attach: function (context, settings) {
      $('#environment-indicator', context).bind('click', function () {
        $('#environment-indicator .environment-switcher-container', context).slideToggle('fast');
      });
    }
  };

  Drupal.behaviors.environmentIndicatorToolbar = {
    attach: function (context, settings) {
      if (typeof(settings.environmentIndicator) != 'undefined') {
        $('#toolbar-bar', context).css('background-color', settings.environmentIndicator.bgColor);
        $('#toolbar-bar .toolbar-item, #toolbar-bar .toolbar-item a', context).css('color', settings.environmentIndicator.fgColor);
      }
    }
  };

  Drupal.behaviors.environmentIndicatorTinycon = {
    attach: function (context, settings) {
      if (typeof(settings.environmentIndicator) != 'undefined' &&
        typeof(settings.environmentIndicator.addFavicon) != 'undefined' &&
        settings.environmentIndicator.addFavicon) {
        // Draw favicon label.
        Tinycon.setBubble(settings.environmentIndicator.name.slice(0, 1));
        Tinycon.setOptions({
          background: settings.environmentIndicator.bgColor,
          colour: settings.environmentIndicator.fgColor
        });
      }
    }
  }

})(jQuery);
