<?php
/**
 * Đặt lịch hẹn: xử lý form (admin-post), lưu CPT isha_appointment, gửi email, cột & meta box trong admin.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Booking {

	const MAX_PER_HOUR = 5;

	public static function init() {
		add_action( 'admin_post_nopriv_isha_booking', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_isha_booking', array( __CLASS__, 'handle' ) );

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
	 * Khung giờ khám.
	 *
	 * @return array
	 */
	public static function time_slots() {
		$slots = array();
		for ( $h = 8; $h <= 17; $h++ ) {
			$slots[] = sprintf( '%02d:00', $h );
			$slots[] = sprintf( '%02d:30', $h );
		}
		return $slots;
	}

	/**
	 * Xử lý form gửi lên.
	 */
	public static function handle() {
		$redirect = wp_get_referer() ? remove_query_arg( 'isha_booking', wp_get_referer() ) : home_url( '/' );

		$nonce = isset( $_POST['isha_booking_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['isha_booking_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'isha_booking' ) ) {
			self::finish( $redirect, 'error' );
		}

		// Honeypot: bot điền ô ẩn này.
		if ( ! empty( $_POST['isha_website'] ) ) {
			self::finish( $redirect, 'success' ); // Giả vờ thành công.
		}

		// Giới hạn tần suất theo IP.
		$ip_key = 'isha_bk_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
		$count  = (int) get_transient( $ip_key );
		if ( $count >= self::MAX_PER_HOUR ) {
			self::finish( $redirect, 'limit' );
		}

		$name    = isset( $_POST['isha_name'] ) ? sanitize_text_field( wp_unslash( $_POST['isha_name'] ) ) : '';
		$phone   = isset( $_POST['isha_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['isha_phone'] ) ) : '';
		$email   = isset( $_POST['isha_email'] ) ? sanitize_email( wp_unslash( $_POST['isha_email'] ) ) : '';
		$service = isset( $_POST['isha_service'] ) ? absint( $_POST['isha_service'] ) : 0;
		$date    = isset( $_POST['isha_date'] ) ? sanitize_text_field( wp_unslash( $_POST['isha_date'] ) ) : '';
		$time    = isset( $_POST['isha_time'] ) ? sanitize_text_field( wp_unslash( $_POST['isha_time'] ) ) : '';
		$note    = isset( $_POST['isha_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['isha_note'] ) ) : '';

		// Validate.
		$today = wp_date( 'Y-m-d' );
		$valid = '' !== $name
			&& mb_strlen( $name ) <= 100
			&& preg_match( '/^[0-9+()\s.\-]{8,20}$/', $phone )
			&& ( '' === $email || is_email( $email ) )
			&& preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && $date >= $today
			&& in_array( $time, self::time_slots(), true)
			&& mb_strlen( $note ) <= 1000;

		if ( $service && 'isha_service' !== get_post_type( $service ) ) {
			$valid = false;
		}
		if ( ! $valid ) {
			self::finish( $redirect, 'invalid' );
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
				),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			self::finish( $redirect, 'error' );
		}

		set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );
		self::notify( $post_id );
		self::finish( $redirect, 'success' );
	}

	private static function finish( $url, $status ) {
		wp_safe_redirect( add_query_arg( 'isha_booking', $status, $url ) . '#isha-booking' );
		exit;
	}

	/**
	 * Gửi email thông báo cho phòng khám.
	 */
	private static function notify( $post_id ) {
		$to      = isha_core_get( 'notify_email', get_option( 'admin_email' ) );
		$meta    = fn( $k ) => get_post_meta( $post_id, $k, true );
		$service = $meta( '_isha_service_id' ) ? get_the_title( $meta( '_isha_service_id' ) ) : '—';

		$body = implode(
			"\n",
			array(
				'Có lịch hẹn mới trên website:',
				'',
				'Họ tên: ' . $meta( '_isha_name' ),
				'Điện thoại: ' . $meta( '_isha_phone' ),
				'Email: ' . ( $meta( '_isha_email' ) ?: '—' ),
				'Dịch vụ: ' . $service,
				'Ngày giờ: ' . $meta( '_isha_date' ) . ' ' . $meta( '_isha_time' ),
				'Ghi chú: ' . ( $meta( '_isha_note' ) ?: '—' ),
				'',
				'Xem trong admin: ' . admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
			)
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		if ( $meta( '_isha_email' ) ) {
			$headers[] = 'Reply-To: ' . $meta( '_isha_email' );
		}

		/* translators: %s: site name */
		wp_mail( $to, sprintf( __( '[%s] Lịch hẹn mới', 'isha-core' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ), $body, $headers );
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
				echo esc_html( isset( $st[ $k ] ) ? $st[ $k ] : $st['new'] );
				break;
		}
	}

	public static function meta_box() {
		add_meta_box( 'isha_appt', __( 'Chi tiết lịch hẹn', 'isha-core' ), array( __CLASS__, 'render_box' ), 'isha_appointment', 'normal', 'high' );
	}

	public static function render_box( $post ) {
		wp_nonce_field( 'isha_appt_status', 'isha_appt_nonce' );
		$m = fn( $k ) => get_post_meta( $post->ID, $k, true );
		$sid = (int) $m( '_isha_service_id' );
		$rows = array(
			__( 'Họ tên', 'isha-core' )     => $m( '_isha_name' ),
			__( 'Điện thoại', 'isha-core' ) => $m( '_isha_phone' ),
			__( 'Email', 'isha-core' )      => $m( '_isha_email' ),
			__( 'Dịch vụ', 'isha-core' )    => $sid ? get_the_title( $sid ) : '',
			__( 'Ngày giờ', 'isha-core' )   => trim( $m( '_isha_date' ) . ' ' . $m( '_isha_time' ) ),
			__( 'Ghi chú', 'isha-core' )    => $m( '_isha_note' ),
		);
		echo '<table class="form-table" role="presentation">';
		foreach ( $rows as $label => $value ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( $value ?: '—' ) ) . '</td></tr>';
		}
		$current = $m( '_isha_status' ) ?: 'new';
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
