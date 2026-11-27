<?php
/**
 * Cảnh kết của trang chủ điện ảnh (class `isha-home-cta`): hành động chính, đối xứng với hero.
 *
 * Nền đêm; ảnh nền tuỳ chọn (cũng là poster của video nền nếu có) nằm trong `.isha-home-cta__media` với lớp phủ tối làm bằng
 * CSS để chữ đạt tương phản. Giữa khung: logo trắng của site, tiêu đề, mô tả, nút chính "Đặt lịch tư vấn riêng", nút "Nhắn
 * Zalo" (ẩn khi chưa có URL Zalo) và liên kết `tel:` hotline (ẩn khi chưa có số). Video nền là thẻ ngủ (`hidden`, nguồn nằm ở
 * `data-src`): home.js chỉ tải và phát khi cảnh đang hiện, không giảm chuyển động, không tiết kiệm dữ liệu. Video lặp vô hạn, không tiếng,
 * không có nút tạm dừng/phát (quyết định của chủ dự án). Iris khép (Giai đoạn 4) do home.js đặt class `is-iris`; không JS, giảm chuyển
 * động hoặc lõi lỗi thì là trạng thái tĩnh.
 *
 * Dữ liệu: `$args` là isha_home_data( 'cta' ): title, description, background_image, background_video (ID, 0 = không có) và
 * các khoá của isha_home_contact() (booking_url, phone, tel, tel_url, zalo_url). Giá trị thô, escape khi in.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_data = ( isset( $args ) && is_array( $args ) && array_key_exists( 'booking_url', $args ) ) ? $args : isha_home_data( 'cta' );
if ( ! is_array( $isha_data ) || ! $isha_data ) {
	return;
}

// Chân trang biết trang này đã có cảnh kết đặt lịch, không in thêm dải mời ở Tầng 1.
isha_cta_printed( true );

// Giá trị chuỗi của một khoá ('' nếu thiếu hoặc không phải chuỗi), đã trim.
$isha_text = static function ( $key ) use ( $isha_data ) {
	return ( isset( $isha_data[ $key ] ) && is_scalar( $isha_data[ $key ] ) ) ? trim( (string) $isha_data[ $key ] ) : '';
};

$isha_title = $isha_text( 'title' );
if ( '' === $isha_title ) {
	$isha_title = __( 'Khởi đầu bằng một buổi tư vấn riêng', 'isha' );
}
$isha_desc    = $isha_text( 'description' );
$isha_booking = $isha_text( 'booking_url' );
$isha_zalo    = $isha_text( 'zalo_url' );
$isha_tel_url = $isha_text( 'tel_url' );
$isha_phone   = $isha_text( 'phone' );
if ( '' === $isha_phone ) {
	$isha_phone = $isha_text( 'tel' );
}
// Hotline di động 10 chữ số liền (0947683768) tách nhịp 4-3-3 cho dễ đọc, cùng quy tắc isha_footer_phone(); giá trị đã có dấu cách thì giữ nguyên.
if ( preg_match( '/^0\d{9}$/', $isha_phone ) ) {
	$isha_phone = substr( $isha_phone, 0, 4 ) . ' ' . substr( $isha_phone, 4, 3 ) . ' ' . substr( $isha_phone, 7 );
}

// Khối nút chỉ in khi còn ít nhất một URL hợp lệ sau esc_url (URL lạ như javascript: bị bỏ, không để lại khối rỗng).
$isha_has_actions = ( '' !== esc_url( $isha_booking ) || '' !== esc_url( $isha_zalo ) );

// Nền: ảnh (trang trí, alt rỗng) và video ngủ. Không có cả hai thì khung chỉ là nền đêm.
$isha_bg_image = '';
if ( isset( $isha_data['background_image'] ) && is_numeric( $isha_data['background_image'] ) && (int) $isha_data['background_image'] > 0 ) {
	$isha_bg_image = isha_home_image(
		(int) $isha_data['background_image'],
		array(
			'size'       => 'isha-hero',
			'sizes'      => '100vw',
			'class'      => 'isha-home-cta__bg',
			'decorative' => true,
		)
	);
}

$isha_video_url  = '';
$isha_video_mime = '';
if ( isset( $isha_data['background_video'] ) && is_numeric( $isha_data['background_video'] ) && (int) $isha_data['background_video'] > 0 ) {
	$isha_video_url  = isha_home_video_url( (int) $isha_data['background_video'] );
	$isha_video_mime = isha_home_video_mime( (int) $isha_data['background_video'] );
}
$isha_has_media = ( '' !== $isha_bg_image || '' !== $isha_video_url );

// Logo trắng của site (ảnh logo trong Tuỳ biến > Nhận dạng trang web). Chưa có thì dùng logo chữ dự phòng ở phần in.
$isha_logo    = '';
$isha_logo_id = absint( get_theme_mod( 'custom_logo' ) );
if ( $isha_logo_id > 0 && wp_attachment_is_image( $isha_logo_id ) ) {
	$isha_logo = wp_get_attachment_image(
		$isha_logo_id,
		'full',
		false,
		array(
			'class'    => 'isha-home-cta__logo',
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	);
}

isha_home_section_open( 'cta', $isha_has_media ? 'isha-home-cta--media' : '' );
?>
	<?php if ( $isha_has_media ) : ?>
		<div class="isha-home-cta__media" aria-hidden="true">
			<?php echo $isha_bg_image; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?>
			<?php if ( '' !== $isha_video_url ) : ?>
				<video class="isha-home-cta__video" muted playsinline loop preload="none" data-src="<?php echo esc_url( $isha_video_url ); ?>" data-type="<?php echo esc_attr( $isha_video_mime ); ?>" hidden></video>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="isha-wrap isha-home-cta__inner">
		<?php if ( '' !== $isha_logo ) : ?>
			<?php echo $isha_logo; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image() trả thẻ <img> đã escape. ?>
		<?php else : ?>
			<?php isha_home_wordmark( array( 'class' => 'isha-home-cta__wordmark', 'ids' => false ) ); ?>
		<?php endif; ?>

		<h2 class="isha-accent isha-home-cta__title" id="<?php echo esc_attr( isha_home_title_id( 'cta' ) ); ?>"><?php echo esc_html( $isha_title ); ?></h2>

		<?php if ( '' !== $isha_desc ) : ?>
			<div class="isha-home-cta__desc">
				<?php isha_home_p( $isha_desc, 'isha-lead isha-home-cta__text' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $isha_has_actions ) : ?>
			<div class="isha-home-cta__actions">
				<?php isha_home_button( __( 'Đặt lịch tư vấn riêng', 'isha' ), $isha_booking, 'primary', array( 'class' => 'isha-sheen' ) ); ?>
				<?php isha_home_button( __( 'Nhắn Zalo', 'isha' ), $isha_zalo, 'ghost', array( 'target' => '_blank', 'rel' => 'noopener noreferrer' ) ); ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== esc_url( $isha_tel_url ) ) : ?>
			<a class="isha-home-cta__hotline" href="<?php echo esc_url( $isha_tel_url ); ?>">
				<span class="isha-label isha-home-cta__hotline-label"><?php esc_html_e( 'Gọi hotline', 'isha' ); ?></span>
				<?php if ( '' !== $isha_phone ) : ?>
					<span class="isha-home-cta__number"><?php echo esc_html( $isha_phone ); ?></span>
				<?php endif; ?>
			</a>
		<?php endif; ?>
	</div>
<?php
isha_home_section_close();
