<?php
/**
 * Bài viết.
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
	<div class="section">
		<div class="container">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
				<p class="entry-meta"><?php isha_posted_on(); ?> · <?php the_category( ', ' ); ?></p>
				<div class="entry-content"><?php the_content(); ?></div>
				<?php the_tags( '<p class="entry-tags">', ' ', '</p>' ); ?>
				<?php the_post_navigation(); ?>
				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</article>
		</div>
	</div>
	<?php
endwhile;

get_footer();
