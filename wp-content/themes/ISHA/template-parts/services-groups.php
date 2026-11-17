<?php
/**
 * Lưới các nhóm dịch vụ của trang Dịch vụ tổng (template-parts/services-page.php), 18/11/2026: nằm sau vùng nội dung Elementor, lấy tự động
 * mọi nhóm dịch vụ (term isha_service_cat) theo thứ tự ở mục Nhóm dịch vụ. Mỗi thẻ là một liên kết tới trang nhóm: ảnh bìa dọc 4:5 trong
 * khung vàng kim lệch phía sau (như section "Câu chuyện nhà sáng lập"), số thứ tự, tên nhóm, mô tả của nhóm, số liệu trình.
 * Giao diện ở assets/css/services-groups.css. Không có nhóm nào thì không in gì.
 *
 * Dữ liệu: `$args['groups']` = isha_services_hero_groups() (id, name, url, thumb, desc, count).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_groups = ( isset( $args['groups'] ) && is_array( $args['groups'] ) ) ? $args['groups'] : array();
$isha_groups = array_values(
	array_filter(
		$isha_groups,
		static function ( $g ) {
			return is_array( $g ) && ! empty( $g['name'] ) && ! empty( $g['url'] );
		}
	)
);
if ( ! $isha_groups ) {
	return;
}
?>
<section class="isha-svc-groups" aria-labelledby="isha-svc-groups-title">
	<div class="container isha-svc-groups__inner">
		<header class="isha-svc-groups__head">
			<h2 id="isha-svc-groups-title" class="isha-svc-groups__title"><?php esc_html_e( 'Các nhóm dịch vụ', 'isha' ); ?></h2>
			<p class="isha-svc-groups__lead"><?php esc_html_e( 'Mỗi nhóm là một hành trình chăm sóc riêng, cùng một chuẩn mực y khoa.', 'isha' ); ?></p>
		</header>
		<ul class="isha-svc-groups__list" role="list">
			<?php foreach ( $isha_groups as $isha_n => $isha_group ) : ?>
				<?php
				$isha_count = isset( $isha_group['count'] ) ? (int) $isha_group['count'] : 0;
				$isha_thumb = isset( $isha_group['thumb'] ) ? (int) $isha_group['thumb'] : 0;
				$isha_img   = $isha_thumb > 0 ? wp_get_attachment_image(
					$isha_thumb,
					'isha-frame',
					false,
					array(
						'class'    => 'isha-svc-groups__img',
						'alt'      => '',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(max-width: 600px) min(88vw, 420px), (max-width: 900px) 44vw, 30vw',
					)
				) : '';
				?>
				<li class="isha-svc-groups__item">
					<a class="isha-svc-groups__card" href="<?php echo esc_url( (string) $isha_group['url'] ); ?>">
						<span class="isha-svc-groups__media">
							<span class="isha-svc-groups__frame">
								<?php echo $isha_img; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image() trả thẻ <img> đã escape. ?>
							</span>
						</span>
						<span class="isha-svc-groups__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $isha_n + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="isha-svc-groups__name"><?php echo esc_html( (string) $isha_group['name'] ); ?></span>
						<?php if ( ! empty( $isha_group['desc'] ) ) : ?>
							<span class="isha-svc-groups__desc"><?php echo esc_html( (string) $isha_group['desc'] ); ?></span>
						<?php endif; ?>
						<?php if ( $isha_count > 0 ) : ?>
							<span class="isha-svc-groups__meta">
								<?php
								/* translators: %d: số liệu trình trong nhóm. */
								echo esc_html( sprintf( _n( '%d liệu trình', '%d liệu trình', $isha_count, 'isha' ), $isha_count ) );
								?>
							</span>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
