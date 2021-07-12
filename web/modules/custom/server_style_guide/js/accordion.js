(function($) {

  const allPanels = $('.accordion > dd').hide();

  $('.accordion a.title-wrapper').click(function() {
    allPanels.slideUp();
    $(this).parent().next().slideDown();
    return false;
  });

})(jQuery);
