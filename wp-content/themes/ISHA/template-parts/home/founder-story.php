<?php
/**
 * Section Founder (founder-story) của trang chủ: thư viện ảnh 3D chiều sâu và câu chuyện của bác sĩ sáng lập (dựng lại 19/11/2026,
 * đặc tả docs/founder-section-wp.md; ghi chú quyết định ở docs/home-architecture.md mục 21).
 *
 * Tông noir (Soft Dark #141414 full-bleed, đánh bóng Quiet Luxury 19/11/2026). Bố cục (ngược với Dịch vụ tiêu biểu): desktop hai cột
 * 2fr | 1fr, khung gallery bên trái tràn tới mép trái màn hình, không viền hộp (canvas mờ dần ở mép, hoà vào cột chữ); cột phải thẳng mép
 * phải container gồm khối chữ (tiêu đề h2, câu trích, câu chuyện từ CMS) và khối chốt (đường kẻ mảnh + nút "Đặt lịch tư vấn riêng cùng Bác
 * sĩ", mở popup đặt lịch qua [data-isha-booking]). Chế độ gọn: chữ, gallery, rồi khối chốt; tràn hai mép.
 *
 * Khung gallery có hai trạng thái:
 * - Tĩnh (mặc định, không JS, giảm chuyển động, không có WebGL hoặc chưa có ảnh gallery): ảnh chân dung kèm chức danh và tên, dưới là lưới
 *   ảnh; mỗi ảnh là liên kết tới ảnh gốc (JS mở bằng Lightbox).
 * - 3D (assets/js/founder-gallery/ thêm class is-3d): canvas WebGL; lưới ảnh chuyển thành danh sách chỉ dành cho trình đọc màn hình; ảnh
 *   chân dung đặt chính giữa khung, chỉ hiện khi đã đi qua hết ảnh (điểm dừng độc tôn: ảnh gallery tắt hẳn khi chân dung hiện); cụm điều
 *   hướng (bộ đếm "01 — 11", thanh tiến trình, gợi ý cuộn / vuốt) ở góc dưới trái.
 * Chân dung: khung 4:5 nền tối có vầng sáng đồng ấm phía sau chủ thể, viền đồng 1px có vệt ánh kim chạy quanh, chân ảnh mờ dần vào khung.
 * Ảnh tách nền (PNG, WebP, AVIF, GIF: class is-cutout) đặt vừa khung, đứng ở đáy; ảnh JPG (có nền) phủ kín khung.
 * Dữ liệu ảnh cho WebGL nằm trong <script type="application/json">: texture (isha-strip 1600×1100 không cắt; thiếu thì large rồi full),
 * texture điện thoại (large), ảnh gốc cho Lightbox (full), kích thước gốc để giữ tỉ lệ, alt.
 *
 * Dữ liệu: `$args` là isha_home_data( 'founder-story' ): title, portrait (ID ảnh), quote, story, name, role, gallery (ID ảnh[]).
 * Giá trị thô, template tự escape.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_data = ( isset( $args ) && is_array( $args ) && $args ) ? $args : isha_home_data( 'founder-story' );
if ( ! is_array( $isha_data ) || ! $isha_data ) {
	return;
}

// Văn bản một dòng từ trường dữ liệu: chuỗi, gộp khoảng trắng và xuống dòng, đã trim.
$isha_line = static function ( $key ) use ( $isha_data ) {
	$value = ( isset( $isha_data[ $key ] ) && is_scalar( $isha_data[ $key ] ) ) ? (string) $isha_data[ $key ] : '';
	return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
};

$isha_title = $isha_line( 'title' );
if ( '' === $isha_title ) {
	$isha_title = __( 'Câu chuyện nhà sáng lập', 'isha' );
}

// Câu trích: bỏ dấu ngoặc kép người nhập tự gõ ở hai đầu (template tự thêm cặp typographic, ẩn khỏi trình đọc màn hình).
$isha_quote = trim( (string) preg_replace( '/^["\x{201C}\x{201D}\x{201E}\x{201F}\x{00AB}\x{00BB}\s]+|["\x{201C}\x{201D}\x{201E}\x{201F}\x{00AB}\x{00BB}\s]+$/u', '', $isha_line( 'quote' ) ) );
$isha_story = ( isset( $isha_data['story'] ) && is_scalar( $isha_data['story'] ) ) ? (string) $isha_data['story'] : '';
$isha_name  = $isha_line( 'name' );
$isha_role  = $isha_line( 'role' );

// Chức danh mặc định là tiếng Anh ("Medical Director"): đúng bản mặc định thì đánh dấu lang="en" cho trình đọc màn hình (WCAG 3.1.2).
$isha_defaults  = isha_home_defaults( 'founder-story' );
$isha_role_lang = ( '' !== $isha_role && isset( $isha_defaults['role'] ) && $isha_role === $isha_defaults['role'] && ! preg_match( '/[^\x00-\x7F]/', $isha_role ) ) ? ' lang="en"' : '';

// Chân dung: alt lấy từ Thư viện; trống thì "Chân dung <tên>".
$isha_portrait_id  = ( isset( $isha_data['portrait'] ) && is_numeric( $isha_data['portrait'] ) ) ? max( 0, (int) $isha_data['portrait'] ) : 0;
$isha_portrait_alt = $isha_portrait_id > 0 ? trim( (string) get_post_meta( $isha_portrait_id, '_wp_attachment_image_alt', true ) ) : '';
if ( '' === $isha_portrait_alt ) {
	/* translators: %s: tên nhà sáng lập */
	$isha_portrait_alt = '' !== $isha_name ? sprintf( __( 'Chân dung %s', 'isha' ), $isha_name ) : __( 'Chân dung nhà sáng lập', 'isha' );
}
// Ảnh tách nền: định dạng có kênh alpha. JPG luôn có nền nên phủ kín khung thay vì đặt vừa.
$isha_portrait_mime   = $isha_portrait_id > 0 ? (string) get_post_mime_type( $isha_portrait_id ) : '';
$isha_portrait_cutout = in_array( $isha_portrait_mime, array( 'image/png', 'image/webp', 'image/avif', 'image/gif' ), true );

