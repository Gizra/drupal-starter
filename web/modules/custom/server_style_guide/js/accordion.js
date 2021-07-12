(function($) {

  const allPanels = $('.accordion > dd').hide();

  $('.accordion > dt > a').click(function() {
    allPanels.slideUp();
    $(this).parent().next().slideDown();
    return false;
  });

})(jQuery);
