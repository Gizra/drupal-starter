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
        $(this).removeClass('js-search-form-open');
      });
    },
  };

  Drupal.behaviors.serverThemeMenuToggle = {
    attach: function (context, settings) {
      const $main_menu = $(once('menu-toggle', 'nav.main-menu-wrapper', context));

      if (!$main_menu.length) {
        // Nothing to process.
        return;
      }

      const root = document.querySelector(':root');
      const behavior = this;
      const $body = $(document.body);
      const $open_menu = $(once('menu-toggle', 'button.js-open-menu', context));
      const $close_menu = $main_menu.find('button.js-close-menu');
      const $root_menu_items = $main_menu.find('button.menu-root-item');
      const $sub_menu_items = $main_menu.find('button.menu-sub-item');
      const $menu_containers = $main_menu.find('.menu.sub-menu');

      const toggleMenu = function (event) {
        event.preventDefault();
        // Get the current scroll position and negate it.
        const top = window.scrollY * -1 + 'px';

        $body.toggleClass('js-menu-open');

        if ($body.hasClass('js-menu-open')) {
          // Menu opened.
          // Set var to place the scroll in correct position while the menu is
          // open, as the window scroll is locked while the menu is open.
          root.style.setProperty('--server-theme-scroll-position', top);
          return;
        }

        // Menu closed.
        // Reset the window's scroll position back to where it was.
        const rootstyle = getComputedStyle(root);
        const scrollY = rootstyle.getPropertyValue('--server-theme-scroll-position');
        // scrollY will be a negative value, so we negate it again to make it
        // positive.
        $(window).scrollTop(parseInt(scrollY || '0') * -1);
      };

      $open_menu.each(function () {
        $(this).on('click', toggleMenu);
      });


      $close_menu.each(function () {
        $(this).on('click', toggleMenu);
      });

      $root_menu_items.each(function () {
        $(this).on('click', function (event) {
          event.preventDefault();
          const $this = $(this);

          const $active_items = $root_menu_items.filter('.active');
          const $active_containers = $menu_containers.filter('.active');
          if (!$active_items.is($this)) {
            // Clicked on a closed item. Hide all active items before activating
            // the new one.
            behavior.closeActiveItems($active_items);
          }
          // Always close the sub menu items when interactive with the root item
          behavior.closeActiveItems($active_containers);

          behavior.toggleActiveItem($this);
        });
      });

      $sub_menu_items.each(function () {
        $(this).on('click', function (event) {
          event.preventDefault();
          const $this = $(this);

          let $active_items = $sub_menu_items.filter('.active');
          let $active_containers = $menu_containers.filter('.active').filter('[data-menu-level=2]');
          if (!$active_items.is($this)) {
            // Clicked on a closed item. Hide all active items before activating
            // the new one.
            behavior.closeActiveItems($active_items);
            behavior.closeActiveItems($active_containers);
          }
          behavior.toggleActiveItem($this);
        });
      });
    },
    closeActiveItems: function ($elements) {
      $elements
        .removeClass('active')
        .next('.sub-menu.active')
        .removeClass('active');

      $elements
        .find('.expand-indicator')
        .removeClass('expand-indicator--expanded');
    },
    toggleActiveItem: function ($element) {
      const child_id = '#' + $element.data('menu-child');
      const $child_menu = $(child_id);

      $element
        .toggleClass('active')
        .next('ul')
        .toggleClass('active');

      $child_menu.toggleClass('active');

      $element
        .find('.expand-indicator')
        .toggleClass('expand-indicator--expanded');
    }
  };

})(jQuery, Drupal, once);
