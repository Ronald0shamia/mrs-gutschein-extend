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

function mrs_gutschein_extend_get_configured_coupons() {
    $coupons = get_option('mrs_gutschein_coupons', []);

    if (!is_array($coupons)) {
        $coupons = [];
    }

    $legacy_coupon = get_option('mrs_gutschein_coupon', '');
    if ($legacy_coupon && empty($coupons)) {
        $coupons[] = $legacy_coupon;
    }

    return array_values(array_unique(array_filter(array_map('wc_format_coupon_code', $coupons))));
}

function mrs_gutschein_extend_product_matches_coupon($product, $coupon) {
    $product_id = $product->get_id();
    $parent_id = $product->get_parent_id();
    $lookup_product_id = $parent_id ? $parent_id : $product_id;

    $product_ids = array_map('absint', $coupon->get_product_ids());
    $category_ids = array_map('absint', $coupon->get_product_categories());

    if (empty($product_ids) && empty($category_ids)) {
        return true;
    }

    if (in_array($product_id, $product_ids, true) || in_array($lookup_product_id, $product_ids, true)) {
        return true;
    }

    $categories = wc_get_product_term_ids($lookup_product_id, 'product_cat');

    return !empty(array_intersect($category_ids, $categories));
}

function mrs_gutschein_extend_maybe_show_notice() {
    if ('yes' !== get_option('mrs_gutschein_notice_enabled', 'yes')) {
        return;
    }

    $notice_text = get_option(
        'mrs_gutschein_notice_text',
        __('Angebotspreise wurden deaktiviert, da ein Gutschein angewendet wurde.', 'mrs-gutschein-extend')
    );

    if (!$notice_text) {
        return;
    }

    wc_add_notice(wp_kses_post($notice_text), 'notice');
}

function mrs_gutschein_extend_disable_sale_prices($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (!$cart || !is_a($cart, 'WC_Cart')) {
        return;
    }

    $configured_coupons = mrs_gutschein_extend_get_configured_coupons();
    if (empty($configured_coupons)) {
        return;
    }

    $applied_coupons = array_map('wc_format_coupon_code', $cart->get_applied_coupons());
    $matching_coupon_codes = array_intersect($configured_coupons, $applied_coupons);
    if (empty($matching_coupon_codes)) {
        return;
    }

    $matching_coupons = array_map(
        static function ($coupon_code) {
            return new WC_Coupon($coupon_code);
        },
        $matching_coupon_codes
    );

    $notice_shown = false;

    foreach ($cart->get_cart() as $item) {
        if (empty($item['data']) || !is_a($item['data'], 'WC_Product')) {
            continue;
        }

        $product = $item['data'];

        if (!$product->is_on_sale()) {
            continue;
        }

        foreach ($matching_coupons as $coupon) {
            if (!mrs_gutschein_extend_product_matches_coupon($product, $coupon)) {
                continue;
            }

            $regular_price = $product->get_regular_price();
            if ('' === $regular_price) {
                continue;
            }

            $product->set_price($regular_price);

            if (!$notice_shown) {
                mrs_gutschein_extend_maybe_show_notice();
                $notice_shown = true;
            }

            break;
        }
    }
}
