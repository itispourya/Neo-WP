<?php
/**
 * Compare Products Template
 */
if (!defined('ABSPATH')) exit;
?>
<h2>⚖️ <?php _e('Compare Products', 'neomarket'); ?></h2>
<a class="back-to-catalog button" style="margin-bottom:20px;">← <?php _e('Back to Catalog', 'neomarket'); ?></a>

<div id="neomarket-compare-host" style="overflow-x:auto;background:#fff;border-radius:8px;padding:20px;">
    <p id="compare-empty" style="padding:60px;text-align:center;"><?php _e('No products to compare yet. Choose up to 4 products from the catalog!', 'neomarket'); ?></p>
</div>

<script>
jQuery(function($) {
    var ids = JSON.parse(localStorage.getItem('neomarket_compare') || '[]');
    var $host = $('#neomarket-compare-host');
    var $empty = $('#compare-empty');
    if (ids.length === 0) return;

    Promise.all(ids.map(function(id) {
        return fetch(wpApiSettings.root + 'neomarket/v1/products/' + id, {headers:{'X-WP-Nonce':wpApiSettings.nonce}}).then(r=>r.json());
    })).then(function(products) {
        // Collect all attribute keys
        var labels = new Set();
        products.forEach(function(p) {
            if (!p.specifications) return;
            p.specifications.forEach(function(s) { labels.add(s.label); });
        });
        labels = Array.from(labels);

        var header = '<tr><th>ویژگی</th>';
        products.forEach(function(p) {
            header += '<th>' + p.title + '</th>';
        });
        header += '</tr>';

        var body = '<tr><td>تصویر</td>';
        products.forEach(function(p) {
            body += '<td><img src="' + (p.thumbnail || '') + '" style="width:150px;height:150px;object-fit:cover;border-radius:8px;"></td>';
        });
        body += '</tr>';

        body += '<tr><td>قیمت</td>';
        products.forEach(function(p) {
            body += '<td>' + Number(p.sale_price || p.regular_price).toLocaleString('fa-IR') + ' تومان</td>';
        });
        body += '</tr>';

        body += '<tr><td>SKU</td>';
        products.forEach(function(p) { body += '<td>' + (p.sku || '—') + '</td>'; });
        body += '</tr>';

        body += '<tr><td>وضعیت موجودی</td>';
        products.forEach(function(p) { body += '<td>' + (p.stock_status === 'instock' ? 'موجود' : 'ناموجود') + '</td>'; });
        body += '</tr>';

        labels.forEach(function(lbl) {
            body += '<tr><td>' + lbl + '</td>';
            products.forEach(function(p) {
                var val = '—';
                if (p.specifications) {
                    p.specifications.forEach(function(s) {
                        if (s.label === lbl) val = s.value;
                    });
                }
                body += '<td>' + val + '</td>';
            });
            body += '</tr>';
        });

        // Add actions row
        body += '<tr><td>عملیات</td>';
        products.forEach(function(p) {
            body += '<td>' +
                '<button class="add-to-cart-btn button-primary" data-product_id="' + p.id + '" style="width:100%;margin-bottom:5px;">افزودن به سبد</button>' +
                '<button class="view-product-btn button" data-id="' + p.id + '" style="width:100%;">جزئیات محصول</button>' +
                '</td>';
        });
        body += '</tr>';

        $host.html('<table class="neomarket-compare-table" style="width:100%;border-collapse:collapse;">' +
            '<thead>' + header + '</thead><tbody>' + body + '</tbody></table>');

        $host.find('th,td').css({padding:'12px',borderBottom:'1px solid #eee',textAlign:'center'});
        $host.find('th').css({background:'#fafafa',fontWeight:'bold'});
        $empty.remove();
    });
});
</script>
