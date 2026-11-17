<?php
/**
 * Markup trang Giới thiệu (templates/template-about.php). Bao trong `div.isha-home.isha-about` để thừa hưởng hệ thiết kế của trang chủ.
 *
 * - Hero (`section.isha-about-hero`, tông night): ảnh hoặc video nền (khi có, kèm lớp phủ tối), vòng tròn vàng kim mảnh (mặt trời, trang
 *   trí), tên website, tiêu đề trang (h1, hiện bằng mặt nạ trượt lên), vạch vàng, mô tả, mục lục tới các section (khi có từ 2 section).
 *   Video do about.js gán nguồn (không phát khi giảm chuyển động hoặc tiết kiệm dữ liệu; không JS thì chỉ có ảnh).
 * - Mỗi section (`section.isha-about-sec`, tông theo isha_about_section_defs()): phong cách "Câu chuyện nhà sáng lập" của trang chủ: ảnh
 *   dọc 4:5 trong khung viền vàng kim lệch phía sau (section lẻ ảnh bên trái, section chẵn bên phải), cột chữ gồm số thứ tự (trang trí),
 *   tiêu đề (h2), tiêu đề nhỏ (chữ nghiêng có vạch vàng bên trái), nội dung. Không có ảnh thì bố cục một cột chữ.
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

// Lấy một giá trị chuỗi từ hero; không phải chuỗi/số thì trả ''.
$isha_hero_text = static function ( $key ) use ( $isha_hero ) {
	return ( isset( $isha_hero[ $key ] ) && is_scalar( $isha_hero[ $key ] ) ) ? trim( (string) $isha_hero[ $key ] ) : '';
};

$isha_title     = $isha_hero_text( 'title' );
$isha_desc      = $isha_hero_text( 'description' );
$isha_video     = $isha_hero_text( 'video_desktop' );
$isha_video_m   = $isha_hero_text( 'video_mobile' );
$isha_image_id  = isset( $isha_hero['image_desktop'] ) ? (int) $isha_hero['image_desktop'] : 0;
$isha_image_mid = isset( $isha_hero['image_mobile'] ) ? (int) $isha_hero['image_mobile'] : 0;

// Ảnh nền hero: ảnh ngang cho màn hình rộng, ảnh dọc (nếu khác) cho điện thoại dọc qua <picture>.
$isha_hero_img = isha_home_image(
	$isha_image_id,
	array(
		'size'          => 'isha-hero',
		'sizes'         => '100vw',
		'class'         => 'isha-about-hero__img',
		'fetchpriority' => 'high',
		'decorative'    => true,
	)
);
$isha_hero_srcset_m = '';
if ( '' !== $isha_hero_img && $isha_image_mid > 0 && $isha_image_mid !== $isha_image_id && wp_attachment_is_image( $isha_image_mid ) ) {
	$isha_hero_srcset_m = (string) wp_get_attachment_image_srcset( $isha_image_mid, 'isha-frame' );
	if ( '' === $isha_hero_srcset_m ) {
		$isha_hero_srcset_m = (string) wp_get_attachment_image_url( $isha_image_mid, 'isha-frame' );
	}
}
$isha_has_media = '' !== $isha_hero_img || '' !== $isha_video;
?>
<div class="isha-home isha-about">
	<section class="isha-sec isha-about-hero<?php echo $isha_has_media ? ' isha-about-hero--media' : ''; ?>" data-tone="night" aria-labelledby="isha-about-title">
		<?php if ( $isha_has_media ) : ?>
			<div class="isha-about-hero__media" aria-hidden="true">
				<?php if ( '' !== $isha_hero_img ) : ?>
					<?php if ( '' !== $isha_hero_srcset_m ) : ?>
						<picture>
							<source media="(max-width: 900px) and (orientation: portrait)" srcset="<?php echo esc_attr( $isha_hero_srcset_m ); ?>" sizes="100vw">
							<?php echo $isha_hero_img; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?>
						</picture>
					<?php else : ?>
						<?php echo $isha_hero_img; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ( '' !== $isha_video ) : ?>
					<video class="isha-about-hero__video" muted loop playsinline preload="none" data-src="<?php echo esc_url( $isha_video ); ?>" data-src-mobile="<?php echo esc_url( '' !== $isha_video_m ? $isha_video_m : $isha_video ); ?>" hidden></video>
				<?php endif; ?>
				<span class="isha-about-hero__veil"></span>
			</div>
		<?php endif; ?>
		<span class="isha-about-hero__sun" aria-hidden="true"></span>

		<div class="isha-wrap isha-about-hero__inner">
			<p class="isha-label isha-about-hero__eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h1 id="isha-about-title" class="isha-about-hero__title"><span class="isha-about-hero__mask"><span class="isha-about-hero__name"><?php echo esc_html( '' !== $isha_title ? $isha_title : __( 'Giới thiệu', 'isha' ) ); ?></span></span></h1>
			<span class="isha-about-hero__rule" aria-hidden="true"></span>
			<?php if ( '' !== $isha_desc ) : ?>
				<p class="isha-lead isha-about-hero__lead"><?php echo esc_html( $isha_desc ); ?></p>
			<?php endif; ?>
			<?php if ( count( $isha_sections ) > 1 ) : ?>
				<nav class="isha-about-hero__index" aria-label="<?php esc_attr_e( 'Các phần của trang Giới thiệu', 'isha' ); ?>">
					<ol>
						<?php foreach ( $isha_sections as $isha_sec ) : ?>
							<li><a href="#<?php echo esc_attr( $isha_sec['anchor'] ); ?>"><span class="isha-about-hero__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) $isha_sec['number'], 2, '0', STR_PAD_LEFT ) ); ?></span><?php echo esc_html( $isha_sec['title'] ); ?></a></li>
						<?php endforeach; ?>
					</ol>
				</nav>
			<?php endif; ?>
		</div>
	</section>

	<?php foreach ( $isha_sections as $isha_k => $isha_sec ) : ?>
		<?php
		$isha_sec_title = 'isha-about-' . sanitize_html_class( $isha_sec['id'] ) . '-title';
		$isha_sec_img   = $isha_sec['image'] > 0 ? isha_home_image(
			$isha_sec['image'],
			array(
				'size'  => 'isha-frame',
				'sizes' => '(max-width: 900px) min(88vw, 420px), 40vw',
				'class' => 'isha-about-sec__img',
			)
		) : '';
		// Bố cục: có ảnh thì xen kẽ ảnh trái (section lẻ) và ảnh phải (section chẵn); không ảnh thì một cột chữ.
		if ( '' === $isha_sec_img ) {
			$isha_layout = 'text';
		} else {
			$isha_layout = 0 === $isha_k % 2 ? 'media-start' : 'media-end';
		}
		?>
		<section id="<?php echo esc_attr( $isha_sec['anchor'] ); ?>" class="isha-sec isha-about-sec isha-about-sec--<?php echo esc_attr( $isha_layout ); ?>" data-tone="<?php echo esc_attr( $isha_sec['tone'] ); ?>" aria-labelledby="<?php echo esc_attr( $isha_sec_title ); ?>">
			<div class="isha-wrap isha-about-sec__grid">
				<?php if ( '' !== $isha_sec_img ) : ?>
					<figure class="isha-about-sec__media">
						<div class="isha-about-sec__frame isha-r-45">
							<?php echo $isha_sec_img; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?>
						</div>
					</figure>
				<?php endif; ?>

				<div class="isha-about-sec__body">
					<p class="isha-about-sec__index" aria-hidden="true"><?php echo esc_html( str_pad( (string) $isha_sec['number'], 2, '0', STR_PAD_LEFT ) ); ?></p>
					<h2 id="<?php echo esc_attr( $isha_sec_title ); ?>" class="isha-sec__title isha-about-sec__title"><?php echo esc_html( $isha_sec['title'] ); ?></h2>
					<?php if ( '' !== $isha_sec['subtitle'] ) : ?>
						<p class="isha-about-sec__subtitle"><?php echo esc_html( $isha_sec['subtitle'] ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $isha_sec['content'] ) : ?>
						<div class="isha-about-sec__content">
							<?php echo wp_kses_post( $isha_sec['content'] ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endforeach; ?>
</div>
