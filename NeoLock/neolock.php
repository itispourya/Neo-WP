<?php
/**
 * Plugin Name: NeoLock
 * Plugin URI: https://neonotion.net/
 * Description: Content protector - lock text copy, image save, right click, screenshot detection and per-page protection
 * Version: 6
 * Author: NeoNotion
 * Programmer: Pourya Omidian
 * Author URI: https://neonotion.net/
 * Text Domain: neolock
 */

if (!defined('ABSPATH')) {
    exit;
}

define('NEOLOCK_VERSION', '1');
define('NEOLOCK_FILE', __FILE__);
define('NEOLOCK_URL', plugin_dir_url(__FILE__));
define('NEOLOCK_PATH', plugin_dir_path(__FILE__));

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

register_activation_hook(__FILE__, 'neolock_activate');

function neolock_default_settings() {
    return array(
        'enabled'                => 1,
        'lock_admin_also'        => 0,
        'disable_right_click'    => 1,
        'disable_text_select'    => 1,
        'disable_copy'           => 1,
        'disable_cut'            => 1,
        'disable_paste'          => 0,
        'disable_drag'           => 1,
        'disable_image_save'     => 1,
        'disable_print'          => 1,
        'disable_view_source'    => 1,
        'disable_devtools'       => 1,
        'screenshot_protection'  => 1,
        'watermark_enabled'      => 0,
        'watermark_text'         => get_bloginfo('name'),
        'watermark_opacity'      => 0.08,
        'watermark_size'         => 22,
        'watermark_angle'        => -28,
        'watermark_color'        => '#33ff77',
        'custom_message'         => 'Content protected by NeoLock.',
        'custom_css'             => '',
        'exclude_roles'          => array('administrator'),
    );
}

function neolock_activate() {
    $defaults = neolock_default_settings();
    $existing = get_option('neolock_settings', false);
    if ($existing === false || !is_array($existing)) {
        add_option('neolock_settings', $defaults);
    } else {
        $merged = wp_parse_args($existing, $defaults);
        if (empty($merged['enabled'])) { $merged['enabled'] = 1; }
        update_option('neolock_settings', $merged);
    }
}

add_action('plugins_loaded', 'neolock_ensure_settings', -999);
function neolock_ensure_settings() {
    $defaults = neolock_default_settings();
    $existing = get_option('neolock_settings', false);
    if ($existing === false || !is_array($existing)) {
        update_option('neolock_settings', $defaults);
        return;
    }
    $needs_save = false;
    foreach ($defaults as $k => $v) {
        if (!array_key_exists($k, $existing)) {
            $existing[$k] = $v;
            $needs_save = true;
        }
    }
    if (empty($existing['enabled'])) {
        $existing['enabled'] = 1;
        $needs_save = true;
    }
    if ($needs_save) {
        update_option('neolock_settings', $existing);
    }
}

add_action('admin_init', 'neolock_ensure_settings', 1);

add_action('admin_menu', 'neolock_admin_menu');
function neolock_admin_menu() {
    neonotion_register_root_menu();

    add_submenu_page(
        'neonotion',
        'NeoLock',
        'NeoLock',
        'manage_options',
        'neolock',
        'neolock_render_admin_page'
    );
}

add_action('admin_enqueue_scripts', 'neolock_admin_assets');
function neolock_admin_assets($hook) {
    if (strpos($hook, 'neolock') === false && strpos($hook, 'neonotion') === false) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style('neolock-admin', NEOLOCK_URL . 'assets/css/admin.css', array('wp-color-picker'), NEOLOCK_VERSION);
    wp_enqueue_script('neolock-admin', NEOLOCK_URL . 'assets/js/neolock.js', array('jquery', 'wp-color-picker'), NEOLOCK_VERSION, true);
}

