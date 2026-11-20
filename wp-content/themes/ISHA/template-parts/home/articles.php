<?php
/**
 * Section Tin tức của trang chủ điện ảnh: "Dark Cinematic Editorial Grid" (20/11/2026, yêu cầu gốc docs/news.md).
 *
 * Nền tối noir (#141414) như Founder. Hàng tiêu đề: dòng nhỏ (eyebrow, CMS) và tiêu đề bên trái, liên kết "Xem tất cả tin tức" bên
 * phải (chế độ gọn: liên kết xuống cuối section). Bài nổi bật: ảnh 16:9 bên trái (~47%, thu từ 55%/16:10 ngày 20/11/2026 để section
 * đỡ cao), chữ bên phải căn giữa theo chiều dọc (chủ đề, tiêu đề, đoạn trích 3 dòng, ngày, nút "Đọc bài viết"). Chỉ nút "Đọc bài viết"
 * là liên kết: ảnh và tiêu đề của bài nổi bật không bấm được (quyết định của người dùng 20/11/2026). Đường kẻ mảnh, rồi lưới 3 bài phụ
 * có ảnh 16:9 (đổi từ 4:3 cùng ngày): mỗi thẻ là MỘT liên kết (liên kết ở tiêu đề phủ cả thẻ), không có liên kết thứ hai. Chuyên mục và
 * ngày của 3 bài phụ gộp một dòng phía trên tiêu đề ("CHUYÊN MỤC · NGÀY"), không còn vạch ngắn dưới chuyên mục (gọn và đỡ cao hơn).
 *
 * Bố cục: section là lưới có cột tràn mép (full) và cột nội dung thẳng hàng với .isha-wrap (CSS khối articles), nên hàng vuốt của
 * chế độ gọn tràn hai mép mà liên kết "Xem tất cả" vẫn là một phần tử duy nhất (chỉ đổi vị trí bằng CSS).
 *
 * Ảnh: bài nổi bật dùng Ảnh banner (`banner_id`, kích thước isha-banner, gốc 16:10 nhưng khung hiện 16:9 nên cắt thêm theo chiều cao),
 * chưa có thì Ảnh đại diện (`image_id`, kích thước large, cắt giữa vào khung). Bài phụ dùng Ảnh đại diện, kích thước isha-card (gốc
 * 4:3, khung hiện 16:9 nên cắt thêm theo chiều cao). Thiếu ảnh thì khối giữ chỗ tối cùng tỉ lệ.
 *
 * Dữ liệu: `$args` là isha_home_data( 'articles' ): eyebrow, title, link_label, archive_url, featured (id, title, excerpt, image_id,
 * banner_id, url, category, date), list (id, title, url, category, date, image_id).
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
// Lấy một số nguyên không âm từ một mảng.
$isha_int = static function ( $source, $key ) {
	return ( is_array( $source ) && isset( $source[ $key ] ) && is_numeric( $source[ $key ] ) ) ? max( 0, (int) $source[ $key ] ) : 0;
};

// Dữ liệu do shortcode truyền vào qua $args; gọi thẳng template không kèm dữ liệu thì tự lấy.
$isha_data    = ( isset( $args ) && is_array( $args ) && $args ) ? $args : isha_home_data( 'articles' );
$isha_eyebrow = $isha_text( $isha_data, 'eyebrow' );
$isha_title   = $isha_text( $isha_data, 'title' );
$isha_all     = $isha_text( $isha_data, 'link_label' );
$isha_archive = $isha_text( $isha_data, 'archive_url' );
$isha_first   = ( isset( $isha_data['featured'] ) && is_array( $isha_data['featured'] ) ) ? $isha_data['featured'] : array();

if ( '' === $isha_text( $isha_first, 'title' ) ) {
	return;
}

$isha_feat = array(
	'title'     => $isha_text( $isha_first, 'title' ),
	// Đoạn trích là một đoạn (CSS cắt 3 dòng): gộp xuống dòng thành dấu cách.
	'excerpt'   => trim( (string) preg_replace( '/\s+/u', ' ', $isha_text( $isha_first, 'excerpt' ) ) ),
	'url'       => $isha_text( $isha_first, 'url' ),
	'category'  => $isha_text( $isha_first, 'category' ),
	'date'      => $isha_text( $isha_first, 'date' ),
	'image_id'  => $isha_int( $isha_first, 'image_id' ),
	'banner_id' => $isha_int( $isha_first, 'banner_id' ),
);

$isha_list = array();
if ( isset( $isha_data['list'] ) && is_array( $isha_data['list'] ) ) {
	foreach ( $isha_data['list'] as $isha_row ) {
		if ( '' === $isha_text( $isha_row, 'title' ) ) {
			continue;
		}
		$isha_list[] = array(
			'title'    => $isha_text( $isha_row, 'title' ),
			'url'      => $isha_text( $isha_row, 'url' ),
			'category' => $isha_text( $isha_row, 'category' ),
			'date'     => $isha_text( $isha_row, 'date' ),
			'image_id' => $isha_int( $isha_row, 'image_id' ),
		);
	}
}

if ( '' === $isha_title ) {
	$isha_title = __( 'Tin tức', 'isha' );
}
if ( '' === $isha_all ) {
	$isha_all = __( 'Xem tất cả tin tức', 'isha' );
}

// Mũi tên mảnh dùng chung (trang trí; chữ của liên kết đã đủ nghĩa).
$isha_arrow = '<svg class="isha-articles__arrow" viewBox="0 0 32 12" width="32" height="12" aria-hidden="true" focusable="false"><path d="M0 6h30M25 1l5 5-5 5" fill="none" stroke="currentColor" stroke-width="1"/></svg>';

// Ảnh bài nổi bật: banner 16:10, không có thì ảnh đại diện; trang trí vì tiêu đề đã gọi tên bài.
$isha_feat_media = '';
if ( $isha_feat['banner_id'] > 0 ) {
	$isha_feat_media = isha_home_image(
		$isha_feat['banner_id'],
		array(
			'size'       => 'isha-banner',
			'sizes'      => '(max-width: 900px) 92vw, 520px',
			'class'      => 'isha-articles__img',
			'decorative' => true,
		)
	);
}
if ( '' === $isha_feat_media ) {
	$isha_feat_media = isha_home_image(
		$isha_feat['image_id'],
		array(
			'size'       => 'large',
			'sizes'      => '(max-width: 900px) 92vw, 520px',
			'class'      => 'isha-articles__img',
			'decorative' => true,
		)
	);
}
if ( '' === $isha_feat_media ) {
	$isha_feat_media = isha_home_placeholder( __( 'Ảnh banner bài viết (16:10)', 'isha' ), array( 'echo' => false ) );
}

isha_home_section_open( 'articles' );
?>
	<header class="isha-articles__head" data-isha-reveal>
		<?php if ( '' !== $isha_eyebrow ) : ?>
			<p class="isha-label isha-articles__eyebrow"><?php echo esc_html( $isha_eyebrow ); ?></p>
		<?php endif; ?>
		<h2 id="<?php echo esc_attr( isha_home_title_id( 'articles' ) ); ?>" class="isha-sec__title isha-articles__heading"><?php echo esc_html( $isha_title ); ?></h2>
	</header>

	<?php if ( '' !== $isha_archive ) : ?>
		<p class="isha-articles__all" data-isha-reveal>
			<a class="isha-btn isha-btn--ghost isha-articles__more" href="<?php echo esc_url( $isha_archive ); ?>"><span><?php echo esc_html( $isha_all ); ?></span><?php echo $isha_arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG tĩnh. ?></a>
		</p>
	<?php endif; ?>

	<article class="isha-articles__feature" data-isha-reveal>
		<div class="isha-articles__frame isha-articles__frame--wide" aria-hidden="true">
			<?php echo $isha_feat_media; // phpcs:ignore WordPress.Security.EscapeOutput -- ảnh do isha_home_image() và khối giữ chỗ do isha_home_placeholder() dựng, đã escape. ?>
		</div>

		<div class="isha-articles__body">
			<?php if ( '' !== $isha_feat['category'] ) : ?>
				<p class="isha-label isha-articles__cat"><?php echo esc_html( $isha_feat['category'] ); ?></p>
			<?php endif; ?>
			<h3 class="isha-articles__title isha-articles__title--lead"><?php echo esc_html( $isha_feat['title'] ); ?></h3>
			<?php if ( '' !== $isha_feat['excerpt'] ) : ?>
				<p class="isha-text isha-articles__excerpt"><?php echo esc_html( $isha_feat['excerpt'] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $isha_feat['date'] ) : ?>
				<p class="isha-label isha-articles__date"><?php echo esc_html( $isha_feat['date'] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $isha_feat['url'] ) : ?>
				<a class="isha-btn isha-btn--primary isha-articles__cta" href="<?php echo esc_url( $isha_feat['url'] ); ?>"><span><?php esc_html_e( 'Đọc bài viết', 'isha' ); ?></span><span class="isha-sr">: <?php echo esc_html( $isha_feat['title'] ); ?></span><?php echo $isha_arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG tĩnh. ?></a>
			<?php endif; ?>
		</div>
	</article>

	<?php if ( $isha_list ) : ?>
		<div class="isha-articles__rule" aria-hidden="true"></div>

		<div class="isha-row isha-articles__row" data-isha-row data-isha-reveal>
			<ul class="isha-row__track isha-articles__track" role="list" tabindex="0" aria-label="<?php echo esc_attr__( 'Tin tức khác', 'isha' ); ?>">
				<?php foreach ( $isha_list as $isha_item ) : ?>
					<?php
					$isha_thumb = isha_home_image(
						$isha_item['image_id'],
						array(
							'size'       => 'isha-card',
							'sizes'      => '(max-width: 900px) 82vw, 360px',
							'class'      => 'isha-articles__img',
							'decorative' => true,
						)
					);
					if ( '' === $isha_thumb ) {
						$isha_thumb = isha_home_placeholder( __( 'Ảnh đại diện bài viết', 'isha' ), array( 'echo' => false ) );
					}
					?>
					<li class="isha-row__item isha-articles__item">
						<article class="isha-articles__card">
							<div class="isha-articles__frame isha-articles__frame--card" aria-hidden="true">
								<?php echo $isha_thumb; // phpcs:ignore WordPress.Security.EscapeOutput -- như trên. ?>
							</div>
							<?php
							// Gộp chuyên mục và ngày thành một dòng gọn phía trên tiêu đề (20/11/2026, tối ưu chiều cao): bỏ vạch ngắn dưới
							// chuyên mục và dòng ngày riêng ở đáy thẻ của bản cũ.
							$isha_meta = implode( ' · ', array_filter( array( $isha_item['category'], $isha_item['date'] ), 'strlen' ) );
							?>
							<?php if ( '' !== $isha_meta ) : ?>
								<p class="isha-label isha-articles__meta"><?php echo esc_html( $isha_meta ); ?></p>
							<?php endif; ?>
							<h3 class="isha-articles__title">
								<?php if ( '' !== $isha_item['url'] ) : ?>
									<a class="isha-articles__link" href="<?php echo esc_url( $isha_item['url'] ); ?>"><?php echo esc_html( $isha_item['title'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $isha_item['title'] ); ?>
								<?php endif; ?>
							</h3>
						</article>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
<?php
isha_home_section_close();
