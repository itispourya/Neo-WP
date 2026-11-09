<?php
/**
 * Plugin Name: NeoMarket
 * Plugin URI: https://neonotion.net/neomarket
 * Description: Complete e-commerce solution integrated into NeoNotion ecosystem. Supports online gateways (Zarinpal, PayPing, Mellat), card-to-card, and crypto payments. Catalog, cart, checkout, wishlist, compare, quick view, dark mode and more.
 * Version: 2.0.0
 * Author: Pourya Omidian
 * Author URI: https://neonotion.net
 * License: GPL2
 * Text Domain: neomarket
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('NEOMARKET_VERSION', '2.0.0');
define('NEOMARKET_PATH', plugin_dir_path(__FILE__));
define('NEOMARKET_URL', plugin_dir_url(__FILE__));
define('NEOMARKET_BASENAME', plugin_basename(__FILE__));

class NeoMarket {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'register_admin_menu'), 20);
        add_action('admin_footer', array($this, 'add_developer_credit'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_head', array($this, 'install_defaults'), 99);
    }

    private function load_dependencies() {
        // 1. Migration engine (runs upgrades on plugins_loaded)
        require_once NEOMARKET_PATH . 'includes/class-neomarket-migrator.php';
        // 2. Database Wrapper (secure queries)
        require_once NEOMARKET_PATH . 'includes/class-neomarket-db.php';
        // 3. Object / Transient cache
        require_once NEOMARKET_PATH . 'includes/class-neomarket-cache.php';
        // 4. CPT / Taxonomies
        require_once NEOMARKET_PATH . 'includes/cpt-registration.php';
        // 5. Product meta fields
        require_once NEOMARKET_PATH . 'includes/meta-fields.php';
        // 6. Admin hooks / columns
        require_once NEOMARKET_PATH . 'includes/admin-hooks.php';
        // 7. Cart engine
        require_once NEOMARKET_PATH . 'includes/class-neomarket-cart.php';
        // 8. Order engine
        require_once NEOMARKET_PATH . 'includes/class-neomarket-order.php';
        // 9. Payments (gateway loader + abstract)
        require_once NEOMARKET_PATH . 'includes/payments/abstract-neomarket-gateway.php';
        // (Gateways are loaded inside NeoMarket_Payments::init())
        // 10. Helpers
        require_once NEOMARKET_PATH . 'includes/helper-logic.php';
        // 11. GDPR / Privacy
        require_once NEOMARKET_PATH . 'includes/privacy-tools.php';
        // 12. Frontend
        require_once NEOMARKET_PATH . 'public/shortcode-engine.php';
        require_once NEOMARKET_PATH . 'public/ajax-handlers.php';
        // 13. REST API controller
        require_once NEOMARKET_PATH . 'includes/class-neomarket-rest.php';
        // 14. Cross-plugin sync
        require_once NEOMARKET_PATH . 'includes/cross-plugin-sync.php';
    }

    public function load_textdomain() {
        load_plugin_textdomain('neomarket', false, dirname(NEOMARKET_BASENAME) . '/languages');
    }

    public function install_defaults() {
        // Set default currency, shop pages on first run
        if (get_option('neomarket_installed') !== 'yes') {
            add_option('neomarket_currency', 'IRT');
            add_option('neomarket_free_shipping_threshold', 1000000);
            add_option('neomarket_flat_shipping', 50000);
            add_option('neomarket_low_stock_threshold', 5);
            add_option('neomarket_installed', 'yes');

            // Enable card-to-card by default so the checkout always works immediately
            $ctc = get_option('neomarket_gateway_cardtocard', array());
            if (empty($ctc['enabled'])) {
                $ctc['enabled'] = 'yes';
                $ctc['title'] = 'پرداخت کارت به کارت';
                $ctc['description'] = 'مبلغ را به شماره کارت تعریف شده واریز کنید و شماره پیگیری را ثبت نمایید.';
                update_option('neomarket_gateway_cardtocard', $ctc);
            }
        }
    }

    public function register_admin_menu() {
        // Dashboard
        add_submenu_page(
            'neonotion',
            __('NeoMarket Dashboard', 'neomarket'),
            __('NeoMarket', 'neomarket'),
            'manage_options',
            'neomarket',
            array($this, 'render_admin_dashboard')
        );
        // Settings
        add_submenu_page(
            'neonotion',
            __('NeoMarket Settings', 'neomarket'),
            __('NeoMarket Settings', 'neomarket'),
            'manage_options',
            'neomarket-settings',
            array($this, 'render_settings_page')
        );
    }

    public function render_admin_dashboard() {
        include_once NEOMARKET_PATH . 'includes/admin-dashboard-template.php';
    }

    public function register_settings() {
        // General settings
        register_setting('neomarket-general', 'neomarket_currency', 'sanitize_text_field');
        register_setting('neomarket-general', 'neomarket_shop_page_id', 'absint');
        register_setting('neomarket-general', 'neomarket_free_shipping_threshold', 'floatval');
        register_setting('neomarket-general', 'neomarket_flat_shipping', 'floatval');
        register_setting('neomarket-general', 'neomarket_low_stock_threshold', 'absint');
        register_setting('neomarket-general', 'neomarket_store_address', 'sanitize_textarea_field');

        // Per-gateway settings: enable via settings-page
        foreach (array_keys(NeoMarket_Payments::get_all_gateways()) as $gw_id) {
            register_setting('neomarket-payments', 'neomarket_gateway_' . $gw_id, array($this, 'sanitize_gateway_settings'));
        }
    }

    public function sanitize_gateway_settings($input) {
        if (!is_array($input)) return array();
        $clean = array();
        foreach ($input as $k => $v) {
            if ($k === 'enabled') $clean[$k] = ($v === 'yes') ? 'yes' : 'no';
            elseif (strpos($k, 'password') !== false || strpos($k, 'token') !== false || strpos($k, 'secret') !== false || strpos($k, 'merchant') !== false) {
                $clean[$k] = sanitize_text_field($v);
            } elseif ($k === 'card_numbers' || $k === 'description' || $k === 'other_wallets') {
                $clean[$k] = sanitize_textarea_field($v);
            } elseif ($k === 'usd_rate' || $k === 'confirmations') {
                $clean[$k] = floatval($v);
            } else {
                $clean[$k] = sanitize_text_field($v);
            }
        }
        return $clean;
    }

    public function render_settings_page() {
        include NEOMARKET_PATH . 'includes/admin/settings-page.php';
    }

    public function add_developer_credit() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen) return;
        $show = (
            strpos($screen->id, 'neomarket') !== false ||
            strpos($screen->id, 'neo_product') !== false ||
            strpos($screen->id, 'neo_order') !== false
        );
        if ($show) : ?>
            <div id="neomarket-footer-credit" style="margin-top:20px;border-top:1px solid #30363d;padding-top:10px;font-size:12px;color:#8b949e;text-align:center;">
                <?php printf(
                    __('NeoMarket v%s — Developed by %s | %s', 'neomarket'),
                    NEOMARKET_VERSION,
                    '<strong style="color:#39ff14;">Pourya Omidian</strong>',
                    '<a href="https://neonotion.net" target="_blank" style="color:#39ff14;text-decoration:none;">NeoNotion.net</a>'
                ); ?>
            </div>
        <?php endif;
    }
}

function NeoMarket() {
    return NeoMarket::get_instance();
}
NeoMarket();

// Activation hook: run migrations explicitly + flush permalinks
register_activation_hook(__FILE__, function() {
    require_once NEOMARKET_PATH . 'includes/class-neomarket-migrator.php';
    NeoMarket_Migrator::maybe_run_upgrades();
    flush_rewrite_rules();
});
