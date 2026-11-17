<?php
/**
 * Markup trang Giới thiệu (templates/template-about.php). Bao trong `div.isha-home.isha-about` để thừa hưởng hệ thiết kế của trang chủ.
 *
 * - Hero (template-parts/about/hero.php, tông night): ảnh hoặc video nền, mặt trời vàng kim, tên website, tiêu đề trang, mô tả, mục lục tới
 *   các section (khi có từ 2 section).
 * - Mỗi section (template-parts/about/section.php, tông theo isha_about_section_defs()): phong cách "Câu chuyện nhà sáng lập" của trang chủ,
 *   ảnh 4:5 xen kẽ trái phải, số thứ tự, tiêu đề, tiêu đề nhỏ, nội dung. Không có ảnh thì một cột chữ.
 *
 * Dữ liệu: `$args` = isha_about_data( ID trang ): hero, sections (xem inc/about.php). Nội dung section là HTML đã lọc, in qua wp_kses_post.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_hero     = ( isset( $args['hero'] ) && is_array( $args['hero'] ) ) ? $args['hero'] : array();
$isha_sections = ( isset( $args['sections'] ) && is_array( $args['sections'] ) ) ? $args['sections'] : array();

$isha_hero['title']       = ( isset( $isha_hero['title'] ) && '' !== trim( (string) $isha_hero['title'] ) ) ? $isha_hero['title'] : __( 'Giới thiệu', 'isha' );
$isha_hero['index']       = $isha_sections;
$isha_hero['index_label'] = __( 'Các phần của trang Giới thiệu', 'isha' );
?>
<div class="isha-home isha-about">
	<?php
	get_template_part( 'template-parts/about/hero', null, $isha_hero );

	foreach ( $isha_sections as $isha_k => $isha_sec ) {
		get_template_part(
			'template-parts/about/section',
			null,
			array(
				'section'  => $isha_sec,
				'position' => $isha_k,
			)
		);
	}
	?>
</div>
