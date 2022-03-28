(function ($, Drupal) {

  Drupal.behaviors.tabCollapse = {
    attach: function (context, settings) {
      $('.nav-tabs').tabCollapse({
        tabsClass: 'hidden-xs hidden-sm',
        accordionClass: 'visible-xs visible-sm'
      });

      $('.paragraph--type--tabs .panel-default:first-of-type a').attr('aria-expanded', 'true');
    }
  };

})(jQuery, Drupal);
