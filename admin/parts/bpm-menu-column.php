<?php 

add_filter( 'manage_edit-shop_order_columns', 'bpm_order_items_column' );
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'bpm_order_items_column' );

function bpm_order_items_column( $columns ) {
    $columns = array_slice( $columns, 0, 4, true ) // 4 columns before
    + array(
        'payment_method' => 'Payment Method',                // Payment Method column
        'payment_method_phone_number' => 'Phone Number', // Payment Method Phone Number column
        'transaction_number' => 'Transaction Number'        // Transaction Number column
    )
    + array_slice( $columns, 4, NULL, true );

    return $columns;
}

add_action( 'manage_shop_order_posts_custom_column', 'bpm_populate_order_items_column', 25, 2 );
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'bpm_populate_order_items_column', 25, 2 );

function bpm_populate_order_items_column( $column_name, $order_or_order_id ) {
    // Get the order object
    $order = $order_or_order_id instanceof WC_Order ? $order_or_order_id : wc_get_order( $order_or_order_id );

    // Display Payment Method
    if ( 'payment_method' === $column_name ) {
        echo esc_html( $order->get_payment_method_title() );
    }

    // Display Payment Method Phone Number
    if ( 'payment_method_phone_number' === $column_name ) {
        $payment_method = $order->get_payment_method();
        $phone_number = '';

        // Custom logic for payment method phone number
        switch ( $payment_method ) {
            case 'bkash':
                $phone_number = get_post_meta( $order->get_id(), '_bkash_phone', true );
                break;
            case 'nagad':
                $phone_number = get_post_meta( $order->get_id(), '_nagad_phone', true );
                break;
            case 'rocket':
                $phone_number = get_post_meta( $order->get_id(), '_rocket_phone', true );
                break;
            case 'upay':
                $phone_number = get_post_meta( $order->get_id(), '_upay_phone', true );
                break;
            default:
                $phone_number = 'N/A';
        }

        echo $phone_number ? esc_html( $phone_number ) : 'N/A'; // Fallback if no phone number is provided
    }

    // Display Transaction Number
    if ( 'transaction_number' === $column_name ) {
        $payment_method = $order->get_payment_method();
        $transaction_number = '';

        // Custom logic for transaction number based on the payment method
        switch ( $payment_method ) {
            case 'bkash':
                $transaction_number = get_post_meta( $order->get_id(), '_bkash_transaction_id', true );
                break;
            case 'nagad':
                $transaction_number = get_post_meta( $order->get_id(), '_nagad_transaction_id', true );
                break;
            case 'rocket':
                $transaction_number = get_post_meta( $order->get_id(), '_rocket_transaction_id', true );
                break;
            case 'upay':
                $transaction_number = get_post_meta( $order->get_id(), '_upay_transaction_id', true );
                break;
            default:
                $transaction_number = 'N/A';
        }

        echo $transaction_number ? esc_html( $transaction_number ) : 'N/A'; // Fallback if no transaction number is provided
    }
}