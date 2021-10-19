(function ($) {

  const allPanels = $('.accordion > dd').hide();

  $('.accordion a.title-wrapper').click(function () {
    allPanels.slideUp();
    $(this).parent().next().slideDown();
  });

  // Check if hash exist, and if so try to open its pane.
  if (window.location.hash) {
    // Puts hash in variable, and removes the # character.
    const hash = window.location.hash.substring(1);
    $('.accordion #' + hash).click();

  }

})(jQuery);
