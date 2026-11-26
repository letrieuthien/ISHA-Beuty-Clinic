<?php
/**
 * Thanh header dùng chung cho header.php và header-home.php (Floating Glass Header, docs/header.md).
 *
 * Ba cụm trong .site-header__inner (CSS Grid): logo | menu chính (#primary-nav) | cụm phải (.site-header__tools: ngôn ngữ, tìm kiếm,
 * Đặt lịch, nút menu). Từ 900px trở xuống #primary-nav là drawer toàn màn hình; cụm ngôn ngữ và nút Đặt lịch có hai bản (trên thanh và
 * trong drawer), CSS chỉ hiện một bản ở mỗi chế độ. Giữ id `masthead`, `primary-nav` và class `menu-toggle` cho assets/js/main.js.
 * Nút "Đặt lịch" là liên kết tới trang đặt lịch (isha_booking_url()), không phải popup.
 *
 * @package ISHA
 *
 * @var array $args { @type bool $home Trang chủ điện ảnh (thêm class site-header--home). }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_home    = ! empty( $args['home'] );
$isha_landing = isha_landing_only(); // Chế độ landing: chỉ in logo.
$isha_book    = $isha_landing ? '' : esc_url( isha_booking_url() );
?>
<header class="site-header<?php echo $isha_home ? ' site-header--home' : ''; ?>" id="masthead">
	<div class="site-header__inner">
		<div class="site-branding"><?php isha_logo(); ?></div>

		<?php if ( ! $isha_landing ) : ?>
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
			<div class="main-navigation__foot">
				<?php isha_header_language_switcher( 'drawer' ); ?>
				<a class="btn btn--primary header-cta header-cta--drawer" href="<?php echo $isha_book; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã esc_url ở trên. ?>">
					<?php esc_html_e( 'Đặt lịch tư vấn riêng', 'isha' ); ?>
				</a>
			</div>
		</nav>

		<div class="site-header__tools">
			<?php isha_header_language_switcher( 'bar' ); ?>
			<?php isha_live_search_button(); ?>
			<a class="btn btn--primary btn--sm header-cta header-cta--bar" href="<?php echo $isha_book; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã esc_url ở trên. ?>">
				<span class="header-cta__label"><?php esc_html_e( 'Đặt lịch', 'isha' ); ?></span>
			</a>
			<button class="menu-toggle" type="button" aria-controls="primary-nav" aria-expanded="false">
				<span class="menu-toggle__bar" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'isha' ); ?></span>
			</button>
		</div>
		<?php endif; ?>
	</div>
</header>
