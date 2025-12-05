<?php
/**
 * NgirimWA API Integration
 *
 * @package NgirimWA_Woo
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Send WhatsApp message via NgirimWA API
 *
 * @param string $receiver_number Recipient phone number.
 * @param string $message_text Message text to send.
 * @return bool|array Normalized API response on success, false on failure.
 */
function apivro_send_message($receiver_number, $message_text) {
    $api_key = get_option('apivro_woonotify_api_key', '');

    if (empty($api_key)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('NgirimWA Woo: API Key is not set.');
        }
        return false;
    }

    $receiver_number = sanitize_text_field($receiver_number);

    if (empty($receiver_number) || empty($message_text)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('NgirimWA Woo: Receiver number or message is empty.');
        }
        return false;
    }

    // NgirimWA base URL
    $url = 'https://dash.ngirimwa.com/api/v1/messages/send';

    $body = array(
        'to' => $receiver_number,
        'message' => $message_text,
    );

    $args = array(
        'timeout' => 30,
        'headers' => array(
            // NgirimWA expects 'x-api-key' header
            'x-api-key' => $api_key,
            'Content-Type' => 'application/json',
        ),
        'body' => wp_json_encode($body),
        'data_format' => 'body',
    );

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('NgirimWA Woo Error: ' . $response->get_error_message());
        }
        return false;
    }

    $http_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    // Normalize success: treat any 2xx as success; preserve existing 'success' if present
    $is_success = ($http_code >= 200 && $http_code < 300);
    if (isset($response_data['success'])) {
        $is_success = $is_success && (bool) $response_data['success'];
    }

    if ($is_success) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('NgirimWA Woo: Message sent successfully to ' . $receiver_number);
        }
        // Ensure top-level success flag for admin testing compatibility
        if (is_array($response_data)) {
            $response_data['success'] = true;
            return $response_data;
        }
        return array('success' => true);
    }

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('NgirimWA Woo: Failed to send to ' . $receiver_number . '. Response: ' . $response_body);
    }
    return false;
}

/**
 * Test NgirimWA API credentials
 *
 * @param string $api_key API Key from NgirimWA.
 * @return bool True if credentials are valid, false otherwise.
 */
function apivro_test_api_credentials($api_key) {
    if (empty($api_key)) {
        return false;
    }

    $test_number = '6285183380099';
    $test_message = 'This is a NgirimWA validation test.';

    // NgirimWA base URL for test
    $url = 'https://dash.ngirimwa.com/api/v1/messages/send';

    $body = array(
        'to' => $test_number,
        'message' => $test_message,
    );

    $args = array(
        'timeout' => 15,
        'headers' => array(
            'x-api-key' => $api_key,
            'Content-Type' => 'application/json',
        ),
        'body' => wp_json_encode($body),
        'data_format' => 'body',
    );

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        return false;
    }

    $http_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    // Consider any 2xx as success; also check 'success' when provided
    if ($http_code >= 200 && $http_code < 300) {
        if (isset($response_data['success'])) {
            return (bool) $response_data['success'];
        }
        return true;
    }

    return false;
}