// Nút chốt: liên kết tới trang Đặt lịch hẹn (không JS vẫn dùng được) kèm data-isha-booking để booking.js mở popup tại chỗ.
$isha_contact     = function_exists( 'isha_home_contact' ) ? isha_home_contact() : array();
$isha_booking_url = isset( $isha_contact['booking_url'] ) ? (string) $isha_contact['booking_url'] : '';

// Nguồn ảnh theo thứ tự ưu tiên kích thước: chỉ nhận kích thước có thật trong metadata (WordPress tự trả ảnh gốc khi thiếu kích thước).
$isha_src = static function ( $id, array $sizes ) {
	$meta = wp_get_attachment_metadata( $id );
	foreach ( $sizes as $size ) {
		if ( 'full' !== $size && ! ( is_array( $meta ) && ! empty( $meta['sizes'][ $size ] ) ) ) {
			continue;
		}
		$img = wp_get_attachment_image_src( $id, $size );
		if ( is_array( $img ) && ! empty( $img[0] ) && ! empty( $img[1] ) && ! empty( $img[2] ) ) {
			return array( (string) $img[0], (int) $img[1], (int) $img[2] );
		}
	}
	return null;
};

// Ảnh gallery: bỏ ID không còn là ảnh hoặc thiếu kích thước (ví dụ SVG).
$isha_gallery = array();
$isha_ids     = ( isset( $isha_data['gallery'] ) && is_array( $isha_data['gallery'] ) ) ? $isha_data['gallery'] : array();
foreach ( $isha_ids as $isha_id ) {
	$isha_id = is_numeric( $isha_id ) ? (int) $isha_id : 0;
	if ( $isha_id < 1 || ! wp_attachment_is_image( $isha_id ) ) {
		continue;
	}
	$isha_full = $isha_src( $isha_id, array( 'full' ) );
	$isha_tex  = $isha_src( $isha_id, array( 'isha-strip', 'large', 'full' ) );
	$isha_lite = $isha_src( $isha_id, array( 'large', 'medium_large', 'full' ) );
	if ( ! $isha_full || ! $isha_tex || ! $isha_lite ) {
		continue;
	}
	$isha_alt = trim( (string) get_post_meta( $isha_id, '_wp_attachment_image_alt', true ) );
	if ( '' === $isha_alt ) {
		/* translators: %d: số thứ tự ảnh */
		$isha_alt = sprintf( __( 'Ảnh %d trong thư viện của nhà sáng lập', 'isha' ), count( $isha_gallery ) + 1 );
	}
	$isha_gallery[] = array(
		'id'   => $isha_id,
		'src'  => $isha_tex[0],
		'lite' => $isha_lite[0],
		'full' => $isha_full[0],
		'w'    => $isha_full[1],
		'h'    => $isha_full[2],
		'alt'  => $isha_alt,
	);
}
$isha_count = count( $isha_gallery );

