/**
 * @file
 * Event Add to Cart.
 *
 */
(function ($, Drupal) {
    // Disable Add to Cart Button during Ajax refresh.
    Drupal.behaviors.AddToCart = {
        attach: function (context, settings) {
            var filter = '.commerce-order-item-add-to-cart-form .field--name-purchased-entity select';
            var btn_selector = '.commerce-order-item-add-to-cart-form .form-actions a.btn';
            // Redirect after select filter.
            $(context).find(filter).each(function() {
                $(this).change(function() {
                    $btn_element = jQuery(btn_selector);
                    if ($btn_element.length > 0) {
                        $btn_element.off('click').attr("href", "javascript: void(0);").addClass('add-to-cart-disable');
                    }
                });
            });
        }
    };
})(jQuery, Drupal);