<?php
/**
 * Section Sự kiện của trang chủ điện ảnh: danh sách các thẻ khung màn ảnh rộng 2.39:1 xếp dọc (trạng thái tĩnh). Giai đoạn 5:
 * khi có JS và từ 2 sự kiện, home.js bỏ `hidden` của khối điều khiển, thêm vai trò carousel (class `is-carousel`) và tự chạy;
 * nút Trước/Sau chỉ hiện mũi tên (chữ nằm trong `.isha-sr` cho trình đọc màn hình).
 *
 * Mỗi thẻ: ảnh phủ kín khung, lớp phủ tối chỉ ở nửa dưới (một lớp che có chức năng, nằm dưới chữ và co giãn theo chiều cao
 * khối chữ để chữ luôn đạt tương phản), góc dưới trái có loại sự kiện (nhãn), tên (Garamond accent), ngày giờ và địa điểm
 * (hai dòng riêng, không nối bằng dấu chấm giữa), nút "Đăng ký tham dự" (có link đăng ký và sự kiện chưa diễn ra) hoặc
 * "Xem chi tiết" (sự kiện đã qua không mời đăng ký). Sự kiện đã qua có nhãn "Đã diễn ra". Điện thoại dọc: thẻ 4:5 và ảnh dọc
 * được dùng nếu có (<picture>); máy tính bảng dọc: thẻ 3:2 ảnh ngang; điện thoại xoay ngang và desktop: thẻ 2.39:1. Khối điều
 * khiển carousel (tạm dừng, trước, vạch tiến trình, sau) có sẵn trong DOM ở dạng `hidden`.
 *
 * Dữ liệu: `$args` là isha_home_data( 'events' ): title, lead, events (id, title, type, start_ts, date_label, time_label,
 * end_time_label, location, register_url, url, image_id, portrait_id, past).
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
$isha_data   = ( isset( $args ) && is_array( $args ) && $args ) ? $args : isha_home_data( 'events' );
$isha_title  = $isha_text( $isha_data, 'title' );
$isha_lead   = $isha_text( $isha_data, 'lead' );
$isha_events = array();

if ( isset( $isha_data['events'] ) && is_array( $isha_data['events'] ) ) {
	foreach ( $isha_data['events'] as $isha_row ) {
		$isha_name = $isha_text( $isha_row, 'title' );
		if ( '' === $isha_name ) {
			continue;
		}
		$isha_events[] = array(
			'id'           => $isha_int( $isha_row, 'id' ),
			'title'        => $isha_name,
			'type'         => $isha_text( $isha_row, 'type' ),
			'start_ts'     => $isha_int( $isha_row, 'start_ts' ),
			'date_label'   => $isha_text( $isha_row, 'date_label' ),
			'time_label'   => $isha_text( $isha_row, 'time_label' ),
			'end_label'    => $isha_text( $isha_row, 'end_time_label' ),
			'location'     => $isha_text( $isha_row, 'location' ),
			'register_url' => $isha_text( $isha_row, 'register_url' ),
			'url'          => $isha_text( $isha_row, 'url' ),
			'image_id'     => $isha_int( $isha_row, 'image_id' ),
			'portrait_id'  => $isha_int( $isha_row, 'portrait_id' ),
			'past'         => ! empty( $isha_row['past'] ),
		);
	}
}

if ( ! $isha_events ) {
	return;
}
if ( '' === $isha_title ) {
	$isha_title = __( 'Workshop & sự kiện', 'isha' );
}

// Điện thoại dọc: thẻ 4:5 dùng ảnh dọc (máy tính bảng dọc dùng thẻ 3:2 và ảnh ngang). Phải khớp media query trong khối `events` của home.css.
$isha_portrait_media = '(max-width: 600px) and (orientation: portrait)';

isha_home_section_open( 'events' );
?>
	<div class="isha-wrap">
		<header class="isha-sec__head">
			<h2 id="<?php echo esc_attr( isha_home_title_id( 'events' ) ); ?>" class="isha-sec__title"><?php echo esc_html( $isha_title ); ?></h2>
			<?php if ( '' !== $isha_lead ) : ?>
				<p class="isha-lead"><?php echo esc_html( $isha_lead ); ?></p>
			<?php endif; ?>
		</header>

		<div class="isha-events__carousel" data-isha-events>
			<ul class="isha-events__list" role="list">
				<?php foreach ( $isha_events as $isha_index => $isha_event ) : ?>
					<?php
					$isha_name_id = 'isha-events-name-' . ( $isha_event['id'] > 0 ? $isha_event['id'] : $isha_index + 1 );

					// Ảnh ngang là ảnh chính; thiếu thì dùng ảnh dọc cho mọi kích thước; thiếu cả hai thì khối giữ chỗ.
					$isha_main_id = ( $isha_event['image_id'] > 0 && wp_attachment_is_image( $isha_event['image_id'] ) ) ? $isha_event['image_id'] : $isha_event['portrait_id'];
					$isha_image   = isha_home_image(
						$isha_main_id,
						array(
							'size'       => ( $isha_main_id === $isha_event['portrait_id'] && $isha_main_id !== $isha_event['image_id'] ) ? 'isha-frame' : 'isha-cinema',
							'sizes'      => '(max-width: 600px) and (orientation: portrait) 100vw, (max-width: 1240px) calc(100vw - 80px), 1160px',
							'class'      => 'isha-events__img',
							'decorative' => true,
						)
					);

					// Nguồn ảnh dọc cho chế độ gọn dọc, chỉ khi ảnh chính là ảnh ngang và có ảnh dọc hợp lệ.
					$isha_portrait_srcset = '';
					if ( '' !== $isha_image && $isha_event['portrait_id'] > 0 && $isha_event['portrait_id'] !== $isha_main_id && wp_attachment_is_image( $isha_event['portrait_id'] ) ) {
						$isha_portrait_srcset = (string) wp_get_attachment_image_srcset( $isha_event['portrait_id'], 'isha-frame' );
						if ( '' === $isha_portrait_srcset ) {
							$isha_portrait_srcset = (string) wp_get_attachment_image_url( $isha_event['portrait_id'], 'isha-frame' );
						}
					}

					// Ngày giờ: một dòng ("14/11/2026 lúc 18:00" hoặc "14/11/2026 lúc 18:00 – 20:00"); địa điểm là dòng riêng. Nối bằng chữ
					// "lúc", không bằng dấu phẩy: định dạng ngày của site (vd. "22 Tháng 11, 2026") đã có dấu phẩy, nối thêm sẽ thành hai dấu phẩy.
					$isha_when = '';
					if ( '' !== $isha_event['date_label'] ) {
						if ( '' !== $isha_event['time_label'] && '' !== $isha_event['end_label'] ) {
							/* translators: 1: ngày, 2: giờ bắt đầu, 3: giờ kết thúc */
							$isha_when = sprintf( esc_html__( '%1$s lúc %2$s – %3$s', 'isha' ), esc_html( $isha_event['date_label'] ), esc_html( $isha_event['time_label'] ), esc_html( $isha_event['end_label'] ) );
						} elseif ( '' !== $isha_event['time_label'] ) {
							/* translators: 1: ngày, 2: giờ bắt đầu */
							$isha_when = sprintf( esc_html__( '%1$s lúc %2$s', 'isha' ), esc_html( $isha_event['date_label'] ), esc_html( $isha_event['time_label'] ) );
						} else {
							$isha_when = esc_html( $isha_event['date_label'] );
						}
					}

					// Nút: sự kiện sắp diễn ra có link đăng ký thì "Đăng ký tham dự"; còn lại "Xem chi tiết" (sự kiện đã qua không mời đăng ký).
					$isha_register = ( ! $isha_event['past'] && '' !== $isha_event['register_url'] );
					$isha_btn_url  = $isha_register ? $isha_event['register_url'] : $isha_event['url'];
					$isha_btn_text = $isha_register ? __( 'Đăng ký tham dự', 'isha' ) : __( 'Xem chi tiết', 'isha' );
					/* translators: 1: nhãn nút, 2: tên sự kiện */
					$isha_btn_name = sprintf( __( '%1$s: %2$s', 'isha' ), $isha_btn_text, $isha_event['title'] );
					?>
					<li class="isha-events__item">
						<article class="isha-events__card<?php echo $isha_event['past'] ? ' is-past' : ''; ?>" aria-labelledby="<?php echo esc_attr( $isha_name_id ); ?>" data-isha-event="<?php echo esc_attr( (string) ( $isha_index + 1 ) ); ?>">
							<div class="isha-events__media" aria-hidden="true">
								<?php if ( '' === $isha_image ) : ?>
									<?php isha_home_placeholder( __( 'Ảnh sự kiện', 'isha' ) ); ?>
								<?php elseif ( '' !== $isha_portrait_srcset ) : ?>
									<picture>
										<source media="<?php echo esc_attr( $isha_portrait_media ); ?>" srcset="<?php echo esc_attr( $isha_portrait_srcset ); ?>" sizes="100vw">
										<?php echo $isha_image; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?>
									</picture>
								<?php else : ?>
									<?php echo $isha_image; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?>
								<?php endif; ?>
							</div>

							<div class="isha-events__body">
								<?php if ( $isha_event['past'] ) : ?>
									<p class="isha-events__status isha-label"><?php esc_html_e( 'Đã diễn ra', 'isha' ); ?></p>
								<?php endif; ?>
								<?php if ( '' !== $isha_event['type'] ) : ?>
									<p class="isha-events__type isha-label"><?php echo esc_html( $isha_event['type'] ); ?></p>
								<?php endif; ?>
								<h3 id="<?php echo esc_attr( $isha_name_id ); ?>" class="isha-events__name isha-accent"><?php echo esc_html( $isha_event['title'] ); ?></h3>
								<?php if ( '' !== $isha_when ) : ?>
									<p class="isha-events__when isha-label">
										<?php if ( $isha_event['start_ts'] > 0 ) : ?>
											<time datetime="<?php echo esc_attr( wp_date( 'c', $isha_event['start_ts'] ) ); ?>"><?php echo $isha_when; // phpcs:ignore WordPress.Security.EscapeOutput -- ghép từ các giá trị đã esc_html. ?></time>
										<?php else : ?>
											<?php echo $isha_when; // phpcs:ignore WordPress.Security.EscapeOutput -- ghép từ các giá trị đã esc_html. ?>
										<?php endif; ?>
									</p>
								<?php endif; ?>
								<?php if ( '' !== $isha_event['location'] ) : ?>
									<p class="isha-events__where"><?php echo esc_html( $isha_event['location'] ); ?></p>
								<?php endif; ?>
								<?php if ( '' !== $isha_btn_url ) : ?>
									<div class="isha-events__actions">
										<?php
										isha_home_button(
											$isha_btn_text,
											$isha_btn_url,
											$isha_register ? 'primary' : 'ghost',
											array( 'aria-label' => $isha_btn_name )
										);
										?>
									</div>
								<?php endif; ?>
							</div>
						</article>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			// Phân trang dùng chung (isha_home_pager) dưới danh sách; home.js bỏ hidden khi dựng carousel.
			isha_home_pager(
				array(
					'count'     => count( $isha_events ),
					'label'     => __( 'Điều khiển sự kiện', 'isha' ),
					'dot_label' => __( 'Sự kiện %d', 'isha' ),
					'prev'      => __( 'Sự kiện trước', 'isha' ),
					'next'      => __( 'Sự kiện sau', 'isha' ),
					'attrs'     => 'data-isha-events-controls',
				)
			);
			?>
		</div>
	</div>
<?php
isha_home_section_close();
