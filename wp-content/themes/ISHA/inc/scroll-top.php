<?php
/**
 * Nút "Lên đầu trang" của theme (thay cho plugin WPFront Scroll Top đã gỡ).
 *
 * In một nút cố định ở góc dưới bên phải mọi trang phía khách. Ở trang chủ điện ảnh, nút đứng ngay phía trên dock liên hệ khi dock đang hiện
 * (scroll-top.js đo chiều cao dock) và hạ xuống mép khi dock ẩn. Nút ẩn
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
