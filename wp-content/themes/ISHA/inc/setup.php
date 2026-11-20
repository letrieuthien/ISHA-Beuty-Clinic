<?php
/**
 * Theme setup.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme supports, menus, image sizes.
 */
function isha_setup() {
	load_theme_textdomain( 'isha', ISHA_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus(
		array(
			'primary' => __( 'Menu chính', 'isha' ),
			'footer'  => __( 'Menu chân trang', 'isha' ),
		)
	);

	add_image_size( 'isha-card', 720, 540, true );
	add_image_size( 'isha-portrait', 640, 800, true );
	add_image_size( 'isha-hero', 1920, 1080, true );

	// Trang chủ điện ảnh: khung dọc 4:5, khung màn ảnh rộng 2.39:1, dải ảnh ngang giữ nguyên tỉ lệ.
	add_image_size( 'isha-frame', 1200, 1500, true );
	add_image_size( 'isha-cinema', 2400, 1004, true );
	add_image_size( 'isha-strip', 1600, 1100, false );

	// Ảnh banner ngang 16:10 của bài Tin tức (bài nổi bật ở section Tin tức của trang chủ, 20/11/2026): bản lớn và bản nhỏ cùng
	// tỉ lệ để srcset có bản cho điện thoại. Chỉ ảnh tải lên từ nay mới có hai bản này; ảnh cũ dùng ảnh gốc kèm srcset của nó.
	add_image_size( 'isha-banner', 1600, 1000, true );
	add_image_size( 'isha-banner-sm', 800, 500, true );
}
add_action( 'after_setup_theme', 'isha_setup' );

/**
 * Content width.
 */
function isha_content_width() {
	$GLOBALS['content_width'] = 1160;
}
add_action( 'after_setup_theme', 'isha_content_width', 0 );

/**
 * Sidebar (dùng cho trang blog).
 */
function isha_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Sidebar blog', 'isha' ),
			'id'            => 'sidebar-blog',
			'description'   => __( 'Hiển thị ở trang tin tức và bài viết.', 'isha' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'isha_widgets_init' );

/**
 * Tóm tắt bài viết.
 */
add_filter(
	'excerpt_length',
	static function () {
		return 28;
	}
);
add_filter(
	'excerpt_more',
	static function () {
		return '…';
	}
);

/**
 * Menu fallback khi chưa gán menu: liệt kê các trang chính.
 */
function isha_menu_fallback() {
	echo '<ul class="primary-menu">';
	wp_list_pages(
		array(
			'title_li' => '',
			'depth'    => 1,
			'number'   => 7,
		)
	);
	echo '</ul>';
}

/**
 * Thêm class tiện cho body: `has-hero` ở trang chủ; `isha-home` chỉ khi trang chủ điện ảnh đang được dựng
 * (mọi selector CSS của trang chủ mới nằm dưới class này).
 *
 * @param array $classes Body classes.
 * @return array
 */
function isha_body_classes( $classes ) {
	if ( is_front_page() ) {
		$classes[] = 'has-hero';
	}
	if ( isha_home_is_active() ) {
		$classes[] = 'isha-home';
	}
	return $classes;
}
add_filter( 'body_class', 'isha_body_classes' );
