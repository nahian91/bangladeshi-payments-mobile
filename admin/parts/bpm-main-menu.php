<?php 

function bangladeshi_payments_add_admin_menu() {
    add_menu_page(
        __('BD Payments', 'bangladeshi-payments-mobile'), // Page title
        __('BD Payments', 'bangladeshi-payments-mobile'), // Menu title
        'manage_options', // Capability
        'bangladeshi-payments-mobile', // Menu slug
        'bangladeshi_payments_render_general_page', // Callback function
        'dashicons-money', // Icon
        56 // Position
    );

    // Transaction Info submenu
    add_submenu_page(
        'bangladeshi-payments-mobile',
        __('Transaction Info', 'bangladeshi-payments-mobile'),
        __('Transaction Info', 'bangladeshi-payments-mobile'),
        'manage_options',
        'bangladeshi-payments-transaction-info',
        'bangladeshi_payments_render_transaction_info_page'
    );
}
add_action('admin_menu', 'bangladeshi_payments_add_admin_menu');