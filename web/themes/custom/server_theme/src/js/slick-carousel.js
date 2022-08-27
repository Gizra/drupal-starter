// Use strict.
'use strict';

(function ($, Drupal) {

  // We don't need to deal with Ajax, so we do it onLoad.
  $(function () {
    // All the elements in the page that need a Slick carousel. It may be
    // a carousel that is always attached, or one that is attached only on
    // mobile.
    const $elements = $('.carousel-wrapper');

    /**
     * Determine if Slick is already attached.
     *
     * @returns {boolean|jQuery}
     */
    function hasSlickAttached($element) {
      return $element.hasClass('slick-slider');
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

      /**
       * Get the total slides count of a carousel of a given element.
       *
       * @param $element
       *   The given element.
       */
      function getTotalCardsCount($element) {
        if (hasSlickAttached($element)) {
          // Don't count cloned slides.
          return $element.find('.slick-slide').not('.slick-cloned').length;
        }
        return $element.find('.carousel-slide').length;
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

        // Get the number of total cards we have.
        const numSlides = getTotalCardsCount($element);
        if (!numSlides) {
          console.error('Slides are missing the `carousel-slide` class');
          return;
        }
        if (hasSlickAttached($element)) {
          // We have already attached Slick.
          return;
        }

        // We'd like to show only a single slide on mobile.
        let slidesToShow;

        if (singleSlide) {
          slidesToShow = 1;
        }
        else if (isResponsive) {
          slidesToShow = 1;
        }
        else {
          slidesToShow = numSlides > 3 ? 3 : numSlides;
        }

        const config = {
          infinite: true,
          focusOnSelect: false,
          arrows: showArrows,
          mobileFirst: true,
          slidesToShow: slidesToShow,
          slidesToScroll: slidesToScroll || slidesToShow,
          dots: showDots,
          variableWidth: variableWidth,
          // This defaults to 1 in slick, which in conjunction with slidesPerRow
          // setting, also default to 1, initializes Grid Mode on Slick. We
          // set rows to 0 to disable grid mode, which adds a lot of extra
          // unwanted markup that adds 'display:inline-block' and ends up
          // creating extra whitespace.
          // @see https://github.com/kenwheeler/slick/issues/3581.
          rows: 0,
          centerMode: centerMode,
          centerPadding: hasPadding ? '10px' : '0',
        };

        if (isResponsive) {
          // Add the responsive config.
          config.responsive = [
            {
              breakpoint: 1279,
              settings: {
                slidesToShow: slidesToShowDesktop,
                slidesToScroll: slidesToShowDesktop,
                arrows: showArrows,
                prevArrow: $prevArrow,
                nextArrow: $nextArrow,
                variableWidth: variableWidth,
                centerPadding: hasPadding ? '150px' : '0',
              },
            },
            {
              breakpoint: 1023,
              settings: {
                slidesToShow: slidesToShowLaptop,
                slidesToScroll: slidesToShowLaptop,
                arrows: showArrows,
                prevArrow: $prevArrow,
                nextArrow: $nextArrow,
                variableWidth: variableWidth,
                centerPadding: hasPadding ? '100px' : '0',
              },
            },
            {
              breakpoint: 639,
              settings: {
                slidesToShow: slidesToShowTablet,
                slidesToScroll: slidesToShowTablet,
                arrows: showArrows,
                variableWidth: variableWidth,
                centerPadding: hasPadding ? '50px' : '0',
              },
            }
          ];
        }

        if (showArrows) {
          config.prevArrow = $prevArrow;
          config.nextArrow = $nextArrow;
        }

        $element.slick(config);
      });
    }

    // Trigger attach on page load.
    attachedOrDestroySlick();

    // Listen on window resize event.
    $(window).resize(function () {
      attachedOrDestroySlick();
    });
  });

})(jQuery, Drupal);
