<?php
/**
 * Khung intro của trang chủ điện ảnh (đặt trong section hero): bộ khung tĩnh cho cảnh mở đầu.
 *
 * Chỉ in khi hero bật intro và có câu intro. Luôn `aria-hidden="true"`: nội dung trang có trong DOM từ đầu, intro không giữ
 * tiêu điểm bàn phím. Mặc định ẩn hoàn toàn bằng CSS (`display: none` ở khối hero của home.css, không dùng thuộc tính
 * `hidden` để giai đoạn 3 tự bật bằng class). Chuyển động (mặt trời nhô lên, tia Light Gloom vẽ ra, câu intro hiện dưới)
 * do home.js làm ở giai đoạn sau.
 *
 * Dữ liệu: `$args` là dữ liệu hero (isha_home_data( 'hero' )) do template hero truyền vào; không truyền thì tự lấy.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_hero = ( isset( $args ) && is_array( $args ) && array_key_exists( 'intro_text', $args ) ) ? $args : isha_home_data( 'hero' );
$isha_text = ( is_array( $isha_hero ) && isset( $isha_hero['intro_text'] ) && is_scalar( $isha_hero['intro_text'] ) ) ? trim( (string) $isha_hero['intro_text'] ) : '';

if ( empty( $isha_hero['intro_enabled'] ) || '' === $isha_text ) {
	return;
}
?>
<div class="isha-intro" data-isha-intro aria-hidden="true">
	<div class="isha-intro__sun" data-isha-intro-sun></div>
	<svg class="isha-intro__rays" data-isha-intro-rays viewBox="-100 -100 200 200" focusable="false" aria-hidden="true"></svg>
	<p class="isha-intro__text" data-isha-intro-text><?php echo esc_html( $isha_text ); ?></p>
</div>
