<?php
/**
 * Khối CTA đặt lịch.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="cta">
	<div class="container container--narrow text-center">
		<h2 class="cta__title"><?php echo esc_html( isha_mod( 'cta_title' ) ); ?></h2>
		<p><?php echo esc_html( isha_mod( 'cta_text' ) ); ?></p>
		<a class="btn btn--primary" href="<?php echo esc_url( isha_booking_url() ); ?>"><?php esc_html_e( 'Đặt lịch hẹn', 'isha' ); ?></a>
	</div>
</section>
