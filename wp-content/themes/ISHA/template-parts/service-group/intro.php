<?php
/**
 * Section "Giới thiệu dịch vụ" của trang nhóm dịch vụ, tông day (nền Daylight, chữ Shadow). Bố cục bất đối xứng như #story của trang chủ,
 * dựng bằng lưới hai cột x hai hàng:
 *   hàng 1: tiêu đề cố định "Giới thiệu dịch vụ"          | đoạn mở đầu (đoạn <p> đầu tiên của nội dung, Garamond cỡ lớn)
 *   hàng 2: khung so sánh trước/sau (CHỈ desktop, từ 1025px) | phần còn lại của nội dung (chữ thân Montserrat, thụt vào theo cột)
 * Khung so sánh nằm hàng 2 nên mép trên của khung thẳng hàng với khối chữ thân bên phải. Khung dùng chung partial compare.php và dữ liệu
 * với section "Hiệu quả liệu trình" (section đó ẩn ở desktop khi có khung này); không có dải thẻ, chỉ có nút Trước/Sau và bộ đếm bên dưới
 * (service-group.js bỏ hidden và đổi cặp). Tablet/điện thoại: không có khung (đã có section "Hiệu quả liệu trình"), một cột.
 *
 * Nội dung trống thì không in section.
 *
 * Dữ liệu: `$args` = isha_service_group_data( $term ); `intro` là HTML đã lọc (wp_kses theo bảng thẻ của trình soạn thảo) và qua wpautop.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_sg_intro = ( isset( $args['intro'] ) && is_string( $args['intro'] ) ) ? trim( $args['intro'] ) : '';
if ( '' === $isha_sg_intro ) {
	return;
}

list( $isha_sg_lead, $isha_sg_rest ) = isha_service_group_split_intro( $isha_sg_intro );

// Khung so sánh ở cột trái (desktop): ảnh nhỏ hơn khung của section Hiệu quả (cột rộng khoảng 340 đến 380px).
$isha_sg_cmp   = isha_service_group_ba_images( $args, '380px' );
$isha_sg_count = count( $isha_sg_cmp );

$isha_sg_classes = 'isha-sec isha-sg-intro';
if ( '' !== $isha_sg_lead ) {
	$isha_sg_classes .= ' isha-sg-intro--lead';
}
if ( $isha_sg_count ) {
	$isha_sg_classes .= ' isha-sg-intro--media';
}
?>
<section id="gioi-thieu-dich-vu" class="<?php echo esc_attr( $isha_sg_classes ); ?>" data-tone="day" aria-labelledby="isha-sg-intro-title">
	<div class="isha-wrap isha-sg-intro__grid">
		<div class="isha-sg-intro__aside">
			<h2 id="isha-sg-intro-title" class="isha-sec__title isha-sg-intro__title"><?php esc_html_e( 'Giới thiệu dịch vụ', 'isha' ); ?></h2>
		</div>

		<?php if ( '' !== $isha_sg_lead ) : ?>
			<div class="isha-sg-intro__lead">
				<?php echo $isha_sg_lead; // phpcs:ignore WordPress.Security.EscapeOutput -- đã lọc bằng wp_kses trong isha_service_group_data(). ?>
			</div>
		<?php endif; ?>

		<?php if ( $isha_sg_count ) : ?>
			<div class="isha-sg-intro__media" data-isha-sg-aside>
				<?php get_template_part( 'template-parts/service-group/compare', null, array( 'id' => 'isha-sg-ba-aside', 'items' => $isha_sg_cmp ) ); ?>
				<?php if ( $isha_sg_count > 1 ) : ?>
					<div class="isha-sg-intro__nav" role="group" aria-label="<?php esc_attr_e( 'Chọn cặp ảnh trước và sau điều trị', 'isha' ); ?>" hidden>
						<button type="button" class="isha-pager__btn isha-pager__btn--prev" data-isha-sg-prev aria-controls="isha-sg-ba-aside"><span class="isha-sr"><?php esc_html_e( 'Cặp ảnh trước', 'isha' ); ?></span></button>
						<p class="isha-sg-intro__count" aria-hidden="true"><span class="isha-sg-intro__current" data-isha-sg-current>01</span><span class="isha-sg-dash"></span><span><?php echo esc_html( str_pad( (string) $isha_sg_count, 2, '0', STR_PAD_LEFT ) ); ?></span></p>
						<button type="button" class="isha-pager__btn isha-pager__btn--next" data-isha-sg-next aria-controls="isha-sg-ba-aside"><span class="isha-sr"><?php esc_html_e( 'Cặp ảnh sau', 'isha' ); ?></span></button>
					</div>
					<?php /* translators: 1: số thứ tự cặp ảnh đang xem, 2: tổng số cặp. Giữ nguyên %1$d và %2$d. */ ?>
					<p class="isha-sr" role="status" data-isha-sg-status data-template="<?php echo esc_attr( __( 'Đang xem cặp ảnh %1$d trên %2$d', 'isha' ) ); ?>"></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $isha_sg_rest ) : ?>
			<div class="isha-sg-intro__body">
				<?php echo $isha_sg_rest; // phpcs:ignore WordPress.Security.EscapeOutput -- đã lọc bằng wp_kses trong isha_service_group_data(). ?>
			</div>
		<?php endif; ?>
	</div>
</section>
