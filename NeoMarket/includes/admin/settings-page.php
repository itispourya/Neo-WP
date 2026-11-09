<?php
/**
 * Admin Settings Page for NeoMarket
 * Tabs: General / Payments / Shipping / Emails
 */

if (!defined('ABSPATH')) {
    exit;
}

$tabs = array(
    'general'    => __('General', 'neomarket'),
    'payments'   => __('Payments', 'neomarket'),
    'shipping'   => __('Shipping', 'neomarket'),
    'emails'     => __('Emails', 'neomarket'),
    'tools'      => __('Tools', 'neomarket'),
);
$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
?>
<div class="neomarket-admin-wrap wrap">
    <h1><?php esc_html_e('NeoMarket Settings', 'neomarket'); ?> <span style="font-size:12px;color:#8b949e;">v<?php echo NEOMARKET_VERSION; ?></span></h1>

    <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
        <?php foreach ($tabs as $k => $label) : ?>
            <a href="<?php echo admin_url('admin.php?page=neomarket-settings&tab=' . $k); ?>" class="nav-tab <?php echo $active_tab === $k ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($label); ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($active_tab === 'general') : ?>
        <form method="post" action="options.php" style="max-width:800px;">
            <?php
            settings_fields('neomarket-general');
            do_settings_sections('neomarket-general');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th><?php esc_html_e('Currency', 'neomarket'); ?></th>
                    <td>
                        <select name="neomarket_currency">
                            <?php foreach (array('IRT'=>'تومان ایران', 'IRR'=>'ریال ایران', 'USD'=>'دلار آمریکا', 'EUR'=>'یورو', 'GBP'=>'پوند', 'USDT'=>'تتر (USDT)') as $k=>$v): ?>
                                <option value="<?php echo $k; ?>" <?php selected(get_option('neomarket_currency','IRT'), $k); ?>><?php echo esc_html($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th><?php esc_html_e('Shop Page ID', 'neomarket'); ?></th>
                    <td><?php wp_dropdown_pages(array('name'=>'neomarket_shop_page_id', 'selected'=>get_option('neomarket_shop_page_id',''), 'show_option_none'=>__('Select page...', 'neomarket'))); ?>
                        <p class="description"><?php _e('The page containing the [neomarket] shortcode.', 'neomarket'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th><?php esc_html_e('Low Stock Threshold', 'neomarket'); ?></th>
                    <td><input type="number" name="neomarket_low_stock_threshold" value="<?php echo absint(get_option('neomarket_low_stock_threshold', 5)); ?>" class="small-text"></td>
                </tr>
                <tr valign="top">
                    <th><?php esc_html_e('Store Address', 'neomarket'); ?></th>
                    <td><textarea name="neomarket_store_address" rows="3" class="large-text"><?php echo esc_textarea(get_option('neomarket_store_address')); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

    <?php elseif ($active_tab === 'payments') : ?>
        <form method="post" action="options.php" style="max-width:1000px;">
            <?php settings_fields('neomarket-payments'); ?>
            <p><?php _e('Click on a gateway to expand its settings:', 'neomarket'); ?></p>
            <?php
            $gateways = NeoMarket_Payments::get_all_gateways();
            foreach ($gateways as $gw_id => $gw) :
                $opts = get_option('neomarket_gateway_' . $gw_id, array());
                $fields = $gw->get_settings_fields();
                ?>
                <details style="border:1px solid #30363d;border-radius:8px;padding:15px;margin-bottom:12px;background:#161b22;color:#c9d1d9;" open>
                    <summary style="cursor:pointer;font-weight:bold;font-size:16px;">
                        <?php
                            $enabled = isset($opts['enabled']) && $opts['enabled'] === 'yes';
                            echo '<span style="color:#39ff14;">●</span> ';
                            echo esc_html($gw->title);
                            echo ' <small style="color:#8b949e;">(' . esc_html($gw_id) . ')</small>';
                            echo $enabled ? ' <span style="color:#39ff14;margin-left:10px;">✔ ' . __('Active', 'neomarket') . '</span>' : '';
                        ?>
                    </summary>
                    <div style="margin-top:15px;display:grid;grid-template-columns:280px 1fr;gap:10px;align-items:center;">
                        <?php foreach ($fields as $key => $f) :
                            $val = isset($opts[$key]) ? $opts[$key] : ($f['default'] ?? '');
                            $name = 'neomarket_gateway_' . $gw_id . '[' . $key . ']';
                            echo '<label style="color:#c9d1d9;">' . esc_html($f['label']) . '</label>';
                            echo '<div>';
                            switch ($f['type']) {
                                case 'checkbox':
                                    echo '<label><input type="checkbox" name="' . $name . '" value="yes" ' . checked($val, 'yes', false) . '> ' . __('Yes', 'neomarket') . '</label>';
                                    break;
                                case 'select':
                                    echo '<select name="' . $name . '" style="width:100%;background:#0d1117;border:1px solid #30363d;color:#39ff14;padding:6px;">';
                                    foreach ($f['options'] as $ok => $ov) echo '<option value="' . esc_attr($ok) . '" ' . selected($val, $ok, false) . '>' . esc_html($ov) . '</option>';
                                    echo '</select>';
                                    break;
                                case 'textarea':
                                case 'password':
                                    $tag = ($f['type'] === 'textarea') ? 'textarea rows="4"' : 'input type="password"';
                                    if ($f['type'] === 'textarea') {
                                        echo '<textarea name="' . $name . '" style="width:100%;background:#0d1117;border:1px solid #30363d;color:#39ff14;padding:6px;">' . esc_textarea($val) . '</textarea>';
                                    } else {
                                        echo '<input type="password" name="' . $name . '" value="' . esc_attr($val) . '" style="width:100%;background:#0d1117;border:1px solid #30363d;color:#39ff14;padding:6px;">';
                                    }
                                    break;
                                case 'number':
                                    echo '<input type="number" step="' . ($f['step'] ?? '0.01') . '" name="' . $name . '" value="' . esc_attr($val) . '" style="width:100%;background:#0d1117;border:1px solid #30363d;color:#39ff14;padding:6px;">';
                                    break;
                                case 'text':
                                default:
                                    echo '<input type="text" name="' . $name . '" value="' . esc_attr($val) . '" style="width:100%;background:#0d1117;border:1px solid #30363d;color:#39ff14;padding:6px;">';
                                    break;
                            }
                            echo '</div>';
                        endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
            <?php submit_button(__('Save Payment Settings', 'neomarket')); ?>
        </form>

    <?php elseif ($active_tab === 'shipping') : ?>
        <form method="post" action="options.php" style="max-width:800px;">
            <?php settings_fields('neomarket-general'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th><?php esc_html_e('Flat Shipping Cost', 'neomarket'); ?></th>
                    <td><input type="number" step="0.01" name="neomarket_flat_shipping" value="<?php echo esc_attr(get_option('neomarket_flat_shipping', 50000)); ?>"></td>
                </tr>
                <tr valign="top">
                    <th><?php esc_html_e('Free Shipping Threshold', 'neomarket'); ?></th>
                    <td><input type="number" step="0.01" name="neomarket_free_shipping_threshold" value="<?php echo esc_attr(get_option('neomarket_free_shipping_threshold', 1000000)); ?>">
                        <p class="description"><?php _e('If subtotal exceeds this amount, shipping becomes free.', 'neomarket'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

    <?php elseif ($active_tab === 'emails') : ?>
        <div class="card" style="max-width:800px;">
            <h3><?php _e('Email Notifications', 'neomarket'); ?></h3>
            <p><?php _e('Emails are automatically sent using WordPress <code>wp_mail()</code> when these events happen:', 'neomarket'); ?></p>
            <ul style="list-style:disc inside;">
                <li><?php _e('New order / manual payment request → Admin email', 'neomarket'); ?></li>
                <li><?php _e('Low Stock → Admin email', 'neomarket'); ?></li>
                <li><?php _e('Order completed → Customer email (planned in future release)', 'neomarket'); ?></li>
            </ul>
            <p><?php _e('For reliable delivery, configure SMTP via any WordPress SMTP plugin (e.g. WP Mail SMTP).', 'neomarket'); ?></p>
        </div>

    <?php elseif ($active_tab === 'tools') : ?>
        <div class="card" style="max-width:900px;">
            <h3><?php _e('System Tools', 'neomarket'); ?></h3>
            <form method="post" onsubmit="return confirm('<?php esc_html_e('Run migration and clear all caches?', 'neomarket'); ?>');">
                <?php wp_nonce_field('neomarket_tool_action', 'neomarket_tool_nonce'); ?>
                <input type="hidden" name="neomarket_run_migrate" value="1">
                <?php submit_button(__('Clear Cache & Re-Run DB Migrations', 'neomarket'), 'secondary'); ?>
            </form>
            <?php
            if (isset($_POST['neomarket_run_migrate']) && check_admin_referer('neomarket_tool_action', 'neomarket_tool_nonce')) {
                NeoMarket_Cache::flush_group();
                delete_option('neomarket_db_version');
                NeoMarket_Migrator::maybe_run_upgrades();
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Migration completed and cache flushed!', 'neomarket') . '</p></div>';
            }
            ?>
            <hr>
            <h4><?php _e('Database Status', 'neomarket'); ?></h4>
            <ul>
                <?php
                global $wpdb;
                $tables = array('neomarket_orders','neomarket_order_items','neomarket_cart','neomarket_transactions');
                foreach ($tables as $t) {
                    $full = $wpdb->prefix . $t;
                    $exists = NeoMarket_Migrator::table_exists($full);
                    echo '<li>' . esc_html($full) . ': ' . ($exists ? '<span style="color:#2ea043;font-weight:bold;">✓ OK</span>' : '<span style="color:#f85149;font-weight:bold;">✗ Missing</span>') . '</li>';
                }
                ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
