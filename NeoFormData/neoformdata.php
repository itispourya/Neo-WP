<?php
/**
 * Plugin Name: NeoFormData
 * Plugin URI: https://neonotion.net/
 * Description: Form Manager
 * Version: 8
 * Author: NeoNotion
 * Programmer: Pourya Omidian
 * Author URI: https://neonotion.net/
 * Text Domain: neoformdata
 */

if (!defined('ABSPATH')) {
    exit;
}

define('NEOFORMDATA_VERSION', '8');
define('NEOFORMDATA_FILE', __FILE__);
define('NEOFORMDATA_URL', plugin_dir_url(__FILE__));

global $wpdb;
define('NEOFORMDATA_TABLE_FORMS', $wpdb->prefix . 'neoformdata_forms');
define('NEOFORMDATA_TABLE_ENTRIES', $wpdb->prefix . 'neoformdata_entries');

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

register_activation_hook(__FILE__, 'neoformdata_activate');

function neoformdata_activate() {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    $forms_sql = "CREATE TABLE " . NEOFORMDATA_TABLE_FORMS . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(191) NOT NULL,
        fields LONGTEXT NOT NULL,
        styles LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) {$charset_collate};";

    $entries_sql = "CREATE TABLE " . NEOFORMDATA_TABLE_ENTRIES . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        form_id BIGINT UNSIGNED NOT NULL,
        entry_data LONGTEXT NOT NULL,
        ip_address VARCHAR(100) NOT NULL DEFAULT '',
        submitted_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY form_id (form_id)
    ) {$charset_collate};";

    dbDelta($forms_sql);
    dbDelta($entries_sql);

    add_option('neoformdata_settings', neoformdata_default_settings());
}

function neoformdata_default_settings() {
    return array(
        'admin_email'      => get_option('admin_email'),
        'font_family'      => 'Arial, sans-serif',
        'text_color'       => '#d7ffe6',
        'label_color'      => '#9dff7a',
        'background_color' => '#071019',
        'input_background' => '#05070a',
        'border_color'     => '#2a8f58',
        'input_text_color' => '#f4fff8',
        'button_label'     => 'Submit',
        'button_bg'        => '#0f7b38',
        'button_text'      => '#ffffff',
        'border_radius'    => 10,
        'field_spacing'    => 16,
        'wrapper_width'    => 780,
        'wrapper_alignment'=> 'center',
        'form_text_align'  => 'left',
        'label_alignment'  => 'left',
        'input_text_align' => 'left',
        'button_alignment' => 'left',
        'button_width'     => 'auto',
        'message_alignment'=> 'left',
    );
}

add_action('admin_menu', 'neoformdata_admin_menu');
function neoformdata_admin_menu() {
    neonotion_register_root_menu();

    add_submenu_page(
        'neonotion',
        'Form Data',
        'Form Data',
        'manage_options',
        'neoformdata',
        'neoformdata_render_admin_page'
    );
}

add_action('admin_enqueue_scripts', 'neoformdata_admin_assets');
function neoformdata_admin_assets($hook) {
    if (strpos($hook, 'neoformdata') === false && strpos($hook, 'neonotion') === false) {
        return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style('neoformdata-admin', NEOFORMDATA_URL . 'assets/css/admin.css', array('wp-color-picker'), NEOFORMDATA_VERSION);
    wp_enqueue_script('neoformdata-admin', NEOFORMDATA_URL . 'assets/js/neoformdata.js', array('jquery', 'wp-color-picker'), NEOFORMDATA_VERSION, true);
}

function neoformdata_get_forms() {
    global $wpdb;

    return $wpdb->get_results("SELECT * FROM " . NEOFORMDATA_TABLE_FORMS . " ORDER BY updated_at DESC");
}

function neoformdata_get_form($id) {
    global $wpdb;

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . NEOFORMDATA_TABLE_FORMS . " WHERE id = %d", $id));
}

function neoformdata_get_entries($form_id) {
    global $wpdb;

    return $wpdb->get_results($wpdb->prepare("SELECT * FROM " . NEOFORMDATA_TABLE_ENTRIES . " WHERE form_id = %d ORDER BY submitted_at DESC", $form_id));
}

function neoformdata_get_entry($entry_id) {
    global $wpdb;

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . NEOFORMDATA_TABLE_ENTRIES . " WHERE id = %d", $entry_id));
}

