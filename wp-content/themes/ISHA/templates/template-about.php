<?php
/**
 * Template Name: Giới thiệu
 * Template Post Type: page
 *
 * Trang Giới thiệu (17/11/2026, docs/home-architecture.md mục 16): hero, ba section Câu chuyện thương hiệu, Triết lý và sứ mệnh, Giá trị cốt
 * lõi theo phong cách "Câu chuyện nhà sáng lập" của trang chủ, rồi cảnh kết của trang chủ. Markup ở template-parts/about/page.php, dữ liệu
 * và nạp tài nguyên ở inc/about.php. Nội dung trang (trình soạn thảo) không được dùng: nhập ở khung "Nội dung trang Giới thiệu" và
 * "Hero banner" của trang.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/about/page', null, isha_about_data( get_the_ID() ) );
endwhile;

// Kết trang: cảnh kết của trang chủ ([isha_home_cta]); bị tắt trong CMS thì dùng khối CTA đặt lịch cũ.
$isha_cta = isha_do_shortcode( 'isha_home_cta' );
if ( '' !== trim( $isha_cta ) ) {
	echo $isha_cta; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML do template của cảnh kết tự escape.
} else {
	isha_cta_section();
}

get_footer();
