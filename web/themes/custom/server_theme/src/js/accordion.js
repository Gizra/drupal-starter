/**
 * @file
 * JS for the Accordion paragraph type.
 */

(function ($, Drupal) {
  Drupal.behaviors.accordion = {
    attach: function (context, settings) {

      $('.accordion .accordion-title').once().click(function() {
        const $this = $(this);
        const $target = $this.next();
        // Indicate if description is hidden.
        const isHidden = $target.hasClass('hidden');

        // Show or hide the description.
        if (isHidden) {
          $target.slideDown();
          $target.removeClass('hidden');
        }
        else {
          $target.slideUp();
          $target.addClass('hidden');
        }

        // Change the SVG icon.
        const hideSvgClassName = isHidden ? 'minus-circle' : 'plus-circle';
        const showSvgClassName = isHidden ? 'plus-circle' : 'minus-circle';
        $this.find('.' + hideSvgClassName).toggleClass('hidden');
        $this.find('.' + showSvgClassName).toggleClass('hidden');

      });
    }
  }
})(jQuery, Drupal);
