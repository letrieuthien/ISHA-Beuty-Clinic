<?php
/**
 * Registry của trang chủ điện ảnh: 13 section theo thứ tự trên trang.
 *
 * Mỗi section có: tag (shortcode), template (template part), tone (tông nền theo giờ trong ngày, đặt vào `data-tone`),
 * class (class của thẻ <section>) và scene (id cảnh, đặt vào `data-isha-scene`). Hợp đồng ở docs/home-architecture.md, mục 5.3.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry gốc, chưa qua bộ lọc (hàm nội bộ): dùng để đăng ký đủ shortcode của mọi section và làm chỗ dự phòng khi tra cứu section.
 *
 * Không gọi __() ở đây nên gọi được ở bất kỳ thời điểm nào, kể cả lúc nạp tệp.
 *
 * @return array array( id => array( tag, template, tone, class, scene ) ).
 */
function isha_home_sections_base() {
	static $base = null;

	if ( null === $base ) {
		$tones = array(
			'hero'         => 'night',
			'story'        => 'day',
			'philosophy'   => 'day',
			'services'     => 'day-warm',
			'values'       => 'day-warm',
			'process'      => 'afternoon',
			'space'        => 'golden',
			'events'       => 'golden',
			'articles'     => 'late-afternoon',
			'testimonials' => 'evening',
			'vision'       => 'twilight',
			'cta'          => 'night',
			'partners'     => 'night',
		);

		$base = array();
		foreach ( $tones as $id => $tone ) {
			$base[ $id ] = array(
				'tag'      => 'isha_home_' . $id,
				'template' => 'template-parts/home/' . $id,
				'tone'     => $tone,
				// Cảnh kết dùng `isha-home-cta` vì `isha-cta` đã có CSS của plugin.
				'class'    => 'cta' === $id ? 'isha-home-cta' : 'isha-' . $id,
				'scene'    => $id,
			);
		}
	}

	return $base;
}

/**
 * Danh sách section của trang chủ theo thứ tự: hero, story, philosophy, services, values, process, space, events,
 * articles, testimonials, vision, cta, partners.
 *
 * Đi qua bộ lọc `isha/home/sections` để bên ngoài bớt, thêm hoặc đổi thứ tự section (chỉ ảnh hưởng isha_home_render();
 * mọi shortcode luôn được đăng ký đủ).
 *
 * @return array array( id => array( 'tag' => 'isha_home_<id>', 'template' => 'template-parts/home/<id>', 'tone' => <tông>,
 *               'class' => 'isha-<id>' (cta: 'isha-home-cta'), 'scene' => <id> ) ).
 */
function isha_home_sections() {
	$sections = apply_filters( 'isha/home/sections', isha_home_sections_base() );
	return is_array( $sections ) ? $sections : isha_home_sections_base();
}
