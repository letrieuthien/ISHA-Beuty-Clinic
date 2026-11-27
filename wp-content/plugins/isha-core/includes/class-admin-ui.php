<?php
/**
 * Giao diện quản trị (wp-admin) tông ISHA, phong cách liquid glass.
 *
 * - Nạp assets/css/isha-admin.css trên mọi màn hình wp-admin (ưu tiên muộn, sau CSS của wp-admin và của các khung isha-core).
 * - Khai báo @font-face Montserrat từ thư mục fonts của theme ISHA (không tải thêm file). Theme không có thì rơi về chồng font dự phòng
 *   trong CSS. Toàn bộ giao diện admin chỉ dùng Montserrat.
 * - Widget "Tổng quan ISHA" trên Bảng tin: số lịch hẹn mới, đã xác nhận, hoàn thành và thư liên hệ mới.
 * - Huy hiệu trạng thái cho cột Trạng thái của Lịch hẹn và Liên hệ (xem ::badge()).
 *
 * Bộ lọc:
 *   isha_core/admin_ui_enabled (bool, mặc định true): trả false để tắt toàn bộ giao diện này và về wp-admin nguyên bản.
 *   isha_core/admin_font_base (string): URL thư mục fonts có đuôi "/", đổi nơi lấy font.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Admin_UI {

	const STYLE_HANDLE = 'isha-admin';
	const THEME_SLUG   = 'ISHA';

	/**
	 * Đăng ký hook.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ), 99 );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_dashboard_widget' ), 99 ); // Muộn để đứng trước widget của plugin khác (Elementor…).
	}

	/**
	 * Có bật giao diện admin ISHA không (mặc định có).
	 *
	 * @return bool
	 */
	public static function enabled() {
		return (bool) apply_filters( 'isha_core/admin_ui_enabled', true );
	}

	/**
	 * Nạp CSS (và font) của giao diện admin.
	 */
	public static function enqueue() {
		if ( ! self::enabled() ) {
			return;
		}
		$relative = 'assets/css/isha-admin.css';
		$path     = ISHA_CORE_DIR . $relative;
		$version  = file_exists( $path ) ? (string) filemtime( $path ) : ISHA_CORE_VERSION;

		wp_enqueue_style( self::STYLE_HANDLE, ISHA_CORE_URL . $relative, array(), $version );

		$fonts = self::font_face_css();
		if ( '' !== $fonts ) {
			wp_add_inline_style( self::STYLE_HANDLE, $fonts );
		}
	}

	/**
	 * URL thư mục fonts của theme ISHA (có "/" cuối), hoặc chuỗi rỗng nếu theme không có.
	 *
	 * @return string
	 */
	private static function font_base_url() {
		$base = apply_filters( 'isha_core/admin_font_base', '' );
		if ( is_string( $base ) && '' !== $base ) {
			return trailingslashit( $base );
		}
		$theme = wp_get_theme( self::THEME_SLUG );
		if ( ! $theme->exists() ) {
			return '';
		}
		return trailingslashit( $theme->get_stylesheet_directory_uri() ) . 'assets/fonts/';
	}

	/**
	 * Khối @font-face Montserrat giống khai báo trong main.css của theme (cùng tên họ font, cùng file), chỉ khác đường dẫn tuyệt đối.
	 *
	 * @return string
	 */
	private static function font_face_css() {
		$base = self::font_base_url();
		if ( '' === $base ) {
			return '';
		}
		$faces = array(
			array( 'Montserrat', 400, 'normal', 'Montserrat/Montserrat-Regular', 'truetype', 'ttf' ),
			array( 'Montserrat', 500, 'normal', 'Montserrat/Montserrat-Medium', 'truetype', 'ttf' ),
			array( 'Montserrat', 600, 'normal', 'Montserrat/Montserrat-SemiBold', 'truetype', 'ttf' ),
		);
		$css   = '';
		foreach ( $faces as $f ) {
			$url  = esc_url_raw( $base . $f[3] );
			$css .= sprintf(
				'@font-face{font-family:"%1$s";font-weight:%2$d;font-style:%3$s;font-display:swap;src:url("%4$s.woff2") format("woff2"),url("%4$s.%5$s") format("%6$s");}',
				$f[0],
				$f[1],
				$f[2],
				$url,
				$f[5],
				$f[4]
			);
		}
		return $css;
	}

	/**
	 * Huy hiệu trạng thái cho cột danh sách. Khoá lạ vẫn hiện nhãn, dùng kiểu mặc định.
	 *
	 * @param string $key   Khoá trạng thái (new, confirmed, done, cancelled, replied…).
	 * @param string $label Nhãn đã dịch.
	 * @return string HTML đã escape.
	 */
	public static function badge( $key, $label ) {
		return '<span class="isha-status isha-status--' . esc_attr( sanitize_html_class( (string) $key ) ) . '">' . esc_html( $label ) . '</span>';
	}

	/* --- Widget Bảng tin ------------------------------------------------- */

	/**
	 * Thêm widget "Tổng quan ISHA" lên đầu cột chính của Bảng tin.
	 */
	public static function register_dashboard_widget() {
		if ( ! self::enabled() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		wp_add_dashboard_widget( 'isha_overview', __( 'Tổng quan ISHA', 'isha-core' ), array( __CLASS__, 'render_overview' ) );

		// Đưa lên đầu cột chính.
		global $wp_meta_boxes;
		if ( isset( $wp_meta_boxes['dashboard']['normal']['core']['isha_overview'] ) ) {
			$widget = array( 'isha_overview' => $wp_meta_boxes['dashboard']['normal']['core']['isha_overview'] );
			unset( $wp_meta_boxes['dashboard']['normal']['core']['isha_overview'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_meta_boxes['dashboard']['normal']['core'] = $widget + $wp_meta_boxes['dashboard']['normal']['core']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Số bài của post type có _isha_status = $status. Với 'new', bài chưa có meta cũng tính là mới (khớp cách cột Trạng thái hiển thị).
	 *
	 * @param string $post_type Post type.
	 * @param string $status    Khoá trạng thái.
	 * @return int
	 */
	private static function count_status( $post_type, $status ) {
		if ( ! post_type_exists( $post_type ) ) {
			return 0;
		}
		$meta = array(
			array(
				'key'   => '_isha_status',
				'value' => $status,
			),
		);
		if ( 'new' === $status ) {
			$meta = array(
				'relation' => 'OR',
				$meta[0],
				array(
					'key'     => '_isha_status',
					'compare' => 'NOT EXISTS',
				),
			);
		}
		$q = new WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		return (int) $q->found_posts;
	}

	/**
	 * Nội dung widget: bốn thẻ thống kê, mỗi thẻ là liên kết tới danh sách tương ứng.
	 */
	public static function render_overview() {
		$tiles = array(
			array(
				'label' => __( 'Lịch hẹn mới', 'isha-core' ),
				'count' => self::count_status( 'isha_appointment', 'new' ),
				'icon'  => 'dashicons-clock',
				'mod'   => '',
				'type'  => 'isha_appointment',
			),
			array(
				'label' => __( 'Lịch hẹn đã xác nhận', 'isha-core' ),
				'count' => self::count_status( 'isha_appointment', 'confirmed' ),
				'icon'  => 'dashicons-yes-alt',
				'mod'   => ' isha-ov__tile--ok',
				'type'  => 'isha_appointment',
			),
			array(
				'label' => __( 'Thư liên hệ mới', 'isha-core' ),
				'count' => self::count_status( 'isha_contact', 'new' ),
				'icon'  => 'dashicons-email-alt',
				'mod'   => ' isha-ov__tile--gold-ink',
				'type'  => 'isha_contact',
			),
			array(
				'label' => __( 'Lịch hẹn đã hoàn thành', 'isha-core' ),
				'count' => self::count_status( 'isha_appointment', 'done' ),
				'icon'  => 'dashicons-saved',
				'mod'   => ' isha-ov__tile--dawn',
				'type'  => 'isha_appointment',
			),
		);

		echo '<div class="isha-ov">';
		foreach ( $tiles as $tile ) {
			if ( ! post_type_exists( $tile['type'] ) ) {
				continue;
			}
			printf(
				'<a class="isha-ov__tile%1$s" href="%2$s"><span class="isha-ov__icon" aria-hidden="true"><span class="dashicons %3$s"></span></span><span class="isha-ov__num">%4$s</span><span class="isha-ov__label">%5$s</span></a>',
				esc_attr( $tile['mod'] ),
				esc_url( admin_url( 'edit.php?post_type=' . $tile['type'] ) ),
				esc_attr( $tile['icon'] ),
				esc_html( number_format_i18n( $tile['count'] ) ),
				esc_html( $tile['label'] )
			);
		}
		echo '</div>';
	}
}
