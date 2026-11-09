import fs from 'fs';
import path from 'path';

const PLUGINS = [
  {
    id: 'neomarket',
    folder: 'NeoMarket',
    name: 'NeoMarket',
    version: '2.0.0',
    tagline: 'Complete E-Commerce & Multi-Gateway Solution for WordPress',
    zipFile: '/downloads/neomarket.zip',
    category: 'E-Commerce',
    color: 'emerald',
    description: 'An enterprise-grade, modular e-commerce engine integrated natively into the NeoNotion ecosystem. Built as a lightweight, high-performance alternative to heavyweight commerce suites. Features direct online payment gateways (Zarinpal, PayPing, Mellat), manual card-to-card processing with receipt upload, cryptocurrency payments (TRC20 USDT, ERC20), persistent guest & user carts, custom database tables for high-throughput order management, custom REST API endpoints (/neomarket/v1), wishlist, product comparison matrix, and full GDPR compliance data tools.',
    shortcodes: [
      { code: '[neomarket]', desc: 'Renders the primary responsive interactive product catalog grid with filters and sorting' },
      { code: '[neomarket_cart]', desc: 'Renders the customer shopping cart with coupon application and subtotal calculations' },
      { code: '[neomarket_checkout]', desc: 'Renders the streamlined checkout flow with customer billing fields and gateway selector' },
      { code: '[neomarket_wishlist]', desc: 'Renders the customer saved items wishlist with quick add-to-cart affordance' },
      { code: '[neomarket_compare]', desc: 'Renders the side-by-side product technical specification comparison matrix' },
      { code: '[neomarket_my_account]', desc: 'Customer account portal for order history, delivery tracking, and invoice downloads' }
    ]
  },
  {
    id: 'neoloadland',
    folder: 'NeoLoadLand',
    name: 'NeoLoadLand',
    version: '7.0',
    tagline: 'High-Performance Loading Screen & Preloader Manager',
    zipFile: '/downloads/neoloadland.zip',
    category: 'User Experience (UX)',
    color: 'indigo',
    description: 'A sophisticated loading screen and page transition manager for WordPress. Eliminates layout shifting and harsh asset rendering by providing configurable CSS3/SVG loading spinners, animated brand logos, smooth fade-out exit transitions, customizable timeout thresholds, and page-specific inclusion and exclusion rules.',
    shortcodes: []
  },
  {
    id: 'neobooking',
    folder: 'NeoBooking',
    name: 'NeoBooking',
    version: '9.0',
    tagline: 'Appointment Scheduling & Time-Slot Booking System',
    zipFile: '/downloads/neobooking.zip',
    category: 'Appointments & Scheduling',
    color: 'amber',
    description: 'An end-to-end appointment scheduling and reservations manager for WordPress. Engineered with dedicated relational database storage (wp_neobooking_entries) for instant query speed. Features an interactive date picker, configurable time slots, client reservation form, automated status workflow (Pending, Approved, Cancelled), and cross-plugin stock synchronization with NeoMarket.',
    shortcodes: [
      { code: '[neobooking]', desc: 'Renders the public interactive booking calendar and appointment reservation form' }
    ]
  },
  {
    id: 'neoformdata',
    folder: 'NeoFormData',
    name: 'NeoFormData',
    version: '8.0',
    tagline: 'Custom Form Builder, Data Storage & Export Hub',
    zipFile: '/downloads/neoformdata.zip',
    category: 'Forms & Lead Generation',
    color: 'cyan',
    description: 'A flexible drag-and-drop form manager with isolated relational database tables (wp_neoformdata_forms, wp_neoformdata_entries). Features client-side AJAX validation, anti-spam protections, instant email notifications upon submission, and 1-click export of collected leads to CSV and Excel formats.',
    shortcodes: [
      { code: '[neoformdata id="1"]', desc: 'Embeds a specific form by its unique database ID with asynchronous AJAX handling' }
    ]
  },
  {
    id: 'neolock',
    folder: 'NeoLock',
    name: 'NeoLock',
    version: '6.0',
    tagline: 'Ultimate Content Protection, Anti-Copy & Anti-Theft Guard',
    zipFile: '/downloads/neolock.zip',
    category: 'Security & Content Rights',
    color: 'rose',
    description: 'An advanced digital rights protection suite for WordPress creators and agencies. Prevents unauthorized content scraping by disabling the context menu (right-click), text selection, image dragging, and browser inspector shortcuts (F12, Ctrl+U, Ctrl+Shift+I, PrintScreen). Includes granular per-page protection overrides and optional dynamic watermark overlays.',
    shortcodes: []
  }
];

const ROOT_DIR = process.cwd();

