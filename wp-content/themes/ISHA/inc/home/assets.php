<?php
/**
 * Tài nguyên của trang chủ điện ảnh: CSS/JS, cấu hình JS, script gate trong <head>, preload, noindex và nocache khi xem trước.
 *
 * Chỉ nạp khi trang chủ điện ảnh đang được dựng (isha_home_is_active()) hoặc khi một shortcode isha_home_* được nhúng ở
 * trang khác. Không nạp page.css (isha_is_inner_page() trả false ở trang chủ mới). Tệp CSS/JS chưa có thì bỏ qua êm,
 * không báo lỗi. Hợp đồng ở docs/home-architecture.md, mục 5.5.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nạp style `isha-home` (home.css), script `isha-motion` (isha-motion.js) rồi `isha-home-js` (home.js), kèm cấu hình
 * `window.ISHA.homeConfig` chạy trước home.js. Script `defer` ở cuối trang; phiên bản theo filemtime. Tệp nào chưa có thì
 * bỏ qua; chạy lại nhiều lần không nạp hay in cấu hình trùng.
 *
 * Gọi ở `wp_enqueue_scripts` (trang chủ) hoặc từ shortcode nhúng (khi đó style in muộn ở chân trang).
 */
function isha_home_enqueue_assets() {
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	$css = $dir . '/assets/css/home.css';
	$mot = $dir . '/assets/js/isha-motion.js';
	$js  = $dir . '/assets/js/home.js';

	if ( is_file( $css ) ) {
		wp_enqueue_style( 'isha-home', $uri . '/assets/css/home.css', array( 'isha-main' ), (string) filemtime( $css ) );
	}

	$script_args = array(
		'in_footer' => true,
		'strategy'  => 'defer',
	);
	if ( is_file( $mot ) ) {
		wp_enqueue_script( 'isha-motion', $uri . '/assets/js/isha-motion.js', array(), (string) filemtime( $mot ), $script_args );
	}
	if ( is_file( $js ) ) {
		$first_time = ! wp_script_is( 'isha-home-js', 'enqueued' );
		wp_enqueue_script( 'isha-home-js', $uri . '/assets/js/home.js', is_file( $mot ) ? array( 'isha-motion' ) : array(), (string) filemtime( $js ), $script_args );
		if ( $first_time ) {
			wp_add_inline_script( 'isha-home-js', 'window.ISHA=window.ISHA||{};window.ISHA.homeConfig=' . isha_home_config_json() . ';', 'before' );
		}
	}
}

/**
 * Cấu hình JS của trang chủ dạng JSON: cờ xem trước và các chuỗi đã dịch (home.js không tự có chuỗi tiếng Việt).
 *
 * @return string JSON an toàn để đặt trong thẻ script.
 */
function isha_home_config_json() {
	$config = array(
		'preview' => isha_home_is_preview(),
		'strings' => array(
			'prev'     => __( 'Trước', 'isha' ),
			'next'     => __( 'Sau', 'isha' ),
			/* translators: %d: số thứ tự khung trong hàng vuốt */
			'item'     => __( 'Khung %d', 'isha' ),
			'rowLabel' => __( 'Danh sách cuộn ngang', 'isha' ),
			// Băng chuyền (sự kiện, lời kể): nhãn vai trò của khối và của từng trang chiếu theo mẫu WAI-ARIA APG.
			'carousel' => __( 'băng chuyền', 'isha' ),
			'slide'    => __( 'trang chiếu', 'isha' ),
			/* translators: 1: số thứ tự của trang chiếu, 2: tổng số trang chiếu */
			'slideOf'  => __( '%1$d trên %2$d', 'isha' ),
		),
	);
	return (string) wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );
}

/**
 * Ở trang chủ điện ảnh, hoặc ở bài/trang có nhúng shortcode isha_home_* trong nội dung (để CSS nằm trong <head>),
 * nạp tài nguyên trang chủ.
 */
