/**
 * @file
 * Bundle Event - Modal Enroll Element behaviors.
 *
 */
(function ($, Drupal) {

  // MODAL BUNDLE EVENTS CLASS DEFINITION
  // ===============================================
  var ModalBundleEvents = function (options) {
    this.options = options;
    // CTA buttons.
    this.$open_modal_button = options.open_modal_button;
    // Modal Element.
    this.$modal = this.options.modal;
    // Handle Actions.
    this.handleModalShow();
  };
  // Define plugin version.
  ModalBundleEvents.VERSION = '1.0.0';
  // Hook open modal action.
  ModalBundleEvents.prototype.handleModalShow = function () {
    var that = this;
    this.$open_modal_button.click(function (event) {
      event.preventDefault();
      event.stopPropagation();
      // Set the Modal Title.
      $title = $(this).html();
      that.$modal.find('.modal-header .modal-title').html($title);
      // Set the Modal Content.
      $content_selector = $(this).attr('data-content-selector');
      $modal_content = $($content_selector).html();
      that.$modal.find('.modal-body .portlet-body').html($modal_content);
      // Show the modal.
      that.$modal.modal('show');
    });
  };
  // Modal Dues Payment Plan Element - behaviors.
  Drupal.behaviors.ModalBundleEvents = {
    attach: function (context, settings) {
      var modal_bundle_event = new ModalBundleEvents({
        modal: $('.modal-bundle-show-event-detail', context),
        open_modal_button: $('a.modal-bundle-event-details', context),
      });
    }
  };
})(jQuery, Drupal);
