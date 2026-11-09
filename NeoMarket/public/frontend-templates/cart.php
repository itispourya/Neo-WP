<?php
/**
 * Cart Template
 */
if (!defined('ABSPATH')) exit;

$state = NeoMarket_Cart::get_state();
?>
<h2>🛒 <?php _e('Your Cart', 'neomarket'); ?></h2>
<a class="back-to-catalog button">← <?php _e('Continue Shopping', 'neomarket'); ?></a>

<?php if (empty($state['items'])) : ?>
    <div class="empty-state" style="padding:80px;text-align:center;background:#fff;border-radius:8px;margin-top:20px;">
        <h3><?php _e('Your cart is empty!', 'neomarket'); ?></h3>
        <p><?php _e('Browse our products and add your favorites to the cart.', 'neomarket'); ?></p>
    </div>
<?php else : ?>
    <div class="cart-layout" style="display:grid;grid-template-columns:1fr 320px;gap:20px;margin-top:20px;">
        <div class="cart-items-wrap">
            <table class="cart-table" style="width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;">
                <thead style="background:#f8f9fa;">
                    <tr>
                        <th style="padding:12px;text-align:right;"><?php _e('Product', 'neomarket'); ?></th>
                        <th style="padding:12px;"><?php _e('Price', 'neomarket'); ?></th>
                        <th style="padding:12px;"><?php _e('Qty', 'neomarket'); ?></th>
                        <th style="padding:12px;"><?php _e('Subtotal', 'neomarket'); ?></th>
                        <th style="padding:12px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($state['items'] as $it) : ?>
                        <tr class="cart-item" data-key="<?php echo esc_attr($it['key']); ?>" style="border-top:1px solid #eee;">
                            <td style="padding:12px;">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <?php if ($it['thumbnail']) : ?>
                                        <img src="<?php echo esc_url($it['thumbnail']); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight:bold;"><?php echo esc_html($it['title']); ?></div>
                                        <div style="font-size:12px;color:#666;">SKU: <?php echo esc_html(get_post_meta($it['product_id'], '_sku', true)); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:12px;"><?php echo number_format($it['price_each']); ?></td>
                            <td style="padding:12px;">
                                <div style="display:flex;align-items:center;gap:5px;justify-content:center;">
                                    <button class="nmk-qty-btn button" data-step="-1">−</button>
                                    <input class="nmk-qty-input" type="number" min="0" value="<?php echo $it['qty']; ?>" style="width:60px;text-align:center;">
                                    <button class="nmk-qty-btn button" data-step="1">＋</button>
                                </div>
                            </td>
                            <td style="padding:12px;"><strong><?php echo number_format($it['subtotal']); ?> تومان</strong></td>
                            <td style="padding:12px;">
                                <button class="nmk-remove-item button-link" style="color:#c00;"><?php _e('Remove', 'neomarket'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="cart-totals-card" style="background:#fff;border-radius:8px;padding:20px;height:fit-content;">
            <h3 style="margin-top:0;"><?php _e('Order Summary', 'neomarket'); ?></h3>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed #ddd;">
                <span><?php _e('Subtotal', 'neomarket'); ?></span>
                <span><?php echo number_format($state['totals']['subtotal']); ?> تومان</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed #ddd;">
                <span><?php _e('Shipping', 'neomarket'); ?></span>
                <span><?php echo $state['totals']['shipping'] > 0 ? number_format($state['totals']['shipping']) . ' تومان' : '<b style="color:#2a9d3f;">' . __('Free', 'neomarket') . '</b>'; ?></span>
            </div>
            <?php if ($state['totals']['discount'] > 0) : ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed #ddd;color:#c00;">
                    <span><?php _e('Discount', 'neomarket'); ?></span>
                    <span>-<?php echo number_format($state['totals']['discount']); ?></span>
                </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;padding:12px 0;font-size:18px;font-weight:bold;border-top:2px solid #333;">
                <span><?php _e('Total', 'neomarket'); ?></span>
                <span style="color:#ef394e;"><?php echo number_format($state['totals']['total']); ?> تومان</span>
            </div>
            <button class="go-to-checkout button-primary" style="width:100%;padding:14px;margin-top:10px;"><?php _e('Proceed to Checkout', 'neomarket'); ?> →</button>
        </div>
    </div>
<?php endif; ?>
