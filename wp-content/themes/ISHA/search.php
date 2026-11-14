<?php
/**
 * Kết quả tìm kiếm.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

/* translators: %s: search query */
isha_page_banner( sprintf( __( 'Kết quả cho: %s', 'isha' ), get_search_query() ) );
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
				<?php the_posts_pagination(); ?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/content', 'none' ); ?>
			<?php endif; ?>
		</div>
		<?php get_sidebar(); ?>
	</div>
</div>
<?php
get_footer();
