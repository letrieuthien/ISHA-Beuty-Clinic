<?php
/**
 * Section "Bình minh lan tỏa" (services cinematic) của trang nhóm dịch vụ. Gọi qua isha_service_group_sections() (inc/service-group.php)
 * khi isha_svc_active(); dữ liệu: isha_svc_data( $term ) (inc/services-cinematic.php).
 *
 * Markup SSR là nội dung thật (SEO, không JS, giảm chuyển động): vòng cung ánh kim (.isha-ring, <ul> các <li> thẻ hai mặt). Mặt trước: ảnh
 * phủ kín thẻ, tên liệu trình đè góc dưới. Mặt sau: thời gian liệu trình, mô tả ngắn, "Xem chi tiết". Chưa có JS: hàng cuộn ngang scroll-snap
 * trên nền poster (rê chuột / Tab vào thẻ thì lật). JS (assets/js/services-cinematic/main.js) nâng cấp vòng thẻ (assets/js/card-ring/)
 * thành vòng 3D xoay và thêm .is-enhanced lên section để chuyển sang chế độ cinematic: section cao N x đoạn + 100svh, stage dính (sticky)
 * cao 100svh chứa canvas WebGL, vòng thẻ ở chân núi, tia nắng của mặt trời chạm viền thẻ.
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

		<?php
		// Vòng cung ánh kim (assets/css/card-ring.css, assets/js/card-ring/; README-card-ring.md): thẻ hai mặt. Chưa có JS: hàng cuộn ngang
		// scroll-snap, viền vàng tĩnh, rê chuột / Tab vào thẻ thì lật ra mặt sau. JS nâng cấp thành vòng 3D xoay ở chân núi (main.js).
		// __glint (ánh kim chạy), __hit / __flare (chỗ tia nắng chạm viền) là lớp trang trí rỗng.
		?>
		<div class="isha-ring isha-svc__ring" data-isha-ring>
			<ul class="isha-ring__ring" role="list" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: tên nhóm dịch vụ */ __( 'Các liệu trình %s', 'isha' ), $isha_svc_cat ) ); ?>">
				<?php foreach ( $isha_svc_list as $isha_svc_k => $isha_svc_it ) : ?>
					<li class="isha-ring__card">
						<div class="isha-ring__flip">
							<div class="isha-ring__side isha-ring__side--front">
								<div class="isha-ring__frame">
									<span class="isha-ring__glint" aria-hidden="true"></span><span class="isha-ring__hit" aria-hidden="true"></span>
									<div class="isha-ring__face">
										<?php if ( ! empty( $isha_svc_it['image'] ) ) : ?>
											<img class="isha-ring__img" src="<?php echo esc_url( $isha_svc_it['image']['src'] ); ?>"<?php echo '' !== $isha_svc_it['image']['srcset'] ? ' srcset="' . esc_attr( $isha_svc_it['image']['srcset'] ) . '" sizes="(max-width: 767px) 44vw, 220px"' : ''; ?> width="<?php echo esc_attr( (string) $isha_svc_it['image']['w'] ); ?>" height="<?php echo esc_attr( (string) $isha_svc_it['image']['h'] ); ?>" alt="<?php echo esc_attr( $isha_svc_it['image']['alt'] ); ?>" decoding="async"<?php echo $isha_svc_k > 2 ? ' loading="lazy"' : ''; ?>>
										<?php endif; ?>
										<h3 class="isha-ring__title"><?php echo esc_html( $isha_svc_it['title'] ); ?></h3>
									</div>
								</div>
								<span class="isha-ring__flare" aria-hidden="true"></span>
							</div>
							<div class="isha-ring__side isha-ring__side--back">
								<div class="isha-ring__frame">
									<span class="isha-ring__glint" aria-hidden="true"></span><span class="isha-ring__hit" aria-hidden="true"></span>
									<div class="isha-ring__face isha-ring__face--back">
										<?php if ( '' !== $isha_svc_it['duration'] ) : ?>
											<div class="isha-ring__meta">
												<p class="isha-ring__meta-label"><svg class="isha-ring__clock" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="6.5"/><path d="M8 4.5V8l2.4 1.6"/></svg><span><?php esc_html_e( 'Thời gian liệu trình', 'isha' ); ?></span></p>
												<p class="isha-ring__meta-value"><?php echo esc_html( $isha_svc_it['duration'] ); ?></p>
											</div>
										<?php endif; ?>
										<?php if ( '' !== $isha_svc_it['excerpt'] ) : ?>
											<p class="isha-ring__desc"><?php echo esc_html( $isha_svc_it['excerpt'] ); ?></p>
										<?php endif; ?>
										<a class="isha-ring__more" href="<?php echo esc_url( $isha_svc_it['url'] ); ?>"><?php esc_html_e( 'Xem chi tiết', 'isha' ); ?><span class="isha-sr">: <?php echo esc_html( $isha_svc_it['title'] ); ?></span></a>
									</div>
								</div>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<p class="isha-sr" aria-live="polite" id="isha-svc-live"></p>
	</div>
	<script type="application/json" id="isha-svc-data"><?php echo wp_json_encode( $isha_svc_json, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
</section>
