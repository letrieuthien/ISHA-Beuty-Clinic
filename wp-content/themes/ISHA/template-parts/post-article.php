<?php
/**
 * Markup trang chi tiết Tin tức (single-isha_article.php), nâng cấp 25/11/2026 (docs/news.md): "Digital Magazine" nền tối liền header.
 * Gọi trong vòng lặp; bao trong `div.isha-home.isha-art` để thừa hưởng hệ thiết kế của trang chủ (như trang chi tiết liệu trình).
 *
 * - Mở đầu (tông night, liền header): đường dẫn Tin tức / chủ đề, tiêu đề h1 (mặt nạ trượt lên), tóm tắt, dòng thông tin (ngày đăng, thời gian
 *   đọc, tác giả); ảnh bìa ngang 16:10 trong khung viền vàng kim lệch bên phải (điện thoại: ảnh dưới chữ).
 * - Nội dung (tông paper): hai cột. Cột chính: mục lục gọn (điện thoại), nội dung bài (the_content: trình soạn thảo mặc định HOẶC Elementor),
 *   rồi khối kết bài (người biên soạn + sao chép liên kết, "Liệu trình gợi ý"). Cột phải dính: mục lục tự quét h2/h3 (JS) và thẻ tư vấn tối.
 * - Liên quan (tông day-warm): ba thẻ ảnh 16:10, điện thoại vuốt ngang. Dock liên hệ nhanh của trang chủ ở góc/đáy màn hình.
 *
 * Mục lục, thanh tiến trình, xem lớn ảnh và nút sao chép chỉ là phần thêm của JS (post-article.js): không có JS thì bài vẫn đọc trọn vẹn.
 * Nội dung bài do biên tập viên soạn bằng Gutenberg, Classic hoặc Elementor (Sửa bằng Elementor); phần đầu trang, thẻ tư vấn, kết bài và
 * bài liên quan là template cố định, không nằm trong vùng soạn của Elementor.
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

$isha_id      = (int) $isha_d['id'];
$isha_lock    = post_password_required();
$isha_builder = ! empty( $isha_d['builder'] );
$isha_bridge  = isset( $isha_d['bridge'] ) && is_array( $isha_d['bridge'] ) ? $isha_d['bridge'] : array();
$isha_book    = (string) $isha_d['booking'];
$isha_zalo    = isset( $isha_d['zalo'] ) ? (string) $isha_d['zalo'] : '';
$isha_tel     = (string) $isha_d['tel_url'];
$isha_phone   = (string) $isha_d['phone'];
$isha_author  = (string) $isha_d['author'];

// Tên rất dài (trên 70 ký tự): hạ cỡ chữ để tiêu đề không chiếm quá nhiều dòng.
$isha_title_class = mb_strlen( (string) $isha_d['title'] ) > 70 ? ' isha-art__title--long' : '';
?>
<div class="isha-home isha-art<?php echo $isha_builder ? ' isha-art--builder' : ''; ?>" data-isha-art>
	<div class="isha-art__progress" data-isha-progress aria-hidden="true" hidden><span></span></div>

	<article id="post-<?php echo esc_attr( (string) $isha_id ); ?>" class="isha-art__article">

		<section id="hero" class="isha-sec isha-art__open" data-tone="night" aria-labelledby="isha-art-title">
			<div class="isha-wrap isha-art__open-grid">
				<div class="isha-art__intro">
					<?php if ( '' !== $isha_d['list']['url'] || '' !== $isha_d['term']['name'] ) : ?>
						<nav class="isha-art__crumbs" aria-label="<?php esc_attr_e( 'Đường dẫn', 'isha' ); ?>">
							<ol>
								<?php if ( '' !== $isha_d['list']['url'] ) : ?>
									<li><a href="<?php echo esc_url( $isha_d['list']['url'] ); ?>"><?php echo esc_html( $isha_d['list']['label'] ); ?></a></li>
								<?php endif; ?>
								<?php if ( '' !== $isha_d['term']['name'] ) : ?>
									<li>
										<?php if ( '' !== $isha_d['term']['url'] ) : ?>
											<a href="<?php echo esc_url( $isha_d['term']['url'] ); ?>"><?php echo esc_html( $isha_d['term']['name'] ); ?></a>
										<?php else : ?>
											<?php echo esc_html( $isha_d['term']['name'] ); ?>
										<?php endif; ?>
									</li>
								<?php endif; ?>
							</ol>
						</nav>
					<?php endif; ?>

					<h1 id="isha-art-title" class="isha-accent isha-art__title<?php echo $isha_title_class; // phpcs:ignore WordPress.Security.EscapeOutput -- chuỗi cố định. ?>"><span class="isha-art__mask"><span class="isha-art__name"><?php echo esc_html( $isha_d['title'] ); ?></span></span></h1>

					<?php if ( '' !== $isha_d['summary'] ) : ?>
						<p class="isha-lead isha-art__lead"><?php echo esc_html( $isha_d['summary'] ); ?></p>
					<?php endif; ?>

					<ul class="isha-art__meta">
						<li><time datetime="<?php echo esc_attr( $isha_d['date_iso'] ); ?>"><?php echo esc_html( $isha_d['date'] ); ?></time></li>
						<?php /* translators: %d: số phút đọc */ ?>
						<li><?php echo esc_html( sprintf( __( '%d phút đọc', 'isha' ), $isha_d['read_min'] ) ); ?></li>
						<?php if ( '' !== $isha_author ) : ?>
							<li><?php echo esc_html( $isha_author ); ?></li>
						<?php endif; ?>
					</ul>
				</div>

				<div class="isha-art__frame">
					<figure class="isha-art__media">
						<?php
						isha_home_media(
							$isha_d['image'],
							__( 'Ảnh bìa bài viết', 'isha' ),
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
					</figure>
				</div>
			</div>
		</section>

		<div class="isha-sec isha-art__body" data-tone="paper">
			<div class="isha-wrap isha-art__layout">

				<div class="isha-art__main">
					<?php if ( ! $isha_lock ) : ?>
						<?php // Mục lục gọn của chế độ gọn; JS điền danh sách và bỏ `hidden` khi bài có từ 2 tiêu đề trở lên. ?>
						<details class="isha-art__toc-m" data-isha-toc-m hidden>
							<summary>
								<span class="isha-art__toc-m-title"><?php esc_html_e( 'Nội dung bài viết', 'isha' ); ?></span>
								<span class="isha-art__toc-m-icon" aria-hidden="true"></span>
							</summary>
							<ol class="isha-art__toc-list" role="list"></ol>
						</details>
					<?php endif; ?>

					<div class="isha-art__content<?php echo $isha_builder ? ' isha-art__content--builder' : ''; ?>" data-isha-article-body>
						<?php
						if ( $isha_lock ) {
							echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput -- biểu mẫu mật khẩu của WordPress.
						} else {
							the_content();
						}
						?>
					</div>

					<footer class="isha-art__end">
						<div class="isha-art__credit">
							<span class="isha-art__seal" aria-hidden="true"></span>
							<p class="isha-art__credit-text">
								<?php if ( '' !== $isha_author ) : ?>
									<span class="isha-art__credit-line">
										<?php
										/* translators: %s: tên người biên soạn */
										echo esc_html( sprintf( __( 'Biên soạn bởi %s', 'isha' ), $isha_author ) );
										?>
									</span>
								<?php endif; ?>
								<span class="isha-art__credit-sub">
									<?php if ( '' !== $isha_d['term']['name'] ) : ?>
										<?php
										/* translators: %s: tên chủ đề */
										echo esc_html( sprintf( __( 'Chủ đề: %s', 'isha' ), $isha_d['term']['name'] ) );
										?>
										<span aria-hidden="true"> · </span>
									<?php endif; ?>
									<?php if ( ! empty( $isha_d['updated'] ) ) : ?>
										<?php
										/* translators: %s: ngày cập nhật gần nhất */
										echo esc_html( sprintf( __( 'Cập nhật %s', 'isha' ), $isha_d['modified'] ) );
										?>
									<?php else : ?>
										<?php
										/* translators: %s: ngày đăng bài */
										echo esc_html( sprintf( __( 'Đăng %s', 'isha' ), $isha_d['date'] ) );
										?>
									<?php endif; ?>
								</span>
							</p>
							<button type="button" class="isha-art__copy" data-isha-copy hidden>
								<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="8.5" y="8.5" width="11" height="11" rx="1.5"/><path d="M15.5 8.5V6A1.5 1.5 0 0 0 14 4.5H6A1.5 1.5 0 0 0 4.5 6v8A1.5 1.5 0 0 0 6 15.5h2.5"/></svg>
								<span class="isha-art__copy-text" data-isha-copy-text><?php esc_html_e( 'Sao chép liên kết', 'isha' ); ?></span>
								<span class="isha-sr" role="status" aria-live="polite" data-isha-copy-status></span>
							</button>
						</div>

						<?php if ( ! empty( $isha_bridge['url'] ) ) : ?>
							<div class="isha-art__bridge">
								<div class="isha-art__bridge-text">
									<p class="isha-label isha-art__bridge-eyebrow"><?php esc_html_e( 'Liệu trình gợi ý', 'isha' ); ?></p>
									<h2 class="isha-art__bridge-title"><?php echo esc_html( $isha_bridge['name'] ); ?></h2>
									<?php if ( ! empty( $isha_bridge['desc'] ) ) : ?>
										<p class="isha-art__bridge-desc"><?php echo esc_html( $isha_bridge['desc'] ); ?></p>
									<?php endif; ?>
								</div>
								<a class="isha-link isha-art__bridge-link" href="<?php echo esc_url( $isha_bridge['url'] ); ?>"><?php esc_html_e( 'Khám phá liệu trình', 'isha' ); ?><span class="isha-art__arrow" aria-hidden="true"></span></a>
							</div>
						<?php endif; ?>
					</footer>
				</div>

				<div class="isha-art__side">
					<?php if ( ! $isha_lock ) : ?>
						<?php // Mục lục tự động của desktop; JS điền danh sách và bỏ `hidden` khi bài có từ 2 tiêu đề trở lên. ?>
						<nav class="isha-art__toc" data-isha-toc aria-labelledby="isha-art-toc-title" hidden>
							<p class="isha-label isha-art__toc-eyebrow" id="isha-art-toc-title"><?php esc_html_e( 'Mục lục', 'isha' ); ?></p>
							<ol class="isha-art__toc-list" role="list"></ol>
						</nav>
					<?php endif; ?>

					<aside class="isha-art__card" data-tone="night" aria-labelledby="isha-art-card-title">
						<p class="isha-label isha-art__card-eyebrow"><?php esc_html_e( 'Tư vấn riêng', 'isha' ); ?></p>
						<h2 id="isha-art-card-title" class="isha-art__card-title"><?php esc_html_e( 'Cần bác sĩ tư vấn riêng?', 'isha' ); ?></h2>
						<p class="isha-art__card-text"><?php esc_html_e( 'Mỗi làn da có câu chuyện riêng. Bác sĩ ISHA sẽ lắng nghe và xây dựng phác đồ phù hợp với bạn.', 'isha' ); ?></p>
						<div class="isha-art__card-actions">
							<?php
							isha_home_button( __( 'Đặt lịch tư vấn riêng', 'isha' ), $isha_book, 'primary', array( 'class' => 'isha-sheen' ) );
							isha_home_button(
								__( 'Nhắn Zalo tư vấn', 'isha' ),
								$isha_zalo,
								'ghost',
								array(
									'target' => '_blank',
									'rel'    => 'noopener noreferrer',
								)
							);
							?>
						</div>
						<?php if ( '' !== esc_url( $isha_tel ) && '' !== $isha_phone ) : ?>
							<p class="isha-art__hotline">
								<a href="<?php echo esc_url( $isha_tel ); ?>">
									<span class="isha-art__hotline-label"><?php esc_html_e( 'Hotline', 'isha' ); ?></span>
									<span class="isha-art__hotline-num"><?php echo esc_html( $isha_phone ); ?></span>
								</a>
							</p>
						<?php endif; ?>
					</aside>
				</div>

			</div>
		</div>

		<?php if ( $isha_d['related'] ) : ?>
			<section class="isha-sec isha-art__related" data-tone="day-warm" aria-labelledby="isha-art-related-title">
				<div class="isha-wrap">
					<div class="isha-art__related-head">
						<div>
							<p class="isha-label isha-art__related-eyebrow"><?php esc_html_e( 'Đọc tiếp', 'isha' ); ?></p>
							<h2 id="isha-art-related-title" class="isha-sec__title isha-art__related-title"><?php esc_html_e( 'Bài viết liên quan', 'isha' ); ?></h2>
						</div>
						<?php if ( '' !== $isha_d['list']['url'] ) : ?>
							<a class="isha-link" href="<?php echo esc_url( $isha_d['list']['url'] ); ?>"><?php esc_html_e( 'Xem tất cả tin tức', 'isha' ); ?><span class="isha-art__arrow" aria-hidden="true"></span></a>
						<?php endif; ?>
					</div>
					<ul class="isha-art__cards" role="list">
						<?php foreach ( $isha_d['related'] as $isha_card ) : ?>
							<?php
							$isha_card_meta = trim( $isha_card['label'] . ( '' !== $isha_card['label'] && '' !== $isha_card['meta'] ? ' · ' : '' ) . $isha_card['meta'] );
							?>
							<li class="isha-art__card-item">
								<article class="isha-art__tile">
									<div class="isha-art__tile-media">
										<?php
										isha_home_media(
											$isha_card['image'],
											__( 'Ảnh bài viết', 'isha' ),
											array(
												'size'       => 'isha-banner-sm',
												'sizes'      => '(max-width: 900px) 82vw, 380px',
												'class'      => 'isha-art__tile-img',
												'decorative' => true,
											)
										);
										?>
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