function neoformdata_sanitize_fields($fields) {
    $sanitized = array();
    $allowed   = array('text', 'number', 'tel', 'url', 'email', 'date', 'textarea');

    foreach ((array) $fields as $index => $field) {
        $label = sanitize_text_field($field['label'] ?? '');
        $type  = sanitize_key($field['type'] ?? 'text');

        if (!$label || !in_array($type, $allowed, true)) {
            continue;
        }

        $slug = sanitize_title($field['slug'] ?? $label);
        if (!$slug) {
            $slug = 'field-' . ($index + 1);
        }

        $sanitized[] = array(
            'label'       => $label,
            'slug'        => $slug . '-' . ($index + 1),
            'type'        => $type,
            'placeholder' => sanitize_text_field($field['placeholder'] ?? ''),
            'required'    => empty($field['required']) ? 0 : 1,
        );
    }

    return $sanitized;
}

function neoformdata_sanitize_styles($styles) {
    $defaults = neoformdata_default_settings();

    return array(
        'font_family'      => sanitize_text_field($styles['font_family'] ?? $defaults['font_family']),
        'text_color'       => sanitize_hex_color($styles['text_color'] ?? $defaults['text_color']) ?: $defaults['text_color'],
        'label_color'      => sanitize_hex_color($styles['label_color'] ?? $defaults['label_color']) ?: $defaults['label_color'],
        'background_color' => sanitize_hex_color($styles['background_color'] ?? $defaults['background_color']) ?: $defaults['background_color'],
        'input_background' => sanitize_hex_color($styles['input_background'] ?? $defaults['input_background']) ?: $defaults['input_background'],
        'border_color'     => sanitize_hex_color($styles['border_color'] ?? $defaults['border_color']) ?: $defaults['border_color'],
        'input_text_color' => sanitize_hex_color($styles['input_text_color'] ?? $defaults['input_text_color']) ?: $defaults['input_text_color'],
        'button_label'     => sanitize_text_field($styles['button_label'] ?? $defaults['button_label']),
        'button_bg'        => sanitize_hex_color($styles['button_bg'] ?? $defaults['button_bg']) ?: $defaults['button_bg'],
        'button_text'      => sanitize_hex_color($styles['button_text'] ?? $defaults['button_text']) ?: $defaults['button_text'],
        'border_radius'    => max(0, absint($styles['border_radius'] ?? $defaults['border_radius'])),
        'field_spacing'    => max(8, absint($styles['field_spacing'] ?? $defaults['field_spacing'])),
        'wrapper_width'    => max(280, absint($styles['wrapper_width'] ?? $defaults['wrapper_width'])),
        'wrapper_alignment'=> in_array(($styles['wrapper_alignment'] ?? ''), array('left', 'center', 'right'), true) ? $styles['wrapper_alignment'] : $defaults['wrapper_alignment'],
        'form_text_align'  => in_array(($styles['form_text_align'] ?? ''), array('left', 'center', 'right'), true) ? $styles['form_text_align'] : $defaults['form_text_align'],
        'label_alignment'  => in_array(($styles['label_alignment'] ?? ''), array('left', 'center', 'right'), true) ? $styles['label_alignment'] : $defaults['label_alignment'],
        'input_text_align' => in_array(($styles['input_text_align'] ?? ''), array('left', 'center', 'right'), true) ? $styles['input_text_align'] : $defaults['input_text_align'],
        'button_alignment' => in_array(($styles['button_alignment'] ?? ''), array('left', 'center', 'right'), true) ? $styles['button_alignment'] : $defaults['button_alignment'],
        'button_width'     => in_array(($styles['button_width'] ?? ''), array('auto', 'full'), true) ? $styles['button_width'] : $defaults['button_width'],
        'message_alignment'=> in_array(($styles['message_alignment'] ?? ''), array('left', 'center', 'right'), true) ? $styles['message_alignment'] : $defaults['message_alignment'],
    );
}

function neoformdata_alignment_to_margin($alignment) {
    switch ($alignment) {
        case 'center':
            return '0 auto';
        case 'right':
            return '0 0 0 auto';
        case 'left':
        default:
            return '0 auto 0 0';
    }
}

function neoformdata_alignment_to_flex($alignment) {
    switch ($alignment) {
        case 'center':
            return 'center';
        case 'right':
            return 'flex-end';
        case 'left':
        default:
            return 'flex-start';
    }
}

