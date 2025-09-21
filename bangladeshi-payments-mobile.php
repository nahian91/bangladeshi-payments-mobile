<?php
/**
 * Plugin Name: Bangladeshi Payments Mobile – QR Code & Transaction Reports
 * Plugin URI: https://devnahian.com/bangladeshi-payments-mobile/
 * Description: Bangladeshi Payments Mobile is a WooCommerce payment gateway that enables seamless mobile payments with bKash, Nagad, Rocket, and Upay for online stores.
 * Version: 1.4
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

// Freemius SDK
if (!function_exists('bpm_fs')) {
    function bpm_fs() {
        global $bpm_fs;

        if (!isset($bpm_fs)) {
            require_once dirname(__FILE__) . '/freemius/start.php';

            $bpm_fs = fs_dynamic_init(array(
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
            ));
        }

        return $bpm_fs;
    }

    bpm_fs();
    do_action('bpm_fs_loaded');
}

// Plugin activation check for WooCommerce
register_activation_hook(__FILE__, 'bangladeshi_payments_mobile_activate');
function bangladeshi_payments_mobile_activate() {
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        wp_die(
            __('This plugin requires WooCommerce to be installed and activated. Please install and activate WooCommerce first.', 'bangladeshi-payments-mobile'),
            __('Plugin Activation Error', 'bangladeshi-payments-mobile'),
            array('back_link' => true)
        );
    }
}

// Add Settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'bangladeshi_payments_mobile_settings_link');
function bangladeshi_payments_mobile_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=bangladeshi-payments-mobile') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Ensure WooCommerce is active before loading plugin
add_action('plugins_loaded', 'bangladeshi_payments_check_woocommerce', 15);
function bangladeshi_payments_check_woocommerce() {
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'bangladeshi_payments_woocommerce_missing_notice');
        return;
    }

    // Load gateway classes
    require_once plugin_dir_path(__FILE__) . 'includes/class-bkash-gateway.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-nagad-gateway.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-rocket-gateway.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-upay-gateway.php';
    require_once plugin_dir_path(__FILE__) . 'admin/bpm-assets.php';
    require_once plugin_dir_path(__FILE__) . 'admin/bpm-menu.php';

    // Add gateways to WooCommerce
    add_filter('woocommerce_payment_gateways', 'bangladeshi_payments_add_gateway_class');

    // Admin menu & WooCommerce orders customization
    add_action('admin_menu', 'bangladeshi_payments_add_admin_menu');
    add_filter('manage_edit-shop_order_columns', 'add_custom_order_column');
    add_action('manage_shop_order_posts_custom_column', 'custom_order_column_content', 10, 2);
    add_filter('manage_edit-shop_order_sortable_columns', 'make_custom_order_column_sortable');
}

// WooCommerce missing notice
function bangladeshi_payments_woocommerce_missing_notice() {
    $woo_install_url = admin_url('plugin-install.php?tab=search&s=woocommerce');
    echo '<div class="error" style="padding:15px; background-color:#f8d7da; border-left:4px solid #f5c6cb; border-radius:5px; color:#721c24;">
        <p><strong>' . esc_html__('Bangladeshi Payments Mobile requires WooCommerce to be installed and active.', 'bangladeshi-payments-mobile') . '</strong></p>
        <p>' . esc_html__('Please install and activate WooCommerce to use the plugin.', 'bangladeshi-payments-mobile') . '</p>
        <p><a href="' . esc_url($woo_install_url) . '" target="_blank" class="button-primary">' . esc_html__('Install WooCommerce Now', 'bangladeshi-payments-mobile') . '</a></p>
    </div>';
}
add_action('admin_notices', 'bangladeshi_payments_woocommerce_missing_notice');
add_action('admin_init', function() {
    if (is_plugin_active('woocommerce/woocommerce.php')) {
        remove_action('admin_notices', 'bangladeshi_payments_woocommerce_missing_notice');
    }
});

// Add gateways to WooCommerce
function bangladeshi_payments_add_gateway_class($gateways) {
    $gateways[] = 'WC_Gateway_bKash';
    $gateways[] = 'WC_Gateway_Nagad';
    $gateways[] = 'WC_Gateway_Rocket';
    $gateways[] = 'WC_Gateway_Upay';
    return $gateways;
}

/**
 * Enqueue admin CSS and JS
 */
add_action('admin_enqueue_scripts', 'bpm_admin_assets');
function bpm_admin_assets($hook) {

    // Admin CSS
    wp_enqueue_style(
        'bpm-admin-css',
        plugin_dir_url(__FILE__) . 'admin/assets/css/jquery.dataTables.min.css',
        array(),
        '1.0.0',
        'all'
    );

    // Admin JS
    wp_enqueue_script(
        'bpm-admin-js',
        plugin_dir_url(__FILE__) . 'admin/assets/js/jquery.dataTables.min.js',
        array('jquery'),
        '1.0.0',
        true
    );

    // Pass variables to JS
    wp_localize_script('bpm-admin-js', 'bpm_admin_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('bpm_admin_nonce')
    ));
}
