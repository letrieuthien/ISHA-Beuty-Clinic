<?php
/**
 * Màn chờ "Cánh cửa phòng trị liệu": hai cánh cửa tối khép kín, núi khắc chìm, sương dưới chân cửa, vầng tia quanh logo; khi trang
 * tải xong thì cửa trượt tách đôi để lộ trang (preloader.css, preloader.js).
 *
 * - Chạy MỘT lần mỗi phiên trình duyệt (sessionStorage `isha_preloader_seen`), ở trang đầu tiên khách vào.
 * - Mặc định ẨN: chỉ hiện khi cổng trong <head> thêm `isha-preloader-on` lên <html>. Không JS, giảm chuyển động, vào bằng #neo,
 *   phiên đã xem hoặc sessionStorage bị chặn thì trang đọc ngay, không bị che. Cố ý đảo so với cách "hiện sẵn rồi ẩn bằng class
 *   skip" để không có JS vẫn đọc trọn vẹn (quy ước chuyển động của theme).
 * - Không che hay ẩn <main>: nội dung vẫn có trong DOM từ đầu (SEO, LCP).
 * - Trang chủ khi intro riêng (mặt trời nhô lên, home.js) sắp chạy: intro đó CHÍNH LÀ màn mở đầu của phiên, nên cổng chỉ đánh dấu
 *   đã xem rồi nhường, không xếp hai màn chồng nhau. Trang chi tiết Chính sách có preloader riêng nên bị loại.
 *
 * Markup in ở `wp_body_open` (cả header.php lẫn header-home.php đều gọi), không cần sửa hai tệp đó.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trang hiện tại có in màn chờ không?
 *
 * Loại: trình xem trước Customizer, trình dựng Elementor, trình dịch TranslatePress (khung xem trước), và trang Chính sách (preloader riêng).
 * Bộ lọc `isha_preloader_enabled` cho phép tắt hoặc bật thêm theo trang.
 *
 * @return bool
 */
