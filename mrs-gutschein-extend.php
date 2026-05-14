<?php
/**
 * Plugin Name: MRS Gutschein Extend
 * Plugin URI:  https://mrs-dev.com/mrs-gutschein-extend/
 * Description: Extends WooCommerce coupons by disabling sale prices when selected coupons are applied.
 * Author: MRS DEV
 * Version: 1.1.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mrs-gutschein-extend
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('MRS_GUTSCHEIN_EXTEND_VERSION')) {
    define('MRS_GUTSCHEIN_EXTEND_VERSION', '1.1.0');
}

if (!defined('MRS_GUTSCHEIN_EXTEND_PATH')) {
    define('MRS_GUTSCHEIN_EXTEND_PATH', plugin_dir_path(__FILE__));
}

if (!defined('MRS_GUTSCHEIN_EXTEND_URL')) {
    define('MRS_GUTSCHEIN_EXTEND_URL', plugin_dir_url(__FILE__));
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
    require_once MRS_GUTSCHEIN_EXTEND_PATH . 'includes/admin-page.php';
}

require_once MRS_GUTSCHEIN_EXTEND_PATH . 'includes/coupon-logic.php';
