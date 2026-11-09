<?php
/**
 * Cart Engine
 */

if (!defined('ABSPATH')) {
    exit;
}

class NeoMarket_Cart {

    private static $cart_contents = null;
    private static $cart_key = null;
    private static $initialized = false;

    const CART_KEY_COOKIE = 'neomarket_cart_key';

    public static function init() {
        if (self::$initialized) return;
        self::$initialized = true;
        add_action('init', array(__CLASS__, 'load_cart_from_storage'));
        add_action('shutdown', array(__CLASS__, 'persist'));
    }

    public static function load_cart_from_storage() {
        if (self::$cart_contents !== null) return;

        // 1. Logged in user: load by user_id
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            self::$cart_key = 'neomarket_cart_user_' . $user_id;
        } else {
            // 2. Guest: use cookie key or generate one
            if (!empty($_COOKIE[self::CART_KEY_COOKIE])) {
                self::$cart_key = sanitize_text_field(wp_unslash($_COOKIE[self::CART_KEY_COOKIE]));
            } else {
                self::$cart_key = 'neomarket_cart_guest_' . wp_generate_password(32, false);
                setcookie(self::CART_KEY_COOKIE, self::$cart_key, time() + (86400 * 60), COOKIEPATH, COOKIE_DOMAIN, is_ssl());
            }
        }

        $stored = NeoMarket_DB::load_cart(self::$cart_key);
        if (is_array($stored) && isset($stored['contents'])) {
            self::$cart_contents = is_array($stored['contents']) ? $stored['contents'] : array();
        } else {
            self::$cart_contents = array();
        }
    }

    /**
     * Generate unique line key per product
     */
    private static function line_key($product_id, $variation_id = 0) {
        return md5($product_id . '|' . $variation_id);
    }

    /**
     * Add product to cart
     */
    public static function add($product_id, $quantity = 1) {
        self::load_cart_from_storage();
        $product = get_post($product_id);

        if (!$product || $product->post_type !== 'neo_product' || $product->post_status !== 'publish') {
            return new WP_Error('invalid_product', __('Invalid product', 'neomarket'));
        }

        $manage_stock = get_post_meta($product_id, '_manage_stock', true);
        $stock_qty = intval(get_post_meta($product_id, '_stock_quantity', true));
        $sold_individually = get_post_meta($product_id, '_sold_individually', true);
        $stock_status = get_post_meta($product_id, '_stock_status', true);

        $key = self::line_key($product_id);
        $existing_qty = isset(self::$cart_contents[$key]) ? self::$cart_contents[$key]['qty'] : 0;
        $new_qty = $existing_qty + $quantity;

        if ($sold_individually === 'yes' && $existing_qty > 0) {
            return new WP_Error('sold_individually', __('This product is sold individually.', 'neomarket'));
        }
        if ($stock_status === 'outofstock') {
            return new WP_Error('outofstock', __('This product is out of stock.', 'neomarket'));
        }
        if ($manage_stock === 'yes' && $stock_qty < $new_qty) {
            return new WP_Error('stock_limit', sprintf(__('Only %d units available.', 'neomarket'), $stock_qty));
        }

        $price = floatval(get_post_meta($product_id, '_sale_price', true) ?: get_post_meta($product_id, '_regular_price', true));

        self::$cart_contents[$key] = array(
            'key'          => $key,
            'product_id'   => $product_id,
            'title'        => get_the_title($product_id),
            'thumbnail'    => has_post_thumbnail($product_id) ? get_the_post_thumbnail_url($product_id, 'thumbnail') : '',
            'qty'          => $new_qty,
            'price_each'   => $price,
            'subtotal'     => $price * $new_qty,
        );
        return true;
    }

    /**
     * Update line qty (0 to remove)
     */
    public static function update_item($key, $qty) {
        self::load_cart_from_storage();
        if (!isset(self::$cart_contents[$key])) {
            return new WP_Error('not_found', __('Item not in cart', 'neomarket'));
        }
        if ($qty <= 0) {
            unset(self::$cart_contents[$key]);
            return true;
        }
        $product_id = self::$cart_contents[$key]['product_id'];
        $manage_stock = get_post_meta($product_id, '_manage_stock', true);
        $stock_qty = intval(get_post_meta($product_id, '_stock_quantity', true));
        if ($manage_stock === 'yes' && $stock_qty < $qty) {
            return new WP_Error('stock_limit', sprintf(__('Only %d units available.', 'neomarket'), $stock_qty));
        }
        $price = self::$cart_contents[$key]['price_each'];
        self::$cart_contents[$key]['qty'] = $qty;
        self::$cart_contents[$key]['subtotal'] = $price * $qty;
        return true;
    }

    public static function remove($key) {
        self::load_cart_from_storage();
        unset(self::$cart_contents[$key]);
    }

    public static function empty_cart() {
        self::$cart_contents = array();
    }

    /**
     * Get full cart state with totals
     */
    public static function get_state() {
        self::load_cart_from_storage();
        $items = array_values(self::$cart_contents ?: array());
        $subtotal = 0;
        foreach ($items as $it) $subtotal += $it['subtotal'];

        $shipping = apply_filters('neomarket_cart_shipping_total', self::calculate_shipping($subtotal), $items, $subtotal);
        $tax = apply_filters('neomarket_cart_tax', round($subtotal * 0.00, 2), $subtotal);
        $discount = apply_filters('neomarket_cart_discount', 0, $subtotal);

        $total = round(($subtotal - $discount) + $shipping + $tax, 2);

        return array(
            'cart_key' => self::$cart_key,
            'items'    => $items,
            'count'    => array_sum(wp_list_pluck($items, 'qty')),
            'totals'   => array(
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'tax'      => $tax,
                'discount' => $discount,
                'total'    => $total,
                'currency' => self::currency(),
            ),
        );
    }

    public static function currency() {
        return get_option('neomarket_currency', 'IRT');
    }

    private static function calculate_shipping($subtotal) {
        $free_threshold = floatval(get_option('neomarket_free_shipping_threshold', 1000000));
        $flat_rate = floatval(get_option('neomarket_flat_shipping', 50000));
        if ($subtotal >= $free_threshold && $subtotal > 0) return 0;
        return empty($items) ? 0 : $flat_rate;
    }

    /**
     * Save cart on shutdown
     */
    public static function persist() {
        if (self::$cart_key === null) return;
        NeoMarket_DB::save_cart(self::$cart_key, array(
            'contents' => self::$cart_contents,
            'totals'   => self::get_state()['totals'],
            'session'  => array(),
        ), is_user_logged_in() ? get_current_user_id() : 0);
    }
}

NeoMarket_Cart::init();