function neolock_get_content_posts() {
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

function neolock_sanitize_settings($input) {
    $defaults = neolock_default_settings();

    $exclude_roles = array();
    if (!empty($input['exclude_roles']) && is_array($input['exclude_roles'])) {
        foreach ($input['exclude_roles'] as $role) {
            $role = sanitize_key($role);
            if (get_role($role)) {
                $exclude_roles[] = $role;
            }
        }
    }

    return array(
        'enabled'                => empty($input['enabled']) ? 0 : 1,
        'lock_admin_also'        => empty($input['lock_admin_also']) ? 0 : 1,
        'disable_right_click'    => empty($input['disable_right_click']) ? 0 : 1,
        'disable_text_select'    => empty($input['disable_text_select']) ? 0 : 1,
        'disable_copy'           => empty($input['disable_copy']) ? 0 : 1,
        'disable_cut'            => empty($input['disable_cut']) ? 0 : 1,
        'disable_paste'          => empty($input['disable_paste']) ? 0 : 1,
        'disable_drag'           => empty($input['disable_drag']) ? 0 : 1,
        'disable_image_save'     => empty($input['disable_image_save']) ? 0 : 1,
        'disable_print'          => empty($input['disable_print']) ? 0 : 1,
        'disable_view_source'    => empty($input['disable_view_source']) ? 0 : 1,
        'disable_devtools'       => empty($input['disable_devtools']) ? 0 : 1,
        'screenshot_protection'  => empty($input['screenshot_protection']) ? 0 : 1,
        'watermark_enabled'      => empty($input['watermark_enabled']) ? 0 : 1,
        'watermark_text'         => sanitize_text_field($input['watermark_text'] ?? $defaults['watermark_text']),
        'watermark_opacity'      => min(1, max(0, floatval($input['watermark_opacity'] ?? $defaults['watermark_opacity']))),
        'watermark_size'         => max(8, absint($input['watermark_size'] ?? $defaults['watermark_size'])),
        'watermark_angle'        => intval($input['watermark_angle'] ?? $defaults['watermark_angle']),
        'watermark_color'        => sanitize_hex_color($input['watermark_color'] ?? $defaults['watermark_color']) ?: $defaults['watermark_color'],
        'custom_message'         => sanitize_textarea_field($input['custom_message'] ?? ''),
        'custom_css'             => wp_strip_all_tags($input['custom_css'] ?? ''),
        'exclude_roles'          => $exclude_roles,
    );
}

function neolock_sanitize_override($input) {
    return array(
        'mode'                => in_array(($input['mode'] ?? 'global'), array('global', 'custom', 'disabled'), true) ? $input['mode'] : 'global',
        'disable_right_click' => empty($input['disable_right_click']) ? 0 : 1,
        'disable_text_select' => empty($input['disable_text_select']) ? 0 : 1,
        'disable_copy'        => empty($input['disable_copy']) ? 0 : 1,
        'disable_image_save'  => empty($input['disable_image_save']) ? 0 : 1,
        'disable_print'       => empty($input['disable_print']) ? 0 : 1,
        'disable_devtools'    => empty($input['disable_devtools']) ? 0 : 1,
        'watermark_enabled'   => empty($input['watermark_enabled']) ? 0 : 1,
        'watermark_text'      => sanitize_text_field($input['watermark_text'] ?? ''),
    );
}

function neolock_render_setting_row($label, $field, $description = '') {
    echo '<div class="neo-grid-row">';
    echo '<label class="neo-label" for="' . esc_attr($field['id']) . '">' . esc_html($label) . '</label>';
    echo '<div class="neo-field-wrap">' . $field['html'];
    if ($description) {
        echo '<p class="description">' . esc_html($description) . '</p>';
    }
    echo '</div></div>';
}

function neolock_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    neolock_ensure_settings();
    $settings = wp_parse_args(get_option('neolock_settings', array()), neolock_default_settings());
    if (!isset($settings['enabled']) || $settings['enabled'] === '' || $settings['enabled'] === null) {
        $settings['enabled'] = 1;
    }
    $notice   = '';

    if (!empty($_POST['neolock_save_settings']) && check_admin_referer('neolock_save_settings', 'neolock_nonce')) {
        $settings = neolock_sanitize_settings($_POST['settings'] ?? array());
        update_option('neolock_settings', $settings);

        $page_overrides = $_POST['page_overrides'] ?? array();
        $posts          = neolock_get_content_posts();

        foreach ($posts as $post) {
            if (isset($page_overrides[$post->ID])) {
                update_post_meta($post->ID, '_neolock_override', neolock_sanitize_override($page_overrides[$post->ID]));
            } else {
                delete_post_meta($post->ID, '_neolock_override');
            }
        }

        $notice = 'NeoLock settings were updated successfully.';
    }

    $posts        = neolock_get_content_posts();
    $all_roles    = wp_roles()->roles;
    ?>
    <div class="wrap neo-admin-shell">
        <div class="neo-admin-card">
            <div class="neo-admin-heading">
                <div>
                    <h1>NeoLock</h1>
                    <p>Protect your content: disable right click, text copy, image save, print, devtools and detect screenshots.</p>
                </div>
                <div class="neo-admin-badge">NeoNotion / NeoLock</div>
            </div>

            <?php if ($notice) : ?>
                <div class="neo-notice"><?php echo esc_html($notice); ?></div>
            <?php endif; ?>

            <?php
            $current_user = wp_get_current_user();
            $show_test_warning = false;
            foreach ((array) ($settings['exclude_roles'] ?? array()) as $excluded) {
                if (in_array($excluded, (array) $current_user->roles, true)) { $show_test_warning = true; break; }
            }
            if (empty($settings['enabled'])) : ?>
                <div class="neo-notice" style="background:rgba(180,35,35,.1);border-color:rgba(180,35,35,.45);color:#ff9b9b;">NeoLock is currently <strong>DISABLED</strong>. Turn on the &ldquo;Enable NeoLock&rdquo; toggle below.</div>
            <?php elseif ($show_test_warning) : ?>
                <div class="neo-notice" style="background:rgba(200,150,0,.08);border-color:rgba(255,200,0,.45);color:#ffe58a;">
                    <strong>Testing hint:</strong> Your current user role is in the &ldquo;Exclude user roles&rdquo; list, so locks do <em>not</em> apply to you. Log out or open the site in an incognito window to verify NeoLock is working for regular visitors.
                </div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('neolock_save_settings', 'neolock_nonce'); ?>

                <div class="neo-panel-grid">
                    <section class="neo-panel">
                        <h2>Global Protection</h2>
                        <?php
                        neolock_render_setting_row(
                            'Enable NeoLock',
                            array(
                                'id'   => 'neolock_enabled',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_enabled" name="settings[enabled]" value="1" ' . checked(1, $settings['enabled'], false) . '><span>Activate NeoLock site-wide (can be overridden per page)</span></label>',
                            )
                        );

                        neolock_render_setting_row(
                            'Lock admin area',
                            array(
                                'id'   => 'neolock_lock_admin_also',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_lock_admin_also" name="settings[lock_admin_also]" value="1" ' . checked(1, $settings['lock_admin_also'], false) . '><span>Apply locks also inside wp-admin (recommended: off)</span></label>',
                            )
                        );
                        ?>

                        <h3 style="color:var(--neo-accent);margin-top:18px;">Interaction Locks</h3>
                        <?php
                        neolock_render_setting_row(
                            'Disable right click',
                            array(
                                'id'   => 'neolock_disable_right_click',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_disable_right_click" name="settings[disable_right_click]" value="1" ' . checked(1, $settings['disable_right_click'], false) . '><span>Block context menu on images and text</span></label>',
                            )
                        );

                        neolock_render_setting_row(
                            'Disable text selection',
                            array(
                                'id'   => 'neolock_disable_text_select',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_disable_text_select" name="settings[disable_text_select]" value="1" ' . checked(1, $settings['disable_text_select'], false) . '><span>Make text non-selectable via user-select CSS</span></label>',
                            )
                        );

                        neolock_render_setting_row(
                            'Disable copy (Ctrl+C)',
                            array(
                                'id'   => 'neolock_disable_copy',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_disable_copy" name="settings[disable_copy]" value="1" ' . checked(1, $settings['disable_copy'], false) . '><span>Intercept copy events</span></label>',
                            )
                        );

                        neolock_render_setting_row(
                            'Disable cut (Ctrl+X)',
                            array(
                                'id'   => 'neolock_disable_cut',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_disable_cut" name="settings[disable_cut]" value="1" ' . checked(1, $settings['disable_cut'], false) . '><span>Intercept cut events</span></label>',
                            )
                        );

                        neolock_render_setting_row(
                            'Disable paste (Ctrl+V)',
                            array(
                                'id'   => 'neolock_disable_paste',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_disable_paste" name="settings[disable_paste]" value="1" ' . checked(1, $settings['disable_paste'], false) . '><span>Intercept paste events (forms)</span></label>',
                            )
                        );

                        neolock_render_setting_row(
                            'Disable drag &amp; drop',
                            array(
                                'id'   => 'neolock_disable_drag',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_disable_drag" name="settings[disable_drag]" value="1" ' . checked(1, $settings['disable_drag'], false) . '><span>Prevent dragging images out of the window</span></label>',
                            )
                        );

                        neolock_render_setting_row(
                            'Block image save',
                            array(
                                'id'   => 'neolock_disable_image_save',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_disable_image_save" name="settings[disable_image_save]" value="1" ' . checked(1, $settings['disable_image_save'], false) . '><span>Transparent overlay + pointer-events none shield on images</span></label>',
                            )
                        );
                        ?>

                        <h3 style="color:var(--neo-accent);margin-top:18px;">Browser &amp; System Locks</h3>
                        <?php
                        neolock_render_setting_row(
                            'Disable print',
                            array(
                                'id'   => 'neolock_disable_print',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_disable_print" name="settings[disable_print]" value="1" ' . checked(1, $settings['disable_print'], false) . '><span>Hide page content in print stylesheets</span></label>',
                            )
                        );

                        neolock_render_setting_row(
                            'Fight view-source / save page',
                            array(
                                'id'   => 'neolock_disable_view_source',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_disable_view_source" name="settings[disable_view_source]" value="1" ' . checked(1, $settings['disable_view_source'], false) . '><span>Obfuscation + disable Ctrl+S, Ctrl+U shortcut hints</span></label>',
                            )
                        );

                        neolock_render_setting_row(
                            'Developer tools detection',
                            array(
                                'id'   => 'neolock_disable_devtools',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_disable_devtools" name="settings[disable_devtools]" value="1" ' . checked(1, $settings['disable_devtools'], false) . '><span>Detect F12 / inspector and blank the page</span></label>',
                            )
                        );

                        neolock_render_setting_row(
                            'Screenshot protection',
                            array(
                                'id'   => 'neolock_screenshot_protection',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_screenshot_protection" name="settings[screenshot_protection]" value="1" ' . checked(1, $settings['screenshot_protection'], false) . '><span>Show full-screen black shield when user switches apps / PrintScreen is attempted (best-effort)</span></label>',
                            )
                        );
                        ?>
                    </section>

                    <section class="neo-panel">
                        <h2>Watermark Shield</h2>
                        <?php
                        neolock_render_setting_row(
                            'Enable watermark',
                            array(
                                'id'   => 'neolock_watermark_enabled',
                                'html' => '<label class="neo-switch"><input type="checkbox" id="neolock_watermark_enabled" name="settings[watermark_enabled]" value="1" ' . checked(1, $settings['watermark_enabled'], false) . '><span>Tile repeated watermark text across the page</span></label>',
                            )
                        );

                        neolock_render_setting_row(
                            'Watermark text',
                            array(
                                'id'   => 'neolock_watermark_text',
                                'html' => '<input type="text" id="neolock_watermark_text" name="settings[watermark_text]" value="' . esc_attr($settings['watermark_text']) . '" class="regular-text" placeholder="' . esc_attr(get_bloginfo('name')) . '">',
                            ),
                            'Tip: use the user email, IP, or site name so leaked screenshots can be traced.'
                        );

                        neolock_render_setting_row(
                            'Watermark color',
                            array(
                                'id'   => 'neolock_watermark_color',
                                'html' => '<input type="text" id="neolock_watermark_color" name="settings[watermark_color]" value="' . esc_attr($settings['watermark_color']) . '" class="neo-color-field">',
                            )
                        );

                        neolock_render_setting_row(
                            'Watermark opacity',
                            array(
                                'id'   => 'neolock_watermark_opacity',
                                'html' => '<input type="number" id="neolock_watermark_opacity" name="settings[watermark_opacity]" value="' . esc_attr($settings['watermark_opacity']) . '" min="0" max="1" step="0.01">',
                            ),
                            '0 = invisible, 0.08 = subtle, 0.3 = strong.'
                        );

                        neolock_render_setting_row(
                            'Watermark font size',
                            array(
                                'id'   => 'neolock_watermark_size',
                                'html' => '<input type="number" id="neolock_watermark_size" name="settings[watermark_size]" value="' . esc_attr($settings['watermark_size']) . '" min="8" step="1"> px',
                            )
                        );

                        neolock_render_setting_row(
                            'Watermark rotation',
                            array(
                                'id'   => 'neolock_watermark_angle',
                                'html' => '<input type="number" id="neolock_watermark_angle" name="settings[watermark_angle]" value="' . esc_attr($settings['watermark_angle']) . '" step="1"> degrees',
                            )
                        );
                        ?>

                        <h2 style="margin-top:20px;">Exclusions &amp; Messages</h2>
                        <?php
                        neolock_render_setting_row(
                            'Custom alert message',
                            array(
                                'id'   => 'neolock_custom_message',
                                'html' => '<textarea id="neolock_custom_message" name="settings[custom_message]" rows="3" placeholder="Content protected by NeoLock.">' . esc_textarea($settings['custom_message']) . '</textarea>',
                            ),
                            'Shown when right-click or copy is blocked (leave empty for silent block).'
                        );

                        $roles_html = '<div style="display:grid;gap:8px;">';
                        foreach ($all_roles as $role_key => $role_data) {
                            $checked = in_array($role_key, (array) $settings['exclude_roles'], true) ? 'checked' : '';
                            $roles_html .= '<label class="neo-switch"><input type="checkbox" name="settings[exclude_roles][]" value="' . esc_attr($role_key) . '" ' . $checked . '><span>' . esc_html(translate_user_role($role_data['name'])) . '</span></label>';
                        }
                        $roles_html .= '</div>';
                        neolock_render_setting_row(
                            'Exclude user roles',
                            array(
                                'id'   => 'neolock_exclude_roles',
                                'html' => $roles_html,
                            ),
                            'These logged-in roles bypass NeoLock (default: administrators).'
                        );

                        neolock_render_setting_row(
                            'Custom inline CSS',
                            array(
                                'id'   => 'neolock_custom_css',
                                'html' => '<textarea id="neolock_custom_css" name="settings[custom_css]" rows="6" placeholder="body { filter: blur(0); }">' . esc_textarea($settings['custom_css']) . '</textarea>',
                            ),
                            'Advanced: extra CSS injected on the front-end when NeoLock is active.'
                        );
                        ?>

                        <div class="neo-callout" style="margin-top:18px;">
                            <p><strong>Heads up:</strong> No client-side protection is 100% uncrackable. NeoLock raises the bar significantly and stops casual piracy / copying. Determined users with technical knowledge can still find workarounds.</p>
                        </div>
                    </section>
                </div>

                <section class="neo-panel neo-panel-full">
                    <h2>Per-page / per-post overrides</h2>
                    <p class="description">Use <strong>Global</strong> to inherit site-wide NeoLock settings, <strong>Custom</strong> to enable a different mix of locks on a specific entry, or <strong>Disabled</strong> to skip protection entirely for that page.</p>
                    <div class="neo-table-wrap">
                        <table class="widefat striped neo-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Mode</th>
                                    <th>Right-click</th>
                                    <th>Select</th>
                                    <th>Copy</th>
                                    <th>Image save</th>
                                    <th>Print</th>
                                    <th>DevTools</th>
                                    <th>Watermark</th>
                                    <th>Watermark text</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post) : ?>
                                    <?php
                                    $override = wp_parse_args(
                                        (array) get_post_meta($post->ID, '_neolock_override', true),
                                        neolock_sanitize_override(array())
                                    );
                                    $is_custom = $override['mode'] === 'custom';
                                    $disabled  = $override['mode'] === 'disabled' || $override['mode'] === 'global';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html(get_the_title($post) ?: '(no title)'); ?></strong>
                                            <div class="neo-muted"><?php echo esc_html($post->post_type); ?></div>
                                            <div><a href="<?php echo esc_url(get_permalink($post)); ?>" target="_blank" rel="noopener">View &rarr;</a></div>
                                        </td>
                                        <td>
                                            <select name="page_overrides[<?php echo esc_attr($post->ID); ?>][mode]" class="neolock-override-mode" data-row="<?php echo esc_attr($post->ID); ?>">
                                                <option value="global" <?php selected($override['mode'], 'global'); ?>>Global</option>
                                                <option value="custom" <?php selected($override['mode'], 'custom'); ?>>Custom</option>
                                                <option value="disabled" <?php selected($override['mode'], 'disabled'); ?>>Disabled</option>
                                            </select>
                                        </td>
                                        <td><input type="checkbox" name="page_overrides[<?php echo esc_attr($post->ID); ?>][disable_right_click]" value="1" <?php checked(!empty($override['disable_right_click'])) ?> <?php disabled($disabled); ?> class="neolock-ov-field neolock-ov-<?php echo esc_attr($post->ID); ?>"></td>
                                        <td><input type="checkbox" name="page_overrides[<?php echo esc_attr($post->ID); ?>][disable_text_select]" value="1" <?php checked(!empty($override['disable_text_select'])) ?> <?php disabled($disabled); ?> class="neolock-ov-field neolock-ov-<?php echo esc_attr($post->ID); ?>"></td>
                                        <td><input type="checkbox" name="page_overrides[<?php echo esc_attr($post->ID); ?>][disable_copy]" value="1" <?php checked(!empty($override['disable_copy'])) ?> <?php disabled($disabled); ?> class="neolock-ov-field neolock-ov-<?php echo esc_attr($post->ID); ?>"></td>
                                        <td><input type="checkbox" name="page_overrides[<?php echo esc_attr($post->ID); ?>][disable_image_save]" value="1" <?php checked(!empty($override['disable_image_save'])) ?> <?php disabled($disabled); ?> class="neolock-ov-field neolock-ov-<?php echo esc_attr($post->ID); ?>"></td>
                                        <td><input type="checkbox" name="page_overrides[<?php echo esc_attr($post->ID); ?>][disable_print]" value="1" <?php checked(!empty($override['disable_print'])) ?> <?php disabled($disabled); ?> class="neolock-ov-field neolock-ov-<?php echo esc_attr($post->ID); ?>"></td>
                                        <td><input type="checkbox" name="page_overrides[<?php echo esc_attr($post->ID); ?>][disable_devtools]" value="1" <?php checked(!empty($override['disable_devtools'])) ?> <?php disabled($disabled); ?> class="neolock-ov-field neolock-ov-<?php echo esc_attr($post->ID); ?>"></td>
                                        <td><input type="checkbox" name="page_overrides[<?php echo esc_attr($post->ID); ?>][watermark_enabled]" value="1" <?php checked(!empty($override['watermark_enabled'])) ?> <?php disabled($disabled); ?> class="neolock-ov-field neolock-ov-<?php echo esc_attr($post->ID); ?>"></td>
                                        <td><input type="text" name="page_overrides[<?php echo esc_attr($post->ID); ?>][watermark_text]" value="<?php echo esc_attr($override['watermark_text']); ?>" class="regular-text neolock-ov-field neolock-ov-<?php echo esc_attr($post->ID); ?>" <?php disabled($disabled); ?>></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="neo-actions">
                    <button type="submit" class="button button-primary" name="neolock_save_settings" value="1">Save NeoLock Settings</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function neolock_should_bypass_for_user($settings) {
    if (!is_user_logged_in()) {
        return false;
    }

    $user = wp_get_current_user();
    if (!$user || empty($user->roles)) {
        return false;
    }

    $exclude = (array) ($settings['exclude_roles'] ?? array());

    foreach ($user->roles as $role) {
        if (in_array($role, $exclude, true)) {
            return true;
        }
    }

    return false;
}

