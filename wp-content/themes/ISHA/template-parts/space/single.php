<?php
/**
 * Markup trang chi tiết bài Không gian ISHA (single-isha_space.php), phong cách trang Giới thiệu:
 *
 * - Hero dùng chung (template-parts/about/hero.php): nhãn là liên kết về trang Không gian ISHA, tên bài, tiêu đề nhỏ, ảnh nền là ảnh ngang
 *   đầu tiên của bộ ảnh (không có thì nền tối với mặt trời vàng kim).
 * - Giới thiệu (template-parts/about/section.php): ảnh đại diện 4:5 trong khung vàng kim lệch, mô tả ngắn in nghiêng, nội dung bài.
 * - Hình ảnh không gian: các ảnh còn lại xếp hai cột so le (CSS columns, ảnh giữ nguyên tỉ lệ), chú thích Garamond nghiêng dưới ảnh.
 * - Không gian khác: tối đa 3 thẻ (ảnh 4:5, tên, tiêu đề nhỏ) và liên kết về trang danh sách.
 * Khối nào thiếu dữ liệu thì bỏ hẳn.
 *
 * Dữ liệu: `$args` = isha_space_single_data( WP_Post ).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_d        = ( isset( $args ) && is_array( $args ) ) ? $args : array();
$isha_hero     = ( isset( $isha_d['hero'] ) && is_array( $isha_d['hero'] ) ) ? $isha_d['hero'] : array();
$isha_intro    = ( isset( $isha_d['intro'] ) && is_array( $isha_d['intro'] ) ) ? $isha_d['intro'] : array();
$isha_gallery  = ( isset( $isha_d['gallery'] ) && is_array( $isha_d['gallery'] ) ) ? $isha_d['gallery'] : array();
$isha_others   = ( isset( $isha_d['others'] ) && is_array( $isha_d['others'] ) ) ? $isha_d['others'] : array();
$isha_list_url = ( isset( $isha_d['list_url'] ) && is_string( $isha_d['list_url'] ) ) ? $isha_d['list_url'] : '';

$isha_hero['title_id'] = 'isha-space-title';
$isha_has_intro        = ( ! empty( $isha_intro['content'] ) || ! empty( $isha_intro['subtitle'] ) || ! empty( $isha_intro['image'] ) );
?>
<div class="isha-home isha-about isha-space-page isha-space-page--single">
	<?php
	get_template_part( 'template-parts/about/hero', null, $isha_hero );

	if ( $isha_has_intro ) {
		get_template_part(
			'template-parts/about/section',
			null,
			array(
				'section'  => $isha_intro,
				'position' => 0,
				'filtered' => true,
				'label'    => __( 'Giới thiệu không gian', 'isha' ),
			)
		);
	}
	?>

	<?php if ( $isha_gallery ) : ?>
		<section class="isha-sec isha-space-gallery" data-tone="day-warm" aria-labelledby="isha-space-gallery-title">
			<div class="isha-wrap">
				<header class="isha-sec__head">
					<h2 id="isha-space-gallery-title" class="isha-sec__title"><?php esc_html_e( 'Hình ảnh không gian', 'isha' ); ?></h2>
				</header>
				<ul class="isha-space-gallery__grid" role="list">
					<?php foreach ( $isha_gallery as $isha_photo ) : ?>
						<?php
						$isha_img = isha_home_image(
							(int) $isha_photo['id'],
							array(
								'size'       => 'isha-strip',
								'sizes'      => '(max-width: 700px) 92vw, 46vw',
								'class'      => 'isha-space-gallery__img',
								'alt'        => (string) $isha_photo['alt'],
								'decorative' => '' === (string) $isha_photo['alt'],
							)
						);
						if ( '' === $isha_img ) {
							continue;
						}
						?>
						<li class="isha-space-gallery__item isha-space-gallery__item--<?php echo esc_attr( (string) $isha_photo['shape'] ); ?>">
							<figure class="isha-space-gallery__figure">
								<div class="isha-space-gallery__frame">
									<?php echo $isha_img; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?>
								</div>
								<?php if ( '' !== (string) $isha_photo['caption'] ) : ?>
									<figcaption class="isha-space-gallery__caption isha-note"><?php echo esc_html( (string) $isha_photo['caption'] ); ?></figcaption>
								<?php endif; ?>
							</figure>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $isha_others ) : ?>
		<section class="isha-sec isha-space-more" data-tone="afternoon" aria-labelledby="isha-space-more-title">
			<div class="isha-wrap">
				<header class="isha-sec__head">
					<h2 id="isha-space-more-title" class="isha-sec__title"><?php esc_html_e( 'Không gian khác', 'isha' ); ?></h2>
				</header>
				<ul class="isha-space-more__list" role="list">
					<?php foreach ( $isha_others as $isha_other ) : ?>
						<?php
						$isha_other_img = isha_home_image(
							(int) $isha_other['image'],
							array(
								'size'       => 'isha-frame',
								'sizes'      => '(max-width: 700px) 80vw, 30vw',
								'class'      => 'isha-space-more__img',
								'decorative' => true,
							)
						);
						?>
						<li class="isha-space-more__item">
							<a class="isha-space-more__card" href="<?php echo esc_url( (string) $isha_other['url'] ); ?>">
								<span class="isha-space-more__frame isha-r-45">
									<?php
									if ( '' !== $isha_other_img ) {
										echo $isha_other_img; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape.
									}
									?>
								</span>
								<span class="isha-space-more__name"><?php echo esc_html( (string) $isha_other['title'] ); ?></span>
								<?php if ( '' !== (string) $isha_other['subtitle'] ) : ?>
									<span class="isha-space-more__sub"><?php echo esc_html( (string) $isha_other['subtitle'] ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php if ( '' !== $isha_list_url ) : ?>
					<p class="isha-space-more__all"><a class="isha-link" href="<?php echo esc_url( $isha_list_url ); ?>"><?php esc_html_e( 'Xem tất cả không gian', 'isha' ); ?></a></p>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>
</div>
