<?php
/**
 * Section Hero của trang chủ điện ảnh (Giai đoạn 3): lớp hình toàn khung (.isha-hero__film: poster hoặc ánh sáng Gold, video, lớp
 * phủ tối) cắt thành VÒNG TRÒN mặt trời bằng clip-path; home.js mở iris theo cuộn (cảnh ghim: track + stage) hoặc theo thời gian
 * (chế độ gọn). Cột chữ .isha-hero__inner: logo chữ, dòng nhỏ, h1 (nằm trong mặt trời), mô tả, nút đặt lịch; vòng mảnh cùng 48 tia
 * Light Gloom; vạch dọc gợi ý cuộn. Kèm khung intro (template-parts/home/intro.php).
 *
 * Trạng thái TĨNH (không JS, giảm chuyển động, isha-motion-fail): poster hiện ngay trong vòng tròn (ứng viên LCP nên eager +
 * fetchpriority high, cùng kích thước và sizes với thẻ preload ở inc/home/assets.php); video không tải (hidden, preload none;
 * home.js chọn nguồn dọc hoặc ngang, gán src và phát sau intro). Video nền luôn lặp vô hạn (`loop`), không tiếng (`muted`: file có
 * tiếng vẫn câm) và KHÔNG có nút tạm dừng/phát (quyết định của chủ dự án). Hình, vòng tròn và tia sáng là trang trí (aria-hidden); nội
 * dung đọc được nằm ở dòng nhỏ, h1, mô tả và nút. Thiếu poster thì vòng tròn chỉ có ánh sáng Gold mờ.
 *
 * Dữ liệu: `$args` = isha_home_data( 'hero' ) (giá trị thô, template tự escape).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_hero = ( isset( $args ) && is_array( $args ) ) ? $args : array();

$isha_text = static function ( $key ) use ( $isha_hero ) {
	return ( isset( $isha_hero[ $key ] ) && is_scalar( $isha_hero[ $key ] ) ) ? trim( (string) $isha_hero[ $key ] ) : '';
};
$isha_id   = static function ( $key ) use ( $isha_hero ) {
	return ( isset( $isha_hero[ $key ] ) && is_numeric( $isha_hero[ $key ] ) ) ? max( 0, (int) $isha_hero[ $key ] ) : 0;
};

$isha_title    = $isha_text( 'title' );
$isha_eyebrow  = $isha_text( 'eyebrow' );
$isha_desc     = $isha_text( 'description' );
$isha_btn      = $isha_text( 'button_label' );
$isha_contact  = isha_home_contact();
$isha_book_url = isset( $isha_contact['booking_url'] ) ? (string) $isha_contact['booking_url'] : '';

if ( '' === $isha_title ) {
	$isha_title = __( 'Vẻ đẹp là một dấu ấn riêng', 'isha' );
}
if ( '' === $isha_btn ) {
	$isha_btn = __( 'Đặt lịch tư vấn riêng', 'isha' );
}

// Poster: ảnh ngang là <img> chính, ảnh dọc là <source> khi màn hình dọc; chỉ có một loại thì dùng cho cả hai.
$isha_poster_l = $isha_id( 'poster_landscape' );
$isha_poster_p = $isha_id( 'poster_portrait' );
$isha_has_l    = $isha_poster_l > 0 && wp_attachment_is_image( $isha_poster_l );
$isha_has_p    = $isha_poster_p > 0 && wp_attachment_is_image( $isha_poster_p );
$isha_img      = '';
$isha_source   = '';

if ( $isha_has_l ) {
	$isha_img = isha_home_image(
		$isha_poster_l,
		array(
			'size'          => 'isha-hero',
			'sizes'         => '100vw',
			'class'         => 'isha-hero__img',
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'decorative'    => true,
		)
	);
	if ( $isha_has_p ) {
		$isha_p_url = wp_get_attachment_image_url( $isha_poster_p, 'isha-frame' );
		$isha_p_set = wp_get_attachment_image_srcset( $isha_poster_p, 'isha-frame' );
		if ( is_string( $isha_p_url ) && '' !== $isha_p_url ) {
			$isha_source = '<source media="(orientation: portrait)" srcset="' . esc_attr( ( is_string( $isha_p_set ) && '' !== $isha_p_set ) ? $isha_p_set : $isha_p_url ) . '"' . ( ( is_string( $isha_p_set ) && '' !== $isha_p_set ) ? ' sizes="100vw"' : '' ) . '>';
		}
	}
} elseif ( $isha_has_p ) {
	$isha_img = isha_home_image(
		$isha_poster_p,
		array(
			'size'          => 'isha-frame',
			'sizes'         => '100vw',
			'class'         => 'isha-hero__img',
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'decorative'    => true,
		)
	);
}

// Video: thiếu một hướng thì dùng hướng còn lại; không có cả hai thì không in video và nút.
$isha_video_l = isha_home_video_url( $isha_id( 'video_landscape' ) );
$isha_video_p = isha_home_video_url( $isha_id( 'video_portrait' ) );
if ( '' === $isha_video_l ) {
	$isha_video_l = $isha_video_p;
}
if ( '' === $isha_video_p ) {
	$isha_video_p = $isha_video_l;
}
$isha_has_video = '' !== $isha_video_l;

// Tia Light Gloom (trang trí): 48 tia cách nhau 7,5 độ, dài ngắn xen kẽ, tia dài có khoảng đứt như bìa deck. Toạ độ trong
// viewBox 200x200 (bán kính vòng tròn mặt trời là 25 đơn vị); gộp thành MỘT path để markup nhỏ. Bề cao của tia bị giới hạn:
// tia gần trục dọc chỉ còn là vạch ngắn (không chạm logo chữ, dòng nhỏ và mô tả nằm trên, dưới vòng tròn), tia gần trục ngang
// dài ra như vệt sáng ống kính ngang.
$isha_ray_len  = array( 15, 7, 11, 5, 18, 8, 13, 6, 10, 20, 7, 12 );
$isha_ray_path = '';
for ( $isha_i = 0; $isha_i < 48; $isha_i++ ) {
	$isha_rad = deg2rad( $isha_i * 7.5 );
	$isha_cos = cos( $isha_rad );
	$isha_sin = sin( $isha_rad );
	$isha_cap = abs( $isha_sin ) > 0.01 ? 32 / abs( $isha_sin ) : 60;
	$isha_len = min( 29 + $isha_ray_len[ $isha_i % count( $isha_ray_len ) ], $isha_cap, 52 ) - 29;
	if ( $isha_len < 2.5 ) {
		continue;
	}
	$isha_spans = $isha_len >= 13 ? array( array( 0, 0.55 ), array( 0.72, 1 ) ) : array( array( 0, 1 ) );
	foreach ( $isha_spans as $isha_span ) {
		$isha_from      = 29 + $isha_len * $isha_span[0];
		$isha_to        = 29 + $isha_len * $isha_span[1];
		$isha_ray_path .= 'M' . number_format( $isha_from * $isha_cos, 2, '.', '' ) . ' ' . number_format( $isha_from * $isha_sin, 2, '.', '' );
		$isha_ray_path .= 'L' . number_format( $isha_to * $isha_cos, 2, '.', '' ) . ' ' . number_format( $isha_to * $isha_sin, 2, '.', '' );
	}
}

isha_home_section_open( 'hero' );
get_template_part( 'template-parts/home/intro', null, $isha_hero );
?>
<div class="isha-hero__track" data-isha-track>
	<div class="isha-hero__stage" data-isha-stage>
		<div class="isha-hero__film" data-isha-hero-film aria-hidden="true">
			<div class="isha-hero__media" data-isha-hero-media>
				<?php if ( '' !== $isha_img ) : ?>
					<picture class="isha-hero__poster">
						<?php echo $isha_source; // phpcs:ignore WordPress.Security.EscapeOutput -- thẻ <source> dựng ở trên bằng esc_attr. ?>
						<?php echo $isha_img; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả HTML đã escape. ?>
					</picture>
				<?php else : ?>
					<div class="isha-hero__glow"></div>
					<?php isha_home_placeholder( __( 'Poster hero ngang', 'isha' ), array( 'class' => 'isha-hero__ph' ) ); ?>
				<?php endif; ?>
				<?php if ( $isha_has_video ) : ?>
					<video class="isha-hero__video" data-isha-hero-video data-src-landscape="<?php echo esc_url( $isha_video_l ); ?>" data-src-portrait="<?php echo esc_url( $isha_video_p ); ?>" muted playsinline loop preload="none" aria-hidden="true" tabindex="-1" hidden></video>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $isha_img || $isha_has_video ) : // Lớp phủ giữ chữ đạt tương phản trên ảnh VÀ trên video (hero chỉ có video vẫn cần). ?>
				<div class="isha-hero__veil" data-isha-hero-veil></div>
			<?php endif; ?>
		</div>

		<div class="isha-hero__inner">
			<div class="isha-hero__top">
				<?php isha_home_wordmark( array( 'class' => 'isha-hero__wordmark', 'ids' => false ) ); ?>
				<?php if ( '' !== $isha_eyebrow ) : ?>
					<p class="isha-label isha-hero__eyebrow"><?php echo esc_html( $isha_eyebrow ); ?></p>
				<?php endif; ?>
			</div>

			<h1 id="<?php echo esc_attr( isha_home_title_id( 'hero' ) ); ?>" class="isha-hero__title"><?php echo esc_html( $isha_title ); ?></h1>

			<div class="isha-hero__bottom">
				<?php if ( '' !== $isha_desc ) : ?>
					<p class="isha-hero__desc"><?php echo esc_html( $isha_desc ); ?></p>
				<?php endif; ?>
				<?php isha_home_button( $isha_btn, $isha_book_url, 'primary', array( 'class' => 'isha-hero__cta' ) ); ?>
			</div>

			<div class="isha-hero__sun" data-isha-hero-sun aria-hidden="true">
				<svg class="isha-hero__rays" data-isha-hero-rays viewBox="-100 -100 200 200" focusable="false" aria-hidden="true">
					<circle class="isha-hero__ring" r="30"></circle>
					<path class="isha-hero__beams" d="<?php echo esc_attr( $isha_ray_path ); ?>"></path>
				</svg>
			</div>
		</div>

		<span class="isha-hero__scroll" aria-hidden="true"></span>
	</div>
</div>
<?php
isha_home_section_close();
