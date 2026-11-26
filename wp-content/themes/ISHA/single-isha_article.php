<?php
/**
 * Trang chi tiết Tin tức (post type isha_article), 18/11/2026 (docs/home-architecture.md mục 17); nâng cấp 25/11/2026 (docs/news.md): markup ở
 * template-parts/post-article.php (Sự kiện vẫn dùng template-parts/post-single.php), dữ liệu ở inc/post-single.php; kết trang bằng cảnh kết
 * của trang chủ ([isha_home_cta]), tắt trong CMS thì dùng khối CTA cũ. Vùng nội dung bài (the_content) soạn được bằng trình soạn thảo mặc
 * định hoặc Elementor.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/post-article', null, isha_post_single_data( get_post() ) );
endwhile;

$isha_cta = isha_do_shortcode( 'isha_home_cta' );
if ( '' !== trim( $isha_cta ) ) {
	echo $isha_cta; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML do template của cảnh kết tự escape.
} else {
	isha_cta_section();
}

get_footer();
