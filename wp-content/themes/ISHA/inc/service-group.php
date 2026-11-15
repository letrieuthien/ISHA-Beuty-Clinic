<?php
/**
 * Trang nhóm dịch vụ (taxonomy-isha_service_cat.php): ba section nằm giữa hero và CTA (giới thiệu, hiệu quả, danh sách liệu trình).
 *
 *   Các liệu trình trong nhóm – thẻ editorial (ảnh 4:5, tên Garamond, thời gian, mô tả 2 dòng), cả thẻ là một liên kết; số cột theo
 *                         số liệu trình (template-parts/service-group/list.php). Chữ giữ chỗ trong dữ liệu bị ẩn.
 *
 *   Giới thiệu dịch vụ  – nội dung giới thiệu của nhóm (term meta _isha_intro, soạn bằng trình soạn thảo gọn trong CMS). Bố cục hai cột
 *                         như section #story của trang chủ: cột trái là tiêu đề cố định, cột phải là nội dung (đoạn đầu cỡ lớn).
 *   Hiệu quả liệu trình – tiêu đề cố định, mô tả ngắn (_isha_ba_desc), khung kéo so sánh ảnh trước/sau (cặp đầu tiên khi tải trang) và dải
 *                         thẻ ảnh ghép của các cặp (bấm thẻ thì khung so sánh đổi sang cặp đó). Ảnh lấy từ _isha_ba_before / _isha_ba_after.
 *
 * Dữ liệu đọc qua ISHA_Core_Term_Fields::get() (plugin ISHA Core). Section nào thiếu dữ liệu thì không in (không có nội dung giới thiệu,
 * không có cặp ảnh hợp lệ). Markup ở template-parts/service-group/{intro,results}.php; hai section bọc trong div.isha-home để dùng hệ
 * thiết kế của trang chủ (home.css: tông màu, chữ, pager). Phần riêng ở assets/css/service-group.css và assets/js/service-group.js.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dữ liệu dựng sẵn cho hai section của một nhóm dịch vụ. Nhớ kết quả theo term trong request (gọi ở cả hook nạp tài nguyên và template).
 *
 * @param WP_Term $term Nhóm dịch vụ.
 * @return array{name:string,intro:string,ba_desc:string,pairs:array} `intro` là HTML đã lọc và qua wpautop (rỗng nếu không có chữ);
 *         `pairs` là danh sách array( 'before' => ID ảnh, 'after' => ID ảnh ), chỉ gồm cặp đủ hai ảnh hợp lệ, giữ thứ tự trong CMS.
 */
function isha_service_group_data( WP_Term $term ) {
	static $cache = array();

	$term_id = (int) $term->term_id;
	if ( isset( $cache[ $term_id ] ) ) {
		return $cache[ $term_id ];
	}

	$fields = array(
		'intro'     => '',
		'ba_desc'   => '',
		'ba_before' => array(),
		'ba_after'  => array(),
	);
	if ( class_exists( 'ISHA_Core_Term_Fields' ) && is_callable( array( 'ISHA_Core_Term_Fields', 'get' ) ) ) {
		$fields = array_merge( $fields, ISHA_Core_Term_Fields::get( $term_id ) );
	}

	// Nội dung giới thiệu: lọc lại theo đúng bảng thẻ của trình soạn thảo (plugin cũ thiếu hàm thì dùng bảng của bài viết), rồi wpautop
	// vì TinyMCE lưu nội dung không có <p> giống bài viết.
	$intro = is_string( $fields['intro'] ) ? $fields['intro'] : '';
	if ( '' !== trim( $intro ) ) {
		$allowed = is_callable( array( 'ISHA_Core_Term_Fields', 'intro_allowed_html' ) ) ? ISHA_Core_Term_Fields::intro_allowed_html() : 'post';
		$intro   = trim( wpautop( wp_kses( $intro, $allowed ) ) );
		if ( '' === trim( str_replace( array( '&nbsp;', "\xC2\xA0" ), ' ', wp_strip_all_tags( $intro ) ) ) ) {
			$intro = '';
		}
	}

	// Cặp ảnh: ảnh thứ n của hai danh sách là một cặp; cặp có một phía hỏng (ID 0, không còn là ảnh) thì bỏ, các cặp sau giữ nguyên vị trí.
	$before = is_array( $fields['ba_before'] ) ? array_values( $fields['ba_before'] ) : array();
	$after  = is_array( $fields['ba_after'] ) ? array_values( $fields['ba_after'] ) : array();
	$pairs  = array();
	$count  = min( count( $before ), count( $after ) );
	for ( $i = 0; $i < $count; $i++ ) {
		$b = is_numeric( $before[ $i ] ) ? (int) $before[ $i ] : 0;
		$a = is_numeric( $after[ $i ] ) ? (int) $after[ $i ] : 0;
		if ( $b > 0 && $a > 0 && wp_attachment_is_image( $b ) && wp_attachment_is_image( $a ) ) {
			$pairs[] = array(
				'before' => $b,
				'after'  => $a,
			);
		}
	}

	$cache[ $term_id ] = array(
		'name'    => trim( wp_strip_all_tags( html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ) ) ),
		'intro'   => $intro,
		'ba_desc' => is_string( $fields['ba_desc'] ) ? trim( $fields['ba_desc'] ) : '',
		'pairs'   => $pairs,
	);
	return $cache[ $term_id ];
}

