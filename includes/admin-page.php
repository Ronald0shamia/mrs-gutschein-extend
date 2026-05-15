<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ===== ADMIN MENU =====
 */
add_action('admin_menu', 'mrs_gutschein_extend_admin_menu', 99);
add_action('admin_enqueue_scripts', 'mrs_gutschein_extend_admin_assets');

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

function mrs_gutschein_extend_admin_assets($hook_suffix) {
    if (false === strpos($hook_suffix, 'mrs-gutschein-extend')) {
        return;
    }

    wp_enqueue_style(
        'mrs-gutschein-extend-admin',
        MRS_GUTSCHEIN_EXTEND_URL . 'assets/admin.css',
        [],
        MRS_GUTSCHEIN_EXTEND_VERSION
    );

    wp_enqueue_script(
        'mrs-gutschein-extend-admin',
        MRS_GUTSCHEIN_EXTEND_URL . 'assets/admin.js',
        [],
        MRS_GUTSCHEIN_EXTEND_VERSION,
        true
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
        wp_die(esc_html__('You do not have permission to access this page.', 'mrs-gutschein-extend'));
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
            esc_html__('Settings saved.', 'mrs-gutschein-extend') .
            '</strong></p></div>';
    }

    $saved_coupons = mrs_gutschein_extend_get_selected_coupons();
    $notice_enabled = get_option('mrs_gutschein_notice_enabled', 'yes');
    $notice_text = mrs_gutschein_extend_get_notice_text();

    $coupons = get_posts([
        'post_type' => 'shop_coupon',
        'numberposts' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    $selected_count = count($saved_coupons);
    $coupon_count = count($coupons);
    ?>
    <div class="wrap woocommerce mrs-gutschein-admin">
        <div class="mrs-ge-header">
            <div class="mrs-ge-brand">
                <img
                    class="mrs-ge-logo"
                    src="<?php echo esc_url(MRS_GUTSCHEIN_EXTEND_URL . 'assets/mrs_icon.png'); ?>"
                    alt=""
                >
                <div>
                    <p class="mrs-ge-kicker"><?php esc_html_e('WooCommerce Coupon Control', 'mrs-gutschein-extend'); ?></p>
                    <h1><?php esc_html_e('MRS Gutschein Extend', 'mrs-gutschein-extend'); ?></h1>
                    <p class="mrs-ge-lead">
                        <?php esc_html_e(
                            'Control which coupons disable sale prices in the cart.',
                            'mrs-gutschein-extend'
                        ); ?>
                    </p>
                </div>
            </div>

            <div class="mrs-ge-stats" aria-label="<?php esc_attr_e('Plugin Status', 'mrs-gutschein-extend'); ?>">
                <div class="mrs-ge-stat">
                    <span><?php echo esc_html((string) $selected_count); ?></span>
                    <small><?php esc_html_e('Active coupons', 'mrs-gutschein-extend'); ?></small>
                </div>
                <div class="mrs-ge-stat">
                    <span><?php echo esc_html((string) $coupon_count); ?></span>
                    <small><?php esc_html_e('Available', 'mrs-gutschein-extend'); ?></small>
                </div>
                <div class="mrs-ge-pill">
                    <?php echo esc_html(MRS_GUTSCHEIN_EXTEND_VERSION); ?>
                </div>
            </div>
        </div>

        <form method="post" class="mrs-ge-form">
            <?php wp_nonce_field('mrs_gutschein_extend_save_settings', 'mrs_gutschein_extend_nonce'); ?>

            <div class="mrs-ge-layout">
                <section class="mrs-ge-panel mrs-ge-panel-main" aria-labelledby="mrs-ge-coupons-title">
                    <div class="mrs-ge-panel-head">
                        <div>
                            <h2 id="mrs-ge-coupons-title"><?php esc_html_e('Coupons', 'mrs-gutschein-extend'); ?></h2>
                            <p>
                                <?php esc_html_e(
                                    'Selected coupons disable sale prices for matching products and categories.',
                                    'mrs-gutschein-extend'
                                ); ?>
                            </p>
                        </div>
                        <label class="mrs-ge-search">
                            <span class="dashicons dashicons-search" aria-hidden="true"></span>
                            <input
                                type="search"
                                data-mrs-coupon-search
                                placeholder="<?php esc_attr_e('Search coupons...', 'mrs-gutschein-extend'); ?>"
                            >
                        </label>
                    </div>

                    <div class="mrs-ge-coupon-list" data-mrs-coupon-list>
                        <?php if (empty($coupons)): ?>
                            <div class="mrs-ge-empty">
                                <strong><?php esc_html_e('No coupons found.', 'mrs-gutschein-extend'); ?></strong>
                                <span><?php esc_html_e('Create a WooCommerce coupon first.', 'mrs-gutschein-extend'); ?></span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($coupons as $coupon): ?>
                                <?php
                                $coupon_code = $coupon->post_title;
                                $is_selected = in_array($coupon_code, $saved_coupons, true);
                                ?>
                                <label
                                    class="mrs-ge-coupon-row <?php echo $is_selected ? 'is-selected' : ''; ?>"
                                    data-mrs-coupon-row
                                    data-search="<?php echo esc_attr(strtolower($coupon_code)); ?>"
                                >
                                    <input
                                        type="checkbox"
                                        name="mrs_gutschein_coupons[]"
                                        value="<?php echo esc_attr($coupon_code); ?>"
                                        <?php checked($is_selected); ?>
                                    >
                                    <span class="mrs-ge-check" aria-hidden="true"></span>
                                    <span class="mrs-ge-coupon-content">
                                        <span class="mrs-ge-coupon-code"><?php echo esc_html($coupon_code); ?></span>
                                        <span class="mrs-ge-coupon-meta">
                                            <?php echo esc_html(sprintf(__('ID #%d', 'mrs-gutschein-extend'), $coupon->ID)); ?>
                                        </span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <aside class="mrs-ge-side">
                    <section class="mrs-ge-panel" aria-labelledby="mrs-ge-notice-title">
                        <div class="mrs-ge-panel-head">
                            <div>
                                <h2 id="mrs-ge-notice-title"><?php esc_html_e('Cart notice', 'mrs-gutschein-extend'); ?></h2>
                                <p><?php esc_html_e('Text and visibility for customers.', 'mrs-gutschein-extend'); ?></p>
                            </div>
                        </div>

                        <label class="mrs-ge-toggle">
                            <input
                                type="checkbox"
                                name="mrs_gutschein_notice_enabled"
                                value="yes"
                                <?php checked($notice_enabled, 'yes'); ?>
                            >
                            <span class="mrs-ge-toggle-track" aria-hidden="true"></span>
                            <span><?php esc_html_e('Show notice', 'mrs-gutschein-extend'); ?></span>
                        </label>

                        <label class="mrs-ge-field" for="mrs_gutschein_notice_text">
                            <span><?php esc_html_e('Notice text', 'mrs-gutschein-extend'); ?></span>
                        </label>
                        <input
                            type="text"
                            id="mrs_gutschein_notice_text"
                            name="mrs_gutschein_notice_text"
                            value="<?php echo esc_attr($notice_text); ?>"
                            class="mrs-ge-input"
                        >

                        <div class="mrs-ge-preview">
                            <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
                            <span><?php echo esc_html($notice_text); ?></span>
                        </div>
                    </section>

                    <section class="mrs-ge-panel" aria-labelledby="mrs-ge-mode-title">
                        <div class="mrs-ge-panel-head">
                            <div>
                                <h2 id="mrs-ge-mode-title"><?php esc_html_e('Rule mode', 'mrs-gutschein-extend'); ?></h2>
                                <p><?php esc_html_e('The plugin currently uses the product and category rules from each coupon.', 'mrs-gutschein-extend'); ?></p>
                            </div>
                        </div>
                        <div class="mrs-ge-mode">
                            <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                            <strong><?php esc_html_e('WooCommerce rules', 'mrs-gutschein-extend'); ?></strong>
                        </div>
                    </section>
                </aside>
            </div>

            <div class="mrs-ge-actions">
                <div>
                    <strong><?php esc_html_e('Ready to save', 'mrs-gutschein-extend'); ?></strong>
                    <span><?php esc_html_e('Changes are used immediately for new cart calculations.', 'mrs-gutschein-extend'); ?></span>
                </div>
                <?php submit_button(__('Save', 'mrs-gutschein-extend'), 'primary', 'submit', false); ?>
            </div>
        </form>
    </div>
    <?php
}
