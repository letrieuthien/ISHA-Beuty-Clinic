<?php
/**
 * Meta box "Nội dung trang Giới thiệu": ba section của trang dùng mẫu "Giới thiệu" (templates/template-about.php), theo thứ tự trên trang:
 * Câu chuyện thương hiệu (story), Triết lý và sứ mệnh (philosophy), Giá trị cốt lõi (values). Thêm ngày 17/11/2026
 * (docs/home-architecture.md mục 16).
 *
 * Mỗi section lưu MỘT meta dạng mảng `_isha_about_<id>`:
 *   enabled  '1' | '0' (không có khoá nghĩa là bật)
 *   title    tiêu đề (văn bản một dòng, tối đa TITLE_MAX ký tự)
 *   subtitle tiêu đề nhỏ (văn bản một dòng, tối đa SUBTITLE_MAX ký tự)
 *   content  nội dung: HTML gọn lọc bằng ::sanitize_content() (đoạn, tiêu đề phụ, đậm, nghiêng, danh sách, trích dẫn, liên kết); lưu như
 *            bài viết (không có <p>), theme hiển thị qua wpautop
 *   image    ảnh minh hoạ dọc 4:5 (ID tệp đính kèm)
 * Chỉ lưu khoá có giá trị; section không còn gì (và không tắt) thì xoá meta. Hero của trang (mô tả, ảnh/video nền) dùng meta box "Hero
 * banner" chung (ISHA_Core_Page_Hero_Fields).
 *
 * Giao diện: ba khung xếp dọc (không dùng thẻ: TinyMCE không dựng được trong khung đang ẩn), mỗi khung có công tắc hiển thị, tiêu đề, tiêu
 * đề nhỏ, trình soạn thảo gọn (wp_editor, không chèn media) và ô chọn ảnh (ISHA_Core_Admin_Media). Trang dùng mẫu này do theme dựng nên
 * trình soạn thảo của trang bị ẩn (cả block editor). Menu quản trị có mục cấp một "Giới thiệu" mở thẳng màn sửa trang Giới thiệu đầu tiên.
 * Lưu ở save_post_page: nonce isha_save_about / isha_about_nonce, quyền edit_post, bỏ qua autosave và revision.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_About_Fields {

	/**
	 * Mẫu trang được hỗ trợ.
	 */
	const TEMPLATE = 'templates/template-about.php';

	/**
	 * Tiền tố khoá meta; khoá đầy đủ là META_PREFIX . <id section>.
	 */
	const META_PREFIX = '_isha_about_';

	/**
	 * Nonce và tên input gốc: isha_about[<section>][...].
	 */
	const NONCE_ACTION = 'isha_save_about';
	const NONCE_FIELD  = 'isha_about_nonce';
	const INPUT        = 'isha_about';

	/**
	 * Giới hạn ký tự.
	 */
	const TITLE_MAX    = 120;
	const SUBTITLE_MAX = 200;

	/**
	 * Đăng ký hook. Chỉ thêm hook, không gọi __() ở đây.
	 */
	public static function init() {
		add_filter( 'use_block_editor_for_post', array( __CLASS__, 'filter_block_editor' ), 10, 2 );
		add_action( 'add_meta_boxes_page', array( __CLASS__, 'register_meta_box' ) );
		add_action( 'edit_form_after_title', array( __CLASS__, 'render_instruction' ) );
		add_action( 'save_post_page', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
		add_filter( 'parent_file', array( __CLASS__, 'filter_parent_file' ) );
	}

	/* --- Lược đồ và dữ liệu ----------------------------------------------- */

	/**
	 * Các section theo thứ tự trên trang.
	 *
	 * @return array<string,string> id => tên section (cũng là tiêu đề mặc định).
	 */
	public static function sections() {
		return array(
			'story'      => __( 'Câu chuyện thương hiệu', 'isha-core' ),
			'philosophy' => __( 'Triết lý và sứ mệnh', 'isha-core' ),
			'values'     => __( 'Giá trị cốt lõi', 'isha-core' ),
		);
	}

	/**
	 * Khoá meta của một section.
	 *
	 * @param string $section Id section.
	 * @return string
	 */
	public static function meta_key( $section ) {
		return self::META_PREFIX . ( is_scalar( $section ) ? (string) $section : '' );
	}

	/**
	 * Trang có dùng mẫu "Giới thiệu" không.
	 *
	 * @param WP_Post|int|null $post Trang.
	 * @return bool
	 */
	public static function applies( $post ) {
		$post = get_post( $post );
		if ( ! $post || 'page' !== $post->post_type ) {
			return false;
		}
		return self::TEMPLATE === (string) get_page_template_slug( $post );
	}

	/**
	 * ID trang Giới thiệu đầu tiên (đã xuất bản trước, rồi nháp hoặc riêng tư), 0 nếu chưa có. Nhớ kết quả trong request.
	 *
	 * @return int
	 */
	public static function page_id() {
		static $id = null;
		if ( null === $id ) {
			$ids = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => array( 'publish', 'private', 'draft', 'pending', 'future' ),
					'posts_per_page' => 10,
					'fields'         => 'ids',
					'orderby'        => array(
						'menu_order' => 'ASC',
						'ID'         => 'ASC',
					),
					'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'     => self::TEMPLATE, // phpcs:ignore WordPress.DB.SlowDBQuery
					'no_found_rows'  => true,
				)
			);
			$id  = 0;
			foreach ( $ids as $candidate ) {
				if ( 'publish' === get_post_status( $candidate ) ) {
					$id = (int) $candidate;
					break;
				}
			}
			if ( ! $id && $ids ) {
				$id = (int) $ids[0];
			}
		}
		return $id;
	}

	/**
	 * Giá trị ĐÃ LƯU của một section, làm sạch lại khi đọc: `enabled` bool (chỉ khi đã lưu), title, subtitle, content (HTML đã lọc, chưa
	 * wpautop), image (int, 0 nếu ảnh không còn). Không có dữ liệu: array().
	 *
	 * @param string $section Id section.
	 * @param int    $post_id ID trang Giới thiệu.
	 * @return array
	 */
	public static function get( $section, $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || ! array_key_exists( (string) $section, self::sections() ) ) {
			return array();
		}
		$stored = get_post_meta( $post_id, self::meta_key( $section ), true );
		if ( ! is_array( $stored ) || ! $stored ) {
			return array();
		}
		$clean = self::sanitize_section( $stored );
		if ( isset( $clean['enabled'] ) ) {
			$clean['enabled'] = '1' === $clean['enabled'];
		}
		return $clean;
	}

	/**
	 * Làm sạch dữ liệu một section. Hàm thuần (chỉ đọc để kiểm tệp đính kèm). Chỉ giữ khoá có giá trị; `enabled` chỉ khi có giá trị rõ ràng.
	 *
	 * @param mixed $raw Dữ liệu thô (đã bỏ slash).
	 * @return array
	 */
	public static function sanitize_section( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();
		$out = array();

		if ( array_key_exists( 'enabled', $raw ) ) {
			$flag = is_scalar( $raw['enabled'] ) ? strtolower( trim( (string) $raw['enabled'] ) ) : '';
			if ( in_array( $flag, array( '1', 'true', 'on', 'yes' ), true ) ) {
				$out['enabled'] = '1';
			} elseif ( in_array( $flag, array( '0', 'false', 'off', 'no' ), true ) ) {
				$out['enabled'] = '0';
			}
		}

		$title = self::sanitize_line( isset( $raw['title'] ) ? $raw['title'] : '', self::TITLE_MAX );
		if ( '' !== $title ) {
			$out['title'] = $title;
		}
		$subtitle = self::sanitize_line( isset( $raw['subtitle'] ) ? $raw['subtitle'] : '', self::SUBTITLE_MAX );
		if ( '' !== $subtitle ) {
			$out['subtitle'] = $subtitle;
		}
		$content = self::sanitize_content( isset( $raw['content'] ) ? $raw['content'] : '' );
		if ( '' !== $content ) {
			$out['content'] = $content;
		}
		$image = class_exists( 'ISHA_Core_Admin_Media' ) ? ISHA_Core_Admin_Media::sanitize( 'image', isset( $raw['image'] ) ? $raw['image'] : 0 ) : 0;
		if ( $image > 0 ) {
			$out['image'] = (int) $image;
		}

		return $out;
	}

	/**
	 * Văn bản một dòng: bỏ thẻ, gộp khoảng trắng, cắt theo $max bằng mb_substr.
	 *
	 * @param mixed $raw Giá trị thô.
	 * @param int   $max Số ký tự tối đa.
	 * @return string
	 */
	private static function sanitize_line( $raw, $max ) {
		if ( ! is_scalar( $raw ) ) {
			return '';
		}
		$text = trim( (string) preg_replace( '/\s+/u', ' ', sanitize_text_field( (string) $raw ) ) );
		if ( mb_strlen( $text, 'UTF-8' ) > $max ) {
			$text = rtrim( mb_substr( $text, 0, $max, 'UTF-8' ) );
		}
		return $text;
	}

	/**
	 * Thẻ HTML được phép trong nội dung section: khớp thanh công cụ của trình soạn thảo gọn.
	 *
	 * @return array Bảng thẻ cho wp_kses().
	 */
	public static function allowed_html() {
		return array(
			'p'          => array(),
			'br'         => array(),
			'h3'         => array(),
			'h4'         => array(),
			'strong'     => array(),
			'b'          => array(),
			'em'         => array(),
			'i'          => array(),
			'ul'         => array(),
			'ol'         => array(),
			'li'         => array(),
			'blockquote' => array(),
			'a'          => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		);
	}

	/**
	 * Làm sạch nội dung: chỉ giữ thẻ của ::allowed_html(), chuẩn hoá xuống dòng về LF. Chỉ có khoảng trắng (kể cả &nbsp;) thì trả ''.
	 *
	 * @param mixed $raw Giá trị thô (đã bỏ slash).
	 * @return string
	 */
	public static function sanitize_content( $raw ) {
		if ( ! is_string( $raw ) ) {
			return '';
		}
		$html = trim( wp_kses( str_replace( array( "\r\n", "\r" ), "\n", $raw ), self::allowed_html() ) );
		$text = trim( str_replace( array( '&nbsp;', "\xC2\xA0" ), ' ', wp_strip_all_tags( $html ) ) );
		return '' === $text ? '' : $html;
	}

	/* --- Trình soạn thảo, meta box, menu ---------------------------------- */

	/**
	 * Tắt block editor cho trang dùng mẫu Giới thiệu (meta box có trình soạn thảo riêng).
	 *
	 * @param bool    $use_block_editor Kết quả hiện tại.
	 * @param WP_Post $post             Bài đang kiểm.
	 * @return bool
	 */
	public static function filter_block_editor( $use_block_editor, $post = null ) {
		return self::applies( $post ) ? false : $use_block_editor;
	}

	/**
	 * Thêm meta box và bỏ hỗ trợ trình soạn thảo của trang (nội dung trang không được dùng).
	 *
	 * @param WP_Post $post Trang đang sửa.
	 */
	public static function register_meta_box( $post ) {
		if ( ! self::applies( $post ) ) {
			return;
		}
		add_meta_box( 'isha_about_fields', __( 'Nội dung trang Giới thiệu', 'isha-core' ), array( __CLASS__, 'render_meta_box' ), 'page', 'normal', 'high' );
		remove_post_type_support( 'page', 'editor' );
	}

	/**
	 * Dòng hướng dẫn dưới ô tiêu đề.
	 *
	 * @param WP_Post $post Trang đang sửa.
	 */
	public static function render_instruction( $post ) {
		if ( ! self::applies( $post ) ) {
			return;
		}
		echo '<p class="isha-about-instruction">' . esc_html__( 'Trang Giới thiệu do theme dựng: tiêu đề trang là tiêu đề của banner; mô tả và ảnh/video nền nhập ở khung "Hero banner"; ba section nhập ở khung "Nội dung trang Giới thiệu".', 'isha-core' ) . '</p>';
	}

	/**
	 * Nạp ô chọn media và vài quy tắc CSS nhỏ ở màn sửa trang Giới thiệu.
	 *
	 * @param string $hook Hook suffix của màn hình quản trị.
	 */
	public static function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! self::applies( get_post() ) ) {
			return;
		}
		if ( class_exists( 'ISHA_Core_Admin_Media' ) ) {
			ISHA_Core_Admin_Media::enqueue();
		}
		$css = '.isha-about-instruction{margin:12px 0 0;padding:10px 12px;border-left:4px solid #8e6837;background:#fff}'
			. '.isha-about-box__sec{margin:0 0 20px;padding:16px 20px 8px;border:1px solid #c3c4c7;border-radius:4px;background:#fff}'
			. '.isha-about-box__sec legend{padding:0 6px;font-size:14px;font-weight:600}'
			. '.isha-about-box__field{margin:0 0 16px}'
			. '.isha-about-box__field>label,.isha-about-box__label{display:block;margin:0 0 6px;font-weight:600;color:#1d2327}'
			. '.isha-about-box__toggle{margin:0 0 14px}';
		wp_register_style( 'isha-about-fields', false, array(), ISHA_CORE_VERSION );
		wp_enqueue_style( 'isha-about-fields' );
		wp_add_inline_style( 'isha-about-fields', $css );
	}

	/**
	 * In meta box: một khung (fieldset) cho mỗi section, kèm nonce.
	 *
	 * @param WP_Post $post Trang đang sửa.
	 */
	public static function render_meta_box( $post ) {
		$post_id = $post instanceof WP_Post ? (int) $post->ID : 0;
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD, false );

		echo '<p class="description">' . esc_html__( 'Ba section hiện theo thứ tự dưới đây. Section không có tiêu đề nhỏ, nội dung và ảnh thì không hiện; ảnh để trống thì section dùng bố cục chỉ có chữ.', 'isha-core' ) . '</p>';

		$index = 0;
		foreach ( self::sections() as $id => $label ) {
			++$index;
			$data    = self::get( $id, $post_id );
			$enabled = ! isset( $data['enabled'] ) || false !== $data['enabled'];
			$base    = 'isha-about-' . $id;
			$name    = static function ( $key ) use ( $id ) {
				return self::INPUT . '[' . $id . '][' . $key . ']';
			};

			echo '<fieldset class="isha-about-box__sec">';
			/* translators: 1: số thứ tự section, 2: tên section */
			echo '<legend>' . esc_html( sprintf( __( 'Section %1$d: %2$s', 'isha-core' ), $index, $label ) ) . '</legend>';

			echo '<div class="isha-about-box__toggle">';
			echo '<input type="hidden" name="' . esc_attr( $name( 'enabled' ) ) . '" value="0">';
			echo '<label for="' . esc_attr( $base . '-enabled' ) . '"><input type="checkbox" id="' . esc_attr( $base . '-enabled' ) . '" name="' . esc_attr( $name( 'enabled' ) ) . '" value="1"' . checked( $enabled, true, false ) . '> ' . esc_html__( 'Hiển thị section này', 'isha-core' ) . '</label>';
			echo '</div>';

			echo '<div class="isha-about-box__field">';
			echo '<label for="' . esc_attr( $base . '-title' ) . '">' . esc_html__( 'Tiêu đề', 'isha-core' ) . '</label>';
			echo '<input type="text" class="widefat" id="' . esc_attr( $base . '-title' ) . '" name="' . esc_attr( $name( 'title' ) ) . '" value="' . esc_attr( isset( $data['title'] ) ? $data['title'] : '' ) . '" maxlength="' . esc_attr( (string) self::TITLE_MAX ) . '" placeholder="' . esc_attr( $label ) . '">';
			echo '</div>';

			echo '<div class="isha-about-box__field">';
			echo '<label for="' . esc_attr( $base . '-subtitle' ) . '">' . esc_html__( 'Tiêu đề nhỏ', 'isha-core' ) . '</label>';
			echo '<input type="text" class="widefat" id="' . esc_attr( $base . '-subtitle' ) . '" name="' . esc_attr( $name( 'subtitle' ) ) . '" value="' . esc_attr( isset( $data['subtitle'] ) ? $data['subtitle'] : '' ) . '" maxlength="' . esc_attr( (string) self::SUBTITLE_MAX ) . '" aria-describedby="' . esc_attr( $base . '-subtitle-help' ) . '">';
			echo '<p class="description" id="' . esc_attr( $base . '-subtitle-help' ) . '">' . esc_html__( 'Một câu ngắn hiện nghiêng dưới tiêu đề, có vạch vàng bên trái (tối đa 200 ký tự).', 'isha-core' ) . '</p>';
			echo '</div>';

			$editor_id = 'isha_about_' . $id . '_content';
			echo '<div class="isha-about-box__field">';
			echo '<label class="isha-about-box__label" for="' . esc_attr( $editor_id ) . '">' . esc_html__( 'Nội dung', 'isha-core' ) . '</label>';
			wp_editor(
				isset( $data['content'] ) ? $data['content'] : '',
				$editor_id,
				array(
					'textarea_name'    => $name( 'content' ),
					'textarea_rows'    => 10,
					'media_buttons'    => false,
					'drag_drop_upload' => false,
					'teeny'            => false,
					'tinymce'          => array(
						'toolbar1'      => 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink,removeformat,undo,redo',
						'toolbar2'      => '',
						'block_formats' => __( 'Đoạn văn', 'isha-core' ) . '=p;' . __( 'Tiêu đề phụ', 'isha-core' ) . '=h3;' . __( 'Tiêu đề nhỏ', 'isha-core' ) . '=h4',
					),
					'quicktags'        => array( 'buttons' => 'strong,em,link,ul,ol,li,block,close' ),
				)
			);
			echo '<p class="description">' . esc_html__( 'Đoạn văn, tiêu đề phụ, chữ đậm, nghiêng, danh sách, trích dẫn và liên kết. Trong danh sách, phần chữ đậm ở đầu mỗi dòng hiện thành tên mục (ví dụ tên từng giá trị).', 'isha-core' ) . '</p>';
			echo '</div>';

			echo '<div class="isha-about-box__field">';
			echo '<span class="isha-about-box__label">' . esc_html__( 'Ảnh minh hoạ (tuỳ chọn)', 'isha-core' ) . '</span>';
			if ( class_exists( 'ISHA_Core_Admin_Media' ) ) {
				ISHA_Core_Admin_Media::render(
					array(
						'name'  => $name( 'image' ),
						'type'  => 'image',
						'value' => isset( $data['image'] ) ? (int) $data['image'] : 0,
						'label' => __( 'Ảnh minh hoạ', 'isha-core' ),
						'help'  => __( 'Ảnh dọc 4:5, tối thiểu 1200×1500, JPG hoặc WebP tối đa 350 KB. Ảnh đặt xen kẽ trái, phải theo thứ tự section, trong khung viền vàng kim.', 'isha-core' ),
						'id'    => $base . '-image',
					)
				);
			}
			echo '</div>';

			echo '</fieldset>';
		}
	}

	/**
	 * Mục menu cấp một "Giới thiệu" (ngay dưới "Trang chủ") mở thẳng màn sửa trang Giới thiệu. Không có trang hoặc không sửa được thì không thêm.
	 */
	public static function register_admin_menu() {
		$page_id = self::page_id();
		if ( $page_id < 1 || ! current_user_can( 'edit_post', $page_id ) ) {
			return;
		}
		add_menu_page( __( 'Nội dung trang Giới thiệu', 'isha-core' ), __( 'Giới thiệu', 'isha-core' ), 'edit_pages', self::admin_menu_slug( $page_id ), '', 'dashicons-info-outline', 20.6 );
	}

	/**
	 * Slug của mục menu: đường dẫn màn sửa trang (WordPress in thẳng liên kết khi slug là một tệp có trong wp-admin).
	 *
	 * @param int $page_id ID trang.
	 * @return string
	 */
	private static function admin_menu_slug( $page_id ) {
		return 'post.php?post=' . (int) $page_id . '&action=edit';
	}

	/**
	 * Khi đang sửa đúng trang của mục menu "Giới thiệu" thì đánh dấu mục đó là mục đang mở.
	 *
	 * @param string $parent_file Mục menu cha WordPress đã chọn.
	 * @return string
	 */
	public static function filter_parent_file( $parent_file ) {
		global $pagenow;

		if ( 'post.php' !== $pagenow ) {
			return $parent_file;
		}
		$post = get_post();
		if ( ! $post || (int) $post->ID !== self::page_id() ) {
			return $parent_file;
		}
		return self::admin_menu_slug( (int) $post->ID );
	}

	/* --- Lưu --------------------------------------------------------------- */

	/**
	 * Lưu ba section khi lưu trang (`save_post_page`). Bỏ qua khi thiếu/sai nonce, autosave, revision, không có quyền, hoặc trang không dùng
	 * mẫu Giới thiệu. Section không có trong dữ liệu gửi lên được giữ nguyên; section không còn gì để lưu (và không tắt) thì xoá meta.
	 *
	 * @param int          $post_id ID trang.
	 * @param WP_Post|null $post    Trang.
	 */
	public static function save( $post_id, $post = null ) {
		$post_id = (int) $post_id;
		if ( $post_id < 1 || ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ! self::applies( $post_id ) ) {
			return;
		}

		$raw = isset( $_POST[ self::INPUT ] ) ? wp_unslash( $_POST[ self::INPUT ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_section() làm sạch từng khoá.
		if ( ! is_array( $raw ) ) {
			return;
		}

		foreach ( array_keys( self::sections() ) as $section ) {
			if ( ! isset( $raw[ $section ] ) || ! is_array( $raw[ $section ] ) ) {
				continue;
			}
			$clean = self::sanitize_section( $raw[ $section ] );
			$blank = ! array_diff_key( $clean, array( 'enabled' => true ) ) && ( ! isset( $clean['enabled'] ) || '1' === $clean['enabled'] );
			if ( $blank ) {
				delete_post_meta( $post_id, self::meta_key( $section ) );
			} else {
				update_post_meta( $post_id, self::meta_key( $section ), wp_slash( $clean ) );
			}
		}
	}
}
