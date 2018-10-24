(function($) {

  $(".lightgallery").lightGallery({
    'hideBarsDelay': 3000,
    'controls': $(window).width() >= 768,
    'showThumbByDefault': false,
    'share': false,
    'actualSize': false,
    'videoMaxWidth': '90%',
    'iframeMaxWidth': '90%'
  });

  // Hide the controls and the caption after sliding
  $(".lightgallery").on('onAfterSlide.lg', function(e) {
    setTimeout(function() {
      $('.lg-outer').addClass('lg-hide-items');
    }, 500);
  });

})( jQuery );
