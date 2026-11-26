<?php
/**
 * Trang Dịch vụ tổng (templates/template-services.php), thứ tự chốt ngày 17/11/2026: hero nan quạt (dùng chung với trang nhóm dịch vụ),
 * vùng nội dung soạn bằng Elementor (hoặc trình soạn thảo), lưới các nhóm dịch vụ lấy tự động (template-parts/services-groups.php), CTA đặt lịch.
 *
 * Nội dung trống thì bỏ khối nội dung (trừ trang dựng bằng Elementor / đang xem trước trong Elementor: luôn in để trình soạn có vùng nội dung). Trang dựng bằng Elementor thì in nội dung tràn mép (Elementor tự quản lý khung và lề);
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

$isha_sp_builder = $isha_sp_post && 'builder' === get_post_meta( $isha_sp_post->ID, '_elementor_edit_mode', true );
// Elementor cần the_content() để tìm vùng soạn: trang mới (nội dung còn rỗng) vẫn phải in khối khi đang dựng bằng Elementor/xem trước trong trình soạn.
$isha_sp_preview = isset( $_GET['elementor-preview'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

if ( $isha_sp_post && ( $isha_sp_builder || $isha_sp_preview || '' !== trim( (string) $isha_sp_post->post_content ) ) ) :
	$isha_sp_builder = $isha_sp_builder || $isha_sp_preview;
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

get_template_part( 'template-parts/services-groups', null, array( 'groups' => isset( $isha_sp_hero['groups'] ) && is_array( $isha_sp_hero['groups'] ) ? $isha_sp_hero['groups'] : array() ) );

// Không in khối CTA cũ ở đây: footer tự in khối mời đặt lịch chuẩn (cùng trang Tin tức), vì trang này không đánh dấu isha_cta_printed().
