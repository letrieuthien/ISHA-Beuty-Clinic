<?php
/**
 * Hero của trang danh sách dịch vụ (templates/template-services.php): dữ liệu dựng sẵn và nạp tài nguyên.
 *
 * Nội dung: tiêu đề là tên trang; mô tả, ảnh và video nền đọc qua ISHA_Core_Page_Hero_Fields::get() (plugin ISHA Core).
 * Nan quạt: các nhóm dịch vụ (term isha_service_cat) theo ISHA_Core_Term_Fields::ordered_terms(), thumbnail là ảnh bìa
 * của nhóm. Số thẻ luôn trong khoảng ISHA_SH_CARDS_MIN..ISHA_SH_CARDS_MAX: thiếu thì lặp lại (isha_services_hero_slots()).
 * Markup ở template-parts/services-hero.php, giao diện ở assets/css/services-hero.css, chuyển động ở assets/js/services-hero.js.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ISHA_SH_TEMPLATE' ) ) {
	define( 'ISHA_SH_TEMPLATE', 'templates/template-services.php' );
}
if ( ! defined( 'ISHA_SH_CARDS_MIN' ) ) {
	define( 'ISHA_SH_CARDS_MIN', 12 );
}
if ( ! defined( 'ISHA_SH_CARDS_MAX' ) ) {
	define( 'ISHA_SH_CARDS_MAX', 16 );
}

/**
 * Thứ tự thẻ trên bánh xe: mảng chỉ số (0..$count-1) dài trong khoảng MIN..MAX.
 *
 * Đủ từ MIN thẻ thì giữ nguyên (cắt ở MAX). Thiếu thì lặp vòng theo thứ tự: ưu tiên tổng là bội số của $count trong khoảng
 * MIN..MAX (mỗi nhóm xuất hiện số lần bằng nhau và cách đều quanh vòng: 6 nhóm → 12 thẻ, mỗi nhóm hai thẻ đối xứng nhau qua
 * tâm, nên nửa vòng nhìn thấy luôn có đủ 6 nhóm). Không có bội số thì lấy tổng nhỏ nhất mà thẻ cuối khác thẻ đầu (bánh xe
 * khép vòng nên hai thẻ này nằm cạnh nhau).
 *
 * @param int $count Số nhóm dịch vụ thật.
 * @return int[] Chỉ số nhóm theo vị trí trên vòng; rỗng nếu $count < 1.
 */
function isha_services_hero_slots( $count ) {
	$count = max( 0, (int) $count );
	if ( $count < 1 ) {
		return array();
	}
	if ( $count >= ISHA_SH_CARDS_MIN ) {
		return range( 0, min( $count, ISHA_SH_CARDS_MAX ) - 1 );
	}

	$total = 0;
	for ( $n = ISHA_SH_CARDS_MIN; $n <= ISHA_SH_CARDS_MAX; $n++ ) {
		if ( 0 === $n % $count ) {
			$total = $n;
			break;
		}
	}
	if ( ! $total ) {
		for ( $n = ISHA_SH_CARDS_MIN; $n <= ISHA_SH_CARDS_MAX; $n++ ) {
			if ( 1 !== $n % $count ) {
				$total = $n;
				break;
			}
		}
	}
	if ( ! $total ) {
		$total = ISHA_SH_CARDS_MIN;
	}

	$slots = array();
	for ( $i = 0; $i < $total; $i++ ) {
		$slots[] = $i % $count;
	}
	return $slots;
}

/**
 * Các nhóm dịch vụ cho nan quạt, theo thứ tự hiển thị.
 *
 * @return array[] Mỗi phần tử: array( 'id' => int, 'name' => string, 'url' => string, 'thumb' => int ID ảnh bìa hoặc 0 ).
 */
function isha_services_hero_groups() {
	if ( ! taxonomy_exists( 'isha_service_cat' ) ) {
		return array();
	}

	$has_order = class_exists( 'ISHA_Core_Term_Fields' ) && is_callable( array( 'ISHA_Core_Term_Fields', 'ordered_terms' ) );
	$terms     = $has_order
		? ISHA_Core_Term_Fields::ordered_terms( array( 'hide_empty' => false ) )
		: get_terms(
			array(
				'taxonomy'   => 'isha_service_cat',
				'hide_empty' => false,
			)
		);
	if ( ! is_array( $terms ) ) {
		return array();
	}

	$groups = array();
	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		$url   = get_term_link( $term );
		$thumb = 0;
		if ( $has_order && is_callable( array( 'ISHA_Core_Term_Fields', 'get' ) ) ) {
			$fields = ISHA_Core_Term_Fields::get( (int) $term->term_id );
			$thumb  = isset( $fields['cover_id'] ) ? (int) $fields['cover_id'] : 0;
		}
		$groups[] = array(
			'id'    => (int) $term->term_id,
			'name'  => trim( wp_strip_all_tags( html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ) ) ),
			'url'   => is_wp_error( $url ) ? '' : (string) $url,
			'thumb' => $thumb > 0 && wp_attachment_is_image( $thumb ) ? $thumb : 0,
		);
	}
	return $groups;
}

