<?php
/**
 * Section Câu chuyện của trang chủ điện ảnh: bố cục bất đối xứng kiểu tạp chí. Cột trái hẹp có tiêu đề section và năm thành
 * lập (Garamond accent); cột phải có đoạn chính cỡ lớn (Garamond) và đoạn phụ cỡ chữ thân.
 *
 * Trạng thái TĨNH: chữ hiện đủ, vệt sáng quét qua đoạn chính làm ở giai đoạn sau. Trường nào để trống thì bỏ phần đó.
 *
 * Dữ liệu: `$args` = isha_home_data( 'story' ) (giá trị thô, template tự escape).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_story = ( isset( $args ) && is_array( $args ) ) ? $args : array();

$isha_text = static function ( $key ) use ( $isha_story ) {
	return ( isset( $isha_story[ $key ] ) && is_scalar( $isha_story[ $key ] ) ) ? trim( (string) $isha_story[ $key ] ) : '';
};

$isha_title = $isha_text( 'title' );
if ( '' === $isha_title ) {
	$isha_title = __( 'Câu chuyện ISHA', 'isha' );
}
$isha_year      = $isha_text( 'year' );
$isha_main      = $isha_text( 'main' );
$isha_secondary = $isha_text( 'secondary' );

isha_home_section_open( 'story' );
?>
<div class="isha-wrap isha-story__grid">
	<div class="isha-story__aside">
		<h2 id="<?php echo esc_attr( isha_home_title_id( 'story' ) ); ?>" class="isha-sec__title isha-story__title"><?php echo esc_html( $isha_title ); ?></h2>
		<?php if ( '' !== $isha_year ) : ?>
			<p class="isha-accent isha-story__year"><?php echo esc_html( $isha_year ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( '' !== $isha_main || '' !== $isha_secondary ) : ?>
		<div class="isha-story__body">
			<?php isha_home_p( $isha_main, 'isha-story__main' ); ?>
			<?php if ( '' !== $isha_secondary ) : ?>
				<div class="isha-story__more">
					<?php isha_home_p( $isha_secondary, 'isha-text isha-story__secondary' ); ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
<?php
isha_home_section_close();
