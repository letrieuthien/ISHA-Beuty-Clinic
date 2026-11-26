<?php
/**
 * Section Testimonials ("Những câu chuyện") của trang chủ: Quiet Luxury / Editorial (20/11/2026, yêu cầu gốc docs/testimonial.md và
 * docs/testimonial-mobile.md).
 *
 * Nền sáng day-warm. Đầu mục: dòng nhỏ (eyebrow, CMS) trên tiêu đề. Trên 5 lời kể thì chia hai dãy: dãy 1 nhận ceil(n/2) lời kể đầu, dãy 2
 * phần còn lại; từ 5 trở xuống một dãy. Thẻ: dấu ngoặc kép trang trí, lời kể (Garamond), đường kẻ mảnh, chân thẻ (ảnh chân dung nếu có,
 * tên, liệu trình). Lời kể không có ảnh chân dung thì KHÔNG dựng vòng tròn (từ 20/11/2026 bỏ avatar chữ cái đầu); chân thẻ giữ chiều
 * cao tối thiểu bằng chân thẻ có ảnh (CSS) nên các thẻ vẫn thẳng hàng.
 *
 * Ba chế độ (khối testimonials của home.css và home.js):
 * - Máy tính có JS: các dãy trôi liên tục cùng tốc độ px/giây, dãy 2 ngược chiều; home.js nhân bản danh sách của từng dãy (aria-hidden,
 *   data-marquee-clone). Rê chuột vào một thẻ, chạm giữ hoặc focus bàn phím vào vùng thẻ thì cả hai dãy dừng.
 * - Chế độ gọn (max-width 900px hoặc max-height 560px): lớp bọc dãy là display: contents nên mọi lời kể gốc nằm chung MỘT hàng vuốt
 *   ngang, không bản sao; thanh tiến trình và bộ đếm chỉ hiện khi có JS.
 * - Máy tính không JS, lõi lỗi hoặc giảm chuyển động: mỗi dãy là một hàng cuộn ngang tĩnh.
 *
 * Dữ liệu: `$args` là isha_home_data( 'testimonials' ): eyebrow, title, items (id, name, quote, service, photo). Giá trị thô, escape khi in.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_data = ( isset( $args ) && is_array( $args ) && isset( $args['items'] ) ) ? $args : isha_home_data( 'testimonials' );
$isha_list = ( isset( $isha_data['items'] ) && is_array( $isha_data['items'] ) ) ? $isha_data['items'] : array();

$isha_eyebrow = ( isset( $isha_data['eyebrow'] ) && is_scalar( $isha_data['eyebrow'] ) ) ? trim( (string) $isha_data['eyebrow'] ) : '';
$isha_title   = ( isset( $isha_data['title'] ) && is_scalar( $isha_data['title'] ) ) ? trim( (string) $isha_data['title'] ) : '';
if ( '' === $isha_title ) {
	$isha_title = __( 'Những câu chuyện', 'isha' );
}

// Chỉ giữ lời kể có nội dung. Lời kể là một câu liền: gộp xuống dòng, bỏ dấu ngoặc kép người nhập tự gõ ở hai đầu (thẻ đã có dấu trang trí).
$isha_items = array();
foreach ( $isha_list as $isha_raw ) {
	if ( ! is_array( $isha_raw ) ) {
		continue;
	}
	$isha_quote = ( isset( $isha_raw['quote'] ) && is_scalar( $isha_raw['quote'] ) ) ? (string) $isha_raw['quote'] : '';
	$isha_quote = trim( (string) preg_replace( '/\s+/u', ' ', $isha_quote ) );
	$isha_quote = trim( (string) preg_replace( '/^["\x{201C}\x{201D}\x{201E}\x{201F}\x{00AB}\x{00BB}\s]+|["\x{201C}\x{201D}\x{201E}\x{201F}\x{00AB}\x{00BB}\s]+$/u', '', $isha_quote ) );
	if ( '' === $isha_quote ) {
		continue;
	}

	$isha_items[] = array(
		'quote'   => $isha_quote,
		'name'    => ( isset( $isha_raw['name'] ) && is_scalar( $isha_raw['name'] ) ) ? trim( (string) $isha_raw['name'] ) : '',
		'service' => ( isset( $isha_raw['service'] ) && is_scalar( $isha_raw['service'] ) ) ? trim( (string) $isha_raw['service'] ) : '',
		'photo'   => isset( $isha_raw['photo'] ) ? (int) $isha_raw['photo'] : 0,
	);
}

if ( ! $isha_items ) {
	return;
}

$isha_total = count( $isha_items );
$isha_mid   = (int) ceil( $isha_total / 2 );
$isha_rows  = $isha_total > 5 ? array( array_slice( $isha_items, 0, $isha_mid ), array_slice( $isha_items, $isha_mid ) ) : array( $isha_items );

isha_home_section_open( 'testimonials' );
?>
	<div class="isha-wrap">
		<header class="isha-sec__head isha-testimonials__head">
			<?php if ( '' !== $isha_eyebrow ) : ?>
				<p class="isha-label isha-testimonials__eyebrow"><?php echo esc_html( $isha_eyebrow ); ?></p>
			<?php endif; ?>
			<h2 class="isha-sec__title" id="<?php echo esc_attr( isha_home_title_id( 'testimonials' ) ); ?>"><?php echo esc_html( $isha_title ); ?></h2>
			<p class="isha-testimonials__note"><?php esc_html_e( 'Kết quả mỗi người một khác', 'isha' ); ?></p>
		</header>
	</div>

	<div class="isha-testimonials__stage" data-isha-tm role="group" tabindex="0" aria-label="<?php echo esc_attr__( 'Lời kể khách hàng', 'isha' ); ?>">
		<?php foreach ( $isha_rows as $isha_k => $isha_row ) : ?>
			<div class="isha-testimonials__row<?php echo 1 === $isha_k ? ' isha-testimonials__row--rev' : ''; ?>" data-isha-tm-row>
				<div class="isha-testimonials__track">
					<ul class="isha-testimonials__list" role="list">
						<?php foreach ( $isha_row as $isha_item ) : ?>
							<li class="isha-testimonials__item">
								<figure class="isha-testimonials__card">
									<span class="isha-testimonials__mark" aria-hidden="true">&ldquo;</span>
									<blockquote class="isha-testimonials__quote">
										<p><?php echo esc_html( $isha_item['quote'] ); ?></p>
									</blockquote>
									<?php if ( '' !== $isha_item['name'] || '' !== $isha_item['service'] ) : ?>
										<figcaption class="isha-testimonials__credit">
											<?php $isha_photo = $isha_item['photo'] > 0 ? wp_get_attachment_image( $isha_item['photo'], 'thumbnail', false, array( 'class' => 'isha-testimonials__photo', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '40px' ) ) : ''; ?>
											<?php if ( '' !== $isha_photo ) : ?>
												<span class="isha-testimonials__avatar" aria-hidden="true"><?php echo $isha_photo; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image tự escape ?></span>
											<?php endif; ?>
											<span class="isha-testimonials__who">
												<?php if ( '' !== $isha_item['name'] ) : ?>
													<span class="isha-testimonials__name"><?php echo esc_html( $isha_item['name'] ); ?></span>
												<?php endif; ?>
												<?php if ( '' !== $isha_item['service'] ) : ?>
													<span class="isha-testimonials__service"><?php echo esc_html( $isha_item['service'] ); ?></span>
												<?php endif; ?>
											</span>
										</figcaption>
									<?php endif; ?>
								</figure>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( $isha_total > 1 ) : ?>
		<div class="isha-testimonials__meter" data-isha-tm-meter aria-hidden="true">
			<span class="isha-testimonials__bar"><span class="isha-testimonials__fill"></span></span>
			<span class="isha-label isha-testimonials__count"><span data-isha-tm-now>01</span> / <?php echo esc_html( sprintf( '%02d', $isha_total ) ); ?></span>
		</div>
	<?php endif; ?>
<?php
isha_home_section_close();
