<?php
/**
 * Phần dùng chung của các form công khai (đặt lịch, liên hệ), 18/11/2026 (docs/home-architecture.md mục 17).
 *
 * - Mã bảo mật (nonce) lấy MỚI qua AJAX (`admin-ajax.php?action=isha_form_nonce`), để form vẫn gửi được khi trang bị cache (nonce in sẵn
 *   trong HTML hết hạn sau 12 đến 24 giờ). Trả kèm header không cache.
 * - Giới hạn tần suất theo IP (băm, không lưu IP thô), ô đồng ý xử lý dữ liệu cá nhân, địa chỉ trang Chính sách bảo mật.
 * - Bong bóng số thư MỚI cạnh mục menu quản trị Lịch hẹn và Liên hệ.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Forms {

	/**
	 * Post type hộp thư => tên action của nonce form tương ứng.
	 */
	const INBOXES = array(
		'isha_appointment' => 'isha_booking',
		'isha_contact'     => 'isha_contact',
	);

	/**
	 * Đăng ký hook.
	 */
	public static function init() {
		add_action( 'wp_ajax_isha_form_nonce', array( __CLASS__, 'ajax_nonce' ) );
		add_action( 'wp_ajax_nopriv_isha_form_nonce', array( __CLASS__, 'ajax_nonce' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu_bubbles' ), 99 );
	}

	/**
	 * Trả nonce mới của mọi form công khai (JSON), không cache.
	 */
	public static function ajax_nonce() {
		nocache_headers();
		$out = array();
		foreach ( self::INBOXES as $action ) {
			$out[ $action ] = wp_create_nonce( $action );
		}
		wp_send_json_success( $out );
	}

	/**
	 * Khoá giới hạn tần suất của người gửi hiện tại cho một form (IP được băm cùng salt của site).
	 *
	 * @param string $form Tên form.
	 * @return string
	 */
	private static function rate_key( $form ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return 'isha_rl_' . sanitize_key( $form ) . '_' . md5( wp_salt( 'nonce' ) . '|' . $ip );
	}

	/**
	 * Người gửi đã chạm giới hạn của form trong giờ này chưa.
	 *
	 * @param string $form Tên form.
	 * @param int    $max  Số lần gửi thành công tối đa mỗi giờ.
	 * @return bool
	 */
	public static function limited( $form, $max ) {
		return (int) get_transient( self::rate_key( $form ) ) >= (int) $max;
	}

	/**
	 * Tăng bộ đếm sau một lần gửi thành công.
	 *
	 * @param string $form Tên form.
	 */
	public static function bump( $form ) {
		$key = self::rate_key( $form );
		set_transient( $key, (int) get_transient( $key ) + 1, HOUR_IN_SECONDS );
	}

	/**
	 * Ô đồng ý có được tick không ('1').
	 *
	 * @param array $in Dữ liệu gửi lên (đã bỏ slash).
	 * @return bool
	 */
	public static function consented( array $in ) {
		return isset( $in['isha_consent'] ) && '1' === (string) $in['isha_consent'];
	}

	/**
	 * Địa chỉ trang Chính sách bảo mật (Cài đặt › Quyền riêng tư), '' nếu chưa có trang đã xuất bản.
	 *
	 * @return string
	 */
	public static function privacy_url() {
		$url = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
		return is_string( $url ) ? $url : '';
	}

	/**
	 * Làm sạch số điện thoại và kiểm định dạng (8 đến 20 ký tự gồm số, khoảng trắng, + ( ) . -).
	 *
	 * @param mixed $raw Giá trị thô.
	 * @return string '' nếu không hợp lệ.
	 */
	public static function phone( $raw ) {
		$phone = is_scalar( $raw ) ? trim( sanitize_text_field( (string) $raw ) ) : '';
		return preg_match( '/^[0-9+()\s.\-]{8,20}$/', $phone ) && preg_match_all( '/[0-9]/', $phone ) >= 8 ? $phone : '';
	}

	/**
	 * Gửi email thông báo cho phòng khám (văn bản thuần). Người nhận: "Email nhận thông báo" ở ISHA Clinic, trống thì email quản trị.
	 *
	 * @param string $subject   Tiêu đề (đã có tên website).
	 * @param array  $lines     Các dòng nội dung.
	 * @param string $reply_to  Email người gửi (tuỳ chọn).
	 * @return bool
	 */
	public static function notify( $subject, array $lines, $reply_to = '' ) {
		$to      = function_exists( 'isha_core_get' ) ? isha_core_get( 'notify_email', get_option( 'admin_email' ) ) : get_option( 'admin_email' );
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		if ( '' !== $reply_to && is_email( $reply_to ) ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}
		return (bool) wp_mail( $to, $subject, implode( "\n", $lines ), $headers );
	}

	/**
	 * Bong bóng số thư mới (trạng thái 'new') cạnh mục menu của mỗi hộp thư.
	 */
	public static function menu_bubbles() {
		global $menu;
		if ( ! is_array( $menu ) ) {
			return;
		}
		foreach ( array_keys( self::INBOXES ) as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}
			$count = self::count_new( $post_type );
			if ( $count < 1 ) {
				continue;
			}
			$slug = 'edit.php?post_type=' . $post_type;
			foreach ( $menu as $i => $item ) {
				if ( isset( $item[2] ) && $slug === $item[2] ) {
					/* translators: %d: số thư mới */
					$menu[ $i ][0] .= ' <span class="awaiting-mod count-' . (int) $count . '"><span class="pending-count" aria-hidden="true">' . (int) $count . '</span><span class="screen-reader-text">' . esc_html( sprintf( _n( '%d mục mới', '%d mục mới', $count, 'isha-core' ), $count ) ) . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
			}
		}
	}

	/**
	 * Số bài ở trạng thái 'new' của một hộp thư (nhớ trong request).
	 *
	 * @param string $post_type Post type.
	 * @return int
	 */
	public static function count_new( $post_type ) {
		static $cache = array();
		if ( ! isset( $cache[ $post_type ] ) ) {
			$q                    = new WP_Query(
				array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => '_isha_status', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'     => 'new', // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);
			$cache[ $post_type ] = (int) $q->found_posts;
		}
		return $cache[ $post_type ];
	}
}