$isha_json = array(
	'images' => array_map(
		static function ( $img ) {
			return array(
				'src'  => $img['src'],
				'lite' => $img['lite'],
				'full' => $img['full'],
				'w'    => $img['w'],
				'h'    => $img['h'],
				'alt'  => $img['alt'],
			);
		},
		$isha_gallery
	),
);

isha_home_section_open( 'founder-story' );
?>
	<div class="isha-founder">
		<div class="isha-founder__body">
			<h2 id="<?php echo esc_attr( isha_home_title_id( 'founder-story' ) ); ?>" class="isha-sec__title isha-founder__title"><?php echo esc_html( $isha_title ); ?></h2>

			<?php if ( '' !== $isha_quote ) : ?>
				<blockquote class="isha-founder__quote">
					<p class="isha-accent isha-founder__quote-text"><span aria-hidden="true">&ldquo;</span><?php echo esc_html( $isha_quote ); ?><span aria-hidden="true">&rdquo;</span></p>
				</blockquote>
			<?php endif; ?>

			<?php if ( '' !== trim( $isha_story ) ) : ?>
				<div class="isha-founder__story" id="isha-founder-text">
					<?php isha_home_p( $isha_story, 'isha-text' ); ?>
				</div>
				<?php // Nút hiện ra khi JS thấy nội dung dài hơn vùng thu gọn (readmore.js); không JS thì cả bài hiển thị đầy đủ. ?>
				<button type="button" class="isha-founder__more" hidden aria-expanded="false" aria-controls="isha-founder-text"
					data-more="<?php echo esc_attr__( 'Đọc trọn vẹn câu chuyện', 'isha' ); ?>" data-less="<?php echo esc_attr__( 'Thu gọn câu chuyện', 'isha' ); ?>">
					<span class="isha-founder__more-text"><?php esc_html_e( 'Đọc trọn vẹn câu chuyện', 'isha' ); ?></span>
					<svg class="isha-founder__more-arrow" viewBox="0 0 12 12" width="12" height="12" aria-hidden="true" focusable="false"><path d="M2 10 10 2M4 2h6v6" fill="none" stroke="currentColor"/></svg>
				</button>
			<?php endif; ?>
		</div>

		<div class="isha-founder__stage" data-isha-founder data-count="<?php echo esc_attr( (string) $isha_count ); ?>"
			data-label="<?php echo esc_attr__( 'Thư viện ảnh của nhà sáng lập. Dùng phím mũi tên trái, phải để xem từng ảnh, phím Enter để phóng to ảnh.', 'isha' ); ?>"
			data-live="<?php /* translators: 1: số thứ tự ảnh, 2: tổng số ảnh, 3: mô tả ảnh */ echo esc_attr__( 'Ảnh %1$s trên %2$s: %3$s', 'isha' ); ?>"
			data-live-end="<?php echo esc_attr( trim( $isha_role . ' ' . $isha_name ) ); ?>">
			<div class="isha-founder__canvas" aria-hidden="true"></div>

			<figure class="isha-founder__portrait">
				<div class="isha-founder__frame isha-r-45<?php echo $isha_portrait_cutout ? ' is-cutout' : ''; ?>">
					<?php
					isha_home_media(
						$isha_portrait_id,
						__( 'Ảnh chân dung nhà sáng lập', 'isha' ),
						array(
							// large (không cắt cứng) để ảnh tách nền giữ nguyên dáng; khung 4:5 tự đặt vừa / phủ kín bằng CSS.
							'size'  => 'large',
							'sizes' => '(max-width: 900px) 52vw, 300px',
							'class' => 'isha-founder__img',
							'alt'   => $isha_portrait_alt,
							'ratio' => '45',
						)
					);
					?>
				</div>
				<?php if ( '' !== $isha_name || '' !== $isha_role ) : ?>
					<figcaption class="isha-founder__caption">
						<?php if ( '' !== $isha_role ) : ?>
							<span class="isha-founder__role"<?php echo $isha_role_lang; // phpcs:ignore WordPress.Security.EscapeOutput -- chuỗi cố định. ?>><?php echo esc_html( $isha_role ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== $isha_name ) : ?>
							<span class="isha-founder__name"><?php echo esc_html( $isha_name ); ?></span>
						<?php endif; ?>
					</figcaption>
				<?php endif; ?>
			</figure>

			<?php if ( $isha_count > 0 ) : ?>
				<ul class="isha-founder__grid">
					<?php foreach ( $isha_gallery as $isha_i => $isha_img ) : ?>
						<li class="isha-founder__cell">
							<a class="isha-founder__item" href="<?php echo esc_url( $isha_img['full'] ); ?>" data-i="<?php echo esc_attr( (string) $isha_i ); ?>">
								<?php
								// phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape.
								echo isha_home_image(
									$isha_img['id'],
									array(
										'size'  => 'medium_large',
										'sizes' => '(max-width: 600px) 46vw, (max-width: 900px) 30vw, 18vw',
										'alt'   => $isha_img['alt'],
									)
								);
								?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php // Cụm điều hướng (chỉ bản 3D): bộ đếm Editorial, thanh tiến trình 1px, gợi ý cuộn / vuốt có icon thở nhẹ. Trang trí: trình đọc màn hình dùng vùng aria-live. ?>
				<div class="isha-founder__hud" aria-hidden="true">
					<span class="isha-founder__count">
						<span class="isha-founder__count-num"><span class="isha-founder__now">01</span><span class="isha-founder__sep">&mdash;</span><span class="isha-founder__total"><?php echo esc_html( sprintf( '%02d', $isha_count ) ); ?></span></span>
						<span class="isha-founder__count-end"><?php esc_html_e( 'Nhà sáng lập', 'isha' ); ?></span>
					</span>
					<span class="isha-founder__bar"><span class="isha-founder__fill"></span></span>
					<span class="isha-founder__hint">
						<span class="isha-founder__hint-fine">
							<svg class="isha-founder__cue" viewBox="0 0 14 22" width="14" height="22" focusable="false"><rect x="0.5" y="0.5" width="13" height="21" rx="6.5" fill="none" stroke="currentColor"/><circle class="isha-founder__cue-dot" cx="7" cy="6.5" r="1.4" fill="currentColor"/></svg>
							<?php esc_html_e( 'Cuộn để khám phá', 'isha' ); ?>
						</span>
						<span class="isha-founder__hint-touch">
							<svg class="isha-founder__cue" viewBox="0 0 30 14" width="30" height="14" focusable="false"><path d="M1 7h28M5 3 1 7l4 4M25 3l4 4-4 4" fill="none" stroke="currentColor"/><circle class="isha-founder__cue-dot isha-founder__cue-dot--x" cx="15" cy="7" r="1.6" fill="currentColor"/></svg>
							<?php esc_html_e( 'Vuốt ngang', 'isha' ); ?>
						</span>
					</span>
				</div>
				<p class="isha-sr isha-founder__live" aria-live="polite"></p>
				<script type="application/json" class="isha-founder__data"><?php echo wp_json_encode( $isha_json, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
			<?php endif; ?>
		</div>

		<?php
		// Có trang Đặt lịch hẹn: liên kết (không JS vẫn tới được trang); không có: nút chỉ mở popup.
		$isha_cta_open = '' !== esc_url( $isha_booking_url )
			? '<a class="isha-btn isha-btn--primary isha-founder__cta" href="' . esc_url( $isha_booking_url ) . '" data-isha-booking="">'
			: '<button type="button" class="isha-btn isha-btn--primary isha-founder__cta" data-isha-booking="">';
		$isha_cta_close = '' !== esc_url( $isha_booking_url ) ? '</a>' : '</button>';
		?>
		<div class="isha-founder__action">
			<?php echo $isha_cta_open; // phpcs:ignore WordPress.Security.EscapeOutput -- dựng ở trên, URL đã qua esc_url. ?>
				<?php esc_html_e( 'Đặt lịch tư vấn riêng cùng Bác sĩ', 'isha' ); ?>
			<?php echo $isha_cta_close; // phpcs:ignore WordPress.Security.EscapeOutput -- thẻ đóng cố định. ?>
		</div>
	</div>

	<?php if ( $isha_count > 0 ) : ?>
		<dialog class="isha-founder-lb" aria-label="<?php esc_attr_e( 'Ảnh phóng to', 'isha' ); ?>">
			<button type="button" class="isha-founder-lb__close" aria-label="<?php esc_attr_e( 'Đóng', 'isha' ); ?>"><span aria-hidden="true">&times;</span></button>
		</dialog>
	<?php endif; ?>
<?php
isha_home_section_close();
