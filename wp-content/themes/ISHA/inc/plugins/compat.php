<?php
/**
 * Tương thích plugin bên thứ ba.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact Form 7: tắt autop để form không bị chèn <br> / <p> thừa.
 */
if ( defined( 'WPCF7_VERSION' ) ) {
	add_filter( 'wpcf7_autop_or_not', '__return_false' );
}

/**
 * Yoast SEO: dùng breadcrumb của Yoast nếu có.
 *
 * @return string HTML breadcrumb hoặc chuỗi rỗng.
 */
function isha_breadcrumbs() {
	if ( function_exists( 'yoast_breadcrumb' ) ) {
		return yoast_breadcrumb( '<nav class="breadcrumbs" aria-label="Breadcrumb">', '</nav>', false );
	}
	return '';
}

/**
 * Elementor: nạp assets/css/elementor.css (ghi đè CSS Elementor, các lớp tiện ích isha-elementor-*) sau CSS của Elementor.
 * Hook này chạy cả ngoài trang lẫn trong khung xem trước của trình soạn, và chỉ khi Elementor bật.
 */
function isha_elementor_overrides_style() {
	wp_enqueue_style(
		'isha-elementor',
		ISHA_URI . '/assets/css/elementor.css',
		array( 'elementor-frontend', 'isha-main' ),
		(string) filemtime( ISHA_DIR . '/assets/css/elementor.css' )
	);
}
add_action( 'elementor/frontend/after_enqueue_styles', 'isha_elementor_overrides_style' );
