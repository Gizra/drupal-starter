/**
 * @file
 * JS for the Header menu.
 */

(function ($) {

  'use strict';

  /**
   * Toggle mobile menu elements visibility.
   */
  Drupal.behaviors.toggleMobileMenuVisibility = {
    attach: function (context, settings) {
      // Toggles visibility for mobile menu with hamburger.
      $('.js-hide-mobile-menu-trigger', context).once('hide-mobile-menu').click(function () {
        $('.js-hide-mobile-menu')
          .toggleClass('hidden')
          .toggleClass('flex');
      });
    },
  };

})(jQuery);
