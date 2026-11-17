<?php
/**
 * Một section theo phong cách "Câu chuyện nhà sáng lập" của trang chủ, dùng chung cho trang Giới thiệu, trang Không gian ISHA và bài Không
 * gian (tách ra từ template-parts/about/page.php ngày 18/11/2026).
 *
 * `section.isha-about-sec`: ảnh dọc 4:5 trong khung viền vàng kim lệch phía sau (vị trí chẵn ảnh bên trái, lẻ bên phải), cột chữ gồm số thứ
 * tự (trang trí), tiêu đề (h2, có thể là liên kết), tiêu đề nhỏ (chữ nghiêng có vạch vàng bên trái), nội dung, liên kết hành động. Không có
 * ảnh thì bố cục một cột chữ.
 *
 * Dữ liệu `$args`:
 *   section  {id, anchor, tone, number (0 = không in), title, subtitle, content (HTML), image (ID ảnh)}.
 *   position Vị trí của section trong trang, từ 0 (quyết định ảnh trái hay phải).
 *   filtered true khi content đã qua bộ lọc the_content (in nguyên); mặc định lọc lại bằng wp_kses_post.
 *   title_url Liên kết của tiêu đề (tuỳ chọn).
 *   link     {url, label, sr} liên kết dưới nội dung (tuỳ chọn); sr là phần chỉ trình đọc màn hình đọc (ví dụ tên bài) để các liên kết cùng chữ
 *            vẫn phân biệt được.
 *   label    Tên truy cập của section khi không có tiêu đề.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_a   = ( isset( $args ) && is_array( $args ) ) ? $args : array();
$isha_sec = ( isset( $isha_a['section'] ) && is_array( $isha_a['section'] ) ) ? $isha_a['section'] : array();

// Lấy một giá trị chuỗi từ một mảng; không phải chuỗi/số thì trả ''.
$isha_str = static function ( $source, $key ) {
	return ( is_array( $source ) && isset( $source[ $key ] ) && is_scalar( $source[ $key ] ) ) ? trim( (string) $source[ $key ] ) : '';
};

$isha_id       = $isha_str( $isha_sec, 'id' );
$isha_anchor   = $isha_str( $isha_sec, 'anchor' );
$isha_tone     = $isha_str( $isha_sec, 'tone' );
$isha_title    = $isha_str( $isha_sec, 'title' );
$isha_subtitle = $isha_str( $isha_sec, 'subtitle' );
$isha_content  = isset( $isha_sec['content'] ) && is_string( $isha_sec['content'] ) ? trim( $isha_sec['content'] ) : '';
$isha_number   = isset( $isha_sec['number'] ) ? (int) $isha_sec['number'] : 0;
$isha_image_id = isset( $isha_sec['image'] ) ? (int) $isha_sec['image'] : 0;
$isha_position = isset( $isha_a['position'] ) ? (int) $isha_a['position'] : 0;
$isha_title_u  = $isha_str( $isha_a, 'title_url' );
$isha_link     = ( isset( $isha_a['link'] ) && is_array( $isha_a['link'] ) ) ? $isha_a['link'] : array();
$isha_link_url = $isha_str( $isha_link, 'url' );
$isha_link_lb  = $isha_str( $isha_link, 'label' );
$isha_link_sr  = $isha_str( $isha_link, 'sr' );
$isha_label    = $isha_str( $isha_a, 'label' );
$isha_filtered = ! empty( $isha_a['filtered'] );

$isha_heading_id = 'isha-about-' . sanitize_html_class( '' !== $isha_id ? $isha_id : $isha_anchor ) . '-title';
$isha_img        = $isha_image_id > 0 ? isha_home_image(
	$isha_image_id,
	array(
		'size'  => 'isha-frame',
		'sizes' => '(max-width: 900px) min(88vw, 420px), 40vw',
		'class' => 'isha-about-sec__img',
	)
) : '';
// Bố cục: có ảnh thì xen kẽ ảnh trái (vị trí chẵn) và ảnh phải (vị trí lẻ); không ảnh thì một cột chữ.
if ( '' === $isha_img ) {
	$isha_layout = 'text';
} else {
	$isha_layout = 0 === $isha_position % 2 ? 'media-start' : 'media-end';
}
$isha_aria = '' !== $isha_title ? 'aria-labelledby="' . esc_attr( $isha_heading_id ) . '"' : ( '' !== $isha_label ? 'aria-label="' . esc_attr( $isha_label ) . '"' : '' );
?>
<section<?php echo '' !== $isha_anchor ? ' id="' . esc_attr( $isha_anchor ) . '"' : ''; ?> class="isha-sec isha-about-sec isha-about-sec--<?php echo esc_attr( $isha_layout ); ?>" data-tone="<?php echo esc_attr( '' !== $isha_tone ? $isha_tone : 'day' ); ?>" <?php echo $isha_aria; // phpcs:ignore WordPress.Security.EscapeOutput -- thuộc tính đã escape ở trên. ?>>
	<div class="isha-wrap isha-about-sec__grid">
		<?php if ( '' !== $isha_img ) : ?>
			<figure class="isha-about-sec__media">
				<div class="isha-about-sec__frame isha-r-45">
					<?php echo $isha_img; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?>
				</div>
			</figure>
		<?php endif; ?>

		<div class="isha-about-sec__body">
			<?php if ( $isha_number > 0 ) : ?>
				<p class="isha-about-sec__index" aria-hidden="true"><?php echo esc_html( str_pad( (string) $isha_number, 2, '0', STR_PAD_LEFT ) ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $isha_title ) : ?>
				<h2 id="<?php echo esc_attr( $isha_heading_id ); ?>" class="isha-sec__title isha-about-sec__title">
					<?php if ( '' !== $isha_title_u ) : ?>
						<a class="isha-about-sec__title-link" href="<?php echo esc_url( $isha_title_u ); ?>"><?php echo esc_html( $isha_title ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $isha_title ); ?>
					<?php endif; ?>
				</h2>
			<?php endif; ?>
			<?php if ( '' !== $isha_subtitle ) : ?>
				<p class="isha-about-sec__subtitle"><?php echo esc_html( $isha_subtitle ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $isha_content ) : ?>
				<div class="isha-about-sec__content">
					<?php
					if ( $isha_filtered ) {
						echo $isha_content; // phpcs:ignore WordPress.Security.EscapeOutput -- đã qua bộ lọc the_content của WordPress.
					} else {
						echo wp_kses_post( $isha_content );
					}
					?>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $isha_link_url && '' !== $isha_link_lb ) : ?>
				<p class="isha-about-sec__action"><a class="isha-link" href="<?php echo esc_url( $isha_link_url ); ?>"><?php echo esc_html( $isha_link_lb ); ?><?php if ( '' !== $isha_link_sr ) : ?><span class="screen-reader-text">: <?php echo esc_html( $isha_link_sr ); ?></span><?php endif; ?></a></p>
			<?php endif; ?>
		</div>
	</div>
</section>
