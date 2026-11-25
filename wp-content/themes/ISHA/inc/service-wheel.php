<?php
/**
 * Shortcode [isha_service_wheel]: vòng xoay liệu trình ("Bánh xe tĩnh lặng", .isha-ring) của section Dịch vụ tiêu biểu ở trang chủ, tách ra để
 * nhúng ở trang khác cho MỘT nhóm dịch vụ (taxonomy isha_service_cat).
 *
 *   [isha_service_wheel group="12"]                       vòng của nhóm có term ID 12
 *   [isha_service_wheel group="12" exclude="153"]         bỏ liệu trình có post ID 153 (ví dụ bài đang xem)
 *   [isha_service_wheel group="12" title="..." lead="..." all="1"]
 *
 * Thuộc tính:
 * - group (hoặc taxonomy_id): term ID của nhóm dịch vụ. Bỏ trống ở trang chi tiết liệu trình thì lấy nhóm chính của bài đang xem.
 * - exclude: post ID liệu trình bị bỏ khỏi vòng. Bỏ trống ở trang chi tiết liệu trình thì bỏ chính bài đang xem.
 * - title: tiêu đề section (mặc định "Liệu trình khác trong nhóm" khi có bỏ bài, không thì "Liệu trình trong nhóm").
 * - lead: câu dẫn dưới tiêu đề (mặc định không có).
 * - all: 1 thì thêm nút "Xem tất cả dịch vụ" (trang Dịch vụ) cạnh "Khám phá nhóm"; mặc định 0.
 *
 * Dùng lại nguyên template-parts/home/services.php (cùng markup, CSS khối services của home.css, card-ring.css và module
 * assets/js/home-wheel/main.js với trang chủ), chỉ khác dữ liệu: một nhóm, không có tab. Không có liệu trình nào (nhóm sai, rỗng, hoặc chỉ còn
 * bài bị bỏ) thì trả chuỗi rỗng. Giới hạn: một vòng mỗi trang (id `services` của section và `[data-isha-wheel]` của main.js là duy nhất).
 * Hợp đồng ở docs/home-architecture.md, mục 12.10.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dữ liệu cho template-parts/home/services.php từ MỘT nhóm dịch vụ (cùng cấu trúc isha_home_data( 'services' ) với một phần tử groups).
 *
 * @param int   $term_id ID term isha_service_cat.
 * @param array $args    Tuỳ chọn: exclude (int post ID bỏ khỏi vòng), title, lead, all (bool: kèm nút "Xem tất cả dịch vụ").
 * @return array array() nếu thiếu CPT/taxonomy, nhóm không tồn tại hoặc không còn liệu trình nào.
 */
function isha_service_wheel_data( $term_id, array $args = array() ) {
	if ( ! post_type_exists( 'isha_service' ) || ! taxonomy_exists( 'isha_service_cat' ) || ! function_exists( 'isha_home_data_service_items' ) ) {
		return array();
	}

	$term = get_term( (int) $term_id, 'isha_service_cat' );
	if ( ! $term instanceof WP_Term ) {
		return array();
	}

	$by_term = isha_home_data_service_items( array( (int) $term->term_id ) );
	$items   = isset( $by_term[ $term->term_id ] ) && is_array( $by_term[ $term->term_id ] ) ? $by_term[ $term->term_id ] : array();

	$exclude = isset( $args['exclude'] ) ? (int) $args['exclude'] : 0;
	if ( $exclude > 0 ) {
		$items = array_values(
			array_filter(
				$items,
				static function ( $item ) use ( $exclude ) {
					return ! ( is_array( $item ) && isset( $item['id'] ) && (int) $item['id'] === $exclude );
				}
			)
		);
	}
	if ( ! $items ) {
		return array();
	}

	$link  = get_term_link( $term );
	$title = isset( $args['title'] ) && is_scalar( $args['title'] ) ? trim( (string) $args['title'] ) : '';
	if ( '' === $title ) {
		$title = $exclude > 0 ? __( 'Liệu trình khác trong nhóm', 'isha' ) : __( 'Liệu trình trong nhóm', 'isha' );
	}

	return array(
		'section'    => 'services',
		'title'      => $title,
		'lead'       => isset( $args['lead'] ) && is_scalar( $args['lead'] ) ? trim( (string) $args['lead'] ) : '',
		'link_label' => '',
		'all_url'    => ! empty( $args['all'] ) && function_exists( 'isha_home_data_services_url' ) ? isha_home_data_services_url() : '',
		'groups'     => array(
			array(
				'id'      => (int) $term->term_id,
				'slug'    => (string) $term->slug,
				'name'    => isha_home_data_plain( $term->name ),
				'tagline' => isha_home_data_plain( $term->description ),
				'url'     => is_wp_error( $link ) ? '' : (string) $link,
				'items'   => $items,
			),
		),
	);
}

