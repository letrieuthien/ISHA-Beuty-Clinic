<?php
/**
 * Registry của trang chủ điện ảnh: 11 section theo thứ tự trên trang (tái cấu trúc 17/11/2026, docs/home-architecture.md mục 15).
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
 * Tên shortcode của một section: `isha_home_<id>`, gạch ngang trong id đổi thành gạch dưới (WordPress khuyên không dùng gạch ngang
 * trong tên shortcode), ví dụ founder-story thành [isha_home_founder_story].
 *
 * @param string $id Mã section.
 * @return string
 */
function isha_home_tag( $id ) {
	return 'isha_home_' . str_replace( '-', '_', (string) $id );
}

/**
 * Registry gốc, chưa qua bộ lọc (hàm nội bộ): dùng để đăng ký đủ shortcode của mọi section và làm chỗ dự phòng khi tra cứu section.
 *
 * Tông đi theo một ngày: hero (đêm, bình minh) rồi sáng dần qua Không gian, Hiệu quả liệu trình, Dịch vụ tiêu biểu, Founder, chiều vàng
 * ở Sự kiện, Tin tức, chiều tối ở Lời kể, rồi về đêm ở Đối tác và Cảnh kết. Quy trình (đang ẩn trong CMS) mang tông hoàng hôn để nếu bật
 * lại vẫn nằm đúng nhịp giữa Lời kể và Đối tác.
 *
 * Không gọi __() ở đây nên gọi được ở bất kỳ thời điểm nào, kể cả lúc nạp tệp.
 *
 * @return array array( id => array( tag, template, tone, class, scene ) ).
 */
function isha_home_sections_base() {
	static $base = null;

	if ( null === $base ) {
		$tones = array(
			'hero'          => 'night',
			'space'         => 'day',
			'results'       => 'day',
			'services'      => 'day-warm',
			'founder-story' => 'afternoon',
			'events'        => 'golden',
			'articles'      => 'late-afternoon',
			'testimonials'  => 'evening',
			'process'       => 'twilight',
			'partners'      => 'night',
			'cta'           => 'night',
		);

		$base = array();
		foreach ( $tones as $id => $tone ) {
			$base[ $id ] = array(
				'tag'      => isha_home_tag( $id ),
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
 * Danh sách section của trang chủ theo thứ tự: hero, space, results, services, founder-story, events, articles, testimonials, process,
 * partners, cta.
 *
 * Đi qua bộ lọc `isha/home/sections` để bên ngoài bớt, thêm hoặc đổi thứ tự section (chỉ ảnh hưởng isha_home_render();
 * mọi shortcode luôn được đăng ký đủ).
 *
 * @return array array( id => array( 'tag' => isha_home_tag( <id> ), 'template' => 'template-parts/home/<id>', 'tone' => <tông>,
 *               'class' => 'isha-<id>' (cta: 'isha-home-cta'), 'scene' => <id> ) ).
 */
function isha_home_sections() {
	$sections = apply_filters( 'isha/home/sections', isha_home_sections_base() );
	return is_array( $sections ) ? $sections : isha_home_sections_base();
}
