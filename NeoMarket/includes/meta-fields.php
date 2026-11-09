<?php
/**
 * Product Meta Fields and Metaboxes for NeoMarket
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', 'neomarket_add_product_metaboxes');
add_action('save_post_neo_product', 'neomarket_save_product_meta');
add_action('admin_enqueue_scripts', 'neomarket_admin_scripts');

/**
 * Enqueue scripts for admin metaboxes
 */
function neomarket_admin_scripts($hook) {
    global $post;
    if ($hook == 'post-new.php' || $hook == 'post.php') {
        if ($post->post_type === 'neo_product') {
            wp_enqueue_media();
            wp_enqueue_style('neomarket-admin-style', NEOMARKET_URL . 'assets/css/neomarket-admin.css', array(), NEOMARKET_VERSION);
            wp_enqueue_script('neomarket-admin-script', NEOMARKET_URL . 'assets/js/neomarket-admin.js', array('jquery'), NEOMARKET_VERSION, true);
            
            wp_localize_script('neomarket-admin-script', 'neomarket_admin', array(
                'gallery_title' => __('Select or Upload Images', 'neomarket'),
                'gallery_button' => __('Add to Gallery', 'neomarket')
            ));
        }
    }
}

/**
 * Add metaboxes to Product CPT
 */
function neomarket_add_product_metaboxes() {
    add_meta_box(
        'neomarket_product_data',
        __('Product Data', 'neomarket'),
        'neomarket_render_product_data_metabox',
        'neo_product',
        'normal',
        'high'
    );

    add_meta_box(
        'neomarket_product_gallery',
        __('Product Gallery', 'neomarket'),
        'neomarket_render_gallery_metabox',
        'neo_product',
        'side',
        'low'
    );
}

/**
 * Render Product Data Metabox
 */
