<?php
/**
 * Ô chọn media dùng chung cho trang quản trị: ảnh đơn, video và gallery (nhiều ảnh có thứ tự).
 *
 * Mỗi ô là một div.isha-media gồm: một input hidden mang giá trị (ID tệp đính kèm; với gallery là chuỗi ID cách nhau
 * dấu phẩy theo thứ tự), phần xem trước, nút chọn/đổi và nút xoá. Việc chọn chạy ở assets/js/admin-media.js (khung
 * wp.media), kiểu dáng ở assets/css/admin-media.css. Gallery đổi thứ tự bằng nút Trước/Sau nên dùng được bằng bàn phím,
 * không phụ thuộc kéo thả.
 *
 * Cách dùng: gọi ISHA_Core_Admin_Media::enqueue() ở `admin_enqueue_scripts` của màn hình có ô chọn, rồi gọi
 * ISHA_Core_Admin_Media::render() để in ô; khi lưu dùng ISHA_Core_Admin_Media::sanitize() cho giá trị nhận về.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Admin_Media {

	/**
	 * Số ảnh tối đa trong một gallery.
	 */
	const MAX_GALLERY = 24;

	/**
	 * Đã nạp asset trong request này chưa.
	 *
	 * @var bool
	 */
	private static $enqueued = false;

	/**
	 * Bộ đếm để sinh id DOM khi người gọi không truyền `id`.
	 *
	 * @var int
	 */
	private static $counter = 0;

	/* --- Asset ------------------------------------------------------------- */

	/**
	 * Nạp thư viện media của WordPress cùng JS và CSS của ô chọn. Gọi nhiều lần vẫn chỉ nạp một lần.
	 *
	 * Gọi ở `admin_enqueue_scripts` (hoặc muộn hơn) của màn hình có ô chọn: chuỗi dịch chỉ dùng được sau `init`.
	 */
	public static function enqueue() {
		if ( self::$enqueued ) {
			return;
		}
		self::$enqueued = true;

		wp_enqueue_media();

		wp_enqueue_style( 'isha-admin-media', ISHA_CORE_URL . 'assets/css/admin-media.css', array(), self::asset_version( 'assets/css/admin-media.css' ) );
		wp_enqueue_script( 'isha-admin-media', ISHA_CORE_URL . 'assets/js/admin-media.js', array( 'media-editor' ), self::asset_version( 'assets/js/admin-media.js' ), true );
		wp_localize_script( 'isha-admin-media', 'ishaAdminMedia', self::strings() );
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

	/**
	 * Mọi chuỗi giao diện của ô chọn. Dùng cho cả HTML dựng sẵn ở server lẫn JS (qua wp_localize_script).
	 * Chỉ gọi sau `init` (không gọi __() lúc nạp file).
	 *
	 * @return array<string,string>
	 */
	private static function strings() {
		return array(
			'chooseImage'        => __( 'Chọn ảnh', 'isha-core' ),
			'changeImage'        => __( 'Đổi ảnh', 'isha-core' ),
			'chooseVideo'        => __( 'Chọn video', 'isha-core' ),
			'changeVideo'        => __( 'Đổi video', 'isha-core' ),
			'addImages'          => __( 'Thêm ảnh', 'isha-core' ),
			'clearImage'         => __( 'Xoá ảnh', 'isha-core' ),
			'clearVideo'         => __( 'Xoá video', 'isha-core' ),
			'clearAll'           => __( 'Xoá tất cả', 'isha-core' ),
			'prev'               => __( 'Trước', 'isha-core' ),
			'next'               => __( 'Sau', 'isha-core' ),
			'remove'             => __( 'Xoá', 'isha-core' ),
			/* translators: %d: vị trí của ảnh trong gallery, bắt đầu từ 1 */
			'moveUpLabel'        => __( 'Chuyển ảnh %d lên trước', 'isha-core' ),
			/* translators: %d: vị trí của ảnh trong gallery, bắt đầu từ 1 */
			'moveDownLabel'      => __( 'Chuyển ảnh %d ra sau', 'isha-core' ),
			/* translators: %d: vị trí của ảnh trong gallery, bắt đầu từ 1 */
			'removeLabel'        => __( 'Xoá ảnh %d', 'isha-core' ),
			'none'               => __( 'Chưa chọn', 'isha-core' ),
			'frameTitleImage'    => __( 'Chọn ảnh', 'isha-core' ),
			'frameTitleVideo'    => __( 'Chọn video', 'isha-core' ),
			'frameTitleGallery'  => __( 'Chọn các ảnh', 'isha-core' ),
			'frameButtonImage'   => __( 'Dùng ảnh này', 'isha-core' ),
			'frameButtonVideo'   => __( 'Dùng video này', 'isha-core' ),
			'frameButtonGallery' => __( 'Dùng các ảnh đã chọn', 'isha-core' ),
			/* translators: 1: vị trí mới của ảnh, 2: tổng số ảnh */
			'moved'              => __( 'Ảnh đang ở vị trí %1$d trên %2$d.', 'isha-core' ),
			/* translators: %d: vị trí của ảnh vừa xoá */
			'removed'            => __( 'Đã xoá ảnh %d.', 'isha-core' ),
			/* translators: %s: tên tệp vừa chọn */
			'chosen'             => __( 'Đã chọn: %s', 'isha-core' ),
			/* translators: %d: số ảnh trong gallery */
			'galleryCount'       => __( 'Danh sách có %d ảnh.', 'isha-core' ),
			/* translators: %d: số ảnh tối đa */
			'limit'              => __( 'Chỉ giữ tối đa %d ảnh, các ảnh còn lại đã bị bỏ qua.', 'isha-core' ),
			'unavailable'        => __( 'Không mở được thư viện media. Hãy tải lại trang rồi thử lại.', 'isha-core' ),
		);
	}

	/* --- Hiển thị ---------------------------------------------------------- */

	/**
	 * In một ô chọn media (HTML đã escape).
	 *
	 * Ô là một nhóm (role="group") có tên truy cập lấy từ `label`; đừng trỏ thẻ <label for> vào nút chọn vì nhãn sẽ che
	 * mất chữ "Chọn ảnh" của nút. Nếu thiếu `name` thì không in gì và báo _doing_it_wrong.
	 *
	 * @param array $args {
	 *     Tham số.
	 *
	 *     @type string           $name  Tên input đầy đủ (bắt buộc), ví dụ `isha_home[hero][video_landscape]`.
	 *     @type string           $type  `image` (mặc định), `video` hoặc `gallery`.
	 *     @type int|int[]|string $value ID tệp đính kèm; gallery nhận mảng ID hoặc chuỗi CSV. ID không hợp lệ bị bỏ.
	 *     @type string           $label Nhãn: dùng làm aria-label của ô và tiêu đề khung chọn.
	 *     @type string           $help  Dòng chú thích (văn bản thường, tuỳ chọn).
	 *     @type string           $id    Gốc id DOM (tuỳ chọn). Ô có id này; input, xem trước, nút chọn và chú thích có hậu tố
	 *                                   `-input`, `-preview`, `-choose`, `-help`. Bỏ trống thì tự sinh.
	 *     @type int              $max   Chỉ gallery: số ảnh tối đa, kẹp trong 1..MAX_GALLERY (mặc định MAX_GALLERY).
	 * }
	 */
	public static function render( array $args ) {
		$args = array_merge(
			array(
				'name'  => '',
				'type'  => 'image',
				'value' => '',
				'label' => '',
				'help'  => '',
				'id'    => '',
				'max'   => self::MAX_GALLERY,
			),
			$args
		);

		$name = is_scalar( $args['name'] ) ? (string) $args['name'] : '';
		if ( '' === $name ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'Thiếu tham số "name" của ô chọn media.', 'isha-core' ), ISHA_CORE_VERSION );
			return;
		}

		$type = is_string( $args['type'] ) ? $args['type'] : '';
		if ( ! in_array( $type, array( 'image', 'video', 'gallery' ), true ) ) {
			$type = 'image';
		}
		$is_gallery = 'gallery' === $type;

		$label = is_scalar( $args['label'] ) ? trim( (string) $args['label'] ) : '';
		$help  = is_scalar( $args['help'] ) ? trim( (string) $args['help'] ) : '';

		$dom_id = is_scalar( $args['id'] ) ? sanitize_html_class( (string) $args['id'] ) : '';
		if ( '' === $dom_id ) {
			$dom_id = 'isha-media-' . ( ++self::$counter );
		}

		// Chỉ hiển thị ID còn hợp lệ: tệp đã bị xoá khỏi thư viện thì ô hiện "Chưa chọn" và không giữ ID cũ.
		$ids = array();
		$id  = 0;
		$max = self::clamp_max( $args['max'] );
		if ( $is_gallery ) {
			$ids   = array_slice( self::sanitize( 'gallery', $args['value'] ), 0, $max );
			$value = implode( ',', $ids );
		} else {
			$id    = self::sanitize( $type, $args['value'] );
			$value = $id ? (string) $id : '';
		}
		$has_value = '' !== $value;

		$s = self::strings();
		if ( $is_gallery ) {
			$choose_text = $s['addImages'];
			$clear_text  = $s['clearAll'];
		} elseif ( 'video' === $type ) {
			$choose_text = $has_value ? $s['changeVideo'] : $s['chooseVideo'];
			$clear_text  = $s['clearVideo'];
		} else {
			$choose_text = $has_value ? $s['changeImage'] : $s['chooseImage'];
			$clear_text  = $s['clearImage'];
		}

		$attrs = array(
			'class'         => 'isha-media isha-media--' . $type,
			'id'            => $dom_id,
			'role'          => 'group',
			'data-type'     => $type,
			'data-multiple' => $is_gallery ? '1' : '0',
			'data-title'    => '' !== $label ? $label : $s[ 'frameTitle' . ucfirst( $type ) ],
			'data-button'   => $s[ 'frameButton' . ucfirst( $type ) ],
		);
		if ( $is_gallery ) {
			$attrs['data-max'] = (string) $max;
		}
		if ( '' !== $label ) {
			$attrs['aria-label'] = $label;
		}
		if ( '' !== $help ) {
			$attrs['aria-describedby'] = $dom_id . '-help';
		}

		echo '<div' . self::attrs( $attrs ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attrs() đã escape từng giá trị.

		echo '<input type="hidden" class="isha-media__input" id="' . esc_attr( $dom_id . '-input' ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';

		echo '<div class="isha-media__preview" id="' . esc_attr( $dom_id . '-preview' ) . '">';
		if ( $is_gallery ) {
			self::render_gallery_preview( $ids, $s );
		} else {
			self::render_single_preview( $type, $id, $s );
		}
		echo '</div>';

		echo '<div class="isha-media__controls">';
		echo '<button type="button" class="button isha-media__choose" id="' . esc_attr( $dom_id . '-choose' ) . '">' . esc_html( $choose_text ) . '</button> ';
		echo '<button type="button" class="button-link button-link-delete isha-media__clear"' . ( $has_value ? '' : ' hidden' ) . '>' . esc_html( $clear_text ) . '</button>';
		echo '</div>';

		// Vùng thông báo cho trình đọc màn hình (JS ghi vào: đổi thứ tự, xoá, vượt giới hạn...).
		echo '<span class="isha-media__status" role="status" aria-live="polite"></span>';

		if ( '' !== $help ) {
			echo '<p class="description isha-media__help" id="' . esc_attr( $dom_id . '-help' ) . '">' . esc_html( $help ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Xem trước của ô ảnh đơn hoặc video.
	 *
	 * @param string $type image|video.
	 * @param int    $id   ID tệp đính kèm đã hợp lệ, 0 nếu chưa chọn.
	 * @param array  $s    Bảng chuỗi giao diện.
	 */
	private static function render_single_preview( $type, $id, array $s ) {
		$post = $id ? get_post( $id ) : null;
		if ( ! $post ) {
			echo '<p class="isha-media__empty">' . esc_html( $s['none'] ) . '</p>';
			return;
		}

		$name = self::attachment_name( $post );

		if ( 'video' === $type ) {
			$url = wp_get_attachment_url( $post->ID );
			echo '<div class="isha-media__thumb isha-media__thumb--video">';
			if ( $url ) {
				echo '<video src="' . esc_url( $url ) . '" muted preload="metadata" playsinline controls aria-label="' . esc_attr( $name ) . '"></video>';
			}
			echo '</div>';
		} else {
			// Ảnh chỉ để nhận diện bằng mắt; tên tệp bên cạnh là phần chữ cho trình đọc màn hình. wp_get_attachment_image() đã escape.
			echo '<div class="isha-media__thumb">' . wp_get_attachment_image( $post->ID, 'thumbnail', false, self::thumb_attr() ) . '</div>';
		}

		echo '<div class="isha-media__info">';
		echo '<span class="isha-media__name">' . esc_html( $name ) . '</span>';
		echo '<span class="isha-media__mime">' . esc_html( (string) get_post_mime_type( $post ) ) . '</span>';
		echo '</div>';
	}

	/**
	 * Xem trước của gallery: dòng "Chưa chọn" (ẩn khi có ảnh) và danh sách ảnh.
	 *
	 * @param int[] $ids ID ảnh đã hợp lệ, theo thứ tự.
	 * @param array $s   Bảng chuỗi giao diện.
	 */
	private static function render_gallery_preview( array $ids, array $s ) {
		$total = count( $ids );

		echo '<p class="isha-media__empty"' . ( $total ? ' hidden' : '' ) . '>' . esc_html( $s['none'] ) . '</p>';
		echo '<ul class="isha-media__list" role="list"' . ( $total ? '' : ' hidden' ) . '>';
		foreach ( array_values( $ids ) as $index => $id ) {
			self::render_gallery_item( (int) $id, $index + 1, $total, $s );
		}
		echo '</ul>';
	}

	/**
	 * Một thẻ ảnh trong gallery, có nút Trước, Sau và Xoá. Cấu trúc phải khớp với JS (buildItem trong admin-media.js).
	 *
	 * @param int   $id    ID ảnh.
	 * @param int   $pos   Vị trí, bắt đầu từ 1.
	 * @param int   $total Tổng số ảnh.
	 * @param array $s     Bảng chuỗi giao diện.
	 */
	private static function render_gallery_item( $id, $pos, $total, array $s ) {
		$post = get_post( $id );
		$name = $post ? self::attachment_name( $post ) : '';

		echo '<li class="isha-media__item" data-id="' . esc_attr( (string) $id ) . '">';
		echo '<span class="isha-media__pos" aria-hidden="true">' . esc_html( (string) $pos ) . '</span>';
		echo '<div class="isha-media__thumb">' . wp_get_attachment_image( $id, 'thumbnail', false, self::thumb_attr() ) . '</div>'; // Core đã escape.
		echo '<span class="isha-media__name" title="' . esc_attr( $name ) . '">' . esc_html( $name ) . '</span>';

		echo '<div class="isha-media__actions">';
		echo '<button type="button" class="button button-small isha-media__move" data-dir="-1" aria-label="' . esc_attr( sprintf( $s['moveUpLabel'], $pos ) ) . '"' . ( 1 === $pos ? ' disabled' : '' ) . '>' . esc_html( $s['prev'] ) . '</button>';
		echo '<button type="button" class="button button-small isha-media__move" data-dir="1" aria-label="' . esc_attr( sprintf( $s['moveDownLabel'], $pos ) ) . '"' . ( $pos === $total ? ' disabled' : '' ) . '>' . esc_html( $s['next'] ) . '</button>';
		echo '<button type="button" class="button-link button-link-delete isha-media__remove" aria-label="' . esc_attr( sprintf( $s['removeLabel'], $pos ) ) . '">' . esc_html( $s['remove'] ) . '</button>';
		echo '</div>';
		echo '</li>';
	}

	/**
	 * Tên để hiển thị của tệp đính kèm: tiêu đề, nếu trống thì tên tệp. Giá trị thô, người gọi phải escape.
	 *
	 * @param WP_Post $post Tệp đính kèm.
	 * @return string
	 */
	private static function attachment_name( $post ) {
		$title = trim( (string) $post->post_title );
		if ( '' !== $title ) {
			return $title;
		}
		return wp_basename( (string) get_attached_file( $post->ID ) );
	}

	/**
	 * Thuộc tính cho ảnh xem trước: alt rỗng (tên tệp bên cạnh là phần chữ) và lazy-load để ảnh trong các thẻ đang ẩn
	 * của meta box trang chủ không bị tải sớm.
	 *
	 * @return array<string,string>
	 */
	private static function thumb_attr() {
		return array(
			'alt'     => '',
			'loading' => 'lazy',
		);
	}

	/**
	 * Chuỗi thuộc tính HTML (đã escape cả tên lẫn giá trị), mỗi thuộc tính bắt đầu bằng một dấu cách.
	 *
	 * @param array<string,string> $attrs Thuộc tính.
	 * @return string
	 */
	private static function attrs( array $attrs ) {
		$out = '';
		foreach ( $attrs as $key => $value ) {
			$out .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}
		return $out;
	}

	/**
	 * Kẹp số ảnh tối đa của gallery vào 1..MAX_GALLERY; giá trị không phải số thì dùng MAX_GALLERY.
	 *
	 * @param mixed $raw Giá trị người gọi truyền.
	 * @return int
	 */
	private static function clamp_max( $raw ) {
		$max = is_numeric( $raw ) ? (int) $raw : self::MAX_GALLERY;
		return max( 1, min( self::MAX_GALLERY, $max ) );
	}

	/* --- Kiểm tra và làm sạch --------------------------------------------- */

	/**
	 * Làm sạch giá trị của một ô chọn.
	 *
	 * @param string $type image|video|gallery.
	 * @param mixed  $raw  Giá trị nhận về (ID, chuỗi CSV hoặc mảng). Đã bỏ slash ở nơi gọi.
	 * @return int|int[] image/video: ID hợp lệ hoặc 0; gallery: mảng ID ảnh hợp lệ (bỏ trùng, giữ thứ tự, tối đa MAX_GALLERY).
	 */
	public static function sanitize( $type, $raw ) {
		if ( 'gallery' === $type ) {
			return self::sanitize_gallery( $raw );
		}

		$id = self::to_id( $raw );
		return ( $id && self::is_valid( $type, $id ) ) ? $id : 0;
	}

	/**
	 * Kiểm tra một ID có phải tệp đính kèm hợp lệ cho kiểu đã cho.
	 *
	 * Phải là bài có post_type `attachment`. `image` và `gallery` (kiểm từng phần tử): tệp là ảnh; `video`: mime bắt đầu
	 * bằng `video/`. Chỉ chấp nhận số nguyên dương hoặc chuỗi toàn chữ số; âm, số thực, bool, mảng, chuỗi lẫn chữ đều sai.
	 *
	 * @param string $type image|video|gallery.
	 * @param mixed  $id   ID cần kiểm.
	 * @return bool
	 */
	public static function is_valid( $type, $id ) {
		$id = self::to_id( $id );
		if ( $id < 1 || ! is_string( $type ) ) {
			return false;
		}

		$post = get_post( $id );
		if ( ! $post || 'attachment' !== $post->post_type ) {
			return false;
		}

		switch ( $type ) {
			case 'image':
			case 'gallery':
				return (bool) wp_attachment_is_image( $post );
			case 'video':
				return 0 === strpos( strtolower( (string) get_post_mime_type( $post ) ), 'video/' );
		}
		return false;
	}

	/**
	 * Làm sạch giá trị gallery: chấp nhận chuỗi CSV, mảng hoặc một số nguyên.
	 *
	 * @param mixed $raw Giá trị nhận về.
	 * @return int[]
	 */
	private static function sanitize_gallery( $raw ) {
		if ( is_int( $raw ) ) {
			$raw = (string) $raw;
		}
		if ( is_string( $raw ) ) {
			$raw = explode( ',', $raw );
		}
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$ids  = array();
		$seen = array();

		// Chỉ xét một số phần tử đầu để một danh sách khổng lồ toàn ID sai không kéo theo hàng nghìn truy vấn.
		foreach ( array_slice( $raw, 0, self::MAX_GALLERY * 8 ) as $item ) {
			$id = self::to_id( $item );
			if ( ! $id || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;

			if ( self::is_valid( 'image', $id ) ) {
				$ids[] = $id;
				if ( count( $ids ) >= self::MAX_GALLERY ) {
					break;
				}
			}
		}
		return $ids;
	}

	/**
	 * Đổi giá trị thô sang ID: chỉ nhận số nguyên dương hoặc chuỗi toàn chữ số (cho phép khoảng trắng hai đầu).
	 * Không dùng absint() vì nó đổi -5 thành 5, true thành 1 và "12abc" thành 12.
	 *
	 * @param mixed $raw Giá trị thô.
	 * @return int 0 nếu không hợp lệ.
	 */
	private static function to_id( $raw ) {
		if ( is_int( $raw ) ) {
			return $raw > 0 ? $raw : 0;
		}
		if ( is_string( $raw ) ) {
			$raw = trim( $raw );
			return preg_match( '/^[0-9]{1,18}$/D', $raw ) ? (int) $raw : 0;
		}
		return 0;
	}
}
