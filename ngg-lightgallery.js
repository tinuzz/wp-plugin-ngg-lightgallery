(function($) {

  $(".lightgallery").each( function (i) {

    lg = lightGallery( this, {
      //plugins: [ lgZoom, lgAutoplay, lgFullscreen, lgHash, lgThumbnail, lgVideo ],
      plugins: [ lgZoom, lgAutoplay, lgFullscreen, lgHash, lgVideo ],
      speed: 800,
      allowMediaOverlap: true,
      gotoNextSlideOnVideoEnd: false,
      autoplayVideoOnSlide: true,
      counter: false,
      mobileSettings: {
        controls: false,
        showCloseIcon: true,
        download: false
      },
      pause: 3000   // autoplay interval
    });

    // We want to hide the thumbnail bar by default. The plugin doesn't provide
    // a setting for that, so we try and toggle the class that controls its
    // visibility from here. Ugly: directly after the 'afterOpen' event fires,
    // the thumbnail bar is apparently not present yet, toggling its visibility
    // doesn't seem to work. It appears to work with a 200ms delay though...
    //
    // However, the thumbnail bar is part of the 'components' area that also
    // hosts the captions. Hence, we cannot have captions while not having
    // thumbnails at the same time. This is annoying, and we disable thumbnails
    // for now in favor of working captions.
    /*
    this.addEventListener('lgAfterOpen', () => {
      setTimeout(function() {
        lg.outer.toggleClass('lg-components-open');
      }, 200)
    });
    */

    // Hide captions after sliding
    this.addEventListener('lgAfterSlide', () => {
        lg.outer.removeClass('lg-components-open');
    });

    // There is an issue with hidden iframes that causes content to not render
    // correctly. To correct this, we reload the contents of the iframe when it
    // is displayed. We use the 'beforeSlide' event for this. Also, we want to
    // do this only once, so we check for the absence of a custom class
    // ('lg-tzz-reloaded') before forcing the content to be reloaded.
    this.addEventListener('lgBeforeSlide', (event) => {
        const { index, prevIndex } = event.detail;
        if (lg.galleryItems[index].iframe == "true") {
          if (!lg.getSlideItem(index).hasClass('lg-tzz-reloaded')) {
            lg.getSlideItem(index).removeClass('lg-loaded');
            lg.loadContent(index, false);
            lg.getSlideItem(index).addClass('lg-tzz-reloaded');
          }
        }
    });

    // Show captions on user interaction and hide them again after 3 seconds
    // This interferes with video controls, though!!
    lg.outer.on('mousemove.lg touchstart.lg', function () {
        lg.outer.addClass('lg-components-open');
        if (typeof mytimeout != 'undefined') {
          clearTimeout(mytimeout);
        }
        mytimeout = setTimeout(function() {
          lg.outer.removeClass('lg-components-open');
        }, 3000)
    });
  });

})( jQuery );
