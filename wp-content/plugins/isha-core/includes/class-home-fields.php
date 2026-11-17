<?php
/**
 * Meta box "Nội dung trang chủ": các trường nội dung của trang chủ điện ảnh, lưu native (khoá meta `_isha_home_<section>`).
 *
 * Lược đồ trường nằm ở includes/home-schema.php (nguồn duy nhất cho giao diện, sanitize và kiểm thử). Mỗi section lưu vào
 * MỘT meta dạng mảng:
 *   enabled  '1' | '0' (không có khoá nghĩa là bật)
 *   <field>  text/textarea: chuỗi; bool: bool; image/video: int (ID tệp đính kèm); gallery: int[] theo thứ tự
 *   <group>  danh sách đủ `count` mảng con, mỗi mảng chứa các trường của một mục lặp (mục chưa nhập là array())
 * Chỉ lưu giá trị khác rỗng (trừ `enabled` và trường bool), nên ô để trống dùng nội dung mặc định của theme; section chưa
 * tuỳ chỉnh gì thì không có meta. Giao diện là các thẻ (tab) trong một meta box, ô chọn media dùng ISHA_Core_Admin_Media.
 *
 * Trang chủ do theme dựng nên trình soạn thảo của trang này bị ẩn (cả block editor); các trang khác không bị ảnh hưởng. Menu quản trị có
 * mục cấp một "Trang chủ" mở thẳng màn sửa trang này.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Home_Fields {

	/**
	 * Tiền tố khoá meta; khoá đầy đủ là META_PREFIX . <id section>.
	 */
	const META_PREFIX = '_isha_home_';

	/**
	 * Action và tên trường của nonce.
	 */
	const NONCE_ACTION = 'isha_save_home';
	const NONCE_FIELD  = 'isha_home_nonce';

	/**
	 * Tên input gốc: isha_home[<section>][...].
	 */
	const INPUT = 'isha_home';

	/**
	 * Id của meta box.
	 */
	const BOX_ID = 'isha_home_fields';

	/**
	 * Các kiểu trường hợp lệ; kiểu lạ trong lược đồ được coi là text.
	 */
	const TYPES = array( 'text', 'textarea', 'bool', 'image', 'video', 'gallery' );

	/**
	 * Giới hạn ký tự dự phòng khi lược đồ thiếu `max` (text, textarea).
	 */
	const DEFAULT_MAX_TEXT     = 200;
	const DEFAULT_MAX_TEXTAREA = 1000;

	/**
	 * Số mục tối đa của một nhóm lặp (chặn lược đồ sai làm phình bộ nhớ).
	 */
	const MAX_ITEMS = 50;

	/**
	 * Lược đồ đọc từ tệp (chưa qua filter); filter chạy lại ở mỗi lần gọi schema().
	 *
	 * @var array|null
	 */
	private static $raw_schema = null;

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

	/* --- Lối tắt "Trang chủ" trong menu quản trị ------------------------------ */

	/**
	 * Đường dẫn màn sửa trang chủ (dùng làm slug của mục menu: WordPress in thẳng liên kết khi slug là một tệp có trong wp-admin).
	 *
	 * @param int $page_id ID trang chủ.
	 * @return string
	 */
	private static function admin_menu_slug( $page_id ) {
		return 'post.php?post=' . (int) $page_id . '&action=edit';
	}

	/**
	 * Mục menu cấp một "Trang chủ" (ngay dưới Trang) mở thẳng màn nhập nội dung trang chủ, để người nhập không phải tìm trang trong
	 * danh sách Trang. Không có trang chủ hoặc người dùng không sửa được trang đó thì không thêm.
	 */
	public static function register_admin_menu() {
		$page_id = self::page_id();
		if ( $page_id < 1 || ! current_user_can( 'edit_post', $page_id ) ) {
			return;
		}
		add_menu_page( __( 'Nội dung trang chủ', 'isha-core' ), __( 'Trang chủ', 'isha-core' ), 'edit_pages', self::admin_menu_slug( $page_id ), '', 'dashicons-admin-home', 20.5 );
	}

	/**
	 * Khi đang sửa trang chủ, đánh dấu mục menu "Trang chủ" (thay vì mục Trang) là mục đang mở.
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
		if ( ! self::is_home_post( $post ) ) {
			return $parent_file;
		}
		return self::admin_menu_slug( (int) $post->ID );
	}

	/* --- Lược đồ và dữ liệu ----------------------------------------------- */

	/**
	 * Lược đồ trường của trang chủ (mảng của home-schema.php), qua filter `isha_core/home_schema`.
	 *
	 * Gọi sau `init` (tệp lược đồ có __()). Trả về đúng mảng của tệp/filter, không thêm khoá mặc định.
	 *
	 * @return array
	 */
	public static function schema() {
		if ( null === self::$raw_schema ) {
			$file   = ISHA_CORE_DIR . 'includes/home-schema.php';
			$loaded = array();
			if ( is_readable( $file ) ) {
				$loaded = include $file;
			}
			self::$raw_schema = is_array( $loaded ) ? $loaded : array();
		}

		$schema = apply_filters( 'isha_core/home_schema', self::$raw_schema );

		return is_array( $schema ) ? $schema : self::$raw_schema;
	}

	/**
	 * Danh sách section theo đúng thứ tự lược đồ.
	 *
	 * @return array<string,string> id => nhãn (nhãn thiếu thì dùng id).
	 */
	public static function sections() {
		$sections = array();
		foreach ( self::schema() as $id => $def ) {
			$id = (string) $id;
			if ( '' === $id || ! is_array( $def ) ) {
				continue;
			}
			$sections[ $id ] = ( isset( $def['label'] ) && is_string( $def['label'] ) && '' !== trim( $def['label'] ) ) ? $def['label'] : $id;
		}
		return $sections;
	}

	/**
	 * ID trang chứa dữ liệu trang chủ. Không nhớ tạm: đọc lại option và DB ở mỗi lần gọi.
	 *
	 * Thứ tự: (1) filter `isha_core/home_page_id` nếu trả ID của một page; (2) `page_on_front` khi `show_on_front` là page và
	 * trang đó KHÔNG dựng bằng Elementor; (3) page có slug `trang-chu`; (4) 0.
	 *
	 * @return int
	 */
	public static function page_id() {
		$filtered = apply_filters( 'isha_core/home_page_id', 0 );
		if ( is_numeric( $filtered ) && (int) $filtered > 0 && 'page' === get_post_type( (int) $filtered ) ) {
			return (int) $filtered;
		}

		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front = (int) get_option( 'page_on_front' );
			if ( $front > 0 && 'page' === get_post_type( $front ) && 'builder' !== get_post_meta( $front, '_elementor_edit_mode', true ) ) {
				return $front;
			}
		}

		$page = get_page_by_path( 'trang-chu', OBJECT, array( 'page' ) );
		if ( $page instanceof WP_Post && 'page' === $page->post_type && 'trash' !== $page->post_status ) {
			return (int) $page->ID;
		}

		return 0;
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
	 * Giá trị ĐÃ LƯU của một section (không trộn mặc định), được sanitize lại khi đọc.
	 *
	 * Chỉ có các khoá đã lưu: `enabled` là bool (không có khoá nếu chưa lưu), text/textarea là chuỗi, bool là bool,
	 * image/video là int, gallery là int[], nhóm là danh sách đủ `count` phần tử. Không có dữ liệu thì trả array().
	 *
	 * @param string $section Id section.
	 * @param int    $page_id ID trang chủ; 0 thì dùng page_id().
	 * @return array
	 */
	public static function get( $section, $page_id = 0 ) {
		if ( null === self::section_def( $section ) ) {
			return array();
		}

		$page_id = (int) $page_id;
		if ( $page_id < 1 ) {
			$page_id = self::page_id();
		}
		if ( $page_id < 1 ) {
			return array();
		}

		$stored = get_post_meta( $page_id, self::meta_key( $section ), true );
		if ( ! is_array( $stored ) || ! $stored ) {
			return array();
		}

		$clean = self::sanitize_section( $section, $stored );
		if ( isset( $clean['enabled'] ) ) {
			$clean['enabled'] = '1' === $clean['enabled'];
		}
		return $clean;
	}

	/**
	 * Làm sạch dữ liệu một section theo lược đồ. Hàm thuần: không ghi gì, chỉ đọc để kiểm tệp đính kèm.
	 *
	 * Kết quả đúng hình dạng sẽ lưu: `enabled` ('1'|'0', chỉ khi đầu vào có giá trị rõ ràng), các trường theo thứ tự lược đồ,
	 * rồi các nhóm (đủ `count` mục). Chỉ giữ giá trị khác rỗng, trừ `enabled` và trường bool. Khoá lạ bị bỏ; giá trị sai
	 * kiểu (ví dụ mảng ở chỗ cần chuỗi) coi như rỗng; chuỗi dài hơn `max` bị cắt; gallery cắt theo `max`.
	 *
	 * @param string $section Id section.
	 * @param mixed  $raw     Dữ liệu thô của section (đã bỏ slash ở nơi gọi).
	 * @return array array() nếu section không có trong lược đồ hoặc không có gì để lưu.
	 */
	public static function sanitize_section( $section, $raw ) {
		$def = self::section_def( $section );
		if ( null === $def ) {
			return array();
		}
		$raw = is_array( $raw ) ? $raw : array();
		$out = array();

		if ( array_key_exists( 'enabled', $raw ) ) {
			$enabled = self::to_bool( $raw['enabled'] );
			if ( null !== $enabled ) {
				$out['enabled'] = $enabled ? '1' : '0';
			}
		}

		$out += self::sanitize_fields( $def['fields'], $raw );

		foreach ( $def['groups'] as $key => $group ) {
			$items = self::sanitize_group( $group, isset( $raw[ $key ] ) ? $raw[ $key ] : null );
			if ( null !== $items ) {
				$out[ $key ] = $items;
			}
		}

		return $out;
	}

	/* --- Chuẩn hoá lược đồ ------------------------------------------------- */

	/**
	 * Section đã chuẩn hoá (điền mặc định cho khoá thiếu), hoặc null nếu section không có trong lược đồ.
	 *
	 * @param mixed $section Id section.
	 * @return array|null
	 */
	private static function section_def( $section ) {
		if ( ! is_string( $section ) || '' === $section ) {
			return null;
		}
		$schema = self::schema();
		if ( ! isset( $schema[ $section ] ) || ! is_array( $schema[ $section ] ) ) {
			return null;
		}
		self::load_media();

		$def    = $schema[ $section ];
		$groups = array();
		if ( isset( $def['groups'] ) && is_array( $def['groups'] ) ) {
			foreach ( $def['groups'] as $key => $group ) {
				$group = self::normalize_group( $group );
				if ( null !== $group ) {
					$groups[ (string) $key ] = $group;
				}
			}
		}

		return array(
			'note'   => ( isset( $def['note'] ) && is_string( $def['note'] ) ) ? $def['note'] : '',
			'fields' => self::normalize_fields( isset( $def['fields'] ) ? $def['fields'] : array() ),
			'groups' => $groups,
		);
	}

	/**
	 * Chuẩn hoá danh sách trường (bỏ phần tử không phải mảng).
	 *
	 * @param mixed $fields array( khoá => trường ).
	 * @return array
	 */
	private static function normalize_fields( $fields ) {
		$out = array();
		if ( ! is_array( $fields ) ) {
			return $out;
		}
		foreach ( $fields as $key => $field ) {
			if ( is_array( $field ) ) {
				$out[ (string) $key ] = self::normalize_field( (string) $key, $field );
			}
		}
		return $out;
	}

	/**
	 * Chuẩn hoá một trường: type (kiểu lạ thành text), label (thiếu thì dùng khoá), max, rows, help, on.
	 *
	 * @param string $key   Khoá trường.
	 * @param array  $field Trường trong lược đồ.
	 * @return array
	 */
	private static function normalize_field( $key, array $field ) {
		$type = ( isset( $field['type'] ) && is_string( $field['type'] ) ) ? $field['type'] : 'text';
		if ( ! in_array( $type, self::TYPES, true ) ) {
			$type = 'text';
		}

		$max = ( isset( $field['max'] ) && is_numeric( $field['max'] ) ) ? (int) $field['max'] : 0;
		if ( 'gallery' === $type ) {
			$max = $max > 0 ? min( $max, ISHA_Core_Admin_Media::MAX_GALLERY ) : ISHA_Core_Admin_Media::MAX_GALLERY;
		} elseif ( 'text' === $type || 'textarea' === $type ) {
			if ( $max < 1 ) {
				$max = 'text' === $type ? self::DEFAULT_MAX_TEXT : self::DEFAULT_MAX_TEXTAREA;
			}
		} else {
			$max = 0;
		}

		$rows = ( isset( $field['rows'] ) && is_numeric( $field['rows'] ) ) ? (int) $field['rows'] : 3;

		return array(
			'type'  => $type,
			'label' => ( isset( $field['label'] ) && is_string( $field['label'] ) && '' !== trim( $field['label'] ) ) ? $field['label'] : $key,
			'max'   => $max,
			'rows'  => max( 2, min( 20, $rows ) ),
			'help'  => ( isset( $field['help'] ) && is_string( $field['help'] ) ) ? trim( $field['help'] ) : '',
			'on'    => ! empty( $field['on'] ),
		);
	}

	/**
	 * Chuẩn hoá một nhóm lặp; null nếu không phải mảng hoặc không có mục/trường nào.
	 *
	 * @param mixed $group Nhóm trong lược đồ.
	 * @return array|null array( label, count, fields ).
	 */
	private static function normalize_group( $group ) {
		if ( ! is_array( $group ) ) {
			return null;
		}

		$count  = ( isset( $group['count'] ) && is_numeric( $group['count'] ) ) ? (int) $group['count'] : 0;
		$count  = max( 0, min( self::MAX_ITEMS, $count ) );
		$fields = self::normalize_fields( isset( $group['fields'] ) ? $group['fields'] : array() );
		if ( $count < 1 || ! $fields ) {
			return null;
		}

		if ( isset( $group['label'] ) && is_string( $group['label'] ) && '' !== trim( $group['label'] ) ) {
			$label = $group['label'];
		} else {
			/* translators: %d: số thứ tự của mục trong nhóm, bắt đầu từ 1 */
			$label = __( 'Mục %d', 'isha-core' );
		}

		return array(
			'label'  => $label,
			'count'  => $count,
			'fields' => $fields,
		);
	}

	/**
	 * Nạp class ô chọn media nếu bước tích hợp chưa nạp (không phụ thuộc thứ tự require_once).
	 */
	private static function load_media() {
		if ( ! class_exists( 'ISHA_Core_Admin_Media', false ) ) {
			require_once ISHA_CORE_DIR . 'includes/class-admin-media.php';
		}
	}

	/* --- Làm sạch ---------------------------------------------------------- */

	/**
	 * Làm sạch các trường của một mảng thô (section hoặc một mục của nhóm).
	 *
	 * @param array $fields Trường đã chuẩn hoá.
	 * @param array $raw    Dữ liệu thô.
	 * @return array Chỉ các trường có giá trị để lưu, theo thứ tự lược đồ.
	 */
	private static function sanitize_fields( array $fields, array $raw ) {
		$out = array();
		foreach ( $fields as $key => $field ) {
			if ( ! array_key_exists( $key, $raw ) ) {
				continue;
			}
			$value = self::sanitize_field( $field, $raw[ $key ] );
			if ( null !== $value ) {
				$out[ $key ] = $value;
			}
		}
		return $out;
	}

	/**
	 * Làm sạch một nhóm lặp: đủ `count` mục theo đúng vị trí (mục thừa bị cắt, mục thiếu là array()).
	 *
	 * @param array $group Nhóm đã chuẩn hoá.
	 * @param mixed $raw   Dữ liệu thô của nhóm: mảng chỉ số 0..count-1 mỗi phần tử là mảng trường.
	 * @return array|null Danh sách mục, hoặc null nếu mọi mục đều rỗng (không lưu).
	 */
	private static function sanitize_group( array $group, $raw ) {
		$raw   = is_array( $raw ) ? $raw : array();
		$items = array();
		$any   = false;

		for ( $i = 0; $i < $group['count']; $i++ ) {
			$item_raw = ( isset( $raw[ $i ] ) && is_array( $raw[ $i ] ) ) ? $raw[ $i ] : array();
			$item     = self::sanitize_fields( $group['fields'], $item_raw );
			$any      = $any || ! empty( $item );
			$items[]  = $item;
		}

		return $any ? $items : null;
	}

	/**
	 * Làm sạch một giá trị theo kiểu trường.
	 *
	 * @param array $field Trường đã chuẩn hoá.
	 * @param mixed $raw   Giá trị thô.
	 * @return mixed null nghĩa là không lưu (rỗng hoặc sai kiểu).
	 */
	private static function sanitize_field( array $field, $raw ) {
		switch ( $field['type'] ) {
			case 'bool':
				return self::to_bool( $raw );

			case 'image':
			case 'video':
				$id = ISHA_Core_Admin_Media::sanitize( $field['type'], $raw );
				return $id > 0 ? (int) $id : null;

			case 'gallery':
				$ids = ISHA_Core_Admin_Media::sanitize( 'gallery', $raw );
				$ids = array_slice( is_array( $ids ) ? array_values( $ids ) : array(), 0, $field['max'] );
				return $ids ? $ids : null;

			case 'textarea':
				return self::sanitize_string( $raw, $field['max'], true );

			default:
				return self::sanitize_string( $raw, $field['max'], false );
		}
	}

	/**
	 * Làm sạch chuỗi: chỉ nhận chuỗi hoặc số; text bỏ thẻ và xuống dòng, textarea giữ xuống dòng (chuẩn hoá về LF để `max`
	 * đếm một xuống dòng là một ký tự như thuộc tính maxlength của trình duyệt); cắt theo `max` bằng mb_substr.
	 *
	 * @param mixed $raw       Giá trị thô.
	 * @param int   $max       Số ký tự tối đa.
	 * @param bool  $multiline true với textarea.
	 * @return string|null null nếu rỗng hoặc sai kiểu.
	 */
	private static function sanitize_string( $raw, $max, $multiline ) {
		if ( is_int( $raw ) || is_float( $raw ) ) {
			$raw = (string) $raw;
		}
		if ( ! is_string( $raw ) ) {
			return null;
		}

		if ( $multiline ) {
			$value = sanitize_textarea_field( str_replace( array( "\r\n", "\r" ), "\n", $raw ) );
		} else {
			$value = sanitize_text_field( $raw );
		}

		if ( $max > 0 && mb_strlen( $value, 'UTF-8' ) > $max ) {
			$value = mb_substr( $value, 0, $max, 'UTF-8' );
			// Không để lại nửa thực thể `&lt;` (sanitize đổi dấu `<` lẻ thành `&lt;`) và khoảng trắng đuôi sau khi cắt.
			$value = rtrim( (string) preg_replace( '/&(?:lt?)?$/', '', $value ) );
		}

		return '' === $value ? null : $value;
	}

	/**
	 * Đổi giá trị thô sang bool: chỉ nhận dạng rõ ràng (1/0, true/false, on/off, yes/no).
	 *
	 * @param mixed $value Giá trị thô.
	 * @return bool|null null nếu không rõ nghĩa (coi như chưa đặt).
	 */
	private static function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_int( $value ) ) {
			return 1 === $value ? true : ( 0 === $value ? false : null );
		}
		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );
			if ( in_array( $value, array( '1', 'true', 'on', 'yes' ), true ) ) {
				return true;
			}
			if ( in_array( $value, array( '0', 'false', 'off', 'no' ), true ) ) {
				return false;
			}
		}
		return null;
	}

	/**
	 * Section đã làm sạch có "trống" không: không còn giá trị nào đáng lưu. `enabled` = '1' và trường bool bằng mặc định
	 * không tính; `enabled` = '0' thì luôn đáng lưu.
	 *
	 * @param string $section Id section.
	 * @param array  $clean   Kết quả của sanitize_section().
	 * @return bool
	 */
	private static function is_blank( $section, array $clean ) {
		if ( isset( $clean['enabled'] ) && '0' === $clean['enabled'] ) {
			return false;
		}

		$def    = self::section_def( $section );
		$fields = $def ? $def['fields'] : array();
		foreach ( $clean as $key => $value ) {
			if ( 'enabled' === $key ) {
				continue;
			}
			if ( isset( $fields[ $key ] ) && 'bool' === $fields[ $key ]['type'] && (bool) $value === $fields[ $key ]['on'] ) {
				continue;
			}
			return false;
		}
		return true;
	}

	/* --- Trình soạn thảo và meta box -------------------------------------- */

	/**
	 * Trang này có phải trang chủ (trang chứa dữ liệu trang chủ) không.
	 *
	 * @param mixed $post WP_Post hoặc ID.
	 * @return bool
	 */
	private static function is_home_post( $post ) {
		if ( empty( $post ) ) {
			return false;
		}
		$post = get_post( $post );
		if ( ! $post || 'page' !== $post->post_type ) {
			return false;
		}
		$home = self::page_id();
		return $home > 0 && (int) $post->ID === $home;
	}

	/**
	 * Tắt block editor cho trang chủ; mọi trang khác giữ nguyên kết quả đã có.
	 *
	 * @param bool    $use_block_editor Kết quả hiện tại.
	 * @param WP_Post $post             Bài đang kiểm.
	 * @return bool
	 */
	public static function filter_block_editor( $use_block_editor, $post = null ) {
		return self::is_home_post( $post ) ? false : $use_block_editor;
	}

	/**
	 * Thêm meta box và bỏ hỗ trợ trình soạn thảo, chỉ khi màn hình đang sửa đúng trang chủ.
	 *
	 * Gọi ở `add_meta_boxes_page`: lúc này edit-form-advanced.php chưa in vùng soạn thảo nên bỏ được `editor`.
	 *
	 * @param WP_Post $post Trang đang sửa.
	 */
	public static function register_meta_box( $post ) {
		if ( ! self::is_home_post( $post ) ) {
			return;
		}

		add_meta_box( self::BOX_ID, __( 'Nội dung trang chủ', 'isha-core' ), array( __CLASS__, 'render_meta_box' ), 'page', 'normal', 'high' );
		remove_post_type_support( 'page', 'editor' );
	}

	/**
	 * In dòng hướng dẫn dưới ô tiêu đề của trang chủ.
	 *
	 * @param WP_Post $post Trang đang sửa.
	 */
	public static function render_instruction( $post ) {
		if ( ! self::is_home_post( $post ) ) {
			return;
		}
		echo '<p class="isha-home-instruction">' . esc_html__( 'Trang chủ do theme dựng; chỉnh nội dung ở các thẻ bên dưới.', 'isha-core' ) . '</p>';
	}

	/**
	 * Nạp JS và CSS: chỉ khi sửa trang chủ ở post.php hoặc post-new.php.
	 *
	 * @param string $hook Hook suffix của màn hình quản trị.
	 */
	public static function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! self::is_home_post( get_post() ) ) {
			return;
		}

		self::load_media();
		ISHA_Core_Admin_Media::enqueue();

		wp_enqueue_style( 'isha-admin-home', ISHA_CORE_URL . 'assets/css/admin-home.css', array( 'isha-admin-media' ), self::asset_version( 'assets/css/admin-home.css' ) );
		wp_enqueue_script( 'isha-admin-home', ISHA_CORE_URL . 'assets/js/admin-home.js', array(), self::asset_version( 'assets/js/admin-home.js' ), true );
	}

	/**
	 * Phiên bản asset theo thời điểm sửa tệp (không có tệp thì dùng phiên bản plugin).
	 *
	 * @param string $relative Đường dẫn tương đối từ thư mục plugin.
	 * @return string
	 */
	private static function asset_version( $relative ) {
		$path = ISHA_CORE_DIR . $relative;
		return file_exists( $path ) ? (string) filemtime( $path ) : ISHA_CORE_VERSION;
	}

	/* --- Hiển thị ---------------------------------------------------------- */

	/**
	 * In meta box: thanh thẻ (tablist) rồi một tabpanel cho mỗi section. Mọi panel đều có đủ trường (panel không chọn chỉ
	 * bị ẩn bằng thuộc tính hidden) để một lần gửi form mang toàn bộ dữ liệu.
	 *
	 * @param WP_Post $post Trang đang sửa.
	 */
	public static function render_meta_box( $post ) {
		self::load_media();

		$post     = get_post( $post );
		$post_id  = $post ? (int) $post->ID : 0;
		$sections = self::sections();

		if ( ! $sections ) {
			echo '<p>' . esc_html__( 'Chưa có lược đồ trường cho trang chủ.', 'isha-core' ) . '</p>';
			return;
		}

		$defaults = self::defaults();
		$slugs    = array();
		$index    = 0;
		foreach ( $sections as $id => $label ) {
			$slug = sanitize_html_class( (string) $id );
			if ( '' === $slug ) {
				$slug = 's' . $index;
			}
			$slugs[ $id ] = $slug;
			++$index;
		}

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD, false );

		echo '<div class="isha-home" id="isha-home">';
		echo '<p class="description isha-home__hint">' . esc_html__( 'Ô để trống sẽ dùng nội dung mặc định, hiện mờ ngay trong ô.', 'isha-core' ) . '</p>';

		echo '<div class="isha-home__tabs" role="tablist" aria-label="' . esc_attr__( 'Các section của trang chủ', 'isha-core' ) . '">';
		$active = true;
		foreach ( $sections as $id => $label ) {
			echo '<button type="button" role="tab" class="isha-home__tab" id="' . esc_attr( 'isha-home-tab-' . $slugs[ $id ] ) . '"'
				. ' aria-selected="' . ( $active ? 'true' : 'false' ) . '"'
				. ' aria-controls="' . esc_attr( 'isha-home-panel-' . $slugs[ $id ] ) . '"'
				. ' tabindex="' . ( $active ? '0' : '-1' ) . '"'
				. ' data-section="' . esc_attr( (string) $id ) . '">' . esc_html( $label ) . '</button>';
			$active = false;
		}
		echo '</div>';

		$active = true;
		foreach ( $sections as $id => $label ) {
			self::render_panel( (string) $id, $slugs[ $id ], $active, $post_id, $defaults );
			$active = false;
		}

		echo '</div>';
	}

	/**
	 * Mặc định của theme (placeholder), qua filter `isha_core/home_defaults`.
	 *
	 * @return array array( section => array( trường => giá trị ) ).
	 */
	private static function defaults() {
		$defaults = apply_filters( 'isha_core/home_defaults', array() );
		return is_array( $defaults ) ? $defaults : array();
	}

	/**
	 * Chuỗi mặc định ở một đường dẫn trong mảng mặc định (placeholder); rỗng nếu không có hoặc không phải chuỗi/số.
	 *
	 * @param array $defaults Mảng mặc định.
	 * @param array $path     Ví dụ array( 'philosophy', 'items', 0, 'letter' ).
	 * @return string
	 */
	private static function default_text( array $defaults, array $path ) {
		$node = $defaults;
		foreach ( $path as $step ) {
			if ( ! is_array( $node ) || ! isset( $node[ $step ] ) ) {
				return '';
			}
			$node = $node[ $step ];
		}
		return ( is_string( $node ) || is_int( $node ) || is_float( $node ) ) ? (string) $node : '';
	}

	/**
	 * Tên input: isha_home[<section>][<khoá>]... theo đường dẫn.
	 *
	 * @param array $path Các phần của đường dẫn.
	 * @return string
	 */
	private static function input_name( array $path ) {
		return self::INPUT . '[' . implode( '][', array_map( 'strval', $path ) ) . ']';
	}

	/**
	 * Gốc id DOM của một trường, theo đường dẫn (khác tiền tố id của thẻ và panel nên không trùng).
	 *
	 * @param array $path Các phần của đường dẫn.
	 * @return string
	 */
	private static function dom_id( array $path ) {
		return 'isha-home-field-' . implode( '-', array_map( 'sanitize_html_class', array_map( 'strval', $path ) ) );
	}

	/**
	 * Tiêu đề của một mục lặp: thay %d (hoặc %1$d) trong nhãn nhóm bằng số thứ tự bắt đầu từ 1; nhãn không có chỗ giữ
	 * chỗ thì nối số vào cuối.
	 *
	 * @param string $label    Nhãn nhóm, ví dụ "Chữ cái %d".
	 * @param int    $position Số thứ tự bắt đầu từ 1.
	 * @return string
	 */
	private static function item_title( $label, $position ) {
		$title = str_replace( array( '%1$d', '%d' ), (string) $position, $label );
		return $title === $label ? trim( $label . ' ' . $position ) : $title;
	}

	/**
	 * Giá trị hiện tại của một trường để đổ vào ô nhập (từ dữ liệu đã lưu, thiếu thì rỗng; bool thiếu thì theo `on`).
	 *
	 * @param array  $field  Trường đã chuẩn hoá.
	 * @param array  $source Mảng đã lưu chứa trường (section hoặc một mục của nhóm).
	 * @param string $key    Khoá trường.
	 * @return mixed
	 */
	private static function field_value( array $field, array $source, $key ) {
		switch ( $field['type'] ) {
			case 'bool':
				return array_key_exists( $key, $source ) ? (bool) $source[ $key ] : $field['on'];
			case 'image':
			case 'video':
				return isset( $source[ $key ] ) ? (int) $source[ $key ] : 0;
			case 'gallery':
				return ( isset( $source[ $key ] ) && is_array( $source[ $key ] ) ) ? $source[ $key ] : array();
			default:
				return ( isset( $source[ $key ] ) && is_string( $source[ $key ] ) ) ? $source[ $key ] : '';
		}
	}

	/**
	 * In một tabpanel: công tắc hiển thị, ghi chú, các trường, rồi các nhóm lặp.
	 *
	 * @param string $id       Id section.
	 * @param string $slug     Phần id DOM của thẻ/panel.
	 * @param bool   $active   Panel đang mở (không có thuộc tính hidden).
	 * @param int    $post_id  ID trang chủ (nguồn dữ liệu đã lưu).
	 * @param array  $defaults Mảng mặc định của theme.
	 */
	private static function render_panel( $id, $slug, $active, $post_id, array $defaults ) {
		$def    = self::section_def( $id );
		$def    = $def ? $def : array(
			'note'   => '',
			'fields' => array(),
			'groups' => array(),
		);
		$stored = self::get( $id, $post_id );

		echo '<div class="isha-home__panel" role="tabpanel" id="' . esc_attr( 'isha-home-panel-' . $slug ) . '" aria-labelledby="' . esc_attr( 'isha-home-tab-' . $slug ) . '" data-section="' . esc_attr( $id ) . '"' . ( $active ? '' : ' hidden' ) . '>';

		// Công tắc hiển thị: hidden 0 đi trước, checkbox 1 (nếu tick) ghi đè.
		$toggle_name = self::input_name( array( $id, 'enabled' ) );
		$toggle_id   = self::dom_id( array( $id, 'enabled' ) );
		$enabled     = ! isset( $stored['enabled'] ) || false !== $stored['enabled'];
		echo '<div class="isha-home__toggle">';
		echo '<input type="hidden" name="' . esc_attr( $toggle_name ) . '" value="0">';
		echo '<label for="' . esc_attr( $toggle_id ) . '"><input type="checkbox" id="' . esc_attr( $toggle_id ) . '" name="' . esc_attr( $toggle_name ) . '" value="1"' . checked( $enabled, true, false ) . '> ' . esc_html__( 'Hiển thị section này', 'isha-core' ) . '</label>';
		echo '</div>';

		if ( '' !== $def['note'] ) {
			echo '<p class="description isha-home__note">' . esc_html( $def['note'] ) . '</p>';
		}

		foreach ( $def['fields'] as $key => $field ) {
			self::render_field(
				$field,
				array( $id, $key ),
				self::field_value( $field, $stored, $key ),
				self::default_text( $defaults, array( $id, $key ) )
			);
		}

		foreach ( $def['groups'] as $gkey => $group ) {
			echo '<div class="isha-home__group">';
			for ( $i = 0; $i < $group['count']; $i++ ) {
				$item = ( isset( $stored[ $gkey ][ $i ] ) && is_array( $stored[ $gkey ][ $i ] ) ) ? $stored[ $gkey ][ $i ] : array();

				echo '<fieldset class="isha-home__item"><legend>' . esc_html( self::item_title( $group['label'], $i + 1 ) ) . '</legend>';
				echo '<div class="isha-home__item-fields">';
				foreach ( $group['fields'] as $key => $field ) {
					self::render_field(
						$field,
						array( $id, $gkey, $i, $key ),
						self::field_value( $field, $item, $key ),
						self::default_text( $defaults, array( $id, $gkey, $i, $key ) )
					);
				}
				echo '</div></fieldset>';
			}
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * In một trường theo kiểu. Text, textarea và bool có <label for>; ô media dùng tiêu đề nhìn thấy bên trên và aria-label
	 * của nhóm do ISHA_Core_Admin_Media in (cùng nội dung nhãn).
	 *
	 * @param array  $field       Trường đã chuẩn hoá.
	 * @param array  $path        Đường dẫn: array( section, khoá ) hoặc array( section, nhóm, chỉ số, khoá ).
	 * @param mixed  $value       Giá trị hiện tại.
	 * @param string $placeholder Chuỗi mặc định để làm placeholder (text, textarea).
	 */
	private static function render_field( array $field, array $path, $value, $placeholder ) {
		$type   = $field['type'];
		$name   = self::input_name( $path );
		$dom_id = self::dom_id( $path );
		$label  = $field['label'];
		$help   = $field['help'];

		$short = ( 'text' === $type && $field['max'] <= 8 ) ? ' isha-home__field--short' : '';
		echo '<div class="isha-home__field isha-home__field--' . esc_attr( $type ) . esc_attr( $short ) . '">';

		if ( 'image' === $type || 'video' === $type || 'gallery' === $type ) {
			echo '<span class="isha-home__label">' . esc_html( $label ) . '</span>';
			ISHA_Core_Admin_Media::render(
				array(
					'name'  => $name,
					'type'  => $type,
					'value' => $value,
					'label' => $label,
					'help'  => $help,
					'id'    => $dom_id,
					'max'   => $field['max'],
				)
			);
			echo '</div>';
			return;
		}

		if ( 'bool' === $type ) {
			echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="0">';
			echo '<label for="' . esc_attr( $dom_id ) . '"><input type="checkbox" id="' . esc_attr( $dom_id ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( (bool) $value, true, false ) . ( '' !== $help ? ' aria-describedby="' . esc_attr( $dom_id . '-help' ) . '"' : '' ) . '> ' . esc_html( $label ) . '</label>';
		} elseif ( 'textarea' === $type ) {
			echo '<label for="' . esc_attr( $dom_id ) . '">' . esc_html( $label ) . '</label>';
			// Hiển thị `<` lẻ đúng như người dùng đã gõ (sanitize lưu thành `&lt;`; esc_textarea sẽ mã hoá lần nữa).
			echo '<textarea class="widefat" id="' . esc_attr( $dom_id ) . '" name="' . esc_attr( $name ) . '" rows="' . esc_attr( (string) $field['rows'] ) . '" maxlength="' . esc_attr( (string) $field['max'] ) . '"'
				. ( '' !== $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' )
				. ( '' !== $help ? ' aria-describedby="' . esc_attr( $dom_id . '-help' ) . '"' : '' )
				. '>' . esc_textarea( str_replace( '&lt;', '<', (string) $value ) ) . '</textarea>';
		} else {
			echo '<label for="' . esc_attr( $dom_id ) . '">' . esc_html( $label ) . '</label>';
			echo '<input type="text" class="widefat" id="' . esc_attr( $dom_id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" maxlength="' . esc_attr( (string) $field['max'] ) . '"'
				. ( '' !== $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' )
				. ( '' !== $help ? ' aria-describedby="' . esc_attr( $dom_id . '-help' ) . '"' : '' )
				. '>';
		}

		if ( '' !== $help ) {
			echo '<p class="description" id="' . esc_attr( $dom_id . '-help' ) . '">' . esc_html( $help ) . '</p>';
		}

		echo '</div>';
	}

	/* --- Lưu --------------------------------------------------------------- */

	/**
	 * Lưu dữ liệu các section khi lưu trang (`save_post_page`).
	 *
	 * Bỏ qua khi: thiếu/sai nonce, autosave, revision, người dùng không có quyền sửa trang, hoặc trang không phải trang chủ.
	 * Section không có trong dữ liệu gửi lên (hoặc không phải mảng) được giữ nguyên. Section đã làm sạch mà trống (và
	 * không tắt) thì xoá meta để dùng mặc định.
	 *
	 * @param int          $post_id ID trang.
	 * @param WP_Post|null $post    Trang (không dùng).
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
		if ( ! current_user_can( 'edit_post', $post_id ) || self::page_id() !== $post_id ) {
			return;
		}

		// Mảng được bỏ slash ngay đây và làm sạch từng trường theo lược đồ ở sanitize_section().
		$raw = isset( $_POST[ self::INPUT ] ) ? wp_unslash( $_POST[ self::INPUT ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_section() làm sạch theo lược đồ.
		if ( ! is_array( $raw ) ) {
			return;
		}

		foreach ( array_keys( self::sections() ) as $section ) {
			$section = (string) $section;
			if ( ! isset( $raw[ $section ] ) || ! is_array( $raw[ $section ] ) ) {
				continue;
			}

			$clean = self::sanitize_section( $section, $raw[ $section ] );
			if ( self::is_blank( $section, $clean ) ) {
				delete_post_meta( $post_id, self::meta_key( $section ) );
			} else {
				update_post_meta( $post_id, self::meta_key( $section ), wp_slash( $clean ) );
			}
		}
	}
}
