<?php
if (!defined('ABSPATH')) exit;

class NET360QR_DB {
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'net360_qr';
    }

    public static function activate() {
        global $wpdb;
        $table = self::table();
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            code VARCHAR(32) NOT NULL,
            name VARCHAR(190) NOT NULL,
            destination_url TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            scans BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            last_scan_at DATETIME NULL,
            logo_attachment_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            qr_shape VARCHAR(24) NOT NULL DEFAULT 'classic',
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY user_id (user_id),
            KEY status (status)
        ) {$charset};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function all($user_id = 0) {
        global $wpdb;
        if ($user_id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; current QR data must be read in real time.
            return $wpdb->get_results($wpdb->prepare(
                'SELECT * FROM %i WHERE user_id=%d ORDER BY id DESC',
                self::table(),
                $user_id
            ));
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; current QR data must be read in real time.
        return $wpdb->get_results($wpdb->prepare('SELECT * FROM %i ORDER BY id DESC', self::table()));
    }

    public static function count($user_id = 0) {
        global $wpdb;
        if ($user_id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; current QR totals must be current.
            return (int) $wpdb->get_var($wpdb->prepare(
                'SELECT COUNT(*) FROM %i WHERE user_id=%d',
                self::table(),
                $user_id
            ));
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; current QR totals must be current.
        return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i', self::table()));
    }

    public static function paginate($per_page = 10, $page = 1, $user_id = 0) {
        global $wpdb;
        $per_page = max(1, absint($per_page));
        $page = max(1, absint($page));
        $offset = ($page - 1) * $per_page;
        if ($user_id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; paginated QR data must be current.
            return $wpdb->get_results($wpdb->prepare(
                'SELECT * FROM %i WHERE user_id=%d ORDER BY id DESC LIMIT %d OFFSET %d',
                self::table(),
                $user_id,
                $per_page,
                $offset
            ));
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; paginated QR data must be current.
        return $wpdb->get_results($wpdb->prepare(
            'SELECT * FROM %i ORDER BY id DESC LIMIT %d OFFSET %d',
            self::table(),
            $per_page,
            $offset
        ));
    }

    public static function get($id) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; QR records must be read in real time.
        return $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM %i WHERE id=%d LIMIT 1',
            self::table(),
            $id
        ));
    }

    public static function get_for_user($id, $user_id) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; QR records must be read in real time.
        return $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM %i WHERE id=%d AND user_id=%d LIMIT 1',
            self::table(),
            $id,
            $user_id
        ));
    }

    public static function get_by_code($code) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; QR records must be read in real time.
        return $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM %i WHERE code=%s LIMIT 1',
            self::table(),
            $code
        ));
    }

    public static function code_exists($code, $exclude_id = 0) {
        global $wpdb;
        if ($exclude_id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; uniqueness checks must use current data.
            return (bool) $wpdb->get_var($wpdb->prepare(
                'SELECT id FROM %i WHERE code=%s AND id!=%d LIMIT 1',
                self::table(),
                $code,
                $exclude_id
            ));
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; uniqueness checks must use current data.
        return (bool) $wpdb->get_var($wpdb->prepare(
            'SELECT id FROM %i WHERE code=%s LIMIT 1',
            self::table(),
            $code
        ));
    }

    public static function insert($data) {
        global $wpdb;
        $now = current_time('mysql');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Writes to the plugin's custom QR table; object caching is intentionally not used because QR state must remain immediately consistent.
        $wpdb->insert(self::table(), $data);
        return $wpdb->insert_id;
    }

    public static function update($id, $data) {
        global $wpdb;
        $data['updated_at'] = current_time('mysql');
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Writes to the plugin's custom QR table; object caching is intentionally not used because QR state must remain immediately consistent.
        return $wpdb->update(self::table(), $data, array('id'=>$id));
    }

    public static function delete($id) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Deletes from the plugin's custom QR table; object caching is intentionally not used because QR state must remain immediately consistent.
        return $wpdb->delete(self::table(), array('id'=>$id), array('%d'));
    }

    public static function register_scan($id) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic scan counter update on the plugin's custom QR table; caching would risk stale scan counts.
        return $wpdb->query($wpdb->prepare(
            'UPDATE %i SET scans=scans+1,last_scan_at=%s WHERE id=%d',
            self::table(),
            current_time('mysql'),
            $id
        ));
    }
}
