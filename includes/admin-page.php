<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ===== ADMIN MENU =====
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

function mrs_gutschein_extend_get_selected_coupons() {
    $saved_coupons = get_option('mrs_gutschein_coupons', []);

    if (!is_array($saved_coupons)) {
        $saved_coupons = [];
    }

    $legacy_coupon = get_option('mrs_gutschein_coupon', '');
    if ($legacy_coupon && empty($saved_coupons)) {
        $saved_coupons[] = $legacy_coupon;
    }

    return array_values(array_unique(array_filter(array_map('sanitize_text_field', $saved_coupons))));
}

function mrs_gutschein_extend_admin_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('Keine Berechtigung.', 'mrs-gutschein-extend'));
    }

    if (
        isset($_POST['mrs_gutschein_extend_nonce'])
        && wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['mrs_gutschein_extend_nonce'])),
            'mrs_gutschein_extend_save_settings'
        )
    ) {
        $coupon_values = isset($_POST['mrs_gutschein_coupons'])
            ? (array) wp_unslash($_POST['mrs_gutschein_coupons'])
            : [];

        $selected_coupons = array_values(array_unique(array_filter(array_map(
            'sanitize_text_field',
            $coupon_values
        ))));

        $notice_enabled = isset($_POST['mrs_gutschein_notice_enabled']) ? 'yes' : 'no';
        $notice_text = isset($_POST['mrs_gutschein_notice_text'])
            ? sanitize_text_field(wp_unslash($_POST['mrs_gutschein_notice_text']))
            : '';

        update_option('mrs_gutschein_coupons', $selected_coupons);
        update_option('mrs_gutschein_notice_enabled', $notice_enabled);
        update_option('mrs_gutschein_notice_text', $notice_text);

        echo '<div class="notice notice-success is-dismissible"><p><strong>' .
            esc_html__('Einstellungen gespeichert!', 'mrs-gutschein-extend') .
            '</strong></p></div>';
    }

    $saved_coupons = mrs_gutschein_extend_get_selected_coupons();
    $notice_enabled = get_option('mrs_gutschein_notice_enabled', 'yes');
    $notice_text = get_option(
        'mrs_gutschein_notice_text',
        __('Angebotspreise wurden deaktiviert, da ein Gutschein angewendet wurde.', 'mrs-gutschein-extend')
    );

    $coupons = get_posts([
        'post_type' => 'shop_coupon',
        'numberposts' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    ?>
    <div class="wrap woocommerce">
        <h1><?php esc_html_e('MRS Gutschein Extend', 'mrs-gutschein-extend'); ?></h1>

        <p>
            <?php esc_html_e(
                'Waehle die Gutscheine aus, die Angebotspreise deaktivieren sollen, sobald sie im Warenkorb verwendet werden.',
                'mrs-gutschein-extend'
            ); ?>
        </p>

        <form method="post">
            <?php wp_nonce_field('mrs_gutschein_extend_save_settings', 'mrs_gutschein_extend_nonce'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="mrs_gutschein_coupons">
                            <?php esc_html_e('Gutscheine auswaehlen', 'mrs-gutschein-extend'); ?>
                        </label>
                    </th>
                    <td>
                        <select
                            id="mrs_gutschein_coupons"
                            name="mrs_gutschein_coupons[]"
                            multiple
                            size="8"
                            style="min-width:320px;"
                        >
                            <?php foreach ($coupons as $coupon): ?>
                                <option value="<?php echo esc_attr($coupon->post_title); ?>"
                                    <?php selected(in_array($coupon->post_title, $saved_coupons, true)); ?>>
                                    <?php echo esc_html($coupon->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Mehrere Gutscheine mit Strg/Cmd + Klick auswaehlen.', 'mrs-gutschein-extend'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php esc_html_e('Warenkorb-Hinweis', 'mrs-gutschein-extend'); ?>
                    </th>
                    <td>
                        <label>
                            <input
                                type="checkbox"
                                name="mrs_gutschein_notice_enabled"
                                value="yes"
                                <?php checked($notice_enabled, 'yes'); ?>
                            >
                            <?php esc_html_e('Hinweis anzeigen, wenn Angebotspreise deaktiviert wurden', 'mrs-gutschein-extend'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="mrs_gutschein_notice_text">
                            <?php esc_html_e('Hinweistext', 'mrs-gutschein-extend'); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="mrs_gutschein_notice_text"
                            name="mrs_gutschein_notice_text"
                            value="<?php echo esc_attr($notice_text); ?>"
                            class="regular-text"
                        >
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Speichern', 'mrs-gutschein-extend')); ?>
        </form>
    </div>
    <?php
}
