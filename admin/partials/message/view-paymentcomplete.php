<?php
if (!defined('ABSPATH')) {
    exit;
}

settings_errors('apivro_woonotify_messages');

$saved_messages = get_option('apivro_woonotify_settings', array());
$current_customer_message = isset($saved_messages['payment_complete_customer']) ? $saved_messages['payment_complete_customer'] : '';
$current_admin_message = isset($saved_messages['payment_complete_admin']) ? $saved_messages['payment_complete_admin'] : '';

apivro_woonotify_render_message_form(
    'apivro_paymentcomplete_customer_message',
    'apivro_paymentcomplete_admin_message',
    $current_customer_message,
    $current_admin_message,
    'apivro_paymentcomplete_action',
    'apivro_paymentcomplete_nonce',
    'submit_paymentcomplete_message'
);