function getFileDescription(filePath, filename) {
  const p = filePath.toLowerCase();
  
  // NeoMarket Core
  if (filename === 'neomarket.php') return 'Core plugin entry point. Initializes the singleton kernel, registers global constants, boots modular dependencies, mounts the unified NeoNotion WordPress menu, registers admin settings, and injects developer credits.';
  if (filename === 'roadmap.md') return 'Comprehensive architectural specification and implementation roadmap for NeoMarket v2.0, detailing custom schema migrations, 5 payment gateways, REST controller architecture, caching strategy, and sprint milestones.';

  // Payments
  if (p.includes('payments/abstract-neomarket-gateway.php')) return 'Abstract base class establishing the contract for all payment processors. Enforces uniform implementations of process_payment(), handle_callback(), gateway icons, and admin setting fields.';
  if (p.includes('payments/gateway-zarinpal.php')) return 'Zarinpal payment gateway integration. Handles merchant authorization, payment request generation, user redirection to bank gateway, and secure callback hash verification.';
  if (p.includes('payments/gateway-payping.php')) return 'PayPing online payment gateway integration. Manages token generation, API client handshakes, and transaction status verification.';
  if (p.includes('payments/gateway-mellat.php')) return 'Bank Mellat (Behpardakht) direct online gateway. Processes credit/debit card transactions with official bank settlement protocols and transaction verification.';
  if (p.includes('payments/gateway-card-to-card.php')) return 'Offline Card-to-Card payment method. Allows customers to submit bank transfer receipts, tracking codes, and payment details for manual administrative review and verification.';
  if (p.includes('payments/gateway-crypto.php')) return 'Cryptocurrency payment processor supporting TRC20 (USDT) and ERC20 tokens. Displays public wallet addresses, QR codes, and facilitates manual or automated transaction hash checks.';

  // Includes
  if (p.includes('class-neomarket-migrator.php')) return 'Database migration engine. Automatically monitors neomarket_db_version and applies sequential schema upgrade scripts from the migrations directory without data loss.';
  if (p.includes('class-neomarket-db.php')) return 'Dedicated database abstraction wrapper utilizing WordPress prepared statements ($wpdb->prepare) to guarantee complete immunity against SQL injection vulnerabilities.';
  if (p.includes('class-neomarket-cache.php')) return 'Object and transient caching controller. Caches expensive catalog filter queries, taxonomy trees, and sales analytics with automatic invalidation upon product saves.';
  if (p.includes('class-neomarket-cart.php')) return 'Persistent shopping cart engine. Manages cart sessions via 30-day cookies for guest visitors and synchronizes user meta across devices for authenticated accounts.';
  if (p.includes('class-neomarket-order.php')) return 'Order lifecycle controller. Manages order states (pending, processing, on-hold, completed, cancelled, refunded) and customer records linked to the neo_order post type.';
  if (p.includes('class-neomarket-rest.php')) return 'WordPress REST API controller exposing endpoints under the /neomarket/v1 namespace for headless commerce, cart updates, checkout submission, and catalog querying.';
  if (p.includes('cpt-registration.php')) return 'Registers custom post types (neo_product, neo_order) and taxonomies (neo_product_cat) with proper capability maps and REST support.';
  if (p.includes('meta-fields.php')) return 'Registers custom meta boxes and fields for products: regular price, sale price, SKU, inventory quantity, manage stock toggle, and technical specifications.';
  if (p.includes('helper-logic.php')) return 'Global utility helper functions providing currency symbol formatting, price localization, stock status calculations, and input sanitization.';
  if (p.includes('privacy-tools.php')) return 'GDPR compliance suite. Registers personal data exporters and erasers with WordPress core privacy tools for customer billing and order data.';
  if (p.includes('cross-plugin-sync.php')) return 'Interoperability bridge. Synchronizes product availability with NeoBooking slots and automatically pipes checkout customer leads into NeoFormData tables.';
  if (p.includes('admin-hooks.php')) return 'WordPress admin hooks for custom post type columns, dashboard metric summaries, stock alert badges, and quick-action buttons.';
  if (p.includes('admin-dashboard-template.php')) return 'Administrative dashboard template providing an executive overview of revenue, monthly order volume, low stock alerts, and recent transactions.';
  if (p.includes('settings-page.php')) return 'Administrative settings renderer managing general store configurations, currency choices, shipping flat rates, and notification email templates.';
  if (p.includes('migrations/2026_09_06-upgrade-to-2-0-0.php')) return 'Version 2.0.0 database schema migration script executing dbDelta to establish dedicated orders, order_items, cart, and transactions tables.';

  // Public
  if (p.includes('public/shortcode-engine.php')) return 'Shortcode parser and renderer. Boots and executes [neomarket], [neomarket_cart], [neomarket_checkout], [neomarket_wishlist], [neomarket_compare], and [neomarket_my_account].';
  if (p.includes('public/ajax-handlers.php')) return 'Asynchronous AJAX controller. Handles client-side actions including add-to-cart, quantity changes, live product search, and dynamic filter queries.';
  if (p.includes('frontend-templates/catalog.php')) return 'Frontend catalog template rendering the responsive product grid, sorting dropdown, pagination, and multi-attribute filter sidebar.';
  if (p.includes('frontend-templates/single-product.php')) return 'Single product page template rendering image galleries, pricing badges, stock indicators, technical specification tables, and add-to-cart forms.';
  if (p.includes('frontend-templates/cart.php')) return 'Shopping cart view template displaying line items, thumbnail previews, quantity selectors, coupon fields, and order totals.';
  if (p.includes('frontend-templates/checkout.php')) return 'Checkout template providing customer shipping/billing forms, order review summary, and interactive payment gateway selection.';
  if (p.includes('frontend-templates/myaccount.php')) return 'Customer account dashboard template displaying historical orders, delivery tracking statuses, and downloadable digital assets.';
  if (p.includes('frontend-templates/wishlist.php')) return 'Wishlist template rendering customer-bookmarked products with direct add-to-cart conversion actions.';
  if (p.includes('frontend-templates/compare.php')) return 'Product comparison template rendering a side-by-side technical specification matrix for up to 4 selected products.';

  // Assets
  if (filename.endsWith('.css')) return 'Stylesheet providing responsive layout rules, design token variables, and user interface styling.';
  if (filename.endsWith('.js')) return 'Client-side JavaScript controller managing DOM interactions, event listeners, dynamic modals, and asynchronous server calls.';

  // NeoLoadLand
  if (filename === 'neoloadland.php') return 'Main NeoLoadLand controller. Registers preloader options in WordPress admin, enqueues CSS/JS assets, and renders the splash screen overlay markup into wp_footer.';

  // NeoBooking
  if (filename === 'neobooking.php') return 'Main NeoBooking controller. Instantiates the wp_neobooking_entries table, registers the [neobooking] shortcode, and handles calendar slot reservation AJAX requests.';

  // NeoFormData
  if (filename === 'neoformdata.php') return 'Main NeoFormData controller. Manages custom relational storage tables (wp_neoformdata_forms, wp_neoformdata_entries), handles form generation, and generates CSV exports.';

  // NeoLock
  if (filename === 'neolock.php') return 'Main NeoLock controller. Orchestrates content protection rules, manages per-page protection overrides, and registers admin toggle controls.';

  return 'Component source file for the NeoNotion WordPress plugin ecosystem.';
}

