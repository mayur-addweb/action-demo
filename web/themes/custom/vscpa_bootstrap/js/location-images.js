/**
 * @file
 * Placeholder file for custom sub-theme behaviors.
 *
 */

(function ($, Drupal) {

  var locationImages = $('.field-location-images.owl-carousel');

  Drupal.behaviors.locationImages = {
    attach: function(context) {
      locationImages.owlCarousel({
        autoplay: false,
        loop: true,
        margin: 15,
        responsive: {
          0:{
            items: 1
          },
          768:{
            items: 2
          }
        }
      });
    }
  };

})(jQuery, Drupal);
