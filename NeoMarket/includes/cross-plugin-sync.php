<?php
/**
 * Cross-plugin Sync with NeoBooking / NeoForm / NeoNotion ecosystem
 */

if (!defined('ABSPATH')) {
    exit;
}

// ===== 1. NeoBooking Sync: if product has _linked_booking_id, sync stock =====
add_action('neomarket_order_status_completed', 'neomarket_sync_neobooking_on_sale', 20, 1);
function neomarket_sync_neobooking_on_sale($order_id) {
    if (!class_exists('NeoBooking')) return;
    $items = NeoMarket_DB::get_order_items($order_id);
    foreach ($items as $item) {
        $booking_id = get_post_meta($item->product_id, '_linked_booking_id', true);
        if ($booking_id) {
            // Deduct availability from NeoBooking
            do_action('neobooking_reduce_availability', $booking_id, $item->quantity, $order_id);
        }
    }
}

// ===== 2. NeoForm Integration: on payment complete, send to NeoForm entry system =====
add_action('neomarket_order_status_completed', 'neomarket_send_to_neoform_on_success', 30, 1);
function neomarket_send_to_neoform_on_success($order_id) {
    $order = NeoMarket_DB::get_order($order_id);
    if (!$order) return;
    $items = NeoMarket_DB::get_order_items($order_id);

    $form_data = apply_filters('neomarket_payment_complete_form_data', array(
        'plugin_source' => 'NeoMarket',
        'order_id'      => $order_id,
        'total'         => $order->total,
        'gateway'       => $order->payment_method,
        'customer'      => $order->billing_first_name . ' ' . $order->billing_last_name,
        'email'         => $order->billing_email,
        'phone'         => $order->billing_phone,
        'items'         => wp_list_pluck($items, 'order_item_name'),
        'order_date'    => $order->date_created,
    ), $order, $items);

    do_action('neoform_record_entry', array(
        'form_id' => apply_filters('neomarket_neoform_target_form_id', 0),
        'data'    => $form_data,
    ));
}

// ===== 3. Add pending orders count to Neonotion root menu badge =====
add_action('admin_menu', 'neomarket_add_root_menu_badge', 100);
function neomarket_add_root_menu_badge() {
    global $menu;
    if (!is_array($menu)) return;

    // Count pending NeoMarket orders
    $pending = (int) get_transient('neomarket_pending_count');
    if ($pending === false) {
        global $wpdb;
        $t = $wpdb->prefix . 'neomarket_orders';
        $pending = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$t} WHERE status IN ('pending','on-hold','processing')"
        );
        set_transient('neomarket_pending_count', $pending, 300);
    }

    // Inject badge text next to the Neonotion root menu label if found
    foreach ($menu as $idx => $m) {
        if (!empty($m[2]) && $m[2] === 'neonotion') {
            if ($pending > 0) {
                $badge = '<span class="update-plugins count-' . $pending . '"><span class="plugin-count">' . $pending . '</span></span>';
                $menu[$idx][0] = rtrim($m[0], ' ') . ' ' . $badge;
            }
            break;
        }
    }
}
add_action('neomarket_order_status_completed', 'neomarket_reset_order_counts');
add_action('neomarket_order_status_cancelled', 'neomarket_reset_order_counts');
function neomarket_reset_order_counts() { delete_transient('neomarket_pending_count'); }
