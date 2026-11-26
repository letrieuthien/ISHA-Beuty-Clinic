<?php
/**
 * Tìm kiếm nhanh trên header (docs/search.md): nút kính lúp trong cụm phải của header (trước nút "Đặt lịch"), popup `<dialog>` tìm theo TIÊU ĐỀ của Dịch vụ
 * (isha_service), Tin tức (isha_article) và Sự kiện (isha_event), trả kết quả gom nhóm qua admin-ajax (action isha_live_search).
 *
 * - Markup popup in ở chân trang (wp_footer); assets/js/live-search.js mở bằng showModal() (lớp trên cùng, không tranh z-index với header,
 *   dock, intro), Ctrl/Cmd + K mở nhanh, Esc hoặc bấm vùng tối quanh khung đóng, trả focus về nút đã mở.
 * - Không có JS: nút là liên kết thật tới trang tìm kiếm mặc định của WordPress (?s=), vẫn dùng được.
 * - Tắt ở chế độ landing (header không có menu) và qua bộ lọc `isha/live_search/enabled`.
 * - Từ khoá gợi ý: bộ lọc `isha/live_search/suggestions` ( string[] ). Số bài mỗi nhóm: `isha/live_search/per_group` ( int, 1–12 ).
 * - Đa ngôn ngữ: chưa có Polylang/WPML; isha_live_search_language_args() là chỗ giữ sẵn để lọc theo ngôn ngữ hiện tại khi cài.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trang hiện tại có nút tìm kiếm không: phía khách, không phải khung nhúng, header đang hiện menu (không ở chế độ landing).
 *
 * @return bool
 */
function isha_live_search_enabled() {
	$enabled = ! is_admin() && ! is_embed() && ! isha_landing_only();

	return (bool) apply_filters( 'isha/live_search/enabled', $enabled );
}

/**
 * Từ khoá gợi ý (sửa danh sách ở đây hoặc qua bộ lọc). Mỗi từ khoá phải khớp ít nhất một tiêu đề đang đăng, nếu không bấm vào sẽ ra
 * trạng thái "không tìm thấy".
 *
 * @return string[]
 */
function isha_live_search_suggestions() {
	$list = array( 'Peel', 'HIFU', 'Botox', 'Mụn', 'Da khỏe', 'Workshop' );
	$list = (array) apply_filters( 'isha/live_search/suggestions', $list );

	return array_values( array_filter( array_map( 'sanitize_text_field', $list ) ) );
}

/**
 * Ba nhóm kết quả theo thứ tự hiển thị. Nhãn phụ: tên nhóm dịch vụ, chuyên mục tin tức, trạng thái sự kiện.
 *
 * @return array<string,array{post_type:string,label:string,taxonomy:string}>
 */
function isha_live_search_groups() {
	return array(
		'service' => array(
			'post_type' => 'isha_service',
			'label'     => __( 'Dịch vụ', 'isha' ),
			'taxonomy'  => 'isha_service_cat',
		),
		'article' => array(
			'post_type' => 'isha_article',
			'label'     => __( 'Tin tức', 'isha' ),
			'taxonomy'  => 'isha_article_cat',
		),
		'event'   => array(
			'post_type' => 'isha_event',
			'label'     => __( 'Sự kiện', 'isha' ),
			'taxonomy'  => '',
		),
	);
}

/**
 * Biểu tượng kính lúp (assets/icons/search.svg) dạng SVG nội tuyến, tô bằng currentColor để đổi màu theo CSS.
 *
 * @param string $class Class thêm vào thẻ svg.
 * @return string
 */
function isha_live_search_icon( $class = '' ) {
	static $path = null;

	if ( null === $path ) {
		$path = '';
		$file = ISHA_DIR . '/assets/icons/search.svg';
		$svg  = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- tệp nội bộ của theme.
		if ( preg_match( '/<path[^>]*\sd="([^"]+)"/', $svg, $m ) ) {
			$path = $m[1];
		}
	}
	if ( '' === $path ) {
		return '';
	}

	return sprintf(
		'<svg class="%s" viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden="true" focusable="false"><path d="%s"/></svg>',
		esc_attr( trim( 'isha-search-icon ' . $class ) ),
		esc_attr( $path )
	);
}

