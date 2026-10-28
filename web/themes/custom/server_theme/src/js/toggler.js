/**
 * @file
 * JS for button controls.
 */
(function ($, Drupal, once) {

  /**
   * Toggles 'hidden' class on items using a button.
   *
   * To set up, simply give the hidden element a unique id, and create a button
   * with 'aria-controls' attribute with a value of the hidden element's id. Do
   * not include the preceding '#'.
   *
   * @type {{attach: Drupal.behaviors.serverThemeToggler.attach}}
   */
  Drupal.behaviors.serverThemeToggler = {
    attach: function (context, settings) {
      const $buttons = $(
        once(
          'server-theme-button',
          'button[aria-controls]:not(.toolbar-button):not([aria-controls="admin-toolbar"])',
          context
        )
      );

      if (!$buttons.length) {
        return;
      }
      $buttons.on('click', function (event) {
        const $this = $(this);
        const $target = $('#' + $this.attr('aria-controls'));
        if (!$target.length) {
          return;
        }

        // Sometimes we don't want to toggle .hidden class. In such cases you
        // can add data-toggle-strategy="aria-expanded" to the button, and this
        // will instead toggle the aria-expanded property on the target element.
        if ($this.data('toggleStrategy') === 'aria-expanded') {
          $target.attr('aria-expanded', $target.attr('aria-expanded') === 'true' ? 'false' : 'true')
        }
        else {
          $target.toggleClass('hidden');
        }
        $this.attr('aria-expanded', $this.attr('aria-expanded') === 'true' ? 'false' : 'true');

        // Handle has-scroll class. It's nice to know element has scrollbar
        // for styling reasons.
        if ($target.is(':visible') && !$target.hasClass('has-scroll') && $target.get(0).scrollHeight > $target.get(0).clientHeight) {
          $target.addClass('has-scroll');
        }

        // Handle tabindex.
        // We set tabindex="-1" on hidden inputs to prevent it being focusable
        // when using tab key. However after the input is shown we want it
        // focusable (tabindex="0").
        const $tabbed_items = $target.find('[tabindex]');
        if (!$tabbed_items.length) {
          return;
        }
        // Make the input focusable if the parent is not hidden.
        $tabbed_items.attr('tabindex', $target.hasClass('hidden') ? '-1' : '0');
      });
    }
  }
})(jQuery, Drupal, once);
