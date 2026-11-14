<?php
/**
 * Header.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Bỏ qua, đến nội dung chính', 'isha' ); ?></a>

<header class="site-header" id="masthead">
	<div class="container site-header__inner">
		<div class="site-branding"><?php isha_logo(); ?></div>

		<?php if ( ! isha_landing_only() ) : ?>
		<button class="menu-toggle" aria-controls="primary-nav" aria-expanded="false">
			<span class="menu-toggle__bar" aria-hidden="true"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'isha' ); ?></span>
		</button>

		<nav id="primary-nav" class="main-navigation" aria-label="<?php esc_attr_e( 'Menu chính', 'isha' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'menu_class'     => 'primary-menu',
					'container'      => false,
					'fallback_cb'    => 'isha_menu_fallback',
					'depth'          => 2,
				)
			);
			?>
			<a class="btn btn--primary btn--sm header-cta" href="<?php echo esc_url( isha_booking_url() ); ?>">
				<?php esc_html_e( 'Đặt lịch', 'isha' ); ?>
			</a>
		</nav>
		<?php endif; ?>
	</div>
</header>

<main id="content" class="site-main<?php echo isha_is_inner_page() ? ' page' : ''; ?>">
