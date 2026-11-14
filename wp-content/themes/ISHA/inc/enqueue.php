<?php
/**
 * Enqueue scripts and styles.
 *
 * Font được tự host trong assets/fonts và khai báo bằng @font-face trong assets/css/main.css.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end assets.
 */
function isha_enqueue_assets() {
	wp_enqueue_style( 'isha-style', get_stylesheet_uri(), array(), ISHA_VERSION );
	wp_enqueue_style(
		'isha-main',
		ISHA_URI . '/assets/css/main.css',
		array( 'isha-style' ),
		filemtime( ISHA_DIR . '/assets/css/main.css' )
	);

	if ( isha_is_inner_page() ) {
		wp_enqueue_style(
			'isha-page',
			ISHA_URI . '/assets/css/page.css',
			array( 'isha-main' ),
			filemtime( ISHA_DIR . '/assets/css/page.css' )
		);
	}

	wp_enqueue_script(
		'isha-main',
		ISHA_URI . '/assets/js/main.js',
		array(),
		filemtime( ISHA_DIR . '/assets/js/main.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	// Trang chi tiết Chính sách: hai màn hình đầu (cổng thẻ kính) – CSS + JS thuần, không thư viện.
	if ( is_singular( 'isha_policy' ) ) {
		wp_enqueue_style(
			'isha-policy-single',
			ISHA_URI . '/assets/css/policy-single.css',
			array( 'isha-main' ),
			filemtime( ISHA_DIR . '/assets/css/policy-single.css' )
		);
		wp_enqueue_script(
			'isha-policy-single',
			ISHA_URI . '/assets/js/policy-single.js',
			array(),
			filemtime( ISHA_DIR . '/assets/js/policy-single.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'isha_enqueue_assets' );

/**
 * Trang chi tiết Chính sách: đánh dấu "có JS" ngay trong <head> để CSS giấu sẵn các phần sẽ hiện ở đoạn mở đầu
 * (không nháy nội dung trước khi preloader phủ lên) và khoá cuộn trong lúc mở đầu.
 * Có chốt an toàn: sau 9 giây mà JS chưa kịp tiếp quản thì bỏ hết trạng thái ẩn và mở khoá cuộn.
 */
function isha_policy_single_head() {
	if ( ! is_singular( 'isha_policy' ) ) {
		return;
	}
	?>
<script>(function(d){var h=d.documentElement;h.classList.add('isha-ps-js','isha-ps-lock');window.ishaPsGuard=setTimeout(function(){h.classList.add('isha-ps-fail');h.classList.remove('isha-ps-lock');},9000);}(document));</script>
	<?php
}
add_action( 'wp_head', 'isha_policy_single_head', 1 );

/**
 * Preload 2 font dùng nhiều nhất để giảm nhấp nháy chữ.
 */
function isha_preload_fonts() {
	$fonts = array(
		'/assets/fonts/GaramondPremrPro/GaramondPremrPro-Disp.otf' => 'font/otf',
		'/assets/fonts/Montserrat/Montserrat-SemiBold.ttf'         => 'font/ttf',
	);
	foreach ( $fonts as $path => $type ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="%s" crossorigin>' . "\n",
			esc_url( ISHA_URI . $path ),
			esc_attr( $type )
		);
	}
}
add_action( 'wp_head', 'isha_preload_fonts', 1 );
