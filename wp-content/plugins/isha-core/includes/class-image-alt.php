<?php
/**
 * Văn bản thay thế (alt) mặc định cho ảnh: tệp ảnh được tải lên mà chưa có alt thì lấy tên tệp, bỏ đuôi.
 *
 * Ví dụ: "spa-phong-kham-01.jpg" → alt "spa-phong-kham-01". Ảnh đã có alt không bị ghi đè.
 * Ảnh tải lên trước khi có tính năng này được điền một lần khi quản trị viên vào wp-admin.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Image_Alt {

	/**
	 * Cờ đã chạy việc điền bù cho ảnh cũ.
	 */
	const BACKFILL_OPTION = 'isha_core_alt_backfill_v1';

	/**
	 * Meta lưu alt của tệp đính kèm (khóa chuẩn của WordPress).
	 */
	const META = '_wp_attachment_image_alt';

	public static function init() {
		add_action( 'add_attachment', array( __CLASS__, 'set_default' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_backfill' ) );
	}

	/**
	 * Tên tệp không có đuôi, dùng làm alt mặc định.
	 *
	 * @param string $file Đường dẫn hoặc tên tệp.
	 * @return string
	 */
	public static function name_from_file( $file ) {
		return sanitize_text_field( pathinfo( (string) $file, PATHINFO_FILENAME ) );
	}

	/**
	 * Điền alt từ tên tệp nếu ảnh chưa có alt.
	 *
	 * @param int $post_id ID tệp đính kèm.
	 */
	public static function set_default( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || ! wp_attachment_is_image( $post_id ) ) {
			return;
		}
		if ( '' !== trim( (string) get_post_meta( $post_id, self::META, true ) ) ) {
			return;
		}
		$name = self::name_from_file( get_attached_file( $post_id ) );
		if ( '' !== $name ) {
			update_post_meta( $post_id, self::META, $name );
		}
	}

	/**
	 * Chạy một lần: điền alt cho mọi ảnh đã tải lên mà còn trống.
	 */
	public static function maybe_backfill() {
		if ( get_option( self::BACKFILL_OPTION ) ) {
			return;
		}
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			self::set_default( $id );
		}
		update_option( self::BACKFILL_OPTION, 1, false );
	}
}