function neoformdata_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $action   = sanitize_key($_GET['action'] ?? 'list');
    $form_id  = absint($_GET['form_id'] ?? 0);
    $entry_id = absint($_GET['entry_id'] ?? 0);
    $notice   = '';

    if (!empty($_POST['neoformdata_save_settings']) && check_admin_referer('neoformdata_save_settings', 'neoformdata_settings_nonce')) {
        update_option(
            'neoformdata_settings',
            array_merge(
                neoformdata_default_settings(),
                neoformdata_sanitize_styles($_POST['styles'] ?? array()),
                array(
                    'admin_email' => sanitize_email($_POST['settings']['admin_email'] ?? get_option('admin_email')),
                )
            )
        );
        $notice = 'Plugin settings were saved.';
    }

    if (!empty($_POST['neoformdata_save_form']) && check_admin_referer('neoformdata_save_form', 'neoformdata_form_nonce')) {
        global $wpdb;

        $saved_form_id = absint($_POST['form_id'] ?? 0);
        $fields        = neoformdata_sanitize_fields($_POST['fields'] ?? array());
        $styles        = neoformdata_sanitize_styles($_POST['styles'] ?? array());
        $title         = sanitize_text_field($_POST['form_title'] ?? 'Untitled Form');

        $payload = array(
            'title'      => $title,
            'fields'     => wp_json_encode($fields),
            'styles'     => wp_json_encode($styles),
            'updated_at' => current_time('mysql'),
        );

        if ($saved_form_id > 0) {
            $wpdb->update(NEOFORMDATA_TABLE_FORMS, $payload, array('id' => $saved_form_id));
            $form_id = $saved_form_id;
            $action  = 'edit';
            $notice  = 'Form updated successfully.';
        } else {
            $payload['created_at'] = current_time('mysql');
            $wpdb->insert(NEOFORMDATA_TABLE_FORMS, $payload);
            $form_id = absint($wpdb->insert_id);
            $action  = 'edit';
            $notice  = 'Form created successfully.';
        }
    }

    if ($action === 'delete' && $form_id && check_admin_referer('neoformdata_delete_form_' . $form_id)) {
        global $wpdb;
        $wpdb->delete(NEOFORMDATA_TABLE_FORMS, array('id' => $form_id));
        $wpdb->delete(NEOFORMDATA_TABLE_ENTRIES, array('form_id' => $form_id));
        $action = 'list';
        $notice = 'Form deleted successfully.';
    }

    if ($action === 'delete_entry' && $entry_id && check_admin_referer('neoformdata_delete_entry_' . $entry_id)) {
        global $wpdb;
        $wpdb->delete(NEOFORMDATA_TABLE_ENTRIES, array('id' => $entry_id));
        $action = 'submissions';
        $notice = 'Submission deleted successfully.';
    }

    if (!empty($_POST['neoformdata_save_entry']) && check_admin_referer('neoformdata_save_entry', 'neoformdata_entry_nonce')) {
        global $wpdb;
        $entry_id = absint($_POST['entry_id'] ?? 0);
        $form_id  = absint($_POST['form_id'] ?? 0);
        $form     = neoformdata_get_form($form_id);
        $fields   = $form ? json_decode($form->fields, true) : array();
        $payload  = array();

        foreach ($fields as $field) {
            $slug              = $field['slug'];
            $payload[$slug]    = array(
                'label' => $field['label'],
                'type'  => $field['type'],
                'value' => sanitize_textarea_field($_POST['entry_data'][$slug] ?? ''),
            );
        }

        $wpdb->update(
            NEOFORMDATA_TABLE_ENTRIES,
            array(
                'entry_data' => wp_json_encode($payload),
            ),
            array('id' => $entry_id)
        );

        $action = 'edit_entry';
        $notice = 'Submission updated successfully.';
    }

    $settings = array_merge(neoformdata_default_settings(), (array) get_option('neoformdata_settings', array()));
    $forms    = neoformdata_get_forms();
    $form     = $form_id ? neoformdata_get_form($form_id) : null;
    $entry    = $entry_id ? neoformdata_get_entry($entry_id) : null;
    ?>
    <div class="wrap neo-admin-shell">
        <div class="neo-admin-card">
            <div class="neo-admin-heading">
                <div>
                    <h1>NeoFormData</h1>
                    <p>Create flexible contact forms, generate unique Neo shortcodes, and manage user submissions.</p>
                </div>
                <div class="neo-admin-badge">NeoNotion / Form Data</div>
            </div>

            <?php if ($notice) : ?>
                <div class="neo-notice"><?php echo esc_html($notice); ?></div>
            <?php endif; ?>

            <nav class="neo-subnav">
                <a class="button <?php echo $action === 'list' ? 'button-primary' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=neoformdata')); ?>">Forms</a>
                <a class="button <?php echo $action === 'create' ? 'button-primary' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=neoformdata&action=create')); ?>">Create Form</a>
                <a class="button <?php echo $action === 'settings' ? 'button-primary' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=neoformdata&action=settings')); ?>">Plugin Settings</a>
            </nav>

            <?php if ($action === 'create' || $action === 'edit') : ?>
                <?php
                $existing_fields = $form ? json_decode($form->fields, true) : array(
                    array(
                        'label'       => 'Full Name',
                        'slug'        => 'full-name-1',
                        'type'        => 'text',
                        'placeholder' => 'Type your full name',
                        'required'    => 1,
                    ),
                );
                $existing_styles = array_merge(neoformdata_default_settings(), $form ? (array) json_decode($form->styles, true) : array());
                ?>
                <form method="post">
                    <?php wp_nonce_field('neoformdata_save_form', 'neoformdata_form_nonce'); ?>
                    <input type="hidden" name="form_id" value="<?php echo esc_attr($form ? $form->id : 0); ?>">

                    <div class="neo-panel-grid">
                        <section class="neo-panel">
                            <h2>Form Builder</h2>
                            <div class="neo-grid-row">
                                <label class="neo-label" for="neoformdata_form_title">Form title</label>
                                <div class="neo-field-wrap">
                                    <input type="text" id="neoformdata_form_title" name="form_title" value="<?php echo esc_attr($form ? $form->title : ''); ?>" class="regular-text" required>
                                </div>
                            </div>

                            <div class="neo-builder-toolbar">
                                <button type="button" class="button" id="neoformdata-add-field">Add Field</button>
                            </div>

                            <div id="neoformdata-field-list">
                                <?php foreach ($existing_fields as $index => $field) : ?>
                                    <?php neoformdata_render_field_builder_row($field, $index); ?>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="neo-panel">
                            <h2>Style Controls</h2>
                            <?php neoformdata_render_style_inputs($existing_styles); ?>
                        </section>
                    </div>

                    <div class="neo-actions">
                        <button type="submit" class="button button-primary" name="neoformdata_save_form" value="1">Save Form</button>
                        <?php if ($form) : ?>
                            <span class="neo-shortcode-pill">Shortcode: <code>[NeoForm_<?php echo esc_html($form->id); ?>]</code></span>
                        <?php endif; ?>
                    </div>
                </form>

                <script type="text/template" id="neoformdata-field-template">
                    <?php neoformdata_render_field_builder_row(array('label' => '', 'slug' => '', 'type' => 'text', 'placeholder' => '', 'required' => 0), '__INDEX__'); ?>
                </script>

            <?php elseif ($action === 'submissions' && $form) : ?>
                <?php $entries = neoformdata_get_entries($form->id); ?>
                <section class="neo-panel neo-panel-full">
                    <h2>Submissions for <?php echo esc_html($form->title); ?></h2>
                    <div class="neo-table-wrap">
                        <table class="widefat striped neo-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Submitted</th>
                                    <th>IP Address</th>
                                    <th>Preview</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($entries) : ?>
                                    <?php foreach ($entries as $entry_item) : ?>
                                        <?php $payload = json_decode($entry_item->entry_data, true); ?>
                                        <tr>
                                            <td><?php echo esc_html($entry_item->id); ?></td>
                                            <td><?php echo esc_html($entry_item->submitted_at); ?></td>
                                            <td><?php echo esc_html($entry_item->ip_address); ?></td>
                                            <td>
                                                <?php foreach ((array) $payload as $item) : ?>
                                                    <div><strong><?php echo esc_html($item['label']); ?>:</strong> <?php echo esc_html(wp_trim_words((string) $item['value'], 8, '...')); ?></div>
                                                <?php endforeach; ?>
                                            </td>
                                            <td>
                                                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=neoformdata&action=edit_entry&form_id=' . $form->id . '&entry_id=' . $entry_item->id)); ?>">Edit</a>
                                                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=neoformdata&action=delete_entry&form_id=' . $form->id . '&entry_id=' . $entry_item->id), 'neoformdata_delete_entry_' . $entry_item->id)); ?>" onclick="return confirm('Delete this submission?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="5">No submissions received yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            <?php elseif ($action === 'edit_entry' && $form && $entry) : ?>
                <?php
                $fields  = json_decode($form->fields, true);
                $payload = (array) json_decode($entry->entry_data, true);
                ?>
                <form method="post">
                    <?php wp_nonce_field('neoformdata_save_entry', 'neoformdata_entry_nonce'); ?>
                    <input type="hidden" name="entry_id" value="<?php echo esc_attr($entry->id); ?>">
                    <input type="hidden" name="form_id" value="<?php echo esc_attr($form->id); ?>">

                    <section class="neo-panel neo-panel-full">
                        <h2>Edit Submission #<?php echo esc_html($entry->id); ?></h2>
                        <p class="neo-muted">Submitted at <?php echo esc_html($entry->submitted_at); ?> from <?php echo esc_html($entry->ip_address); ?></p>
                        <?php foreach ((array) $fields as $field) : ?>
                            <div class="neo-grid-row">
                                <label class="neo-label"><?php echo esc_html($field['label']); ?></label>
                                <div class="neo-field-wrap">
                                    <?php if ($field['type'] === 'textarea') : ?>
                                        <textarea name="entry_data[<?php echo esc_attr($field['slug']); ?>]" rows="4"><?php echo esc_textarea($payload[$field['slug']]['value'] ?? ''); ?></textarea>
                                    <?php else : ?>
                                        <input type="text" name="entry_data[<?php echo esc_attr($field['slug']); ?>]" value="<?php echo esc_attr($payload[$field['slug']]['value'] ?? ''); ?>" class="regular-text">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>

                    <div class="neo-actions">
                        <button type="submit" class="button button-primary" name="neoformdata_save_entry" value="1">Save Submission</button>
                    </div>
                </form>

            <?php elseif ($action === 'settings') : ?>
                <form method="post">
                    <?php wp_nonce_field('neoformdata_save_settings', 'neoformdata_settings_nonce'); ?>
                    <section class="neo-panel neo-panel-full">
                        <h2>Plugin Settings</h2>
                        <div class="neo-grid-row">
                            <label class="neo-label" for="neoformdata_admin_email">Notification email</label>
                            <div class="neo-field-wrap">
                                <input type="email" id="neoformdata_admin_email" name="settings[admin_email]" value="<?php echo esc_attr($settings['admin_email']); ?>" class="regular-text">
                            </div>
                        </div>
                        <?php neoformdata_render_style_inputs($settings); ?>
                    </section>

                    <div class="neo-actions">
                        <button type="submit" class="button button-primary" name="neoformdata_save_settings" value="1">Save Plugin Settings</button>
                    </div>
                </form>

            <?php else : ?>
                <section class="neo-panel neo-panel-full">
                    <h2>Forms List</h2>
                    <div class="neo-table-wrap">
                        <table class="widefat striped neo-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Shortcode</th>
                                    <th>Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($forms) : ?>
                                    <?php foreach ($forms as $form_item) : ?>
                                        <tr>
                                            <td><?php echo esc_html($form_item->title); ?></td>
                                            <td><code>[NeoForm_<?php echo esc_html($form_item->id); ?>]</code></td>
                                            <td><?php echo esc_html($form_item->updated_at); ?></td>
                                            <td>
                                                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=neoformdata&action=edit&form_id=' . $form_item->id)); ?>">Edit</a>
                                                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=neoformdata&action=submissions&form_id=' . $form_item->id)); ?>">View Submissions</a>
                                                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=neoformdata&action=delete&form_id=' . $form_item->id), 'neoformdata_delete_form_' . $form_item->id)); ?>" onclick="return confirm('Delete this form and all submissions?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="4">No forms created yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function neoformdata_render_field_builder_row($field, $index) {
    ?>
    <div class="neo-field-builder" data-index="<?php echo esc_attr($index); ?>">
        <div class="neo-field-builder-grid">
            <input type="text" name="fields[<?php echo esc_attr($index); ?>][label]" value="<?php echo esc_attr($field['label']); ?>" placeholder="Field label">
            <select name="fields[<?php echo esc_attr($index); ?>][type]">
                <option value="text" <?php selected($field['type'], 'text'); ?>>Text</option>
                <option value="number" <?php selected($field['type'], 'number'); ?>>Number</option>
                <option value="tel" <?php selected($field['type'], 'tel'); ?>>Phone</option>
                <option value="url" <?php selected($field['type'], 'url'); ?>>Website URL</option>
                <option value="email" <?php selected($field['type'], 'email'); ?>>Email</option>
                <option value="date" <?php selected($field['type'], 'date'); ?>>Date</option>
                <option value="textarea" <?php selected($field['type'], 'textarea'); ?>>Paragraph</option>
            </select>
            <input type="text" name="fields[<?php echo esc_attr($index); ?>][placeholder]" value="<?php echo esc_attr($field['placeholder']); ?>" placeholder="Placeholder text">
            <label class="neo-inline-check">
                <input type="checkbox" name="fields[<?php echo esc_attr($index); ?>][required]" value="1" <?php checked(!empty($field['required'])); ?>>
                <span>Required</span>
            </label>
            <button type="button" class="button neo-remove-field">Remove</button>
        </div>
    </div>
    <?php
}

