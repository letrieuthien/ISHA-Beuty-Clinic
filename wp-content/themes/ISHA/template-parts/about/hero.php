<?php
/**
 * Hero dùng chung của các trang theo phong cách trang Giới thiệu (trang Giới thiệu, trang Không gian ISHA và bài Không gian), tách ra từ
 * template-parts/about/page.php ngày 18/11/2026.
 *
 * `section.isha-about-hero` (tông night): ảnh hoặc video nền (khi có, kèm lớp phủ tối), vòng tròn vàng kim mảnh (mặt trời, trang trí), dòng
 * nhãn (tên website hoặc liên kết về trang cha), tiêu đề (h1, hiện bằng mặt nạ trượt lên), vạch vàng, mô tả, mục lục tới các section (khi có
 * từ 2 mục). Video do about.js gán nguồn (không phát khi giảm chuyển động hoặc tiết kiệm dữ liệu; không JS thì chỉ có ảnh).
 *
 * Dữ liệu `$args`:
 *   title, description          Văn bản thô.
 *   eyebrow, eyebrow_url        Dòng nhãn trên tiêu đề (mặc định tên website); có URL thì là liên kết.
 *   image_desktop, image_mobile ID ảnh nền (ảnh ngang cho màn hình rộng, ảnh dọc cho điện thoại dọc nếu khác).
 *   video_desktop, video_mobile URL video nền.
 *   index                       Mục lục: mảng {anchor, number, title}; in khi có từ 2 mục.
 *   index_label                 Tên truy cập của mục lục.
 *   title_id                    id của h1 (mặc định isha-about-title).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_hero = ( isset( $args ) && is_array( $args ) ) ? $args : array();

// Lấy một giá trị chuỗi từ dữ liệu; không phải chuỗi/số thì trả ''.
$isha_hero_text = static function ( $key ) use ( $isha_hero ) {
	return ( isset( $isha_hero[ $key ] ) && is_scalar( $isha_hero[ $key ] ) ) ? trim( (string) $isha_hero[ $key ] ) : '';
};

$isha_title       = $isha_hero_text( 'title' );
$isha_desc        = $isha_hero_text( 'description' );
$isha_eyebrow     = $isha_hero_text( 'eyebrow' );
$isha_eyebrow_url = $isha_hero_text( 'eyebrow_url' );
$isha_video       = $isha_hero_text( 'video_desktop' );
$isha_video_m     = $isha_hero_text( 'video_mobile' );
$isha_title_id    = $isha_hero_text( 'title_id' );
$isha_index_label = $isha_hero_text( 'index_label' );
$isha_image_id    = isset( $isha_hero['image_desktop'] ) ? (int) $isha_hero['image_desktop'] : 0;
$isha_image_mid   = isset( $isha_hero['image_mobile'] ) ? (int) $isha_hero['image_mobile'] : 0;
$isha_index       = ( isset( $isha_hero['index'] ) && is_array( $isha_hero['index'] ) ) ? $isha_hero['index'] : array();

if ( '' === $isha_eyebrow ) {
	$isha_eyebrow = get_bloginfo( 'name' );
}
if ( '' === $isha_title_id ) {
	$isha_title_id = 'isha-about-title';
}

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
<section class="isha-sec isha-about-hero<?php echo $isha_has_media ? ' isha-about-hero--media' : ''; ?>" data-tone="night" aria-labelledby="<?php echo esc_attr( $isha_title_id ); ?>">
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
		<?php if ( '' !== $isha_eyebrow_url ) : ?>
			<p class="isha-label isha-about-hero__eyebrow"><a class="isha-about-hero__back" href="<?php echo esc_url( $isha_eyebrow_url ); ?>"><?php echo esc_html( $isha_eyebrow ); ?></a></p>
		<?php else : ?>
			<p class="isha-label isha-about-hero__eyebrow"><?php echo esc_html( $isha_eyebrow ); ?></p>
		<?php endif; ?>
		<h1 id="<?php echo esc_attr( $isha_title_id ); ?>" class="isha-about-hero__title"><span class="isha-about-hero__mask"><span class="isha-about-hero__name"><?php echo esc_html( '' !== $isha_title ? $isha_title : get_bloginfo( 'name' ) ); ?></span></span></h1>
		<span class="isha-about-hero__rule" aria-hidden="true"></span>
		<?php if ( '' !== $isha_desc ) : ?>
			<p class="isha-lead isha-about-hero__lead"><?php echo esc_html( $isha_desc ); ?></p>
		<?php endif; ?>
		<?php if ( count( $isha_index ) > 1 ) : ?>
			<nav class="isha-about-hero__index" aria-label="<?php echo esc_attr( '' !== $isha_index_label ? $isha_index_label : __( 'Các phần của trang', 'isha' ) ); ?>">
				<ol>
					<?php foreach ( $isha_index as $isha_item ) : ?>
						<?php
						if ( ! is_array( $isha_item ) || empty( $isha_item['anchor'] ) || empty( $isha_item['title'] ) ) {
							continue;
						}
						?>
						<li><a href="#<?php echo esc_attr( (string) $isha_item['anchor'] ); ?>"><span class="isha-about-hero__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( isset( $isha_item['number'] ) ? (int) $isha_item['number'] : 0 ), 2, '0', STR_PAD_LEFT ) ); ?></span><?php echo esc_html( (string) $isha_item['title'] ); ?></a></li>
					<?php endforeach; ?>
				</ol>
			</nav>
		<?php endif; ?>
	</div>
</section>
