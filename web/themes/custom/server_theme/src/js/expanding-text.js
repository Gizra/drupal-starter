/**
 * @file
 * JS for button controls.
 */
(function ($, Drupal, once) {
  Drupal.behaviors.expandingText = {
    attach: function(context, settings) {
      const $expandingText = $(once('expanding-text', '.js-expanding-text', context));
      if (!$expandingText.length) {
        return;
      }

      const checkExpandingText = function() {
        $expandingText.each(function () {
          const $text = $(this).find('.text-formatted');
          if (!$text.length) {
            return;
          }
          const $button = $(this).find('button');
          const $textHeight = $text.height();
          const $textScrollHeight = $text.prop('scrollHeight');

          // If the text was clamped, the scrollHeight will be higher than the
          // height.
          if ($textHeight < $textScrollHeight) {
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
