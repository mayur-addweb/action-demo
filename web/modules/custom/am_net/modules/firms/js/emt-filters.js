/**
 * @file
 * EMT - Filters behaviors.
 *
 */
(function ($, Drupal) {

    // Explicitly update a url parameter using HTML5's replaceState().
    function handleEmployeeFilter($selector, $context) {
        var $search = $($selector, $context);
        var $element = $search.find('input.employee-search-input');
        var $items = $search.next(".firm-employee-list").find('.list-group-item');
        $element.on('keyup', function () {
            var value = $(this).val().toLowerCase();
            $items.filter(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    }

    // Filter behaviors.
    Drupal.behaviors.EMTFilters = {
        attach: function (context, settings) {
            // Add filter for 'Members'.
            handleEmployeeFilter('.employee-search-members', context);
            // Add filter for 'Need to Review'.
            handleEmployeeFilter('.employee-search-need_to_renew', context);
            // Add filter for 'Nonmembers'.
            handleEmployeeFilter('.employee-search-non_members', context);
        }
    };
})(jQuery, Drupal);