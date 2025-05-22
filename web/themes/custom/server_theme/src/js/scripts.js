/**
 * @file
 */


(function ($, Drupal, once) {
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
        class: 'fa-solid fa-link no-underline text-xl'
      };
      anchors.add();
    },
  };
  /**
   * Actions with desktop search.
   */
  Drupal.behaviors.serverThemeSearchToggle = {
    attach: function (context, settings) {
      const $toggle = $(once('server-theme-search-toggle', '.js-search-toggle', context));
      if (!$toggle.length) {
        return;
      }

      const $body = $(document.body);

      // Disable the hidden form inputs so they don't get selected when a user's
      // tabbing through the page.
      const $form = $('.js-search-form');
      const $inputs = $form.find('input');

      $inputs.prop('disabled', 'disabled');
      // Shows search textfield for desktop.
      $toggle.on('click', function (event) {
        event.stopPropagation();
        // Enable the inputs.
        $inputs.prop('disabled', false);
        $body.addClass('js-search-form-open');
        $form.find('input[name="key"]').focus();
      });

      $form.on('click', function(event) {
        event.stopPropagation();
      });

      $body.on('click', function(event) {
        $body.removeClass('js-search-form-open');

        if ($(this).hasClass('js-search-form-open')) {
          $(this).removeClass('js-search-form-open');
        }
      });
    },
  };

})(jQuery, Drupal, once);
