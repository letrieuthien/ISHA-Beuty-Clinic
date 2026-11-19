<?php
/**
 * Section Workshop & Sự kiện của trang chủ điện ảnh: danh sách tạp chí tương tác (Editorial Split List).
 *
 * Desktop có con trỏ (rộng từ 768px, hover + pointer: fine): bên trái một khung ảnh 4:5 sạch (không chữ, không lớp phủ), bên phải 4
 * dòng sự kiện. Mỗi dòng là một liên kết tới trang chi tiết: ô ngày lớn (số ngày, tháng, năm), loại sự kiện, tên, giờ và địa điểm, mũi
 * tên. Rê chuột hoặc focus vào dòng nào thì ảnh bên trái chuyển mờ dần sang ảnh đại diện của sự kiện đó. Hàng đầu đang chọn sẵn.
 * Thiết bị cảm ứng và màn hình hẹp: khung ảnh trái ẩn, mỗi sự kiện là một thẻ dọc (ảnh 16:10 phía trên, chữ bên dưới).
 *
 * Bộ lọc theo Loại sự kiện (taxonomy isha_event_type) chỉ có khi có từ 2 loại: nằm trong `hidden` cho tới khi module
 * assets/js/events-list/main.js chạy, nên không có JS thì chỉ thấy ba sự kiện của tab "Tất cả". Dữ liệu chứa sẵn sự kiện của mọi tab
 * (`tabs` là danh sách tab chứa sự kiện, `initial` là sự kiện thuộc "Tất cả"); các sự kiện không thuộc "Tất cả" nằm trong `hidden` và
 * ảnh bên trái của chúng không tải cho tới khi tab của chúng được chọn.
 *
 * Ảnh: ảnh đại diện (featured image) của sự kiện, kích thước isha-cinema (2.39:1) cắt giữa vào khung. Mỗi sự kiện có hai thẻ ảnh (khung trái
 * cho desktop, ảnh thẻ cho cảm ứng); thẻ nằm trong vùng display: none thì trình duyệt không tải (loading="lazy").
 *
 * Dữ liệu: `$args` là isha_home_data( 'events' ): title, lead, link_label, archive_url, types (slug, name), events (id, title, type,
 * type_slugs, tabs, initial, start_ts, date_label, time_label, end_time_label, day, month_label, year, location, url, image_id).
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
// Lấy một danh sách chuỗi (đã lọc rỗng) từ một mảng.
$isha_list = static function ( $source, $key ) {
	$out = array();
	if ( is_array( $source ) && isset( $source[ $key ] ) && is_array( $source[ $key ] ) ) {
		foreach ( $source[ $key ] as $value ) {
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				$out[] = trim( (string) $value );
			}
		}
	}
	return $out;
};
// Thêm thuộc tính (chuỗi đã escape) vào thẻ mở đầu tiên của một đoạn HTML <img> hoặc <div> do isha_home_image() / isha_home_placeholder() dựng.
$isha_with_attrs = static function ( $html, $attrs ) {
	return (string) preg_replace( '/^<(img|div)\b/', '<$1 ' . $attrs, (string) $html, 1 );
};

// Dữ liệu do shortcode truyền vào qua $args; gọi thẳng template không kèm dữ liệu thì tự lấy.
$isha_data    = ( isset( $args ) && is_array( $args ) && $args ) ? $args : isha_home_data( 'events' );
$isha_title   = $isha_text( $isha_data, 'title' );
$isha_lead    = $isha_text( $isha_data, 'lead' );
$isha_all     = $isha_text( $isha_data, 'link_label' );
$isha_archive = $isha_text( $isha_data, 'archive_url' );
$isha_events  = array();

if ( isset( $isha_data['events'] ) && is_array( $isha_data['events'] ) ) {
	foreach ( $isha_data['events'] as $isha_row ) {
		$isha_name = $isha_text( $isha_row, 'title' );
		if ( '' === $isha_name ) {
			continue;
		}
		$isha_tabs     = $isha_list( $isha_row, 'tabs' );
		$isha_events[] = array(
			'id'         => $isha_int( $isha_row, 'id' ),
			'title'      => $isha_name,
			'type'       => $isha_text( $isha_row, 'type' ),
			'tabs'       => $isha_tabs ? $isha_tabs : array( 'all' ),
			'initial'    => ! isset( $isha_row['initial'] ) || ! empty( $isha_row['initial'] ),
			'start_ts'   => $isha_int( $isha_row, 'start_ts' ),
			'time_label' => $isha_text( $isha_row, 'time_label' ),
			'end_label'  => $isha_text( $isha_row, 'end_time_label' ),
			'day'        => $isha_text( $isha_row, 'day' ),
			'month'      => $isha_text( $isha_row, 'month_label' ),
			'year'       => $isha_text( $isha_row, 'year' ),
			'location'   => $isha_text( $isha_row, 'location' ),
			'url'        => $isha_text( $isha_row, 'url' ),
			'image_id'   => $isha_int( $isha_row, 'image_id' ),
		);
	}
}

if ( ! $isha_events ) {
	return;
}
if ( '' === $isha_title ) {
	$isha_title = __( 'Workshop & sự kiện', 'isha' );
}
if ( '' === $isha_all ) {
	$isha_all = __( 'Xem tất cả sự kiện', 'isha' );
}

// Loại sự kiện cho bộ lọc (chỉ dùng khi có từ 2 loại).
$isha_types = array();
if ( isset( $isha_data['types'] ) && is_array( $isha_data['types'] ) ) {
	foreach ( $isha_data['types'] as $isha_type ) {
		$isha_slug = $isha_text( $isha_type, 'slug' );
		$isha_tn   = $isha_text( $isha_type, 'name' );
		if ( '' !== $isha_slug && '' !== $isha_tn ) {
			$isha_types[ $isha_slug ] = $isha_tn;
		}
	}
}
if ( count( $isha_types ) < 2 ) {
	$isha_types = array();
}

// Sự kiện đang chọn sẵn: hàng hiển thị đầu tiên (thuộc tab "Tất cả").
$isha_first_id = 0;
foreach ( $isha_events as $isha_event ) {
	if ( $isha_event['initial'] ) {
		$isha_first_id = $isha_event['id'];
		break;
	}
}

// Ảnh trong khung trái ~42% bề rộng nhưng cắt 4:5 từ ảnh 2.39:1 nên ảnh hiển thị rộng gấp ~3 lần khung: `sizes` phải báo bề rộng đó để trình
// duyệt không chọn bản ảnh quá nhỏ (vỡ hạt). Thẻ cảm ứng 16:10 cắt từ 2.39:1 hiển thị rộng ~1,5 lần thẻ.
$isha_stage_sizes = '(min-width: 1280px) 1500px, 1200px';
$isha_thumb_sizes = '(min-width: 768px) 75vw, 150vw';

isha_home_section_open( 'events' );
?>
	<div class="isha-wrap">
		<header class="isha-sec__head isha-events__head">
			<div class="isha-events__intro">
				<h2 id="<?php echo esc_attr( isha_home_title_id( 'events' ) ); ?>" class="isha-sec__title"><?php echo esc_html( $isha_title ); ?></h2>
				<?php if ( '' !== $isha_lead ) : ?>
					<p class="isha-lead"><?php echo esc_html( $isha_lead ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $isha_types ) : ?>
				<div class="isha-events__filter" role="group" aria-label="<?php esc_attr_e( 'Lọc theo loại sự kiện', 'isha' ); ?>" data-isha-events-filter hidden>
					<button type="button" class="isha-events__tab is-active" data-tab="all" aria-pressed="true"><?php esc_html_e( 'Tất cả', 'isha' ); ?></button>
					<?php foreach ( $isha_types as $isha_slug => $isha_tn ) : ?>
						<button type="button" class="isha-events__tab" data-tab="<?php echo esc_attr( $isha_slug ); ?>" aria-pressed="false"><?php echo esc_html( $isha_tn ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</header>

		<div class="isha-events__layout" data-isha-events<?php echo $isha_types ? ' data-status="' . esc_attr__( 'Đang hiển thị %d sự kiện', 'isha' ) . '"' : ''; ?>>
			<div class="isha-events__stage" aria-hidden="true">
				<div class="isha-events__frame">
					<?php foreach ( $isha_events as $isha_event ) : ?>
						<?php
						$isha_shot_class = 'isha-events__shot' . ( $isha_event['id'] === $isha_first_id ? ' is-active' : '' );
						$isha_shot_attrs = 'data-event="' . esc_attr( (string) $isha_event['id'] ) . '"' . ( $isha_event['initial'] ? '' : ' hidden' ) . ' ';
						$isha_shot       = isha_home_image(
							$isha_event['image_id'],
							array(
								'size'       => 'isha-cinema',
								'sizes'      => $isha_stage_sizes,
								'class'      => $isha_shot_class,
								'decorative' => true,
							)
						);
						if ( '' === $isha_shot ) {
							$isha_shot = isha_home_placeholder(
								__( 'Ảnh sự kiện', 'isha' ),
								array(
									'class' => $isha_shot_class,
									'echo'  => false,
								)
							);
						}
						echo $isha_with_attrs( $isha_shot, $isha_shot_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput -- ảnh và khối giữ chỗ đã escape; thuộc tính thêm vào đã qua esc_attr.
						?>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="isha-events__side">
				<ul class="isha-events__list" role="list">
					<?php foreach ( $isha_events as $isha_event ) : ?>
						<?php
						// Giờ và địa điểm: một dòng "18:00 – 20:00 · Địa điểm"; thiếu phần nào bỏ phần đó.
						$isha_meta = array();
						if ( '' !== $isha_event['time_label'] ) {
							$isha_meta[] = '' !== $isha_event['end_label'] ? $isha_event['time_label'] . ' – ' . $isha_event['end_label'] : $isha_event['time_label'];
						}
						if ( '' !== $isha_event['location'] ) {
							$isha_meta[] = $isha_event['location'];
						}
						$isha_is_first = ( $isha_event['id'] === $isha_first_id );
						$isha_thumb    = isha_home_image(
							$isha_event['image_id'],
							array(
								'size'       => 'isha-cinema',
								'sizes'      => $isha_thumb_sizes,
								'class'      => 'isha-events__thumbimg',
								'decorative' => true,
							)
						);
						if ( '' === $isha_thumb ) {
							$isha_thumb = isha_home_placeholder( __( 'Ảnh sự kiện', 'isha' ), array( 'echo' => false ) );
						}
						?>
						<li class="isha-events__item<?php echo $isha_is_first ? ' is-active' : ''; ?>" data-event="<?php echo esc_attr( (string) $isha_event['id'] ); ?>" data-tabs="<?php echo esc_attr( implode( ' ', $isha_event['tabs'] ) ); ?>"<?php echo $isha_event['initial'] ? '' : ' hidden'; ?>>
							<a class="isha-events__row" href="<?php echo esc_url( $isha_event['url'] ); ?>">
								<div class="isha-events__thumb" aria-hidden="true"><?php echo $isha_thumb; // phpcs:ignore WordPress.Security.EscapeOutput -- ảnh và khối giữ chỗ đã escape. ?></div>
								<?php if ( '' !== $isha_event['day'] ) : ?>
									<?php
									$isha_time_attr = $isha_event['start_ts'] > 0 ? ' datetime="' . esc_attr( wp_date( 'c', $isha_event['start_ts'] ) ) . '"' : '';
									?>
									<time class="isha-events__date"<?php echo $isha_time_attr; // phpcs:ignore WordPress.Security.EscapeOutput -- đã qua esc_attr. ?>>
										<span class="isha-events__day"><?php echo esc_html( $isha_event['day'] ); ?></span>
										<span class="isha-events__month"><?php echo esc_html( implode( ' · ', array_filter( array( $isha_event['month'], $isha_event['year'] ) ) ) ); ?></span>
									</time>
								<?php else : ?>
									<span class="isha-events__date is-empty" aria-hidden="true"><span class="isha-events__day">&mdash;</span></span>
								<?php endif; ?>
								<div class="isha-events__text">
									<?php if ( '' !== $isha_event['type'] ) : ?>
										<span class="isha-events__type isha-label"><?php echo esc_html( $isha_event['type'] ); ?></span>
									<?php endif; ?>
									<h3 class="isha-events__name"><?php echo esc_html( $isha_event['title'] ); ?></h3>
									<?php if ( $isha_meta ) : ?>
										<span class="isha-events__meta"><?php echo esc_html( implode( ' · ', $isha_meta ) ); ?></span>
									<?php endif; ?>
								</div>
								<svg class="isha-events__arrow" viewBox="0 0 32 12" width="32" height="12" aria-hidden="true" focusable="false"><path d="M0 6h30M25 1l5 5-5 5" fill="none" stroke="currentColor" stroke-width="1"/></svg>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php if ( $isha_types ) : ?>
					<p class="isha-sr" role="status" aria-live="polite" data-isha-events-status></p>
				<?php endif; ?>
			</div>

			<?php if ( '' !== $isha_archive ) : ?>
				<?php // Nút nằm ngoài cột danh sách: desktop chia đôi thì đứng dưới khung ảnh, góc trái; thẻ dọc thì đứng sau danh sách. ?>
				<p class="isha-events__all"><?php isha_home_button( $isha_all, $isha_archive, 'primary' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
<?php
isha_home_section_close();
