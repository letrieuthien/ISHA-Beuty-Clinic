<?php
/**
 * Hàm in markup dùng chung của trang chủ điện ảnh và isha_home_render().
 *
 * Quy ước in/trả (ba nhóm):
 *   - TRẢ VỀ chuỗi HTML đã escape, không bao giờ in: isha_home_image( $id, $args ). Template viết `echo isha_home_image( ... );`.
 *     isha_home_video_url/isha_home_video_mime trả URL/mime thô (template tự esc_url khi in); isha_home_title_id trả chuỗi id.
 *   - IN thẳng ra màn hình, không trả gì: isha_home_section_open/close, isha_home_button, isha_home_pager (isha_home_row_controls là tên cũ), isha_home_render.
 *   - IN mặc định như các hàm template của WordPress, và trả chuỗi khi truyền echo = false: isha_home_media, isha_home_placeholder,
 *     isha_home_wordmark (khoá `echo` trong $args), isha_home_p (đối số thứ ba $echo). Mặc định in và trả '' nên cả gọi trần
 *     `isha_home_media( ... );` lẫn `echo isha_home_media( ... );` đều ra đúng một lần; cần chuỗi (ghép, so sánh) thì đặt echo = false.
 * Hợp đồng ở docs/home-architecture.md, mục 5.3 và 5.6.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cờ "đang render trong phim" (isha_home_render() đang chạy). Shortcode dùng cờ này để biết có cần bọc `isha-home--embed`
 * và nạp tài nguyên hay không.
 *
 * @param bool|null $set Truyền true/false để đặt cờ; bỏ trống (null) để chỉ đọc.
 * @return bool Giá trị cờ hiện tại (sau khi đặt, nếu có truyền).
 */
function isha_home_in_film( $set = null ) {
	static $in_film = false;

	if ( null !== $set ) {
		$in_film = (bool) $set;
	}
	return $in_film;
}

/**
 * Chuỗi class an toàn từ một chuỗi (hoặc mảng) class: mỗi class đi qua sanitize_html_class, bỏ trùng.
 *
 * @param string|array $classes Các class cách nhau bằng khoảng trắng, hoặc mảng.
 * @return string Rỗng nếu không có class hợp lệ.
 */
function isha_home_classes( $classes ) {
	if ( is_array( $classes ) ) {
		$classes = implode( ' ', array_filter( $classes, 'is_scalar' ) );
	}
	if ( ! is_scalar( $classes ) ) {
		return '';
	}

	$out = array();
	foreach ( preg_split( '/\s+/', trim( (string) $classes ), -1, PREG_SPLIT_NO_EMPTY ) as $class ) {
		$class = sanitize_html_class( $class );
		if ( '' !== $class ) {
			$out[] = $class;
		}
	}
	return implode( ' ', array_unique( $out ) );
}

/**
 * Thông tin một section từ registry: lấy ở registry đã qua bộ lọc, nếu bị bộ lọc bỏ thì lấy ở registry gốc (để shortcode
 * nhúng ở trang khác vẫn đúng id, class, tông); mã lạ thì suy ra từ chính mã.
 *
 * @param string $id Mã section (đã qua sanitize_key).
 * @return array Đủ khoá tag, template, tone, class, scene.
 */
function isha_home_section_info( $id ) {
	$sections = isha_home_sections();
	if ( isset( $sections[ $id ] ) && is_array( $sections[ $id ] ) ) {
		$info = $sections[ $id ];
	} else {
		$base = isha_home_sections_base();
		$info = isset( $base[ $id ] ) ? $base[ $id ] : array();
	}

	return $info + array(
		'tag'      => 'isha_home_' . $id,
		'template' => 'template-parts/home/' . $id,
		'tone'     => '',
		'class'    => 'isha-' . $id,
		'scene'    => $id,
	);
}

/**
 * Id của tiêu đề một section (dùng cho `aria-labelledby` và `id` của h1/h2).
 *
 * @param string $section Mã section.
 * @return string Dạng `isha-<id>-title`.
 */
function isha_home_title_id( $section ) {
	return 'isha-' . ( is_scalar( $section ) ? sanitize_key( $section ) : '' ) . '-title';
}

