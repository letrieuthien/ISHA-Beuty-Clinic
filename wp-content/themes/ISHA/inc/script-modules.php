<?php
/**
 * Thư viện JS tự host dạng ES Module (assets/vendor), đăng ký qua WordPress Script Modules API (importmap gốc, WP 6.5+).
 *
 * Dùng chung cho trang nhóm dịch vụ (section "Bình minh lan tỏa", inc/services-cinematic.php) và trang chủ (gallery 3D của section
 * Founder, inc/home/assets.php). Mỗi module chỉ đăng ký một lần trong một request dù nhiều nơi cùng cần. Nơi dùng khai báo module là
 * phụ thuộc `'import' => 'dynamic'` của entry: WordPress đưa nó vào importmap nhưng không tải sớm; entry tự import() khi cần.
 *
 * Theme cổ điển: WordPress in importmap và module ở chân trang, nên đăng ký muộn (ví dụ trong shortcode) vẫn kịp.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thư viện tự host: id module (trùng bare specifier dùng trong import) => array( đường dẫn trong assets/vendor, phiên bản, phụ thuộc tĩnh ).
 * three.module.js tự import ./three.core.js (đường dẫn tương đối), không cần khai riêng.
 *
 * @return array
 */
function isha_vendor_modules() {
	return array(
		'three'                             => array( 'three@0.187.0/build/three.module.js', '0.187.0', array() ),
		'three/addons/libs/stats.module.js' => array( 'three@0.187.0/examples/jsm/libs/stats.module.js', '0.187.0', array() ),
		'lil-gui'                           => array( 'lil-gui@0.21.0/dist/lil-gui.esm.js', '0.21.0', array() ),
		// Phân loại GPU; dữ liệu benchmark tự host cạnh nó (detect-gpu@6.0.28/dist/benchmarks, tier.js đặt benchmarksURL).
		'@pmndrs/detect-gpu'                => array( 'detect-gpu@6.0.28/dist/index.mjs', '6.0.28', array() ),
	);
}

/**
 * Đăng ký một thư viện tự host (một lần mỗi request).
 *
 * @param string $id Id module trong isha_vendor_modules().
 * @return bool True nếu module đã sẵn sàng trong importmap (đăng ký lần này hoặc trước đó); false nếu WordPress < 6.5, id lạ hoặc thiếu tệp.
 */
function isha_register_vendor_module( $id ) {
	static $done = array();

	if ( isset( $done[ $id ] ) ) {
		return $done[ $id ];
	}
	$mods = isha_vendor_modules();
	$ok   = false;
	if ( function_exists( 'wp_register_script_module' ) && isset( $mods[ $id ] ) ) {
		list( $path, $version, $deps ) = $mods[ $id ];
		if ( is_file( ISHA_DIR . '/assets/vendor/' . $path ) ) {
			wp_register_script_module( $id, ISHA_URI . '/assets/vendor/' . $path, $deps, $version );
			$ok = true;
		}
	}
	$done[ $id ] = $ok;
	return $ok;
}

/**
 * Danh sách phụ thuộc động cho entry module từ các id thư viện; id nào không đăng ký được thì bỏ.
 *
 * @param string[] $ids Id module.
 * @return array array( array( 'id' => <id>, 'import' => 'dynamic' ), ... ).
 */
function isha_vendor_dynamic_deps( array $ids ) {
	$deps = array();
	foreach ( $ids as $id ) {
		if ( isha_register_vendor_module( $id ) ) {
			$deps[] = array(
				'id'     => $id,
				'import' => 'dynamic',
			);
		}
	}
	return $deps;
}
