<?php
/**
 * Thẻ bài viết.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'isha-card' ); ?>
		</a>
	<?php endif; ?>
	<div class="card__body">
		<p class="card__meta"><?php isha_posted_on(); ?></p>
		<h3 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p><?php echo esc_html( get_the_excerpt() ); ?></p>
		<a class="card__link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Đọc tiếp', 'isha' ); ?></a>
	</div>
</article>
