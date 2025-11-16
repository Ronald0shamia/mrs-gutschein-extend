<?php
if (!defined('ABSPATH')) exit;

/* Admin Menü: unter WooCommerce -> Marketing */
add_action('admin_menu', 'mrsg_admin_menu');
function mrsg_admin_menu() {
    add_submenu_page(
        'woocommerce-marketing',
        __('mrs Gutschein Extend', 'mrs-gutschein-extend'),
        __('mrs Gutschein Extend', 'mrs-gutschein-extend'),
        'manage_woocommerce',
        MRSG_SLUG,
        'mrsg_admin_page'
    );
}

function mrsg_admin_page() {
    if (!current_user_can('manage_woocommerce')) wp_die(__('Keine Berechtigung', 'mrs-gutschein-extend'));

    ?>
    <div class="wrap">
        <h1><?php _e('mrs Gutschein Extend', 'mrs-gutschein-extend'); ?></h1>
        <p><?php _e('Free + Pro: Free-Funktionen sind aktiv. Für Pro-Funktionen trage hier deinen Lemon Squeezy Lizenzschlüssel ein oder kaufe eine Lizenz.', 'mrs-gutschein-extend'); ?></p>

        <form method="post" action="options.php">
            <?php
            settings_fields('mrsg_settings_group');
            do_settings_sections('mrsg_settings');
            ?>
            <?php mrsg_settings_html(); ?>
            <?php submit_button(); ?>
        </form>

        <hr />

        <h2><?php _e('Pro kaufen / Lizenz verwalten', 'mrs-gutschein-extend'); ?></h2>
        <p>
            <?php
            $buy = get_option('mrsg_buy_url', MRSG_BUY_URL);
            if (!$buy) $buy = MRSG_BUY_URL;
            if ($buy) {
                printf(
                    __('Kaufe die Pro-Version: <a href="%s" target="_blank">Jetzt Pro kaufen</a>. Nach dem Kauf erhältst du einen Lizenzschlüssel, den du oben eintragen kannst.', 'mrs-gutschein-extend'),
                    esc_url($buy)
                );
            } else {
                _e('Lege die Kauf-URL in den Plugin-Einstellungen fest oder setze die Konstante MRSG_BUY_URL.', 'mrs-gutschein-extend');
            }
            ?>
        </p>
    </div>
    <?php
}

/* Register settings and fields */
add_action('admin_init', 'mrsg_register_settings');
function mrsg_register_settings(){
    register_setting('mrsg_settings_group', 'mrsg_lemonsqueezy_api_key');
    register_setting('mrsg_settings_group', 'mrsg_license_key');
    register_setting('mrsg_settings_group', 'mrsg_license_status');
    register_setting('mrsg_settings_group', 'mrsg_license_last_checked');
    register_setting('mrsg_settings_group', 'mrsg_buy_url');
}

/* Settings HTML (fields) */
function mrsg_settings_html() {
    $api = esc_attr(get_option('mrsg_lemonsqueezy_api_key', ''));
    $license = esc_attr(get_option('mrsg_license_key', ''));
    $status = esc_html(get_option('mrsg_license_status', 'not_checked'));
    $last = get_option('mrsg_license_last_checked', 0);
    $buy = esc_attr(get_option('mrsg_buy_url', MRSG_BUY_URL));
    $last_text = $last ? date_i18n('Y-m-d H:i', (int)$last) : __('Nie', 'mrs-gutschein-extend');
    ?>
    <h2><?php _e('Lizenz / API Einstellungen', 'mrs-gutschein-extend'); ?></h2>

    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php _e('Lemon Squeezy API Key', 'mrs-gutschein-extend'); ?></th>
            <td>
                <input type="password" name="mrsg_lemonsqueezy_api_key" value="<?php echo $api; ?>" style="width:400px;">
                <p class="description"><?php _e('Merchant API Token (nur Admins). Zum Validieren von Lizenzen.', 'mrs-gutschein-extend'); ?></p>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row"><?php _e('Lizenzschlüssel', 'mrs-gutschein-extend'); ?></th>
            <td>
                <input type="text" id="mrsg_license_key" name="mrsg_license_key" value="<?php echo $license; ?>" style="width:300px;">
                <button type="button" class="button" id="mrsg_check_license"><?php _e('Lizenz prüfen', 'mrs-gutschein-extend'); ?></button>
                <p id="mrsg_license_result" class="description">
                    <?php printf(__('Status: %s (Letzte Prüfung: %s)', 'mrs-gutschein-extend'), $status, $last_text); ?>
                </p>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row"><?php _e('Kauf-URL (optional)', 'mrs-gutschein-extend'); ?></th>
            <td>
                <input type="text" name="mrsg_buy_url" value="<?php echo $buy; ?>" style="width:400px;">
                <p class="description"><?php _e('Link zu deiner Lemon Squeezy Produktseite (wird im Admin angezeigt).', 'mrs-gutschein-extend'); ?></p>
            </td>
        </tr>
    </table>

    <script>
    (function($){
        $('#mrsg_check_license').on('click', function(){
            $('#mrsg_license_result').text('<?php _e('Prüfung läuft...', 'mrs-gutschein-extend'); ?>');
            $.post(ajaxurl, {
                action: 'mrsg_validate_license_ajax',
                license_key: $('#mrsg_license_key').val(),
                _wpnonce: '<?php echo wp_create_nonce('mrsg_license_nonce'); ?>'
            }, function(resp){
                if (resp.success) {
                    $('#mrsg_license_result').text(resp.data.message);
                } else {
                    $('#mrsg_license_result').text(resp.data ? resp.data.message : '<?php _e('Fehler', 'mrs-gutschein-extend'); ?>');
                }
            }, 'json');
        });
    })(jQuery);
    </script>
    <?php
}
