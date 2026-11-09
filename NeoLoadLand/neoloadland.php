<?php
/**
 * Plugin Name: NeoLoadLand
 * Plugin URI: https://neonotion.net/
 * Description: Loading screen manager
 * Version: 7
 * Author: NeoNotion
 * Programmer: Pourya Omidian
 * Author URI: https://neonotion.net/
 * Text Domain: neoloadland
 */

if (!defined('ABSPATH')) {
    exit;
}

define('NEOLOADLAND_VERSION', '7');
define('NEOLOADLAND_FILE', __FILE__);
define('NEOLOADLAND_URL', plugin_dir_url(__FILE__));
define('NEOLOADLAND_PATH', plugin_dir_path(__FILE__));

if (!function_exists('neonotion_register_root_menu')) {
    function neonotion_register_root_menu() {
        static $registered = false;

        if ($registered) {
            return;
        }

        add_menu_page(
            'NeoNotion',
            'NeoNotion',
            'manage_options',
            'neonotion',
            'neonotion_render_root_page',
            'dashicons-superhero-alt',
            58
        );

        $registered = true;
    }
}

if (!function_exists('neonotion_render_root_page')) {
    function neonotion_render_root_page() {
        ?>
        <div class="wrap neo-admin-shell">
            <div class="neo-admin-card">
                <h1>NeoNotion</h1>
                <p>Use the menu items on the left to manage NeoNotion plugins.</p>
            </div>
        </div>
        <?php
    }
}

register_activation_hook(__FILE__, 'neoloadland_activate');

function neoloadland_default_settings() {
    return array(
        'enabled'          => 1,
        'media_url'        => '',
        'message'          => 'Please wait, loading...',
        'text_color'       => '#9dff7a',
        'font_family'      => '"Courier New", monospace',
        'font_size'        => 18,
        'media_width'      => 180,
        'background_image' => '',
        'background_color' => '#03070b',
        'overlay_opacity'  => 0.94,
        'vertical_align'   => 'center',
        'horizontal_align' => 'center',
        'offset_x'         => 0,
        'offset_y'         => 0,
    );
}

function neoloadland_activate() {
    add_option('neoloadland_settings', neoloadland_default_settings());
}

add_action('admin_menu', 'neoloadland_admin_menu');
function neoloadland_admin_menu() {
    neonotion_register_root_menu();

    add_submenu_page(
        'neonotion',
        'Loading land page',
        'Loading land page',
        'manage_options',
        'neoloadland',
        'neoloadland_render_admin_page'
    );
}

add_action('admin_enqueue_scripts', 'neoloadland_admin_assets');
function neoloadland_admin_assets($hook) {
    if (strpos($hook, 'neoloadland') === false && strpos($hook, 'neonotion') === false) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style('neoloadland-admin', NEOLOADLAND_URL . 'assets/css/admin.css', array('wp-color-picker'), NEOLOADLAND_VERSION);
    wp_enqueue_script('neoloadland-admin', NEOLOADLAND_URL . 'assets/js/neoloadland.js', array('jquery', 'wp-color-picker'), NEOLOADLAND_VERSION, true);
}

function neoloadland_get_content_posts() {
    $post_types = get_post_types(
        array(
            'public' => true,
        ),
        'names'
    );

    unset($post_types['attachment']);

    return get_posts(
        array(
            'post_type'      => array_values($post_types),
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'future', 'draft', 'private'),
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );
}

function neoloadland_sanitize_settings($input) {
    $defaults = neoloadland_default_settings();

    return array(
        'enabled'          => empty($input['enabled']) ? 0 : 1,
        'media_url'        => esc_url_raw($input['media_url'] ?? $defaults['media_url']),
        'message'          => sanitize_text_field($input['message'] ?? $defaults['message']),
        'text_color'       => sanitize_hex_color($input['text_color'] ?? $defaults['text_color']) ?: $defaults['text_color'],
        'font_family'      => sanitize_text_field($input['font_family'] ?? $defaults['font_family']),
        'font_size'        => max(10, absint($input['font_size'] ?? $defaults['font_size'])),
        'media_width'      => max(40, absint($input['media_width'] ?? $defaults['media_width'])),
        'background_image' => esc_url_raw($input['background_image'] ?? $defaults['background_image']),
        'background_color' => sanitize_hex_color($input['background_color'] ?? $defaults['background_color']) ?: $defaults['background_color'],
        'overlay_opacity'  => min(1, max(0, floatval($input['overlay_opacity'] ?? $defaults['overlay_opacity']))),
        'vertical_align'   => in_array(($input['vertical_align'] ?? ''), array('top', 'center', 'bottom'), true) ? $input['vertical_align'] : $defaults['vertical_align'],
        'horizontal_align' => in_array(($input['horizontal_align'] ?? ''), array('left', 'center', 'right'), true) ? $input['horizontal_align'] : $defaults['horizontal_align'],
        'offset_x'         => intval($input['offset_x'] ?? $defaults['offset_x']),
        'offset_y'         => intval($input['offset_y'] ?? $defaults['offset_y']),
    );
}

