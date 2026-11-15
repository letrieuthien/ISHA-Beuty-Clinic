<?php
/**
 * Section "Hiệu quả liệu trình" của trang nhóm dịch vụ, tông dusk (nền nâu tối, chữ Daylight). Thứ tự:
 *   1. Tiêu đề cố định "Hiệu quả liệu trình" và mô tả ngắn (_isha_ba_desc, văn bản thuần; trống thì bỏ).
 *   2. Khung kéo so sánh trước/sau (.isha-sg-ba): mỗi cặp là một figure gồm hai lớp ảnh chồng nhau, lớp "sau" bị cắt bởi
 *      clip-path: inset(0 0 0 var(--ba)); đường chia dọc và nút kéo nằm ở --ba. Chỉ cặp đầu hiện khi tải trang, các cặp sau `hidden`
 *      (ảnh lazy nên chưa tải). Nút kéo (role="slider") do PHP in sẵn ở dạng hidden; service-group.js bỏ hidden và nối kéo chuột/chạm/phím.
 *      Không có JS: khung chia đôi tĩnh ở giữa.
 *   3. Panel "Các ca điều trị" (.isha-sg-strip, chỉ khi có từ 2 cặp): đầu panel có tên và bộ đếm ca đang xem (01 / N); mỗi thẻ là nút ghép
 *      hai nửa ảnh trước | sau bằng CSS kèm số ca; bấm thẻ thì khung so sánh đổi sang cặp đó. Khung và panel nằm chung .isha-sg-stage:
 *      desktop là hai cột (panel bên phải, thẻ xếp dọc 3 thẻ mỗi khung, nút Lên/Xuống), tablet/điện thoại xếp chồng (dải ngang 3 / 1 thẻ).
 *      service-group.js biến dải thành băng chuyền vòng lặp theo trục tương ứng. Không có JS: panel/dải cuộn được bằng chạm và bàn phím.
 *
 * Không có cặp ảnh hợp lệ thì không in section.
 *
 * Dữ liệu: `$args` = isha_service_group_data( $term ).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_sg_pairs = ( isset( $args['pairs'] ) && is_array( $args['pairs'] ) ) ? array_values( $args['pairs'] ) : array();
$isha_sg_name  = ( isset( $args['name'] ) && is_scalar( $args['name'] ) ) ? trim( (string) $args['name'] ) : '';
$isha_sg_desc  = ( isset( $args['ba_desc'] ) && is_scalar( $args['ba_desc'] ) ) ? trim( (string) $args['ba_desc'] ) : '';

// Dựng sẵn thẻ img của khung lớn và của thẻ nhỏ; cặp nào thiếu ảnh dùng được thì bỏ.
$isha_sg_items = isha_service_group_ba_images( $args, '(max-width: 600px) 100vw, (max-width: 1024px) 60vw, 480px', true );

// Desktop (từ 1025px) đã có khung so sánh ở cột trái của section Giới thiệu (khi có nội dung giới thiệu) nên section này ẩn ở desktop.
$isha_sg_aside = isset( $args['intro'] ) && '' !== trim( (string) $args['intro'] );

if ( ! $isha_sg_items ) {
	return;
}

$isha_sg_total = count( $isha_sg_items );
?>
<section id="hieu-qua-lieu-trinh" class="isha-sec isha-sg-results<?php echo $isha_sg_total > 1 ? '' : ' isha-sg-results--solo'; ?><?php echo $isha_sg_aside ? ' isha-sg-results--aside' : ''; ?>" data-tone="dusk" aria-labelledby="isha-sg-results-title">
	<div class="isha-wrap">
		<div class="isha-sec__head isha-sg-results__head">
			<h2 id="isha-sg-results-title" class="isha-sec__title isha-sg-results__title"><?php esc_html_e( 'Hiệu quả liệu trình', 'isha' ); ?></h2>
			<?php isha_home_p( $isha_sg_desc, 'isha-lead isha-sg-results__lead' ); ?>
		</div>

		<?php $isha_sg_multi = $isha_sg_total > 1; ?>
		<div class="isha-sg-stage<?php echo $isha_sg_multi ? '' : ' isha-sg-stage--solo'; ?>">
			<?php get_template_part( 'template-parts/service-group/compare', null, array( 'id' => 'isha-sg-ba', 'items' => $isha_sg_items ) ); ?>

			<?php if ( $isha_sg_multi ) : ?>
				<?php $isha_sg_pad = static function ( $n ) { return str_pad( (string) $n, 2, '0', STR_PAD_LEFT ); }; ?>
				<div class="isha-sg-strip" role="group" aria-labelledby="isha-sg-strip-title" data-isha-sg-strip>
					<div class="isha-sg-strip__head">
						<p id="isha-sg-strip-title" class="isha-sg-strip__title"><?php esc_html_e( 'Các ca điều trị', 'isha' ); ?></p>
						<p class="isha-sg-strip__count" aria-hidden="true"><span class="isha-sg-strip__current" data-isha-sg-current>01</span><span class="isha-sg-dash"></span><span><?php echo esc_html( $isha_sg_pad( $isha_sg_total ) ); ?></span></p>
					</div>
					<div class="isha-sg-strip__viewport">
						<ul class="isha-sg-strip__track">
							<?php foreach ( $isha_sg_items as $isha_sg_k => $isha_sg_item ) : ?>
								<li class="isha-sg-strip__item">
									<button type="button" class="isha-sg-card<?php echo 0 === $isha_sg_k ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr( (string) $isha_sg_k ); ?>" aria-controls="isha-sg-ba" aria-current="<?php echo 0 === $isha_sg_k ? 'true' : 'false'; ?>">
										<span class="isha-sg-card__half isha-sg-card__half--before">
											<?php echo $isha_sg_item['thumb_before']; // phpcs:ignore WordPress.Security.EscapeOutput -- thẻ img do isha_home_image() dựng và đã escape. ?>
											<span class="isha-sg-card__tag" aria-hidden="true"><?php esc_html_e( 'Trước', 'isha' ); ?></span>
										</span>
										<span class="isha-sg-card__half isha-sg-card__half--after">
											<?php echo $isha_sg_item['thumb_after']; // phpcs:ignore WordPress.Security.EscapeOutput -- thẻ img do isha_home_image() dựng và đã escape. ?>
											<span class="isha-sg-card__tag" aria-hidden="true"><?php esc_html_e( 'Sau', 'isha' ); ?></span>
										</span>
										<span class="isha-sg-card__no" aria-hidden="true"><?php echo esc_html( $isha_sg_pad( $isha_sg_k + 1 ) ); ?></span>
										<?php /* translators: 1: số thứ tự cặp ảnh, 2: tổng số cặp */ ?>
										<span class="isha-sr"><?php echo esc_html( sprintf( __( 'Xem cặp ảnh %1$d trên %2$d', 'isha' ), $isha_sg_k + 1, $isha_sg_total ) ); ?></span>
									</button>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php
					isha_home_pager(
						array(
							'count'     => $isha_sg_total,
							'label'     => __( 'Điều hướng các ca điều trị', 'isha' ),
							/* translators: %d: số thứ tự cặp ảnh */
							'dot_label' => __( 'Tới cặp ảnh %d', 'isha' ),
							'class'     => 'isha-sg-strip__pager',
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>

		<?php
		/* translators: 1: số thứ tự cặp ảnh đang xem, 2: tổng số cặp. Giữ nguyên %1$d và %2$d. */
		$isha_sg_status = __( 'Đang xem cặp ảnh %1$d trên %2$d', 'isha' );
		?>
		<p class="isha-sr" role="status" data-isha-sg-status data-template="<?php echo esc_attr( $isha_sg_status ); ?>"></p>
	</div>
</section>
