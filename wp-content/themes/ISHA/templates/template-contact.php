<?php
/**
 * Template Name: Liên hệ
 * Template Post Type: page
 *
 * Trang Liên hệ (18/11/2026, docs/home-architecture.md mục 17): thông tin liên hệ, form gửi lời nhắn (lưu vào hộp thư Liên hệ trong admin,
 * gửi email báo phòng khám), bản đồ. Markup ở template-parts/contact/page.php (dùng chung với trang Đặt lịch hẹn), tài nguyên ở inc/forms.php.
 * Nội dung trang trong trình soạn thảo là lời dẫn dưới tiêu đề.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/contact/page', null, array( 'mode' => 'contact' ) );
endwhile;

get_footer();
