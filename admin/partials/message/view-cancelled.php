<?php
if (!defined('ABSPATH')) {
    exit;
}

settings_errors('apivro_woonotify_messages');

$saved_messages = get_option('apivro_woonotify_settings', array());
$current_customer_message = isset($saved_messages['cancelled_order_customer']) ? $saved_messages['cancelled_order_customer'] : '';
$current_admin_message = isset($saved_messages['cancelled_order_admin']) ? $saved_messages['cancelled_order_admin'] : '';

apivro_woonotify_render_message_form(
    'apivro_cancelled_customer_message',
    'apivro_cancelled_admin_message',
    $current_customer_message,
    $current_admin_message,
    'apivro_cancelled_action',
    'apivro_cancelled_nonce',
    'submit_cancelled_message'
);
