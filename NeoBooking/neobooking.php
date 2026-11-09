<?php
/**
 * Plugin Name: NeoBooking
 * Plugin URI: https://neonotion.net/
 * Description: Booking service
 * Version: 9
 * Author: NeoNotion
 * Programmer: Pourya Omidian
 * Author URI: https://neonotion.net/
 * Text Domain: neobooking
 */

if (!defined('ABSPATH')) {
    exit;
}

define('NEOBOOKING_VERSION', '9');
define('NEOBOOKING_FILE', __FILE__);
define('NEOBOOKING_URL', plugin_dir_url(__FILE__));

global $wpdb;
define('NEOBOOKING_TABLE', $wpdb->prefix . 'neobooking_entries');

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

register_activation_hook(__FILE__, 'neobooking_activate');

function neobooking_default_working_hours() {
    return array(
        'monday'    => array('enabled' => 1, 'start' => '09:00', 'end' => '17:00'),
        'tuesday'   => array('enabled' => 1, 'start' => '09:00', 'end' => '17:00'),
        'wednesday' => array('enabled' => 1, 'start' => '09:00', 'end' => '17:00'),
        'thursday'  => array('enabled' => 1, 'start' => '09:00', 'end' => '17:00'),
        'friday'    => array('enabled' => 1, 'start' => '09:00', 'end' => '17:00'),
        'saturday'  => array('enabled' => 0, 'start' => '09:00', 'end' => '13:00'),
        'sunday'    => array('enabled' => 0, 'start' => '09:00', 'end' => '13:00'),
    );
}

function neobooking_default_settings() {
    return array(
        'calendar_type'      => 'gregorian',
        'booking_title'      => 'Book Your Appointment',
        'booking_description'=> 'Choose the service, date, and time that work best for you.',
        'submit_label'       => 'Book Appointment',
        'contact_title'      => 'Contact & Location Details',
        'empty_slots_message'=> 'No booking slots are available right now. Please check back later.',
        'services'           => "General Consultation\nProduct Demo\nVIP Support",
        'slot_duration'      => 30,
        'booking_delay_unit' => 'hours',
        'booking_delay_value'=> 2,
        'booking_horizon'    => 21,
        'admin_email'        => get_option('admin_email'),
        'address'            => '',
        'phone'              => '',
        'apple_maps'         => '',
        'google_maps'        => '',
        'waze'               => '',
        'alignment'          => 'center',
        'font_family'        => 'Arial, sans-serif',
        'text_color'         => '#eafff1',
        'accent_color'       => '#33ff77',
        'background_color'   => '#071019',
        'panel_opacity'      => 0.94,
        'disabled_slots'     => '',
        'working_hours'      => neobooking_default_working_hours(),
    );
}

function neobooking_activate() {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE " . NEOBOOKING_TABLE . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        service_label VARCHAR(191) NOT NULL,
        slot_key DATETIME NOT NULL,
        customer_name VARCHAR(191) NOT NULL,
        customer_email VARCHAR(191) NOT NULL,
        customer_phone VARCHAR(80) NOT NULL,
        note LONGTEXT NOT NULL,
        ip_address VARCHAR(100) NOT NULL DEFAULT '',
        token VARCHAR(191) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slot_key (slot_key)
    ) {$charset_collate};";

    dbDelta($sql);

    add_option('neobooking_settings', neobooking_default_settings());
}

add_action('admin_menu', 'neobooking_admin_menu');
function neobooking_admin_menu() {
    neonotion_register_root_menu();

    add_submenu_page(
        'neonotion',
        'Booking System',
        'Booking System',
        'manage_options',
        'neobooking',
        'neobooking_render_admin_page'
    );
}

