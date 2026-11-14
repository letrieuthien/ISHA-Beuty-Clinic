<?php
/**
 * Tuỳ chỉnh khu vực quản trị khi theme ISHA đang bật.
 *
 * Hiện chỉ ẩn mục Bình luận (website không dùng bình luận): menu bên trái của wp-admin và biểu tượng bình luận trên thanh admin.
 * Đây chỉ là ẩn menu, không tắt tính năng bình luận của WordPress: bài viết vẫn có thể có bình luận, và trang Bình luận vẫn mở được bằng đường dẫn trực tiếp
 * (wp-admin/edit-comments.php). Muốn tắt hẳn thì đóng bình luận ở Cài đặt › Thảo luận. Đổi lại thành hiện menu: bộ lọc `isha/admin/hide_comments_menu` trả false.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Có ẩn mục Bình luận không (mặc định có; bộ lọc `isha/admin/hide_comments_menu` đổi được).
 *
 * @return bool
 */
function isha_admin_hide_comments() {
	return (bool) apply_filters( 'isha/admin/hide_comments_menu', true );
}

/**
 * Gỡ mục Bình luận khỏi menu bên trái của wp-admin (hook admin_menu, ưu tiên muộn để gỡ cả mục do plugin thêm lại).
 */
function isha_admin_remove_comments_menu() {
	if ( ! isha_admin_hide_comments() ) {
		return;
	}
	remove_menu_page( 'edit-comments.php' );
}
add_action( 'admin_menu', 'isha_admin_remove_comments_menu', 999 );

/**
 * Gỡ biểu tượng Bình luận khỏi thanh admin (hook admin_bar_menu, hiện cả ở trang khách khi đang đăng nhập).
 *
 * @param WP_Admin_Bar $bar Thanh admin.
 */
function isha_admin_remove_comments_bar( $bar ) {
	if ( ! isha_admin_hide_comments() || ! is_object( $bar ) || ! method_exists( $bar, 'remove_node' ) ) {
		return;
	}
	$bar->remove_node( 'comments' );
}
add_action( 'admin_bar_menu', 'isha_admin_remove_comments_bar', 999 );
