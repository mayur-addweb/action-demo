/**
 * @file
 * EMT - Modal ADA Compliance Form Element behaviors.
 */
(function ($, Drupal) {

  // MODAL ADA COMPLIANCE CLASS DEFINITION
  // ===================================
  var ModalADACompliance = function (options) {
    this.options = options;
    this.$modal = options.selector;
    this.$open_modal_button = options.trigger;
    this.$cta_register_now = this.$modal.find('a.cta-register-now');
    this.handleModalShow();
    this.handleOnCloseEvent();
    this.handleEventRegistrationCta();
  };
  // Define plugin version.
  ModalADACompliance.VERSION = '1.0.0';
  // Hook Open Modal Action.
  ModalADACompliance.prototype.handleModalShow = function () {
    var that = this;
    this.$open_modal_button.click(function (event) {
      if ($(this).hasClass('registering')) {
        return true;
      }
      event.preventDefault();
      event.stopPropagation();
      that.$modal.modal('show');
    });
  };
  // Hook Close Modal Action.
  ModalADACompliance.prototype.handleEventRegistrationCta = function () {
    var that = this;
    this.$cta_register_now.click(function (event) {
      event.preventDefault();
      event.stopPropagation();
      that.$open_modal_button.addClass('loading');
      that.$open_modal_button.addClass('registering');
      that.$open_modal_button.html('Registering... <div class="loader"></div>');
      // Close modal.
      that.$modal.modal('hide');
      // Let the normal registration continue.
      that.$open_modal_button.click();
    });
  };
  // Handle Modal On Close Event.
  ModalADACompliance.prototype.handleOnCloseEvent = function () {
    var that = this;
    this.$modal.on('hidden.bs.modal', function () {
      that.$cta_register_now.removeClass('clicked');
      if (!that.$open_modal_button.hasClass('loading')) {
        that.$open_modal_button.removeClass('clicked');
      }
    });
  };
  // Modal ADA Compliance Form Element - behaviors.
  Drupal.behaviors.ModalADACompliance = {
    attach: function (context, settings) {
      // Only allow for one instantiation of this script.
      if (typeof $.fn['ModalADACompliance'] !== 'undefined') {
        return;
      }
      $('.commerce-order-item-add-to-cart-form', context).each(function () {
        $form = $(this);
        $trigger = $form.find('button.form-submit');
        $element = $form.find('div.modal-ada-compliance');
        new ModalADACompliance({
          selector: $element,
          trigger: $trigger,
        });
      });
    }
  };
})(jQuery, Drupal);
