(function ($) {
  const allPanels = $('#main-content div:not("title-wrapper")').hide();

  $('.title-wrapper a').click(function () {
    allPanels.slideUp();
    $(this).parent().next().slideDown();
    return false;
  });

})(jQuery);