/**
 * In thẻ mở của một section: id, class, tông và cảnh lấy từ registry.
 *
 * @param string       $section     Mã section.
 * @param string|array $extra_class Class thêm (tuỳ chọn).
 */
function isha_home_section_open( $section, $extra_class = '' ) {
	$id   = is_scalar( $section ) ? sanitize_key( $section ) : '';
	$info = isha_home_section_info( $id );

	$classes = trim( 'isha-sec ' . isha_home_classes( $info['class'] ) . ' ' . isha_home_classes( $extra_class ) );

	$html  = '<section id="' . esc_attr( $id ) . '" class="' . esc_attr( $classes ) . '"';
	$html .= ' data-isha-scene="' . esc_attr( $info['scene'] ) . '"';
	if ( '' !== (string) $info['tone'] ) {
		$html .= ' data-tone="' . esc_attr( $info['tone'] ) . '"';
	}
	$html .= ' aria-labelledby="' . esc_attr( isha_home_title_id( $id ) ) . '">';

	echo $html . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- mọi giá trị đã qua esc_attr ở trên.
}

/**
 * In thẻ đóng của một section.
 */
function isha_home_section_close() {
	echo "</section>\n";
}

/**
 * Ảnh responsive từ một tệp đính kèm, qua wp_get_attachment_image (srcset và sizes do WordPress dựng).
 *
 * Chịu được tệp đính kèm thiếu metadata (không có srcset, width, height; giữ tỉ lệ bằng class tỉ lệ ở CSS) và loại bỏ
 * `sizes` khi không có `srcset` để HTML hợp lệ. Luôn có class `isha-img` và `decoding="async"`.
 *
 * @param int   $id   ID tệp đính kèm.
 * @param array $args {
 *     Tuỳ chọn.
 *
 *     @type string|int[] $size          Tên kích thước hoặc array( w, h ). Mặc định 'large'.
 *     @type string       $sizes         Thuộc tính `sizes`.
 *     @type string       $class         Class thêm.
 *     @type string       $loading       'lazy' (mặc định) hoặc 'eager'.
 *     @type string       $fetchpriority 'high', 'low' hoặc 'auto'; 'high' luôn đi cùng loading eager.
 *     @type string       $alt           Ghi đè alt (mặc định lấy từ thư viện).
 *     @type bool         $decorative    True thì alt rỗng (ảnh trang trí).
 * }
 * @return string Thẻ <img> đã escape, hoặc '' nếu ID không phải ảnh hợp lệ.
 */
function isha_home_image( $id, $args = array() ) {
	if ( ! is_numeric( $id ) || (int) $id < 1 || ! wp_attachment_is_image( (int) $id ) ) {
		return '';
	}
	$args = wp_parse_args(
		is_array( $args ) ? $args : array(),
		array(
			'size'          => 'large',
			'sizes'         => '',
			'class'         => '',
			'loading'       => 'lazy',
			'fetchpriority' => '',
			'alt'           => null,
			'decorative'    => false,
		)
	);

	$size = $args['size'];
	if ( ! ( is_string( $size ) && '' !== $size ) && ! ( is_array( $size ) && 2 === count( $size ) ) ) {
		$size = 'large';
	}

	$priority = in_array( $args['fetchpriority'], array( 'high', 'low', 'auto' ), true ) ? $args['fetchpriority'] : '';
	$loading  = ( 'eager' === $args['loading'] || 'high' === $priority ) ? 'eager' : 'lazy';

	$attr = array(
		'class'    => trim( 'isha-img ' . isha_home_classes( $args['class'] ) ),
		'loading'  => $loading,
		'decoding' => 'async',
	);
	if ( '' !== $priority ) {
		$attr['fetchpriority'] = $priority;
	}
	if ( ! empty( $args['decorative'] ) ) {
		$attr['alt'] = '';
	} elseif ( is_scalar( $args['alt'] ) ) {
		$attr['alt'] = (string) $args['alt'];
	}
	if ( is_string( $args['sizes'] ) && '' !== trim( $args['sizes'] ) ) {
		$attr['sizes'] = trim( $args['sizes'] );
	}

	$html = wp_get_attachment_image( (int) $id, $size, false, $attr );
	if ( '' === $html ) {
		return '';
	}

	// `sizes` chỉ hợp lệ khi có `srcset`; tệp thiếu metadata không có srcset.
	if ( false === strpos( $html, ' srcset="' ) ) {
		$html = (string) preg_replace( '/\s+sizes="[^"]*"/', '', $html, 1 );
	}
	return $html;
}

