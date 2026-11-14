<?php
/**
 * Template dùng chung của trang danh sách dịch vụ: hero nan quạt + danh sách liệu trình + CTA đặt lịch.
 *
 * Gọi qua isha_services_listing() (inc/services-hero.php) từ:
 *   templates/template-services.php – `context` là WP_Post (trang Dịch vụ): mọi liệu trình, kèm nội dung soạn trong trang nếu có.
 *   taxonomy-isha_service_cat.php   – `context` là WP_Term (nhóm dịch vụ): liệu trình của nhóm, thẻ của nhóm được đánh dấu.
 *
 * Dữ liệu: `$args` = array( 'context' => WP_Post|WP_Term, 'hero' => isha_services_hero_data() ).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_sl_context = isset( $args['context'] ) ? $args['context'] : null;
$isha_sl_hero    = isset( $args['hero'] ) && is_array( $args['hero'] ) ? $args['hero'] : array();
$isha_sl_is_term = $isha_sl_context instanceof WP_Term;

get_template_part( 'template-parts/services-hero', null, $isha_sl_hero );
?>
<section class="section" id="isha-services-list">
	<div class="container">
		<?php
		if ( $isha_sl_is_term ) {
			echo isha_do_shortcode( 'isha_services', array( 'category' => $isha_sl_context->slug, 'count' => -1, 'columns' => 3 ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			if ( $isha_sl_context instanceof WP_Post && '' !== trim( (string) $isha_sl_context->post_content ) ) {
				echo '<div class="entry-content container--narrow">';
				the_content();
				echo '</div>';
			}
			echo isha_do_shortcode( 'isha_services', array( 'count' => -1, 'columns' => 3 ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?>
	</div>
</section>
<?php
isha_cta_section();
