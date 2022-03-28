/**
 * @file
 * Dues Payment Plan - Modal Enroll Element behaviors.
 *
 */
(function ($, Drupal) {

    // MODAL DUES PAYMENT PLAN ENROLL CLASS DEFINITION
    // ===============================================
    var ModalDuesPaymentPlanEnroll = function (options) {
        this.options = options;
        this.$element = options.selector;
        this.$open_modal_button = options.open_modal_button;
        this.$plan_radios = this.$element.find(options.action_cta_radios_selector);
        this.$plan_details_container = this.$element.find(options.payment_plan_details_selector);
        // CTA buttons.
        this.$cta_pay_in_full_purple_button = this.$element.find(options.cta_pay_in_full_purple_selector);
        this.$cta_pay_in_full_white_button = this.$element.find(options.cta_pay_in_full_white_selector);
        this.$cta_pay_in_installments_button = this.$element.find(options.cta_pay_in_installments_selector);
        // Modal Element.
        this.$modal = this.$element.find(options.modal_selector);
        // Handle Actions.
        this.handleModalShow();
        this.handleEnroll();
        this.handleDoNotEnroll();
        this.handlePlanActions();
    };
    // Define plugin version.
    ModalDuesPaymentPlanEnroll.VERSION = '1.0.0';
    // Hook open modal action.
    ModalDuesPaymentPlanEnroll.prototype.handleModalShow = function () {
      var that = this;
      this.$open_modal_button.click(function (event) {
        event.preventDefault();
        event.stopPropagation();
        if ($(this).hasClass('clicked')) {
          return false;
        }
        that.$modal.modal('show');
      });
    };
    // Hook 'Plan Actions' toggle.
    ModalDuesPaymentPlanEnroll.prototype.handlePlanActions = function () {
        var that = this;
        this.$plan_radios.click(function (event) {
            var selected_value = $("input[name='payment_plan_cta_action']:checked").val();
            if (selected_value == 'payment-plan-action-go') {
                that.handleShowPlanBalance();
            } else {
                that.handleHidePlanBalance();
            }
        });
    };
    // Hook action: 'Hide Plan Balance'.
    ModalDuesPaymentPlanEnroll.prototype.handleHidePlanBalance = function () {
        this.$plan_details_container.addClass('hide');
        this.$cta_pay_in_full_purple_button.removeClass('hide');
        this.$cta_pay_in_full_white_button.addClass('hide');
        this.$cta_pay_in_installments_button.addClass('hide');
    };
    // Hook action: 'Show Plan Balance'.
    ModalDuesPaymentPlanEnroll.prototype.handleShowPlanBalance = function () {
        this.$plan_details_container.removeClass('hide');
        this.$cta_pay_in_full_purple_button.addClass('hide');
        this.$cta_pay_in_full_white_button.removeClass('hide');
        this.$cta_pay_in_installments_button.removeClass('hide');
    };
    // Action: 'Do Pay in Installments'.
    ModalDuesPaymentPlanEnroll.prototype.doPayInInstallments = function () {
        this.$open_modal_button.addClass('clicked').html('Adding products to the cart... <div class="loader"></div>');
        // Close modal.
        this.$modal.modal('hide');
        // Submit Form.
        $('input[name=enroll]').val(1);
        $(this.options.submit_selector).trigger('click');
    };
    // Action: 'Do Pay in Full'.
    ModalDuesPaymentPlanEnroll.prototype.doPayInFull = function () {
        this.$open_modal_button.addClass('clicked').html('Adding products to the cart... <div class="loader"></div>');
        // Close modal.
        this.$modal.modal('hide');
        // Submit Form.
        $('input[name=enroll]').val(0);
        $(this.options.submit_selector).trigger('click');
    };
    // Hook 'Pay in Installments' action.
    ModalDuesPaymentPlanEnroll.prototype.handleEnroll = function () {
        var that = this;
        this.$cta_pay_in_installments_button.click(function (event) {
            event.preventDefault();
            event.stopPropagation();
            that.doPayInInstallments();
        });
    };
    // Hook 'Pay in Full' action.
    ModalDuesPaymentPlanEnroll.prototype.handleDoNotEnroll = function () {
        var that = this;
        this.$cta_pay_in_full_purple_button.click(function (event) {
            event.preventDefault();
            event.stopPropagation();
            that.doPayInFull();
        });
        this.$cta_pay_in_full_white_button.click(function (event) {
            event.preventDefault();
            event.stopPropagation();
            that.doPayInFull();
        });
    };
    // Modal Dues Payment Plan Element - behaviors.
    Drupal.behaviors.ModalDuesPaymentPlanEnroll = {
        attach: function (context, settings) {
            var modal_enroll_dues_plan = new ModalDuesPaymentPlanEnroll({
                selector: $('.modal-dues-plan-section', context),
                open_modal_button: $('a.modal-dues-payment-plan-button', context),
                modal_selector: '#modal-enroll-dues-plan',
                submit_selector: 'button.btn-opacity-hidden',
                action_cta_radios_selector: '.payment-plan-actions input.payment-plan-action',
                payment_plan_details_selector: '.payment-plan-details',
                cta_pay_in_full_purple_selector: '.cta-dues-plan-pay-in-full',
                cta_pay_in_full_white_selector: '.cta-dues-plan-no-enroll',
                cta_pay_in_installments_selector: '.cta-dues-plan-enroll'
            });
            $('input.contribution-amount', context).on('change keyup keydown keypress onchange', function (e) {
                $('.modal-dues-payment-plan-button').addClass('clicked');
            });

        }
    };
})(jQuery, Drupal);
