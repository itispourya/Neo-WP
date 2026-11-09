<?php
/**
 * AJAX Handlers for NeoMarket Frontend - Complete Version
 * Includes: Catalog, Product, Cart, Views Loader, Checkout, Reviews, Payments
 */

if (!defined('ABSPATH')) {
    exit;
}

// ===== Catalog / Product (existing) =====
add_action('wp_ajax_neomarket_load_catalog', 'neomarket_ajax_load_catalog');
add_action('wp_ajax_nopriv_neomarket_load_catalog', 'neomarket_ajax_load_catalog');

add_action('wp_ajax_neomarket_load_product', 'neomarket_ajax_load_product');
add_action('wp_ajax_nopriv_neomarket_load_product', 'neomarket_ajax_load_product');

add_action('wp_ajax_neomarket_submit_review', 'neomarket_ajax_submit_review');
add_action('wp_ajax_nopriv_neomarket_submit_review', 'neomarket_ajax_submit_review');

// ===== Views (Cart / Checkout / Wishlist / Compare) =====
add_action('wp_ajax_neomarket_load_view', 'neomarket_ajax_load_view');
add_action('wp_ajax_nopriv_neomarket_load_view', 'neomarket_ajax_load_view');

// ===== Cart AJAX =====
add_action('wp_ajax_neomarket_cart_get', 'neomarket_ajax_cart_get');
add_action('wp_ajax_nopriv_neomarket_cart_get', 'neomarket_ajax_cart_get');

add_action('wp_ajax_neomarket_cart_add', 'neomarket_ajax_cart_add');
add_action('wp_ajax_nopriv_neomarket_cart_add', 'neomarket_ajax_cart_add');

add_action('wp_ajax_neomarket_cart_update', 'neomarket_ajax_cart_update');
add_action('wp_ajax_nopriv_neomarket_cart_update', 'neomarket_ajax_cart_update');

add_action('wp_ajax_neomarket_cart_remove', 'neomarket_ajax_cart_remove');
add_action('wp_ajax_nopriv_neomarket_cart_remove', 'neomarket_ajax_cart_remove');

// ===== Checkout =====
add_action('wp_ajax_neomarket_checkout_submit', 'neomarket_ajax_checkout_submit');
add_action('wp_ajax_nopriv_neomarket_checkout_submit', 'neomarket_ajax_checkout_submit');

