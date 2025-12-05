<?php
/**
 * WooCommerce Notifications Handler
 *
 * @package NgirimWA_Woo
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure Action Scheduler is available
// Do not force-include Action Scheduler from a brittle WooCommerce path.
// WooCommerce will load Action Scheduler itself. Below, all Action Scheduler
// calls are prefixed with function_exists checks for safety.

class APIvro_WC_Notifications {

    private $messages;
    private $admin_phone;
    private static $processed_orders = array();
    private $max_retry_attempts = 3; // Maximum number of retry attempts

    public function __construct() {
        // Hooks for scheduling notifications
        add_action('woocommerce_order_status_changed', array($this, 'schedule_status_change_notification'), 10, 4);
        add_action('woocommerce_new_order', array($this, 'schedule_new_order_notification'), 10, 1);
        add_action('woocommerce_payment_complete', array($this, 'schedule_payment_complete_notification'), 10, 1);
        
        // Register actions for Action Scheduler
        add_action('apivro_process_new_order_notification', array($this, 'process_new_order_notification'), 10, 2);
        add_action('apivro_process_payment_complete_notification', array($this, 'process_payment_complete_notification'), 10, 2);
        add_action('apivro_process_status_change_notification', array($this, 'process_status_change_notification'), 10, 3);
    }

    private function load_settings() {
        if ($this->messages === null) {
            $this->messages = get_option('apivro_woonotify_settings', array());
            $this->admin_phone = get_option('apivro_woonotify_admin_phone', '');
        }
    }

    private function is_order_processed($order_id, $action) {
        $key = $order_id . '_' . $action;
        if (isset(self::$processed_orders[$key])) {
            return true;
        }
        self::$processed_orders[$key] = true;
        return false;
    }

    /**
     * Schedule new order notification using Action Scheduler
     */
    public function schedule_new_order_notification($order_id) {
        if (!function_exists('as_enqueue_async_action')) {
            return;
        }
        
        // Check if there's already a scheduled action for this order
        if (!as_has_scheduled_action('apivro_process_new_order_notification', array('order_id' => $order_id), 'apivro_whatsapp_notifications')) {
            // Enqueue without 'attempt' for consistency with duplicate checking;
            // the attempt value will be managed in the worker and retry mechanism.
            as_enqueue_async_action(
                'apivro_process_new_order_notification',
                array('order_id' => $order_id),
                'apivro_whatsapp_notifications'
            );
        }
    }

    /**
     * Schedule payment complete notification using Action Scheduler
     */
    public function schedule_payment_complete_notification($order_id) {
        if (!function_exists('as_enqueue_async_action')) {
            return;
        }
        
        if (!as_has_scheduled_action('apivro_process_payment_complete_notification', array('order_id' => $order_id), 'apivro_whatsapp_notifications')) {
            // Enqueue without 'attempt' for consistency with duplicate checking;
            // the attempt value will be managed in the worker and retry mechanism.
            as_enqueue_async_action(
                'apivro_process_payment_complete_notification',
                array('order_id' => $order_id),
                'apivro_whatsapp_notifications'
            );
        }
    }

    /**
     * Schedule status change notification
     */
    public function schedule_status_change_notification($order_id, $old_status, $new_status, $order) {
        if (!function_exists('as_enqueue_async_action')) {
            return;
        }
        
        // Ensure the order object is available
        if (!is_object($order)) {
            $order = wc_get_order($order_id);
        }
        
        $status_key = $order_id . '_status_' . $new_status;
        
        // Send notification for on-hold status
        if ($new_status === 'on-hold') {
            if (!as_has_scheduled_action('apivro_process_status_change_notification', 
                array(
                    'order_id' => $order_id,
                    'old_status' => $old_status,
                    'new_status' => 'on-hold'
                ), 
                'apivro_whatsapp_notifications')) {
                
                // Enqueue without 'attempt' for consistency; attempt will be managed during retry.
                as_enqueue_async_action(
                    'apivro_process_status_change_notification',
                    array(
                        'order_id' => $order_id,
                        'old_status' => $old_status,
                        'new_status' => 'on-hold'
                    ),
                    'apivro_whatsapp_notifications'
                );
                return;
            }
        }
        
        // For other statuses
        if (!as_has_scheduled_action('apivro_process_status_change_notification', 
            array(
                'order_id' => $order_id,
                'old_status' => $old_status,
                'new_status' => $new_status
            ), 
            'apivro_whatsapp_notifications')) {
                
            // Enqueue without 'attempt' for consistency; attempt will be managed during retry.
            as_enqueue_async_action(
                'apivro_process_status_change_notification',
                array(
                    'order_id' => $order_id,
                    'old_status' => $old_status,
                    'new_status' => $new_status
                ),
                'apivro_whatsapp_notifications'
            );
        }
    }

    /**
     * Process new order notification
     */
    public function process_new_order_notification($order_id, $attempt = 1) {
        try {
            if ($this->is_order_processed($order_id, 'new_order')) {
                return;
            }
            
            $result = $this->send_notification($order_id, 'new_order_admin', 'new_order_customer');
            
            if (is_wp_error($result)) {
                if ($result->get_error_code() === 'no_messages_configured') {
                    // Log as skipped rather than error
                    $this->log_notification($order_id, 'new_order', 'skipped', $result->get_error_message());
                    return;
                }
                throw new Exception($result->get_error_message());
            }
            
            // Log success
            $this->log_notification($order_id, 'new_order', 'success');
            
        } catch (Exception $e) {
            $this->handle_notification_error($e, 'new_order', $order_id, $attempt);
        }
    }

    /**
     * Process payment complete notification
     */
    public function process_payment_complete_notification($order_id, $attempt = 1) {
        try {
            if ($this->is_order_processed($order_id, 'payment_complete')) {
                return;
            }
            
            $result = $this->send_notification($order_id, 'payment_complete_admin', 'payment_complete_customer');
            
            if (is_wp_error($result)) {
                if ($result->get_error_code() === 'no_messages_configured') {
                    $this->log_notification($order_id, 'payment_complete', 'skipped', $result->get_error_message());
                    return;
                }
                throw new Exception($result->get_error_message());
            }
            
            // Log success
            $this->log_notification($order_id, 'payment_complete', 'success');
            
        } catch (Exception $e) {
            $this->handle_notification_error($e, 'payment_complete', $order_id, $attempt);
        }
    }
    
    /**
     * Process status change notification
     */
    public function process_status_change_notification($order_id, $old_status, $new_status, $attempt = 1) {
        try {
            $status_key = 'status_' . $new_status;
            if ($this->is_order_processed($order_id, $status_key)) {
                return;
            }
            
            $admin_key = $new_status . '_order_admin';
            $customer_key = $new_status . '_order_customer';
            
            $result = $this->send_notification($order_id, $admin_key, $customer_key);
            
            if (is_wp_error($result)) {
                if ($result->get_error_code() === 'no_messages_configured') {
                    $this->log_notification($order_id, 'status_' . $new_status, 'skipped', $result->get_error_message());
                    return;
                }
                throw new Exception($result->get_error_message());
            }
            
            // Log success
            $this->log_notification($order_id, 'status_' . $new_status, 'success');
            
        } catch (Exception $e) {
            $this->handle_notification_error($e, 'status_' . $new_status, $order_id, $attempt);
        }
    }

    /**
     * Handle notification errors and schedule retries
     */
    private function handle_notification_error($exception, $notification_type, $order_id, $attempt) {
        // Log the error
        $error_message = sprintf(
            'NgirimWA Woo: Failed to send %s notification for order #%d (attempt %d/%d). Error: %s',
            $notification_type,
            $order_id,
            $attempt,
            $this->max_retry_attempts,
            $exception->getMessage()
        );
        
        error_log($error_message);
        $this->log_notification($order_id, $notification_type, 'error', $error_message);
        
        // Schedule a retry if we haven't exceeded max attempts
        if ($attempt < $this->max_retry_attempts) {
            $next_attempt = $attempt + 1;
            $retry_delay = min(pow(2, $attempt) * 60, 3600); // Exponential backoff, max 1 hour
            
            // Determine the action hook based on notification type
            $action = '';
            $args = array('order_id' => $order_id, 'attempt' => $next_attempt);
            
            if (strpos($notification_type, 'status_') === 0) {
                $action = 'apivro_process_status_change_notification';
                $status = str_replace('status_', '', $notification_type);
                $args['new_status'] = $status;
                $args['old_status'] = ''; // We don't have this info here, but it's only used for logging
            } elseif ($notification_type === 'new_order') {
                $action = 'apivro_process_new_order_notification';
            } elseif ($notification_type === 'payment_complete') {
                $action = 'apivro_process_payment_complete_notification';
            }
            
            if ($action && function_exists('as_schedule_single_action')) {
                as_schedule_single_action(
                    time() + $retry_delay,
                    $action,
                    $args,
                    'apivro_whatsapp_notifications_retry'
                );
                
                error_log(sprintf(
                    'NgirimWA Woo: Scheduled retry #%d for %s notification (order #%d) in %d seconds',
                    $next_attempt,
                    $notification_type,
                    $order_id,
                    $retry_delay
                ));
            }
        } else {
            error_log(sprintf(
                'NgirimWA Woo: Max retry attempts reached for %s notification (order #%d)',
                $notification_type,
                $order_id
            ));
        }
    }
    
    /**
     * Log notification status
     */
    private function log_notification($order_id, $notification_type, $status, $message = '') {
        $log_entry = array(
            'time' => current_time('mysql'),
            'status' => $status,
            'type' => $notification_type,
            'message' => $message
        );
        
        $logs = get_post_meta($order_id, '_apivro_notification_logs', true);
        if (!is_array($logs)) {
            $logs = array();
        }
        
        $logs[] = $log_entry;
        
        // Keep only the last 50 log entries to prevent bloating
        if (count($logs) > 50) {
            $logs = array_slice($logs, -50);
        }
        
        update_post_meta($order_id, '_apivro_notification_logs', $logs);
    }

    /**
     * Send notification to admin and/or customer
     * 
     * @param int $order_id Order ID
     * @param string $admin_message_key Admin message key in settings
     * @param string $customer_message_key Customer message key in settings
     * @param WC_Order|null $order Order object (optional)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function send_notification($order_id, $admin_message_key, $customer_message_key, $order = null) {
        if (!$order) {
            $order = wc_get_order($order_id);
        }
        
        if (!$order) {
            return new WP_Error('invalid_order', 'Invalid order ID: ' . $order_id);
        }

        $this->load_settings();
        // If neither admin nor customer templates are configured, indicate a skip to avoid misleading success logs
        $admin_template_empty = empty($this->messages[$admin_message_key]);
        $customer_template_empty = empty($this->messages[$customer_message_key]);
        if ($admin_template_empty && $customer_template_empty) {
            return new WP_Error('no_messages_configured', __('No message templates configured for this notification type.', 'apivro-woonotify'));
        }
        $errors = new WP_Error();
        $success = false;

        // Send to admin
        if (!empty($this->admin_phone) && !empty($this->messages[$admin_message_key])) {
            $admin_message = $this->process_message_with_variables($this->messages[$admin_message_key], $order);
            $admin_recipients = apivro_woonotify_parse_recipients($this->admin_phone);
            
            foreach ($admin_recipients as $recipient) {
                $result = apivro_send_message($recipient, $admin_message);
                if ($result === false) {
                    $errors->add('admin_notification_failed', sprintf('Failed to send admin notification to %s', $recipient));
                } else {
                    $success = true;
                }
            }
        }

        // Send to customer
        if (!empty($this->messages[$customer_message_key])) {
            $customer_message = $this->process_message_with_variables($this->messages[$customer_message_key], $order);
            $formatted_phone = apivro_woonotify_format_phone_number($order->get_billing_phone());
            
            if (!empty($formatted_phone)) {
                $result = apivro_send_message($formatted_phone, $customer_message);
                if ($result === false) {
                    $errors->add('customer_notification_failed', 'Failed to send customer notification');
                } else {
                    $success = true;
                }
            }
        }

        // If we have any errors but at least one message was sent successfully, log the errors but don't fail
        if ($success && $errors->has_errors()) {
            error_log('NgirimWA Woo: Partial notification failure - ' . json_encode($errors->get_error_messages()));
            return true;
        }
        
        // If we have errors and no successes, return the errors
        if ($errors->has_errors()) {
            return $errors;
        }
        
        // If we get here, everything was successful
        return true;
    }

    private function process_message_with_variables($message, $order) {
        if (!$order instanceof WC_Order) {
            return $message;
        }

        $phone = apivro_woonotify_format_phone_number($order->get_billing_phone());
        $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        if (empty($customer_name)) {
            $customer_name = $order->get_billing_company();
        }
        if (empty($customer_name)) {
            $customer_name = __('Guest', 'apivro-woonotify');
        }

        $order_items = array();
        foreach ($order->get_items() as $item) {
            $order_items[] = $item->get_name() . ' (x' . $item->get_quantity() . ')';
        }

        $replacements = array(
            '{customer_name}' => $customer_name,
            '{customer_phone}' => $phone,
            '{customer_email}' => $order->get_billing_email(),
            '{customer_address}' => wp_strip_all_tags($order->get_formatted_billing_address()),
            '{order_id}' => $order->get_id(),
            '{order_number}' => $order->get_order_number(),
            '{order_date}' => wc_format_datetime($order->get_date_created()),
            '{order_status}' => wc_get_order_status_name($order->get_status()),
            '{order_items}' => implode(', ', $order_items),
            '{shipping_total}' => wp_strip_all_tags(wc_price($order->get_shipping_total())),
            '{order_total}' => wp_strip_all_tags(wc_price($order->get_total())),
            '{invoice_link}' => $order->get_checkout_order_received_url(),
            '{order_pay_method}' => $order->get_payment_method_title(),
        );

        foreach ($replacements as $placeholder => $value) {
            $message = str_replace($placeholder, $value, $message);
        }

        return $message;
    }
}

new APIvro_WC_Notifications();