(function ($) {

  // Hide all panels on load.
  $('.accordion > dd').hide();

  $('.accordion a.title-wrapper').click(function (event) {
    event.preventDefault();
    const $this = $(this);
    // Update the URL hash.
    window.location.hash = $this.attr('href');
    $this.parent().next().slideToggle(400, function () {
      if (typeof $.fn.slick != 'function') {
        // No slick installed.
        return;
      }
      // Re-position any slick sliders in this panel.
      const $slick = $(this).find('.slick-initialized');
      if (!$slick.length) {
        // No slick sliders.
        return;
      }
      $slick.slick('setPosition');
    });
  });

  // Check if hash exist, and if so try to open its pane.
  if (window.location.hash) {
    // Puts hash in variable, and removes the # character.
    const hash = window.location.hash.substring(1);
    $('.accordion #' + hash).click();

  }

})(jQuery);
