// Use strict.
'use strict';

(function ($, Drupal) {

  // We don't need to deal with Ajax, so we do it onLoad.
  $(function () {
    // All the elements in the page that need a Slick carousel. It may be
    // a carousel that is always attached, or one that is attached only on
    // mobile.
    const $elements = $('.swiper');

    /**
     * Determine if Slick is already attached.
     *
     * @returns {boolean|jQuery}
     */
    function hasSlickAttached($element) {
      return $element.hasClass('swiper-slide');
    }

    /**
     * Attach slick to all elements, unless they are already attached or need
     * to be attached only for mobile.
     *
     * If we should have it on mobile only, and the page is bigger, then destroy
     * Slick.
     */
    function attachedOrDestroySlick() {
      if (!$elements.length) {
        // No carousels needed on page.
        return;
      }

      $elements.each(function () {
        const $element = $(this);

        const swiper = new Swiper('.swiper', {
          loop: true,

          // If we need pagination
          pagination: {
            el: '.swiper-pagination',
          },

          // Navigation arrows
          navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
          },

          // And if we need scrollbar
          scrollbar: {
            el: '.swiper-scrollbar',
          },
        });
      });
    }

    // Trigger attach on page load.
    attachedOrDestroySlick();

    // Listen on window resize event.
    $(window).resize(function () {
      // @todo: Keep?
      // attachedOrDestroySlick();
    });
  });

})(jQuery, Drupal);
