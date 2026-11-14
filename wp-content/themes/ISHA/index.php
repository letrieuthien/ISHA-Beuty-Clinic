<?php
/**
 * Template mặc định / trang tin tức.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

isha_page_banner( is_home() && ! is_front_page() ? single_post_title( '', false ) : __( 'Tin tức', 'isha' ) );
?>
<div class="section">
	<div class="container layout layout--sidebar">
		<div class="layout__main">
			<?php if ( have_posts() ) : ?>
				<div class="grid grid--2">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content', 'card' );
					endwhile;
					?>
				</div>
				<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/content', 'none' ); ?>
			<?php endif; ?>
		</div>
		<?php get_sidebar(); ?>
	</div>
</div>
<?php
get_footer();