function neoformdata_render_style_inputs($values) {
    $map = array(
        'font_family'       => array('label' => 'Font family', 'type' => 'text'),
        'text_color'        => array('label' => 'Text color', 'type' => 'color'),
        'label_color'       => array('label' => 'Label color', 'type' => 'color'),
        'background_color'  => array('label' => 'Form background', 'type' => 'color'),
        'input_background'  => array('label' => 'Input background', 'type' => 'color'),
        'border_color'      => array('label' => 'Border color', 'type' => 'color'),
        'input_text_color'  => array('label' => 'Input text color', 'type' => 'color'),
        'button_label'      => array('label' => 'Submit button label', 'type' => 'text'),
        'button_bg'         => array('label' => 'Submit button background', 'type' => 'color'),
        'button_text'       => array('label' => 'Submit button text', 'type' => 'color'),
        'border_radius'     => array('label' => 'Border radius', 'type' => 'number', 'min' => 0),
        'field_spacing'     => array('label' => 'Field spacing', 'type' => 'number', 'min' => 0),
        'wrapper_width'     => array('label' => 'Form width', 'type' => 'number', 'min' => 280),
        'wrapper_alignment' => array('label' => 'Form box alignment', 'type' => 'select', 'options' => array('left' => 'Left', 'center' => 'Center', 'right' => 'Right')),
        'form_text_align'   => array('label' => 'Main text alignment', 'type' => 'select', 'options' => array('left' => 'Left', 'center' => 'Center', 'right' => 'Right')),
        'label_alignment'   => array('label' => 'Label alignment', 'type' => 'select', 'options' => array('left' => 'Left', 'center' => 'Center', 'right' => 'Right')),
        'input_text_align'  => array('label' => 'Field text alignment', 'type' => 'select', 'options' => array('left' => 'Left', 'center' => 'Center', 'right' => 'Right')),
        'button_alignment'  => array('label' => 'Button alignment', 'type' => 'select', 'options' => array('left' => 'Left', 'center' => 'Center', 'right' => 'Right')),
        'button_width'      => array('label' => 'Button width', 'type' => 'select', 'options' => array('auto' => 'Auto', 'full' => 'Full Width')),
        'message_alignment' => array('label' => 'Message alignment', 'type' => 'select', 'options' => array('left' => 'Left', 'center' => 'Center', 'right' => 'Right')),
    );

    foreach ($map as $key => $config) {
        $value = $values[$key] ?? '';
        echo '<div class="neo-grid-row">';
        echo '<label class="neo-label" for="neoformdata_' . esc_attr($key) . '">' . esc_html($config['label']) . '</label>';
        echo '<div class="neo-field-wrap">';

        if ($config['type'] === 'color') {
            echo '<input type="text" class="neo-color-field" id="neoformdata_' . esc_attr($key) . '" name="styles[' . esc_attr($key) . ']" value="' . esc_attr($value) . '">';
        } elseif ($config['type'] === 'number') {
            echo '<input type="number" id="neoformdata_' . esc_attr($key) . '" name="styles[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" min="' . esc_attr($config['min'] ?? 0) . '" step="1">';
        } elseif ($config['type'] === 'select') {
            echo '<select id="neoformdata_' . esc_attr($key) . '" name="styles[' . esc_attr($key) . ']">';
            foreach ($config['options'] as $option_value => $option_label) {
                echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
            }
            echo '</select>';
        } else {
            echo '<input type="text" id="neoformdata_' . esc_attr($key) . '" name="styles[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" class="regular-text">';
        }

        echo '</div></div>';
    }
}

