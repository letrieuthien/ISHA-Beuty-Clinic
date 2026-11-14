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
	get_template_part( 'template-parts/cta' );
}

/**
 * Danh sách link mạng xã hội (đọc từ plugin ISHA Core).
 */
function isha_social_links() {
	$networks = array(
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'tiktok'    => 'TikTok',
		'youtube'   => 'YouTube',
		'zalo'      => 'Zalo',
	);
	$out = '';
	foreach ( $networks as $key => $label ) {
		$url = isha_opt( $key );
		if ( $url ) {
			$out .= sprintf(
				'<li><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></li>',
				esc_url( $url ),
				esc_html( $label )
			);
		}
	}
	if ( $out ) {
		echo '<ul class="social-links">' . $out . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput -- đã escape ở trên.
	}
}
