<?php
/**
 * Template Name: Không gian ISHA
 * Template Post Type: page
 *
 * Trang danh sách Không gian ISHA (18/11/2026, docs/home-architecture.md mục 18): hero (meta box "Hero banner" của trang), mỗi bài Không
 * gian một section theo phong cách trang Giới thiệu kèm liên kết tới bài, rồi cảnh kết của trang chủ. Dữ liệu ở inc/space.php, markup ở
 * template-parts/space/list.php. Bài soạn ở menu Không gian ISHA của trang quản trị; nội dung soạn trong Trang này không hiển thị.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/space/list', null, isha_space_list_data( get_the_ID() ) );
endwhile;

// Kết trang: cảnh kết của trang chủ ([isha_home_cta]); bị tắt trong CMS thì dùng khối CTA đặt lịch cũ.
$isha_cta = isha_do_shortcode( 'isha_home_cta' );
if ( '' !== trim( $isha_cta ) ) {
	echo $isha_cta; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML do template của cảnh kết tự escape.
} else {
	isha_cta_section();
}

get_footer();
