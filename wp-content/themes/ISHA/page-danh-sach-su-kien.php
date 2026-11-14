<?php
/**
 * Template Name: Danh sách sự kiện (Bento)
 * Template Post Type: page
 *
 * Danh sách Sự kiện (post type isha_event) dạng Editorial Bento Grid. WordPress tự dùng mẫu này cho trang có slug
 * "danh-sach-su-kien" (quy tắc page-{slug}.php, không cần sửa dữ liệu) và vẫn chọn được tay ở mục Mẫu trang khi slug khác.
 *
 * Template dùng chung với trang Danh sách tin tức: toàn bộ logic và giao diện nằm ở inc/editorial-list.php,
 * template-parts/editorial/ và assets/css/editorial-list.css. Hai trang chỉ khác nguồn dữ liệu (xem page-tin-tuc.php).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

isha_editorial_render_page( 'isha_event' );
