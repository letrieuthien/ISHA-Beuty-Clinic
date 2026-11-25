<?php
/**
 * Chân trang 3 tầng: dữ liệu và hàm hỗ trợ cho footer.php (docs/footer.md, docs/home-architecture.md mục 27).
 *
 * Nguồn dữ liệu liên hệ là option của plugin ISHA Core (isha_opt()): phone, email, address, hours, facebook, instagram, zalo.
 * Mọi hàm trả giá trị thô (chưa escape); footer.php escape khi in.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trang này đã in khối CTA đặt lịch riêng chưa? Gọi isha_cta_printed( true ) để đánh dấu (isha_cta_section() làm việc này).
 *
 * @param bool $mark True để đánh dấu đã in.
 * @return bool
 */
function isha_cta_printed( $mark = false ) {
	static $printed = false;
	if ( $mark ) {
		$printed = true;
	}
	return $printed;
}

/**
 * Hiện Tầng 1 (dải mời đặt lịch) của footer? Không hiện khi:
 * - chế độ landing (không có nút đặt lịch);
 * - trang đã in một khối CTA đặt lịch ngay trên footer: cảnh kết điện ảnh (template-parts/home/cta.php: trang chủ, Giới thiệu,
 *   Không gian, Dịch vụ...) hoặc khối CTA cũ (isha_cta_section());
 * - chính trang Đặt lịch hẹn.
 * Tránh hai khối CTA đặt lịch liền nhau.
 *
 * @return bool
 */
function isha_footer_invite_visible() {
	if ( isha_landing_only() || isha_cta_printed() ) {
		return false;
	}
	if ( is_page_template( 'templates/template-booking.php' ) ) {
		return false;
	}
	return true;
}

/**
 * Hotline: chữ hiển thị có khoảng cách dễ đọc và số cho liên kết tel:.
 * Số di động 10 chữ số liền (0947683768) tách thành 4-3-3; giá trị đã có dấu cách, chấm, gạch thì giữ nguyên cách nhập.
 *
 * @return array array( 'text' => ..., 'tel' => ... ) hoặc array() nếu chưa nhập.
 */
function isha_footer_phone() {
	$raw = trim( (string) isha_opt( 'phone' ) );
	$tel = preg_replace( '/[^0-9+]/', '', $raw );
	if ( '' === $tel ) {
		return array();
	}
	$text = $raw;
	if ( preg_match( '/^0\d{9}$/', $raw ) ) {
		$text = substr( $raw, 0, 4 ) . ' ' . substr( $raw, 4, 3 ) . ' ' . substr( $raw, 7 );
	}
	return array(
		'text' => $text,
		'tel'  => $tel,
	);
}

/**
 * Email hiển thị chữ thường (không đổi dữ liệu đã lưu).
 *
 * @return string '' nếu chưa nhập hoặc không hợp lệ.
 */
function isha_footer_email() {
	$email = strtolower( trim( (string) isha_opt( 'email' ) ) );
	return is_email( $email ) ? $email : '';
}

/**
 * Liên kết "Chỉ đường" của Google Maps, dựng từ địa chỉ. `map_url` của ISHA Core là địa chỉ NHÚNG (output=embed), không mở được
 * như liên kết chỉ đường nên không dùng ở đây.
 *
 * @return string '' nếu chưa có địa chỉ.
 */
function isha_footer_directions_url() {
	$address = trim( (string) isha_opt( 'address' ) );
	if ( '' === $address ) {
		return '';
	}
	$address = preg_replace( '/\s*[\r\n]+\s*/', ', ', $address );
	return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $address );
}

/**
 * Giờ làm việc: mỗi dòng của ô "Giờ làm việc" thành nhãn (ngày) và giờ. Tách tại khung giờ đầu tiên có dạng 09:00 hoặc 9h00;
 * dấu gạch nối quanh khung giờ đổi thành gạch ngang dài. Dòng không có khung giờ thì cả dòng là nhãn.
 *
 * @return array[] Mỗi phần tử array( 'label' => ..., 'time' => ... ).
 */
