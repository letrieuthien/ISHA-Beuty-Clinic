<?php
/**
 * Mẫu trang chi tiết Chính sách khách hàng (một hạng thẻ).
 *
 * Hai màn hình đầu (template-parts/policy-screens.php): cổng thẻ kính → thẻ của bài nổi giữa hai dòng tiêu đề.
 * Bên dưới là nội dung bài (điều kiện, quyền lợi) và nút quay lại.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$isha_ps = isha_policy_screen_data( get_the_ID() );
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry policy-single' ); ?>>
		<?php get_template_part( 'template-parts/policy-screens', null, $isha_ps ); ?>

		<div class="isha-ps-details" id="isha-chi-tiet">
			<div class="container">
				<?php
				// Không có JS thì hai màn hình đầu không chạy: hiện lại ảnh thẻ mặt trước / mặt sau như trước đây.
				if ( class_exists( 'ISHA_Core_Shortcodes' ) ) {
					echo '<noscript>' . ISHA_Core_Shortcodes::policy_cards( get_the_ID() ) . '</noscript>'; // phpcs:ignore WordPress.Security.EscapeOutput -- đã escape trong policy_cards().
				}
				?>
				<div class="entry-content isha-ps-details__content"><?php the_content(); ?></div>

				<p class="text-center policy-single__back">
					<a class="btn btn--outline" href="<?php echo esc_url( isha_policies_url() ); ?>"><?php esc_html_e( '← Tất cả chính sách', 'isha' ); ?></a>
				</p>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();
