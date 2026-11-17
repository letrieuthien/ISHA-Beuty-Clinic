<?php
/**
 * Plugin Name: ISHA Core
 * Description: Custom post type (Dịch vụ, Chính sách khách hàng, Lịch hẹn, Sự kiện, Lời kể khách hàng, Tin tức, Không gian ISHA, hộp thư Liên hệ), nội dung trang chủ, form đặt lịch, trang cài đặt và các shortcode cho website phòng khám ISHA.
 * Version:     1.1.0
 * Author:      ISHA Beauty Clinic
 * Text Domain: isha-core
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ISHA_CORE_VERSION', '1.1.0' );
define( 'ISHA_CORE_FILE', __FILE__ );
define( 'ISHA_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ISHA_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Phiên bản bộ rewrite rules (so với option isha_core_rewrite_ver). Tăng số này mỗi khi thêm hoặc đổi post type, taxonomy
 * hay slug, để website tự flush rewrite rules đúng một lần mà không cần bấm lưu Permalinks.
 */
define( 'ISHA_CORE_REWRITE_VERSION', '3' ); // 3: thêm isha_space (/khong-gian-isha/), Tin tức /bai-viet/ → /tin-tuc/ (18/11/2026).

require_once ISHA_CORE_DIR . 'includes/class-post-types.php';
require_once ISHA_CORE_DIR . 'includes/class-service-fields.php';
require_once ISHA_CORE_DIR . 'includes/class-policy-fields.php';
require_once ISHA_CORE_DIR . 'includes/class-settings.php';
require_once ISHA_CORE_DIR . 'includes/class-forms.php';
require_once ISHA_CORE_DIR . 'includes/class-booking.php';
require_once ISHA_CORE_DIR . 'includes/class-contact.php';
require_once ISHA_CORE_DIR . 'includes/class-shortcodes.php';

// Trang chủ điện ảnh: ô chọn media dùng chung (không có init) nạp trước các class dùng nó.
require_once ISHA_CORE_DIR . 'includes/class-admin-media.php';
require_once ISHA_CORE_DIR . 'includes/class-home-fields.php';
require_once ISHA_CORE_DIR . 'includes/class-event-fields.php';
require_once ISHA_CORE_DIR . 'includes/class-testimonial-fields.php';
require_once ISHA_CORE_DIR . 'includes/class-article-fields.php';
require_once ISHA_CORE_DIR . 'includes/class-term-fields.php';
require_once ISHA_CORE_DIR . 'includes/class-page-hero-fields.php';
require_once ISHA_CORE_DIR . 'includes/class-about-fields.php';
require_once ISHA_CORE_DIR . 'includes/class-space-fields.php';
require_once ISHA_CORE_DIR . 'includes/class-legacy-urls.php';
require_once ISHA_CORE_DIR . 'includes/class-seed.php';

/**
 * Khởi tạo các module.
 */
function isha_core_init() {
	load_plugin_textdomain( 'isha-core', false, dirname( plugin_basename( ISHA_CORE_FILE ) ) . '/languages' );

	ISHA_Core_Post_Types::init();

	// Trang chủ điện ảnh (ISHA_Core_Admin_Media không có init: chỉ cần require_once ở trên).
	ISHA_Core_Home_Fields::init();
	ISHA_Core_Event_Fields::init();
	ISHA_Core_Testimonial_Fields::init();
	ISHA_Core_Article_Fields::init();
	ISHA_Core_Term_Fields::init();
	ISHA_Core_Page_Hero_Fields::init();
	ISHA_Core_About_Fields::init(); // Trang Giới thiệu (mẫu templates/template-about.php).
	ISHA_Core_Space_Fields::init(); // Bài Không gian ISHA (CPT isha_space).
	ISHA_Core_Legacy_Urls::init();  // 301 cho đường dẫn Tin tức cũ /bai-viet/….
	ISHA_Core_Seed::init();

	ISHA_Core_Service_Fields::init();
	ISHA_Core_Policy_Fields::init();
	ISHA_Core_Settings::init();
	ISHA_Core_Forms::init();   // Phần dùng chung của form công khai: nonce lấy mới qua AJAX, giới hạn tần suất, bong bóng thư mới.
	ISHA_Core_Booking::init();
	ISHA_Core_Contact::init(); // Hộp thư Liên hệ (CPT isha_contact) và xử lý form liên hệ.
	ISHA_Core_Shortcodes::init();
}
add_action( 'plugins_loaded', 'isha_core_init' );

/**
 * Flush rewrite rules đúng một lần khi bộ rewrite đổi phiên bản (ISHA_CORE_REWRITE_VERSION), thay vì flush ở mọi request.
 * Chạy ở init ưu tiên 99, sau khi mọi post type và taxonomy (đăng ký ở init ưu tiên 10) đã có. Flush mềm: chỉ làm mới option
 * rewrite_rules, không ghi lại .htaccess.
 */
function isha_core_maybe_flush_rewrite() {
	if ( (string) get_option( 'isha_core_rewrite_ver', '' ) === ISHA_CORE_REWRITE_VERSION ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'isha_core_rewrite_ver', ISHA_CORE_REWRITE_VERSION );
}
add_action( 'init', 'isha_core_maybe_flush_rewrite', 99 );

/**
 * Kích hoạt: đăng ký CPT rồi flush rewrite rules và ghi nhận phiên bản rewrite đã flush.
 */
function isha_core_activate() {
	ISHA_Core_Post_Types::register();
	flush_rewrite_rules();
	update_option( 'isha_core_rewrite_ver', ISHA_CORE_REWRITE_VERSION );
}
register_activation_hook( __FILE__, 'isha_core_activate' );

/**
 * Tắt: flush rewrite rules.
 */
function isha_core_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'isha_core_deactivate' );

/**
 * Đọc thiết lập của plugin.
 *
 * @param string $key     Khoá.
 * @param string $default Mặc định.
 * @return string
 */
function isha_core_get( $key, $default = '' ) {
	$settings = get_option( 'isha_core_settings', array() );
	return ( is_array( $settings ) && isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) ? $settings[ $key ] : $default;
}