/**
 * In hoặc trả một đoạn HTML đã escape (hàm nội bộ cho các hàm có tuỳ chọn echo).
 *
 * @param string $html HTML đã dựng và đã escape.
 * @param bool   $echo True để in (và trả ''), false để trả chuỗi.
 * @return string '' khi đã in; ngược lại là $html.
 */
function isha_home_emit( $html, $echo ) {
	if ( ! $echo ) {
		return $html;
	}
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- $html do các hàm isha_home_* dựng và đã escape.
	return '';
}

/**
 * Khối giữ chỗ trung tính khi thiếu ảnh. Tô theo tông của section (CSS). Nhãn tài nguyên chỉ được in cho người sửa được
 * trang (`edit_pages`: đang xem trước hoặc đã đăng nhập), qua thuộc tính `data-ph-label="..."` mà CSS hiển thị bằng
 * `content: attr(data-ph-label)`; khách chỉ thấy khối màu trơn (thuộc tính rỗng, không có chữ).
 *
 * @param string $label Tên tài nguyên còn thiếu (đã dịch), ví dụ "Poster hero ngang".
 * @param array  $args  Tuỳ chọn: `ratio` ('45', '34', '239', '32', '11' hoặc đủ 'isha-r-45'), `class`,
 *                      `echo` (mặc định true: in và trả ''; false: trả chuỗi).
 * @return string '' khi đã in; khối <div> đã escape khi echo = false.
 */
function isha_home_placeholder( $label = '', $args = array() ) {
	$args    = is_array( $args ) ? $args : array();
	$classes = array( 'isha-ph' );

	$ratio = ( isset( $args['ratio'] ) && is_scalar( $args['ratio'] ) ) ? (string) $args['ratio'] : '';
	if ( preg_match( '/^(?:isha-r-)?([0-9]{1,4})$/', $ratio, $match ) ) {
		$classes[] = 'isha-r-' . $match[1];
	}
	if ( isset( $args['class'] ) ) {
		$extra = isha_home_classes( $args['class'] );
		if ( '' !== $extra ) {
			$classes[] = $extra;
		}
	}

	$label = is_scalar( $label ) ? trim( (string) $label ) : '';
	$attr  = ( '' !== $label && current_user_can( 'edit_pages' ) ) ? ' data-ph-label="' . esc_attr( $label ) . '"' : ' data-ph-label';

	$html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" aria-hidden="true"' . $attr . '></div>';
	return isha_home_emit( $html, ! isset( $args['echo'] ) || $args['echo'] );
}

/**
 * Ảnh nếu có, không thì khối giữ chỗ.
 *
 * @param int    $id    ID tệp đính kèm (0 = không có).
 * @param string $label Nhãn của khối giữ chỗ.
 * @param array  $args  Tuỳ chọn của isha_home_image() và isha_home_placeholder(), cộng `echo` (mặc định true: in và trả '';
 *                      false: trả chuỗi).
 * @return string '' khi đã in; HTML đã escape khi echo = false.
 */
function isha_home_media( $id, $label = '', $args = array() ) {
	$args  = is_array( $args ) ? $args : array();
	$image = isha_home_image( $id, $args );
	if ( '' === $image ) {
		$image = isha_home_placeholder( $label, array_merge( $args, array( 'echo' => false ) ) );
	}
	return isha_home_emit( $image, ! isset( $args['echo'] ) || $args['echo'] );
}

/**
 * Mime của một tệp video đính kèm.
 *
 * @param int $id ID tệp đính kèm.
 * @return string Ví dụ `video/mp4`; '' nếu không phải tệp đính kèm video.
 */
