/**
 * @file
 * JS for the Accordion paragraph type.
 */

(function ($, Drupal) {
  Drupal.behaviors.accordion = {
    attach: function (context, settings) {
      // Hide all accordion item's descriptions.
      let items = $('.accordion .accordion-brief').hide();
      // Get the accordion's title.
      let title = $('.accordion .accordion-title');
      // Add on click function.
      title.once().click(function() {
        // Check if clicked accordion item is not active already.
        if ($(this).find('.minus-circle').hasClass('hidden')) {
          items.slideUp('fast');
          // Show only the clicked accordion item.
          $(this).next().slideDown('fast');
          $(this).find('.minus-circle').toggleClass('hidden');
          $(this).find('.plus-circle').toggleClass('hidden');

          // Set icons for other items.
          title.not(this).find('.minus-circle').addClass('hidden');
          title.not(this).find('.plus-circle').removeClass('hidden');
        }
      });
    }
  }
})(jQuery, Drupal);
