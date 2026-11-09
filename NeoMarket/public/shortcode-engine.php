<?php
/**
 * Shortcode Engine for NeoMarket - Complete with all shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('neomarket', 'neomarket_frontend_mount');
add_shortcode('neomarket_cart', 'neomarket_cart_shortcode');
add_shortcode('neomarket_checkout', 'neomarket_checkout_shortcode');
add_shortcode('neomarket_wishlist', 'neomarket_wishlist_shortcode');
add_shortcode('neomarket_compare', 'neomarket_compare_shortcode');
add_shortcode('neomarket_my_account', 'neomarket_myaccount_shortcode');

function neomarket_frontend_mount($atts) {
    neomarket_enqueue_public_assets();

    // Handle success/onhold messages from payment gateways (browser redirect after pay)
    $flash = '';
    if (!empty($_GET['neomarket_order'])) {
        $status = sanitize_text_field(wp_unslash($_GET['neomarket_order']));
        $messages = array(
            'success'        => '<div class="nmk-flash success" style="background:#e8f5e9;border:1px solid #2e7d32;padding:15px;border-radius:6px;margin:10px 0;color:#2e7d32;">' . __('Payment successful! Thank you for your order. We will process it shortly.', 'neomarket') . '</div>',
            'failed'         => '<div class="nmk-flash error"   style="background:#ffebee;border:1px solid #c62828;padding:15px;border-radius:6px;margin:10px 0;color:#c62828;">' . __('Payment failed or cancelled. Please try again or contact support.', 'neomarket') . '</div>',
            'onhold'         => '<div class="nmk-flash info"    style="background:#fff8e1;border:1px solid #f57f17;padding:15px;border-radius:6px;margin:10px 0;color:#f57f17;">' . __('Your order is pending admin verification (Card-to-Card / Crypto). We will confirm soon!', 'neomarket') . '</div>',
            'crypto_pending' => '<div class="nmk-flash info"    style="background:#e3f2fd;border:1px solid #1565c0;padding:15px;border-radius:6px;margin:10px 0;color:#1565c0;">' . __('Crypto transaction submitted! After network confirmations, your order will be approved.', 'neomarket') . '</div>',
            'cancelled'      => '<div class="nmk-flash error"   style="background:#ffebee;border:1px solid #c62828;padding:15px;border-radius:6px;margin:10px 0;color:#c62828;">' . __('The payment was cancelled by the user.', 'neomarket') . '</div>',
        );
        $flash = $messages[$status] ?? '';
    }

    ob_start();
    echo $flash;
    ?>
    <div id="neomarket-app" class="neomarket-container">
        <div id="neomarket-loader" class="neomarket-loading-spinner"><?php _e('Loading store...', 'neomarket'); ?></div>
        <div id="neomarket-content-area"></div>
    </div>
    <?php
    return ob_get_clean();
}

// Standalone shortcodes that render their respective view right away (also trigger SPA)
function neomarket_cart_shortcode() {
    neomarket_enqueue_public_assets();
    ob_start();
    echo '<div id="neomarket-app"><div id="neomarket-loader" class="neomarket-loading-spinner"></div><div id="neomarket-content-area" data-initial-view="cart"></div></div>';
    return ob_get_clean();
}
function neomarket_checkout_shortcode() {
    neomarket_enqueue_public_assets();
    ob_start();
    echo '<div id="neomarket-app"><div id="neomarket-loader" class="neomarket-loading-spinner"></div><div id="neomarket-content-area" data-initial-view="checkout"></div></div>';
    return ob_get_clean();
}
function neomarket_wishlist_shortcode() {
    neomarket_enqueue_public_assets();
    ob_start();
    echo '<div id="neomarket-app"><div id="neomarket-loader" class="neomarket-loading-spinner"></div><div id="neomarket-content-area" data-initial-view="wishlist"></div></div>';
    return ob_get_clean();
}
function neomarket_compare_shortcode() {
    neomarket_enqueue_public_assets();
    ob_start();
    echo '<div id="neomarket-app"><div id="neomarket-loader" class="neomarket-loading-spinner"></div><div id="neomarket-content-area" data-initial-view="compare"></div></div>';
    return ob_get_clean();
}
function neomarket_myaccount_shortcode() {
    neomarket_enqueue_public_assets();
    ob_start();
    echo '<div id="neomarket-app"><div id="neomarket-loader" class="neomarket-loading-spinner"></div><div id="neomarket-content-area" data-initial-view="myaccount"></div></div>';
    return ob_get_clean();
}

/**
 * Enqueue public CSS and JS
 */
function neomarket_enqueue_public_assets() {
    wp_enqueue_style('neomarket-public-style', NEOMARKET_URL . 'assets/css/neomarket-public.css', array(), NEOMARKET_VERSION);
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');
    wp_enqueue_style('nouislider-css', 'https://cdn.jsdelivr.net/npm/nouislider@15.8.1/dist/nouislider.min.css', array(), '15.8.1');

    wp_enqueue_script('jquery');
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true);
    wp_enqueue_script('nouislider-js', 'https://cdn.jsdelivr.net/npm/nouislider@15.8.1/dist/nouislider.min.js', array(), '15.8.1', true);

    wp_enqueue_script('neomarket-public-script', NEOMARKET_URL . 'assets/js/neomarket-public.js', array('jquery', 'swiper-js', 'nouislider-js'), NEOMARKET_VERSION, true);

    // Ensure wpApiSettings is available for REST calls inside JS
    if (!wp_script_is('wp-api-request', 'enqueued')) {
        wp_enqueue_script('wp-api-request', includes_url('/js/api-request.min.js'), array('jquery'), false, true);
    }

    wp_localize_script('neomarket-public-script', 'neomarket_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('neomarket_frontend_nonce'),
        'i18n'     => array(
            'loading' => __('Loading...', 'neomarket'),
            'error'   => __('Something went wrong.', 'neomarket')
        )
    ));

    wp_localize_script('neomarket-public-script', 'neomarket_public', array(
        'rest_url'     => esc_url_raw(rest_url()),
        'shop_url'     => get_permalink(get_option('neomarket_shop_page_id')),
        'currency'     => get_option('neomarket_currency', 'IRT'),
    ));
}