/**
 * Thẻ img dựng sẵn của các cặp trước/sau cho khung so sánh (và thẻ nhỏ của dải "Các ca điều trị" nếu cần). Cặp có ảnh không dùng được thì
 * bỏ. Dùng chung cho section "Hiệu quả liệu trình" và khung so sánh ở cột trái của "Giới thiệu dịch vụ" (desktop).
 *
 * @param array  $data   Kết quả của isha_service_group_data().
 * @param string $sizes  Thuộc tính sizes của ảnh lớn.
 * @param bool   $thumbs Có dựng thêm ảnh thẻ nhỏ (thumb_before, thumb_after) không.
 * @return array Danh sách array( 'before', 'after'[, 'thumb_before', 'thumb_after'] ) là HTML đã escape.
 */
function isha_service_group_ba_images( array $data, $sizes, $thumbs = false ) {
	$name  = isset( $data['name'] ) ? (string) $data['name'] : '';
	$pairs = ( isset( $data['pairs'] ) && is_array( $data['pairs'] ) ) ? array_values( $data['pairs'] ) : array();
	$large = array(
		'size'  => 'isha-frame',
		'sizes' => (string) $sizes,
		'class' => 'isha-sg-ba__img',
	);
	$small = array(
		'size'       => 'isha-portrait',
		'sizes'      => '(max-width: 600px) 46vw, (max-width: 1024px) 16vw, 150px',
		'class'      => 'isha-sg-card__img',
		'decorative' => true,
	);

	$out = array();
	foreach ( $pairs as $pair ) {
		if ( ! is_array( $pair ) ) {
			continue;
		}
		$no   = count( $out ) + 1;
		$b_id = isset( $pair['before'] ) ? $pair['before'] : 0;
		$a_id = isset( $pair['after'] ) ? $pair['after'] : 0;
		/* translators: 1: tên nhóm dịch vụ, 2: số thứ tự cặp ảnh */
		$before = isha_home_image( $b_id, $large + array( 'alt' => sprintf( __( 'Ảnh trước điều trị, %1$s, cặp %2$d', 'isha' ), $name, $no ) ) );
		/* translators: 1: tên nhóm dịch vụ, 2: số thứ tự cặp ảnh */
		$after = isha_home_image( $a_id, $large + array( 'alt' => sprintf( __( 'Ảnh sau điều trị, %1$s, cặp %2$d', 'isha' ), $name, $no ) ) );
		if ( '' === $before || '' === $after ) {
			continue;
		}
		$item = array(
			'before' => $before,
			'after'  => $after,
		);
		if ( $thumbs ) {
			$item['thumb_before'] = isha_home_image( $b_id, $small );
			$item['thumb_after']  = isha_home_image( $a_id, $small );
		}
		$out[] = $item;
	}
	return $out;
}

/**
 * Icon FOCUS-MINDFUL (bộ nhận diện ISHA) cho nút kéo của khung so sánh, đọc từ assets/images/vector-1.svg: chỉ lấy viewBox và các phần tử
 * <line> (toạ độ là số, được làm sạch), bỏ mọi thuộc tính khác để tô lại bằng gradient vàng kim trong compare.php. Thay tệp SVG là icon đổi
 * theo. Nhớ kết quả trong request.
 *
 * @return array{viewbox:string,lines:string} lines là chuỗi <line .../> đã dựng lại; rỗng nếu tệp thiếu hoặc không có line.
 */
