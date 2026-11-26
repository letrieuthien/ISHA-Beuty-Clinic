<?php
/**
 * Section Không gian của trang chủ điện ảnh: "khung cửa sổ" ảnh tràn màn hình (edge-to-edge). Ảnh chuyển bằng cross-fade, ảnh đang hiện
 * thu nhỏ rất chậm (Ken Burns), và có parallax theo cuộn trang.
 *
 * Desktop có chuyển động (21/11/2026): section CHỒNG lên màn cuối của hero; hết cảnh hero mà cuộn tiếp thì rèm lá dọc mở ra để lộ khung ảnh,
 * cuộn ngược lên thì rèm đóng, trả lại hero (CSS: khối space "Chồng lên hero"; JS: home.js khối space, curtain()). Chế độ gọn, giảm chuyển
 * động, không JS: section đứng sau hero như thường.
 *
 * Mọi thành phần UI nằm lơ lửng trên ảnh, không có hộp nền: tiêu đề Garamond cỡ lớn, vạch vàng ánh kim, câu dẫn Montserrat nét mảnh
 * ở góc trên trái (thẳng hàng với .isha-wrap) trên lớp tối dần từ mép trái của ảnh; số thứ tự 01 — 05, tên phòng và vạch chỉ mục ở góc dưới trái; hai mũi tên ánh kim ở sát mép trái và phải;
 * dòng gợi ý "Cuộn xuống để xem thêm" ở góc dưới phải, trên nút Lên đầu trang (chỉ desktop có chuột, xem home.css). Ảnh KHÔNG là liên kết; nút primary tới trang Không gian ISHA (khi trang đó có) nằm dưới câu dẫn.
 *
 * Chế độ gọn (điện thoại): khối chữ (tiêu đề, vạch, câu dẫn) tách khỏi ảnh và nằm phía trên; ảnh tràn mép, không có gì đè lên ngoài tên phòng,
 * số thứ tự, không parallax để thấy trọn ảnh; nút primary nằm ngay dưới ảnh (CSS: display: contents cho .isha-space__head và order).
 *
 * Không JS hoặc lõi chuyển động lỗi: các ảnh nằm trong một hàng cuộn ngang có điểm bám (vuốt, cuộn bàn phím), không có số thứ tự.
 * Có JS (home.js khối space, hàm carousel() dùng chung): .is-carousel xếp ảnh chồng lên nhau, bật điều hướng và số thứ tự, tự chạy, vuốt/kéo.
 *
 * Dữ liệu: `$args` là isha_home_data( 'space' ): title, lead, link_label, all_url, images (id, caption, alt, url). `url` không còn dùng ở đây.
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
$isha_total = count( $isha_photos );

// Liên kết "Cuộn xuống": id section kế tiếp theo registry. Section đó có thể ẩn (thiếu dữ liệu, không in ra); home.js trỏ lại tới section hiện kế tiếp thật.
$isha_ids  = array_keys( isha_home_sections() );
$isha_at   = array_search( 'space', $isha_ids, true );
$isha_next = ( false !== $isha_at && isset( $isha_ids[ $isha_at + 1 ] ) ) ? sanitize_key( (string) $isha_ids[ $isha_at + 1 ] ) : '';
?>
	<div class="isha-space__stage" data-isha-space>
		<header class="isha-space__head">
			<h2 id="<?php echo esc_attr( isha_home_title_id( 'space' ) ); ?>" class="isha-sec__title isha-space__title"><?php echo esc_html( $isha_title ); ?></h2>
			<span class="isha-space__rule" aria-hidden="true"></span>
			<?php if ( '' !== $isha_lead ) : ?>
				<p class="isha-space__lead"><?php echo esc_html( $isha_lead ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $isha_all && '' !== $isha_all_lb ) : ?>
				<p class="isha-space__cta"><?php isha_home_button( $isha_all_lb, $isha_all, 'primary' ); ?></p>
			<?php endif; ?>
		</header>

		<div class="isha-space__view">
		<ul class="isha-space__list" role="list" tabindex="0" aria-label="<?php echo esc_attr( $isha_title ); ?>">
			<?php foreach ( $isha_photos as $isha_index => $isha_photo ) : ?>
				<?php
				$isha_image = isha_home_image(
					$isha_photo['id'],
					array(
						'size'       => 'isha-strip',
						'sizes'      => '100vw',
						'class'      => 'isha-space__img',
						'alt'        => $isha_photo['alt'],
						'decorative' => '' === $isha_photo['alt'],
					)
				);
				?>
				<li class="isha-space__item<?php echo 0 === $isha_index ? ' is-active' : ''; ?>">
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
							<figcaption class="isha-space__caption"><?php echo esc_html( $isha_photo['caption'] ); ?></figcaption>
						<?php endif; ?>
					</figure>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( $isha_total > 1 ) : ?>
			<p class="isha-space__count" aria-hidden="true">
				<span class="isha-space__now" data-isha-space-now>01</span>
				<span class="isha-space__dash"></span>
				<span class="isha-space__total"><?php echo esc_html( sprintf( '%02d', $isha_total ) ); ?></span>
			</p>
		<?php endif; ?>

		<?php
		isha_home_pager(
			array(
				'count' => $isha_total,
				'label' => $isha_title,
				'class' => 'isha-space__pager',
				'attrs' => 'data-isha-space-controls',
			)
		);
		?>
		</div>
		<?php if ( '' !== $isha_next ) : ?>
			<a class="isha-space__hint" href="#<?php echo esc_attr( $isha_next ); ?>"><?php esc_html_e( 'Cuộn xuống để xem thêm', 'isha' ); ?> <span aria-hidden="true">↓</span></a>
		<?php endif; ?>
	</div>
<?php
isha_home_section_close();
