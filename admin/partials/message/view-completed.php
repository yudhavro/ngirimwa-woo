<?php
if (!defined('ABSPATH')) {
    exit;
}

settings_errors('apivro_woonotify_messages');

$saved_messages = get_option('apivro_woonotify_settings', array());
$current_customer_message = isset($saved_messages['completed_order_customer']) ? $saved_messages['completed_order_customer'] : '';
$current_admin_message = isset($saved_messages['completed_order_admin']) ? $saved_messages['completed_order_admin'] : '';

apivro_woonotify_render_message_form(
    'apivro_completed_customer_message',
    'apivro_completed_admin_message',
    $current_customer_message,
    $current_admin_message,
    'apivro_completed_action',
    'apivro_completed_nonce',
    'submit_completed_message'
);