function scanDirectory(folderPath, relativeBase = '') {
  const result = [];
  const entries = fs.readdirSync(folderPath);

  for (const entry of entries) {
    const fullPath = path.join(folderPath, entry);
    const relPath = path.join(relativeBase, entry).replace(/\\/g, '/');
    const stat = fs.statSync(fullPath);

    if (stat.isDirectory()) {
      result.push(...scanDirectory(fullPath, relPath));
    } else {
      const content = fs.readFileSync(fullPath, 'utf8');
      const ext = path.extname(entry).slice(1);
      const desc = getFileDescription(relPath, entry);

      result.push({
        path: relPath,
        name: entry,
        extension: ext,
        sizeBytes: stat.size,
        sizeFormatted: (stat.size / 1024).toFixed(1) + ' KB',
        description: desc,
        content: content
      });
    }
  }

  return result;
}

const packagedPlugins = PLUGINS.map(p => {
  const pluginDir = path.join(ROOT_DIR, p.folder);
  if (!fs.existsSync(pluginDir)) {
    return { ...p, files: [] };
  }
  const files = scanDirectory(pluginDir);
  return {
    ...p,
    totalFiles: files.length,
    files
  };
});

const outTs = `/**
 * NeoNotion WordPress Plugins Data Manifest (English Edition)
 * Automatically indexed and generated from uploaded plugin directories.
 * Author: Pourya Omidian (https://neonotion.net)
 */

export interface PluginFile {
  path: string;
  name: string;
  extension: string;
  sizeBytes: number;
  sizeFormatted: string;
  description: string;
  content: string;
}

export interface PluginInfo {
  id: string;
  folder: string;
  name: string;
  version: string;
  tagline: string;
  zipFile: string;
  category: string;
  color: 'emerald' | 'indigo' | 'amber' | 'cyan' | 'rose';
  description: string;
  totalFiles: number;
  shortcodes: { code: string; desc: string }[];
  files: PluginFile[];
}

export const PLUGINS_DATA: PluginInfo[] = ${JSON.stringify(packagedPlugins, null, 2)};
`;

const TARGET_FILE = path.join(ROOT_DIR, 'src', 'data', 'pluginsData.ts');
fs.mkdirSync(path.dirname(TARGET_FILE), { recursive: true });
fs.writeFileSync(TARGET_FILE, outTs);
console.log(`Successfully generated ${TARGET_FILE} with 100% English documentation for ${packagedPlugins.reduce((acc, p) => acc + p.totalFiles, 0)} files.`);
