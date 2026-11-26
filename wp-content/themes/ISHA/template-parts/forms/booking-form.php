<?php
/**
 * Form đặt lịch (popup và trang Đặt lịch hẹn). Gửi tới ISHA Core: AJAX khi có JS (booking.js), admin-post.php khi không (action isha_booking).
 *
 * `$args`: prefix (gốc id DOM), context (popup | page), service (ID dịch vụ chọn sẵn), nonce (true: in sẵn nonce cho đường gửi không JS; popup
 * không in vì trang có thể bị cache, booking.js lấy nonce mới), status (trạng thái của lần gửi thường vừa xong, hiện câu báo).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_f = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'prefix'  => 'isha-booking',
		'context' => 'page',
		'service' => 0,
		'nonce'   => true,
		'status'  => '',
	)
);

$isha_messages = class_exists( 'ISHA_Core_Booking' ) ? ISHA_Core_Booking::messages() : array();
$isha_slots    = class_exists( 'ISHA_Core_Booking' ) ? ISHA_Core_Booking::time_slots() : array();
$isha_info     = isha_forms_contact_info();
$isha_p        = sanitize_html_class( $isha_f['prefix'] );
?>
<form class="isha-form-lux" id="<?php echo esc_attr( $isha_p ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-isha-form="isha_booking" data-isha-now="<?php echo esc_attr( wp_date( 'Y-m-d H:i' ) ); ?>">
	<input type="hidden" name="action" value="isha_booking">
	<input type="hidden" name="isha_source" value="<?php echo esc_attr( 'popup' === $isha_f['context'] ? 'popup' : 'page' ); ?>">
	<?php if ( $isha_f['nonce'] ) : ?>
		<?php wp_nonce_field( 'isha_booking', 'isha_booking_nonce', false ); ?>
	<?php else : ?>
		<input type="hidden" name="isha_booking_nonce" value="">
	<?php endif; ?>
	<div class="isha-form-lux__hp" aria-hidden="true"><label>Website <input type="text" name="isha_website" tabindex="-1" autocomplete="off"></label></div>

	<?php if ( isset( $isha_messages[ $isha_f['status'] ] ) ) : ?>
		<p class="isha-form-lux__notice isha-form-lux__notice--<?php echo 'success' === $isha_f['status'] ? 'ok' : 'err'; ?>" role="alert"><?php echo esc_html( $isha_messages[ $isha_f['status'] ] ); ?></p>
	<?php endif; ?>

	<div class="isha-form-lux__grid">
		<?php
		isha_forms_field(
			array(
				'prefix'   => $isha_p,
				'name'     => 'isha_name',
				'label'    => __( 'Họ và tên', 'isha' ),
				'required' => true,
				'attrs'    => array(
					'autocomplete' => 'name',
					'maxlength'    => '100',
				),
			)
		);
		isha_forms_field(
			array(
				'prefix'   => $isha_p,
				'name'     => 'isha_phone',
				'label'    => __( 'Số điện thoại', 'isha' ),
				'type'     => 'tel',
				'required' => true,
				'attrs'    => array(
					'autocomplete' => 'tel',
					'inputmode'    => 'tel',
					'maxlength'    => '20',
				),
			)
		);
		isha_forms_field(
			array(
				'prefix' => $isha_p,
				'name'   => 'isha_email',
				'label'  => __( 'Email (không bắt buộc)', 'isha' ),
				'type'   => 'email',
				'attrs'  => array(
					'autocomplete' => 'email',
					'maxlength'    => '120',
				),
			)
		);
		isha_forms_field(
			array(
				'prefix'  => $isha_p,
				'name'    => 'isha_service',
				'label'   => __( 'Dịch vụ quan tâm', 'isha' ),
				'type'    => 'select',
				'options' => isha_forms_service_groups(),
				'empty'   => __( 'Chưa chọn, cần bác sĩ tư vấn', 'isha' ),
				'value'   => (string) absint( $isha_f['service'] ),
			)
		);
		isha_forms_field(
			array(
				'prefix'   => $isha_p,
				'name'     => 'isha_date',
				'label'    => __( 'Ngày hẹn', 'isha' ),
				'type'     => 'date',
				'required' => true,
				'attrs'    => array(
					'min'  => wp_date( 'Y-m-d' ),
					'lang' => 'vi-VN', // Trình duyệt dùng định dạng ngày theo ngôn ngữ này (dd/mm/yyyy) khi hỗ trợ.
				),
			)
		);
		isha_forms_field(
			array(
				'prefix'   => $isha_p,
				'name'     => 'isha_time',
				'label'    => __( 'Giờ hẹn', 'isha' ),
				'type'     => 'select',
				'required' => true,
				'options'  => array(
					array(
						'label' => '',
						'items' => array_combine( $isha_slots, $isha_slots ),
					),
				),
				'empty'    => __( 'Chọn giờ', 'isha' ),
			)
		);
		isha_forms_field(
			array(
				'prefix' => $isha_p,
				'name'   => 'isha_note',
				'label'  => __( 'Ghi chú (tình trạng da, mong muốn…)', 'isha' ),
				'type'   => 'textarea',
				'wide'   => true,
				'attrs'  => array(
					'rows'      => '3',
					'maxlength' => '1000',
				),
			)
		);
		?>
	</div>

	<div class="isha-form-lux__consent" data-field="isha_consent">
		<input type="checkbox" class="isha-form-lux__check" id="<?php echo esc_attr( $isha_p . '-consent' ); ?>" name="isha_consent" value="1" required aria-describedby="<?php echo esc_attr( $isha_p . '-consent-err' ); ?>">
		<label for="<?php echo esc_attr( $isha_p . '-consent' ); ?>"><?php echo isha_forms_consent_label( __( 'liên hệ xác nhận lịch hẹn', 'isha' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- isha_forms_consent_label() trả HTML đã escape. ?></label>
		<p class="isha-form-lux__err" id="<?php echo esc_attr( $isha_p . '-consent-err' ); ?>" hidden></p>
	</div>

	<div class="isha-form-lux__actions">
		<button type="submit" class="isha-form-lux__submit"><?php esc_html_e( 'Gửi yêu cầu đặt lịch', 'isha' ); ?></button>
		<?php if ( '' !== $isha_info['tel_url'] ) : ?>
			<p class="isha-form-lux__alt">
				<?php esc_html_e( 'Hoặc gọi hotline', 'isha' ); ?>
				<a href="<?php echo esc_url( $isha_info['tel_url'] ); ?>"><?php echo esc_html( $isha_info['phone'] ); ?></a>
			</p>
		<?php endif; ?>
	</div>
	<p class="isha-form-lux__status" role="status" aria-live="polite"></p>
</form>
