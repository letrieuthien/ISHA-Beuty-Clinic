<?php
/**
 * Không gian ISHA (18/11/2026, docs/home-architecture.md mục 18): trang danh sách (Trang dùng mẫu templates/template-space.php) và trang
 * chi tiết bài (single-isha_space.php). Dữ liệu dựng sẵn và nạp tài nguyên.
 *
 * Hai trang dùng phong cách của trang Giới thiệu (hero tông night, section "Câu chuyện nhà sáng lập"): bao trong
 * `div.isha-home.isha-about.isha-space-page`, markup chung ở template-parts/about/hero.php và section.php, nạp about.css và about.js qua
 * isha_about_enqueue(); phần riêng (lưới ảnh, thẻ không gian khác) ở assets/css/space.css. Bài lấy từ CPT isha_space (plugin ISHA Core,
 * ISHA_Core_Space_Fields: tiêu đề nhỏ, mô tả ngắn, ảnh, ô "Xuất hiện ở trang chủ"); thứ tự là menu_order.
 *
 * Mọi giá trị trả về là văn bản thô (template tự escape), trừ nội dung bài đã qua bộ lọc the_content.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mẫu trang danh sách.
 */
const ISHA_SPACE_TEMPLATE = 'templates/template-space.php';

/**
 * Tông nền của các section trên trang danh sách, lặp lại theo thứ tự (sáng dần ấm như trang Giới thiệu).
 *
 * @return string[]
 */
function isha_space_tones() {
	return array( 'day', 'day-warm', 'afternoon' );
}

/**
 * Trang đang xem có phải trang danh sách hoặc bài Không gian không.
 *
 * @return bool
 */
function isha_space_is_active() {
	return is_page_template( ISHA_SPACE_TEMPLATE ) || is_singular( 'isha_space' );
}

/**
 * Trang danh sách đã xuất bản: Trang đầu tiên dùng mẫu "Không gian ISHA", không có thì Trang slug khong-gian-isha.
 *
 * @return WP_Post|null
 */
function isha_space_list_page() {
	static $memo = false;
	if ( false !== $memo ) {
		return $memo;
	}
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery -- một truy vấn, nhớ trong request.
			'meta_value'     => ISHA_SPACE_TEMPLATE, // phpcs:ignore WordPress.DB.SlowDBQuery -- như trên.
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	if ( $pages ) {
		$memo = $pages[0];
		return $memo;
	}
	$page = get_page_by_path( 'khong-gian-isha', OBJECT, 'page' );
	$memo = ( $page instanceof WP_Post && 'publish' === $page->post_status ) ? $page : null;
	return $memo;
}

/**
 * Đường dẫn trang danh sách ('' nếu chưa có trang).
 *
 * @return string
 */
function isha_space_list_url() {
	$page = isha_space_list_page();
	return $page ? (string) get_permalink( $page ) : '';
}

/**
 * Trường của một bài (rỗng khi plugin chưa bật).
 *
 * @param int $post_id ID bài.
 * @return array{home:bool,subtitle:string,summary:string,gallery:int[]}
 */
function isha_space_fields( $post_id ) {
	$fields = class_exists( 'ISHA_Core_Space_Fields' ) ? ISHA_Core_Space_Fields::get( $post_id ) : array();
	return array(
		'home'     => ! empty( $fields['home'] ),
		'subtitle' => isset( $fields['subtitle'] ) ? (string) $fields['subtitle'] : '',
		'summary'  => isset( $fields['summary'] ) ? (string) $fields['summary'] : '',
		'gallery'  => isset( $fields['gallery'] ) && is_array( $fields['gallery'] ) ? array_map( 'intval', $fields['gallery'] ) : array(),
	);
}

/**
 * Bài đã xuất bản theo thứ tự hiển thị.
 *
 * @param bool $home_only Chỉ bài được chọn "Xuất hiện ở trang chủ".
 * @return WP_Post[]
 */
function isha_space_posts( $home_only = false ) {
	if ( class_exists( 'ISHA_Core_Space_Fields' ) ) {
		return ISHA_Core_Space_Fields::posts( $home_only );
	}
	return array();
}

/**
 * Ảnh đại diện của bài: ảnh đại diện, chưa có thì ảnh đầu tiên của bộ ảnh (0 nếu không có).
 *
 * @param WP_Post $post   Bài.
 * @param array   $fields isha_space_fields() của bài.
 * @return int
 */