/**
 * Trường hero thô (ID media, mô tả) của trang dùng template Dịch vụ; 0 / '' khi thiếu plugin hoặc trang.
 *
 * @param int $post_id ID trang.
 * @return array{image_desktop:int,image_mobile:int,video_desktop:int,video_mobile:int,description:string}
 */
function isha_services_hero_page_fields( $post_id ) {
	$fields = array(
		'image_desktop' => 0,
		'image_mobile'  => 0,
		'video_desktop' => 0,
		'video_mobile'  => 0,
		'description'   => '',
	);
	if ( $post_id && class_exists( 'ISHA_Core_Page_Hero_Fields' ) ) {
		$fields = array_merge( $fields, ISHA_Core_Page_Hero_Fields::get( (int) $post_id ) );
	}
	return $fields;
}

/**
 * Trường hero của một nhóm dịch vụ, đã trộn nguồn dự phòng theo thứ tự cho từng ô:
 *   ảnh rộng:     hero rộng của nhóm → hero rộng của trang Dịch vụ → ảnh bìa nhóm (4:5, bị cắt nhiều nên để cuối)
 *   ảnh điện thoại: hero điện thoại của nhóm → ảnh bìa nhóm (vốn là khung dọc) → hero điện thoại của trang Dịch vụ
 *   video rộng:   video rộng của nhóm → video rộng của trang Dịch vụ
 *   video điện thoại: video điện thoại của nhóm → video bìa nhóm → video điện thoại của trang Dịch vụ
 * Mô tả là mô tả (description) của nhóm.
 *
 * @param WP_Term $term Nhóm dịch vụ.
 * @return array Cùng khoá với isha_services_hero_page_fields().
 */
function isha_services_hero_term_fields( WP_Term $term ) {
	$own = array(
		'image_desktop' => 0,
		'image_mobile'  => 0,
		'video_desktop' => 0,
		'video_mobile'  => 0,
	);
	if ( class_exists( 'ISHA_Core_Page_Hero_Fields' ) && is_callable( array( 'ISHA_Core_Page_Hero_Fields', 'get_term' ) ) ) {
		$own = array_merge( $own, ISHA_Core_Page_Hero_Fields::get_term( (int) $term->term_id ) );
	}
	$cover = array(
		'cover_id'       => 0,
		'cover_video_id' => 0,
	);
	if ( class_exists( 'ISHA_Core_Term_Fields' ) && is_callable( array( 'ISHA_Core_Term_Fields', 'get' ) ) ) {
		$cover = array_merge( $cover, ISHA_Core_Term_Fields::get( (int) $term->term_id ) );
	}
	$page = isha_services_hero_page_fields( (int) isha_services_page_id( ISHA_SH_TEMPLATE ) );

	$first = static function () {
		foreach ( func_get_args() as $id ) {
			if ( (int) $id > 0 ) {
				return (int) $id;
			}
		}
		return 0;
	};

	return array(
		'image_desktop' => $first( $own['image_desktop'], $page['image_desktop'], $cover['cover_id'] ),
		'image_mobile'  => $first( $own['image_mobile'], $cover['cover_id'], $page['image_mobile'] ),
		'video_desktop' => $first( $own['video_desktop'], $page['video_desktop'] ),
		'video_mobile'  => $first( $own['video_mobile'], $cover['cover_video_id'], $page['video_mobile'] ),
		'description'   => trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( term_description( $term ) ) ) ),
	);
}

/**
 * ID trang đã xuất bản đầu tiên dùng một template (0 nếu không có). Nhớ kết quả trong request.
 *
 * @param string $template Đường dẫn template, ví dụ templates/template-services.php.
 * @return int
 */
function isha_services_page_id( $template ) {
	static $cache = array();
	if ( ! isset( $cache[ $template ] ) ) {
		$ids                = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $template, // phpcs:ignore WordPress.DB.SlowDBQuery
				'no_found_rows'  => true,
			)
		);
		$cache[ $template ] = $ids ? (int) $ids[0] : 0;
	}
	return $cache[ $template ];
}

/**
 * Dữ liệu dựng sẵn cho template-parts/services-hero.php, cho trang Dịch vụ (WP_Post) hoặc một nhóm dịch vụ (WP_Term).
 *
 * Ảnh và video: thiếu bản điện thoại thì dùng bản màn hình rộng và ngược lại. Ở trang nhóm, `current` là ID nhóm đang xem
 * (thẻ của nhóm được đánh dấu và bánh xe xoay thẻ đó lên đỉnh).
 *
 * @param WP_Post|WP_Term|int $context Trang, nhóm dịch vụ, hoặc ID trang.
 * @return array{title:string,description:string,image_desktop:int,image_mobile:int,video_desktop:string,video_mobile:string,groups:array,slots:int[],current:int}
 */