function neomarket_render_product_data_metabox($post) {
    wp_nonce_field('neomarket_product_meta_nonce', 'neomarket_product_meta_nonce_field');

    $regular_price = get_post_meta($post->ID, '_regular_price', true);
    $sale_price = get_post_meta($post->ID, '_sale_price', true);
    $sku = get_post_meta($post->ID, '_sku', true);
    $stock_status = get_post_meta($post->ID, '_stock_status', true);
    $specifications = get_post_meta($post->ID, '_specifications', true);
    if (!is_array($specifications)) {
        $specifications = array();
    }
    ?>
    <div class="neomarket-metabox-wrapper">
        <div class="neomarket-field-group">
            <label for="neomarket_sku"><?php _e('SKU', 'neomarket'); ?></label>
            <input type="text" id="neomarket_sku" name="neomarket_sku" value="<?php echo esc_attr($sku); ?>" class="widefat">
        </div>

        <div class="neomarket-field-row">
            <div class="neomarket-field-group">
                <label for="neomarket_regular_price"><?php _e('Regular Price', 'neomarket'); ?></label>
                <input type="number" step="0.01" id="neomarket_regular_price" name="neomarket_regular_price" value="<?php echo esc_attr($regular_price); ?>">
            </div>
            <div class="neomarket-field-group">
                <label for="neomarket_sale_price"><?php _e('Sale Price', 'neomarket'); ?></label>
                <input type="number" step="0.01" id="neomarket_sale_price" name="neomarket_sale_price" value="<?php echo esc_attr($sale_price); ?>">
            </div>
        </div>

        <div class="neomarket-field-group">
            <label for="neomarket_stock_status"><?php _e('Stock Status', 'neomarket'); ?></label>
            <select id="neomarket_stock_status" name="neomarket_stock_status" class="widefat">
                <option value="instock" <?php selected($stock_status, 'instock'); ?>><?php _e('In Stock', 'neomarket'); ?></option>
                <option value="outofstock" <?php selected($stock_status, 'outofstock'); ?>><?php _e('Out of Stock', 'neomarket'); ?></option>
                <option value="onbackorder" <?php selected($stock_status, 'onbackorder'); ?>><?php _e('On Backorder', 'neomarket'); ?></option>
            </select>
        </div>

        <div class="neomarket-field-group">
            <label><?php _e('Detailed Specifications', 'neomarket'); ?></label>
            <div id="neomarket-specs-repeater">
                <?php foreach ($specifications as $key => $spec) : ?>
                    <div class="neomarket-spec-item">
                        <input type="text" name="neomarket_spec_label[]" placeholder="<?php _e('Label (e.g. Color)', 'neomarket'); ?>" value="<?php echo esc_attr($spec['label']); ?>">
                        <input type="text" name="neomarket_spec_value[]" placeholder="<?php _e('Value (e.g. Red)', 'neomarket'); ?>" value="<?php echo esc_attr($spec['value']); ?>">
                        <button type="button" class="button remove-spec"><?php _e('Remove', 'neomarket'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-spec" class="button"><?php _e('Add Specification', 'neomarket'); ?></button>
        </div>
    </div>
    <style>
        .neomarket-field-group { margin-bottom: 15px; }
        .neomarket-field-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .neomarket-field-row { display: flex; gap: 20px; }
        .neomarket-field-row .neomarket-field-group { flex: 1; }
        .neomarket-spec-item { display: flex; gap: 10px; margin-bottom: 10px; }
        .neomarket-spec-item input { flex: 1; }
    </style>
    <?php
}

/**
 * Render Gallery Metabox
 */
function neomarket_render_gallery_metabox($post) {
    $gallery = get_post_meta($post->ID, '_product_gallery', true);
    $gallery_ids = !empty($gallery) ? explode(',', $gallery) : array();
    ?>
    <div id="neomarket-gallery-container">
        <ul id="neomarket-gallery-list" style="display: flex; flex-wrap: wrap; gap: 5px; list-style: none; padding: 0;">
            <?php foreach ($gallery_ids as $attachment_id) : 
                $img_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
                if ($img_url) : ?>
                    <li data-id="<?php echo $attachment_id; ?>" style="position: relative;">
                        <img src="<?php echo $img_url; ?>" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #ddd;">
                        <a href="#" class="remove-gallery-img" style="position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; width: 15px; height: 15px; text-align: center; line-height: 15px; font-size: 10px; text-decoration: none;">&times;</a>
                    </li>
                <?php endif;
            endforeach; ?>
        </ul>
        <input type="hidden" id="neomarket_product_gallery" name="neomarket_product_gallery" value="<?php echo esc_attr($gallery); ?>">
        <button type="button" id="neomarket-add-gallery" class="button widefat"><?php _e('Add Images', 'neomarket'); ?></button>
    </div>
    <?php
}

/**
 * Save Product Meta
 */
function neomarket_save_product_meta($post_id) {
    if (!isset($_POST['neomarket_product_meta_nonce_field']) || !wp_verify_nonce($_POST['neomarket_product_meta_nonce_field'], 'neomarket_product_meta_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save Price and SKU
    if (isset($_POST['neomarket_regular_price'])) {
        update_post_meta($post_id, '_regular_price', sanitize_text_field($_POST['neomarket_regular_price']));
    }
    if (isset($_POST['neomarket_sale_price'])) {
        update_post_meta($post_id, '_sale_price', sanitize_text_field($_POST['neomarket_sale_price']));
    }
    if (isset($_POST['neomarket_sku'])) {
        update_post_meta($post_id, '_sku', sanitize_text_field($_POST['neomarket_sku']));
    }
    if (isset($_POST['neomarket_stock_status'])) {
        update_post_meta($post_id, '_stock_status', sanitize_text_field($_POST['neomarket_stock_status']));
    }

    // Save Specifications
    if (isset($_POST['neomarket_spec_label']) && isset($_POST['neomarket_spec_value'])) {
        $labels = $_POST['neomarket_spec_label'];
        $values = $_POST['neomarket_spec_value'];
        $specs = array();
        for ($i = 0; $i < count($labels); $i++) {
            if (!empty($labels[$i])) {
                $specs[] = array(
                    'label' => sanitize_text_field($labels[$i]),
                    'value' => sanitize_text_field($values[$i])
                );
            }
        }
        update_post_meta($post_id, '_specifications', $specs);
    }

    // Save Gallery
    if (isset($_POST['neomarket_product_gallery'])) {
        update_post_meta($post_id, '_product_gallery', sanitize_text_field($_POST['neomarket_product_gallery']));
    }
}
