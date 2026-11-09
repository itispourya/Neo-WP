# NeoNotion WordPress Plugins Ecosystem

> **Official Suite of High-Performance WordPress Plugins for E-Commerce, UX, Bookings, Forms & Security.**  
> Developed & Maintained by **[Pourya Omidian](https://neonotion.net)** · [NeoNotion.net](https://neonotion.net)

[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)](LICENSE)
[![WordPress](https://img.shields.io/badge/WordPress-5.9%2B%20%7C%206.x-21759B?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Hub Framework](https://img.shields.io/badge/Hub_Portal-React_19_|_Tailwind_v4-61DAFB?logo=react&logoColor=black)](https://react.dev/)
[![Author](https://img.shields.io/badge/Author-Pourya_Omidian-orange)](mailto:itispourya@gmail.com)

---

## 🌟 The Plugin Suite Overview

This repository contains the official, production-ready WordPress plugin suite for the **NeoNotion** platform, engineered by **Pourya Omidian**. Each plugin operates autonomously while integrating seamlessly under a single, unified WordPress administrative menu (`dashicons-superhero-alt`).

| Plugin | Version | Category | Description | Ready-to-Install ZIP |
|---|---|---|---|---|
| **NeoMarket** | `2.0.0` | E-Commerce | Enterprise-grade store with online gateways (Zarinpal, PayPing, Mellat), Card-to-Card receipt upload, Crypto (TRC20/ERC20), AJAX cart, wishlist, and compare. | [Download ZIP](public/downloads/neomarket.zip) (74 KB) |
| **NeoLoadLand** | `7.0` | UX / Preloader | Modern loading screen manager with animated SVG/CSS spinners, custom brand splash screens, and smooth exit transitions. | [Download ZIP](public/downloads/neoloadland.zip) (8.3 KB) |
| **NeoBooking** | `9.0` | Appointments | Online appointment booking system with dedicated database tables, interactive time-slot selector, and automated workflow. | [Download ZIP](public/downloads/neobooking.zip) (12 KB) |
| **NeoFormData** | `8.0` | Forms & Leads | Dynamic form builder with custom relational database tables, anti-spam validation, and 1-click CSV/Excel export. | [Download ZIP](public/downloads/neoformdata.zip) (9.7 KB) |
| **NeoLock** | `6.0` | Security | Content protection suite: disables context menu (right-click), text selection, image dragging, and browser inspector shortcuts. | [Download ZIP](public/downloads/neolock.zip) (17 KB) |
| **Complete Bundle** | `v2.0` | All-in-One | All 5 plugins packaged together for multi-site deployments or full-suite setup. | [Download Bundle ZIP](public/downloads/neonotion-plugins-bundle.zip) (120 KB) |

---

## 📦 Detailed Architecture by Plugin

### 1. 🛒 NeoMarket (v2.0.0)
The complete e-commerce engine designed for high throughput and zero vendor lock-in:
- **`neomarket.php`**: Plugin kernel, singleton instance, dependency loader, admin menu bootstrap, and developer credits.
- **`includes/payments/`**:
  - `abstract-neomarket-gateway.php`: Uniform abstract base class enforcing `process_payment()`, `handle_callback()`, and gateway settings.
  - `gateway-zarinpal.php`: Zarinpal gateway integration with merchant ID and callback verification.
  - `gateway-payping.php`: PayPing gateway integration with token generation and status checks.
  - `gateway-mellat.php`: Bank Mellat (Behpardakht) direct processing for debit/credit cards.
  - `gateway-card-to-card.php`: Manual Card-to-Card payment with receipt image upload and tracking code input.
  - `gateway-crypto.php`: Cryptocurrency payments supporting TRC20 (USDT), ERC20, and QR code wallet presentation.
- **`includes/class-neomarket-migrator.php`**: Schema migration engine executing automatic table upgrades.
- **`includes/class-neomarket-db.php`**: Database abstraction layer utilizing `$wpdb->prepare()` for SQL-injection immunity.
- **`includes/class-neomarket-cart.php`**: Persistent cart supporting guest cookies (30-day lifecycle) and user synchronization.
- **`includes/class-neomarket-order.php`**: Custom order lifecycle engine (`neo_order` post type, status flows).
- **`includes/class-neomarket-rest.php`**: Headless REST API under `/neomarket/v1` namespace.
- **`includes/cross-plugin-sync.php`**: Connects product inventory with NeoBooking slots and pipes leads into NeoFormData.
- **`public/shortcode-engine.php`**: Handlers for `[neomarket]`, `[neomarket_cart]`, `[neomarket_checkout]`, `[neomarket_wishlist]`, `[neomarket_compare]`, and `[neomarket_my_account]`.

### 2. ⚡ NeoLoadLand (v7.0)
- **`neoloadland.php`**: Admin configuration panel, splash screen markup generator, and conditional enqueue logic.
- **`assets/js/neoloadland.js`**: Client-side window onload event handlers and smooth fade-out CSS transitions.
- **`assets/css/`**: High-performance CSS keyframe spinners, pulse animations, and theme overlays.

### 3. 📅 NeoBooking (v9.0)
- **`neobooking.php`**: Core booking engine with custom table `{$wpdb->prefix}neobooking_entries`.
- **`assets/js/neobooking.js`**: Interactive client-side date-picker and AJAX slot reservation.
- **Shortcode**: `[neobooking]` renders public reservation calendar.

### 4. 📝 NeoFormData (v8.0)
- **`neoformdata.php`**: Dynamic form engine with custom tables `{$wpdb->prefix}neoformdata_forms` and `{$wpdb->prefix}neoformdata_entries`.
- **`assets/js/neoformdata.js`**: Dynamic form builder, live input validator, and submission processor.
- **Shortcode**: `[neoformdata id="1"]` embeds any generated form.

### 5. 🛡️ NeoLock (v6.0)
- **`neolock.php`**: Content protection coordinator with per-page overrides and admin toggle panel.
- **`assets/js/neolock.js`**: High-speed DOM interception for `contextmenu`, `copy`, `cut`, `dragstart`, `keydown` (F12, Ctrl+U, PrintScreen, Ctrl+Shift+I).

---

## 🚀 How to Install in WordPress

### Method 1: WordPress Dashboard Upload (Recommended)
1. Download the desired plugin `.zip` file from the table above or via the interactive web hub.
2. In your WordPress Admin Dashboard, navigate to **Plugins** → **Add New** → **Upload Plugin**.
3. Choose the downloaded `.zip` file and click **Install Now**.
4. Click **Activate Plugin**.
5. Access the unified **NeoNotion** menu in the WordPress sidebar.

### Method 2: Manual FTP / SSH Installation
1. Extract the plugin folder (e.g., `NeoMarket`).
2. Upload the extracted folder to `/wp-content/plugins/` on your server.
3. Go to **Plugins → Installed Plugins** in the WordPress Dashboard and click **Activate**.

### Method 3: WP-CLI
```bash
wp plugin install /path/to/neomarket.zip --activate
```

---

## 💻 Running the Local Interactive Hub

The repository includes a modern React 19 + Tailwind CSS v4 distribution hub for browsing file contents, viewing code in real time, and downloading assets:

```bash
# Clone the repository
git clone https://github.com/itispourya/neonotion.git
cd neonotion

# Install dependencies
npm install

# Run the distribution hub
npm run dev
```

---

## 👥 Author & Copyright

- **Author & Lead Architect**: [Pourya Omidian](mailto:itispourya@gmail.com)
- **Website**: [NeoNotion.net](https://neonotion.net)
- **Email**: [itispourya@gmail.com](mailto:itispourya@gmail.com)
- **License**: [GPL-2.0-or-later](LICENSE) / Apache 2.0