function isha_space_cover( $post, array $fields ) {
	$thumb = (int) get_post_thumbnail_id( $post );
	if ( $thumb > 0 && wp_attachment_is_image( $thumb ) ) {
		return $thumb;
	}
	return $fields['gallery'] ? (int) $fields['gallery'][0] : 0;
}

/**
 * Hướng của một ảnh theo metadata: 'wide' khi rộng/cao từ $min, 'tall' khi nhỏ hơn, '' khi thiếu metadata.
 *
 * @param int   $id  ID ảnh.
 * @param float $min Ngưỡng tỉ lệ.
 * @return string
 */
function isha_space_shape( $id, $min = 1.05 ) {
	$meta = wp_get_attachment_metadata( $id );
	if ( ! is_array( $meta ) || empty( $meta['width'] ) || empty( $meta['height'] ) ) {
		return '';
	}
	return ( (float) $meta['width'] / (float) $meta['height'] ) >= $min ? 'wide' : 'tall';
}

/**
 * Một mục không gian cho danh sách, trang chủ và khối "Không gian khác".
 *
 * @param WP_Post $post Bài.
 * @return array{id:int,slug:string,title:string,subtitle:string,summary:string,url:string,image:int}
 */
function isha_space_item( $post ) {
	$fields = isha_space_fields( $post->ID );
	return array(
		'id'       => (int) $post->ID,
		'slug'     => (string) $post->post_name,
		'title'    => isha_home_data_plain( get_the_title( $post ) ),
		'subtitle' => $fields['subtitle'],
		'summary'  => $fields['summary'],
		'url'      => (string) get_permalink( $post ),
		'image'    => isha_space_cover( $post, $fields ),
	);
}

/**
 * Dữ liệu trang danh sách: hero (meta box "Hero banner" của trang) và sections (mỗi bài một section, kèm liên kết tới bài).
 *
 * @param int $page_id ID trang danh sách.
 * @return array{hero:array,sections:array}
 */
function isha_space_list_data( $page_id ) {
	$hero  = isha_about_hero_data( $page_id, __( 'Không gian ISHA', 'isha' ), __( 'Một nơi nghỉ ngơi và hồi phục cho mọi giác quan, nơi mỗi liệu trình bắt đầu bằng sự thư thái.', 'isha' ) );
	$tones = isha_space_tones();

	$sections = array();
	foreach ( isha_space_posts() as $post ) {
		$item       = isha_space_item( $post );
		$n          = count( $sections );
		$sections[] = array(
			'id'       => 'space-' . $item['id'],
			'anchor'   => 'khong-gian-' . ( '' !== $item['slug'] ? sanitize_title( $item['slug'] ) : (string) $item['id'] ),
			'tone'     => $tones[ $n % count( $tones ) ],
			'number'   => $n + 1,
			'title'    => $item['title'],
			'subtitle' => $item['subtitle'],
			'content'  => '' !== $item['summary'] ? wpautop( esc_html( $item['summary'] ) ) : '',
			'image'    => $item['image'],
			'url'      => $item['url'],
		);
	}

	return array(
		'hero'     => $hero,
		'sections' => $sections,
	);
}

/**
 * Dữ liệu trang chi tiết bài Không gian.
 *
 * - hero: tiêu đề bài, mô tả (tiêu đề nhỏ, trống thì mô tả ngắn), ảnh nền (ảnh ngang đủ nét đầu tiên của bộ ảnh), nhãn là liên kết về trang
 *   danh sách.
 * - intro: section giới thiệu (ảnh đại diện 4:5, mô tả ngắn in nghiêng, nội dung bài).
 * - gallery: ảnh của bài trừ ảnh đã dùng ở intro (id, caption, alt, shape).
 * - others: tối đa 3 không gian kế tiếp theo thứ tự hiển thị (vòng về đầu danh sách).
 *
 * @param WP_Post $post Bài.
 * @return array
 */
