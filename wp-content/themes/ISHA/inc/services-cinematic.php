<?php
/**
 * Section "Bình minh lan tỏa" (services cinematic) của trang nhóm dịch vụ: thay section "Các liệu trình trong nhóm".
 *
 * Mỗi liệu trình là một điểm dừng khi cuộn: mặt trời mọc từ chân đỉnh núi chính lên trên đỉnh, card kính mờ của liệu trình lật vào ở giữa
 * màn hình. Cảnh nền (v3, đang dựng): một ảnh núi thật + bản đồ độ sâu suy từ độ sáng + camera 2.5D bằng WebGL (Three.js r187), cuộn
 * bằng GSAP ScrollTrigger (không pin: stage là position: sticky) và Lenis (chỉ thiết bị không cảm ứng). Đặc tả: docs/isha-lookdev-prompt-v3.md
 * (thay docs/ISHA Beauty Clinic_ productsList.md cho phần cảnh); ghi chú quyết định: README-services-cinematic.md (thư mục theme).
 *
 * Tệp này: dựng dữ liệu (isha_svc_data, cache transient 12 giờ theo nhóm, xoá khi lưu liệu trình hoặc sửa nhóm), đăng ký thư viện tự host
 * trong assets/vendor bằng WordPress Script Modules API (importmap gốc của WP 6.5+), nạp CSS/JS có điều kiện (chỉ trang nhóm dịch vụ có
 * liệu trình). Markup ở template-parts/services-cinematic.php; giao diện ở assets/css/services-cinematic.css; JS ở
 * assets/js/services-cinematic/ (main.js là entry). Không JS / giảm chuyển động: section là lưới thẻ tĩnh (HTML thật, đủ cho SEO).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ISHA_SVC_MAX' ) ) {
	define( 'ISHA_SVC_MAX', 6 );       // Số liệu trình tối đa của section (nhiều hơn thì lấy theo menu_order).
}
if ( ! defined( 'ISHA_SVC_CACHE_TTL' ) ) {
	define( 'ISHA_SVC_CACHE_TTL', 12 * HOUR_IN_SECONDS );
}

/**
 * Thư viện tự host: id module (trùng bare specifier dùng trong import) => array( đường dẫn trong assets/vendor, phiên bản, phụ thuộc tĩnh ).
 *
 * @return array
 */
function isha_svc_vendor_modules() {
	return array(
		'three'                             => array( 'three@0.187.0/build/three.module.js', '0.187.0', array() ),
		'three/addons/libs/stats.module.js' => array( 'three@0.187.0/examples/jsm/libs/stats.module.js', '0.187.0', array() ),
		'gsap'                              => array( 'gsap@3.15.1/index.js', '3.15.1', array() ),
		'gsap/ScrollTrigger'                => array( 'gsap@3.15.1/ScrollTrigger.js', '3.15.1', array() ),
		'lenis'                             => array( 'lenis@1.3.27/dist/lenis.mjs', '1.3.27', array() ),
		'lil-gui'                           => array( 'lil-gui@0.21.0/dist/lil-gui.esm.js', '0.21.0', array() ),
		// Phân loại GPU; dữ liệu benchmark tự host cạnh nó (detect-gpu@6.0.28/dist/benchmarks, tier.js đặt benchmarksURL).
		'@pmndrs/detect-gpu'                => array( 'detect-gpu@6.0.28/dist/index.mjs', '6.0.28', array() ),
	);
}

/**
 * Bảng debug (lil-gui + stats) chỉ cho quản trị viên đã đăng nhập và có ?isha-debug=1.
 *
 * @return bool
 */
