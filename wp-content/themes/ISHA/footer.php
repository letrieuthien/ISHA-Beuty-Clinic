<?php
/**
 * Footer: chân trang 3 tầng (docs/footer.md; kỹ thuật: docs/home-architecture.md mục 27).
 *
 * - Tầng 1: dải mời đặt lịch riêng (isha_footer_invite_visible(): ẩn khi trang đã có khối đặt lịch riêng).
 * - Tầng 2: lưới 4 cột: nhận diện + mạng xã hội, Liên hệ, Giờ làm việc, Khám phá.
 * - Tầng 3: bottom bar: bản quyền, liên kết chính sách, chỗ đặt bộ chuyển ngôn ngữ (action isha_footer_language_switcher).
 * Chế độ landing (isha_landing_only()): chỉ còn cột nhận diện và bản quyền.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_landing = isha_landing_only();
$isha_credit  = isha_mod( 'footer_credit' );
$isha_about   = isha_mod( 'footer_about' );
$isha_phone   = isha_footer_phone();
$isha_email   = isha_footer_email();
$isha_address = trim( (string) isha_opt( 'address' ) );
$isha_route   = isha_footer_directions_url();
$isha_hours   = isha_footer_hours();
$isha_legal   = $isha_landing ? array() : isha_footer_legal_links();
$isha_lang    = ! $isha_landing && has_action( 'isha_footer_language_switcher' );
?>
</main><!-- #content -->

<footer class="site-footer" id="colophon">
	<span class="site-footer__mark" aria-hidden="true">ISHA</span>

	<div class="container site-footer__inner">
		<?php if ( isha_footer_invite_visible() ) : ?>
		<section class="site-footer__invite" aria-labelledby="isha-footer-invite">
			<div class="site-footer__invite-text">
				<p class="site-footer__eyebrow" lang="en">Private consultation</p>
				<h2 class="site-footer__invite-title" id="isha-footer-invite"><?php echo esc_html( isha_mod( 'footer_invite' ) ); ?></h2>
			</div>
			<a class="site-footer__cta" href="<?php echo esc_url( isha_booking_url() ); ?>" data-isha-booking="">
				<span><?php esc_html_e( 'Đặt lịch tư vấn riêng', 'isha' ); ?></span>
				<span class="site-footer__cta-arrow" aria-hidden="true">➜</span>
			</a>
		</section>
		<?php endif; ?>

		<div class="site-footer__grid<?php echo $isha_landing ? ' site-footer__grid--single' : ''; ?>">
			<div class="site-footer__col site-footer__col--brand">
				<div class="site-footer__logo"><?php isha_logo(); ?></div>
				<?php if ( $isha_about ) : ?>
					<p class="site-footer__about"><?php echo esc_html( $isha_about ); ?></p>
				<?php endif; ?>
				<?php isha_social_links(); ?>
			</div>

			<?php if ( ! $isha_landing ) : ?>
			<div class="site-footer__col site-footer__col--contact">
				<h2 class="site-footer__title"><?php esc_html_e( 'Liên hệ', 'isha' ); ?></h2>
				<ul class="site-footer__list">
					<?php if ( $isha_phone ) : ?>
					<li>
						<span class="site-footer__k"><?php esc_html_e( 'Hotline', 'isha' ); ?></span>
						<a class="site-footer__v" href="<?php echo esc_url( 'tel:' . $isha_phone['tel'], array( 'tel' ) ); ?>"><?php echo esc_html( $isha_phone['text'] ); ?></a>
					</li>
					<?php endif; ?>
					<?php if ( $isha_email ) : ?>
					<?php $isha_email_parts = explode( '@', $isha_email, 2 ); ?>
					<li>
						<span class="site-footer__k">Email</span>
						<a class="site-footer__v site-footer__email" href="mailto:<?php echo esc_attr( antispambot( $isha_email ) ); ?>"><?php echo esc_html( antispambot( $isha_email_parts[0] ) ); ?><wbr><?php echo esc_html( antispambot( '@' . $isha_email_parts[1] ) ); ?></a>
					</li>
					<?php endif; ?>
					<?php if ( '' !== $isha_address ) : ?>
					<li>
						<span class="site-footer__k"><?php esc_html_e( 'Địa chỉ', 'isha' ); ?></span>
						<address class="site-footer__v"><?php echo nl2br( esc_html( $isha_address ) ); ?></address>
						<?php if ( '' !== $isha_route ) : ?>
						<a class="site-footer__route" href="<?php echo esc_url( $isha_route ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Chỉ đường', 'isha' ); ?> <span aria-hidden="true">&#8599;</span><span class="screen-reader-text"><?php esc_html_e( '(mở tab mới)', 'isha' ); ?></span>
						</a>
						<?php endif; ?>
					</li>
					<?php endif; ?>
				</ul>
			</div>

			<div class="site-footer__col site-footer__col--hours">
				<h2 class="site-footer__title"><?php esc_html_e( 'Giờ làm việc', 'isha' ); ?></h2>
				<?php if ( $isha_hours ) : ?>
				<ul class="site-footer__hours">
					<?php foreach ( $isha_hours as $isha_row ) : ?>
					<li>
						<?php if ( '' !== $isha_row['label'] ) : ?>
							<span class="site-footer__hours-day"><?php echo esc_html( $isha_row['label'] ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== $isha_row['time'] ) : ?>
							<span class="site-footer__hours-time"><?php echo esc_html( $isha_row['time'] ); ?></span>
						<?php endif; ?>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</div>

			<div class="site-footer__col site-footer__col--explore">
				<h2 class="site-footer__title"><?php esc_html_e( 'Khám phá', 'isha' ); ?></h2>
				<nav class="footer-nav" aria-label="<?php esc_attr_e( 'Khám phá', 'isha' ); ?>">
					<?php
					if ( has_nav_menu( 'footer' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'footer',
								'menu_class'     => 'footer-menu',
								'container'      => false,
								'depth'          => 1,
								'fallback_cb'    => false,
							)
						);
					} else {
						echo '<ul class="footer-menu">';
						foreach ( isha_footer_menu_items() as $isha_item ) {
							printf(
								'<li><a href="%s">%s</a></li>',
								esc_url( $isha_item['url'] ),
								esc_html( $isha_item['label'] )
							);
						}
						echo '</ul>';
					}
					?>
				</nav>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="site-footer__bottom<?php echo $isha_landing ? ' site-footer__bottom--single' : ''; ?>">
		<div class="container site-footer__bottom-inner">
			<p class="site-footer__copy">
				<?php
				if ( $isha_credit ) {
					echo esc_html( $isha_credit );
				} else {
					printf(
						/* translators: %s: year */
						esc_html__( '© %s ISHA Beauty Clinic. Bảo lưu mọi quyền.', 'isha' ),
						esc_html( wp_date( 'Y' ) )
					);
				}
				?>
			</p>
			<?php if ( $isha_legal || $isha_lang ) : ?>
			<div class="site-footer__legal">
				<?php if ( $isha_legal ) : ?>
				<nav aria-label="<?php esc_attr_e( 'Chính sách', 'isha' ); ?>">
					<ul class="site-footer__legal-list">
						<?php foreach ( $isha_legal as $isha_link ) : ?>
							<li><a href="<?php echo esc_url( $isha_link['url'] ); ?>"><?php echo esc_html( $isha_link['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
				<?php endif; ?>
				<?php if ( $isha_lang ) : ?>
					<div class="site-footer__lang"><?php do_action( 'isha_footer_language_switcher' ); ?></div>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
