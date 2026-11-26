<?php
/**
 * Trang chi tiết Tin tức (isha_article) và Sự kiện (isha_event), 18/11/2026 (docs/home-architecture.md mục 17): dữ liệu dựng sẵn và nạp
 * tài nguyên. Tin tức (single-isha_article.php, nâng cấp 25/11/2026, docs/news.md): markup ở template-parts/post-article.php, giao diện ở
 * assets/css/post-article.css và assets/js/post-article.js. Sự kiện (single-isha_event.php, nâng cấp 25/11/2026, docs/events.md): markup ở
 * template-parts/post-event.php, giao diện dùng lại post-article.css và post-article.js (khung trang, hero tối, thẻ tối, xem lớn ảnh, sao chép
 * liên kết) cộng phần riêng ở assets/css/post-event.css. Trang sự kiện chỉ hiển thị thông tin, không có nút đăng ký.
 *
 * Trang dựng trên hệ thiết kế của trang chủ như trang chi tiết liệu trình: bao trong `div.isha-home.isha-art`, nạp home.css, isha-motion.js,
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
 * Ảnh bìa ngang của bài Tin tức: ảnh banner 16:10 nếu biên tập viên đã chọn (ISHA Core, meta _isha_article_banner), không thì ảnh đại diện
 * (cắt giữa theo kích thước isha-banner).
 *
 * @param WP_Post $post Bài.
 * @return int ID tệp đính kèm hoặc 0.
 */
function isha_post_single_cover_id( $post ) {
	$fields = class_exists( 'ISHA_Core_Article_Fields' ) ? ISHA_Core_Article_Fields::get( $post->ID ) : array();
	if ( ! empty( $fields['banner_id'] ) ) {
		return (int) $fields['banner_id'];
	}
	return (int) get_post_thumbnail_id( $post );
}

/**
 * Bài được dựng bằng Elementor (Sửa bằng Elementor)? Khi đó vùng nội dung để Elementor tự quản lý bố cục và bề rộng.
 *
 * @param WP_Post $post Bài.
 * @return bool
 */
function isha_post_single_is_builder( $post ) {
	return 'builder' === get_post_meta( $post->ID, '_elementor_edit_mode', true );
}

/**
 * Khối "Liệu trình gợi ý" cuối bài: nhóm dịch vụ gắn với chủ đề của bài (ISHA Core > Tin tức > Chủ đề > "Nhóm dịch vụ gợi ý"); chủ đề chưa gắn
 * nhóm thì dẫn tới trang Dịch vụ. Không có cả trang Dịch vụ thì url rỗng và template bỏ khối.
 *
 * @param WP_Post $post Bài Tin tức.
 * @return array{name:string,desc:string,url:string,mapped:bool}
 */
function isha_post_single_bridge( $post ) {
	$bridge = array(
		'name'   => __( 'Liệu trình tại ISHA', 'isha' ),
		'desc'   => __( 'Mỗi làn da có một phác đồ riêng, xây dựng sau buổi thăm khám trực tiếp cùng Bác sĩ.', 'isha' ),
		'url'    => function_exists( 'isha_home_data_services_url' ) ? isha_home_data_services_url() : '',
		'mapped' => false,
	);

	$terms = get_the_terms( $post, 'isha_article_cat' );
	if ( ! class_exists( 'ISHA_Core_Article_Fields' ) || ! is_array( $terms ) ) {
		return $bridge;
	}
	foreach ( $terms as $term ) {
		$group_id = $term instanceof WP_Term ? ISHA_Core_Article_Fields::service_group_of( $term->term_id ) : 0;
		$group    = $group_id > 0 ? get_term( $group_id, 'isha_service_cat' ) : null;
		$link     = $group instanceof WP_Term ? get_term_link( $group ) : null;
		if ( $group instanceof WP_Term && is_string( $link ) ) {
			$desc = trim( wp_strip_all_tags( $group->description ) );
			return array(
				'name'   => wp_strip_all_tags( $group->name ),
				'desc'   => '' !== $desc ? wp_trim_words( $desc, 28, '…' ) : $bridge['desc'],
				'url'    => $link,
				'mapped' => true,
			);
		}
	}
	return $bridge;
}

/**
 * Thời điểm cuối ngày (23:59:59 theo múi giờ của website) của ngày chứa một mốc thời gian.
 *
 * @param int $ts Timestamp UTC.
 * @return int Timestamp UTC.
 */
