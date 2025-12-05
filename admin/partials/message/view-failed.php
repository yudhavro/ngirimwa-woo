<?php
if (!defined('ABSPATH')) {
    exit;
}

settings_errors('apivro_woonotify_messages');

$saved_messages = get_option('apivro_woonotify_settings', array());
$current_customer_message = isset($saved_messages['failed_order_customer']) ? $saved_messages['failed_order_customer'] : '';
$current_admin_message = isset($saved_messages['failed_order_admin']) ? $saved_messages['failed_order_admin'] : '';

apivro_woonotify_render_message_form(
    'apivro_failed_customer_message',
    'apivro_failed_admin_message',
    $current_customer_message,
    $current_admin_message,
    'apivro_failed_action',
    'apivro_failed_nonce',
    'submit_failed_message'
);
