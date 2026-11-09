<?php
/**
 * Helper Logic for NeoMarket
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render Related Products
 */
function neomarket_render_related_products($product_id) {
    $cats = wp_get_post_terms($product_id, 'neo_product_cat', array('fields' => 'ids'));
    
    $args = array(
        'post_type'      => 'neo_product',
        'posts_per_page' => 4,
        'post__not_in'   => array($product_id),
        'tax_query'      => array(
            array(
                'taxonomy' => 'neo_product_cat',
                'field'    => 'id',
                'terms'    => $cats,
            ),
        ),
    );

    $related = new WP_Query($args);

    if ($related->have_posts()) : ?>
        <div class="neomarket-related-grid">
            <?php while ($related->have_posts()) : $related->the_post(); ?>
                <div class="neomarket-product-card mini" data-id="<?php the_ID(); ?>">
                    <?php if (has_post_thumbnail()) the_post_thumbnail('thumbnail'); ?>
                    <h4><?php the_title(); ?></h4>
                    <button class="view-product-btn button-small" data-id="<?php the_ID(); ?>"><?php _e('View', 'neomarket'); ?></button>
                </div>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    <?php else : ?>
        <p><?php _e('No related products found.', 'neomarket'); ?></p>
    <?php endif;
}

/**
 * Render Verified Reviews
 */
function neomarket_render_verified_reviews($product_id) {
    $user_id = get_current_user_id();
    $can_review = neomarket_user_can_review($user_id, $product_id);

    echo '<div class="neomarket-reviews-section">';
    
    if ($can_review) {
        // Render Review Form
        ?>
        <div class="neomarket-review-form">
            <h4><?php _e('Write a Review', 'neomarket'); ?></h4>
            <form id="neomarket-submit-review">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                <div class="rating-input">
                    <label><?php _e('Rating:', 'neomarket'); ?></label>
                    <select name="rating">
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="2">2 Stars</option>
                        <option value="1">1 Star</option>
                    </select>
                </div>
                <textarea name="review_content" placeholder="<?php _e('Your review...', 'neomarket'); ?>" required></textarea>
                <button type="submit" class="button-primary"><?php _e('Submit Review', 'neomarket'); ?></button>
            </form>
        </div>
        <?php
    } else {
        echo '<p class="review-notice">' . __('Only verified purchasers can leave a review.', 'neomarket') . '</p>';
    }

    // List Existing Reviews
    $comments = get_comments(array(
        'post_id' => $product_id,
        'status'  => 'approve'
    ));

    if ($comments) {
        echo '<ul class="neomarket-comments-list">';
        foreach ($comments as $comment) {
            $rating = get_comment_meta($comment->comment_ID, 'rating', true);
            ?>
            <li class="neomarket-comment">
                <div class="comment-header">
                    <strong><?php echo esc_html($comment->comment_author); ?></strong>
                    <span class="verified-badge"><?php _e('(Verified Purchase)', 'neomarket'); ?></span>
                    <span class="rating-stars"><?php echo str_repeat('★', intval($rating)); ?></span>
                </div>
                <div class="comment-content"><?php echo wpautop(esc_html($comment->comment_content)); ?></div>
            </li>
            <?php
        }
        echo '</ul>';
    } else {
        echo '<p>' . __('No reviews yet.', 'neomarket') . '</p>';
    }

    echo '</div>';
}

/**
 * Check if user can review a product
 * (Placeholder logic: in a real store, we would check if the user has a completed order for this product)
 */
function neomarket_user_can_review($user_id, $product_id) {
    if (!$user_id) return false;

    // For demonstration, let's assume if they are logged in, they can review 
    // BUT we will add a filter here for real order integration later
    $has_purchased = apply_filters('neomarket_user_has_purchased', false, $user_id, $product_id);
    
    // If you want to allow admins to review for testing:
    if (current_user_can('manage_options')) return true;

    return $has_purchased;
}
