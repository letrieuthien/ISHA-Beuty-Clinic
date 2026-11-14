<?php
/**
 * Template Name: Danh sách tin tức (Bento)
 * Template Post Type: page
 *
 * Danh sách Tin tức (post type isha_article) dạng Editorial Bento Grid. WordPress tự dùng mẫu này cho trang có slug "tin-tuc"
 * (quy tắc page-{slug}.php, không cần sửa dữ liệu) và vẫn chọn được tay ở mục Mẫu trang khi slug khác.
 *
 * Lưu ý: nếu trang đó đang được đặt làm "Trang bài viết" ở Cài đặt › Đọc (mặc định của website này) thì WordPress dùng home.php
 * (cùng dữ liệu isha_article) chứ không dùng mẫu này; mẫu này là dự phòng khi trang là một Page thông thường.
 *
 * Template dùng chung với trang Danh sách sự kiện: toàn bộ logic và giao diện nằm ở inc/editorial-list.php,
 * template-parts/editorial/ và assets/css/editorial-list.css. Hai trang chỉ khác nguồn dữ liệu (xem page-danh-sach-su-kien.php).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

isha_editorial_render_page( 'isha_article' );
