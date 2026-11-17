<?php
/**
 * Trang chi tiết Sự kiện (post type isha_event), 18/11/2026 (docs/home-architecture.md mục 17): markup ở template-parts/post-single.php,
 * dữ liệu ở inc/post-single.php; kết trang bằng cảnh kết của trang chủ ([isha_home_cta]), tắt trong CMS thì dùng khối CTA cũ.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/post-single', null, isha_post_single_data( get_post() ) );
endwhile;

$isha_cta = isha_do_shortcode( 'isha_home_cta' );
if ( '' !== trim( $isha_cta ) ) {
	echo $isha_cta; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML do template của cảnh kết tự escape.
} else {
	isha_cta_section();
}

get_footer();
