/**
 * @file
 * EMT - Manage Employees Dues behaviors.
 *
 */
(function ($, Drupal) {
    // Empty Js implementation.
    function empty(mixedVar) {
        //  discuss at: http://locutus.io/php/empty/
        // original by: Philippe Baumann
        //    input by: Onno Marsman (https://twitter.com/onnomarsman)
        //    input by: LH
        //    input by: Stoyan Kyosev (http://www.svest.org/)
        // bugfixed by: Kevin van Zonneveld (http://kvz.io)
        // improved by: Onno Marsman (https://twitter.com/onnomarsman)
        // improved by: Francesco
        // improved by: Marc Jansen
        // improved by: Rafał Kukawski (http://blog.kukawski.pl)
        //   example 1: empty(null)
        //   returns 1: true
        //   example 2: empty(undefined)
        //   returns 2: true
        //   example 3: empty([])
        //   returns 3: true
        //   example 4: empty({})
        //   returns 4: true
        //   example 5: empty({'aFunc' : function () { alert('humpty'); } })
        //   returns 5: false
        var undef
        var key
        var i
        var len
        var emptyValues = [undef, null, false, 0, '', '0']

        for (i = 0, len = emptyValues.length; i < len; i++) {
            if (mixedVar === emptyValues[i]) {
                return true
            }
        }

        if (typeof mixedVar === 'object') {
            for (key in mixedVar) {
                if (mixedVar.hasOwnProperty(key)) {
                    return false
                }
            }
            return true
        }

        return false
    }

    // Re-calculate Row totals.
    function updateRowTotals(context, $element) {
        $contribution_value = $element.val();
        $id_selector = $element.attr('data-id');
        if (!empty($id_selector)) {
            // Dues Balance.
            $dues_balance = 0;
            $dues_balance_selector = 'div.dues-balance-' + $id_selector;
            $dues_balance_element = $($dues_balance_selector, context);
            if (!empty($dues_balance_element)) {
                $dues_balance = $dues_balance_element.html();
            }
            // Pac Contribution value:
            $pac_contribution = 0;
            $pac_contribution_selector = 'input.pac-balance-' + $id_selector;
            $pac_contribution_element = $($pac_contribution_selector, context);
            if (!empty($pac_contribution_element)) {
                $pac_contribution = $pac_contribution_element.val();
            }
            // Educational Contribution value:
            $educational_contribution = 0;
            $educational_contribution_selector = 'input.educational-balance-' + $id_selector;
            $educational_contribution_element = $($educational_contribution_selector, context);
            if (!empty($educational_contribution_element)) {
                $educational_contribution = $educational_contribution_element.val();
            }
            // Calculate new total.
            $total = parseFloat($dues_balance) + parseFloat($pac_contribution) + parseFloat($educational_contribution);
            // Set the change on the total row balance.
            $total_selector = 'div.total-' + $id_selector;
            $total_element = $($total_selector, context);
            if (!empty($total_element)) {
                $label = '$' + Number($total).toFixed(2);
                $total_element.html($label);
            }
        }
    }

    // Manage Employees Dues - behaviors.
    Drupal.behaviors.ManageEmployeesDues = {
        attach: function (context, settings) {
            $('.money-contribution', context).change(function () {
                updateRowTotals(context, $(this));
            });
            // Hook Clear Donation button action.
            $('.clear-donations', context).click(function( event ) {
                event.preventDefault();
                event.stopPropagation();
                $('.money-contribution').val(0).change();
            });
        }
    };
})(jQuery, Drupal);