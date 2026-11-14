<?php
/**
 * Shortcode của trang chủ điện ảnh (mỗi section một cái, sinh từ registry): [isha_home_hero], [isha_home_story], ... [isha_home_partners].
 *
 * Mỗi shortcode lấy dữ liệu qua isha_home_data(), trả chuỗi rỗng khi section bị ẩn (tắt trong CMS hoặc không có dữ liệu),
 * không thì trả HTML của template-parts/home/<id>.php. Dùng trong isha_home_render() hoặc nhúng riêng ở trang khác
 * (ví dụ [isha_home_cta] ở trang liệu trình): khi đó kết quả được bọc trong `div.isha-home.isha-home--embed` và tài nguyên
 * trang chủ được nạp. Hợp đồng ở docs/home-architecture.md, mục 5.4.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Số mục mặc định của các shortcode có thuộc tính `count`.
 *
 * @return int[] array( id => số mục ).
 */
function isha_home_shortcode_counts() {
	return array(
		'events'       => 5,
		'articles'     => 4,
		'testimonials' => 6,
	);
}

/**
 * Đối số truyền cho isha_home_data() từ thuộc tính của shortcode.
 *
 * Thuộc tính `count` (chỉ events, articles, testimonials): số trong khoảng 1 đến 12 (số ngoài khoảng được kẹp lại); để trống
 * hoặc không phải số thì dùng mặc định của section (5, 4, 6). Section khác không có thuộc tính.
 *
 * @param string $id   Mã section.
 * @param mixed  $atts Thuộc tính của shortcode (mảng, hoặc '' khi không có).
 * @return array
 */
function isha_home_shortcode_args( $id, $atts = array() ) {
	$counts = isha_home_shortcode_counts();
	if ( ! isset( $counts[ $id ] ) ) {
		return array();
	}

	$atts  = shortcode_atts( array( 'count' => $counts[ $id ] ), is_array( $atts ) ? $atts : array(), 'isha_home_' . $id );
	$count = is_numeric( $atts['count'] ) ? (int) $atts['count'] : $counts[ $id ];
	return array( 'count' => max( 1, min( 12, $count ) ) );
}

/**
 * Xử lý một shortcode section, cách ly lỗi: ở site chạy thật (WP_DEBUG tắt) một section ném lỗi (dữ liệu lạ, bộ lọc hay plugin khác trả sai
 * kiểu) chỉ ẩn riêng nó, không làm trang chủ hiện "critical error". Khi WP_DEBUG bật, lỗi vẫn nổi lên để người phát triển thấy. Bộ lọc
 * `isha/home/isolate_errors` ( bool $isolate, $id, Throwable ) có quyền quyết định cuối. Lỗi được báo qua hành động
 * `isha/home/section_error` ( $id, Throwable ) và ghi nhật ký.
 *
 * @param string $id   Mã section.
 * @param mixed  $atts Thuộc tính của shortcode.
 * @return string HTML của section; '' nếu section ẩn hoặc lỗi.
 */
function isha_home_shortcode( $id, $atts = array() ) {
	try {
		return isha_home_shortcode_render( $id, $atts );
	} catch ( Throwable $error ) {
		if ( ! apply_filters( 'isha/home/isolate_errors', ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ), $id, $error ) ) {
			throw $error;
		}
		do_action( 'isha/home/section_error', $id, $error );
		error_log( 'ISHA home, section ' . $id . ': ' . $error->getMessage() . ' (' . basename( $error->getFile() ) . ':' . $error->getLine() . ')' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- nhật ký lỗi có chủ đích.
		return '';
	}
}

/**
 * Dựng HTML một section (xem isha_home_shortcode(), nơi bắt lỗi).
 *
 * @param string $id   Mã section.
 * @param mixed  $atts Thuộc tính của shortcode.
 * @return string HTML của section; '' nếu section ẩn.
 */
function isha_home_shortcode_render( $id, $atts = array() ) {
	$data = isha_home_data( $id, isha_home_shortcode_args( $id, $atts ) );
	if ( ! is_array( $data ) || ! $data ) {
		return '';
	}

	ob_start();
	try {
		get_template_part( 'template-parts/home/' . $id, null, $data );
	} finally {
		$html = (string) ob_get_clean();
	}
	if ( '' === trim( $html ) ) {
		return '';
	}

	// Nhúng ngoài phim: bọc để CSS dưới `.isha-home` áp dụng, và nạp CSS/JS trang chủ.
	if ( ! isha_home_in_film() ) {
		isha_home_enqueue_assets();
		$html = '<div class="isha-home isha-home--embed">' . $html . '</div>';
	}
	return $html;
}

/**
 * Đăng ký đủ shortcode của mọi section (không phụ thuộc bộ lọc của registry).
 */
function isha_home_register_shortcodes() {
	foreach ( array_keys( isha_home_sections_base() ) as $id ) {
		add_shortcode(
			'isha_home_' . $id,
			static function ( $atts = array() ) use ( $id ) {
				return isha_home_shortcode( $id, $atts );
			}
		);
	}
}
add_action( 'init', 'isha_home_register_shortcodes' );
