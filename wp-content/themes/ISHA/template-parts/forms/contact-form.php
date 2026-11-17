<?php
/**
 * Form liên hệ (trang Liên hệ). Gửi tới ISHA Core: AJAX khi có JS (booking.js), admin-post.php khi không (action isha_contact).
 *
 * `$args`: prefix (gốc id DOM), status (trạng thái của lần gửi thường vừa xong, hiện câu báo).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_f = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'prefix' => 'isha-contact',
		'status' => '',
	)
);

$isha_messages = class_exists( 'ISHA_Core_Contact' ) ? ISHA_Core_Contact::messages() : array();
$isha_topics   = class_exists( 'ISHA_Core_Contact' ) ? ISHA_Core_Contact::topics() : array();
$isha_p        = sanitize_html_class( $isha_f['prefix'] );
?>
<form class="isha-form-lux" id="<?php echo esc_attr( $isha_p ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-isha-form="isha_contact">
	<input type="hidden" name="action" value="isha_contact">
	<?php wp_nonce_field( 'isha_contact', 'isha_contact_nonce', false ); ?>
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
				'name'    => 'isha_topic',
				'label'   => __( 'Chủ đề', 'isha' ),
				'type'    => 'select',
				'options' => array(
					array(
						'label' => '',
						'items' => $isha_topics,
					),
				),
				'empty'   => __( 'Chọn chủ đề', 'isha' ),
			)
		);
		isha_forms_field(
			array(
				'prefix'   => $isha_p,
				'name'     => 'isha_message',
				'label'    => __( 'Lời nhắn', 'isha' ),
				'type'     => 'textarea',
				'required' => true,
				'wide'     => true,
				'attrs'    => array(
					'rows'      => '5',
					'minlength' => '10',
					'maxlength' => '1500',
				),
			)
		);
		?>
	</div>

	<div class="isha-form-lux__consent" data-field="isha_consent">
		<input type="checkbox" class="isha-form-lux__check" id="<?php echo esc_attr( $isha_p . '-consent' ); ?>" name="isha_consent" value="1" required aria-describedby="<?php echo esc_attr( $isha_p . '-consent-err' ); ?>">
		<label for="<?php echo esc_attr( $isha_p . '-consent' ); ?>"><?php echo isha_forms_consent_label( __( 'phản hồi lời nhắn này', 'isha' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- isha_forms_consent_label() trả HTML đã escape. ?></label>
		<p class="isha-form-lux__err" id="<?php echo esc_attr( $isha_p . '-consent-err' ); ?>" hidden></p>
	</div>

	<div class="isha-form-lux__actions">
		<button type="submit" class="isha-form-lux__submit"><?php esc_html_e( 'Gửi lời nhắn', 'isha' ); ?></button>
	</div>
	<p class="isha-form-lux__status" role="status" aria-live="polite"></p>
</form>
