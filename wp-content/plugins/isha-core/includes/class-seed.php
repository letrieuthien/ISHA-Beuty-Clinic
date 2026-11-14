<?php
/**
 * Dữ liệu dịch vụ khởi tạo theo leaflet: 6 nhóm dịch vụ (term isha_service_cat), 17 liệu trình (bài isha_service ở trạng
 * thái nháp) và 3 loại sự kiện (term isha_event_type). Nội dung lấy từ Phụ lục A của docs/prompt-trang-chu.md.
 *
 * Cách chạy:
 *   - Quản trị viên: nút "Tạo dữ liệu dịch vụ từ leaflet" trong thông báo admin (chỉ hiện khi chưa có nhóm dịch vụ nào).
 *   - WP-CLI: wp isha seed services [--status=<draft|publish>] [--keep-legacy] [--dry-run]
 *   - Code: ISHA_Core_Seed::run( array( 'status' => 'draft', 'trash_legacy' => true, 'dry_run' => false ) ).
 *
 * Chạy lại nhiều lần an toàn: mỗi term/bài tạo ra mang meta _isha_seed_key. Nếu đã có (ở mọi trạng thái, kể cả thùng rác)
 * thì không tạo trùng và không ghi đè dữ liệu đã sửa. Term trùng slug (hoặc trùng tên) mà chưa có mã được nhận làm term đã
 * có: chỉ gắn mã, giữ nguyên mô tả và thứ tự. Các bài isha_service cũ có tên trùng một trong 6 nhóm (không phân biệt hoa
 * thường, bỏ dấu cách thừa, "Mĩ" = "Mỹ", và lỗi chính tả "phẩu" = "phẫu" của bài cũ "PHẨU THUẬT THẨM MỸ"), chưa gán term
 * nào, đang ở trạng thái publish/draft thì chuyển vào thùng rác bằng wp_trash_post() (khôi phục được, không xoá hẳn).
 * Chế độ chạy thử (dry_run) không ghi gì cả.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Seed {

	const POST_TYPE    = 'isha_service';
	const TAXONOMY     = 'isha_service_cat';
	const EVENT_TAX    = 'isha_event_type';
	const KEY_META     = '_isha_seed_key';
	const ORDER_META   = '_isha_order';
	const LEGACY_META  = '_isha_seed_legacy';
	const ACTION       = 'isha_seed_services';
	const NONCE_ACTION = 'isha_seed_services';
	const NONCE_FIELD  = 'isha_seed_nonce';
	const RESULT_KEY   = 'isha_seed_result_';
	const RESULT_TTL   = 300;

	/**
	 * Trạng thái được phép cho liệu trình tạo ra.
	 */
	const STATUSES = array( 'draft', 'publish', 'pending', 'private' );

	/**
	 * Từ viết sai chính tả đã biết (chữ thường) và dạng chuẩn, dùng khi so tên bài cũ với tên nhóm.
	 */
	const SPELLING = array(
		'mĩ'   => 'mỹ',
		'phẩu' => 'phẫu',
	);

	/**
	 * Đăng ký thông báo admin, bộ xử lý nút bấm và (khi chạy dưới WP-CLI) lệnh `wp isha seed`.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'render_notice' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_post' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'ISHA_Core_Seed_Command', false ) ) {
			try {
				// Lệnh gốc `wp isha` (chỉ gom các lệnh con) phải có trước khi đăng ký `wp isha seed`.
				if ( class_exists( 'ISHA_Core_CLI_Namespace', false ) ) {
					WP_CLI::add_command( 'isha', 'ISHA_Core_CLI_Namespace' );
				}
				WP_CLI::add_command( 'isha seed', 'ISHA_Core_Seed_Command' );
			} catch ( Exception $e ) {
				WP_CLI::warning( 'Không đăng ký được lệnh wp isha seed: ' . $e->getMessage() );
			}
		}
	}

	/* --- Dữ liệu ----------------------------------------------------------- */

	/**
	 * Dữ liệu seed theo Phụ lục A: 6 nhóm (tên, slug không dấu, tagline = mô tả term, thứ tự 10..60), 17 liệu trình
	 * (menu_order toàn cục theo nhóm: nhóm n, liệu trình thứ m có n*100 + m*10, ví dụ 110, 120, 130 | 210, 220) và 3 loại sự kiện.
	 * Mỗi mục có `key` là mã đánh dấu _isha_seed_key.
	 *
	 * @return array{groups:array<int,array<string,mixed>>,event_types:array<int,array<string,string>>}
	 */
	public static function data() {
		$groups = array(
			array(
				'name'    => 'Chăm sóc da chuyên sâu',
				'tagline' => 'Chăm sóc nền da – duy trì vẻ tươi khỏe',
				'items'   => array( 'Điều trị mụn chuẩn y khoa', 'Chăm sóc da chuẩn Hàn', 'Chăm sóc da toàn diện chuẩn ISHA' ),
			),
			array(
				'name'    => 'Peel và tái tạo bề mặt da',
				'tagline' => 'Cải thiện bề mặt – tái tạo làn da mịn màng và trắng sáng',
				'items'   => array( 'Peel', 'Mesopeel' ),
			),
			array(
				'name'    => 'Cấp ẩm và cải thiện chất lượng da',
				'tagline' => 'Chăm sóc độ ẩm – trẻ hóa làn da – căng mướt sáng mịn',
				'items'   => array( 'Mesotherapy', 'Skin Booster', 'BAP' ),
			),
			array(
				'name'    => 'Thẩm mỹ công nghệ cao',
				'tagline' => 'Chăm sóc sắc da – săn chắc đường nét',
				'items'   => array( 'Laser', 'HIFU', 'RF' ),
			),
			array(
				'name'    => 'Nghệ thuật tạo hình gương mặt',
				'tagline' => 'Tinh chỉnh đường nét – chạm đến vẻ đẹp hài hòa',
				'items'   => array( 'Filler', 'Botox', 'ISHA Signature Harmony', 'Nâng cơ chuyên sâu' ),
			),
			array(
				'name'    => 'Phẫu thuật thẩm mỹ',
				'tagline' => 'Cân đối tỉ lệ – tạo hình tự nhiên – tôn vinh nét đẹp độc bản',
				'items'   => array( 'Thẩm mỹ mắt', 'Thẩm mỹ mũi' ),
			),
		);

		$out = array(
			'groups'      => array(),
			'event_types' => array(),
		);

		foreach ( $groups as $index => $group ) {
			$slug  = sanitize_title( $group['name'] );
			$items = array();
			foreach ( $group['items'] as $position => $title ) {
				$items[] = array(
					'key'        => 'service:' . sanitize_title( $title ),
					'title'      => $title,
					// Thứ tự TOÀN CỤC theo nhóm (110, 120, 130 | 210, 220 | ...): trang Dịch vụ cũ và ô chọn dịch vụ ở form đặt lịch liệt kê phẳng theo menu_order,
					// nên nếu mỗi nhóm đánh lại từ 10 thì các nhóm bị trộn xen kẽ khi xuất bản. Trong một nhóm thứ tự vẫn tăng dần.
					'menu_order' => ( $index + 1 ) * 100 + ( $position + 1 ) * 10,
				);
			}
			$out['groups'][] = array(
				'key'     => 'cat:' . $slug,
				'name'    => $group['name'],
				'slug'    => $slug,
				'tagline' => $group['tagline'],
				'order'   => ( $index + 1 ) * 10,
				'items'   => $items,
			);
		}

		foreach ( array( 'Workshop', 'Design Talk', 'Charity' ) as $name ) {
			$slug                 = sanitize_title( $name );
			$out['event_types'][] = array(
				'key'  => 'event_type:' . $slug,
				'name' => $name,
				'slug' => $slug,
			);
		}

		return $out;
	}

	/* --- Chạy seed --------------------------------------------------------- */

	/**
	 * Tạo nhóm dịch vụ, liệu trình và loại sự kiện (idempotent), rồi chuyển các bài dịch vụ cũ vào thùng rác.
	 *
	 * @param array $opts {
	 *     Tuỳ chọn.
	 *
	 *     @type string $status       Trạng thái của liệu trình: draft (mặc định), publish, pending hoặc private. Giá trị khác dùng draft.
	 *     @type bool   $trash_legacy Chuyển bài isha_service cũ trùng tên nhóm vào thùng rác (mặc định true).
	 *     @type bool   $dry_run      Chỉ tính toán, KHÔNG ghi gì (mặc định false). Các số đếm khi đó là số sẽ tạo.
	 * }
	 * @return array {
	 *     Tóm tắt.
	 *
	 *     @type int      $terms_created        Số nhóm dịch vụ tạo mới.
	 *     @type int      $terms_existing       Số nhóm đã có.
	 *     @type int      $posts_created        Số liệu trình tạo mới.
	 *     @type int      $posts_existing       Số liệu trình đã có (kể cả đang ở thùng rác).
	 *     @type int      $event_types_created  Số loại sự kiện tạo mới.
	 *     @type int      $event_types_existing Số loại sự kiện đã có.
	 *     @type int[]    $legacy_trashed       ID các bài cũ đã chuyển vào thùng rác (khi dry_run: sẽ chuyển).
	 *     @type bool     $dry_run              Có phải chạy thử không.
	 *     @type string   $status               Trạng thái đã dùng cho liệu trình.
	 *     @type string[] $errors               Các lỗi (rỗng nếu mọi việc xong).
	 *     @type string[] $log                  Các dòng nhật ký tiếng Việt, theo thứ tự thực hiện.
	 * }
	 */
	public static function run( array $opts = array() ) {
		$opts = array_merge(
			array(
				'status'       => 'draft',
				'trash_legacy' => true,
				'dry_run'      => false,
			),
			$opts
		);

		$dry = self::to_bool( $opts['dry_run'] );
		if ( $dry ) {
			return self::execute( $opts );
		}

		// Hai lần chạy cùng lúc (bấm đúp nút, hai tab) sẽ cùng thấy "chưa có" và tạo trùng liệu trình: chỉ cho một lần chạy.
		if ( ! self::acquire_lock() ) {
			$s = self::blank_summary( false );
			self::fail( $s, __( 'Đang có một lần tạo dữ liệu khác chạy cùng lúc. Hãy đợi vài giây rồi mở lại trang để xem kết quả.', 'isha-core' ) );
			return $s;
		}

		try {
			return self::execute( $opts );
		} finally {
			self::release_lock();
		}
	}

	/**
	 * Khoá mức cơ sở dữ liệu (MySQL GET_LOCK) dành cho một lần seed thật.
	 *
	 * @return bool true nếu giữ được khoá, hoặc nếu máy chủ không hỗ trợ GET_LOCK (khi đó không chặn).
	 */
	private static function acquire_lock() {
		global $wpdb;
		$got = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK( %s, 0 )', self::lock_name() ) );
		if ( null === $got ) {
			return true;
		}
		return 1 === (int) $got;
	}

	/**
	 * Nhả khoá của lần seed thật.
	 */
	private static function release_lock() {
		global $wpdb;
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK( %s )', self::lock_name() ) );
	}

	/**
	 * Tên khoá (tối đa 64 ký tự), riêng cho từng cơ sở dữ liệu và tiền tố bảng.
	 *
	 * @return string
	 */
	private static function lock_name() {
		global $wpdb;
		return substr( 'isha_seed_' . DB_NAME . '_' . $wpdb->prefix, 0, 64 );
	}

	/**
	 * Bản tóm tắt rỗng (đủ các khoá của run()).
	 *
	 * @param bool $dry Chạy thử.
	 * @return array
	 */
	private static function blank_summary( $dry ) {
		return array(
			'terms_created'        => 0,
			'terms_existing'       => 0,
			'posts_created'        => 0,
			'posts_existing'       => 0,
			'event_types_created'  => 0,
			'event_types_existing' => 0,
			'legacy_trashed'       => array(),
			'dry_run'              => (bool) $dry,
			'status'               => 'draft',
			'errors'               => array(),
			'log'                  => array(),
		);
	}

	/**
	 * Phần việc thật của run() (đã được bọc khoá khi không phải chạy thử).
	 *
	 * @param array $opts Tuỳ chọn đã gộp mặc định.
	 * @return array Tóm tắt (xem run()).
	 */
	private static function execute( array $opts ) {
		$dry = self::to_bool( $opts['dry_run'] );
		$s   = self::blank_summary( $dry );

		if ( ! self::is_available() ) {
			self::fail( $s, __( 'Chưa đăng ký post type isha_service hoặc taxonomy isha_service_cat, không thể tạo dữ liệu.', 'isha-core' ) );
			return $s;
		}

		if ( $dry ) {
			$s['log'][] = __( 'Chạy thử: không ghi gì vào cơ sở dữ liệu.', 'isha-core' );
		}

		$status      = self::clean_status( $opts['status'], $s );
		$s['status'] = $status;
		$data        = self::data();
		$known_posts = self::seeded_posts();
		$author      = $dry ? 0 : self::author_id();

		foreach ( $data['groups'] as $group ) {
			$term_id = self::seed_term( self::TAXONOMY, $group, 'group', $dry, $s );

			if ( ! $term_id && ! $dry ) {
				/* translators: %s: tên nhóm dịch vụ */
				self::note( $s, sprintf( __( 'Bỏ qua các liệu trình của nhóm "%s" vì chưa có nhóm.', 'isha-core' ), $group['name'] ) );
				continue;
			}
			foreach ( $group['items'] as $item ) {
				self::seed_post( $item, $term_id, $status, $author, $dry, $known_posts, $s );
			}
		}

		if ( taxonomy_exists( self::EVENT_TAX ) ) {
			foreach ( $data['event_types'] as $type ) {
				self::seed_term( self::EVENT_TAX, $type, 'event', $dry, $s );
			}
		} else {
			self::fail( $s, __( 'Chưa đăng ký taxonomy isha_event_type, bỏ qua các loại sự kiện.', 'isha-core' ) );
		}

		if ( self::to_bool( $opts['trash_legacy'] ) ) {
			self::trash_legacy( wp_list_pluck( $data['groups'], 'name' ), $dry, $s );
		} else {
			self::note( $s, __( 'Giữ nguyên các bài dịch vụ cũ (không chuyển vào thùng rác).', 'isha-core' ) );
		}

		$wrote = $s['terms_created'] || $s['posts_created'] || $s['event_types_created'] || $s['legacy_trashed'];
		if ( ! $dry && $wrote ) {
			flush_rewrite_rules( false );
		}

		return $s;
	}

	/**
	 * Câu tóm tắt một dòng (tiếng Việt) của kết quả run(), dùng cho thông báo admin và WP-CLI.
	 *
	 * @param array $s Kết quả của run().
	 * @return string
	 */
	public static function summary_text( array $s ) {
		$s = array_merge(
			array(
				'terms_created'        => 0,
				'terms_existing'       => 0,
				'posts_created'        => 0,
				'posts_existing'       => 0,
				'event_types_created'  => 0,
				'event_types_existing' => 0,
				'legacy_trashed'       => array(),
				'dry_run'              => false,
			),
			$s
		);

		$numbers = array(
			(int) $s['terms_created'],
			(int) $s['terms_existing'],
			(int) $s['posts_created'],
			(int) $s['posts_existing'],
			(int) $s['event_types_created'],
			(int) $s['event_types_existing'],
			is_array( $s['legacy_trashed'] ) ? count( $s['legacy_trashed'] ) : 0,
		);

		if ( $s['dry_run'] ) {
			/* translators: 1: số nhóm dịch vụ sẽ tạo, 2: số nhóm đã có, 3: số liệu trình sẽ tạo, 4: số liệu trình đã có, 5: số loại sự kiện sẽ tạo, 6: số loại sự kiện đã có, 7: số bài cũ sẽ chuyển vào thùng rác */
			$format = __( 'Chạy thử, chưa ghi gì: sẽ tạo %1$d nhóm dịch vụ (%2$d đã có), %3$d liệu trình (%4$d đã có), %5$d loại sự kiện (%6$d đã có) và chuyển %7$d bài cũ vào thùng rác.', 'isha-core' );
		} else {
			/* translators: 1: số nhóm dịch vụ đã tạo, 2: số nhóm đã có, 3: số liệu trình đã tạo, 4: số liệu trình đã có, 5: số loại sự kiện đã tạo, 6: số loại sự kiện đã có, 7: số bài cũ đã chuyển vào thùng rác */
			$format = __( 'Hoàn tất: tạo %1$d nhóm dịch vụ (%2$d đã có), %3$d liệu trình (%4$d đã có), %5$d loại sự kiện (%6$d đã có); đã chuyển %7$d bài cũ vào thùng rác.', 'isha-core' );
		}
		return vsprintf( $format, $numbers );
	}

	/**
	 * Đổi giá trị tuỳ chọn sang bool: chuỗi rỗng, "0", "false", "no", "off" là false.
	 *
	 * @param mixed $value Giá trị.
	 * @return bool
	 */
	public static function to_bool( $value ) {
		if ( is_string( $value ) ) {
			return ! in_array( strtolower( trim( $value ) ), array( '', '0', 'false', 'no', 'off' ), true );
		}
		return (bool) $value;
	}

	/**
	 * Có đủ post type và taxonomy để seed không.
	 *
	 * @return bool
	 */
	private static function is_available() {
		return post_type_exists( self::POST_TYPE ) && taxonomy_exists( self::TAXONOMY );
	}

	/**
	 * Làm sạch trạng thái liệu trình; giá trị sai thì dùng draft và ghi nhật ký.
	 *
	 * @param mixed $raw Giá trị truyền vào.
	 * @param array $s   Tóm tắt đang dựng (để ghi nhật ký).
	 * @return string
	 */
	private static function clean_status( $raw, array &$s ) {
		$status = is_string( $raw ) ? strtolower( trim( $raw ) ) : '';
		if ( in_array( $status, self::STATUSES, true ) ) {
			return $status;
		}
		if ( null !== $raw && '' !== $raw ) {
			self::note( $s, __( 'Trạng thái liệu trình không hợp lệ, dùng "draft".', 'isha-core' ) );
		}
		return 'draft';
	}

	/**
	 * ID người dùng làm tác giả của liệu trình: người đang đăng nhập, không có (WP-CLI) thì quản trị viên đầu tiên.
	 *
	 * @return int 0 nếu không tìm được.
	 */
	private static function author_id() {
		$id = get_current_user_id();
		if ( $id ) {
			return $id;
		}
		$admins = get_users(
			array(
				'role'    => 'administrator',
				'number'  => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'fields'  => 'ID',
			)
		);
		return $admins ? (int) $admins[0] : 0;
	}

	/**
	 * Ghi một dòng nhật ký (có tiền tố khi chạy thử).
	 *
	 * @param array  $s    Tóm tắt đang dựng.
	 * @param string $text Nội dung.
	 */
	private static function note( array &$s, $text ) {
		$s['log'][] = ( $s['dry_run'] ? __( '[Chạy thử]', 'isha-core' ) . ' ' : '' ) . $text;
	}

	/**
	 * Ghi một lỗi vào `errors` và nhật ký.
	 *
	 * @param array  $s    Tóm tắt đang dựng.
	 * @param string $text Nội dung lỗi.
	 */
	private static function fail( array &$s, $text ) {
		$s['errors'][] = $text;
		/* translators: %s: nội dung lỗi */
		$s['log'][] = sprintf( __( 'Lỗi: %s', 'isha-core' ), $text );
	}

	/* --- Term -------------------------------------------------------------- */

	/**
	 * Tạo (hoặc nhận lại) một term: nhóm dịch vụ hoặc loại sự kiện.
	 *
	 * Tìm theo mã _isha_seed_key, rồi theo slug, rồi theo tên. Tìm thấy theo slug/tên thì gắn mã nếu chưa có và giữ nguyên
	 * mô tả, thứ tự. Không thấy thì tạo mới kèm mô tả, _isha_order (chỉ nhóm dịch vụ) và mã.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param array  $def      Một phần tử của data()['groups'] hoặc data()['event_types'].
	 * @param string $kind     group|event.
	 * @param bool   $dry      Chạy thử.
	 * @param array  $s        Tóm tắt đang dựng.
	 * @return int ID term (0 khi chạy thử mà term chưa có, hoặc khi tạo lỗi).
	 */
	private static function seed_term( $taxonomy, array $def, $kind, $dry, array &$s ) {
		$is_group  = 'group' === $kind;
		$count_new = $is_group ? 'terms_created' : 'event_types_created';
		$count_old = $is_group ? 'terms_existing' : 'event_types_existing';
		$name      = $def['name'];

		$found = self::find_term( $taxonomy, $def['key'], $def['slug'], $name );
		if ( $found ) {
			$adopted = false;
			if ( ! $dry && 'key' !== $found['by'] ) {
				$adopted = self::adopt_term( $found['id'], $def['key'] );
			}
			$s[ $count_old ]++;
			self::log_term( $s, $is_group, $adopted ? 'adopted' : 'existing', $name, $found['id'] );
			return $found['id'];
		}

		if ( $dry ) {
			$s[ $count_new ]++;
			self::log_term( $s, $is_group, 'planned', $name, 0 );
			return 0;
		}

		$args = array( 'slug' => $def['slug'] );
		if ( ! empty( $def['tagline'] ) ) {
			$args['description'] = $def['tagline'];
		}
		$result = wp_insert_term( wp_slash( $name ), $taxonomy, wp_slash( $args ) );

		if ( is_wp_error( $result ) ) {
			// Term cùng tên/slug đã có (ví dụ do chạy song song): nhận làm term đã có.
			$data     = $result->get_error_data();
			$existing = ( in_array( $result->get_error_code(), array( 'term_exists', 'duplicate_term_slug' ), true ) && is_numeric( $data ) ) ? (int) $data : 0;
			if ( $existing ) {
				self::adopt_term( $existing, $def['key'] );
				$s[ $count_old ]++;
				self::log_term( $s, $is_group, 'adopted', $name, $existing );
				return $existing;
			}
			self::log_term( $s, $is_group, 'failed', $name, 0, $result->get_error_message() );
			return 0;
		}

		$term_id = (int) $result['term_id'];
		update_term_meta( $term_id, self::KEY_META, wp_slash( $def['key'] ) );
		if ( isset( $def['order'] ) ) {
			update_term_meta( $term_id, self::ORDER_META, (int) $def['order'] );
		}
		$s[ $count_new ]++;
		self::log_term( $s, $is_group, 'created', $name, $term_id );
		return $term_id;
	}

	/**
	 * Ghi dòng nhật ký của một term.
	 *
	 * @param array  $s        Tóm tắt đang dựng.
	 * @param bool   $is_group true: nhóm dịch vụ, false: loại sự kiện.
	 * @param string $state    created|planned|existing|adopted|failed.
	 * @param string $name     Tên term.
	 * @param int    $term_id  ID term (0 nếu chưa có).
	 * @param string $error    Nội dung lỗi (khi state là failed).
	 */
	private static function log_term( array &$s, $is_group, $state, $name, $term_id, $error = '' ) {
		switch ( $state ) {
			case 'created':
				/* translators: 1: tên, 2: ID term */
				$text = $is_group ? __( 'Nhóm dịch vụ "%1$s": đã tạo (ID %2$d).', 'isha-core' ) : __( 'Loại sự kiện "%1$s": đã tạo (ID %2$d).', 'isha-core' );
				break;
			case 'planned':
				/* translators: 1: tên, 2: ID term (không dùng) */
				$text = $is_group ? __( 'Nhóm dịch vụ "%1$s": sẽ được tạo.', 'isha-core' ) : __( 'Loại sự kiện "%1$s": sẽ được tạo.', 'isha-core' );
				break;
			case 'adopted':
				/* translators: 1: tên, 2: ID term */
				$text = $is_group ? __( 'Nhóm dịch vụ "%1$s": đã có (ID %2$d) theo slug hoặc tên, đã gắn mã khởi tạo; giữ nguyên mô tả và thứ tự.', 'isha-core' ) : __( 'Loại sự kiện "%1$s": đã có (ID %2$d) theo slug hoặc tên, đã gắn mã khởi tạo.', 'isha-core' );
				break;
			case 'failed':
				/* translators: 1: tên, 2: nội dung lỗi */
				$text = $is_group ? __( 'Không tạo được nhóm dịch vụ "%1$s": %2$s', 'isha-core' ) : __( 'Không tạo được loại sự kiện "%1$s": %2$s', 'isha-core' );
				break;
			default:
				/* translators: 1: tên, 2: ID term */
				$text = $is_group ? __( 'Nhóm dịch vụ "%1$s": đã có (ID %2$d), giữ nguyên.', 'isha-core' ) : __( 'Loại sự kiện "%1$s": đã có (ID %2$d), giữ nguyên.', 'isha-core' );
		}

		if ( 'failed' === $state ) {
			self::fail( $s, sprintf( $text, $name, $error ) );
			return;
		}
		self::note( $s, sprintf( $text, $name, $term_id ) );
	}

	/**
	 * Tìm term đã có: theo mã seed, rồi theo slug, rồi theo tên (cấp gốc).
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $key      Mã seed.
	 * @param string $slug     Slug mong muốn.
	 * @param string $name     Tên mong muốn.
	 * @return array{id:int,by:string}|null `by` là key|slug|name.
	 */
	private static function find_term( $taxonomy, $key, $slug, $name ) {
		$base = array(
			'taxonomy'               => $taxonomy,
			'hide_empty'             => false,
			'number'                 => 1,
			'fields'                 => 'ids',
			'update_term_meta_cache' => false,
		);

		$ids = get_terms(
			array_merge(
				$base,
				array(
					'meta_key'   => self::KEY_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Bảng term meta nhỏ, chỉ chạy khi seed.
					'meta_value' => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			)
		);
		if ( is_array( $ids ) && $ids ) {
			return array(
				'id' => (int) $ids[0],
				'by' => 'key',
			);
		}

		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term instanceof WP_Term ) {
			return array(
				'id' => (int) $term->term_id,
				'by' => 'slug',
			);
		}

		$ids = get_terms(
			array_merge(
				$base,
				array(
					'name'   => $name,
					'parent' => 0,
				)
			)
		);
		if ( is_array( $ids ) && $ids ) {
			return array(
				'id' => (int) $ids[0],
				'by' => 'name',
			);
		}

		return null;
	}

	/**
	 * Gắn mã seed cho term đã có nếu term chưa có mã (không đụng tới mô tả, thứ tự).
	 *
	 * @param int    $term_id ID term.
	 * @param string $key     Mã seed.
	 * @return bool true nếu vừa gắn mã.
	 */
	private static function adopt_term( $term_id, $key ) {
		if ( '' !== (string) get_term_meta( $term_id, self::KEY_META, true ) ) {
			return false;
		}
		update_term_meta( $term_id, self::KEY_META, wp_slash( $key ) );
		return true;
	}

	/* --- Liệu trình -------------------------------------------------------- */

	/**
	 * Mọi trạng thái bài, kể cả thùng rác và auto-draft (WP_Query với 'any' bỏ qua hai trạng thái này).
	 *
	 * @return string[]
	 */
	private static function all_statuses() {
		return array_values( get_post_stati() );
	}

	/**
	 * Các bài liệu trình đã seed (mọi trạng thái, kể cả thùng rác): mã seed => ID.
	 *
	 * @return array<string,int>
	 */
	private static function seeded_posts() {
		$ids = get_posts(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => self::all_statuses(),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'meta_key'               => self::KEY_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Chỉ chạy khi seed.
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);

		$map = array();
		foreach ( $ids as $id ) {
			$key = (string) get_post_meta( $id, self::KEY_META, true );
			if ( '' !== $key && ! isset( $map[ $key ] ) ) {
				$map[ $key ] = (int) $id;
			}
		}
		return $map;
	}

	/**
	 * Tạo một liệu trình nếu chưa có (theo mã seed, mọi trạng thái).
	 *
	 * @param array  $item        Phần tử của data()['groups'][n]['items'].
	 * @param int    $term_id     ID nhóm dịch vụ (0 khi chạy thử mà nhóm chưa có).
	 * @param string $status      Trạng thái bài.
	 * @param int    $author      ID tác giả (0: mặc định của WordPress).
	 * @param bool   $dry         Chạy thử.
	 * @param array  $known_posts Mã seed => ID các bài đã có (cập nhật khi tạo thêm).
	 * @param array  $s           Tóm tắt đang dựng.
	 */
	private static function seed_post( array $item, $term_id, $status, $author, $dry, array &$known_posts, array &$s ) {
		$key   = $item['key'];
		$title = $item['title'];

		if ( isset( $known_posts[ $key ] ) ) {
			$s['posts_existing']++;
			/* translators: 1: tên liệu trình, 2: ID bài */
			self::note( $s, sprintf( __( 'Liệu trình "%1$s": đã có (ID %2$d), giữ nguyên.', 'isha-core' ), $title, $known_posts[ $key ] ) );
			return;
		}

		if ( $dry ) {
			$s['posts_created']++;
			/* translators: 1: tên liệu trình, 2: trạng thái bài */
			self::note( $s, sprintf( __( 'Liệu trình "%1$s": sẽ được tạo (trạng thái %2$s).', 'isha-core' ), $title, $status ) );
			return;
		}

		$postarr = array(
			'post_type'   => self::POST_TYPE,
			'post_status' => $status,
			'post_title'  => $title,
			'menu_order'  => (int) $item['menu_order'],
		);
		if ( $author ) {
			$postarr['post_author'] = $author;
		}

		$post_id = wp_insert_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			/* translators: 1: tên liệu trình, 2: nội dung lỗi */
			self::fail( $s, sprintf( __( 'Không tạo được liệu trình "%1$s": %2$s', 'isha-core' ), $title, is_wp_error( $post_id ) ? $post_id->get_error_message() : '' ) );
			return;
		}

		$post_id = (int) $post_id;
		update_post_meta( $post_id, self::KEY_META, wp_slash( $key ) );
		$known_posts[ $key ] = $post_id;

		$assigned = wp_set_object_terms( $post_id, array( (int) $term_id ), self::TAXONOMY );
		if ( is_wp_error( $assigned ) ) {
			/* translators: 1: tên liệu trình, 2: nội dung lỗi */
			self::fail( $s, sprintf( __( 'Không gán được nhóm cho liệu trình "%1$s": %2$s', 'isha-core' ), $title, $assigned->get_error_message() ) );
		}

		$s['posts_created']++;
		/* translators: 1: tên liệu trình, 2: ID bài, 3: trạng thái bài */
		self::note( $s, sprintf( __( 'Liệu trình "%1$s": đã tạo (ID %2$d, trạng thái %3$s).', 'isha-core' ), $title, $post_id, $status ) );
	}

	/* --- Bài dịch vụ cũ ---------------------------------------------------- */

	/**
	 * Chuẩn hoá tên để so sánh: Unicode NFC (nếu có intl), chữ thường, gộp khoảng trắng, và đưa các từ viết sai chính tả
	 * đã biết của bài cũ về dạng chuẩn ("mĩ" thành "mỹ"; "phẩu" thành "phẫu", vì bài cũ "PHẨU THUẬT THẨM MỸ" viết sai).
	 *
	 * @param string $title Tên.
	 * @return string
	 */
	private static function normalize_title( $title ) {
		$title = (string) $title;
		if ( class_exists( 'Normalizer' ) ) {
			$nfc = Normalizer::normalize( $title, Normalizer::FORM_C );
			if ( is_string( $nfc ) ) {
				$title = $nfc;
			}
		}
		$title = function_exists( 'mb_strtolower' ) ? mb_strtolower( $title, 'UTF-8' ) : strtolower( $title );
		$title = trim( (string) preg_replace( '/\s+/u', ' ', $title ) );

		$words = explode( ' ', $title );
		foreach ( $words as $index => $word ) {
			if ( isset( self::SPELLING[ $word ] ) ) {
				$words[ $index ] = self::SPELLING[ $word ];
			}
		}
		return implode( ' ', $words );
	}

	/**
	 * Các bài isha_service cũ cần chuyển vào thùng rác: tên chuẩn hoá trùng một tên nhóm, chưa gán term nào, trạng thái
	 * publish hoặc draft, không phải bài đã seed và chưa từng bị seed chuyển vào thùng rác (người dùng đã khôi phục).
	 *
	 * @param string[] $group_names Tên 6 nhóm.
	 * @return array<int,array{id:int,title:string}> Theo ID tăng dần.
	 */
	private static function find_legacy( array $group_names ) {
		$targets = array();
		foreach ( $group_names as $name ) {
			$targets[ self::normalize_title( $name ) ] = true;
		}

		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		$taxonomies = get_object_taxonomies( self::POST_TYPE );
		$found      = array();
		foreach ( $posts as $post ) {
			if ( ! isset( $targets[ self::normalize_title( $post->post_title ) ] ) ) {
				continue;
			}
			if ( '' !== (string) get_post_meta( $post->ID, self::KEY_META, true ) || '' !== (string) get_post_meta( $post->ID, self::LEGACY_META, true ) ) {
				continue;
			}
			if ( $taxonomies && wp_get_object_terms( $post->ID, $taxonomies, array( 'fields' => 'ids' ) ) ) {
				continue;
			}
			$found[] = array(
				'id'    => (int) $post->ID,
				'title' => (string) $post->post_title,
			);
		}
		return $found;
	}

	/**
	 * Chuyển các bài dịch vụ cũ vào thùng rác bằng wp_trash_post() và đánh dấu để lần chạy sau không chuyển lại nếu người
	 * dùng khôi phục. Khi thùng rác bị tắt (EMPTY_TRASH_DAYS = 0) wp_trash_post() sẽ xoá hẳn, nên bỏ qua việc này.
	 *
	 * @param string[] $group_names Tên 6 nhóm.
	 * @param bool     $dry         Chạy thử.
	 * @param array    $s           Tóm tắt đang dựng.
	 */
	private static function trash_legacy( array $group_names, $dry, array &$s ) {
		$legacy = self::find_legacy( $group_names );
		if ( ! $legacy ) {
			self::note( $s, __( 'Không có bài dịch vụ cũ nào cần chuyển vào thùng rác.', 'isha-core' ) );
			return;
		}

		if ( ! $dry && defined( 'EMPTY_TRASH_DAYS' ) && ! EMPTY_TRASH_DAYS ) {
			self::fail( $s, __( 'Thùng rác đang tắt (EMPTY_TRASH_DAYS = 0), không chuyển bài dịch vụ cũ để tránh xoá mất dữ liệu.', 'isha-core' ) );
			return;
		}

		foreach ( $legacy as $row ) {
			if ( $dry ) {
				$s['legacy_trashed'][] = $row['id'];
				/* translators: 1: tên bài, 2: ID bài */
				self::note( $s, sprintf( __( 'Bài dịch vụ cũ "%1$s" (ID %2$d): sẽ chuyển vào thùng rác.', 'isha-core' ), $row['title'], $row['id'] ) );
				continue;
			}

			if ( ! wp_trash_post( $row['id'] ) ) {
				/* translators: 1: tên bài, 2: ID bài */
				self::fail( $s, sprintf( __( 'Không chuyển được bài dịch vụ cũ "%1$s" (ID %2$d) vào thùng rác.', 'isha-core' ), $row['title'], $row['id'] ) );
				continue;
			}
			update_post_meta( $row['id'], self::LEGACY_META, '1' );
			$s['legacy_trashed'][] = $row['id'];
			/* translators: 1: tên bài, 2: ID bài */
			self::note( $s, sprintf( __( 'Bài dịch vụ cũ "%1$s" (ID %2$d): đã chuyển vào thùng rác, khôi phục được.', 'isha-core' ), $row['title'], $row['id'] ) );
		}
	}

	/* --- Thông báo admin và nút bấm --------------------------------------- */

	/**
	 * Thông báo admin, chỉ cho người có manage_options: kết quả của lần seed vừa chạy (hiện một lần), hoặc lời mời tạo
	 * dữ liệu khi taxonomy isha_service_cat chưa có term nào. Màn hiện kết quả không hiện lời mời.
	 */
	public static function render_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$key    = self::RESULT_KEY . get_current_user_id();
		$result = get_transient( $key );
		if ( is_array( $result ) ) {
			delete_transient( $key );
			self::render_result( $result );
			return;
		}

		if ( ! self::is_available() || self::has_terms() ) {
			return;
		}
		self::render_offer();
	}

	/**
	 * Taxonomy isha_service_cat đã có term nào chưa (truy vấn nhẹ: một dòng, chỉ lấy ID).
	 *
	 * @return bool
	 */
	private static function has_terms() {
		$ids = get_terms(
			array(
				'taxonomy'               => self::TAXONOMY,
				'hide_empty'             => false,
				'number'                 => 1,
				'fields'                 => 'ids',
				'update_term_meta_cache' => false,
			)
		);
		return is_array( $ids ) && ! empty( $ids );
	}

	/**
	 * In lời mời: mô tả việc sẽ làm và nút bấm (form POST tới admin-post.php có nonce).
	 */
	private static function render_offer() {
		$data  = self::data();
		$items = 0;
		foreach ( $data['groups'] as $group ) {
			$items += count( $group['items'] );
		}
		$legacy = count( self::find_legacy( wp_list_pluck( $data['groups'], 'name' ) ) );

		/* translators: 1: số nhóm dịch vụ, 2: số liệu trình, 3: số loại sự kiện, 4: số bài dịch vụ cũ */
		$text = sprintf( __( 'Chưa có nhóm dịch vụ nào. Nút bên dưới sẽ tạo %1$d nhóm dịch vụ (kèm mô tả và thứ tự), tạo %2$d liệu trình ở trạng thái nháp, tạo %3$d loại sự kiện và chuyển %4$d bài dịch vụ cũ trùng tên nhóm vào thùng rác (có thể khôi phục). Chạy lại vẫn an toàn: dữ liệu đã có không bị tạo trùng hay ghi đè.', 'isha-core' ), count( $data['groups'] ), $items, count( $data['event_types'] ), $legacy );
		?>
		<div class="notice notice-info isha-seed-notice">
			<p><strong><?php esc_html_e( 'Dữ liệu dịch vụ ISHA', 'isha-core' ); ?></strong></p>
			<p><?php echo esc_html( $text ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Tạo dữ liệu dịch vụ từ leaflet', 'isha-core' ); ?></button></p>
			</form>
		</div>
		<?php
	}

	/**
	 * In kết quả của lần seed vừa chạy: câu tóm tắt, lỗi (nếu có), liên kết tới thùng rác và chi tiết nhật ký.
	 *
	 * @param array $result Kết quả của run() (lấy từ transient).
	 */
	private static function render_result( array $result ) {
		$result = array_merge(
			array(
				'legacy_trashed' => array(),
				'errors'         => array(),
				'log'            => array(),
			),
			$result
		);
		$errors = array_filter( (array) $result['errors'], 'is_string' );
		$log    = array_filter( (array) $result['log'], 'is_string' );
		?>
		<div class="notice <?php echo esc_attr( $errors ? 'notice-warning' : 'notice-success' ); ?> is-dismissible isha-seed-notice">
			<p><strong><?php esc_html_e( 'Dữ liệu dịch vụ ISHA', 'isha-core' ); ?></strong></p>
			<p><?php echo esc_html( self::summary_text( $result ) ); ?></p>
			<?php if ( $errors ) : ?>
				<ul class="ul-disc">
					<?php foreach ( $errors as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( ! empty( $result['legacy_trashed'] ) ) : ?>
				<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_status=trash&post_type=' . self::POST_TYPE ) ); ?>"><?php esc_html_e( 'Xem thùng rác để khôi phục các bài dịch vụ cũ', 'isha-core' ); ?></a></p>
			<?php endif; ?>
			<?php if ( $log ) : ?>
				<details>
					<summary><?php esc_html_e( 'Chi tiết', 'isha-core' ); ?></summary>
					<ul class="ul-disc">
						<?php foreach ( $log as $line ) : ?>
							<li><?php echo esc_html( $line ); ?></li>
						<?php endforeach; ?>
					</ul>
				</details>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Xử lý nút bấm (admin_post_isha_seed_services): kiểm quyền và nonce, chạy seed, lưu kết quả vào transient ngắn hạn
	 * của người dùng rồi chuyển hướng về trang trước (không hợp lệ thì về bảng điều khiển).
	 */
	public static function handle_post() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền tạo dữ liệu dịch vụ.', 'isha-core' ), '', array( 'response' => 403 ) );
			return;
		}

		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Liên kết đã hết hạn. Hãy tải lại trang rồi thử lại.', 'isha-core' ), '', array( 'response' => 403 ) );
			return;
		}

		$summary = self::run(
			array(
				'status'       => 'draft',
				'trash_legacy' => true,
				'dry_run'      => false,
			)
		);
		set_transient( self::RESULT_KEY . get_current_user_id(), $summary, self::RESULT_TTL );

		$back = wp_get_referer();
		if ( wp_safe_redirect( $back ? $back : admin_url() ) ) {
			exit;
		}
	}
}

