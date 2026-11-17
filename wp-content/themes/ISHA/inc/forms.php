<?php
/**
 * Form công khai và popup Đặt lịch (18/11/2026, docs/home-architecture.md mục 17).
 *
 * - Popup Đặt lịch (`dialog#isha-book`, template-parts/forms/booking-dialog.php) in ở chân MỌI trang phía khách khi có trang dùng mẫu
 *   "Đặt lịch hẹn". booking.js chặn cú bấm vào mọi liên kết tới trang đó (header, hero, dock, cảnh kết, nút trong trang Elementor...) và
 *   mọi phần tử [data-isha-booking], mở popup (tham số ?service=ID chọn sẵn dịch vụ). Không JS hoặc trình duyệt không có <dialog>: liên
 *   kết vẫn mở trang Đặt lịch hẹn như cũ.
 * - Form (đặt lịch, liên hệ) gửi bằng AJAX khi có JS; mã bảo mật lấy MỚI lúc mở popup hoặc lúc gửi (trang bị cache vẫn gửi được). Không JS:
 *   form gửi thường tới admin-post.php (form ở trang có in sẵn nonce). Xử lý ở ISHA Core (ISHA_Core_Booking, ISHA_Core_Contact).
 * - Tài nguyên dùng chung: assets/css/booking.css (popup và kiểu form, tự đủ token, không cần home.css) và assets/js/booking.js.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Địa chỉ trang Đặt lịch hẹn đã xuất bản, '' nếu chưa có (khác isha_booking_url(): không rơi về trang chủ).
 *
 * @return string
 */
function isha_forms_booking_url() {
	static $url = null;
	if ( null === $url ) {
		$ids = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => 'templates/template-booking.php', // phpcs:ignore WordPress.DB.SlowDBQuery
				'no_found_rows'  => true,
			)
		);
		$url = $ids ? (string) get_permalink( (int) $ids[0] ) : '';
	}
	return $url;
}

/**
 * Popup có được dùng ở trang này không: phía khách, có trang Đặt lịch hẹn và ISHA Core đang bật.
 *
 * @return bool
 */
function isha_forms_popup_enabled() {
	return ! is_admin() && class_exists( 'ISHA_Core_Booking' ) && '' !== isha_forms_booking_url();
}

/**
 * Thông tin liên hệ dựng sẵn từ trang cài đặt ISHA Clinic (giá trị thô, template tự escape).
 *
 * @return array{phone:string,tel_url:string,email:string,address:string[],hours:string[],map_url:string,directions_url:string,zalo_url:string,social:array}
 */
function isha_forms_contact_info() {
	$text  = static function ( $key ) {
		$value = isha_opt( $key );
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	};
	$lines = static function ( $value ) {
		return array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', (string) $value ) ) ) );
	};

	$phone   = $text( 'phone' );
	$tel     = (string) preg_replace( '/[^0-9+]/', '', $phone );
	$address = $text( 'address' );
	$map     = esc_url_raw( $text( 'map_url' ) );

	$social = array();
	foreach ( array(
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'tiktok'    => 'TikTok',
		'youtube'   => 'YouTube',
	) as $key => $label ) {
		$url = esc_url_raw( $text( $key ) );
		if ( '' !== $url ) {
			$social[] = array(
				'label' => $label,
				'url'   => $url,
			);
		}
	}

	return array(
		'phone'          => $phone,
		'tel_url'        => '' !== $tel ? 'tel:' . $tel : '',
		'email'          => is_email( $text( 'email' ) ) ? $text( 'email' ) : '',
		'address'        => $lines( $address ),
		'hours'          => $lines( $text( 'hours' ) ),
		'map_url'        => 0 === strpos( $map, 'https://' ) ? $map : '',
		'directions_url' => '' !== $address ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( (string) preg_replace( '/\s+/u', ' ', $address ) ) : '',
		'zalo_url'       => function_exists( 'isha_home_data_zalo_url' ) ? isha_home_data_zalo_url( isha_opt( 'zalo' ) ) : '',
		'social'         => $social,
	);
}

/**
 * Dịch vụ đã xuất bản gom theo nhóm (thứ tự hiển thị của nhóm, liệu trình theo menu_order) cho ô chọn của form đặt lịch.
 *
 * @return array Danh sách array( 'label' => tên nhóm ('' = chưa có nhóm), 'items' => array( ID => tên ) ).
 */
