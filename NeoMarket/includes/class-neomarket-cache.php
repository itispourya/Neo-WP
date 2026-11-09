<?php
/**
 * Caching Layer: Transient + Object Cache helpers
 */

if (!defined('ABSPATH')) {
    exit;
}

class NeoMarket_Cache {

    const GROUP = 'neomarket';
    const DEFAULT_TTL = 3600; // 1 hour

    /**
     * Get cached value
     */
    public static function get($key) {
        $result = wp_cache_get($key, self::GROUP);
        if (false === $result) {
            $transient_key = self::transient_key($key);
            $result = get_transient($transient_key);
            if (false !== $result) {
                wp_cache_set($key, $result, self::GROUP, self::DEFAULT_TTL);
            }
        }
        return $result;
    }

    /**
     * Set cache value with TTL
     */
    public static function set($key, $value, $ttl = self::DEFAULT_TTL) {
        wp_cache_set($key, $value, self::GROUP, $ttl);
        set_transient(self::transient_key($key), $value, $ttl);
    }

    /**
     * Delete cache
     */
    public static function delete($key) {
        wp_cache_delete($key, self::GROUP);
        delete_transient(self::transient_key($key));
    }

    /**
     * Flush all cache group
     * (Since WordPress doesn't support group flushing natively, we bump a version salt)
     */
    public static function flush_group() {
        $version = get_option('neomarket_cache_version', 1);
        update_option('neomarket_cache_version', ++$version);
    }

    private static function transient_key($key) {
        $version = get_option('neomarket_cache_version', 1);
        return 'neomarket_' . $version . '_' . substr(md5($key), 0, 20);
    }

    // =================================================
    // Helper: Cached Query builders
    // =================================================
    public static function get_cached_products($args, $force = false) {
        $hash = 'products_' . md5(serialize($args));
        if (!$force && ($cached = self::get($hash))) {
            return $cached;
        }
        $query = new WP_Query($args);
        $data = array(
            'posts'         => $query->posts,
            'found_posts'   => $query->found_posts,
            'max_num_pages' => $query->max_num_pages,
        );
        self::set($hash, $data, 1800);
        return $data;
    }

    public static function get_cached_related($product_id) {
        $hash = 'related_' . $product_id;
        if (false !== ($cached = self::get($hash))) {
            return $cached;
        }
        ob_start();
        neomarket_render_related_products($product_id);
        $html = ob_get_clean();
        self::set($hash, $html, 7200);
        return $html;
    }
}

// Invalidate on save
add_action('save_post_neo_product', array('NeoMarket_Cache', 'flush_group'));
add_action('neomarket_order_status_completed', array('NeoMarket_Cache', 'flush_group'));
add_action('edit_term', array('NeoMarket_Cache', 'flush_group'));