function isha_svc_debug_enabled() {
	return isset( $_GET['isha-debug'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['isha-debug'] ) ) && current_user_can( 'manage_options' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ bật giao diện debug, không thay đổi dữ liệu.
}

/**
 * Dữ liệu của section cho một nhóm dịch vụ (hợp đồng PHP → JS, xem đặc tả mục "HỢP ĐỒNG DỮ LIỆU"). Cache transient 'isha_svc_{term_id}'.
 *
 * @param WP_Term $term Nhóm dịch vụ.
 * @return array{version:int,category:array,services:array,cta:array,seed:int} services rỗng khi nhóm chưa có liệu trình đã xuất bản.
 */
function isha_svc_data( WP_Term $term ) {
	static $memo = array();

	$term_id = (int) $term->term_id;
	if ( isset( $memo[ $term_id ] ) ) {
		return $memo[ $term_id ];
	}

	$key    = 'isha_svc_' . $term_id;
	$cached = get_transient( $key );
	if ( is_array( $cached ) && isset( $cached['version'] ) && 1 === $cached['version'] ) {
		$memo[ $term_id ] = $cached;
		return $cached;
	}

	$items = function_exists( 'isha_service_group_items' ) ? isha_service_group_items( $term ) : array();
	if ( count( $items ) > ISHA_SVC_MAX ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[ISHA svc] Nhóm "%s" có %d liệu trình, section chỉ hiển thị %d liệu trình đầu theo menu_order.', $term->slug, count( $items ), ISHA_SVC_MAX ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		$items = array_slice( $items, 0, ISHA_SVC_MAX );
	}

	$services = array();
	foreach ( $items as $item ) {
		$image = null;
		if ( ! empty( $item['thumb'] ) ) {
			$src = wp_get_attachment_image_src( (int) $item['thumb'], 'isha-frame' );
			if ( $src ) {
				$alt   = trim( (string) get_post_meta( (int) $item['thumb'], '_wp_attachment_image_alt', true ) );
				$image = array(
					'id'     => (int) $item['thumb'],
					'src'    => (string) $src[0],
					'srcset' => (string) wp_get_attachment_image_srcset( (int) $item['thumb'], 'isha-frame' ),
					'sizes'  => '(max-width: 600px) 88vw, 420px',
					'alt'    => '' !== $alt ? $alt : $item['title'],
					'w'      => (int) $src[1],
					'h'      => (int) $src[2],
				);
			}
		}
		$excerpt = (string) $item['desc'];
		if ( function_exists( 'mb_strlen' ) && mb_strlen( $excerpt ) > 160 ) {
			$excerpt = rtrim( mb_substr( $excerpt, 0, 159 ) ) . '…';
		}
		$services[] = array(
			'id'       => (int) $item['id'],
			'slug'     => (string) get_post_field( 'post_name', (int) $item['id'] ),
			'title'    => (string) $item['title'],
			'excerpt'  => $excerpt,
			'duration' => (string) $item['duration'],
			'url'      => (string) $item['url'],
			'image'    => $image,
		);
	}

	$contact = function_exists( 'isha_home_contact' ) ? isha_home_contact() : array();
	$data    = array(
		'version'  => 1,
		'category' => array(
			'id'   => $term_id,
			'slug' => (string) $term->slug,
			'name' => trim( wp_strip_all_tags( html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ) ) ),
		),
		'services' => $services,
		'cta'      => array(
			'label'   => __( 'Đặt lịch tư vấn riêng', 'isha' ),
			'url'     => isset( $contact['booking_url'] ) ? (string) $contact['booking_url'] : '',
			'zalo'    => isset( $contact['zalo_url'] ) ? (string) $contact['zalo_url'] : '',
			'hotline' => isset( $contact['tel_url'] ) ? (string) $contact['tel_url'] : '',
			'phone'   => isset( $contact['phone'] ) ? (string) $contact['phone'] : '',
		),
		// Seed cố định theo nhóm: mỗi nhóm có cảnh hơi khác nhau, mọi lần tải của cùng nhóm giống hệt nhau.
		'seed'     => ( $term_id * 7919 ) % 100000,
	);

	set_transient( $key, $data, ISHA_SVC_CACHE_TTL );
	$memo[ $term_id ] = $data;
	return $data;
}

/**
 * Xoá cache của các nhóm chứa một liệu trình vừa lưu (kể cả đổi nhóm: xoá theo nhóm cũ lẫn mới vì set_object_terms chạy trước hook này).
 *
 * @param int $post_id ID liệu trình.
 */
function isha_svc_flush_post( $post_id ) {
	$terms = get_the_terms( (int) $post_id, 'isha_service_cat' );
	if ( is_array( $terms ) ) {
		foreach ( $terms as $term ) {
			delete_transient( 'isha_svc_' . (int) $term->term_id );
		}
	}
}
add_action( 'save_post_isha_service', 'isha_svc_flush_post' );
add_action( 'before_delete_post', 'isha_svc_flush_post' );

