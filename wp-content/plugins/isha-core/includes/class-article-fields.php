<?php
/**
 * Trường dữ liệu của Bài viết kiến thức (isha_article).
 *
 * Meta box native (không dùng ACF). Khoá meta bắt đầu bằng gạch dưới:
 *   _isha_article_featured  – '1' khi là bài nổi bật
 *   _isha_article_summary   – nội dung ngắn (một dòng, tối đa 200 ký tự) cho thẻ bài
 * Hàm đọc chỉ dùng get_post_meta, không phụ thuộc ACF.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Article_Fields {

	const POST_TYPE    = 'isha_article';
	const NONCE_ACTION = 'isha_save_article';
	const NONCE_FIELD  = 'isha_article_nonce';

	const META_FEATURED = '_isha_article_featured';
	const META_SUMMARY  = '_isha_article_summary';

	/**
	 * Độ dài tối đa của nội dung ngắn (ký tự).
	 */
	const SUMMARY_MAX   = 200;

	/**
	 * Gắn hook (gọi một lần từ isha_core_init()).
	 */
	public static function init() {
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
	}

	/* --- Đọc dữ liệu (không phụ thuộc ACF) -------------------------------- */

	/**
	 * Dữ liệu bài viết để hiển thị. Chỉ đọc; giá trị được làm sạch lại khi đọc.
	 * `summary` là nội dung ngắn một dòng đã làm sạch (rỗng nếu chưa có).
	 *
	 * @param int $post_id ID bài viết.
	 * @return array{featured:bool,summary:string}
	 */
	public static function get( $post_id ) {
		$post_id = absint( $post_id );

		return array(
			'featured' => '1' === self::meta( $post_id, self::META_FEATURED ),
			'summary'  => self::clean_summary( self::meta( $post_id, self::META_SUMMARY ) ),
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

	/* --- Tính toán và làm sạch (hàm thuần) --------------------------------- */

	/**
	 * Làm sạch nội dung ngắn: bỏ thẻ HTML, gộp mọi khoảng trắng và xuống dòng thành MỘT dấu cách (nội dung ngắn là một
	 * đoạn một dòng), cắt ở SUMMARY_MAX ký tự (mb_substr nên không vỡ UTF-8 tiếng Việt có dấu).
	 *
	 * @param mixed $raw Giá trị thô.
	 * @return string
	 */
	private static function clean_summary( $raw ) {
		// sanitize_text_field() bỏ thẻ HTML và gộp dấu cách, tab, xuống dòng ASCII; preg_replace() gộp thêm khoảng trắng
		// Unicode (ví dụ dấu cách không ngắt U+00A0 khi dán từ web hoặc Word). Kết quả lỗi (null) thì coi như rỗng.
		$text = (string) preg_replace( '/\s+/u', ' ', sanitize_text_field( $raw ) );
		return trim( mb_substr( $text, 0, self::SUMMARY_MAX, 'UTF-8' ) );
	}

	/* --- Meta box --------------------------------------------------------- */

	public static function add_meta_box() {
		add_meta_box( 'isha_article_fields', __( 'Thông tin bài viết', 'isha-core' ), array( __CLASS__, 'render' ), self::POST_TYPE, 'normal', 'high' );
	}

	/**
	 * In meta box.
	 *
	 * @param WP_Post $post Bài viết đang sửa.
	 */
	public static function render( $post ) {
		$data = self::get( $post->ID );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD, false );
		?>
		<table class="form-table isha-fields" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="isha_article_summary"><?php esc_html_e( 'Nội dung ngắn', 'isha-core' ); ?></label></th>
					<td>
						<textarea id="isha_article_summary" name="isha_article_summary" class="large-text" rows="3" maxlength="<?php echo esc_attr( (string) self::SUMMARY_MAX ); ?>" aria-describedby="isha_article_summary_desc"><?php echo esc_textarea( $data['summary'] ); ?></textarea>
						<p class="description" id="isha_article_summary_desc"><?php esc_html_e( 'Đoạn mô tả ngắn (1–2 câu, tối đa 200 ký tự) hiện trên thẻ bài ở trang danh sách và trang chủ. Để trống thì dùng Đoạn trích (Excerpt) hoặc đoạn đầu của bài.', 'isha-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Nổi bật', 'isha-core' ); ?></th>
					<td>
						<label for="isha_article_featured">
							<input type="checkbox" id="isha_article_featured" name="isha_article_featured" value="1" <?php checked( $data['featured'] ); ?> aria-describedby="isha_article_featured_desc">
							<?php esc_html_e( 'Bài nổi bật', 'isha-core' ); ?>
						</label>
						<p class="description" id="isha_article_featured_desc"><?php esc_html_e( 'Bài nổi bật đứng đầu ở section Tin tức trên trang chủ.', 'isha-core' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/* --- Lưu -------------------------------------------------------------- */

	/**
	 * Hook save_post_isha_article (chạy cả khi lưu qua block editor, nơi yêu cầu REST không mang nonce của meta box).
	 *
	 * Nội dung ngắn và cờ nổi bật chỉ lưu khi có nonce đúng và quyền sửa bài; riêng nội dung ngắn chỉ đổi khi ô có trong dữ liệu
	 * gửi lên (thiếu khoá thì giữ giá trị cũ, có khoá mà rỗng thì xoá meta). Bỏ qua autosave, revision và bản nháp tự động.
	 *
	 * @param int          $post_id ID bài viết.
	 * @param WP_Post|null $post    Bài viết (tự lấy nếu không truyền).
	 */
	public static function save( $post_id, $post = null ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! ( $post instanceof WP_Post ) ) {
			$post = get_post( $post_id );
		}
		if ( ! $post || self::POST_TYPE !== $post->post_type || 'auto-draft' === $post->post_status ) {
			return;
		}


		if ( ! self::verify_request( $post_id ) ) {
			return;
		}

		self::set_meta( $post_id, self::META_FEATURED, isset( $_POST['isha_article_featured'] ) ? '1' : '' );

		// Nội dung ngắn: thiếu khoá trong dữ liệu gửi lên thì giữ giá trị cũ; có khoá mà rỗng thì xoá meta.
		if ( isset( $_POST['isha_article_summary'] ) ) {
			self::set_meta( $post_id, self::META_SUMMARY, self::clean_summary( sanitize_textarea_field( wp_unslash( $_POST['isha_article_summary'] ) ) ) );
		}
	}

	/**
	 * Kiểm tra nonce của meta box và quyền sửa bài.
	 *
	 * @param int $post_id ID bài.
	 * @return bool
	 */
	private static function verify_request( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return false;
		}
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Ghi một meta; giá trị rỗng ('') thì xoá meta cho gọn. Slash trước khi lưu vì core tự unslash.
	 *
	 * @param int    $post_id ID bài.
	 * @param string $key     Khoá meta.
	 * @param string $value   Giá trị.
	 */
	private static function set_meta( $post_id, $key, $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}
		update_post_meta( $post_id, $key, wp_slash( $value ) );
	}

	/* --- Cột trong danh sách ---------------------------------------------- */

	/**
	 * Thêm cột Nổi bật ngay sau cột tiêu đề.
	 *
	 * @param array $columns Các cột mặc định.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['isha_article_featured'] = __( 'Nổi bật', 'isha-core' );
			}
		}
		if ( ! isset( $new['isha_article_featured'] ) ) {
			$new['isha_article_featured'] = __( 'Nổi bật', 'isha-core' );
		}
		return $new;
	}

	/**
	 * Nội dung cột. Chỉ đọc meta (đã được nạp sẵn cho cả trang danh sách), không truy vấn thêm theo từng dòng.
	 *
	 * @param string $column  Khoá cột.
	 * @param int    $post_id ID bài viết.
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'isha_article_featured' !== $column ) {
			return;
		}
		if ( '1' === self::meta( $post_id, self::META_FEATURED ) ) {
			echo esc_html__( 'Có', 'isha-core' );
		} else {
			echo '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Không', 'isha-core' ) . '</span>';
		}
	}
}
