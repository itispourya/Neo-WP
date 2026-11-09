<?php
/**
 * Abstract Payment Gateway Class
 * Every gateway extends this
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class NeoMarket_Gateway {

    public $id = '';
    public $title = '';
    public $description = '';
    public $enabled = false;
    public $icon = '';

    public function __construct() {
        $settings = get_option('neomarket_gateway_' . $this->id, array());
        $this->enabled = isset($settings['enabled']) ? $settings['enabled'] === 'yes' : false;
        $this->title = $settings['title'] ?? $this->title;
        $this->description = $settings['description'] ?? $this->description;
    }

    public function is_enabled() {
        return (bool) $this->enabled;
    }

    public function get_icon() {
        return esc_url($this->icon);
    }

    public function get_title() {
        return esc_html($this->title);
    }

    public function get_description() {
        return wpautop(wp_kses_post($this->description));
    }

    public function get_option($key, $default = '') {
        $settings = get_option('neomarket_gateway_' . $this->id, array());
        return $settings[$key] ?? $default;
    }

    /**
     * Process payment, returns redirect URL or form HTML
     *
     * @param int $order_id
     * @return array|WP_Error
     */
    abstract public function process_payment($order_id);

    /**
     * Handles gateway callback/IPN
     */
    abstract public function handle_callback();

    /**
     * Settings for the admin UI
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'enabled' => array(
                'label' => __('Enable', 'neomarket'),
                'type'  => 'checkbox',
                'default' => 'no',
            ),
            'title' => array(
                'label' => __('Display Title', 'neomarket'),
                'type'  => 'text',
                'default' => $this->title,
            ),
            'description' => array(
                'label' => __('Customer Message', 'neomarket'),
                'type'  => 'textarea',
                'default' => $this->description,
            ),
        );
    }
}

/**
 * Factory: Loads all gateways
 */
class NeoMarket_Payments {

    private static $gateways = array();
    private static $loaded = false;

    public static function init() {
        if (self::$loaded) return;
        self::$loaded = true;

        require_once NEOMARKET_PATH . 'includes/payments/gateway-zarinpal.php';
        require_once NEOMARKET_PATH . 'includes/payments/gateway-payping.php';
        require_once NEOMARKET_PATH . 'includes/payments/gateway-mellat.php';
        require_once NEOMARKET_PATH . 'includes/payments/gateway-card-to-card.php';
        require_once NEOMARKET_PATH . 'includes/payments/gateway-crypto.php';

        do {
            $gw_classes = apply_filters('neomarket_register_gateways', array(
                'zarinpal'   => 'NeoMarket_Gateway_Zarinpal',
                'payping'    => 'NeoMarket_Gateway_PayPing',
                'mellat'     => 'NeoMarket_Gateway_Mellat',
                'cardtocard' => 'NeoMarket_Gateway_CardToCard',
                'crypto'     => 'NeoMarket_Gateway_Crypto',
            ));
            foreach ($gw_classes as $id => $class) {
                if (class_exists($class)) {
                    self::$gateways[$id] = new $class();
                }
            }
        } while (0);

        // Register callback hook
        add_action('template_redirect', array(__CLASS__, 'route_callback'));
        add_action('rest_api_init', array(__CLASS__, 'register_callback_routes'));
    }

    public static function get_gateway($id) {
        self::init();
        return self::$gateways[$id] ?? null;
    }

    public static function get_all_gateways() {
        self::init();
        return self::$gateways;
    }

    public static function get_enabled_gateways() {
        return array_filter(self::get_all_gateways(), function($gw) {
            return $gw->is_enabled();
        });
    }

    /**
     * Legacy callback via template_redirect
     * URL: /?neomarket_gateway=zarinpal&order_id=XX
     */
    public static function route_callback() {
        if (empty($_GET['neomarket_gateway'])) return;
        $id = sanitize_text_field(wp_unslash($_GET['neomarket_gateway']));
        $gw = self::get_gateway($id);
        if ($gw && is_callable(array($gw, 'handle_callback'))) {
            $gw->handle_callback();
            exit;
        }
    }

    /**
     * Modern callback via REST
     */
    public static function register_callback_routes() {
        register_rest_route(NeoMarket_REST::NAMESPACE, '/payment/callback/(?P<gateway>[a-zA-Z0-9_]+)', array(
            'methods'  => WP_REST_Server::ALLMETHODS,
            'callback' => array(__CLASS__, 'rest_callback'),
            'permission_callback' => '__return_true',
        ));
    }

    public static function rest_callback($request) {
        $id = $request['gateway'];
        $gw = self::get_gateway($id);
        if ($gw && is_callable(array($gw, 'handle_callback'))) {
            ob_start();
            $gw->handle_callback();
            $out = ob_get_clean();
            return new WP_REST_Response(array('gateway' => $id, 'output' => $out), 200);
        }
        return new WP_Error('invalid_gateway', __('Unknown gateway', 'neomarket'), array('status' => 404));
    }

    /**
     * Helper: Log transaction
     */
    public static function log_transaction($order_id, $gateway_id, $txn_type, $status, $gateway_txn_id, $response, $amount, $currency = 'IRT') {
        return NeoMarket_DB::insert_transaction(array(
            'order_id'         => $order_id,
            'gateway_id'       => $gateway_id,
            'txn_type'         => $txn_type,
            'txn_status'       => $status,
            'gateway_txn_id'   => $gateway_txn_id,
            'gateway_response' => maybe_serialize($response),
            'amount'           => $amount,
            'currency'         => $currency,
        ));
    }

    /**
     * Order success page after payment
     */
    public static function get_thankyou_url($order_id) {
        // Return to shortcode page with ?order_received=ID
        $shop_page_id = get_option('neomarket_shop_page_id');
        $base = $shop_page_id ? get_permalink($shop_page_id) : home_url('/');
        return add_query_arg(array('order_received' => $order_id, 'key' => get_post_meta($order_id, '_order_key', true)), $base);
    }
}

NeoMarket_Payments::init();
