<?php
/**
 * GDPR / WordPress Privacy Tools - Data Exporter & Eraser
 * Exports and removes NeoMarket customer PII on user request
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wp_privacy_personal_data_exporters', 'neomarket_register_data_exporter');
function neomarket_register_data_exporter($exporters) {
    $exporters['neomarket-customer'] = array(
        'exporter_friendly_name' => __('NeoMarket Customer Data', 'neomarket'),
        'callback'               => 'neomarket_personal_data_exporter',
    );
    return $exporters;
}

function neomarket_personal_data_exporter($email_address, $page = 1) {
    $user = get_user_by('email', $email_address);
    $data_to_export = array();

    // 1. Order Data
    $orders = NeoMarket_DB::get_user_orders($user ? $user->ID : 0);
    // Also search guest orders by email if no user found
    if (!$user) {
        global $wpdb;
        $table = $wpdb->prefix . 'neomarket_orders';
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE billing_email = %s ORDER BY date_created DESC LIMIT 500",
            $email_address
        ));
    }

    foreach ($orders as $order) {
        $items = NeoMarket_DB::get_order_items($order->order_id);
        $item_list = array_map(function($i) { return "{$i->order_item_name} x {$i->quantity}"; }, $items);
        $data_to_export[] = array(
            'group_id'    => 'neomarket_orders',
            'group_label' => __('NeoMarket Orders', 'neomarket'),
            'item_id'     => 'neomarket-order-' . $order->order_id,
            'data'        => array(
                array('name' => __('Order #', 'neomarket'), 'value' => $order->order_id),
                array('name' => __('Date', 'neomarket'), 'value' => $order->date_created),
                array('name' => __('Status', 'neomarket'), 'value' => $order->status),
                array('name' => __('Total', 'neomarket'), 'value' => $order->total . ' ' . $order->currency),
                array('name' => __('Items', 'neomarket'), 'value' => implode(', ', $item_list)),
                array('name' => __('Billing Name', 'neomarket'), 'value' => $order->billing_first_name . ' ' . $order->billing_last_name),
                array('name' => __('Billing Phone', 'neomarket'), 'value' => $order->billing_phone),
                array('name' => __('Billing Address', 'neomarket'), 'value' => $order->billing_address_1 . ', ' . $order->billing_city),
                array('name' => __('Shipping Address', 'neomarket'), 'value' => $order->shipping_address_1 . ', ' . $order->shipping_city),
            ),
        );
    }

    // 2. Cart session data
    $cart_saved = NeoMarket_DB::load_cart('neomarket_cart_user_' . ($user ? $user->ID : 0));
    if ($cart_saved && !empty($cart_saved['contents'])) {
        $data_to_export[] = array(
            'group_id'    => 'neomarket_cart',
            'group_label' => __('NeoMarket Saved Cart', 'neomarket'),
            'item_id'     => 'neomarket-cart',
            'data'        => array(array('name' => __('Items', 'neomarket'), 'value' => maybe_serialize($cart_saved['contents']))),
        );
    }

    return array(
        'data' => $data_to_export,
        'done' => true,
    );
}

// ====== Eraser ======
add_filter('wp_privacy_personal_data_erasers', 'neomarket_register_data_eraser');
function neomarket_register_data_eraser($erasers) {
    $erasers['neomarket-customer'] = array(
        'eraser_friendly_name' => __('NeoMarket Customer Data', 'neomarket'),
        'callback'             => 'neomarket_personal_data_eraser',
    );
    return $erasers;
}

function neomarket_personal_data_eraser($email_address, $page = 1) {
    global $wpdb;
    $table = $wpdb->prefix . 'neomarket_orders';
    $items_table = $wpdb->prefix . 'neomarket_order_items';

    $user = get_user_by('email', $email_address);
    $order_ids = array();

    // Collect order ids
    if ($user) {
        $ids = $wpdb->get_col($wpdb->prepare("SELECT order_id FROM {$table} WHERE user_id = %d", $user->ID));
        $order_ids = array_merge($order_ids, $ids);
    }
    $ids = $wpdb->get_col($wpdb->prepare("SELECT order_id FROM {$table} WHERE billing_email = %s", $email_address));
    $order_ids = array_unique(array_merge($order_ids, $ids));

    // Anonymize PII after 365 days
    $cutoff = date('Y-m-d H:i:s', time() - (365 * 86400));
    $erased_orders = 0;
    foreach ($order_ids as $oid) {
        $order = NeoMarket_DB::get_order($oid);
        if (!$order) continue;
        if ($order->date_created > $cutoff && !in_array($order->status, array('cancelled', 'refunded'), true)) {
            continue; // Keep recent active orders
        }
        $wpdb->update($table,
            array(
                'billing_email'      => '[anonymized]',
                'billing_phone'      => '',
                'billing_first_name' => '[Anon]',
                'billing_last_name'  => '',
                'billing_address_1'  => '',
                'billing_address_2'  => '',
                'billing_city'       => '',
                'billing_state'      => '',
                'billing_postcode'   => '',
                'shipping_first_name' => '[Anon]',
                'shipping_last_name'  => '',
                'shipping_address_1'  => '',
                'shipping_address_2'  => '',
                'shipping_city'       => '',
                'shipping_state'      => '',
                'shipping_postcode'   => '',
                'customer_ip_address' => '',
                'customer_user_agent' => '',
                'crypto_tx_hash' => '',
                'card_payment_ref'  => '',
                'card_payment_receipt_url' => '',
            ),
            array('order_id' => $oid)
        );
        $erased_orders++;
    }

    // Clean carts
    if ($user) {
        $wpdb->delete($wpdb->prefix . 'neomarket_cart', array('user_id' => $user->ID));
    }
    // Clean comments (reviews) — erase the author identity
    if ($user) {
        $wpdb->update($wpdb->comments,
            array('comment_author' => '[Anonymized]', 'comment_author_email' => '', 'comment_author_IP' => ''),
            array('user_id' => $user->ID)
        );
    }

    return array(
        'items_removed'  => $erased_orders,
        'items_retained' => count($order_ids) - $erased_orders,
        'messages'       => array(
            sprintf(_n('%d NeoMarket order anonymized.', '%d NeoMarket orders anonymized.', $erased_orders, 'neomarket'), $erased_orders)
        ),
        'done'           => true,
    );
}