add_action('admin_enqueue_scripts', 'neobooking_admin_assets');
function neobooking_admin_assets($hook) {
    if (strpos($hook, 'neobooking') === false && strpos($hook, 'neonotion') === false) {
        return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style('neobooking-admin', NEOBOOKING_URL . 'assets/css/admin.css', array('wp-color-picker'), NEOBOOKING_VERSION);
    wp_enqueue_script('neobooking-admin', NEOBOOKING_URL . 'assets/js/neobooking.js', array('jquery', 'wp-color-picker'), NEOBOOKING_VERSION, true);
}

function neobooking_parse_services($raw) {
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
    $lines = array_map('sanitize_text_field', $lines);
    $lines = array_filter(array_map('trim', $lines));
    $lines = array_values($lines);

    if (empty($lines)) {
        $lines[] = 'Appointment';
    }

    return $lines;
}

function neobooking_sanitize_settings($input) {
    $defaults       = neobooking_default_settings();
    $working_hours  = array();
    $default_hours  = neobooking_default_working_hours();

    foreach ($default_hours as $day => $day_config) {
        $working_hours[$day] = array(
            'enabled' => empty($input['working_hours'][$day]['enabled']) ? 0 : 1,
            'start'   => sanitize_text_field($input['working_hours'][$day]['start'] ?? $day_config['start']),
            'end'     => sanitize_text_field($input['working_hours'][$day]['end'] ?? $day_config['end']),
        );
    }

    return array(
        'calendar_type'       => in_array(($input['calendar_type'] ?? ''), array('gregorian', 'persian', 'hijri'), true) ? $input['calendar_type'] : $defaults['calendar_type'],
        'booking_title'       => sanitize_text_field($input['booking_title'] ?? $defaults['booking_title']),
        'booking_description' => sanitize_textarea_field($input['booking_description'] ?? $defaults['booking_description']),
        'submit_label'        => sanitize_text_field($input['submit_label'] ?? $defaults['submit_label']),
        'contact_title'       => sanitize_text_field($input['contact_title'] ?? $defaults['contact_title']),
        'empty_slots_message' => sanitize_text_field($input['empty_slots_message'] ?? $defaults['empty_slots_message']),
        'services'            => implode("\n", neobooking_parse_services($input['services'] ?? $defaults['services'])),
        'slot_duration'       => max(15, absint($input['slot_duration'] ?? $defaults['slot_duration'])),
        'booking_delay_unit'  => in_array(($input['booking_delay_unit'] ?? ''), array('hours', 'days'), true) ? $input['booking_delay_unit'] : $defaults['booking_delay_unit'],
        'booking_delay_value' => max(0, absint($input['booking_delay_value'] ?? $defaults['booking_delay_value'])),
        'booking_horizon'     => max(1, absint($input['booking_horizon'] ?? $defaults['booking_horizon'])),
        'admin_email'         => sanitize_email($input['admin_email'] ?? $defaults['admin_email']),
        'address'             => sanitize_textarea_field($input['address'] ?? ''),
        'phone'               => sanitize_text_field($input['phone'] ?? ''),
        'apple_maps'          => esc_url_raw($input['apple_maps'] ?? ''),
        'google_maps'         => esc_url_raw($input['google_maps'] ?? ''),
        'waze'                => esc_url_raw($input['waze'] ?? ''),
        'alignment'           => in_array(($input['alignment'] ?? ''), array('left', 'center', 'right'), true) ? $input['alignment'] : $defaults['alignment'],
        'font_family'         => sanitize_text_field($input['font_family'] ?? $defaults['font_family']),
        'text_color'          => sanitize_hex_color($input['text_color'] ?? $defaults['text_color']) ?: $defaults['text_color'],
        'accent_color'        => sanitize_hex_color($input['accent_color'] ?? $defaults['accent_color']) ?: $defaults['accent_color'],
        'background_color'    => sanitize_hex_color($input['background_color'] ?? $defaults['background_color']) ?: $defaults['background_color'],
        'panel_opacity'       => min(1, max(0.2, floatval($input['panel_opacity'] ?? $defaults['panel_opacity']))),
        'disabled_slots'      => sanitize_textarea_field($input['disabled_slots'] ?? ''),
        'working_hours'       => $working_hours,
    );
}

function neobooking_get_bookings() {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM " . NEOBOOKING_TABLE . " ORDER BY slot_key ASC");
}

function neobooking_get_booking($id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . NEOBOOKING_TABLE . " WHERE id = %d", $id));
}