function isha_post_single_day_end( $ts ) {
	try {
		$day = ( new DateTimeImmutable( '@' . (int) $ts ) )->setTimezone( wp_timezone() )->setTime( 23, 59, 59 );
		return $day->getTimestamp();
	} catch ( Exception $e ) {
		return (int) $ts + DAY_IN_SECONDS;
	}
}

/**
 * Địa điểm có phải hình thức trực tuyến không (ghi "Trực tuyến", "Zoom", "Google Meet"…): khi đó không tạo link tìm kiếm bản đồ từ chữ địa điểm.
 * Bộ lọc `isha/event/online_pattern` cho phép đổi biểu thức (PCRE, có cờ u).
 *
 * @param string $location Địa điểm.
 * @return bool
 */
function isha_post_single_is_online( $location ) {
	$pattern = (string) apply_filters( 'isha/event/online_pattern', '/trực\s*tuyến|truc\s*tuyen|online|zoom|google\s*meet|teams|livestream|webinar|fanpage|facebook|youtube|tiktok/iu' );
	return '' !== $pattern && 1 === preg_match( $pattern, $location );
}

/**
 * Link "Chỉ đường": link bản đồ biên tập viên nhập (ISHA Core > Sự kiện > Link bản đồ) nếu có, không thì link tìm kiếm Google Maps theo đúng
 * chuỗi địa điểm. Không có địa điểm thì không có link (sự kiện ưu đãi, ra mắt dịch vụ không cần bản đồ); địa điểm trực tuyến mà không nhập
 * link bản đồ thì cũng không có link.
 *
 * @param string $location Địa điểm.
 * @param string $map_url  Link bản đồ đã làm sạch ('' nếu chưa nhập).
 * @return string URL hoặc ''.
 */
function isha_post_single_event_map( $location, $map_url ) {
	if ( '' === $location ) {
		return '';
	}
	if ( '' !== $map_url ) {
		return $map_url;
	}
	if ( isha_post_single_is_online( $location ) ) {
		return '';
	}
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $location );
}

/**
 * Link tạo sự kiện trên Google Calendar (mở thẻ mới, điền sẵn tiêu đề, thời gian, mô tả và địa điểm). Dựng bằng PHP nên chạy cả khi không có JS.
 * Không có giờ kết thúc thì lịch dài 2 giờ kể từ giờ bắt đầu (Google yêu cầu có khoảng thời gian).
 *
 * @param string $title    Tiêu đề.
 * @param string $summary  Mô tả ngắn ('' nếu không có).
 * @param string $location Địa điểm ('' nếu không có).
 * @param int    $start    Timestamp bắt đầu (UTC), > 0.
 * @param int    $end      Timestamp kết thúc (UTC) hoặc 0.
 * @param string $url      Địa chỉ trang sự kiện.
 * @return string URL hoặc '' khi thiếu thời gian bắt đầu.
 */
function isha_post_single_event_calendar( $title, $summary, $location, $start, $end, $url ) {
	if ( $start < 1 ) {
		return '';
	}
	$end     = $end > $start ? $end : $start + 2 * HOUR_IN_SECONDS;
	// Nối bằng dấu cách: template in link qua esc_url(), hàm này bỏ ký tự xuống dòng (%0A) khỏi URL.
	$details = trim( $summary . ' ' . $url );
	$link    = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
		. '&text=' . rawurlencode( $title )
		. '&dates=' . rawurlencode( gmdate( 'Ymd\THis\Z', $start ) . '/' . gmdate( 'Ymd\THis\Z', $end ) )
		. '&details=' . rawurlencode( $details );
	if ( '' !== $location ) {
		$link .= '&location=' . rawurlencode( $location );
	}
	return $link;
}

