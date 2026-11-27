<?php
/**
 * Trường dữ liệu của Chính sách khách hàng (isha_policy).
 *
 * Cấu trúc: Tiêu đề (post title) + 2 ảnh + 1 màu
 *   isha_card_front – Ảnh thẻ mặt trước
 *   isha_card_back  – Ảnh thẻ mặt sau
 *   isha_card_color – Màu thẻ (nút tròn chọn hạng ở [isha_lp_cards_member]); để trống thì trình duyệt tự lấy màu từ ảnh mặt trước
 * Đăng ký bằng ACF (field Image, Color Picker có trong bản miễn phí). Giá trị ảnh lưu là ID ảnh, màu lưu dạng #rrggbb.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Policy_Fields {

	public static function init() {
		add_action( 'acf/init', array( __CLASS__, 'register_acf_group' ) );
		add_action( 'admin_notices', array( __CLASS__, 'acf_notice' ) );
	}

	/**
	 * Dữ liệu chính sách để hiển thị.
	 *
	 * @param int $post_id ID chính sách.
	 * @return array{title:string,front:int,back:int,color:string} color là #rrggbb hoặc '' nếu admin chưa chọn.
	 */
	public static function get( $post_id ) {
		$valid = static function ( $key ) use ( $post_id ) {
			$id = absint( get_post_meta( $post_id, $key, true ) );
			return ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
		};
		return array(
			'title' => get_the_title( $post_id ),
			'front' => $valid( 'isha_card_front' ),
			'back'  => $valid( 'isha_card_back' ),
			'color' => (string) sanitize_hex_color( (string) get_post_meta( $post_id, 'isha_card_color', true ) ),
		);
	}

	public static function register_acf_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$image = array(
			'type'          => 'image',
			'return_format' => 'id',
			'preview_size'  => 'medium',
			'library'       => 'all',
			'mime_types'    => 'jpg,jpeg,png,webp',
			'required'      => 1,
			'wrapper'       => array( 'width' => '50' ), // Mặt trước và mặt sau nằm cùng một hàng trên desktop.
		);

		acf_add_local_field_group(
			array(
				'key'             => 'group_isha_policy',
				'title'           => __( 'Thông tin thẻ', 'isha-core' ),
				'fields'          => array(
					array_merge(
						$image,
						array(
							'key'   => 'field_isha_card_front',
							'label' => __( 'Ảnh thẻ mặt trước', 'isha-core' ),
							'name'  => 'isha_card_front',
						)
					),
					array_merge(
						$image,
						array(
							'key'   => 'field_isha_card_back',
							'label' => __( 'Ảnh thẻ mặt sau', 'isha-core' ),
							'name'  => 'isha_card_back',
						)
					),
					array(
						'key'            => 'field_isha_card_color',
						'label'          => __( 'Màu thẻ', 'isha-core' ),
						'name'           => 'isha_card_color',
						'type'           => 'color_picker',
						'instructions'   => __( 'Màu nút tròn đại diện cho thẻ ở thang hạng thẻ (bấm nút để nhảy tới thẻ). Để trống: tự lấy màu chủ đạo từ ảnh thẻ mặt trước.', 'isha-core' ),
						'required'       => 0,
						'default_value'  => '',
						'enable_opacity' => 0,
						'return_format'  => 'string',
					),
				),
				'location'        => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'isha_policy' ) ) ),
				'position'        => 'normal', // Nằm dưới khu vực nội dung.
				'label_placement' => 'top',
				'show_in_rest'    => 1,
			)
		);
	}

	public static function acf_notice() {
		$screen = get_current_screen();
		if ( ! $screen || 'isha_policy' !== $screen->post_type || function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Cần kích hoạt plugin Advanced Custom Fields để tải ảnh thẻ mặt trước và mặt sau.', 'isha-core' ) . '</p></div>';
	}
}
