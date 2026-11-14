<?php
/**
 * Trang "Bài viết" của WordPress (Cài đặt › Đọc › Trang bài viết): ở website này là trang Danh sách tin tức.
 *
 * WordPress dùng mẫu này cho trang đó (và bỏ qua mẫu trang chọn tay lẫn page-{slug}.php), nên danh sách tin tức dạng Editorial
 * Bento Grid (post type isha_article) được gọi tại đây, dùng chung template với trang Danh sách sự kiện: xem
 * inc/editorial-list.php và template-parts/editorial/.
 *
 * Khi trang chủ đang hiển thị "bài mới nhất" (không dùng trang tĩnh) thì giữ nguyên giao diện danh sách mặc định (index.php).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_home() && ! is_front_page() ) {
	isha_editorial_render_page( 'isha_article' );
	return;
}

get_template_part( 'index' );