function isha_home_video_mime( $id ) {
	if ( ! is_numeric( $id ) || (int) $id < 1 || 'attachment' !== get_post_type( (int) $id ) ) {
		return '';
	}
	$mime = get_post_mime_type( (int) $id );
	return ( is_string( $mime ) && 0 === strpos( $mime, 'video/' ) ) ? $mime : '';
}

/**
 * URL của một tệp video đính kèm.
 *
 * @param int $id ID tệp đính kèm.
 * @return string URL thô (template tự esc_url khi in); '' nếu không phải tệp đính kèm video.
 */
function isha_home_video_url( $id ) {
	if ( '' === isha_home_video_mime( $id ) ) {
		return '';
	}
	$url = wp_get_attachment_url( (int) $id );
	return is_string( $url ) ? $url : '';
}

/**
 * Nội dung SVG logo chữ từ tệp trong theme (assets/images/isha-wordmark.svg), bỏ phần khai báo XML, DOCTYPE và chú thích.
 * Chỉ đọc tệp nằm trong thư mục theme. Kết quả được nhớ lại trong suốt request.
 *
 * @return string Chuỗi bắt đầu bằng `<svg`; '' nếu không có tệp hoặc tệp không phải SVG.
 */
function isha_home_wordmark_file() {
	static $cache = array();

	$path = get_template_directory() . '/assets/images/isha-wordmark.svg';
	$key  = is_file( $path ) ? $path . '|' . filemtime( $path ) : $path; // Tệp được thêm hoặc sửa thì đọc lại.
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$cache[ $key ] = '';
	if ( is_file( $path ) && is_readable( $path ) && filesize( $path ) < 262144 ) {
		$svg = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- tệp cục bộ trong theme.
		if ( is_string( $svg ) ) {
			$svg = (string) preg_replace( array( '/^\xEF\xBB\xBF/', '/<\?xml.*?\?>/s', '/<!DOCTYPE[^>]*>/i', '/<!--.*?-->/s' ), '', $svg );
			$svg = trim( $svg );
			if ( 0 === stripos( $svg, '<svg' ) ) {
				$cache[ $key ] = $svg;
			}
		}
	}
	return $cache[ $key ];
}

/**
 * Logo chữ ISHA nội tuyến (SVG), luôn `aria-hidden="true"` (trang trí; tên thương hiệu đã có ở logo và tiêu đề).
 *
 * Có tệp assets/images/isha-wordmark.svg trong theme thì nội tuyến tệp đó (mỗi chữ cái một path, id isha-i, isha-s, isha-h,
 * isha-a). Chưa có tệp thì dựng bản dự phòng gồm 4 phần tử <text> cùng các id đó; font Garamond do CSS quyết định qua class
 * `isha-wordmark__letter`. Không tạo tệp SVG giả logo.
 *
 * @param array $args {
 *     Tuỳ chọn.
 *
 *     @type string $class Class thêm cho thẻ <svg>.
 *     @type bool   $ids   False để bỏ các id isha-i/s/h/a (tránh trùng id khi đặt logo nhiều lần). Mặc định true.
 *     @type bool   $echo  Mặc định true: in và trả ''; false: trả chuỗi.
 * }
 * @return string '' khi đã in; thẻ <svg> đã dựng khi echo = false.
 */
function isha_home_wordmark( $args = array() ) {
	$args = wp_parse_args(
		is_array( $args ) ? $args : array(),
		array(
			'class' => '',
			'ids'   => true,
			'echo'  => true,
		)
	);

	return isha_home_emit( isha_home_wordmark_markup( $args ), (bool) $args['echo'] );
}

/**
 * Dựng thẻ <svg> logo chữ (hàm nội bộ của isha_home_wordmark()).
 *
 * @param array $args Tuỳ chọn đã có đủ khoá class và ids.
 * @return string
 */
