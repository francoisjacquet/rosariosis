/**
 * jQuery formNavigation plugin
 *
 * Navigate form inputs using arrow keys, and vertically using tab key.
 *
 * @example $('.list-wrapper .list').formNavigation();
 *
 * Based on formNavigation https://github.com/omichelsen/FormNavigation
 * @link https://stackoverflow.com/questions/38575817/set-tabindex-in-vertical-order-of-columns
 *
 * @copyright 2019 François Jacquet
 * @copyright 2013 Ole Bjørn Michelsen <http://ole.michelsen.dk/>
 * @licence MIT
 */
(function($) {
  $.fn.formNavigation = function() {
    $(this).each(function() {
      // Events triggered on keyup
      $(this).find('input').on('keyup', function(e) {
        var $td = $(this).closest('td');

        switch (e.which) {
          case 39:
            // Arrow right.
            $td.next().find('input').focus();
            break;

          case 37:
            // Arrow left.
            $td.prev().find('input').focus();
            break;

          case 40:
            // Arrow bottom.
            $(this).closest('tr').next().children().eq($td.index()).find('input').focus();
            break;

          case 38:
            // Arrow top.
            $(this).closest('tr').prev().children().eq($td.index()).find('input').focus();
            break;
        }
      });

      // Events triggered on keydown (repeatable when holding the key).
      $(this).find('input').on('keydown', function(e) {
        var $td = $(this).closest('td'),
          $tr = $(this).closest('tr'),
          $tb = $(this).closest('tbody');

        // Vertical navigation using tab as OP wanted.
        if (e.which === 9 && !e.shiftKey) {
          // Navigate forward.
          if ($tr.next().find('input').length > 0) {
            // When there is another row below.
            e.preventDefault();
            $tr.next().children().eq($td.index()).find('input').focus();
          } else if ($tb.find('tr:first').children().eq($td.index() + 1).find('input').length > 0) {
            // When last row reached.
            e.preventDefault();
            $tb.find('tr:first').children().eq($td.index() + 1).find('input').focus();
          }
        } else if (e.which === 9 && e.shiftKey) {
          // Navigate backward.
          if ($tr.prev().find('input').length > 0) {
            // When there is another row above.
            e.preventDefault();
            $tr.prev().children().eq($td.index()).find('input').focus();
          } else if ($tb.find('tr:last').children().eq($td.index() - 1).find('input').length > 0) {
            // When first row reached.
            e.preventDefault();
            $tb.find('tr:last').children().eq($td.index() - 1).find('input').focus();
          }
        }
      });
    });
  };
})(jQuery);
