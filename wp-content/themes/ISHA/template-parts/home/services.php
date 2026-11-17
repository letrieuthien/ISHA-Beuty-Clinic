<?php
/**
 * Section Dịch vụ tiêu biểu của trang chủ điện ảnh: tiêu đề, câu dẫn, rồi mỗi nhóm dịch vụ được tick "Dịch vụ tiêu biểu" là một khung
 * `article` gồm ảnh bìa dọc 4:5 (xen kẽ trái/phải ở desktop), tên nhóm, tagline, danh sách liệu trình (mỗi dòng một liên kết, liệu trình
 * nháp có nhãn "Nháp") và liên kết ghost "Đặt lịch tư vấn riêng". Nhóm chưa có liệu trình chỉ có tagline và nút. Cuối section là liên kết
 * "Xem tất cả dịch vụ" tới trang Dịch vụ (khi có trang dùng mẫu "Dịch vụ").
 *
 * Ảnh trước/sau của nhóm không còn hiện ở đây từ 17/11/2026 (trang chủ có section Hiệu quả liệu trình riêng, template-parts/home/results.php).
 *
 * Trạng thái TĨNH. Desktop: các khung xếp dọc. Chế độ gọn: hàng vuốt ngang `isha-row`, mỗi khung ~88vw (nút Trước/Sau và chỉ
 * báo do home.js bật khi hàng tràn). `nav.isha-services__index` (nút Trước, liên kết neo dạng vạch, nút Sau) có sẵn nhưng ẩn
 * (hidden); home.js chỉ bỏ hidden ở desktop có chuyển động: không ghim, không chạy theo cuộn chuột, khung đổi khi bấm Trước/Sau hoặc vạch (11/11/2026). Hàng và chỉ mục nằm trong
 * `.isha-services__pin[data-isha-track] > .isha-services__stage[data-isha-stage]`: hai khối này chỉ có kiểu khi home.js thêm
 * class `is-live` cho section (desktop, chuyển động bật), còn lại là khối thường. Video bìa (cover_video_id) chưa dùng.
 *
 * Dữ liệu: `$args` = isha_home_data( 'services' ) (giá trị thô, template tự escape): title, lead, link_label, all_url, groups.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_services = ( isset( $args ) && is_array( $args ) ) ? $args : array();

// Lấy một giá trị chuỗi (đã cắt khoảng trắng) từ một mảng; không phải chuỗi/số thì trả ''.
$isha_text = static function ( $source, $key ) {
	return ( is_array( $source ) && isset( $source[ $key ] ) && is_scalar( $source[ $key ] ) ) ? trim( (string) $source[ $key ] ) : '';
};

$isha_title = $isha_text( $isha_services, 'title' );
if ( '' === $isha_title ) {
	$isha_title = __( 'Dịch vụ tiêu biểu', 'isha' );
}
$isha_lead     = $isha_text( $isha_services, 'lead' );
$isha_all_url  = $isha_text( $isha_services, 'all_url' );
$isha_all_text = $isha_text( $isha_services, 'link_label' );
if ( '' === $isha_all_text ) {
	$isha_all_text = __( 'Xem tất cả dịch vụ', 'isha' );
}

// Chuẩn hoá danh sách nhóm: bỏ nhóm không có tên, id neo duy nhất theo slug (hoặc số thứ tự).
$isha_groups = array();
$isha_used   = array();
if ( isset( $isha_services['groups'] ) && is_array( $isha_services['groups'] ) ) {
	foreach ( $isha_services['groups'] as $isha_raw ) {
		$isha_name = $isha_text( $isha_raw, 'name' );
		if ( '' === $isha_name ) {
			continue;
		}
		$isha_slug = sanitize_title( $isha_text( $isha_raw, 'slug' ) );
		$isha_key  = '' !== $isha_slug ? $isha_slug : (string) ( count( $isha_groups ) + 1 );
		while ( isset( $isha_used[ $isha_key ] ) ) {
			$isha_key .= '-' . ( count( $isha_groups ) + 1 );
		}
		$isha_used[ $isha_key ] = true;

		$isha_items = array();
		if ( isset( $isha_raw['items'] ) && is_array( $isha_raw['items'] ) ) {
			foreach ( $isha_raw['items'] as $isha_raw_item ) {
				$isha_item_title = $isha_text( $isha_raw_item, 'title' );
				if ( '' === $isha_item_title ) {
					continue;
				}
				$isha_items[] = array(
					'title' => $isha_item_title,
					'url'   => $isha_text( $isha_raw_item, 'url' ),
					'draft' => ! empty( $isha_raw_item['draft'] ),
				);
			}
		}

		$isha_groups[] = array(
			'key'     => $isha_key,
			'name'    => $isha_name,
			'tagline' => $isha_text( $isha_raw, 'tagline' ),
			'cover'   => ( isset( $isha_raw['cover_id'] ) && is_numeric( $isha_raw['cover_id'] ) ) ? max( 0, (int) $isha_raw['cover_id'] ) : 0,
			'items'   => $isha_items,
		);
	}
}

if ( ! $isha_groups ) {
	return;
}

$isha_contact  = isha_home_contact();
$isha_book_url = isset( $isha_contact['booking_url'] ) ? (string) $isha_contact['booking_url'] : '';

isha_home_section_open( 'services' );
?>
<div class="isha-wrap">
	<div class="isha-sec__head">
		<h2 id="<?php echo esc_attr( isha_home_title_id( 'services' ) ); ?>" class="isha-sec__title"><?php echo esc_html( $isha_title ); ?></h2>
		<?php if ( '' !== $isha_lead ) : ?>
			<p class="isha-lead"><?php echo esc_html( $isha_lead ); ?></p>
		<?php endif; ?>
	</div>
</div>

<div class="isha-services__pin" data-isha-track>
<div class="isha-services__stage" data-isha-stage>
<div class="isha-row isha-services__row" data-isha-row>
	<ul class="isha-row__track isha-services__track" tabindex="0" aria-label="<?php esc_attr_e( 'Các nhóm dịch vụ tiêu biểu', 'isha' ); ?>">
		<?php foreach ( $isha_groups as $isha_group ) : ?>
			<?php $isha_frame_id = 'isha-service-' . $isha_group['key']; ?>
			<li class="isha-row__item isha-services__item">
				<article id="<?php echo esc_attr( $isha_frame_id ); ?>" class="isha-services__frame" aria-labelledby="<?php echo esc_attr( $isha_frame_id . '-name' ); ?>">
					<div class="isha-services__media isha-r-45">
						<?php
						isha_home_media(
							$isha_group['cover'],
							__( 'Ảnh bìa nhóm dịch vụ', 'isha' ),
							array(
								'size'  => 'isha-frame',
								'sizes' => '(max-width: 900px) 88vw, 600px',
								'class' => 'isha-services__img',
								'ratio' => '45',
							)
						);
						?>
					</div>
					<div class="isha-services__body">
						<h3 id="<?php echo esc_attr( $isha_frame_id . '-name' ); ?>" class="isha-accent isha-services__name"><?php echo esc_html( $isha_group['name'] ); ?></h3>
						<?php if ( '' !== $isha_group['tagline'] ) : ?>
							<p class="isha-note isha-services__tagline"><?php echo esc_html( $isha_group['tagline'] ); ?></p>
						<?php endif; ?>
						<?php if ( $isha_group['items'] ) : ?>
							<ul class="isha-services__list">
								<?php foreach ( $isha_group['items'] as $isha_item ) : ?>
									<?php $isha_item_url = esc_url( $isha_item['url'] ); ?>
									<li class="isha-services__entry">
										<?php if ( '' !== $isha_item_url ) : ?>
											<a class="isha-services__link" href="<?php echo $isha_item_url; // phpcs:ignore WordPress.Security.EscapeOutput -- đã qua esc_url ở trên. ?>">
												<span class="isha-services__title"><?php echo esc_html( $isha_item['title'] ); ?></span>
												<?php if ( $isha_item['draft'] ) : ?>
													<span class="isha-label isha-services__draft"><?php esc_html_e( 'Nháp', 'isha' ); ?></span>
												<?php endif; ?>
											</a>
										<?php else : ?>
											<span class="isha-services__link">
												<span class="isha-services__title"><?php echo esc_html( $isha_item['title'] ); ?></span>
												<?php if ( $isha_item['draft'] ) : ?>
													<span class="isha-label isha-services__draft"><?php esc_html_e( 'Nháp', 'isha' ); ?></span>
												<?php endif; ?>
											</span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<div class="isha-services__actions">
							<?php isha_home_button( __( 'Đặt lịch tư vấn riêng', 'isha' ), $isha_book_url, 'ghost' ); ?>
						</div>
					</div>
				</article>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
	isha_home_row_controls(
		array(
			'count' => count( $isha_groups ),
			'label' => __( 'Điều khiển danh sách dịch vụ', 'isha' ),
		)
	);
	?>
</div>

<?php
$isha_pager_links = array();
foreach ( $isha_groups as $isha_group ) {
	$isha_pager_links[] = array(
		'href'  => '#isha-service-' . $isha_group['key'],
		'label' => $isha_group['name'],
	);
}
isha_home_pager(
	array(
		'label' => __( 'Chỉ mục nhóm dịch vụ', 'isha' ),
		'links' => $isha_pager_links,
		'class' => 'isha-services__pager',
	)
);
?>
</div>
</div>

<?php if ( '' !== $isha_all_url ) : ?>
	<div class="isha-wrap">
		<p class="isha-services__all"><a class="isha-link" href="<?php echo esc_url( $isha_all_url ); ?>"><?php echo esc_html( $isha_all_text ); ?></a></p>
	</div>
<?php endif; ?>
<?php
isha_home_section_close();
