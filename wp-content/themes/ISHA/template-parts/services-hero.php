<?php
/**
 * Hero của trang danh sách dịch vụ: khung bo góc lớn, ảnh hoặc video nền phủ lớp nâu đậm nhạt dần, tiêu đề (tên trang) và mô tả
 * căn giữa phía trên, nan quạt các nhóm dịch vụ quay quanh tâm ở giữa mép dưới, mảng kem cong chứa logo và "Beauty Clinic".
 *
 * Trạng thái tĩnh (không JS): ảnh nền hiện ngay (ứng viên LCP: eager + fetchpriority high), bánh xe quay bằng CSS. Có JS
 * (assets/js/services-hero.js): chọn video theo khổ màn hình rồi hiện dần trên ảnh, bánh xe do JS quay (chậm dần rồi dừng khi
 * rê chuột, quay thẻ đang focus lên đỉnh). Giảm chuyển động: không video, bánh xe đứng yên.
 * Thẻ lặp lại (khi ít hơn 12 nhóm) là bản sao trang trí: aria-hidden và không nhận focus.
 *
 * Dữ liệu: `$args` = isha_services_hero_data() (inc/services-hero.php), template tự escape.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_sh = ( isset( $args ) && is_array( $args ) ) ? $args : array();
$isha_sh = array_merge(
	array(
		'title'         => '',
		'description'   => '',
		'image_desktop' => 0,
		'image_mobile'  => 0,
		'video_desktop' => '',
		'video_mobile'  => '',
		'groups'        => array(),
		'slots'         => array(),
	),
	$isha_sh
);

// Ảnh nền: bản màn hình rộng là <img>, bản điện thoại là <source> khi màn hình hẹp (cùng điểm ngắt với JS chọn video).
$isha_sh_img    = '';
$isha_sh_source = '';
if ( $isha_sh['image_desktop'] ) {
	$isha_sh_img = wp_get_attachment_image(
		(int) $isha_sh['image_desktop'],
		'full',
		false,
		array(
			'class'         => 'isha-sh__img',
			'alt'           => '',
			'sizes'         => '100vw',
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'decoding'      => 'async',
		)
	);
	if ( $isha_sh['image_mobile'] && (int) $isha_sh['image_mobile'] !== (int) $isha_sh['image_desktop'] ) {
		$isha_sh_m_url = wp_get_attachment_image_url( (int) $isha_sh['image_mobile'], 'full' );
		$isha_sh_m_set = wp_get_attachment_image_srcset( (int) $isha_sh['image_mobile'], 'full' );
		if ( $isha_sh_m_url ) {
			$isha_sh_source = '<source media="(max-width: 767px)" srcset="' . esc_attr( $isha_sh_m_set ? $isha_sh_m_set : $isha_sh_m_url ) . '" sizes="100vw">';
		}
	}
}
$isha_sh_has_video = '' !== $isha_sh['video_desktop'];

$isha_sh_groups = is_array( $isha_sh['groups'] ) ? $isha_sh['groups'] : array();
$isha_sh_slots  = is_array( $isha_sh['slots'] ) ? $isha_sh['slots'] : array();
$isha_sh_total  = count( $isha_sh_slots );

// Trang nhóm: vị trí thẻ GỐC (không phải bản lặp) của nhóm đang xem, để JS xoay thẻ đó lên đỉnh. -1 = không có.
$isha_sh_current = isset( $isha_sh['current'] ) ? (int) $isha_sh['current'] : 0;
$isha_sh_start   = -1;
if ( $isha_sh_current ) {
	foreach ( $isha_sh_slots as $isha_sh_pos => $isha_sh_index ) {
		if ( isset( $isha_sh_groups[ $isha_sh_index ]['id'] ) && $isha_sh_current === (int) $isha_sh_groups[ $isha_sh_index ]['id'] ) {
			$isha_sh_start = (int) $isha_sh_pos;
			break;
		}
	}
}
?>
<section class="isha-sh" aria-labelledby="isha-sh-title" data-isha-sh>
	<div class="isha-sh__frame">
		<div class="isha-sh__media" aria-hidden="true">
			<?php if ( $isha_sh_img ) : ?>
				<picture class="isha-sh__picture">
					<?php echo $isha_sh_source; // phpcs:ignore WordPress.Security.EscapeOutput -- dựng ở trên bằng esc_attr. ?>
					<?php echo $isha_sh_img; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image() đã escape. ?>
				</picture>
			<?php endif; ?>
			<?php if ( $isha_sh_has_video ) : ?>
				<video class="isha-sh__video" data-isha-sh-video data-src-desktop="<?php echo esc_url( $isha_sh['video_desktop'] ); ?>" data-src-mobile="<?php echo esc_url( $isha_sh['video_mobile'] ); ?>" muted playsinline loop preload="none" tabindex="-1"></video>
			<?php endif; ?>
			<div class="isha-sh__veil"></div>
		</div>

		<div class="isha-sh__head">
			<h1 class="isha-sh__title" id="isha-sh-title"><?php echo esc_html( $isha_sh['title'] ); ?></h1>
			<?php if ( '' !== $isha_sh['description'] ) : ?>
				<p class="isha-sh__desc"><?php echo esc_html( $isha_sh['description'] ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $isha_sh_total ) : ?>
			<nav class="isha-sh__fan" aria-label="<?php esc_attr_e( 'Nhóm dịch vụ', 'isha' ); ?>">
				<div class="isha-sh__wheel" data-isha-sh-wheel<?php echo $isha_sh_start >= 0 ? ' data-start="' . esc_attr( (string) $isha_sh_start ) . '"' : ''; ?> style="--n: <?php echo esc_attr( (string) $isha_sh_total ); ?>">
					<?php
					$isha_sh_seen = array();
					foreach ( $isha_sh_slots as $isha_sh_pos => $isha_sh_index ) :
						if ( ! isset( $isha_sh_groups[ $isha_sh_index ] ) ) {
							continue;
						}
						$isha_sh_group = $isha_sh_groups[ $isha_sh_index ];
						$isha_sh_copy  = isset( $isha_sh_seen[ $isha_sh_index ] );
						$isha_sh_seen[ $isha_sh_index ] = true;
						$isha_sh_tag   = '' !== $isha_sh_group['url'] ? 'a' : 'span';
						$isha_sh_cur   = $isha_sh_current && isset( $isha_sh_group['id'] ) && $isha_sh_current === (int) $isha_sh_group['id'];
						$isha_sh_attr  = '';
						if ( $isha_sh_copy ) {
							$isha_sh_attr = ' aria-hidden="true" tabindex="-1"';
						} elseif ( $isha_sh_cur ) {
							$isha_sh_attr = ' aria-current="page"';
						}
						?>
						<<?php echo $isha_sh_tag; // phpcs:ignore WordPress.Security.EscapeOutput -- 'a' hoặc 'span'. ?> class="isha-sh__card<?php echo $isha_sh_copy ? ' is-copy' : ''; ?><?php echo $isha_sh_cur ? ' is-current' : ''; ?>" style="--i: <?php echo esc_attr( (string) $isha_sh_pos ); ?>"<?php echo 'a' === $isha_sh_tag ? ' href="' . esc_url( $isha_sh_group['url'] ) . '"' : ''; ?><?php echo $isha_sh_attr; // phpcs:ignore WordPress.Security.EscapeOutput -- chuỗi cố định. ?> data-isha-sh-card>
							<span class="isha-sh__pill">
								<span class="isha-sh__thumb" aria-hidden="true">
									<?php
									if ( $isha_sh_group['thumb'] ) {
										echo wp_get_attachment_image( (int) $isha_sh_group['thumb'], 'thumbnail', false, array( 'alt' => '', 'decoding' => 'async', 'sizes' => '40px' ) );
									} else {
										echo esc_html( mb_substr( $isha_sh_group['name'], 0, 1, 'UTF-8' ) );
									}
									?>
								</span>
								<span class="isha-sh__name"><?php echo esc_html( $isha_sh_group['name'] ); ?></span>
							</span>
						</<?php echo $isha_sh_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php endforeach; ?>
				</div>
			</nav>
		<?php endif; ?>

		<a class="isha-sh__dock" href="#isha-services-list">
			<svg class="isha-sh__mound" viewBox="0 0 320 112" preserveAspectRatio="none" aria-hidden="true" focusable="false">
				<path d="M0 112C58 112 70 6 160 6S262 112 320 112Z"></path>
			</svg>
			<span class="isha-sh__logo" role="img" aria-label="ISHA"></span>
			<span class="isha-sh__brand"><?php esc_html_e( 'Beauty Clinic', 'isha' ); ?></span>
			<span class="screen-reader-text"><?php esc_html_e( '– xem danh sách dịch vụ', 'isha' ); ?></span>
		</a>
	</div>
</section>
