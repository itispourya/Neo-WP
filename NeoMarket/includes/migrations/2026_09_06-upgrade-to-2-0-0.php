<?php
/**
 * Migration 2026_09_06: Create core e-commerce tables
 * Version target: 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

// ==============================================
// 1. Orders Table
// ==============================================
$table_orders = $wpdb->prefix . 'neomarket_orders';
$sql_orders = "CREATE TABLE {$table_orders} (
    `order_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_key` VARCHAR(100) NOT NULL,
    `user_id` BIGINT(20) UNSIGNED DEFAULT 0,
    `billing_email` VARCHAR(100) DEFAULT '',
    `billing_phone` VARCHAR(20) DEFAULT '',
    `billing_first_name` VARCHAR(50) DEFAULT '',
    `billing_last_name` VARCHAR(50) DEFAULT '',
    `billing_address_1` VARCHAR(255) DEFAULT '',
    `billing_address_2` VARCHAR(255) DEFAULT '',
    `billing_city` VARCHAR(100) DEFAULT '',
    `billing_state` VARCHAR(100) DEFAULT '',
    `billing_postcode` VARCHAR(20) DEFAULT '',
    `billing_country` VARCHAR(3) DEFAULT '',
    `shipping_first_name` VARCHAR(50) DEFAULT '',
    `shipping_last_name` VARCHAR(50) DEFAULT '',
    `shipping_address_1` VARCHAR(255) DEFAULT '',
    `shipping_address_2` VARCHAR(255) DEFAULT '',
    `shipping_city` VARCHAR(100) DEFAULT '',
    `shipping_state` VARCHAR(100) DEFAULT '',
    `shipping_postcode` VARCHAR(20) DEFAULT '',
    `shipping_country` VARCHAR(3) DEFAULT '',
    `payment_method` VARCHAR(50) DEFAULT '',
    `payment_method_title` VARCHAR(100) DEFAULT '',
    `transaction_id` VARCHAR(200) DEFAULT '',
    `customer_ip_address` VARCHAR(40) DEFAULT '',
    `customer_user_agent` VARCHAR(255) DEFAULT '',
    `status` VARCHAR(30) DEFAULT 'pending',
    `currency` VARCHAR(5) DEFAULT 'IRT',
    `prices_include_tax` TINYINT(1) DEFAULT 0,
    `discount_total` DECIMAL(26,8) DEFAULT 0,
    `discount_tax` DECIMAL(26,8) DEFAULT 0,
    `shipping_total` DECIMAL(26,8) DEFAULT 0,
    `shipping_tax` DECIMAL(26,8) DEFAULT 0,
    `cart_tax` DECIMAL(26,8) DEFAULT 0,
    `total` DECIMAL(26,8) DEFAULT 0,
    `total_tax` DECIMAL(26,8) DEFAULT 0,
    `order_notes` LONGTEXT,
    `date_created` DATETIME DEFAULT '0000-00-00 00:00:00',
    `date_modified` DATETIME DEFAULT '0000-00-00 00:00:00',
    `date_paid` DATETIME DEFAULT '0000-00-00 00:00:00',
    `date_completed` DATETIME DEFAULT '0000-00-00 00:00:00',
    `crypto_tx_hash` VARCHAR(150) DEFAULT '',
    `crypto_amount` DECIMAL(32,18) DEFAULT 0,
    `crypto_currency` VARCHAR(20) DEFAULT '',
    `card_payment_ref` VARCHAR(50) DEFAULT '',
    `card_payment_receipt_url` VARCHAR(255) DEFAULT '',
    `manual_verified_by` BIGINT(20) UNSIGNED DEFAULT 0,
    PRIMARY KEY (`order_id`),
    KEY `order_key` (`order_key`),
    KEY `user_id` (`user_id`),
    KEY `status` (`status`),
    KEY `date_created` (`date_created`)
) {$charset_collate};";

// ==============================================
// 2. Order Items Table
// ==============================================
$table_items = $wpdb->prefix . 'neomarket_order_items';
$sql_items = "CREATE TABLE {$table_items} (
    `order_item_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_item_name` LONGTEXT NOT NULL,
    `order_item_type` VARCHAR(30) NOT NULL DEFAULT 'line_item',
    `order_id` BIGINT(20) UNSIGNED NOT NULL,
    `product_id` BIGINT(20) UNSIGNED NOT NULL,
    `variation_id` BIGINT(20) UNSIGNED DEFAULT 0,
    `quantity` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `unit_price` DECIMAL(26,8) DEFAULT 0,
    `line_subtotal` DECIMAL(26,8) DEFAULT 0,
    `line_subtotal_tax` DECIMAL(26,8) DEFAULT 0,
    `line_total` DECIMAL(26,8) DEFAULT 0,
    `line_tax` DECIMAL(26,8) DEFAULT 0,
    `tax_class` VARCHAR(40) DEFAULT '',
    PRIMARY KEY (`order_item_id`),
    KEY `order_id` (`order_id`),
    KEY `product_id` (`product_id`)
) {$charset_collate};";

// ==============================================
// 3. Carts Table (Persistent)
// ==============================================
$table_carts = $wpdb->prefix . 'neomarket_cart';
$sql_carts = "CREATE TABLE {$table_carts} (
    `cart_key` VARCHAR(60) NOT NULL,
    `user_id` BIGINT(20) UNSIGNED DEFAULT 0,
    `cart_contents` LONGTEXT,
    `cart_totals` LONGTEXT,
    `session_data` LONGTEXT,
    `created_at` DATETIME DEFAULT '0000-00-00 00:00:00',
    `updated_at` DATETIME DEFAULT '0000-00-00 00:00:00',
    `expires_at` DATETIME DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`cart_key`),
    KEY `user_id` (`user_id`),
    KEY `expires_at` (`expires_at`)
) {$charset_collate};";

// ==============================================
// 4. Transactions / Gateway Logs Table
// ==============================================
$table_transactions = $wpdb->prefix . 'neomarket_transactions';
$sql_transactions = "CREATE TABLE {$table_transactions} (
    `txn_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT(20) UNSIGNED NOT NULL,
    `gateway_id` VARCHAR(50) NOT NULL,
    `txn_type` VARCHAR(30) DEFAULT 'payment',
    `txn_status` VARCHAR(30) DEFAULT 'pending',
    `gateway_txn_id` VARCHAR(200) DEFAULT '',
    `gateway_response` LONGTEXT,
    `amount` DECIMAL(26,8) DEFAULT 0,
    `currency` VARCHAR(5) DEFAULT 'IRT',
    `ip_address` VARCHAR(40) DEFAULT '',
    `created_at` DATETIME DEFAULT '0000-00-00 00:00:00',
    `updated_at` DATETIME DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`txn_id`),
    KEY `order_id` (`order_id`),
    KEY `gateway_id` (`gateway_id`)
) {$charset_collate};";

// Execute all
NeoMarket_Migrator::delta($sql_orders);
NeoMarket_Migrator::delta($sql_items);
NeoMarket_Migrator::delta($sql_carts);
NeoMarket_Migrator::delta($sql_transactions);

// Cleanup expired carts
$wpdb->query("DELETE FROM {$table_carts} WHERE expires_at < NOW() AND expires_at <> '0000-00-00 00:00:00'");
