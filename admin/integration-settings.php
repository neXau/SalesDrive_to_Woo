<?php
/**
 * Functions for managing settings and integration with SalesDrive in WooCommerce.
 *
 * This set of functions includes methods to define constants, display entry fields,
 * initialize settings, add settings pages to WooCommerce submenu, and handle click functions.
 *
 * @package SalesDriveToWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constants for keys and settings
define('FILE_URL', 'file_url');
define('SETTINGS_SECTION', 'settings_section');

// Displaying entry fields
function file_url_callback() {
    $file_url = esc_attr(get_option('file_url'));
    echo "<input type='text' name='file_url' value='$file_url' />";
}

function settings_page() {
    ?>
    <div class="wrap">
        <h1>
            <?php _e('SalesDrive - WooCommerce Integration Settings', 'salesdrive-to-woo'); ?>
        </h1>
        <div class="card">
            <form method="post" action="options.php">
                <?php
                settings_fields(FILE_URL);
                do_settings_sections(FILE_URL);
                wp_nonce_field('si_run_integration_nonce', 'si_run_integration_nonce');
                ?>
                <button type="submit" name="si_run_integration" class="button button-primary"><?php _e('Run Integration', 'salesdrive-to-woo'); ?></button>
            </form>
        </div>
    </div>
    <?php
}

// Initialization setup 
function settings_init() {
    add_settings_section(SETTINGS_SECTION, 'API Settings', 'settings_section_callback', FILE_URL);
    add_settings_field('file_url_field', 'SalesDrive File URL', 'file_url_callback', FILE_URL, SETTINGS_SECTION);
    register_setting(FILE_URL, 'file_url');
}

// Adding a page to the WooCommerce submenu
function add_settings_page_to_woocommerce() {
    add_submenu_page(
        'woocommerce',
        __('SalesDrive Integration', 'salesdrive-to-woo'),
        __('SalesDrive', 'salesdrive-to-woo'),
        'manage_options',
        'si_settings_page',
        'settings_page'
    );
}

// Adding hooks for click functions
add_action('admin_menu', 'add_settings_page_to_woocommerce', 70);
add_action('admin_init', 'settings_init');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_settings_link');

function add_settings_link($links) {
    array_unshift($links, '<a href="admin.php?page=si_settings_page">' . __('Settings', 'salesdrive-to-woo') . '</a>');
    return $links;
}

function settings_section_callback() {
    return null;
}