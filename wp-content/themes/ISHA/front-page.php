<?php
/**
 * Trang chủ: định tuyến giữa landing và trang chủ điện ảnh (docs/home-architecture.md, mục 5.2).
 *
 * - Chế độ landing BẬT (mặc định) và không xem trước: giữ nguyên hành vi cũ, chỉ in template-parts/landing-policy với
 *   header.php và footer.php như trước.
 * - Chế độ landing tắt, hoặc quản trị viên xem trước bằng `?isha_preview=home`: header-home.php (get_header( 'home' )), rồi
 *   isha_home_render() dựng 13 section và dock. Nội dung do ISHA Core và trang "Trang chủ" cung cấp (inc/home/data.php);
 *   Customizer cũ (hero_*, cta_*) chỉ còn là chữ dự phòng cho hero và cảnh kết.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_home = isha_home_is_active();

get_header( $isha_home ? 'home' : null );

if ( $isha_home ) {
	isha_home_render();
} else {
	// Giai đoạn landing: chỉ hiển thị Chính sách khách hàng (bật/tắt ở Tùy biến > ISHA > Chế độ landing).
	get_template_part( 'template-parts/landing-policy' );
}

get_footer();
