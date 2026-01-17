<?php
/**
 * Plugin Name: MRS Gutschein Extend
 * Plugin URI:  https://mrs-dev.com/mrs-gutschein-extend/
 * Description: Extends WooCommerce coupons by disabling sale prices when selected coupons are applied.
 * Author: MRS DEV
 * Version: 1.0.0
 * License: GPL v2 or later
 * Text Domain: mrs-gutschein-extend
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ===== TEXTDOMAIN LADEN =====
 */
add_action('plugins_loaded', 'mrs_gutschein_extend_load_textdomain');
function mrs_gutschein_extend_load_textdomain() {
    load_plugin_textdomain(
        'mrs-gutschein-extend',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}

/**
 * ===== ADMIN & LOGIK LADEN =====
 */
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
}

require_once plugin_dir_path(__FILE__) . 'includes/coupon-logic.php';
