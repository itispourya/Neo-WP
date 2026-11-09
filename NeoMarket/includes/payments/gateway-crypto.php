<?php
/**
 * Crypto Payment Gateway (رمز ارز — TRC20/ERC20)
 * Manual admin verification (or hook for third-party webhook).
 */

if (!defined('ABSPATH')) {
    exit;
}

class NeoMarket_Gateway_Crypto extends NeoMarket_Gateway {

    public $id = 'crypto';
    public $title = 'پرداخت با رمز ارز (USDT - TRC20/ERC20)';
    public $description = 'مبلغ دلاری معادل بر اساس نرخ بازار به کیف‌پول زیر واریز کنید و شماره تراکنش (Tx Hash) را وارد کنید.';

    public function get_settings_fields() {
        return parent::get_settings_fields() + array(
            'usdt_trc20' => array(
                'label'   => 'USDT Wallet (TRC20)',
                'type'    => 'text',
                'default' => 'TXxxxxxxxxxxxxxxxxxxxxxxxxxx',
            ),
            'usdt_erc20' => array(
                'label'   => 'USDT Wallet (ERC20)',
                'type'    => 'text',
                'default' => '0x0000000000000000000000000000000000000000',
            ),
            'other_wallets' => array(
                'label'   => __('Other addresses (one per line: COIN:ADDRESS)', 'neomarket'),
                'type'    => 'textarea',
                'default' => "BTC:bc1qxxxxxx\nETH:0x0000000000000000000000000000000000000000",
            ),
            'usd_rate'  => array(
                'label'   => __('USD/IRT Conversion rate (for total calculation)', 'neomarket'),
                'type'    => 'number',
                'step'    => '0.01',
                'default' => 60000,
            ),
            'confirmations' => array(
                'label'   => __('Block confirmations required for auto-approve', 'neomarket'),
                'type'    => 'number',
                'default' => 6,
            ),
            'verify_by' => array(
                'label'   => __('Auto-complete after TX submit?', 'neomarket'),
                'type'    => 'select',
                'options' => array('on-hold' => __('Manual Verify (Recommended)', 'neomarket'), 'processing' => __('Set Processing', 'neomarket')),
                'default' => 'on-hold',
            ),
        );
    }

    public function process_payment($order_id) {
        $order = NeoMarket_DB::get_order($order_id);

        if (empty($_POST['crypto_tx_hash']) && empty($_GET['crypto_tx_hash'])) {
            return array(
                'result'   => 'need_details',
                'html'     => $this->build_crypto_form($order),
                'order_id' => $order_id,
            );
        }

        $hash = sanitize_text_field(wp_unslash($_POST['crypto_tx_hash'] ?? $_GET['crypto_tx_hash'] ?? ''));
        $currency = sanitize_text_field(wp_unslash($_POST['crypto_currency'] ?? 'USDT'));
        $amount = floatval($_POST['crypto_amount'] ?? 0);

        NeoMarket_DB::update_order($order_id, array(
            'crypto_tx_hash'  => $hash,
            'crypto_currency' => $currency,
            'crypto_amount'   => $amount,
            'payment_method'  => 'crypto',
            'payment_method_title' => $this->title,
            'transaction_id'  => $hash,
        ));

        $status = $this->get_option('verify_by', 'on-hold');
        NeoMarket_DB::update_order_status($order_id, $status);

        NeoMarket_Payments::log_transaction($order_id, $this->id, 'payment', $status, $hash, array(
            'tx_hash'  => $hash,
            'currency' => $currency,
            'amount'   => $amount,
            'note'     => __('Pending admin verification', 'neomarket'),
        ), $amount, $currency);

        // Email admin
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('Crypto payment submitted for order #%d', 'neomarket'), $order_id);
        $message = sprintf(__("Tx: %s\nAmount: %s %s\nApprove: %s", 'neomarket'), $hash, $amount, $currency, add_query_arg(array('post' => $order_id, 'action' => 'edit'), admin_url('post.php')));
        @wp_mail($admin_email, $subject, $message);

        // Optional: Schedule auto-check cron hook
        if (!wp_next_scheduled('neomarket_crypto_verify_' . $order_id)) {
            wp_schedule_single_event(time() + 3600, 'neomarket_crypto_verify_' . $order_id, array($order_id, $hash));
        }

