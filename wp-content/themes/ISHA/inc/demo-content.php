<?php
/**
 * Tạo trang + menu mặc định khi kích hoạt theme (chỉ chạy một lần).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Danh sách trang mặc định.
 *
 * @return array
 */
function isha_default_pages() {
	return array(
		'home'     => array(
			'title'    => 'Trang chủ',
			'slug'     => 'trang-chu',
			'template' => '',
			'content'  => '',
		),
		'about'    => array(
			'title'    => 'Giới thiệu',
			'slug'     => 'gioi-thieu',
			'template' => 'templates/template-about.php',
			'content'  => '<!-- wp:paragraph --><p>ISHA là phòng khám da liễu thẩm mỹ được xây dựng để giúp mỗi người tìm lại vẻ đẹp vốn có. Hãy chỉnh sửa nội dung này trong trình soạn thảo.</p><!-- /wp:paragraph -->',
		),
		'services' => array(
			'title'    => 'Dịch vụ',
			'slug'     => 'dich-vu',
			'template' => 'templates/template-services.php',
			'content'  => '',
		),
		'policies' => array(
			'title'    => 'Chính sách dịch vụ',
			'slug'     => 'chinh-sach-dich-vu',
			'template' => 'templates/template-policies.php',
			'content'  => '',
		),
		'booking'  => array(
			'title'    => 'Đặt lịch hẹn',
			'slug'     => 'dat-lich-hen',
			'template' => 'templates/template-booking.php',
			'content'  => '',
		),
		'news'     => array(
			'title'    => 'Tin tức',
			'slug'     => 'tin-tuc',
			'template' => '',
			'content'  => '',
		),
		'contact'  => array(
			'title'    => 'Liên hệ',
			'slug'     => 'lien-he',
			'template' => 'templates/template-contact.php',
			'content'  => '',
		),
	);
}

/**
 * Tạo trang, đặt trang chủ / trang tin tức, tạo menu chính.
 */
function isha_install_default_content() {
	if ( get_option( 'isha_default_content_installed' ) ) {
		return;
	}

	$ids = array();
	foreach ( isha_default_pages() as $key => $page ) {
		$existing = get_page_by_path( $page['slug'] );
		if ( $existing ) {
			$ids[ $key ] = $existing->ID;
			continue;
		}
		$args = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $page['title'],
			'post_name'    => $page['slug'],
			'post_content' => $page['content'],
		);
		if ( $page['template'] ) {
			$args['meta_input'] = array( '_wp_page_template' => $page['template'] );
		}
		$id = wp_insert_post( $args );
		if ( $id && ! is_wp_error( $id ) ) {
			$ids[ $key ] = $id;
		}
	}

	if ( ! empty( $ids['home'] ) && ! empty( $ids['news'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
		update_option( 'page_for_posts', $ids['news'] );
	}

	// Menu chính.
	$menu_name = 'ISHA – Menu chính';
	if ( ! wp_get_nav_menu_object( $menu_name ) ) {
		$menu_id = wp_create_nav_menu( $menu_name );
		if ( ! is_wp_error( $menu_id ) ) {
			foreach ( array( 'home', 'about', 'services', 'policies', 'news', 'contact' ) as $key ) {
				if ( empty( $ids[ $key ] ) ) {
					continue;
				}
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object-id' => $ids[ $key ],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
			}
			$locations            = get_theme_mod( 'nav_menu_locations', array() );
			$locations['primary'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}

	update_option( 'isha_default_content_installed', 1 );
	flush_rewrite_rules( false );
}
add_action( 'after_switch_theme', 'isha_install_default_content' );
