<?php
/**
 * Section Testimonials ("Những câu chuyện") của trang chủ điện ảnh.
 *
 * Vòng tròn đầu thẻ: ảnh chân dung (ảnh đại diện của lời kể) nếu có, không thì chữ cái đầu của tên.
 * Một hàng thẻ tự trôi liên tục (marquee), dừng khi rê chuột; tham khảo UI/UX phần đánh giá của Aurel Med Spa, chỉ dùng dữ liệu
 * sẵn có (không sao, không điểm đánh giá, không ảnh). Mỗi thẻ: lời kể (Montserrat), chân thẻ gồm vòng tròn chữ cái đầu của tên (trang trí,
 * ẩn khỏi trình đọc màn hình), tên (Garamond) và liệu trình. home.js nhân bản danh sách (aria-hidden, inert) để trôi vòng liền mạch;
 * không JS hoặc giảm chuyển động thì là hàng vuốt ngang tĩnh.
 *
 * Dữ liệu: `$args` là isha_home_data( 'testimonials' ): title, items (id, name, quote, service). Giá trị thô, escape khi in.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_data = ( isset( $args ) && is_array( $args ) && isset( $args['items'] ) ) ? $args : isha_home_data( 'testimonials' );
$isha_list = ( isset( $isha_data['items'] ) && is_array( $isha_data['items'] ) ) ? $isha_data['items'] : array();

$isha_title = ( isset( $isha_data['title'] ) && is_scalar( $isha_data['title'] ) ) ? trim( (string) $isha_data['title'] ) : '';
if ( '' === $isha_title ) {
	$isha_title = __( 'Những câu chuyện', 'isha' );
}

// Chỉ giữ lời kể có nội dung. Lời kể là một câu liền: gộp xuống dòng, bỏ dấu ngoặc kép người nhập tự gõ ở hai đầu (template tự thêm).
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

isha_home_section_open( 'testimonials' );
?>
	<div class="isha-wrap">
		<header class="isha-sec__head isha-testimonials__head">
			<h2 class="isha-sec__title" id="<?php echo esc_attr( isha_home_title_id( 'testimonials' ) ); ?>"><?php echo esc_html( $isha_title ); ?></h2>
		</header>
	</div>

	<div class="isha-testimonials__marquee" data-isha-marquee>
		<div class="isha-testimonials__track">
			<ul class="isha-testimonials__list" role="list">
				<?php foreach ( $isha_items as $isha_item ) : ?>
					<li class="isha-testimonials__item">
						<figure class="isha-testimonials__card">
							<blockquote class="isha-testimonials__quote">
								<p><span aria-hidden="true">&ldquo;</span><?php echo esc_html( $isha_item['quote'] ); ?><span aria-hidden="true">&rdquo;</span></p>
							</blockquote>
							<?php if ( '' !== $isha_item['name'] || '' !== $isha_item['service'] ) : ?>
								<figcaption class="isha-testimonials__credit">
									<?php $isha_photo = $isha_item['photo'] > 0 ? wp_get_attachment_image( $isha_item['photo'], 'thumbnail', false, array( 'class' => 'isha-testimonials__photo', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '40px' ) ) : ''; ?>
									<?php if ( '' !== $isha_photo ) : ?>
										<span class="isha-testimonials__avatar" aria-hidden="true"><?php echo $isha_photo; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image tự escape ?></span>
									<?php elseif ( '' !== $isha_item['name'] ) : ?>
										<span class="isha-testimonials__avatar" aria-hidden="true"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( $isha_item['name'], 0, 1 ) : substr( $isha_item['name'], 0, 1 ) ); ?></span>
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
<?php
isha_home_section_close();
