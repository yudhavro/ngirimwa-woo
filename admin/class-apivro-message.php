<?php
/**
 * Message Templates Manager
 *
 * @package NgirimWA_Woo
 */

if (!defined('ABSPATH')) {
    exit;
}

class APIvro_Message_Manager {

    private $message_configs = array(
        // New order notifications (triggered via woocommerce_new_order)
        'neworder' => array(
            'submit_name'    => 'submit_neworder_message',
            'nonce_action'   => 'apivro_neworder_action',
            'nonce_name'     => 'apivro_neworder_nonce',
            'admin_field'    => 'apivro_neworder_admin_message',
            'customer_field' => 'apivro_neworder_customer_message',
            'admin_key'      => 'new_order_admin',
            'customer_key'   => 'new_order_customer',
        ),
        'onhold' => array(
            'submit_name' => 'submit_onhold_message',
            'nonce_action' => 'apivro_onhold_action',
            'nonce_name' => 'apivro_onhold_nonce',
            'admin_field' => 'apivro_onhold_admin_message',
            'customer_field' => 'apivro_onhold_customer_message',
            'admin_key' => 'onhold_order_admin',
            'customer_key' => 'onhold_order_customer',
        ),
        // Payment complete notifications (triggered via woocommerce_payment_complete)
        'paymentcomplete' => array(
            'submit_name'    => 'submit_paymentcomplete_message',
            'nonce_action'   => 'apivro_paymentcomplete_action',
            'nonce_name'     => 'apivro_paymentcomplete_nonce',
            'admin_field'    => 'apivro_paymentcomplete_admin_message',
            'customer_field' => 'apivro_paymentcomplete_customer_message',
            'admin_key'      => 'payment_complete_admin',
            'customer_key'   => 'payment_complete_customer',
        ),
        'processing' => array(
            'submit_name' => 'submit_processing_message',
            'nonce_action' => 'apivro_processing_action',
            'nonce_name' => 'apivro_processing_nonce',
            'admin_field' => 'apivro_processing_admin_message',
            'customer_field' => 'apivro_processing_customer_message',
            'admin_key' => 'processing_order_admin',
            'customer_key' => 'processing_order_customer',
        ),
        'completed' => array(
            'submit_name' => 'submit_completed_message',
            'nonce_action' => 'apivro_completed_action',
            'nonce_name' => 'apivro_completed_nonce',
            'admin_field' => 'apivro_completed_admin_message',
            'customer_field' => 'apivro_completed_customer_message',
            'admin_key' => 'completed_order_admin',
            'customer_key' => 'completed_order_customer',
        ),
        'cancelled' => array(
            'submit_name' => 'submit_cancelled_message',
            'nonce_action' => 'apivro_cancelled_action',
            'nonce_name' => 'apivro_cancelled_nonce',
            'admin_field' => 'apivro_cancelled_admin_message',
            'customer_field' => 'apivro_cancelled_customer_message',
            'admin_key' => 'cancelled_order_admin',
            'customer_key' => 'cancelled_order_customer',
        ),
        'refunded' => array(
            'submit_name' => 'submit_refunded_message',
            'nonce_action' => 'apivro_refunded_action',
            'nonce_name' => 'apivro_refunded_nonce',
            'admin_field' => 'apivro_refunded_admin_message',
            'customer_field' => 'apivro_refunded_customer_message',
            'admin_key' => 'refunded_order_admin',
            'customer_key' => 'refunded_order_customer',
        ),
        'failed' => array(
            'submit_name' => 'submit_failed_message',
            'nonce_action' => 'apivro_failed_action',
            'nonce_name' => 'apivro_failed_nonce',
            'admin_field' => 'apivro_failed_admin_message',
            'customer_field' => 'apivro_failed_customer_message',
            'admin_key' => 'failed_order_admin',
            'customer_key' => 'failed_order_customer',
        ),
    );

    public function __construct() {
        add_action('admin_init', array($this, 'process_all_messages'));
    }

    public function process_all_messages() {
        foreach ($this->message_configs as $config) {
            if (isset($_POST[$config['submit_name']]) && check_admin_referer($config['nonce_action'], $config['nonce_name'])) {
                $messages = get_option('apivro_woonotify_settings', array());
                
                if (!is_array($messages)) {
                    $messages = array();
                }

                $messages[$config['admin_key']] = isset($_POST[$config['admin_field']]) ? sanitize_textarea_field($_POST[$config['admin_field']]) : '';
                $messages[$config['customer_key']] = isset($_POST[$config['customer_field']]) ? sanitize_textarea_field($_POST[$config['customer_field']]) : '';
                
                update_option('apivro_woonotify_settings', $messages);
                add_settings_error('apivro_woonotify_messages', 'settings_updated', __('Messages saved successfully.', 'apivro-woonotify'), 'updated');
                break;
            }
        }
    }
}

new APIvro_Message_Manager();