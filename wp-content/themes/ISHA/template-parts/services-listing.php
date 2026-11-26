<?php
/**
 * Trang nhóm dịch vụ (taxonomy-isha_service_cat.php): hero nan quạt, "Giới thiệu dịch vụ", "Hiệu quả liệu trình", "Các liệu trình
 * trong nhóm", CTA đặt lịch.
 *
 * Gọi qua isha_services_listing() (inc/services-hero.php) với `context` là WP_Term. Ba section giữa hero và CTA do
 * isha_service_group_sections() in (inc/service-group.php); section nào thiếu dữ liệu thì bỏ. Trang Dịch vụ tổng
 * (templates/template-services.php) dùng template riêng: template-parts/services-page.php.
 *
 * Dữ liệu: `$args` = array( 'context' => WP_Term, 'hero' => isha_services_hero_data() ).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_sl_term = ( isset( $args['context'] ) && $args['context'] instanceof WP_Term ) ? $args['context'] : null;
$isha_sl_hero = isset( $args['hero'] ) && is_array( $args['hero'] ) ? $args['hero'] : array();

if ( ! $isha_sl_term ) {
	return;
}

get_template_part( 'template-parts/services-hero', null, $isha_sl_hero );

if ( function_exists( 'isha_service_group_sections' ) ) {
	// Giới thiệu, hiệu quả liệu trình và danh sách liệu trình (thẻ editorial, thay cho lưới shortcode [isha_services]).
	isha_service_group_sections( $isha_sl_term );
} else {
	?>
	<section class="section" id="isha-services-list">
		<div class="container">
			<?php echo isha_do_shortcode( 'isha_services', array( 'category' => $isha_sl_term->slug, 'count' => -1, 'columns' => 3 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>
	<?php
}

// Không in khối CTA cũ ở đây: footer tự in khối mời đặt lịch chuẩn (cùng trang Tin tức), vì trang này không đánh dấu isha_cta_printed().
