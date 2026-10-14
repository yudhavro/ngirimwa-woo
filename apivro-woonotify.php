<?php
/**
 * Plugin Name: NgirimWA Woo
 * Plugin URI: https://github.com/yudhavro/ngirimwa-woo
 * Description: Send notifications for orders and order changes from WooCommerce to both admin and customers via WhatsApp using NgirimWA.
 * Version: 1.2.0
 * Author: Yudhavro
 * Author URI: https://yudhavro.com
 * Text Domain: apivro-woonotify
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC tested up to: 10.3
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('APIVRO_WOONOTIFY_VERSION', '1.2.0');
define('APIVRO_WOONOTIFY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('APIVRO_WOONOTIFY_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('before_woocommerce_init', 'apivro_woonotify_declare_compatibility');

function apivro_woonotify_declare_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
    }
}

add_action('plugins_loaded', 'apivro_woonotify_check_woocommerce');

function apivro_woonotify_check_woocommerce() {
    // Load textdomain for translations
    load_plugin_textdomain('apivro-woonotify', false, dirname(plugin_basename(__FILE__)) . '/languages');

    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'apivro_woonotify_woocommerce_notice');
        return;
    }

    require_once APIVRO_WOONOTIFY_PLUGIN_DIR . 'includes/class-apivro-utils.php';
    require_once APIVRO_WOONOTIFY_PLUGIN_DIR . 'includes/class-apivro-api.php';
    require_once APIVRO_WOONOTIFY_PLUGIN_DIR . 'includes/class-apivro-wc.php';

    // Action Scheduler is ready only from `action_scheduler_init` (fired on init).
    add_action('action_scheduler_init', 'apivro_woonotify_maybe_upgrade');

    if (is_admin()) {
        require_once APIVRO_WOONOTIFY_PLUGIN_DIR . 'admin/class-apivro-settings.php';
        require_once APIVRO_WOONOTIFY_PLUGIN_DIR . 'admin/class-apivro-testing.php';
        require_once APIVRO_WOONOTIFY_PLUGIN_DIR . 'admin/class-apivro-message.php';
    }
}

function apivro_woonotify_woocommerce_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('NgirimWA Woo requires WooCommerce to be installed and active.', 'apivro-woonotify'); ?></p>
    </div>
    <?php
}

/**
 * 1.2.0: cancel retry chains left by 1.1.0. Its status-change retries lost their attempt counter and
 * rescheduled themselves every 2 minutes forever (thousands of rejected API calls per hour once the
 * device logged out). Fresh notifications are unaffected.
 */
function apivro_woonotify_maybe_upgrade() {
    if (version_compare(get_option('apivro_woonotify_version', '0'), '1.2.0', '>=')) {
        return;
    }
    if (!function_exists('as_unschedule_all_actions')) {
        return; // try again on the next load
    }
    as_unschedule_all_actions('', array(), 'apivro_whatsapp_notifications_retry');
    update_option('apivro_woonotify_version', APIVRO_WOONOTIFY_VERSION, false);
}

/** Notice on every admin page while sending is paused. */
add_action('admin_notices', 'apivro_woonotify_paused_notice');

function apivro_woonotify_paused_notice() {
    if (!current_user_can('manage_options') || !function_exists('apivro_woonotify_paused')) {
        return;
    }
    $paused = apivro_woonotify_paused();
    if (!$paused) {
        return;
    }
    printf(
        '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
        esc_html__('NgirimWA Woo is paused:', 'apivro-woonotify'),
        esc_html($paused['message']),
        esc_url(admin_url('admin.php?page=apivro-woonotify-settings')),
        esc_html__('Open settings', 'apivro-woonotify')
    );
}

register_activation_hook(__FILE__, 'apivro_woonotify_activation');

function apivro_woonotify_activation() {
    if (!get_option('apivro_woonotify_settings')) {
        update_option('apivro_woonotify_settings', array());
    }
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'apivro_woonotify_add_plugin_action_links');

function apivro_woonotify_add_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=apivro-woonotify-settings') . '">' . esc_html__('Settings', 'apivro-woonotify') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_action('admin_enqueue_scripts', 'apivro_woonotify_enqueue_admin_styles');

function apivro_woonotify_enqueue_admin_styles($hook) {
    if (strpos($hook, 'apivro-woonotify') === false) {
        return;
    }
    wp_enqueue_style('apivro-woonotify-admin-styles', APIVRO_WOONOTIFY_PLUGIN_URL . 'assets/css/admin-styles.css', array(), APIVRO_WOONOTIFY_VERSION);
    wp_enqueue_script('apivro-woonotify-admin-script', APIVRO_WOONOTIFY_PLUGIN_URL . 'assets/js/admin-subtabs.js', array('jquery'), APIVRO_WOONOTIFY_VERSION, true);

    // Localize strings for JS i18n
    wp_localize_script('apivro-woonotify-admin-script', 'apivroWoonotifyI18n', array(
        'copied' => __('Copied!', 'apivro-woonotify'),
        'error'   => __('Error!', 'apivro-woonotify'),
    ));
}

// Diagnostic notice: warn if Action Scheduler is not available while WooCommerce is active.
add_action('admin_notices', 'apivro_woonotify_action_scheduler_diagnostic_notice');

function apivro_woonotify_action_scheduler_diagnostic_notice() {
    if (!is_admin()) {
        return;
    }

    // Only show on our settings page
    $on_settings_page = isset($_GET['page']) && $_GET['page'] === 'apivro-woonotify-settings';
    if (!$on_settings_page) {
        return;
    }

    // WooCommerce must be active for this diagnostic
    if (!class_exists('WooCommerce')) {
        return;
    }

    // If Action Scheduler functions not available, show warning
    $as_available = function_exists('as_enqueue_async_action') || function_exists('as_schedule_single_action') || class_exists('ActionScheduler');
    if (!$as_available) {
        echo '<div class="notice notice-warning"><p>' . esc_html__(
            'Action Scheduler is not available. WooCommerce normally loads Action Scheduler automatically. Please ensure WooCommerce is active and no plugin conflicts prevent it. Scheduled notifications may be affected.',
            'apivro-woonotify'
        ) . '</p></div>';
    }
}