/**
 * Lịch và thông tin của một sự kiện: ngày (thứ, ngày/tháng/năm), giờ (bắt đầu – kết thúc; kéo dài sang ngày khác thì kèm ngày kết thúc),
 * địa điểm, link "Chỉ đường", trạng thái.
 *
 * Trạng thái (`state`): 'upcoming' (sắp diễn ra), 'ongoing' (đang diễn ra), 'past' (đã kết thúc) hoặc '' khi sự kiện không có ngày (ưu đãi,
 * ra mắt dịch vụ). Sự kiện không có giờ kết thúc kéo dài hết ngày bắt đầu (23:59:59 theo múi giờ của website), nên workshop đang diễn ra không bị
 * coi là đã kết thúc.
 *
 * @param int $post_id ID sự kiện.
 * @return array{start_ts:int,end_ts:int,until_ts:int,date:string,time:string,location:string,map_url:string,portrait_id:int,state:string,past:bool}
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
	$until = $start > 0 ? ( $end > $start ? $end : isha_post_single_day_end( $start ) ) : 0;
	$now   = time();
	$state = '';
	if ( $start > 0 ) {
		$state = $until < $now ? 'past' : ( $start <= $now ? 'ongoing' : 'upcoming' );
	}
	$location = isset( $f['location'] ) ? (string) $f['location'] : '';

	return array(
		'start_ts'    => $start,
		'end_ts'      => $end,
		'until_ts'    => $until,
		'date'        => $start > 0 ? wp_date( 'l, d/m/Y', $start ) : '',
		'time'        => $time,
		'location'    => $location,
		'map_url'     => isha_post_single_event_map( $location, isset( $f['map_url'] ) ? (string) $f['map_url'] : '' ),
		'portrait_id' => isset( $f['portrait_id'] ) ? (int) $f['portrait_id'] : 0,
		'state'       => $state,
		'past'        => 'past' === $state,
	);
}

/**
 * Ảnh bìa ngang của sự kiện: ảnh đại diện (ảnh ngang 2.39:1 của sự kiện, cắt giữa về 16:10 bằng kích thước isha-banner); chưa có thì ảnh dọc 4:5.
 *
 * @param WP_Post $post Sự kiện.
 * @return int ID tệp đính kèm hoặc 0.
 */
function isha_post_single_event_cover_id( $post ) {
	$id = (int) get_post_thumbnail_id( $post );
	if ( $id > 0 ) {
		return $id;
	}
	$f = class_exists( 'ISHA_Core_Event_Fields' ) ? ISHA_Core_Event_Fields::get( $post->ID ) : array();
	return isset( $f['portrait_id'] ) ? (int) $f['portrait_id'] : 0;
}

/**
 * Một thẻ của khối bài liên quan: ảnh ngang 16:10 (sự kiện: ảnh đại diện, chưa có thì ảnh dọc), nhãn, tiêu đề, dòng phụ; sự kiện kèm trạng thái
 * (`state`: upcoming | ongoing | past | '') cho nhãn trạng thái trên ảnh.
 *
 * @param WP_Post $post Bài.
 * @return array{id:int,title:string,url:string,image:int,label:string,meta:string,state:string}
 */