function neoloadland_sanitize_override($input) {
    return array(
        'mode'             => in_array(($input['mode'] ?? 'global'), array('global', 'custom', 'disabled'), true) ? $input['mode'] : 'global',
        'media_url'        => esc_url_raw($input['media_url'] ?? ''),
        'message'          => sanitize_text_field($input['message'] ?? ''),
        'vertical_align'   => in_array(($input['vertical_align'] ?? 'center'), array('top', 'center', 'bottom'), true) ? $input['vertical_align'] : 'center',
        'horizontal_align' => in_array(($input['horizontal_align'] ?? 'center'), array('left', 'center', 'right'), true) ? $input['horizontal_align'] : 'center',
        'offset_x'         => intval($input['offset_x'] ?? 0),
        'offset_y'         => intval($input['offset_y'] ?? 0),
    );
}

function neoloadland_render_field_row($label, $field) {
    echo '<div class="neo-grid-row">';
    echo '<label class="neo-label" for="' . esc_attr($field['id']) . '">' . esc_html($label) . '</label>';
    echo '<div class="neo-field-wrap">' . $field['html'] . '</div>';
    echo '</div>';
}

function neoloadland_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = wp_parse_args(get_option('neoloadland_settings', array()), neoloadland_default_settings());
    $notice   = '';

    if (!empty($_POST['neoloadland_save_settings']) && check_admin_referer('neoloadland_save_settings', 'neoloadland_nonce')) {
        $settings = neoloadland_sanitize_settings($_POST['settings'] ?? array());
        update_option('neoloadland_settings', $settings);

        $page_overrides = $_POST['page_overrides'] ?? array();
        $posts          = neoloadland_get_content_posts();

        foreach ($posts as $post) {
            if (isset($page_overrides[$post->ID])) {
                update_post_meta($post->ID, '_neoloadland_override', neoloadland_sanitize_override($page_overrides[$post->ID]));
            } else {
                delete_post_meta($post->ID, '_neoloadland_override');
            }
        }

        $notice = 'NeoLoadLand settings were updated successfully.';
    }

    $posts = neoloadland_get_content_posts();
    ?>
    <div class="wrap neo-admin-shell">
        <div class="neo-admin-card">
            <div class="neo-admin-heading">
                <div>
                    <h1>NeoLoadLand</h1>
                    <p>Build a polished loading screen with global controls and per-page overrides.</p>
                </div>
                <div class="neo-admin-badge">NeoNotion / Loading land page</div>
            </div>

            <?php if ($notice) : ?>
                <div class="neo-notice"><?php echo esc_html($notice); ?></div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('neoloadland_save_settings', 'neoloadland_nonce'); ?>

                <div class="neo-panel-grid">
                    <section class="neo-panel">
                        <h2>Global loading settings</h2>
                        <?php
                        neoloadland_render_field_row(
                            'Enable loader',
                            array(
                                'id'   => 'neoloadland_enabled',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neoloadland_enabled" name="settings[enabled]" value="1" ' . checked(1, $settings['enabled'], false) . '><span>Show the loading screen site-wide</span></label>',
                            )
                        );

                        neoloadland_render_field_row(
                            'Loader media',
                            array(
                                'id'   => 'neoloadland_media_url',
                                'html' => '<div class="neo-media-picker"><input type="url" id="neoloadland_media_url" name="settings[media_url]" value="' . esc_attr($settings['media_url']) . '" class="regular-text neo-media-target" placeholder="https://example.com/loader.mp4"><button type="button" class="button neo-media-button" data-target="#neoloadland_media_url">Upload / Choose Media</button></div><p class="description">Supported: PNG, JPG, SVG, GIF, MP4</p>',
                            )
                        );

                        neoloadland_render_field_row(
                            'Message',
                            array(
                                'id'   => 'neoloadland_message',
                                'html' => '<input type="text" id="neoloadland_message" name="settings[message]" value="' . esc_attr($settings['message']) . '" class="regular-text" placeholder="Please wait, loading...">',
                            )
                        );

                        neoloadland_render_field_row(
                            'Text color',
                            array(
                                'id'   => 'neoloadland_text_color',
                                'html' => '<input type="text" id="neoloadland_text_color" name="settings[text_color]" value="' . esc_attr($settings['text_color']) . '" class="neo-color-field">',
                            )
                        );

                        neoloadland_render_field_row(
                            'Font family',
                            array(
                                'id'   => 'neoloadland_font_family',
                                'html' => '<input type="text" id="neoloadland_font_family" name="settings[font_family]" value="' . esc_attr($settings['font_family']) . '" class="regular-text" placeholder="&quot;Courier New&quot;, monospace">',
                            )
                        );

                        neoloadland_render_field_row(
                            'Font size',
                            array(
                                'id'   => 'neoloadland_font_size',
                                'html' => '<input type="number" id="neoloadland_font_size" name="settings[font_size]" value="' . esc_attr($settings['font_size']) . '" min="10" step="1"> px',
                            )
                        );

                        neoloadland_render_field_row(
                            'Loader width',
                            array(
                                'id'   => 'neoloadland_media_width',
                                'html' => '<input type="number" id="neoloadland_media_width" name="settings[media_width]" value="' . esc_attr($settings['media_width']) . '" min="40" step="1"> px',
                            )
                        );

                        neoloadland_render_field_row(
                            'Background image',
                            array(
                                'id'   => 'neoloadland_background_image',
                                'html' => '<div class="neo-media-picker"><input type="url" id="neoloadland_background_image" name="settings[background_image]" value="' . esc_attr($settings['background_image']) . '" class="regular-text neo-media-target" placeholder="https://example.com/background.jpg"><button type="button" class="button neo-media-button" data-target="#neoloadland_background_image">Upload / Choose Background</button></div>',
                            )
                        );

                        neoloadland_render_field_row(
                            'Background color',
                            array(
                                'id'   => 'neoloadland_background_color',
                                'html' => '<input type="text" id="neoloadland_background_color" name="settings[background_color]" value="' . esc_attr($settings['background_color']) . '" class="neo-color-field">',
                            )
                        );

                        neoloadland_render_field_row(
                            'Overlay opacity',
                            array(
                                'id'   => 'neoloadland_overlay_opacity',
                                'html' => '<input type="number" id="neoloadland_overlay_opacity" name="settings[overlay_opacity]" value="' . esc_attr($settings['overlay_opacity']) . '" min="0" max="1" step="0.05">',
                            )
                        );

                        neoloadland_render_field_row(
                            'Vertical alignment',
                            array(
                                'id'   => 'neoloadland_vertical_align',
                                'html' => '<select id="neoloadland_vertical_align" name="settings[vertical_align]"><option value="top" ' . selected($settings['vertical_align'], 'top', false) . '>Top</option><option value="center" ' . selected($settings['vertical_align'], 'center', false) . '>Center</option><option value="bottom" ' . selected($settings['vertical_align'], 'bottom', false) . '>Bottom</option></select>',
                            )
                        );

                        neoloadland_render_field_row(
                            'Horizontal alignment',
                            array(
                                'id'   => 'neoloadland_horizontal_align',
                                'html' => '<select id="neoloadland_horizontal_align" name="settings[horizontal_align]"><option value="left" ' . selected($settings['horizontal_align'], 'left', false) . '>Left</option><option value="center" ' . selected($settings['horizontal_align'], 'center', false) . '>Center</option><option value="right" ' . selected($settings['horizontal_align'], 'right', false) . '>Right</option></select>',
                            )
                        );

                        neoloadland_render_field_row(
                            'Horizontal offset',
                            array(
                                'id'   => 'neoloadland_offset_x',
                                'html' => '<input type="number" id="neoloadland_offset_x" name="settings[offset_x]" value="' . esc_attr($settings['offset_x']) . '" step="1"> px',
                            )
                        );

                        neoloadland_render_field_row(
                            'Vertical offset',
                            array(
                                'id'   => 'neoloadland_offset_y',
                                'html' => '<input type="number" id="neoloadland_offset_y" name="settings[offset_y]" value="' . esc_attr($settings['offset_y']) . '" step="1"> px',
                            )
                        );
                        ?>
                    </section>

                    <section class="neo-panel">
                        <h2>Preview notes</h2>
                        <div class="neo-callout">
                            <p>The global configuration is used on every public screen unless a page-level override says otherwise.</p>
                            <ul>
                                <li><strong>Global</strong>: use the site-wide design.</li>
                                <li><strong>Custom</strong>: create a dedicated media, label, and placement for a specific page.</li>
                                <li><strong>Disabled</strong>: skip the loading layer entirely for that page.</li>
                            </ul>
                        </div>
                    </section>
                </div>

                <section class="neo-panel neo-panel-full">
                    <h2>Page-level overrides</h2>
                    <p class="description">Assign the loader to specific pages and posts, or disable it where you do not want the animation to appear.</p>
                    <div class="neo-table-wrap">
                        <table class="widefat striped neo-table">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th>Mode</th>
                                    <th>Custom media</th>
                                    <th>Custom text</th>
                                    <th>Vertical</th>
                                    <th>Horizontal</th>
                                    <th>Offset X</th>
                                    <th>Offset Y</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post) : ?>
                                    <?php $override = wp_parse_args((array) get_post_meta($post->ID, '_neoloadland_override', true), neoloadland_sanitize_override(array())); ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html(get_the_title($post) ?: '(no title)'); ?></strong>
                                            <div class="neo-muted"><?php echo esc_html($post->post_type); ?></div>
                                        </td>
                                        <td>
                                            <select name="page_overrides[<?php echo esc_attr($post->ID); ?>][mode]">
                                                <option value="global" <?php selected($override['mode'], 'global'); ?>>Global</option>
                                                <option value="custom" <?php selected($override['mode'], 'custom'); ?>>Custom</option>
                                                <option value="disabled" <?php selected($override['mode'], 'disabled'); ?>>Disabled</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="neo-media-picker">
                                                <input type="url" name="page_overrides[<?php echo esc_attr($post->ID); ?>][media_url]" value="<?php echo esc_attr($override['media_url']); ?>" class="regular-text neo-media-target" id="neoloadland_page_media_<?php echo esc_attr($post->ID); ?>">
                                                <button type="button" class="button neo-media-button" data-target="#neoloadland_page_media_<?php echo esc_attr($post->ID); ?>">Choose</button>
                                            </div>
                                        </td>
                                        <td><input type="text" name="page_overrides[<?php echo esc_attr($post->ID); ?>][message]" value="<?php echo esc_attr($override['message']); ?>" class="regular-text"></td>
                                        <td>
                                            <select name="page_overrides[<?php echo esc_attr($post->ID); ?>][vertical_align]">
                                                <option value="top" <?php selected($override['vertical_align'], 'top'); ?>>Top</option>
                                                <option value="center" <?php selected($override['vertical_align'], 'center'); ?>>Center</option>
                                                <option value="bottom" <?php selected($override['vertical_align'], 'bottom'); ?>>Bottom</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="page_overrides[<?php echo esc_attr($post->ID); ?>][horizontal_align]">
                                                <option value="left" <?php selected($override['horizontal_align'], 'left'); ?>>Left</option>
                                                <option value="center" <?php selected($override['horizontal_align'], 'center'); ?>>Center</option>
                                                <option value="right" <?php selected($override['horizontal_align'], 'right'); ?>>Right</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="page_overrides[<?php echo esc_attr($post->ID); ?>][offset_x]" value="<?php echo esc_attr($override['offset_x']); ?>" class="small-text"></td>
                                        <td><input type="number" name="page_overrides[<?php echo esc_attr($post->ID); ?>][offset_y]" value="<?php echo esc_attr($override['offset_y']); ?>" class="small-text"></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="neo-actions">
                    <button type="submit" class="button button-primary" name="neoloadland_save_settings" value="1">Save NeoLoadLand Settings</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function neoloadland_hex_to_rgba($hex, $opacity) {
    $hex = ltrim((string) $hex, '#');

    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    if (strlen($hex) !== 6) {
        return 'rgba(3,7,11,' . floatval($opacity) . ')';
    }

    $rgb = array_map('hexdec', str_split($hex, 2));

    return 'rgba(' . implode(',', $rgb) . ',' . min(1, max(0, floatval($opacity))) . ')';
}

