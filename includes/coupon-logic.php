<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ===== ANGEBOTSPREISE DEAKTIVIEREN =====
 */
add_action(
    'woocommerce_before_calculate_totals',
    'mrs_gutschein_extend_disable_sale_prices',
    20
);

function mrs_gutschein_extend_disable_sale_prices($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    $target_coupon = strtolower(get_option('mrs_gutschein_coupon'));
    if (!$target_coupon) {
        return;
    }

    $applied = array_map('strtolower', $cart->get_applied_coupons());
    if (!in_array($target_coupon, $applied)) {
        return;
    }

    $coupon = new WC_Coupon($target_coupon);
    $product_ids  = $coupon->get_product_ids();
    $category_ids = $coupon->get_product_categories();

    $notice_shown = false;

    foreach ($cart->get_cart() as $item) {
        $product = $item['data'];
        $product_id = $product->get_id();
        $categories = wc_get_product_term_ids($product_id, 'product_cat');

        $match_product  = in_array($product_id, $product_ids);
        $match_category = array_intersect($category_ids, $categories);

        if ($match_product || !empty($match_category)) {
            if ($product->is_on_sale()) {
                $product->set_price($product->get_regular_price());

                if (!$notice_shown) {
                    wc_add_notice(
                        __('Angebotspreise wurden deaktiviert, da der Gutschein angewendet wurde.', 'mrs-gutschein-extend'),
                        'notice'
                    );
                    $notice_shown = true;
                }
            }
        }
    }
}
