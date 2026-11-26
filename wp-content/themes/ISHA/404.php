<?php
/**
 * 404: trang không tìm thấy, dùng chung khung header và footer với các trang khác.
 *
 * - Nền: ảnh tuỳ chọn (Tùy biến > ISHA – Giao diện > Trang 404), làm mờ bằng CSS (assets/css/404.css). Chưa có ảnh thì nền tối có vầng vàng nhẹ.
 * - Cụm "404": ảnh assets/images/404.png (4 · mặt trời · 4, nền trong suốt). Khối bọc ảnh là aria-hidden, còn "404" được đọc một lần
 *   qua chữ ẩn (screen-reader-text). Ảnh nghiêng nhẹ theo con trỏ: assets/js/404.js; kiểu dáng trong assets/css/404.css.
 * - Dải mời đặt lịch nằm ở chân trang (footer.php, Tầng 1), không in riêng ở đây. Theo chế độ landing như các trang khác:
 *   bật thì ẩn, tắt thì hiện.
 * - Menu và nút đặt lịch trên header cũng theo chế độ landing (header.php).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ảnh nền: chỉ lấy khi có ID ảnh hợp lệ. Ảnh đã xoá khỏi thư viện thì coi như chưa có ảnh.
$isha_404_bg       = '';
$isha_404_image_id = absint( get_theme_mod( 'isha_404_image', 0 ) );
if ( $isha_404_image_id > 0 && wp_attachment_is_image( $isha_404_image_id ) ) {
	$isha_404_url = wp_get_attachment_image_url( $isha_404_image_id, 'full' );
	$isha_404_bg  = is_string( $isha_404_url ) ? $isha_404_url : '';
}

get_header();
?>
<section class="isha-404<?php echo '' !== $isha_404_bg ? ' isha-404--photo' : ''; ?>" aria-labelledby="isha-404-title">
	<?php if ( '' !== $isha_404_bg ) : ?>
		<div class="isha-404__bg" aria-hidden="true" style="background-image: url('<?php echo esc_url( $isha_404_bg ); ?>');"></div>
	<?php endif; ?>

	<div class="container container--narrow isha-404__inner">
		<p class="screen-reader-text">404</p>
		<div class="isha-404__code" aria-hidden="true"><img src="<?php echo esc_url( ISHA_URI . '/assets/images/404.png' ); ?>" width="2016" height="780" alt="" decoding="async"></div>
		<h1 class="isha-404__title" id="isha-404-title"><?php esc_html_e( 'Trang không tìm thấy', 'isha' ); ?></h1>
		<p class="isha-404__lead"><?php esc_html_e( 'Đường dẫn này có thể đã thay đổi hoặc không còn tồn tại. Mời bạn quay lại trang chủ để tiếp tục khám phá ISHA.', 'isha' ); ?></p>
		<div class="isha-404__actions">
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Quay về trang chủ', 'isha' ); ?></a>
		</div>
	</div>
</section>
<?php
get_footer();
