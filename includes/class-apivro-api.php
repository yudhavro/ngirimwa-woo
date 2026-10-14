<?php
/**
 * NgirimWA API Integration (API v1).
 *
 * Errors that retrying cannot fix (deleted/inactive API key, device logged out, quota used up,
 * inactive subscription) PAUSE all sending until the admin fixes the cause — a broken setup must
 * never keep hammering the API with requests that are rejected anyway.
 *
 * @package NgirimWA_Woo
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Codes that will not succeed by retrying. */
function apivro_woonotify_pause_codes() {
    return array(
        'api_key_missing',
        'invalid_api_key',
        'api_key_inactive',
        'subscription_inactive',
        'account_suspended',
        'device_logged_out',
        'device_required',
        'quota_exceeded',
    );
}

function apivro_woonotify_api_base_url() {
    return apply_filters('apivro_woonotify_api_base_url', 'https://dash.ngirimwa.com/api/v1');
}

/**
 * Low-level request. Returns array: ok, http, code, message, data, retry_after.
 */
function apivro_woonotify_request($method, $path, $body = null, $headers = array(), $api_key = null) {
    $api_key = trim((string) ($api_key === null ? get_option('apivro_woonotify_api_key', '') : $api_key));
    $result = function ($ok, $http, $code, $message, $data = array(), $retry_after = 0) {
        return compact('ok', 'http', 'code', 'message', 'data', 'retry_after');
    };
    if ($api_key === '') {
        return $result(false, 0, 'api_key_missing', __('API Key is not set.', 'apivro-woonotify'));
    }

    $args = array(
        'method'  => $method,
        'timeout' => 20,
        'headers' => array_merge(array('x-api-key' => $api_key, 'Accept' => 'application/json'), $headers),
    );
    if ($body !== null) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body'] = wp_json_encode($body);
        $args['data_format'] = 'body';
    }

    $response = wp_remote_request(apivro_woonotify_api_base_url() . $path, $args);
    if (is_wp_error($response)) {
        return $result(false, 0, 'network_error', $response->get_error_message());
    }

    $http = (int) wp_remote_retrieve_response_code($response);
    $json = json_decode(wp_remote_retrieve_body($response), true);
    $retry_after = absint(wp_remote_retrieve_header($response, 'retry-after'));
    if (!is_array($json)) {
        return $result(false, $http, $http >= 500 ? 'internal_error' : 'invalid_response', 'HTTP ' . $http, array(), $retry_after);
    }
    $ok = $http >= 200 && $http < 300 && !empty($json['success']);
    $code = isset($json['details']['code']) ? (string) $json['details']['code'] : ($ok ? 'ok' : 'http_' . $http);
    $message = isset($json['message']) ? (string) $json['message'] : '';
    $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : array();
    return $result($ok, $http, $code, $message, $data, $retry_after);
}

/** Last failed send result of this request (for retry decisions). */
function apivro_woonotify_last_error($set = null) {
    static $last = null;
    if ($set !== null) {
        $last = $set;
    }
    return $last;
}

function apivro_woonotify_paused() {
    $paused = get_option('apivro_woonotify_paused');
    return is_array($paused) ? $paused : null;
}

function apivro_woonotify_pause($result) {
    update_option('apivro_woonotify_paused', array(
        'code'    => $result['code'],
        'message' => apivro_woonotify_describe_error($result),
        'time'    => current_time('mysql'),
    ), false);
}

function apivro_woonotify_resume() {
    delete_option('apivro_woonotify_paused');
}

function apivro_woonotify_describe_error($result) {
    $known = array(
        'api_key_missing'       => __('API Key is not set.', 'apivro-woonotify'),
        'invalid_api_key'       => __('API Key is invalid or was deleted. Create a new key in the NgirimWA dashboard.', 'apivro-woonotify'),
        'api_key_inactive'      => __('API Key is inactive. Activate it in the NgirimWA dashboard.', 'apivro-woonotify'),
        'subscription_inactive' => __('Your NgirimWA subscription is not active.', 'apivro-woonotify'),
        'account_suspended'     => __('Your NgirimWA account is suspended. Contact support.', 'apivro-woonotify'),
        'device_logged_out'     => __('The WhatsApp device is logged out. Scan the QR code again in the NgirimWA dashboard.', 'apivro-woonotify'),
        'device_required'       => __('This API Key has no device. Create the key from a device in the NgirimWA dashboard.', 'apivro-woonotify'),
        'quota_exceeded'        => __('Monthly message quota is used up.', 'apivro-woonotify'),
        'network_error'         => __('Could not reach NgirimWA.', 'apivro-woonotify'),
    );
    return isset($known[$result['code']]) ? $known[$result['code']] : ($result['message'] !== '' ? $result['message'] : $result['code']);
}

/**
 * Send WhatsApp message via NgirimWA API.
 *
 * @param string $receiver_number Recipient phone number or group ID.
 * @param string $message_text    Message text to send.
 * @param string $idempotency_key Same key = accepted only once (safe retries).
 * @param string $ref_id          Your reference, e.g. WC-1234.
 * @return bool|array Normalized API response on success, false on failure (see apivro_woonotify_last_error()).
 */
function apivro_send_message($receiver_number, $message_text, $idempotency_key = '', $ref_id = '') {
    $receiver_number = sanitize_text_field($receiver_number);
    if (empty($receiver_number) || empty($message_text)) {
        apivro_woonotify_last_error(array('ok' => false, 'http' => 0, 'code' => 'validation_error', 'message' => 'Empty recipient or message', 'data' => array(), 'retry_after' => 0));
        return false;
    }

    $paused = apivro_woonotify_paused();
    if ($paused) {
        apivro_woonotify_last_error(array('ok' => false, 'http' => 0, 'code' => 'paused', 'message' => $paused['message'], 'data' => array(), 'retry_after' => 0));
        return false;
    }

    $body = array('to' => $receiver_number, 'message' => $message_text);
    if ($ref_id !== '') {
        $body['ref_id'] = substr($ref_id, 0, 100);
    }
    $headers = $idempotency_key !== '' ? array('Idempotency-Key' => substr($idempotency_key, 0, 64)) : array();

    $result = apivro_woonotify_request('POST', '/messages/send', $body, $headers);
    if ($result['ok']) {
        $data = $result['data'];
        $data['success'] = true;
        return $data;
    }

    apivro_woonotify_last_error($result);
    if (in_array($result['code'], apivro_woonotify_pause_codes(), true)) {
        apivro_woonotify_pause($result);
    }
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('NgirimWA Woo: Failed to send to ' . $receiver_number . ' (' . $result['code'] . '): ' . $result['message']);
    }
    return false;
}

/**
 * Check the API key + device without sending a message (GET /devices/status).
 *
 * @return array Result (see apivro_woonotify_request) — data.status = connected|connecting|disconnected.
 */
function apivro_woonotify_check_connection($api_key = null) {
    return apivro_woonotify_request('GET', '/devices/status', null, array(), $api_key);
}

/**
 * Back-compat wrapper: true when the key is valid. No longer sends a test message.
 */
function apivro_test_api_credentials($api_key) {
    $result = apivro_woonotify_check_connection($api_key);
    return $result['ok'];
}
