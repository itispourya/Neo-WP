<?php
/**
 * Wishlist Template - Pulls from localStorage via AJAX
 */
if (!defined('ABSPATH')) exit;
?>
<h2>💟 <?php _e('My Wishlist', 'neomarket'); ?></h2>
<a class="back-to-catalog button" style="margin-bottom:20px;">← <?php _e('Back to Catalog', 'neomarket'); ?></a>

<div id="neomarket-wishlist-host" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:20px;">
    <p id="wishlist-empty" style="grid-column:1/-1;padding:60px;text-align:center;background:#fff;border-radius:8px;"><?php _e('Wishlist is empty. Add products from the catalog!', 'neomarket'); ?></p>
</div>

<script>
jQuery(function($) {
    var ids = JSON.parse(localStorage.getItem('neomarket_wishlist') || '[]');
    var $host = $('#neomarket-wishlist-host');
    var $empty = $('#wishlist-empty');

    if (ids.length === 0) return;

    // Fetch each product via REST
    Promise.all(ids.map(function(id) {
        return fetch(wpApiSettings.root + 'neomarket/v1/products/' + id, {headers:{'X-WP-Nonce':wpApiSettings.nonce}}).then(r=>r.json());
    })).then(function(products) {
        products.forEach(function(p) {
            if (!p.id) return;
            $host.append(
                '<div class="neomarket-product-card" data-id="' + p.id + '">' +
                    '<div class="product-thumbnail-wrap" style="position:relative;">' +
                        '<div class="product-thumbnail"><img src="' + (p.thumbnail || '') + '" style="width:100%;height:200px;object-fit:cover;"/></div>' +
                    '</div>' +
                    '<div class="product-info">' +
                        '<h2 class="product-title" style="font-size:14px;min-height:40px;">' + p.title + '</h2>' +
                        '<div class="product-price"><span class="regular-price">' + Number(p.sale_price || p.regular_price).toLocaleString('fa-IR') + ' تومان</span></div>' +
                        '<div style="display:flex;gap:5px;margin-top:10px;">' +
                            '<button class="view-product-btn button" style="flex:1;" data-id="' + p.id + '">جزئیات</button>' +
                            '<button class="add-to-cart-btn button-primary" style="flex:1;" data-product_id="' + p.id + '">افزودن</button>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
        });
        $empty.remove();
    });
});
</script>
