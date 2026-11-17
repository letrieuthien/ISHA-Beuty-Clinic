<?php
/**
 * Section Không gian của trang chủ điện ảnh: câu dẫn, rồi dải ảnh ngang (isha-row ở mọi kích thước) với các khung xen kẽ
 * dọc cao và ngang rộng; chú thích Garamond in nghiêng đặt lệch dưới mỗi ảnh.
 *
 * Hình dạng khung theo hướng của ảnh gốc (đọc từ metadata của tệp đính kèm): ảnh dọc hoặc vuông vào khung 4:5, ảnh ngang vào
 * khung 3:2; thiếu metadata thì xen kẽ theo thứ tự. Ảnh dùng kích thước `isha-strip` (không cắt) và `object-fit: cover` trong
 * khung. Chú thích nằm ngoài khung ảnh (figcaption) để giai đoạn 4 cho chú thích trôi nhanh hơn ảnh.
 *
 * Từ 18/11/2026 mỗi ảnh là một bài Không gian ISHA được đánh dấu hiện ở trang chủ: cả khung và chú thích (tên bài) là một liên kết tới bài.
 * Ảnh dự phòng (chưa có bài nào được đánh dấu) không có liên kết. Dưới dải ảnh là liên kết tới trang Không gian ISHA khi trang đó có.
 *
 * Dữ liệu: `$args` là isha_home_data( 'space' ): title, lead, link_label, all_url, images (id, caption, alt, url).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Lấy một giá trị chuỗi (đã cắt khoảng trắng) từ một mảng; không phải chuỗi/số thì trả ''.
$isha_text = static function ( $source, $key ) {
	return ( is_array( $source ) && isset( $source[ $key ] ) && is_scalar( $source[ $key ] ) ) ? trim( (string) $source[ $key ] ) : '';
};

// Dữ liệu do shortcode truyền vào qua $args; gọi thẳng template không kèm dữ liệu thì tự lấy.
$isha_data   = ( isset( $args ) && is_array( $args ) && $args ) ? $args : isha_home_data( 'space' );
$isha_title  = $isha_text( $isha_data, 'title' );
$isha_lead   = $isha_text( $isha_data, 'lead' );
$isha_all    = $isha_text( $isha_data, 'all_url' );
$isha_all_lb = $isha_text( $isha_data, 'link_label' );
$isha_photos = array();

if ( isset( $isha_data['images'] ) && is_array( $isha_data['images'] ) ) {
	foreach ( $isha_data['images'] as $isha_row ) {
		$isha_photo_id = ( is_array( $isha_row ) && isset( $isha_row['id'] ) && is_numeric( $isha_row['id'] ) ) ? (int) $isha_row['id'] : 0;
		if ( $isha_photo_id < 1 ) {
			continue;
		}
		$isha_photos[] = array(
			'id'      => $isha_photo_id,
			'caption' => $isha_text( $isha_row, 'caption' ),
			'alt'     => $isha_text( $isha_row, 'alt' ),
			'url'     => $isha_text( $isha_row, 'url' ),
		);
	}
}

if ( ! $isha_photos ) {
	return;
}
if ( '' === $isha_title ) {
	$isha_title = __( 'Không gian ISHA', 'isha' );
}

isha_home_section_open( 'space' );
?>
	<div class="isha-wrap">
		<header class="isha-sec__head">
			<h2 id="<?php echo esc_attr( isha_home_title_id( 'space' ) ); ?>" class="isha-sec__title"><?php echo esc_html( $isha_title ); ?></h2>
			<?php if ( '' !== $isha_lead ) : ?>
				<p class="isha-lead"><?php echo esc_html( $isha_lead ); ?></p>
			<?php endif; ?>
		</header>
	</div>

	<div class="isha-row isha-space__row">
		<ul class="isha-row__track isha-space__track" role="list" tabindex="0" aria-label="<?php echo esc_attr( $isha_title ); ?>">
			<?php foreach ( $isha_photos as $isha_index => $isha_photo ) : ?>
				<?php
				// Hướng ảnh: dọc hoặc vuông (tỉ lệ rộng/cao dưới 1,05) là khung cao; còn lại là khung rộng. Thiếu metadata thì xen kẽ.
				$isha_meta  = wp_get_attachment_metadata( $isha_photo['id'] );
				$isha_ratio = ( is_array( $isha_meta ) && ! empty( $isha_meta['width'] ) && ! empty( $isha_meta['height'] ) ) ? (float) $isha_meta['width'] / (float) $isha_meta['height'] : 0.0;
				if ( $isha_ratio > 0 ) {
					$isha_shape = ( $isha_ratio < 1.05 ) ? 'tall' : 'wide';
				} else {
					$isha_shape = ( 0 === $isha_index % 2 ) ? 'tall' : 'wide';
				}

				$isha_image = isha_home_image(
					$isha_photo['id'],
					array(
						'size'       => 'isha-strip',
						'sizes'      => ( 'tall' === $isha_shape ) ? '(max-width: 900px) 64vw, 380px' : '(max-width: 900px) 84vw, 620px',
						'class'      => 'isha-space__img',
						'alt'        => $isha_photo['alt'],
						'decorative' => '' === $isha_photo['alt'],
					)
				);
				?>
				<li class="isha-row__item isha-space__item isha-space__item--<?php echo esc_attr( $isha_shape ); ?>">
					<?php if ( '' !== $isha_photo['url'] ) : ?>
						<a class="isha-space__link" href="<?php echo esc_url( $isha_photo['url'] ); ?>">
					<?php endif; ?>
					<figure class="isha-space__figure">
						<div class="isha-space__frame">
							<?php
							if ( '' !== $isha_image ) {
								echo $isha_image; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape.
							} else {
								isha_home_placeholder( __( 'Ảnh không gian', 'isha' ) );
							}
							?>
						</div>
						<?php if ( '' !== $isha_photo['caption'] ) : ?>
							<figcaption class="isha-space__caption isha-note"><?php echo esc_html( $isha_photo['caption'] ); ?></figcaption>
						<?php endif; ?>
					</figure>
					<?php if ( '' !== $isha_photo['url'] ) : ?>
						</a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		isha_home_row_controls(
			array(
				'count' => count( $isha_photos ),
				'label' => $isha_title,
			)
		);
		?>
	</div>
	<?php if ( '' !== $isha_all && '' !== $isha_all_lb ) : ?>
		<div class="isha-wrap">
			<p class="isha-space__all"><a class="isha-link" href="<?php echo esc_url( $isha_all ); ?>"><?php echo esc_html( $isha_all_lb ); ?></a></p>
		</div>
	<?php endif; ?>
<?php
isha_home_section_close();
