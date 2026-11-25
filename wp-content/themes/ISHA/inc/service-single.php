<?php
/**
 * Trang chi tiết liệu trình (single-isha_service.php): dữ liệu dựng sẵn cho template và nạp tài nguyên.
 *
 * Giữ nguyên dữ liệu và luồng đặt lịch hiện có: trường đọc qua ISHA_Core_Service_Fields::get() (duration, short_desc, gallery, steps),
 * nút đặt lịch là isha_booking_url() kèm ?service=<ID>. Tách khỏi template để kiểm thử được bằng PHP CLI. Giao diện dùng lại hệ thống
 * của trang chủ điện ảnh (class isha-home, tông, nút, ảnh, hàng vuốt), nên trang nạp cả tài nguyên trang chủ (cảnh kết là shortcode
 * [isha_home_cta]). Hợp đồng ở docs/home-architecture.md, mục 12.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nhóm chính của một liệu trình: nếu thuộc nhiều nhóm thì lấy nhóm đứng trước theo thứ tự hiển thị (ISHA_Core_Term_Fields::ordered_terms()).
 *
 * @param int $post_id ID liệu trình.
 * @return array array( 'id' => int, 'name' => string, 'url' => string ), hoặc array() nếu chưa gán nhóm.
 */
function isha_service_single_group( $post_id ) {
	$terms = get_the_terms( (int) $post_id, 'isha_service_cat' );
	if ( ! is_array( $terms ) || ! $terms ) {
		return array();
	}

	$by_id = array();
	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term ) {
			$by_id[ (int) $term->term_id ] = $term;
		}
	}
	if ( ! $by_id ) {
		return array();
	}

	$pick = null;
	if ( class_exists( 'ISHA_Core_Term_Fields' ) && is_callable( array( 'ISHA_Core_Term_Fields', 'ordered_terms' ) ) ) {
		foreach ( (array) ISHA_Core_Term_Fields::ordered_terms() as $ordered ) {
			if ( $ordered instanceof WP_Term && isset( $by_id[ (int) $ordered->term_id ] ) ) {
				$pick = $by_id[ (int) $ordered->term_id ];
				break;
			}
		}
	}
	if ( ! $pick ) {
		$pick = reset( $by_id );
	}

	$url = get_term_link( $pick );
	return array(
		'id'   => (int) $pick->term_id,
		'name' => isha_home_data_plain( $pick->name ),
		'url'  => is_wp_error( $url ) ? '' : (string) $url,
	);
}

/**
 * Ảnh đại diện của khung mở đầu: ảnh đại diện của bài, không có thì ảnh bìa của nhóm, không có nữa thì 0 (khối giữ chỗ).
 *
 * @param int   $post_id ID liệu trình.
 * @param array $group   Kết quả của isha_service_single_group().
 * @return int ID tệp đính kèm hoặc 0.
 */
function isha_service_single_image_id( $post_id, array $group ) {
	$thumb = (int) get_post_thumbnail_id( (int) $post_id );
	if ( $thumb && wp_attachment_is_image( $thumb ) ) {
		return $thumb;
	}
	if ( ! empty( $group['id'] ) && class_exists( 'ISHA_Core_Term_Fields' ) && is_callable( array( 'ISHA_Core_Term_Fields', 'get' ) ) ) {
		$fields = ISHA_Core_Term_Fields::get( (int) $group['id'] );
		$cover  = is_array( $fields ) && isset( $fields['cover_id'] ) ? (int) $fields['cover_id'] : 0;
		if ( $cover && wp_attachment_is_image( $cover ) ) {
			return $cover;
		}
	}
	return 0;
}

/**
 * Ảnh liệu trình (gallery) đã chuẩn hoá cho hàng vuốt và hộp xem lớn: ID, URL ảnh xem lớn (size isha-strip, không cắt), alt, chú thích.
 *
 * @param int[] $ids ID tệp đính kèm.
 * @return array Danh sách array( 'id', 'url', 'alt', 'caption' ); ảnh hỏng bị bỏ.
 */
function isha_service_single_gallery( array $ids ) {
	$out = array();
	foreach ( $ids as $id ) {
		$id  = (int) $id;
		$url = $id > 0 && wp_attachment_is_image( $id ) ? wp_get_attachment_image_url( $id, 'isha-strip' ) : '';
		if ( ! $url ) {
			continue;
		}
		$alt     = get_post_meta( $id, '_wp_attachment_image_alt', true );
		$caption = wp_get_attachment_caption( $id );
		$out[]   = array(
			'id'      => $id,
			'url'     => (string) $url,
			'alt'     => is_string( $alt ) ? isha_home_data_plain( $alt ) : '',
			'caption' => is_string( $caption ) ? isha_home_data_plain( $caption ) : '',
		);
	}
	return $out;
}

