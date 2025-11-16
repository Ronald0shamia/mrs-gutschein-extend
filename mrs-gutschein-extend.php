<?php
/**
 * Plugin Name: mrs Gutschein Extend
 * Plugin URI:  https://mrs-dev.com/plugin
 * Description: Free + Pro Gutschein-Logik für WooCommerce. Deaktiviert Angebotspreise bei bestimmten Gutscheinen/Produkten/Kategorien. Pro-Funktionen via Lemon Squeezy Lizenz freischaltbar.
 * Version:     1.1.0
 * Author:      MRS-DEV
 * Author URI:  https://mrs-dev.com
 * Text Domain: mrs-gutschein-extend
 * Domain Path: /languages
 * License:     GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

/* -------------------------
 * Konfiguration
 * ------------------------- */
if (!defined('MRSG_SLUG')) define('MRSG_SLUG', 'mrs-gutschein-extend');
// Trage hier (falls gewünscht) deine Verkaufs-URL ein (Lemon Squeezy Produktseite)
if (!defined('MRSG_BUY_URL')) define('MRSG_BUY_URL', 'https://your-lemonsqueezy-product-link.example');

// Produkt-ID aus deinem Lemon Squeezy Account (du hast gegeben)
if (!defined('MRSG_PRODUCT_ID')) define('MRSG_PRODUCT_ID', 694245);

/* Includes */
require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/license-system.php';
require_once plugin_dir_path(__FILE__) . 'includes/gutschein-logic.php';

/* i18n */
add_action('plugins_loaded', function(){
    load_plugin_textdomain('mrs-gutschein-extend', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});

/* Activation / Deactivation */
register_activation_hook(__FILE__, function(){
    if (!wp_next_scheduled('mrsg_daily_license_check')) {
        wp_schedule_event(time()+3600, 'daily', 'mrsg_daily_license_check');
    }
});
register_deactivation_hook(__FILE__, function(){
    wp_clear_scheduled_hook('mrsg_daily_license_check');
});
