<?php
/**
 * 404.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="section">
	<div class="container container--narrow text-center">
		<p class="hero__eyebrow">404</p>
		<h1 class="section__title"><?php esc_html_e( 'Không tìm thấy trang', 'isha' ); ?></h1>
		<p class="lead"><?php esc_html_e( 'Trang bạn tìm có thể đã được di chuyển hoặc không còn tồn tại.', 'isha' ); ?></p>
		<?php get_search_form(); ?>
		<p><a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Về trang chủ', 'isha' ); ?></a></p>
	</div>
</section>
<?php
get_footer();
