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
        const singleSlide = $element.data('carousel-single-slide');
        const showDots = $element.data('carousel-dots');
        const showArrows = $element.data('carousel-arrows');
        const isResponsive = $element.data('carousel-responsive');
        const centerMode = $element.data('carousel-center-mode-mobile');
        const variableWidth = $element.data('carousel-fixed-width-on-mobile');
        const hasPadding = $element.data('carousel-padding');
        const slidesToShowTablet = $element.data('slides-tablet') || 2;
        const slidesToShowLaptop = $element.data('slides-laptop') || 2;
        const slidesToShowDesktop = $element.data('slides-desktop') || 3;
        const slidesToScroll = $element.data('slides-to-scroll') || 1;
        const $parent = $element.parent();
        const $prevArrow = $parent.find('.slick-arrow-prev');
        const $nextArrow = $parent.find('.slick-arrow-next');

        const swiper = new Swiper('.swiper', {
          // Optional parameters
          direction: 'horizontal',
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

        console.log(swiper);

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
