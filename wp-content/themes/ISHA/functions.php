<?php
/**
 * ISHA theme functions.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ISHA_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'ISHA_DIR', get_template_directory() );
define( 'ISHA_URI', get_template_directory_uri() );

$isha_includes = array(
	'inc/helpers.php',            // Hàm tiện ích (đọc option, URL đặt lịch, ...).
	'inc/setup.php',              // Theme supports, menu, image size, sidebar.
	'inc/enqueue.php',            // CSS / JS.
	'inc/preloader.php',          // Màn chờ "Cánh cửa phòng trị liệu": một lần mỗi phiên, cổng ở <head> (preloader.css/js).
	'inc/template-tags.php',      // Hàm hiển thị dùng trong template.
	'inc/script-modules.php',     // Thư viện JS tự host dạng ES Module (three, ...): đăng ký một lần, dùng chung trang chủ và trang nhóm dịch vụ.
	'inc/policy-single.php',      // Dữ liệu cho trang chi tiết Chính sách (hai màn hình đầu).
	'inc/home/data.php',          // Dữ liệu trang chủ điện ảnh: mặc định Phụ lục A, trộn với nội dung CMS, dựng sẵn từng section.
	'inc/home/registry.php',      // Trang chủ điện ảnh: danh sách 11 section theo thứ tự (tag, template, tông, class).
	'inc/home/shortcodes.php',    // Trang chủ điện ảnh: shortcode isha_home_* của từng section.
	'inc/home/assets.php',        // Trang chủ điện ảnh: nạp CSS/JS, script gate, preload, noindex khi xem trước.
	'inc/home/helpers.php',       // Trang chủ điện ảnh: hàm in markup dùng chung và isha_home_render().
	'inc/service-wheel.php',      // Shortcode [isha_service_wheel group="ID"]: vòng xoay liệu trình của trang chủ cho một nhóm dịch vụ.
	'inc/service-single.php',     // Trang chi tiết liệu trình: dữ liệu dựng sẵn và nạp tài nguyên (service-single.css/js).
	'inc/about.php',              // Trang Giới thiệu (mẫu templates/template-about.php): dữ liệu dựng sẵn và nạp tài nguyên (about.css/js).
	'inc/space.php',              // Không gian ISHA: trang danh sách (mẫu templates/template-space.php) và bài chi tiết, phong cách trang Giới thiệu (space.css).
	'inc/forms.php',              // Form công khai và popup Đặt lịch ở mọi trang (booking.css/js); trang Liên hệ, Đặt lịch hẹn (contact.css).
	'inc/post-single.php',        // Trang chi tiết Tin tức và Sự kiện: dữ liệu dựng sẵn và nạp tài nguyên (post-article.css, post-event.css).
	'inc/editorial-list.php',     // Trang danh sách Tin tức / Sự kiện dạng Editorial Bento Grid (một template dùng chung, editorial-list.css).
	'inc/services-hero.php',      // Trang danh sách dịch vụ: hero nan quạt (dữ liệu, nạp services-hero.css/js).
	'inc/service-group.php',      // Trang nhóm dịch vụ: section Giới thiệu dịch vụ + Hiệu quả liệu trình (dữ liệu, nạp service-group.css/js).
	'inc/services-cinematic.php', // Trang nhóm dịch vụ: section "Bình minh lan tỏa" (WebGL theo cuộn, script modules tự host trong assets/vendor).
	'inc/header.php',             // Header: bộ chuyển ngôn ngữ TranslatePress (VI / EN), ẩn mục English khỏi menu chính, nút mở menu con.
	'inc/live-search.php',        // Tìm kiếm nhanh trên header: nút kính lúp sau "Đặt lịch", popup <dialog>, AJAX isha_live_search (live-search.css/js).
	'inc/scroll-top.php',       // Nút Lên đầu trang (thay plugin WPFront Scroll Top): markup ở chân trang, scroll-top.css/js.
	'inc/footer.php',             // Chân trang 3 tầng: hotline, giờ làm việc, chỉ đường, menu dự phòng, điều kiện hiện dải mời đặt lịch.
	'inc/admin.php',              // Tuỳ chỉnh wp-admin khi theme bật: ẩn mục Bình luận (menu và thanh admin).
	'inc/customizer.php',         // Tuỳ biến giao diện (hero, footer, ...).
	'inc/demo-content.php',       // Tạo trang + menu mặc định khi kích hoạt theme.
	'inc/plugins/recommended.php',// Nhắc cài plugin ISHA Core & plugin khuyên dùng.
	'inc/plugins/compat.php',     // Tương thích plugin bên thứ ba (Contact Form 7, ...).
);

foreach ( $isha_includes as $isha_file ) {
	require_once ISHA_DIR . '/' . $isha_file;
}
unset( $isha_includes, $isha_file );
