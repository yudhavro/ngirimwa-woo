<?php
if (!defined('ABSPATH')) {
    exit;
}

settings_errors('apivro_woonotify_messages');

$saved_messages = get_option('apivro_woonotify_settings', array());
$current_customer_message = isset($saved_messages['new_order_customer']) ? $saved_messages['new_order_customer'] : '';
$current_admin_message = isset($saved_messages['new_order_admin']) ? $saved_messages['new_order_admin'] : '';

apivro_woonotify_render_message_form(
    'apivro_neworder_customer_message',
    'apivro_neworder_admin_message',
    $current_customer_message,
    $current_admin_message,
    'apivro_neworder_action',
    'apivro_neworder_nonce',
    'submit_neworder_message'
);