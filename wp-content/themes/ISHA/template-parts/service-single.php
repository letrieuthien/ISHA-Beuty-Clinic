<?php
/**
 * Trang chi tiết liệu trình (single-isha_service.php), dựng theo hệ thiết kế của trang chủ điện ảnh.
 *
 * Bố cục: khung mở đầu (nhóm là liên kết về trang nhóm, tên liệu trình, mô tả ngắn, thời gian; ảnh đại diện dọc 4:5 lệch phải), rồi vùng
 * nội dung hai cột: nội dung biên tập (the_content), các bước liệu trình trên một đường kẻ dọc, ảnh liệu trình (hàng vuốt có nút mũi tên,
 * bấm mở hộp xem lớn trong trang) ở cột chính; thẻ thông tin đặt lịch dính bên cạnh (desktop) hoặc nằm cuối (chế độ gọn). Cuối trang là
 * "Liệu trình khác trong nhóm" (tối đa 5). Khối nào thiếu dữ liệu thì bỏ hẳn, không để khoảng trống.
 *
 * Trạng thái TĨNH là mặc định và đọc được trọn vẹn: service-single.js chỉ thêm chuyển động (ảnh tiến chậm, các bước hiện lần lượt) và hộp
 * xem lớn; không có JS thì ảnh liệu trình là liên kết tới tệp ảnh. Dùng class isha-home để thừa hưởng tông màu, chữ, nút, ảnh, hàng vuốt
 * của trang chủ (home.css); riêng trang này ở service-single.css.
 *
 * Dữ liệu: `$args` = isha_service_single_data( $post_id ).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_d = ( isset( $args ) && is_array( $args ) ) ? $args : array();

// Lấy một giá trị chuỗi (đã cắt khoảng trắng) từ dữ liệu; không phải chuỗi/số thì trả ''.
$isha_text = static function ( $key ) use ( $isha_d ) {
	return ( isset( $isha_d[ $key ] ) && is_scalar( $isha_d[ $key ] ) ) ? trim( (string) $isha_d[ $key ] ) : '';
};

$isha_id    = ( isset( $isha_d['id'] ) && is_numeric( $isha_d['id'] ) ) ? (int) $isha_d['id'] : (int) get_the_ID();
$isha_title = $isha_text( 'title' );
if ( '' === $isha_title ) {
	$isha_title = wp_strip_all_tags( get_the_title( $isha_id ) );
}
$isha_desc = $isha_text( 'short_desc' );
$isha_dur  = $isha_text( 'duration' );
$isha_book = $isha_text( 'book_url' );
$isha_zalo = $isha_text( 'zalo_url' );

$isha_group      = ( isset( $isha_d['group'] ) && is_array( $isha_d['group'] ) ) ? $isha_d['group'] : array();
$isha_group_name = ( isset( $isha_group['name'] ) && is_scalar( $isha_group['name'] ) ) ? trim( (string) $isha_group['name'] ) : '';
$isha_group_url  = ( isset( $isha_group['url'] ) && is_scalar( $isha_group['url'] ) ) ? trim( (string) $isha_group['url'] ) : '';

$isha_steps = array();
if ( isset( $isha_d['steps'] ) && is_array( $isha_d['steps'] ) ) {
	foreach ( $isha_d['steps'] as $isha_step ) {
		if ( is_scalar( $isha_step ) && '' !== trim( (string) $isha_step ) ) {
			$isha_steps[] = trim( (string) $isha_step );
		}
	}
}

$isha_gallery = array();
if ( isset( $isha_d['gallery'] ) && is_array( $isha_d['gallery'] ) ) {
	foreach ( $isha_d['gallery'] as $isha_shot ) {
		if ( is_array( $isha_shot ) && ! empty( $isha_shot['id'] ) && ! empty( $isha_shot['url'] ) ) {
			$isha_gallery[] = $isha_shot;
		}
	}
}
$isha_total = count( $isha_gallery );

$isha_others = ( isset( $isha_d['others'] ) && is_array( $isha_d['others'] ) ) ? $isha_d['others'] : array();

// Nội dung biên tập qua get_the_content() (bài đặt mật khẩu thì ra biểu mẫu mật khẩu, không lộ nội dung) và bộ lọc the_content;
// bỏ khối nếu chỉ có thẻ rỗng (không chữ, không ảnh, không video).
$isha_content = trim( str_replace( ']]>', ']]&gt;', (string) apply_filters( 'the_content', get_the_content( null, false, $isha_id ) ) ) );
if ( '' !== $isha_content && '' === trim( wp_strip_all_tags( $isha_content ) ) && ! preg_match( '/<(?:img|figure|iframe|video)\b/i', $isha_content ) ) {
	$isha_content = '';
}

$isha_has_main = '' !== $isha_content || $isha_steps || $isha_gallery;

// Tên rất dài (trên 44 ký tự): hạ cỡ chữ để khỏi chiếm quá nhiều dòng.
$isha_title_class = mb_strlen( $isha_title ) > 44 ? ' isha-svc__title--long' : '';
?>
<div class="isha-home isha-svc">
	<article id="post-<?php echo esc_attr( (string) $isha_id ); ?>" class="isha-svc__article">

		<section class="isha-sec isha-svc__open" data-tone="day-warm" aria-labelledby="isha-svc-title">
			<div class="isha-wrap isha-svc__open-grid">
				<div class="isha-svc__intro">
					<?php if ( '' !== $isha_group_name ) : ?>
						<p class="isha-label isha-svc__group">
							<span class="isha-sr"><?php esc_html_e( 'Nhóm dịch vụ:', 'isha' ); ?></span>
							<?php if ( '' !== $isha_group_url ) : ?>
								<a class="isha-svc__group-link" href="<?php echo esc_url( $isha_group_url ); ?>"><?php echo esc_html( $isha_group_name ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $isha_group_name ); ?>
							<?php endif; ?>
						</p>
					<?php endif; ?>

					<h1 id="isha-svc-title" class="isha-accent isha-svc__title<?php echo $isha_title_class; // phpcs:ignore WordPress.Security.EscapeOutput -- chuỗi cố định. ?>"><span class="isha-svc__mask"><span class="isha-svc__name"><?php echo esc_html( $isha_title ); ?></span></span></h1>

					<?php if ( '' !== $isha_desc ) : ?>
						<p class="isha-lead isha-svc__lead"><?php echo esc_html( $isha_desc ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== $isha_dur ) : ?>
						<dl class="isha-svc__facts">
							<div class="isha-svc__fact">
								<dt class="isha-label"><?php esc_html_e( 'Thời gian liệu trình', 'isha' ); ?></dt>
								<dd><?php echo esc_html( $isha_dur ); ?></dd>
							</div>
						</dl>
					<?php endif; ?>
				</div>

				<div class="isha-svc__frame">
					<div class="isha-svc__media isha-r-45">
						<?php
						isha_home_media(
							isset( $isha_d['image_id'] ) ? $isha_d['image_id'] : 0,
							__( 'Ảnh đại diện liệu trình', 'isha' ),
							array(
								'size'          => 'isha-frame',
								'sizes'         => '(max-width: 900px) 88vw, 520px',
								'class'         => 'isha-svc__img',
								'ratio'         => '45',
								'loading'       => 'eager',
								'fetchpriority' => 'high',
							)
						);
						?>
					</div>
				</div>
			</div>
		</section>

		<?php if ( $isha_has_main || '' !== $isha_book ) : ?>
			<div class="isha-sec isha-svc__body" data-tone="day">
				<div class="isha-wrap isha-svc__layout<?php echo $isha_has_main ? '' : ' isha-svc__layout--solo'; ?>">

					<?php if ( $isha_has_main ) : ?>
						<div class="isha-svc__main">

							<?php if ( '' !== $isha_content ) : ?>
								<section class="isha-svc__block isha-svc__intro-text" aria-label="<?php esc_attr_e( 'Giới thiệu liệu trình', 'isha' ); ?>">
									<div class="isha-svc__content">
										<?php echo $isha_content; // phpcs:ignore WordPress.Security.EscapeOutput -- đã qua bộ lọc the_content của WordPress. ?>
									</div>
								</section>
							<?php endif; ?>

							<?php if ( $isha_steps ) : ?>
								<section class="isha-svc__block isha-svc__steps-block" aria-labelledby="isha-svc-steps-title">
									<h2 id="isha-svc-steps-title" class="isha-sec__title isha-svc__h2"><?php esc_html_e( 'Các bước liệu trình', 'isha' ); ?></h2>
									<ol class="isha-svc__steps" role="list">
										<?php foreach ( $isha_steps as $isha_n => $isha_step ) : ?>
											<li class="isha-svc__step">
												<span class="isha-svc__num" aria-hidden="true"><?php echo esc_html( (string) ( $isha_n + 1 ) ); ?></span>
												<p class="isha-svc__step-text"><?php echo esc_html( $isha_step ); ?></p>
											</li>
										<?php endforeach; ?>
									</ol>
								</section>
							<?php endif; ?>

							<?php if ( $isha_gallery ) : ?>
								<section class="isha-svc__block isha-svc__gallery" aria-labelledby="isha-svc-gallery-title">
									<h2 id="isha-svc-gallery-title" class="isha-sec__title isha-svc__h2"><?php esc_html_e( 'Hình ảnh liệu trình', 'isha' ); ?></h2>
									<div class="isha-row isha-svc__row" data-isha-row>
										<ul class="isha-row__track isha-svc__track" role="list" tabindex="0" aria-label="<?php esc_attr_e( 'Ảnh liệu trình', 'isha' ); ?>">
											<?php foreach ( $isha_gallery as $isha_k => $isha_shot ) : ?>
												<?php
												$isha_alt = isset( $isha_shot['alt'] ) ? (string) $isha_shot['alt'] : '';
												$isha_cap = isset( $isha_shot['caption'] ) ? (string) $isha_shot['caption'] : '';
												if ( '' !== $isha_alt ) {
													/* translators: %s: mô tả của ảnh */
													$isha_label = sprintf( __( 'Xem lớn ảnh: %s', 'isha' ), $isha_alt );
												} else {
													/* translators: 1: số thứ tự ảnh, 2: tổng số ảnh */
													$isha_label = sprintf( __( 'Xem lớn ảnh %1$d trên %2$d', 'isha' ), $isha_k + 1, $isha_total );
												}
												?>
												<li class="isha-row__item isha-svc__shot">
													<a class="isha-svc__zoom" href="<?php echo esc_url( (string) $isha_shot['url'] ); ?>" data-isha-zoom data-alt="<?php echo esc_attr( $isha_alt ); ?>" data-cap="<?php echo esc_attr( $isha_cap ); ?>" aria-haspopup="dialog" aria-label="<?php echo esc_attr( $isha_label ); ?>">
														<?php
														echo isha_home_image( // phpcs:ignore WordPress.Security.EscapeOutput -- thẻ img do isha_home_image() dựng và đã escape.
															(int) $isha_shot['id'],
															array(
																'size'  => 'isha-strip',
																'sizes' => '(max-width: 900px) 80vw, 400px',
																'class' => 'isha-svc__thumb',
															)
														);
														?>
													</a>
												</li>
											<?php endforeach; ?>
										</ul>
										<?php
										isha_home_row_controls(
											array(
												'count' => $isha_total,
												'label' => __( 'Điều khiển ảnh liệu trình', 'isha' ),
											)
										);
										?>
									</div>

									<dialog class="isha-svc__viewer" data-tone="night" aria-label="<?php esc_attr_e( 'Xem ảnh liệu trình', 'isha' ); ?>">
										<div class="isha-svc__viewer-bar">
											<p class="isha-svc__viewer-count" aria-live="polite"></p>
											<button type="button" class="isha-btn isha-btn--ghost isha-svc__viewer-btn" data-isha-close><?php esc_html_e( 'Đóng', 'isha' ); ?></button>
										</div>
										<figure class="isha-svc__viewer-fig" data-isha-stage>
											<img class="isha-svc__viewer-img" alt="" decoding="async">
											<figcaption class="isha-svc__viewer-cap" hidden></figcaption>
										</figure>
										<div class="isha-svc__viewer-nav">
											<button type="button" class="isha-btn isha-btn--ghost isha-svc__viewer-btn" data-isha-prev><?php esc_html_e( 'Trước', 'isha' ); ?></button>
											<button type="button" class="isha-btn isha-btn--ghost isha-svc__viewer-btn" data-isha-next><?php esc_html_e( 'Sau', 'isha' ); ?></button>
										</div>
									</dialog>
								</section>
							<?php endif; ?>

						</div>
					<?php endif; ?>

					<aside class="isha-svc__card" data-tone="day-warm" aria-labelledby="isha-svc-card-title">
						<h2 id="isha-svc-card-title" class="isha-svc__card-title"><?php esc_html_e( 'Thông tin liệu trình', 'isha' ); ?></h2>
						<?php if ( '' !== $isha_dur ) : ?>
							<dl class="isha-svc__facts isha-svc__facts--card">
								<div class="isha-svc__fact">
									<dt class="isha-label"><?php esc_html_e( 'Thời gian liệu trình', 'isha' ); ?></dt>
									<dd><?php echo esc_html( $isha_dur ); ?></dd>
								</div>
							</dl>
						<?php endif; ?>
						<div class="isha-svc__card-actions">
							<?php
							isha_home_button( __( 'Đặt lịch liệu trình này', 'isha' ), $isha_book, 'primary' );
							isha_home_button(
								__( 'Nhắn Zalo', 'isha' ),
								$isha_zalo,
								'ghost',
								array(
									'target' => '_blank',
									'rel'    => 'noopener noreferrer',
								)
							);
							?>
						</div>
					</aside>

				</div>
			</div>
		<?php endif; ?>

		<?php if ( $isha_others ) : ?>
			<section class="isha-sec isha-svc__more" data-tone="day-warm" aria-labelledby="isha-svc-more-title">
				<div class="isha-wrap">
					<div class="isha-sec__head">
						<h2 id="isha-svc-more-title" class="isha-sec__title"><?php esc_html_e( 'Liệu trình khác trong nhóm', 'isha' ); ?></h2>
						<?php if ( '' !== $isha_group_name ) : ?>
							<p class="isha-lead"><?php echo esc_html( $isha_group_name ); ?></p>
						<?php endif; ?>
					</div>
					<ul class="isha-svc__others">
						<?php foreach ( $isha_others as $isha_other ) : ?>
							<?php
							if ( ! is_array( $isha_other ) || empty( $isha_other['title'] ) || empty( $isha_other['url'] ) ) {
								continue;
							}
							$isha_other_dur = ( isset( $isha_other['duration'] ) && is_scalar( $isha_other['duration'] ) ) ? trim( (string) $isha_other['duration'] ) : '';
							?>
							<li class="isha-svc__other-item">
								<a class="isha-svc__other" href="<?php echo esc_url( (string) $isha_other['url'] ); ?>">
									<span class="isha-svc__other-name"><?php echo esc_html( (string) $isha_other['title'] ); ?></span>
									<?php if ( '' !== $isha_other_dur ) : ?>
										<span class="isha-svc__other-meta"><?php echo esc_html( $isha_other_dur ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php if ( '' !== $isha_group_url ) : ?>
						<a class="isha-link" href="<?php echo esc_url( $isha_group_url ); ?>"><?php esc_html_e( 'Xem tất cả liệu trình trong nhóm', 'isha' ); ?></a>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

	</article>
</div>
