// Use strict.
'use strict';

(function ($, Drupal, once) {

  /**
   * Determine if Slick is already attached.
   *
   * @param {jQuery} $element
   * @returns {boolean}
   */
  function hasSlickAttached($element) {
    return $element.hasClass('slick-slider');
  }

  /**
   * Initialise Slick on a single .carousel-wrapper element.
   *
   * @param {jQuery} $element
   */
  function initSlickElement($element) {
    if (hasSlickAttached($element)) {
      return;
    }

    /**
     * Get the total slides count of a carousel of a given element.
     *
     * @param $el
     *   The given element.
     */
    function getTotalCardsCount($el) {
      if (hasSlickAttached($el)) {
        // Don't count cloned slides.
        return $el.find('.slick-slide').not('.slick-cloned').length;
      }
      return $el.find('.carousel-slide').length;
    }

    const singleSlide = $element.data('carousel-single-slide');
    const showDots = $element.data('carousel-dots');
    const showArrows = $element.data('carousel-arrows');
    const isResponsive = $element.data('carousel-responsive');
    const centerMode = $element.data('carousel-center-mode-mobile');
    const variableWidth = $element.data('carousel-fixed-width-on-mobile');
    const hasPadding = $element.data('carousel-padding');
    const $parent = $element.parent();
    const $prevArrow = $parent.find('.slick-arrow-prev');
    const $nextArrow = $parent.find('.slick-arrow-next');
    // Determine if the carousel should be an infinite loop.
    const infiniteLoop = $element.data('carousel-infinite') || false;
    // Determine how many slides to show per breakpoint and how many to scroll.
    const slidesToShow = 1;
    const slidesToShowTablet = $element.data('slides-tablet') || 2;
    const slidesToShowLaptop = $element.data('slides-laptop') || 2;
    const slidesToShowDesktop = $element.data('slides-desktop') || 3;
    const slidesToScroll = $element.data('slides-to-scroll');

    // Get the number of total cards we have.
    const numSlides = getTotalCardsCount($element);
    if (!numSlides) {
      console.error('Slides are missing the `carousel-slide` class');
      return;
    }

    const config = {
      infinite: infiniteLoop,
      // Set the direction based on the current language.
      rtl: drupalSettings.language && drupalSettings.language.direction === 'rtl',
      focusOnSelect: false,
      arrows: showArrows,
      mobileFirst: true,
      slidesToShow: slidesToShow,
      slidesToScroll: slidesToScroll ? slidesToScroll : slidesToShow,
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
            slidesToScroll: slidesToScroll ? slidesToScroll : slidesToShowDesktop,
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
            slidesToScroll: slidesToScroll ? slidesToScroll : slidesToShowLaptop,
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
            slidesToScroll: slidesToScroll ? slidesToScroll : slidesToShowTablet,
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
  }

  Drupal.behaviors.serverThemeSlickCarousel = {
    attach(context) {
      once('slick-carousel', '.carousel-wrapper', context).forEach((el) => {
        initSlickElement($(el));
      });

      // Re-run init on resize in case new carousels become visible (e.g. tabs).
      // Use once() on body so the listener is attached only once per page.
      once('slick-carousel-resize', 'body', context).forEach(() => {
        $(window).on('resize.slickCarousel', () => {
          $('.carousel-wrapper').each((i, el) => initSlickElement($(el)));
        });
      });
    },
  };

})(jQuery, Drupal, once);
