<?php
/**
 * Dock liên hệ nhanh của trang chủ điện ảnh: "Đặt lịch tư vấn", "Nhắn Zalo", "Gọi hotline".
 *
 * Dữ liệu từ isha_home_contact(); nút nào thiếu dữ liệu thì không in, không có nút nào thì không in cả khối. Vị trí và
 * trạng thái hiện/ẩn (qua hero, ẩn ở cảnh kết) do CSS và home.js quyết định; không có JS thì luôn hiện.
 * Mỗi nút: biểu tượng (trang trí, aria-hidden) rồi nhãn chữ; ở desktop có chuột chỉ hiện biểu tượng, hover hoặc focus thì nhãn giãn ra (home.css).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_contact = isha_home_contact();
$isha_book    = isset( $isha_contact['booking_url'] ) ? esc_url( (string) $isha_contact['booking_url'] ) : '';
// Trang chi tiết liệu trình truyền booking_url kèm ?service=<ID> để popup Đặt lịch chọn sẵn đúng liệu trình.
if ( isset( $args['booking_url'] ) && is_string( $args['booking_url'] ) && '' !== esc_url( $args['booking_url'] ) ) {
	$isha_book = esc_url( $args['booking_url'] );
}
$isha_zalo    = isset( $isha_contact['zalo_url'] ) ? esc_url( (string) $isha_contact['zalo_url'] ) : '';
$isha_tel     = isset( $isha_contact['tel_url'] ) ? esc_url( (string) $isha_contact['tel_url'] ) : '';

if ( '' === $isha_book && '' === $isha_zalo && '' === $isha_tel ) {
	return;
}
?>
<aside class="isha-dock" data-isha-dock aria-label="<?php esc_attr_e( 'Liên hệ nhanh', 'isha' ); ?>">
	<?php if ( '' !== $isha_book ) : ?>
		<a class="isha-btn isha-btn--primary isha-dock__btn isha-dock__btn--book isha-sheen" href="<?php echo $isha_book; // phpcs:ignore WordPress.Security.EscapeOutput -- đã qua esc_url ở trên. ?>"><span class="isha-dock__icon" aria-hidden="true"></span><span class="isha-dock__label"><span><?php esc_html_e( 'Đặt lịch tư vấn', 'isha' ); ?></span></span></a>
	<?php endif; ?>
	<?php if ( '' !== $isha_zalo ) : ?>
		<a class="isha-btn isha-btn--ghost isha-dock__btn isha-dock__btn--zalo" href="<?php echo $isha_zalo; // phpcs:ignore WordPress.Security.EscapeOutput -- đã qua esc_url ở trên. ?>" target="_blank" rel="noopener noreferrer"><span class="isha-dock__icon" aria-hidden="true"></span><span class="isha-dock__label"><span><?php esc_html_e( 'Nhắn Zalo', 'isha' ); ?></span></span></a>
	<?php endif; ?>
	<?php if ( '' !== $isha_tel ) : ?>
		<a class="isha-btn isha-btn--ghost isha-dock__btn isha-dock__btn--call" href="<?php echo $isha_tel; // phpcs:ignore WordPress.Security.EscapeOutput -- đã qua esc_url ở trên. ?>"><span class="isha-dock__icon" aria-hidden="true"></span><span class="isha-dock__label"><span><?php esc_html_e( 'Gọi hotline', 'isha' ); ?></span></span></a>
	<?php endif; ?>
</aside>
