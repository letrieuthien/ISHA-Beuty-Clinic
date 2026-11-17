<?php
/**
 * Helper functions.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Đọc một thiết lập của plugin ISHA Core (thông tin liên hệ, giờ làm việc, mạng xã hội).
 *
 * @param string $key     Khoá: phone, email, address, hours, facebook, instagram, tiktok, youtube, zalo ...
 * @param string $default Giá trị mặc định.
 * @return string
 */
function isha_opt( $key, $default = '' ) {
	$settings = get_option( 'isha_core_settings', array() );
	if ( is_array( $settings ) && isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) {
		return $settings[ $key ];
	}
	return $default;
}

/**
 * Đọc theme_mod với giá trị mặc định tập trung ở một chỗ.
 *
 * @param string $key Tên setting (không có tiền tố isha_).
 * @return mixed
 */
function isha_mod( $key ) {
	$defaults = isha_mod_defaults();
	return get_theme_mod( 'isha_' . $key, isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
}

/**
 * Giá trị mặc định cho Customizer.
 *
 * @return array
 */
function isha_mod_defaults() {
	return array(
		'hero_eyebrow'   => __( 'Phòng khám da liễu thẩm mỹ', 'isha' ),
		'hero_title'     => __( 'Đánh thức vẻ đẹp tự nhiên của bạn', 'isha' ),
		'hero_subtitle'  => __( 'ISHA đồng hành cùng bạn tìm lại vẻ đẹp vốn có và cảm giác tái sinh, bằng khoa học da liễu và sự thấu hiểu tận tâm.', 'isha' ),
		'hero_btn_text'  => __( 'Đặt lịch tư vấn', 'isha' ),
		'hero_btn2_text' => __( 'Xem dịch vụ', 'isha' ),
		'hero_image'     => '',
		'intro_title'    => __( 'Khoa học. Tinh tế. Cân bằng.', 'isha' ),
		'intro_text'     => __( 'Mỗi làn da là một câu chuyện riêng. Chúng tôi lắng nghe, thăm khám kỹ lưỡng và xây dựng liệu trình phù hợp, trung thực về kết quả, tôn trọng vẻ đẹp nội tại của bạn.', 'isha' ),
		'cta_title'      => __( 'Sẵn sàng bắt đầu hành trình làn da khỏe đẹp?', 'isha' ),
		'cta_text'       => __( 'Đặt lịch để được thăm khám và tư vấn liệu trình phù hợp riêng cho bạn.', 'isha' ),
		'footer_about'   => __( 'ISHA giúp mỗi người tìm lại vẻ đẹp vốn có và cảm giác tái sinh.', 'isha' ),
		'footer_credit'  => '',
	);
}

/**
 * Trang "nội dung" dùng chung style page.css (class `page` trên <main>):
 * Page, trang tin tức, bài viết, chi tiết dịch vụ, chi tiết chính sách, chi tiết Tin tức và Sự kiện (từ 18/11/2026).
 * Trang chủ điện ảnh (isha_home_is_active()) KHÔNG phải trang nội dung: không nạp page.css, <main> không có class `page`.
 *
 * @return bool
 */
function isha_is_inner_page() {
	if ( isha_home_is_active() ) {
		return false;
	}
	return is_page() || is_home() || is_singular( array( 'post', 'isha_service', 'isha_policy', 'isha_article', 'isha_event', 'isha_space' ) );
}

/**
 * Chế độ landing (giai đoạn 1): trang chủ chỉ hiển thị Chính sách khách hàng, ẩn menu và nút đặt lịch.
 * Mặc định BẬT. Tắt trong Tùy biến > ISHA – Giao diện > Chế độ landing khi muốn mở lại toàn bộ website.
 * Đang xem trước trang chủ mới (isha_home_is_preview()) thì luôn trả false để header, footer hiện đủ menu.
 *
 * @return bool
 */
function isha_landing_only() {
	if ( isha_home_is_preview() ) {
		return false;
	}
	return (bool) get_theme_mod( 'isha_landing_only', true );
}

/**
 * Đang xem trước trang chủ điện ảnh? True khi: đã qua hook `wp`, đang ở trang chủ, người xem đã đăng nhập và có quyền
 * `edit_pages`, và URL có `?isha_preview=home`. Gọi sớm hơn hook `wp` thì trả false (không báo _doing_it_wrong).
 * Chỉ đọc cờ trên URL, không đổi dữ liệu nên không cần nonce.
 *
 * @return bool
 */
function isha_home_is_preview() {
	if ( ! did_action( 'wp' ) ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc cờ xem trước.
	$flag = ( isset( $_GET['isha_preview'] ) && ! is_array( $_GET['isha_preview'] ) ) ? sanitize_key( wp_unslash( $_GET['isha_preview'] ) ) : '';
	if ( 'home' !== $flag ) {
		return false;
	}
	return is_front_page() && is_user_logged_in() && current_user_can( 'edit_pages' );
}

/**
 * Trang chủ điện ảnh đang được dựng? True khi đang ở trang chủ và (chế độ landing tắt hoặc đang xem trước).
 * False nếu chưa qua hook `wp` (chưa biết đang ở trang nào).
 *
 * @return bool
 */
function isha_home_is_active() {
	if ( ! did_action( 'wp' ) ) {
		return false;
	}
	return is_front_page() && ( ! isha_landing_only() || isha_home_is_preview() );
}

/**
 * Plugin ISHA Core đang hoạt động?
 *
 * @return bool
 */
function isha_core_active() {
	return defined( 'ISHA_CORE_VERSION' );
}

/**
 * URL trang đặt lịch (trang dùng template "Đặt lịch hẹn"), fallback về trang chủ.
 *
 * @return string
 */
function isha_booking_url() {
	return isha_page_url_by_template( 'templates/template-booking.php' );
}

/**
 * URL trang danh sách chính sách (trang dùng mẫu "Chính sách dịch vụ"), fallback về trang chủ.
 *
 * @return string
 */
function isha_policies_url() {
	return isha_page_url_by_template( 'templates/template-policies.php' );
}

/**
 * Tìm URL của trang đầu tiên (đã xuất bản) đang dùng một page template.
 *
 * @param string $template Đường dẫn template, vd: templates/template-booking.php.
 * @return string
 */
function isha_page_url_by_template( $template ) {
	static $cache = array();
	if ( isset( $cache[ $template ] ) ) {
		return $cache[ $template ];
	}
	$pages = get_posts(
		array(
			'post_type'              => 'page',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'meta_key'               => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'             => $template, // phpcs:ignore WordPress.DB.SlowDBQuery
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
		)
	);
	$cache[ $template ] = $pages ? get_permalink( $pages[0] ) : home_url( '/' );
	return $cache[ $template ];
}

/**
 * Render shortcode an toàn: nếu plugin chưa bật, chỉ quản trị viên thấy lời nhắc.
 *
 * @param string $tag   Tên shortcode.
 * @param array  $atts  Thuộc tính.
 * @return string HTML (đã escape bởi shortcode).
 */
function isha_do_shortcode( $tag, $atts = array() ) {
	if ( ! shortcode_exists( $tag ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			/* translators: %s: shortcode tag */
			return '<p class="isha-notice">' . esc_html( sprintf( __( 'Cần kích hoạt plugin ISHA Core để hiển thị [%s].', 'isha' ), $tag ) ) . '</p>';
		}
		return '';
	}
	$parts = array();
	foreach ( $atts as $name => $value ) {
		$parts[] = sprintf( '%s="%s"', $name, esc_attr( $value ) );
	}
	return do_shortcode( '[' . $tag . ' ' . implode( ' ', $parts ) . ']' );
}