function neobooking_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $tab       = sanitize_key($_GET['tab'] ?? 'settings');
    $booking_id = absint($_GET['booking_id'] ?? 0);
    $notice    = '';

    if (!empty($_POST['neobooking_save_settings']) && check_admin_referer('neobooking_save_settings', 'neobooking_settings_nonce')) {
        update_option('neobooking_settings', neobooking_sanitize_settings($_POST['settings'] ?? array()));
        $notice = 'Booking settings were saved.';
    }

    if ($tab === 'delete_booking' && $booking_id && check_admin_referer('neobooking_delete_booking_' . $booking_id)) {
        global $wpdb;
        $wpdb->delete(NEOBOOKING_TABLE, array('id' => $booking_id));
        $tab    = 'bookings';
        $notice = 'Booking deleted and the slot is now available again.';
    }

    if (!empty($_POST['neobooking_save_booking']) && check_admin_referer('neobooking_save_booking', 'neobooking_booking_nonce')) {
        global $wpdb;

        $booking_id = absint($_POST['booking_id'] ?? 0);
        $wpdb->update(
            NEOBOOKING_TABLE,
            array(
                'service_label'  => sanitize_text_field($_POST['service_label'] ?? ''),
                'slot_key'       => sanitize_text_field($_POST['slot_key'] ?? ''),
                'customer_name'  => sanitize_text_field($_POST['customer_name'] ?? ''),
                'customer_email' => sanitize_email($_POST['customer_email'] ?? ''),
                'customer_phone' => sanitize_text_field($_POST['customer_phone'] ?? ''),
                'note'           => sanitize_textarea_field($_POST['note'] ?? ''),
                'ip_address'     => sanitize_text_field($_POST['ip_address'] ?? ''),
            ),
            array('id' => $booking_id)
        );

        $tab    = 'bookings';
        $notice = 'Booking updated successfully.';
    }

    $settings = array_merge(neobooking_default_settings(), (array) get_option('neobooking_settings', array()));
    $availability = neobooking_generate_availability($settings);
    ?>
    <div class="wrap neo-admin-shell">
        <div class="neo-admin-card">
            <div class="neo-admin-heading">
                <div>
                    <h1>NeoBooking</h1>
                    <p>Build bookable time slots, lock reserved appointments, and share add-to-calendar links with visitors.</p>
                </div>
                <div class="neo-admin-badge">NeoNotion / Booking System</div>
            </div>

            <?php if ($notice) : ?>
                <div class="neo-notice"><?php echo esc_html($notice); ?></div>
            <?php endif; ?>

            <nav class="neo-subnav">
                <a class="button <?php echo $tab === 'settings' ? 'button-primary' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=neobooking&tab=settings')); ?>">Settings</a>
                <a class="button <?php echo $tab === 'bookings' ? 'button-primary' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=neobooking&tab=bookings')); ?>">Booked Slots</a>
            </nav>

            <?php if ($tab === 'edit_booking' && $booking_id) : ?>
                <?php $booking = neobooking_get_booking($booking_id); ?>
                <?php if ($booking) : ?>
                    <form method="post">
                        <?php wp_nonce_field('neobooking_save_booking', 'neobooking_booking_nonce'); ?>
                        <input type="hidden" name="booking_id" value="<?php echo esc_attr($booking->id); ?>">
                        <section class="neo-panel neo-panel-full">
                            <h2>Edit Booking #<?php echo esc_html($booking->id); ?></h2>
                            <?php
                            $fields = array(
                                'service_label'  => 'Service',
                                'slot_key'       => 'Slot DateTime',
                                'customer_name'  => 'Customer Name',
                                'customer_email' => 'Customer Email',
                                'customer_phone' => 'Customer Phone',
                                'ip_address'     => 'IP Address',
                            );
                            foreach ($fields as $key => $label) :
                                ?>
                                <div class="neo-grid-row">
                                    <label class="neo-label" for="neobooking_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
                                    <div class="neo-field-wrap">
                                        <input type="text" id="neobooking_<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($booking->$key); ?>" class="regular-text">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="neo-grid-row">
                                <label class="neo-label" for="neobooking_note">Note</label>
                                <div class="neo-field-wrap">
                                    <textarea id="neobooking_note" name="note" rows="4"><?php echo esc_textarea($booking->note); ?></textarea>
                                </div>
                            </div>
                        </section>
                        <div class="neo-actions">
                            <button type="submit" class="button button-primary" name="neobooking_save_booking" value="1">Save Booking</button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php elseif ($tab === 'bookings') : ?>
                <?php $bookings = neobooking_get_bookings(); ?>
                <section class="neo-panel neo-panel-full">
                    <h2>Booked Slots</h2>
                    <div class="neo-table-wrap">
                        <table class="widefat striped neo-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Service</th>
                                    <th>Slot</th>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>IP</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($bookings) : ?>
                                    <?php foreach ($bookings as $booking) : ?>
                                        <tr>
                                            <td><?php echo esc_html($booking->id); ?></td>
                                            <td><?php echo esc_html($booking->service_label); ?></td>
                                            <td><?php echo esc_html($booking->slot_key); ?></td>
                                            <td><?php echo esc_html($booking->customer_name); ?></td>
                                            <td>
                                                <div><?php echo esc_html($booking->customer_email); ?></div>
                                                <div><?php echo esc_html($booking->customer_phone); ?></div>
                                            </td>
                                            <td><?php echo esc_html($booking->ip_address); ?></td>
                                            <td>
                                                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=neobooking&tab=edit_booking&booking_id=' . $booking->id)); ?>">Edit</a>
                                                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=neobooking&tab=delete_booking&booking_id=' . $booking->id), 'neobooking_delete_booking_' . $booking->id)); ?>" onclick="return confirm('Delete this booking and free the slot?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="7">No bookings yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php else : ?>
                <form method="post">
                    <?php wp_nonce_field('neobooking_save_settings', 'neobooking_settings_nonce'); ?>

                    <div class="neo-panel-grid">
                        <section class="neo-panel">
                            <h2>Booking Engine</h2>
                            <?php neobooking_render_setting_row('Calendar Type', '<select name="settings[calendar_type]"><option value="gregorian" ' . selected($settings['calendar_type'], 'gregorian', false) . '>Gregorian</option><option value="persian" ' . selected($settings['calendar_type'], 'persian', false) . '>Persian</option><option value="hijri" ' . selected($settings['calendar_type'], 'hijri', false) . '>Hijri</option></select>'); ?>
                            <?php neobooking_render_setting_row('Booking title', '<input type="text" name="settings[booking_title]" value="' . esc_attr($settings['booking_title']) . '" class="regular-text">'); ?>
                            <?php neobooking_render_setting_row('Booking description', '<textarea name="settings[booking_description]" rows="3">' . esc_textarea($settings['booking_description']) . '</textarea>'); ?>
                            <?php neobooking_render_setting_row('Submit button label', '<input type="text" name="settings[submit_label]" value="' . esc_attr($settings['submit_label']) . '" class="regular-text">'); ?>
                            <?php neobooking_render_setting_row('Bookable items', '<textarea name="settings[services]" rows="5">' . esc_textarea($settings['services']) . '</textarea><p class="description">One service per line.</p>'); ?>
                            <?php neobooking_render_setting_row('Slot duration', '<input type="number" name="settings[slot_duration]" value="' . esc_attr($settings['slot_duration']) . '" min="15" step="15"> minutes'); ?>
                            <?php neobooking_render_setting_row('First available delay', '<div class="neo-inline-group"><input type="number" name="settings[booking_delay_value]" value="' . esc_attr($settings['booking_delay_value']) . '" min="0" step="1"><select name="settings[booking_delay_unit]"><option value="hours" ' . selected($settings['booking_delay_unit'], 'hours', false) . '>Hours</option><option value="days" ' . selected($settings['booking_delay_unit'], 'days', false) . '>Days</option></select></div>'); ?>
                            <?php neobooking_render_setting_row('Booking horizon', '<input type="number" name="settings[booking_horizon]" value="' . esc_attr($settings['booking_horizon']) . '" min="1" step="1"> days'); ?>
                            <?php neobooking_render_setting_row('Empty state message', '<input type="text" name="settings[empty_slots_message]" value="' . esc_attr($settings['empty_slots_message']) . '" class="regular-text">'); ?>
                            <?php neobooking_render_setting_row('Notification email', '<input type="email" name="settings[admin_email]" value="' . esc_attr($settings['admin_email']) . '" class="regular-text">'); ?>
                            <?php neobooking_render_setting_row('Content alignment', '<select name="settings[alignment]"><option value="left" ' . selected($settings['alignment'], 'left', false) . '>Left</option><option value="center" ' . selected($settings['alignment'], 'center', false) . '>Center</option><option value="right" ' . selected($settings['alignment'], 'right', false) . '>Right</option></select>'); ?>
                        </section>

                        <section class="neo-panel">
                            <h2>Contact & Location</h2>
                            <?php neobooking_render_setting_row('Contact panel title', '<input type="text" name="settings[contact_title]" value="' . esc_attr($settings['contact_title']) . '" class="regular-text">'); ?>
                            <?php neobooking_render_setting_row('Address', '<textarea name="settings[address]" rows="4">' . esc_textarea($settings['address']) . '</textarea>'); ?>
                            <?php neobooking_render_setting_row('Phone', '<input type="text" name="settings[phone]" value="' . esc_attr($settings['phone']) . '" class="regular-text">'); ?>
                            <?php neobooking_render_setting_row('Apple Maps URL', '<input type="url" name="settings[apple_maps]" value="' . esc_attr($settings['apple_maps']) . '" class="regular-text">'); ?>
                            <?php neobooking_render_setting_row('Google Maps URL', '<input type="url" name="settings[google_maps]" value="' . esc_attr($settings['google_maps']) . '" class="regular-text">'); ?>
                            <?php neobooking_render_setting_row('Waze URL', '<input type="url" name="settings[waze]" value="' . esc_attr($settings['waze']) . '" class="regular-text">'); ?>
                            <?php neobooking_render_setting_row('Shortcode', '<code>[NeoBooking]</code>'); ?>
                        </section>
                    </div>

                    <div class="neo-panel-grid">
                        <section class="neo-panel">
                            <h2>Appearance</h2>
                            <?php neobooking_render_setting_row('Font family', '<input type="text" name="settings[font_family]" value="' . esc_attr($settings['font_family']) . '" class="regular-text">'); ?>
                            <?php neobooking_render_setting_row('Text color', '<input type="text" name="settings[text_color]" value="' . esc_attr($settings['text_color']) . '" class="neo-color-field">'); ?>
                            <?php neobooking_render_setting_row('Accent color', '<input type="text" name="settings[accent_color]" value="' . esc_attr($settings['accent_color']) . '" class="neo-color-field">'); ?>
                            <?php neobooking_render_setting_row('Background color', '<input type="text" name="settings[background_color]" value="' . esc_attr($settings['background_color']) . '" class="neo-color-field">'); ?>
                            <?php neobooking_render_setting_row('Panel opacity', '<input type="number" name="settings[panel_opacity]" value="' . esc_attr($settings['panel_opacity']) . '" min="0.2" max="1" step="0.05">'); ?>
                        </section>

                        <section class="neo-panel">
                            <h2>Blocked Slots</h2>
                            <?php neobooking_render_setting_row('Disabled slot list', '<textarea name="settings[disabled_slots]" rows="8" placeholder="2026-08-01 10:00:00&#10;2026-08-01 10:30:00">' . esc_textarea($settings['disabled_slots']) . '</textarea><p class="description">Add one slot per line to mark it as unavailable.</p>'); ?>
                        </section>
                    </div>

                    <section class="neo-panel neo-panel-full">
                        <h2>Working Hours</h2>
                        <div class="neo-table-wrap">
                            <table class="widefat striped neo-table">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Enabled</th>
                                        <th>Start</th>
                                        <th>End</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (neobooking_default_working_hours() as $day => $day_data) : ?>
                                        <?php $current = $settings['working_hours'][$day] ?? $day_data; ?>
                                        <tr>
                                            <td><?php echo esc_html(ucfirst($day)); ?></td>
                                            <td><input type="checkbox" name="settings[working_hours][<?php echo esc_attr($day); ?>][enabled]" value="1" <?php checked(!empty($current['enabled'])); ?>></td>
                                            <td><input type="time" name="settings[working_hours][<?php echo esc_attr($day); ?>][start]" value="<?php echo esc_attr($current['start']); ?>"></td>
                                            <td><input type="time" name="settings[working_hours][<?php echo esc_attr($day); ?>][end]" value="<?php echo esc_attr($current['end']); ?>"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="neo-panel neo-panel-full">
                        <h2>Publish & Availability</h2>
                        <div class="neo-callout">
                            <p><strong>Use this shortcode:</strong> <code>[NeoBooking]</code></p>
                            <p><strong>Available dates right now:</strong> <?php echo esc_html(count($availability)); ?></p>
                            <p><strong>Total open time slots:</strong> <?php echo esc_html(neobooking_count_open_slots($availability)); ?></p>
                            <?php if (!empty($availability)) : ?>
                                <div class="neo-availability-preview">
                                    <?php foreach (array_slice($availability, 0, 5, true) as $date_key => $slots) : ?>
                                        <div class="neo-preview-day">
                                            <strong><?php echo esc_html($date_key); ?></strong>
                                            <span><?php echo esc_html(count($slots)); ?> open slots</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <p class="neo-muted">No open slots are currently generated from your working hours and blocked-slot rules.</p>
                            <?php endif; ?>
                        </div>
                    </section>

                    <div class="neo-actions">
                        <button type="submit" class="button button-primary" name="neobooking_save_settings" value="1">Save Booking Settings</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function neobooking_render_setting_row($label, $html) {
    echo '<div class="neo-grid-row"><label class="neo-label">' . esc_html($label) . '</label><div class="neo-field-wrap">' . $html . '</div></div>';
}

