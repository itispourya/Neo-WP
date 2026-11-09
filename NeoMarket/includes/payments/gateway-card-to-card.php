<?php
/**
 * Card-to-Card Payment Gateway (پرداخت کارت به کارت)
 * Manual admin verification after user submits tracking number.
 */

if (!defined('ABSPATH')) {
    exit;
}

class NeoMarket_Gateway_CardToCard extends NeoMarket_Gateway {

    public $id = 'cardtocard';
    public $title = 'پرداخت کارت به کارت';
    public $description = 'مبلغ را به شماره کارت زیر واریز کنید و شماره پیگیری را در فرم ثبت سفارش وارد نمایید. پس از تأیید ادمین سفارش شما تکمیل می‌شود.';

    public function get_settings_fields() {
        return parent::get_settings_fields() + array(
            'card_numbers'  => array(
                'label'   => __('Destination Card Numbers (one per line)', 'neomarket'),
                'type'    => 'textarea',
                'default' => "6037-9918-XXXX-XXXX\n6104-3378-XXXX-XXXX",
            ),
            'account_owner' => array(
                'label'   => __('Account Owner Name', 'neomarket'),
                'type'    => 'text',
                'default' => __('Pourya Omidian', 'neomarket'),
            ),
            'verify_by' => array(
                'label'   => __('Order status after submission', 'neomarket'),
                'type'    => 'select',
                'options' => array('on-hold' => __('On Hold (Recommended)', 'neomarket'), 'processing' => __('Processing', 'neomarket')),
                'default' => 'on-hold',
            ),
        );
    }

    public function process_payment($order_id) {
        $order = NeoMarket_DB::get_order($order_id);

        if (empty($_POST['card_payment_ref']) && empty($_GET['card_payment_ref'])) {
            // Just return form HTML for the client to collect the tracking number
            return array(
                'result'   => 'need_details',
                'html'     => $this->build_details_form($order_id),
                'order_id' => $order_id,
            );
        }

        $ref = sanitize_text_field(wp_unslash($_POST['card_payment_ref'] ?? $_GET['card_payment_ref'] ?? ''));
        $receipt = esc_url_raw(wp_unslash($_POST['card_payment_receipt'] ?? ''));

        NeoMarket_DB::update_order($order_id, array(
            'card_payment_ref'         => $ref,
            'card_payment_receipt_url' => $receipt,
            'payment_method'           => 'cardtocard',
            'payment_method_title'     => $this->title,
            'transaction_id'           => 'CTC-' . $order_id . '-' . time(),
        ));

        $status = $this->get_option('verify_by', 'on-hold');
        NeoMarket_DB::update_order_status($order_id, $status);

        NeoMarket_Payments::log_transaction($order_id, $this->id, 'payment', $status, $ref, array(
            'ref' => $ref, 'receipt' => $receipt, 'notice' => __('Pending admin verification', 'neomarket')
        ), $order->total, $order->currency);

        // Send email notification to admin
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('Card-to-card payment submitted for order #%d', 'neomarket'), $order_id);
        $message = sprintf(__("Ref: %s\nAdmin verify: %s\n", 'neomarket'), $ref, add_query_arg(array('post' => $order_id, 'action' => 'edit'), admin_url('post.php')));
        @wp_mail($admin_email, $subject, $message);

        // Empty the cart
        NeoMarket_Cart::empty_cart();

        $thank_you = NeoMarket_Payments::get_thankyou_url($order_id);
        return array(
            'result'   => 'success',
            'redirect' => add_query_arg(array('neomarket_order' => 'onhold', 'order_id' => $order_id), $thank_you),
            'order_id' => $order_id,
        );
    }

    public function build_details_form($order_id) {
        $cards = preg_split('/\r\n|\r|\n/', $this->get_option('card_numbers'));
        $owner = $this->get_option('account_owner');
        ob_start();
        ?>
        <div class="neomarket-cardtocard-form">
            <h4><?php esc_html_e('Card-to-card payment details', 'neomarket'); ?></h4>
            <div class="card-list" style="background:#fff8dc;padding:15px;border-radius:8px;margin-bottom:20px;border:1px dashed #e0a800;">
                <strong><?php esc_html_e('Destination owner:', 'neomarket'); ?> <?php echo esc_html($owner); ?></strong>
                <ul style="margin-top:10px;list-style:disc inside;">
                    <?php foreach ($cards as $c) : if (trim($c)) : ?>
                        <li dir="ltr"><strong><?php echo esc_html($c); ?></strong></li>
                    <?php endif; endforeach; ?>
                </ul>
            </div>
            <form id="neomarket-ctc-confirm" onsubmit="event.preventDefault(); NeoMarketPublic.confirmCardToCard(jQuery(this).serialize());">
                <input type="hidden" name="action" value="neomarket_ajax_ctc_submit">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <?php wp_nonce_field('neomarket_ctc_nonce', 'neomarket_ctc_nonce'); ?>
                <div>
                    <label><?php esc_html_e('Tracking / Reference number:', 'neomarket'); ?></label>
                    <input type="text" name="card_payment_ref" required style="width:100%;padding:8px;">
                </div>
                <div style="margin-top:10px;">
                    <label><?php esc_html_e('Receipt image URL (optional):', 'neomarket'); ?></label>
                    <input type="url" name="card_payment_receipt" placeholder="https://..." style="width:100%;padding:8px;">
                </div>
                <button type="submit" class="button-primary" style="margin-top:15px;"><?php esc_html_e('Submit & Complete Order', 'neomarket'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_callback() {
        // No callback used; handled via AJAX form
        wp_die(esc_html__('No callback handler for card-to-card gateway.', 'neomarket'));
    }
}

// AJAX handler for confirming CTC order submission
add_action('wp_ajax_neomarket_ajax_ctc_submit', 'neomarket_ctc_submit_ajax');
add_action('wp_ajax_nopriv_neomarket_ajax_ctc_submit', 'neomarket_ctc_submit_ajax');
function neomarket_ctc_submit_ajax() {
    check_ajax_referer('neomarket_ctc_nonce', 'neomarket_ctc_nonce');
    $_POST['payment_method'] = 'cardtocard';
    $gateway = NeoMarket_Payments::get_gateway('cardtocard');
    $order_id = absint($_POST['order_id']);
    $result = $gateway->process_payment($order_id);
    if (is_wp_error($result)) wp_send_json_error($result);
    wp_send_json_success($result);
}
