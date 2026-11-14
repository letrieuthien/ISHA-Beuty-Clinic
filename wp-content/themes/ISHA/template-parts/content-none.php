<?php
/**
 * Không có nội dung.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="no-results text-center">
	<h2><?php esc_html_e( 'Chưa có nội dung', 'isha' ); ?></h2>
	<p><?php esc_html_e( 'Không tìm thấy kết quả phù hợp. Hãy thử tìm kiếm với từ khoá khác.', 'isha' ); ?></p>
	<?php get_search_form(); ?>
</div>