function neobooking_count_open_slots($availability) {
    $count = 0;

    foreach ((array) $availability as $slots) {
        $count += count((array) $slots);
    }

    return $count;
}

function neobooking_get_disabled_slots($settings) {
    $lines = preg_split('/\r\n|\r|\n/', (string) ($settings['disabled_slots'] ?? ''));
    $slots = array();

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line) {
            $slots[$line] = true;
        }
    }

    return $slots;
}

function neobooking_get_booked_slot_map() {
    global $wpdb;
    $bookings = $wpdb->get_col("SELECT slot_key FROM " . NEOBOOKING_TABLE);
    $map      = array();

    foreach ((array) $bookings as $slot) {
        $map[$slot] = true;
    }

    return $map;
}

function neobooking_generate_availability($settings) {
    $timezone     = wp_timezone();
    $now          = new DateTime('now', $timezone);
    $delay_value  = absint($settings['booking_delay_value']);
    $delay_unit   = $settings['booking_delay_unit'];
    $slot_minutes = max(15, absint($settings['slot_duration']));
    $horizon      = max(1, absint($settings['booking_horizon']));
    $disabled     = neobooking_get_disabled_slots($settings);
    $booked       = neobooking_get_booked_slot_map();
    $availability = array();

    if ($delay_value > 0) {
        $now->modify('+' . $delay_value . ' ' . $delay_unit);
    }

    for ($i = 0; $i < $horizon; $i++) {
        $day = clone $now;
        $day->setTime(0, 0, 0);
        if ($i > 0) {
            $day->modify('+' . $i . ' day');
        }

        $day_name = strtolower($day->format('l'));
        $hours    = $settings['working_hours'][$day_name] ?? array('enabled' => 0);

        if (empty($hours['enabled'])) {
            continue;
        }

        $start = DateTime::createFromFormat('Y-m-d H:i', $day->format('Y-m-d') . ' ' . $hours['start'], $timezone);
        $end   = DateTime::createFromFormat('Y-m-d H:i', $day->format('Y-m-d') . ' ' . $hours['end'], $timezone);

        if (!$start || !$end || $end <= $start) {
            continue;
        }

        while ($start < $end) {
            $slot = clone $start;
            $slot_end = clone $start;
            $slot_end->modify('+' . $slot_minutes . ' minutes');

            if ($slot_end > $end) {
                break;
            }

            $slot_key = $slot->format('Y-m-d H:i:s');
            $date_key = $slot->format('Y-m-d');

            if ($slot <= $now || isset($disabled[$slot_key]) || isset($booked[$slot_key])) {
                $start->modify('+' . $slot_minutes . ' minutes');
                continue;
            }

            if (!isset($availability[$date_key])) {
                $availability[$date_key] = array();
            }

            $availability[$date_key][] = array(
                'value' => $slot_key,
                'label' => $slot->format('H:i'),
            );

            $start->modify('+' . $slot_minutes . ' minutes');
        }
    }

    return $availability;
}