function isha_footer_hours() {
	$rows = array();
	foreach ( preg_split( '/\R/u', (string) isha_opt( 'hours' ) ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$label = $line;
		$time  = '';
		if ( preg_match( '/^(.*?)[\s:：]*((?<!\d)\d{1,2}[:hH]\d{2}.*)$/u', $line, $m ) ) {
			$label = trim( $m[1] );
			$time  = trim( preg_replace( '/\s*[-–—]\s*/u', ' – ', $m[2] ) );
		}
		$label  = trim( preg_replace( '/\s+-\s+/u', ' – ', $label ) );
		$rows[] = array(
			'label' => $label,
			'time'  => $time,
		);
	}
	return $rows;
}

/**
 * URL của một trang đã xuất bản theo đường dẫn (slug), '' nếu không có.
 *
 * @param string $path Đường dẫn trang, vd: chinh-sach-khach-hang.
 * @return string
 */
function isha_footer_page_url( $path ) {
	$page = get_page_by_path( $path, OBJECT, 'page' );
	if ( ! ( $page instanceof WP_Post ) || 'publish' !== $page->post_status ) {
		return '';
	}
	$url = get_permalink( $page );
	return is_string( $url ) ? $url : '';
}

/**
 * Menu dự phòng của cột Khám phá, dùng khi chưa gán menu cho vị trí "Menu chân trang". Bỏ mục nào chưa có trang đích.
 *
 * @return array[] Mỗi phần tử array( 'label' => ..., 'url' => ... ).
 */
function isha_footer_menu_items() {
	$home  = untrailingslashit( home_url( '/' ) );
	$items = array(
		array( __( 'Giới thiệu', 'isha' ), isha_page_url_by_template( 'templates/template-about.php' ) ),
		array( __( 'Không gian ISHA', 'isha' ), isha_page_url_by_template( 'templates/template-space.php' ) ),
		array( __( 'Dịch vụ', 'isha' ), isha_home_data_services_url() ),
		array( __( 'Workshop & Sự kiện', 'isha' ), isha_home_data_events_url() ),
		array( __( 'Tin tức', 'isha' ), isha_home_data_news_url() ),
		array( __( 'Liên hệ', 'isha' ), isha_page_url_by_template( 'templates/template-contact.php' ) ),
	);
	$out   = array();
	foreach ( $items as $item ) {
		// isha_page_url_by_template() trả về trang chủ khi chưa có trang dùng mẫu đó.
		if ( '' === $item[1] || untrailingslashit( $item[1] ) === $home ) {
			continue;
		}
		$out[] = array(
			'label' => $item[0],
			'url'   => $item[1],
		);
	}
	return $out;
}

/**
 * Liên kết pháp lý ở bottom bar: Chính sách khách hàng, Chính sách bảo mật (chỉ những trang đã có).
 *
 * @return array[] Mỗi phần tử array( 'label' => ..., 'url' => ... ).
 */
function isha_footer_legal_links() {
	$home     = untrailingslashit( home_url( '/' ) );
	$customer = isha_footer_page_url( 'chinh-sach-khach-hang' );
	if ( '' === $customer ) {
		$policies = isha_policies_url();
		$customer = untrailingslashit( $policies ) === $home ? '' : $policies;
	}
	$privacy = (string) get_privacy_policy_url();
	if ( '' === $privacy ) {
		$privacy = isha_footer_page_url( 'chinh-sach-bao-mat' );
	}
	$links = array();
	if ( '' !== $customer ) {
		$links[] = array(
			'label' => __( 'Chính sách khách hàng', 'isha' ),
			'url'   => $customer,
		);
	}
	if ( '' !== $privacy ) {
		$links[] = array(
			'label' => __( 'Chính sách bảo mật', 'isha' ),
			'url'   => $privacy,
		);
	}
	return $links;
}
