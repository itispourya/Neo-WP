<?php
/**
 * Zarinpal Gateway (زرین‌پال) - Iran's most popular payment gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class NeoMarket_Gateway_Zarinpal extends NeoMarket_Gateway {

    public $id = 'zarinpal';
    public $title = 'پرداخت آنلاین زرین‌پال';
    public $description = 'با استفاده از تمامی کارت‌های عضو شتاب پرداخت کنید.';
    public $icon = '';

    public function get_settings_fields() {
        return parent::get_settings_fields() + array(
            'merchant_id' => array(
                'label' => __('Merchant ID', 'neomarket'),
                'type'  => 'text',
                'default' => '',
            ),
            'mode' => array(
                'label' => __('Mode', 'neomarket'),
                'type'  => 'select',
                'options' => array('sandbox' => 'Sandbox', 'production' => 'Production'),
                'default' => 'sandbox',
            ),
            'currency' => array(
                'label' => __('Currency', 'neomarket'),
                'type'  => 'select',
                'options' => array('IRT' => 'Toman', 'IRR' => 'Rial'),
                'default' => 'IRT',
            ),
        );
    }

    private function get_api_base() {
        return ($this->get_option('mode', 'sandbox') === 'sandbox')
            ? 'https://sandbox.zarinpal.com/pg/rest/WebGate/'
            : 'https://www.zarinpal.com/pg/rest/WebGate/';
    }

    public function process_payment($order_id) {
        $order = NeoMarket_DB::get_order($order_id);
        $merchant = $this->get_option('merchant_id');
        $amount = (float) $order->total;
        $currency = $this->get_option('currency', 'IRT');
        if ($currency === 'IRT') $amount = $amount * 10; // Zarinpal expects Rial if merchant uses IRR, convert Toman->Rial

        $callback_url = add_query_arg(array(
            'neomarket_gateway' => 'zarinpal',
            'order_id' => $order_id,
        ), home_url('/'));

        $payload = array(
            'MerchantID'  => $merchant,
            'Amount'      => $amount,
            'CallbackURL' => $callback_url,
            'Description' => sprintf(__('Order #%d', 'neomarket'), $order_id),
            'Email'       => $order->billing_email,
            'Mobile'      => $order->billing_phone,
        );

        $response = $this->remote_post($this->get_api_base() . 'PaymentRequest.json', $payload);

        if (is_wp_error($response)) return $response;
        if (empty($response->Authority) || intval($response->Status) !== 100) {
            return new WP_Error('zarinpal_error', __('Zarinpal request failed. Status: ', 'neomarket') . ($response->Status ?? 'N/A'));
        }

        NeoMarket_Payments::log_transaction($order_id, $this->id, 'payment', 'pending', $response->Authority, $response, $order->total, $order->currency);
        NeoMarket_DB::update_order($order_id, array('transaction_id' => $response->Authority));

        $redirect = ($this->get_option('mode', 'sandbox') === 'sandbox')
            ? 'https://sandbox.zarinpal.com/pg/StartPay/' . $response->Authority
            : 'https://www.zarinpal.com/pg/StartPay/' . $response->Authority;

        return array(
            'result'   => 'success',
            'redirect' => $redirect,
            'order_id' => $order_id,
        );
    }

    public function handle_callback() {
        $order_id = absint($_GET['order_id'] ?? 0);
        $authority = sanitize_text_field(wp_unslash($_GET['Authority'] ?? ''));
        $status = sanitize_text_field(wp_unslash($_GET['Status'] ?? 'NOK'));

        $order = NeoMarket_DB::get_order($order_id);
        if (!$order) {
            wp_die(esc_html__('Invalid order.', 'neomarket'));
        }

        if ($status !== 'OK') {
            NeoMarket_DB::update_order_status($order_id, 'cancelled');
            wp_redirect(add_query_arg(array('neomarket_order' => 'cancelled'), $this->get_return_url()));
            exit;
        }

        $amount = (float) $order->total;
        $currency = $this->get_option('currency', 'IRT');
        if ($currency === 'IRT') $amount = $amount * 10;

        $verify = $this->remote_post($this->get_api_base() . 'PaymentVerification.json', array(
            'MerchantID' => $this->get_option('merchant_id'),
            'Authority'  => $authority,
            'Amount'     => $amount,
        ));

        if (!is_wp_error($verify) && in_array(intval($verify->Status ?? 0), array(100, 101), true)) {
            NeoMarket_DB::update_order($order_id, array('transaction_id' => $authority . '|' . ($verify->RefID ?? '')));
            NeoMarket_DB::update_order_status($order_id, 'completed');
            NeoMarket_Cart::empty_cart();
            NeoMarket_Payments::log_transaction($order_id, $this->id, 'verify', 'success', $verify->RefID ?? $authority, $verify, $order->total, $order->currency);
            wp_redirect(add_query_arg(array('neomarket_order' => 'success', 'order_id' => $order_id), $this->get_return_url()));
        } else {
            NeoMarket_DB::update_order_status($order_id, 'failed');
            NeoMarket_Payments::log_transaction($order_id, $this->id, 'verify', 'failed', $authority, $verify, $order->total, $order->currency);
            wp_redirect(add_query_arg(array('neomarket_order' => 'failed', 'reason' => 'verification_failed'), $this->get_return_url()));
        }
        exit;
    }

    private function remote_post($url, $payload) {
        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode($payload),
            'timeout' => 30,
        ));
        if (is_wp_error($response)) return $response;
        return json_decode(wp_remote_retrieve_body($response));
    }

    private function get_return_url() {
        $shop_page_id = get_option('neomarket_shop_page_id');
        return $shop_page_id ? get_permalink($shop_page_id) : home_url('/');
    }
}
