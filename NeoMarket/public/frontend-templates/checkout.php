<?php
/**
 * Checkout Template - Multi-Gateway
 */
if (!defined('ABSPATH')) exit;

$state = NeoMarket_Cart::get_state();
$gateways = NeoMarket_Payments::get_enabled_gateways();
$user = wp_get_current_user();
?>
<h2>🔐 <?php _e('Checkout', 'neomarket'); ?></h2>
<a class="go-to-cart button" style="margin-bottom:15px;">← <?php _e('Back to Cart', 'neomarket'); ?></a>

<?php if (empty($state['items'])) : ?>
    <div class="empty-state" style="padding:80px;text-align:center;background:#fff;border-radius:8px;margin-top:20px;">
        <?php _e('No items to checkout.', 'neomarket'); ?>
    </div>
<?php else : ?>
<form id="neomarket-checkout-form" style="display:grid;grid-template-columns:1fr 360px;gap:20px;">
    <?php wp_nonce_field('neomarket_frontend_nonce', 'nonce'); ?>
    <div class="checkout-forms" style="display:flex;flex-direction:column;gap:20px;">
        <div class="billing-fields" style="background:#fff;padding:20px;border-radius:8px;">
            <h3 style="margin-top:0;"><?php _e('Billing Details', 'neomarket'); ?></h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <input required type="text" name="billing_first_name" placeholder="<?php _e('First Name', 'neomarket'); ?>" value="<?php echo esc_attr($user->first_name); ?>" class="widefat">
                <input required type="text" name="billing_last_name" placeholder="<?php _e('Last Name', 'neomarket'); ?>" value="<?php echo esc_attr($user->last_name); ?>" class="widefat">
                <input required type="email" name="billing_email" placeholder="<?php _e('Email', 'neomarket'); ?>" value="<?php echo esc_attr($user->user_email); ?>" class="widefat">
                <input required type="tel" name="billing_phone" placeholder="<?php _e('Phone', 'neomarket'); ?> 09..." class="widefat">
            </div>
            <div style="margin-top:10px;">
                <input required type="text" name="billing_address_1" placeholder="<?php _e('Full Address', 'neomarket'); ?>" class="widefat">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-top:10px;">
                <input required type="text" name="billing_city" placeholder="<?php _e('City', 'neomarket'); ?>" class="widefat">
                <input type="text" name="billing_state" placeholder="<?php _e('State', 'neomarket'); ?>" class="widefat">
                <input type="text" name="billing_postcode" placeholder="<?php _e('Postcode', 'neomarket'); ?>" class="widefat">
            </div>
            <input type="hidden" name="billing_country" value="IR">
            <div style="margin-top:10px;">
                <textarea name="order_comments" rows="3" placeholder="<?php _e('Order notes (optional)', 'neomarket'); ?>" class="widefat"></textarea>
            </div>
        </div>

        <div class="shipping-fields" style="background:#fff;padding:20px;border-radius:8px;">
            <label style="display:block;margin-bottom:10px;">
                <input type="checkbox" id="ship-diff">
                <strong><?php _e('Ship to a different address', 'neomarket'); ?></strong>
            </label>
            <div id="shipping-box" style="display:none;display-grid:grid-template-columns:1fr 1fr;gap:10px;">
                <input type="text" name="shipping_first_name" placeholder="<?php _e('First Name', 'neomarket'); ?>" class="widefat">
                <input type="text" name="shipping_last_name" placeholder="<?php _e('Last Name', 'neomarket'); ?>" class="widefat">
                <input style="grid-column:span 2;" type="text" name="shipping_address_1" placeholder="<?php _e('Full Address', 'neomarket'); ?>" class="widefat">
                <input type="text" name="shipping_city" placeholder="<?php _e('City', 'neomarket'); ?>" class="widefat">
                <input type="text" name="shipping_postcode" placeholder="<?php _e('Postcode', 'neomarket'); ?>" class="widefat">
            </div>
        </div>
    </div>

    <div class="checkout-sidebar" style="display:flex;flex-direction:column;gap:20px;">
        <div class="order-review" style="background:#fff;padding:20px;border-radius:8px;">
            <h3 style="margin-top:0;"><?php _e('Your Order', 'neomarket'); ?></h3>
            <table style="width:100%;font-size:14px;">
                <tbody>
                <?php foreach ($state['items'] as $it) : ?>
                    <tr style="border-bottom:1px dashed #eee;">
                        <td style="padding:8px 0;"><?php echo esc_html($it['title']); ?> × <?php echo $it['qty']; ?></td>
                        <td style="padding:8px 0;text-align:left;"><?php echo number_format($it['subtotal']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="border-top:2px solid #333;">
                        <td style="padding-top:10px;"><strong><?php _e('Subtotal', 'neomarket'); ?></strong></td>
                        <td style="padding-top:10px;text-align:left;"><?php echo number_format($state['totals']['subtotal']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Shipping', 'neomarket'); ?></td>
                        <td style="text-align:left;"><?php echo $state['totals']['shipping'] > 0 ? number_format($state['totals']['shipping']) : '<span style="color:#2a9d3f;font-weight:bold;">رایگان</span>'; ?></td>
                    </tr>
                    <tr>
                        <td style="font-size:18px;padding-top:8px;"><strong><?php _e('Total', 'neomarket'); ?></strong></td>
                        <td style="text-align:left;padding-top:8px;font-size:18px;font-weight:bold;color:#ef394e;"><?php echo number_format($state['totals']['total']); ?> تومان</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="payment-gateways" style="background:#fff;padding:20px;border-radius:8px;">
            <h3 style="margin-top:0;"><?php _e('Payment Method', 'neomarket'); ?></h3>
            <?php if (empty($gateways)) : ?>
                <p style="color:#c00;"><?php _e('No payment gateways enabled. Please enable from settings.', 'neomarket'); ?></p>
            <?php else :
                $first = true;
                foreach ($gateways as $gw) : ?>
                    <label style="display:block;padding:10px;border:1px solid #eee;border-radius:6px;margin-bottom:8px;cursor:pointer;<?php echo $first ? 'border-color:#ef394e;background:#fff5f6;' : ''; ?>">
                        <input type="radio" name="payment_method" value="<?php echo esc_attr($gw->id); ?>" <?php checked($first); ?>>
                        <strong><?php echo esc_html($gw->get_title()); ?></strong>
                        <div style="font-size:12px;color:#666;margin-top:4px;"><?php echo wp_kses_post($gw->get_description()); ?></div>
                    </label>
                    <?php $first = false;
                endforeach;
            endif; ?>
            <button type="submit" class="button-primary" style="width:100%;padding:14px;margin-top:15px;font-size:16px;">
                <?php _e('Pay Now & Complete Order', 'neomarket'); ?>
            </button>
        </div>
        <div id="neomarket-payment-details-host"></div>
    </div>
</form>
<script>
document.getElementById('ship-diff')?.addEventListener('change', function() {
    document.getElementById('shipping-box').style.display = this.checked ? 'block' : 'none';
});
</script>
<?php endif; ?>
