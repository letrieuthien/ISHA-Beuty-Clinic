<?php
/**
 * Trường dữ liệu của Dịch vụ (isha_service).
 *
 * Cấu trúc: Tiêu đề (post title) + 4 trường
 *   isha_duration   – Thời gian liệu trình (text)
 *   isha_short_desc – Mô tả ngắn (textarea)
 *   isha_gallery    – Ảnh liệu trình (mảng ID ảnh)
 *   isha_steps      – Các bước liệu trình (textarea, mỗi dòng một bước)
 *
 * Các trường được đăng ký bằng ACF (local field group, nằm trong code). ACF bản miễn phí không có
 * field Gallery (chỉ có ở ACF PRO), nên khi không có PRO plugin tự hiển thị một meta box chọn nhiều ảnh
 * bằng thư viện media của WordPress. Cả hai cách đều lưu cùng một meta key với cùng định dạng
 * (mảng ID), nên nâng cấp lên ACF PRO sau này không cần chuyển dữ liệu.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Service_Fields {

	const GALLERY_KEY = 'isha_gallery';

	public static function init() {
		add_action( 'acf/init', array( __CLASS__, 'register_acf_group' ) );
		add_action( 'add_meta_boxes_isha_service', array( __CLASS__, 'add_gallery_box' ) );
		add_action( 'save_post_isha_service', array( __CLASS__, 'save_gallery' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'admin_notices', array( __CLASS__, 'acf_notice' ) );
	}

	/* --- Đọc dữ liệu (không phụ thuộc ACF) -------------------------------- */

	/**
	 * Lấy dữ liệu dịch vụ để hiển thị.
	 *
	 * @param int $post_id ID dịch vụ.
	 * @return array{duration:string,short_desc:string,gallery:int[],steps:string[]}
	 */
	public static function get( $post_id ) {
		$gallery = get_post_meta( $post_id, self::GALLERY_KEY, true );
		$gallery = is_array( $gallery ) ? $gallery : array();
		$gallery = array_values( array_filter( array_map( 'absint', $gallery ), static function ( $id ) {
			return $id && wp_attachment_is_image( $id );
		} ) );

		$steps = preg_split( '/\r\n|\r|\n/', (string) get_post_meta( $post_id, 'isha_steps', true ) );
		$steps = array_values( array_filter( array_map( 'trim', $steps ), 'strlen' ) );

		return array(
			'duration'   => (string) get_post_meta( $post_id, 'isha_duration', true ),
			'short_desc' => (string) get_post_meta( $post_id, 'isha_short_desc', true ),
			'gallery'    => $gallery,
			'steps'      => $steps,
		);
	}

	/* --- ACF -------------------------------------------------------------- */

	private static function acf_pro() {
		return function_exists( 'acf_is_pro' ) && acf_is_pro();
	}

	public static function register_acf_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$fields = array(
			array(
				'key'          => 'field_isha_duration',
				'label'        => __( 'Thời gian liệu trình', 'isha-core' ),
				'name'         => 'isha_duration',
				'type'         => 'text',
				'instructions' => __( 'Ví dụ: 45 phút, hoặc 6 buổi / 3 tháng.', 'isha-core' ),
			),
			array(
				'key'          => 'field_isha_short_desc',
				'label'        => __( 'Mô tả ngắn', 'isha-core' ),
				'name'         => 'isha_short_desc',
				'type'         => 'textarea',
				'rows'         => 3,
				'new_lines'    => '',
				'instructions' => __( 'Hiển thị ở thẻ dịch vụ và đầu trang chi tiết.', 'isha-core' ),
			),
		);

		if ( self::acf_pro() ) {
			$fields[] = array(
				'key'           => 'field_isha_gallery',
				'label'         => __( 'Ảnh liệu trình', 'isha-core' ),
				'name'          => self::GALLERY_KEY,
				'type'          => 'gallery',
				'return_format' => 'id',
				'preview_size'  => 'medium',
				'insert'        => 'append',
				'library'       => 'all',
				'mime_types'    => 'jpg,jpeg,png,webp',
			);
		}

		$fields[] = array(
			'key'          => 'field_isha_steps',
			'label'        => __( 'Các bước liệu trình', 'isha-core' ),
			'name'         => 'isha_steps',
			'type'         => 'textarea',
			'rows'         => 8,
			'new_lines'    => '',
			'instructions' => __( 'Mỗi dòng là một bước (hiển thị thành danh sách đánh số).', 'isha-core' ),
		);

		acf_add_local_field_group(
			array(
				'key'                   => 'group_isha_service',
				'title'                 => __( 'Thông tin liệu trình', 'isha-core' ),
				'fields'                => $fields,
				'location'              => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'isha_service' ) ) ),
				'position'              => 'acf_after_title',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'hide_on_screen'        => array(),
				'show_in_rest'          => 1,
			)
		);
	}

	public static function acf_notice() {
		$screen = get_current_screen();
		if ( ! $screen || 'isha_service' !== $screen->post_type || function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Cần kích hoạt plugin Advanced Custom Fields để nhập Thời gian liệu trình, Mô tả ngắn và Các bước liệu trình.', 'isha-core' ) . '</p></div>';
	}

	/* --- Fallback chọn nhiều ảnh (khi không có ACF PRO) ------------------- */

	public static function add_gallery_box() {
		if ( self::acf_pro() ) {
			return;
		}
		add_meta_box( 'isha_gallery_box', __( 'Ảnh liệu trình', 'isha-core' ), array( __CLASS__, 'render_gallery_box' ), 'isha_service', 'normal', 'default' );
	}

	public static function render_gallery_box( $post ) {
		$ids = self::get( $post->ID )['gallery'];
		wp_nonce_field( 'isha_save_gallery', 'isha_gallery_nonce' );
		?>
		<div class="isha-gallery">
			<ul class="isha-gallery__list">
				<?php foreach ( $ids as $id ) : ?>
					<li data-id="<?php echo esc_attr( $id ); ?>">
						<?php echo wp_get_attachment_image( $id, 'thumbnail' ); ?>
						<button type="button" class="isha-gallery__remove" aria-label="<?php esc_attr_e( 'Xoá ảnh', 'isha-core' ); ?>">&times;</button>
					</li>
				<?php endforeach; ?>
			</ul>
			<input type="hidden" name="isha_gallery_ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
			<p><button type="button" class="button isha-gallery__add"><?php esc_html_e( 'Thêm ảnh', 'isha-core' ); ?></button>
			<span class="description"><?php esc_html_e( 'Kéo thả để đổi thứ tự.', 'isha-core' ); ?></span></p>
		</div>
		<?php
	}

	public static function save_gallery( $post_id ) {
		if ( self::acf_pro() ) {
			return; // ACF tự lưu.
		}
		if ( ! isset( $_POST['isha_gallery_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['isha_gallery_nonce'] ) ), 'isha_save_gallery' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$raw = isset( $_POST['isha_gallery_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['isha_gallery_ids'] ) ) : '';
		$ids = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', $raw ) ), static function ( $id ) {
			return $id && wp_attachment_is_image( $id );
		} ) ) );
		update_post_meta( $post_id, self::GALLERY_KEY, $ids );
	}

	public static function admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'isha_service' !== get_post_type() || self::acf_pro() ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'isha-gallery-admin', ISHA_CORE_URL . 'assets/js/admin-gallery.js', array( 'jquery', 'jquery-ui-sortable' ), ISHA_CORE_VERSION, true );
		wp_localize_script( 'isha-gallery-admin', 'ishaGallery', array(
			'title'  => __( 'Chọn ảnh liệu trình', 'isha-core' ),
			'button' => __( 'Thêm vào liệu trình', 'isha-core' ),
		) );
		wp_add_inline_style( 'wp-admin', '.isha-gallery__list{display:flex;flex-wrap:wrap;gap:10px;margin:0 0 10px;padding:0;list-style:none}.isha-gallery__list li{position:relative;cursor:move}.isha-gallery__list img{width:110px;height:110px;object-fit:cover;display:block}.isha-gallery__remove{position:absolute;top:2px;right:2px;width:22px;height:22px;border:0;border-radius:50%;background:#1c1b19;color:#fff;cursor:pointer;line-height:20px;padding:0}' );
	}
}
