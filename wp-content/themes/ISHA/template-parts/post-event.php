<?php
/**
 * Markup trang chi tiết Sự kiện (single-isha_event.php), nâng cấp 25/11/2026 (docs/events.md): cùng ngôn ngữ với trang chi tiết Tin tức
 * (template-parts/post-article.php): nền night liền header, ảnh bìa 16:10, thẻ thông tin tối. Gọi trong vòng lặp; bao trong
 * `div.isha-home.isha-art.isha-art--event` nên dùng lại giao diện của post-article.css, phần riêng của sự kiện ở post-event.css.
 *
 * Trang CHỈ HIỂN THỊ THÔNG TIN, không có nút đăng ký hay giữ chỗ (quyết định của người dùng 25/11/2026). Liên hệ nhanh do dock của trang chủ
 * (chế độ gọn: thanh đáy màn hình) đảm nhiệm.
 *
 * - Mở đầu (tông night, liền header): đường dẫn Sự kiện / loại, nhãn trạng thái (sắp diễn ra, đang diễn ra, đã kết thúc), tiêu đề h1, tóm tắt,
 *   dải thông số nhanh (chỉ hiện ô có dữ liệu: thời gian, địa điểm, hình thức hoặc trạng thái); ảnh bìa 16:10 lệch phải trong khung viền vàng
 *   (bấm mở hộp xem lớn ảnh nguyên khung; điện thoại: ảnh dưới chữ).
 * - Nội dung (tông paper): the_content() bên trái (trình soạn thảo mặc định HOẶC Elementor), thẻ thông tin tối dính bên phải: thời gian (kèm
 *   "Thêm vào lịch" khi chưa kết thúc), địa điểm (kèm "Chỉ đường" CHỈ khi có địa điểm), hình thức, hotline và nút sao chép liên kết.
 * - Sự kiện khác (tông day-warm): ba thẻ ảnh 16:10 kèm nhãn trạng thái, điện thoại vuốt ngang.
 *
 * Không có dữ liệu thì không in (không địa điểm: không có ô địa điểm, không có Chỉ đường, không có chỗ trống). Dữ liệu: `$args` =
 * isha_post_single_data( WP_Post ).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_d = isset( $args ) && is_array( $args ) ? $args : array();
if ( ! $isha_d || empty( $isha_d['event'] ) || ! is_array( $isha_d['event'] ) ) {
	return;
}

$isha_id      = (int) $isha_d['id'];
$isha_e       = $isha_d['event'];
$isha_lock    = post_password_required();
$isha_builder = ! empty( $isha_d['builder'] );
$isha_book    = (string) $isha_d['booking'];
$isha_tel     = (string) $isha_d['tel_url'];
$isha_phone   = (string) $isha_d['phone'];
$isha_type    = (string) $isha_d['term']['name'];
$isha_state   = (string) $isha_e['state'];
$isha_past    = 'past' === $isha_state;
$isha_full    = isset( $isha_d['image_full'] ) ? (string) $isha_d['image_full'] : '';

// Nhãn trạng thái: ở đầu trang và thẻ thông tin ("Đã kết thúc"), trên ảnh thẻ Sự kiện khác ("Đã diễn ra").
$isha_status_labels = array(
	'upcoming' => __( 'Sắp diễn ra', 'isha' ),
	'ongoing'  => __( 'Đang diễn ra', 'isha' ),
	'past'     => __( 'Đã kết thúc', 'isha' ),
);
$isha_tag_labels    = array(
	'upcoming' => __( 'Sắp diễn ra', 'isha' ),
	'ongoing'  => __( 'Đang diễn ra', 'isha' ),
	'past'     => __( 'Đã diễn ra', 'isha' ),
);
$isha_status        = isset( $isha_status_labels[ $isha_state ] ) ? $isha_status_labels[ $isha_state ] : '';

// Dải thông số nhanh: chỉ ô có dữ liệu. Ô cuối là hình thức (loại sự kiện) hoặc, nếu chưa có loại, trạng thái.
$isha_has_when  = $isha_e['start_ts'] > 0 && '' !== $isha_e['date'];
$isha_has_place = '' !== $isha_e['location'];
$isha_kind      = '' !== $isha_type ? $isha_type : $isha_status;
$isha_kind_lbl  = '' !== $isha_type ? __( 'Hình thức', 'isha' ) : __( 'Trạng thái', 'isha' );
$isha_cells     = (int) $isha_has_when + (int) $isha_has_place + ( '' !== $isha_kind ? 1 : 0 );

// Thẻ thông tin: tiêu đề theo trạng thái (không có ngày thì dùng tên loại nếu có).
$isha_card_titles = array(
	'upcoming' => __( 'Sự kiện sắp diễn ra', 'isha' ),
	'ongoing'  => __( 'Sự kiện đang diễn ra', 'isha' ),
	'past'     => __( 'Sự kiện đã khép lại', 'isha' ),
);
$isha_card_title  = isset( $isha_card_titles[ $isha_state ] ) ? $isha_card_titles[ $isha_state ] : $isha_type;

// Tên rất dài (trên 70 ký tự): hạ cỡ chữ để tiêu đề không chiếm quá nhiều dòng.
$isha_title_class = mb_strlen( (string) $isha_d['title'] ) > 70 ? ' isha-art__title--long' : '';
?>
<div class="isha-home isha-art isha-art--event<?php echo $isha_builder ? ' isha-art--builder' : ''; ?>" data-isha-art>
	<article id="post-<?php echo esc_attr( (string) $isha_id ); ?>" class="isha-art__article">

		<section id="hero" class="isha-sec isha-art__open" data-tone="night" aria-labelledby="isha-art-title">
			<div class="isha-wrap isha-art__open-grid">
				<div class="isha-art__intro">
					<?php if ( '' !== $isha_d['list']['url'] || '' !== $isha_type || '' !== $isha_status ) : ?>
						<div class="isha-evt__top">
							<?php if ( '' !== $isha_d['list']['url'] || '' !== $isha_type ) : ?>
								<nav class="isha-art__crumbs" aria-label="<?php esc_attr_e( 'Đường dẫn', 'isha' ); ?>">
									<ol>
										<?php if ( '' !== $isha_d['list']['url'] ) : ?>
											<li><a href="<?php echo esc_url( $isha_d['list']['url'] ); ?>"><?php echo esc_html( $isha_d['list']['label'] ); ?></a></li>
										<?php endif; ?>
										<?php if ( '' !== $isha_type ) : ?>
											<li><?php echo esc_html( $isha_type ); ?></li>
										<?php endif; ?>
									</ol>
								</nav>
							<?php endif; ?>
							<?php if ( '' !== $isha_status ) : ?>
								<p class="isha-evt__status isha-evt__status--<?php echo esc_attr( $isha_state ); ?>">
									<span class="isha-evt__dot" aria-hidden="true"></span>
									<span><?php echo esc_html( $isha_status ); ?></span>
								</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<h1 id="isha-art-title" class="isha-accent isha-art__title<?php echo $isha_title_class; // phpcs:ignore WordPress.Security.EscapeOutput -- chuỗi cố định. ?>"><span class="isha-art__mask"><span class="isha-art__name"><?php echo esc_html( $isha_d['title'] ); ?></span></span></h1>

					<?php if ( '' !== $isha_d['summary'] ) : ?>
						<p class="isha-lead isha-art__lead"><?php echo esc_html( $isha_d['summary'] ); ?></p>
					<?php endif; ?>

					<?php if ( $isha_cells > 0 ) : ?>
						<dl class="isha-evt__strip">
							<?php if ( $isha_has_when ) : ?>
								<div class="isha-evt__cell isha-evt__cell--when">
									<dt><?php esc_html_e( 'Thời gian', 'isha' ); ?></dt>
									<dd>
										<time datetime="<?php echo esc_attr( wp_date( 'c', $isha_e['start_ts'] ) ); ?>"><?php echo esc_html( $isha_e['date'] ); ?></time>
										<?php if ( '' !== $isha_e['time'] ) : ?>
											<span class="isha-evt__sub"><?php echo esc_html( $isha_e['time'] ); ?></span>
										<?php endif; ?>
									</dd>
								</div>
							<?php endif; ?>
							<?php if ( $isha_has_place ) : ?>
								<div class="isha-evt__cell isha-evt__cell--place">
									<dt><?php esc_html_e( 'Địa điểm', 'isha' ); ?></dt>
									<dd><?php echo esc_html( $isha_e['location'] ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( '' !== $isha_kind ) : ?>
								<div class="isha-evt__cell isha-evt__cell--kind">
									<dt><?php echo esc_html( $isha_kind_lbl ); ?></dt>
									<dd><?php echo esc_html( $isha_kind ); ?></dd>
								</div>
							<?php endif; ?>
						</dl>
					<?php endif; ?>
				</div>

				<div class="isha-art__frame">
					<figure class="isha-art__media">
						<?php
						// Ảnh bìa có bản gốc: bấm mở hộp xem lớn nguyên khung (post-article.js); thiếu JS thì liên kết mở ảnh gốc.
						if ( '' !== $isha_full ) :
							?>
							<a class="isha-evt__zoom" href="<?php echo esc_url( $isha_full ); ?>" data-isha-cover data-isha-label="<?php esc_attr_e( 'Xem lớn ảnh bìa sự kiện', 'isha' ); ?>">
						<?php endif; ?>
						<?php
						isha_home_media(
							$isha_d['image'],
							__( 'Ảnh bìa sự kiện', 'isha' ),
							array(
								'size'          => 'isha-banner',
								'sizes'         => '(max-width: 900px) 92vw, 640px',
								'class'         => 'isha-art__img',
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'decorative'    => true,
							)
						);
						?>
						<?php if ( '' !== $isha_full ) : ?>
							</a>
						<?php endif; ?>
					</figure>
				</div>
			</div>
		</section>

		<div class="isha-sec isha-art__body" data-tone="paper">
			<div class="isha-wrap isha-art__layout">

				<div class="isha-art__main">
					<div class="isha-art__content isha-evt__content<?php echo $isha_builder ? ' isha-art__content--builder' : ''; ?>" data-isha-article-body>
						<?php
						if ( $isha_lock ) {
							echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput -- biểu mẫu mật khẩu của WordPress.
						} else {
							the_content();
						}
						?>
					</div>
				</div>

				<div class="isha-art__side">
					<aside class="isha-art__card isha-evt__card" data-tone="night" aria-label="<?php esc_attr_e( 'Thông tin sự kiện', 'isha' ); ?>">
						<p class="isha-label isha-art__card-eyebrow"><?php esc_html_e( 'Thông tin sự kiện', 'isha' ); ?></p>
						<?php if ( '' !== $isha_card_title ) : ?>
							<h2 class="isha-art__card-title"><?php echo esc_html( $isha_card_title ); ?></h2>
						<?php endif; ?>

						<?php if ( $isha_has_when || $isha_has_place || '' !== $isha_type ) : ?>
							<dl class="isha-evt__facts">
								<?php if ( $isha_has_when ) : ?>
									<div class="isha-evt__fact">
										<dt><?php esc_html_e( 'Thời gian', 'isha' ); ?></dt>
										<dd>
											<time datetime="<?php echo esc_attr( wp_date( 'c', $isha_e['start_ts'] ) ); ?>"><?php echo esc_html( $isha_e['date'] ); ?></time>
											<?php if ( '' !== $isha_e['time'] ) : ?>
												<span class="isha-evt__sub"><?php echo esc_html( $isha_e['time'] ); ?></span>
											<?php endif; ?>
											<?php if ( '' !== $isha_e['calendar_url'] ) : ?>
												<a class="isha-evt__act" href="<?php echo esc_url( $isha_e['calendar_url'] ); ?>" target="_blank" rel="noopener noreferrer"><span class="isha-evt__plus" aria-hidden="true"></span><?php esc_html_e( 'Thêm vào lịch', 'isha' ); ?><span class="isha-sr"> <?php esc_html_e( '(mở thẻ mới)', 'isha' ); ?></span></a>
											<?php endif; ?>
										</dd>
									</div>
								<?php endif; ?>
								<?php if ( $isha_has_place ) : ?>
									<div class="isha-evt__fact">
										<dt><?php esc_html_e( 'Địa điểm', 'isha' ); ?></dt>
										<dd>
											<?php echo esc_html( $isha_e['location'] ); ?>
											<?php if ( '' !== $isha_e['map_url'] ) : ?>
												<a class="isha-evt__act" href="<?php echo esc_url( $isha_e['map_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Chỉ đường', 'isha' ); ?><span class="isha-evt__out" aria-hidden="true"> ↗</span><span class="isha-sr"> <?php esc_html_e( '(mở thẻ mới)', 'isha' ); ?></span></a>
											<?php endif; ?>
										</dd>
									</div>
								<?php endif; ?>
								<?php if ( '' !== $isha_type ) : ?>
									<div class="isha-evt__fact">
										<dt><?php esc_html_e( 'Hình thức', 'isha' ); ?></dt>
										<dd><?php echo esc_html( $isha_type ); ?></dd>
									</div>
								<?php endif; ?>
							</dl>
						<?php endif; ?>

						<?php if ( $isha_past && '' !== $isha_d['list']['url'] ) : ?>
							<a class="isha-link isha-evt__more" href="<?php echo esc_url( $isha_d['list']['url'] ); ?>"><?php esc_html_e( 'Xem sự kiện sắp tới', 'isha' ); ?><span class="isha-art__arrow" aria-hidden="true"></span></a>
						<?php endif; ?>

						<div class="isha-evt__util">
							<?php if ( '' !== esc_url( $isha_tel ) && '' !== $isha_phone ) : ?>
								<p class="isha-art__hotline">
									<a href="<?php echo esc_url( $isha_tel ); ?>">
										<span class="isha-art__hotline-label"><?php esc_html_e( 'Hotline', 'isha' ); ?></span>
										<span class="isha-art__hotline-num"><?php echo esc_html( $isha_phone ); ?></span>
									</a>
								</p>
							<?php endif; ?>
							<?php // Nút sao chép: hidden tới khi post-article.js bật (không JS thì không có nút vô dụng). ?>
							<button type="button" class="isha-art__copy isha-evt__copy" data-isha-copy hidden>
								<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="8.5" y="8.5" width="11" height="11" rx="1.5"/><path d="M15.5 8.5V6A1.5 1.5 0 0 0 14 4.5H6A1.5 1.5 0 0 0 4.5 6v8A1.5 1.5 0 0 0 6 15.5h2.5"/></svg>
								<span class="isha-art__copy-text" data-isha-copy-text><?php esc_html_e( 'Sao chép link mời bạn', 'isha' ); ?></span>
								<span class="isha-sr" role="status" aria-live="polite" data-isha-copy-status></span>
							</button>
						</div>
					</aside>
				</div>

			</div>
		</div>

		<?php if ( $isha_d['related'] ) : ?>
			<section class="isha-sec isha-art__related" data-tone="day-warm" aria-labelledby="isha-art-related-title">
				<div class="isha-wrap">
					<div class="isha-art__related-head">
						<div>
							<p class="isha-label isha-art__related-eyebrow"><?php esc_html_e( 'Khám phá thêm', 'isha' ); ?></p>
							<h2 id="isha-art-related-title" class="isha-sec__title isha-art__related-title"><?php esc_html_e( 'Sự kiện khác', 'isha' ); ?></h2>
						</div>
						<?php if ( '' !== $isha_d['list']['url'] ) : ?>
							<a class="isha-link" href="<?php echo esc_url( $isha_d['list']['url'] ); ?>"><?php esc_html_e( 'Xem tất cả sự kiện', 'isha' ); ?><span class="isha-art__arrow" aria-hidden="true"></span></a>
						<?php endif; ?>
					</div>
					<ul class="isha-art__cards" role="list">
						<?php foreach ( $isha_d['related'] as $isha_card ) : ?>
							<?php
							$isha_card_meta = trim( $isha_card['label'] . ( '' !== $isha_card['label'] && '' !== $isha_card['meta'] ? ' · ' : '' ) . $isha_card['meta'] );
							$isha_tag       = isset( $isha_tag_labels[ $isha_card['state'] ] ) ? $isha_tag_labels[ $isha_card['state'] ] : '';
							?>
							<li class="isha-art__card-item">
								<article class="isha-art__tile">
									<div class="isha-art__tile-media">
										<?php
										isha_home_media(
											$isha_card['image'],
											__( 'Ảnh sự kiện', 'isha' ),
											array(
												'size'       => 'isha-banner-sm',
												'sizes'      => '(max-width: 900px) 82vw, 380px',
												'class'      => 'isha-art__tile-img',
												'decorative' => true,
											)
										);
										?>
										<?php if ( '' !== $isha_tag ) : ?>
											<span class="isha-evt__tag isha-evt__tag--<?php echo esc_attr( $isha_card['state'] ); ?>"><?php echo esc_html( $isha_tag ); ?></span>
										<?php endif; ?>
									</div>
									<?php if ( '' !== $isha_card_meta ) : ?>
										<p class="isha-art__tile-meta"><?php echo esc_html( $isha_card_meta ); ?></p>
									<?php endif; ?>
									<h3 class="isha-art__tile-title"><a href="<?php echo esc_url( $isha_card['url'] ); ?>"><?php echo esc_html( $isha_card['title'] ); ?></a></h3>
								</article>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

	</article>
	<?php get_template_part( 'template-parts/home/dock', null, array( 'booking_url' => $isha_book ) ); ?>
</div>
