<?php
/**
 * Liên hệ: hộp thư CPT isha_contact (chỉ trong admin), xử lý form liên hệ, gửi email báo phòng khám, cột và meta box. 18/11/2026
 * (docs/home-architecture.md mục 17).
 *
 * Hai đường gửi dùng chung ::process(): admin-post.php và admin-ajax.php (action isha_contact), như form đặt lịch.
 * Trường: isha_name*, isha_phone*, isha_email, isha_topic (khoá của ::topics()), isha_message* (10 đến 1500 ký tự), isha_consent* ('1'),
 * isha_contact_nonce (action isha_contact; form có JS lấy mới qua ISHA_Core_Forms), isha_website (bẫy bot, phải rỗng).
 * Meta: _isha_name, _isha_phone, _isha_email, _isha_topic, _isha_message, _isha_status (new|replied|done), _isha_consent_at.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Contact {

	const POST_TYPE    = 'isha_contact';
	const MAX_PER_HOUR = 5;
	const MESSAGE_MIN  = 10;
	const MESSAGE_MAX  = 1500;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_nopriv_isha_contact', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_isha_contact', array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_isha_contact', array( __CLASS__, 'ajax' ) );
		add_action( 'wp_ajax_isha_contact', array( __CLASS__, 'ajax' ) );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_status' ) );
	}

	/**
	 * Hộp thư Liên hệ: không có trang ngoài website, không tạo tay (chỉ nhận từ form).
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Liên hệ', 'isha-core' ),
					'singular_name' => __( 'Lời nhắn', 'isha-core' ),
					'menu_name'     => __( 'Liên hệ', 'isha-core' ),
					'edit_item'     => __( 'Chi tiết lời nhắn', 'isha-core' ),
					'all_items'     => __( 'Tất cả lời nhắn', 'isha-core' ),
					'search_items'  => __( 'Tìm lời nhắn', 'isha-core' ),
					'not_found'     => __( 'Chưa có lời nhắn nào.', 'isha-core' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'exclude_from_search' => true,
				'show_in_rest'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'menu_icon'           => 'dashicons-email-alt',
				'menu_position'       => 29,
				'supports'            => array( 'title' ),
				'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'        => true,
			)
		);
	}

	public static function statuses() {
		return array(
			'new'     => __( 'Mới', 'isha-core' ),
			'replied' => __( 'Đã liên hệ lại', 'isha-core' ),
			'done'    => __( 'Đã xong', 'isha-core' ),
		);
	}

	/**
	 * Chủ đề lời nhắn (khoá => nhãn). Đổi bằng bộ lọc `isha_core/contact_topics`.
	 *
	 * @return array<string,string>
	 */
	public static function topics() {
		$topics   = array(
			'tu-van'     => __( 'Tư vấn liệu trình', 'isha-core' ),
			'thanh-vien' => __( 'Chính sách khách hàng thành viên', 'isha-core' ),
			'phan-hoi'   => __( 'Góp ý, phản hồi dịch vụ', 'isha-core' ),
			'hop-tac'    => __( 'Hợp tác, truyền thông', 'isha-core' ),
			'khac'       => __( 'Khác', 'isha-core' ),
		);
		$filtered = apply_filters( 'isha_core/contact_topics', $topics );
		return is_array( $filtered ) && $filtered ? $filtered : $topics;
	}

	public static function messages() {
		return array(
			'success' => __( 'Cảm ơn bạn đã gửi lời nhắn. ISHA sẽ phản hồi trong vòng một ngày làm việc.', 'isha-core' ),
			'invalid' => __( 'Thông tin chưa hợp lệ. Vui lòng kiểm tra các ô được đánh dấu.', 'isha-core' ),
			'limit'   => __( 'Bạn đã gửi quá nhiều lời nhắn trong một giờ. Vui lòng thử lại sau hoặc gọi hotline.', 'isha-core' ),
			'error'   => __( 'Phiên gửi đã hết hạn hoặc có lỗi xảy ra. Vui lòng tải lại trang và thử lại.', 'isha-core' ),
		);
	}

	/**
	 * Xử lý một lần gửi (dùng chung cho admin-post và AJAX).
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

		$nonce = isset( $in['isha_contact_nonce'] ) ? sanitize_text_field( (string) $in['isha_contact_nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'isha_contact' ) ) {
			return $result;
		}
		if ( ! empty( $in['isha_website'] ) ) {
			$result['status'] = 'success';
			return $result;
		}
		if ( ISHA_Core_Forms::limited( 'contact', self::MAX_PER_HOUR ) ) {
			$result['status'] = 'limit';
			return $result;
		}

		$name    = isset( $in['isha_name'] ) ? trim( sanitize_text_field( (string) $in['isha_name'] ) ) : '';
		$phone   = ISHA_Core_Forms::phone( isset( $in['isha_phone'] ) ? $in['isha_phone'] : '' );
		$email   = isset( $in['isha_email'] ) ? trim( (string) $in['isha_email'] ) : '';
		$topic   = isset( $in['isha_topic'] ) ? sanitize_key( (string) $in['isha_topic'] ) : '';
		$message = isset( $in['isha_message'] ) ? trim( sanitize_textarea_field( (string) $in['isha_message'] ) ) : '';

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
		if ( '' !== $topic && ! array_key_exists( $topic, self::topics() ) ) {
			$errors['isha_topic'] = __( 'Chủ đề không hợp lệ, vui lòng chọn lại.', 'isha-core' );
		}
		$length = mb_strlen( $message );
		if ( $length < self::MESSAGE_MIN ) {
			$errors['isha_message'] = __( 'Vui lòng viết lời nhắn (ít nhất 10 ký tự).', 'isha-core' );
		} elseif ( $length > self::MESSAGE_MAX ) {
			$errors['isha_message'] = __( 'Lời nhắn tối đa 1500 ký tự.', 'isha-core' );
		}
		if ( ! ISHA_Core_Forms::consented( $in ) ) {
			$errors['isha_consent'] = __( 'Vui lòng đồng ý để ISHA dùng thông tin này để phản hồi bạn.', 'isha-core' );
		}
		if ( $errors ) {
			$result['status'] = 'invalid';
			$result['errors'] = $errors;
			return $result;
		}

		$topics  = self::topics();
		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				/* translators: 1: họ tên, 2: chủ đề */
				'post_title'  => '' !== $topic ? sprintf( __( '%1$s – %2$s', 'isha-core' ), $name, $topics[ $topic ] ) : $name,
				'meta_input'  => array(
					'_isha_name'       => $name,
					'_isha_phone'      => $phone,
					'_isha_email'      => $email,
					'_isha_topic'      => $topic,
					'_isha_message'    => $message,
					'_isha_status'     => 'new',
					'_isha_consent_at' => current_time( 'mysql' ),
				),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return $result;
		}

		ISHA_Core_Forms::bump( 'contact' );
		ISHA_Core_Forms::notify(
			/* translators: 1: tên website, 2: chủ đề */
			sprintf( __( '[%1$s] Liên hệ mới: %2$s', 'isha-core' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), '' !== $topic ? $topics[ $topic ] : __( 'Không chọn chủ đề', 'isha-core' ) ),
			array(
				'Có lời nhắn mới từ trang Liên hệ:',
				'',
				'Họ tên: ' . $name,
				'Điện thoại: ' . $phone,
				'Email: ' . ( '' !== $email ? $email : '—' ),
				'Chủ đề: ' . ( '' !== $topic ? $topics[ $topic ] : '—' ),
				'',
				$message,
				'',
				'Xem trong admin: ' . admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
			),
			$email
		);

		$result['status'] = 'success';
		$result['id']     = (int) $post_id;
		return $result;
	}

	/**
	 * Gửi thường (admin-post): xử lý rồi chuyển hướng về trang trước kèm ?isha_contact=<trạng thái>.
	 */
	public static function handle() {
		$redirect = wp_get_referer() ? remove_query_arg( 'isha_contact', wp_get_referer() ) : home_url( '/' );
		$result   = self::process( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- process() kiểm nonce và làm sạch từng trường.
		wp_safe_redirect( add_query_arg( 'isha_contact', $result['status'], $redirect ) . '#isha-contact-form' );
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

	/* --- Admin ---------------------------------------------------------- */

	public static function columns( $cols ) {
		return array(
			'cb'           => isset( $cols['cb'] ) ? $cols['cb'] : '',
			'title'        => __( 'Người gửi', 'isha-core' ),
			'isha_phone'   => __( 'Điện thoại', 'isha-core' ),
			'isha_email'   => __( 'Email', 'isha-core' ),
			'isha_topic'   => __( 'Chủ đề', 'isha-core' ),
			'isha_status'  => __( 'Trạng thái', 'isha-core' ),
			'date'         => __( 'Gửi lúc', 'isha-core' ),
		);
	}

	public static function column_content( $col, $post_id ) {
		switch ( $col ) {
			case 'isha_phone':
			case 'isha_email':
				echo esc_html( (string) get_post_meta( $post_id, '_' . $col, true ) );
				break;
			case 'isha_topic':
				$topics = self::topics();
				$key    = (string) get_post_meta( $post_id, '_isha_topic', true );
				echo esc_html( isset( $topics[ $key ] ) ? $topics[ $key ] : '—' );
				break;
			case 'isha_status':
				$st = self::statuses();
				$k  = (string) get_post_meta( $post_id, '_isha_status', true );
				echo esc_html( isset( $st[ $k ] ) ? $st[ $k ] : $st['new'] );
				break;
		}
	}

	public static function meta_box() {
		add_meta_box( 'isha_contact_box', __( 'Chi tiết lời nhắn', 'isha-core' ), array( __CLASS__, 'render_box' ), self::POST_TYPE, 'normal', 'high' );
	}

	public static function render_box( $post ) {
		wp_nonce_field( 'isha_contact_status', 'isha_contact_status_nonce' );
		$m      = fn( $k ) => (string) get_post_meta( $post->ID, $k, true );
		$topics = self::topics();
		$rows   = array(
			__( 'Họ tên', 'isha-core' )               => $m( '_isha_name' ),
			__( 'Điện thoại', 'isha-core' )           => $m( '_isha_phone' ),
			__( 'Email', 'isha-core' )                => $m( '_isha_email' ),
			__( 'Chủ đề', 'isha-core' )               => isset( $topics[ $m( '_isha_topic' ) ] ) ? $topics[ $m( '_isha_topic' ) ] : '',
			__( 'Lời nhắn', 'isha-core' )             => $m( '_isha_message' ),
			__( 'Đồng ý xử lý dữ liệu', 'isha-core' ) => $m( '_isha_consent_at' ) ? sprintf( /* translators: %s: thời điểm */ __( 'Có, lúc %s', 'isha-core' ), $m( '_isha_consent_at' ) ) : '',
		);
		echo '<table class="form-table" role="presentation">';
		foreach ( $rows as $label => $value ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( '' !== $value ? $value : '—' ) ) . '</td></tr>';
		}
		$current = '' !== $m( '_isha_status' ) ? $m( '_isha_status' ) : 'new';
		echo '<tr><th><label for="isha_contact_status">' . esc_html__( 'Trạng thái', 'isha-core' ) . '</label></th><td><select id="isha_contact_status" name="isha_contact_status">';
		foreach ( self::statuses() as $k => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $current, $k, false ), esc_html( $label ) );
		}
		echo '</select>';
		if ( '' !== $m( '_isha_phone' ) ) {
			echo ' <a class="button" href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $m( '_isha_phone' ) ) ) . '">' . esc_html__( 'Gọi lại', 'isha-core' ) . '</a>';
		}
		if ( '' !== $m( '_isha_email' ) ) {
			echo ' <a class="button" href="' . esc_url( 'mailto:' . $m( '_isha_email' ) ) . '">' . esc_html__( 'Trả lời email', 'isha-core' ) . '</a>';
		}
		echo '</td></tr></table>';
	}

	public static function save_status( $post_id ) {
		if ( ! isset( $_POST['isha_contact_status_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['isha_contact_status_nonce'] ) ), 'isha_contact_status' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		$status = isset( $_POST['isha_contact_status'] ) ? sanitize_key( wp_unslash( $_POST['isha_contact_status'] ) ) : 'new';
		if ( array_key_exists( $status, self::statuses() ) ) {
			update_post_meta( $post_id, '_isha_status', $status );
		}
	}
}
