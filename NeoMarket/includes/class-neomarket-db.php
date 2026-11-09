<?php
/**
 * Secure Database Wrapper Class
 * Enforces prepared statements for all queries
 */

if (!defined('ABSPATH')) {
    exit;
}

class NeoMarket_DB {

    /**
     * Get order by ID
     */
    public static function get_order($order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'neomarket_orders';
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE order_id = %d", $order_id)
        );
    }

    /**
     * Get order by key
     */
    public static function get_order_by_key($order_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'neomarket_orders';
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE order_key = %s", $order_key)
        );
    }

    /**
     * Get orders by user
     */
    public static function get_user_orders($user_id, $status = 'any') {
        global $wpdb;
        $table = $wpdb->prefix . 'neomarket_orders';
        $sql = "SELECT * FROM {$table} WHERE user_id = %d";
        if ($status !== 'any') {
            $sql .= $wpdb->prepare(" AND status = %s", $status);
        }
        $sql .= " ORDER BY date_created DESC";
        return $wpdb->get_results($wpdb->prepare($sql, $user_id));
    }

    /**
     * Get order items
     */
    public static function get_order_items($order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'neomarket_order_items';
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE order_id = %d", $order_id)
        );
    }

    /**
     * Insert new order
     */
    public static function insert_order($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'neomarket_orders';
        $defaults = array(
            'date_created'  => current_time('mysql'),
            'date_modified' => current_time('mysql'),
            'status'        => 'pending',
            'user_id'       => get_current_user_id(),
            'customer_ip_address' => self::get_ip_address(),
        );
        $data = wp_parse_args($data, $defaults);

        $format = array(
            '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            '%f', '%f', '%f', '%f', '%f', '%f', '%s',
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'
        );

        $wpdb->insert($table, $data, $format);
        return $wpdb->insert_id;
    }

    /**
     * Update order
     */
    public static function update_order($order_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'neomarket_orders';
        $data['date_modified'] = current_time('mysql');
        return $wpdb->update(
            $table,
            $data,
            array('order_id' => $order_id)
        );
    }

    /**
     * Update order status
     */
    public static function update_order_status($order_id, $new_status) {
        global $wpdb;
        $old_order = self::get_order($order_id);
        $result = self::update_order($order_id, array('status' => $new_status));

        if ($new_status === 'completed' && (!$old_order || $old_order->status !== 'completed')) {
            // Trigger stock deduction via action
            do_action('neomarket_order_status_completed', $order_id, $old_order ? $old_order->status : '');
        }
        if ($new_status === 'cancelled' && (!$old_order || $old_order->status === 'processing' || $old_order->status === 'completed')) {
            do_action('neomarket_order_status_cancelled', $order_id, $old_order ? $old_order->status : '');
        }
        do_action('neomarket_order_status_' . $new_status, $order_id, $old_order ? $old_order->status : '');

        return $result;
    }

    /**
     * Insert order item
     */
    public static function insert_order_item($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'neomarket_order_items';
        $wpdb->insert($table, $data, array('%s', '%s', '%d', '%d', '%d', '%d', '%f', '%f', '%f', '%f', '%f', '%s'));
        return $wpdb->insert_id;
    }

    /**
     * Insert transaction log
     */
    public static function insert_transaction($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'neomarket_transactions';
        $defaults = array(
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'ip_address' => self::get_ip_address(),
        );
        $data = wp_parse_args($data, $defaults);
        $wpdb->insert($table, $data, array('%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s'));
        return $wpdb->insert_id;
    }

    /**
     * Persist Cart
     */
    public static function save_cart($cart_key, $cart_data, $user_id = 0, $expire_days = 60) {
        global $wpdb;
        $table = $wpdb->prefix . 'neomarket_cart';
        $existing = $wpdb->get_var($wpdb->prepare("SELECT cart_key FROM {$table} WHERE cart_key = %s", $cart_key));

        $payload = array(
            'cart_key'     => $cart_key,
            'user_id'      => $user_id,
            'cart_contents' => maybe_serialize($cart_data['contents']),
            'cart_totals'  => maybe_serialize($cart_data['totals']),
            'session_data' => maybe_serialize($cart_data['session']),
            'updated_at'   => current_time('mysql'),
            'expires_at'   => date('Y-m-d H:i:s', time() + ($expire_days * 86400)),
        );

        if ($existing) {
            $wpdb->update($table, $payload, array('cart_key' => $cart_key));
        } else {
            $payload['created_at'] = current_time('mysql');
            $wpdb->insert($table, $payload);
        }
    }

    /**
     * Load Cart
     */
    public static function load_cart($cart_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'neomarket_cart';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE cart_key = %s AND (expires_at > NOW() OR expires_at = '0000-00-00 00:00:00')", $cart_key));
        if (!$row) return null;
        return array(
            'contents' => maybe_unserialize($row->cart_contents),
            'totals'   => maybe_unserialize($row->cart_totals),
            'session'  => maybe_unserialize($row->session_data),
        );
    }

    /**
     * Helper: Real IP
     */
    public static function get_ip_address() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            return sanitize_text_field(trim($parts[0]));
        }
        return sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
    }
}