/**
 * Dữ liệu dựng sẵn cho template-parts/service-single.php.
 *
 * @param int $post_id ID liệu trình.
 * @return array {
 *     @type int    $id         ID bài.
 *     @type string $title      Tên liệu trình (văn bản thuần).
 *     @type string $short_desc Mô tả ngắn (văn bản thuần).
 *     @type string $duration   Thời gian liệu trình (văn bản thuần).
 *     @type array  $steps      Các bước (chuỗi thuần).
 *     @type array  $gallery    Kết quả của isha_service_single_gallery().
 *     @type array  $group      Kết quả của isha_service_single_group().
 *     @type int    $image_id   Ảnh khung mở đầu (isha_service_single_image_id()).
 *     @type string $book_url   isha_booking_url() kèm ?service=<ID>.
 *     @type string $zalo_url   URL Zalo hợp lệ hoặc ''.
 *     @type string $tel_url    tel: của hotline hoặc ''.
 *     @type string $phone      Hotline hiển thị có khoảng cách (0947 683 768) hoặc ''.
 *     @type string $services_url Trang "Dịch vụ" (đầu breadcrumb) hoặc ''.
 * }
 */
function isha_service_single_data( $post_id ) {
	$post_id = (int) $post_id;
	$fields  = class_exists( 'ISHA_Core_Service_Fields' )
		? ISHA_Core_Service_Fields::get( $post_id )
		: array(
			'duration'   => '',
			'short_desc' => '',
			'gallery'    => array(),
			'steps'      => array(),
		);

	// Liệu trình đặt mật khẩu: chỉ còn tên, nhóm, ảnh đại diện và (ở template) biểu mẫu mật khẩu; mô tả, thời gian, các bước và ảnh liệu trình bị giữ lại.
	if ( post_password_required( $post_id ) ) {
		$fields = array(
			'duration'   => '',
			'short_desc' => '',
			'gallery'    => array(),
			'steps'      => array(),
		);
	}

	$group   = isha_service_single_group( $post_id );
	$contact = function_exists( 'isha_home_contact' ) ? isha_home_contact() : array();
	$phone   = function_exists( 'isha_footer_phone' ) ? isha_footer_phone() : array();

	// Trang "Dịch vụ" (slug dich-vu; CPT không bật archive).
	$services_page = get_page_by_path( 'dich-vu' );
	$services_url  = $services_page ? (string) get_permalink( $services_page ) : home_url( '/dich-vu/' );

	return array(
		'id'         => $post_id,
		'title'      => isha_home_data_plain( get_the_title( $post_id ) ),
		'short_desc' => isha_home_data_plain( isset( $fields['short_desc'] ) ? $fields['short_desc'] : '' ),
		'duration'   => isha_home_data_plain( isset( $fields['duration'] ) ? $fields['duration'] : '' ),
		'steps'      => isset( $fields['steps'] ) && is_array( $fields['steps'] ) ? array_values( array_filter( array_map( 'isha_home_data_plain', $fields['steps'] ), 'strlen' ) ) : array(),
		'gallery'    => isha_service_single_gallery( isset( $fields['gallery'] ) && is_array( $fields['gallery'] ) ? $fields['gallery'] : array() ),
		'group'      => $group,
		'image_id'   => isha_service_single_image_id( $post_id, $group ),
		'book_url'   => add_query_arg( 'service', $post_id, isha_booking_url() ),
		'zalo_url'   => isset( $contact['zalo_url'] ) ? (string) $contact['zalo_url'] : '',
		'tel_url'    => isset( $contact['tel_url'] ) ? (string) $contact['tel_url'] : '',
		'phone'      => isset( $phone['text'] ) ? (string) $phone['text'] : '',
		'services_url' => $services_url,
	);
}

/**
 * Nạp tài nguyên của trang chi tiết liệu trình: tài nguyên trang chủ (hệ thiết kế, lõi chuyển động, hàng vuốt, cảnh kết) rồi
 * service-single.css và service-single.js. Gọi sớm (wp_enqueue_scripts) để CSS nằm trong <head>, không in muộn ở chân trang.
 */
function isha_service_single_assets() {
	if ( ! is_singular( 'isha_service' ) ) {
		return;
	}
	if ( function_exists( 'isha_home_enqueue_assets' ) ) {
		isha_home_enqueue_assets();
	}

	$css_path = ISHA_DIR . '/assets/css/service-single.css';
	$js_path  = ISHA_DIR . '/assets/js/service-single.js';

	if ( is_file( $css_path ) ) {
		wp_enqueue_style(
			'isha-service-single',
			ISHA_URI . '/assets/css/service-single.css',
			wp_style_is( 'isha-home', 'enqueued' ) ? array( 'isha-main', 'isha-home' ) : array( 'isha-main' ),
			(string) filemtime( $css_path )
		);
	}
	if ( is_file( $js_path ) ) {
		$deps = array();
		foreach ( array( 'isha-motion', 'isha-home-js' ) as $handle ) {
			if ( wp_script_is( $handle, 'enqueued' ) ) {
				$deps[] = $handle;
			}
		}
		wp_enqueue_script(
			'isha-service-single',
			ISHA_URI . '/assets/js/service-single.js',
			$deps,
			(string) filemtime( $js_path ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'isha_service_single_assets', 20 );