/**
 * Xoá cache khi sửa, tạo hoặc gán lại liệu trình cho nhóm.
 *
 * @param int $term_id ID nhóm.
 */
function isha_svc_flush_term( $term_id ) {
	delete_transient( 'isha_svc_' . (int) $term_id );
}
add_action( 'edited_isha_service_cat', 'isha_svc_flush_term' );
add_action( 'created_isha_service_cat', 'isha_svc_flush_term' );

/**
 * Gán/bỏ nhóm của một liệu trình (Quick Edit, REST): xoá cache của các nhóm liên quan.
 *
 * @param int    $object_id  ID bài.
 * @param array  $terms      Term mới.
 * @param array  $tt_ids     Term taxonomy ID mới.
 * @param string $taxonomy   Taxonomy.
 * @param bool   $append     Thêm hay thay.
 * @param array  $old_tt_ids Term taxonomy ID cũ.
 */
function isha_svc_flush_relationships( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
	if ( 'isha_service_cat' !== $taxonomy ) {
		return;
	}
	foreach ( array_unique( array_merge( (array) $tt_ids, (array) $old_tt_ids ) ) as $tt_id ) {
		$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, 'isha_service_cat' );
		if ( $term instanceof WP_Term ) {
			delete_transient( 'isha_svc_' . (int) $term->term_id );
		}
	}
}
add_action( 'set_object_terms', 'isha_svc_flush_relationships', 10, 6 );

/**
 * Trang hiện tại có in section cinematic không: trang nhóm dịch vụ có ít nhất một liệu trình. Bộ lọc 'isha_svc_should_load' cho phép tắt
 * (trả false: trang nhóm quay về lưới thẻ editorial template-parts/service-group/list.php).
 *
 * @return bool
 */
function isha_svc_active() {
	static $active = null;
	if ( null !== $active ) {
		return $active;
	}
	$active = false;
	if ( is_tax( 'isha_service_cat' ) ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$data   = isha_svc_data( $term );
			$active = ! empty( $data['services'] );
		}
	}
	$active = (bool) apply_filters( 'isha_svc_should_load', $active );
	return $active;
}

/**
 * Nạp CSS và entry module. Thư viện là phụ thuộc 'dynamic' của entry: WordPress đưa vào importmap nhưng không tải sớm; main.js tự
 * import khi section sắp vào khung nhìn và không bật giảm chuyển động.
 */
function isha_svc_assets() {
	if ( ! isha_svc_active() ) {
		return;
	}

	$css = ISHA_DIR . '/assets/css/services-cinematic.css';
	if ( is_file( $css ) ) {
		wp_enqueue_style(
			'isha-services-cinematic',
			ISHA_URI . '/assets/css/services-cinematic.css',
			wp_style_is( 'isha-home', 'enqueued' ) || wp_style_is( 'isha-home', 'registered' ) ? array( 'isha-main', 'isha-home' ) : array( 'isha-main' ),
			(string) filemtime( $css )
		);
	}

	if ( ! function_exists( 'wp_register_script_module' ) ) {
		return; // WordPress < 6.5: không có importmap gốc; section ở chế độ lưới tĩnh.
	}

	$deps = array();
	foreach ( isha_svc_vendor_modules() as $id => $mod ) {
		list( $path, $version, $mod_deps ) = $mod;
		if ( ! is_file( ISHA_DIR . '/assets/vendor/' . $path ) ) {
			continue;
		}
		wp_register_script_module( $id, ISHA_URI . '/assets/vendor/' . $path, $mod_deps, $version );
		$deps[] = array(
			'id'     => $id,
			'import' => 'dynamic',
		);
	}

	$main = ISHA_DIR . '/assets/js/services-cinematic/main.js';
	if ( is_file( $main ) ) {
		wp_enqueue_script_module( 'isha-svc-main', ISHA_URI . '/assets/js/services-cinematic/main.js', $deps, (string) filemtime( $main ) );
	}
}
add_action( 'wp_enqueue_scripts', 'isha_svc_assets', 25 );
