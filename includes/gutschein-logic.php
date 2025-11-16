<?php
if (!defined('ABSPATH')) exit;

/**
 * Hauptlogik für Gutscheine:
 * - Free: Grundfunktion (1 Gutschein)
 * - Pro : erweitert (mehrere Gutscheine / Kategorie-Logik)
 */
add_action('woocommerce_before_calculate_totals', 'mrsg_handle_gutschein_logic', 20, 1);

function mrsg_handle_gutschein_logic($cart) {
    // Vermeide Backend-Manipulation
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (empty($cart->get_cart())) return;

    $applied = $cart->get_applied_coupons();
    if (empty($applied)) return;

    $notice_shown = false;

    foreach ($applied as $coupon_code) {
        $coupon = new WC_Coupon($coupon_code);
        $coupon_product_ids = (array) $coupon->get_product_ids();
        $coupon_cat_ids = (array) $coupon->get_product_categories();

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();

            $in_product = in_array($product_id, $coupon_product_ids);
            $product_cats = wc_get_product_term_ids($product_id, 'product_cat');
            $in_category = !empty(array_intersect($product_cats, $coupon_cat_ids));

            if (!mrsg_is_pro()) {
                // Free: einfache Regel - nur erste passende Gutscheinregel wirkt
                if (($in_product || $in_category) && $product->is_on_sale()) {
                    $regular = $product->get_regular_price();
                    $product->set_price($regular);

                    if (!$notice_shown) {
                        wc_add_notice(__('Angebotspreise wurden deaktiviert, da ein Gutschein angewendet wurde. Für mehr Funktionen bitte Pro kaufen.', 'mrs-gutschein-extend'), 'notice');
                        $notice_shown = true;
                    }
                }
            } else {
                // PRO: erweiterte Regeln (z.B. mehrere Gutscheine, Kategorien)
                if (($in_product || $in_category) && $product->is_on_sale()) {
                    $product->set_price($product->get_regular_price());
                    if (!$notice_shown) {
                        wc_add_notice(__('Angebotspreise wurden deaktiviert (Pro).', 'mrs-gutschein-extend'), 'notice');
                        $notice_shown = true;
                    }
                }
            }
        }
    }
}
