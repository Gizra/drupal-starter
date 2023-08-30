/**
 * @file
 * JS for the Accordion paragraph type.
 */

(function ($, Drupal) {
  Drupal.behaviors.accordion = {
    attach: function (context, settings) {

      $(once('accordion-toogle', '.accordion .accordion-title')).click(function() {
        const $this = $(this);
        const $target = $this.next();

        // Show or hide the description.
        $target.slideToggle();
        $target.toggleClass('hidden');

        // Change the SVG icon.
        $this.find('.minus-circle').toggleClass('hidden');
        $this.find('.plus-circle').toggleClass('hidden');
      });

    }
  }
})(jQuery, Drupal);
