<?php
/**
 * Markup trang chi tiết Tin tức và Sự kiện (single-isha_article.php, single-isha_event.php), gọi trong vòng lặp. Bao trong
 * `div.isha-home.isha-post` để thừa hưởng hệ thiết kế của trang chủ.
 *
 * - Mở đầu (tông day-warm): liên kết quay lại trang danh sách, nhãn chủ đề hoặc loại sự kiện (kèm nhãn Sắp diễn ra / Đã diễn ra), tiêu đề h1
 *   (mặt nạ trượt lên), tóm tắt (Garamond nghiêng), dòng thông tin (Tin tức: ngày đăng, tác giả, thời gian đọc; Sự kiện: ngày, giờ, địa
 *   điểm), rồi ảnh đại diện rộng trong khung viền vàng kim lệch (Tin tức 3:2, Sự kiện 2.39:1).
 * - Nội dung (tông day): bài viết (the_content) bên trái, thẻ thông tin dính bên phải (Sự kiện: thời gian, địa điểm, đăng ký; Tin tức: mời
 *   đặt lịch tư vấn).
 * - Liên quan (tông day-warm): ba thẻ (Tin tức cùng chủ đề; Sự kiện sắp diễn ra), ảnh dọc 4:5.
 *
 * Dữ liệu: `$args` = isha_post_single_data( WP_Post ).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_d = isset( $args ) && is_array( $args ) ? $args : array();
if ( ! $isha_d ) {
	return;
}
$isha_event = 'isha_event' === $isha_d['type'];
$isha_e     = $isha_d['event'];
$isha_lock  = post_password_required();

$isha_cover = $isha_d['image'] > 0 ? isha_home_image(
	$isha_d['image'],
	array(
		'size'          => $isha_event ? 'isha-cinema' : 'isha-hero',
		'sizes'         => '(max-width: 1200px) 100vw, 1200px',
		'class'         => 'isha-post__img',
		'fetchpriority' => 'high',
		'decorative'    => true,
	)
) : '';

// Nút đăng ký của sự kiện: link đăng ký ngoài website mở thẻ mới.
$isha_register_ext = false;
if ( $isha_event && '' !== $isha_e['register_url'] ) {
	$isha_host         = wp_parse_url( $isha_e['register_url'], PHP_URL_HOST );
	$isha_register_ext = is_string( $isha_host ) && wp_parse_url( home_url(), PHP_URL_HOST ) !== $isha_host;
}
?>
<div class="isha-home isha-post isha-post--<?php echo $isha_event ? 'event' : 'article'; ?>">
	<section class="isha-sec isha-post__open" data-tone="day-warm" aria-labelledby="isha-post-title">
		<div class="isha-wrap isha-post__head">
			<?php if ( '' !== $isha_d['list']['url'] ) : ?>
				<a class="isha-post__back" href="<?php echo esc_url( $isha_d['list']['url'] ); ?>"><?php echo esc_html( $isha_d['list']['label'] ); ?></a>
			<?php endif; ?>

			<p class="isha-post__kicker">
				<?php if ( '' !== $isha_d['term']['name'] ) : ?>
					<span class="isha-label"><?php echo esc_html( $isha_d['term']['name'] ); ?></span>
				<?php endif; ?>
				<?php if ( $isha_event && $isha_e['start_ts'] > 0 ) : ?>
					<span class="isha-post__status<?php echo $isha_e['past'] ? ' is-past' : ''; ?>"><?php echo esc_html( $isha_e['past'] ? __( 'Đã diễn ra', 'isha' ) : __( 'Sắp diễn ra', 'isha' ) ); ?></span>
				<?php endif; ?>
			</p>

			<h1 id="isha-post-title" class="isha-post__title"><span class="isha-post__mask"><span class="isha-post__name"><?php echo esc_html( $isha_d['title'] ); ?></span></span></h1>

			<?php if ( '' !== $isha_d['summary'] ) : ?>
				<p class="isha-post__lead"><?php echo esc_html( $isha_d['summary'] ); ?></p>
			<?php endif; ?>

			<ul class="isha-post__meta">
				<?php if ( $isha_event ) : ?>
					<?php if ( '' !== $isha_e['date'] ) : ?>
						<li><time datetime="<?php echo esc_attr( wp_date( 'c', $isha_e['start_ts'] ) ); ?>"><?php echo esc_html( $isha_e['date'] ); ?></time></li>
					<?php endif; ?>
					<?php if ( '' !== $isha_e['time'] ) : ?>
						<li><?php echo esc_html( $isha_e['time'] ); ?></li>
					<?php endif; ?>
					<?php if ( '' !== $isha_e['location'] ) : ?>
						<li><?php echo esc_html( $isha_e['location'] ); ?></li>
					<?php endif; ?>
				<?php else : ?>
					<li><time datetime="<?php echo esc_attr( $isha_d['date_iso'] ); ?>"><?php echo esc_html( $isha_d['date'] ); ?></time></li>
					<?php if ( '' !== $isha_d['author'] ) : ?>
						<li><?php echo esc_html( $isha_d['author'] ); ?></li>
					<?php endif; ?>
					<?php /* translators: %d: số phút đọc */ ?>
					<li><?php echo esc_html( sprintf( __( '%d phút đọc', 'isha' ), $isha_d['read_min'] ) ); ?></li>
				<?php endif; ?>
			</ul>
		</div>

		<?php if ( '' !== $isha_cover ) : ?>
			<div class="isha-wrap">
				<figure class="isha-post__cover">
					<div class="isha-post__frame">
						<?php echo $isha_cover; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?>
					</div>
				</figure>
			</div>
		<?php endif; ?>
	</section>

	<section class="isha-sec isha-post__body" data-tone="day" aria-label="<?php echo esc_attr( $isha_event ? __( 'Chi tiết sự kiện', 'isha' ) : __( 'Nội dung bài viết', 'isha' ) ); ?>">
		<div class="isha-wrap isha-post__layout">
			<div class="isha-post__content">
				<?php
				if ( $isha_lock ) {
					echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput -- biểu mẫu mật khẩu của WordPress.
				} else {
					the_content();
				}
				?>
			</div>

			<aside class="isha-post__card" aria-labelledby="isha-post-card-title">
				<?php if ( $isha_event ) : ?>
					<h2 id="isha-post-card-title" class="isha-post__card-title"><?php esc_html_e( 'Thông tin sự kiện', 'isha' ); ?></h2>
					<dl class="isha-post__facts">
						<?php if ( '' !== $isha_e['date'] ) : ?>
							<div><dt><?php esc_html_e( 'Thời gian', 'isha' ); ?></dt><dd><?php echo esc_html( $isha_e['date'] ); ?><?php echo '' !== $isha_e['time'] ? '<br>' . esc_html( $isha_e['time'] ) : ''; ?></dd></div>
						<?php endif; ?>
						<?php if ( '' !== $isha_e['location'] ) : ?>
							<div><dt><?php esc_html_e( 'Địa điểm', 'isha' ); ?></dt><dd><?php echo esc_html( $isha_e['location'] ); ?></dd></div>
						<?php endif; ?>
						<?php if ( '' !== $isha_d['term']['name'] ) : ?>
							<div><dt><?php esc_html_e( 'Hình thức', 'isha' ); ?></dt><dd><?php echo esc_html( $isha_d['term']['name'] ); ?></dd></div>
						<?php endif; ?>
					</dl>
					<div class="isha-post__actions">
						<?php if ( $isha_e['past'] ) : ?>
							<p class="isha-post__note"><?php esc_html_e( 'Sự kiện đã diễn ra. Mời bạn xem các sự kiện sắp tới của ISHA.', 'isha' ); ?></p>
							<?php if ( '' !== $isha_d['list']['url'] ) : ?>
								<a class="isha-btn isha-btn--ghost" href="<?php echo esc_url( $isha_d['list']['url'] ); ?>"><?php esc_html_e( 'Sự kiện sắp tới', 'isha' ); ?></a>
							<?php endif; ?>
						<?php else : ?>
							<?php if ( '' !== $isha_e['register_url'] ) : ?>
								<a class="isha-btn isha-btn--primary" href="<?php echo esc_url( $isha_e['register_url'] ); ?>"<?php echo $isha_register_ext ? ' target="_blank" rel="noopener"' : ''; ?>><?php esc_html_e( 'Đăng ký tham dự', 'isha' ); ?><?php if ( $isha_register_ext ) : ?><span class="isha-sr"> <?php esc_html_e( '(mở thẻ mới)', 'isha' ); ?></span><?php endif; ?></a>
							<?php elseif ( '' !== $isha_d['tel_url'] ) : ?>
								<a class="isha-btn isha-btn--primary" href="<?php echo esc_url( $isha_d['tel_url'] ); ?>"><?php esc_html_e( 'Gọi đăng ký', 'isha' ); ?></a>
							<?php endif; ?>
							<?php if ( '' !== $isha_d['booking'] ) : ?>
								<a class="isha-btn isha-btn--ghost" href="<?php echo esc_url( $isha_d['booking'] ); ?>"><?php esc_html_e( 'Đặt lịch tư vấn riêng', 'isha' ); ?></a>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<h2 id="isha-post-card-title" class="isha-post__card-title"><?php esc_html_e( 'Cần bác sĩ tư vấn riêng?', 'isha' ); ?></h2>
					<p class="isha-post__note"><?php esc_html_e( 'Mỗi làn da có câu chuyện riêng. Bác sĩ ISHA sẽ soi da, lắng nghe và xây dựng phác đồ phù hợp với bạn.', 'isha' ); ?></p>
					<div class="isha-post__actions">
						<?php if ( '' !== $isha_d['booking'] ) : ?>
							<a class="isha-btn isha-btn--primary" href="<?php echo esc_url( $isha_d['booking'] ); ?>"><?php esc_html_e( 'Đặt lịch tư vấn', 'isha' ); ?></a>
						<?php endif; ?>
						<?php if ( '' !== $isha_d['tel_url'] ) : ?>
							<a class="isha-btn isha-btn--ghost" href="<?php echo esc_url( $isha_d['tel_url'] ); ?>"><?php echo esc_html( sprintf( /* translators: %s: số hotline */ __( 'Gọi %s', 'isha' ), $isha_d['phone'] ) ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</aside>
		</div>
	</section>

	<?php if ( $isha_d['related'] ) : ?>
		<section class="isha-sec isha-post__related" data-tone="day-warm" aria-labelledby="isha-post-related-title">
			<div class="isha-wrap">
				<div class="isha-post__related-head">
					<h2 id="isha-post-related-title" class="isha-sec__title"><?php echo esc_html( $isha_event ? __( 'Sự kiện khác', 'isha' ) : __( 'Bài viết liên quan', 'isha' ) ); ?></h2>
					<?php if ( '' !== $isha_d['list']['url'] ) : ?>
						<a class="isha-link" href="<?php echo esc_url( $isha_d['list']['url'] ); ?>"><?php echo esc_html( $isha_event ? __( 'Xem tất cả sự kiện', 'isha' ) : __( 'Xem tất cả tin tức', 'isha' ) ); ?></a>
					<?php endif; ?>
				</div>
				<ul class="isha-post__cards">
					<?php foreach ( $isha_d['related'] as $isha_card ) : ?>
						<li class="isha-post__card-item">
							<article class="isha-post__tile">
								<div class="isha-post__tile-media isha-r-45">
									<?php
									isha_home_media(
										$isha_card['image'],
										__( 'Ảnh bài viết', 'isha' ),
										array(
											'size'       => 'isha-frame',
											'sizes'      => '(max-width: 900px) 72vw, 380px',
											'class'      => 'isha-post__tile-img',
											'decorative' => true,
											'ratio'      => '45',
										)
									);
									?>
								</div>
								<?php if ( '' !== $isha_card['label'] ) : ?>
									<p class="isha-label isha-post__tile-label"><?php echo esc_html( $isha_card['label'] ); ?></p>
								<?php endif; ?>
								<h3 class="isha-post__tile-title"><a href="<?php echo esc_url( $isha_card['url'] ); ?>"><?php echo esc_html( $isha_card['title'] ); ?></a></h3>
								<?php if ( '' !== $isha_card['meta'] ) : ?>
									<p class="isha-post__tile-meta"><?php echo esc_html( $isha_card['meta'] ); ?></p>
								<?php endif; ?>
							</article>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>
</div>
