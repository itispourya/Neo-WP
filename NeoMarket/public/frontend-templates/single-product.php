<?php
/**
 * Single Product Template for NeoMarket
 */

if (!defined('ABSPATH')) {
    exit;
}

$product_id = get_the_ID();
$regular_price = get_post_meta($product_id, '_regular_price', true);
$sale_price = get_post_meta($product_id, '_sale_price', true);
$sku = get_post_meta($product_id, '_sku', true);
$stock_status = get_post_meta($product_id, '_stock_status', true);
$gallery = get_post_meta($product_id, '_product_gallery', true);
$gallery_ids = !empty($gallery) ? explode(',', $gallery) : array();
$specifications = get_post_meta($product_id, '_specifications', true);
?>

<div class="neomarket-single-product">
    <button class="back-to-catalog button"><?php _e('&larr; Back to Catalog', 'neomarket'); ?></button>

    <div class="product-header">
        <!-- Media Gallery -->
        <div class="product-gallery">
            <div class="swiper neomarket-main-slider">
                <div class="swiper-wrapper">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="swiper-slide">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($gallery_ids as $attachment_id) : ?>
                        <div class="swiper-slide">
                            <?php echo wp_get_attachment_image($attachment_id, 'large'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
            
            <div class="swiper neomarket-thumbs-slider" style="margin-top: 10px;">
                <div class="swiper-wrapper">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="swiper-slide">
                            <?php the_post_thumbnail('thumbnail'); ?>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($gallery_ids as $attachment_id) : ?>
                        <div class="swiper-slide">
                            <?php echo wp_get_attachment_image($attachment_id, 'thumbnail'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Product Details -->
        <div class="product-main-info">
            <h1 class="product-title"><?php the_title(); ?></h1>
            <p class="product-sku"><?php _e('SKU:', 'neomarket'); ?> <span><?php echo esc_html($sku); ?></span></p>
            
            <div class="product-price-box">
                <?php if ($sale_price) : ?>
                    <span class="sale-price"><?php echo number_format($sale_price); ?></span>
                    <span class="regular-price-crossed"><?php echo number_format($regular_price); ?></span>
                <?php else : ?>
                    <span class="regular-price"><?php echo number_format($regular_price); ?></span>
                <?php endif; ?>
            </div>

            <div class="product-stock-status <?php echo esc_attr($stock_status); ?>">
                <?php 
                if ($stock_status === 'instock') _e('In Stock', 'neomarket');
                elseif ($stock_status === 'outofstock') _e('Out of Stock', 'neomarket');
                else _e('On Backorder', 'neomarket');
                ?>
            </div>

            <div class="product-excerpt">
                <?php the_excerpt(); ?>
            </div>

            <button class="add-to-cart-btn button-primary"><?php _e('Add to Cart', 'neomarket'); ?></button>
        </div>
    </div>

    <!-- Tabs: Description & Specs -->
    <div class="product-tabs">
        <ul class="tab-nav">
            <li class="active" data-tab="description"><?php _e('Description', 'neomarket'); ?></li>
            <li data-tab="specs"><?php _e('Specifications', 'neomarket'); ?></li>
            <li data-tab="reviews"><?php _e('Reviews', 'neomarket'); ?></li>
        </ul>
        <div class="tab-content">
            <div id="tab-description" class="tab-pane active">
                <?php the_content(); ?>
            </div>
            <div id="tab-specs" class="tab-pane">
                <table class="specs-table">
                    <?php if (!empty($specifications)) : ?>
                        <?php foreach ($specifications as $spec) : ?>
                            <tr>
                                <th><?php echo esc_html($spec['label']); ?></th>
                                <td><?php echo esc_html($spec['value']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p><?php _e('No specifications available.', 'neomarket'); ?></p>
                    <?php endif; ?>
                </table>
            </div>
            <div id="tab-reviews" class="tab-pane">
                <?php neomarket_render_verified_reviews($product_id); ?>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <div class="related-products">
        <h3><?php _e('Related Products', 'neomarket'); ?></h3>
        <?php neomarket_render_related_products($product_id); ?>
    </div>
</div>
