<?php
/**
 * Section "Bình minh lan tỏa" (services cinematic) của trang nhóm dịch vụ. Gọi qua isha_service_group_sections() (inc/service-group.php)
 * khi isha_svc_active(); dữ liệu: isha_svc_data( $term ) (inc/services-cinematic.php).
 *
 * Markup SSR là nội dung thật (SEO, không JS, giảm chuyển động): danh sách <ol> các <article class="isha-card"> (ảnh, tên, mô tả, thời gian,
 * "Xem chi tiết", "Đặt lịch tư vấn riêng") trên nền poster line-art. JS (assets/js/services-cinematic/main.js) thêm .is-enhanced lên section
 * để chuyển sang chế độ cinematic: section cao N x đoạn + 100svh (đoạn mặc định 180svh desktop, 150svh cảm ứng), stage dính (sticky) cao 100svh chứa canvas WebGL, card kính mờ ở giữa và
 * nhãn thời điểm. Canvas, poster, tia nắng và nhãn thời điểm chỉ để trang trí (aria-hidden).
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
$isha_svc_cta  = isset( $isha_svc['cta'] ) && is_array( $isha_svc['cta'] ) ? $isha_svc['cta'] : array();
$isha_svc_pad  = static function ( $n ) {
	return str_pad( (string) $n, 2, '0', STR_PAD_LEFT );
};
$isha_svc_json = $isha_svc;
// Cờ theo request (không nằm trong cache transient): bảng debug cho quản trị viên.
$isha_svc_json['debug'] = function_exists( 'isha_svc_debug_enabled' ) && isha_svc_debug_enabled();
$isha_svc_json['base']  = ISHA_URI . '/assets/js/services-cinematic/';

// Poster line-art: ba dải núi bằng nét dọc (dày ở chân, mờ dần lên), nửa mặt trời với quạt tia mảnh. Toạ độ trong khung 1600 x 900.
$isha_svc_ridges = array(
	// array( đường viền đỉnh (cặp x,y), lớp: 0 xa nhất ).
	array( '0,640 180,560 330,600 470,520 620,470 800,300 980,470 1130,520 1270,590 1420,560 1600,620', 0 ),
	array( '0,700 160,650 300,690 470,610 640,640 800,560 960,640 1140,600 1310,680 1460,640 1600,700', 1 ),
	array( '0,780 220,740 420,770 600,720 800,760 1000,720 1200,770 1400,740 1600,780', 2 ),
);
$isha_svc_rays = '';
for ( $isha_svc_i = 0; $isha_svc_i < 25; $isha_svc_i++ ) {
	$isha_svc_a     = deg2rad( 180 + $isha_svc_i * 7.5 );
	$isha_svc_rays .= sprintf( '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f"/>', 800 + 70 * cos( $isha_svc_a ), 300 + 70 * sin( $isha_svc_a ), 800 + 220 * cos( $isha_svc_a ), 300 + 220 * sin( $isha_svc_a ) );
}
?>
<section id="lieu-trinh" class="isha-svc" data-tone="day" style="--isha-svc-n: <?php echo esc_attr( (string) $isha_svc_n ); ?>" aria-labelledby="isha-svc-title" data-isha-svc>
	<div class="isha-svc__stage">
		<div class="isha-svc__poster" aria-hidden="true">
			<svg class="isha-svc__lineart" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMax slice" focusable="false">
				<defs>
					<pattern id="isha-svc-hatch" width="7" height="900" patternUnits="userSpaceOnUse">
						<line x1="1" y1="0" x2="1" y2="900"/>
					</pattern>
					<linearGradient id="isha-svc-fade" x1="0" y1="0" x2="0" y2="1">
						<stop offset="0" stop-color="#fff" stop-opacity="0"/>
						<stop offset="0.45" stop-color="#fff" stop-opacity="0.35"/>
						<stop offset="1" stop-color="#fff" stop-opacity="1"/>
					</linearGradient>
					<mask id="isha-svc-mask" maskContentUnits="objectBoundingBox">
						<rect width="1" height="1" fill="url(#isha-svc-fade)"/>
					</mask>
				</defs>
				<g class="isha-svc__sun">
					<g class="isha-svc__sunrays"><?php echo $isha_svc_rays; // phpcs:ignore WordPress.Security.EscapeOutput -- chỉ toạ độ số do sprintf %f dựng. ?></g>
					<path class="isha-svc__sundisc" d="M740 300 a60 60 0 0 1 120 0 z"/>
				</g>
				<?php foreach ( $isha_svc_ridges as $isha_svc_r ) : ?>
					<polygon class="isha-svc__ridge isha-svc__ridge--<?php echo esc_attr( (string) $isha_svc_r[1] ); ?>" points="<?php echo esc_attr( $isha_svc_r[0] . ' 1600,900 0,900' ); ?>" fill="url(#isha-svc-hatch)" mask="url(#isha-svc-mask)"/>
				<?php endforeach; ?>
			</svg>
		</div>
		<canvas class="isha-svc__canvas" aria-hidden="true" role="presentation"></canvas>
		<?php
		// Vòng tia line-art FOCUS-MINDFUL quanh mặt trời (assets/images/vector-1.svg), JS đặt theo vị trí chiếu của mặt trời. Gradient tô bằng token.
		$isha_svc_icon = function_exists( 'isha_service_group_handle_icon' ) ? isha_service_group_handle_icon() : array( 'viewbox' => '', 'lines' => '' );
		?>
		<?php if ( '' !== $isha_svc_icon['lines'] ) : ?>
			<svg class="isha-svc__rays" viewBox="<?php echo esc_attr( $isha_svc_icon['viewbox'] ); ?>" aria-hidden="true" focusable="false">
				<defs>
					<linearGradient id="isha-svc-rays-grad" x1="0" y1="0" x2="1" y2="1">
						<stop offset="0" class="isha-svc__rays-stop isha-svc__rays-stop--a"/>
						<stop offset="0.5" class="isha-svc__rays-stop isha-svc__rays-stop--b"/>
						<stop offset="1" class="isha-svc__rays-stop isha-svc__rays-stop--c"/>
					</linearGradient>
				</defs>
				<g class="isha-svc__rays-spin" stroke="url(#isha-svc-rays-grad)" stroke-linecap="round" fill="none"><?php echo $isha_svc_icon['lines']; // phpcs:ignore WordPress.Security.EscapeOutput -- chỉ gồm <line> với toạ độ số do sprintf %f dựng. ?></g>
			</svg>
		<?php endif; ?>

		<div class="isha-wrap isha-svc__head">
			<p class="isha-svc__eyebrow"><?php esc_html_e( 'Các liệu trình trong nhóm', 'isha' ); ?><span class="isha-svc__daylabel" aria-hidden="true"></span></p>
			<h2 id="isha-svc-title" class="isha-sec__title isha-svc__heading"><?php echo esc_html( $isha_svc_cat ); ?></h2>
		</div>

		<ol class="isha-svc__list">
			<?php foreach ( $isha_svc_list as $isha_svc_k => $isha_svc_it ) : ?>
				<?php $isha_svc_tid = 'isha-svc-card-' . (int) $isha_svc_it['id']; ?>
				<li class="isha-svc__item">
					<article class="isha-card" data-index="<?php echo esc_attr( (string) $isha_svc_k ); ?>" aria-labelledby="<?php echo esc_attr( $isha_svc_tid ); ?>">
						<div class="isha-card__face">
							<?php if ( ! empty( $isha_svc_it['image'] ) ) : ?>
								<div class="isha-card__media">
									<img class="isha-card__img" src="<?php echo esc_url( $isha_svc_it['image']['src'] ); ?>"<?php echo '' !== $isha_svc_it['image']['srcset'] ? ' srcset="' . esc_attr( $isha_svc_it['image']['srcset'] ) . '" sizes="' . esc_attr( $isha_svc_it['image']['sizes'] ) . '"' : ''; ?> width="<?php echo esc_attr( (string) $isha_svc_it['image']['w'] ); ?>" height="<?php echo esc_attr( (string) $isha_svc_it['image']['h'] ); ?>" alt="<?php echo esc_attr( $isha_svc_it['image']['alt'] ); ?>" loading="lazy" decoding="async">
								</div>
							<?php endif; ?>
							<div class="isha-card__body">
								<p class="isha-card__count" aria-hidden="true"><span><?php echo esc_html( $isha_svc_pad( $isha_svc_k + 1 ) ); ?></span><span class="isha-card__dash"></span><span><?php echo esc_html( $isha_svc_pad( $isha_svc_n ) ); ?></span></p>
								<h3 id="<?php echo esc_attr( $isha_svc_tid ); ?>" class="isha-card__title"><a href="<?php echo esc_url( $isha_svc_it['url'] ); ?>"><?php echo esc_html( $isha_svc_it['title'] ); ?></a></h3>
								<?php if ( '' !== $isha_svc_it['excerpt'] ) : ?>
									<p class="isha-card__desc"><?php echo esc_html( $isha_svc_it['excerpt'] ); ?></p>
								<?php endif; ?>
								<?php if ( '' !== $isha_svc_it['duration'] ) : ?>
									<p class="isha-card__meta">
										<svg class="isha-card__clock" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="6.5"/><path d="M8 4.5V8l2.4 1.6"/></svg>
										<span><?php esc_html_e( 'Thời gian:', 'isha' ); ?> <?php echo esc_html( $isha_svc_it['duration'] ); ?></span>
									</p>
								<?php endif; ?>
								<div class="isha-card__actions">
									<a class="isha-btn isha-btn--ghost isha-card__more" href="<?php echo esc_url( $isha_svc_it['url'] ); ?>"><?php esc_html_e( 'Xem chi tiết', 'isha' ); ?><span class="isha-sr">: <?php echo esc_html( $isha_svc_it['title'] ); ?></span></a>
									<?php if ( ! empty( $isha_svc_cta['url'] ) ) : ?>
										<a class="isha-btn isha-btn--primary isha-card__cta" href="<?php echo esc_url( $isha_svc_cta['url'] ); ?>"><?php echo esc_html( $isha_svc_cta['label'] ); ?></a>
									<?php endif; ?>
								</div>
							</div>
						</div>
						<div class="isha-card__back" aria-hidden="true"></div>
					</article>
				</li>
			<?php endforeach; ?>
		</ol>

		<p class="isha-sr" aria-live="polite" id="isha-svc-live"></p>
	</div>
	<script type="application/json" id="isha-svc-data"><?php echo wp_json_encode( $isha_svc_json, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
</section>
