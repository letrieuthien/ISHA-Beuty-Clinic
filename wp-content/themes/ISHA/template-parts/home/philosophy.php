<?php
/**
 * Section Triết lý của trang chủ điện ảnh: logo chữ ISHA lớn làm trang trí (aria-hidden, chữ mờ), rồi danh sách có thứ tự 4
 * mục: chữ cái lớn (Garamond accent, Gold), tên tiếng Anh (nhãn Montserrat), tên tiếng Việt (chữ nghiêng) và mô tả.
 *
 * Trạng thái TĨNH. Desktop: danh sách dọc, mỗi mục một hàng có đường kẻ mảnh. Chế độ gọn: hàng vuốt ngang `isha-row` 4 khung
 * (nút Trước/Sau và chỉ báo do home.js bật khi hàng tràn). Cảnh ghim "đầy nắng" từng chữ cái (Giai đoạn 4): toàn bộ nội dung nằm
 * trong `.isha-philosophy__track[data-isha-track] > .isha-philosophy__stage[data-isha-stage]`; hai khối này chỉ có kiểu khi home.js
 * thêm class `is-live` cho section (desktop, chuyển động bật), còn lại chúng là khối thường nên bố cục tĩnh không đổi.
 *
 * Dữ liệu: `$args` = isha_home_data( 'philosophy' ) (giá trị thô, template tự escape).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_phil = ( isset( $args ) && is_array( $args ) ) ? $args : array();

$isha_title = ( isset( $isha_phil['title'] ) && is_scalar( $isha_phil['title'] ) ) ? trim( (string) $isha_phil['title'] ) : '';
if ( '' === $isha_title ) {
	$isha_title = __( 'Triết lý ISHA', 'isha' );
}
$isha_lead  = ( isset( $isha_phil['lead'] ) && is_scalar( $isha_phil['lead'] ) ) ? trim( (string) $isha_phil['lead'] ) : '';
$isha_items = array();
if ( isset( $isha_phil['items'] ) && is_array( $isha_phil['items'] ) ) {
	foreach ( $isha_phil['items'] as $isha_raw ) {
		if ( ! is_array( $isha_raw ) ) {
			continue;
		}
		$isha_item = array();
		foreach ( array( 'letter', 'en', 'vi', 'desc' ) as $isha_key ) {
			$isha_item[ $isha_key ] = ( isset( $isha_raw[ $isha_key ] ) && is_scalar( $isha_raw[ $isha_key ] ) ) ? trim( (string) $isha_raw[ $isha_key ] ) : '';
		}
		if ( '' !== $isha_item['letter'] || '' !== $isha_item['en'] || '' !== $isha_item['vi'] || '' !== $isha_item['desc'] ) {
			$isha_items[] = $isha_item;
		}
	}
}

isha_home_section_open( 'philosophy' );
?>
<div class="isha-philosophy__track" data-isha-track>
<div class="isha-philosophy__stage" data-isha-stage>
<div class="isha-wrap">
	<div class="isha-sec__head">
		<h2 id="<?php echo esc_attr( isha_home_title_id( 'philosophy' ) ); ?>" class="isha-sec__title"><?php echo esc_html( $isha_title ); ?></h2>
		<?php if ( '' !== $isha_lead ) : ?>
			<p class="isha-lead"><?php echo esc_html( $isha_lead ); ?></p>
		<?php endif; ?>
	</div>

	<div class="isha-philosophy__mark" aria-hidden="true">
		<?php isha_home_wordmark( array( 'class' => 'isha-philosophy__wordmark' ) ); ?>
	</div>
</div>

<?php if ( $isha_items ) : ?>
	<div class="isha-row isha-philosophy__row" data-isha-row>
		<ol class="isha-row__track isha-philosophy__list" tabindex="0" aria-label="<?php echo esc_attr( $isha_title ); ?>">
			<?php foreach ( $isha_items as $isha_item ) : ?>
				<li class="isha-row__item isha-philosophy__item">
					<?php if ( '' !== $isha_item['letter'] ) : ?>
						<span class="isha-accent isha-philosophy__letter"><?php echo esc_html( $isha_item['letter'] ); ?></span>
					<?php endif; ?>
					<div class="isha-philosophy__names">
						<?php if ( '' !== $isha_item['en'] ) : ?>
							<h3 class="isha-label isha-philosophy__en" lang="en"><?php echo esc_html( $isha_item['en'] ); ?></h3>
						<?php endif; ?>
						<?php if ( '' !== $isha_item['vi'] ) : ?>
							<p class="isha-note isha-philosophy__vi"><?php echo esc_html( $isha_item['vi'] ); ?></p>
						<?php endif; ?>
					</div>
					<?php if ( '' !== $isha_item['desc'] ) : ?>
						<?php isha_home_p( $isha_item['desc'], 'isha-text isha-philosophy__desc' ); ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php
		isha_home_row_controls(
			array(
				'count' => count( $isha_items ),
				'label' => __( 'Điều khiển danh sách triết lý', 'isha' ),
			)
		);
		?>
	</div>
<?php endif; ?>
</div>
</div>
<?php
isha_home_section_close();
