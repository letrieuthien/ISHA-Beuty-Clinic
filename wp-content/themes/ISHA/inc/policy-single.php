<?php
/**
 * Trang chi tiết Chính sách (isha_policy): dữ liệu cho hai màn hình đầu.
 *
 *   Màn 1 (hero): thẻ kính đứng như một cổng giữa cảnh sóng; bên trong kính có ảnh thẻ của bài, làm mờ.
 *   Màn 2: ảnh thẻ của bài nổi giữa hai dòng tiêu đề; nút "Mặt sau" lật thẻ.
 * Tất cả chữ lấy từ chính bài viết (tiêu đề, câu đầu, các câu còn lại của đoạn mở đầu), không có nội dung tự đặt thêm.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tách một đoạn văn thành các câu.
 * Số có dấu chấm ngăn nghìn ("500.000.000") không bị cắt vì sau dấu chấm không có khoảng trắng;
 * chỉ cắt khi sau dấu câu là khoảng trắng rồi chữ hoa hoặc chữ số.
 *
 * @param string $text Văn bản thuần.
 * @return string[]
 */
function isha_ps_sentences( $text ) {
	$parts = preg_split( '/(?<=[.!?…])\s+(?=[\p{Lu}\d])/u', trim( (string) $text ), -1, PREG_SPLIT_NO_EMPTY );
	return is_array( $parts ) ? $parts : array();
}

/**
 * Đoạn mở đầu của bài (văn bản thuần, đã gộp khoảng trắng và &nbsp;).
 *
 * @param int $post_id ID bài.
 * @return string
 */
function isha_ps_first_paragraph( $post_id ) {
	$raw = strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) );
	$raw = (string) preg_replace( '/<!--.*?-->/s', '', $raw );

	if ( preg_match( '#<p\b[^>]*>(.*?)</p>#is', $raw, $m ) ) {
		$para = $m[1];
	} else {
		$chunks = preg_split( '/\R\s*\R/u', trim( wp_strip_all_tags( $raw ) ) );
		$para   = $chunks ? $chunks[0] : '';
	}

	$para = html_entity_decode( wp_strip_all_tags( $para ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( (string) preg_replace( '/[\s\x{00A0}]+/u', ' ', $para ) );
}

/**
 * Bọc từng từ trong <span> (để hiện chữ lần lượt). $index tăng dần qua các lần gọi để độ trễ nối tiếp nhau.
 *
 * @param string $text  Văn bản thuần.
 * @param int    $index Số thứ tự từ bắt đầu (truyền tham chiếu).
 * @return string HTML đã escape.
 */
function isha_ps_words( $text, &$index = 0 ) {
	$out   = '';
	$words = preg_split( '/\s+/u', trim( (string) $text ), -1, PREG_SPLIT_NO_EMPTY );
	foreach ( $words ? $words : array() as $word ) {
		$out .= '<span class="isha-ps__w" style="--i:' . (int) $index . '">' . esc_html( $word ) . '</span> ';
		++$index;
	}
	return rtrim( $out );
}

/**
 * Dữ liệu cho hai màn hình đầu của một chính sách.
 *
 * @param int $post_id ID chính sách.
 * @return array
 */
function isha_policy_screen_data( $post_id ) {
	$fields = class_exists( 'ISHA_Core_Policy_Fields' )
		? ISHA_Core_Policy_Fields::get( $post_id )
		: array( 'front' => 0, 'back' => 0, 'color' => '' );

	$title = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );

	// Tách tiêu đề làm hai dòng: "HẠNG" / "PRESIDENT ELITE". Tiêu đề không bắt đầu bằng "Hạng" thì chia đôi theo số từ;
	// một từ thì chỉ có một dòng.
	$words = preg_split( '/\s+/u', $title, -1, PREG_SPLIT_NO_EMPTY );
	$words = $words ? $words : array();
	$count = count( $words );
	if ( $count >= 2 ) {
		// WordPress chỉ có sẵn bản thay thế cho mb_strlen, không có cho mb_strtolower → kiểm tra trước khi gọi.
		$first = function_exists( 'mb_strtolower' ) ? mb_strtolower( $words[0] ) : strtolower( $words[0] );
		$cut   = ( 'hạng' === $first ) ? 1 : (int) ceil( $count / 2 );
		$line1 = implode( ' ', array_slice( $words, 0, $cut ) );
		$line2 = implode( ' ', array_slice( $words, $cut ) );
	} else {
		$line1 = '';
		$line2 = $title;
	}

	// Lời dẫn: tóm tắt nếu có, không thì câu đầu của đoạn mở đầu. Chữ màn 2: các câu còn lại của đoạn đó.
	$para    = isha_ps_first_paragraph( $post_id );
	$excerpt = has_excerpt( $post_id ) ? trim( wp_strip_all_tags( get_the_excerpt( $post_id ) ) ) : '';
	if ( '' !== $excerpt ) {
		$lead = $excerpt;
		$rest = $para;
	} else {
		$sentences = isha_ps_sentences( $para );
		$lead      = $sentences ? $sentences[0] : '';
		$rest      = implode( ' ', array_slice( $sentences, 1 ) );
	}

	// Logo của site, dùng làm "chữ khổng lồ" mờ phía sau (mask theo kênh alpha nên logo trắng hay đen đều dùng được).
	$logo    = null;
	$logo_id = absint( get_theme_mod( 'custom_logo' ) );
	if ( $logo_id ) {
		$src = wp_get_attachment_image_src( $logo_id, 'full' );
		if ( $src && ! empty( $src[1] ) && ! empty( $src[2] ) ) {
			$logo = array(
				'url'   => $src[0],
				'ratio' => round( $src[1] / $src[2], 4 ),
			);
		}
	}

	return array(
		'id'       => (int) $post_id,
		'title'    => $title,
		'line1'    => $line1,
		'line2'    => $line2,
		'lead'     => wp_trim_words( $lead, 40, '…' ),
		'text'     => wp_trim_words( $rest, 38, '…' ),
		'front'    => (int) $fields['front'],
		'back'     => (int) $fields['back'],
		'color'    => (string) $fields['color'],
		'logo'     => $logo,
		'eyebrow'  => __( 'Chính sách khách hàng', 'isha' ),
		'all_url'  => isha_policies_url(),
		// Chế độ landing (giai đoạn 1) ẩn nút đặt lịch trên toàn site → màn 2 cũng không có nút đặt lịch.
		'book_url' => isha_landing_only() ? '' : isha_booking_url(),
	);
}
