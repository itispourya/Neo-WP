<?php
/**
 * Admin Hooks for NeoMarket
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add custom columns to Product list
add_filter('manage_neo_product_posts_columns', 'neomarket_set_product_columns');
add_action('manage_neo_product_posts_custom_column', 'neomarket_custom_product_column', 10, 2);

function neomarket_set_product_columns($columns) {
    $new_columns = array();
    foreach($columns as $key => $value) {
        if ($key === 'date') {
            $new_columns['sku'] = __('SKU', 'neomarket');
            $new_columns['price'] = __('Price', 'neomarket');
        }
        $new_columns[$key] = $value;
    }
    return $new_columns;
}

function neomarket_custom_product_column($column, $post_id) {
    switch ($column) {
        case 'sku':
            echo esc_html(get_post_meta($post_id, '_sku', true));
            break;
        case 'price':
            $regular = get_post_meta($post_id, '_regular_price', true);
            $sale = get_post_meta($post_id, '_sale_price', true);
            if ($sale) {
                echo '<del>' . number_format($regular) . '</del> <strong>' . number_format($sale) . '</strong>';
            } else {
                echo number_format($regular);
            }
            break;
    }
}