add_shortcode('NeoBooking', 'neobooking_render_shortcode');
function neobooking_render_shortcode() {
    $settings     = array_merge(neobooking_default_settings(), (array) get_option('neobooking_settings', array()));
    $services     = neobooking_parse_services($settings['services']);
    $availability = neobooking_generate_availability($settings);
    $status       = sanitize_key($_GET['neo_booking_status'] ?? '');
    $booking_id   = absint($_GET['booking_id'] ?? 0);
    $token        = sanitize_text_field($_GET['token'] ?? '');
    $booking      = ($status === 'success' && $booking_id && $token) ? neobooking_get_booking($booking_id) : null;

    wp_enqueue_script('neobooking-front', NEOBOOKING_URL . 'assets/js/neobooking.js', array('jquery'), NEOBOOKING_VERSION, true);

    ob_start();
    ?>
    <div
        class="neobooking-wrap"
        data-calendar-type="<?php echo esc_attr($settings['calendar_type']); ?>"
        style="
            --neobook-font: <?php echo esc_attr($settings['font_family']); ?>;
            --neobook-text: <?php echo esc_attr($settings['text_color']); ?>;
            --neobook-accent: <?php echo esc_attr($settings['accent_color']); ?>;
            --neobook-bg: <?php echo esc_attr($settings['background_color']); ?>;
            --neobook-align: <?php echo esc_attr($settings['alignment']); ?>;
            --neobook-opacity: <?php echo esc_attr($settings['panel_opacity']); ?>;
        "
    >
        <style>
            .neobooking-wrap{font-family:var(--neobook-font);color:var(--neobook-text);background:rgba(7,16,25,var(--neobook-opacity));border:1px solid var(--neobook-accent);padding:24px;border-radius:14px;max-width:860px;text-align:var(--neobook-align)}
            .neobooking-wrap form{display:grid;gap:16px}
            .neobooking-wrap .neo-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
            .neobooking-wrap .neo-heading{margin-bottom:18px}
            .neobooking-wrap .neo-heading h3{margin:0 0 8px;color:var(--neobook-accent)}
            .neobooking-wrap .neo-heading p{margin:0}
            .neobooking-wrap label{display:grid;gap:8px;font-weight:600}
            .neobooking-wrap input,.neobooking-wrap select,.neobooking-wrap textarea{width:100%;background:#05070a;color:#f5fff9;border:1px solid rgba(51,255,119,.35);border-radius:10px;padding:12px 14px}
            .neobooking-wrap textarea{min-height:120px}
            .neobooking-wrap button{background:var(--neobook-accent);color:#04120a;border:0;border-radius:10px;padding:14px 18px;font-weight:700;cursor:pointer}
            .neobooking-wrap .neo-contact{margin-top:20px;padding:16px;border:1px dashed rgba(51,255,119,.35);border-radius:12px}
            .neobooking-wrap .neo-alert{margin-bottom:16px;padding:12px 14px;border-radius:10px}
            .neobooking-wrap .neo-alert-success{background:rgba(15,123,56,.14);border:1px solid rgba(15,123,56,.45)}
            .neobooking-wrap .neo-alert-error{background:rgba(180,35,35,.14);border:1px solid rgba(180,35,35,.45)}
            .neobooking-wrap .neo-links{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
            .neobooking-wrap .neo-links a{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border:1px solid var(--neobook-accent);border-radius:10px;color:var(--neobook-accent);text-decoration:none}
            .neobooking-wrap .neo-empty{padding:16px;border:1px dashed rgba(255,255,255,.18);border-radius:12px;background:rgba(255,255,255,.03)}
            @media (max-width: 700px){.neobooking-wrap .neo-grid{grid-template-columns:1fr}}
        </style>

        <div class="neo-heading">
            <h3><?php echo esc_html($settings['booking_title']); ?></h3>
            <?php if (!empty($settings['booking_description'])) : ?>
                <p><?php echo esc_html($settings['booking_description']); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($status === 'success' && $booking && hash_equals($booking->token, $token)) : ?>
            <div class="neo-alert neo-alert-success">
                Your booking is confirmed for <?php echo esc_html($booking->slot_key); ?>.
            </div>
            <div class="neo-links">
                <a href="<?php echo esc_url(neobooking_get_google_calendar_link($booking, $settings)); ?>" target="_blank" rel="noopener">Add to Google Calendar</a>
                <a href="<?php echo esc_url(neobooking_get_ics_link($booking)); ?>">Download Calendar File</a>
            </div>
        <?php elseif ($status === 'error') : ?>
            <div class="neo-alert neo-alert-error">That time slot is no longer available. Please choose another one.</div>
        <?php endif; ?>

        <?php if (!empty($availability)) : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="neobooking_submit">
                <input type="hidden" name="redirect_to" value="<?php echo esc_url_raw(get_permalink()); ?>">
                <?php wp_nonce_field('neobooking_submit', 'neobooking_nonce'); ?>

                <div class="neo-grid">
                    <label>
                        <span>Service</span>
                        <select name="service_label" required>
                            <?php foreach ($services as $service) : ?>
                                <option value="<?php echo esc_attr($service); ?>"><?php echo esc_html($service); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <span>Date</span>
                        <select name="booking_date" class="neobooking-date-select" required></select>
                    </label>
                </div>

                <div class="neo-grid">
                    <label>
                        <span>Time</span>
                        <select name="slot_key" class="neobooking-time-select" required></select>
                    </label>

                    <label>
                        <span>Full Name</span>
                        <input type="text" name="customer_name" required>
                    </label>
                </div>

                <div class="neo-grid">
                    <label>
                        <span>Email</span>
                        <input type="email" name="customer_email" required>
                    </label>

                    <label>
                        <span>Phone</span>
                        <input type="tel" name="customer_phone" required>
                    </label>
                </div>

                <label>
                    <span>Notes</span>
                    <textarea name="note" placeholder="Add any booking notes here"></textarea>
                </label>

                <button type="submit"><?php echo esc_html($settings['submit_label']); ?></button>
            </form>
        <?php else : ?>
            <div class="neo-empty"><?php echo esc_html($settings['empty_slots_message']); ?></div>
        <?php endif; ?>

        <div class="neo-contact">
            <?php if (!empty($settings['contact_title'])) : ?><h4><?php echo esc_html($settings['contact_title']); ?></h4><?php endif; ?>
            <?php if (!empty($settings['address'])) : ?><div><strong>Address:</strong> <?php echo esc_html($settings['address']); ?></div><?php endif; ?>
            <?php if (!empty($settings['phone'])) : ?><div><strong>Phone:</strong> <?php echo esc_html($settings['phone']); ?></div><?php endif; ?>
            <div class="neo-links">
                <?php if (!empty($settings['apple_maps'])) : ?><a href="<?php echo esc_url($settings['apple_maps']); ?>" target="_blank" rel="noopener">Apple Maps</a><?php endif; ?>
                <?php if (!empty($settings['google_maps'])) : ?><a href="<?php echo esc_url($settings['google_maps']); ?>" target="_blank" rel="noopener">Google Maps</a><?php endif; ?>
                <?php if (!empty($settings['waze'])) : ?><a href="<?php echo esc_url($settings['waze']); ?>" target="_blank" rel="noopener">Waze</a><?php endif; ?>
            </div>
        </div>

        <script type="application/json" class="neobooking-availability"><?php echo wp_json_encode($availability); ?></script>
    </div>
    <?php

    return ob_get_clean();
}

add_action('admin_post_nopriv_neobooking_submit', 'neobooking_handle_submission');
add_action('admin_post_neobooking_submit', 'neobooking_handle_submission');
function neobooking_handle_submission() {
    if (!wp_verify_nonce($_POST['neobooking_nonce'] ?? '', 'neobooking_submit')) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    $settings     = array_merge(neobooking_default_settings(), (array) get_option('neobooking_settings', array()));
    $availability = neobooking_generate_availability($settings);
    $slot_key     = sanitize_text_field($_POST['slot_key'] ?? '');
    $date_key     = sanitize_text_field($_POST['booking_date'] ?? '');
    $redirect_to  = esc_url_raw($_POST['redirect_to'] ?? home_url('/'));
    $service      = sanitize_text_field($_POST['service_label'] ?? '');

    $valid_slot = false;
    foreach ((array) ($availability[$date_key] ?? array()) as $slot) {
        if ($slot['value'] === $slot_key) {
            $valid_slot = true;
            break;
        }
    }

    if (!$valid_slot) {
        wp_safe_redirect(add_query_arg('neo_booking_status', 'error', $redirect_to));
        exit;
    }

    global $wpdb;
    $token = wp_generate_uuid4();
    $inserted = $wpdb->insert(
        NEOBOOKING_TABLE,
        array(
            'service_label'  => $service,
            'slot_key'       => $slot_key,
            'customer_name'  => sanitize_text_field($_POST['customer_name'] ?? ''),
            'customer_email' => sanitize_email($_POST['customer_email'] ?? ''),
            'customer_phone' => sanitize_text_field($_POST['customer_phone'] ?? ''),
            'note'           => sanitize_textarea_field($_POST['note'] ?? ''),
            'ip_address'     => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'token'          => $token,
            'created_at'     => current_time('mysql'),
        )
    );

    if (!$inserted) {
        wp_safe_redirect(add_query_arg('neo_booking_status', 'error', $redirect_to));
        exit;
    }

    $booking_id = absint($wpdb->insert_id);
    if (!empty($settings['admin_email'])) {
        $message = implode(
            "\n",
            array(
                'Service: ' . $service,
                'Slot: ' . $slot_key,
                'Name: ' . sanitize_text_field($_POST['customer_name'] ?? ''),
                'Email: ' . sanitize_email($_POST['customer_email'] ?? ''),
                'Phone: ' . sanitize_text_field($_POST['customer_phone'] ?? ''),
                'Note: ' . sanitize_textarea_field($_POST['note'] ?? ''),
                'IP Address: ' . sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            )
        );

        wp_mail($settings['admin_email'], 'New booking received', $message);
    }

    wp_safe_redirect(
        add_query_arg(
            array(
                'neo_booking_status' => 'success',
                'booking_id'         => $booking_id,
                'token'              => $token,
            ),
            $redirect_to
        )
    );
    exit;
}

function neobooking_get_booking_by_token($id, $token) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . NEOBOOKING_TABLE . " WHERE id = %d AND token = %s", $id, $token));
}

