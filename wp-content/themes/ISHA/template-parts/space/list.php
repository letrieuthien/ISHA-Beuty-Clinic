<?php
/**
 * Markup trang danh sách Không gian ISHA (templates/template-space.php): hero dùng chung (template-parts/about/hero.php, mục lục tới từng
 * không gian khi có từ 2 bài), rồi mỗi bài một section (template-parts/about/section.php): ảnh đại diện 4:5 xen kẽ trái phải, số thứ tự, tên
 * bài là liên kết, tiêu đề nhỏ, mô tả ngắn, liên kết "Khám phá không gian". Chưa có bài nào thì một dòng thông báo.
 *
 * Dữ liệu: `$args` = isha_space_list_data( ID trang ).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_hero     = ( isset( $args['hero'] ) && is_array( $args['hero'] ) ) ? $args['hero'] : array();
$isha_sections = ( isset( $args['sections'] ) && is_array( $args['sections'] ) ) ? $args['sections'] : array();

$isha_hero['index']       = $isha_sections;
$isha_hero['index_label'] = __( 'Các không gian của ISHA', 'isha' );
?>
<div class="isha-home isha-about isha-space-page">
	<?php
	get_template_part( 'template-parts/about/hero', null, $isha_hero );

	if ( ! $isha_sections ) :
		?>
		<section class="isha-sec isha-about-sec isha-about-sec--text" data-tone="day" aria-label="<?php esc_attr_e( 'Không gian ISHA', 'isha' ); ?>">
			<div class="isha-wrap isha-about-sec__grid">
				<p class="isha-lead isha-space-page__empty"><?php esc_html_e( 'Hình ảnh không gian đang được cập nhật. Mời bạn ghé thăm ISHA để tự mình cảm nhận.', 'isha' ); ?></p>
			</div>
		</section>
		<?php
	endif;

	foreach ( $isha_sections as $isha_k => $isha_sec ) {
		get_template_part(
			'template-parts/about/section',
			null,
			array(
				'section'   => $isha_sec,
				'position'  => $isha_k,
				'title_url' => $isha_sec['url'],
				'link'      => array(
					'url'   => $isha_sec['url'],
					'label' => __( 'Khám phá không gian', 'isha' ),
					'sr'    => $isha_sec['title'],
				),
			)
		);
	}
	?>
</div>
