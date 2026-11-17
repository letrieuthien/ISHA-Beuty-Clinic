<?php
/**
 * Trang chi tiết Tin tức (isha_article) và Sự kiện (isha_event), 18/11/2026 (docs/home-architecture.md mục 17): dữ liệu dựng sẵn và nạp
 * tài nguyên. Markup ở template-parts/post-single.php (single-isha_article.php, single-isha_event.php), giao diện ở assets/css/post-single.css.
 *
 * Trang dựng trên hệ thiết kế của trang chủ như trang chi tiết liệu trình: bao trong `div.isha-home.isha-post`, nạp home.css, isha-motion.js,
 * home.js (cảnh kết [isha_home_cta] ở cuối trang cần home.js để phát video nền). Mọi giá trị trả về là văn bản thô; template tự escape.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trang danh sách của một loại bài (đường dẫn quay lại): Tin tức là trang Tin tức (isha_home_data_news_url()), Sự kiện là trang slug
 * danh-sach-su-kien.
 *
 * @param string $type isha_article | isha_event.
 * @return array{url:string,label:string}
 */
function isha_post_single_list( $type ) {
	if ( 'isha_event' === $type ) {
		$page = get_page_by_path( 'danh-sach-su-kien', OBJECT, 'page' );
		return array(
			'url'   => ( $page instanceof WP_Post && 'publish' === $page->post_status ) ? (string) get_permalink( $page ) : '',
			'label' => __( 'Workshop & sự kiện', 'isha' ),
		);
	}
	return array(
		'url'   => function_exists( 'isha_home_data_news_url' ) ? isha_home_data_news_url() : '',
		'label' => __( 'Tin tức', 'isha' ),
	);
}

/**
 * Tên term đầu tiên của bài trong một taxonomy, kèm liên kết ('' nếu không có).
 *
 * @param WP_Post $post     Bài.
 * @param string  $taxonomy Taxonomy.
 * @return array{name:string,url:string}
 */
function isha_post_single_term( $post, $taxonomy ) {
	$terms = get_the_terms( $post, $taxonomy );
	if ( is_array( $terms ) && $terms && $terms[0] instanceof WP_Term ) {
		$link = get_term_link( $terms[0] );
		return array(
			'name' => wp_strip_all_tags( $terms[0]->name ),
			'url'  => is_wp_error( $link ) ? '' : (string) $link,
		);
	}
	return array(
		'name' => '',
		'url'  => '',
	);
}

/**
 * Thời gian đọc (phút): số từ của nội dung đã bỏ thẻ chia 200, làm tròn lên, tối thiểu 1.
 *
 * @param WP_Post $post Bài.
 * @return int
 */
function isha_post_single_read_min( $post ) {
	$text  = trim( wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ) );
	$words = '' === $text ? 0 : count( preg_split( '/\s+/u', $text ) );
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Lịch và thông tin của một sự kiện: ngày (thứ, ngày/tháng/năm), giờ (bắt đầu – kết thúc; kéo dài sang ngày khác thì kèm ngày kết thúc),
 * địa điểm, link đăng ký, đã diễn ra chưa.
 *
 * @param int $post_id ID sự kiện.
 * @return array{start_ts:int,end_ts:int,date:string,time:string,location:string,register_url:string,portrait_id:int,past:bool}
 */
function isha_post_single_event( $post_id ) {
	$f     = class_exists( 'ISHA_Core_Event_Fields' ) ? ISHA_Core_Event_Fields::get( $post_id ) : array();
	$start = isset( $f['start_ts'] ) ? (int) $f['start_ts'] : 0;
	$end   = isset( $f['end_ts'] ) ? (int) $f['end_ts'] : 0;
	$time  = '';
	if ( $start > 0 ) {
		$time = wp_date( 'H:i', $start );
		if ( $end > $start ) {
			$time .= ' – ' . ( wp_date( 'Y-m-d', $start ) === wp_date( 'Y-m-d', $end ) ? wp_date( 'H:i', $end ) : wp_date( 'H:i, d/m/Y', $end ) );
		}
	}
	$until = $end > 0 ? $end : $start;

	return array(
		'start_ts'     => $start,
		'end_ts'       => $end,
		'date'         => $start > 0 ? wp_date( 'l, d/m/Y', $start ) : '',
		'time'         => $time,
		'location'     => isset( $f['location'] ) ? (string) $f['location'] : '',
		'register_url' => isset( $f['register_url'] ) ? (string) $f['register_url'] : '',
		'portrait_id'  => isset( $f['portrait_id'] ) ? (int) $f['portrait_id'] : 0,
		'past'         => $until > 0 && $until < time(),
	);
}

/**
 * Một thẻ của khối bài liên quan: ảnh dọc (sự kiện: ảnh dọc 4:5 nếu có, không thì ảnh đại diện), nhãn, tiêu đề, dòng phụ.
 *
 * @param WP_Post $post Bài.
 * @return array{id:int,title:string,url:string,image:int,label:string,meta:string}
 */
