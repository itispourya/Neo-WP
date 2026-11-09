<?php
/**
 * My Account Template - User Dashboard
 */
if (!defined('ABSPATH')) exit;
$user_id = get_current_user_id();
?>
<h2>👤 <?php _e('My Account', 'neomarket'); ?></h2>
<?php if (!$user_id) : ?>
    <div style="padding:40px;background:#fff;border-radius:8px;text-align:center;">
        <p><?php _e('Please login to view your account area.', 'neomarket'); ?></p>
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="button-primary"><?php _e('Login', 'neomarket'); ?></a>
    </div>
<?php return; endif;

$user = wp_get_current_user();
$orders = NeoMarket_DB::get_user_orders($user_id);
?>
<div style="display:grid;grid-template-columns:1fr 3fr;gap:20px;">
    <div class="nmk-account-side" style="background:#fff;padding:20px;border-radius:8px;">
        <div style="text-align:center;margin-bottom:20px;">
            <?php echo get_avatar($user_id, 80); ?>
            <h3 style="margin:10px 0 0;"><?php echo esc_html($user->display_name); ?></h3>
            <p style="color:#666;font-size:12px;margin:0;"><?php echo esc_html($user->user_email); ?></p>
        </div>
        <hr style="border-color:#f1f1f1;">
        <ul style="list-style:none;padding:0;">
            <li><button class="button widefat" data-view="wishlist" style="margin-bottom:5px;">💟 <?php _e('My Wishlist', 'neomarket'); ?></button></li>
            <li><button class="button widefat" data-view="compare" style="margin-bottom:5px;">⚖️ <?php _e('Compare', 'neomarket'); ?></button></li>
            <li><button class="button widefat" data-view="cart">🛒 <?php _e('Cart', 'neomarket'); ?></button></li>
        </ul>
    </div>
    <div class="nmk-orders-wrap" style="background:#fff;padding:20px;border-radius:8px;">
        <h3 style="margin-top:0;"><?php _e('Your Orders', 'neomarket'); ?> (<?php echo count($orders); ?>)</h3>
        <?php if (empty($orders)) : ?>
            <p style="padding:40px;text-align:center;background:#fafafa;border-radius:6px;color:#666;">
                <?php _e('You have no orders yet.', 'neomarket'); ?>
                <br><a data-view="catalog" class="button" style="margin-top:10px;"><?php _e('Start Shopping', 'neomarket'); ?></a>
            </p>
        <?php else : ?>
            <table class="nmk-my-orders-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#fafafa;">
                        <th style="padding:10px;text-align:right;">#</th>
                        <th style="padding:10px;text-align:right;"><?php _e('Total', 'neomarket'); ?></th>
                        <th style="padding:10px;text-align:right;"><?php _e('Status', 'neomarket'); ?></th>
                        <th style="padding:10px;text-align:right;"><?php _e('Date', 'neomarket'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o) :
                        $status_color = array(
                            'completed'  => '#2e7d32',
                            'processing' => '#1565c0',
                            'on-hold'    => '#f57f17',
                            'pending'    => '#616161',
                            'cancelled'  => '#c62828',
                            'failed'     => '#c62828',
                            'refunded'   => '#6a1b9a',
                        )[$o->status] ?? '#333';
                        $status_tex = array(
                            'completed'  => __('Completed', 'neomarket'),
                            'processing' => __('Processing', 'neomarket'),
                            'on-hold'    => __('On-Hold', 'neomarket'),
                            'pending'    => __('Pending', 'neomarket'),
                            'cancelled'  => __('Cancelled', 'neomarket'),
                            'failed'     => __('Failed', 'neomarket'),
                            'refunded'   => __('Refunded', 'neomarket'),
                        )[$o->status] ?? $o->status;
                        ?>
                        <tr style="border-top:1px solid #eee;">
                            <td style="padding:10px;">
                                <a href="#" onclick="NeoMarketPublic.showOrder(<?php echo $o->order_id; ?>);return false;"><strong>#<?php echo $o->order_id; ?></strong></a>
                            </td>
                            <td style="padding:10px;"><strong><?php echo number_format($o->total); ?></strong> <?php echo esc_html($o->currency); ?></td>
                            <td style="padding:10px;"><span style="padding:3px 8px;border-radius:20px;font-size:12px;color:#fff;background:<?php echo $status_color; ?>;"><?php echo $status_tex; ?></span></td>
                            <td style="padding:10px;"><?php echo date_i18n('Y/m/d H:i', strtotime($o->date_created)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
