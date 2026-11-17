<?php
/**
 * Template Name: Đặt lịch hẹn
 * Template Post Type: page
 *
 * Trang Đặt lịch hẹn (18/11/2026, docs/home-architecture.md mục 17). Khách thường đặt lịch qua popup (booking.js chặn liên kết tới trang này);
 * trang vẫn là đường dự phòng khi không có JS hoặc khi mở liên kết ở thẻ mới. Cùng bố cục với trang Liên hệ (template-parts/contact/page.php),
 * cột phải là form đặt lịch (?service=ID chọn sẵn dịch vụ). Nội dung trang trong trình soạn thảo là lời dẫn dưới tiêu đề.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/contact/page', null, array( 'mode' => 'booking' ) );
endwhile;

get_footer();
