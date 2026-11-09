# NeoMarket v2.0 - Full E-Commerce Solution
### Roadmap & Implementation Plan
**Developed by: Pourya Omidian | NeoNotion.net**
**Date: 2026-09-06**
**Status: Active Implementation**

---

## 🎯 Mission Statement
Transform NeoMarket from a basic product catalog into a **full-featured, scalable, and secure multi-gateway e-commerce platform** integrated seamlessly into the NeoNotion plugin ecosystem.

---

## 🗺️ Phase 1: Foundation & Architecture Upgrade
### ✅ Status: Pending Implementation

#### 1.1 Migration Engine
- **File:** `includes/class-neomarket-migrator.php`
- **Goal:** Safe schema upgrades for future versions
- **Features:**
  - Version tracking via `neomarket_db_version` option
  - delta migrations stored in `includes/migrations/`
  - Rollback support (partial)
  - Fire on `plugins_loaded` hook

#### 1.2 Custom Database Tables
- **Tables to create:**
  - `{$wpdb->prefix}neomarket_orders` — Order header data
  - `{$wpdb->prefix}neomarket_order_items` — Line items per order
  - `{$wpdb->prefix}neomarket_cart` — Persistent carts for guests & users
  - `{$wpdb->prefix}neomarket_transactions` — Payment logs & gateway responses
- **Engine:** InnoDB with proper foreign key constraints (via dbDelta)

#### 1.3 Object Caching Layer
- **Strategy:** WordPress Transient API + Object Cache drop-in support
- **Cached Queries:**
  - Product catalog results (per filter combination)
  - Related products
  - Category trees
  - Top-selling products (dashboard widget)
- **Cache invalidation:** On product save, order complete, term edit

#### 1.4 REST API Migration
- **Namespace:** `neomarket/v1`
- **Endpoints to expose:**
  | Method | Route | Purpose |
  |--------|-------|---------|
  | GET | `/products` | Catalog listing with filters |
  | GET | `/products/(?P<id>\d+)` | Single product details |
  | GET | `/cart` | Retrieve current cart |
  | POST | `/cart/add` | Add item to cart |
  | POST | `/cart/(?P<key>[a-z0-9]+)/update` | Update qty |
  | DELETE | `/cart/(?P<key>[a-z0-9]+)` | Remove item |
  | POST | `/checkout` | Submit order (returns payment URL/form) |
  | GET | `/orders/(?P<id>\d+)` | User order lookup |
  | POST | `/reviews` | Submit verified review |

---

## 🗺️ Phase 2: Core Commerce Engine
### ✅ Status: Pending Implementation

#### 2.1 Advanced Inventory Management
- **Meta Fields added to `neo_product`:**
  - `_stock_quantity` (INT)
  - `_manage_stock` (yes/no)
  - `_backorders` (yes/no/notify)
  - `_sold_individually` (yes/no)
- **Hooks:**
  - Deduct stock on `neomarket_order_status_completed`
  - Restore stock on `neomarket_order_status_cancelled`
  - Email admin on low-stock threshold (via filter `neomarket_low_stock_threshold`)

#### 2.2 Cart System
- **Persistence:**
  - Guest users: Cookie (30 days) + DB fallback (`neomarket_cart`)
  - Logged-in: User meta + sync across devices
- **Features:**
  - Add / Remove / Quantity update
  - Price calculation (subtotal, discount preview, tax placeholder)
  - Cart hash for cache invalidation
  - Cart expiry (60 days for guests)
- **Template:** `public/frontend-templates/cart.php`

#### 2.3 Checkout & Order Flow
- **Checkout Template:** `public/frontend-templates/checkout.php`
- **Fields:**
  - Billing / Shipping info (Persian + English)
  - Payment method selector
  - Order notes
- **Order statuses (custom taxonomy on `neo_order` CPT):**
  - `pending` (Default)
  - `processing`
  - `on-hold`
  - `completed`
  - `cancelled`
  - `refunded`
  - `failed`
- **Customer:** Link orders to `user_id` or guest email

---

## 🗺️ Phase 3: Multi-Gateway Payment System
### ✅ Status: Pending Implementation

