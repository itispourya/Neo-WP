<?php
/**
 * Order Factory & Stock Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class NeoMarket_Order {

    /**
     * Create order from cart state
     */
    public static function create_from_cart($cart, $form_data = array()) {
        if (empty($cart['items'])) {
            return new WP_Error('empty_cart', __('Cart is empty', 'neomarket'));
        }

        $totals = $cart['totals'];
        $user_id = get_current_user_id();

        $order_key = 'nmk_' . wp_generate_password(20, false);

        $order_data = array(
            'order_key'           => $order_key,
            'user_id'             => $user_id,
            'billing_email'       => sanitize_email($form_data['billing_email'] ?? ''),
            'billing_phone'       => sanitize_text_field($form_data['billing_phone'] ?? ''),
            'billing_first_name'  => sanitize_text_field($form_data['billing_first_name'] ?? ''),
            'billing_last_name'   => sanitize_text_field($form_data['billing_last_name'] ?? ''),
            'billing_address_1'   => sanitize_textarea_field($form_data['billing_address_1'] ?? ''),
            'billing_city'        => sanitize_text_field($form_data['billing_city'] ?? ''),
            'billing_state'       => sanitize_text_field($form_data['billing_state'] ?? ''),
            'billing_postcode'    => sanitize_text_field($form_data['billing_postcode'] ?? ''),
            'billing_country'     => sanitize_text_field($form_data['billing_country'] ?? 'IR'),
            'shipping_first_name' => sanitize_text_field($form_data['shipping_first_name'] ?? ($form_data['billing_first_name'] ?? '')),
            'shipping_last_name'  => sanitize_text_field($form_data['shipping_last_name'] ?? ($form_data['billing_last_name'] ?? '')),
            'shipping_address_1'  => sanitize_textarea_field($form_data['shipping_address_1'] ?? ($form_data['billing_address_1'] ?? '')),
            'shipping_city'       => sanitize_text_field($form_data['shipping_city'] ?? ($form_data['billing_city'] ?? '')),
            'shipping_state'      => sanitize_text_field($form_data['shipping_state'] ?? ($form_data['billing_state'] ?? '')),
            'shipping_postcode'   => sanitize_text_field($form_data['shipping_postcode'] ?? ($form_data['billing_postcode'] ?? '')),
            'shipping_country'    => sanitize_text_field($form_data['shipping_country'] ?? 'IR'),
            'payment_method'      => sanitize_text_field($form_data['payment_method'] ?? ''),
            'currency'            => $totals['currency'] ?? 'IRT',
            'discount_total'      => $totals['discount'] ?? 0,
            'shipping_total'      => $totals['shipping'] ?? 0,
            'shipping_tax'        => 0,
            'cart_tax'            => $totals['tax'] ?? 0,
            'total'               => $totals['total'] ?? 0,
            'total_tax'           => 0,
            'prices_include_tax'  => 0,
            'order_notes'         => sanitize_textarea_field($form_data['order_comments'] ?? ''),
        );

        // Card-to-card or crypto metadata
        if (!empty($form_data['card_payment_ref'])) {
            $order_data['card_payment_ref'] = sanitize_text_field($form_data['card_payment_ref']);
        }
        if (!empty($form_data['card_payment_receipt'])) {
            $order_data['card_payment_receipt_url'] = esc_url_raw($form_data['card_payment_receipt']);
        }
        if (!empty($form_data['crypto_tx_hash'])) {
            $order_data['crypto_tx_hash'] = sanitize_text_field($form_data['crypto_tx_hash']);
            $order_data['crypto_currency'] = sanitize_text_field($form_data['crypto_currency'] ?? 'USDT');
            $order_data['crypto_amount'] = floatval($form_data['crypto_amount'] ?? 0);
        }

        $order_id = NeoMarket_DB::insert_order($order_data);
        if (!$order_id) {
            return new WP_Error('db_error', __('Could not create order.', 'neomarket'));
        }

        // Save line items
        foreach ($cart['items'] as $item) {
            NeoMarket_DB::insert_order_item(array(
                'order_item_name'   => $item['title'],
                'order_item_type'   => 'line_item',
                'order_id'          => $order_id,
                'product_id'        => $item['product_id'],
                'variation_id'      => 0,
                'quantity'          => $item['qty'],
                'unit_price'        => $item['price_each'],
                'line_subtotal'     => $item['subtotal'],
                'line_subtotal_tax' => 0,
                'line_total'        => $item['subtotal'],
                'line_tax'          => 0,
            ));
        }

        // Mark user can review after completion
        do_action('neomarket_new_order_created', $order_id, $order_data);

        return $order_id;
    }

    /**
     * Helper: Determine if user has purchased a product
     * Used by Verified Reviews check
     */
    public static function user_purchased_product($user_id, $product_id) {
        if (!$user_id) return false;
        global $wpdb;
        $orders_table = $wpdb->prefix . 'neomarket_orders';
        $items_table  = $wpdb->prefix . 'neomarket_order_items';

        $sql = $wpdb->prepare(
            "SELECT COUNT(o.order_id) FROM {$orders_table} o
             LEFT JOIN {$items_table} i ON i.order_id = o.order_id
             WHERE o.user_id = %d AND o.status IN ('completed', 'processing')
               AND i.product_id = %d LIMIT 1",
            $user_id, $product_id
        );
        return (int) $wpdb->get_var($sql) > 0;
    }
}

// Hook Verified Purchases check
add_filter('neomarket_user_has_purchased', array('NeoMarket_Order', 'user_purchased_product'), 10, 3);

// Stock management on completed/canceled orders
add_action('neomarket_order_status_completed', 'neomarket_adjust_stock_on_complete', 10, 1);
function neomarket_adjust_stock_on_complete($order_id) {
    $items = NeoMarket_DB::get_order_items($order_id);
    foreach ($items as $item) {
        $manage = get_post_meta($item->product_id, '_manage_stock', true);
        if ($manage === 'yes') {
            $current = intval(get_post_meta($item->product_id, '_stock_quantity', true));
            $new = max(0, $current - intval($item->quantity));
            update_post_meta($item->product_id, '_stock_quantity', $new);
            if ($new <= 0) {
                update_post_meta($item->product_id, '_stock_status', 'outofstock');
            }
            // Low stock email
            $threshold = apply_filters('neomarket_low_stock_threshold', 5);
            if ($new <= $threshold) {
                $to = get_option('admin_email');
                $subject = sprintf(__('Low stock: %s', 'neomarket'), get_the_title($item->product_id));
                @wp_mail($to, $subject, sprintf(__('Stock is now %d for product #%d', 'neomarket'), $new, $item->product_id));
            }
        }
    }
}

add_action('neomarket_order_status_cancelled', 'neomarket_restore_stock_on_cancel', 10, 1);
function neomarket_restore_stock_on_cancel($order_id) {
    $items = NeoMarket_DB::get_order_items($order_id);
    foreach ($items as $item) {
        $manage = get_post_meta($item->product_id, '_manage_stock', true);
        if ($manage === 'yes') {
            $current = intval(get_post_meta($item->product_id, '_stock_quantity', true));
            $new = $current + intval($item->quantity);
            update_post_meta($item->product_id, '_stock_quantity', $new);
            if ($new > 0 && get_post_meta($item->product_id, '_stock_status', true) === 'outofstock') {
                update_post_meta($item->product_id, '_stock_status', 'instock');
            }
        }
    }
}
