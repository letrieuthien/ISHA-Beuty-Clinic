<?php
/**
 * Trang chi tiết Chính sách: hai màn hình đầu, được ghim khi cuộn.
 *
 *   preloader  →  màn 1 (cảnh sóng + thẻ kính đứng như cổng + chữ)  →  cuộn: máy quay tiến vào, thẻ quay thẳng,
 *   viền sáng lên, thẻ đầy ánh sáng rồi phủ kín màn hình  →  màn 2 (thẻ của bài nổi giữa hai dòng tiêu đề).
 * Chuyển động do assets/js/policy-single.js điều khiển, hình thức do assets/css/policy-single.css.
 *
 * @package ISHA
 *
 * @var array $args Dữ liệu từ isha_policy_screen_data().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d = $args;

$style = array();
if ( ! empty( $d['color'] ) ) {
	$style[] = '--isha-ps-tier:' . $d['color'];
}
// Độ dài dòng tiêu đề dài nhất: tiêu đề dài hơn "PRESIDENT ELITE" (15 ký tự) thì CSS thu nhỏ chữ cho vừa màn hình.
$style[] = '--isha-ps-len:' . max( 1, mb_strlen( $d['line1'] ), mb_strlen( $d['line2'] ) );
if ( ! empty( $d['logo'] ) ) {
	$style[] = '--isha-ps-logo:url(' . esc_url( $d['logo']['url'] ) . ')';
	$style[] = '--isha-ps-logo-ratio:' . (float) $d['logo']['ratio'];
}

/* Ảnh thẻ: dùng chung một ảnh cho lớp kính (mờ) và thẻ nổi ở màn 2. Phải eager vì khung chứa bị scale(0) lúc đầu. */
$front_alt = sprintf( /* translators: %s: tên hạng */ __( 'Thẻ %s', 'isha' ), $d['title'] );
$front_img = $d['front']
	? wp_get_attachment_image(
		$d['front'],
		'large',
		false,
		array(
			'alt'      => $front_alt,
			'class'    => 'isha-ps__face-img',
			'loading'  => 'eager',
			'decoding' => 'async',
			'sizes'    => '(max-width: 1023px) 90vw, 700px',
		)
	)
	: '<span class="isha-ps__ph">' . esc_html( $d['title'] ) . '</span>';
$art_img   = $d['front']
	? wp_get_attachment_image(
		$d['front'],
		'medium_large',
		false,
		array(
			'alt'      => '',
			'class'    => 'isha-ps__art-img',
			'loading'  => 'eager',
			'decoding' => 'async',
			'sizes'    => '700px',
		)
	)
	: '';
$back_img  = $d['back']
	? wp_get_attachment_image(
		$d['back'],
		'large',
		false,
		array(
			'alt'      => sprintf( /* translators: %s: tên hạng */ __( 'Mặt sau thẻ %s', 'isha' ), $d['title'] ),
			'class'    => 'isha-ps__face-img',
			'loading'  => 'eager',
			'decoding' => 'async',
			'sizes'    => '(max-width: 1023px) 90vw, 700px',
		)
	)
	: '';

$word_text = empty( $d['logo'] ); // Chưa có logo thì chữ khổng lồ mờ là tên site.
$word_i    = 0;
$h1_words = '<span class="isha-ps__line" aria-hidden="true">' . isha_ps_words( $d['line1'], $word_i ) . '</span>'
	. '<span class="isha-ps__line" aria-hidden="true">' . isha_ps_words( $d['line2'], $word_i ) . '</span>';