#### 3.1 Gateway Abstract Class
- **File:** `includes/payments/abstract-neomarket-gateway.php`
- **Methods:**
  - `process_payment( $order_id )` → redirect/JSON
  - `handle_callback()` → verify & complete order
  - `get_icon()` → logo
  - `get_title()`, `get_description()` → admin UI
- **Available Gateways (built-in):**
  1. `Zarinpal_Gateway` — ایرانی زرین‌پال (درگاه ایرانی)
  2. `PayPing_Gateway` — ایران پی‌پینگ
  3. `Mellat_Gateway` — بانک ملت (Saderat)
  4. `CardToCard_Gateway` — **پرداخت کارت به کارت** (با فیلد شماره پیگیری و آپلود فیش)
  5. `Crypto_Gateway` — **پرداخت رمز ارز** (TRC20 / ERC20 — نمایش آدرس و Q.R. + تأیید دستی توسط ادمین یا وب‌هوک)

#### 3.2 Admin Gateway Settings
- **Menu:** Neonotion → NeoMarket → Settings → Payments
- **Per gateway fields:** API Key / Secret / Merchant ID / Active toggle
- **Card to Card:** Admin enters destination card number(s)
- **Crypto:** Admin enters wallet addresses (Tron / Ethereum) + confirmations required
- **Order Verification:**
  - For online gateways: Hash check via callback
  - For Card-to-Card / Crypto: Manual approve button + email notice to admin

---

## 🗺️ Phase 4: Frontend Experience (Amazon/Digikala 2.0)
### ✅ Status: Pending Implementation

#### 4.1 Advanced Filtering Bar
- **Replace sidebar inputs with:**
  - `noUiSlider` — Dual-handle price range (AJAX live)
  - Hierarchical category tree with collapsible children
  - Dynamic attribute checkboxes (pulled from Specifications meta)
  - Inline search with debounce (250ms)
- **CSS:** New file `assets/css/neomarket-filters.css`

#### 4.2 Wishlist System
- **Endpoint:** `POST /neomarket/v1/wishlist`
- **Storage:** `user_meta` + localStorage fallback for guests
- **UI:** Heart icon on product cards, dedicated page via `[neomarket_wishlist]`
- **Count bubble** next to cart icon

#### 4.3 Product Comparison
- **Limit:** Max 4 products
- **Storage:** localStorage + cookie
- **Template:** `[neomarket_compare]` shortcode
- **Rows:** Pull `_specifications` dynamically and align side-by-side

#### 4.4 Quick View Modal
- **Trigger:** "Quick View" button on catalog cards
- **Modal contents:** Gallery, price, add-to-cart form, excerpt
- **Libraries:** Native `<dialog>` HTML5 element (no extra dep needed)

#### 4.5 Dark Mode (User Toggle)
- **Implementation:** CSS variables
  - `.neomarket-container` (light, default)
  - `.neomarket-container[data-theme="dark"]`
- **Toggle:** Switch in footer/corner (JS saves to localStorage)
- **Colors:** Matrix/GitHub-themed (matches user preference)

---

## 🗺️ Phase 5: Security & Compliance
### ✅ Status: Pending Implementation

#### 5.1 Rate Limiting
- **Implement:** `Transient` + IP-based throttling for:
  - Checkout endpoint: 2 requests / min
  - Review submit: 10 requests / hour per user
  - Cart add: 60 requests / hour
- **Response:** HTTP 429 + JSON error

#### 5.2 SQL Injection Hardening
- **All raw queries (if any) → `$wpdb->prepare()` mandatory**
- Add defensive wrapper class `NeoMarket_DB` with parameterized helpers for:
  - `get_order_by_id( $id )`
  - `get_user_orders( $user_id )`
  - `search_orders( $query )`

#### 5.3 GDPR / Privacy Tools
- **Hooks registered:**
  - `wp_privacy_personal_data_exporters`
  - `wp_privacy_personal_data_erasers`
- **Exporter:** Returns customer billing info, orders, reviews
- **Eraser:** Deletes/anonymizes order PII after 365 days (filterable)

#### 5.4 Input Sanitization Audit
- **Functions used consistently:**
  - `sanitize_text_field()`
  - `sanitize_textarea_field()`
  - `sanitize_email()`
  - `absint()`
  - `esc_attr()`, `esc_html()`, `esc_url()` in all templates