function isha_post_single_card( $post ) {
	if ( 'isha_event' === $post->post_type ) {
		$event = isha_post_single_event( $post->ID );
		$term  = isha_post_single_term( $post, 'isha_event_type' );
		$meta  = $event['start_ts'] > 0 ? wp_date( 'd/m/Y', $event['start_ts'] ) : '';
		if ( $event['past'] ) {
			$meta = trim( $meta . ' · ' . __( 'Đã diễn ra', 'isha' ), ' ·' );
		}
		$image = $event['portrait_id'] > 0 ? $event['portrait_id'] : (int) get_post_thumbnail_id( $post );
	} else {
		$term  = isha_post_single_term( $post, 'isha_article_cat' );
		$meta  = get_the_date( 'd/m/Y', $post );
		$image = (int) get_post_thumbnail_id( $post );
	}
	return array(
		'id'    => (int) $post->ID,
		'title' => wp_strip_all_tags( get_the_title( $post ) ),
		'url'   => (string) get_permalink( $post ),
		'image' => $image,
		'label' => $term['name'],
		'meta'  => (string) $meta,
	);
}

/**
 * Bài liên quan (tối đa $count): Tin tức cùng chủ đề trước rồi bài mới nhất; Sự kiện sắp diễn ra gần nhất trước rồi sự kiện đã qua gần nhất.
 *
 * @param WP_Post $post  Bài đang xem.
 * @param int     $count Số thẻ.
 * @return array Danh sách isha_post_single_card().
 */
function isha_post_single_related( $post, $count = 3 ) {
	$base = array(
		'post_type'           => $post->post_type,
		'post_status'         => 'publish',
		'post__not_in'        => array( $post->ID ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	$picked = array();

	if ( 'isha_event' === $post->post_type ) {
		$pool = get_posts( $base + array( 'posts_per_page' => 30 ) );
		$rows = array();
		foreach ( $pool as $item ) {
			$e      = isha_post_single_event( $item->ID );
			$rows[] = array(
				'post' => $item,
				'past' => $e['past'],
				'key'  => $e['start_ts'],
			);
		}
		usort(
			$rows,
			static function ( $a, $b ) {
				if ( $a['past'] !== $b['past'] ) {
					return $a['past'] ? 1 : -1;
				}
				return $a['past'] ? $b['key'] <=> $a['key'] : $a['key'] <=> $b['key'];
			}
		);
		$picked = wp_list_pluck( array_slice( $rows, 0, $count ), 'post' );
	} else {
		$terms = wp_get_post_terms( $post->ID, 'isha_article_cat', array( 'fields' => 'ids' ) );
		if ( is_array( $terms ) && $terms ) {
			$picked = get_posts(
				$base + array(
					'posts_per_page' => $count,
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array(
							'taxonomy' => 'isha_article_cat',
							'terms'    => $terms,
						),
					),
				)
			);
		}
		if ( count( $picked ) < $count ) {
			$more   = get_posts(
				array_merge(
					$base,
					array(
						'posts_per_page' => $count - count( $picked ),
						'post__not_in'   => array_merge( array( $post->ID ), wp_list_pluck( $picked, 'ID' ) ),
					)
				)
			);
			$picked = array_merge( $picked, $more );
		}
	}

	return array_map( 'isha_post_single_card', $picked );
}

/**
 * Dữ liệu dựng sẵn của trang chi tiết.
 *
 * @param WP_Post $post Bài (isha_article hoặc isha_event).
 * @return array
 */
function isha_post_single_data( $post ) {
	$type     = $post->post_type;
	$is_event = 'isha_event' === $type;
	$term     = isha_post_single_term( $post, $is_event ? 'isha_event_type' : 'isha_article_cat' );
	$info     = function_exists( 'isha_forms_contact_info' ) ? isha_forms_contact_info() : array(
		'phone'   => '',
		'tel_url' => '',
	);

	$data = array(
		'type'     => $type,
		'id'       => (int) $post->ID,
		'title'    => wp_strip_all_tags( get_the_title( $post ) ),
		'summary'  => function_exists( 'isha_editorial_summary' ) ? isha_editorial_summary( $post ) : wp_strip_all_tags( $post->post_excerpt ),
		'term'     => $term,
		'image'    => (int) get_post_thumbnail_id( $post ),
		'list'     => isha_post_single_list( $type ),
		'related'  => isha_post_single_related( $post ),
		'booking'  => function_exists( 'isha_forms_booking_url' ) ? isha_forms_booking_url() : '',
		'phone'    => $info['phone'],
		'tel_url'  => $info['tel_url'],
		'event'    => array(),
		'date'     => get_the_date( 'd/m/Y', $post ),
		'date_iso' => get_the_date( 'c', $post ),
		'author'   => function_exists( 'isha_editorial_author_name' ) ? isha_editorial_author_name( (int) $post->post_author ) : '',
		'read_min' => $is_event ? 0 : isha_post_single_read_min( $post ),
	);
	if ( $is_event ) {
		$data['event'] = isha_post_single_event( $post->ID );
	}
	return $data;
}

/**
 * Nạp tài nguyên (chỉ trang chi tiết Tin tức, Sự kiện): tài nguyên trang chủ rồi post-single.css (CSS nằm trong <head>).
 */
function isha_post_single_assets() {
	if ( ! is_singular( array( 'isha_article', 'isha_event' ) ) ) {
		return;
	}
	if ( function_exists( 'isha_home_enqueue_assets' ) ) {
		isha_home_enqueue_assets();
	}
	$css = ISHA_DIR . '/assets/css/post-single.css';
	if ( is_file( $css ) ) {
		wp_enqueue_style( 'isha-post-single', ISHA_URI . '/assets/css/post-single.css', wp_style_is( 'isha-home', 'enqueued' ) ? array( 'isha-main', 'isha-home' ) : array( 'isha-main' ), (string) filemtime( $css ) );
	}
}
add_action( 'wp_enqueue_scripts', 'isha_post_single_assets', 20 );