function isha_home_maybe_enqueue_assets() {
	if ( isha_home_is_active() ) {
		isha_home_enqueue_assets();
		return;
	}
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post && false !== strpos( (string) $post->post_content, '[isha_home_' ) ) {
			isha_home_enqueue_assets();
		}
	}
}
add_action( 'wp_enqueue_scripts', 'isha_home_maybe_enqueue_assets' );

/**
 * Bỏ tài nguyên không dùng ở trang chủ điện ảnh: trang không có block của WordPress. Gỡ CSS của block editor (khoảng 141 KB,
 * chặn hiển thị) để LCP không gánh tài nguyên vô ích. Chạy sau các plugin (ưu tiên 100); chỉ ở trang chủ điện ảnh, các trang
 * khác giữ nguyên. Không gỡ global-styles (chứa preset của theme.json). Nút "Lên đầu trang" là của theme (inc/scroll-top.php).
 */
function isha_home_trim_assets() {
	if ( ! isha_home_is_active() ) {
		return;
	}
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
}
add_action( 'wp_enqueue_scripts', 'isha_home_trim_assets', 100 );

/**
 * Script gate trong <head> (chỉ trang chủ điện ảnh): thêm class `isha-motion-on` lên <html> để CSS bắt đầu giấu trạng thái ban đầu
 * của các cảnh (dock ẩn tới khi home.js bật); sau 6 giây nếu lõi chuyển động chưa khởi động (window.ISHA.motion.started chưa true)
 * HOẶC home.js chưa chạy (window.ISHA.home.register chưa có; home.js gán nó ngay dòng đầu của IIFE) thì thêm `isha-motion-fail` để
 * hiện toàn bộ nội dung. Thiếu vế home.js thì khi home.js lỗi hay bị chặn, dock ẩn vĩnh viễn.
 *
 * Intro (khi hero bật intro và có câu intro): thêm `isha-intro-on` ngay từ đầu để lớp phủ intro che trang từ khung hình đầu tiên
 * (không nháy hero rồi mới phủ), trừ khi giảm chuyển động, URL có #neo hoặc phiên này đã xem intro (sessionStorage 'ishaIntro',
 * do home.js đặt khi intro bắt đầu). Chốt an toàn: sau 3,5 giây luôn gỡ `isha-intro-on` dù home.js có chạy hay không.
 */
function isha_home_head_gate() {
	if ( ! isha_home_is_active() ) {
		return;
	}
	$hero  = isha_home_data( 'hero' );
	$intro = is_array( $hero ) && ! empty( $hero['intro_enabled'] ) && isset( $hero['intro_text'] ) && is_scalar( $hero['intro_text'] ) && '' !== trim( (string) $hero['intro_text'] );

	$js  = "(function(d,w){var h=d.documentElement,c=h.classList;c.add('isha-motion-on');";
	$js .= "setTimeout(function(){var i=w.ISHA||{},m=i.motion,o=i.home;if(!m||!m.started||!o||!o.register){c.add('isha-motion-fail');c.remove('isha-intro-on','isha-intro-end');}},6000);";
	if ( $intro ) {
		$js .= "try{if(!w.matchMedia('(prefers-reduced-motion: reduce)').matches&&!w.location.hash&&!w.sessionStorage.getItem('ishaIntro')){c.add('isha-intro-on');setTimeout(function(){c.remove('isha-intro-on','isha-intro-end');},3500);}}catch(e){}";
	}
	$js .= '}(document,window));';

	wp_print_inline_script_tag( $js );
}
add_action( 'wp_head', 'isha_home_head_gate', 1 );

/**
 * URL, srcset và sizes để preload một ảnh poster.
 *
 * @param int    $id   ID tệp đính kèm.
 * @param string $size Tên kích thước ảnh.
 * @return array|null array( url, srcset ) hoặc null nếu không phải ảnh có URL.
 */
