<?php
/**
 * Sidebar.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_active_sidebar( 'sidebar-blog' ) ) {
	return;
}
?>
<aside class="sidebar" aria-label="<?php esc_attr_e( 'Sidebar', 'isha' ); ?>">
	<?php dynamic_sidebar( 'sidebar-blog' ); ?>
</aside>