/**
 * Biểu tượng nét mảnh của từng nhóm (SVG tĩnh, tô bằng currentColor).
 *
 * @return array<string,string>
 */
function isha_live_search_group_icons() {
	$open  = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">';
	$close = '</svg>';

	return array(
		// Mặt trời nhỏ: dịch vụ (motif chính của thương hiệu).
		'service' => $open . '<circle cx="12" cy="12" r="3.6"/><path d="M12 3v2.4M12 18.6V21M3 12h2.4M18.6 12H21M5.6 5.6l1.7 1.7M16.7 16.7l1.7 1.7M5.6 18.4l1.7-1.7M16.7 7.3l1.7-1.7"/>' . $close,
		// Trang báo: tin tức.
		'article' => $open . '<rect x="4" y="4.5" width="16" height="15" rx="1.5"/><path d="M8 9h8M8 12.5h8M8 16h5"/>' . $close,
		// Lịch: sự kiện.
		'event'   => $open . '<rect x="4" y="5.5" width="16" height="14" rx="1.5"/><path d="M4 10h16M8.5 3.5v4M15.5 3.5v4"/>' . $close,
	);
}

/**
 * In nút tìm kiếm trên header (gọi trong template-parts/header/masthead.php, cụm .site-header__tools). Là liên kết tới trang tìm kiếm mặc định
 * khi chưa có JS; live-search.js chặn bấm để mở popup và thêm role="button", aria-haspopup, aria-keyshortcuts (chỉ đúng khi có JS).
 */
