<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ===== ADMIN MENÜ =====
 */
add_action('admin_menu', 'mrs_gutschein_extend_admin_menu', 99);

function mrs_gutschein_extend_admin_menu() {
    add_submenu_page(
        'woocommerce-marketing',
        __('MRS Gutschein Extend', 'mrs-gutschein-extend'),
        __('MRS Gutschein Extend', 'mrs-gutschein-extend'),
        'manage_woocommerce',
        'mrs-gutschein-extend',
        'mrs_gutschein_extend_admin_page'
    );
}

function mrs_gutschein_extend_admin_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(__('Keine Berechtigung.', 'mrs-gutschein-extend'));
    }

    if (isset($_POST['mrs_gutschein_coupon'])) {
        update_option(
            'mrs_gutschein_coupon',
            sanitize_text_field($_POST['mrs_gutschein_coupon'])
        );
        echo '<div class="updated"><p><strong>' .
            esc_html__('Einstellung gespeichert!', 'mrs-gutschein-extend') .
            '</strong></p></div>';
    }

    $saved_coupon = get_option('mrs_gutschein_coupon', '');
    $coupons = get_posts([
        'post_type'   => 'shop_coupon',
        'numberposts' => -1,
        'post_status' => 'publish'
    ]);
    ?>
    <div class="wrap woocommerce">
        <h1><?php _e('MRS Gutschein Extend', 'mrs-gutschein-extend'); ?></h1>

        <p>
            <?php _e(
                'Wähle den Gutschein aus, der Angebotspreise deaktivieren soll, sobald er im Warenkorb verwendet wird.',
                'mrs-gutschein-extend'
            ); ?>
        </p>

        <form method="post">
            <table class="form-table">
                <tr>
                    <th><?php _e('Gutschein auswählen', 'mrs-gutschein-extend'); ?></th>
                    <td>
                        <select name="mrs_gutschein_coupon" style="min-width:260px;">
                            <option value="">
                                <?php _e('Bitte wählen...', 'mrs-gutschein-extend'); ?>
                            </option>
                            <?php foreach ($coupons as $coupon): ?>
                                <option value="<?php echo esc_attr($coupon->post_title); ?>"
                                    <?php selected($saved_coupon, $coupon->post_title); ?>>
                                    <?php echo esc_html($coupon->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Speichern', 'mrs-gutschein-extend')); ?>
        </form>
    </div>
    <?php
}
