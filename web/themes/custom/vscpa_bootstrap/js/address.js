(function ($, Drupal) {
  Drupal.behaviors.defaultVA = {
    attach: function (context, settings) {
      // Default Address Field to VA
      $('.address-container-inline select', context).val('VA');
    }
  };
})(jQuery, Drupal);