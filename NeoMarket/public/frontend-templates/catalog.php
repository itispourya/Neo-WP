<?php
/**
 * Catalog Template - With noUiSlider, Wishlist / Compare / Quick View action icons
 */

if (!defined('ABSPATH')) {
    exit;
}
global $query;

if (!isset($query)) {
    $paged = max(1, intval($_POST['paged'] ?? 1));
    $query = new WP_Query(array(
        'post_type'      => 'neo_product',
        'posts_per_page' => 12,
        'paged'          => $paged,
        'post_status'    => 'publish',
    ));
}
?>

<div class="neomarket-topbar" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <h2 style="margin:0;"><?php _e('Product Catalog', 'neomarket'); ?></h2>
    <div class="neomarket-nav-buttons">
        <button class="button" data-view="wishlist">💟 <?php _e('Wishlist', 'neomarket'); ?> <span class="neomarket-wishlist-count">0</span></button>
        <button class="button" data-view="compare">⚖️ <?php _e('Compare', 'neomarket'); ?> <span class="neomarket-compare-count">0</span></button>
        <button class="button" data-view="cart">🛒 <?php _e('Cart', 'neomarket'); ?> (<span class="neomarket-cart-count">0</span>) <span class="neomarket-cart-total"></span></button>
    </div>
</div>

<div class="neomarket-store-layout">
    <aside class="neomarket-sidebar">
        <div class="neomarket-filter-widget">
            <h3><?php _e('Search', 'neomarket'); ?></h3>
            <input type="text" id="neomarket-search-input" class="widefat" placeholder="<?php _e('Search products...', 'neomarket'); ?>">
        </div>

        <div class="neomarket-filter-widget">
            <h3><?php _e('Categories', 'neomarket'); ?></h3>
            <ul class="neomarket-cat-list">
                <li><a href="#" data-cat=""><?php _e('All Categories', 'neomarket'); ?></a></li>
                <?php
                $categories = get_terms(array('taxonomy' => 'neo_product_cat', 'hide_empty' => true, 'parent' => 0));
                if (!is_wp_error($categories)) {
                    foreach ($categories as $cat) {
                        echo '<li class="parent-cat"><a href="#" data-cat="' . esc_attr($cat->slug) . '"><strong>' . esc_html($cat->name) . '</strong></a>';
                        $children = get_terms(array('taxonomy' => 'neo_product_cat', 'hide_empty' => true, 'parent' => $cat->term_id));
                        if (!is_wp_error($children) && $children) {
                            echo '<ul class="child-cats" style="margin-right:15px;">';
                            foreach ($children as $ch) {
                                echo '<li><a href="#" data-cat="' . esc_attr($ch->slug) . '">' . esc_html($ch->name) . '</a></li>';
                            }
                            echo '</ul>';
                        }
                        echo '</li>';
                    }
                }
                ?>
            </ul>
        </div>

        <div class="neomarket-filter-widget">
            <h3><?php _e('Price Range (Toman)', 'neomarket'); ?></h3>
            <div id="neomarket-price-slider" style="margin:20px 10px;"></div>
            <div style="display:flex;justify-content:space-between;margin-top:10px;font-size:12px;color:#666;">
                <span id="price-min-display">0</span>
                <span id="price-max-display">50,000,000 تومان</span>
            </div>
            <div style="display:none;">
                <input id="price-min" type="number" placeholder="Min">
                <input id="price-max" type="number" placeholder="Max">
                <button id="apply-price-filter" class="button small"><?php _e('Apply', 'neomarket'); ?></button>
            </div>
        </div>

        <div style="margin-top:20px;text-align:center;">
            <button id="nmk-theme-toggle" class="button" style="width:100%;">🌓 <?php _e('Toggle Dark Mode', 'neomarket'); ?></button>
        </div>
    </aside>

    <main class="neomarket-main-content">
        <?php if ($query->have_posts()) : ?>
            <div class="neomarket-product-grid">
                <?php while ($query->have_posts()) : $query->the_post();
                    $product_id = get_the_ID();
                    $regular_price = (float) get_post_meta($product_id, '_regular_price', true);
                    $sale_price = (float) get_post_meta($product_id, '_sale_price', true);
                    $stock_status = get_post_meta($product_id, '_stock_status', true);
                    ?>
                    <div class="neomarket-product-card" data-id="<?php echo $product_id; ?>">
                        <div class="product-thumbnail-wrap" style="position:relative;">
                            <div class="product-thumbnail">
                                <?php if (has_post_thumbnail()) : the_post_thumbnail('medium'); else : ?>
                                    <img src="https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=e-commerce-product-placeholder&image_size=square" alt="Placeholder">
                                <?php endif; ?>
                            </div>
                            <div class="card-actions" style="position:absolute;top:10px;left:10px;display:flex;flex-direction:column;gap:5px;">
                                <button type="button" class="add-to-wishlist button-mini" data-id="<?php echo $product_id; ?>" title="Wishlist">💟</button>
                                <button type="button" class="add-to-compare button-mini" data-id="<?php echo $product_id; ?>" title="Compare">⚖️</button>
                                <button type="button" class="quick-view-btn button-mini" data-id="<?php echo $product_id; ?>" title="Quick View">👁</button>
                            </div>
                            <?php if ($sale_price) :
                                $percent = round((($regular_price - $sale_price) / max(1, $regular_price)) * 100);
                                echo '<span class="sale-badge" style="position:absolute;top:10px;right:10px;background:#ef394e;color:#fff;padding:2px 8px;border-radius:999px;font-size:12px;">%' . $percent . ' ' . __('OFF', 'neomarket') . '</span>';
                            endif; ?>
                        </div>
                        <div class="product-info">
                            <h2 class="product-title"><?php the_title(); ?></h2>
                            <div class="product-price">
                                <?php if ($sale_price) : ?>
                                    <span class="sale-price"><?php echo number_format($sale_price); ?> تومان</span>
                                    <span class="regular-price-crossed"><?php echo number_format($regular_price); ?></span>
                                <?php else : ?>
                                    <span class="regular-price"><?php echo number_format($regular_price); ?> تومان</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-bottom" style="display:flex;gap:5px;margin-top:10px;">
                                <button class="view-product-btn button" style="flex:1;" data-id="<?php echo $product_id; ?>"><?php _e('Details', 'neomarket'); ?></button>
                                <button class="add-to-cart-btn button-primary" style="flex:1;" data-product_id="<?php echo $product_id; ?>">🛒 <?php _e('Add', 'neomarket'); ?></button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>

            <div class="neomarket-pagination" style="margin-top:30px;text-align:center;">
                <?php
                echo paginate_links(array(
                    'total'   => $query->max_num_pages,
                    'current' => intval($_POST['paged'] ?? 1),
                    'format'  => '?paged=%#%',
                ));
                ?>
            </div>
        <?php else : ?>
            <p class="no-products" style="padding:60px;text-align:center;background:#fff;border-radius:8px;"><?php _e('No products found matching your criteria.', 'neomarket'); ?></p>
        <?php endif; ?>
    </main>
</div>
