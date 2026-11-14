<?php
/**
 * Gỡ plugin: chỉ xoá thiết lập và option phiên bản rewrite. Nội dung (dịch vụ, lịch hẹn, sự kiện, lời kể, bài viết, nội dung
 * trang chủ) được GIỮ LẠI để tránh mất dữ liệu.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'isha_core_settings' );
delete_option( 'isha_core_rewrite_ver' );
