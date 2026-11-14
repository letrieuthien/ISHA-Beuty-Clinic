<?php
/**
 * Một ô (thẻ bài) của lưới Editorial Bento. Dùng chung cho mọi vị trí và cả hai post type; chỉ khác class cấu trúc.
 *
 * Mỗi thẻ có đúng 5 phần: ảnh đại diện, tác giả, ngày viết, tiêu đề, nội dung ngắn. Cả ô bấm được nhờ liên kết ở tiêu đề (CSS
 * phủ ::after lên ô); ảnh chỉ để trang trí nên ẩn khỏi trình đọc màn hình. Thiếu ảnh thì dùng khối giữ chỗ (tông thương hiệu).
 *
 * Tham số ($args): card (kết quả isha_editorial_card()), role (hero, c1..c3, a1..a3), variant (hero, stack, side, cover, strip),
 * index (thứ tự xuất hiện, dùng làm độ trễ hoạt ảnh --i).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_card    = ( isset( $args['card'] ) && is_array( $args['card'] ) ) ? $args['card'] : array();
$isha_role    = ( isset( $args['role'] ) && is_string( $args['role'] ) ) ? sanitize_html_class( $args['role'] ) : 'c1';
$isha_variant = ( isset( $args['variant'] ) && is_string( $args['variant'] ) ) ? sanitize_html_class( $args['variant'] ) : 'stack';
$isha_index   = ( isset( $args['index'] ) && is_numeric( $args['index'] ) ) ? max( 0, (int) $args['index'] ) : 0;

if ( empty( $isha_card['id'] ) ) {
	return;
}

$isha_image = isha_editorial_image( isset( $isha_card['image_id'] ) ? $isha_card['image_id'] : 0, $isha_role );

$isha_classes = array(
	'isha-bento__tile',
	'isha-bento__tile--' . $isha_role,
	'isha-bento__tile--' . $isha_variant,
);
if ( '' === $isha_image ) {
	$isha_classes[] = 'is-noimg';
}

// Ô nằm dưới màn hình đầu hiện theo cuộn (CSS scroll-driven animation); ô còn lại hiện dần ngay khi tải trang.
$isha_reveal = $isha_index >= 3 ? ' data-reveal="scroll"' : '';
?>
<article id="isha-bento-<?php echo (int) $isha_card['id']; ?>" class="<?php echo esc_attr( implode( ' ', $isha_classes ) ); ?>" style="--i:<?php echo (int) $isha_index; ?>"<?php echo $isha_reveal; // phpcs:ignore WordPress.Security.EscapeOutput -- chuỗi cố định. ?>>
	<div class="isha-bento__media" aria-hidden="true">
		<?php if ( '' !== $isha_image ) : ?>
			<?php echo $isha_image; // phpcs:ignore WordPress.Security.EscapeOutput -- thẻ <img> do wp_get_attachment_image() dựng, đã escape. ?>
		<?php else : ?>
			<span class="isha-bento__ph"></span>
		<?php endif; ?>
	</div>

	<div class="isha-bento__body">
		<p class="isha-bento__meta">
			<span class="isha-bento__author"><?php echo esc_html( $isha_card['author'] ); ?></span>
			<time class="isha-bento__date" datetime="<?php echo esc_attr( $isha_card['date_iso'] ); ?>"><?php echo esc_html( $isha_card['date'] ); ?></time>
		</p>
		<h3 class="isha-bento__title"><a class="isha-bento__link" href="<?php echo esc_url( $isha_card['url'] ); ?>"><?php echo esc_html( $isha_card['title'] ); ?></a></h3>
		<?php if ( '' !== $isha_card['summary'] ) : ?>
			<p class="isha-bento__summary"><?php echo esc_html( $isha_card['summary'] ); ?></p>
		<?php endif; ?>
	</div>
</article>
