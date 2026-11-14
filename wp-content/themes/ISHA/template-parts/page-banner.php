<?php
/**
 * Banner tiêu đề trang con.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_title    = ! empty( $args['title'] ) ? $args['title'] : get_the_title();
$isha_subtitle = ! empty( $args['subtitle'] ) ? $args['subtitle'] : '';
?>
<section class="page-banner page-header">
	<div class="container">
		<?php echo isha_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<h1 class="page-banner__title"><?php echo esc_html( $isha_title ); ?></h1>
		<?php if ( $isha_subtitle ) : ?>
			<p class="page-banner__subtitle"><?php echo esc_html( $isha_subtitle ); ?></p>
		<?php endif; ?>
	</div>
</section>
