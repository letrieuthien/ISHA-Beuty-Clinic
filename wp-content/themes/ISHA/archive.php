<?php
/**
 * Lưu trữ (danh mục, thẻ, tác giả, ngày).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

isha_page_banner( get_the_archive_title(), wp_strip_all_tags( get_the_archive_description() ) );
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