function isha_home_preload_image( $id, $size ) {
	if ( $id < 1 || ! wp_attachment_is_image( $id ) ) {
		return null;
	}
	$src = wp_get_attachment_image_src( $id, $size );
	if ( ! is_array( $src ) || empty( $src[0] ) ) {
		return null;
	}
	$srcset = wp_get_attachment_image_srcset( $id, $size );
	return array(
		'url'    => (string) $src[0],
		'srcset' => is_string( $srcset ) ? $srcset : '',
	);
}

/**
 * Preload trong <head> (chỉ trang chủ điện ảnh): poster hero (ngang và dọc, chỉ khi có ảnh) kèm `fetchpriority="high"`.
 *
 * Cố ý KHÔNG preload font nào ở đây (hai font dùng nhiều nhất đã được preload ở inc/enqueue.php). Đo A/B trên 4G giả lập (9 Mbit/s,
 * RTT 170 ms) cho thấy preload thêm font tranh băng thông với CSS chặn hiển thị: Garamond Medium (388 KB) làm LCP chậm hơn 190 đến
 * 380 ms mà CLS không giảm. Font Garamond nghiêng của câu intro được home.js tải sớm bằng document.fonts.load() khi intro bắt đầu.
 *
 * Poster dùng kích thước `isha-hero` (ngang) và `isha-frame` (dọc) với sizes `100vw`; template hero nên dùng cùng
 * kích thước và sizes để trình duyệt tái dùng ảnh đã preload.
 */
function isha_home_head_preload() {
	if ( ! isha_home_is_active() ) {
		return;
	}

	$hero = isha_home_data( 'hero' );
	if ( is_array( $hero ) && $hero ) {
		$posters = array(
			'(orientation: landscape)' => array( isset( $hero['poster_landscape'] ) ? (int) $hero['poster_landscape'] : 0, 'isha-hero' ),
			'(orientation: portrait)'  => array( isset( $hero['poster_portrait'] ) ? (int) $hero['poster_portrait'] : 0, 'isha-frame' ),
		);
		foreach ( $posters as $media => $poster ) {
			$image = isha_home_preload_image( $poster[0], $poster[1] );
			$href  = null === $image ? '' : esc_url( $image['url'] );
			if ( '' === $href ) {
				continue;
			}
			printf(
				'<link rel="preload" as="image" href="%1$s"%2$s media="%3$s" fetchpriority="high">' . "\n",
				$href, // phpcs:ignore WordPress.Security.EscapeOutput -- đã qua esc_url ở trên.
				'' !== $image['srcset'] ? sprintf( ' imagesrcset="%s" imagesizes="100vw"', esc_attr( $image['srcset'] ) ) : '', // phpcs:ignore WordPress.Security.EscapeOutput -- esc_attr bên trong.
				esc_attr( $media )
			);
		}
	}

}
add_action( 'wp_head', 'isha_home_head_preload', 2 );

/**
 * Xem trước: không cho trình duyệt và plugin cache giữ trang (header nocache + hằng DONOTCACHEPAGE).
 * Chạy ở template_redirect vì chỉ lúc này mới biết đang ở trang chủ nhưng header chưa gửi.
 */
function isha_home_preview_headers() {
	if ( ! isha_home_is_preview() ) {
		return;
	}
	nocache_headers();
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
}
add_action( 'template_redirect', 'isha_home_preview_headers', 1 );

/**
 * Xem trước: thêm noindex vào thẻ robots để công cụ tìm kiếm không lập chỉ mục bản nháp.
 *
 * @param array $robots Chỉ thị robots.
 * @return array
 */
function isha_home_preview_robots( $robots ) {
	if ( is_array( $robots ) && isha_home_is_preview() ) {
		return wp_robots_no_robots( $robots );
	}
	return $robots;
}
add_filter( 'wp_robots', 'isha_home_preview_robots' );
