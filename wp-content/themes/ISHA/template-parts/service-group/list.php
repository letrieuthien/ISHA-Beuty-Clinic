<?php
/**
 * Section "Các liệu trình trong nhóm" của trang nhóm dịch vụ, tông day. Thay cho lưới thẻ của shortcode [isha_services] (style cũ của plugin).
 *
 * Mỗi liệu trình là một thẻ editorial theo hệ thiết kế trang chủ: ảnh dọc 4:5 không khung, đường kẻ 1px, thời gian liệu trình (nhãn vàng),
 * tên (Garamond), mô tả ngắn tối đa 2 dòng, mũi tên ở chân thẻ. Liên kết nằm trên tên; ::after của liên kết phủ cả thẻ nên bấm chỗ nào cũng
 * mở trang chi tiết, còn tên truy cập của liên kết chỉ là tên liệu trình. Thiếu ảnh thì khối giữ chỗ theo tông; mô tả/thời gian trống
 * hoặc là chữ giữ chỗ thì bỏ dòng đó (đã lọc ở isha_service_group_items()).
 *
 * Số cột theo số liệu trình (--cols): 1 → một thẻ canh giữa, 2 → 2, 3 → 3, 4 → 4, từ 5 → 3 (hàng lẻ cuối canh giữa). Tablet tối đa 3
 * (4 → 2x2), chế độ gọn là hàng vuốt ngang (service-group.css).
 *
 * Dữ liệu: `$args` = array( 'name' => tên nhóm, 'items' => isha_service_group_items( $term ) ).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_sg_list = ( isset( $args['items'] ) && is_array( $args['items'] ) ) ? array_values( $args['items'] ) : array();
if ( ! $isha_sg_list ) {
	return;
}

$isha_sg_n    = count( $isha_sg_list );
$isha_sg_cols = ( $isha_sg_n >= 2 && $isha_sg_n <= 4 ) ? $isha_sg_n : 3;
?>
<section id="lieu-trinh" class="isha-sec isha-sg-list" data-tone="day" aria-labelledby="isha-sg-list-title">
	<div class="isha-wrap">
		<div class="isha-sec__head isha-sg-list__head">
			<h2 id="isha-sg-list-title" class="isha-sec__title"><?php esc_html_e( 'Các liệu trình trong nhóm', 'isha' ); ?></h2>
		</div>

		<ul class="isha-sg-list__grid" style="--cols: <?php echo esc_attr( (string) $isha_sg_cols ); ?>" data-count="<?php echo esc_attr( (string) $isha_sg_n ); ?>">
			<?php foreach ( $isha_sg_list as $isha_sg_k => $isha_sg_it ) : ?>
				<?php $isha_sg_tid = 'isha-sg-item-' . (int) $isha_sg_it['id']; ?>
				<li class="isha-sg-list__item">
					<article class="isha-sg-item" aria-labelledby="<?php echo esc_attr( $isha_sg_tid ); ?>">
						<div class="isha-sg-item__media">
							<?php
							isha_home_media(
								$isha_sg_it['thumb'],
								__( 'Ảnh đại diện liệu trình', 'isha' ),
								array(
									'size'       => 'isha-frame',
									'sizes'      => '(max-width: 900px) 72vw, (max-width: 1240px) 33vw, 380px',
									'class'      => 'isha-sg-item__img',
									'ratio'      => '45',
									'decorative' => true,
								)
							);
							?>
						</div>
						<div class="isha-sg-item__body">
							<?php if ( '' !== $isha_sg_it['duration'] ) : ?>
								<p class="isha-sg-item__meta"><?php echo esc_html( $isha_sg_it['duration'] ); ?></p>
							<?php endif; ?>
							<h3 id="<?php echo esc_attr( $isha_sg_tid ); ?>" class="isha-sg-item__title">
								<a class="isha-sg-item__link" href="<?php echo esc_url( $isha_sg_it['url'] ); ?>"><?php echo esc_html( $isha_sg_it['title'] ); ?></a>
							</h3>
							<?php if ( '' !== $isha_sg_it['desc'] ) : ?>
								<p class="isha-sg-item__desc"><?php echo esc_html( $isha_sg_it['desc'] ); ?></p>
							<?php endif; ?>
							<span class="isha-sg-item__arrow" aria-hidden="true"></span>
						</div>
					</article>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
