<?php
/**
 * Trường hero của trang danh sách dịch vụ (Page dùng template templates/template-services.php), của trang Giới thiệu (Page dùng
 * templates/template-about.php, từ 17/11/2026), của trang Không gian ISHA (templates/template-space.php, từ 18/11/2026) và của trang nhóm dịch vụ (term isha_service_cat: 4 ô media lưu ở term meta cùng khoá, đọc
 * bằng ::get_term()).
 *
 * Meta box native (không dùng ACF), chỉ hiện khi trang đang dùng một template trong TEMPLATES. Khoá meta bắt đầu bằng gạch dưới:
 *   _isha_hero_image_desktop – ảnh nền màn hình rộng (ID tệp đính kèm)
 *   _isha_hero_image_mobile  – ảnh nền điện thoại, khung dọc (ID tệp đính kèm)
 *   _isha_hero_video_desktop – video nền màn hình rộng (ID tệp đính kèm, ưu tiên hơn ảnh)
 *   _isha_hero_video_mobile  – video nền điện thoại (ID tệp đính kèm, ưu tiên hơn ảnh)
 *   _isha_hero_desc          – đoạn mô tả ngắn dưới tiêu đề (văn bản thường, tối đa DESC_MAX ký tự)
 * Tiêu đề hero là tên trang nên không có ô riêng. Đọc dữ liệu bằng ::get().
 *
 * Đổi template ở thanh bên rồi bấm Cập nhật thì tải lại trang sửa để meta box hiện ra.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Page_Hero_Fields {

	const TEMPLATES    = array( 'templates/template-services.php', 'templates/template-about.php', 'templates/template-space.php' );
	const NONCE_ACTION = 'isha_save_page_hero';
	const NONCE_FIELD  = 'isha_page_hero_nonce';
	const FIELD        = 'isha_page_hero';
	const DESC_MAX     = 280;
	const TAXONOMY     = 'isha_service_cat';

	/**
	 * Khoá trong ::get() => array( khoá meta, kiểu media hoặc 'text' ).
	 */
	const KEYS = array(
		'image_desktop' => array( '_isha_hero_image_desktop', 'image' ),
		'image_mobile'  => array( '_isha_hero_image_mobile', 'image' ),
		'video_desktop' => array( '_isha_hero_video_desktop', 'video' ),
		'video_mobile'  => array( '_isha_hero_video_mobile', 'video' ),
		'description'   => array( '_isha_hero_desc', 'text' ),
	);

	/**
	 * Gắn hook (gọi một lần từ isha_core_init()).
	 */
	public static function init() {
		add_action( 'add_meta_boxes_page', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_page', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );

		// Nhóm dịch vụ: 4 ô media hero ở màn "Sửa nhóm dịch vụ" (in sau các ô của ISHA_Core_Term_Fields).
		add_action( self::TAXONOMY . '_edit_form_fields', array( __CLASS__, 'render_term' ), 20 );
		add_action( 'edited_' . self::TAXONOMY, array( __CLASS__, 'save_term' ) );
	}

	/* --- Nhóm dịch vụ (term meta) ------------------------------------------ */

	/**
	 * Dữ liệu hero riêng của một nhóm dịch vụ (chỉ 4 ô media; mô tả hero là mô tả của nhóm). Ô trống là 0, theme tự lấy dự phòng.
	 *
	 * @param int $term_id ID term isha_service_cat.
	 * @return array{image_desktop:int,image_mobile:int,video_desktop:int,video_mobile:int}
	 */
	public static function get_term( $term_id ) {
		$term_id = absint( $term_id );
		$out     = array();
		foreach ( self::KEYS as $key => $def ) {
			if ( 'text' === $def[1] ) {
				continue;
			}
			$out[ $key ] = $term_id ? ISHA_Core_Admin_Media::sanitize( $def[1], get_term_meta( $term_id, $def[0], true ) ) : 0;
		}
		return $out;
	}

	/**
	 * In 4 ô media hero ở màn "Sửa nhóm dịch vụ" (tr.form-field > th + td, như các ô sẵn có).
	 *
	 * @param WP_Term $term Term đang sửa.
	 */
	public static function render_term( $term ) {
		$term_id = $term instanceof WP_Term ? (int) $term->term_id : 0;
		$data    = self::get_term( $term_id );
		$labels  = self::media_labels();

		echo '<tr class="form-field isha-term-field"><th scope="row" colspan="2"><h2 style="margin:24px 0 0">' . esc_html__( 'Hero banner của trang nhóm', 'isha-core' ) . '</h2>';
		echo '<p class="description" style="font-weight:400">' . esc_html__( 'Tiêu đề là tên nhóm, mô tả là ô Mô tả ở trên. Ô nào trống thì dùng ảnh bìa/video bìa của nhóm, rồi tới hero của trang Dịch vụ.', 'isha-core' ) . '</p>';
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD, false );
		echo '</th></tr>';

		foreach ( $labels as $key => $text ) {
			echo '<tr class="form-field isha-term-field"><th scope="row">' . esc_html( $text[0] ) . '</th><td>';
			ISHA_Core_Admin_Media::render(
				array(
					'name'  => self::FIELD . '[' . $key . ']',
					'type'  => self::KEYS[ $key ][1],
					'value' => $data[ $key ],
					'label' => $text[0],
					'help'  => $text[1],
					'id'    => 'isha-term-hero-' . str_replace( '_', '-', $key ),
				)
			);
			echo '</td></tr>';
		}
	}

	/**
	 * Lưu 4 ô media hero của nhóm (edited_isha_service_cat). Cần nonce đúng và quyền sửa term; chỉ đổi ô có trong dữ liệu gửi lên.
	 *
	 * @param int $term_id ID term.
	 */
	public static function save_term( $term_id ) {
		$term_id = absint( $term_id );
		if ( ! $term_id || ! isset( $_POST[ self::NONCE_FIELD ], $_POST[ self::FIELD ] ) || ! is_array( $_POST[ self::FIELD ] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) || ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}
		$raw = wp_unslash( $_POST[ self::FIELD ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch từng khoá bên dưới.
		foreach ( self::KEYS as $key => $def ) {
			if ( 'text' === $def[1] || ! array_key_exists( $key, $raw ) ) {
				continue;
			}
			$id = ISHA_Core_Admin_Media::sanitize( $def[1], $raw[ $key ] );
			if ( $id ) {
				update_term_meta( $term_id, $def[0], (int) $id );
			} else {
				delete_term_meta( $term_id, $def[0] );
			}
		}
	}

	/**
	 * Nhãn và chú thích của 4 ô media (dùng chung cho trang và nhóm).
	 *
	 * @return array<string,string[]>
	 */
	private static function media_labels() {
		return array(
			'image_desktop' => array( __( 'Ảnh nền – màn hình rộng', 'isha-core' ), __( 'Khung ngang, rộng tối thiểu 2400px. Chủ thể lệch khỏi vùng giữa phía trên để chữ dễ đọc.', 'isha-core' ) ),
			'image_mobile'  => array( __( 'Ảnh nền – điện thoại', 'isha-core' ), __( 'Khung dọc (khoảng 4:5 đến 9:16), rộng tối thiểu 1200px.', 'isha-core' ) ),
			'video_desktop' => array( __( 'Video nền – màn hình rộng', 'isha-core' ), __( 'Tuỳ chọn. MP4 (H.264) hoặc WebM, vòng lặp ngắn, không tiếng, dưới 8MB.', 'isha-core' ) ),
			'video_mobile'  => array( __( 'Video nền – điện thoại', 'isha-core' ), __( 'Tuỳ chọn. Khung dọc, dưới 5MB.', 'isha-core' ) ),
		);
	}

	/* --- Đọc dữ liệu ------------------------------------------------------ */

	/**
	 * Dữ liệu hero của một trang, đã làm sạch lại khi đọc (ID media hỏng hoặc sai loại thành 0).
	 *
	 * @param int $post_id ID trang.
	 * @return array{image_desktop:int,image_mobile:int,video_desktop:int,video_mobile:int,description:string}
	 */
	public static function get( $post_id ) {
		$post_id = absint( $post_id );
		$out     = array();
		foreach ( self::KEYS as $key => $def ) {
			$raw         = $post_id ? get_post_meta( $post_id, $def[0], true ) : '';
			$out[ $key ] = 'text' === $def[1] ? self::clean_desc( $raw ) : ISHA_Core_Admin_Media::sanitize( $def[1], $raw );
		}
		return $out;
	}

	/**
	 * Trang có dùng template được hỗ trợ không.
	 *
	 * @param WP_Post|int|null $post Trang.
	 * @return bool
	 */
	public static function applies( $post ) {
		$post = get_post( $post );
		if ( ! $post || 'page' !== $post->post_type ) {
			return false;
		}
		return in_array( (string) get_page_template_slug( $post ), self::TEMPLATES, true );
	}

	/**
	 * Làm sạch mô tả: bỏ thẻ HTML, gộp mọi khoảng trắng (cả khoảng trắng Unicode) thành một dấu cách, cắt ở DESC_MAX ký tự.
	 *
	 * @param mixed $raw Giá trị thô.
	 * @return string
	 */
	private static function clean_desc( $raw ) {
		if ( ! is_scalar( $raw ) ) {
			return '';
		}
		$text = (string) preg_replace( '/\s+/u', ' ', sanitize_text_field( (string) $raw ) );
		return trim( mb_substr( $text, 0, self::DESC_MAX, 'UTF-8' ) );
	}

	/* --- Meta box --------------------------------------------------------- */

	/**
	 * Thêm meta box khi trang đang dùng template được hỗ trợ.
	 *
	 * @param WP_Post $post Trang đang sửa.
	 */
	public static function add_meta_box( $post ) {
		if ( ! self::applies( $post ) ) {
			return;
		}
		add_meta_box( 'isha_page_hero', __( 'Hero banner', 'isha-core' ), array( __CLASS__, 'render' ), 'page', 'normal', 'high' );
	}

	/**
	 * Nạp ô chọn media ở màn sửa trang dùng template được hỗ trợ.
	 *
	 * @param string $hook Hook suffix của màn hình quản trị.
	 */
	public static function enqueue( $hook ) {
		$screen  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_term = 'term.php' === $hook && $screen && self::TAXONOMY === $screen->taxonomy;
		$is_page = in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && self::applies( get_post() );
		if ( $is_term || $is_page ) {
			ISHA_Core_Admin_Media::enqueue();
		}
	}

	/**
	 * In meta box.
	 *
	 * @param WP_Post $post Trang đang sửa.
	 */
	public static function render( $post ) {
		$data = self::get( $post->ID );
		$name = static function ( $key ) {
			return self::FIELD . '[' . $key . ']';
		};

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD, false );
		?>
		<p class="description"><?php esc_html_e( 'Tiêu đề hero là tên trang. Có video thì hero dùng video, ảnh làm hình chờ trong lúc video tải; thiếu bản điện thoại thì dùng bản màn hình rộng và ngược lại.', 'isha-core' ); ?></p>
		<table class="form-table isha-fields" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="isha_page_hero_desc"><?php esc_html_e( 'Mô tả ngắn', 'isha-core' ); ?></label></th>
					<td>
						<textarea id="isha_page_hero_desc" name="<?php echo esc_attr( $name( 'description' ) ); ?>" class="large-text" rows="2" maxlength="<?php echo esc_attr( (string) self::DESC_MAX ); ?>" aria-describedby="isha_page_hero_desc_help"><?php echo esc_textarea( $data['description'] ); ?></textarea>
						<p class="description" id="isha_page_hero_desc_help"><?php esc_html_e( 'Một đến hai câu dưới tiêu đề (tối đa 280 ký tự).', 'isha-core' ); ?></p>
					</td>
				</tr>
				<?php
				foreach ( self::media_labels() as $key => $text ) :
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $text[0] ); ?></th>
						<td>
							<?php
							ISHA_Core_Admin_Media::render(
								array(
									'name'  => $name( $key ),
									'type'  => self::KEYS[ $key ][1],
									'value' => $data[ $key ],
									'label' => $text[0],
									'help'  => $text[1],
									'id'    => 'isha-page-hero-' . str_replace( '_', '-', $key ),
								)
							);
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/* --- Lưu -------------------------------------------------------------- */

	/**
	 * Hook save_post_page. Chỉ lưu khi có nonce đúng, quyền sửa trang và dữ liệu của meta box trong yêu cầu
	 * (yêu cầu REST của block editor không mang meta box nên giữ nguyên giá trị cũ). Bỏ qua autosave và revision.
	 *
	 * @param int          $post_id ID trang.
	 * @param WP_Post|null $post    Trang.
	 */
	public static function save( $post_id, $post = null ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE_FIELD ], $_POST[ self::FIELD ] ) || ! is_array( $_POST[ self::FIELD ] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = wp_unslash( $_POST[ self::FIELD ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch từng khoá bên dưới.
		foreach ( self::KEYS as $key => $def ) {
			if ( ! array_key_exists( $key, $raw ) ) {
				continue;
			}
			$value = 'text' === $def[1]
				? self::clean_desc( sanitize_textarea_field( is_scalar( $raw[ $key ] ) ? (string) $raw[ $key ] : '' ) )
				: ISHA_Core_Admin_Media::sanitize( $def[1], $raw[ $key ] );

			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, $def[0] );
			} else {
				update_post_meta( $post_id, $def[0], 'text' === $def[1] ? wp_slash( $value ) : (int) $value );
			}
		}
	}
}
