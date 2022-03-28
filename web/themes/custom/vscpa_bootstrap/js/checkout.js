/**
 * @file
 * Placeholder file for custom sub-theme behaviors.
 *
 */

(function ($, Drupal) {

  Drupal.behaviors.disableMultipleClicks = {
    attach: function (context, settings) {
      $(context).find('.layout-region-checkout-footer .btn-primary').each(function() {
        $(this).click(function() {
          $(this).addClass('clicked');
        });
      });
    }
  };

})(jQuery, Drupal);
