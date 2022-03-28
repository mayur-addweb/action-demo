/**
 * @file
 * Placeholder file for custom sub-theme behaviors.
 *
 */

(function ($, Drupal) {

    Drupal.behaviors.eventFilter = {
        attach: function (context, settings) {
            $('.form-item-field-dates-times-value-max input', context).appendTo('.form-item-field-dates-times-value-min .col-sm-9');

            $('.event-filter .btn-gold').unbind('click').bind('click', function () {
                $(this).parent().parent().toggleClass('show');
            });

            $('.event-filter label, .event-filter legend').unbind('click').bind('click', function () {
                $(this).toggleClass('gold').next().toggleClass('show').parents('.form-item').toggleClass('is-open');
            });

            $('.form-item-sort-by select').on('change', function () {
                $(this).parents('.event-filter').find('.form-actions button').click();
            });
            // Logic related to the checkboxes field: credit_type.
            $('input[name="credit_type[any]"]').on('change', function () {
                if (this.checked) {
                    // Un-check the other options.
                    $('input[name="credit_type[attest_compilation]"]').prop('checked', false);
                    $('input[name="credit_type[cfp]"]').prop('checked', false);
                    $('input[name="credit_type[yellow_book]"]').prop('checked', false);
                }
            });
            var selector = 'input[name="credit_type[attest_compilation]"], input[name="credit_type[cfp]"], input[name="credit_type[yellow_book]"]';
            $(selector).on('change', function () {
                if (this.checked) {
                    $('input[name="credit_type[any]"]').prop('checked', false);
                }
            });
            // Logic related to the checkboxes field: discounts.
            $('input[name="discounts[any]"]').on('change', function () {
                if (this.checked) {
                    // Un-check the other options.
                    $('input[name="discounts[aicpa]"]').prop('checked', false);
                    $('input[name="discounts[early_registration]"]').prop('checked', false);
                    $('input[name="discounts[free]"]').prop('checked', false);
                }
            });
            selector = 'input[name="discounts[aicpa]"], input[name="discounts[early_registration]"], input[name="discounts[free]"]';
            $(selector).on('change', function () {
                if (this.checked) {
                    $('input[name="discounts[any]"]').prop('checked', false);
                }
            });
            // Logic related to the checkboxes field: format.
            $('input[name="format[any]"]').on('change', function () {
                if (this.checked) {
                    // Un-check the other options.
                    $('input[name="format[in-person]"]').prop('checked', false);
                    $('input[name="format[online]"]').prop('checked', false);
                    $('input[name="format[on-demand]"]').prop('checked', false);
                    $('input[name="format[bundle]"]').prop('checked', false);
                }
            });
            selector = 'input[name="format[in-person]"], input[name="format[online]"], input[name="format[on-demand]"], input[name="format[bundle]"]';
            $(selector).on('change', function () {
                if (this.checked) {
                    $('input[name="format[any]"]').prop('checked', false);
                }
            });
        }
    };

})(jQuery, Drupal);
