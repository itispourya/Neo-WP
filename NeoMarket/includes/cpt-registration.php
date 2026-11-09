<?php
/**
 * Register Custom Post Type and Taxonomies for NeoMarket
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'neomarket_register_cpt_and_taxonomies');

function neomarket_register_cpt_and_taxonomies() {
    // Labels for Product CPT
    $product_labels = array(
        'name'               => _x('Products', 'post type general name', 'neomarket'),
        'singular_name'      => _x('Product', 'post type singular name', 'neomarket'),
        'menu_name'          => _x('Products', 'admin menu', 'neomarket'),
        'name_admin_bar'     => _x('Product', 'add new on admin bar', 'neomarket'),
        'add_new'            => _x('Add New', 'product', 'neomarket'),
        'add_new_item'       => __('Add New Product', 'neomarket'),
        'new_item'           => __('New Product', 'neomarket'),
        'edit_item'          => __('Edit Product', 'neomarket'),
        'view_item'          => __('View Product', 'neomarket'),
        'all_items'          => __('All Products', 'neomarket'),
        'search_items'       => __('Search Products', 'neomarket'),
        'parent_item_colon'  => __('Parent Products:', 'neomarket'),
        'not_found'          => __('No products found.', 'neomarket'),
        'not_found_in_trash' => __('No products found in Trash.', 'neomarket')
    );

    $product_args = array(
        'labels'             => $product_labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => 'neonotion', // Register under shared menu
        'query_var'          => true,
        'rewrite'            => array('slug' => 'product'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'comments'),
        'show_in_rest'       => true,
    );

    register_post_type('neo_product', $product_args);

    // Labels for Category Taxonomy
    $cat_labels = array(
        'name'              => _x('Product Categories', 'taxonomy general name', 'neomarket'),
        'singular_name'     => _x('Product Category', 'taxonomy singular name', 'neomarket'),
        'search_items'      => __('Search Categories', 'neomarket'),
        'all_items'         => __('All Categories', 'neomarket'),
        'parent_item'       => __('Parent Category', 'neomarket'),
        'parent_item_colon' => __('Parent Category:', 'neomarket'),
        'edit_item'         => __('Edit Category', 'neomarket'),
        'update_item'       => __('Update Category', 'neomarket'),
        'add_new_item'      => __('Add New Category', 'neomarket'),
        'new_item_name'     => __('New Category Name', 'neomarket'),
        'menu_name'         => __('Categories', 'neomarket'),
    );

    $cat_args = array(
        'hierarchical'      => true,
        'labels'            => $cat_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'product-category'),
        'show_in_rest'      => true,
    );

    register_taxonomy('neo_product_cat', array('neo_product'), $cat_args);

    // Labels for Tag Taxonomy
    $tag_labels = array(
        'name'                       => _x('Product Tags', 'taxonomy general name', 'neomarket'),
        'singular_name'              => _x('Product Tag', 'taxonomy singular name', 'neomarket'),
        'search_items'               => __('Search Tags', 'neomarket'),
        'popular_items'              => __('Popular Tags', 'neomarket'),
        'all_items'                  => __('All Tags', 'neomarket'),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __('Edit Tag', 'neomarket'),
        'update_item'                => __('Update Tag', 'neomarket'),
        'add_new_item'               => __('Add New Tag', 'neomarket'),
        'new_item_name'              => __('New Tag Name', 'neomarket'),
        'separate_items_with_commas' => __('Separate tags with commas', 'neomarket'),
        'add_or_remove_items'        => __('Add or remove tags', 'neomarket'),
        'choose_from_most_used'      => __('Choose from the most used tags', 'neomarket'),
        'not_found'                  => __('No tags found.', 'neomarket'),
        'menu_name'                  => __('Tags', 'neomarket'),
    );

    $tag_args = array(
        'hierarchical'          => false,
        'labels'                => $tag_labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var'             => true,
        'rewrite'               => array('slug' => 'product-tag'),
        'show_in_rest'          => true,
    );

    register_taxonomy('neo_product_tag', array('neo_product'), $tag_args);

    // ============== Order CPT ==============
    $order_labels = array(
        'name'               => _x('Orders', 'post type general name', 'neomarket'),
        'singular_name'      => _x('Order', 'post type singular name', 'neomarket'),
        'menu_name'          => _x('Orders', 'admin menu', 'neomarket'),
        'add_new'            => _x('Add New', 'order', 'neomarket'),
        'add_new_item'       => __('Add New Order', 'neomarket'),
        'new_item'           => __('New Order', 'neomarket'),
        'edit_item'          => __('Edit Order', 'neomarket'),
        'view_item'          => __('View Order', 'neomarket'),
        'all_items'          => __('All Orders', 'neomarket'),
        'search_items'       => __('Search Orders', 'neomarket'),
        'not_found'          => __('No orders found.', 'neomarket'),
    );

    $order_args = array(
        'labels'        => $order_labels,
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => 'neonotion',
        'menu_icon'     => 'dashicons-cart',
        'capability_type' => 'post',
        'hierarchical'  => false,
        'supports'      => array('title'),
        'has_archive'   => false,
    );
    register_post_type('neo_order', $order_args);
}

// Register custom order statuses on 'init' later (via register_post_status)
add_action('init', function() {
    $stati = array(
        'wc-pending'    => _x('Pending Payment', 'Order status', 'neomarket'),
        'wc-processing' => _x('Processing',      'Order status', 'neomarket'),
        'wc-on-hold'    => _x('On Hold',         'Order status', 'neomarket'),
        'wc-completed'  => _x('Completed',       'Order status', 'neomarket'),
        'wc-cancelled'  => _x('Cancelled',       'Order status', 'neomarket'),
        'wc-refunded'   => _x('Refunded',        'Order status', 'neomarket'),
        'wc-failed'     => _x('Failed',          'Order status', 'neomarket'),
    );
    foreach ($stati as $k => $label) {
        register_post_status($k, array(
            'label'                     => $label,
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('%s <span class="count">(%s)</span>', '%s <span class="count">(%s)</span>'),
        ));
    }
}, 99);