        NeoMarket_Cart::empty_cart();
        $thank_you = NeoMarket_Payments::get_thankyou_url($order_id);
        return array(
            'result'   => 'success',
            'redirect' => add_query_arg(array('neomarket_order' => 'crypto_pending', 'order_id' => $order_id), $thank_you),
            'order_id' => $order_id,
        );
    }

    public function build_crypto_form($order) {
        $rate = floatval($this->get_option('usd_rate', 60000));
        $usdt_total = round($order->total / max(1, $rate), 6);

        $wallets = array(
            'USDT (TRC20)' => $this->get_option('usdt_trc20'),
            'USDT (ERC20)' => $this->get_option('usdt_erc20'),
        );
        foreach (preg_split('/\r\n|\r|\n/', $this->get_option('other_wallets')) as $line) {
            if (strpos($line, ':') !== false) {
                list($coin, $addr) = explode(':', $line, 2);
                $wallets[trim($coin)] = trim($addr);
            }
        }

        ob_start();
        ?>
        <div class="neomarket-crypto-form">
            <h4><?php esc_html_e('Crypto Payment Instructions', 'neomarket'); ?></h4>
            <div style="background:#f0fff4;padding:15px;border-radius:8px;border:1px dashed #38a169;margin-bottom:20px;">
                <p><strong><?php esc_html_e('Amount due:', 'neomarket'); ?></strong> <?php echo esc_html($usdt_total); ?> USDT ≈ <?php echo number_format($order->total); ?> <?php echo esc_html($order->currency); ?></p>
                <p style="font-size:12px;color:#555;"><?php printf(esc_html__('Rate: 1 USD = %s %s (adjusted by admin)', 'neomarket'), number_format($rate), esc_html($order->currency)); ?></p>
            </div>
            <h5><?php esc_html_e('Send to any of these wallets:', 'neomarket'); ?></h5>
            <ul style="list-style:none;padding:0;">
                <?php foreach ($wallets as $label => $addr) : if (trim($addr)) : ?>
                    <li style="background:#fff;padding:12px;border-radius:6px;border:1px solid #eee;margin-bottom:10px;">
                        <strong><?php echo esc_html($label); ?>:</strong><br>
                        <code dir="ltr" style="display:block;margin-top:6px;word-break:break-all;"><?php echo esc_html($addr); ?></code>
                    </li>
                <?php endif; endforeach; ?>
            </ul>

            <form id="neomarket-crypto-confirm" onsubmit="event.preventDefault(); NeoMarketPublic.confirmCrypto(jQuery(this).serialize());">
                <input type="hidden" name="action" value="neomarket_ajax_crypto_submit">
                <input type="hidden" name="order_id" value="<?php echo $order->order_id; ?>">
                <?php wp_nonce_field('neomarket_crypto_nonce', 'neomarket_crypto_nonce'); ?>
                <div>
                    <label><?php esc_html_e('Crypto Currency', 'neomarket'); ?></label>
                    <select name="crypto_currency" style="width:100%;padding:8px;">
                        <option>USDT</option><option>USDC</option><option>BTC</option><option>ETH</option><option>TRX</option>
                    </select>
                </div>
                <div style="margin-top:10px;">
                    <label><?php printf(esc_html__('Amount sent (%s)', 'neomarket'), 'USD'); ?></label>
                    <input type="number" step="0.000001" name="crypto_amount" value="<?php echo $usdt_total; ?>" required style="width:100%;padding:8px;">
                </div>
                <div style="margin-top:10px;">
                    <label><?php esc_html_e('Transaction Hash (TX ID):', 'neomarket'); ?></label>
                    <input type="text" name="crypto_tx_hash" placeholder="0x... or T..." dir="ltr" required style="width:100%;padding:8px;">
                </div>
                <button type="submit" class="button-primary" style="margin-top:15px;"><?php esc_html_e('Submit Payment & Complete Order', 'neomarket'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_callback() {
        // Hook for future webhook implementations (BlockCypher / TronGrid)
        // Verify tx, update order if confirmed
        $order_id = absint($_GET['order_id'] ?? 0);
        $tx = sanitize_text_field(wp_unslash($_GET['tx'] ?? ''));
        if (!$order_id || !$tx) wp_die('Invalid callback');
        $order = NeoMarket_DB::get_order($order_id);
        if ($order && $order->crypto_tx_hash === $tx) {
            NeoMarket_DB::update_order_status($order_id, 'completed');
            wp_send_json_success(array('status' => 'auto-approved via webhook'));
        }
        wp_die('Mismatch');
    }
}

add_action('wp_ajax_neomarket_ajax_crypto_submit', 'neomarket_crypto_submit_ajax');
add_action('wp_ajax_nopriv_neomarket_ajax_crypto_submit', 'neomarket_crypto_submit_ajax');
function neomarket_crypto_submit_ajax() {
    check_ajax_referer('neomarket_crypto_nonce', 'neomarket_crypto_nonce');
    $_POST['payment_method'] = 'crypto';
    $gw = NeoMarket_Payments::get_gateway('crypto');
    $result = $gw->process_payment(absint($_POST['order_id']));
    if (is_wp_error($result)) wp_send_json_error($result);
    wp_send_json_success($result);
}
