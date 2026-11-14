<?php
/**
 * Template Name: Liên hệ
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
				<?php echo isha_do_shortcode( 'isha_map', array( 'height' => 420 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<aside class="card card--aside">
				<h2 class="card__title"><?php esc_html_e( 'Thông tin liên hệ', 'isha' ); ?></h2>
				<?php echo isha_do_shortcode( 'isha_contact_info', array( 'show' => 'phone,email,address' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<h2 class="card__title"><?php esc_html_e( 'Giờ làm việc', 'isha' ); ?></h2>
				<?php echo isha_do_shortcode( 'isha_opening_hours' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<a class="btn btn--primary btn--block" href="<?php echo esc_url( isha_booking_url() ); ?>"><?php esc_html_e( 'Đặt lịch hẹn', 'isha' ); ?></a>
			</aside>
		</div>
	</section>
	<?php
endwhile;

get_footer();
