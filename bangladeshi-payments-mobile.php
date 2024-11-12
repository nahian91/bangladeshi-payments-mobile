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

if ( ! function_exists( 'bpm_fs' ) ) {
    // Create a helper function for easy SDK access.
    function bpm_fs() {
        global $bpm_fs;

        if ( ! isset( $bpm_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $bpm_fs = fs_dynamic_init( array(
                'id'                  => '16934',
                'slug'                => 'bangladeshi-payments-mobile',
                'type'                => 'plugin',
                'public_key'          => 'pk_ff1bb6ab2d17aaed18d152b447c88',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
                'menu'                => array(
                    'slug'           => 'bangladeshi-payments-mobile',
                    'account'        => false,
                    'support'        => false,
                ),
            ) );
        }

        return $bpm_fs;
    }

    // Init Freemius.
    bpm_fs();
    // Signal that SDK was initiated.
    do_action( 'bpm_fs_loaded' );
}

/**
 * Plugin activation hook
 * Sets a transient to handle redirection after activation
 */
register_activation_hook(__FILE__, 'bangladeshi_payments_mobile_activate');

function bangladeshi_payments_mobile_activate() {
    // Set a transient to check activation
    set_transient('bangladeshi_payments_mobile_activation_redirect', true, 30);
}

/**
 * Admin init hook for handling redirection after activation
 */
add_action('admin_init', 'bangladeshi_payments_mobile_redirect_after_activation');

function bangladeshi_payments_mobile_redirect_after_activation() {
    // Check if the transient is set
    if (get_transient('bangladeshi_payments_mobile_activation_redirect')) {
        // Delete the transient to avoid repeated redirects
        delete_transient('bangladeshi_payments_mobile_activation_redirect');

        // Prevent redirection during bulk plugin activation
        if (is_network_admin() || isset($_GET['activate-multi'])) {
            return;
        }

        // Redirect to the WooCommerce General Settings page
        wp_safe_redirect(admin_url('admin.php?page=bangladeshi-payments-mobile'));
        exit;
    }
}

/**
 * Add Settings link on the plugin page
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'bangladeshi_payments_mobile_settings_link');

function bangladeshi_payments_mobile_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=bangladeshi-payments-mobile') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
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
    // Check if WooCommerce is not active
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        // The URL for WooCommerce installation page
        $woo_install_url = admin_url('plugin-install.php?tab=search&s=woocommerce');

        // Admin notice HTML
        echo '<div class="error" id="bpm-woocommerce-notice" style="padding: 15px; background-color: #f8d7da; border-left: 4px solid #f5c6cb; border-radius: 5px; color: #721c24;">
            <p style="font-size: 16px; font-weight: bold; margin-bottom: 10px;">' . 
            esc_html__('Bangladeshi Payments Mobile requires WooCommerce to be installed and active.', 'bangladeshi-payments-mobile') . '</p>
            <p style="font-size: 14px; margin: 0;">' . 
            esc_html__('Please install and activate WooCommerce to use the Bangladeshi Payments Mobile plugin.', 'bangladeshi-payments-mobile') . '</p>
            <p id="bpm-install-message" style="margin-top: 10px; font-size: 14px;">
                <a href="' . esc_url($woo_install_url) . '" target="_blank" class="button-primary" style="background-color: #0073aa; color: #fff; padding: 8px 12px; border-radius: 3px; text-decoration: none;">
                    ' . esc_html__('Install WooCommerce Now', 'bangladeshi-payments-mobile') . '
                </a>
            </p>
        </div>';
    }
}
add_action('admin_notices', 'bangladeshi_payments_woocommerce_missing_notice');

// Check if WooCommerce is active, hide notice if true
function hide_woocommerce_missing_notice() {
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        remove_action('admin_notices', 'bangladeshi_payments_woocommerce_missing_notice');
    }
}
add_action('admin_init', 'hide_woocommerce_missing_notice');



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
