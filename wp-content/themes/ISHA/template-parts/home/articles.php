<?php
/**
 * Section Bài viết của trang chủ điện ảnh: bố cục tạp chí. Một bài nổi bật (ảnh 4:5, tiêu đề Garamond, đoạn trích) và danh
 * sách các bài còn lại (tiêu đề, chủ đề, ngày; chủ đề và ngày mỗi thứ một dòng), rồi liên kết "Xem tất cả bài viết".
 *
 * Mọi bài nằm trong một `isha-row` (một danh sách, bài nổi bật là mục đầu). Desktop: CSS xếp bài nổi bật thành khối hai cột
 * (ảnh và chữ) và các bài còn lại thành hàng cột bên dưới. Chế độ gọn: danh sách là hàng vuốt ngang các thẻ. Liên kết ảnh
 * của bài nổi bật chỉ để chạm, ẩn khỏi trình đọc màn hình và thứ tự tab (đã có liên kết ở tiêu đề).
 *
 * Dữ liệu: `$args` là isha_home_data( 'articles' ): title, link_label, archive_url, featured (id, title, excerpt, image_id,
 * url, category, date), list (id, title, url, category, date).
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

// Dữ liệu do shortcode truyền vào qua $args; gọi thẳng template không kèm dữ liệu thì tự lấy.
$isha_data    = ( isset( $args ) && is_array( $args ) && $args ) ? $args : isha_home_data( 'articles' );
$isha_title   = $isha_text( $isha_data, 'title' );
$isha_all     = $isha_text( $isha_data, 'link_label' );
$isha_archive = $isha_text( $isha_data, 'archive_url' );
$isha_first   = ( isset( $isha_data['featured'] ) && is_array( $isha_data['featured'] ) ) ? $isha_data['featured'] : array();

if ( '' === $isha_text( $isha_first, 'title' ) ) {
	return;
}

$isha_feat = array(
	'title'    => $isha_text( $isha_first, 'title' ),
	'excerpt'  => $isha_text( $isha_first, 'excerpt' ),
	'url'      => $isha_text( $isha_first, 'url' ),
	'category' => $isha_text( $isha_first, 'category' ),
	'date'     => $isha_text( $isha_first, 'date' ),
	'image_id' => ( isset( $isha_first['image_id'] ) && is_numeric( $isha_first['image_id'] ) ) ? max( 0, (int) $isha_first['image_id'] ) : 0,
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
		);
	}
}

if ( '' === $isha_title ) {
	$isha_title = __( 'Kiến thức làn da', 'isha' );
}
if ( '' === $isha_all ) {
	$isha_all = __( 'Xem tất cả bài viết', 'isha' );
}

// Ảnh bài nổi bật (4:5, trang trí vì tiêu đề đã gọi tên bài); thiếu ảnh thì khối giữ chỗ cùng tỉ lệ.
$isha_feat_media = isha_home_image(
	$isha_feat['image_id'],
	array(
		'size'       => 'isha-frame',
		'sizes'      => '(max-width: 900px) 340px, 480px',
		'class'      => 'isha-articles__img',
		'decorative' => true,
	)
);
if ( '' === $isha_feat_media ) {
	$isha_feat_media = isha_home_placeholder( __( 'Ảnh bài viết', 'isha' ), array( 'echo' => false ) );
}

isha_home_section_open( 'articles' );
?>
	<div class="isha-wrap">
		<header class="isha-sec__head">
			<h2 id="<?php echo esc_attr( isha_home_title_id( 'articles' ) ); ?>" class="isha-sec__title"><?php echo esc_html( $isha_title ); ?></h2>
		</header>
	</div>

	<div class="isha-row isha-articles__row" data-isha-row>
		<ul class="isha-row__track isha-articles__track" role="list" tabindex="0" aria-label="<?php echo esc_attr( $isha_title ); ?>">
			<li class="isha-row__item isha-articles__item isha-articles__item--featured">
				<article class="isha-articles__card isha-articles__card--featured">
					<div class="isha-articles__media" aria-hidden="true">
						<?php if ( '' !== $isha_feat['url'] ) : ?>
							<a class="isha-articles__media-link" href="<?php echo esc_url( $isha_feat['url'] ); ?>" tabindex="-1"><?php echo $isha_feat_media; // phpcs:ignore WordPress.Security.EscapeOutput -- ảnh do isha_home_image() và khối giữ chỗ do isha_home_placeholder() dựng, đã escape. ?></a>
						<?php else : ?>
							<?php echo $isha_feat_media; // phpcs:ignore WordPress.Security.EscapeOutput -- như trên. ?>
						<?php endif; ?>
					</div>

					<div class="isha-articles__body">
						<?php if ( '' !== $isha_feat['category'] ) : ?>
							<p class="isha-articles__cat isha-label"><?php echo esc_html( $isha_feat['category'] ); ?></p>
						<?php endif; ?>
						<h3 class="isha-articles__title">
							<?php if ( '' !== $isha_feat['url'] ) : ?>
								<a href="<?php echo esc_url( $isha_feat['url'] ); ?>"><?php echo esc_html( $isha_feat['title'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $isha_feat['title'] ); ?>
							<?php endif; ?>
						</h3>
						<?php if ( '' !== $isha_feat['excerpt'] ) : ?>
							<div class="isha-articles__excerpt">
								<?php isha_home_p( $isha_feat['excerpt'], 'isha-text' ); ?>
							</div>
						<?php endif; ?>
						<?php if ( '' !== $isha_feat['date'] ) : ?>
							<p class="isha-articles__date isha-label"><?php echo esc_html( $isha_feat['date'] ); ?></p>
						<?php endif; ?>
					</div>
				</article>
			</li>
			<?php foreach ( $isha_list as $isha_item ) : ?>
				<li class="isha-row__item isha-articles__item">
					<article class="isha-articles__card">
						<?php if ( '' !== $isha_item['category'] ) : ?>
							<p class="isha-articles__cat isha-label"><?php echo esc_html( $isha_item['category'] ); ?></p>
						<?php endif; ?>
						<h3 class="isha-articles__title">
							<?php if ( '' !== $isha_item['url'] ) : ?>
								<a href="<?php echo esc_url( $isha_item['url'] ); ?>"><?php echo esc_html( $isha_item['title'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $isha_item['title'] ); ?>
							<?php endif; ?>
						</h3>
						<?php if ( '' !== $isha_item['date'] ) : ?>
							<div class="isha-articles__meta">
								<?php if ( '' !== $isha_item['date'] ) : ?>
									<p class="isha-articles__date isha-label"><?php echo esc_html( $isha_item['date'] ); ?></p>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</article>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		isha_home_row_controls(
			array(
				'count' => 1 + count( $isha_list ),
				'label' => $isha_title,
			)
		);
		?>
	</div>

	<?php if ( '' !== $isha_archive ) : ?>
		<div class="isha-wrap">
			<p class="isha-articles__all"><a class="isha-link" href="<?php echo esc_url( $isha_archive ); ?>"><?php echo esc_html( $isha_all ); ?></a></p>
		</div>
	<?php endif; ?>
<?php
isha_home_section_close();
