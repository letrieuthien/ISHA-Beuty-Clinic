<?php
/**
 * Section Tầm nhìn của trang chủ điện ảnh: lời kết cảm xúc ngay trước hành động.
 *
 * Nền đêm. `h2` ẩn khỏi mắt (`.isha-sr`, dành cho trình đọc màn hình); câu trích Garamond accent nghiêng căn giữa với dấu ngoặc
 * kép typographic do template thêm (ẩn khỏi trình đọc màn hình: thẻ blockquote đã báo đây là lời trích); đoạn tầm nhìn cỡ chữ
 * thân; khối chữ ký gồm ảnh chữ ký (tuỳ chọn, trang trí vì tên in ngay dưới), tên và chức danh. Không có ảnh chữ ký thì chỉ có
 * tên và chức danh. Chữ ký đưa về màu sáng bằng CSS (khối `vision` của home.css).
 *
 * Dữ liệu: `$args` là isha_home_data( 'vision' ): title, quote, body, role, name, signature (ID ảnh, 0 = không có). Giá trị thô.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_data = ( isset( $args ) && is_array( $args ) && array_key_exists( 'quote', $args ) ) ? $args : isha_home_data( 'vision' );
if ( ! is_array( $isha_data ) || ! $isha_data ) {
	return;
}

// Văn bản một dòng từ trường dữ liệu: chuỗi, gộp khoảng trắng và xuống dòng, đã trim.
$isha_line = static function ( $key ) use ( $isha_data ) {
	$value = ( isset( $isha_data[ $key ] ) && is_scalar( $isha_data[ $key ] ) ) ? (string) $isha_data[ $key ] : '';
	return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
};

$isha_title = $isha_line( 'title' );
if ( '' === $isha_title ) {
	$isha_title = __( 'Tầm nhìn', 'isha' );
}

// Câu trích: bỏ dấu ngoặc kép người nhập tự gõ ở hai đầu (template tự thêm cặp typographic).
$isha_quote = trim( (string) preg_replace( '/^["\x{201C}\x{201D}\x{201E}\x{201F}\x{00AB}\x{00BB}\s]+|["\x{201C}\x{201D}\x{201E}\x{201F}\x{00AB}\x{00BB}\s]+$/u', '', $isha_line( 'quote' ) ) );
$isha_body  = ( isset( $isha_data['body'] ) && is_scalar( $isha_data['body'] ) ) ? (string) $isha_data['body'] : '';
$isha_role  = $isha_line( 'role' );
$isha_name  = $isha_line( 'name' );
// Chức danh mặc định là tiếng Anh ("Medical Director"): đúng bản mặc định thì đánh dấu lang="en" cho trình đọc màn hình (WCAG 3.1.2). Chữ do người dùng nhập
// (có thể là tiếng Việt không dấu) thì không đoán ngôn ngữ.
$isha_role_default = isha_home_defaults( 'vision' );
$isha_role_lang    = ( '' !== $isha_role && isset( $isha_role_default['role'] ) && $isha_role === $isha_role_default['role'] && ! preg_match( '/[^\x00-\x7F]/', $isha_role ) ) ? ' lang="en"' : '';

$isha_signature = '';
if ( isset( $isha_data['signature'] ) && is_numeric( $isha_data['signature'] ) && (int) $isha_data['signature'] > 0 ) {
	$isha_signature = isha_home_image(
		(int) $isha_data['signature'],
		array(
			'size'       => 'medium_large',
			'sizes'      => '(max-width: 900px) 60vw, 320px',
			'class'      => 'isha-vision__signature',
			'decorative' => true,
		)
	);
}

isha_home_section_open( 'vision' );
?>
	<div class="isha-wrap isha-vision__inner">
		<h2 class="isha-sr" id="<?php echo esc_attr( isha_home_title_id( 'vision' ) ); ?>"><?php echo esc_html( $isha_title ); ?></h2>

		<?php if ( '' !== $isha_quote ) : ?>
			<blockquote class="isha-vision__quote">
				<p class="isha-accent isha-vision__quote-text"><span class="isha-vision__mark" aria-hidden="true">&ldquo;</span><?php echo esc_html( $isha_quote ); ?><span class="isha-vision__mark" aria-hidden="true">&rdquo;</span></p>
			</blockquote>
		<?php endif; ?>

		<?php if ( '' !== trim( $isha_body ) ) : ?>
			<div class="isha-vision__body">
				<?php isha_home_p( $isha_body, 'isha-text isha-vision__text' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $isha_signature || '' !== $isha_name || '' !== $isha_role ) : ?>
			<div class="isha-vision__sign">
				<?php echo $isha_signature; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?>
				<?php if ( '' !== $isha_name ) : ?>
					<p class="isha-vision__name"><?php echo esc_html( $isha_name ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $isha_role ) : ?>
					<p class="isha-label isha-vision__role"<?php echo $isha_role_lang; // phpcs:ignore WordPress.Security.EscapeOutput -- chuỗi cố định. ?>><?php echo esc_html( $isha_role ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
<?php
isha_home_section_close();
