<?php
/**
 * Chi tiết liệu trình: khung mở đầu (nhóm, tên, mô tả ngắn, thời gian, ảnh đại diện), nội dung, các bước, ảnh liệu trình, thẻ thông tin
 * đặt lịch, liệu trình khác trong nhóm, rồi cảnh kết của trang chủ.
 *
 * Markup nằm ở template-parts/service-single.php, dữ liệu ở inc/service-single.php (isha_service_single_data()).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/service-single', null, isha_service_single_data( get_the_ID() ) );
endwhile;

// Kết trang: cảnh kết của trang chủ ([isha_home_cta]); bị tắt trong CMS thì dùng khối CTA đặt lịch cũ.
$isha_cta = isha_do_shortcode( 'isha_home_cta' );
if ( '' !== trim( $isha_cta ) ) {
	echo $isha_cta; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML do template của cảnh kết tự escape.
} else {
	isha_cta_section();
}

get_footer();