function isha_services_hero_data( $context ) {
	$current = 0;
	if ( $context instanceof WP_Term ) {
		$fields  = isha_services_hero_term_fields( $context );
		$title   = $context->name;
		$current = (int) $context->term_id;
	} else {
		$post_id = $context instanceof WP_Post ? (int) $context->ID : (int) $context;
		$fields  = isha_services_hero_page_fields( $post_id );
		$title   = get_the_title( $post_id );
	}

	$img_d = (int) $fields['image_desktop'];
	$img_m = (int) $fields['image_mobile'];
	$vid_d = $fields['video_desktop'] ? (string) wp_get_attachment_url( (int) $fields['video_desktop'] ) : '';
	$vid_m = $fields['video_mobile'] ? (string) wp_get_attachment_url( (int) $fields['video_mobile'] ) : '';

	$groups = isha_services_hero_groups();

	return array(
		'title'         => trim( wp_strip_all_tags( html_entity_decode( (string) $title, ENT_QUOTES, 'UTF-8' ) ) ),
		'description'   => (string) $fields['description'],
		'image_desktop' => $img_d ? $img_d : $img_m,
		'image_mobile'  => $img_m ? $img_m : $img_d,
		'video_desktop' => '' !== $vid_d ? $vid_d : $vid_m,
		'video_mobile'  => '' !== $vid_m ? $vid_m : $vid_d,
		'groups'        => $groups,
		'slots'         => isha_services_hero_slots( count( $groups ) ),
		'current'       => $current,
	);
}

/**
 * Trang đang xem có dùng giao diện danh sách dịch vụ không (template Dịch vụ hoặc trang nhóm dịch vụ).
 *
 * @return bool
 */
function isha_services_listing_active() {
	return is_page_template( ISHA_SH_TEMPLATE ) || is_tax( 'isha_service_cat' );
}

/**
 * Nạp services-hero.css (trong <head>) và services-hero.js (defer, không phụ thuộc thư viện) ở trang danh sách dịch vụ.
 */
function isha_services_hero_assets() {
	if ( ! isha_services_listing_active() ) {
		return;
	}
	$css = ISHA_DIR . '/assets/css/services-hero.css';
	$js  = ISHA_DIR . '/assets/js/services-hero.js';

	if ( is_file( $css ) ) {
		wp_enqueue_style( 'isha-services-hero', ISHA_URI . '/assets/css/services-hero.css', array( 'isha-main' ), (string) filemtime( $css ) );
	}
	if ( is_file( $js ) ) {
		wp_enqueue_script(
			'isha-services-hero',
			ISHA_URI . '/assets/js/services-hero.js',
			array(),
			(string) filemtime( $js ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'isha_services_hero_assets', 20 );

/**
 * In hero (giữ cho nơi gọi cũ). Trang danh sách dùng isha_services_listing().
 *
 * @param WP_Post|WP_Term|int $context Trang, nhóm dịch vụ hoặc ID trang (mặc định trang hiện tại).
 */
function isha_services_hero( $context = 0 ) {
	if ( ! $context ) {
		$context = (int) get_the_ID();
	}
	get_template_part( 'template-parts/services-hero', null, isha_services_hero_data( $context ) );
}

/**
 * In toàn bộ trang danh sách dịch vụ (hero + danh sách liệu trình + CTA) bằng template dùng chung
 * template-parts/services-listing.php. Dùng ở templates/template-services.php (WP_Post) và taxonomy-isha_service_cat.php (WP_Term).
 *
 * @param WP_Post|WP_Term $context Trang Dịch vụ hoặc nhóm dịch vụ đang xem.
 */
function isha_services_listing( $context ) {
	if ( ! $context instanceof WP_Post && ! $context instanceof WP_Term ) {
		return;
	}
	get_template_part(
		'template-parts/services-listing',
		null,
		array(
			'context' => $context,
			'hero'    => isha_services_hero_data( $context ),
		)
	);
}

/**
 * Menu: ở trang nhóm dịch vụ và trang chi tiết liệu trình, mục menu trỏ tới trang Dịch vụ (template Dịch vụ) được đánh dấu là
 * mục cha của trang đang xem (`current-menu-ancestor`, `current-menu-parent`) để được tô sáng như khi đang ở trang Dịch vụ.
 * Nhận cả mục kiểu "Trang" (so ID) lẫn mục "Liên kết tự tạo" (so URL).
 *
 * @param string[] $classes Class của mục menu.
 * @param WP_Post  $item    Mục menu.
 * @return string[]
 */
function isha_services_menu_ancestor( $classes, $item ) {
	if ( ! is_tax( 'isha_service_cat' ) && ! is_singular( 'isha_service' ) ) {
		return $classes;
	}
	$page_id = isha_services_page_id( ISHA_SH_TEMPLATE );
	if ( ! $page_id ) {
		return $classes;
	}

	$match = ( 'post_type' === $item->type && 'page' === $item->object && $page_id === (int) $item->object_id )
		|| ( 'custom' === $item->type && untrailingslashit( (string) $item->url ) === untrailingslashit( (string) get_permalink( $page_id ) ) );

	if ( $match ) {
		$classes[] = 'current-menu-ancestor';
		$classes[] = 'current-menu-parent';
	}
	return array_values( array_unique( $classes ) );
}
add_filter( 'nav_menu_css_class', 'isha_services_menu_ancestor', 10, 2 );
