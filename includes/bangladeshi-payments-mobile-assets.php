<?php
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue styles and scripts
function bangladeshi_payments_enqueue_scripts() {
    wp_enqueue_style(
        'banglapay-style',
        plugins_url('css/style.css', __FILE__),
        [],
        '1.0.0' // Plugin version
    );
}
add_action('wp_enqueue_scripts', 'bangladeshi_payments_enqueue_scripts');

