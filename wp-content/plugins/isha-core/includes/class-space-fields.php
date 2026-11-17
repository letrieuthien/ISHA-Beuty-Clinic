<?php
/**
 * Trường dữ liệu của bài Không gian ISHA (isha_space), 18/11/2026 (docs/home-architecture.md mục 18).
 *
 * Meta box native (không dùng ACF). Khoá meta bắt đầu bằng gạch dưới:
 *   _isha_space_home     – '1' khi bài xuất hiện ở section Không gian của trang chủ
 *   _isha_space_subtitle – tiêu đề nhỏ (một dòng, tối đa SUBTITLE_MAX ký tự), chữ nghiêng dưới tiêu đề
 *   _isha_space_summary  – mô tả ngắn (tối đa SUMMARY_MAX ký tự) trên trang danh sách
 *   _isha_space_gallery  – mảng ID ảnh (tối đa GALLERY_MAX) ở trang chi tiết
 * Thứ tự hiển thị là menu_order (ô Thứ tự của bài). Hàm đọc chỉ dùng get_post_meta.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Space_Fields {

	const POST_TYPE    = 'isha_space';
	const NONCE_ACTION = 'isha_save_space';
	const NONCE_FIELD  = 'isha_space_nonce';

	const META_HOME     = '_isha_space_home';
	const META_SUBTITLE = '_isha_space_subtitle';
	const META_SUMMARY  = '_isha_space_summary';
	const META_GALLERY  = '_isha_space_gallery';

	const SUBTITLE_MAX = 120;
	const SUMMARY_MAX  = 400;
	const GALLERY_MAX  = 12;

	/**
	 * Số bài tối đa ở section trang chủ (nhiều hơn thì lấy theo thứ tự hiển thị).
	 */
	const HOME_MAX = 12;

	/**
	 * Vị trí trong menu admin: ngay dưới Giới thiệu (20.6), trên Dịch vụ (21), đúng thứ tự trên menu chính của website.
	 */
	const MENU_POSITION = '20.7';

	/**
	 * Gắn hook (gọi một lần từ isha_core_init()).
	 */
	public static function init() {
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 99 );
		add_action( 'pre_get_posts', array( __CLASS__, 'admin_order' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
	}

	/* --- Đọc dữ liệu ------------------------------------------------------- */

	/**
	 * Dữ liệu một bài để hiển thị. Giá trị được làm sạch lại khi đọc.
	 *
	 * @param int $post_id ID bài.
	 * @return array{home:bool,subtitle:string,summary:string,gallery:int[]}
	 */
	public static function get( $post_id ) {
		$post_id = absint( $post_id );
		$gallery = get_post_meta( $post_id, self::META_GALLERY, true );

		return array(
			'home'     => '1' === self::meta( $post_id, self::META_HOME ),
			'subtitle' => self::clean_line( self::meta( $post_id, self::META_SUBTITLE ), self::SUBTITLE_MAX ),
			'summary'  => self::clean_line( self::meta( $post_id, self::META_SUMMARY ), self::SUMMARY_MAX ),
			'gallery'  => self::clean_gallery( $gallery ),
		);
	}

	/**
	 * Bài đã xuất bản, theo thứ tự hiển thị (menu_order tăng dần, rồi tiêu đề).
	 *
	 * @param bool $home_only Chỉ lấy bài được chọn "Xuất hiện ở trang chủ" (tối đa HOME_MAX).
	 * @return WP_Post[]
	 */
	public static function posts( $home_only = false ) {
		$args = array(
			'post_type'        => self::POST_TYPE,
			'post_status'      => 'publish',
			'posts_per_page'   => $home_only ? self::HOME_MAX : 50,
			'orderby'          => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'    => true,
			'suppress_filters' => false,
		);
		if ( $home_only ) {
			$args['meta_key']   = self::META_HOME; // phpcs:ignore WordPress.DB.SlowDBQuery -- vài chục bài, một khoá meta.
			$args['meta_value'] = '1'; // phpcs:ignore WordPress.DB.SlowDBQuery -- như trên.
		}
		return get_posts( $args );
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

	/* --- Làm sạch (hàm thuần) ---------------------------------------------- */

	/**
	 * Một đoạn văn bản thuần một dòng: bỏ thẻ, gộp mọi khoảng trắng (kể cả khoảng trắng Unicode), cắt ở $max ký tự.
	 *
	 * @param mixed $raw Giá trị thô.
	 * @param int   $max Số ký tự tối đa.
	 * @return string
	 */
	private static function clean_line( $raw, $max ) {
		$text = (string) preg_replace( '/\s+/u', ' ', sanitize_text_field( is_scalar( $raw ) ? (string) $raw : '' ) );
		return trim( mb_substr( $text, 0, $max, 'UTF-8' ) );
	}

	/**
	 * Mảng ID ảnh hợp lệ (bỏ trùng, giữ thứ tự, tối đa GALLERY_MAX).
	 *
	 * @param mixed $raw Mảng ID hoặc chuỗi CSV.
	 * @return int[]
	 */
	private static function clean_gallery( $raw ) {
		$ids = class_exists( 'ISHA_Core_Admin_Media' ) ? ISHA_Core_Admin_Media::sanitize( 'gallery', $raw ) : array();
		return array_slice( is_array( $ids ) ? $ids : array(), 0, self::GALLERY_MAX );
	}

	/* --- Meta box ---------------------------------------------------------- */

	public static function add_meta_box() {
		add_meta_box( 'isha_space_fields', __( 'Thông tin không gian', 'isha-core' ), array( __CLASS__, 'render' ), self::POST_TYPE, 'normal', 'high' );
	}

	/**
	 * In meta box.
	 *
	 * @param WP_Post $post Bài đang sửa.
	 */
	public static function render( $post ) {
		$data = self::get( $post->ID );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD, false );
		?>
		<table class="form-table isha-fields" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Trang chủ', 'isha-core' ); ?></th>
					<td>
						<label for="isha_space_home">
							<input type="checkbox" id="isha_space_home" name="isha_space_home" value="1" <?php checked( $data['home'] ); ?> aria-describedby="isha_space_home_desc">
							<?php esc_html_e( 'Xuất hiện bài này ở trang chủ', 'isha-core' ); ?>
						</label>
						<p class="description" id="isha_space_home_desc">
							<?php
							/* translators: %d: số bài tối đa. */
							echo esc_html( sprintf( __( 'Bài được chọn hiện trong section Không gian ISHA của trang chủ (ảnh đại diện và tên bài, bấm vào mở bài này), tối đa %d bài theo ô Thứ tự. Chưa chọn bài nào thì section dùng ảnh ở mục Trang chủ › Không gian ISHA.', 'isha-core' ), self::HOME_MAX ) );
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="isha_space_subtitle"><?php esc_html_e( 'Tiêu đề nhỏ', 'isha-core' ); ?></label></th>
					<td>
						<input type="text" id="isha_space_subtitle" name="isha_space_subtitle" class="large-text" maxlength="<?php echo esc_attr( (string) self::SUBTITLE_MAX ); ?>" value="<?php echo esc_attr( $data['subtitle'] ); ?>" aria-describedby="isha_space_subtitle_desc">
						<p class="description" id="isha_space_subtitle_desc"><?php esc_html_e( 'Một câu ngắn, chữ nghiêng dưới tên không gian. Ví dụ: Nơi mỗi hành trình bắt đầu.', 'isha-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="isha_space_summary"><?php esc_html_e( 'Mô tả ngắn', 'isha-core' ); ?></label></th>
					<td>
						<textarea id="isha_space_summary" name="isha_space_summary" class="large-text" rows="3" maxlength="<?php echo esc_attr( (string) self::SUMMARY_MAX ); ?>" aria-describedby="isha_space_summary_desc"><?php echo esc_textarea( $data['summary'] ); ?></textarea>
						<p class="description" id="isha_space_summary_desc"><?php esc_html_e( '2–3 câu (tối đa 400 ký tự) hiện ở trang Không gian ISHA. Nội dung chi tiết soạn ở khung soạn thảo phía trên, hiện ở trang của bài.', 'isha-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Ảnh không gian', 'isha-core' ); ?></th>
					<td>
						<?php
						ISHA_Core_Admin_Media::render(
							array(
								'name'  => 'isha_space_gallery',
								'type'  => 'gallery',
								'value' => $data['gallery'],
								'label' => __( 'Ảnh không gian', 'isha-core' ),
								'help'  => __( '3–12 ảnh ở trang của bài, cạnh dài từ 2000 px, cả dọc và ngang. Chú thích dưới ảnh lấy từ trường Chú thích trong Thư viện phương tiện. Ảnh đại diện (dọc 4:5) dùng ở trang chủ và trang danh sách; chưa đặt thì dùng ảnh đầu tiên ở đây.', 'isha-core' ),
								'id'    => 'isha-space-gallery',
								'max'   => self::GALLERY_MAX,
							)
						);
						?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/* --- Lưu --------------------------------------------------------------- */

	/**
	 * Hook save_post_isha_space (chạy cả khi lưu qua block editor; meta box gửi kèm theo yêu cầu riêng có nonce).
	 *
	 * Chỉ lưu khi có nonce đúng và quyền sửa bài; ô văn bản và ảnh thiếu khoá trong dữ liệu gửi lên thì giữ giá trị cũ. Bỏ qua autosave,
	 * revision và bản nháp tự động.
	 *
	 * @param int          $post_id ID bài.
	 * @param WP_Post|null $post    Bài (tự lấy nếu không truyền).
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
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		self::set_meta( $post_id, self::META_HOME, isset( $_POST['isha_space_home'] ) ? '1' : '' );
		if ( isset( $_POST['isha_space_subtitle'] ) ) {
			self::set_meta( $post_id, self::META_SUBTITLE, self::clean_line( wp_unslash( $_POST['isha_space_subtitle'] ), self::SUBTITLE_MAX ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- clean_line() làm sạch.
		}
		if ( isset( $_POST['isha_space_summary'] ) ) {
			self::set_meta( $post_id, self::META_SUMMARY, self::clean_line( sanitize_textarea_field( wp_unslash( $_POST['isha_space_summary'] ) ), self::SUMMARY_MAX ) );
		}
		if ( isset( $_POST['isha_space_gallery'] ) ) {
			$ids = self::clean_gallery( sanitize_text_field( wp_unslash( $_POST['isha_space_gallery'] ) ) );
			if ( $ids ) {
				update_post_meta( $post_id, self::META_GALLERY, $ids );
			} else {
				delete_post_meta( $post_id, self::META_GALLERY );
			}
		}
	}

	/**
	 * Ghi một meta chuỗi; rỗng thì xoá. Slash trước khi lưu vì core tự unslash.
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

	/* --- Admin ------------------------------------------------------------- */

	/**
	 * Nạp ô chọn ảnh, chỉ ở màn hình thêm/sửa bài Không gian.
	 *
	 * @param string $hook Hook của trang admin.
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
	 * Đặt mục menu của post type về MENU_POSITION (menu_position của register_post_type() chỉ nhận số nguyên; WordPress sắp menu theo khoá
	 * sau hook admin_menu nên đổi khoá ở đây là đủ).
	 */
	public static function admin_menu() {
		global $menu;
		if ( ! is_array( $menu ) || isset( $menu[ self::MENU_POSITION ] ) ) {
			return;
		}
		foreach ( $menu as $key => $item ) {
			if ( isset( $item[2] ) && 'edit.php?post_type=' . self::POST_TYPE === $item[2] ) {
				unset( $menu[ $key ] );
				$menu[ self::MENU_POSITION ] = $item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride -- chỉ đổi vị trí mục của chính plugin.
				return;
			}
		}
	}

	/**
	 * Danh sách bài trong admin sắp theo thứ tự hiển thị (giống trang danh sách) khi người dùng chưa chọn cột sắp xếp.
	 *
	 * @param WP_Query $query Truy vấn.
	 */
	public static function admin_order( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || self::POST_TYPE !== $query->get( 'post_type' ) || $query->get( 'orderby' ) ) {
			return;
		}
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			)
		);
	}

	/**
	 * Thêm cột Trang chủ và Thứ tự ngay sau cột tiêu đề.
	 *
	 * @param array $columns Các cột mặc định.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['isha_space_home']  = __( 'Trang chủ', 'isha-core' );
				$new['isha_space_order'] = __( 'Thứ tự', 'isha-core' );
			}
		}
		return $new;
	}

	/**
	 * Nội dung cột (meta đã được nạp sẵn cho cả trang danh sách).
	 *
	 * @param string $column  Khoá cột.
	 * @param int    $post_id ID bài.
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'isha_space_order' === $column ) {
			echo esc_html( (string) (int) get_post_field( 'menu_order', $post_id ) );
			return;
		}
		if ( 'isha_space_home' !== $column ) {
			return;
		}
		if ( '1' === self::meta( $post_id, self::META_HOME ) ) {
			echo esc_html__( 'Có', 'isha-core' );
		} else {
			echo '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Không', 'isha-core' ) . '</span>';
		}
	}
}