/**
 * Dựng HTML vòng xoay của một nhóm (xem đầu tệp về thuộc tính). Dùng làm hàm của shortcode và gọi thẳng được từ template.
 *
 * Lỗi bên trong chỉ ẩn riêng khối này khi WP_DEBUG tắt (như shortcode của trang chủ); bật WP_DEBUG thì ném lên để thấy.
 *
 * @param array|string $atts Thuộc tính shortcode.
 * @return string HTML đã bọc `div.isha-home.isha-home--embed` (CSS và JS trang chủ được nạp), hoặc ''.
 */
function isha_service_wheel( $atts = array() ) {
	try {
		return isha_service_wheel_render( $atts );
	} catch ( Throwable $error ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			throw $error;
		}
		error_log( 'ISHA service wheel: ' . $error->getMessage() . ' (' . basename( $error->getFile() ) . ':' . $error->getLine() . ')' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- nhật ký lỗi có chủ đích.
		return '';
	}
}

/**
 * Phần dựng của isha_service_wheel() (tách ra để hàm ngoài bắt lỗi).
 *
 * @param array|string $atts Thuộc tính shortcode.
 * @return string
 */
function isha_service_wheel_render( $atts ) {
	$atts = shortcode_atts(
		array(
			'group'       => '',
			'taxonomy_id' => '',
			'exclude'     => '',
			'title'       => '',
			'lead'        => '',
			'all'         => '0',
		),
		is_array( $atts ) ? $atts : array(),
		'isha_service_wheel'
	);

	$term_id = is_numeric( $atts['group'] ) ? (int) $atts['group'] : ( is_numeric( $atts['taxonomy_id'] ) ? (int) $atts['taxonomy_id'] : 0 );
	$exclude = is_numeric( $atts['exclude'] ) ? (int) $atts['exclude'] : 0;

	// Ở trang chi tiết liệu trình: bỏ trống thì dùng nhóm chính và chính bài đang xem.
	if ( is_singular( 'isha_service' ) ) {
		$post_id = (int) get_queried_object_id();
		if ( $term_id < 1 && function_exists( 'isha_service_single_group' ) ) {
			$group   = isha_service_single_group( $post_id );
			$term_id = isset( $group['id'] ) ? (int) $group['id'] : 0;
		}
		if ( '' === trim( (string) $atts['exclude'] ) ) {
			$exclude = $post_id;
		}
	}
	if ( $term_id < 1 ) {
		return '';
	}

	$data = isha_service_wheel_data(
		$term_id,
		array(
			'exclude' => $exclude,
			'title'   => sanitize_text_field( (string) $atts['title'] ),
			'lead'    => sanitize_text_field( (string) $atts['lead'] ),
			'all'     => in_array( strtolower( trim( (string) $atts['all'] ) ), array( '1', 'true', 'yes' ), true ),
		)
	);
	if ( ! $data ) {
		return '';
	}

	ob_start();
	try {
		get_template_part( 'template-parts/home/services', null, $data );
	} finally {
		$html = (string) ob_get_clean();
	}
	if ( '' === trim( $html ) ) {
		return '';
	}

	// Ngoài phim của trang chủ: bọc để CSS dưới `.isha-home` áp dụng và nạp tài nguyên trang chủ (card-ring.css, home.css, module home-wheel).
	if ( ! isha_home_in_film() ) {
		isha_home_enqueue_assets();
		$html = '<div class="isha-home isha-home--embed">' . $html . '</div>';
	}
	return $html;
}

/**
 * Đăng ký shortcode.
 */
function isha_service_wheel_register() {
	add_shortcode( 'isha_service_wheel', 'isha_service_wheel' );
}
add_action( 'init', 'isha_service_wheel_register' );
