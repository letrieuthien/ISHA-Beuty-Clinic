<?php
/**
 * Trường dữ liệu của Sự kiện (isha_event).
 *
 * Meta box native (không dùng ACF). Khoá meta bắt đầu bằng gạch dưới nên không hiện ở "Custom Fields":
 *   _isha_event_start, _isha_event_end        – ngày giờ theo múi giờ của site, dạng Y-m-d H:i
 *   _isha_event_start_ts, _isha_event_end_ts  – timestamp UTC tương ứng (dùng để sắp xếp, so sánh)
 *   _isha_event_location                      – địa điểm (text)
 *   _isha_event_register_url                  – link đăng ký (http hoặc https)
 *   _isha_event_featured                      – '1' khi là sự kiện nổi bật
 *   _isha_event_portrait_id                   – ID ảnh dọc 4:5 cho điện thoại (ô chọn ISHA_Core_Admin_Media)
 *   _isha_event_summary                       – nội dung ngắn (một dòng, tối đa 200 ký tự) cho thẻ bài
 * Ảnh ngang 2.39:1 là ảnh đại diện (thumbnail) của bài. Hàm đọc chỉ dùng get_post_meta, không phụ thuộc ACF.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Event_Fields {

	const POST_TYPE    = 'isha_event';
	const NONCE_ACTION = 'isha_save_event';
	const NONCE_FIELD  = 'isha_event_nonce';

	const META_START        = '_isha_event_start';
	const META_END          = '_isha_event_end';
	const META_START_TS     = '_isha_event_start_ts';
	const META_END_TS       = '_isha_event_end_ts';
	const META_LOCATION     = '_isha_event_location';
	const META_REGISTER_URL = '_isha_event_register_url';
	const META_FEATURED     = '_isha_event_featured';
	const META_PORTRAIT     = '_isha_event_portrait_id';
	const META_SUMMARY      = '_isha_event_summary';

	/**
	 * Độ dài tối đa của địa điểm và của nội dung ngắn (ký tự), của link đăng ký (byte).
	 */
	const LOCATION_MAX = 200;
	const URL_MAX      = 2048;
	const SUMMARY_MAX  = 200;

	/**
	 * Gắn hook (gọi một lần từ isha_core_init()).
	 */
	public static function init() {
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
	}

	/* --- Đọc dữ liệu (không phụ thuộc ACF) -------------------------------- */

	/**
	 * Dữ liệu sự kiện để hiển thị. Chỉ đọc; giá trị được làm sạch lại khi đọc.
	 * `start`/`end` là chuỗi Y-m-d H:i theo múi giờ của site (rỗng nếu chưa đặt); `*_ts` là timestamp UTC (0 nếu chưa đặt).
	 * `summary` là nội dung ngắn một dòng đã làm sạch (rỗng nếu chưa có).
	 *
	 * @param int $post_id ID sự kiện.
	 * @return array{start:string,start_ts:int,end:string,end_ts:int,location:string,register_url:string,featured:bool,portrait_id:int,summary:string}
	 */
	public static function get( $post_id ) {
		$post_id  = absint( $post_id );
		$start    = self::clean_local( self::meta( $post_id, self::META_START ) );
		$start_ts = self::clean_ts( self::meta( $post_id, self::META_START_TS ) );
		$end      = self::clean_local( self::meta( $post_id, self::META_END ) );
		$end_ts   = self::clean_ts( self::meta( $post_id, self::META_END_TS ) );

		// Kết thúc chỉ có nghĩa khi đã có bắt đầu và đứng sau bắt đầu (cùng quy tắc với lúc lưu).
		$has_start = '' !== $start || $start_ts > 0;
		$bad_end   = ! $has_start
			|| ( $start_ts > 0 && $end_ts > 0 && $end_ts <= $start_ts )
			|| ( '' !== $start && '' !== $end && $end <= $start );
		if ( $bad_end ) {
			$end    = '';
			$end_ts = 0;
		}

		return array(
			'start'        => $start,
			'start_ts'     => $start_ts,
			'end'          => $end,
			'end_ts'       => $end_ts,
			'location'     => self::clean_location( self::meta( $post_id, self::META_LOCATION ) ),
			'register_url' => self::sanitize_url( self::meta( $post_id, self::META_REGISTER_URL ) ),
			'featured'     => '1' === self::meta( $post_id, self::META_FEATURED ),
			'portrait_id'  => self::clean_image_id( self::meta( $post_id, self::META_PORTRAIT ) ),
			'summary'      => self::clean_summary( self::meta( $post_id, self::META_SUMMARY ) ),
		);
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

	/* --- Làm sạch (hàm thuần, dùng khi lưu và khi đọc) --------------------- */

	/**
	 * Đổi ngày giờ nhập vào thành cặp chuỗi + timestamp UTC theo múi giờ của site (wp_timezone()).
	 *
	 * Ngày giờ sai (không đúng định dạng, ngày không tồn tại như 30/02, năm ngoài 2000-2100) bị bỏ. Kết thúc phải sau
	 * bắt đầu và chỉ có nghĩa khi đã có bắt đầu, nếu không thì bị xoá.
	 *
	 * @param mixed $start_raw Bắt đầu, dạng Y-m-d\TH:i (ô datetime-local) hoặc Y-m-d H:i.
	 * @param mixed $end_raw   Kết thúc, cùng định dạng.
	 * @return array{start:string,start_ts:int,end:string,end_ts:int}
	 */
	public static function sanitize_times( $start_raw, $end_raw = '' ) {
		$out   = array(
			'start'    => '',
			'start_ts' => 0,
			'end'      => '',
			'end_ts'   => 0,
		);
		$start = self::parse_local( $start_raw );
		if ( ! $start ) {
			return $out;
		}
		$out['start']    = $start['local'];
		$out['start_ts'] = $start['ts'];

		$end = self::parse_local( $end_raw );
		if ( $end && $end['ts'] > $start['ts'] ) {
			$out['end']    = $end['local'];
			$out['end_ts'] = $end['ts'];
		}
		return $out;
	}

	/**
	 * Phân tích một ngày giờ theo múi giờ của site.
	 *
	 * @param mixed $raw Chuỗi Y-m-d\TH:i hoặc Y-m-d H:i (cho phép thêm giây, sẽ bị bỏ).
	 * @return array{local:string,ts:int}|null null nếu không hợp lệ.
	 */
	private static function parse_local( $raw ) {
		if ( ! is_string( $raw ) || ! preg_match( '/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})(?::\d{2}(?:\.\d+)?)?$/D', trim( $raw ), $m ) ) {
			return null;
		}
		$local = $m[1] . ' ' . $m[2];
		$date  = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $local, wp_timezone() );

		// So lại chuỗi để loại ngày tràn (30/02 thành 02/03), giờ 24:xx và giờ rơi vào khoảng nhảy giờ mùa hè.
		if ( ! $date || $date->format( 'Y-m-d H:i' ) !== $local ) {
			return null;
		}
		$year = (int) $date->format( 'Y' );
		if ( $year < 2000 || $year > 2100 ) {
			return null;
		}
		return array(
			'local' => $local,
			'ts'    => $date->getTimestamp(),
		);
	}

	/**
	 * Làm sạch chuỗi ngày giờ đã lưu (Y-m-d H:i).
	 *
	 * @param string $raw Giá trị đã lưu.
	 * @return string Chuỗi chuẩn hoá hoặc ''.
	 */
	private static function clean_local( $raw ) {
		$parsed = self::parse_local( $raw );
		return $parsed ? $parsed['local'] : '';
	}

	/**
	 * Làm sạch timestamp đã lưu.
	 *
	 * @param string $raw Giá trị đã lưu.
	 * @return int Số nguyên dương hoặc 0.
	 */
	private static function clean_ts( $raw ) {
		return preg_match( '/^\d{1,11}$/D', $raw ) ? (int) $raw : 0;
	}

	/**
	 * Làm sạch địa điểm: bỏ thẻ HTML, gộp khoảng trắng, cắt ở LOCATION_MAX ký tự.
	 *
	 * @param mixed $raw Giá trị thô.
	 * @return string
	 */
	private static function clean_location( $raw ) {
		return trim( mb_substr( sanitize_text_field( $raw ), 0, self::LOCATION_MAX ) );
	}

	/**
	 * Làm sạch nội dung ngắn: bỏ thẻ HTML, gộp mọi khoảng trắng và xuống dòng thành MỘT dấu cách (nội dung ngắn là một
	 * đoạn một dòng), cắt ở SUMMARY_MAX ký tự (mb_substr nên không vỡ UTF-8 tiếng Việt có dấu).
	 *
	 * @param mixed $raw Giá trị thô.
	 * @return string
	 */
	private static function clean_summary( $raw ) {
		// sanitize_text_field() bỏ thẻ HTML và gộp dấu cách, tab, xuống dòng ASCII; preg_replace() gộp thêm khoảng trắng
		// Unicode (ví dụ dấu cách không ngắt U+00A0 khi dán từ web hoặc Word). Kết quả lỗi (null) thì coi như rỗng.
		$text = (string) preg_replace( '/\s+/u', ' ', sanitize_text_field( $raw ) );
		return trim( mb_substr( $text, 0, self::SUMMARY_MAX, 'UTF-8' ) );
	}

	/**
	 * Làm sạch link đăng ký: chỉ nhận URL tuyệt đối http hoặc https có tên miền hợp lệ.
	 * Chuỗi có khoảng trắng, không có http(s)://, dùng javascript:, data:... hoặc dài quá URL_MAX thì trả ''.
	 *
	 * @param mixed $raw Giá trị thô (đã bỏ slash).
	 * @return string URL đã qua esc_url_raw hoặc ''.
	 */
	public static function sanitize_url( $raw ) {
		if ( ! is_string( $raw ) ) {
			return '';
		}
		$raw = trim( $raw );
		if ( '' === $raw || strlen( $raw ) > self::URL_MAX || ! preg_match( '#^https?://\S+$#iD', $raw ) ) {
			return '';
		}
		$url  = esc_url_raw( $raw, array( 'http', 'https' ) );
		$host = '' === $url ? null : wp_parse_url( $url, PHP_URL_HOST );
		return ( is_string( $host ) && self::is_valid_host( $host ) ) ? $url : '';
	}

	/**
	 * Tên miền hợp lệ: nhãn chữ/số (Unicode) nối bằng dấu gạch ngang, cách nhau dấu chấm; hoặc địa chỉ IP.
	 *
	 * @param string $host Tên miền lấy từ URL.
	 * @return bool
	 */
	private static function is_valid_host( $host ) {
		$host = trim( $host, '[]' );
		if ( false !== filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return true;
		}
		$label = '[\p{L}\p{N}](?:[\p{L}\p{N}-]{0,61}[\p{L}\p{N}])?';
		return strlen( $host ) <= 253 && 1 === preg_match( '/^(?:' . $label . '\.)*' . $label . '\.?$/u', $host );
	}

	/**
	 * Làm sạch ID ảnh dọc đã lưu: phải là tệp đính kèm ảnh.
	 *
	 * @param string $raw Giá trị đã lưu.
	 * @return int ID hoặc 0.
	 */
	private static function clean_image_id( $raw ) {
		if ( ! preg_match( '/^\d{1,18}$/D', $raw ) ) {
			return 0;
		}
		$id = (int) $raw;
		return ( $id > 0 && 'attachment' === get_post_type( $id ) && wp_attachment_is_image( $id ) ) ? $id : 0;
	}

	/* --- Meta box --------------------------------------------------------- */

	public static function add_meta_box() {
		add_meta_box( 'isha_event_fields', __( 'Thông tin sự kiện', 'isha-core' ), array( __CLASS__, 'render' ), self::POST_TYPE, 'normal', 'high' );
	}

	/**
	 * In meta box.
	 *
	 * @param WP_Post $post Sự kiện đang sửa.
	 */
	public static function render( $post ) {
		$data = self::get( $post->ID );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD, false );
		?>
		<table class="form-table isha-fields" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="isha_event_summary"><?php esc_html_e( 'Nội dung ngắn', 'isha-core' ); ?></label></th>
					<td>
						<textarea id="isha_event_summary" name="isha_event_summary" class="large-text" rows="3" maxlength="<?php echo esc_attr( (string) self::SUMMARY_MAX ); ?>" aria-describedby="isha_event_summary_desc"><?php echo esc_textarea( $data['summary'] ); ?></textarea>
						<p class="description" id="isha_event_summary_desc"><?php esc_html_e( 'Đoạn mô tả ngắn (1–2 câu, tối đa 200 ký tự) hiện trên thẻ bài ở trang danh sách và trang chủ. Để trống thì dùng Đoạn trích (Excerpt) hoặc đoạn đầu của bài.', 'isha-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="isha_event_start"><?php esc_html_e( 'Bắt đầu', 'isha-core' ); ?></label></th>
					<td>
						<input type="datetime-local" id="isha_event_start" name="isha_event_start" value="<?php echo esc_attr( self::to_input_value( $data['start'] ) ); ?>" aria-describedby="isha_event_start_desc">
						<p class="description" id="isha_event_start_desc"><?php esc_html_e( 'Ngày và giờ bắt đầu theo múi giờ của website (Cài đặt › Chung). Dùng để sắp xếp sự kiện và xác định sự kiện đã diễn ra.', 'isha-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="isha_event_end"><?php esc_html_e( 'Kết thúc (tuỳ chọn)', 'isha-core' ); ?></label></th>
					<td>
						<input type="datetime-local" id="isha_event_end" name="isha_event_end" value="<?php echo esc_attr( self::to_input_value( $data['end'] ) ); ?>" aria-describedby="isha_event_end_desc">
						<p class="description" id="isha_event_end_desc"><?php esc_html_e( 'Không bắt buộc. Phải sau thời điểm bắt đầu, nếu không ô này sẽ bị bỏ trống khi lưu.', 'isha-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="isha_event_location"><?php esc_html_e( 'Địa điểm', 'isha-core' ); ?></label></th>
					<td>
						<input type="text" id="isha_event_location" name="isha_event_location" class="large-text" maxlength="<?php echo esc_attr( (string) self::LOCATION_MAX ); ?>" value="<?php echo esc_attr( $data['location'] ); ?>" aria-describedby="isha_event_location_desc">
						<p class="description" id="isha_event_location_desc"><?php esc_html_e( 'Tên địa điểm hoặc địa chỉ ngắn, hiển thị trên banner sự kiện.', 'isha-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="isha_event_register_url"><?php esc_html_e( 'Link đăng ký', 'isha-core' ); ?></label></th>
					<td>
						<input type="url" id="isha_event_register_url" name="isha_event_register_url" class="large-text" maxlength="<?php echo esc_attr( (string) self::URL_MAX ); ?>" value="<?php echo esc_url( $data['register_url'] ); ?>" placeholder="https://" aria-describedby="isha_event_register_url_desc">
						<p class="description" id="isha_event_register_url_desc"><?php esc_html_e( 'Địa chỉ đầy đủ, bắt đầu bằng http:// hoặc https://. Để trống thì nút trên banner là “Xem chi tiết” (dẫn tới trang của sự kiện).', 'isha-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Nổi bật', 'isha-core' ); ?></th>
					<td>
						<label for="isha_event_featured">
							<input type="checkbox" id="isha_event_featured" name="isha_event_featured" value="1" <?php checked( $data['featured'] ); ?> aria-describedby="isha_event_featured_desc">
							<?php esc_html_e( 'Sự kiện nổi bật', 'isha-core' ); ?>
						</label>
						<p class="description" id="isha_event_featured_desc"><?php esc_html_e( 'Chỉ sự kiện nổi bật mới được đưa vào banner sự kiện trên trang chủ.', 'isha-core' ); ?></p>
					</td>
				</tr>
				<?php if ( class_exists( 'ISHA_Core_Admin_Media' ) ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Ảnh dọc cho điện thoại', 'isha-core' ); ?></th>
						<td>
							<?php
							ISHA_Core_Admin_Media::render(
								array(
									'name'  => 'isha_event_portrait_id',
									'type'  => 'image',
									'value' => $data['portrait_id'],
									'label' => __( 'Ảnh dọc cho điện thoại (4:5)', 'isha-core' ),
									'help'  => __( 'Ảnh tỉ lệ 4:5 (ví dụ 1080 × 1350 px) dùng cho màn hình điện thoại. Ảnh ngang 2.39:1 đặt ở “Ảnh đại diện”. Để trống thì điện thoại dùng ảnh ngang.', 'isha-core' ),
									'id'    => 'isha_event_portrait',
								)
							);
							?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Chuỗi Y-m-d H:i đã lưu thành giá trị của ô datetime-local (Y-m-d\TH:i); vẫn theo múi giờ của site.
	 *
	 * @param string $local Chuỗi Y-m-d H:i hoặc ''.
	 * @return string
	 */
	private static function to_input_value( $local ) {
		return str_replace( ' ', 'T', $local );
	}

	/* --- Lưu -------------------------------------------------------------- */

	/**
	 * Lưu các trường (hook save_post_isha_event). Bỏ qua khi thiếu hoặc sai nonce, autosave, revision hoặc không có quyền sửa bài.
	 * Ô để trống thì xoá meta tương ứng; riêng ảnh dọc và nội dung ngắn chỉ đổi khi ô có trong dữ liệu gửi lên.
	 *
	 * @param int $post_id ID sự kiện.
	 */
	public static function save( $post_id ) {
		if ( ! self::can_save( $post_id ) ) {
			return;
		}

		$times = self::sanitize_times(
			isset( $_POST['isha_event_start'] ) ? sanitize_text_field( wp_unslash( $_POST['isha_event_start'] ) ) : '',
			isset( $_POST['isha_event_end'] ) ? sanitize_text_field( wp_unslash( $_POST['isha_event_end'] ) ) : ''
		);
		self::set_meta( $post_id, self::META_START, $times['start'] );
		self::set_meta( $post_id, self::META_START_TS, $times['start_ts'] );
		self::set_meta( $post_id, self::META_END, $times['end'] );
		self::set_meta( $post_id, self::META_END_TS, $times['end_ts'] );

		$location = isset( $_POST['isha_event_location'] ) ? sanitize_text_field( wp_unslash( $_POST['isha_event_location'] ) ) : '';
		self::set_meta( $post_id, self::META_LOCATION, self::clean_location( $location ) );

		// sanitize_url() kiểm tra chặt (phải có http:// hoặc https://) rồi mới gọi esc_url_raw(), nên không gọi esc_url_raw() trước.
		// wpstyle:ignore superglobal-input
		$url = isset( $_POST['isha_event_register_url'] ) ? self::sanitize_url( wp_unslash( $_POST['isha_event_register_url'] ) ) : '';
		self::set_meta( $post_id, self::META_REGISTER_URL, $url );

		self::set_meta( $post_id, self::META_FEATURED, isset( $_POST['isha_event_featured'] ) ? '1' : '' );

		if ( isset( $_POST['isha_event_portrait_id'] ) && class_exists( 'ISHA_Core_Admin_Media' ) ) {
			self::set_meta( $post_id, self::META_PORTRAIT, ISHA_Core_Admin_Media::sanitize( 'image', sanitize_text_field( wp_unslash( $_POST['isha_event_portrait_id'] ) ) ) );
		}

		// Nội dung ngắn: thiếu khoá trong dữ liệu gửi lên thì giữ giá trị cũ; có khoá mà rỗng thì xoá meta.
		if ( isset( $_POST['isha_event_summary'] ) ) {
			self::set_meta( $post_id, self::META_SUMMARY, self::clean_summary( sanitize_textarea_field( wp_unslash( $_POST['isha_event_summary'] ) ) ) );
		}
	}

	/**
	 * Kiểm tra nonce, autosave, revision và quyền sửa bài.
	 *
	 * @param int $post_id ID bài.
	 * @return bool
	 */
	private static function can_save( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return false;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return false;
		}
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Ghi một meta; giá trị rỗng ('' hoặc 0) thì xoá meta cho gọn. Slash trước khi lưu vì core tự unslash.
	 *
	 * @param int        $post_id ID bài.
	 * @param string     $key     Khoá meta.
	 * @param string|int $value   Giá trị.
	 */
	private static function set_meta( $post_id, $key, $value ) {
		if ( '' === $value || 0 === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}
		update_post_meta( $post_id, $key, wp_slash( $value ) );
	}

	/* --- Asset ------------------------------------------------------------ */

	/**
	 * Nạp ô chọn media, chỉ ở màn hình thêm/sửa sự kiện.
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

	/* --- Cột trong danh sách ---------------------------------------------- */

	/**
	 * Thêm cột Bắt đầu và Nổi bật ngay sau cột tiêu đề.
	 *
	 * @param array $columns Các cột mặc định.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['isha_event_start']    = __( 'Bắt đầu', 'isha-core' );
				$new['isha_event_featured'] = __( 'Nổi bật', 'isha-core' );
			}
		}
		if ( ! isset( $new['isha_event_start'] ) ) {
			$new['isha_event_start']    = __( 'Bắt đầu', 'isha-core' );
			$new['isha_event_featured'] = __( 'Nổi bật', 'isha-core' );
		}
		return $new;
	}

	/**
	 * Nội dung cột. Chỉ đọc meta (đã được nạp sẵn cho cả trang danh sách), không truy vấn thêm theo từng dòng.
	 *
	 * @param string $column  Khoá cột.
	 * @param int    $post_id ID sự kiện.
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'isha_event_start' === $column ) {
			$ts = self::clean_ts( self::meta( $post_id, self::META_START_TS ) );
			if ( $ts ) {
				echo esc_html( wp_date( 'd/m/Y H:i', $ts ) );
			} else {
				echo '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Chưa đặt', 'isha-core' ) . '</span>';
			}
		} elseif ( 'isha_event_featured' === $column ) {
			if ( '1' === self::meta( $post_id, self::META_FEATURED ) ) {
				echo esc_html__( 'Có', 'isha-core' );
			} else {
				echo '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Không', 'isha-core' ) . '</span>';
			}
		}
	}
}
