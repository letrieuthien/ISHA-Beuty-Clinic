<?php
/**
 * Template tags.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logo (custom logo hoặc tên site).
 */
function isha_logo() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	printf(
		'<a class="site-title" href="%s" rel="home">%s</a>',
		esc_url( home_url( '/' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}

/**
 * Ngày đăng + tác giả.
 */
function isha_posted_on() {
	printf(
		'<time class="entry-date" datetime="%1$s">%2$s</time>',
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() )
	);
}

/**
 * Banner tiêu đề trang con.
 *
 * @param string $title    Tiêu đề (mặc định: tiêu đề bài/trang).
 * @param string $subtitle Mô tả phụ.
 */
function isha_page_banner( $title = '', $subtitle = '' ) {
	get_template_part(
		'template-parts/page-banner',
		null,
		array(
			'title'    => $title,
			'subtitle' => $subtitle,
		)
	);
}

/**
 * Khối CTA đặt lịch.
 */
function isha_cta_section() {
	isha_cta_printed( true );
	get_template_part( 'template-parts/cta' );
}

/**
 * Danh sách icon mạng xã hội (đọc link từ plugin ISHA Core). Chỉ hiện mạng nào có CẢ link lẫn file icon trong assets/icons
 * (hiện: Facebook, Instagram, Zalo); thêm icon cho TikTok, YouTube bằng cách đặt tiktok.svg, youtube.svg vào thư mục đó.
 * Hàng icon không lẫn link chữ.
 */
function isha_social_links() {
	$networks = array(
		'facebook'  => array( 'Facebook', 'facebook.svg' ),
		'instagram' => array( 'Instagram', 'instagram.svg' ),
		'tiktok'    => array( 'TikTok', 'tiktok.svg' ),
		'youtube'   => array( 'YouTube', 'youtube.svg' ),
		'zalo'      => array( 'Zalo', 'zalo.svg' ),
	);
	$out = '';
	foreach ( $networks as $key => $data ) {
		$url = isha_opt( $key );
		list( $label, $icon_file ) = $data;
		if ( ! $url || ! file_exists( get_theme_file_path( 'assets/icons/' . $icon_file ) ) ) {
			continue;
		}
		$out .= sprintf(
			'<li><a href="%s" target="_blank" rel="noopener noreferrer" class="social-links__icon" title="%s" aria-label="%s"><img src="%s" alt="" width="20" height="20"></a></li>',
			esc_url( $url ),
			esc_attr( $label ),
			esc_attr( $label ),
			esc_url( get_theme_file_uri( 'assets/icons/' . $icon_file ) )
		);
	}
	if ( $out ) {
		echo '<ul class="social-links">' . $out . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput -- đã escape ở trên.
	}
}
