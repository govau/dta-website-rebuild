(function ($) {

  'use strict';

  /**
   * Provides the off-canvas menu.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the off-canvas menu.
   */
  Drupal.behaviors.responsive_menu_mmenu = {
    attach: function (context) {
      $(context).find('body').once('responsive-menu-mmenu').each(function() {

        if (typeof($.mmenu) !== 'undefined') {

          // Get the position and theme options from Drupal settings.
          var position = drupalSettings.responsive_menu.position,
            theme = drupalSettings.responsive_menu.theme,
            pagedim = drupalSettings.responsive_menu.pagedim,
            extension_keyboard = drupalSettings.responsive_menu.extension_keyboard;

          var settings = {
            extensions: [
              theme,
              'effect-slide-menu',
              position === 'left' ? 'position-left' : 'position-right'
            ],
            offCanvas: {
              zposition: 'next',
              position: position
            },
            screenReader: {
              aria: true,
              text: true
            }
          };

          var options = {
            clone: false,
            classNames: {
              selected: "menu__item--active-trail"
            }
          };

          if (extension_keyboard) {
            settings.keyboardNavigation = {
              enable: true,
              enhance: true
            }
          }

          if (pagedim !== 'none') {
            settings.extensions.push(pagedim);
          }

          // Set up the off canvas menu.
          $('#off-canvas').mmenu(settings, options);

          // Fix some quirks with Firefox rendering the toolbar in an odd position.
          if ($('#toolbar-administration').length > 0) {
            var api = $('#off-canvas').data('mmenu');
            api.bind("opened", function ($panel) {
              $('.toolbar-tray-vertical').css('overflow-x', 'auto');
            });
            api.bind("closed", function ($panel) {
              $('.toolbar-tray-vertical').css('overflow-x', 'hidden');
            });
          }
        }
      });
    }
  };

  /**
   * Provides additional but optional functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for superfish and hammerjs.
   */
  Drupal.behaviors.responsive_menu_optional = {
    attach: function (context, settings) {

      $(context).find('body').once('responsive-menu-optional').each(function () {

        // Apply the superfish library to the menu.
        if ($.fn.superfish && drupalSettings.responsive_menu.superfish.active) {
          // Get the superfish settings.
          var superfishDelay = drupalSettings.responsive_menu.superfish.delay,
            superfishSpeed = drupalSettings.responsive_menu.superfish.speed,
            superfishSpeedOut = drupalSettings.responsive_menu.superfish.speedOut;
          // Attach superfish to the responsive menu.
          $('#horizontal-menu').superfish({
            delay: parseInt(superfishDelay, 10),
            speed: parseInt(superfishSpeed, 10),
            speedOut: parseInt(superfishSpeedOut, 10)
          }).addClass('sf-menu');
        }

        // Add the Hammer config if needed.
        if (typeof(Hammer) !== 'undefined') {

          var mc = new Hammer($('body').get(0), {
            cssProps: {
              userSelect: true
            },
            // Reset the touchAction to auto as this allows pinch zooming.
            // mmenu handles the scrolling once open.
            touchAction: 'auto'
          });
          mc.get('swipe').set({
            velocity: 0.3,
            threshold: 5
          });

          mc.on("swipeleft swiperight", function(e) {
            // Only do something if we're below our breakpoint. The simplest
            // method is to check whether the horizontal desktop sized
            // responsive menu is hidden.
            if ($('.responsive-menu-block-wrapper').is(':hidden')) {
              hammerswipe(mc, e);
            }
          });

        }

      });
    }
  };

  /**
   * Opens or closes the mmenu based on swipe direction.
   *
   * @param mc
   *   Hammer object instance.
   * @param e
   *   Swipe event.
   */
  function hammerswipe(mc, e) {
    var position = drupalSettings.responsive_menu.position;
    var api = $('#off-canvas').data('mmenu'),
      $html = $('html');

    if (e.type == 'swiperight') {
      if (position == 'right' && $html.hasClass('mm-opened')) {
        // Close the menu.
        api.close();
      }
      if (position == 'left' && !$html.hasClass('mm-opened')) {
        // Open the menu.
        api.open();
      }
    }

    if (e.type == 'swipeleft') {
      if (position == 'right' && !$html.hasClass('mm-opened')) {
        // Open the menu.
        api.open();
      }
      if (position == 'left' && $html.hasClass('mm-opened')) {
        // Close the menu.
        api.close();
      }
    }
  }

})(jQuery);
