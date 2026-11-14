<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

const ZIGZAG_SECTION_ASSETS_VERSION_246455eb = '1.0.0';

function register_zigzag_section_widget_246455eb( $widgets_manager ) {
    require_once __DIR__ . '/widget-zigzag-section.php';
    $widgets_manager->register( new \AngieSnippets\ZigZag_Section_246455eb() );
}
add_action( 'elementor/widgets/register', 'register_zigzag_section_widget_246455eb' );

function register_zigzag_section_assets_246455eb() {
    wp_register_style( 'zigzag-section-style-246455eb', angie_cs_get_snippet_asset_url( __FILE__, 'style.css' ), [], ZIGZAG_SECTION_ASSETS_VERSION_246455eb );
}
add_action( 'wp_enqueue_scripts', 'register_zigzag_section_assets_246455eb' );
