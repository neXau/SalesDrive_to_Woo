<?php
/**
 * Functions for managing WooCommerce products and related operations.
 *
 * This set of functions includes methods to check product existence, add images to products,
 * update product status, add new products, and update existing products in WooCommerce.
 *
 * @package SalesDriveToWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check if a WooCommerce product exists by its salesdrive_product_id.
 *
 * @param int $product_id The salesdrive_product_id to check.
 * @return mixed The existing product post object if found, false otherwise.
 */
function si__woocommerce_product_exists($product_id) {
    // Check if the product with the given product_id exists
    $existing_product = get_posts(array(
        'post_type' => 'product',
        'meta_key' => 'salesdrive_product_id',
        'meta_value' => $product_id,
        'post_status' => 'any',
        'numberposts' => 1,
    ));

    return !empty($existing_product) ? $existing_product[0] : false;
}

/**
 * Add an image to a WooCommerce product as its thumbnail.
 *
 * @param int $product_id The ID of the product to add the image to.
 * @param string $image_url The URL of the image to add.
 * @return int|false The attachment ID if successful, false otherwise.
 */
function si__add_image_to_product($product_id, $image_url) {
    // Get the attachment ID from the image URL on the site
    $attachment_id = si__get_attachment_id_from_url($image_url);

    if ($attachment_id) {
        // Set the image as the product thumbnail
        set_post_thumbnail($product_id, $attachment_id);
    }

    return $attachment_id;
}

/**
 * Add an image to a WooCommerce product's gallery.
 *
 * @param int $product_id The ID of the product to add the image to.
 * @param string $image_url The URL of the image to add.
 */
function si__add_image_to_product_gallery($product_id, $image_url) {
    // Get the attachment ID from the image URL on the site
    $attachment_id = si__get_attachment_id_from_url($image_url);

    if ($attachment_id) {
        // Get the current product image gallery
        $product_gallery = get_post_meta($product_id, '_product_image_gallery', true);

        // Add a new image to the gallery
        if ($product_gallery) {
            $product_gallery .= ",";
        }
        $product_gallery .= $attachment_id;

        // Update product image gallery metadata
        update_post_meta($product_id, '_product_image_gallery', $product_gallery);
    }
}

/**
 * Get the attachment ID from an image URL.
 *
 * @param string $image_url The URL of the image.
 * @return int|false The attachment ID if found, false otherwise.
 */
function si__get_attachment_id_from_url($image_url) {
    $attachment_id = false;

    // Получаем вложение по URL
    $attachment = attachment_url_to_postid($image_url);

    if ($attachment) {
        $attachment_id = $attachment;
    }

    return $attachment_id;
}

/**
 * Update the status of a WooCommerce product based on quantity.
 *
 * @param int $product_id The ID of the product to update.
 * @param int $quantity The new quantity of the product.
 */
function update_product_status($product_id, $quantity) {
    if ($quantity > 0) {
        $stock = $quantity;
        $stock_status = 'instock';
        $visibility = 'visible';
        $post_status = 'publish';
    } else {
        $stock = 0;
        $stock_status = 'outofstock';
        $visibility = 'hidden';
        $post_status = 'draft';
    }

    wp_update_post(array(
        'ID' => $product_id,
        'post_status' => $post_status,
    ));

    update_post_meta($product_id, '_stock', $stock);
    update_post_meta($product_id, '_stock_status', $stock_status);
    update_post_meta($product_id, '_visibility', $visibility);
    wc_delete_product_transients($product_id);
}

/**
 * Add a new product to WooCommerce if it doesn't already exist.
 *
 * @param array $product_data An array of product data including title, description, etc.
 * @return bool True if the product is added successfully, false otherwise.
 */
function si__woocommerce_add_product($product_data) {
    // Extracting data from the array
    extract($product_data);

    // Check if the product already exists
    $existing_product = si__woocommerce_product_exists($salesdrive_product_id);

    if (!$existing_product) {

        $product_ob_arr = array(
            'post_title'   => $product_title,
            'post_content' => $product_description,
            'post_status'  => 'draft',
            'post_type'    => 'product',
        );

        // Product does not exist, create a new one
        $product_id = wp_insert_post($product_ob_arr);
        
        update_product_status($product_id, $product_quantity);

        // Update product metadata
        update_post_meta($product_id, '_price', $product_price);
        update_post_meta($product_id, '_regular_price', $product_price);
        update_post_meta($product_id, '_sale_price', $product_price);
        update_post_meta($product_id, '_sku', $product_sku);

        // Servio ID for the product
        update_post_meta($product_id, 'salesdrive_product_id', $salesdrive_product_id);
        

        // Add main product image
        if ($product_main_image_url) {
            $image_id = si__add_image_to_product($product_id, $product_main_image_url);
            set_post_thumbnail($product_id, $image_id);
        }

        // Add additional images to the product gallery
        if (!empty($product_additional_images)) {
            foreach ($product_additional_images as $additional_image_url) {
                si__add_image_to_product_gallery($product_id, $additional_image_url);
            }
        }
        
        // Check if category exists, if not create it
        $term = term_exists($category_name, 'product_cat');
        if ($term !== 0 && $term !== null) {
            // Category exists, assign product to it
            wp_set_object_terms($product_id, $category_name, 'product_cat', true);
        } else {
            // Category does not exist, create it and then assign product to it
            $term = wp_insert_term($category_name, 'product_cat');
            if (!is_wp_error($term)) {
                wp_set_object_terms($product_id, $term['term_id'], 'product_cat', true);
            }
        }

        return true;
    } else {
        return false;
    }
}

/**
 * Update an existing product in WooCommerce.
 *
 * @param array $product_data An array of updated product data.
 */
function si__woocommerce_update_product($product_data) {
    // Extracting data from the array
    extract($product_data);

    // Check if the product with the given salesdrive_product_id exists
    $existing_product = si__woocommerce_product_exists($salesdrive_product_id);

    if ($existing_product) {
        // The product exists, update its details
        $product_id = $existing_product->ID;

        wp_update_post(array(
            'ID' => $product_id,
            'post_title' => $new_product_title,
            'post_content' => $new_product_description,
        ));
        
        update_product_status($product_id, $new_product_quantity);

        update_post_meta($product_id, '_price', $new_product_price);
        update_post_meta($product_id, '_regular_price', $new_product_price);
        update_post_meta($product_id, '_sale_price', $new_product_price);
        update_post_meta($product_id, '_sku', $new_product_sku);

        // Update main product image if new image URL provided
        if ($new_product_main_image_url) {
            // Delete existing main image
            delete_post_thumbnail($product_id);
            // Add new main image
            $image_id = si__add_image_to_product($product_id, $new_product_main_image_url);
            set_post_thumbnail($product_id, $image_id);
        }

        // Add additional images to the product gallery
        if (!empty($new_product_additional_images)) {
            foreach ($new_product_additional_images as $additional_image_url) {
                si__add_image_to_product_gallery($product_id, $additional_image_url);
            }
        }
        
        // Check if category exists, if not create it
        $term = term_exists($new_category_name, 'product_cat');
        if ($term !== 0 && $term !== null) {
            // Category exists, assign product to it
            wp_set_object_terms($product_id, $new_category_name, 'product_cat', true);
        } else {
            // Category does not exist, create it and then assign product to it
            $term = wp_insert_term($new_category_name, 'product_cat');
            if (!is_wp_error($term)) {
                wp_set_object_terms($product_id, $term['term_id'], 'product_cat', true);
            }
        }
    }
}
