<?php
if (!defined('ABSPATH')) {
    exit;
}

settings_errors('apivro_woonotify_messages');

$saved_messages = get_option('apivro_woonotify_settings', array());
$current_customer_message = isset($saved_messages['onhold_order_customer']) ? $saved_messages['onhold_order_customer'] : '';
$current_admin_message = isset($saved_messages['onhold_order_admin']) ? $saved_messages['onhold_order_admin'] : '';

apivro_woonotify_render_message_form(
    'apivro_onhold_customer_message',
    'apivro_onhold_admin_message',
    $current_customer_message,
    $current_admin_message,
    'apivro_onhold_action',
    'apivro_onhold_nonce',
    'submit_onhold_message'
);
