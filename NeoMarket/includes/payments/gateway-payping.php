<?php
/**
 * PayPing Gateway (پی‌پینگ) - Iranian payment provider
 */

if (!defined('ABSPATH')) {
    exit;
}

class NeoMarket_Gateway_PayPing extends NeoMarket_Gateway {

    public $id = 'payping';
    public $title = 'پرداخت آنلاین پی‌پینگ';
    public $description = 'پرداخت امن از طریق درگاه پی‌پینگ';

    public function get_settings_fields() {
        return parent::get_settings_fields() + array(
            'token'   => array('label' => 'Access Token', 'type' => 'text', 'default' => ''),
            'sandbox' => array('label' => 'Sandbox Mode', 'type' => 'checkbox', 'default' => 'yes'),
        );
    }

    public function process_payment($order_id) {
        $order = NeoMarket_DB::get_order($order_id);
        $token = $this->get_option('token');
        $sandbox = $this->get_option('sandbox', 'yes');
        $base = $sandbox === 'yes' ? 'https://api.payping.ir/v1/test/' : 'https://api.payping.ir/v1/';

        $callback = add_query_arg(array('neomarket_gateway' => 'payping', 'order_id' => $order_id), home_url('/'));

        $payload = array(
            'Amount'      => (float) $order->total, // Toman
            'ReturnUrl'   => $callback,
            'Description' => sprintf(__('Order #%d', 'neomarket'), $order_id),
            'PayerIdentity' => $order->billing_email,
        );

        $response = $this->post($base . 'pay', $token, $payload);
        if (is_wp_error($response)) return $response;
        $code = $response['code'] ?? '';
        if (empty($code) || wp_remote_retrieve_response_code($response['raw'] ?? null) !== 200 && empty($code)) {
            return new WP_Error('payping_error', __('Unable to initiate PayPing payment'));
        }
        NeoMarket_Payments::log_transaction($order_id, $this->id, 'payment', 'pending', $code, $response, $order->total, $order->currency);
        NeoMarket_DB::update_order($order_id, array('transaction_id' => $code));

        $redirect = ($sandbox === 'yes' ? 'https://api.payping.ir/v1/test/pay/gotoipg/' : 'https://api.payping.ir/v1/pay/gotoipg/';
        return array(
            'result'   => 'success',
            'redirect' => $redirect . $code,
            'order_id' => $order_id,
        );
    }

    public function handle_callback() {
        $order_id = absint($_GET['order_id'] ?? 0);
        $order = NeoMarket_DB::get_order($order_id);
        if (!$order) wp_die('Invalid order');

        $refId = sanitize_text_field(wp_unslash($_POST['refId'] ?? $_GET['refId'] ?? ''));
        $amount = (float) $order->total;
        $token = $this->get_option('token');
        $sandbox = $this->get_option('sandbox', 'yes');
        $base = $sandbox === 'yes' ? 'https://api.payping.ir/v1/test/pay/verify/' : 'https://api.payping.ir/v1/pay/verify/';

        $resp = $this->post($base, $token, array('Amount' => $amount, 'refId' => $refId));
        $success = !is_wp_error($resp) && !empty($resp['refId']);

        if ($success) {
            NeoMarket_DB::update_order($order_id, array('transaction_id' => $refId));
            NeoMarket_DB::update_order_status($order_id, 'completed');
            NeoMarket_Cart::empty_cart();
            NeoMarket_Payments::log_transaction($order_id, $this->id, 'verify', 'success', $refId, $resp, $order->total, $order->currency);
            $redir = add_query_arg(array('neomarket_order' => 'success', 'order_id' => $order_id), home_url('/'));
        } else {
            NeoMarket_DB::update_order_status($order_id, 'failed');
            NeoMarket_Payments::log_transaction($order_id, $this->id, 'verify', 'failed', $refId, $resp, $order->total, $order->currency);
            $redir = add_query_arg(array('neomarket_order' => 'failed'), home_url('/'));
        }
        wp_redirect($redir);
        exit;
    }

    private function post($url, $token, $payload) {
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'body'    => wp_json_encode($payload),
            'timeout' => 30,
        ));
        if (is_wp_error($response)) return $response;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return (array) $body + array('raw' => $response);
    }
}
