<?php
/**
 * Template Name: Chính sách dịch vụ
 * Template Post Type: page
 *
 * Trang danh sách các Chính sách khách hàng (post type isha_policy); mỗi thẻ dẫn tới trang chi tiết.
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
		<div class="container">
			<?php if ( get_the_content() ) : ?>
				<div class="entry-content container--narrow policies-intro"><?php the_content(); ?></div>
			<?php endif; ?>
			<?php echo isha_do_shortcode( 'isha_policies', array( 'view' => 'list' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>
	<?php
endwhile;

get_footer();
