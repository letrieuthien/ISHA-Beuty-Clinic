<?php
/**
 * Section Dịch vụ của trang chủ điện ảnh: tiêu đề, câu dẫn, rồi mỗi nhóm dịch vụ là một khung `article` gồm ảnh bìa dọc 4:5
 * (xen kẽ trái/phải ở desktop), tên nhóm, tagline, danh sách liệu trình (mỗi dòng một liên kết, liệu trình nháp có nhãn
 * "Nháp") và liên kết ghost "Đặt lịch tư vấn riêng". Nhóm chưa có liệu trình chỉ có tagline và nút.
 *
 * Ảnh trước/sau điều trị (groups[].pairs): nhóm có ít nhất một cặp hợp lệ thì khung 4:5 hiện cặp ảnh thay cho ảnh bìa, chia đôi
 * ở giữa (trước bên trái, sau bên phải; mỗi lớp mang nhãn Trước/Sau, mỗi cặp mang chú thích riêng). Thanh kéo (input range) và chấm
 * chọn cặp do PHP in sẵn ở dạng hidden, home.js bỏ hidden và nối sự kiện; không có JS thì chỉ có cặp đầu ở dạng chia đôi tĩnh. Ghi chú
 * miễn trừ (ba_note) in dưới danh sách liệu trình của nhóm có cặp ảnh.
 *
 * Trạng thái TĨNH. Desktop: các khung xếp dọc. Chế độ gọn: hàng vuốt ngang `isha-row`, mỗi khung ~88vw (nút Trước/Sau và chỉ
 * báo do home.js bật khi hàng tràn). `nav.isha-services__index` (nút Trước, 6 liên kết neo dạng vạch, nút Sau) có sẵn nhưng ẩn
 * (hidden); home.js chỉ bỏ hidden ở desktop có chuyển động: không ghim, không chạy theo cuộn chuột, khung đổi khi bấm Trước/Sau hoặc vạch (11/11/2026). Hàng và chỉ mục nằm trong
 * `.isha-services__pin[data-isha-track] > .isha-services__stage[data-isha-stage]`: hai khối này chỉ có kiểu khi home.js thêm
 * class `is-live` cho section (desktop, chuyển động bật), còn lại là khối thường. Video bìa (cover_video_id) chưa dùng.
 *
 * Dữ liệu: `$args` = isha_home_data( 'services' ) (giá trị thô, template tự escape).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_services = ( isset( $args ) && is_array( $args ) ) ? $args : array();

$isha_title = ( isset( $isha_services['title'] ) && is_scalar( $isha_services['title'] ) ) ? trim( (string) $isha_services['title'] ) : '';
if ( '' === $isha_title ) {
	$isha_title = __( 'Dịch vụ', 'isha' );
}
$isha_lead = ( isset( $isha_services['lead'] ) && is_scalar( $isha_services['lead'] ) ) ? trim( (string) $isha_services['lead'] ) : '';
$isha_note = ( isset( $isha_services['ba_note'] ) && is_scalar( $isha_services['ba_note'] ) ) ? trim( (string) $isha_services['ba_note'] ) : '';

// Chuẩn hoá danh sách nhóm: bỏ nhóm không có tên, id neo duy nhất theo slug (hoặc số thứ tự).
$isha_groups = array();
$isha_used   = array();
if ( isset( $isha_services['groups'] ) && is_array( $isha_services['groups'] ) ) {
	foreach ( $isha_services['groups'] as $isha_raw ) {
		if ( ! is_array( $isha_raw ) ) {
			continue;
		}
		$isha_name = ( isset( $isha_raw['name'] ) && is_scalar( $isha_raw['name'] ) ) ? trim( (string) $isha_raw['name'] ) : '';
		if ( '' === $isha_name ) {
			continue;
		}
		$isha_slug = ( isset( $isha_raw['slug'] ) && is_scalar( $isha_raw['slug'] ) ) ? sanitize_title( (string) $isha_raw['slug'] ) : '';
		$isha_key  = '' !== $isha_slug ? $isha_slug : (string) ( count( $isha_groups ) + 1 );
		while ( isset( $isha_used[ $isha_key ] ) ) {
			$isha_key .= '-' . ( count( $isha_groups ) + 1 );
		}
		$isha_used[ $isha_key ] = true;

		$isha_items = array();
		if ( isset( $isha_raw['items'] ) && is_array( $isha_raw['items'] ) ) {
			foreach ( $isha_raw['items'] as $isha_raw_item ) {
				$isha_item_title = ( is_array( $isha_raw_item ) && isset( $isha_raw_item['title'] ) && is_scalar( $isha_raw_item['title'] ) ) ? trim( (string) $isha_raw_item['title'] ) : '';
				if ( '' === $isha_item_title ) {
					continue;
				}
				$isha_items[] = array(
					'title' => $isha_item_title,
					'url'   => ( isset( $isha_raw_item['url'] ) && is_scalar( $isha_raw_item['url'] ) ) ? (string) $isha_raw_item['url'] : '',
					'draft' => ! empty( $isha_raw_item['draft'] ),
				);
			}
		}

		// Cặp ảnh trước/sau: dựng sẵn thẻ img (alt nêu rõ trước/sau và nhóm); cặp có ảnh không dùng được thì bỏ.
		$isha_pairs = array();
		if ( isset( $isha_raw['pairs'] ) && is_array( $isha_raw['pairs'] ) ) {
			foreach ( $isha_raw['pairs'] as $isha_raw_pair ) {
				if ( ! is_array( $isha_raw_pair ) || count( $isha_pairs ) >= 6 ) {
					continue;
				}
				$isha_no   = count( $isha_pairs ) + 1;
				$isha_args = array(
					'size'  => 'isha-frame',
					'sizes' => '(max-width: 900px) 88vw, 600px',
				);
				/* translators: 1: tên nhóm dịch vụ, 2: số thứ tự cặp ảnh */
				$isha_before = isha_home_image( isset( $isha_raw_pair['before'] ) ? $isha_raw_pair['before'] : 0, $isha_args + array( 'alt' => sprintf( __( 'Ảnh trước điều trị, %1$s, cặp %2$d', 'isha' ), $isha_name, $isha_no ) ) );
				/* translators: 1: tên nhóm dịch vụ, 2: số thứ tự cặp ảnh */
				$isha_after = isha_home_image( isset( $isha_raw_pair['after'] ) ? $isha_raw_pair['after'] : 0, $isha_args + array( 'alt' => sprintf( __( 'Ảnh sau điều trị, %1$s, cặp %2$d', 'isha' ), $isha_name, $isha_no ) ) );
				if ( '' === $isha_before || '' === $isha_after ) {
					continue;
				}
				$isha_pairs[] = array(
					'before'  => $isha_before,
					'after'   => $isha_after,
					'caption' => ( isset( $isha_raw_pair['caption'] ) && is_scalar( $isha_raw_pair['caption'] ) ) ? trim( (string) $isha_raw_pair['caption'] ) : '',
				);
			}
		}

		$isha_groups[] = array(
			'key'     => $isha_key,
			'name'    => $isha_name,
			'tagline' => ( isset( $isha_raw['tagline'] ) && is_scalar( $isha_raw['tagline'] ) ) ? trim( (string) $isha_raw['tagline'] ) : '',
			'cover'   => ( isset( $isha_raw['cover_id'] ) && is_numeric( $isha_raw['cover_id'] ) ) ? max( 0, (int) $isha_raw['cover_id'] ) : 0,
			'pairs'   => $isha_pairs,
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
	<ul class="isha-row__track isha-services__track" tabindex="0" aria-label="<?php esc_attr_e( 'Các nhóm dịch vụ', 'isha' ); ?>">
		<?php foreach ( $isha_groups as $isha_group ) : ?>
			<?php $isha_frame_id = 'isha-service-' . $isha_group['key']; ?>
			<li class="isha-row__item isha-services__item">
				<article id="<?php echo esc_attr( $isha_frame_id ); ?>" class="isha-services__frame" aria-labelledby="<?php echo esc_attr( $isha_frame_id . '-name' ); ?>">
					<div class="isha-services__media isha-r-45">
						<?php if ( $isha_group['pairs'] ) : ?>
							<div class="isha-ba" data-isha-ba>
								<?php foreach ( $isha_group['pairs'] as $isha_k => $isha_pair ) : ?>
									<figure class="isha-ba__pair"<?php echo 0 === $isha_k ? '' : ' hidden'; ?>>
										<div class="isha-ba__layer isha-ba__layer--before">
											<?php echo $isha_pair['before']; // phpcs:ignore WordPress.Security.EscapeOutput -- thẻ img do isha_home_image() dựng và đã escape. ?>
											<span class="isha-label isha-ba__tag" aria-hidden="true"><?php esc_html_e( 'Trước', 'isha' ); ?></span>
										</div>
										<div class="isha-ba__layer isha-ba__layer--after">
											<?php echo $isha_pair['after']; // phpcs:ignore WordPress.Security.EscapeOutput -- thẻ img do isha_home_image() dựng và đã escape. ?>
											<span class="isha-label isha-ba__tag" aria-hidden="true"><?php esc_html_e( 'Sau', 'isha' ); ?></span>
										</div>
										<?php if ( '' !== $isha_pair['caption'] ) : ?>
											<figcaption class="isha-ba__cap"><?php echo esc_html( $isha_pair['caption'] ); ?></figcaption>
										<?php endif; ?>
									</figure>
								<?php endforeach; ?>
								<span class="isha-ba__line" aria-hidden="true"></span>
								<input class="isha-ba__range" type="range" min="0" max="100" step="1" value="50" aria-label="<?php esc_attr_e( 'Kéo để so sánh ảnh trước và sau điều trị', 'isha' ); ?>" hidden>
								<?php if ( count( $isha_group['pairs'] ) > 1 ) : ?>
									<div class="isha-ba__pager" role="group" aria-label="<?php esc_attr_e( 'Chọn cặp ảnh', 'isha' ); ?>" hidden>
										<?php foreach ( $isha_group['pairs'] as $isha_k => $isha_pair ) : ?>
											<button type="button" class="isha-ba__dot" aria-label="<?php echo esc_attr( sprintf( __( 'Cặp ảnh %d', 'isha' ), $isha_k + 1 ) ); ?>" aria-current="<?php echo 0 === $isha_k ? 'true' : 'false'; ?>"></button>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php else : ?>
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
						<?php endif; ?>
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
						<?php if ( $isha_group['pairs'] && '' !== $isha_note ) : ?>
							<p class="isha-note isha-services__ba-note"><?php echo esc_html( $isha_note ); ?></p>
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
<?php
isha_home_section_close();
