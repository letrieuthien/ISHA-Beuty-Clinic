<?php
/**
 * Section Đối tác của trang chủ điện ảnh: phần credit cuối phim.
 *
 * Nền đêm. Tiêu đề nhỏ, rồi MỘT lưới logo thoáng, căn giữa; mọi ô cùng kích thước (tỉ lệ 3:2, đệm đều, ảnh object-fit: contain),
 * logo đưa về một màu sáng và opacity 0,7 bằng CSS (khối `partners` của home.css). Không liên kết; tên đối tác là alt của ảnh
 * (thiếu alt thì lấy tiêu đề của tệp trong Thư viện). Logo do người dùng tải lên luôn in bằng thẻ <img>, không nội tuyến.
 *
 * Dữ liệu: `$args` là isha_home_data( 'partners' ): title, images (id, alt). Giá trị thô, escape khi in.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_data = ( isset( $args ) && is_array( $args ) && isset( $args['images'] ) ) ? $args : isha_home_data( 'partners' );
$isha_list = ( isset( $isha_data['images'] ) && is_array( $isha_data['images'] ) ) ? $isha_data['images'] : array();

$isha_title = ( isset( $isha_data['title'] ) && is_scalar( $isha_data['title'] ) ) ? trim( (string) $isha_data['title'] ) : '';
if ( '' === $isha_title ) {
	$isha_title = __( 'Đồng hành cùng ISHA', 'isha' );
}

// Dựng trước từng logo: ô nào không có ảnh hợp lệ thì bỏ hẳn để lưới không có lỗ trống.
$isha_logos = array();
foreach ( $isha_list as $isha_raw ) {
	if ( ! is_array( $isha_raw ) || ! isset( $isha_raw['id'] ) || ! is_numeric( $isha_raw['id'] ) || (int) $isha_raw['id'] < 1 ) {
		continue;
	}

	$isha_id  = (int) $isha_raw['id'];
	$isha_alt = ( isset( $isha_raw['alt'] ) && is_scalar( $isha_raw['alt'] ) ) ? trim( (string) $isha_raw['alt'] ) : '';
	if ( '' === $isha_alt ) {
		$isha_alt = trim( wp_strip_all_tags( get_the_title( $isha_id ) ) );
	}

	$isha_html = isha_home_image(
		$isha_id,
		array(
			'size'  => 'medium_large',
			'sizes' => '(max-width: 900px) 44vw, 260px',
			'class' => 'isha-partners__logo',
			'alt'   => $isha_alt,
		)
	);
	if ( '' !== $isha_html ) {
		$isha_logos[] = $isha_html;
	}
}

if ( ! $isha_logos ) {
	return;
}

isha_home_section_open( 'partners' );
?>
	<div class="isha-wrap">
		<header class="isha-sec__head isha-partners__head">
			<h2 class="isha-sec__title" id="<?php echo esc_attr( isha_home_title_id( 'partners' ) ); ?>"><?php echo esc_html( $isha_title ); ?></h2>
		</header>

		<ul class="isha-partners__grid">
			<?php foreach ( $isha_logos as $isha_logo ) : ?>
				<li class="isha-partners__cell"><?php echo $isha_logo; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php
isha_home_section_close();