$word_j   = 0;
$h2_top   = isha_ps_words( $d['line1'], $word_j );
$h2_bot   = isha_ps_words( $d['line2'], $word_j );
?>
<section class="isha-ps" data-isha-ps style="<?php echo esc_attr( implode( ';', $style ) ); ?>">
	<div class="isha-ps__track">
		<div class="isha-ps__stage">
			<span class="isha-ps__probe" aria-hidden="true"></span>

			<?php /* Preloader: chữ khổng lồ mờ + bộ đếm. Chỉ có tác dụng khi bật JS. */ ?>
			<div class="isha-ps__pre" aria-hidden="true">
				<div class="isha-ps__word<?php echo $word_text ? ' isha-ps__word--text' : ''; ?>"><?php echo $word_text ? esc_html( get_bloginfo( 'name' ) ) : ''; ?></div>
				<p class="isha-ps__count"><span data-ps-count>00</span>%</p>
			</div>

			<?php /* Lớp ảnh: cảnh + thẻ kính. Lớp này tự phóng to từ giữa màn hình ở đoạn mở đầu. */ ?>
			<div class="isha-ps__media" data-ps-media aria-hidden="true">
				<div class="isha-ps__scene" data-ps-scene>
					<div class="isha-ps__sky"></div>
					<?php
					if ( class_exists( 'ISHA_Core_Shortcodes' ) && method_exists( 'ISHA_Core_Shortcodes', 'lp_waves' ) ) {
						echo ISHA_Core_Shortcodes::lp_waves( 'isha-ps__waves' ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG tự dựng từ số liệu cố định.
					}
					?>
					<div class="isha-ps__shade"></div>
				</div>
				<div class="isha-ps__plane isha-ps__glass" data-ps-plane="glass"></div>
				<div class="isha-ps__plane isha-ps__light" data-ps-plane="light">
					<div class="isha-ps__art"><?php echo $art_img; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image() đã escape. ?></div>
					<div class="isha-ps__edge"></div>
				</div>
			</div>

			<?php /* Chữ màn 1 */ ?>
			<div class="isha-ps__hero" data-ps-hero>
				<div class="isha-ps__col">
					<p class="isha-ps__badge" data-ps-in style="--d:1.1s"><i aria-hidden="true"></i><span><?php echo esc_html( $d['eyebrow'] ); ?></span></p>
					<h1 class="isha-ps__h1" aria-label="<?php echo esc_attr( $d['title'] ); ?>"><?php echo $h1_words; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_ps_words() đã escape. ?></h1>
				</div>
				<?php if ( '' !== $d['lead'] ) : ?>
					<p class="isha-ps__lead" data-ps-in style="--d:1.85s"><?php echo esc_html( $d['lead'] ); ?></p>
				<?php endif; ?>
				<div class="isha-ps__actions" data-ps-in style="--d:1.55s">
					<a class="isha-ps__btn isha-ps__btn--light" href="#isha-chi-tiet"><?php esc_html_e( 'Xem quyền lợi', 'isha' ); ?></a>
					<a class="isha-ps__btn isha-ps__btn--ghost" href="<?php echo esc_url( $d['all_url'] ); ?>"><?php esc_html_e( 'Tất cả hạng thẻ', 'isha' ); ?></a>
				</div>
			</div>

			<?php /* Cổng: viền sáng + lớp lấp đầy. Nằm trên chữ để phủ chữ khi phóng lớn. */ ?>
			<div class="isha-ps__over" data-ps-over aria-hidden="true">
				<div class="isha-ps__plane isha-ps__portal" data-ps-plane="portal"><i class="isha-ps__fill"></i><i class="isha-ps__rim"></i></div>
			</div>

			<?php /* Màn 2 */ ?>
			<section class="isha-ps__ready<?php echo '' === $d['book_url'] ? ' is-nobtn' : ''; ?>" data-ps-ready aria-label="<?php echo esc_attr( $front_alt ); ?>">
				<div class="isha-ps__rword" data-ps-r style="--d:.4s" aria-hidden="true"><div class="isha-ps__word<?php echo $word_text ? ' isha-ps__word--text' : ''; ?>"><?php echo $word_text ? esc_html( get_bloginfo( 'name' ) ) : ''; ?></div></div>

				<div class="isha-ps__card<?php echo $back_img ? ' is-flippable' : ''; ?>" data-ps-card<?php if ( $back_img ) : ?> role="button" tabindex="0" aria-pressed="false" aria-label="<?php echo esc_attr( __( 'Bấm để xem mặt sau của thẻ', 'isha' ) ); ?>" data-label-back="<?php esc_attr_e( 'Bấm để xem mặt sau của thẻ', 'isha' ); ?>" data-label-front="<?php esc_attr_e( 'Bấm để xem mặt trước của thẻ', 'isha' ); ?>"<?php endif; ?>>
					<div class="isha-ps__turn">
						<div class="isha-ps__tilt" data-ps-tilt>
							<div class="isha-ps__float">
								<div class="isha-ps__flip" data-ps-flipper>
									<div class="isha-ps__face isha-ps__face--front"><?php echo $front_img; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
									<?php if ( $back_img ) : ?>
										<div class="isha-ps__face isha-ps__face--back"><?php echo $back_img; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>

				<h2 class="isha-ps__h2" aria-hidden="true">
					<span class="isha-ps__h2-a" data-ps-words="a"><?php echo $h2_top; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="isha-ps__h2-b" data-ps-words="b"><?php echo $h2_bot; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</h2>

				<?php if ( '' !== $d['text'] ) : ?>
					<p class="isha-ps__rtext" data-ps-r style="--d:.65s"><?php echo esc_html( $d['text'] ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $d['book_url'] ) : ?>
					<a class="isha-ps__btn isha-ps__btn--dark isha-ps__rbtn isha-sheen" data-ps-r style="--d:.75s" href="<?php echo esc_url( $d['book_url'] ); ?>"><?php esc_html_e( 'Đặt lịch tư vấn', 'isha' ); ?></a>
				<?php endif; ?>
				<a class="isha-ps__hint" data-ps-r style="--d:.9s" href="#isha-chi-tiet"><?php esc_html_e( 'Cuộn xuống để xem quyền lợi', 'isha' ); ?> <span aria-hidden="true">↓</span></a>
			</section>
		</div>
	</div>
</section>