function isha_home_wordmark_markup( array $args ) {
	$classes = trim( 'isha-wordmark ' . isha_home_classes( $args['class'] ) );
	$source  = isha_home_wordmark_file();

	if ( '' !== $source && ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		// WordPress trước 6.2 (theme khai báo hỗ trợ từ 6.0): sửa thẻ gốc bằng regex, bỏ class riêng của tệp.
		$source = (string) preg_replace_callback(
			'/^<svg\b([^>]*)>/i',
			static function ( $match ) use ( $classes ) {
				$attrs = (string) preg_replace( '/\s(?:class|aria-hidden|focusable|role|aria-label|aria-labelledby)\s*=\s*(?:"[^"]*"|\'[^\']*\')/i', '', $match[1] );
				return '<svg' . $attrs . ' class="' . esc_attr( $classes ) . '" aria-hidden="true" focusable="false">';
			},
			$source,
			1
		);
		return empty( $args['ids'] ) ? (string) preg_replace( '/\sid="isha-(?:i|s|h|a)"/', '', $source ) : $source;
	}

	if ( '' !== $source ) {
		$processor = new WP_HTML_Tag_Processor( $source );
		$is_root   = true;
		while ( $processor->next_tag() ) {
			if ( $is_root ) {
				$is_root = false;
				$old     = $processor->get_attribute( 'class' );
				$processor->set_attribute( 'class', trim( ( is_string( $old ) ? $old . ' ' : '' ) . $classes ) );
				$processor->set_attribute( 'aria-hidden', 'true' );
				$processor->set_attribute( 'focusable', 'false' );
				$processor->remove_attribute( 'role' );
				$processor->remove_attribute( 'aria-label' );
				$processor->remove_attribute( 'aria-labelledby' );
			} elseif ( empty( $args['ids'] ) && in_array( $processor->get_attribute( 'id' ), array( 'isha-i', 'isha-s', 'isha-h', 'isha-a' ), true ) ) {
				$processor->remove_attribute( 'id' );
			}
		}
		return $processor->get_updated_html();
	}

	// Bản dự phòng: bốn chữ cái căn giữa trong bốn ô bằng nhau; cỡ chữ và font do CSS (class isha-wordmark__letter) quyết định.
	$html = '<svg class="' . esc_attr( $classes ) . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 100" aria-hidden="true" focusable="false">';
	foreach ( array( 'i', 's', 'h', 'a' ) as $index => $letter ) {
		$html .= '<text' . ( empty( $args['ids'] ) ? '' : ' id="isha-' . $letter . '"' );
		$html .= ' class="isha-wordmark__letter isha-wordmark__letter--' . $letter . '" x="' . ( 50 + 100 * $index ) . '" y="80" text-anchor="middle" font-size="100">';
		$html .= strtoupper( $letter ) . '</text>';
	}
	return $html . '</svg>';
}

/**
 * Một hoặc nhiều thẻ <p> từ văn bản thuần: dòng trống = đoạn mới, xuống dòng đơn = <br>. Mọi thứ được escape (không nhận HTML).
 *
 * @param string       $text  Văn bản thuần.
 * @param string|array $class Class cho mỗi thẻ <p> (tuỳ chọn).
 * @param bool         $echo  Mặc định true: in và trả ''; false: trả chuỗi.
 * @return string '' khi đã in hoặc văn bản rỗng; HTML đã escape khi echo = false.
 */
function isha_home_p( $text, $class = '', $echo = true ) {
	$out = '';
	if ( is_scalar( $text ) ) {
		$text = trim( str_replace( array( "\r\n", "\r" ), "\n", (string) $text ) );
		if ( '' !== $text ) {
			$class = isha_home_classes( $class );
			$open  = '' !== $class ? '<p class="' . esc_attr( $class ) . '">' : '<p>';
			foreach ( (array) preg_split( '/\n[ \t]*\n/', $text ) as $paragraph ) {
				$paragraph = trim( (string) $paragraph );
				if ( '' !== $paragraph ) {
					$out .= $open . str_replace( "\n", '<br>', esc_html( $paragraph ) ) . "</p>\n";
				}
			}
		}
	}
	return isha_home_emit( $out, (bool) $echo );
}

