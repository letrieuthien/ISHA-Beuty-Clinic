<?php
/**
 * Trường dữ liệu của Lời kể khách hàng (isha_testimonial).
 *
 * Meta box native (không dùng ACF). Tên hiển thị của khách là tiêu đề bài; ảnh chân dung (tuỳ chọn) là ảnh đại diện.
 *   _isha_tm_quote       – lời kể, tối đa 300 ký tự
 *   _isha_tm_service_id  – ID bài isha_service (liệu trình đã dùng)
 * Mọi lời kể đã xuất bản và có nội dung đều hiển thị (nội dung là lời kể của doanh nghiệp, không phải của khách thật nên không có ô "đồng ý công khai";
 * khoá cũ _isha_tm_consent nếu còn trong DB thì bị bỏ qua).
 * Hàm đọc chỉ dùng get_post_meta, không phụ thuộc ACF.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Testimonial_Fields {

	const POST_TYPE    = 'isha_testimonial';
	const NONCE_ACTION = 'isha_save_testimonial';
	const NONCE_FIELD  = 'isha_testimonial_nonce';

	const META_QUOTE      = '_isha_tm_quote';
	const META_SERVICE_ID = '_isha_tm_service_id';

	/**
	 * Số ký tự tối đa của lời kể; số liệu trình tối đa trong ô chọn; trạng thái của liệu trình được phép chọn.
	 */
	const QUOTE_MAX        = 300;
	const SERVICE_LIMIT    = 200;
	const SERVICE_STATUSES = array( 'publish', 'draft', 'pending', 'private' );

	/**
	 * Handle script đếm ký tự (không có tệp: mã nằm trong wp_add_inline_script).
	 */
	const SCRIPT_HANDLE = 'isha-tm-counter';

	/**
	 * Gắn hook (gọi một lần từ isha_core_init()).
	 */
	public static function init() {
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
	}

	/* --- Đọc dữ liệu (không phụ thuộc ACF) -------------------------------- */

	/**
	 * Dữ liệu lời kể để hiển thị. Chỉ đọc; giá trị được làm sạch lại khi đọc.
	 * `service_id` là 0 (và `service_title` rỗng) nếu chưa chọn hoặc bài được chọn không còn là liệu trình hợp lệ.
	 *
	 * @param int $post_id ID lời kể.
	 * @return array{name:string,quote:string,service_id:int,service_title:string}
	 */
	public static function get( $post_id ) {
		$post_id    = absint( $post_id );
		$post       = $post_id ? get_post( $post_id ) : null;
		$service_id = self::clean_service_id( self::meta( $post_id, self::META_SERVICE_ID ) );
		$service    = $service_id ? get_post( $service_id ) : null;

		return array(
			'name'          => $post ? trim( wp_strip_all_tags( $post->post_title ) ) : '',
			'quote'         => self::sanitize_quote( self::meta( $post_id, self::META_QUOTE ) ),
			'service_id'    => $service_id,
			'service_title' => $service ? trim( wp_strip_all_tags( $service->post_title ) ) : '',
		);
	}

	/**
	 * Giá trị meta dạng chuỗi ('' nếu thiếu hoặc không phải giá trị đơn).
	 *
	 * @param int    $post_id ID bài.
	 * @param string $key     Khoá meta.
	 * @return string
	 */
	private static function meta( $post_id, $key ) {
		$value = get_post_meta( $post_id, $key, true );
		return is_scalar( $value ) ? (string) $value : '';
	}

	/* --- Làm sạch (hàm thuần, dùng khi lưu và khi đọc) --------------------- */

	/**
	 * Làm sạch lời kể: bỏ thẻ HTML, giữ xuống dòng, cắt ở QUOTE_MAX ký tự bằng mb_substr.
	 * Xuống dòng CRLF của trình duyệt được đổi về LF trước khi cắt để khớp bộ đếm và thuộc tính maxlength.
	 *
	 * @param mixed $raw Giá trị thô (đã bỏ slash).
	 * @return string
	 */
	public static function sanitize_quote( $raw ) {
		if ( ! is_scalar( $raw ) ) {
			return '';
		}
		$text = sanitize_textarea_field( str_replace( array( "\r\n", "\r" ), "\n", (string) $raw ) );
		return trim( mb_substr( $text, 0, self::QUOTE_MAX ) );
	}

	/**
	 * Làm sạch ID liệu trình: phải là bài isha_service đang ở một trong SERVICE_STATUSES.
	 *
	 * @param string $raw Giá trị thô.
	 * @return int ID hoặc 0.
	 */
	private static function clean_service_id( $raw ) {
		if ( ! preg_match( '/^\d{1,18}$/D', $raw ) ) {
			return 0;
		}
		$id   = (int) $raw;
		$post = $id > 0 ? get_post( $id ) : null;
		return ( $post && 'isha_service' === $post->post_type && in_array( $post->post_status, self::SERVICE_STATUSES, true ) ) ? $id : 0;
	}

	/* --- Meta box --------------------------------------------------------- */

	public static function add_meta_box() {
		add_meta_box( 'isha_testimonial_fields', __( 'Lời kể khách hàng', 'isha-core' ), array( __CLASS__, 'render' ), self::POST_TYPE, 'normal', 'high' );
	}

	/**
	 * In meta box.
	 *
	 * @param WP_Post $post Lời kể đang sửa.
	 */
	public static function render( $post ) {
		$data    = self::get( $post->ID );
		$options = self::service_options();

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD, false );
		?>
		<p class="description"><?php esc_html_e( 'Tên hiển thị của khách là tiêu đề của bài; ảnh chân dung (tuỳ chọn) đặt ở “Ảnh chân dung” bên phải.', 'isha-core' ); ?></p>
		<table class="form-table isha-fields" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="isha_tm_quote"><?php esc_html_e( 'Lời kể', 'isha-core' ); ?></label></th>
					<td>
						<textarea id="isha_tm_quote" name="isha_tm_quote" class="large-text" rows="4" maxlength="<?php echo esc_attr( (string) self::QUOTE_MAX ); ?>" aria-describedby="isha_tm_quote_desc"><?php echo esc_textarea( $data['quote'] ); ?></textarea>
						<p class="description" id="isha_tm_quote_desc">
							<?php esc_html_e( 'Lời khách kể bằng giọng của họ, tối đa 300 ký tự.', 'isha-core' ); ?>
							<span class="isha-tm-count"><span id="isha_tm_quote_count"><?php echo esc_html( (string) mb_strlen( $data['quote'] ) ); ?></span> / <?php echo esc_html( (string) self::QUOTE_MAX ); ?> <?php esc_html_e( 'ký tự', 'isha-core' ); ?></span>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="isha_tm_service_id"><?php esc_html_e( 'Liệu trình đã dùng', 'isha-core' ); ?></label></th>
					<td>
						<select id="isha_tm_service_id" name="isha_tm_service_id" aria-describedby="isha_tm_service_desc">
							<option value="0"<?php selected( $data['service_id'], 0 ); ?>><?php esc_html_e( 'Không chọn', 'isha-core' ); ?></option>
							<?php foreach ( $options as $service_id => $label ) : ?>
								<option value="<?php echo esc_attr( (string) $service_id ); ?>"<?php selected( $data['service_id'], $service_id ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description" id="isha_tm_service_desc"><?php esc_html_e( 'Tên liệu trình hiển thị dưới tên khách. Danh sách gồm các liệu trình đã xuất bản, nháp, chờ duyệt và riêng tư, kèm tên nhóm trong ngoặc.', 'isha-core' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Danh sách liệu trình cho ô chọn: id => "Tên liệu trình (Tên nhóm)". Một truy vấn bài và một lần nạp nhóm cho tất cả
	 * (update_post_term_cache), nên get_the_terms() không phát sinh truy vấn theo từng liệu trình.
	 *
	 * @return array<int,string>
	 */
	private static function service_options() {
		$services = get_posts(
			array(
				'post_type'              => 'isha_service',
				'post_status'            => self::SERVICE_STATUSES,
				'posts_per_page'         => self::SERVICE_LIMIT,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => true,
			)
		);

		$options = array();
		foreach ( $services as $service ) {
			$title = trim( wp_strip_all_tags( $service->post_title ) );
			$title = '' !== $title ? $title : __( '(không có tiêu đề)', 'isha-core' );
			$terms = get_the_terms( $service, 'isha_service_cat' );
			$group = is_array( $terms ) ? implode( ', ', wp_list_pluck( $terms, 'name' ) ) : '';

			$options[ (int) $service->ID ] = '' !== $group ? $title . ' (' . $group . ')' : $title;
		}
		return $options;
	}

	/* --- Lưu -------------------------------------------------------------- */

	/**
	 * Lưu các trường (hook save_post_isha_testimonial). Bỏ qua khi thiếu hoặc sai nonce, autosave, revision hoặc không có quyền sửa bài.
	 * Ô để trống thì xoá meta tương ứng.
	 *
	 * @param int $post_id ID lời kể.
	 */
	public static function save( $post_id ) {
		if ( ! self::can_save( $post_id ) ) {
			return;
		}

		// sanitize_quote() chạy sanitize_textarea_field() rồi cắt theo QUOTE_MAX.
		// wpstyle:ignore superglobal-input
		$quote = isset( $_POST['isha_tm_quote'] ) ? self::sanitize_quote( wp_unslash( $_POST['isha_tm_quote'] ) ) : '';
		self::set_meta( $post_id, self::META_QUOTE, $quote );

		$service_id = isset( $_POST['isha_tm_service_id'] ) ? self::clean_service_id( sanitize_text_field( wp_unslash( $_POST['isha_tm_service_id'] ) ) ) : 0;
		self::set_meta( $post_id, self::META_SERVICE_ID, $service_id );
	}

	/**
	 * Kiểm tra nonce, autosave, revision và quyền sửa bài.
	 *
	 * @param int $post_id ID bài.
	 * @return bool
	 */
	private static function can_save( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return false;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return false;
		}
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Ghi một meta; giá trị rỗng ('' hoặc 0) thì xoá meta cho gọn. Slash trước khi lưu vì core tự unslash.
	 *
	 * @param int        $post_id ID bài.
	 * @param string     $key     Khoá meta.
	 * @param string|int $value   Giá trị.
	 */
	private static function set_meta( $post_id, $key, $value ) {
		if ( '' === $value || 0 === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}
		update_post_meta( $post_id, $key, wp_slash( $value ) );
	}

	/* --- Asset ------------------------------------------------------------ */

	/**
	 * Nạp script đếm ký tự, chỉ ở màn hình thêm/sửa lời kể.
	 *
	 * @param string $hook Hook của trang admin.
	 */
	public static function admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}
		wp_register_script( self::SCRIPT_HANDLE, false, array(), ISHA_CORE_VERSION, true );
		wp_add_inline_script( self::SCRIPT_HANDLE, self::counter_script() );
		wp_enqueue_script( self::SCRIPT_HANDLE );
	}

	/**
	 * Mã JS (thuần) cập nhật số ký tự đã nhập. Dùng ủy quyền sự kiện trên document nên vẫn chạy nếu meta box bị chuyển chỗ.
	 *
	 * @return string
	 */
	private static function counter_script() {
		return <<<'JS'
( function () {
	var FIELD_ID = 'isha_tm_quote';
	var COUNT_ID = 'isha_tm_quote_count';

	function update() {
		var field = document.getElementById( FIELD_ID );
		var count = document.getElementById( COUNT_ID );
		if ( field && count ) {
			count.textContent = String( field.value.length );
		}
	}

	document.addEventListener( 'input', function ( event ) {
		if ( event.target && event.target.id === FIELD_ID ) {
			update();
		}
	} );
	update();
}() );
JS;
	}
}
