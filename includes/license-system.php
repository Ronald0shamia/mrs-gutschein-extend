<?php
if (!defined('ABSPATH')) exit;

/**
 * AJAX handler, API call, helper functions
 */

/* AJAX */
add_action('wp_ajax_mrsg_validate_license_ajax', 'mrsg_validate_license_ajax');
function mrsg_validate_license_ajax() {
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Keine Berechtigung', 'mrs-gutschein-extend')]);
    }
    check_ajax_referer('mrsg_license_nonce', '_wpnonce');

    $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
    if (empty($license_key)) wp_send_json_error(['message' => __('Lizenzschlüssel fehlt', 'mrs-gutschein-extend')]);

    $api_key = get_option('mrsg_lemonsqueezy_api_key', '');
    if (empty($api_key)) wp_send_json_error(['message' => __('API Key fehlt. Bitte in den Einstellungen eintragen.', 'mrs-gutschein-extend')]);

    $res = mrsg_validate_license_with_lemonsqueezy($license_key, $api_key);

    // Update options
    update_option('mrsg_license_key', $license_key);
    update_option('mrsg_license_status', $res['status']);
    update_option('mrsg_license_last_checked', time());

    if ($res['ok']) {
        wp_send_json_success(['message' => $res['message']]);
    } else {
        wp_send_json_error(['message' => $res['message']]);
    }
}

/* Validate via Lemon Squeezy API */
function mrsg_validate_license_with_lemonsqueezy($license_key, $api_key) {
    $url = 'https://api.lemonsqueezy.com/v1/licenses/validate';
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ],
        'body' => wp_json_encode(['license_key' => $license_key]),
        'timeout' => 20,
    ];

    $resp = wp_remote_post($url, $args);
    if (is_wp_error($resp)) {
        return ['ok' => false, 'status' => 'invalid', 'message' => __('API Fehler: ', 'mrs-gutschein-extend') . $resp->get_error_message()];
    }

    $code = wp_remote_retrieve_response_code($resp);
    $body = json_decode(wp_remote_retrieve_body($resp), true);

    if ($code >= 200 && $code < 300 && isset($body['data']['attributes'])) {
        $attr = $body['data']['attributes'];
        // State/status
        $state = '';
        if (isset($attr['state'])) $state = $attr['state'];
        if (empty($state) && isset($attr['status'])) $state = $attr['status'];
        $valid_until = isset($attr['valid_until']) ? $attr['valid_until'] : '';

        // Optional: prüfen, ob Lizenz zum Produkt gehört
        $product_ok = true;
        // Einige API-Responses haben relationship oder purchase info
        if (isset($body['data']['relationships']['purchase']['data']['id'])) {
            // purchase id available - we can't map product directly here reliably in all responses
            // Keep product_ok true (optional advanced check could query purchases endpoint)
        }
        // Wenn API attribute product_id vorhanden:
        if (isset($attr['product_id']) && MRSG_PRODUCT_ID) {
            $product_ok = ((int)$attr['product_id'] === (int)MRSG_PRODUCT_ID);
        }

        if (in_array($state, ['active','valid']) && $product_ok) {
            $message = __('Lizenz gültig', 'mrs-gutschein-extend');
            if ($valid_until) $message .= ' — ' . sprintf(__('Gültig bis: %s', 'mrs-gutschein-extend'), esc_html($valid_until));
            return ['ok' => true, 'status' => 'valid', 'message' => $message];
        } else {
            $msg = __('Lizenz nicht aktiv oder gehört nicht zum Produkt.', 'mrs-gutschein-extend');
            return ['ok' => false, 'status' => 'invalid', 'message' => $msg];
        }
    }

    // Fehlerdetails
    $err = __('Ungültige Antwort von Lemon Squeezy.', 'mrs-gutschein-extend');
    if (isset($body['errors']) && is_array($body['errors'])) {
        $err = implode(' | ', array_map(function($e){ return isset($e['detail']) ? $e['detail'] : json_encode($e); }, $body['errors']));
    }
    return ['ok' => false, 'status' => 'invalid', 'message' => $err];
}

/* Helper: Is Pro active? */
function mrsg_is_pro() {
    return get_option('mrsg_license_status') === 'valid';
}

/* Cron-Task: tägliche Re-Validierung */
add_action('mrsg_daily_license_check', function(){
    $key = get_option('mrsg_license_key', '');
    $api = get_option('mrsg_lemonsqueezy_api_key', '');
    if (!$key || !$api) return;
    $res = mrsg_validate_license_with_lemonsqueezy($key, $api);
    update_option('mrsg_license_status', $res['status']);
});
