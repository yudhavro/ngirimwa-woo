<?php
/**
 * Admin Settings Page
 *
 * @package APIvro_WooNotify
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'apivro_woonotify_register_settings_page');

function apivro_woonotify_register_settings_page() {
    add_submenu_page(
        'woocommerce',
        __('NgirimWA Woo Settings', 'apivro-woonotify'),
        __('NgirimWA Woo', 'apivro-woonotify'),
        'manage_options',
        'apivro-woonotify-settings',
        'apivro_woonotify_settings_page_content'
    );
}

function apivro_woonotify_settings_page_content() {
    settings_errors('apivro_woonotify_settings');
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'setting';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('NgirimWA Woo Settings', 'apivro-woonotify'); ?></h1>
        
        <nav class="nav-tab-wrapper">
            <a href="?page=apivro-woonotify-settings&tab=setting" class="nav-tab <?php echo $active_tab === 'setting' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Settings', 'apivro-woonotify'); ?></a>
            <a href="?page=apivro-woonotify-settings&tab=testing" class="nav-tab <?php echo $active_tab === 'testing' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Testing', 'apivro-woonotify'); ?></a>
            <a href="?page=apivro-woonotify-settings&tab=message" class="nav-tab <?php echo $active_tab === 'message' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Message Templates', 'apivro-woonotify'); ?></a>
        </nav>
      
        <?php 
        if ($active_tab === 'setting') {
            include dirname(__FILE__) . '/partials/settings/view-setting.php';
        } elseif ($active_tab === 'testing') {
            include dirname(__FILE__) . '/partials/testing/view-testing.php';
        } elseif ($active_tab === 'message') {
            include dirname(__FILE__) . '/partials/message/view-message.php';
        }
        ?>
    </div>
    <?php
}

function apivro_woonotify_settings_init() {
    register_setting('apivro-woonotify-settings-group', 'apivro_woonotify_api_key', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));

    register_setting('apivro-woonotify-settings-group', 'apivro_woonotify_admin_phone', array(
        'type' => 'string',
        'sanitize_callback' => 'apivro_woonotify_sanitize_recipients',
        'default' => '',
    ));
}

function apivro_woonotify_sanitize_recipients($input) {
    $input = sanitize_textarea_field($input);
    $input = preg_replace('/\s*,\s*/', ',', $input);
    return $input;
}

function apivro_woonotify_register_settings() {
    if (isset($_POST['submit']) && isset($_POST['apivro_woonotify_api_key']) && check_admin_referer('apivro-woonotify-settings-group-options')) {
        $api_key = sanitize_text_field($_POST['apivro_woonotify_api_key']);
        $admin_phone = apivro_woonotify_sanitize_recipients($_POST['apivro_woonotify_admin_phone']);

        update_option('apivro_woonotify_api_key', $api_key);
        update_option('apivro_woonotify_admin_phone', $admin_phone);

        // Checks the key + device without sending a message (1.1.0 sent a real test message).
        $check = !empty($api_key) ? apivro_woonotify_check_connection($api_key) : null;
        if ($check && $check['ok']) {
            $status = isset($check['data']['status']) ? $check['data']['status'] : '';
            if ($status === 'connected') {
                apivro_woonotify_resume();
                add_settings_error('apivro_woonotify_settings', 'apivro_woonotify_settings_message', __('API Key validated successfully!', 'apivro-woonotify'), 'updated');
            } else {
                /* translators: %s: device status */
                add_settings_error('apivro_woonotify_settings', 'apivro_woonotify_settings_message', sprintf(__('API Key is valid, but the device status is "%s". Reconnect it in the NgirimWA dashboard.', 'apivro-woonotify'), $status), 'error');
            }
        } elseif ($check) {
            add_settings_error('apivro_woonotify_settings', 'apivro_woonotify_settings_message', apivro_woonotify_describe_error($check), 'error');
        } else {
            add_settings_error('apivro_woonotify_settings', 'apivro_woonotify_settings_message', __('Settings saved. Please enter your API Key.', 'apivro-woonotify'), 'updated');
        }
    }
}

add_action('admin_init', 'apivro_woonotify_settings_init');
add_action('admin_init', 'apivro_woonotify_register_settings');

