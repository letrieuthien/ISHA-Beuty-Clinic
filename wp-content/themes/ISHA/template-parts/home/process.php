<?php
/**
 * Section Quy trình của trang chủ điện ảnh: một đường kẻ ngang mảnh có các mốc (năm bước); dưới mỗi mốc là số thứ tự, tên
 * bước và mô tả.
 *
 * Danh sách có thứ tự (<ol>) để trình đọc màn hình đọc đúng trình tự; đường kẻ và mốc do CSS vẽ ở đầu mỗi bước (viền trên
 * và chấm), nên ở chế độ gọn, khi danh sách là hàng vuốt ngang (isha-row), đường kẻ vẫn chạy liền ở phía trên. Số thứ tự
 * hiện ra bằng chữ số, phần "Bước" chỉ dành cho trình đọc màn hình. Mọi chữ ở mức tương phản đầy đủ (không dùng opacity thấp).
 *
 * Dữ liệu: `$args` là isha_home_data( 'process' ): title, items (name, desc).
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
$isha_data  = ( isset( $args ) && is_array( $args ) && $args ) ? $args : isha_home_data( 'process' );
$isha_title = $isha_text( $isha_data, 'title' );
$isha_steps = array();

if ( isset( $isha_data['items'] ) && is_array( $isha_data['items'] ) ) {
	foreach ( $isha_data['items'] as $isha_row ) {
		$isha_name = $isha_text( $isha_row, 'name' );
		$isha_desc = $isha_text( $isha_row, 'desc' );
		if ( '' === $isha_name && '' === $isha_desc ) {
			continue;
		}
		$isha_steps[] = array(
			'name' => $isha_name,
			'desc' => $isha_desc,
		);
	}
}

if ( ! $isha_steps ) {
	return;
}
if ( '' === $isha_title ) {
	$isha_title = __( 'Quy trình thăm khám da tại ISHA', 'isha' );
}

isha_home_section_open( 'process' );
?>
	<div class="isha-wrap">
		<header class="isha-sec__head">
			<h2 id="<?php echo esc_attr( isha_home_title_id( 'process' ) ); ?>" class="isha-sec__title"><?php echo esc_html( $isha_title ); ?></h2>
		</header>
	</div>

	<div class="isha-row isha-process__row" data-isha-row>
		<ol class="isha-row__track isha-process__steps" role="list" tabindex="0" aria-label="<?php echo esc_attr( $isha_title ); ?>">
			<?php foreach ( $isha_steps as $isha_index => $isha_step ) : ?>
				<?php
				/* translators: %d: số thứ tự của bước trong quy trình */
				$isha_step_label = sprintf( __( 'Bước %d', 'isha' ), $isha_index + 1 );
				?>
				<li class="isha-row__item isha-process__step">
					<p class="isha-process__num"><span class="isha-sr"><?php echo esc_html( $isha_step_label ); ?></span><span aria-hidden="true"><?php echo esc_html( (string) ( $isha_index + 1 ) ); ?></span></p>
					<?php if ( '' !== $isha_step['name'] ) : ?>
						<h3 class="isha-process__name"><?php echo esc_html( $isha_step['name'] ); ?></h3>
					<?php endif; ?>
					<div class="isha-process__desc">
						<?php isha_home_p( $isha_step['desc'], 'isha-text' ); ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php
		isha_home_row_controls(
			array(
				'count' => count( $isha_steps ),
				'label' => $isha_title,
			)
		);
		?>
	</div>
<?php
isha_home_section_close();