/**
 * In một nút dạng liên kết `a.isha-btn.isha-btn--<variant>`. Không in gì nếu thiếu chữ hoặc URL không hợp lệ.
 *
 * @param string $label   Chữ trên nút (văn bản thuần, đã dịch).
 * @param string $url     Địa chỉ liên kết (http, https, tel, mailto...); qua esc_url.
 * @param string $variant 'primary' (mặc định) hoặc 'ghost'.
 * @param array  $attrs   Thuộc tính thêm: class, id, target (_blank|_self), rel, title, download, aria-*, data-*.
 *                        Thuộc tính khác (kể cả on*, style, href) bị bỏ. target="_blank" luôn kèm rel="noopener".
 */
function isha_home_button( $label, $url, $variant = 'primary', $attrs = array() ) {
	$label = is_scalar( $label ) ? trim( (string) $label ) : '';
	$href  = is_scalar( $url ) ? esc_url( (string) $url ) : '';
	if ( '' === $label || '' === $href ) {
		return;
	}

	$variant = in_array( $variant, array( 'primary', 'ghost' ), true ) ? $variant : 'primary';
	$classes = 'isha-btn isha-btn--' . $variant;
	$extra   = '';
	$blank   = false;
	$rel     = '';
	$attrs   = is_array( $attrs ) ? $attrs : array();

	foreach ( $attrs as $name => $value ) {
		$name = strtolower( (string) $name );
		if ( 'class' === $name ) {
			$more = isha_home_classes( $value );
			if ( '' !== $more ) {
				$classes .= ' ' . $more;
			}
		} elseif ( 'target' === $name ) {
			$blank = ( '_blank' === $value );
		} elseif ( 'rel' === $name ) {
			$rel = is_scalar( $value ) ? trim( (string) preg_replace( '/[^a-z ]/i', '', (string) $value ) ) : '';
		} elseif ( in_array( $name, array( 'id', 'title', 'download' ), true ) || preg_match( '/^(?:aria|data)-[a-z0-9_-]+$/', $name ) ) {
			if ( true === $value ) {
				$extra .= ' ' . $name;
			} elseif ( is_scalar( $value ) && false !== $value ) {
				$extra .= ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
			}
		}
	}
	if ( $blank ) {
		$extra .= ' target="_blank"';
		$rel    = trim( $rel . ( false === strpos( ' ' . $rel . ' ', ' noopener ' ) ? ' noopener' : '' ) );
	}
	if ( '' !== $rel ) {
		$extra .= ' rel="' . esc_attr( $rel ) . '"';
	}

	echo '<a class="' . esc_attr( $classes ) . '" href="' . $href . '"' . $extra . '>' . esc_html( $label ) . "</a>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- $href qua esc_url, $extra gồm giá trị đã esc_attr.
}

/**
 * Component phân trang dùng chung cho mọi slider và hàng vuốt của trang chủ: nút Trước (<), cột chấm xếp dọc, nút Sau (>). Mặc định `hidden`;
 * home.js bỏ `hidden` khi slider chạy. Nút là biểu tượng (chữ Trước/Sau nằm trong .isha-sr); chấm là nút hoặc, khi truyền `links`, là liên kết neo.
 * Kiểu ở khối PAGER của home.css (điện thoại ẩn cả cụm).
 *
 * @param array $args {
 *     Tuỳ chọn.
 *
 *     @type int    $count     Số chấm (khi không có links); tối đa 24.
 *     @type string $label     Tên nhóm điều khiển cho trình đọc màn hình (đã dịch).
 *     @type string $dot_label Mẫu nhãn chấm, %d là số thứ tự. Mặc định "Khung %d".
 *     @type string $prev      Nhãn nút Trước cho trình đọc màn hình. Mặc định "Trước".
 *     @type string $next      Nhãn nút Sau. Mặc định "Sau".
 *     @type array  $links     Danh sách array( 'href' => ..., 'label' => ... ): chấm là liên kết neo thay cho nút.
 *     @type string $class     Class thêm cho khối (theo section).
 *     @type string $attrs     Thuộc tính data-* thêm (đã escape bởi người gọi).
 * }
 */
