<?php
/**
 * Section Dịch vụ tiêu biểu của trang chủ điện ảnh: "Bánh xe tĩnh lặng" (The Quiet Wheel).
 *
 * Bố cục: trên cùng là tiêu đề nhỏ của section, câu dẫn và hàng tab nhóm dịch vụ (Montserrat mảnh). Dưới đó, bên trái (1/3) là khối chữ
 * đứng yên của nhóm đang xem (số thứ tự, tên nhóm Garamond lớn, vạch vàng, mô tả, số liệu trình, "Khám phá nhóm", "Xem tất cả dịch vụ");
 * bên phải (2/3) là MỘT vòng xoay 3D lồi chứa toàn bộ liệu trình của các nhóm được tick "Dịch vụ tiêu biểu", xếp liền theo nhóm. Mỗi thẻ là
 * một khung tranh viền vàng ánh kim (vòng cung ánh kim dùng chung, assets/js/card-ring/, biến thể một mặt .isha-ring--solo): ảnh phủ kín,
 * lớp kính mờ mỏng, dải kính ngà ở đáy chứa tên nhóm và tên liệu trình; thẻ ở giữa (được chọn) mở ra nút "Đặt lịch tư vấn" (popup đặt lịch,
 * chọn sẵn liệu trình) và liên kết "Xem chi tiết".
 *
 * Tab là liên kết thật tới trang nhóm (không JS: mở trang nhóm); có JS: bấm tab thì vòng quay nhanh tới đoạn của nhóm đó, khối chữ đổi
 * theo; khi vòng tự trôi, khối chữ và tab tự theo nhóm của thẻ đang ở giữa. JS: assets/js/home-wheel/main.js (script module, nạp khi
 * section sắp vào khung nhìn). Không JS / giảm chuyển động: thẻ là hàng cuộn ngang, khối chữ hiện nhóm đầu.
 *
 * Dữ liệu: `$args` = isha_home_data( 'services' ) (giá trị thô, template tự escape): title, lead, link_label, all_url, groups (id, slug,
 * name, tagline, url, items: id, title, url, draft, thumb).
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

// Chuẩn hoá danh sách nhóm (bỏ nhóm không tên) và liệu trình (bỏ liệu trình không tên).
$isha_groups = array();
if ( isset( $isha_services['groups'] ) && is_array( $isha_services['groups'] ) ) {
	foreach ( $isha_services['groups'] as $isha_raw ) {
		$isha_name = $isha_text( $isha_raw, 'name' );
		if ( '' === $isha_name ) {
			continue;
		}
		$isha_items = array();
		if ( isset( $isha_raw['items'] ) && is_array( $isha_raw['items'] ) ) {
			foreach ( $isha_raw['items'] as $isha_raw_item ) {
				$isha_item_title = $isha_text( $isha_raw_item, 'title' );
				if ( '' === $isha_item_title ) {
					continue;
				}
				$isha_items[] = array(
					'id'    => ( isset( $isha_raw_item['id'] ) && is_numeric( $isha_raw_item['id'] ) ) ? (int) $isha_raw_item['id'] : 0,
					'title' => $isha_item_title,
					'url'   => $isha_text( $isha_raw_item, 'url' ),
					'draft' => ! empty( $isha_raw_item['draft'] ),
					'thumb' => ( isset( $isha_raw_item['thumb'] ) && is_numeric( $isha_raw_item['thumb'] ) ) ? (int) $isha_raw_item['thumb'] : 0,
				);
			}
		}
		$isha_groups[] = array(
			'name'    => $isha_name,
			'tagline' => $isha_text( $isha_raw, 'tagline' ),
			'url'     => $isha_text( $isha_raw, 'url' ),
			'items'   => $isha_items,
		);
	}
}

if ( ! $isha_groups ) {
	return;
}

$isha_contact  = isha_home_contact();
$isha_book_url = isset( $isha_contact['booking_url'] ) ? (string) $isha_contact['booking_url'] : '';
$isha_n_groups = count( $isha_groups );
$isha_n_cards  = 0;
foreach ( $isha_groups as $isha_group ) {
	$isha_n_cards += count( $isha_group['items'] );
}

isha_home_section_open( 'services' );
?>
<div class="isha-wheel<?php echo 1 === $isha_n_groups ? ' isha-wheel--single' : ''; ?>" data-isha-wheel>
	

	<div class="isha-wheel__inner">
		<header class="isha-wheel__head">
			<h2 id="<?php echo esc_attr( isha_home_title_id( 'services' ) ); ?>" class="isha-sec__title isha-wheel__eyebrow"><?php echo esc_html( $isha_title ); ?></h2>
			<?php if ( '' !== $isha_lead ) : ?>
				<p class="isha-wheel__lead"><?php echo esc_html( $isha_lead ); ?></p>
			<?php endif; ?>
			<?php if ( $isha_n_groups > 1 ) : ?>
				<nav class="isha-wheel__tabs" aria-label="<?php esc_attr_e( 'Nhóm dịch vụ tiêu biểu', 'isha' ); ?>">
					<ul role="list">
						<?php foreach ( $isha_groups as $isha_k => $isha_group ) : ?>
							<li>
								<a class="isha-wheel__tab" href="<?php echo esc_url( '' !== $isha_group['url'] ? $isha_group['url'] : '#' . isha_home_title_id( 'services' ) ); ?>" data-k="<?php echo esc_attr( (string) $isha_k ); ?>"<?php echo 0 === $isha_k ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $isha_group['name'] ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>
		</header>

		<div class="isha-wheel__text">
			<div class="isha-wheel__panels">
				<?php foreach ( $isha_groups as $isha_k => $isha_group ) : ?>
					<div class="isha-wheel__panel<?php echo 0 === $isha_k ? ' is-active' : ''; ?>" data-k="<?php echo esc_attr( (string) $isha_k ); ?>" data-url="<?php echo esc_url( $isha_group['url'] ); ?>" data-name="<?php echo esc_attr( $isha_group['name'] ); ?>">
						<?php if ( $isha_n_groups > 1 ) : ?><p class="isha-wheel__index" aria-hidden="true"><span class="isha-wheel__now"><?php echo esc_html( sprintf( '%02d', $isha_k + 1 ) ); ?></span><span class="isha-wheel__dash"></span><span class="isha-wheel__total"><?php echo esc_html( sprintf( '%02d', $isha_n_groups ) ); ?></span></p><?php endif; ?>
						<p class="isha-wheel__name"><?php echo esc_html( $isha_group['name'] ); ?></p>
						<span class="isha-wheel__rule" aria-hidden="true"></span>
						<?php if ( '' !== $isha_group['tagline'] ) : ?>
							<p class="isha-wheel__tagline"><?php echo esc_html( $isha_group['tagline'] ); ?></p>
						<?php endif; ?>
						<?php if ( $isha_group['items'] ) : ?>
							<?php /* translators: %d: số liệu trình trong nhóm. */ ?>
							<p class="isha-wheel__count"><?php echo esc_html( sprintf( _n( '%d liệu trình', '%d liệu trình', count( $isha_group['items'] ), 'isha' ), count( $isha_group['items'] ) ) ); ?></p>
						<?php endif; ?>
						
					</div>
				<?php endforeach; ?>
			</div>
						<div class="isha-wheel__actions">
				<a class="isha-btn isha-btn--primary isha-wheel__more-link" href="<?php echo esc_url( $isha_groups[0]['url'] ); ?>"<?php echo '' === $isha_groups[0]['url'] ? ' hidden' : ''; ?>><?php esc_html_e( 'Khám phá nhóm', 'isha' ); ?></a>
				<?php if ( '' !== $isha_all_url ) : ?>
					<a class="isha-btn isha-btn--ghost isha-wheel__all" href="<?php echo esc_url( $isha_all_url ); ?>"><?php echo esc_html( $isha_all_text ); ?></a>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $isha_n_cards > 0 ) : ?>
			<div class="isha-ring isha-ring--solo isha-wheel__ring" data-isha-ring>
				<ul class="isha-ring__ring" role="list" aria-label="<?php esc_attr_e( 'Liệu trình tiêu biểu', 'isha' ); ?>">
					<?php foreach ( $isha_groups as $isha_k => $isha_group ) : ?>
						<?php foreach ( $isha_group['items'] as $isha_item ) : ?>
							<?php
							$isha_img = $isha_item['thumb'] > 0 ? isha_home_image(
								$isha_item['thumb'],
								array(
									'size'       => 'isha-frame',
									'sizes'      => '(max-width: 767px) 44vw, 260px',
									'class'      => 'isha-ring__img',
									'decorative' => true,
								)
							) : '';
							$isha_book = ( '' !== $isha_book_url && $isha_item['id'] > 0 ) ? add_query_arg( 'service', $isha_item['id'], $isha_book_url ) : $isha_book_url;
							?>
							<li class="isha-ring__card isha-wheel__card" data-k="<?php echo esc_attr( (string) $isha_k ); ?>">
								<div class="isha-ring__flip">
									<div class="isha-ring__side isha-ring__side--front">
										<div class="isha-ring__frame">
											<span class="isha-ring__glint" aria-hidden="true"></span>
											<div class="isha-ring__face isha-wheel__face">
												<?php
												if ( '' !== $isha_img ) {
													echo $isha_img; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape.
												} else {
													isha_home_placeholder( __( 'Ảnh liệu trình', 'isha' ) );
												}
												?>
												<span class="isha-wheel__glass" aria-hidden="true"></span>
												<div class="isha-wheel__cap">
													<p class="isha-wheel__group"><?php echo esc_html( $isha_group['name'] ); ?></p>
													<h3 class="isha-ring__title isha-wheel__title">
														<?php echo esc_html( $isha_item['title'] ); ?>
														<?php if ( $isha_item['draft'] ) : ?>
															<span class="isha-label isha-wheel__draft"><?php esc_html_e( 'Nháp', 'isha' ); ?></span>
														<?php endif; ?>
													</h3>
													<div class="isha-wheel__acts">
														<div class="isha-wheel__acts-in">
															<?php if ( '' !== $isha_book ) : ?>
																<a class="isha-btn isha-btn--primary isha-ring__act isha-wheel__book isha-sheen" href="<?php echo esc_url( $isha_book ); ?>"><?php esc_html_e( 'Đặt lịch tư vấn', 'isha' ); ?><span class="isha-sr">: <?php echo esc_html( $isha_item['title'] ); ?></span></a>
															<?php endif; ?>
															<?php if ( '' !== $isha_item['url'] ) : ?>
																<a class="isha-ring__more isha-wheel__detail" href="<?php echo esc_url( $isha_item['url'] ); ?>"><?php esc_html_e( 'Xem chi tiết', 'isha' ); ?><span class="isha-sr">: <?php echo esc_html( $isha_item['title'] ); ?></span></a>
															<?php endif; ?>
														</div>
													</div>
												</div>
											</div>
										</div>
										
									</div>
								</div>
							</li>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
isha_home_section_close();
