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