function neobooking_get_ics_link($booking) {
    return add_query_arg(
        array(
            'neo_booking_ics' => 1,
            'booking_id'      => $booking->id,
            'token'           => $booking->token,
        ),
        home_url('/')
    );
}

function neobooking_get_google_calendar_link($booking, $settings) {
    $start = new DateTime($booking->slot_key, wp_timezone());
    $end   = clone $start;
    $end->modify('+' . max(15, absint($settings['slot_duration'])) . ' minutes');

    return 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' .
        rawurlencode($booking->service_label) .
        '&dates=' . rawurlencode($start->format('Ymd\THis')) . '/' . rawurlencode($end->format('Ymd\THis')) .
        '&details=' . rawurlencode('Booked through website') .
        '&location=' . rawurlencode($settings['address']);
}

add_action('init', 'neobooking_download_ics');
function neobooking_download_ics() {
    if (empty($_GET['neo_booking_ics']) || empty($_GET['booking_id']) || empty($_GET['token'])) {
        return;
    }

    $booking = neobooking_get_booking_by_token(absint($_GET['booking_id']), sanitize_text_field($_GET['token']));
    $settings = array_merge(neobooking_default_settings(), (array) get_option('neobooking_settings', array()));

    if (!$booking) {
        status_header(404);
        exit;
    }

    $start = new DateTime($booking->slot_key, wp_timezone());
    $end   = clone $start;
    $end->modify('+' . max(15, absint($settings['slot_duration'])) . ' minutes');

    $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//NeoBooking//EN\r\nBEGIN:VEVENT\r\nUID:" . $booking->token . "\r\nDTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\nDTSTART:" . $start->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z') . "\r\nDTEND:" . $end->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z') . "\r\nSUMMARY:" . neobooking_escape_ics_text($booking->service_label) . "\r\nDESCRIPTION:" . neobooking_escape_ics_text('Booked through website') . "\r\nLOCATION:" . neobooking_escape_ics_text($settings['address']) . "\r\nEND:VEVENT\r\nEND:VCALENDAR";

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename=booking-' . absint($booking->id) . '.ics');
    echo $ics;
    exit;
}

function neobooking_escape_ics_text($text) {
    $text = str_replace(array('\\', ',', ';'), array('\\\\', '\,', '\;'), (string) $text);
    return preg_replace("/\r\n|\r|\n/", '\n', $text);
}