function neolock_get_current_config() {
    if (!did_action('plugins_loaded') && !doing_action('plugins_loaded')) {
        neolock_ensure_settings();
    }
    $settings = wp_parse_args(get_option('neolock_settings', array()), neolock_default_settings());
    if (!isset($settings['enabled']) || $settings['enabled'] === '' || $settings['enabled'] === null) {
        $settings['enabled'] = 1;
    }

    if (empty($settings['enabled'])) {
        return false;
    }

    if (!is_admin() && neolock_should_bypass_for_user($settings)) {
        return false;
    }

    if (is_admin() && empty($settings['lock_admin_also'])) {
        return false;
    }

    $config = $settings;
    if (!isset($config['custom_message']) || $config['custom_message'] === '') {
        $config['custom_message'] = 'Content protected by NeoLock.';
    }

    if (is_singular()) {
        $post_id  = get_queried_object_id();
        $override = (array) get_post_meta($post_id, '_neolock_override', true);
        $override = wp_parse_args($override, neolock_sanitize_override(array()));

        if (!empty($override['mode']) && $override['mode'] === 'disabled') {
            return false;
        }

        if (!empty($override['mode']) && $override['mode'] === 'custom') {
            $override_fields = array(
                'disable_right_click',
                'disable_text_select',
                'disable_copy',
                'disable_image_save',
                'disable_print',
                'disable_devtools',
                'watermark_enabled',
                'watermark_text',
            );
            foreach ($override_fields as $f) {
                $config[$f] = $override[$f] ?? $config[$f];
            }
        }
    }

    return $config;
}

