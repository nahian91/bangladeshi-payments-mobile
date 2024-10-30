<?php
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue frontend styles and scripts
function bangladeshi_payments_enqueue_scripts() {
    wp_enqueue_style(
        'banglapay-style',
        plugins_url('css/style.css', __FILE__),
        [],
        '1.0.0' // Plugin version
    );
}
add_action('wp_enqueue_scripts', 'bangladeshi_payments_enqueue_scripts');

// Enqueue admin styles
function bangladeshi_payments_enqueue_admin_styles() {
    wp_enqueue_style(
        'bpm-admin-style',
        plugins_url('css/admin-style.css', __FILE__)
    );
}
add_action('admin_enqueue_scripts', 'bangladeshi_payments_enqueue_admin_styles');