function isha_preloader_enabled() {
	$enabled = ! is_customize_preview() && ! is_singular( 'isha_policy' );

	if ( $enabled && defined( 'ELEMENTOR_VERSION' ) && class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
		$enabled = false;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ kiểm tồn tại tham số của TranslatePress, không đọc giá trị.
	if ( $enabled && isset( $_GET['trp-edit-translation'] ) ) {
		$enabled = false;
	}

	return (bool) apply_filters( 'isha_preloader_enabled', $enabled );
}

/**
 * Trang chủ có intro riêng (hero bật intro và có câu intro)? Cùng điều kiện với isha_home_head_gate() (inc/home/assets.php).
 *
 * @return bool
 */
function isha_preloader_home_has_intro() {
	if ( ! function_exists( 'isha_home_is_active' ) || ! isha_home_is_active() ) {
		return false;
	}
	$hero = isha_home_data( 'hero' );
	return is_array( $hero ) && ! empty( $hero['intro_enabled'] ) && isset( $hero['intro_text'] ) && is_scalar( $hero['intro_text'] ) && '' !== trim( (string) $hero['intro_text'] );
}

/**
 * Cổng trong <head> (rất ngắn, chạy trước khung hình đầu tiên): quyết định có hiện màn chờ không.
 *
 * Bỏ qua khi: phiên đã xem, URL có #neo, giảm chuyển động, sessionStorage lỗi. Ở trang chủ có intro riêng mà intro chưa chạy trong
 * phiên thì đánh dấu đã xem và nhường cho intro. Còn lại thêm `isha-preloader-on` (CSS bật lớp phủ và khoá cuộn).
 * Ghi `ishaPreT` (mốc thời gian lớp phủ bắt đầu hiện) để preloader.js tính sàn thời gian chờ từ khung hình đầu, không phải từ lúc script tải xong.
 * Chốt an toàn: sau 5 giây mà preloader.js chưa tiếp quản (chưa gỡ `ishaPreGuard`) thì tự gỡ class, trang vẫn dùng được.
 */
function isha_preloader_head_gate() {
	if ( ! isha_preloader_enabled() ) {
		return;
	}

	$js  = "(function(d,w){try{var s=w.sessionStorage,k='isha_preloader_seen';";
	$js .= "if(s.getItem(k)||w.location.hash||w.matchMedia('(prefers-reduced-motion: reduce)').matches){return;}";
	if ( isha_preloader_home_has_intro() ) {
		$js .= "if(!s.getItem('ishaIntro')){s.setItem(k,'1');return;}";
	}
	$js .= "var h=d.documentElement;h.classList.add('isha-preloader-on');w.ishaPreT=w.performance&&w.performance.now?w.performance.now():0;";
	$js .= "w.ishaPreGuard=setTimeout(function(){h.classList.remove('isha-preloader-on');},5000);";
	$js .= '}catch(e){}}(document,window));';

	wp_print_inline_script_tag( $js );
}
add_action( 'wp_head', 'isha_preloader_head_gate', 1 );

/**
 * CSS và JS của màn chờ. CSS chặn hiển thị (nằm trong <head>) để lớp phủ có kiểu ngay từ khung hình đầu; JS defer ở <head> để tải sớm.
 */
function isha_preloader_assets() {
	if ( ! isha_preloader_enabled() ) {
		return;
	}

	$css = ISHA_DIR . '/assets/css/preloader.css';
	if ( is_file( $css ) ) {
		wp_enqueue_style(
			'isha-preloader',
			ISHA_URI . '/assets/css/preloader.css',
			array( 'isha-main' ),
			filemtime( $css )
		);
	}

	$js = ISHA_DIR . '/assets/js/preloader.js';
	if ( is_file( $js ) ) {
		wp_enqueue_script(
			'isha-preloader',
			ISHA_URI . '/assets/js/preloader.js',
			array(),
			filemtime( $js ),
			array(
				'in_footer' => false,
				'strategy'  => 'defer',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'isha_preloader_assets' );

/**
 * Markup lớp phủ, ngay đầu <body>. Chỉ trang trí (`aria-hidden`), không có chữ nên không cần dịch.
 * Ảnh (núi, mây, logo, vầng tia) nạp bằng CSS và chỉ khi `html.isha-preloader-on` có mặt, nên các trang sau trong phiên không tải thêm byte ảnh nào.
 * Cố ý không preload ảnh: ảnh nền CSS được trình duyệt nạp ngay khi lớp phủ hiện, và lớp núi/mây mờ dần vào nên không giật.
 */
function isha_preloader_markup() {
	if ( ! isha_preloader_enabled() ) {
		return;
	}
	?>
<div id="isha-preloader" class="isha-preloader" aria-hidden="true">
	<div class="isha-preloader__light"></div>
	<div class="isha-preloader__door isha-preloader__door--left"><div class="isha-preloader__ridge"></div></div>
	<div class="isha-preloader__door isha-preloader__door--right"><div class="isha-preloader__ridge"></div></div>
	<div class="isha-preloader__mist">
		<div class="isha-preloader__cloud isha-preloader__cloud--left"><div class="isha-preloader__cloud-img"></div></div>
		<div class="isha-preloader__cloud isha-preloader__cloud--right"><div class="isha-preloader__cloud-img"></div></div>
		<div class="isha-preloader__cloud isha-preloader__cloud--left-2"><div class="isha-preloader__cloud-img"></div></div>
		<div class="isha-preloader__cloud isha-preloader__cloud--right-2"><div class="isha-preloader__cloud-img"></div></div>
		<div class="isha-preloader__cloud isha-preloader__cloud--mid-left"><div class="isha-preloader__cloud-img"></div></div>
		<div class="isha-preloader__cloud isha-preloader__cloud--mid-right"><div class="isha-preloader__cloud-img"></div></div>
	</div>
	<div class="isha-preloader__seam"></div>
	<div class="isha-preloader__beam isha-preloader__beam--top"></div>
	<div class="isha-preloader__beam isha-preloader__beam--bottom"></div>
	<div class="isha-preloader__emblem">
		<div class="isha-preloader__halo"><div class="isha-preloader__halo-ring"></div></div>
		<div class="isha-preloader__logo"><span class="isha-preloader__mark"></span></div>
	</div>
</div>
	<?php
}
add_action( 'wp_body_open', 'isha_preloader_markup', 1 );