function isha_live_search_button() {
	if ( ! isha_live_search_enabled() ) {
		return;
	}
	printf(
		'<a class="header-search" href="%1$s" data-isha-search-open>%2$s<span class="screen-reader-text">%3$s</span></a>',
		esc_url( home_url( '/?s=' ) ),
		isha_live_search_icon( 'header-search__icon' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG dựng sẵn, thuộc tính đã escape.
		esc_html__( 'Tìm kiếm', 'isha' )
	);
}

/**
 * Tham số lọc theo ngôn ngữ hiện tại cho WP_Query (chỗ giữ sẵn, chưa có tác dụng vì website chưa cài plugin đa ngôn ngữ).
 *
 * - Polylang: WP_Query nhận tham số `lang` (mã ngôn ngữ). Ngôn ngữ lấy từ JS gửi lên (thuộc tính lang của <html>) vì admin-ajax không
 *   biết trang đang xem là ngôn ngữ nào.
 * - WPML: chuyển ngôn ngữ trước khi truy vấn (action wpml_switch_language), WPML tự lọc.
 *
 * @param string $lang Mã ngôn ngữ do trình duyệt gửi (đã làm sạch), ví dụ "vi", "en".
 * @return array
 */
function isha_live_search_language_args( $lang ) {
	$args = array();

	if ( '' !== $lang && function_exists( 'pll_languages_list' ) ) {
		$codes = (array) pll_languages_list( array( 'fields' => 'slug' ) );
		if ( in_array( $lang, $codes, true ) ) {
			$args['lang'] = $lang;
		}
	} elseif ( '' !== $lang && has_action( 'wpml_switch_language' ) ) {
		do_action( 'wpml_switch_language', $lang );
	}

	return (array) apply_filters( 'isha/live_search/language_args', $args, $lang );
}

/**
 * Ảnh nhỏ của một bài (ảnh đại diện, cỡ thumbnail); '' nếu không có (JS hiện ảnh dự phòng của thương hiệu).
 *
 * @param WP_Post $post Bài.
 * @return string
 */
function isha_live_search_thumb( $post ) {
	$id = (int) get_post_thumbnail_id( $post );
	if ( ! $id && 'isha_event' === $post->post_type && function_exists( 'isha_post_single_event_cover_id' ) ) {
		$id = (int) isha_post_single_event_cover_id( $post );
	}
	$url = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';

	return $url ? (string) $url : '';
}

/**
 * Nhãn phụ của một bài: tên term đầu tiên (dịch vụ, tin tức) hoặc trạng thái sự kiện (cùng quy tắc với trang chi tiết: qua giờ kết thúc,
 * không có giờ kết thúc thì qua hết ngày bắt đầu, là đã kết thúc).
 *
 * @param WP_Post $post     Bài.
 * @param string  $taxonomy Taxonomy của nhóm ('' với sự kiện).
 * @return string
 */
function isha_live_search_badge( $post, $taxonomy ) {
	if ( 'isha_event' === $post->post_type ) {
		if ( ! function_exists( 'isha_post_single_event' ) ) {
			return '';
		}
		$states = array(
			'upcoming' => __( 'Sắp diễn ra', 'isha' ),
			'ongoing'  => __( 'Đang diễn ra', 'isha' ),
			'past'     => __( 'Đã kết thúc', 'isha' ),
		);
		$state = isha_post_single_event( $post->ID )['state'];

		return isset( $states[ $state ] ) ? $states[ $state ] : '';
	}
	if ( '' === $taxonomy ) {
		return '';
	}
	$terms = get_the_terms( $post, $taxonomy );

	return ( is_array( $terms ) && $terms && $terms[0] instanceof WP_Term ) ? wp_strip_all_tags( $terms[0]->name ) : '';
}

/**
 * Tìm bài theo tiêu đề trong một post type. Điều kiện LIKE trên post_title gắn bằng bộ lọc posts_clauses tạm thời, gỡ ngay sau khi chạy
 * xong để không ảnh hưởng truy vấn khác. Sắp xếp: tiêu đề bắt đầu bằng từ khoá lên trước, rồi theo tiêu đề.
 * So khớp không phân biệt hoa thường và dấu (collation utf8mb4_unicode_520_ci của bảng): "mun" ra "mụn", "khỏe" ra "khoẻ".
 *
 * @param string $post_type Post type.
 * @param string $q         Từ khoá đã làm sạch.
 * @param int    $limit     Số bài tối đa.
 * @param array  $lang_args Tham số ngôn ngữ (isha_live_search_language_args()).
 * @return WP_Post[]
 */
function isha_live_search_query( $post_type, $q, $limit, array $lang_args ) {
	global $wpdb;

	$filter = static function ( $clauses ) use ( $wpdb, $q ) {
		$like              = '%' . $wpdb->esc_like( $q ) . '%';
		$prefix            = $wpdb->esc_like( $q ) . '%';
		$clauses['where'] .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", $like );
		$clauses['orderby'] = $wpdb->prepare( "CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN 0 ELSE 1 END, {$wpdb->posts}.post_title ASC", $prefix );
		return $clauses;
	};

	add_filter( 'posts_clauses', $filter );
	try {
		$query = new WP_Query(
			array_merge(
				array(
					'post_type'              => $post_type,
					'post_status'            => 'publish',
					'posts_per_page'         => $limit,
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'has_password'           => false,
					'suppress_filters'       => false,
					'update_post_meta_cache' => true,
					'update_post_term_cache' => true,
				),
				$lang_args
			)
		);
	} finally {
		remove_filter( 'posts_clauses', $filter );
	}

	return $query->posts;
}

/**
 * AJAX: lấy nonce mới khi trang được phục vụ từ bộ nhớ đệm và nonce in sẵn đã hết hạn (JS chỉ gọi khi tìm kiếm báo lỗi nonce).
 */
function isha_live_search_nonce() {
	nocache_headers();
	wp_send_json_success( array( 'nonce' => wp_create_nonce( 'isha_live_search' ) ) );
}
add_action( 'wp_ajax_isha_live_search_nonce', 'isha_live_search_nonce' );
add_action( 'wp_ajax_nopriv_isha_live_search_nonce', 'isha_live_search_nonce' );

/**
 * AJAX: tìm kiếm. Nhận q (từ khoá), lang (ngôn ngữ của trang), nonce. Trả { groups: [{key,label,count,items:[{title,url,thumb,badge}]}], total }.
 */
function isha_live_search_ajax() {
	if ( ! check_ajax_referer( 'isha_live_search', 'nonce', false ) ) {
		wp_send_json_error( array( 'code' => 'nonce' ), 403 );
	}

	$q    = isset( $_POST['q'] ) ? trim( sanitize_text_field( wp_unslash( (string) $_POST['q'] ) ) ) : '';
	$q    = function_exists( 'mb_substr' ) ? mb_substr( $q, 0, 80 ) : substr( $q, 0, 80 );
	$lang = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( (string) $_POST['lang'] ) ) : '';

	if ( '' === $q ) {
		wp_send_json_success(
			array(
				'groups' => array(),
				'total'  => 0,
			)
		);
	}

	$limit     = max( 1, min( 12, (int) apply_filters( 'isha/live_search/per_group', 6 ) ) );
	$lang_args = isha_live_search_language_args( substr( $lang, 0, 12 ) );
	$groups    = array();
	$total     = 0;

	foreach ( isha_live_search_groups() as $key => $group ) {
		if ( ! post_type_exists( $group['post_type'] ) ) {
			continue;
		}
		$items = array();
		foreach ( isha_live_search_query( $group['post_type'], $q, $limit, $lang_args ) as $post ) {
			$items[] = array(
				'title' => wp_strip_all_tags( html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ) ),
				'url'   => esc_url_raw( get_permalink( $post ) ),
				'thumb' => esc_url_raw( isha_live_search_thumb( $post ) ),
				'badge' => isha_live_search_badge( $post, $group['taxonomy'] ),
			);
		}
		if ( ! $items ) {
			continue;
		}
		$groups[] = array(
			'key'   => $key,
			'label' => $group['label'],
			'count' => count( $items ),
			'items' => $items,
		);
		$total   += count( $items );
	}

	nocache_headers();
	wp_send_json_success(
		array(
			'groups' => $groups,
			'total'  => $total,
		)
	);
}
add_action( 'wp_ajax_isha_live_search', 'isha_live_search_ajax' );
add_action( 'wp_ajax_nopriv_isha_live_search', 'isha_live_search_ajax' );

