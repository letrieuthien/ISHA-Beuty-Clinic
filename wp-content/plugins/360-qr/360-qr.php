<?php
/**
 * Plugin Name: 360 QR
 * Description: Create and manage dynamic QR codes with editable URLs, scan statistics, and SVG/PNG downloads.
 * Version: 1.0.14
 * Author: NET360
 * Author URI: https://net360.pe/
 * Plugin URI: https://net360.pe/360-qr/
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 360-qr
 */
if (!defined('ABSPATH')) exit;

define('NET360QR_VERSION', '1.0.14');
define('NET360QR_DIR', plugin_dir_path(__FILE__));
define('NET360QR_URL', plugin_dir_url(__FILE__));

define('NET360QR_DB_VERSION', '1.0.3');

require_once NET360QR_DIR . 'includes/class-360-qr-db.php';
require_once NET360QR_DIR . 'includes/class-360-qr-local.php';
require_once NET360QR_DIR . 'includes/class-360-qr-redirect.php';
require_once NET360QR_DIR . 'includes/class-360-qr-admin.php';
require_once NET360QR_DIR . 'includes/class-360-qr-frontend.php';

function net360qr_install() {
    NET360QR_DB::activate();
    update_option('net360qr_db_version', NET360QR_DB_VERSION, false);
    NET360QR_REDIRECT::add_rules();
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'net360qr_install');
register_deactivation_hook(__FILE__, function () { flush_rewrite_rules(); });

add_action('plugins_loaded', function () {
    if (get_option('net360qr_db_version') !== NET360QR_DB_VERSION) {
        NET360QR_DB::activate();
        update_option('net360qr_db_version', NET360QR_DB_VERSION, false);
        NET360QR_REDIRECT::add_rules();
        flush_rewrite_rules();
    }
    new NET360QR_REDIRECT();
    new NET360QR_ADMIN();
    new NET360QR_FRONTEND();
});