function isha_space_single_data( $post ) {
	$fields   = isha_space_fields( $post->ID );
	$cover    = isha_space_cover( $post, $fields );
	$list_url = isha_space_list_url();
	$list     = isha_space_list_page();

	// Ảnh nền hero: ảnh ngang đủ nét cho màn hình rộng (cạnh ngang từ 1600px); không có thì hero nền tối với mặt trời vàng kim.
	$hero_img = 0;
	foreach ( $fields['gallery'] as $id ) {
		$meta = wp_get_attachment_metadata( $id );
		if ( 'wide' === isha_space_shape( $id, 1.2 ) && is_array( $meta ) && (int) $meta['width'] >= 1600 ) {
			$hero_img = $id;
			break;
		}
	}

	$content = trim( str_replace( ']]>', ']]&gt;', (string) apply_filters( 'the_content', get_the_content( null, false, $post ) ) ) );

	$gallery = array();
	foreach ( $fields['gallery'] as $id ) {
		if ( $id === $cover || ! wp_attachment_is_image( $id ) ) {
			continue;
		}
		$shape     = isha_space_shape( $id );
		$gallery[] = array(
			'id'      => $id,
			'caption' => isha_home_data_plain( wp_get_attachment_caption( $id ) ),
			'alt'     => isha_home_data_plain( get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
			'shape'   => '' !== $shape ? $shape : 'wide',
		);
	}

	// Không gian khác: các bài sau bài này theo thứ tự hiển thị, hết danh sách thì vòng về đầu.
	$all   = isha_space_posts();
	$ids   = wp_list_pluck( $all, 'ID' );
	$at    = array_search( $post->ID, $ids, true );
	$order = false === $at ? $all : array_merge( array_slice( $all, (int) $at + 1 ), array_slice( $all, 0, (int) $at ) );

	$others = array();
	foreach ( $order as $other ) {
		if ( (int) $other->ID === (int) $post->ID ) {
			continue;
		}
		$others[] = isha_space_item( $other );
		if ( count( $others ) >= 3 ) {
			break;
		}
	}

	return array(
		'hero'     => array(
			'title'         => isha_home_data_plain( get_the_title( $post ) ),
			'description'   => '' !== $fields['subtitle'] ? $fields['subtitle'] : $fields['summary'],
			'eyebrow'       => $list ? isha_home_data_plain( get_the_title( $list ) ) : __( 'Không gian ISHA', 'isha' ),
			'eyebrow_url'   => $list_url,
			'image_desktop' => $hero_img,
			'image_mobile'  => $hero_img,
		),
		'intro'    => array(
			'id'       => 'space-intro',
			'anchor'   => 'gioi-thieu-khong-gian',
			'tone'     => 'day',
			'number'   => 0,
			'title'    => '',
			'subtitle' => '' !== $fields['subtitle'] ? $fields['summary'] : '',
			'content'  => $content,
			'image'    => $cover,
		),
		'gallery'  => $gallery,
		'others'   => $others,
		'list_url' => $list_url,
	);
}

/**
 * Nạp tài nguyên (trang danh sách và bài Không gian): phong cách trang Giới thiệu (isha_about_enqueue()) rồi space.css.
 */
function isha_space_assets() {
	if ( ! isha_space_is_active() || ! function_exists( 'isha_about_enqueue' ) ) {
		return;
	}
	isha_about_enqueue();
	$css = ISHA_DIR . '/assets/css/space.css';
	if ( is_file( $css ) ) {
		wp_enqueue_style( 'isha-space', ISHA_URI . '/assets/css/space.css', wp_style_is( 'isha-about', 'enqueued' ) ? array( 'isha-about' ) : array( 'isha-main' ), (string) filemtime( $css ) );
	}
}
add_action( 'wp_enqueue_scripts', 'isha_space_assets', 20 );

/**
 * View Transitions giữa trang chủ và trang danh sách Không gian ISHA (20/11/2026): cả hai trang bật chuyển cảnh khi điều hướng cùng nguồn,
 * nên đi giữa hai trang là một lần chuyển mờ cả trang, và ảnh phòng đang hiện ở trang chủ (home.js đặt view-transition-name isha-room lúc
 * pageswap) "mở ra" thành ảnh hero của trang Không gian (space.css đặt cùng tên). Chỉ hai trang này bật; trang khác điều hướng như cũ.
 * Giảm chuyển động thì không bật. Trình duyệt chưa hỗ trợ (Firefox, Safari cũ) bỏ qua quy tắc.
 */
function isha_space_view_transition() {
	if ( ! is_front_page() && ! is_page_template( ISHA_SPACE_TEMPLATE ) ) {
		return;
	}
	echo '<style id="isha-view-transition">@media (prefers-reduced-motion: no-preference){@view-transition{navigation:auto}}</style>' . "\n";
}
add_action( 'wp_head', 'isha_space_view_transition', 2 );
