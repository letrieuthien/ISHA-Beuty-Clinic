<?php
/**
 * Đặt lịch hẹn: xử lý form, lưu CPT isha_appointment, gửi email, cột và meta box trong admin.
 *
 * Hai đường gửi dùng chung ::process() (18/11/2026, docs/home-architecture.md mục 17):
 *   - admin-post.php (action isha_booking): form gửi thường khi không có JS; xong thì chuyển hướng về trang trước kèm ?isha_booking=<trạng thái>.
 *   - admin-ajax.php (action isha_booking): popup đặt lịch và form có JS; trả JSON { success, data: { status, message, errors } }.
 * Trường: isha_name*, isha_phone*, isha_email, isha_service, isha_date*, isha_time*, isha_note, isha_consent* ('1'), isha_source (popup|page),
 * isha_booking_nonce (action isha_booking; popup lấy mới qua ISHA_Core_Forms), isha_website (bẫy bot, phải rỗng).
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Booking {

	const MAX_PER_HOUR = 5;
	const NOTE_MAX     = 1000;

	public static function init() {
		add_action( 'admin_post_nopriv_isha_booking', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_isha_booking', array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_isha_booking', array( __CLASS__, 'ajax' ) );
		add_action( 'wp_ajax_isha_booking', array( __CLASS__, 'ajax' ) );

		add_filter( 'manage_isha_appointment_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_isha_appointment_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_action( 'save_post_isha_appointment', array( __CLASS__, 'save_status' ) );
	}

	public static function statuses() {
		return array(
			'new'       => __( 'Mới', 'isha-core' ),
			'confirmed' => __( 'Đã xác nhận', 'isha-core' ),
			'done'      => __( 'Đã hoàn thành', 'isha-core' ),
			'cancelled' => __( 'Đã huỷ', 'isha-core' ),
		);
	}

	/**
	 * Khung giờ hẹn: mỗi 30 phút từ 09:00 tới 18:00 (phòng khám mở cửa 09:00 – 19:00, ca cuối bắt đầu trước giờ đóng cửa một tiếng).
	 * Đổi bằng bộ lọc `isha_core/booking_slots` (mảng chuỗi 'HH:MM').
	 *
	 * @return string[]
	 */
	public static function time_slots() {
		$slots = array();
		for ( $h = 9; $h <= 18; $h++ ) {
			$slots[] = sprintf( '%02d:00', $h );
			if ( $h < 18 ) {
				$slots[] = sprintf( '%02d:30', $h );
			}
		}
		$filtered = apply_filters( 'isha_core/booking_slots', $slots );
		return is_array( $filtered ) && $filtered ? array_values( array_map( 'strval', $filtered ) ) : $slots;
	}

	/**
	 * Câu báo theo trạng thái gửi.
	 *
	 * @return array<string,string>
	 */
	public static function messages() {
		return array(
			'success' => __( 'Cảm ơn bạn! ISHA đã nhận yêu cầu đặt lịch và sẽ gọi lại để xác nhận trong giờ làm việc.', 'isha-core' ),
			'invalid' => __( 'Thông tin chưa hợp lệ. Vui lòng kiểm tra các ô được đánh dấu.', 'isha-core' ),
			'limit'   => __( 'Bạn đã gửi quá nhiều yêu cầu trong một giờ. Vui lòng thử lại sau hoặc gọi hotline.', 'isha-core' ),
			'error'   => __( 'Phiên gửi đã hết hạn hoặc có lỗi xảy ra. Vui lòng tải lại trang và thử lại.', 'isha-core' ),
		);
	}

	/**
	 * Xử lý một lần gửi (dùng chung cho admin-post và AJAX). Hàm không chuyển hướng, không in gì.
	 *
	 * @param array $in Dữ liệu gửi lên (đã bỏ slash).
	 * @return array{status:string,errors:array<string,string>,id:int}
	 */
	public static function process( array $in ) {
		$result = array(
			'status' => 'error',
			'errors' => array(),
			'id'     => 0,
		);

		$nonce = isset( $in['isha_booking_nonce'] ) ? sanitize_text_field( (string) $in['isha_booking_nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'isha_booking' ) ) {
			return $result;
		}
		// Bẫy bot: ô ẩn có chữ thì giả vờ thành công, không lưu gì.
		if ( ! empty( $in['isha_website'] ) ) {
			$result['status'] = 'success';
			return $result;
		}
		if ( ISHA_Core_Forms::limited( 'booking', self::MAX_PER_HOUR ) ) {
			$result['status'] = 'limit';
			return $result;
		}

		$name    = isset( $in['isha_name'] ) ? trim( sanitize_text_field( (string) $in['isha_name'] ) ) : '';
		$phone   = ISHA_Core_Forms::phone( isset( $in['isha_phone'] ) ? $in['isha_phone'] : '' );
		$email   = isset( $in['isha_email'] ) ? trim( (string) $in['isha_email'] ) : '';
		$service = isset( $in['isha_service'] ) ? absint( $in['isha_service'] ) : 0;
		$date    = isset( $in['isha_date'] ) ? sanitize_text_field( (string) $in['isha_date'] ) : '';
		$time    = isset( $in['isha_time'] ) ? sanitize_text_field( (string) $in['isha_time'] ) : '';
		$note    = isset( $in['isha_note'] ) ? sanitize_textarea_field( (string) $in['isha_note'] ) : '';
		$source  = isset( $in['isha_source'] ) && 'popup' === $in['isha_source'] ? 'popup' : 'page';

		$errors = array();
		if ( '' === $name ) {
			$errors['isha_name'] = __( 'Vui lòng nhập họ và tên.', 'isha-core' );
		} elseif ( mb_strlen( $name ) > 100 ) {
			$errors['isha_name'] = __( 'Họ tên tối đa 100 ký tự.', 'isha-core' );
		}
		if ( '' === $phone ) {
			$errors['isha_phone'] = __( 'Số điện thoại chưa đúng (ít nhất 8 chữ số).', 'isha-core' );
		}
		if ( '' !== $email ) {
			$email = sanitize_email( $email );
			if ( ! is_email( $email ) ) {
				$errors['isha_email'] = __( 'Email chưa đúng định dạng.', 'isha-core' );
			}
		}
		if ( $service && ( 'isha_service' !== get_post_type( $service ) || 'publish' !== get_post_status( $service ) ) ) {
			$errors['isha_service'] = __( 'Dịch vụ không hợp lệ, vui lòng chọn lại.', 'isha-core' );
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || $date < wp_date( 'Y-m-d' ) ) {
			$errors['isha_date'] = __( 'Vui lòng chọn ngày hẹn từ hôm nay trở đi.', 'isha-core' );
		}
		// Giờ không có trong danh sách, hoặc đã qua (ngày hôm nay): cùng một thông báo sẵn có cho ô giờ.
		$past_slot = ( '' !== $date && ! isset( $errors['isha_date'] ) && $date === wp_date( 'Y-m-d' ) && $time <= wp_date( 'H:i' ) );
		if ( ! in_array( $time, self::time_slots(), true ) || $past_slot ) {
			$errors['isha_time'] = __( 'Vui lòng chọn giờ hẹn.', 'isha-core' );
		}
		if ( mb_strlen( $note ) > self::NOTE_MAX ) {
			$errors['isha_note'] = __( 'Ghi chú tối đa 1000 ký tự.', 'isha-core' );
		}
		if ( ! ISHA_Core_Forms::consented( $in ) ) {
			$errors['isha_consent'] = __( 'Vui lòng đồng ý để ISHA liên hệ xác nhận lịch hẹn.', 'isha-core' );
		}
		if ( $errors ) {
			$result['status'] = 'invalid';
			$result['errors'] = $errors;
			return $result;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'isha_appointment',
				'post_status' => 'publish',
				'post_title'  => sprintf( '%s – %s %s', $name, $date, $time ),
				'meta_input'  => array(
					'_isha_name'       => $name,
					'_isha_phone'      => $phone,
					'_isha_email'      => $email,
					'_isha_service_id' => $service,
					'_isha_date'       => $date,
					'_isha_time'       => $time,
					'_isha_note'       => $note,
					'_isha_status'     => 'new',
					'_isha_consent_at' => current_time( 'mysql' ),
					'_isha_source'     => $source,
				),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return $result;
		}

		ISHA_Core_Forms::bump( 'booking' );
		self::notify( $post_id );
		$result['status'] = 'success';
		$result['id']     = (int) $post_id;
		return $result;
	}

	/**
	 * Gửi thường (admin-post): xử lý rồi chuyển hướng về trang trước.
	 */
	public static function handle() {
		$redirect = wp_get_referer() ? remove_query_arg( 'isha_booking', wp_get_referer() ) : home_url( '/' );
		$result   = self::process( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- process() kiểm nonce và làm sạch từng trường.
		wp_safe_redirect( add_query_arg( 'isha_booking', $result['status'], $redirect ) . '#isha-booking' );
		exit;
	}

	/**
	 * Gửi qua AJAX: trả JSON trạng thái, câu báo và lỗi theo từng ô.
	 */
	public static function ajax() {
		nocache_headers();
		$result   = self::process( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- process() kiểm nonce và làm sạch từng trường.
		$messages = self::messages();
		$payload  = array(
			'status'  => $result['status'],
			'message' => isset( $messages[ $result['status'] ] ) ? $messages[ $result['status'] ] : $messages['error'],
			'errors'  => $result['errors'],
		);
		if ( 'success' === $result['status'] ) {
			wp_send_json_success( $payload );
		}
		wp_send_json_error( $payload );
	}

	/**
	 * Gửi email thông báo cho phòng khám.
	 *
	 * @param int $post_id ID lịch hẹn.
	 */
	private static function notify( $post_id ) {
		$meta    = fn( $k ) => get_post_meta( $post_id, $k, true );
		$service = $meta( '_isha_service_id' ) ? get_the_title( $meta( '_isha_service_id' ) ) : '—';

		ISHA_Core_Forms::notify(
			/* translators: %s: tên website */
			sprintf( __( '[%s] Lịch hẹn mới', 'isha-core' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
			array(
				'Có lịch hẹn mới trên website:',
				'',
				'Họ tên: ' . $meta( '_isha_name' ),
				'Điện thoại: ' . $meta( '_isha_phone' ),
				'Email: ' . ( $meta( '_isha_email' ) ? $meta( '_isha_email' ) : '—' ),
				'Dịch vụ: ' . $service,
				'Ngày giờ: ' . $meta( '_isha_date' ) . ' ' . $meta( '_isha_time' ),
				'Ghi chú: ' . ( $meta( '_isha_note' ) ? $meta( '_isha_note' ) : '—' ),
				'',
				'Xem trong admin: ' . admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
			),
			(string) $meta( '_isha_email' )
		);
	}

	/* --- Admin ---------------------------------------------------------- */

	public static function columns( $cols ) {
		return array(
			'cb'            => $cols['cb'],
			'title'         => __( 'Khách hàng', 'isha-core' ),
			'isha_phone'    => __( 'Điện thoại', 'isha-core' ),
			'isha_service'  => __( 'Dịch vụ', 'isha-core' ),
			'isha_datetime' => __( 'Ngày giờ hẹn', 'isha-core' ),
			'isha_status'   => __( 'Trạng thái', 'isha-core' ),
			'date'          => __( 'Gửi lúc', 'isha-core' ),
		);
	}

	public static function column_content( $col, $post_id ) {
		switch ( $col ) {
			case 'isha_phone':
				echo esc_html( get_post_meta( $post_id, '_isha_phone', true ) );
				break;
			case 'isha_service':
				$sid = (int) get_post_meta( $post_id, '_isha_service_id', true );
				echo $sid ? esc_html( get_the_title( $sid ) ) : '—';
				break;
			case 'isha_datetime':
				echo esc_html( get_post_meta( $post_id, '_isha_date', true ) . ' ' . get_post_meta( $post_id, '_isha_time', true ) );
				break;
			case 'isha_status':
				$st = self::statuses();
				$k  = get_post_meta( $post_id, '_isha_status', true );
				$k  = isset( $st[ $k ] ) ? $k : 'new';
				echo ISHA_Core_Admin_UI::badge( $k, $st[ $k ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- badge() đã escape.
				break;
		}
	}

	public static function meta_box() {
		add_meta_box( 'isha_appt', __( 'Chi tiết lịch hẹn', 'isha-core' ), array( __CLASS__, 'render_box' ), 'isha_appointment', 'normal', 'high' );
	}

	public static function render_box( $post ) {
		wp_nonce_field( 'isha_appt_status', 'isha_appt_nonce' );
		$m       = fn( $k ) => get_post_meta( $post->ID, $k, true );
		$sid     = (int) $m( '_isha_service_id' );
		$sources = array(
			'popup' => __( 'Popup đặt lịch', 'isha-core' ),
			'page'  => __( 'Trang Đặt lịch hẹn', 'isha-core' ),
		);
		$rows    = array(
			__( 'Họ tên', 'isha-core' )               => $m( '_isha_name' ),
			__( 'Điện thoại', 'isha-core' )           => $m( '_isha_phone' ),
			__( 'Email', 'isha-core' )                => $m( '_isha_email' ),
			__( 'Dịch vụ', 'isha-core' )              => $sid ? get_the_title( $sid ) : '',
			__( 'Ngày giờ', 'isha-core' )             => trim( $m( '_isha_date' ) . ' ' . $m( '_isha_time' ) ),
			__( 'Ghi chú', 'isha-core' )              => $m( '_isha_note' ),
			__( 'Đồng ý xử lý dữ liệu', 'isha-core' ) => $m( '_isha_consent_at' ) ? sprintf( /* translators: %s: thời điểm */ __( 'Có, lúc %s', 'isha-core' ), $m( '_isha_consent_at' ) ) : '',
			__( 'Gửi từ', 'isha-core' )               => isset( $sources[ $m( '_isha_source' ) ] ) ? $sources[ $m( '_isha_source' ) ] : '',
		);
		echo '<table class="form-table" role="presentation">';
		foreach ( $rows as $label => $value ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( $value ? $value : '—' ) ) . '</td></tr>';
		}
		$current = $m( '_isha_status' ) ? $m( '_isha_status' ) : 'new';
		echo '<tr><th><label for="isha_status">' . esc_html__( 'Trạng thái', 'isha-core' ) . '</label></th><td><select id="isha_status" name="isha_status">';
		foreach ( self::statuses() as $k => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $current, $k, false ), esc_html( $label ) );
		}
		echo '</select></td></tr></table>';
	}

	public static function save_status( $post_id ) {
		if ( ! isset( $_POST['isha_appt_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['isha_appt_nonce'] ) ), 'isha_appt_status' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		$status = isset( $_POST['isha_status'] ) ? sanitize_key( wp_unslash( $_POST['isha_status'] ) ) : 'new';
		if ( array_key_exists( $status, self::statuses() ) ) {
			update_post_meta( $post_id, '_isha_status', $status );
		}
	}
}
