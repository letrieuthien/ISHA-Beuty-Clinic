<?php
/**
 * Template Name: Dịch vụ
 * Template Post Type: page
 *
 * Trang Dịch vụ tổng: hero nan quạt (dùng chung với trang nhóm dịch vụ), nội dung soạn trong trang, CTA đặt lịch.
 * Markup ở template-parts/services-page.php (trang nhóm dịch vụ dùng template-parts/services-listing.php).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	isha_services_page( get_post() );
endwhile;

get_footer();
