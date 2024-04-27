<?php
/**
 * Plugin Name: SalesDrive To Woo
 * Description: ---
 * Version: 1.3.5
 * Requires at least: 6.0
 * Requires PHP: 7.2
 * Author: neXau
 * Author URI: https://www.linkedin.com/in/nexau/ 
 * 
 * @package SalesDriveToWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function load_salesdrive_textdomain() {
    load_plugin_textdomain('salesdrive-to-woo', false, plugin_dir_path(__FILE__) . 'languages/');
}
add_action('plugins_loaded', 'load_salesdrive_textdomain');

// Hook for adding an option for SalesDrive when activating the plugin
function si_check_options() {
    add_option('si_perform_add', '0');
    add_option('si_perform_update', '0');
}
add_action('after_setup_theme', 'si_check_options');

// Check WooCommerce Active
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    // Check WooCommerce Version
    $woocommerce_version = get_option('woocommerce_version');
    if (version_compare($woocommerce_version, '6.5.0', '>=')) {

        // Admin
        require_once plugin_dir_path(__FILE__) . 'admin/integration-settings.php';

        // Readers
        require plugin_dir_path(__FILE__) . 'readers/salesDriveXmlReader.php';

        // Writers
        require_once plugin_dir_path(__FILE__) . 'writers/genProducts.php';

        // Controllers
        require_once plugin_dir_path(__FILE__) . 'controllers/products_sync.php';

    } else {
        add_action('admin_notices', 'si_notice');
        function si_notice() {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><b><?php _e('Warning: Update WooCommerce! (SALESDRIVE INTEGRATION).', 'salesdrive-to-woo'); ?></b></p>
            </div>
            <?php
        }
    }

} else {
    add_action('admin_notices', 'si_notice');
    function si_notice() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><b><?php _e('Warning: WooCommerce must be installed and activated for SALESDRIVE INTEGRATION to work correctly.', 'salesdrive-to-woo'); ?></b></p>
        </div>
        <?php
    }
}