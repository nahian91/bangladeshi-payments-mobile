<?php
/**
 * Plugin Name: Bangladeshi Payments for Mobile
 * Plugin URI: https://devnahian.com/bangladeshi-payments-mobile/
 * Description: Bangladeshi Payments Mobile is a WooCommerce payment gateway that enables seamless mobile payments with bKash, Nagad, Rocket, and Upay for online stores.
 * Version: 1.0.1
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
}

// Show an admin notice if WooCommerce is not installed or activated
function bangladeshi_payments_woocommerce_missing_notice() {
    echo '<div class="error"><p>' . esc_html__('Bangladeshi Payments for Mobile requires WooCommerce to be installed and active.', 'bangladeshi-payments-mobile') . '</p></div>';
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