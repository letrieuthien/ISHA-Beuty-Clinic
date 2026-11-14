<?php
/**
 * Term meta của nhóm dịch vụ (taxonomy isha_service_cat): ảnh bìa, video bìa, ảnh trước/sau điều trị và thứ tự hiển thị.
 *
 * Khoá meta (đều bắt đầu bằng gạch dưới nên không lộ ra ô "Custom Fields"):
 *   _isha_cover_id       – ảnh bìa dọc 4:5 (ID tệp đính kèm)
 *   _isha_cover_video_id – video bìa tuỳ chọn, vòng lặp ngắn, không tiếng (ID tệp đính kèm)
 *   _isha_ba_before      – ảnh TRƯỚC điều trị (mảng ID có thứ tự, tối đa BA_MAX)
 *   _isha_ba_after       – ảnh SAU điều trị (mảng ID có thứ tự, tối đa BA_MAX); ảnh thứ n của hai danh sách là một cặp
 *   _isha_order          – thứ tự hiển thị (số nguyên, nhỏ đứng trước; chưa đặt thì đứng cuối)
 * Mô tả (description) của term chính là tagline, nên không có ô riêng và không bị đổi ở đây.
 *
 * Giao diện: ô chọn media dùng chung ISHA_Core_Admin_Media ở màn "Thêm" (div.form-field) và "Sửa" (tr.form-field) nhóm
 * dịch vụ. Lưu ở created_/edited_isha_service_cat, có nonce isha_save_term / isha_term_nonce và kiểm tra quyền edit_term.
 * Giá trị nhận về được làm sạch bằng ISHA_Core_Admin_Media::sanitize() (ảnh, video) và ::sanitize_order() (thứ tự).
 * Đọc dữ liệu: ::get() và ::ordered_terms(). Màn danh sách có thêm cột ảnh bìa nhỏ và thứ tự.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Term_Fields {

	const TAXONOMY      = 'isha_service_cat';
	const KEY_COVER     = '_isha_cover_id';
	const KEY_VIDEO     = '_isha_cover_video_id';
	const KEY_ORDER     = '_isha_order';
	const KEY_BA_PRE    = '_isha_ba_before';
	const KEY_BA_POST   = '_isha_ba_after';
	const NONCE_ACTION  = 'isha_save_term';
	const NONCE_FIELD   = 'isha_term_nonce';
	const FIELD_COVER   = 'isha_term_cover_id';
	const FIELD_VIDEO   = 'isha_term_cover_video_id';
	const FIELD_ORDER   = 'isha_term_order';
	const FIELD_BA_PRE  = 'isha_term_ba_before';
	const FIELD_BA_POST = 'isha_term_ba_after';

	/**
	 * Giá trị tuyệt đối lớn nhất của thứ tự (9 chữ số), để số không tràn khi lưu và sắp xếp.
	 */
	const ORDER_LIMIT = 999999999;

	/**
	 * Số cặp ảnh trước/sau tối đa của một nhóm dịch vụ.
	 */
	const BA_MAX = 6;

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
	 * @return array{cover_id:int,cover_video_id:int,order:int|null,ba_before:int[],ba_after:int[]} `order` là null khi chưa đặt;
	 *         `ba_before`/`ba_after` là mảng ID ảnh (tối đa BA_MAX), ảnh thứ n của hai mảng là một cặp. ID hỏng (ảnh đã bị xoá khỏi thư viện)
	 *         thành 0 nhưng GIỮ VỊ TRÍ, để các cặp phía sau không bị lệch (ghép nhầm ảnh trước của người này với ảnh sau của người khác).
	 */
	public static function get( $term_id ) {
		$term_id = is_numeric( $term_id ) ? (int) $term_id : 0;
		if ( $term_id < 1 ) {
			return array(
				'cover_id'       => 0,
				'cover_video_id' => 0,
				'order'          => null,
				'ba_before'      => array(),
				'ba_after'       => array(),
			);
		}

		return array(
			'cover_id'       => self::clean_media( 'image', get_term_meta( $term_id, self::KEY_COVER, true ) ),
			'cover_video_id' => self::clean_media( 'video', get_term_meta( $term_id, self::KEY_VIDEO, true ) ),
			'order'          => self::sanitize_order( get_term_meta( $term_id, self::KEY_ORDER, true ) ),
			'ba_before'      => self::clean_positions( get_term_meta( $term_id, self::KEY_BA_PRE, true ) ),
			'ba_after'       => self::clean_positions( get_term_meta( $term_id, self::KEY_BA_POST, true ) ),
		);
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
	 * In các ô (ảnh bìa, video bìa, ảnh trước, ảnh sau, thứ tự) và nonce. Nonce nằm trong ô thứ tự vì ô này luôn được in.
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

		if ( class_exists( 'ISHA_Core_Admin_Media' ) ) {
			self::open_field( $mode, 'cover', __( 'Ảnh bìa', 'isha-core' ) );
			ISHA_Core_Admin_Media::render(
				array(
					'name'  => self::FIELD_COVER,
					'type'  => 'image',
					'value' => $data['cover_id'],
					'label' => __( 'Ảnh bìa', 'isha-core' ),
					'help'  => __( 'Ảnh dọc tỉ lệ 4:5, cạnh dài từ 1500px. Hiển thị ở section Dịch vụ của trang chủ.', 'isha-core' ),
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

			self::open_field( $mode, 'ba-before', __( 'Ảnh trước điều trị', 'isha-core' ) );
			ISHA_Core_Admin_Media::render(
				array(
					'name'  => self::FIELD_BA_PRE,
					'type'  => 'gallery',
					'value' => $data['ba_before'],
					'max'   => self::BA_MAX,
					'label' => __( 'Ảnh trước điều trị', 'isha-core' ),
					'help'  => $ba_warn . __( 'Tối đa 6 ảnh, cùng tỉ lệ dọc 4:5 (tối thiểu 1200×1500), cùng góc chụp và ánh sáng với ảnh sau. Ảnh thứ n ở đây ghép cặp với ảnh thứ n ở ô "Ảnh sau điều trị". Chú thích của ảnh (trong Thư viện media) hiện dưới cặp ảnh. Chỉ dùng ảnh khách đã đồng ý cho công khai.', 'isha-core' ),
					'id'    => 'isha-term-ba-before',
				)
			);
			self::close_field( $mode );

			self::open_field( $mode, 'ba-after', __( 'Ảnh sau điều trị', 'isha-core' ) );
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
			self::close_field( $mode );
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
	 * Thêm cột ảnh bìa (sau ô chọn) và thứ tự (sau slug) vào danh sách nhóm dịch vụ.
	 *
	 * @param array $columns Các cột hiện có.
	 * @return array
	 */
	public static function columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			return $columns;
		}

		$cover = __( 'Ảnh bìa', 'isha-core' );
		$order = __( 'Thứ tự', 'isha-core' );

		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'cb' === $key ) {
				$out['isha_cover'] = $cover;
			}
			if ( 'slug' === $key ) {
				$out['isha_order'] = $order;
			}
		}
		if ( ! isset( $out['isha_cover'] ) ) {
			$out = array( 'isha_cover' => $cover ) + $out;
		}
		if ( ! isset( $out['isha_order'] ) ) {
			$out['isha_order'] = $order;
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

		return $content;
	}
}
