/**
 * @file
 * Placeholder file for custom sub-theme behaviors.
 *
 */

(function ($, Drupal) {

  Drupal.behaviors.cpaResources = {
    attach: function (context, settings) {
      $(context).on('click', '.cpa-resources .switch', function() {
        $(this).toggleClass('on').next().toggleClass('hide');
      });
    }
  };

})(jQuery, Drupal);