/*
 * Lệnh WP-CLI. Chỉ khai báo khi chạy dưới WP-CLI (lớp cha WP_CLI_Command do WP-CLI nạp trước plugin).
 */
if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI_Command' ) ) {

	/**
	 * Dữ liệu dịch vụ khởi tạo của ISHA (6 nhóm, 17 liệu trình, 3 loại sự kiện).
	 */
	class ISHA_Core_Seed_Command extends WP_CLI_Command {

		/**
		 * Tạo nhóm dịch vụ, liệu trình và loại sự kiện theo leaflet; chạy lại không tạo trùng.
		 *
		 * ## OPTIONS
		 *
		 * [--status=<status>]
		 * : Trạng thái của liệu trình được tạo.
		 * ---
		 * default: draft
		 * options:
		 *   - draft
		 *   - publish
		 * ---
		 *
		 * [--keep-legacy]
		 * : Giữ nguyên các bài dịch vụ cũ trùng tên nhóm (mặc định chuyển vào thùng rác, khôi phục được).
		 *
		 * [--dry-run]
		 * : Chỉ in việc sẽ làm, không ghi gì.
		 *
		 * ## EXAMPLES
		 *
		 *     wp isha seed services --dry-run
		 *     wp isha seed services --status=publish --keep-legacy
		 *
		 * @param array $args       Đối số vị trí (không dùng).
		 * @param array $assoc_args Tuỳ chọn.
		 */
		public function services( $args, $assoc_args ) {
			$status = isset( $assoc_args['status'] ) && is_string( $assoc_args['status'] ) ? strtolower( trim( $assoc_args['status'] ) ) : 'draft';
			if ( ! in_array( $status, array( 'draft', 'publish' ), true ) ) {
				WP_CLI::error( 'Giá trị --status không hợp lệ: chỉ nhận draft hoặc publish.' );
				return;
			}

			$summary = ISHA_Core_Seed::run(
				array(
					'status'       => $status,
					'trash_legacy' => ! ( isset( $assoc_args['keep-legacy'] ) && ISHA_Core_Seed::to_bool( $assoc_args['keep-legacy'] ) ),
					'dry_run'      => isset( $assoc_args['dry-run'] ) && ISHA_Core_Seed::to_bool( $assoc_args['dry-run'] ),
				)
			);

			foreach ( $summary['log'] as $line ) {
				WP_CLI::log( $line );
			}

			$text = ISHA_Core_Seed::summary_text( $summary );
			if ( $summary['errors'] ) {
				foreach ( $summary['errors'] as $error ) {
					WP_CLI::warning( $error );
				}
				WP_CLI::error( $text );
				return;
			}
			WP_CLI::success( $text );
		}
	}

	if ( class_exists( 'WP_CLI\Dispatcher\CommandNamespace' ) ) {

		/**
		 * Lệnh quản trị của ISHA Core.
		 */
		class ISHA_Core_CLI_Namespace extends WP_CLI\Dispatcher\CommandNamespace {
		}
	}
}