function isha_forms_service_groups() {
	if ( ! post_type_exists( 'isha_service' ) ) {
		return array();
	}
	$posts = get_posts(
		array(
			'post_type'      => 'isha_service',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	);
	$terms  = class_exists( 'ISHA_Core_Term_Fields' ) ? ISHA_Core_Term_Fields::ordered_terms() : array();
	$groups = array();
	$placed = array();
	foreach ( $terms as $term ) {
		$items = array();
		foreach ( $posts as $post ) {
			if ( ! isset( $placed[ $post->ID ] ) && has_term( $term->term_id, 'isha_service_cat', $post ) ) {
				$items[ $post->ID ]   = wp_strip_all_tags( get_the_title( $post ) );
				$placed[ $post->ID ] = true;
			}
		}
		if ( $items ) {
			$groups[] = array(
				'label' => wp_strip_all_tags( $term->name ),
				'items' => $items,
			);
		}
	}
	$rest = array();
	foreach ( $posts as $post ) {
		if ( ! isset( $placed[ $post->ID ] ) ) {
			$rest[ $post->ID ] = wp_strip_all_tags( get_the_title( $post ) );
		}
	}
	if ( $rest ) {
		$groups[] = array(
			'label' => '',
			'items' => $rest,
		);
	}
	return $groups;
}

/**
 * Trạng thái của lần gửi thường vừa xong (tham số ?isha_booking= hoặc ?isha_contact= trên địa chỉ), '' nếu không có.
 *
 * @param string $param isha_booking | isha_contact.
 * @return string
 */
function isha_forms_status( $param ) {
	return isset( $_GET[ $param ] ) ? sanitize_key( wp_unslash( $_GET[ $param ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để hiện câu báo.
}

/**
 * Câu đồng ý xử lý dữ liệu cá nhân, kèm liên kết Chính sách bảo mật (mở thẻ mới) khi đã có trang đó. HTML đã escape.
 *
 * @param string $purpose Mục đích (văn bản thuần), ví dụ "liên hệ xác nhận lịch hẹn".
 * @return string
 */
function isha_forms_consent_label( $purpose ) {
	/* translators: %s: mục đích dùng thông tin */
	$text    = esc_html( sprintf( __( 'Tôi đồng ý để ISHA lưu và dùng thông tin trên để %s.', 'isha' ), $purpose ) );
	$privacy = class_exists( 'ISHA_Core_Forms' ) ? ISHA_Core_Forms::privacy_url() : '';
	if ( '' !== $privacy ) {
		$text .= ' <a href="' . esc_url( $privacy ) . '" target="_blank" rel="noopener">' . esc_html__( 'Chính sách bảo mật', 'isha' ) . '<span class="screen-reader-text"> ' . esc_html__( '(mở thẻ mới)', 'isha' ) . '</span></a>';
	}
	return $text;
}

/**
 * In một ô của form: nhãn, ô nhập (input | select | textarea), dòng báo lỗi (ẩn, booking.js điền khi máy chủ trả lỗi, gắn qua
 * aria-describedby). Ô bắt buộc có dấu * nhìn thấy (ẩn khỏi trình đọc màn hình, đã có thuộc tính required).
 *
 * @param array $a {
 *     @type string $prefix   Gốc id DOM của form (bắt buộc, khác nhau giữa các form trên cùng trang).
 *     @type string $name     Tên trường gửi lên (isha_*).
 *     @type string $label    Nhãn (văn bản thuần).
 *     @type string $type     text | tel | email | date | select | textarea. Mặc định text.
 *     @type bool   $required Bắt buộc.
 *     @type array  $attrs    Thuộc tính thêm cho ô nhập (autocomplete, maxlength, min, rows, placeholder, inputmode).
 *     @type array  $options  select: danh sách array( 'label' => nhóm ('' = không nhóm), 'items' => array( giá trị => nhãn ) ).
 *     @type string $empty    select: nhãn của lựa chọn trống.
 *     @type string $value    Giá trị chọn sẵn (select).
 *     @type bool   $wide     Chiếm cả hàng của lưới form.
 * }
 */
function isha_forms_field( array $a ) {
	$a    = wp_parse_args(
		$a,
		array(
			'prefix'   => 'isha-form',
			'name'     => '',
			'label'    => '',
			'type'     => 'text',
			'required' => false,
			'attrs'    => array(),
			'options'  => array(),
			'empty'    => '',
			'value'    => '',
			'wide'     => false,
		)
	);
	$id   = $a['prefix'] . '-' . str_replace( '_', '-', (string) preg_replace( '/^isha_/', '', $a['name'] ) );
	$err  = $id . '-err';
	$attr = ' id="' . esc_attr( $id ) . '" name="' . esc_attr( $a['name'] ) . '" aria-describedby="' . esc_attr( $err ) . '"' . ( $a['required'] ? ' required' : '' );
	foreach ( $a['attrs'] as $key => $value ) {
		$attr .= ' ' . esc_attr( $key ) . '="' . esc_attr( (string) $value ) . '"';
	}

	echo '<div class="isha-form-lux__field' . ( $a['wide'] ? ' isha-form-lux__field--wide' : '' ) . '" data-field="' . esc_attr( $a['name'] ) . '">';
	echo '<label class="isha-form-lux__label" for="' . esc_attr( $id ) . '">' . esc_html( $a['label'] ) . ( $a['required'] ? ' <span class="isha-form-lux__req" aria-hidden="true">*</span>' : '' ) . '</label>';
	if ( 'select' === $a['type'] ) {
		echo '<select class="isha-form-lux__control"' . $attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $attr dựng từ giá trị đã esc_attr.
		echo '<option value="">' . esc_html( $a['empty'] ) . '</option>';
		foreach ( $a['options'] as $group ) {
			if ( '' !== $group['label'] ) {
				echo '<optgroup label="' . esc_attr( $group['label'] ) . '">';
			}
			foreach ( $group['items'] as $value => $text ) {
				echo '<option value="' . esc_attr( (string) $value ) . '"' . selected( (string) $a['value'], (string) $value, false ) . '>' . esc_html( $text ) . '</option>';
			}
			if ( '' !== $group['label'] ) {
				echo '</optgroup>';
			}
		}
		echo '</select>';
	} elseif ( 'textarea' === $a['type'] ) {
		echo '<textarea class="isha-form-lux__control"' . $attr . '></textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $attr dựng từ giá trị đã esc_attr.
	} else {
		echo '<input class="isha-form-lux__control" type="' . esc_attr( $a['type'] ) . '"' . $attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $attr dựng từ giá trị đã esc_attr.
	}
	echo '<p class="isha-form-lux__err" id="' . esc_attr( $err ) . '" hidden></p>';
	echo '</div>';
}

/**
 * In popup Đặt lịch ở chân trang.
 */
function isha_forms_print_dialog() {
	if ( isha_forms_popup_enabled() ) {
		get_template_part( 'template-parts/forms/booking-dialog' );
	}
}
add_action( 'wp_footer', 'isha_forms_print_dialog', 5 );

/**
 * Nạp booking.css và booking.js ở mọi trang phía khách có popup hoặc có form (trang Liên hệ, Đặt lịch hẹn), kèm cấu hình JS.
 */
function isha_forms_assets() {
	$form_page = is_page_template( 'templates/template-contact.php' ) || is_page_template( 'templates/template-booking.php' );
	if ( ! isha_forms_popup_enabled() && ! $form_page ) {
		return;
	}
	$css = ISHA_DIR . '/assets/css/booking.css';
	$js  = ISHA_DIR . '/assets/js/booking.js';
	if ( is_file( $css ) ) {
		wp_enqueue_style( 'isha-booking', ISHA_URI . '/assets/css/booking.css', array( 'isha-main' ), (string) filemtime( $css ) );
	}
	if ( is_file( $js ) ) {
		wp_enqueue_script(
			'isha-booking',
			ISHA_URI . '/assets/js/booking.js',
			array(),
			(string) filemtime( $js ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		$booking = isha_forms_booking_url();
		$config  = array(
			'ajax'        => admin_url( 'admin-ajax.php' ),
			'bookingPath' => '' !== $booking ? (string) wp_parse_url( $booking, PHP_URL_PATH ) : '',
			'strings'     => array(
				'sending' => __( 'Đang gửi…', 'isha' ),
				'network' => __( 'Không gửi được do lỗi kết nối. Vui lòng thử lại hoặc gọi hotline.', 'isha' ),
			),
		);
		wp_add_inline_script( 'isha-booking', 'window.ISHA=window.ISHA||{};window.ISHA.forms=' . wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );
	}
}
add_action( 'wp_enqueue_scripts', 'isha_forms_assets', 20 );

/**
 * Trang Liên hệ và Đặt lịch hẹn: home.css (tông màu, chữ, nút của hệ thiết kế trang chủ) và contact.css. Không cần home.js (không có cảnh).
 */
function isha_contact_page_assets() {
	if ( ! is_page_template( 'templates/template-contact.php' ) && ! is_page_template( 'templates/template-booking.php' ) ) {
		return;
	}
	$home = ISHA_DIR . '/assets/css/home.css';
	$css  = ISHA_DIR . '/assets/css/contact.css';
	if ( is_file( $home ) ) {
		wp_enqueue_style( 'isha-home', ISHA_URI . '/assets/css/home.css', array( 'isha-main' ), (string) filemtime( $home ) );
	}
	if ( is_file( $css ) ) {
		wp_enqueue_style( 'isha-contact', ISHA_URI . '/assets/css/contact.css', array( 'isha-main', 'isha-home', 'isha-booking' ), (string) filemtime( $css ) );
	}
}
add_action( 'wp_enqueue_scripts', 'isha_contact_page_assets', 21 );
