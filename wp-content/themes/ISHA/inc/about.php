<?php
/**
 * Trang Giới thiệu (templates/template-about.php): dữ liệu dựng sẵn và nạp tài nguyên. Thêm ngày 17/11/2026 (docs/home-architecture.md mục 16).
 *
 * Trang dựng trên hệ thiết kế của trang chủ, như trang chi tiết liệu trình: bao trong `div.isha-home.isha-about` và nạp home.css,
 * isha-motion.js, home.js, nên thừa hưởng tông màu (data-tone), chữ, ảnh và token vàng kim loại. Cấu trúc: hero tông night (tiêu đề trang,
 * mô tả và ảnh/video nền từ meta box "Hero banner" dùng chung, mục lục), ba section theo phong cách "Câu chuyện nhà sáng lập" của trang
 * chủ (nội dung từ ISHA_Core_About_Fields), rồi cảnh kết [isha_home_cta]. Phần riêng: assets/css/about.css, assets/js/about.js.
 *
 * Mọi giá trị trả về là văn bản thô hoặc HTML đã lọc (nội dung section); template tự escape.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cấu hình cố định của ba section theo thứ tự trên trang: id neo (không dấu, dùng cho mục lục và menu), tông nền, tiêu đề mặc định.
 * Tông sáng dần ấm theo nhịp một ngày: day, day-warm, afternoon (như trang chủ).
 *
 * @return array<string,array{anchor:string,tone:string,title:string}>
 */
function isha_about_section_defs() {
	return array(
		'story'      => array(
			'anchor' => 'cau-chuyen-thuong-hieu',
			'tone'   => 'day',
			'title'  => __( 'Câu chuyện thương hiệu', 'isha' ),
		),
		'philosophy' => array(
			'anchor' => 'triet-ly-va-su-menh',
			'tone'   => 'day-warm',
			'title'  => __( 'Triết lý và sứ mệnh', 'isha' ),
		),
		'values'     => array(
			'anchor' => 'gia-tri-cot-loi',
			'tone'   => 'afternoon',
			'title'  => __( 'Giá trị cốt lõi', 'isha' ),
		),
	);
}

/**
 * Trang đang xem có phải trang dùng mẫu "Giới thiệu" không.
 *
 * @return bool
 */
function isha_about_is_active() {
	return is_page_template( 'templates/template-about.php' );
}

/**
 * Dữ liệu dựng sẵn của trang Giới thiệu.
 *
 * - hero: title (tên trang), description (mô tả ở "Hero banner"; trống thì câu mặc định), image_desktop, image_mobile (ID ảnh; thiếu một
 *   bản thì dùng bản kia), video_desktop, video_mobile (URL; thiếu một bản thì dùng bản kia).
 * - sections: các section đang bật và có ít nhất tiêu đề nhỏ, nội dung hoặc ảnh, theo thứ tự: id, anchor, tone, number (1, 2, 3 theo thứ
 *   tự hiện), title (trống thì tên section), subtitle, content (HTML đã lọc và qua wpautop), image (ID ảnh, 0 = không có).
 *
 * @param int $post_id ID trang Giới thiệu.
 * @return array{hero:array,sections:array}
 */
function isha_about_data( $post_id ) {
	$post_id = absint( $post_id );

	$fields = class_exists( 'ISHA_Core_Page_Hero_Fields' ) ? ISHA_Core_Page_Hero_Fields::get( $post_id ) : array();
	$fields = is_array( $fields ) ? $fields : array();
	$pick   = static function ( $key ) use ( $fields ) {
		return isset( $fields[ $key ] ) && is_numeric( $fields[ $key ] ) ? (int) $fields[ $key ] : 0;
	};

	$image_desktop = $pick( 'image_desktop' );
	$image_mobile  = $pick( 'image_mobile' );
	$video_desktop = isha_home_video_url( $pick( 'video_desktop' ) );
	$video_mobile  = isha_home_video_url( $pick( 'video_mobile' ) );

	$title       = isha_home_data_plain( get_the_title( $post_id ) );
	$description = isset( $fields['description'] ) && is_string( $fields['description'] ) ? trim( $fields['description'] ) : '';

	$hero = array(
		'title'         => '' !== $title ? $title : __( 'Giới thiệu', 'isha' ),
		'description'   => '' !== $description ? $description : __( 'Tôn vinh nét đẹp tự nhiên theo tiêu chuẩn thẩm mỹ cân bằng, toàn diện và bền vững theo thời gian.', 'isha' ),
		'image_desktop' => $image_desktop ? $image_desktop : $image_mobile,
		'image_mobile'  => $image_mobile ? $image_mobile : $image_desktop,
		'video_desktop' => '' !== $video_desktop ? $video_desktop : $video_mobile,
		'video_mobile'  => '' !== $video_mobile ? $video_mobile : $video_desktop,
	);

	$sections = array();
	foreach ( isha_about_section_defs() as $id => $def ) {
		$stored = class_exists( 'ISHA_Core_About_Fields' ) ? ISHA_Core_About_Fields::get( $id, $post_id ) : array();
		if ( isset( $stored['enabled'] ) && false === $stored['enabled'] ) {
			continue;
		}
		$subtitle = isset( $stored['subtitle'] ) ? isha_home_data_plain( $stored['subtitle'] ) : '';
		$content  = isset( $stored['content'] ) && is_string( $stored['content'] ) ? trim( $stored['content'] ) : '';
		$image    = isset( $stored['image'] ) ? isha_home_data_attachment( $stored['image'] ) : 0;
		if ( $image && ! wp_attachment_is_image( $image ) ) {
			$image = 0;
		}
		if ( '' === $subtitle && '' === $content && ! $image ) {
			continue;
		}
		$title = isset( $stored['title'] ) ? isha_home_data_plain( $stored['title'] ) : '';

		$sections[] = array(
			'id'       => $id,
			'anchor'   => $def['anchor'],
			'tone'     => $def['tone'],
			'number'   => count( $sections ) + 1,
			'title'    => '' !== $title ? $title : $def['title'],
			'subtitle' => $subtitle,
			'content'  => '' !== $content ? wpautop( $content ) : '',
			'image'    => $image,
		);
	}

	return array(
		'hero'     => $hero,
		'sections' => $sections,
	);
}

/**
 * Nạp tài nguyên trang Giới thiệu (chỉ trang dùng mẫu "Giới thiệu"): tài nguyên trang chủ (home.css, isha-motion.js, home.js), rồi
 * about.css (phụ thuộc isha-main, isha-home) và about.js (phụ thuộc isha-motion, defer, chân trang). CSS nằm trong <head>.
 */
function isha_about_assets() {
	if ( ! isha_about_is_active() ) {
		return;
	}
	if ( function_exists( 'isha_home_enqueue_assets' ) ) {
		isha_home_enqueue_assets();
	}

	$css_path = ISHA_DIR . '/assets/css/about.css';
	$js_path  = ISHA_DIR . '/assets/js/about.js';

	if ( is_file( $css_path ) ) {
		wp_enqueue_style(
			'isha-about',
			ISHA_URI . '/assets/css/about.css',
			wp_style_is( 'isha-home', 'enqueued' ) ? array( 'isha-main', 'isha-home' ) : array( 'isha-main' ),
			(string) filemtime( $css_path )
		);
	}
	if ( is_file( $js_path ) ) {
		wp_enqueue_script(
			'isha-about',
			ISHA_URI . '/assets/js/about.js',
			wp_script_is( 'isha-motion', 'enqueued' ) ? array( 'isha-motion' ) : array(),
			(string) filemtime( $js_path ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'isha_about_assets', 20 );
