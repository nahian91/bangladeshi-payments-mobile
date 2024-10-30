<?php
/**
 * Plugin Name: Bangladeshi Payments Mobile
 * Plugin URI: https://devnahian.com/bangladeshi-payments-mobile/
 * Description: Bangladeshi Payments Mobile is a WooCommerce payment gateway that enables seamless mobile payments with bKash, Nagad, Rocket, and Upay for online stores.
 * Version: 1.0.3
 * Author: Abdullah Nahian
 * Author URI: https://devnahian.com
 * Text Domain: bangladeshi-payments-mobile
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Ensure WooCommerce is active before initializing the plugin
add_action('plugins_loaded', 'bangladeshi_payments_check_woocommerce', 11);
function bangladeshi_payments_check_woocommerce() {
    if (!class_exists('WC_Payment_Gateway')) {
        // WooCommerce is not active, show an admin notice
        add_action('admin_notices', 'bangladeshi_payments_woocommerce_missing_notice');
        return;
    }

    // Load dependencies
    require_once plugin_dir_path(__FILE__) . 'includes/class-bkash-gateway.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-nagad-gateway.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-rocket-gateway.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-upay-gateway.php';
    require_once plugin_dir_path(__FILE__) . 'includes/bangladeshi-payments-mobile-assets.php';

    // Add the gateway to WooCommerce
    add_filter('woocommerce_payment_gateways', 'bangladeshi_payments_add_gateway_class');

    // Add an admin menu page for transaction info
    add_action('admin_menu', 'bangladeshi_payments_add_admin_menu');

    // Add a custom column to the WooCommerce Orders page
    add_filter('manage_edit-shop_order_columns', 'add_custom_order_column');
    add_action('manage_shop_order_posts_custom_column', 'custom_order_column_content', 10, 2);
    add_filter('manage_edit-shop_order_sortable_columns', 'make_custom_order_column_sortable');
}

// Show an admin notice if WooCommerce is not installed or activated
function bangladeshi_payments_woocommerce_missing_notice() {
    echo '<div class="error"><p>' . esc_html__('Bangladeshi Payments Mobile requires WooCommerce to be installed and active.', 'bangladeshi-payments-mobile') . '</p></div>';
}

// Add the payment gateway to WooCommerce
function bangladeshi_payments_add_gateway_class($gateways) {
    $gateways[] = 'WC_Gateway_bKash';
    $gateways[] = 'WC_Gateway_Nagad';
    $gateways[] = 'WC_Gateway_Rocket';
    $gateways[] = 'WC_Gateway_Upay';
    return $gateways;
}

require_once plugin_dir_path(__FILE__) . 'includes/bangladeshi-payments-mobile-menu.php';

// Add a custom column to the WooCommerce Orders page
function add_custom_order_column($columns) {
    // Insert the custom column after the "order total" column
    $columns['custom_column'] = __('Custom Column', 'bangladeshi-payments-mobile'); // Update text domain
    return $columns;
}

// Display content in the custom column
function custom_order_column_content($column, $post_id) {
    if ('custom_column' === $column) {
        // For debugging: output the post ID and check if the function is called
        echo '<strong>Order ID:</strong> ' . esc_html($post_id) . '<br>'; // Debug line
        
        // Retrieve your custom data (replace with your logic)
        // Temporary static value for testing
        $custom_data = 'Test Value'; // Temporarily return a test value
        echo esc_html($custom_data);
    }
}

// Make the custom column sortable (optional)
function make_custom_order_column_sortable($columns) {
    $columns['custom_column'] = 'custom_column';
    return $columns;
}
