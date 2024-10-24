/**
 * @file
 * JS for button controls.
 */
(function ($, Drupal, once) {
  Drupal.behaviors.expandingText = {
    attach: function(context, settings) {
      const $expanding_text = $(once('expanding-text', '.js-expanding-text', context));
      if (!$expanding_text.length) {
        return;
      }

      const checkExpandingText = function() {
        $expanding_text.each(function () {
          const $text = $(this).find('.text-formatted');
          if (!$text.length) {
            return;
          }
          const $button = $(this).find('button');
          const $text_height = $text.height();
          const $text_scroll_height = $text.prop('scrollHeight');

          // If the text was clamped, the scrollHeight will be higher than the
          // height.
          if ($text_height < $text_scroll_height) {
            // Text is clamped, if the button's hidden, reveal it.
            if ($button.hasClass('hidden')) {
              $button.removeClass('hidden');
            }
            return;
          }

          // Text is not clamped, hide the button.
          $button.addClass('hidden');
        });
      }

      checkExpandingText();

      $(window).on('resize', checkExpandingText);
    }
  }
})(jQuery, Drupal, once);
