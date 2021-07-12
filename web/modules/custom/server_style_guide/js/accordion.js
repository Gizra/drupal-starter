(function($) {

  const allPanels = $('.accordion > dd').hide();

  $('.accordion .title-wrapper').click(function() {
    allPanels.slideUp();
    $(this).parent().next().slideDown();
    return false;
  });

})(jQuery);