add_action('init', 'neoformdata_register_shortcodes');
function neoformdata_register_shortcodes() {
    $forms = neoformdata_get_forms();

    add_shortcode('NeoFormData', 'neoformdata_render_generic_shortcode');

    foreach ($forms as $form) {
        add_shortcode(
            'NeoForm_' . $form->id,
            function ($atts = array()) use ($form) {
                return neoformdata_render_form_front($form->id);
            }
        );
    }
}

function neoformdata_render_generic_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'id' => 0,
        ),
        $atts
    );

    return neoformdata_render_form_front(absint($atts['id']));
}

function neoformdata_render_form_front($form_id) {
    $form = neoformdata_get_form($form_id);

    if (!$form) {
        return '';
    }

    $fields   = (array) json_decode($form->fields, true);
    $settings = array_merge(neoformdata_default_settings(), (array) get_option('neoformdata_settings', array()));
    $styles   = array_merge($settings, (array) json_decode($form->styles, true));
    $success  = absint($_GET['neoformdata_sent'] ?? 0) === absint($form_id);
    $wrapper_margin = neoformdata_alignment_to_margin($styles['wrapper_alignment']);
    $button_justify = neoformdata_alignment_to_flex($styles['button_alignment']);

    ob_start();
    ?>
    <div
        class="neoformdata-wrap"
        style="
            --neoform-font: <?php echo esc_attr($styles['font_family']); ?>;
            --neoform-text: <?php echo esc_attr($styles['text_color']); ?>;
            --neoform-label: <?php echo esc_attr($styles['label_color']); ?>;
            --neoform-bg: <?php echo esc_attr($styles['background_color']); ?>;
            --neoform-input-bg: <?php echo esc_attr($styles['input_background']); ?>;
            --neoform-border: <?php echo esc_attr($styles['border_color']); ?>;
            --neoform-input-text: <?php echo esc_attr($styles['input_text_color']); ?>;
            --neoform-button-bg: <?php echo esc_attr($styles['button_bg']); ?>;
            --neoform-button-text: <?php echo esc_attr($styles['button_text']); ?>;
            --neoform-radius: <?php echo esc_attr(intval($styles['border_radius'])); ?>px;
            --neoform-gap: <?php echo esc_attr(intval($styles['field_spacing'])); ?>px;
            --neoform-width: <?php echo esc_attr(intval($styles['wrapper_width'])); ?>px;
            --neoform-margin: <?php echo esc_attr($wrapper_margin); ?>;
            --neoform-text-align: <?php echo esc_attr($styles['form_text_align']); ?>;
            --neoform-label-align: <?php echo esc_attr($styles['label_alignment']); ?>;
            --neoform-input-align: <?php echo esc_attr($styles['input_text_align']); ?>;
            --neoform-button-justify: <?php echo esc_attr($button_justify); ?>;
            --neoform-button-width: <?php echo esc_attr($styles['button_width'] === 'full' ? '100%' : 'auto'); ?>;
            --neoform-message-align: <?php echo esc_attr($styles['message_alignment']); ?>;
        "
    >
        <style>
            .neoformdata-wrap{font-family:var(--neoform-font);color:var(--neoform-text);background:var(--neoform-bg);padding:24px;border:1px solid var(--neoform-border);border-radius:var(--neoform-radius);max-width:var(--neoform-width);width:100%;margin:var(--neoform-margin);text-align:var(--neoform-text-align)}
            .neoformdata-wrap form{display:grid;gap:var(--neoform-gap)}
            .neoformdata-wrap label{display:grid;gap:8px;color:var(--neoform-label);font-weight:600;text-align:var(--neoform-label-align)}
            .neoformdata-wrap input,.neoformdata-wrap textarea{width:100%;background:var(--neoform-input-bg);color:var(--neoform-input-text);border:1px solid var(--neoform-border);border-radius:var(--neoform-radius);padding:12px 14px;text-align:var(--neoform-input-align)}
            .neoformdata-wrap textarea{min-height:140px;resize:vertical}
            .neoformdata-wrap button{background:var(--neoform-button-bg);color:var(--neoform-button-text);border:0;border-radius:var(--neoform-radius);padding:14px 18px;font-weight:700;cursor:pointer;width:var(--neoform-button-width)}
            .neoformdata-button-row{display:flex;justify-content:var(--neoform-button-justify)}
            .neoformdata-success{margin-bottom:16px;padding:12px 14px;background:rgba(15,123,56,.16);border:1px solid rgba(15,123,56,.5);border-radius:var(--neoform-radius);text-align:var(--neoform-message-align)}
        </style>

        <?php if ($success) : ?>
            <div class="neoformdata-success">Thanks. Your form has been submitted successfully.</div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="neoformdata_submit">
            <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
            <input type="hidden" name="redirect_to" value="<?php echo esc_url_raw(get_permalink()); ?>">
            <?php wp_nonce_field('neoformdata_submit_' . $form_id, 'neoformdata_submit_nonce'); ?>

            <?php foreach ($fields as $field) : ?>
                <label>
                    <span><?php echo esc_html($field['label']); ?></span>
                    <?php if ($field['type'] === 'textarea') : ?>
                        <textarea name="neoformdata_fields[<?php echo esc_attr($field['slug']); ?>]" placeholder="<?php echo esc_attr($field['placeholder']); ?>" <?php echo !empty($field['required']) ? 'required' : ''; ?>></textarea>
                    <?php else : ?>
                        <input type="<?php echo esc_attr($field['type']); ?>" name="neoformdata_fields[<?php echo esc_attr($field['slug']); ?>]" placeholder="<?php echo esc_attr($field['placeholder']); ?>" <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>

            <div class="neoformdata-button-row">
                <button type="submit"><?php echo esc_html($styles['button_label']); ?></button>
            </div>
        </form>
    </div>
    <?php

    return ob_get_clean();
}

