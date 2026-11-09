<?php
/**
 * Migration Engine for NeoMarket Database Upgrades
 * Inspired by WooCommerce's dbDelta approach
 */

if (!defined('ABSPATH')) {
    exit;
}

class NeoMarket_Migrator {

    /**
     * Current DB version stored in the database option
     */
    const DB_VERSION_OPTION = 'neomarket_db_version';

    /**
     * Target DB version from this codebase
     */
    const TARGET_DB_VERSION = '2.0.0';

    /**
     * Path to migration scripts
     */
    public static function get_migrations_path() {
        return NEOMARKET_PATH . 'includes/migrations/';
    }

    /**
     * Initialize migrator
     */
    public static function init() {
        add_action('plugins_loaded', array(__CLASS__, 'maybe_run_upgrades'));
    }

    /**
     * Check if DB is outdated and run upgrades
     */
    public static function maybe_run_upgrades() {
        $current_version = get_option(self::DB_VERSION_OPTION, '0.0.0');

        if (version_compare($current_version, self::TARGET_DB_VERSION, '<')) {
            self::run_migrations($current_version);
            update_option(self::DB_VERSION_OPTION, self::TARGET_DB_VERSION);
        }
    }

    /**
     * Run migration files in order
     */
    public static function run_migrations($from_version) {
        $migrations = self::get_available_migrations();

        foreach ($migrations as $migration_file) {
            $migration_version = self::extract_version_from_filename($migration_file);

            if (version_compare($from_version, $migration_version, '<')) {
                $full_path = self::get_migrations_path() . $migration_file;
                if (file_exists($full_path)) {
                    include_once $full_path;
                }
            }
        }
    }

    /**
     * Scan migrations directory and sort
     */
    private static function get_available_migrations() {
        $files = array();
        $path = self::get_migrations_path();

        if (is_dir($path) && $handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if (preg_match('/^(\d{4}_\d{2}_\d{2})-upgrade-(.+)\.php$/', $file)) {
                    $files[] = $file;
                }
            }
            closedir($handle);
            sort($files);
        }
        return $files;
    }

    /**
     * Extract semantic version from migration file name
     * Falls back to timestamp ordering if not embedded
     */
    private static function extract_version_from_filename($filename) {
        if (preg_match('/-upgrade-to-(\d+-\d+-\d+)\.php$/', $filename, $matches)) {
            return str_replace('-', '.', $matches[1]);
        }
        return '99.99.99';
    }

    /**
     * Wrapper for dbDelta
     */
    public static function delta($sql) {
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        return dbDelta($sql);
    }

    /**
     * Check if table exists (helper for rollback/migration check)
     */
    public static function table_exists($table_name) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
    }
}

NeoMarket_Migrator::init();