- **Audit target:** All files, ensure no direct echoes of `$_POST` without sanitization

---

## 🗺️ Phase 6: Admin Dashboard & Reporting
### ✅ Status: Pending Implementation

#### 6.1 Enhanced Dashboard (Matrix/GitHub theme continued)
- **Widgets:**
  1. **Sales Chart — Monthly (Chart.js Line)**
  2. **Top Selling Products (Bar chart)**
  3. **Low Stock Alert List**
  4. **Pending Payment / Manual Review Orders**
- **Files:**
  - View: `includes/admin-dashboard-template.php` (refactor)
  - Logic: `includes/admin-dashboard-functions.php`

#### 6.2 Order Management Screen
- **Post Type:** `neo_order`
- **Columns:** Order #, Customer, Status, Total, Gateway, Date
- **Bulk Actions:** Change status (complete / cancel / processing)
- **Row Actions:** View Details (Modal), Send Invoice Email
- **Meta Box:** Order items table, payment log, tracking code input

#### 6.3 Cross-Plugin Sync (NeoNotion Ecosystem)
- **NeoBooking Sync:** If product meta `_linked_booking_id` set, sync stock with booking availability
- **NeoForm Lead Capture:** After payment, send order data to a NeoForm entry (filter `neomarket_payment_complete`)
- **Central Notifications:** NeoNotion root menu badge count shows pending orders + form entries

---

## 🗺️ Phase 7: Settings & UX Polish
### ✅ Status: Pending Implementation

#### 7.1 Settings Page
- **Tabs:**
  1. General (Currency, Shop page ID, Store address)
  2. Payments (Gateway activation per above)
  3. Shipping (Flat rate table, free shipping threshold)
  4. Email (From name, SMTP previews)

#### 7.2 Shortcodes Summary
| Shortcode | Output |
|-----------|--------|
| `[neomarket]` | Main catalog (SPA) |
| `[neomarket_cart]` | Cart page |
| `[neomarket_checkout]` | Checkout form |
| `[neomarket_wishlist]` | User wishlist |
| `[neomarket_compare]` | Comparison table |
| `[neomarket_my_account]` | Customer dashboard (orders) |

---

## 📦 Delivery Checklist
- [x] Roadmap.md created
- [ ] Migration Engine & DB tables
- [ ] REST API Controller Class
- [ ] Cart, Checkout, Order Engine
- [ ] 5 Payment Gateways (Zarinpal, PayPing, Mellat, Card-to-Card, Crypto)
- [ ] Advanced Filters with noUiSlider
- [ ] Wishlist & Compare & Quick View
- [ ] Dark Mode Toggle
- [ ] Dashboard with Chart.js reports
- [ ] Rate Limiting & Privacy Tools
- [ ] Cross-plugin sync (NeoBooking / NeoForm hooks)

---

## 🧪 Testing Plan
1. **Unit tests (PHP):**
   - Cart addition / removal (persistence tests)
   - Price calculation (sale price + stock deduction)
   - Gateway callback validation (mock)
2. **E2E tests (Browser):**
   - Guest checkout (Card-to-Card flow)
   - Logged-in wishlist sync
   - Admin dashboard chart render
3. **Security scan:**
   - Manual audit: `$_POST` sanitization map
   - `$_GET` order lookup: verify capability check

---

## 📅 Timeline Estimate (Agile Sprints)
| Sprint | Duration | Focus |
|--------|----------|-------|
| 1 | 2 Days | Foundation (Migration, DB, REST) |
| 2 | 3 Days | Cart + Checkout + Order CPT |
| 3 | 3 Days | Payment Gateways (5 integrations) |
| 4 | 2 Days | Frontend (Filters, Wishlist, Quick View, Dark mode) |
| 5 | 2 Days | Dashboard Reporting + Sync + Security |
| **Total** | **12 Days** | **Full-featured deployment ready** |

---

### End of Roadmap
**Version Lock:** NeoMarket v2.0.0
**Author:** Pourya Omidian
**Company:** NeoNotion.net
**License:** GPL-2.0-or-later
