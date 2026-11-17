<?php
/**
 * Popup Đặt lịch (in ở chân mọi trang phía khách, xem inc/forms.php). `<dialog>` mở bằng showModal() trong booking.js: Esc đóng, bấm vùng tối
 * quanh khung đóng, trả focus về nút đã mở. Bố cục: cột trái tối (thương hiệu, lời dẫn, các ý chính, hotline, giờ làm việc, địa chỉ; vòng
 * mặt trời vàng kim trang trí), cột phải là form đặt lịch; gửi xong thay form bằng khung cảm ơn. Điện thoại: phủ kín màn hình, cột trái
 * thu thành phần đầu ngắn.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_info = isha_forms_contact_info();
?>
<dialog class="isha-book" id="isha-book" aria-labelledby="isha-book-title" aria-describedby="isha-book-lead">
	<div class="isha-book__panel">
		<aside class="isha-book__brand">
			<span class="isha-book__sun" aria-hidden="true"></span>
			<p class="isha-book__eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h2 class="isha-book__title" id="isha-book-title"><?php esc_html_e( 'Đặt lịch tư vấn riêng', 'isha' ); ?></h2>
			<p class="isha-book__lead" id="isha-book-lead"><?php esc_html_e( 'Bác sĩ trực tiếp thăm khám, soi da và cùng bạn xây dựng phác đồ phù hợp. Buổi tư vấn đầu tiên không ràng buộc liệu trình.', 'isha' ); ?></p>
			<ul class="isha-book__points">
				<li><?php esc_html_e( 'Thăm khám 45 phút cùng bác sĩ da liễu', 'isha' ); ?></li>
				<li><?php esc_html_e( 'Soi da 3D và phân tích tình trạng da', 'isha' ); ?></li>
				<li><?php esc_html_e( 'Gọi xác nhận lịch trong giờ làm việc', 'isha' ); ?></li>
			</ul>
			<dl class="isha-book__facts">
				<?php if ( '' !== $isha_info['phone'] ) : ?>
					<div><dt><?php esc_html_e( 'Hotline', 'isha' ); ?></dt><dd><a href="<?php echo esc_url( $isha_info['tel_url'] ); ?>"><?php echo esc_html( $isha_info['phone'] ); ?></a></dd></div>
				<?php endif; ?>
				<?php if ( $isha_info['hours'] ) : ?>
					<div><dt><?php esc_html_e( 'Giờ làm việc', 'isha' ); ?></dt><dd><?php echo esc_html( implode( ' · ', $isha_info['hours'] ) ); ?></dd></div>
				<?php endif; ?>
				<?php if ( $isha_info['address'] ) : ?>
					<div><dt><?php esc_html_e( 'Địa chỉ', 'isha' ); ?></dt><dd><?php echo esc_html( implode( ', ', $isha_info['address'] ) ); ?></dd></div>
				<?php endif; ?>
			</dl>
		</aside>

		<div class="isha-book__main">
			<button type="button" class="isha-book__close" data-isha-book-close><span class="screen-reader-text"><?php esc_html_e( 'Đóng', 'isha' ); ?></span></button>
			<div class="isha-book__form">
				<p class="isha-book__hint"><?php esc_html_e( 'Ô có dấu * là bắt buộc.', 'isha' ); ?></p>
				<?php
				get_template_part(
					'template-parts/forms/booking-form',
					null,
					array(
						'prefix'  => 'isha-book-form',
						'context' => 'popup',
						'nonce'   => false,
					)
				);
				?>
			</div>
			<div class="isha-book__done" data-isha-book-done tabindex="-1" hidden>
				<span class="isha-book__done-mark" aria-hidden="true"></span>
				<h3 class="isha-book__done-title"><?php esc_html_e( 'ISHA đã nhận yêu cầu của bạn', 'isha' ); ?></h3>
				<p class="isha-book__done-text" data-isha-book-message></p>
				<button type="button" class="isha-form-lux__submit isha-form-lux__submit--ghost" data-isha-book-close><?php esc_html_e( 'Đóng', 'isha' ); ?></button>
			</div>
		</div>
	</div>
</dialog>
