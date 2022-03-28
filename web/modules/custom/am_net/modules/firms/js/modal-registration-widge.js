/**
 * @file
 * EMT - Modal Registration Form Element behaviors.
 *
 */
(function ($, Drupal) {

    // MODAL REGISTRATION CLASS DEFINITION
    // ===================================
    var ModalRegistration = function (options) {
        this.options = options;
        this.$element = options.selector;
        this.$modal = this.$element.find(options.modal_selector);
        this.$modal_body = this.$modal.find('.modal-body');
        this.$messages_area = this.$element.find('.messages-area');
        this.$open_modal_button = this.$element.find('a.modal-button');
        this.$cta_register_employees = this.$element.find('a.cta-register-employees');
        this.$employee_search_input = this.$element.find('input.employee-search-input');
        this.$employee_filter = this.$element.find('.table-employee-listing tr');
        this.$registration_info = this.$element.find('.registration-info');
        this.showVariationSelector = options.showVariationSelector;
        this.handleModalShow();
        this.handleEventRegistrationCta();
        this.handleOnCloseEvent();
        this.handleOnOpenEvent();
        this.handleGroupCheckable();
        this.handleVariationChanges();
        this.handleEmployeeSearch();
    };
    // Define plugin version.
    ModalRegistration.VERSION = '1.0.0';
    // Define Helper method to show alert message.
    ModalRegistration.prototype.showAlertMessage = function ($message) {
        this.$modal_body.scrollTop('0');
        var content = '<div class="alert alert-warning alert-dismissible" role="alert"> <button role="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button> <h4 class="sr-only">Warning message</h4> ' + $message + '</div>';
        this.$messages_area.html('').removeClass('hide').append(content);
    };
    // Define Helper method to 'Update selected employees'.
    ModalRegistration.prototype.updateSelectedEmployees = function () {
        // Clear the current value.
        this.$registration_info.attr('data-selected-employees', '');
        // Get the new values.
        var selected = [];
        this.$element.find('.table-employee-listing input:checked').each(function () {
            selected.push($(this).val());
        });
        if (!selected.length) {
            return false;
        }
        // Update values.
        var data_value = JSON.stringify(selected);
        this.$registration_info.attr('data-selected-employees', data_value);
        return true;
    };
    // Hook Open Modal Action.
    ModalRegistration.prototype.handleModalShow = function () {
        var that = this;
        this.$open_modal_button.click(function (event) {
            event.preventDefault();
            event.stopPropagation();
            that.$modal.modal('show');
        });
    };
    // Do Product registration.
    ModalRegistration.prototype.doProductRegistration = function () {
        // Add Loading label.
        this.$open_modal_button.addClass('clicked').html('Registering employees... <div class="loader"></div>');
        var employees = this.$registration_info.attr('data-selected-employees');
        if (employees.length) {
            employees = JSON.parse(employees);
        }
        var selected_variation_id = this.$registration_info.attr('data-selected-variation-id');
        var post_data = JSON.stringify({
            'variation_id': selected_variation_id,
            'employees': employees
        });
        $.ajax({
            url: '/ajax/product-registrations',
            dataType: 'json',
            type: 'post',
            contentType: 'application/json',
            data: post_data,
            processData: false,
            success: function (data) {
                console.log(data);
                if (data.success) {
                    // Reload the page.
                    location.reload();
                }
            },
            error: function (jqXhr, textStatus, errorThrown) {
                console.log(errorThrown);
            }
        });
    };
    // Hook Close Modal Action.
    ModalRegistration.prototype.handleEventRegistrationCta = function () {
        var that = this;
        this.$cta_register_employees.click(function (event) {
            event.preventDefault();
            event.stopPropagation();
            var result = that.updateSelectedEmployees();
            if (!result) {
                that.$open_modal_button.removeClass('loading');
                // Show validation message.
                var $message = 'Please select one or more employees in the table to continue with the registration.';
                that.showAlertMessage($message);
                $(this).removeClass('clicked');
            } else {
                that.$open_modal_button.addClass('loading');
                // Close modal.
                that.$modal.modal('hide');
                if (that.showVariationSelector) {
                    // Show variation selection form.
                } else {
                    // Do product registration.
                    that.doProductRegistration();
                }
            }
        });
    };
    // Handle Modal On Close Event.
    ModalRegistration.prototype.handleOnCloseEvent = function () {
        var that = this;
        this.$modal.on('hidden.bs.modal', function () {
            that.$cta_register_employees.removeClass('clicked');
            if (!that.$open_modal_button.hasClass('loading')) {
                that.$open_modal_button.removeClass('clicked');
            }
        });
    };
    // Handle Modal On Open Event.
    ModalRegistration.prototype.handleOnOpenEvent = function () {
        var that = this;
        this.$modal.on('shown.bs.modal', function () {
            // Clear messages area.
            that.$messages_area.html('').addClass('hide');
        });
    };
    // Handle group checkable.
    ModalRegistration.prototype.handleGroupCheckable = function () {
        var that = this;
        this.$element.find('input[type="checkbox"].group-checkable').click(function () {
            var checked = $(this).is(':checked');
            that.$element.find('.table-employee-listing tbody .table-checkbox input[type="checkbox"]').prop('checked', checked);
        });
    };
    // Handle Variation Changes.
    ModalRegistration.prototype.handleVariationChanges = function () {
        var that = this;
        $('.commerce-order-item-add-to-cart-form select[name="purchased_entity[0][variation]"]').change(function () {
            var selected_variation = $(this).val();
            that.$registration_info.attr('data-selected-variation-id', selected_variation);
        });
    };
    // Handle Employee Search.
    ModalRegistration.prototype.handleEmployeeSearch = function () {
        var that = this;
        this.$employee_search_input.on('keyup', function () {
            var value = $(this).val().toLowerCase();
            that.$employee_filter.filter(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    };
    // Modal Registration Form Element - behaviors.
    Drupal.behaviors.ModalRegistration = {
        attach: function (context, settings) {
          $('.modal-registration-section', context).each(function () {
            new ModalRegistration({
              selector: $(this),
              modal_selector: '#modal-select-employees',
              showVariationSelector: false
            });
          });
        }
    };
})(jQuery, Drupal);
