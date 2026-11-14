<?php
/**
 * Section Giá trị cốt lõi của trang chủ điện ảnh: dải phim gồm các khung dọc, mỗi khung có tên giá trị, mô tả và ảnh nền tuỳ chọn.
 *
 * Trạng thái tĩnh (giai đoạn 2): mô tả của mọi khung luôn có trong DOM và luôn hiện, nên cả năm nút `aria-expanded` đều là
 * "true" (nội dung đang mở). Chọn khung bằng rê chuột, focus hoặc chạm là việc của giai đoạn 5: lúc đó home.js đặt lại
 * `aria-expanded` và class `is-active` theo khung được chọn. Khung đầu mang sẵn `is-active` (đường kẻ Gold ở đỉnh khung).
 * Nút kích hoạt là `button.isha-values__trigger` nằm trong tiêu đề h3 của khung (không đặt thẻ <p> trong <button>), mô tả là
 * phần tử `aria-controls` của nút. Ở chế độ gọn dải này là hàng vuốt ngang (isha-row), mỗi khung hiện đủ tên và mô tả.
 *
 * Dữ liệu: `$args` là isha_home_data( 'values' ): title, items (name, desc, image).
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
$isha_data  = ( isset( $args ) && is_array( $args ) && $args ) ? $args : isha_home_data( 'values' );
$isha_title = $isha_text( $isha_data, 'title' );
$isha_items = array();

if ( isset( $isha_data['items'] ) && is_array( $isha_data['items'] ) ) {
	foreach ( $isha_data['items'] as $isha_row ) {
		$isha_name = $isha_text( $isha_row, 'name' );
		$isha_desc = $isha_text( $isha_row, 'desc' );
		if ( '' === $isha_name && '' === $isha_desc ) {
			continue;
		}
		$isha_items[] = array(
			'name'  => $isha_name,
			'desc'  => $isha_desc,
			'image' => ( isset( $isha_row['image'] ) && is_numeric( $isha_row['image'] ) ) ? (int) $isha_row['image'] : 0,
		);
	}
}

if ( ! $isha_items ) {
	return;
}
if ( '' === $isha_title ) {
	$isha_title = __( 'Giá trị cốt lõi', 'isha' );
}

isha_home_section_open( 'values' );
?>
	<div class="isha-wrap">
		<header class="isha-sec__head">
			<h2 id="<?php echo esc_attr( isha_home_title_id( 'values' ) ); ?>" class="isha-sec__title"><?php echo esc_html( $isha_title ); ?></h2>
		</header>
	</div>

	<div class="isha-row isha-values__row" data-isha-row>
		<ul class="isha-row__track isha-values__strip" role="list" tabindex="0" aria-label="<?php echo esc_attr( $isha_title ); ?>">
			<?php foreach ( $isha_items as $isha_index => $isha_item ) : ?>
				<?php
				$isha_desc_id = 'isha-values-desc-' . ( $isha_index + 1 );
				// Ảnh nền là ảnh trang trí (alt rỗng), chỉ in khi đã chọn ảnh; không có ảnh thì khung chỉ có chữ, không in khối giữ chỗ.
				$isha_image = isha_home_image(
					$isha_item['image'],
					array(
						'sizes'      => '(max-width: 900px) 340px, 240px',
						'class'      => 'isha-values__img',
						'decorative' => true,
					)
				);
				?>
				<li class="isha-row__item isha-values__frame<?php echo 0 === $isha_index ? ' is-active' : ''; ?>">
					<?php if ( '' !== $isha_image ) : ?>
						<div class="isha-values__media" aria-hidden="true"><?php echo $isha_image; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?></div>
					<?php endif; ?>
					<?php if ( '' !== $isha_item['name'] ) : ?>
						<h3 class="isha-values__name"><button type="button" class="isha-values__trigger" aria-expanded="true" aria-disabled="true" tabindex="-1" aria-controls="<?php echo esc_attr( $isha_desc_id ); ?>"><?php echo esc_html( $isha_item['name'] ); ?></button></h3>
					<?php endif; ?>
					<div class="isha-values__desc" id="<?php echo esc_attr( $isha_desc_id ); ?>">
						<?php isha_home_p( $isha_item['desc'], 'isha-text' ); ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		isha_home_row_controls(
			array(
				'count' => count( $isha_items ),
				'label' => $isha_title,
			)
		);
		?>
	</div>
<?php
isha_home_section_close();
