<?php
/**
 * Trang Liên hệ và trang Đặt lịch hẹn (templates/template-contact.php, templates/template-booking.php): cùng một bố cục theo phong cách
 * section "Câu chuyện nhà sáng lập" của trang chủ.
 *
 * - Mở đầu (`section.isha-contact__open`, tông day): cột trái gồm tên website, tiêu đề trang (h1, mặt nạ trượt lên), vạch vàng, lời dẫn
 *   (nội dung trang trong trình soạn thảo; trống thì câu mặc định), thông tin liên hệ (địa chỉ, hotline, email, giờ làm việc, mạng xã hội),
 *   nút hành động; cột phải là thẻ form trong khung viền vàng kim lệch phía sau (form liên hệ hoặc form đặt lịch).
 * - Bản đồ (`section.isha-contact__map`, tông day-warm): iframe Google Maps (link nhúng ở ISHA Clinic) trong khung viền vàng, chú thích địa
 *   chỉ và liên kết "Chỉ đường". Không có link nhúng thì bỏ section.
 *
 * `$args`: mode (contact | booking).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_mode    = ( isset( $args['mode'] ) && 'booking' === $args['mode'] ) ? 'booking' : 'contact';
$isha_info    = isha_forms_contact_info();
$isha_post    = get_post();
$isha_title   = $isha_post ? wp_strip_all_tags( get_the_title( $isha_post ) ) : '';
$isha_content = ( $isha_post && ! post_password_required( $isha_post ) ) ? trim( (string) $isha_post->post_content ) : '';
$isha_booking = isha_forms_booking_url();

if ( 'booking' === $isha_mode ) {
	$isha_title      = '' !== $isha_title ? $isha_title : __( 'Đặt lịch hẹn', 'isha' );
	$isha_lead       = __( 'Chọn ngày giờ thuận tiện, ISHA sẽ gọi lại để xác nhận lịch và chuẩn bị buổi thăm khám riêng cho bạn.', 'isha' );
	$isha_card_title = __( 'Thông tin lịch hẹn', 'isha' );
	$isha_card_note  = __( 'Ô có dấu * là bắt buộc. Thông tin chỉ dùng để liên hệ xác nhận lịch hẹn.', 'isha' );
} else {
	$isha_title      = '' !== $isha_title ? $isha_title : __( 'Liên hệ', 'isha' );
	$isha_lead       = __( 'Đội ngũ ISHA luôn sẵn sàng lắng nghe câu hỏi, góp ý hay đề xuất hợp tác của bạn.', 'isha' );
	$isha_card_title = __( 'Gửi lời nhắn cho ISHA', 'isha' );
	$isha_card_note  = __( 'Ô có dấu * là bắt buộc. ISHA phản hồi trong vòng một ngày làm việc.', 'isha' );
}
?>
<div class="isha-home isha-contact isha-contact--<?php echo esc_attr( $isha_mode ); ?>">
	<section class="isha-sec isha-contact__open" data-tone="day" aria-labelledby="isha-contact-title">
		<div class="isha-wrap isha-contact__grid">
			<div class="isha-contact__info">
				<p class="isha-label isha-contact__eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
				<h1 id="isha-contact-title" class="isha-contact__title"><span class="isha-contact__mask"><span class="isha-contact__name"><?php echo esc_html( $isha_title ); ?></span></span></h1>
				<span class="isha-contact__rule" aria-hidden="true"></span>
				<div class="isha-contact__lead">
					<?php
					if ( '' !== $isha_content ) {
						echo wp_kses_post( apply_filters( 'the_content', $isha_content ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- bộ lọc lõi của WordPress.
					} else {
						echo '<p>' . esc_html( $isha_lead ) . '</p>';
					}
					?>
				</div>

				<dl class="isha-contact__facts">
					<?php if ( $isha_info['address'] ) : ?>
						<div class="isha-contact__fact">
							<dt><?php esc_html_e( 'Địa chỉ', 'isha' ); ?></dt>
							<dd>
								<?php echo esc_html( implode( ', ', $isha_info['address'] ) ); ?>
								<?php if ( '' !== $isha_info['directions_url'] ) : ?>
									<a class="isha-contact__link" href="<?php echo esc_url( $isha_info['directions_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Chỉ đường', 'isha' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(mở Google Maps ở thẻ mới)', 'isha' ); ?></span></a>
								<?php endif; ?>
							</dd>
						</div>
					<?php endif; ?>
					<?php if ( '' !== $isha_info['phone'] ) : ?>
						<div class="isha-contact__fact">
							<dt><?php esc_html_e( 'Hotline', 'isha' ); ?></dt>
							<dd><a class="isha-contact__big" href="<?php echo esc_url( $isha_info['tel_url'] ); ?>"><?php echo esc_html( $isha_info['phone'] ); ?></a></dd>
						</div>
					<?php endif; ?>
					<?php if ( '' !== $isha_info['email'] ) : ?>
						<div class="isha-contact__fact">
							<dt><?php esc_html_e( 'Email', 'isha' ); ?></dt>
							<dd><a href="<?php echo esc_url( 'mailto:' . $isha_info['email'] ); ?>"><?php echo esc_html( $isha_info['email'] ); ?></a></dd>
						</div>
					<?php endif; ?>
					<?php if ( $isha_info['hours'] ) : ?>
						<div class="isha-contact__fact">
							<dt><?php esc_html_e( 'Giờ làm việc', 'isha' ); ?></dt>
							<dd><?php echo implode( '<br>', array_map( 'esc_html', $isha_info['hours'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- từng dòng đã esc_html. ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( $isha_info['social'] ) : ?>
						<div class="isha-contact__fact">
							<dt><?php esc_html_e( 'Theo dõi ISHA', 'isha' ); ?></dt>
							<dd>
								<ul class="isha-contact__social">
									<?php foreach ( $isha_info['social'] as $isha_net ) : ?>
										<li>
											<a class="isha-contact__social-link" href="<?php echo esc_url( $isha_net['url'] ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( $isha_net['label'] ); ?>" aria-label="<?php echo esc_attr( $isha_net['label'] . ' ' . __( '(mở thẻ mới)', 'isha' ) ); ?>"><img src="<?php echo esc_url( $isha_net['icon'] ); ?>" alt="" width="18" height="18"></a>
										</li>
									<?php endforeach; ?>
								</ul>
							</dd>
						</div>
					<?php endif; ?>
				</dl>

				<div class="isha-contact__actions">
					<?php if ( 'contact' === $isha_mode && '' !== $isha_booking ) : ?>
						<a class="isha-btn isha-btn--primary" href="<?php echo esc_url( $isha_booking ); ?>"><?php esc_html_e( 'Đặt lịch tư vấn', 'isha' ); ?></a>
					<?php elseif ( '' !== $isha_info['tel_url'] ) : ?>
						<a class="isha-btn isha-btn--primary" href="<?php echo esc_url( $isha_info['tel_url'] ); ?>"><?php esc_html_e( 'Gọi hotline', 'isha' ); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<div class="isha-contact__card-wrap">
				<div class="isha-contact__card" id="<?php echo 'booking' === $isha_mode ? 'isha-booking' : 'isha-contact-form'; ?>">
					<h2 class="isha-contact__card-title"><?php echo esc_html( $isha_card_title ); ?></h2>
					<p class="isha-contact__card-note"><?php echo esc_html( $isha_card_note ); ?></p>
					<?php
					if ( 'booking' === $isha_mode ) {
						get_template_part(
							'template-parts/forms/booking-form',
							null,
							array(
								'prefix'  => 'isha-booking-page',
								'context' => 'page',
								'service' => isset( $_GET['service'] ) ? absint( $_GET['service'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ chọn sẵn một lựa chọn.
								'nonce'   => true,
								'status'  => isha_forms_status( 'isha_booking' ),
							)
						);
					} else {
						get_template_part(
							'template-parts/forms/contact-form',
							null,
							array(
								'prefix' => 'isha-contact-page',
								'status' => isha_forms_status( 'isha_contact' ),
							)
						);
					}
					?>
				</div>
			</div>
		</div>
	</section>

	<?php if ( '' !== $isha_info['map_url'] ) : ?>
		<section class="isha-sec isha-contact__map" data-tone="day-warm" aria-labelledby="isha-contact-map-title">
			<div class="isha-wrap">
				<h2 id="isha-contact-map-title" class="isha-sec__title isha-contact__map-title"><?php esc_html_e( 'Đường đến ISHA', 'isha' ); ?></h2>
				<figure class="isha-contact__map-figure">
					<div class="isha-contact__map-frame">
						<iframe src="<?php echo esc_url( $isha_info['map_url'] ); ?>" title="<?php esc_attr_e( 'Bản đồ vị trí ISHA Beauty Clinic', 'isha' ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
					</div>
					<?php if ( $isha_info['address'] ) : ?>
						<figcaption class="isha-contact__map-caption">
							<?php echo esc_html( implode( ', ', $isha_info['address'] ) ); ?>
							<?php if ( '' !== $isha_info['directions_url'] ) : ?>
								<a class="isha-contact__link" href="<?php echo esc_url( $isha_info['directions_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Chỉ đường', 'isha' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(mở Google Maps ở thẻ mới)', 'isha' ); ?></span></a>
							<?php endif; ?>
						</figcaption>
					<?php endif; ?>
				</figure>
			</div>
		</section>
	<?php endif; ?>
</div>
