<?php
/**
 * Template Name: Dịch vụ
 * Template Post Type: page
 *
 * Trang danh sách dịch vụ. Giao diện dùng chung với trang nhóm dịch vụ: template-parts/services-listing.php.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	isha_services_listing( get_post() );
endwhile;

get_footer();
