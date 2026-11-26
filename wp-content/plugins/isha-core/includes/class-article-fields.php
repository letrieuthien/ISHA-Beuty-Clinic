<?php
/**
 * Trường dữ liệu của Bài viết kiến thức (isha_article).
 *
 * Meta box native (không dùng ACF). Khoá meta bắt đầu bằng gạch dưới:
 *   _isha_article_featured  – '1' khi là bài nổi bật
 *   _isha_article_summary   – nội dung ngắn (một dòng, tối đa 200 ký tự) cho thẻ bài
 *   _isha_article_banner    – ID ảnh banner ngang 16:10 (bài nổi bật ở section Tin tức của trang chủ; 20/11/2026)
 * Hàm đọc chỉ dùng get_post_meta, không phụ thuộc ACF.
 *
 * Meta box luôn nằm ở vùng chính (dưới nội dung). Thứ tự hộp người dùng đã lưu có thể đưa nó sang cột bên (`side`): trình soạn
 * thảo khối khi đó dựng nó trong thanh bên (đang thu gọn) và vùng meta chính bên dưới trống. keep_in_main() bỏ hộp khỏi `side`
 * trong thứ tự đã lưu nên hộp luôn về vị trí đăng ký (`normal`).
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Article_Fields {

	const POST_TYPE    = 'isha_article';
	const BOX_ID       = 'isha_article_fields';
	const NONCE_ACTION = 'isha_save_article';
	const NONCE_FIELD  = 'isha_article_nonce';

	const META_FEATURED = '_isha_article_featured';
	const META_SUMMARY  = '_isha_article_summary';
	const META_BANNER   = '_isha_article_banner';

	/**
	 * Term meta của chủ đề tin tức (isha_article_cat): ID nhóm dịch vụ (isha_service_cat) gợi ý ở cuối bài (khối "Liệu trình gợi ý").
	 */
	const TERM_TAX      = 'isha_article_cat';
	const TERM_META_SVC = '_isha_service_group';
	const TERM_FIELD    = 'isha_article_cat_service_group';
	const TERM_NONCE    = 'isha_article_cat_nonce';

	/**
	 * Độ dài tối đa của nội dung ngắn (ký tự).
	 */
	const SUMMARY_MAX   = 200;

	/**
	 * Gắn hook (gọi một lần từ isha_core_init()).
	 */
	public static function init() {
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'add_meta_box' ) );
		add_filter( 'get_user_option_meta-box-order_' . self::POST_TYPE, array( __CLASS__, 'keep_in_main' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );

		// Chủ đề tin tức: chọn nhóm dịch vụ gợi ý ở cuối bài.
		add_action( self::TERM_TAX . '_add_form_fields', array( __CLASS__, 'render_term_add' ) );
		add_action( self::TERM_TAX . '_edit_form_fields', array( __CLASS__, 'render_term_edit' ) );
		add_action( 'created_' . self::TERM_TAX, array( __CLASS__, 'save_term' ) );
		add_action( 'edited_' . self::TERM_TAX, array( __CLASS__, 'save_term' ) );
	}

	/* --- Chủ đề tin tức: nhóm dịch vụ gợi ý ------------------------------- */

	/**
	 * ID nhóm dịch vụ (isha_service_cat) mà chủ đề tin tức gợi ý ở cuối bài; 0 nếu chưa chọn hoặc nhóm không còn tồn tại.
	 *
	 * @param int $term_id ID chủ đề tin tức (isha_article_cat).
	 * @return int
	 */
	public static function service_group_of( $term_id ) {
		$term_id = absint( $term_id );
		if ( $term_id < 1 ) {
			return 0;
		}
		$raw = get_term_meta( $term_id, self::TERM_META_SVC, true );
		$id  = is_scalar( $raw ) && preg_match( '/^\d{1,18}$/D', (string) $raw ) ? (int) $raw : 0;
		return ( $id > 0 && taxonomy_exists( 'isha_service_cat' ) && get_term( $id, 'isha_service_cat' ) instanceof WP_Term ) ? $id : 0;
	}

	/**
	 * Các nhóm dịch vụ để chọn (theo thứ tự hiển thị của trang chủ nếu có).
	 *
	 * @return WP_Term[]
	 */
	private static function service_groups() {
		if ( ! taxonomy_exists( 'isha_service_cat' ) ) {
			return array();
		}
		if ( class_exists( 'ISHA_Core_Term_Fields' ) && method_exists( 'ISHA_Core_Term_Fields', 'ordered_terms' ) ) {
			return ISHA_Core_Term_Fields::ordered_terms();
		}
		$terms = get_terms(
			array(
				'taxonomy'   => 'isha_service_cat',
				'hide_empty' => false,
			)
		);
		return is_array( $terms ) ? array_filter( $terms, static function ( $t ) {
			return $t instanceof WP_Term;
		} ) : array();
	}

	/**
	 * Ô chọn nhóm dịch vụ (dùng chung cho màn Thêm và Sửa chủ đề).
	 *
	 * @param int $current ID nhóm đang chọn.
	 */
	private static function render_term_select( $current ) {
		wp_nonce_field( self::NONCE_ACTION . '_term', self::TERM_NONCE, false );
		?>
		<select name="<?php echo esc_attr( self::TERM_FIELD ); ?>" id="<?php echo esc_attr( self::TERM_FIELD ); ?>" aria-describedby="<?php echo esc_attr( self::TERM_FIELD ); ?>_desc">
			<option value="0"><?php esc_html_e( '— Chưa chọn (dẫn tới trang Dịch vụ) —', 'isha-core' ); ?></option>
			<?php foreach ( self::service_groups() as $group ) : ?>
				<option value="<?php echo esc_attr( (string) $group->term_id ); ?>" <?php selected( $current, (int) $group->term_id ); ?>><?php echo esc_html( $group->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description" id="<?php echo esc_attr( self::TERM_FIELD ); ?>_desc"><?php esc_html_e( 'Khối "Liệu trình gợi ý" ở cuối mọi bài thuộc chủ đề này sẽ dẫn tới nhóm dịch vụ đã chọn. Chưa chọn thì dẫn tới trang Dịch vụ.', 'isha-core' ); ?></p>
		<?php
	}

	/**
	 * Màn "Thêm chủ đề".
	 */
	public static function render_term_add() {
		?>
		<div class="form-field">
			<label for="<?php echo esc_attr( self::TERM_FIELD ); ?>"><?php esc_html_e( 'Nhóm dịch vụ gợi ý', 'isha-core' ); ?></label>
			<?php self::render_term_select( 0 ); ?>
		</div>
		<?php
	}

	/**
	 * Màn "Sửa chủ đề".
	 *
	 * @param WP_Term $term Chủ đề đang sửa.
	 */
	public static function render_term_edit( $term ) {
		?>
		<tr class="form-field">
			<th scope="row"><label for="<?php echo esc_attr( self::TERM_FIELD ); ?>"><?php esc_html_e( 'Nhóm dịch vụ gợi ý', 'isha-core' ); ?></label></th>
			<td><?php self::render_term_select( self::service_group_of( $term->term_id ) ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Lưu ở created_/edited_isha_article_cat: chỉ khi có nonce hợp lệ và quyền sửa term (tạo term bằng code, Quick Edit, REST không đụng tới meta).
	 * Nhóm không hợp lệ hoặc "chưa chọn" thì xoá meta.
	 *
	 * @param int $term_id ID chủ đề vừa tạo hoặc sửa.
	 */
	public static function save_term( $term_id ) {
		$term_id = absint( $term_id );
		$nonce   = isset( $_POST[ self::TERM_NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::TERM_NONCE ] ) ) : '';
		if ( $term_id < 1 || ! wp_verify_nonce( $nonce, self::NONCE_ACTION . '_term' ) || ! current_user_can( 'edit_term', $term_id ) || ! isset( $_POST[ self::TERM_FIELD ] ) ) {
			return;
		}
		$group = absint( sanitize_text_field( wp_unslash( $_POST[ self::TERM_FIELD ] ) ) );
		if ( $group > 0 && taxonomy_exists( 'isha_service_cat' ) && get_term( $group, 'isha_service_cat' ) instanceof WP_Term ) {
			update_term_meta( $term_id, self::TERM_META_SVC, $group );
		} else {
			delete_term_meta( $term_id, self::TERM_META_SVC );
		}
	}

	/* --- Đọc dữ liệu (không phụ thuộc ACF) -------------------------------- */

	/**
	 * Dữ liệu bài viết để hiển thị. Chỉ đọc; giá trị được làm sạch lại khi đọc.
	 * `summary` là nội dung ngắn một dòng đã làm sạch (rỗng nếu chưa có). `banner_id` là ID ảnh banner 16:10, 0 nếu chưa chọn
	 * hoặc tệp không còn là ảnh.
	 *
	 * @param int $post_id ID bài viết.
	 * @return array{featured:bool,summary:string,banner_id:int}
	 */
	public static function get( $post_id ) {
		$post_id = absint( $post_id );

		return array(
			'featured'  => '1' === self::meta( $post_id, self::META_FEATURED ),
			'summary'   => self::clean_summary( self::meta( $post_id, self::META_SUMMARY ) ),
			'banner_id' => self::clean_image_id( self::meta( $post_id, self::META_BANNER ) ),
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

	/**
	 * Làm sạch ID ảnh đã lưu: phải là tệp đính kèm ảnh.
	 *
	 * @param string $raw Giá trị đã lưu.
	 * @return int ID hoặc 0.
	 */
	private static function clean_image_id( $raw ) {
		if ( ! preg_match( '/^\d{1,18}$/D', $raw ) ) {
			return 0;
		}
		$id = (int) $raw;
		return ( $id > 0 && 'attachment' === get_post_type( $id ) && wp_attachment_is_image( $id ) ) ? $id : 0;
	}

	/* --- Meta box --------------------------------------------------------- */

	public static function add_meta_box() {
		add_meta_box( self::BOX_ID, __( 'Thông tin bài viết', 'isha-core' ), array( __CLASS__, 'render' ), self::POST_TYPE, 'normal', 'high' );
	}

	/**
	 * Bộ lọc get_user_option_meta-box-order_isha_article: bỏ meta box khỏi cột bên (`side`) trong thứ tự hộp người dùng đã lưu,
	 * để hộp luôn nằm ở vùng chính như lúc đăng ký. Bảng nhập rộng (nội dung ngắn, ảnh banner) không dùng được trong thanh bên.
	 *
	 * @param mixed $order Thứ tự đã lưu: array( context => 'id1,id2' ) hoặc false.
	 * @return mixed
	 */
	public static function keep_in_main( $order ) {
		if ( ! is_array( $order ) || empty( $order['side'] ) || ! is_string( $order['side'] ) ) {
			return $order;
		}
		$order['side'] = implode( ',', array_diff( explode( ',', $order['side'] ), array( self::BOX_ID ) ) );
		return $order;
	}

	/**
	 * Nạp ô chọn ảnh dùng chung ở màn hình sửa bài Tin tức.
	 *
	 * @param string $hook Màn hình quản trị hiện tại.
	 */
	public static function admin_assets( $hook ) {
		if ( ! class_exists( 'ISHA_Core_Admin_Media' ) || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && self::POST_TYPE === $screen->post_type ) {
			ISHA_Core_Admin_Media::enqueue();
		}
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
				<?php if ( class_exists( 'ISHA_Core_Admin_Media' ) ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Ảnh banner', 'isha-core' ); ?></th>
						<td>
							<?php
							ISHA_Core_Admin_Media::render(
								array(
									'name'  => 'isha_article_banner_id',
									'type'  => 'image',
									'value' => $data['banner_id'],
									'label' => __( 'Ảnh banner ngang (16:10)', 'isha-core' ),
									'help'  => __( 'Ảnh ngang tỉ lệ 16:10 (ví dụ 1600 × 1000 px) dùng khi bài đứng đầu section Tin tức trên trang chủ. Để trống thì dùng Ảnh đại diện (cắt phần giữa).', 'isha-core' ),
									'id'    => 'isha_article_banner',
								)
							);
							?>
						</td>
					</tr>
				<?php endif; ?>
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

		// Ảnh banner: thiếu khoá thì giữ giá trị cũ; ID không phải ảnh hợp lệ thì xoá meta.
		if ( isset( $_POST['isha_article_banner_id'] ) && class_exists( 'ISHA_Core_Admin_Media' ) ) {
			$banner = ISHA_Core_Admin_Media::sanitize( 'image', sanitize_text_field( wp_unslash( $_POST['isha_article_banner_id'] ) ) );
			self::set_meta( $post_id, self::META_BANNER, $banner ? (string) $banner : '' );
		}

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
