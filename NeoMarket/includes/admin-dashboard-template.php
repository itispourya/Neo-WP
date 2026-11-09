<?php
/**
 * Admin Dashboard - Matrix Themed with Chart.js reports
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue Chart.js only here
wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', array(), '4.4.1', true);
wp_localize_script('chart-js', 'neomarket_rest', array(
    'root'  => esc_url_raw(rest_url()),
    'nonce' => wp_create_nonce('wp_rest'),
));

global $wpdb;
$order_table = $wpdb->prefix . 'neomarket_orders';
$total_products = wp_count_posts('neo_product')->publish;
$total_orders = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$order_table}");
$total_revenue = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total),0) FROM {$order_table} WHERE status = %s", 'completed'));
$pending_review = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$order_table} WHERE status IN ('pending','on-hold')"));

$top_selling = $wpdb->get_results(
    "SELECT p.post_title as title, COALESCE(SUM(oi.quantity),0) as units_sold
     FROM {$wpdb->prefix}neomarket_order_items oi
     LEFT JOIN {$wpdb->posts} p ON p.ID = oi.product_id
     GROUP BY oi.product_id ORDER BY units_sold DESC LIMIT 5", ARRAY_A);

$recent = $wpdb->get_results(
    "SELECT order_id, total, status, billing_first_name, billing_last_name, date_created
     FROM {$order_table} ORDER BY date_created DESC LIMIT 8", ARRAY_A);
?>
<div class="neomarket-admin-wrap wrap">
    <h1 style="color:#39ff14;letter-spacing:1px;">
        ╔══════════════════════════════════════════════════════════════╗<br>
        ║&nbsp;&nbsp;NEO_MARKET // CONTROL PANEL v<?php echo NEOMARKET_VERSION; ?> - ADMIN: <?php echo esc_html(wp_get_current_user()->display_name); ?><br>
        ╚══════════════════════════════════════════════════════════════╝
    </h1>
    <div id="neomarket-cli" style="background:#000;border:1px solid #30363d;padding:15px;border-radius:6px;margin-bottom:20px;font-family:Courier New;font-size:13px;color:#39ff14;line-height:1.7;">
        <div>$ neomarket system --status <span style="color:#fff;">[OK]</span></div>
        <div>$ db tables: neomarket_orders, neomarket_order_items, neomarket_cart, neomarket_transactions <span style="color:#39ff14;">[connected]</span></div>
        <div>$ payment gateways: zarinpal, payping, mellat, cardtocard, crypto <span style="color:#FFD60A;">[<?php echo count(NeoMarket_Payments::get_enabled_gateways()); ?> active]</span></div>
        <div>$ user_ip = <span style="color:#FFD60A;"><?php echo esc_html(NeoMarket_DB::get_ip_address()); ?></span>; timestamp = <span style="color:#FFD60A;"><?php echo date('Y-m-d H:i:s'); ?></span></div>
        <div style="color:#8b949e;">Tip: در تنظیمات درگاه‌های پرداخت فعال را وارد کنید تا مشتریان بتوانند از آن‌ها استفاده نمایند.</div>
    </div>

    <!-- Stats Row -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:15px;margin-bottom:20px;">
        <?php
        $stats = array(
            array('📦', 'محصولات ثبت شده', number_format($total_products), 'linear-gradient(135deg,#39ff14 0%,#1a6f2b 100%)'),
            array('🧾', 'کل سفارشات', number_format($total_orders), 'linear-gradient(135deg,#79c0ff 0%,#1f6feb 100%)'),
            array('💰', 'درآمد تایید شده', number_format($total_revenue) . ' تومان', 'linear-gradient(135deg,#ffd60a 0%,#8a6d00 100%)'),
            array('⚠️', 'سفارشات در انتظار', number_format($pending_review), 'linear-gradient(135deg,#ff7b72 0%,#a40e26 100%)'),
        );
        foreach ($stats as $s) :
        ?>
        <div style="background:<?php echo $s[3]; ?>;border-radius:8px;padding:20px;color:#fff;box-shadow:0 2px 10px rgba(0,0,0,0.3);">
            <div style="font-size:34px;line-height:1;"><?php echo $s[0]; ?></div>
            <div style="opacity:0.85;font-size:13px;margin:8px 0;"><?php echo esc_html($s[1]); ?></div>
            <div style="font-size:24px;font-weight:bold;"><?php echo $s[2]; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:25px;">
        <div style="background:#161b22;border:1px solid #30363d;border-radius:8px;padding:20px;">
            <h3 style="color:#39ff14;margin-top:0;">📈 <?php _e('Monthly Sales', 'neomarket'); ?></h3>
            <canvas id="nmk-monthly-chart" height="180"></canvas>
        </div>
        <div style="background:#161b22;border:1px solid #30363d;border-radius:8px;padding:20px;">
            <h3 style="color:#39ff14;margin-top:0;">🏆 <?php _e('Top Products', 'neomarket'); ?></h3>
            <canvas id="nmk-top-products" height="180"></canvas>
        </div>
    </div>

    <!-- Recent Orders -->
    <div style="background:#161b22;border:1px solid #30363d;border-radius:8px;padding:20px;">
        <h3 style="color:#39ff14;margin-top:0;">🕓 <?php _e('Recent Orders', 'neomarket'); ?></h3>
        <?php if (empty($recent)) : ?>
            <p style="color:#8b949e;"><?php _e('No orders yet. A customer purchase will appear here!', 'neomarket'); ?></p>
        <?php else : ?>
            <table class="widefat" style="background:#0d1117;color:#c9d1d9;border-color:#30363d;">
                <thead style="background:#0d1117;">
                    <tr style="color:#39ff14;">
                        <th><?php _e('#', 'neomarket'); ?></th>
                        <th><?php _e('Customer', 'neomarket'); ?></th>
                        <th><?php _e('Total', 'neomarket'); ?></th>
                        <th><?php _e('Status', 'neomarket'); ?></th>
                        <th><?php _e('Date', 'neomarket'); ?></th>
                        <th><?php _e('Actions', 'neomarket'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $o) :
                    $scolors = array('completed'=>'#2ea043','processing'=>'#1f6feb','on-hold'=>'#d29922','pending'=>'#6e7681','cancelled'=>'#f85149','failed'=>'#f85149','refunded'=>'a371f7');
                    $stexts = array('completed'=>'تکمیل شده','processing'=>'در پردازش','on-hold'=>'در انتظار تایید','pending'=>'در انتظار پرداخت','cancelled'=>'لغو شده','failed'=>'ناموفق','refunded'=>'بازپرداخت');
                    $order_link = add_query_arg(array('post_type'=>'neo_product','page'=>'neomarket-order-view','order_id'=>$o['order_id']), admin_url('edit.php'));
                    ?>
                    <tr style="border-top:1px solid #30363d;">
                        <td><strong>#<?php echo $o['order_id']; ?></strong></td>
                        <td><?php echo esc_html($o['billing_first_name'] . ' ' . $o['billing_last_name']); ?></td>
                        <td><?php echo number_format($o['total']); ?> تومان</td>
                        <td><span style="padding:2px 8px;border-radius:999px;font-size:11px;color:#fff;background:<?php echo $scolors[$o['status']] ?? '#666'; ?>;"><?php echo $stexts[$o['status']] ?? $o['status']; ?></span></td>
                        <td><?php echo esc_html($o['date_created']); ?></td>
                        <td><a class="button button-small" href="<?php echo admin_url('admin.php?page=neomarket&order_view=' . $o['order_id']); ?>">جزئیات</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Low Stock -->
    <?php
    $low = new WP_Query(array('post_type'=>'neo_product','posts_per_page'=>6,'meta_query'=>array(
        'relation'=>'AND',
        array('key'=>'_manage_stock','value'=>'yes'),
        array('key'=>'_stock_quantity','value'=>intval(get_option('neomarket_low_stock_threshold', 5)),'type'=>'NUMERIC','compare'=>'<=')
    )));
    if ($low->have_posts()) : ?>
    <div style="background:#161b22;border:1px solid #f85149;border-radius:8px;padding:20px;margin-top:20px;">
        <h3 style="color:#f85149;margin-top:0;">⚠️ <?php _e('Low Stock Alert', 'neomarket'); ?></h3>
        <ul style="list-style:inside;disc;color:#c9d1d9;">
            <?php while ($low->have_posts()) : $low->the_post();
                $q = get_post_meta(get_the_ID(),'_stock_quantity',true); ?>
                <li><strong><?php the_title(); ?></strong> — <?php echo intval($q); ?> عدد باقی مانده — <a href="<?php echo get_edit_post_link(); ?>" style="color:#39ff14;">ویرایش</a></li>
            <?php endwhile; wp_reset_postdata(); ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch(neomarket_rest.root + 'neomarket/v1/admin/stats', {headers: {'X-WP-Nonce': neomarket_rest.nonce}})
        .then(r => r.json())
        .then(function(stats) {
            const labels = stats.monthly_sales.map(m => m.month).reverse();
            const data = stats.monthly_sales.map(m => Number(m.revenue)).reverse();
            new Chart(document.getElementById('nmk-monthly-chart'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '<?php _e('Revenue (Toman)', 'neomarket'); ?>',
                        data: data,
                        borderColor: '#39ff14',
                        backgroundColor: 'rgba(57, 255, 20, 0.1)',
                        fill: true,
                        tension: 0.4,
                    }]
                },
                options: { plugins: { legend: { labels: { color: '#c9d1d9' } } },
                    scales: { x: { ticks: { color: '#8b949e' } }, y: { ticks: { color: '#8b949e' } } }
                }
            });
            new Chart(document.getElementById('nmk-top-products'), {
                type: 'bar',
                data: {
                    labels: stats.top_products.map(p => p.title),
                    datasets: [{ label:'Units Sold', data: stats.top_products.map(p => Number(p.units_sold)), backgroundColor: '#1f6feb' }]
                },
                options: { indexAxis: 'y',
                    plugins: { legend: { labels: { color: '#c9d1d9' } } },
                    scales: { x: { ticks: { color: '#8b949e' } }, y: { ticks: { color: '#8b949e' } } }
                }
            });
        });
});
</script>
