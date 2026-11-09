<?php
/**
 * REST API Controller for NeoMarket
 * Namespace: neomarket/v1
 */

if (!defined('ABSPATH')) {
    exit;
}

class NeoMarket_REST extends WP_REST_Controller {

    const NAMESPACE = 'neomarket/v1';

    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    public static function register_routes() {
        // Product catalog
        register_rest_route(self::NAMESPACE, '/products', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'get_products'),
            'permission_callback' => '__return_true',
        ));

        // Single product
        register_rest_route(self::NAMESPACE, '/products/(?P<id>\d+)', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'get_product'),
            'permission_callback' => '__return_true',
        ));

        // CRUD Cart
        register_rest_route(self::NAMESPACE, '/cart', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'get_cart'),
            'permission_callback' => '__return_true',
        ));
        register_rest_route(self::NAMESPACE, '/cart/add', array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => array(__CLASS__, 'cart_add'),
            'permission_callback' => '__return_true',
        ));
        register_rest_route(self::NAMESPACE, '/cart/(?P<key>[a-zA-Z0-9]+)/update', array(
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => array(__CLASS__, 'cart_update'),
            'permission_callback' => '__return_true',
        ));
        register_rest_route(self::NAMESPACE, '/cart/(?P<key>[a-zA-Z0-9]+)', array(
            'methods'  => WP_REST_Server::DELETABLE,
            'callback' => array(__CLASS__, 'cart_remove'),
            'permission_callback' => '__return_true',
        ));

        // Checkout
        register_rest_route(self::NAMESPACE, '/checkout', array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => array(__CLASS__, 'submit_checkout'),
            'permission_callback' => '__return_true',
        ));

        // Order lookup
        register_rest_route(self::NAMESPACE, '/orders/(?P<id>\d+)', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'get_order'),
            'permission_callback' => array(__CLASS__, 'order_permission_check'),
        ));

        // Verified reviews
        register_rest_route(self::NAMESPACE, '/reviews', array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => array(__CLASS__, 'submit_review'),
            'permission_callback' => '__return_true',
        ));

        // Dashboard stats
        register_rest_route(self::NAMESPACE, '/admin/stats', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'admin_stats'),
            'permission_callback' => 'current_user_can_manage_options',
        ));
    }

    // ================= Products =================
    public static function get_products($request) {
        self::rate_limit_check('catalog', 60);
        $paged = max(1, intval($request->get_param('paged')));
        $cat = sanitize_text_field($request->get_param('category'));
        $search = sanitize_text_field($request->get_param('search'));
        $price_min = floatval($request->get_param('price_min'));
        $price_max = floatval($request->get_param('price_max'));

        $args = array(
            'post_type' => 'neo_product',
            'posts_per_page' => 12,
            'paged' => $paged,
            's' => $search,
            'post_status' => 'publish'
        );

        $meta_query = array('relation' => 'AND');
        if ($price_min > 0 || $price_max > 0) {
            $meta_query[] = array(
                'key'     => '_regular_price',
                'value'   => array($price_min, $price_max > 0 ? $price_max : PHP_INT_MAX),
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN',
            );
        }
        if (!empty($meta_query)) $args['meta_query'] = $meta_query;

        if (!empty($cat)) {
            $args['tax_query'] = array(
                array('taxonomy' => 'neo_product_cat', 'field' => 'slug', 'terms' => $cat)
            );
        }

        $cached = NeoMarket_Cache::get_cached_products($args);
        return new WP_REST_Response($cached, 200);
    }

    public static function get_product($request) {
        $id = absint($request['id']);
        $post = get_post($id);
        if (!$post || $post->post_type !== 'neo_product') {
            return new WP_Error('not_found', __('Product not found', 'neomarket'), array('status' => 404));
        }
        $product = self::prepare_product_response($post);
        return new WP_REST_Response($product, 200);
    }

    private static function prepare_product_response($post) {
        $product_id = $post->ID;
        return array(
            'id'           => $product_id,
            'title'        => get_the_title($product_id),
            'excerpt'      => get_the_excerpt($product_id),
            'content'      => apply_filters('the_content', $post->post_content),
            'link'         => get_permalink($product_id),
            'thumbnail'    => has_post_thumbnail($product_id) ? get_the_post_thumbnail_url($product_id, 'large') : '',
            'gallery'      => self::get_gallery($product_id),
            'regular_price' => get_post_meta($product_id, '_regular_price', true),
            'sale_price'   => get_post_meta($product_id, '_sale_price', true),
            'sku'          => get_post_meta($product_id, '_sku', true),
            'stock_status' => get_post_meta($product_id, '_stock_status', true),
            'stock_qty'    => get_post_meta($product_id, '_stock_quantity', true),
            'specifications' => get_post_meta($product_id, '_specifications', true),
            'categories'   => wp_get_post_terms($product_id, 'neo_product_cat', array('fields' => 'names')),
            'tags'         => wp_get_post_terms($product_id, 'neo_product_tag', array('fields' => 'names')),
            'related_html' => NeoMarket_Cache::get_cached_related($product_id),
        );
    }

    private static function get_gallery($product_id) {
        $gallery = get_post_meta($product_id, '_product_gallery', true);
        $ids = $gallery ? explode(',', $gallery) : array();
        $images = array();
        foreach ($ids as $id) {
            $url = wp_get_attachment_image_url($id, 'large');
            if ($url) $images[] = $url;
        }
        return $images;
    }

    // ================= Cart =================
    public static function get_cart() {
        return new WP_REST_Response(NeoMarket_Cart::get_state(), 200);
    }

    public static function cart_add($request) {
        self::rate_limit_check('cart', 60, 60);
        $product_id = absint($request->get_param('product_id'));
        $quantity = max(1, intval($request->get_param('quantity', 1)));
        $result = NeoMarket_Cart::add($product_id, $quantity);
        if (is_wp_error($result)) return $result;
        return new WP_REST_Response(NeoMarket_Cart::get_state(), 200);
    }

    public static function cart_update($request) {
        $key = $request['key'];
        $quantity = max(0, intval($request->get_param('quantity', 0)));
        $result = NeoMarket_Cart::update_item($key, $quantity);
        if (is_wp_error($result)) return $result;
        return new WP_REST_Response(NeoMarket_Cart::get_state(), 200);
    }

    public static function cart_remove($request) {
        NeoMarket_Cart::remove($request['key']);
        return new WP_REST_Response(NeoMarket_Cart::get_state(), 200);
    }

    // ================= Checkout =================
    public static function submit_checkout($request) {
        self::rate_limit_check('checkout', 2, 60);

        $params = $request->get_params();
        $cart = NeoMarket_Cart::get_state();
        if (empty($cart['items'])) {
            return new WP_Error('empty_cart', __('Cart is empty', 'neomarket'), array('status' => 400));
        }

        $gateway_id = sanitize_text_field($params['payment_method'] ?? '');
        $gateway = NeoMarket_Payments::get_gateway($gateway_id);
        if (!$gateway || !$gateway->is_enabled()) {
            return new WP_Error('invalid_gateway', __('Invalid payment method', 'neomarket'), array('status' => 400));
        }

        // Create order
        $order_id = NeoMarket_Order::create_from_cart($cart, $params);
        if (is_wp_error($order_id)) return $order_id;

        // Process payment
        $payment = $gateway->process_payment($order_id);
        if (is_wp_error($payment)) {
            NeoMarket_DB::update_order_status($order_id, 'failed');
            return $payment;
        }

        return new WP_REST_Response($payment, 200);
    }

    // ================= Order =================
    public static function order_permission_check($request) {
        $id = absint($request['id']);
        $user_id = get_current_user_id();
        if (current_user_can('manage_options')) return true;
        $order = NeoMarket_DB::get_order($id);
        return $order && intval($order->user_id) === $user_id;
    }

    public static function get_order($request) {
        $id = absint($request['id']);
        $order = NeoMarket_DB::get_order($id);
        $items = NeoMarket_DB::get_order_items($id);
        return new WP_REST_Response(array('order' => $order, 'items' => $items), 200);
    }

    // ================= Reviews =================
    public static function submit_review($request) {
        self::rate_limit_check('review', 10, 3600);
        $params = $request->get_params();
        $product_id = absint($params['product_id']);
        $rating = absint($params['rating'] ?? 5);
        $content = sanitize_textarea_field($params['review_content'] ?? '');
        $user_id = get_current_user_id();

        if (!$user_id || !neomarket_user_can_review($user_id, $product_id)) {
            return new WP_Error('forbidden', __('Only verified purchasers can review.', 'neomarket'), array('status' => 403));
        }
        if (empty($content)) {
            return new WP_Error('empty', __('Please enter your review content.', 'neomarket'), array('status' => 400));
        }

        $comment_id = wp_insert_comment(array(
            'comment_post_ID'      => $product_id,
            'comment_content'      => $content,
            'comment_type'         => 'comment',
            'user_id'              => $user_id,
            'comment_author'       => wp_get_current_user()->display_name,
            'comment_author_email' => wp_get_current_user()->user_email,
            'comment_approved'     => 1,
        ));
        if ($comment_id) {
            update_comment_meta($comment_id, 'rating', min(5, max(1, $rating)));
            update_comment_meta($comment_id, 'is_verified', 1);
            return new WP_REST_Response(array('message' => __('Review submitted!', 'neomarket')), 200);
        }
        return new WP_Error('server_err', __('Unable to submit review.', 'neomarket'), array('status' => 500));
    }

    // ================= Admin Stats =================
    public static function admin_stats() {
        global $wpdb;
        $order_table = $wpdb->prefix . 'neomarket_orders';
        $items_table = $wpdb->prefix . 'neomarket_order_items';

        $stats = array(
            'total_products' => wp_count_posts('neo_product')->publish,
            'total_orders'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$order_table}"),
            'completed_orders_total' => (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total),0) FROM {$order_table} WHERE status = %s", 'completed'
            )),
            'monthly_sales'  => $wpdb->get_results(
                "SELECT DATE_FORMAT(date_created, '%Y-%m') as month, 
                        COALESCE(SUM(CASE WHEN status='completed' THEN total ELSE 0 END),0) as revenue,
                        COUNT(*) as count
                 FROM {$order_table}
                 GROUP BY DATE_FORMAT(date_created, '%Y-%m')
                 ORDER BY month DESC LIMIT 12"
            , ARRAY_A),
            'top_products'   => $wpdb->get_results(
                "SELECT p.post_title as title, COALESCE(SUM(oi.quantity),0) as units_sold
                 FROM {$items_table} oi
                 LEFT JOIN {$wpdb->posts} p ON p.ID = oi.product_id
                 GROUP BY oi.product_id
                 ORDER BY units_sold DESC LIMIT 5"
            , ARRAY_A),
            'low_stock'      => self::get_low_stock(),
            'pending_orders' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$order_table} WHERE status = %s", 'on-hold')),
        );
        return new WP_REST_Response($stats, 200);
    }

    private static function get_low_stock() {
        $threshold = apply_filters('neomarket_low_stock_threshold', 5);
        $q = new WP_Query(array(
            'post_type'      => 'neo_product',
            'posts_per_page' => 10,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_manage_stock',
                    'value'   => 'yes',
                    'compare' => '='
                ),
                array(
                    'key'     => '_stock_quantity',
                    'value'   => $threshold,
                    'type'    => 'NUMERIC',
                    'compare' => '<='
                )
            ),
            'fields' => 'ids'
        ));
        return array_map(function($id) {
            return array('id' => $id, 'title' => get_the_title($id), 'qty' => get_post_meta($id, '_stock_quantity', true));
        }, $q->posts);
    }

    // ================= Rate Limit =================
    private static function rate_limit_check($action, $limit = 10, $window = 3600) {
        $ip = NeoMarket_DB::get_ip_address();
        $user = get_current_user_id() ?: 'guest';
        $key = sprintf('neomarket_rl_%s_%s_%s', $action, $user, md5($ip));
        $count = (int) get_transient($key);
        if ($count >= $limit) {
            wp_die(json_encode(array('code' => 'rate_limit_exceeded', 'message' => __('Too many requests, please slow down.', 'neomarket'))), 429);
        }
        if ($count === 0) {
            set_transient($key, 1, $window);
        } else {
            set_transient($key, ++$count, $window);
        }
    }
}

NeoMarket_REST::init();
