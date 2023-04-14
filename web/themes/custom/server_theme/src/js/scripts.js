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
      anchors.add();
    },
  };

})(jQuery, Drupal);
