/**
 * @file
 * JS for View more button for lists.
 */

(function ($, Drupal) {
  Drupal.behaviors.showMore = {
    attach: function (context, settings) {

      $('.view-more-wrapper .button-wrapper', context).once().click(function (e) {
        e.preventDefault();
        $(this).addClass('hidden')
          .siblings()
          .removeClass('hidden');
      });
    }
  }
})(jQuery, Drupal);
