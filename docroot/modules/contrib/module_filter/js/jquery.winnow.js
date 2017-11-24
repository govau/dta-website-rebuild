/**
 * jQuery plugin
 * Filter out elements based on user input.
 */

(function ($) {

  var now = Date.now || function() {
      return new Date().getTime();
    };

  function debounce(func, wait, immediate) {
    var timeout;
    var args;
    var context;
    var timestamp;
    var result;

    var later = function() {
      var last = now() - timestamp;

      if (last < wait && last >= 0) {
        timeout = setTimeout(later, wait - last);
      }
      else {
        timeout = null;
        if (!immediate) {
          result = func.apply(context, args);
          if (!timeout) {
            args = context = null;
          }
        }
      }
    };

    return function() {
      context = this;
      args = arguments;
      timestamp = now();
      var callNow = immediate && !timeout;
      if (!timeout) {
        timeout = setTimeout(later, wait);
      }
      if (callNow) {
        result = func.apply(context, args);
        args = context = null;
      }

      return result;
    }
  }

  function explode(string) {
    return string.match(/(\w*\:(\w+|"[^"]+")*)|\w+|"[^"]+"/g);
  }

  function preventEnterKey(e) {
    if (e.which === 13) {
      e.preventDefault();
      e.stopPropagation();
    }
  };

  var Winnow = function(element, selector, options) {
    var self = this;

    self.element = element;
    self.selector = selector;
    self.text = '';
    self.queries = [];
    self.results = [];
    self.state = {};

    self.options = $.extend({
      delay: 500,
      striping: false,
      selector: '',
      textSelector: null,
      emptyMessage: '',
      clearLabel: 'clear',
      rules: [],
      buildIndex: [],
      additionalOperators: {}
    }, $.fn.winnow.defaults, options);
    if (self.options.wrapper === undefined) {
      self.options.wrapper = $(self.selector).parent();
    }

    self.element.wrap('<div class="winnow-input"></div>');

    // Add clear button.
    self.clearButton = $('<a href="#" class="winnow-clear">' + self.options.clearLabel + '</a>');
    self.clearButton.css({
      'display': 'inline-block',
      'margin-left': '0.75em'
    });
    if (self.element.val() == '') {
      self.clearButton.hide();
    }
    self.clearButton.click(function(e) {
      e.preventDefault();

      self.clearFilter();
    });
    self.element.after(self.clearButton);

    self.element.on({
      keyup: debounce(function() {
        var value = self.element.val();
        if (!value || explode(value).pop().slice(-1) !== ':') {
          // Only filter if we aren't using the operator autocomplete.
          self.filter();
        }
      }, self.options.delay),
      keydown: preventEnterKey
    });
    self.element.on({
      keyup: function() {
        // Show/hide the clear button.
        if (self.element.val() != '') {
          self.clearButton.show();
        }
        else {
          self.clearButton.hide();
        }
      }
    });

    // Autocomplete operators. When last query is ":", return list of available
    // operators with the exception of "text".
    if (typeof self.element.autocomplete === 'function') {
      var operators = Object.keys(self.getOperators());
      var source = [];
      for (var i in operators) {
        if (operators[i] != 'text') {
          source.push({
            label: operators[i],
            value: operators[i] + ':'
          });
        }
      }

      self.element.autocomplete({
        'search': function(event) {
          if (explode(event.target.value).pop() != ':') {
            return false;
          }
        },
        'source': function(request, response) {
          return response(source);
        },
        'select': function(event, ui) {
          var terms = explode(event.target.value);
          // Remove the current input.
          terms.pop();
          // Add the selected item.
          terms.push(ui.item.value);
          event.target.value = terms.join(' ');
          // Return false to tell jQuery UI that we've filled in the value already.
          return false;
        },
        'focus': function() {
          return false;
        }
      });
    }

    self.element.data('winnow', self);
  };

  Winnow.prototype.setQueries = function(string) {
    var self = this;
    var strings = explode(string);

    self.text = string;
    self.queries = [];

    for (var i in strings) {
      if (strings.hasOwnProperty(i)) {
        var query = { operator: 'text', string: strings[i] };
        var operators = self.getOperators();

        if (query.string.indexOf(':') > 0) {
          var parts = query.string.split(':', 2);
          var operator = parts.shift();
          if (operators[operator] !== undefined) {
            query.operator = operator;
            query.string = parts.shift();
          }
        }

        if (query.string.charAt(0) == '"') {
          // Remove wrapping double quotes.
          query.string = query.string.replace(/^"|"$/g, '');
        }

        query.string = query.string.toLowerCase();

        self.queries.push(query);
      }
    }
  };

  Winnow.prototype.buildIndex = function() {
    var self = this;
    this.index = [];

    $(self.selector, self.wrapper).each(function(i) {
      var text = (self.options.textSelector) ? $(self.options.textSelector, this).text() : $(this).text();
      var item = {
        key: i,
        element: $(this),
        text: text.toLowerCase()
      };

      for (var j in self.options.buildIndex) {
        item = $.extend(self.options.buildIndex[j].apply(this, [item]), item);
      }

      $(this).data('winnowIndex', i);
      self.index.push(item);
    });

    return self.trigger('finishIndexing', [ self ]);
  };

  Winnow.prototype.bind = function() {
    var args = arguments;
    args[0] = 'winnow:' + args[0];

    return this.element.bind.apply(this.element, args);
  };

  Winnow.prototype.trigger = function(event) {
    var args = arguments;
    args[0] = 'winnow:' + args[0];

    return this.element.trigger.apply(this.element, args);
  };

  Winnow.prototype.filter = function() {
    var self = this;

    self.results = [];
    self.setQueries(self.element.val());

    if (self.index === undefined) {
      self.buildIndex();
    }

    var start = self.trigger('start');

    $.each(self.index, function(key, item) {
      var $item = item.element;
      var operatorMatch = true;

      if (self.text != '') {
        operatorMatch = false;
        for (var i in self.queries) {
          var query = self.queries[i];
          var operators = self.getOperators();

          if (operators[query.operator] !== undefined) {
            result = operators[query.operator].apply($item, [query.string, item]);
            if (!result) {
              // Is not a text match so continue to next query string.
              continue;
            }
          }

          operatorMatch = true;
          break;
        }
      }

      if (operatorMatch && self.processRules(item) !== false) {
        // Item is a match.
        $item.show();
        self.results.push(item);
        return true;
      }

      // By reaching here, the $item is not a match so we hide it.
      $item.hide();
    });

    var finish = self.trigger('finish', [ self.results ]);

    if (self.options.striping) {
      stripe();
    }

    if (self.options.emptyMessage) {
      if (self.results.length > 0) {
        self.options.wrapper.children('.winnow-no-results').remove();
      }
      else if (!self.options.wrapper.children('.winnow-no-results').length) {
        self.options.wrapper.append($('<p class="winnow-no-results"></p>').text(self.options.emptyMessage));
      }
    }
  };

  Winnow.prototype.getOperators = function() {
    return $.extend({}, {
      text: function(string, item) {
        if (item.text.indexOf(string) >= 0) {
          return true;
        }
      }
    }, this.options.additionalOperators);
  };

  Winnow.prototype.processRules = function(item) {
    var self = this;
    var $item = item.element;
    var result = true;

    if (self.options.rules.length > 0) {
      for (var i in self.options.rules) {
        result = self.options.rules[i].apply($item, [item]);
        if (result === false) {
          break;
        }
      }
    }

    return result;
  };

  Winnow.prototype.stripe = function() {
    var flip = { even: 'odd', odd: 'even' };
    var stripe = 'odd';

    $.each(this.index, function(key, item) {
      if (!item.element.is(':visible')) {
        item.element.removeClass('odd even').addClass(stripe);
        stripe = flip[stripe];
      }
    });
  };

  Winnow.prototype.clearFilter = function() {
    this.element.val('');
    this.filter();
    this.clearButton.hide();
    this.element.focus();
  };

  $.fn.winnow = function(selector, options) {
    var $input = this.not('.winnow-processed').addClass('winnow-processed');

    $input.each(function() {
      var winnow = new Winnow($input, selector, options);
    });

    return this;
  };

  $.fn.winnow.defaults = {};

})(jQuery);