function isha_home_pager( $args = array() ) {
	$args  = wp_parse_args(
		is_array( $args ) ? $args : array(),
		array(
			'count'     => 0,
			'label'     => '',
			'dot_label' => __( 'Khung %d', 'isha' ),
			'prev'      => __( 'Trước', 'isha' ),
			'next'      => __( 'Sau', 'isha' ),
			'links'     => array(),
			'class'     => '',
			'attrs'     => '',
		)
	);
	$links = is_array( $args['links'] ) ? array_values( $args['links'] ) : array();
	$count = $links ? count( $links ) : ( is_numeric( $args['count'] ) ? max( 0, min( 24, (int) $args['count'] ) ) : 0 );
	$label = is_scalar( $args['label'] ) ? trim( (string) $args['label'] ) : '';
	$group = '' !== $label ? ' role="group" aria-label="' . esc_attr( $label ) . '"' : '';
	$class = trim( 'isha-pager ' . ( is_scalar( $args['class'] ) ? (string) $args['class'] : '' ) );

	$dots = '';
	for ( $i = 0; $i < $count; $i++ ) {
		$current = 0 === $i ? ' aria-current="true"' : '';
		if ( $links ) {
			$href  = isset( $links[ $i ]['href'] ) ? (string) $links[ $i ]['href'] : '';
			$name  = isset( $links[ $i ]['label'] ) ? (string) $links[ $i ]['label'] : '';
			$dots .= '<li><a href="' . esc_attr( $href ) . '"' . $current . '><span class="isha-sr">' . esc_html( $name ) . '</span></a></li>';
		} else {
			$dots .= '<li><button type="button" aria-label="' . esc_attr( sprintf( (string) $args['dot_label'], $i + 1 ) ) . '"' . $current . '></button></li>';
		}
	}
	?>
	<div class="<?php echo esc_attr( $class ); ?>" data-isha-pager<?php echo $args['attrs'] ? ' ' . $args['attrs'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- attrs do nơi gọi dựng sẵn. ?><?php echo $group; // phpcs:ignore WordPress.Security.EscapeOutput -- đã qua esc_attr. ?> hidden>
		<button type="button" class="isha-pager__btn isha-pager__btn--prev"><span class="isha-sr"><?php echo esc_html( (string) $args['prev'] ); ?></span></button>
		<ol class="isha-pager__dots"><?php echo $dots; // phpcs:ignore WordPress.Security.EscapeOutput -- đã qua esc_attr / esc_html. ?></ol>
		<button type="button" class="isha-pager__btn isha-pager__btn--next"><span class="isha-sr"><?php echo esc_html( (string) $args['next'] ); ?></span></button>
	</div>
	<?php
}

/**
 * Phân trang của hàng vuốt ngang (tương thích cũ): gọi isha_home_pager() với nhãn chấm "Khung N".
 *
 * @param array $args count, label (xem isha_home_pager()).
 */
function isha_home_row_controls( $args = array() ) {
	isha_home_pager( is_array( $args ) ? $args : array() );
}

/**
 * Dựng trang chủ: khối phim (lớp bầu trời + từng section qua shortcode) rồi dock liên hệ.
 *
 * Section trả '' (bị tắt hoặc không có dữ liệu) thì bỏ qua, không để lại khoảng trống. Trong lúc render, cờ
 * isha_home_in_film() là true (shortcode không bọc `isha-home--embed`); xong thì trả lại giá trị cũ.
 */
function isha_home_render() {
	$was_in_film = isha_home_in_film();
	isha_home_in_film( true );

	try {
		echo '<div class="isha-film" data-isha-film>' . "\n";
		echo '<div class="isha-sky" data-isha-sky aria-hidden="true"></div>' . "\n";

		foreach ( isha_home_sections() as $section ) {
			$tag = ( is_array( $section ) && isset( $section['tag'] ) && is_scalar( $section['tag'] ) ) ? sanitize_key( $section['tag'] ) : '';
			if ( '' === $tag || ! shortcode_exists( $tag ) ) {
				continue;
			}
			$html = do_shortcode( '[' . $tag . ']' );
			if ( '' !== trim( $html ) ) {
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML do template của từng section tự escape.
			}
		}

		echo '</div>' . "\n";
	} finally {
		isha_home_in_film( $was_in_film );
	}

	get_template_part( 'template-parts/home/dock' );
}