add_action('wp_enqueue_scripts', 'neolock_front_assets', 1);
add_action('admin_enqueue_scripts', 'neolock_front_assets', 1);
function neolock_front_assets() {
    $config = neolock_get_current_config();

    if (!$config) {
        return;
    }

    wp_enqueue_style('neolock-front', NEOLOCK_URL . 'assets/css/front.css', array(), NEOLOCK_VERSION);

    $custom_css = trim((string) ($config['custom_css'] ?? ''));
    if ($custom_css !== '') {
        wp_add_inline_style('neolock-front', $custom_css);
    }

    $hardened_css = '';
    if (!empty($config['disable_text_select'])) {
        $hardened_css .= '
            html.neolock-active, html.neolock-active *,
            body.neolock-active, body.neolock-active * {
                -webkit-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                user-select: none !important;
                -webkit-touch-callout: none !important;
            }
            html.neolock-active input, html.neolock-active textarea, html.neolock-active select,
            body.neolock-active input, body.neolock-active textarea, body.neolock-active select,
            html.neolock-active [contenteditable="true"], html.neolock-active [contenteditable=""],
            body.neolock-active [contenteditable="true"], body.neolock-active [contenteditable=""] {
                -webkit-user-select: text !important;
                -moz-user-select: text !important;
                -ms-user-select: text !important;
                user-select: text !important;
                -webkit-touch-callout: default !important;
            }
        ';
    }
    if (!empty($config['disable_image_save'])) {
        $hardened_css .= '
            html.neolock-active img, html.neolock-active picture img,
            body.neolock-active img, body.neolock-active picture img {
                -webkit-user-drag: none !important;
                user-drag: none !important;
                -webkit-user-select: none !important;
            }
            html.neolock-active a:has(img), body.neolock-active a:has(img) {
                -webkit-user-drag: none !important;
            }
        ';
    }
    if (!empty($config['disable_print'])) {
        $hardened_css .= '
            @media print {
                html.neolock-active, html.neolock-active * { visibility: hidden !important; }
                html.neolock-active #neolock-print-warning,
                html.neolock-active #neolock-print-warning * {
                    visibility: visible !important;
                    display: grid !important;
                    position: fixed !important;
                    inset: 0 !important;
                    color: #fff !important;
                    background: #000 !important;
                    font-family: "Courier New", monospace !important;
                    font-size: 24px !important;
                    padding: 40px !important;
                    text-align: center !important;
                }
            }
        ';
    }
    if (!empty($config['disable_right_click']) || !empty($config['disable_image_save'])) {
        $hardened_css .= '
            html.neolock-active, body.neolock-active {
                -webkit-tap-highlight-color: transparent !important;
                touch-action: manipulation !important;
            }
        ';
    }
    if (trim($hardened_css) !== '') {
        wp_add_inline_style('neolock-front', $hardened_css);
    }

    wp_enqueue_script('neolock-front', NEOLOCK_URL . 'assets/js/neolock.js', array(), NEOLOCK_VERSION, false);
    wp_script_add_data('neolock-front', 'strategy', 'defer');
    wp_localize_script(
        'neolock-front',
        'NeoLockConfig',
        array(
            'disable_right_click'   => !empty($config['disable_right_click']),
            'disable_text_select'   => !empty($config['disable_text_select']),
            'disable_copy'          => !empty($config['disable_copy']),
            'disable_cut'           => !empty($config['disable_cut']),
            'disable_paste'         => !empty($config['disable_paste']),
            'disable_drag'          => !empty($config['disable_drag']),
            'disable_image_save'    => !empty($config['disable_image_save']),
            'disable_print'         => !empty($config['disable_print']),
            'disable_view_source'   => !empty($config['disable_view_source']),
            'disable_devtools'      => !empty($config['disable_devtools']),
            'screenshot_protection' => !empty($config['screenshot_protection']),
            'watermark_enabled'     => !empty($config['watermark_enabled']),
            'watermark_text'        => $config['watermark_text'] ?? '',
            'watermark_opacity'     => floatval($config['watermark_opacity'] ?? 0.08),
            'watermark_size'        => absint($config['watermark_size'] ?? 22),
            'watermark_angle'       => intval($config['watermark_angle'] ?? -28),
            'watermark_color'       => $config['watermark_color'] ?? '#33ff77',
            'custom_message'        => $config['custom_message'] ?? '',
            'ajax_url'              => admin_url('admin-ajax.php'),
            'rest_url'              => rest_url('neolock/v1'),
        )
    );
}