function isha_service_group_handle_icon() {
	static $icon = null;
	if ( null !== $icon ) {
		return $icon;
	}
	$icon = array(
		'viewbox' => '0 0 200 200',
		'lines'   => '',
	);
	$file = ISHA_DIR . '/assets/images/vector-1.svg';
	$svg  = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- tệp cục bộ của theme.
	if ( '' === $svg ) {
		return $icon;
	}
	if ( preg_match( '/viewBox="([0-9.\s-]+)"/i', $svg, $m ) ) {
		$icon['viewbox'] = trim( preg_replace( '/\s+/', ' ', $m[1] ) );
	}
	if ( preg_match_all( '/<line\b([^>]*)\/?>/i', $svg, $all ) ) {
		foreach ( $all[1] as $attrs ) {
			$p = array();
			foreach ( array( 'x1', 'y1', 'x2', 'y2' ) as $k ) {
				$p[ $k ] = preg_match( '/\b' . $k . '="(-?[0-9.]+)"/', $attrs, $v ) ? (float) $v[1] : 0.0;
			}
			$icon['lines'] .= sprintf( '<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f"/>', $p['x1'], $p['y1'], $p['x2'], $p['y2'] );
		}
	}
	return $icon;
}

/**
 * Tách nội dung giới thiệu (HTML đã qua wpautop) thành đoạn mở đầu và phần còn lại, để đoạn mở đầu nằm hàng trên (ngang tiêu đề) và
 * phần còn lại nằm hàng dưới (ngang khung so sánh ở desktop). Nội dung không mở đầu bằng một đoạn <p> thì không có đoạn mở đầu.
 *
 * @param string $html Nội dung giới thiệu.
 * @return array array( string lead, string rest ).
 */
function isha_service_group_split_intro( $html ) {
	$html = trim( (string) $html );
	if ( preg_match( '#^(<p(?:\s[^>]*)?>.*?</p>)\s*(.*)$#is', $html, $m ) ) {
		return array( trim( $m[1] ), trim( $m[2] ) );
	}
	return array( '', $html );
}

/**
 * Chữ giữ chỗ còn sót từ dữ liệu khởi tạo ("Mô tả ngắn của mục này sẽ được cập nhật sau.", "Thời lượng"...): coi như trống để thẻ
 * không in câu chưa hoàn thiện ra trang công khai.
 *
 * @param string $text Văn bản thuần.
 * @return bool
 */
function isha_service_group_is_placeholder( $text ) {
	$text = trim( (string) $text );
	if ( '' === $text ) {
		return true;
	}
	if ( preg_match( '/(sẽ được cập nhật|đang cập nhật|cập nhật sau|lorem ipsum)/iu', $text ) ) {
		return true;
	}
	return in_array( mb_strtolower( rtrim( $text, ' .:' ), 'UTF-8' ), array( 'thời lượng', 'thời gian', 'thời gian liệu trình', 'mô tả ngắn' ), true );
}

/**
 * Các liệu trình (bài isha_service đã xuất bản) của một nhóm, theo menu_order rồi tên. Nhớ kết quả theo term trong request.
 *
 * @param WP_Term $term Nhóm dịch vụ.
 * @return array Danh sách array( 'id', 'title', 'url', 'desc', 'duration', 'thumb' ); desc/duration là '' khi trống hoặc là chữ giữ chỗ,
 *               thumb là ảnh đại diện (chưa có thì ảnh đầu của "Ảnh liệu trình", không có thì 0).
 */
