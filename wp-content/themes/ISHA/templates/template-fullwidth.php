<?php
/**
 * Template Name: Toàn chiều rộng (không banner)
 * Template Post Type: page
 *
 * Dùng cho landing page dựng bằng block / shortcode.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="entry-content entry-content--full"><?php the_content(); ?></div>
	<?php
endwhile;

get_footer();