add_action('wp_head', 'neolock_inject_front_bootstrap', -999999);
add_action('admin_head', 'neolock_inject_front_bootstrap', -999999);
function neolock_inject_front_bootstrap() {
    $config = neolock_get_current_config();
    if (!$config) {
        return;
    }
    ?>
    <script id="neolock-bootstrap">
        (function () {
            try {
                document.documentElement.classList.add('neolock-active');
                if (document.body) document.body.classList.add('neolock-active');
                document.addEventListener('DOMContentLoaded', function () {
                    document.documentElement.classList.add('neolock-active');
                    document.body && document.body.classList.add('neolock-active');
                });
            } catch (e) {}
            try {
                window.__NEOLOCK_BOOT = <?php echo wp_json_encode(array(
                    'disable_right_click'   => !empty($config['disable_right_click']),
                    'disable_text_select'   => !empty($config['disable_text_select']),
                    'disable_copy'          => !empty($config['disable_copy']),
                    'disable_cut'           => !empty($config['disable_cut']),
                    'disable_paste'         => !empty($config['disable_paste']),
                    'disable_drag'          => !empty($config['disable_drag']),
                    'disable_image_save'    => !empty($config['disable_image_save']),
                    'disable_print'         => !empty($config['disable_print']),
                    'disable_view_source'   => !empty($config['disable_view_source']),
                    'disable_devtools'      => !empty($config['disable_devtools']),
                    'screenshot_protection' => !empty($config['screenshot_protection']),
                    'custom_message'        => $config['custom_message'] ?? '',
                )); ?>;
                (function (C) {
                    function kill(e) { try { if (e && typeof e.preventDefault === 'function') e.preventDefault(); } catch (x) {} try { if (e && typeof e.stopPropagation === 'function') e.stopPropagation(); } catch (x) {} try { if (e && typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation(); } catch (x) {} try { if (e) { e.cancelBubble = true; e.returnValue = false; } } catch (x) {} return false; }
                    function editable(t) { if (!t) return false; var n = (t.tagName || '').toLowerCase(); if (n === 'input' || n === 'textarea' || n === 'select') return true; if (t.isContentEditable) return true; try { var c = t.closest && t.closest('[contenteditable="true"], [contenteditable=""]'); if (c) return true; } catch (x) {} return false; }
                    function msg(ev) { if (C.custom_message) { try { window.alert(C.custom_message); } catch (x) {} } return kill(ev); }
                    function addCaptured(evName, fn, tgt) { tgt = tgt || window; try { tgt.addEventListener(evName, fn, true); } catch (x) {} try { document.addEventListener && document.addEventListener(evName, fn, true); } catch (x) {} try { document.documentElement && document.documentElement.addEventListener && document.documentElement.addEventListener(evName, fn, true); } catch (x) {} try { document.body && document.body.addEventListener(evName, fn, true); } catch (x) {} }
                    if (C.disable_right_click) {
                        addCaptured('contextmenu', function (e) { if (editable(e.target)) return; return msg(e); });
                        addCaptured('gesturestart', function (e) { return kill(e); });
                    }
                    if (C.disable_copy || C.disable_cut || C.disable_paste) {
                        if (C.disable_copy) addCaptured('copy', function (e) { if (editable(e.target)) return; try { if (e.clipboardData) e.clipboardData.clearData(); } catch (x) {} try { window.clipboardData && window.clipboardData.clearData && window.clipboardData.clearData(); } catch (x) {} return msg(e); });
                        if (C.disable_cut) addCaptured('cut', function (e) { if (editable(e.target)) return; return msg(e); });
                        if (C.disable_paste) addCaptured('paste', function (e) { if (editable(e.target)) return; return kill(e); });
                    }
                    if (C.disable_drag) {
                        addCaptured('dragstart', function (e) { var t = e.target; if (!t) return; var n = (t.tagName || '').toLowerCase(); if (n === 'img' || n === 'a' || n === 'picture' || n === 'video') return kill(e); });
                        addCaptured('dragend', function (e) { var t = e.target; if (!t) return; var n = (t.tagName || '').toLowerCase(); if (n === 'img') return kill(e); });
                    }
                    if (C.disable_print || C.disable_view_source || C.disable_devtools) {
                        addCaptured('keydown', function (e) {
                            try {
                                var k = (e.key || '').toLowerCase();
                                var c = e.code || '';
                                var ctrl = e.ctrlKey || e.metaKey;
                                var shift = e.shiftKey;
                                if (C.disable_devtools) {
                                    if (c === 'F12') return kill(e);
                                    if (ctrl && shift && (c === 'KeyI' || c === 'KeyJ' || c === 'KeyC' || k === 'i' || k === 'j' || k === 'c')) return kill(e);
                                }
                                if (C.disable_view_source) {
                                    if (ctrl && (c === 'KeyS' || c === 'KeyU' || k === 's' || k === 'u')) return kill(e);
                                }
                                if (C.disable_print) {
                                    if (ctrl && (c === 'KeyP' || k === 'p')) return msg(e);
                                }
                            } catch (x) {}
                        });
                    }
                })(window.__NEOLOCK_BOOT);
            } catch (e) {}
        })();
    </script>
    <style id="neolock-runtime-vars">
        :root {
            --neolock-wm-opacity: <?php echo esc_attr(floatval($config['watermark_opacity'] ?? 0.08)); ?>;
            --neolock-wm-size: <?php echo esc_attr(absint($config['watermark_size'] ?? 22)); ?>px;
            --neolock-wm-angle: <?php echo esc_attr(intval($config['watermark_angle'] ?? -28)); ?>deg;
            --neolock-wm-color: <?php echo esc_attr($config['watermark_color'] ?? '#33ff77'); ?>;
        }
    </style>
    <?php
}

add_action('wp_body_open', 'neolock_render_shield_and_watermark');
add_action('wp_footer', 'neolock_render_shield_and_watermark');
add_action('in_admin_header', 'neolock_render_shield_and_watermark');
add_action('admin_footer', 'neolock_render_shield_and_watermark');
function neolock_render_shield_and_watermark() {
    static $rendered = false;
    if ($rendered) return;

    $config = neolock_get_current_config();
    if (!$config) return;

    $rendered = true;

    echo '<script>document.documentElement.classList.add("neolock-active"); if(document.body){document.body.classList.add("neolock-active");}</script>';

    if (!empty($config['disable_print'])) {
        echo '<div id="neolock-print-warning" style="display:none;position:fixed;inset:0;place-items:center;color:#fff;background:#000;font-family:monospace;font-size:22px;padding:40px;text-align:center;">PRINTING DISABLED<br><small>Content is protected by NeoLock</small></div>';
    }

    if (!empty($config['watermark_enabled'])) {
        $text = esc_html($config['watermark_text'] ?: get_bloginfo('name'));
        echo '<div id="neolock-watermark" class="neolock-watermark" aria-hidden="true" data-text="' . esc_attr($text) . '"></div>';
    }

    if (!empty($config['screenshot_protection'])) {
        echo '<div id="neolock-shield" class="neolock-shield" aria-hidden="true"></div>';
    }
}

add_action('admin_print_footer_scripts', 'neolock_footer_admin_class');
add_action('wp_footer', 'neolock_footer_admin_class');
function neolock_footer_admin_class() {
    $config = neolock_get_current_config();
    if (!$config) return;
    ?>
    <script>
        (function(){
            try {
                document.documentElement.classList.add('neolock-active');
                document.body && document.body.classList.add('neolock-active');
            } catch(e){}
        })();
    </script>
    <?php
}
