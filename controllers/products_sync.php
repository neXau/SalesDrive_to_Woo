<?php
/**
 * Functions for integrating SalesDrive with WooCommerce for product synchronization.
 *
 * This set of functions includes methods to initialize the integration runner,
 * process integration on init and cron hooks, and register a cron task for periodic integration.
 *
 * @package SalesDriveToWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Initialize the runner for WooCommerce integration with SalesDrive.
 *
 * This function checks if the file URL is set in options, processes integration when triggered,
 * and schedules a cron job to run the integration periodically.
 */
function si__init_runner_woo() {

    if (get_option('file_url') !== false && get_option('file_url') !== '') {

        $file_url = esc_attr(get_option('file_url'));

        $si_run_integration_nonce = isset($_POST['si_run_integration_nonce']) ? sanitize_text_field($_POST['si_run_integration_nonce']) : '';

        if (isset($_POST['si_run_integration']) && wp_verify_nonce($si_run_integration_nonce, 'si_run_integration_nonce') ) { // || (defined('DOING_CRON') && DOING_CRON)
            
            $list = getProductsList($file_url);

            $products = $list['products'];
            $categories = $list['categories'];

            // var_dump($categories);

            if ($products != false) {

                foreach ($products as $key => $product) {

                    if (array_key_exists('id', $product['@attributes'])) {
    
                        // Checking for keys and clearing their values if necessary
                        isset($product['name'])              ? null : $product['name'] = '';
                        isset($product['description'])       ? null : $product['description'] = '';
                        isset($product['price'])             ? null : $product['price'] = '';
                        isset($product['vendorCode'])        ? null : $product['vendorCode'] = '';
                        isset($product['quantity_in_stock']) ? null : $product['quantity_in_stock'] = '';
                        isset($product['categoryId'])        ? null : $product['categoryId'] = '';
    
                        if (isset($product['picture']) && is_array($product['picture']) && count($product['picture']) > 1) {
                            $first_pic = array_shift($product['picture']);
                        } elseif (isset($product['picture']) && is_string($product['picture'])) {
    
                            $first_pic = $product['picture'];
                            unset($product['picture']);
                        }       
                        
                        if (!si__woocommerce_add_product(array(

                            'salesdrive_product_id'     => $product['@attributes']['id'],
                            'product_title'             => $product['name'],
                            'product_description'       => $product['description'],
                            'product_price'             => $product['price'],
                            'product_sku'               => $product['vendorCode'],
                            'product_quantity'          => $product['quantity_in_stock'],
                            'category_name'             => $categories[ (int) $product['categoryId']],

                            'product_main_image_url'    =>  isset($first_pic) ? $first_pic : null,
                            'product_additional_images' =>  isset($product['picture']) ? $product['picture'] : null,
                            
                        ))) {
                            
                            si__woocommerce_update_product(array(
            
                                'salesdrive_product_id'         => $product['@attributes']['id'],
                                'new_product_title'             => $product['name'],
                                'new_product_description'       => $product['description'],
                                'new_product_price'             => $product['price'],
                                'new_product_sku'               => $product['vendorCode'],
                                'new_product_quantity'          => $product['quantity_in_stock'],
                                'new_category_name'             => $categories[ (int) $product['categoryId']],

                                'new_product_main_image_url'    =>  isset($first_pic) ? $first_pic : null,
                                'new_product_additional_images' =>  isset($product['picture']) ? $product['picture'] : null,
                            ));
                        }
    
                        
                    }
                    // var_dump($product['description']);
                }
            }
        }
    }
}

add_action('init', 'si__init_runner_woo');

// Add a hook for cron execution
add_action('si_cron_hook', 'si__init_runner_woo');

// Register a cron task to call our function every 30 minutes
if (!wp_next_scheduled('si_cron_hook')) {
    
    // Set the cron task to run every 30 minutes
    wp_schedule_event(time(), 'halfhourly', 'si_cron_hook');
}