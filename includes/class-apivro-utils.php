<?php
/**
 * Utility Functions
 *
 * @package NgirimWA_Woo
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Parse recipients string into array of formatted phone numbers or group IDs
 *
 * @param string $recipients Comma-separated recipients (phone numbers or group IDs).
 * @return array Array of formatted recipients.
 */
function apivro_woonotify_parse_recipients($recipients) {
    if (empty($recipients)) {
        return array();
    }

    $recipients = preg_replace('/\s*,\s*/', ',', $recipients);
    $recipient_list = explode(',', $recipients);
    $parsed = array();
    
    foreach ($recipient_list as $recipient) {
        $recipient = trim($recipient);
        if (empty($recipient)) {
            continue;
        }
        
        if (strpos($recipient, '@g.us') !== false) {
            $parsed[] = $recipient;
        } else {
            $formatted = apivro_woonotify_format_phone_number($recipient);
            if (!empty($formatted)) {
                $parsed[] = $formatted;
            }
        }
    }
    
    return array_filter($parsed);
}

/**
 * Format phone number to international format
 *
 * @param string $phone Phone number to format.
 * @return string Formatted phone number or empty string if invalid.
 */
function apivro_woonotify_format_phone_number($phone) {
    if (empty($phone)) {
        return '';
    }

    if (strpos($phone, '@g.us') !== false) {
        return $phone;
    }

    $phone = preg_replace('/[^0-9+]/', '', $phone);

    if (substr($phone, 0, 1) === '0') {
        return '62' . substr($phone, 1);
    }

    if (substr($phone, 0, 1) === '+') {
        return substr($phone, 1);
    }

    return $phone;
}
