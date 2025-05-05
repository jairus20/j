(function($) {
  "use strict";
  
  $(window).scroll(function() {
    if ($(window).scrollTop() > 50) {
      $('.start-header').addClass('scroll-on');
    } else {
      $('.start-header').removeClass('scroll-on');
    }
  });

  // Add smooth scrolling to nav links
  $('a[href*="#"]').on('click', function(e) {
    e.preventDefault();
    $('html, body').animate(
      {
        scrollTop: $($(this).attr('href')).offset().top - 100,
      },
      500,
      'linear'
    );
  });

})(jQuery);