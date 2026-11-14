<?php
/**
 * Nhắc cài plugin ISHA Core (bắt buộc cho dịch vụ/đặt lịch) và plugin khuyên dùng.
 * Không phụ thuộc TGMPA để theme nhẹ và dễ bảo trì.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Danh sách plugin khuyên dùng (slug => tên).
 *
 * @return array
 */
function isha_recommended_plugins() {
	return array(
		'wordpress-seo' => 'Yoast SEO',
		'wp-super-cache' => 'WP Super Cache',
		'updraftplus'   => 'UpdraftPlus (sao lưu)',
	);
}

/**
 * Thông báo trong admin.
 */
function isha_plugin_notices() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->base, array( 'dashboard', 'themes', 'plugins' ), true ) ) {
		return;
	}

	if ( ! isha_core_active() ) {
		$plugin_file = 'isha-core/isha-core.php';
		$installed   = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
		echo '<div class="notice notice-warning"><p><strong>ISHA:</strong> ';
		if ( $installed ) {
			$url = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $plugin_file ) ), 'activate-plugin_' . $plugin_file );
			printf(
				'%s <a href="%s" class="button button-primary">%s</a>',
				esc_html__( 'Hãy kích hoạt plugin ISHA Core để dùng dịch vụ, đặt lịch hẹn và các shortcode.', 'isha' ),
				esc_url( $url ),
				esc_html__( 'Kích hoạt ngay', 'isha' )
			);
		} else {
			esc_html_e( 'Không tìm thấy plugin ISHA Core trong wp-content/plugins/isha-core.', 'isha' );
		}
		echo '</p></div>';
	}
}
add_action( 'admin_notices', 'isha_plugin_notices' );
