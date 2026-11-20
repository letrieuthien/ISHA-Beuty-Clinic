<?php
/**
 * Section Đối tác của trang chủ điện ảnh: "Đồng hành cùng ISHA" (nâng cấp 20/11/2026).
 *
 * Nền đêm. Tiêu đề căn trái như các section phía trên (cỡ tiêu đề section thường). Ở giữa là MỘT khung vuông: lớp vector tia mặt trời
 * (assets/images/vector-2.png) tô vàng ánh kim bằng CSS (mask + --metal-dark, khối `partners` của home.css), và các logo xếp thành 3
 * vòng tròn đồng tâm đè lên vector. Mỗi logo nằm trong một "huy hiệu" tròn nền đêm viền vàng mảnh để tia vector không cắt ngang logo;
 * logo đưa về một màu sáng bằng CSS. Không liên kết; tên đối tác là alt của ảnh (thiếu alt thì lấy tiêu đề của tệp trong Thư viện).
 * Logo do người dùng tải lên luôn in bằng thẻ <img>, không nội tuyến.
 *
 * Số vòng theo số logo: từ 9 logo là 3 vòng, 4 đến 8 logo là 2 vòng, 1 đến 3 logo là 1 vòng (1 logo thì nằm giữa lỗ của vector). Số logo
 * chia gần đều giữa các vòng (12 logo = 4/4/4), vòng sau lệch nửa bước so với vòng kề trong. Toạ độ từng logo tính ở đây (phần trăm của
 * khung vuông, tâm ở 50% 50%) và đi vào CSS qua biến --x, --y, nên bố cục không phụ thuộc hàm lượng giác của CSS; --pt-cap (đặt trên
 * khung) giới hạn cỡ huy hiệu khi số logo lẻ làm hai logo kề nhau gần hơn bình thường. Thứ tự DOM đi từ vòng trong ra vòng ngoài (logo
 * hiện lần lượt theo thứ tự này).
 *
 * Dữ liệu: `$args` là isha_home_data( 'partners' ): title, images (id, alt). Giá trị thô, escape khi in.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_data = ( isset( $args ) && is_array( $args ) && isset( $args['images'] ) ) ? $args : isha_home_data( 'partners' );
$isha_list = ( isset( $isha_data['images'] ) && is_array( $isha_data['images'] ) ) ? $isha_data['images'] : array();

$isha_title = ( isset( $isha_data['title'] ) && is_scalar( $isha_data['title'] ) ) ? trim( (string) $isha_data['title'] ) : '';
if ( '' === $isha_title ) {
	$isha_title = __( 'Đồng hành cùng ISHA', 'isha' );
}

// Dựng trước từng logo: ô nào không có ảnh hợp lệ thì bỏ hẳn để vòng không có lỗ trống.
$isha_logos = array();
foreach ( $isha_list as $isha_raw ) {
	if ( ! is_array( $isha_raw ) || ! isset( $isha_raw['id'] ) || ! is_numeric( $isha_raw['id'] ) || (int) $isha_raw['id'] < 1 ) {
		continue;
	}

	$isha_id  = (int) $isha_raw['id'];
	$isha_alt = ( isset( $isha_raw['alt'] ) && is_scalar( $isha_raw['alt'] ) ) ? trim( (string) $isha_raw['alt'] ) : '';
	if ( '' === $isha_alt ) {
		$isha_alt = trim( wp_strip_all_tags( get_the_title( $isha_id ) ) );
	}

	$isha_html = isha_home_image(
		$isha_id,
		array(
			'size'  => 'medium_large',
			'sizes' => '(max-width: 900px) 20vw, 120px',
			'class' => 'isha-partners__logo',
			'alt'   => $isha_alt,
		)
	);
	if ( '' !== $isha_html ) {
		$isha_logos[] = $isha_html;
	}
}

$isha_count = count( $isha_logos );
if ( ! $isha_count ) {
	return;
}

// Bán kính từng vòng (phần trăm cạnh khung vuông; tia vector kéo dài tới khoảng 47,8%, lỗ giữa bán kính khoảng 18,5%).
if ( $isha_count >= 9 ) {
	$isha_radii = array( 25.0, 35.5, 45.0 );
} elseif ( $isha_count >= 4 ) {
	$isha_radii = array( 30.0, 43.0 );
} else {
	$isha_radii = array( 36.0 );
}

// Chia số logo cho các vòng gần đều nhau (vòng ngoài dài hơn nên nhận phần dư trước): ba vòng đều số logo xếp so le thành hình hoa
// đều đặn, còn chia tỉ lệ theo bán kính thì logo của hai vòng kề nhau dễ đè lên nhau.
$isha_rings = count( $isha_radii );
$isha_per   = array();
foreach ( array_keys( $isha_radii ) as $isha_k ) {
	$isha_per[ $isha_k ] = (int) floor( $isha_count / $isha_rings );
}
for ( $isha_k = $isha_rings - 1, $isha_left = $isha_count - array_sum( $isha_per ); $isha_left > 0; --$isha_k, --$isha_left ) {
	++$isha_per[ $isha_k ];
}

// Toạ độ. Vòng trong nhất bắt đầu từ đỉnh (-90 độ). Vòng sau: cùng số logo với vòng kề trong thì lệch nửa bước; khác số logo thì thử từng
// độ trong một bước chia, chọn góc làm khoảng cách ngắn nhất tới mọi logo đã đặt LỚN NHẤT, để huy hiệu của các vòng không đè lên nhau.
$isha_cells  = array();
$isha_placed = array();
$isha_next   = 0;
$isha_prev   = 0.0;
foreach ( $isha_radii as $isha_k => $isha_r ) {
	$isha_n    = $isha_per[ $isha_k ];
	$isha_step = 360.0 / $isha_n;
	$isha_best = 0.0;
	if ( $isha_k > 0 && $isha_n === $isha_per[ $isha_k - 1 ] ) {
		// Cùng số logo với vòng kề trong: lệch đúng nửa bước (hình hoa đều, vòng cách một vòng thì thẳng tia với nhau).
		$isha_best = $isha_prev + $isha_step / 2.0;
	} elseif ( $isha_k > 0 ) {
		$isha_gap = -1.0;
		for ( $isha_off = 0.0; $isha_off < $isha_step; $isha_off += 1.0 ) {
			$isha_min = INF;
			for ( $isha_j = 0; $isha_j < $isha_n; $isha_j++ ) {
				$isha_angle = deg2rad( -90.0 + $isha_off + $isha_j * $isha_step );
				foreach ( $isha_placed as $isha_p ) {
					$isha_min = min( $isha_min, hypot( $isha_r * cos( $isha_angle ) - $isha_p[0], $isha_r * sin( $isha_angle ) - $isha_p[1] ) );
				}
			}
			if ( $isha_min > $isha_gap + 0.01 ) {
				$isha_gap  = $isha_min;
				$isha_best = $isha_off;
			}
		}
	}
	$isha_prev = $isha_best;

	for ( $isha_j = 0; $isha_j < $isha_n; $isha_j++ ) {
		$isha_angle = deg2rad( -90.0 + $isha_best + $isha_j * $isha_step );
		// Một logo duy nhất nằm giữa khung (lỗ giữa của vector).
		$isha_dx = ( 1 === $isha_count ) ? 0.0 : $isha_r * cos( $isha_angle );
		$isha_dy = ( 1 === $isha_count ) ? 0.0 : $isha_r * sin( $isha_angle );

		$isha_placed[] = array( $isha_dx, $isha_dy );
		$isha_cells[]  = array(
			'html' => $isha_logos[ $isha_next++ ],
			'ring' => $isha_k + 1,
			'x'    => number_format( 50.0 + $isha_dx, 3, '.', '' ),
			'y'    => number_format( 50.0 + $isha_dy, 3, '.', '' ),
		);
	}
}

// Cỡ huy hiệu tối đa = 90% khoảng cách ngắn nhất giữa hai tâm (phần trăm cạnh khung): số logo lẻ (10, 11, 13, 14, 16) chia vòng không đều nên
// logo kề nhau gần hơn, huy hiệu tự nhỏ lại thay vì đè lên nhau. Từ 12, 15 hoặc 9 logo (ba vòng đều) giới hạn này lớn hơn cỡ CSS nên không có tác dụng.
$isha_closest = INF;
$isha_total   = count( $isha_placed );
for ( $isha_i = 0; $isha_i < $isha_total; $isha_i++ ) {
	for ( $isha_j = $isha_i + 1; $isha_j < $isha_total; $isha_j++ ) {
		$isha_closest = min( $isha_closest, hypot( $isha_placed[ $isha_i ][0] - $isha_placed[ $isha_j ][0], $isha_placed[ $isha_i ][1] - $isha_placed[ $isha_j ][1] ) );
	}
}
$isha_cap = ( INF === $isha_closest ) ? '' : number_format( floor( $isha_closest * 90.0 ) / 100.0, 2, '.', '' );

isha_home_section_open( 'partners' );
?>
	<div class="isha-wrap isha-partners__stage">
		<header class="isha-sec__head isha-partners__head">
			<h2 class="isha-sec__title" id="<?php echo esc_attr( isha_home_title_id( 'partners' ) ); ?>"><?php echo esc_html( $isha_title ); ?></h2>
		</header>

		<div class="isha-partners__orbit"<?php echo '' !== $isha_cap ? ' style="--pt-cap: ' . esc_attr( $isha_cap ) . '%;"' : ''; ?>>
			<span class="isha-partners__vector" aria-hidden="true"></span>
			<ul class="isha-partners__grid" role="list">
				<?php foreach ( $isha_cells as $isha_cell ) : ?>
					<li class="isha-partners__cell" data-ring="<?php echo esc_attr( (string) $isha_cell['ring'] ); ?>" style="--x: <?php echo esc_attr( $isha_cell['x'] ); ?>; --y: <?php echo esc_attr( $isha_cell['y'] ); ?>;"><?php echo $isha_cell['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- isha_home_image() trả thẻ <img> đã escape. ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
<?php
isha_home_section_close();
