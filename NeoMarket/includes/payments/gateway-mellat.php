<?php
/**
 * Mellat Gateway (بانک ملت - Behpardakht)
 */

if (!defined('ABSPATH')) {
    exit;
}

class NeoMarket_Gateway_Mellat extends NeoMarket_Gateway {

    public $id = 'mellat';
    public $title = 'پرداخت آنلاین بانک ملت (به‌پرداخت)';
    public $description = 'پرداخت امن با کارت‌های عضو شتاب';

    public function get_settings_fields() {
        return parent::get_settings_fields() + array(
            'terminal_id' => array('label' => 'Terminal ID', 'type' => 'text'),
            'username'    => array('label' => 'Username',   'type' => 'text'),
            'password'    => array('label' => 'Password',   'type' => 'password'),
            'sandbox'     => array('label' => 'Sandbox Mode', 'type' => 'checkbox', 'default' => 'yes'),
        );
    }

    private function get_endpoint() {
        // NOTE: Mellat requires a NuSoap / SoapClient setup. We use a REST proxy approach here.
        // For a real implementation, we'd use PHP SoapClient against https://bpm.shaparak.ir/pgwchannel/services/pgw
        return ($this->get_option('sandbox') === 'yes')
            ? 'https://sandbox.bankmellat.ir/pgwchannel/'
            : 'https://bpm.shaparak.ir/pgwchannel/';
    }

    public function process_payment($order_id) {
        $order = NeoMarket_DB::get_order($order_id);
        $amount = intval(round($order->total * 10)); // Mellat uses Rials (Toman * 10)

        $callback = add_query_arg(array('neomarket_gateway' => 'mellat', 'order_id' => $order_id), home_url('/'));
        $ref_id = time() . $order_id;

        // In a real implementation, we would call bpPayRequest via SOAP
        // Here we log an initial transaction and redirect (Simulated)
        NeoMarket_Payments::log_transaction($order_id, $this->id, 'payment', 'pending', $ref_id, array('mellat_mode' => $this->get_option('sandbox'), 'note' => 'Mock implementation — replace with SoapClient for production'), $order->total, $order->currency);
        NeoMarket_DB::update_order($order_id, array('transaction_id' => $ref_id));

        // For the purpose of this scaffold, we redirect to a payment page stub
        // In production, use: $client->bpPayRequest(...) → extract RefId → redirect to /startPay.do?RefId=...
        return array(
            'result'   => 'success',
            'redirect' => add_query_arg(array('nmk_mellat_ref' => $ref_id, 'Amount' => $amount, 'RedirectURL' => urlencode($callback)), home_url('/')),
            'order_id' => $order_id,
            'note'     => __('Redirect link generated for Mellat. In production use SoapClient.', 'neomarket'),
        );
    }

    public function handle_callback() {
        $order_id = absint($_GET['order_id'] ?? 0);
        $order = NeoMarket_DB::get_order($order_id);
        if (!$order) wp_die('Invalid order');

        $ref_id = sanitize_text_field(wp_unslash($_POST['RefId'] ?? $_GET['RefId'] ?? ''));
        $sale_order_id = sanitize_text_field(wp_unslash($_POST['SaleOrderId'] ?? ''));
        $res_code = sanitize_text_field(wp_unslash($_POST['ResCode'] ?? ''));

        if ($res_code === '0') {
            // Simulate verify + settle
            NeoMarket_DB::update_order($order_id, array('transaction_id' => $ref_id));
            NeoMarket_DB::update_order_status($order_id, 'completed');
            NeoMarket_Cart::empty_cart();
            NeoMarket_Payments::log_transaction($order_id, $this->id, 'verify', 'success', $ref_id, $_POST, $order->total, $order->currency);
            $redir = add_query_arg(array('neomarket_order' => 'success', 'order_id' => $order_id), home_url('/'));
        } else {
            NeoMarket_DB::update_order_status($order_id, 'failed');
            NeoMarket_Payments::log_transaction($order_id, $this->id, 'verify', 'failed', $ref_id, $_POST, $order->total, $order->currency);
            $redir = add_query_arg(array('neomarket_order' => 'failed', 'res_code' => $res_code), home_url('/'));
        }
        wp_redirect($redir);
        exit;
    }
}
