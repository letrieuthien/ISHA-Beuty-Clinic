<?php
/**
 * Section "Bình minh lan tỏa" (services cinematic) của trang nhóm dịch vụ. Gọi qua isha_service_group_sections() (inc/service-group.php)
 * khi isha_svc_active(); dữ liệu: isha_svc_data( $term ) (inc/services-cinematic.php).
 *
 * Markup SSR là nội dung thật (SEO, không JS, giảm chuyển động): danh sách <ol> các <article class="isha-card"> hai mặt. Mặt trước: ảnh phủ
 * kín card (dưới ảnh là khung chờ vector núi + mặt trời, hiện khi ảnh đang tải hoặc không có ảnh) và tên liệu trình đè lên góc dưới. Mặt sau:
 * thời gian, mô tả ngắn, "Xem chi tiết". Chế độ tĩnh: hai mặt xếp chồng trong lưới thẻ trên nền poster (ảnh cảnh đã tô màu). JS
 * (assets/js/services-cinematic/main.js) thêm .is-enhanced lên section để chuyển sang chế độ cinematic: section cao N x đoạn + 100svh, stage
 * dính (sticky) cao 100svh chứa canvas WebGL, dãy card trượt ngang ở chân núi (card lật khi rê chuột / focus / chạm).
 * Canvas và poster chỉ để trang trí (aria-hidden).
 *
 * Dữ liệu: `$args` = array( 'data' => isha_svc_data( $term ) ).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_svc      = ( isset( $args['data'] ) && is_array( $args['data'] ) ) ? $args['data'] : array();
$isha_svc_list = ( isset( $isha_svc['services'] ) && is_array( $isha_svc['services'] ) ) ? array_values( $isha_svc['services'] ) : array();
if ( ! $isha_svc_list ) {
	return;
}

$isha_svc_n    = count( $isha_svc_list );
$isha_svc_cat  = isset( $isha_svc['category']['name'] ) ? (string) $isha_svc['category']['name'] : '';
$isha_svc_json = $isha_svc;
// Cờ theo request (không nằm trong cache transient): bảng debug cho quản trị viên.
$isha_svc_json['debug'] = function_exists( 'isha_svc_debug_enabled' ) && isha_svc_debug_enabled();
$isha_svc_json['base']  = ISHA_URI . '/assets/js/services-cinematic/';

// Poster: chính cảnh v3 đã tô màu ở giữa đoạn cuộn (tools/bake-scene.mjs). Khung dọc dùng bản dọc (bố cục cảnh khác: ảnh fit ngang).
$isha_svc_img = ISHA_URI . '/assets/img/scene/';
?>
<section id="lieu-trinh" class="isha-svc" data-tone="day" style="--isha-svc-n: <?php echo esc_attr( (string) $isha_svc_n ); ?>" aria-labelledby="isha-svc-title" data-isha-svc>
	<div class="isha-svc__stage">
		<div class="isha-svc__poster" aria-hidden="true">
			<picture>
				<source media="(orientation: portrait)" srcset="<?php echo esc_url( $isha_svc_img . 'poster-m.webp' ); ?>" width="860" height="1864">
				<img class="isha-svc__poster-img" src="<?php echo esc_url( $isha_svc_img . 'poster.webp' ); ?>" width="1920" height="1080" alt="" decoding="async" loading="lazy">
			</picture>
		</div>
		<canvas class="isha-svc__canvas" aria-hidden="true" role="presentation"></canvas>

		<div class="isha-wrap isha-svc__head">
			<p class="isha-svc__eyebrow"><?php esc_html_e( 'Các liệu trình trong nhóm', 'isha' ); ?></p>
			<h2 id="isha-svc-title" class="isha-sec__title isha-svc__heading"><?php echo esc_html( $isha_svc_cat ); ?></h2>
		</div>

		<ol class="isha-svc__list">
			<?php foreach ( $isha_svc_list as $isha_svc_k => $isha_svc_it ) : ?>
				<?php $isha_svc_tid = 'isha-svc-card-' . (int) $isha_svc_it['id']; ?>
				<li class="isha-svc__item" style="--i: <?php echo esc_attr( (string) $isha_svc_k ); ?>">
					<article class="isha-card" data-index="<?php echo esc_attr( (string) $isha_svc_k ); ?>" aria-labelledby="<?php echo esc_attr( $isha_svc_tid ); ?>">
						<div class="isha-card__inner">
							<div class="isha-card__face isha-card__front">
								<div class="isha-card__media">
									<?php // Khung chờ: nét vector vàng hình núi + mặt trời (CSS), hiện khi ảnh đang tải lazy hoặc không có ảnh. ?>
									<span class="isha-card__skeleton" aria-hidden="true"><span class="isha-card__skeleton-sun"></span></span>
									<?php if ( ! empty( $isha_svc_it['image'] ) ) : ?>
										<img class="isha-card__img" src="<?php echo esc_url( $isha_svc_it['image']['src'] ); ?>"<?php echo '' !== $isha_svc_it['image']['srcset'] ? ' srcset="' . esc_attr( $isha_svc_it['image']['srcset'] ) . '" sizes="(max-width: 767px) 44vw, 220px"' : ''; ?> width="<?php echo esc_attr( (string) $isha_svc_it['image']['w'] ); ?>" height="<?php echo esc_attr( (string) $isha_svc_it['image']['h'] ); ?>" alt="<?php echo esc_attr( $isha_svc_it['image']['alt'] ); ?>" loading="lazy" decoding="async">
									<?php endif; ?>
								</div>
								<h3 id="<?php echo esc_attr( $isha_svc_tid ); ?>" class="isha-card__title"><?php echo esc_html( $isha_svc_it['title'] ); ?></h3>
							</div>
							<div class="isha-card__face isha-card__back">
								<?php if ( '' !== $isha_svc_it['duration'] ) : ?>
									<p class="isha-card__meta">
										<svg class="isha-card__clock" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="6.5"/><path d="M8 4.5V8l2.4 1.6"/></svg>
										<span><?php esc_html_e( 'Thời gian:', 'isha' ); ?> <?php echo esc_html( $isha_svc_it['duration'] ); ?></span>
									</p>
								<?php endif; ?>
								<?php if ( '' !== $isha_svc_it['excerpt'] ) : ?>
									<p class="isha-card__desc"><?php echo esc_html( $isha_svc_it['excerpt'] ); ?></p>
								<?php endif; ?>
								<a class="isha-btn isha-btn--ghost isha-card__more" href="<?php echo esc_url( $isha_svc_it['url'] ); ?>"><?php esc_html_e( 'Xem chi tiết', 'isha' ); ?><span class="isha-sr">: <?php echo esc_html( $isha_svc_it['title'] ); ?></span></a>
							</div>
						</div>
					</article>
				</li>
			<?php endforeach; ?>
		</ol>

		<p class="isha-sr" aria-live="polite" id="isha-svc-live"></p>
	</div>
	<script type="application/json" id="isha-svc-data"><?php echo wp_json_encode( $isha_svc_json, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
</section>
