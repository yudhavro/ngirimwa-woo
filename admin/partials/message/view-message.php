<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/view-helper.php';

settings_errors('apivro_woonotify_messages');

$active_subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'neworder';

$subtabs = array(
    'neworder' => __('New order', 'apivro-woonotify'),
    'paymentcomplete' => __('Payment Complete', 'apivro-woonotify'),
    'onhold' => __('On Hold', 'apivro-woonotify'),
    'processing' => __('Processing', 'apivro-woonotify'),
    'completed' => __('Completed', 'apivro-woonotify'),
    'cancelled' => __('Cancelled', 'apivro-woonotify'),
    'refunded' => __('Refunded', 'apivro-woonotify'),
    'failed' => __('Failed', 'apivro-woonotify'),
);
?>

<ul class="subsubsub">
    <?php
    $subtab_count = count($subtabs);
    $counter = 0;
    foreach ($subtabs as $key => $label) :
        $counter++;
        $is_active = ($key === $active_subtab);
        $separator = ($counter < $subtab_count) ? ' | ' : '';
    ?>
        <li>
            <a href="?page=apivro-woonotify-settings&tab=message&subtab=<?php echo esc_attr($key); ?>" 
               class="<?php echo $is_active ? 'current' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a><?php echo $separator; ?>
        </li>
    <?php endforeach; ?>
</ul>
<br class="clear" />

<?php
$view_file_map = array(
    'neworder' => 'view-neworder.php',
    'paymentcomplete' => 'view-paymentcomplete.php',
    'onhold' => 'view-onhold.php',
    'processing' => 'view-processing.php',
    'completed' => 'view-completed.php',
    'cancelled' => 'view-cancelled.php',
    'refunded' => 'view-refunded.php',
    'failed' => 'view-failed.php',
);

$view_file = isset($view_file_map[$active_subtab]) ? $view_file_map[$active_subtab] : 'view-' . $active_subtab . '.php';
$view_path = dirname(__FILE__) . '/' . $view_file;

if (file_exists($view_path)) {
    include $view_path;
} else {
    echo '<p>' . esc_html__('Template not found.', 'apivro-woonotify') . '</p>';
}
?>