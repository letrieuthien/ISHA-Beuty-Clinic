<?php
/**
 * Trang nhóm dịch vụ (/nhom-dich-vu/<nhóm>/): cùng giao diện với trang Dịch vụ (template dùng chung
 * template-parts/services-listing.php); dữ liệu hero lấy từ nhóm, xem isha_services_hero_term_fields().
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
isha_services_listing( get_queried_object() );
get_footer();
