<?php
/**
 * Term meta của nhóm dịch vụ (taxonomy isha_service_cat): cờ "Dịch vụ tiêu biểu", ảnh bìa, video bìa, nội dung giới thiệu, nhóm
 * "Hiệu quả liệu trình" (mô tả ngắn, ảnh trước/sau điều trị) và thứ tự hiển thị.
 *
 * Khoá meta (đều bắt đầu bằng gạch dưới nên không lộ ra ô "Custom Fields"):
 *   _isha_featured       – '1' khi nhóm hiện ở section "Dịch vụ tiêu biểu" của trang chủ (không có meta nghĩa là không hiện)
 *   _isha_cover_id       – ảnh bìa dọc 4:5 (ID tệp đính kèm)
 *   _isha_cover_video_id – video bìa tuỳ chọn, vòng lặp ngắn, không tiếng (ID tệp đính kèm)
 *   _isha_intro          – nội dung giới thiệu nhóm (HTML gọn, lọc bằng ::sanitize_intro(); lưu như bài viết: không có <p>, hiển thị qua wpautop)
 *   _isha_ba_desc        – mô tả ngắn hiệu quả liệu trình (văn bản thuần, nhiều dòng)
 *   _isha_ba_before      – ảnh TRƯỚC điều trị (mảng ID có thứ tự, tối đa BA_MAX)
 *   _isha_ba_after       – ảnh SAU điều trị (mảng ID có thứ tự, tối đa BA_MAX); ảnh thứ n của hai danh sách là một cặp
 *   _isha_order          – thứ tự hiển thị (số nguyên, nhỏ đứng trước; chưa đặt thì đứng cuối)
 * Mô tả (description) của term chính là tagline, nên không có ô riêng và không bị đổi ở đây.
 *
 * Giao diện: ô chọn media dùng chung ISHA_Core_Admin_Media ở màn "Thêm" (div.form-field) và "Sửa" (tr.form-field) nhóm
 * dịch vụ. Nội dung giới thiệu là trình soạn thảo gọn (wp_editor, không chèn media), chỉ có ở màn "Sửa": màn "Thêm" gửi bằng AJAX
 * nên TinyMCE không kịp ghi nội dung về textarea. Mô tả ngắn, ảnh trước và ảnh sau gom trong một nhóm "Hiệu quả liệu trình".
 * Lưu ở created_/edited_isha_service_cat, có nonce isha_save_term / isha_term_nonce và kiểm tra quyền edit_term.
 * Giá trị nhận về được làm sạch bằng ISHA_Core_Admin_Media::sanitize() (ảnh, video), ::sanitize_intro() (nội dung giới thiệu),
 * sanitize_textarea_field() (mô tả ngắn) và ::sanitize_order() (thứ tự).
 * Đọc dữ liệu: ::get() và ::ordered_terms(). Màn danh sách có thêm cột ảnh bìa nhỏ, thứ tự và "Tiêu biểu".
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Term_Fields {

	const TAXONOMY      = 'isha_service_cat';
	const KEY_FEATURED  = '_isha_featured';
	const KEY_COVER     = '_isha_cover_id';
	const KEY_VIDEO     = '_isha_cover_video_id';
	const KEY_ORDER     = '_isha_order';
	const KEY_BA_PRE    = '_isha_ba_before';
	const KEY_BA_POST   = '_isha_ba_after';
	const KEY_INTRO     = '_isha_intro';
	const KEY_BA_DESC   = '_isha_ba_desc';
	const NONCE_ACTION  = 'isha_save_term';
	const NONCE_FIELD   = 'isha_term_nonce';
	const FIELD_FEATURE = 'isha_term_featured';
	const FIELD_COVER   = 'isha_term_cover_id';
	const FIELD_VIDEO   = 'isha_term_cover_video_id';
	const FIELD_ORDER   = 'isha_term_order';
	const FIELD_BA_PRE  = 'isha_term_ba_before';
	const FIELD_BA_POST = 'isha_term_ba_after';
	const FIELD_INTRO   = 'isha_term_intro';
	const FIELD_BA_DESC = 'isha_term_ba_desc';

	/**
	 * ID của trình soạn thảo nội dung giới thiệu (wp_editor chỉ nhận chữ thường và gạch dưới); cũng là id của textarea.
	 */
	const EDITOR_ID = 'isha_term_intro';

	/**
	 * Số ký tự tối đa của mô tả ngắn hiệu quả liệu trình.
	 */
	const BA_DESC_MAX = 600;

	/**
	 * Giá trị tuyệt đối lớn nhất của thứ tự (9 chữ số), để số không tràn khi lưu và sắp xếp.
	 */
	const ORDER_LIMIT = 999999999;

	/**
	 * Số cặp ảnh trước/sau tối đa của một nhóm dịch vụ (không vượt ISHA_Core_Admin_Media::MAX_GALLERY = 24).
	 */
	const BA_MAX = 20;

	/**
	 * Đăng ký hook: ô nhập ở màn thêm/sửa term, lưu, nạp asset và cột của danh sách.
	 */
	public static function init() {
		$tax = self::TAXONOMY;

		add_action( $tax . '_add_form_fields', array( __CLASS__, 'render_add_fields' ) );
		add_action( $tax . '_edit_form_fields', array( __CLASS__, 'render_edit_fields' ) );
		add_action( 'created_' . $tax, array( __CLASS__, 'save' ) );
		add_action( 'edited_' . $tax, array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'manage_edit-' . $tax . '_columns', array( __CLASS__, 'columns' ) );
		add_filter( 'manage_' . $tax . '_custom_column', array( __CLASS__, 'column_content' ), 10, 3 );
	}

	/* --- Đọc dữ liệu ------------------------------------------------------- */

	/**
	 * Dữ liệu term meta của một nhóm dịch vụ, làm sạch lại khi đọc (tệp đã bị xoá khỏi thư viện thì trả 0).
	 *
	 * @param int $term_id ID term.
	 * @return array{featured:bool,cover_id:int,cover_video_id:int,order:int|null,intro:string,ba_desc:string,ba_before:int[],ba_after:int[]} `featured`
	 *         là true khi nhóm được tick "Dịch vụ tiêu biểu"; `order` là null khi chưa đặt;
	 *         `intro` là HTML đã lọc lại bằng ::sanitize_intro() (chưa qua wpautop); `ba_desc` là văn bản thuần;
	 *         `ba_before`/`ba_after` là mảng ID ảnh (tối đa BA_MAX), ảnh thứ n của hai mảng là một cặp. ID hỏng (ảnh đã bị xoá khỏi thư viện)
	 *         thành 0 nhưng GIỮ VỊ TRÍ, để các cặp phía sau không bị lệch (ghép nhầm ảnh trước của người này với ảnh sau của người khác).
	 */
	public static function get( $term_id ) {
		$term_id = is_numeric( $term_id ) ? (int) $term_id : 0;
		if ( $term_id < 1 ) {
			return array(
				'featured'       => false,
				'cover_id'       => 0,
				'cover_video_id' => 0,
				'order'          => null,
				'intro'          => '',
				'ba_desc'        => '',
				'ba_before'      => array(),
				'ba_after'       => array(),
			);
		}

		$intro   = get_term_meta( $term_id, self::KEY_INTRO, true );
		$ba_desc = get_term_meta( $term_id, self::KEY_BA_DESC, true );

		return array(
			'featured'       => '1' === (string) get_term_meta( $term_id, self::KEY_FEATURED, true ),
			'cover_id'       => self::clean_media( 'image', get_term_meta( $term_id, self::KEY_COVER, true ) ),
			'cover_video_id' => self::clean_media( 'video', get_term_meta( $term_id, self::KEY_VIDEO, true ) ),
			'order'          => self::sanitize_order( get_term_meta( $term_id, self::KEY_ORDER, true ) ),
			'intro'          => is_string( $intro ) ? self::sanitize_intro( $intro ) : '',
			'ba_desc'        => is_string( $ba_desc ) ? self::sanitize_ba_desc( $ba_desc ) : '',
			'ba_before'      => self::clean_positions( get_term_meta( $term_id, self::KEY_BA_PRE, true ) ),
			'ba_after'       => self::clean_positions( get_term_meta( $term_id, self::KEY_BA_POST, true ) ),
		);
	}

	/**
	 * Thẻ HTML được phép trong nội dung giới thiệu: khớp thanh công cụ của trình soạn thảo gọn (đoạn, tiêu đề phụ, đậm, nghiêng,
	 * danh sách, trích dẫn, liên kết). Thẻ khác (ảnh, span có style, bảng...) bị bỏ, chữ bên trong được giữ.
	 *
	 * @return array Bảng thẻ cho wp_kses().
	 */
	public static function intro_allowed_html() {
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
	 * Làm sạch nội dung giới thiệu: chỉ giữ thẻ của ::intro_allowed_html(). Chỉ có khoảng trắng (kể cả &nbsp;) thì trả ''.
	 *
	 * @param mixed $raw Giá trị thô (đã wp_unslash).
	 * @return string
	 */
	public static function sanitize_intro( $raw ) {
		if ( ! is_string( $raw ) ) {
			return '';
		}
		$html = trim( wp_kses( $raw, self::intro_allowed_html() ) );
		$text = trim( str_replace( array( '&nbsp;', "\xC2\xA0" ), ' ', wp_strip_all_tags( $html ) ) );
		return '' === $text ? '' : $html;
	}

	/**
	 * Làm sạch mô tả ngắn hiệu quả liệu trình: văn bản thuần nhiều dòng, tối đa BA_DESC_MAX ký tự.
	 *
	 * @param mixed $raw Giá trị thô (đã wp_unslash).
	 * @return string
	 */
	public static function sanitize_ba_desc( $raw ) {
		if ( ! is_string( $raw ) ) {
			return '';
		}
		$text = trim( sanitize_textarea_field( $raw ) );
		if ( function_exists( 'mb_substr' ) ) {
			$text = mb_substr( $text, 0, self::BA_DESC_MAX );
		}
		return trim( $text );
	}

	/**
	 * Các nhóm dịch vụ theo thứ tự hiển thị: `order` tăng dần (chưa đặt thì đứng cuối), cùng thứ tự thì theo tên.
	 *
	 * Luôn trả WP_Term[] (đè `fields`, `orderby`, `order`). `number` và `offset` được áp dụng sau khi sắp xếp để phân trang
	 * đúng theo thứ tự hiển thị. Các tham số còn lại của get_terms() dùng bình thường (mặc định `hide_empty` là false).
	 *
	 * @param array $args Tham số của get_terms().
	 * @return WP_Term[]
	 */
	public static function ordered_terms( array $args = array() ) {
		$args = wp_parse_args( $args, array( 'hide_empty' => false ) );

		$number = isset( $args['number'] ) ? absint( $args['number'] ) : 0;
		$offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;

		$args['taxonomy'] = self::TAXONOMY;
		$args['fields']   = 'all';
		$args['orderby']  = 'name';
		$args['order']    = 'ASC';
		$args['number']   = 0;
		$args['offset']   = 0;

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		$rows = array();
		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			$order  = self::sanitize_order( get_term_meta( $term->term_id, self::KEY_ORDER, true ) );
			$rows[] = array(
				'term'  => $term,
				'unset' => null === $order ? 1 : 0,
				'order' => null === $order ? 0 : $order,
				'name'  => remove_accents( $term->name ),
			);
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				if ( $a['unset'] !== $b['unset'] ) {
					return $a['unset'] <=> $b['unset'];
				}
				if ( $a['order'] !== $b['order'] ) {
					return $a['order'] <=> $b['order'];
				}
				$by_name = strnatcasecmp( $a['name'], $b['name'] );
				if ( 0 !== $by_name ) {
					return $by_name;
				}
				return $a['term']->term_id <=> $b['term']->term_id;
			}
		);

		$sorted = array();
		foreach ( $rows as $row ) {
			$sorted[] = $row['term'];
		}

		if ( $offset > 0 || $number > 0 ) {
			$sorted = array_slice( $sorted, $offset, $number > 0 ? $number : null );
		}
		return $sorted;
	}

	/**
	 * Làm sạch thứ tự: số nguyên có tối đa 9 chữ số (cho phép dấu +/- và khoảng trắng hai đầu).
	 *
	 * @param mixed $raw Giá trị thô.
	 * @return int|null null nếu không phải số nguyên hợp lệ (kể cả chuỗi rỗng, mảng, số thực, "1e3", "12abc").
	 */
	public static function sanitize_order( $raw ) {
		if ( is_string( $raw ) ) {
			$raw = trim( $raw );
			if ( ! preg_match( '/^[+-]?[0-9]{1,9}$/D', $raw ) ) {
				return null;
			}
			$raw = (int) $raw;
		}
		if ( ! is_int( $raw ) || abs( $raw ) > self::ORDER_LIMIT ) {
			return null;
		}
		return $raw;
	}

	/**
	 * Làm sạch ID media bằng ô chọn dùng chung.
	 *
	 * @param string $type image|video.
	 * @param mixed  $raw  Giá trị thô.
	 * @return int ID hợp lệ hoặc 0.
	 */
	private static function clean_media( $type, $raw ) {
		if ( ! class_exists( 'ISHA_Core_Admin_Media' ) ) {
			return 0;
		}
		$id = ISHA_Core_Admin_Media::sanitize( $type, $raw );
		return is_int( $id ) ? $id : 0;
	}

	/**
	 * Đọc danh sách ảnh trước/sau giữ nguyên vị trí: phần tử nào không còn là ảnh hợp lệ thì thành 0 (không bị loại), tối đa BA_MAX.
	 *
	 * @param mixed $raw Giá trị đã lưu (mảng ID hoặc chuỗi CSV).
	 * @return int[]
	 */
	private static function clean_positions( $raw ) {
		if ( is_string( $raw ) ) {
			// Chuỗi rỗng (term chưa có meta) là danh sách rỗng, không phải một phần tử hỏng.
			$raw = '' === trim( $raw ) ? array() : explode( ',', $raw );
		}
		if ( ! is_array( $raw ) || ! class_exists( 'ISHA_Core_Admin_Media' ) ) {
			return array();
		}
		$out = array();
		foreach ( array_slice( array_values( $raw ), 0, self::BA_MAX ) as $item ) {
			$id    = is_numeric( $item ) ? (int) $item : 0;
			$out[] = ( $id > 0 && ISHA_Core_Admin_Media::is_valid( 'image', $id ) ) ? $id : 0;
		}
		return $out;
	}

	/**
	 * Bỏ các cặp trước/sau có một phía hỏng (ID 0: ảnh đã bị xoá khỏi Thư viện) khỏi CẢ HAI danh sách, bằng cùng một tập vị trí, nên các cặp còn
	 * lại vẫn thẳng hàng. Ảnh dư ở cuối danh sách dài hơn (chưa kịp thêm phía kia) được giữ nguyên.
	 *
	 * @param int[] $before Danh sách ảnh trước (có thể có ID 0).
	 * @param int[] $after  Danh sách ảnh sau (có thể có ID 0).
	 * @return array array( int[] ảnh trước, int[] ảnh sau ) đã bỏ cặp hỏng, đánh số lại từ 0.
	 */
	private static function drop_broken_pairs( array $before, array $after ) {
		$before = array_values( $before );
		$after  = array_values( $after );
		$keep_b = array();
		$keep_a = array();
		$count  = max( count( $before ), count( $after ) );

		for ( $i = 0; $i < $count; $i++ ) {
			$b = array_key_exists( $i, $before ) ? (int) $before[ $i ] : null;
			$a = array_key_exists( $i, $after ) ? (int) $after[ $i ] : null;
			if ( 0 === $b || 0 === $a ) {
				continue;
			}
			if ( null !== $b ) {
				$keep_b[] = $b;
			}
			if ( null !== $a ) {
				$keep_a[] = $a;
			}
		}

		return array( $keep_b, $keep_a );
	}

	/**
	 * Làm sạch danh sách ảnh trước/sau khi LƯU: chỉ giữ ID ảnh hợp lệ, theo thứ tự, tối đa BA_MAX.
	 *
	 * @param mixed $raw Giá trị thô (mảng ID hoặc chuỗi CSV).
	 * @return int[]
	 */
	private static function clean_gallery( $raw ) {
		if ( ! class_exists( 'ISHA_Core_Admin_Media' ) ) {
			return array();
		}
		$ids = ISHA_Core_Admin_Media::sanitize( 'gallery', $raw );
		return is_array( $ids ) ? array_slice( array_values( array_map( 'intval', $ids ) ), 0, self::BA_MAX ) : array();
	}

	/* --- Giao diện --------------------------------------------------------- */

	/**
	 * Nạp ô chọn media, chỉ ở màn danh sách/thêm (edit-tags.php) và màn sửa (term.php) của nhóm dịch vụ.
	 *
	 * @param string $hook_suffix Hook của màn hình admin hiện tại.
	 */
	public static function enqueue( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || self::TAXONOMY !== $screen->taxonomy ) {
			return;
		}
		if ( class_exists( 'ISHA_Core_Admin_Media' ) ) {
			ISHA_Core_Admin_Media::enqueue();
		}

		// Khung nhóm "Hiệu quả liệu trình" và trình soạn thảo nội dung giới thiệu: vài quy tắc nhỏ, màu theo wp-admin.
		$css = '.isha-term-group{max-width:760px;margin:0;padding:16px 20px 4px;border:1px solid #c3c4c7;border-radius:4px;background:#fff}'
			. '.isha-term-group__item{margin:0 0 20px}'
			. '.isha-term-group__item+.isha-term-group__item{padding-top:16px;border-top:1px solid #f0f0f1}'
			. '.form-field .isha-term-group__label,.isha-term-group__label{display:block;margin:0 0 6px;font-weight:600;color:#1d2327}'
			. '.isha-term-group textarea{width:100%;max-width:100%}'
			. '.isha-term-group__lead{margin:0 0 16px;color:#646970}'
			. '.isha-term-field--intro .wp-editor-wrap{max-width:760px}';
		wp_register_style( 'isha-term-fields', false, array(), ISHA_CORE_VERSION );
		wp_enqueue_style( 'isha-term-fields' );
		wp_add_inline_style( 'isha-term-fields', $css );
	}

	/**
	 * Các ô ở màn "Thêm nhóm dịch vụ": mỗi ô là một div.form-field.
	 */
	public static function render_add_fields() {
		self::render_fields( 'add', self::get( 0 ) );
	}

	/**
	 * Các ô ở màn "Sửa nhóm dịch vụ": mỗi ô là một tr.form-field có th (nhãn) và td (ô nhập).
	 *
	 * @param WP_Term $term Term đang sửa.
	 */
	public static function render_edit_fields( $term ) {
		$term_id = $term instanceof WP_Term ? (int) $term->term_id : 0;
		self::render_fields( 'edit', self::get( $term_id ) );
	}

	/**
	 * In các ô (ảnh bìa, video bìa, nội dung giới thiệu, nhóm "Hiệu quả liệu trình" gồm mô tả ngắn + ảnh trước + ảnh sau, thứ tự)
	 * và nonce. Nonce nằm trong ô thứ tự vì ô này luôn được in.
	 *
	 * @param string $mode add|edit.
	 * @param array  $data Kết quả của ::get().
	 */
	private static function render_fields( $mode, array $data ) {
		// Ảnh trước/sau ghép cặp theo vị trí. Ảnh đã bị xoá khỏi Thư viện là chỗ trống (ID 0) mà ô gallery không thể hiện; để nguyên thì ô
		// bị nén và lần lưu kế tiếp làm lệch các cặp phía sau (ảnh trước của người này nằm cạnh ảnh sau của người khác). Nên bỏ cả hai phía
		// của cặp hỏng trước khi dựng ô: người dùng thấy đúng những gì sẽ được lưu.
		$ba_note = in_array( 0, $data['ba_before'], true ) || in_array( 0, $data['ba_after'], true );
		list( $data['ba_before'], $data['ba_after'] ) = self::drop_broken_pairs( $data['ba_before'], $data['ba_after'] );
		$ba_warn = $ba_note ? __( 'Có cặp ảnh đã bị xoá khỏi Thư viện phương tiện nên cặp đó đã được bỏ khỏi cả hai ô; bấm Cập nhật để lưu. Nếu muốn giữ cặp, hãy thêm lại cả ảnh trước lẫn ảnh sau ở đúng vị trí.', 'isha-core' ) . ' ' : '';

		// Dịch vụ tiêu biểu: ô ẩn "0" đứng trước checkbox "1" nên bỏ tick vẫn gửi giá trị (lưu thành bỏ cờ).
		self::open_field( $mode, 'featured', __( 'Dịch vụ tiêu biểu', 'isha-core' ), 'isha-term-featured' );
		echo '<input type="hidden" name="' . esc_attr( self::FIELD_FEATURE ) . '" value="0">';
		echo '<label for="isha-term-featured"><input type="checkbox" name="' . esc_attr( self::FIELD_FEATURE ) . '" id="isha-term-featured" value="1"' . checked( $data['featured'], true, false ) . ' aria-describedby="isha-term-featured-help"> ' . esc_html__( 'Hiện nhóm này ở section "Dịch vụ tiêu biểu" của trang chủ', 'isha-core' ) . '</label>';
		echo '<p class="description" id="isha-term-featured-help">' . esc_html__( 'Các nhóm được tick hiện theo Thứ tự hiển thị bên dưới. Chưa nhóm nào được tick thì section ẩn khỏi trang chủ.', 'isha-core' ) . '</p>';
		self::close_field( $mode );

		if ( class_exists( 'ISHA_Core_Admin_Media' ) ) {
			self::open_field( $mode, 'cover', __( 'Ảnh bìa', 'isha-core' ) );
			ISHA_Core_Admin_Media::render(
				array(
					'name'  => self::FIELD_COVER,
					'type'  => 'image',
					'value' => $data['cover_id'],
					'label' => __( 'Ảnh bìa', 'isha-core' ),
					'help'  => __( 'Ảnh dọc tỉ lệ 4:5, cạnh dài từ 1500px. Hiển thị ở section Dịch vụ tiêu biểu của trang chủ (khi nhóm được tick) và ở các trang dịch vụ.', 'isha-core' ),
					'id'    => 'isha-term-cover',
				)
			);
			self::close_field( $mode );

			self::open_field( $mode, 'video', __( 'Video bìa (tuỳ chọn)', 'isha-core' ) );
			ISHA_Core_Admin_Media::render(
				array(
					'name'  => self::FIELD_VIDEO,
					'type'  => 'video',
					'value' => $data['cover_video_id'],
					'label' => __( 'Video bìa (tuỳ chọn)', 'isha-core' ),
					'help'  => __( 'Video lặp ngắn, không tiếng, tối đa 3 MB. Để trống thì dùng ảnh bìa.', 'isha-core' ),
					'id'    => 'isha-term-cover-video',
				)
			);
			self::close_field( $mode );
		}

		// Nội dung giới thiệu: chỉ ở màn "Sửa" (màn "Thêm" gửi bằng AJAX, TinyMCE không kịp ghi nội dung về textarea).
		if ( 'edit' === $mode ) {
			self::open_field( $mode, 'intro', __( 'Nội dung giới thiệu', 'isha-core' ), self::EDITOR_ID );
			wp_editor(
				$data['intro'],
				self::EDITOR_ID,
				array(
					'textarea_name'    => self::FIELD_INTRO,
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
			echo '<p class="description">' . esc_html__( 'Hiển thị ở section "Giới thiệu dịch vụ" ngay dưới banner của trang nhóm. Đoạn đầu tiên in cỡ lớn, các đoạn sau cỡ chữ thân. Để trống thì không hiện section.', 'isha-core' ) . '</p>';
			self::close_field( $mode );
		}

		if ( class_exists( 'ISHA_Core_Admin_Media' ) ) {
			// Nhóm "Hiệu quả liệu trình": mô tả ngắn, ảnh trước, ảnh sau. Tên nhóm (th ở màn Sửa, nhãn ở màn Thêm) là tên truy cập của khung.
			self::open_group( $mode, 'ba', __( 'Hiệu quả liệu trình', 'isha-core' ) );
			echo '<p class="isha-term-group__lead">' . esc_html__( 'Tablet và điện thoại: section "Hiệu quả liệu trình" gồm tiêu đề cố định, mô tả ngắn, khung kéo so sánh ảnh trước/sau và dải ảnh ghép của các cặp. Máy tính: section này ẩn, các cặp ảnh hiện trong khung so sánh ở cột trái của "Giới thiệu dịch vụ" (có nút Trước/Sau để đổi cặp). Không có cặp ảnh hợp lệ nào thì không hiện khung.', 'isha-core' ) . '</p>';

			echo '<div class="isha-term-group__item">';
			echo '<label class="isha-term-group__label" for="isha-term-ba-desc">' . esc_html__( 'Mô tả ngắn hiệu quả liệu trình', 'isha-core' ) . '</label>';
			echo '<textarea name="' . esc_attr( self::FIELD_BA_DESC ) . '" id="isha-term-ba-desc" rows="3" maxlength="' . esc_attr( (string) self::BA_DESC_MAX ) . '" aria-describedby="isha-term-ba-desc-help">' . esc_textarea( $data['ba_desc'] ) . '</textarea>';
			echo '<p class="description" id="isha-term-ba-desc-help">' . esc_html( sprintf( /* translators: %d: số ký tự tối đa */ __( 'Một đến hai câu, hiện dưới tiêu đề "Hiệu quả liệu trình". Lưu ý: chỉ hiển thị trên tablet và điện thoại (trên máy tính, section này được thay bằng khung so sánh ở cột trái của "Giới thiệu dịch vụ"; nếu nhóm chưa có nội dung giới thiệu thì section vẫn hiện cả trên máy tính). Tối đa %d ký tự, không định dạng.', 'isha-core' ), self::BA_DESC_MAX ) ) . '</p>';
			echo '</div>';

			echo '<div class="isha-term-group__item">';
			echo '<p class="isha-term-group__label" id="isha-term-ba-before-label">' . esc_html__( 'Ảnh trước điều trị', 'isha-core' ) . '</p>';
			ISHA_Core_Admin_Media::render(
				array(
					'name'  => self::FIELD_BA_PRE,
					'type'  => 'gallery',
					'value' => $data['ba_before'],
					'max'   => self::BA_MAX,
					'label' => __( 'Ảnh trước điều trị', 'isha-core' ),
					/* translators: %d: số cặp ảnh tối đa */
					'help'  => $ba_warn . sprintf( __( 'Tối đa %d ảnh, cùng tỉ lệ dọc 4:5 (tối thiểu 1200×1500), cùng góc chụp và ánh sáng với ảnh sau; đặt vùng điều trị ở giữa ảnh (khung so sánh trên máy tính là 3:2 nên phần trên và dưới ảnh bị cắt bớt). Ảnh thứ n ở đây ghép cặp với ảnh thứ n ở ô "Ảnh sau điều trị". Chỉ dùng ảnh khách đã đồng ý cho công khai. Quy chuẩn xử lý ảnh: nếu cần che mắt, dùng MỘT dải kính mờ (frosted glass) hoặc Liner pattern của ISHA che ngang vùng mắt, cùng vị trí, cùng độ rộng và cùng độ mờ trên CẢ HAI ảnh trước và sau; không dùng dải màu bệt, không để một ảnh che bằng dải màu còn ảnh kia đeo kính bảo hộ. Hai ảnh nên cùng tông sáng, cùng cân bằng trắng.', 'isha-core' ), self::BA_MAX ),
					'id'    => 'isha-term-ba-before',
				)
			);
			echo '</div>';

			echo '<div class="isha-term-group__item">';
			echo '<p class="isha-term-group__label" id="isha-term-ba-after-label">' . esc_html__( 'Ảnh sau điều trị', 'isha-core' ) . '</p>';
			ISHA_Core_Admin_Media::render(
				array(
					'name'  => self::FIELD_BA_POST,
					'type'  => 'gallery',
					'value' => $data['ba_after'],
					'max'   => self::BA_MAX,
					'label' => __( 'Ảnh sau điều trị', 'isha-core' ),
					'help'  => __( 'Cùng số ảnh và cùng thứ tự với ô "Ảnh trước điều trị". Cặp nào thiếu một ảnh thì không hiển thị.', 'isha-core' ),
					'id'    => 'isha-term-ba-after',
				)
			);
			echo '</div>';

			self::close_group( $mode );
		}

		self::open_field( $mode, 'order', __( 'Thứ tự hiển thị', 'isha-core' ), 'isha-term-order' );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD, false );
		$order = null === $data['order'] ? '' : (string) $data['order'];
		echo '<input type="number" class="small-text" name="' . esc_attr( self::FIELD_ORDER ) . '" id="isha-term-order" value="' . esc_attr( $order ) . '" step="1" min="-' . esc_attr( (string) self::ORDER_LIMIT ) . '" max="' . esc_attr( (string) self::ORDER_LIMIT ) . '" inputmode="numeric" aria-describedby="isha-term-order-help">';
		echo '<p class="description" id="isha-term-order-help">' . esc_html__( 'Số nhỏ đứng trước (ví dụ 10, 20, 30). Để trống thì nhóm đứng cuối.', 'isha-core' ) . '</p>';
		self::close_field( $mode );
	}

	/**
	 * Mở một ô: div.form-field ở màn thêm, tr.form-field > th + td ở màn sửa.
	 *
	 * @param string $mode     add|edit.
	 * @param string $modifier Hậu tố class (cover|video|ba-before|ba-after|order).
	 * @param string $label    Nhãn hiển thị.
	 * @param string $for      id của input mà nhãn trỏ tới (bỏ trống cho ô chọn media: nhãn của ô nằm ở aria-label).
	 */
	private static function open_field( $mode, $modifier, $label, $for = '' ) {
		$for_attr = '' !== $for ? ' for="' . esc_attr( $for ) . '"' : '';
		$label    = '<label' . $for_attr . '>' . esc_html( $label ) . '</label>';
		$class    = 'form-field isha-term-field isha-term-field--' . $modifier;

		if ( 'edit' === $mode ) {
			echo '<tr class="' . esc_attr( $class ) . '"><th scope="row">' . $label . '</th><td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $label đã escape ở trên.
			return;
		}
		echo '<div class="' . esc_attr( $class ) . '">' . $label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $label đã escape ở trên.
	}

	/**
	 * Đóng ô đã mở bằng open_field().
	 *
	 * @param string $mode add|edit.
	 */
	private static function close_field( $mode ) {
		echo 'edit' === $mode ? '</td></tr>' : '</div>';
	}

	/**
	 * Mở một nhóm ô: như open_field() nhưng tên nhóm không trỏ vào một input (mỗi ô con có nhãn riêng); phần thân là
	 * div.isha-term-group role="group" lấy tên nhóm làm tên truy cập.
	 *
	 * @param string $mode     add|edit.
	 * @param string $modifier Hậu tố class và id.
	 * @param string $label    Tên nhóm.
	 */
	private static function open_group( $mode, $modifier, $label ) {
		$id    = 'isha-term-' . $modifier . '-group';
		$class = 'form-field isha-term-field isha-term-field--' . $modifier;
		$title = '<span id="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</span>';
		$box   = '<div class="isha-term-group" role="group" aria-labelledby="' . esc_attr( $id ) . '">';

		if ( 'edit' === $mode ) {
			echo '<tr class="' . esc_attr( $class ) . '"><th scope="row">' . $title . '</th><td>' . $box; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $title, $box đã escape ở trên.
			return;
		}
		echo '<div class="' . esc_attr( $class ) . '"><label>' . $title . '</label>' . $box; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $title, $box đã escape ở trên.
	}

	/**
	 * Đóng nhóm đã mở bằng open_group().
	 *
	 * @param string $mode add|edit.
	 */
	private static function close_group( $mode ) {
		echo '</div>';
		self::close_field( $mode );
	}

	/* --- Lưu --------------------------------------------------------------- */

	/**
	 * Lưu term meta ở created_/edited_isha_service_cat.
	 *
	 * Chỉ lưu khi có nonce hợp lệ và người dùng có quyền sửa term, nên việc tạo term bằng code (seed), Quick Edit hay REST
	 * không đụng tới term meta. Chỉ cập nhật ô có trong dữ liệu gửi lên. Ảnh/video sai (không phải tệp hợp lệ) hoặc rỗng thì
	 * xoá meta; thứ tự rỗng thì xoá meta (đứng cuối), thứ tự không hợp lệ thì bỏ qua và giữ giá trị cũ.
	 *
	 * @param int $term_id ID term vừa tạo hoặc sửa.
	 */
	public static function save( $term_id ) {
		$term_id = is_numeric( $term_id ) ? (int) $term_id : 0;
		if ( $term_id < 1 ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		$term = get_term( $term_id, self::TAXONOMY );
		if ( ! ( $term instanceof WP_Term ) || ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		// Dịch vụ tiêu biểu: tick ('1') thì lưu cờ, bỏ tick thì xoá meta.
		if ( isset( $_POST[ self::FIELD_FEATURE ] ) ) {
			if ( '1' === sanitize_text_field( wp_unslash( $_POST[ self::FIELD_FEATURE ] ) ) ) {
				update_term_meta( $term_id, self::KEY_FEATURED, '1' );
			} else {
				delete_term_meta( $term_id, self::KEY_FEATURED );
			}
		}

		if ( class_exists( 'ISHA_Core_Admin_Media' ) ) {
			// Ảnh/video được làm sạch bằng ISHA_Core_Admin_Media::sanitize() (chỉ nhận ID tệp đính kèm hợp lệ).
			if ( isset( $_POST[ self::FIELD_COVER ] ) ) {
				self::store( $term_id, self::KEY_COVER, ISHA_Core_Admin_Media::sanitize( 'image', wp_unslash( $_POST[ self::FIELD_COVER ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
			if ( isset( $_POST[ self::FIELD_VIDEO ] ) ) {
				self::store( $term_id, self::KEY_VIDEO, ISHA_Core_Admin_Media::sanitize( 'video', wp_unslash( $_POST[ self::FIELD_VIDEO ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
			// Ảnh trước/sau: danh sách ID ảnh hợp lệ, rỗng thì xoá meta.
			if ( isset( $_POST[ self::FIELD_BA_PRE ] ) ) {
				self::store_list( $term_id, self::KEY_BA_PRE, wp_unslash( $_POST[ self::FIELD_BA_PRE ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
			if ( isset( $_POST[ self::FIELD_BA_POST ] ) ) {
				self::store_list( $term_id, self::KEY_BA_POST, wp_unslash( $_POST[ self::FIELD_BA_POST ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
		}

		// Nội dung giới thiệu (HTML gọn) và mô tả ngắn hiệu quả (văn bản thuần): rỗng thì xoá meta.
		if ( isset( $_POST[ self::FIELD_INTRO ] ) ) {
			self::store_text( $term_id, self::KEY_INTRO, self::sanitize_intro( wp_unslash( $_POST[ self::FIELD_INTRO ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- lọc bằng sanitize_intro() (wp_kses).
		}
		if ( isset( $_POST[ self::FIELD_BA_DESC ] ) ) {
			self::store_text( $term_id, self::KEY_BA_DESC, self::sanitize_ba_desc( wp_unslash( $_POST[ self::FIELD_BA_DESC ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- lọc bằng sanitize_ba_desc().
		}

		if ( isset( $_POST[ self::FIELD_ORDER ] ) ) {
			$raw = wp_unslash( $_POST[ self::FIELD_ORDER ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Chỉ nhận chuỗi số nguyên, kiểm bằng sanitize_order().
			if ( is_string( $raw ) && '' === trim( $raw ) ) {
				delete_term_meta( $term_id, self::KEY_ORDER );
			} else {
				$order = self::sanitize_order( $raw );
				if ( null !== $order ) {
					update_term_meta( $term_id, self::KEY_ORDER, $order );
				}
			}
		}
	}

	/**
	 * Ghi một term meta số: giá trị 0 hoặc rỗng thì xoá meta.
	 *
	 * @param int    $term_id ID term.
	 * @param string $key     Khoá meta.
	 * @param int    $value   Giá trị đã làm sạch.
	 */
	private static function store( $term_id, $key, $value ) {
		if ( $value ) {
			update_term_meta( $term_id, $key, (int) $value );
		} else {
			delete_term_meta( $term_id, $key );
		}
	}

	/**
	 * Ghi một term meta chuỗi đã làm sạch: chuỗi rỗng thì xoá meta. update_term_meta() tự wp_unslash() giá trị nên phải wp_slash()
	 * trước, nếu không dấu gạch chéo ngược trong nội dung bị mất.
	 *
	 * @param int    $term_id ID term.
	 * @param string $key     Khoá meta.
	 * @param string $value   Giá trị đã làm sạch.
	 */
	private static function store_text( $term_id, $key, $value ) {
		if ( is_string( $value ) && '' !== $value ) {
			update_term_meta( $term_id, $key, wp_slash( $value ) );
		} else {
			delete_term_meta( $term_id, $key );
		}
	}

	/**
	 * Ghi một term meta dạng danh sách ID ảnh: làm sạch, cắt tối đa BA_MAX; danh sách rỗng thì xoá meta.
	 *
	 * @param int    $term_id ID term.
	 * @param string $key     Khoá meta.
	 * @param mixed  $raw     Giá trị thô gửi lên.
	 */
	private static function store_list( $term_id, $key, $raw ) {
		$ids = self::clean_gallery( $raw );
		if ( $ids ) {
			update_term_meta( $term_id, $key, $ids );
		} else {
			delete_term_meta( $term_id, $key );
		}
	}

	/* --- Cột của danh sách ------------------------------------------------- */

	/**
	 * Thêm cột ảnh bìa (sau ô chọn), thứ tự và "Tiêu biểu" (sau slug) vào danh sách nhóm dịch vụ.
	 *
	 * @param array $columns Các cột hiện có.
	 * @return array
	 */
	public static function columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			return $columns;
		}

		$cover    = __( 'Ảnh bìa', 'isha-core' );
		$order    = __( 'Thứ tự', 'isha-core' );
		$featured = __( 'Tiêu biểu', 'isha-core' );

		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'cb' === $key ) {
				$out['isha_cover'] = $cover;
			}
			if ( 'slug' === $key ) {
				$out['isha_order']    = $order;
				$out['isha_featured'] = $featured;
			}
		}
		if ( ! isset( $out['isha_cover'] ) ) {
			$out = array( 'isha_cover' => $cover ) + $out;
		}
		if ( ! isset( $out['isha_order'] ) ) {
			$out['isha_order'] = $order;
		}
		if ( ! isset( $out['isha_featured'] ) ) {
			$out['isha_featured'] = $featured;
		}
		return $out;
	}

	/**
	 * Nội dung hai cột thêm vào (HTML đã escape). Cột khác giữ nguyên.
	 *
	 * @param string $content  Nội dung hiện có của ô.
	 * @param string $column   Tên cột.
	 * @param int    $term_id  ID term.
	 * @return string
	 */
	public static function column_content( $content, $column, $term_id ) {
		$content = is_string( $content ) ? $content : '';

		if ( 'isha_cover' === $column ) {
			$data = self::get( $term_id );
			if ( $data['cover_id'] ) {
				$image = wp_get_attachment_image(
					$data['cover_id'],
					'thumbnail',
					false,
					array(
						'alt'     => '',
						'loading' => 'lazy',
						'style'   => 'width:40px;height:50px;object-fit:cover;',
					)
				);
				return $content . $image;
			}
			return $content . '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Chưa có ảnh bìa', 'isha-core' ) . '</span>';
		}

		if ( 'isha_order' === $column ) {
			$data = self::get( $term_id );
			if ( null !== $data['order'] ) {
				return $content . esc_html( (string) $data['order'] );
			}
			return $content . '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Chưa đặt thứ tự', 'isha-core' ) . '</span>';
		}

		if ( 'isha_featured' === $column ) {
			$data = self::get( $term_id );
			if ( $data['featured'] ) {
				return $content . esc_html__( 'Có', 'isha-core' );
			}
			return $content . '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Không', 'isha-core' ) . '</span>';
		}

		return $content;
	}
}
