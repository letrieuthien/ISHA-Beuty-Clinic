<?php
/**
 * Trang Dịch vụ tổng (templates/template-services.php): hero nan quạt (dùng chung với trang nhóm dịch vụ), nội dung soạn trong trang,
 * CTA đặt lịch. Không còn lưới liệu trình cố định: phần dưới hero do người biên tập dựng trong nội dung trang.
 *
 * Nội dung trống thì bỏ khối nội dung. Trang dựng bằng Elementor thì in nội dung tràn mép (Elementor tự quản lý khung và lề);
 * còn lại in trong khung .container như page.php.
 *
 * Gọi qua isha_services_page() (inc/services-hero.php) trong vòng lặp của trang. Dữ liệu: `$args` = array( 'context' => WP_Post,
 * 'hero' => isha_services_hero_data() ).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_sp_post = ( isset( $args['context'] ) && $args['context'] instanceof WP_Post ) ? $args['context'] : null;
$isha_sp_hero = isset( $args['hero'] ) && is_array( $args['hero'] ) ? $args['hero'] : array();

get_template_part( 'template-parts/services-hero', null, $isha_sp_hero );

if ( $isha_sp_post && '' !== trim( (string) $isha_sp_post->post_content ) ) :
	$isha_sp_builder = 'builder' === get_post_meta( $isha_sp_post->ID, '_elementor_edit_mode', true );
	?>
	<?php if ( $isha_sp_builder ) : ?>
		<div id="isha-services-content" class="isha-services-page__content">
			<?php the_content(); ?>
		</div>
	<?php else : ?>
		<div id="isha-services-content" class="section isha-services-page__content">
			<div class="container">
				<article id="post-<?php echo esc_attr( (string) $isha_sp_post->ID ); ?>" <?php post_class( 'entry', $isha_sp_post ); ?>>
					<div class="entry-content">
						<?php
						the_content();
						wp_link_pages();
						?>
					</div>
				</article>
			</div>
		</div>
	<?php endif; ?>
	<?php
endif;

isha_cta_section();
