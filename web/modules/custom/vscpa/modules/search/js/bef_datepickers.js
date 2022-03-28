/**
 * @file bef_datepickers.js
 *
 * Provides jQueryUI Datepicker integration with Better Exposed Filters.
 */

(function ($, Drupal, drupalSettings) {
    /*
     * Helper functions
     */

    Drupal.behaviors.betterExposedFiltersDatePickers = {
        attach: function (context, settings) {

            // Check for and initialize datepickers
            var befSettings = drupalSettings.better_exposed_filters;
            if (befSettings && befSettings.datepicker && befSettings.datepicker_options && $.fn.datepicker) {
                var dateFormat = 'mm/dd/yy',
                    from = $("input[name='field_dates_times_value[min]']")
                        .datepicker({
                            defaultDate: "+1w",
                            changeMonth: true,
                            numberOfMonths: 1
                        })
                        .on("change", function () {
                            to.datepicker("option", "minDate", getDate(this));
                        }),
                    to = $("input[name='field_dates_times_value[max]']").datepicker({
                        defaultDate: "+1w",
                        changeMonth: true,
                        numberOfMonths: 1
                    })
                        .on("change", function () {
                            from.datepicker("option", "maxDate", getDate(this));
                        });

                function getDate(element) {
                    var date;
                    try {
                        date = $.datepicker.parseDate(dateFormat, element.value);
                    } catch (error) {
                        date = null;
                    }

                    return date;
                }
            }

        }
    };

})(jQuery, Drupal, drupalSettings);