add_action('admin_post_nopriv_neoformdata_submit', 'neoformdata_handle_form_submit');
add_action('admin_post_neoformdata_submit', 'neoformdata_handle_form_submit');
function neoformdata_handle_form_submit() {
    $form_id     = absint($_POST['form_id'] ?? 0);
    $redirect_to = esc_url_raw($_POST['redirect_to'] ?? home_url('/'));

    if (!$form_id || !wp_verify_nonce($_POST['neoformdata_submit_nonce'] ?? '', 'neoformdata_submit_' . $form_id)) {
        wp_safe_redirect($redirect_to);
        exit;
    }

    $form = neoformdata_get_form($form_id);
    if (!$form) {
        wp_safe_redirect($redirect_to);
        exit;
    }

    $fields   = (array) json_decode($form->fields, true);
    $payload  = array();
    $messages = array();

    foreach ($fields as $field) {
        $raw_value = $_POST['neoformdata_fields'][$field['slug']] ?? '';

        switch ($field['type']) {
            case 'email':
                $value = sanitize_email($raw_value);
                break;
            case 'url':
                $value = esc_url_raw($raw_value);
                break;
            case 'number':
                $value = sanitize_text_field($raw_value);
                break;
            case 'textarea':
                $value = sanitize_textarea_field($raw_value);
                break;
            default:
                $value = sanitize_text_field($raw_value);
        }

        $payload[$field['slug']] = array(
            'label' => $field['label'],
            'type'  => $field['type'],
            'value' => $value,
        );

        $messages[] = $field['label'] . ': ' . $value;
    }

    global $wpdb;
    $wpdb->insert(
        NEOFORMDATA_TABLE_ENTRIES,
        array(
            'form_id'      => $form_id,
            'entry_data'   => wp_json_encode($payload),
            'ip_address'   => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'submitted_at' => current_time('mysql'),
        )
    );

    $settings = array_merge(neoformdata_default_settings(), (array) get_option('neoformdata_settings', array()));
    if (!empty($settings['admin_email'])) {
        wp_mail(
            $settings['admin_email'],
            'New form submission: ' . $form->title,
            implode("\n", $messages)
        );
    }

    $redirect_to = add_query_arg('neoformdata_sent', $form_id, $redirect_to);
    wp_safe_redirect($redirect_to);
    exit;
}