// ---------- Catalog ----------
function neomarket_ajax_load_catalog() {
    check_ajax_referer('neomarket_frontend_nonce', 'nonce');

    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $price_min = isset($_POST['price_min']) ? floatval($_POST['price_min']) : 0;
    $price_max = isset($_POST['price_max']) ? floatval($_POST['price_max']) : 0;

    $args = array(
        'post_type'      => 'neo_product',
        'posts_per_page' => 12,
        'paged'          => $paged,
        'post_status'    => 'publish',
        's'              => $search,
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

    if (!empty($category)) {
        $args['tax_query'] = array(
            array('taxonomy' => 'neo_product_cat', 'field' => 'slug', 'terms' => $category),
        );
    }

    $query = new WP_Query($args);

    ob_start();
    include NEOMARKET_PATH . 'public/frontend-templates/catalog.php';
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
}

// ---------- Single Product ----------
function neomarket_ajax_load_product() {
    check_ajax_referer('neomarket_frontend_nonce', 'nonce');

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if (!$product_id) wp_send_json_error(__('Invalid product ID', 'neomarket'));

    $post = get_post($product_id);
    if (!$post || $post->post_type !== 'neo_product') wp_send_json_error(__('Product not found', 'neomarket'));

    global $product_id;
    $product_id = $post->ID;
    ob_start();
    include NEOMARKET_PATH . 'public/frontend-templates/single-product.php';
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
}

// ---------- Review ----------
function neomarket_ajax_submit_review() {
    check_ajax_referer('neomarket_frontend_nonce', 'nonce');

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
    $content = isset($_POST['review_content']) ? sanitize_textarea_field($_POST['review_content']) : '';
    $user_id = get_current_user_id();

    if (!$user_id || !neomarket_user_can_review($user_id, $product_id)) {
        wp_send_json_error(__('Only verified purchasers can review this product.', 'neomarket'));
    }
    if (empty($content)) {
        wp_send_json_error(__('Please enter review content.', 'neomarket'));
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
        wp_send_json_success(__('Review submitted successfully!', 'neomarket'));
    }
    wp_send_json_error(__('Failed to submit review.', 'neomarket'));
}

// ---------- Views Loader ----------
function neomarket_ajax_load_view() {
    check_ajax_referer('neomarket_frontend_nonce', 'nonce');
    $view = isset($_POST['view']) ? sanitize_key($_POST['view']) : 'catalog';
    $allowed = array('cart', 'checkout', 'wishlist', 'compare', 'myaccount', 'thankyou');
    if (!in_array($view, $allowed, true)) {
        wp_send_json_error(__('Invalid view', 'neomarket'));
    }
    ob_start();
    $template = NEOMARKET_PATH . 'public/frontend-templates/' . $view . '.php';
    if (file_exists($template)) {
        include $template;
    } else {
        echo '<p>' . esc_html__('Template missing:', 'neomarket') . ' ' . esc_html($view) . '</p>';
    }
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
}

// ---------- Cart GET ----------
function neomarket_ajax_cart_get() {
    check_ajax_referer('neomarket_frontend_nonce', 'nonce');
    wp_send_json_success(NeoMarket_Cart::get_state());
}

// ---------- Cart ADD ----------
function neomarket_ajax_cart_add() {
    check_ajax_referer('neomarket_frontend_nonce', 'nonce');
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

    $r = NeoMarket_Cart::add($product_id, $quantity);
    if (is_wp_error($r)) {
        wp_send_json_error($r->get_error_message());
    }
    wp_send_json_success(array(
        'message' => sprintf(__('%s added to your cart!', 'neomarket'), get_the_title($product_id)),
        'state'   => NeoMarket_Cart::get_state(),
    ));
}

// ---------- Cart UPDATE ----------
function neomarket_ajax_cart_update() {
    check_ajax_referer('neomarket_frontend_nonce', 'nonce');
    $key = sanitize_text_field(wp_unslash($_POST['key'] ?? ''));
    $qty = max(0, intval($_POST['quantity'] ?? 0));
    $r = NeoMarket_Cart::update_item($key, $qty);
    if (is_wp_error($r)) wp_send_json_error($r->get_error_message());
    wp_send_json_success(NeoMarket_Cart::get_state());
}

// ---------- Cart REMOVE ----------
function neomarket_ajax_cart_remove() {
    check_ajax_referer('neomarket_frontend_nonce', 'nonce');
    $key = sanitize_text_field(wp_unslash($_POST['key'] ?? ''));
    NeoMarket_Cart::remove($key);
    wp_send_json_success(NeoMarket_Cart::get_state());
}

// ---------- Checkout Submit ----------
function neomarket_ajax_checkout_submit() {
    check_ajax_referer('neomarket_frontend_nonce', 'nonce');

    $cart = NeoMarket_Cart::get_state();
    if (empty($cart['items'])) {
        wp_send_json_error(__('Your cart is empty.', 'neomarket'));
    }

    $gateway_id = sanitize_text_field(wp_unslash($_POST['payment_method'] ?? ''));
    $gateway = NeoMarket_Payments::get_gateway($gateway_id);
    if (!$gateway || !$gateway->is_enabled()) {
        // Gateways might return "need_details". If so, create order first, then provide form.
        $available = NeoMarket_Payments::get_enabled_gateways();
        if (empty($gateway_id) && !empty($available)) {
            $gateway_id = array_key_first($available);
            $gateway = reset($available);
        } else {
            wp_send_json_error(__('Please select a valid payment method.', 'neomarket'));
        }
    }

    // Build order data from form
    $order_data = array(
        'billing_email'       => sanitize_email($_POST['billing_email'] ?? ''),
        'billing_phone'       => sanitize_text_field($_POST['billing_phone'] ?? ''),
        'billing_first_name'  => sanitize_text_field($_POST['billing_first_name'] ?? ''),
        'billing_last_name'   => sanitize_text_field($_POST['billing_last_name'] ?? ''),
        'billing_address_1'   => sanitize_textarea_field($_POST['billing_address_1'] ?? ''),
        'billing_city'        => sanitize_text_field($_POST['billing_city'] ?? ''),
        'billing_state'       => sanitize_text_field($_POST['billing_state'] ?? ''),
        'billing_postcode'    => sanitize_text_field($_POST['billing_postcode'] ?? ''),
        'billing_country'     => sanitize_text_field($_POST['billing_country'] ?? 'IR'),
        'shipping_first_name' => sanitize_text_field($_POST['shipping_first_name'] ?? ''),
        'shipping_last_name'  => sanitize_text_field($_POST['shipping_last_name'] ?? ''),
        'shipping_address_1'  => sanitize_textarea_field($_POST['shipping_address_1'] ?? ''),
        'shipping_city'       => sanitize_text_field($_POST['shipping_city'] ?? ''),
        'shipping_state'      => sanitize_text_field($_POST['shipping_state'] ?? ''),
        'shipping_postcode'   => sanitize_text_field($_POST['shipping_postcode'] ?? ''),
        'shipping_country'    => sanitize_text_field($_POST['shipping_country'] ?? 'IR'),
        'order_comments'      => sanitize_textarea_field($_POST['order_comments'] ?? ''),
        'payment_method'      => $gateway->id,
        'card_payment_ref'    => sanitize_text_field($_POST['card_payment_ref'] ?? ''),
        'card_payment_receipt'=> esc_url_raw($_POST['card_payment_receipt'] ?? ''),
        'crypto_tx_hash'      => sanitize_text_field($_POST['crypto_tx_hash'] ?? ''),
        'crypto_currency'     => sanitize_text_field($_POST['crypto_currency'] ?? ''),
        'crypto_amount'       => floatval($_POST['crypto_amount'] ?? 0),
    );

    $order_id = NeoMarket_Order::create_from_cart($cart, $order_data);
    if (is_wp_error($order_id)) {
        wp_send_json_error($order_id->get_error_message());
    }

    // Store order id as reference for the gateway (in case CTC/Crypto first pass -> needs details)
    $_REQUEST['order_id'] = $order_id;
    $_POST['order_id'] = $order_id;

    $payment_result = $gateway->process_payment($order_id);
    if (is_wp_error($payment_result)) {
        NeoMarket_DB::update_order_status($order_id, 'failed');
        wp_send_json_error($payment_result->get_error_message());
    }

    wp_send_json_success($payment_result);
}