function neoloadland_get_current_config() {
    $settings = wp_parse_args(get_option('neoloadland_settings', array()), neoloadland_default_settings());

    if (empty($settings['enabled'])) {
        return false;
    }

    if (is_singular()) {
        $override = (array) get_post_meta(get_queried_object_id(), '_neoloadland_override', true);

        if (!empty($override['mode']) && $override['mode'] === 'disabled') {
            return false;
        }

        if (!empty($override['mode']) && $override['mode'] === 'custom') {
            $settings['media_url']        = !empty($override['media_url']) ? $override['media_url'] : $settings['media_url'];
            $settings['message']          = $override['message'] !== '' ? $override['message'] : $settings['message'];
            $settings['vertical_align']   = $override['vertical_align'] ?? $settings['vertical_align'];
            $settings['horizontal_align'] = $override['horizontal_align'] ?? $settings['horizontal_align'];
            $settings['offset_x']         = intval($override['offset_x'] ?? $settings['offset_x']);
            $settings['offset_y']         = intval($override['offset_y'] ?? $settings['offset_y']);
        }
    }

    return $settings;
}

add_action('wp_enqueue_scripts', 'neoloadland_front_assets');
function neoloadland_front_assets() {
    if (is_admin()) {
        return;
    }

    $config = neoloadland_get_current_config();

    if (!$config) {
        return;
    }

    wp_enqueue_style('neoloadland-front', NEOLOADLAND_URL . 'assets/css/front.css', array(), NEOLOADLAND_VERSION);
    wp_enqueue_script('neoloadland-front', NEOLOADLAND_URL . 'assets/js/neoloadland.js', array(), NEOLOADLAND_VERSION, true);
    wp_localize_script(
        'neoloadland-front',
        'NeoLoadLandConfig',
        array(
            'fadeDuration' => 650,
        )
    );
}

