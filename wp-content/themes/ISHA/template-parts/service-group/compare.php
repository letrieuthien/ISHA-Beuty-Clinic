<?php
/**
 * Khung kéo so sánh trước/sau (.isha-sg-ba) dùng chung: section "Hiệu quả liệu trình" và cột trái của "Giới thiệu dịch vụ" (desktop).
 *
 * Mỗi cặp là một figure gồm hai lớp ảnh chồng nhau, lớp "sau" bị cắt bởi clip-path: inset(0 0 0 var(--ba)); đường chia dọc và nút kéo nằm
 * ở --ba. Chỉ cặp đầu hiện khi tải trang, các cặp sau `hidden` (ảnh lazy nên chưa tải). Nút kéo (role="slider") in sẵn ở dạng hidden;
 * service-group.js bỏ hidden và nối kéo chuột/chạm/phím. Không có JS: khung chia đôi tĩnh ở giữa.
 *
 * Dữ liệu: `$args` = array( 'id' => id DOM của khung (duy nhất trong trang), 'items' => isha_service_group_ba_images(), 'class' => class thêm ).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_cmp_items = ( isset( $args['items'] ) && is_array( $args['items'] ) ) ? array_values( $args['items'] ) : array();
$isha_cmp_id    = ( isset( $args['id'] ) && is_scalar( $args['id'] ) ) ? sanitize_html_class( (string) $args['id'] ) : 'isha-sg-ba';
$isha_cmp_class = ( isset( $args['class'] ) && is_scalar( $args['class'] ) ) ? trim( (string) $args['class'] ) : '';

if ( ! $isha_cmp_items ) {
	return;
}

/* translators: 1: phần trăm ảnh trước đang hiện, 2: phần trăm ảnh sau đang hiện. Giữ nguyên %1$d, %2$d và %%. */
$isha_cmp_valuetext = __( '%1$d%% ảnh trước, %2$d%% ảnh sau', 'isha' );

// Icon FOCUS-MINDFUL của nút kéo (assets/images/vector-1.svg, đọc qua isha_service_group_handle_icon()), tô gradient vàng kim
// #FCF6BA → #BF953F theo toạ độ viewBox (userSpaceOnUse: tia ngang/dọc có khung bao rộng 0 nên gradient theo khung bao sẽ không tô được).
// Tệp thiếu thì dùng họa tiết tia nắng dự phòng 16 tia.
$isha_cmp_grad = $isha_cmp_id . '-ray';
$isha_cmp_icon = function_exists( 'isha_service_group_handle_icon' ) ? isha_service_group_handle_icon() : array( 'viewbox' => '', 'lines' => '' );
if ( '' === $isha_cmp_icon['lines'] ) {
	$isha_cmp_icon = array( 'viewbox' => '0 0 200 200', 'lines' => '' );
	for ( $isha_cmp_i = 0; $isha_cmp_i < 16; $isha_cmp_i++ ) {
		$isha_cmp_a              = deg2rad( $isha_cmp_i * 22.5 );
		$isha_cmp_icon['lines'] .= sprintf( '<line x1="%1$.2f" y1="%2$.2f" x2="%3$.2f" y2="%4$.2f"/>', 100 + 38 * cos( $isha_cmp_a ), 100 + 38 * sin( $isha_cmp_a ), 100 + 80 * cos( $isha_cmp_a ), 100 + 80 * sin( $isha_cmp_a ) );
	}
}
$isha_cmp_vb = array_map( 'floatval', preg_split( '/\s+/', $isha_cmp_icon['viewbox'] ) ) + array( 0, 0, 200, 200 );
?>
<div id="<?php echo esc_attr( $isha_cmp_id ); ?>" class="<?php echo esc_attr( trim( 'isha-sg-ba ' . $isha_cmp_class ) ); ?>" data-isha-sg-ba>
	<?php foreach ( $isha_cmp_items as $isha_cmp_k => $isha_cmp_item ) : ?>
		<figure class="isha-sg-ba__pair" data-index="<?php echo esc_attr( (string) $isha_cmp_k ); ?>"<?php echo 0 === $isha_cmp_k ? '' : ' hidden'; ?>>
			<div class="isha-sg-ba__layer isha-sg-ba__layer--before">
				<?php echo $isha_cmp_item['before']; // phpcs:ignore WordPress.Security.EscapeOutput -- thẻ img do isha_home_image() dựng và đã escape. ?>
				<span class="isha-label isha-sg-ba__tag" aria-hidden="true"><?php esc_html_e( 'Trước', 'isha' ); ?></span>
			</div>
			<div class="isha-sg-ba__layer isha-sg-ba__layer--after">
				<?php echo $isha_cmp_item['after']; // phpcs:ignore WordPress.Security.EscapeOutput -- thẻ img do isha_home_image() dựng và đã escape. ?>
				<span class="isha-label isha-sg-ba__tag" aria-hidden="true"><?php esc_html_e( 'Sau', 'isha' ); ?></span>
			</div>
		</figure>
	<?php endforeach; ?>
	<span class="isha-sg-ba__divider" aria-hidden="true"></span>
	<div class="isha-sg-ba__handle" role="slider" tabindex="0" aria-label="<?php esc_attr_e( 'Kéo để so sánh ảnh trước và sau điều trị', 'isha' ); ?>" aria-controls="<?php echo esc_attr( $isha_cmp_id ); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50" aria-valuetext="<?php echo esc_attr( sprintf( $isha_cmp_valuetext, 50, 50 ) ); ?>" data-valuetext="<?php echo esc_attr( $isha_cmp_valuetext ); ?>" hidden>
		<svg class="isha-sg-ba__sun" viewBox="<?php echo esc_attr( $isha_cmp_icon['viewbox'] ); ?>" aria-hidden="true" focusable="false">
			<defs>
				<linearGradient id="<?php echo esc_attr( $isha_cmp_grad ); ?>" gradientUnits="userSpaceOnUse" x1="<?php echo esc_attr( (string) $isha_cmp_vb[0] ); ?>" y1="<?php echo esc_attr( (string) $isha_cmp_vb[1] ); ?>" x2="<?php echo esc_attr( (string) ( $isha_cmp_vb[0] + $isha_cmp_vb[2] ) ); ?>" y2="<?php echo esc_attr( (string) ( $isha_cmp_vb[1] + $isha_cmp_vb[3] ) ); ?>">
					<stop offset="0" stop-color="#FCF6BA"/>
					<stop offset="0.55" stop-color="#E6C77A"/>
					<stop offset="1" stop-color="#BF953F"/>
				</linearGradient>
			</defs>
			<g class="isha-sg-ba__rays" stroke="url(#<?php echo esc_attr( $isha_cmp_grad ); ?>)" stroke-linecap="round" fill="none"><?php echo $isha_cmp_icon['lines']; // phpcs:ignore WordPress.Security.EscapeOutput -- chỉ gồm <line> với toạ độ số do sprintf %f dựng. ?></g>
		</svg>
	</div>
</div>
