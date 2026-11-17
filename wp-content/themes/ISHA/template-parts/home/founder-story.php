<?php
/**
 * Section Founder (founder-story) của trang chủ điện ảnh: câu chuyện của bác sĩ sáng lập. Thay section Tầm nhìn từ 17/11/2026.
 *
 * Tông afternoon (nền Skin, chữ Shadow). Desktop: hai cột, chân dung dọc 4:5 bên trái với khung viền vàng kim lệch phía sau; cột phải gồm
 * tiêu đề (h2), câu trích Garamond nghiêng có vạch Gold bên trái (dấu ngoặc kép typographic do template thêm, ẩn khỏi trình đọc màn hình:
 * thẻ blockquote đã báo đây là lời trích), câu chuyện (Montserrat; dòng trống tách đoạn) và khối chữ ký: ảnh chữ ký (tuỳ chọn, trang trí vì
 * tên in ngay dưới), tên, chức danh. Chế độ gọn: chân dung trên, chữ dưới. Thiếu chân dung thì khối giữ chỗ 4:5; khối chữ nào trống thì bỏ.
 *
 * Dữ liệu: `$args` là isha_home_data( 'founder-story' ): title, portrait (ID ảnh), quote, story, name, role, signature (ID ảnh, 0 = không có).
 * Giá trị thô, template tự escape.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_data = ( isset( $args ) && is_array( $args ) && $args ) ? $args : isha_home_data( 'founder-story' );
if ( ! is_array( $isha_data ) || ! $isha_data ) {
	return;
}

// Văn bản một dòng từ trường dữ liệu: chuỗi, gộp khoảng trắng và xuống dòng, đã trim.
$isha_line = static function ( $key ) use ( $isha_data ) {
	$value = ( isset( $isha_data[ $key ] ) && is_scalar( $isha_data[ $key ] ) ) ? (string) $isha_data[ $key ] : '';
	return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
};

// ID ảnh từ trường dữ liệu (0 nếu không có).
$isha_media_id = static function ( $key ) use ( $isha_data ) {
	return ( isset( $isha_data[ $key ] ) && is_numeric( $isha_data[ $key ] ) ) ? max( 0, (int) $isha_data[ $key ] ) : 0;
};

$isha_title = $isha_line( 'title' );
if ( '' === $isha_title ) {
	$isha_title = __( 'Câu chuyện nhà sáng lập', 'isha' );
}

// Câu trích: bỏ dấu ngoặc kép người nhập tự gõ ở hai đầu (template tự thêm cặp typographic).
$isha_quote = trim( (string) preg_replace( '/^["\x{201C}\x{201D}\x{201E}\x{201F}\x{00AB}\x{00BB}\s]+|["\x{201C}\x{201D}\x{201E}\x{201F}\x{00AB}\x{00BB}\s]+$/u', '', $isha_line( 'quote' ) ) );
$isha_story = ( isset( $isha_data['story'] ) && is_scalar( $isha_data['story'] ) ) ? (string) $isha_data['story'] : '';
$isha_name  = $isha_line( 'name' );
$isha_role  = $isha_line( 'role' );

// Chức danh mặc định là tiếng Anh ("Medical Director"): đúng bản mặc định thì đánh dấu lang="en" cho trình đọc màn hình (WCAG 3.1.2). Chữ do
// người dùng nhập (có thể là tiếng Việt không dấu) thì không đoán ngôn ngữ.
$isha_defaults  = isha_home_defaults( 'founder-story' );
$isha_role_lang = ( '' !== $isha_role && isset( $isha_defaults['role'] ) && $isha_role === $isha_defaults['role'] && ! preg_match( '/[^\x00-\x7F]/', $isha_role ) ) ? ' lang="en"' : '';

// Chân dung: alt lấy từ Thư viện; trống thì "Chân dung <tên>".
$isha_portrait_id  = $isha_media_id( 'portrait' );
$isha_portrait_alt = $isha_portrait_id > 0 ? trim( (string) get_post_meta( $isha_portrait_id, '_wp_attachment_image_alt', true ) ) : '';
if ( '' === $isha_portrait_alt ) {
	/* translators: %s: tên nhà sáng lập */
	$isha_portrait_alt = '' !== $isha_name ? sprintf( __( 'Chân dung %s', 'isha' ), $isha_name ) : __( 'Chân dung nhà sáng lập', 'isha' );
}

$isha_signature = '';
if ( $isha_media_id( 'signature' ) > 0 ) {
	$isha_signature = isha_home_image(
		$isha_media_id( 'signature' ),
		array(
			'size'       => 'medium_large',
			'sizes'      => '(max-width: 900px) 50vw, 240px',
			'class'      => 'isha-founder-story__signature',
			'decorative' => true,
		)
	);
}

isha_home_section_open( 'founder-story' );
?>
	<div class="isha-wrap isha-founder-story__grid">
		<figure class="isha-founder-story__portrait">
			<div class="isha-founder-story__frame isha-r-45">
				<?php
				isha_home_media(
					$isha_portrait_id,
					__( 'Ảnh chân dung nhà sáng lập', 'isha' ),
					array(
						'size'  => 'isha-frame',
						'sizes' => '(max-width: 900px) min(88vw, 420px), 40vw',
						'class' => 'isha-founder-story__img',
						'alt'   => $isha_portrait_alt,
						'ratio' => '45',
					)
				);
				?>
			</div>
		</figure>

		<div class="isha-founder-story__body">
			<h2 id="<?php echo esc_attr( isha_home_title_id( 'founder-story' ) ); ?>" class="isha-sec__title isha-founder-story__title"><?php echo esc_html( $isha_title ); ?></h2>

			<?php if ( '' !== $isha_quote ) : ?>
				<blockquote class="isha-founder-story__quote">
					<p class="isha-accent isha-founder-story__quote-text"><span aria-hidden="true">&ldquo;</span><?php echo esc_html( $isha_quote ); ?><span aria-hidden="true">&rdquo;</span></p>
				</blockquote>
			<?php endif; ?>

			<?php if ( '' !== trim( $isha_story ) ) : ?>
				<div class="isha-founder-story__story">
					<?php isha_home_p( $isha_story, 'isha-text' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $isha_signature || '' !== $isha_name || '' !== $isha_role ) : ?>
				<div class="isha-founder-story__sign">
					<?php echo $isha_signature; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?>
					<?php if ( '' !== $isha_name ) : ?>
						<p class="isha-founder-story__name"><?php echo esc_html( $isha_name ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $isha_role ) : ?>
						<p class="isha-label isha-founder-story__role"<?php echo $isha_role_lang; // phpcs:ignore WordPress.Security.EscapeOutput -- chuỗi cố định. ?>><?php echo esc_html( $isha_role ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php
isha_home_section_close();
