/**
 * @file
 */


(function ($, Drupal) {
  'use strict';

  /**
   * General behaviours.
   */
  Drupal.behaviors.themeServerGeneral = {
    attach: function (context, settings) {
      // Add anchor links to all headings.
      const anchors = new AnchorJS();
      anchors.options = {
        icon: '',
        class: 'fa-solid fa-link no-underline text-base text-dark-gray not-prose'
      };
      anchors.add();
    },
  };

})(jQuery, Drupal);
