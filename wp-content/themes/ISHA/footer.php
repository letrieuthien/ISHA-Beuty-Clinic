<?php
/**
 * Footer.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_credit = isha_mod( 'footer_credit' );
?>
</main><!-- #content -->

<footer class="site-footer">
	<div class="container site-footer__grid<?php echo isha_landing_only() ? ' site-footer__grid--single' : ''; ?>">
		<div class="site-footer__col">
			<div class="site-footer__brand"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
			<p><?php echo esc_html( isha_mod( 'footer_about' ) ); ?></p>
			<?php isha_social_links(); ?>
		</div>

		<?php if ( ! isha_landing_only() ) : ?>
		<div class="site-footer__col">
			<h3 class="site-footer__title"><?php esc_html_e( 'Liên hệ', 'isha' ); ?></h3>
			<?php echo isha_do_shortcode( 'isha_contact_info', array( 'show' => 'phone,email,address' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>

		<div class="site-footer__col">
			<h3 class="site-footer__title"><?php esc_html_e( 'Giờ làm việc', 'isha' ); ?></h3>
			<?php echo isha_do_shortcode( 'isha_opening_hours' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>

		<div class="site-footer__col">
			<h3 class="site-footer__title"><?php esc_html_e( 'Liên kết', 'isha' ); ?></h3>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'menu_class'     => 'footer-menu',
					'container'      => false,
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
			?>
		</div>
		<?php endif; ?>
	</div>

	<div class="site-footer__bottom">
		<div class="container">
			<?php
			if ( $isha_credit ) {
				echo esc_html( $isha_credit );
			} else {
				printf(
					/* translators: 1: year, 2: site name */
					esc_html__( '© %1$s %2$s. Bảo lưu mọi quyền.', 'isha' ),
					esc_html( wp_date( 'Y' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
			}
			?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
