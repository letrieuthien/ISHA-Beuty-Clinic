<?php
/**
 * Template Name: Đặt lịch hẹn
 * Template Post Type: page
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	isha_page_banner();
	?>
	<section class="section">
		<div class="container layout layout--aside">
			<div class="layout__main">
				<?php if ( get_the_content() ) : ?>
					<div class="entry-content"><?php the_content(); ?></div>
				<?php endif; ?>
				<?php echo isha_do_shortcode( 'isha_booking_form' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<aside class="card card--aside">
				<h2 class="card__title"><?php esc_html_e( 'Giờ làm việc', 'isha' ); ?></h2>
				<?php echo isha_do_shortcode( 'isha_opening_hours' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<h2 class="card__title"><?php esc_html_e( 'Liên hệ nhanh', 'isha' ); ?></h2>
				<?php echo isha_do_shortcode( 'isha_contact_info', array( 'show' => 'phone,email' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</aside>
		</div>
	</section>
	<?php
endwhile;

get_footer();