function neoloadland_render_media($url, $width) {
    if (!$url) {
        return '<div class="neoloadland-fallback" style="width:' . esc_attr($width) . 'px;height:' . esc_attr($width) . 'px;">N</div>';
    }

    $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

    if ($extension === 'mp4') {
        return '<video class="neoloadland-media" autoplay muted loop playsinline style="width:' . esc_attr($width) . 'px;"><source src="' . esc_url($url) . '" type="video/mp4"></video>';
    }

    return '<img class="neoloadland-media" src="' . esc_url($url) . '" alt="Loading screen media" style="width:' . esc_attr($width) . 'px;" />';
}

add_action('wp_body_open', 'neoloadland_render_overlay');
add_action('wp_footer', 'neoloadland_render_overlay');
function neoloadland_render_overlay() {
    static $rendered = false;

    if (is_admin()) {
        return;
    }

    if ($rendered) {
        return;
    }

    $config = neoloadland_get_current_config();

    if (!$config) {
        return;
    }

    $rendered = true;

    $justify = array(
        'top'    => 'flex-start',
        'center' => 'center',
        'bottom' => 'flex-end',
    );

    $align = array(
        'left'   => 'flex-start',
        'center' => 'center',
        'right'  => 'flex-end',
    );

    $background_style = 'background-color:' . esc_attr(neoloadland_hex_to_rgba($config['background_color'], $config['overlay_opacity'])) . ';';

    if (!empty($config['background_image'])) {
        $background_style .= 'background-image:url(' . esc_url($config['background_image']) . ');';
    }
    ?>
    <div
        id="neoloadland-overlay"
        class="neoloadland-overlay"
        style="<?php echo esc_attr($background_style); ?>"
        data-fade-duration="650"
    >
        <div
            class="neoloadland-content"
            style="
                justify-content:<?php echo esc_attr($justify[$config['vertical_align']] ?? 'center'); ?>;
                align-items:<?php echo esc_attr($align[$config['horizontal_align']] ?? 'center'); ?>;
            "
        >
            <div
                class="neoloadland-inner"
                style="
                    transform:translate(<?php echo esc_attr(intval($config['offset_x'])); ?>px, <?php echo esc_attr(intval($config['offset_y'])); ?>px);
                    color:<?php echo esc_attr($config['text_color']); ?>;
                    font-family:<?php echo esc_attr($config['font_family']); ?>;
                    font-size:<?php echo esc_attr(intval($config['font_size'])); ?>px;
                "
            >
                <?php echo wp_kses_post(neoloadland_render_media($config['media_url'], $config['media_width'])); ?>
                <?php if (!empty($config['message'])) : ?>
                    <div class="neoloadland-message"><?php echo esc_html($config['message']); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
