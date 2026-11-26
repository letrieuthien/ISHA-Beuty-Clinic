<?php
/**
 * Nút "Lên đầu trang" của theme (thay cho plugin WPFront Scroll Top đã gỡ).
 *
 * In một nút cố định ở góc dưới bên phải mọi trang phía khách. Ở trang chủ điện ảnh, nút đứng ngay phía trên dock liên hệ khi dock đang hiện
 * (scroll-top.js đo chiều cao dock) và hạ xuống mép khi dock ẩn. Trang chủ và trang chi tiết liệu trình dùng kiểu thu gọn (body có class
 * isha-top-compact, xem isha_scroll_top_compact()). Nút ẩn
 * cho tới khi người xem cuộn xuống quá một đoạn (assets/js/scroll-top.js bật class is-visible); không có JS thì nút không bao giờ hiện.
 * Muốn bật hoặc tắt ở trang nào đó, dùng bộ lọc `isha/scroll_top/enabled` ( bool $enabled ): ví dụ
 * add_filter( 'isha/scroll_top/enabled', '__return_false' ) tắt hẳn. Hợp đồng ở docs/home-architecture.md, mục 13.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trang hiện tại có nút Lên đầu trang không: phía khách, không phải khung nhúng và không phải trang chi tiết Chính sách (isha_policy: hai
 * màn hình ghim có liên kết "Cuộn xuống" và nút Đặt lịch ở góc dưới, nút này sẽ đè lên); bộ lọc có quyền quyết định cuối.
 *
 * @return bool
 */
function isha_scroll_top_enabled() {
	$enabled = ! is_admin() && ! is_embed() && ! is_singular( 'isha_policy' );

	return (bool) apply_filters( 'isha/scroll_top/enabled', $enabled );
}

/**
 * Trang hiện tại dùng kiểu nút THU GỌN (ô vuông 48px chỉ có mũi tên, hover hoặc focus phím thì nhãn giãn ra; xem scroll-top.css) không:
 * trang chủ điện ảnh, trang chi tiết liệu trình (isha_service), trang chi tiết Tin tức (isha_article) và trang chi tiết Sự kiện (isha_event); ba trang sau đều có dock liên hệ nhanh như trang chủ.
 * Trang khác giữ nút có nhãn đầy đủ. Bộ lọc `isha/scroll_top/compact`
 * ( bool $compact ) cho trang khác bật theo; chỉ có tác dụng khi trang có nút.
 *
 * @return bool
 */
function isha_scroll_top_compact() {
	if ( ! isha_scroll_top_enabled() ) {
		return false;
	}

	$compact = function_exists( 'isha_home_is_active' ) && isha_home_is_active();
	$compact = $compact || is_singular( array( 'isha_service', 'isha_article', 'isha_event' ) );

	return (bool) apply_filters( 'isha/scroll_top/compact', $compact );
}

/**
 * Thêm class `isha-top-compact` vào body khi nút ở kiểu thu gọn: scroll-top.css (nút) và main.css (chỗ chừa ở chân trang) cùng đọc class này.
 *
 * @param array $classes Body classes.
 * @return array
 */
function isha_scroll_top_body_class( $classes ) {
	if ( isha_scroll_top_compact() ) {
		$classes[] = 'isha-top-compact';
	}
	return $classes;
}
add_filter( 'body_class', 'isha_scroll_top_body_class' );

/**
 * Nạp scroll-top.css và scroll-top.js (defer, chân trang) khi trang có nút.
 */
function isha_scroll_top_assets() {
	if ( ! isha_scroll_top_enabled() ) {
		return;
	}

	$css = ISHA_DIR . '/assets/css/scroll-top.css';
	$js  = ISHA_DIR . '/assets/js/scroll-top.js';

	if ( is_file( $css ) ) {
		wp_enqueue_style( 'isha-scroll-top', ISHA_URI . '/assets/css/scroll-top.css', array( 'isha-main' ), (string) filemtime( $css ) );
	}
	if ( is_file( $js ) ) {
		wp_enqueue_script(
			'isha-scroll-top',
			ISHA_URI . '/assets/js/scroll-top.js',
			array(),
			(string) filemtime( $js ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'isha_scroll_top_assets', 30 );

/**
 * In nút ở chân trang. Nút có chữ (không chỉ biểu tượng); mũi tên chỉ là hình trang trí (aria-hidden).
 */
function isha_scroll_top_markup() {
	if ( ! isha_scroll_top_enabled() ) {
		return;
	}
	?>
	<button type="button" class="isha-top" data-isha-top>
		<span class="isha-top__icon" aria-hidden="true"></span>
		<span class="isha-top__text"><span><?php esc_html_e( 'Lên đầu trang', 'isha' ); ?></span></span>
	</button>
	<?php
}
add_action( 'wp_footer', 'isha_scroll_top_markup', 20 );