function isha_post_single_card( $post ) {
	$state = '';
	if ( 'isha_event' === $post->post_type ) {
		$event = isha_post_single_event( $post->ID );
		$term  = isha_post_single_term( $post, 'isha_event_type' );
		$meta  = $event['start_ts'] > 0 ? wp_date( 'd/m/Y', $event['start_ts'] ) : '';
		$state = $event['state'];
		$image = isha_post_single_event_cover_id( $post );
	} else {
		$term  = isha_post_single_term( $post, 'isha_article_cat' );
		$meta  = get_the_date( 'd/m/Y', $post );
		$image = isha_post_single_cover_id( $post );
	}
	return array(
		'id'    => (int) $post->ID,
		'title' => wp_strip_all_tags( get_the_title( $post ) ),
		'url'   => (string) get_permalink( $post ),
		'image' => $image,
		'label' => $term['name'],
		'meta'  => (string) $meta,
		'state' => $state,
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

	// Liên hệ nhanh (Tin tức và Sự kiện): hotline tách nhịp 4-3-3 và Zalo lấy từ cùng nguồn với dock và cảnh kết, không viết cứng.
	$contact = function_exists( 'isha_home_contact' ) ? isha_home_contact() : array();
	$phone   = function_exists( 'isha_footer_phone' ) ? isha_footer_phone() : array();

	$data['zalo']    = isset( $contact['zalo_url'] ) ? (string) $contact['zalo_url'] : '';
	$data['tel_url'] = ! empty( $contact['tel_url'] ) ? (string) $contact['tel_url'] : $data['tel_url'];
	$data['phone']   = isset( $phone['text'] ) ? (string) $phone['text'] : $data['phone'];
	$data['builder'] = isha_post_single_is_builder( $post );

	if ( $is_event ) {
		// Sự kiện (template-parts/post-event.php): ảnh bìa ngang 16:10 (kèm địa chỉ ảnh gốc cho hộp xem lớn), lịch, trạng thái, link bản đồ và link lịch.
		$event                 = isha_post_single_event( $post->ID );
		$event['calendar_url'] = 'past' === $event['state'] ? '' : isha_post_single_event_calendar( $data['title'], $data['summary'], $event['location'], $event['start_ts'], $event['end_ts'], (string) get_permalink( $post ) );
		$data['event']         = $event;
		$data['image']         = isha_post_single_event_cover_id( $post );
		$data['image_full']    = $data['image'] > 0 ? (string) wp_get_attachment_image_url( $data['image'], 'full' ) : '';
		return $data;
	}

	// Tin tức (template-parts/post-article.php): ảnh bìa ngang 16:10, ngày cập nhật, liệu trình gợi ý.
	$data['image']        = isha_post_single_cover_id( $post );
	$data['modified']     = get_the_modified_date( 'd/m/Y', $post );
	$data['modified_iso'] = get_the_modified_date( 'c', $post );
	// Chỉ báo "Cập nhật" khi bài được sửa sau ngày đăng ít nhất một ngày (tránh dòng thừa ngay sau khi đăng).
	$data['updated']      = ( strtotime( $post->post_modified_gmt . ' UTC' ) - strtotime( $post->post_date_gmt . ' UTC' ) ) > DAY_IN_SECONDS;
	$data['bridge']       = isha_post_single_bridge( $post );
	return $data;
}

/**
 * Nạp tài nguyên (chỉ trang chi tiết Tin tức, Sự kiện): tài nguyên trang chủ rồi CSS của trang (CSS nằm trong <head>).
 * Tin tức (nâng cấp 25/11/2026, docs/news.md) và Sự kiện (docs/events.md) cùng nạp post-article.css và post-article.js (xem lớn ảnh, sao chép
 * liên kết; Tin tức còn mục lục và thanh tiến trình đọc). Sự kiện nạp thêm post-event.css (phần riêng: dải thông số, thẻ thông tin, nhãn trạng thái).
 */
function isha_post_single_assets() {
	if ( ! is_singular( array( 'isha_article', 'isha_event' ) ) ) {
		return;
	}
	if ( function_exists( 'isha_home_enqueue_assets' ) ) {
		isha_home_enqueue_assets();
	}
	$deps = wp_style_is( 'isha-home', 'enqueued' ) ? array( 'isha-main', 'isha-home' ) : array( 'isha-main' );

	$css = ISHA_DIR . '/assets/css/post-article.css';
	$js  = ISHA_DIR . '/assets/js/post-article.js';
	if ( is_file( $css ) ) {
		wp_enqueue_style( 'isha-post-article', ISHA_URI . '/assets/css/post-article.css', $deps, (string) filemtime( $css ) );
	}
	if ( is_singular( 'isha_event' ) ) {
		$event_css = ISHA_DIR . '/assets/css/post-event.css';
		if ( is_file( $event_css ) ) {
			wp_enqueue_style( 'isha-post-event', ISHA_URI . '/assets/css/post-event.css', array_merge( $deps, array( 'isha-post-article' ) ), (string) filemtime( $event_css ) );
		}
	}
	if ( is_file( $js ) ) {
		$js_deps = array();
		foreach ( array( 'isha-motion', 'isha-home-js' ) as $handle ) {
			if ( wp_script_is( $handle, 'enqueued' ) ) {
				$js_deps[] = $handle;
			}
		}
		wp_enqueue_script(
			'isha-post-article',
			ISHA_URI . '/assets/js/post-article.js',
			$js_deps,
			(string) filemtime( $js ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		// Chuỗi hiển thị do JS tạo ra (mục lục, hộp xem lớn, nút sao chép); post-article.js không tự có chữ tiếng Việt.
		wp_add_inline_script(
			'isha-post-article',
			'window.ISHA=window.ISHA||{};window.ISHA.postArticle=' . wp_json_encode(
				array(
					'strings' => array(
						'toc'      => __( 'Mục lục', 'isha' ),
						'tocTitle' => __( 'Nội dung bài viết', 'isha' ),
						'copy'     => __( 'Sao chép liên kết', 'isha' ),
						'copied'   => __( 'Đã sao chép ✓', 'isha' ),
						'zoom'     => __( 'Xem lớn ảnh', 'isha' ),
						'close'    => __( 'Đóng', 'isha' ),
						'prev'     => __( 'Trước', 'isha' ),
						'next'     => __( 'Sau', 'isha' ),
						'viewer'   => __( 'Xem ảnh trong bài', 'isha' ),
					),
				)
			) . ';',
			'before'
		);
	}
}
add_action( 'wp_enqueue_scripts', 'isha_post_single_assets', 20 );
