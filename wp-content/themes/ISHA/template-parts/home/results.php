<?php
/**
 * Section Hiệu quả liệu trình (results) của trang chủ điện ảnh: tiêu đề, câu dẫn, rồi tối đa 5 ảnh trước/sau ĐÃ GHÉP SẴN (mỗi ảnh là một
 * tấm do khách hàng thiết kế, gồm cả hai hình trước và sau), chú thích dưới ảnh, cụm phân trang dùng chung và ghi chú miễn trừ kết quả.
 *
 * Tĩnh (không JS, lõi lỗi): các ảnh xếp dọc, đọc được trọn vẹn. home.js (khối results, hàm deck()) dựng băng chuyền APG dùng chung với
 * Sự kiện khi có từ 2 ảnh: các ảnh chồng trong một ô, đổi bằng nút Trước/Sau, chấm, kéo chuột hoặc vuốt, tự chạy mỗi 5 giây.
 *
 * Ảnh giữ đúng tỉ lệ gốc (không cắt; kích thước isha-strip), cao tối đa khoảng 60% màn hình, trong khung viền vàng kim. Alt lấy từ Thư viện
 * phương tiện, trống thì "Ảnh trước và sau điều trị n".
 *
 * Dữ liệu: `$args` là isha_home_data( 'results' ): title, lead, note, images (id, caption, alt). Giá trị thô, template tự escape.
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

$isha_data  = ( isset( $args ) && is_array( $args ) && $args ) ? $args : isha_home_data( 'results' );
$isha_title = $isha_text( $isha_data, 'title' );
$isha_lead  = $isha_text( $isha_data, 'lead' );
$isha_note  = $isha_text( $isha_data, 'note' );

// Dựng sẵn thẻ ảnh; ảnh không dùng được thì bỏ.
$isha_slides = array();
if ( isset( $isha_data['images'] ) && is_array( $isha_data['images'] ) ) {
	foreach ( $isha_data['images'] as $isha_row ) {
		if ( count( $isha_slides ) >= 5 ) {
			break;
		}
		$isha_id  = ( is_array( $isha_row ) && isset( $isha_row['id'] ) && is_numeric( $isha_row['id'] ) ) ? (int) $isha_row['id'] : 0;
		$isha_no  = count( $isha_slides ) + 1;
		$isha_alt = $isha_text( $isha_row, 'alt' );
		if ( '' === $isha_alt ) {
			/* translators: %d: số thứ tự ảnh */
			$isha_alt = sprintf( __( 'Ảnh trước và sau điều trị %d', 'isha' ), $isha_no );
		}
		$isha_image = isha_home_image(
			$isha_id,
			array(
				'size'  => 'isha-strip',
				'sizes' => '(max-width: 900px) 92vw, 1100px',
				'class' => 'isha-results__img',
				'alt'   => $isha_alt,
			)
		);
		if ( '' === $isha_image ) {
			continue;
		}
		$isha_slides[] = array(
			'image'   => $isha_image,
			'caption' => $isha_text( $isha_row, 'caption' ),
		);
	}
}

if ( ! $isha_slides ) {
	return;
}
if ( '' === $isha_title ) {
	$isha_title = __( 'Hiệu quả liệu trình', 'isha' );
}

isha_home_section_open( 'results' );
?>
	<div class="isha-wrap">
		<header class="isha-sec__head">
			<h2 id="<?php echo esc_attr( isha_home_title_id( 'results' ) ); ?>" class="isha-sec__title"><?php echo esc_html( $isha_title ); ?></h2>
			<?php if ( '' !== $isha_lead ) : ?>
				<p class="isha-lead"><?php echo esc_html( $isha_lead ); ?></p>
			<?php endif; ?>
		</header>

		<div class="isha-results__carousel" data-isha-results>
			<ul class="isha-results__list" role="list">
				<?php foreach ( $isha_slides as $isha_slide ) : ?>
					<li class="isha-results__item">
						<figure class="isha-results__figure">
							<div class="isha-results__frame">
								<div class="isha-results__view">
									<?php echo $isha_slide['image']; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?>
								</div>
							</div>
							<?php if ( '' !== $isha_slide['caption'] ) : ?>
								<figcaption class="isha-note isha-results__caption"><?php echo esc_html( $isha_slide['caption'] ); ?></figcaption>
							<?php endif; ?>
						</figure>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			if ( count( $isha_slides ) > 1 ) {
				// Phân trang dùng chung (isha_home_pager); home.js bỏ hidden khi dựng băng chuyền.
				isha_home_pager(
					array(
						'count'     => count( $isha_slides ),
						'label'     => __( 'Điều khiển ảnh hiệu quả liệu trình', 'isha' ),
						'dot_label' => __( 'Kết quả %d', 'isha' ),
						'prev'      => __( 'Kết quả trước', 'isha' ),
						'next'      => __( 'Kết quả tiếp theo', 'isha' ),
						'attrs'     => 'data-isha-results-controls',
					)
				);
			}
			?>
		</div>

		<?php if ( '' !== $isha_note ) : ?>
			<p class="isha-note isha-results__note"><?php echo esc_html( $isha_note ); ?></p>
		<?php endif; ?>
	</div>
<?php
isha_home_section_close();
