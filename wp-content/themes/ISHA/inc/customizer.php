<?php
/**
 * Customizer.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Đăng ký panel, section, setting.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function isha_customize_register( $wp_customize ) {
	$defaults = isha_mod_defaults();

	$wp_customize->add_panel(
		'isha_panel',
		array(
			'title'    => __( 'ISHA – Giao diện', 'isha' ),
			'priority' => 30,
		)
	);

	// Section: Chế độ landing.
	$wp_customize->add_section(
		'isha_landing',
		array(
			'title'    => __( 'Chế độ landing', 'isha' ),
			'panel'    => 'isha_panel',
			'priority' => 1,
		)
	);
	$wp_customize->add_setting(
		'isha_landing_only',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'isha_landing_only',
		array(
			'label'       => __( 'Chỉ hiển thị Chính sách khách hàng ở trang chủ', 'isha' ),
			'description' => __( 'Bật: trang chủ chỉ có chính sách, ẩn menu và nút đặt lịch. Tắt: hiển thị toàn bộ website.', 'isha' ),
			'section'     => 'isha_landing',
			'type'        => 'checkbox',
		)
	);

	// Section: Hero.
	$wp_customize->add_section(
		'isha_hero',
		array(
			'title' => __( 'Trang chủ: Hero', 'isha' ),
			'panel' => 'isha_panel',
		)
	);

	$text_fields = array(
		'hero_eyebrow'   => array( __( 'Dòng nhỏ phía trên', 'isha' ), 'text' ),
		'hero_title'     => array( __( 'Tiêu đề', 'isha' ), 'text' ),
		'hero_subtitle'  => array( __( 'Mô tả', 'isha' ), 'textarea' ),
		'hero_btn_text'  => array( __( 'Nút chính (đặt lịch)', 'isha' ), 'text' ),
		'hero_btn2_text' => array( __( 'Nút phụ (xem dịch vụ)', 'isha' ), 'text' ),
	);
	foreach ( $text_fields as $key => $field ) {
		$wp_customize->add_setting(
			'isha_' . $key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => 'textarea' === $field[1] ? 'sanitize_textarea_field' : 'sanitize_text_field',
				'transport'         => 'postMessage',
			)
		);
		$wp_customize->add_control(
			'isha_' . $key,
			array(
				'label'   => $field[0],
				'section' => 'isha_hero',
				'type'    => $field[1],
			)
		);
	}

	$wp_customize->add_setting(
		'isha_hero_image',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Cropped_Image_Control(
			$wp_customize,
			'isha_hero_image',
			array(
				'label'         => __( 'Ảnh nền hero', 'isha' ),
				'section'       => 'isha_hero',
				'flex_width'    => false,
				'flex_height'   => false,
				'width'         => 1920,
				'height'        => 1080,
			)
		)
	);

	// Section: Nội dung trang chủ.
	$wp_customize->add_section(
		'isha_home',
		array(
			'title' => __( 'Trang chủ: Giới thiệu & CTA', 'isha' ),
			'panel' => 'isha_panel',
		)
	);
	$home_fields = array(
		'intro_title' => array( __( 'Giới thiệu: tiêu đề', 'isha' ), 'text' ),
		'intro_text'  => array( __( 'Giới thiệu: nội dung', 'isha' ), 'textarea' ),
		'cta_title'   => array( __( 'Khối CTA: tiêu đề', 'isha' ), 'text' ),
		'cta_text'    => array( __( 'Khối CTA: nội dung', 'isha' ), 'textarea' ),
	);
	foreach ( $home_fields as $key => $field ) {
		$wp_customize->add_setting(
			'isha_' . $key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => 'textarea' === $field[1] ? 'sanitize_textarea_field' : 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			'isha_' . $key,
			array(
				'label'   => $field[0],
				'section' => 'isha_home',
				'type'    => $field[1],
			)
		);
	}

	// Section: Footer.
	$wp_customize->add_section(
		'isha_footer',
		array(
			'title' => __( 'Chân trang', 'isha' ),
			'panel' => 'isha_panel',
		)
	);
	$footer_fields = array(
		'footer_about'  => array( __( 'Giới thiệu ngắn', 'isha' ), 'textarea' ),
		'footer_invite' => array( __( 'Câu mời đặt lịch (dải đầu chân trang, chỉ hiện ở trang chưa có khối đặt lịch riêng)', 'isha' ), 'textarea' ),
		'footer_credit' => array( __( 'Dòng bản quyền (để trống = tự động)', 'isha' ), 'text' ),
	);
	foreach ( $footer_fields as $key => $field ) {
		$wp_customize->add_setting(
			'isha_' . $key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => 'textarea' === $field[1] ? 'sanitize_textarea_field' : 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			'isha_' . $key,
			array(
				'label'   => $field[0],
				'section' => 'isha_footer',
				'type'    => $field[1],
			)
		);
	}

	$wp_customize->get_setting( 'blogname' )->transport = 'postMessage';

	// Bốn điều khiển của trang chủ cũ không còn nơi nào đọc (trang chủ mới nhập nội dung ở Trang > Trang chủ): ghi rõ để người dùng khỏi sửa vô ích.
	foreach ( array( 'isha_hero_image', 'isha_hero_btn2_text', 'isha_intro_title', 'isha_intro_text' ) as $unused_id ) {
		$unused_control = $wp_customize->get_control( $unused_id );
		if ( $unused_control ) {
			$unused_control->description = __( 'Không còn tác dụng: trang chủ mới nhập nội dung ở Trang › Trang chủ (khung "Nội dung trang chủ").', 'isha' );
		}
	}
}
add_action( 'customize_register', 'isha_customize_register' );

/**
 * Live preview cho các setting postMessage.
 */
function isha_customize_preview_js() {
	wp_enqueue_script(
		'isha-customizer',
		ISHA_URI . '/assets/js/customizer.js',
		array( 'customize-preview' ),
		ISHA_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'isha_customize_preview_js' );