function isha_service_group_items( WP_Term $term ) {
	static $cache = array();

	$term_id = (int) $term->term_id;
	if ( isset( $cache[ $term_id ] ) ) {
		return $cache[ $term_id ];
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'isha_service',
			'post_status'            => 'publish',
			'posts_per_page'         => 48,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
				'ID'         => 'ASC',
			),
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy'         => 'isha_service_cat',
					'field'            => 'term_id',
					'terms'            => array( $term_id ),
					'include_children' => false,
				),
			),
		)
	);

	$items = array();
	foreach ( $query->posts as $post ) {
		$title = trim( wp_strip_all_tags( html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ) ) );
		if ( '' === $title ) {
			continue;
		}
		$fields = class_exists( 'ISHA_Core_Service_Fields' ) ? ISHA_Core_Service_Fields::get( $post->ID ) : array();
		$locked = post_password_required( $post );
		$desc   = ( ! $locked && isset( $fields['short_desc'] ) ) ? trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $fields['short_desc'] ) ) ) : '';
		$dur    = ( ! $locked && isset( $fields['duration'] ) ) ? trim( wp_strip_all_tags( (string) $fields['duration'] ) ) : '';
		$thumb  = (int) get_post_thumbnail_id( $post );
		if ( ! $thumb && ! empty( $fields['gallery'] ) ) {
			$thumb = (int) $fields['gallery'][0];
		}

		$items[] = array(
			'id'       => (int) $post->ID,
			'title'    => $title,
			'url'      => (string) get_permalink( $post ),
			'desc'     => isha_service_group_is_placeholder( $desc ) ? '' : $desc,
			'duration' => isha_service_group_is_placeholder( $dur ) ? '' : $dur,
			'thumb'    => $thumb > 0 && wp_attachment_is_image( $thumb ) ? $thumb : 0,
		);
	}

	$cache[ $term_id ] = $items;
	return $items;
}

/**
 * Nhóm dịch vụ có ít nhất một section (giới thiệu, hiệu quả, danh sách liệu trình) để in không.
 *
 * @param array   $data Kết quả của isha_service_group_data().
 * @param WP_Term $term Nhóm dịch vụ (để xét danh sách liệu trình; bỏ trống thì chỉ xét hai section đầu).
 * @return bool
 */
function isha_service_group_has_sections( array $data, $term = null ) {
	if ( '' !== $data['intro'] || ! empty( $data['pairs'] ) ) {
		return true;
	}
	return $term instanceof WP_Term && (bool) isha_service_group_items( $term );
}

/**
 * In các section của nhóm dịch vụ (giới thiệu, hiệu quả liệu trình, danh sách liệu trình) trong div.isha-home. Section thiếu dữ liệu
 * thì bỏ; không có section nào thì không in gì.
 *
 * @param WP_Term $term Nhóm dịch vụ đang xem.
 */
function isha_service_group_sections( WP_Term $term ) {
	$data = isha_service_group_data( $term );
	if ( ! isha_service_group_has_sections( $data, $term ) ) {
		return;
	}
	echo '<div class="isha-home isha-sg">' . "\n";
	get_template_part( 'template-parts/service-group/intro', null, $data );
	get_template_part( 'template-parts/service-group/results', null, $data );
	get_template_part(
		'template-parts/service-group/list',
		null,
		array(
			'name'  => $data['name'],
			'items' => isha_service_group_items( $term ),
		)
	);
	echo "</div>\n";
}

/**
 * Nạp tài nguyên ở trang nhóm dịch vụ có ít nhất một section: home.css (hệ thiết kế, chỉ CSS: không cần lõi chuyển động và home.js),
 * service-group.css (trong <head>) và service-group.js (defer, không phụ thuộc thư viện). Tệp chưa có thì bỏ qua.
 */
function isha_service_group_assets() {
	if ( ! is_tax( 'isha_service_cat' ) ) {
		return;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term || ! isha_service_group_has_sections( isha_service_group_data( $term ), $term ) ) {
		return;
	}

	$home = ISHA_DIR . '/assets/css/home.css';
	$css  = ISHA_DIR . '/assets/css/service-group.css';
	$js   = ISHA_DIR . '/assets/js/service-group.js';

	if ( is_file( $home ) && ! wp_style_is( 'isha-home', 'enqueued' ) ) {
		wp_enqueue_style( 'isha-home', ISHA_URI . '/assets/css/home.css', array( 'isha-main' ), (string) filemtime( $home ) );
	}
	if ( is_file( $css ) ) {
		wp_enqueue_style(
			'isha-service-group',
			ISHA_URI . '/assets/css/service-group.css',
			wp_style_is( 'isha-home', 'enqueued' ) ? array( 'isha-main', 'isha-home' ) : array( 'isha-main' ),
			(string) filemtime( $css )
		);
	}
	if ( is_file( $js ) ) {
		wp_enqueue_script(
			'isha-service-group',
			ISHA_URI . '/assets/js/service-group.js',
			array(),
			(string) filemtime( $js ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'isha_service_group_assets', 20 );
