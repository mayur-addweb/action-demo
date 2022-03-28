/**
 * @file
 * Custom JS for managing Slideshow featurettes.
 *
 */

(function ($, Drupal) {

  var slideshowTop = $('.field--name-field-featurette-top .owl-carousel');
  var slideshowBottom = $('.field--name-field-featurette-bottom .owl-carousel');

  Drupal.behaviors.slideshowTop = {
    attach: function(context) {
      slideshowTop.owlCarousel({
        autoplay: false,
        loop: true,
        margin: 15,
        nav: true,
        responsive: {
          0:{
            items: 1
          },
          768:{
            items: 2
          },
          1200:{
            items: 3
          }
        }
      });

      $('.paragraph-slide').matchHeight();
    }
  };

  Drupal.behaviors.slideshowBottom = {
    attach: function(context) {
      slideshowBottom.owlCarousel({
        autoplay: false,
        loop: true,
        margin: 15,
        nav: true,
        responsive: {
          0:{
            items: 1
          },
          768:{
            items: 2
          }
        }
      });

      $('.paragraph-slide').matchHeight();
    }
  };

})(jQuery, Drupal);
