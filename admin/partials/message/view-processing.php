<?php
if (!defined('ABSPATH')) {
    exit;
}

settings_errors('apivro_woonotify_messages');

$saved_messages = get_option('apivro_woonotify_settings', array());
$current_customer_message = isset($saved_messages['processing_order_customer']) ? $saved_messages['processing_order_customer'] : '';
$current_admin_message = isset($saved_messages['processing_order_admin']) ? $saved_messages['processing_order_admin'] : '';

apivro_woonotify_render_message_form(
    'apivro_processing_customer_message',
    'apivro_processing_admin_message',
    $current_customer_message,
    $current_admin_message,
    'apivro_processing_action',
    'apivro_processing_nonce',
    'submit_processing_message'
);
