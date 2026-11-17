<?php
/**
 * Chuyển hướng 301 cho đường dẫn cũ của Tin tức (18/11/2026, docs/home-architecture.md mục 18).
 *
 * Trước ngày này bài Tin tức (isha_article) nằm ở /bai-viet/ten-bai/ và có trang lưu trữ /bai-viet/. Nay bài nằm dưới trang Tin tức
 * (/tin-tuc/ten-bai/) và danh sách duy nhất là trang Tin tức, nên mọi đường dẫn /bai-viet/… còn trong công cụ tìm kiếm, mạng xã hội hay
 * nội dung cũ được chuyển vĩnh viễn:
 *   /bai-viet/               → trang Tin tức
 *   /bai-viet/page/N/        → trang Tin tức ?trang=N (danh sách phân trang bằng tham số trang, xem inc/editorial-list.php của theme)
 *   /bai-viet/ten-bai/…      → /tin-tuc/ten-bai/ (bài còn xuất bản), không thì trang Tin tức
 * Chỉ chạy khi WordPress không tìm thấy gì ở đường dẫn đó (is_404), nên một Trang mới có slug bai-viet vẫn hiển thị bình thường.
 * Trang lưu trữ chủ đề (/chu-de/…) do theme chuyển về danh sách đã lọc vì tham số lọc ?chuyen-muc thuộc theme.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Legacy_Urls {

	/**
	 * Slug cũ của post type isha_article.
	 */
	const OLD_ARTICLE_BASE = 'bai-viet';

	/**
	 * Gắn hook (gọi một lần từ isha_core_init()).
	 */
	public static function init() {
		// Trước redirect_canonical (ưu tiên 10), vốn tự đoán đường dẫn cho trang 404 và có thể đưa tới chỗ khác.
		add_action( 'template_redirect', array( __CLASS__, 'redirect' ), 1 );
	}

	/**
	 * Chuyển hướng nếu đường dẫn đang xem là đường dẫn cũ.
	 */
	public static function redirect() {
		if ( ! is_404() ) {
			return;
		}
		$target = self::target( self::request_path() );
		if ( '' !== $target ) {
			wp_safe_redirect( $target, 301, 'ISHA Core' );
			exit;
		}
	}

	/**
	 * Đường dẫn yêu cầu, tương đối với thư mục gốc của website, không có dấu gạch chéo hai đầu ('' nếu không đọc được).
	 *
	 * @return string
	 */
	private static function request_path() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- chỉ so khớp đường dẫn, không in ra.
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( '' !== $base && 0 === strpos( $path, $base ) ) {
			$path = substr( $path, strlen( $base ) );
		}
		return trim( rawurldecode( $path ), '/' );
	}

	/**
	 * Đích chuyển hướng của một đường dẫn (hàm thuần theo dữ liệu hiện có), '' nếu không phải đường dẫn cũ.
	 *
	 * @param string $path Đường dẫn tương đối, không có dấu gạch chéo hai đầu.
	 * @return string
	 */
	public static function target( $path ) {
		$base = self::OLD_ARTICLE_BASE;
		if ( $path !== $base && 0 !== strpos( $path, $base . '/' ) ) {
			return '';
		}
		$news = self::news_url();
		$rest = trim( substr( $path, strlen( $base ) ), '/' );
		if ( '' === $rest || 'feed' === $rest ) {
			return $news;
		}
		if ( preg_match( '#^page/(\d+)$#', $rest, $m ) ) {
			$page = (int) $m[1];
			return $page > 1 ? add_query_arg( 'trang', $page, $news ) : $news;
		}

		// Bài cũ: phần đầu là slug (phần sau như /amp/, /feed/ hay số trang thì bỏ).
		$parts = explode( '/', $rest );
		$slug  = sanitize_title( $parts[0] );
		if ( '' !== $slug ) {
			$found = get_posts(
				array(
					'post_type'      => 'isha_article',
					'post_status'    => 'publish',
					'name'           => $slug,
					'posts_per_page' => 1,
					'no_found_rows'  => true,
				)
			);
			if ( $found ) {
				return (string) get_permalink( $found[0] );
			}
		}
		return $news;
	}

	/**
	 * Trang Tin tức: "Trang bài viết" ở Cài đặt › Đọc, không có thì Trang slug tin-tuc, không có nữa thì trang chủ.
	 *
	 * @return string
	 */
	public static function news_url() {
		$page_id = (int) get_option( 'page_for_posts' );
		if ( $page_id < 1 || 'publish' !== get_post_status( $page_id ) ) {
			$page    = get_page_by_path( 'tin-tuc', OBJECT, 'page' );
			$page_id = ( $page instanceof WP_Post && 'publish' === $page->post_status ) ? (int) $page->ID : 0;
		}
		$url = $page_id > 0 ? (string) get_permalink( $page_id ) : home_url( '/' );

		/**
		 * Lọc trang đích của đường dẫn Tin tức cũ.
		 *
		 * @param string $url Đường dẫn trang Tin tức.
		 */
		return (string) apply_filters( 'isha_core/news_url', $url );
	}
}