/**
 * Nạp live-search.css (mọi trang có nút: nút nằm trên header nên CSS phải có sẵn) và live-search.js (defer), kèm cấu hình.
 */
function isha_live_search_assets() {
	if ( ! isha_live_search_enabled() ) {
		return;
	}

	$css = ISHA_DIR . '/assets/css/live-search.css';
	$js  = ISHA_DIR . '/assets/js/live-search.js';

	if ( is_file( $css ) ) {
		wp_enqueue_style( 'isha-live-search', ISHA_URI . '/assets/css/live-search.css', array( 'isha-main' ), (string) filemtime( $css ) );
	}
	if ( ! is_file( $js ) ) {
		return;
	}
	wp_enqueue_script(
		'isha-live-search',
		ISHA_URI . '/assets/js/live-search.js',
		array(),
		(string) filemtime( $js ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	$config = array(
		'ajax'     => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'isha_live_search' ),
		'fallback' => ISHA_URI . '/assets/images/vector-1.svg',
		'icons'    => isha_live_search_group_icons(),
		'strings'  => array(
			'empty'   => __( 'Không tìm thấy kết quả phù hợp cho «%s»', 'isha' ),
			'count'   => __( '%d kết quả', 'isha' ),
			'error'   => __( 'Chưa tải được kết quả. Vui lòng thử lại.', 'isha' ),
			'loading' => __( 'Đang tìm…', 'isha' ),
		),
	);
	wp_add_inline_script( 'isha-live-search', 'window.ISHA=window.ISHA||{};window.ISHA.search=' . wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'isha_live_search_assets', 30 );

/**
 * In popup ở chân trang.
 */
function isha_live_search_print_dialog() {
	if ( isha_live_search_enabled() ) {
		get_template_part( 'template-parts/search/live-search-dialog' );
	}
}
add_action( 'wp_footer', 'isha_live_search_print_dialog', 6 );
