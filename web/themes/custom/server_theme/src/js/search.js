/**
 * @file
 * JS for Search pages.
 */

(function ($) {

  'use strict';

  /**
   * Toggle showing and hiding the filters on mobile view.
   *
   * @type {{attach: Drupal.behaviors.themeServerToggleFilters.attach}}
   */
  Drupal.behaviors.themeServerToggleFilters = {
    attach: function (context) {
      $('.filters-title', context).once('filters-title-once').each(function () {
        $(this).click(function() {
          $('.facets-wrapper').toggleClass('hidden');
        });
      });
    }
  };

  /**
   * Clear filters, and reload page without it.
   *
   * @type {{attach: Drupal.behaviors.themeServerToggleFilters.attach}}
   */
  Drupal.behaviors.themeServerClearFilters = {
    attach: function (context) {
      $('.clear-filters', context).once('clear-filters-once').each(function () {
        $(this).click(function() {
          // Reload page without the query params.
          window.location = window.location.pathname;
        });
      });
    }
  };

})(jQuery);
