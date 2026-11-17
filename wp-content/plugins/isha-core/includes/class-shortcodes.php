<?php
/**
 * Shortcodes.
 *
 * [isha_services count="6" columns="3" category="slug" orderby="menu_order"]
 * [isha_policies count="-1" view="full|list"]
 * [isha_lp_card_circle]
 * [isha_lp_cards_member]
 * [isha_booking_form title="Đặt lịch hẹn"]
 * [isha_contact_info show="phone,email,address"]
 * [isha_opening_hours]
 * [isha_map height="400"]
 * [isha_social]
 * [isha_button url="/dat-lich-hen/" text="Đặt lịch" style="primary|outline"]
 * [isha_cta title="..." text="..." button_text="..." button_url="..."]
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Shortcodes {

	public static function init() {
		$tags = array( 'services', 'policies', 'lp_card_circle', 'lp_cards_member', 'booking_form', 'contact_info', 'opening_hours', 'map', 'social', 'button', 'cta' );
		foreach ( $tags as $tag ) {
			add_shortcode( 'isha_' . $tag, array( __CLASS__, $tag ) );
		}
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function assets() {
		// Đổi theo thời điểm sửa file để trình duyệt luôn lấy bản CSS mới.
		wp_enqueue_style( 'isha-core', ISHA_CORE_URL . 'assets/css/isha-core.css', array(), (string) filemtime( ISHA_CORE_DIR . 'assets/css/isha-core.css' ) );
	}

	private static function columns( $value, $default ) {
		return max( 1, min( 4, absint( $value ) ?: $default ) );
	}

	/* [isha_services] ----------------------------------------------------- */
	public static function services( $atts ) {
		$a = shortcode_atts(
			array(
				'count'    => 6,
				'columns'  => 3,
				'category' => '',
				'orderby'  => 'menu_order',
			),
			$atts,
			'isha_services'
		);

		$args = array(
			'post_type'      => 'isha_service',
			'posts_per_page' => (int) $a['count'],
			'orderby'        => in_array( $a['orderby'], array( 'menu_order', 'title', 'date', 'rand' ), true ) ? $a['orderby'] : 'menu_order',
			'order'          => 'title' === $a['orderby'] ? 'ASC' : ( 'date' === $a['orderby'] ? 'DESC' : 'ASC' ),
			'no_found_rows'  => true,
		);
		if ( $a['category'] ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'isha_service_cat',
					'field'    => 'slug',
					'terms'    => array_map( 'sanitize_title', explode( ',', $a['category'] ) ),
				),
			);
		}

		$q = new WP_Query( $args );
		if ( ! $q->have_posts() ) {
			return '<p class="isha-empty">' . esc_html__( 'Chưa có dịch vụ nào.', 'isha-core' ) . '</p>';
		}

		ob_start();
		echo '<div class="isha-grid isha-grid--' . esc_attr( self::columns( $a['columns'], 3 ) ) . '">';
		while ( $q->have_posts() ) {
			$q->the_post();
			$data = ISHA_Core_Service_Fields::get( get_the_ID() );
			// Ảnh thẻ: ảnh đại diện (featured image) của liệu trình; chưa đặt thì dùng ảnh đầu của "Ảnh liệu trình".
			$thumb = (int) get_post_thumbnail_id();
			if ( ! $thumb && $data['gallery'] ) {
				$thumb = (int) $data['gallery'][0];
			}
			?>
			<article class="isha-card">
				<?php if ( $thumb ) : ?>
					<a class="isha-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php echo wp_get_attachment_image( $thumb, 'medium_large', false, array( 'alt' => '' ) ); ?></a>
				<?php endif; ?>
				<div class="isha-card__body">
					<h3 class="isha-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<?php if ( $data['short_desc'] ) : ?>
						<p><?php echo esc_html( wp_trim_words( $data['short_desc'], 26 ) ); ?></p>
					<?php endif; ?>
					<?php if ( $data['duration'] ) : ?>
						<p class="isha-card__meta"><?php echo esc_html( $data['duration'] ); ?></p>
					<?php endif; ?>
					<a class="isha-card__link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Xem chi tiết', 'isha-core' ); ?></a>
				</div>
			</article>
			<?php
		}
		echo '</div>';
		wp_reset_postdata();
		return ob_get_clean();
	}

	/* [isha_policies] ----------------------------------------------------- */

	/**
	 * HTML hai ảnh thẻ (mặt trước / mặt sau) của một chính sách. Dùng cho shortcode và template single.
	 *
	 * @param int $post_id ID chính sách.
	 * @return string
	 */
	public static function policy_cards( $post_id ) {
		$data  = ISHA_Core_Policy_Fields::get( $post_id );
		$title = get_the_title( $post_id );
		$sides = array(
			'front' => __( 'Mặt trước', 'isha-core' ),
			'back'  => __( 'Mặt sau', 'isha-core' ),
		);

		$out = '';
		foreach ( $sides as $key => $label ) {
			if ( ! $data[ $key ] ) {
				continue;
			}
			$out .= '<figure class="isha-policy__card">';
			$out .= '<a href="' . esc_url( wp_get_attachment_image_url( $data[ $key ], 'full' ) ) . '" target="_blank" rel="noopener">';
			$out .= wp_get_attachment_image( $data[ $key ], 'large', false, array( 'alt' => $title . ' – ' . $label ) );
			$out .= '</a><figcaption>' . esc_html( $label ) . '</figcaption></figure>';
		}
		return $out ? '<div class="isha-policy__cards">' . $out . '</div>' : '';
	}

	/**
	 * [isha_policies count="-1" view="full|list"]
	 * full: tiêu đề + 2 ảnh thẻ + nội dung (dùng cho landing).
	 * list: lưới thẻ dẫn tới trang chi tiết (dùng cho trang "Chính sách dịch vụ").
	 */
	public static function policies( $atts ) {
		static $rendering = false;

		$a    = shortcode_atts( array( 'count' => -1, 'view' => 'full' ), $atts, 'isha_policies' );
		$view = 'list' === $a['view'] ? 'list' : 'full';

		$q = new WP_Query(
			array(
				'post_type'      => 'isha_policy',
				'posts_per_page' => (int) $a['count'],
				'orderby'        => 'menu_order date',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		if ( ! $q->have_posts() ) {
			return '<p class="isha-empty">' . esc_html__( 'Chính sách đang được cập nhật.', 'isha-core' ) . '</p>';
		}

		ob_start();
		if ( 'list' === $view ) {
			echo '<div class="isha-grid isha-grid--3">';
			while ( $q->have_posts() ) {
				$q->the_post();
				$data    = ISHA_Core_Policy_Fields::get( get_the_ID() );
				$excerpt = wp_trim_words( wp_strip_all_tags( strip_shortcodes( get_the_content() ) ), 24 );
				?>
				<article class="isha-card">
					<?php if ( $data['front'] ) : ?>
						<a class="isha-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php echo wp_get_attachment_image( $data['front'], 'medium_large', false, array( 'alt' => '' ) ); ?></a>
					<?php endif; ?>
					<div class="isha-card__body">
						<h3 class="isha-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<?php if ( $excerpt ) : ?>
							<p><?php echo esc_html( $excerpt ); ?></p>
						<?php endif; ?>
						<a class="isha-card__link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Xem chính sách', 'isha-core' ); ?></a>
					</div>
				</article>
				<?php
			}
			echo '</div>';
		} else {
			echo '<div class="isha-policies">';
			while ( $q->have_posts() ) {
				$q->the_post();
				?>
				<article class="isha-policy">
					<h3 class="isha-policy__title"><?php the_title(); ?></h3>
					<?php echo self::policy_cards( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput -- đã escape trong policy_cards(). ?>
					<?php
					// Chặn đệ quy nếu ai đó chèn [isha_policies] vào chính nội dung chính sách.
					if ( ! $rendering ) {
						$rendering = true;
						$content   = apply_filters( 'the_content', get_the_content() );
						$rendering = false;
						if ( trim( wp_strip_all_tags( $content ) ) ) {
							echo '<div class="isha-policy__content">' . $content . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
						}
					}
					?>
				</article>
				<?php
			}
			echo '</div>';
		}
		wp_reset_postdata();
		return ob_get_clean();
	}

	/* [isha_lp_card_circle] ----------------------------------------------- */

	/**
	 * [isha_lp_card_circle]
	 * Xếp ảnh "Ảnh thẻ mặt trước" của mọi chính sách đã publish thành một chùm thẻ xòe quạt (trang trí).
	 * Chỉ hiển thị khi có hơn 1 chính sách publish; chính sách không có ảnh hợp lệ sẽ bị bỏ qua.
	 * Vị trí / kích thước / animation do CSS (.isha-lp-stack) quyết định; hiệu ứng theo chuột ở assets/js/isha-lp-stack.js.
	 * (Tên shortcode giữ nguyên "circle" để không phải sửa lại trang Elementor.)
	 */
	public static function lp_card_circle() {
		$ids = get_posts(
			array(
				'post_type'      => 'isha_policy',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order date',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		if ( count( $ids ) < 2 ) {
			return '';
		}

		$images = array();
		foreach ( $ids as $post_id ) {
			$front = ISHA_Core_Policy_Fields::get( $post_id )['front']; // 0 nếu thiếu / không phải ảnh.
			if ( $front ) {
				$images[] = $front;
			}
		}
		if ( ! $images ) {
			return '';
		}

		$items = '';
		$n     = 0;
		foreach ( $images as $image_id ) {
			$img = wp_get_attachment_image( $image_id, 'medium_large', false, array( 'alt' => '', 'decoding' => 'async' ) );
			if ( $img ) {
				$items .= '<span class="isha-lp-stack__item" style="--i:' . $n . '"><span class="isha-lp-stack__card">' . $img . '</span></span>';
				++$n;
			}
		}
		if ( ! $n ) {
			return '';
		}

		wp_enqueue_script( 'isha-lp-stack', ISHA_CORE_URL . 'assets/js/isha-lp-stack.js', array(), (string) filemtime( ISHA_CORE_DIR . 'assets/js/isha-lp-stack.js' ), true );

		return '<div class="isha-lp-stack" aria-hidden="true" style="--n:' . $n . '"><div class="isha-lp-stack__tilt">' . $items . '</div></div>';
	}

	/* [isha_lp_cards_member] ---------------------------------------------- */

	/**
	 * SVG 6 lớp sóng (dữ liệu trích từ file thiết kế, xem includes/lp-waves-data.php).
	 * Mỗi lớp là một <svg> riêng để CSS chuyển động (opacity / transform / mask) chạy trên layer riêng, không vẽ lại cả tranh.
	 *
	 * Public để template trang chi tiết Chính sách (theme ISHA) dùng lại làm nền hero; khi đó truyền class bao riêng
	 * để không dính style định vị của thang hạng thẻ.
	 *
	 * @param string $wrapper_class Class của thẻ bao ngoài.
	 * @return string
	 */
	public static function lp_waves( $wrapper_class = 'isha-lp-ladder__waves' ) {
		static $instance = 0;
		++$instance;

		$data = include ISHA_CORE_DIR . 'includes/lp-waves-data.php';
		$w    = $data['width'];
		$h    = $data['height'];
		$fmt  = static function ( $v ) {
			return rtrim( rtrim( number_format( (float) $v, 3, '.', '' ), '0' ), '.' );
		};

		$out = '<div class="' . esc_attr( $wrapper_class ) . '" aria-hidden="true">';
		foreach ( $data['layers'] as $k => $layer ) {
			$id   = 'isha-lp-' . $instance . '-' . $k;
			$defs = '<clipPath id="' . $id . '-c"><path d="' . esc_attr( $layer['clip'] ) . '"/></clipPath>';
			$mask = '';

			if ( $layer['mask'] ) {
				// Trong suốt dưới b1 → đậm dần theo u^n đến b2 → đặc phía trên (cùng cách file thiết kế mờ các vạch về phía dưới).
				$m     = $layer['mask'];
				$stops = '<stop offset="0" stop-color="#000"/><stop offset="' . $fmt( $m['b1'] ) . '" stop-color="#000"/>';
				for ( $s = 1; $s <= 12; $s++ ) {
					$u      = $s / 12;
					$gray   = (int) round( 255 * pow( $u, $m['n'] ) );
					$stops .= '<stop offset="' . $fmt( $m['b1'] + $u * ( $m['b2'] - $m['b1'] ) ) . '" stop-color="rgb(' . $gray . ',' . $gray . ',' . $gray . ')"/>';
				}
				$stops .= '<stop offset="1" stop-color="#fff"/>';
				$defs  .= '<linearGradient id="' . $id . '-g" gradientUnits="userSpaceOnUse" x1="0" y1="' . $fmt( $m['y0'] ) . '" x2="0" y2="' . $fmt( $m['y1'] ) . '">' . $stops . '</linearGradient>';
				$defs  .= '<mask id="' . $id . '-m" maskUnits="userSpaceOnUse" x="-200" y="-600" width="3500" height="3200">'
					. '<rect x="-200" y="-600" width="3500" height="3200" fill="#fff"/>'
					. '<rect x="-200" y="' . $fmt( $m['y0'] ) . '" width="3500" height="' . $fmt( $m['y1'] - $m['y0'] ) . '" fill="url(#' . $id . '-g)"/></mask>';
				$mask   = ' mask="url(#' . $id . '-m)"';
			}

			$d = '';
			for ( $b = 0; $b < $layer['count']; $b++ ) {
				$d .= 'M' . $fmt( $layer['x0'] + $b * $layer['pitch'] ) . ' ' . $fmt( $layer['y1'] ) . 'V' . $fmt( $layer['y2'] );
			}

			// Lật trục y để dùng thẳng toạ độ PDF (y hướng lên).
			$out .= '<svg class="isha-lp-wave" style="--k:' . (int) $k . '" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="xMidYMax slice" focusable="false">'
				. '<defs>' . $defs . '</defs>'
				. '<g transform="matrix(1 0 0 -1 0 ' . $h . ')"><g clip-path="url(#' . $id . '-c)"' . $mask . '>'
				. '<path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="' . (float) $layer['stroke'] . '"/>'
				. '</g></g></svg>';
		}
		return $out . '</div>';
	}

	/**
	 * [isha_lp_cards_member order="asc|desc"]
	 * Thang hạng thẻ thành viên dạng scrollytelling: khung ghim bên trái (thẻ + sóng), nội dung từng hạng cuộn bên phải.
	 * Mỗi bài isha_policy đã publish là một hạng. Thang đi TỪ THẤP LÊN CAO: bài đứng đầu là hạng thấp nhất, càng về sau càng nhiều lớp sóng.
	 * - order="asc" (mặc định): theo ô "Order" tăng dần (Order nhỏ = hạng thấp), cùng Order thì theo ngày tạo.
	 * - order="desc": đảo ngược (dùng khi Order đang đặt kiểu "hạng cao nhất = 0").
	 * Dãy nút tròn ở bộ đếm lấy màu từ field "Màu thẻ" của từng bài (bấm để nhảy tới hạng đó).
	 * Hiệu ứng chuyển hạng do assets/js/isha-lp-ladder.js điều khiển, hình thức do CSS (.isha-lp-ladder).
	 */
	public static function lp_cards_member( $atts ) {
		static $rendering = false;
		if ( $rendering ) {
			return ''; // Chặn đệ quy nếu nội dung một chính sách lại chứa chính shortcode này.
		}

		$a = shortcode_atts( array( 'order' => 'asc' ), $atts, 'isha_lp_cards_member' );

		$q = new WP_Query(
			array(
				'post_type'      => 'isha_policy',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order date',
				'order'          => 'desc' === strtolower( (string) $a['order'] ) ? 'DESC' : 'ASC',
				'no_found_rows'  => true,
			)
		);
		if ( ! $q->have_posts() ) {
			return '';
		}

		$rendering = true;
		$total     = (int) $q->post_count;
		$cards     = '';
		$chapters  = '';
		$dots      = '';
		$i         = 0;

		while ( $q->have_posts() ) {
			$q->the_post();
			$post_id = get_the_ID();
			$plain   = wp_strip_all_tags( get_the_title() );
			$title   = esc_html( get_the_title() );
			$data    = ISHA_Core_Policy_Fields::get( $post_id );
			$front   = $data['front'];
			$num     = str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT );

			// Nút tròn chọn hạng: màu do admin chọn (field "Màu thẻ"); chưa chọn thì đánh dấu data-auto để JS lấy màu từ ảnh thẻ.
			$dots .= '<a class="isha-lp-dot" href="#isha-hang-' . (int) $post_id . '" data-i="' . $i . '" data-label="' . esc_attr( $plain ) . '"'
				. ( $data['color'] ? ' style="--c:' . esc_attr( $data['color'] ) . '"' : ' data-auto' )
				. ' aria-label="' . esc_attr( sprintf( /* translators: %s: tên hạng */ __( 'Xem %s', 'isha-core' ), $plain ) ) . '"></a>';

			// Thẻ: thiếu ảnh thì dùng khung chữ thay thế để sân khấu không bị trống.
			$media = $front
				? wp_get_attachment_image( $front, 'large', false, array( 'alt' => sprintf( /* translators: %s: tên hạng */ __( 'Thẻ %s', 'isha-core' ), $plain ), 'decoding' => 'async' ) )
				: '<span class="isha-lp-tier__ph">' . $title . '</span>';
			$cards .= '<figure class="isha-lp-tier" data-i="' . $i . '"' . ( $i ? ' aria-hidden="true"' : '' ) . '><span class="isha-lp-tier__float">' . $media . '</span></figure>';

			$url = get_permalink( $post_id );
			if ( ! $i ) {
				$first = array( 'url' => $url, 'title' => $plain );
			}

			$body      = apply_filters( 'the_content', get_the_content() );
			$chapters .= '<article class="isha-lp-chapter" id="isha-hang-' . (int) $post_id . '" data-i="' . $i . '" data-url="' . esc_url( $url ) . '"><div class="isha-lp-chapter__panel">'
				. '<p class="isha-lp-eyebrow">' . esc_html( $num . ' / ' . str_pad( (string) $total, 2, '0', STR_PAD_LEFT ) ) . '</p>'
				. '<h2 class="isha-lp-title isha-lp-title--left">' . $title . '</h2>'
				. '<div class="isha-lp-text isha-lp-chapter__body">' . $body . '</div>'
				. '</div></article>';
			++$i;
		}
		wp_reset_postdata();
		$rendering = false;

		$counter = '';
		if ( $total > 1 ) {
			$counter = '<div class="isha-lp-ladder__counter">'
				. '<div class="isha-lp-ladder__meta" aria-hidden="true"><span class="isha-lp-ladder__cur">01</span>'
				. '<span class="isha-lp-ladder__bar"><i></i></span><span class="isha-lp-ladder__tot">' . str_pad( (string) $total, 2, '0', STR_PAD_LEFT ) . '</span></div>'
				. '<nav class="isha-lp-ladder__dots" aria-label="' . esc_attr__( 'Chọn hạng thẻ', 'isha-core' ) . '">' . $dots . '</nav>'
				. '</div>';
		}

		// Nút vào trang chi tiết thẻ: cùng hàng với bộ đếm, nằm phía bên kia của thẻ; JS đổi đường dẫn theo hạng đang xem.
		$more_text = __( 'Xem chi tiết', 'isha-core' );
		$more      = '<a class="isha-lp-ladder__more" href="' . esc_url( $first['url'] ) . '" aria-label="' . esc_attr( $more_text . ' – ' . $first['title'] ) . '"><span>' . esc_html( $more_text ) . '</span></a>';
		$head      = '<div class="isha-lp-ladder__head">' . $counter . $more . '</div>';

		wp_enqueue_script( 'isha-lp-ladder', ISHA_CORE_URL . 'assets/js/isha-lp-ladder.js', array(), (string) filemtime( ISHA_CORE_DIR . 'assets/js/isha-lp-ladder.js' ), true );

		return '<section class="isha-lp-ladder" data-count="' . $total . '" aria-label="' . esc_attr__( 'Các hạng thẻ thành viên', 'isha-core' ) . '">'
			. '<div class="isha-lp-ladder__stage"><div class="isha-lp-ladder__bg" aria-hidden="true">' . self::lp_waves() . '</div><div class="isha-lp-ladder__cards">' . $cards . '</div>' . $head . '</div>'
			. '<div class="isha-lp-ladder__chapters">' . $chapters . '</div>'
			. '</section>';
	}

	/* [isha_booking_form] ------------------------------------------------- */
	public static function booking_form( $atts ) {
		$a = shortcode_atts( array( 'title' => '' ), $atts, 'isha_booking_form' );

		$services = get_posts( array( 'post_type' => 'isha_service', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );

		// Chọn sẵn từ URL (?service=ID) — chỉ đọc để chọn option, không ghi ra HTML thô.
		// phpcs:disable WordPress.Security.NonceVerification
		$pre_service = isset( $_GET['service'] ) ? absint( $_GET['service'] ) : 0;
		$status      = isset( $_GET['isha_booking'] ) ? sanitize_key( wp_unslash( $_GET['isha_booking'] ) ) : '';
		// phpcs:enable

		$messages = array(
			'success' => array( 'ok', __( 'Cảm ơn bạn! ISHA đã nhận lịch hẹn và sẽ liên hệ xác nhận sớm nhất.', 'isha-core' ) ),
			'invalid' => array( 'err', __( 'Thông tin chưa hợp lệ. Vui lòng kiểm tra họ tên, số điện thoại, ngày và giờ hẹn.', 'isha-core' ) ),
			'limit'   => array( 'err', __( 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau hoặc gọi hotline.', 'isha-core' ) ),
			'error'   => array( 'err', __( 'Có lỗi xảy ra. Vui lòng thử lại.', 'isha-core' ) ),
		);

		ob_start();
		?>
		<div class="isha-booking" id="isha-booking">
			<?php if ( $a['title'] ) : ?>
				<h2 class="isha-booking__title"><?php echo esc_html( $a['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( isset( $messages[ $status ] ) ) : ?>
				<p class="isha-alert isha-alert--<?php echo esc_attr( $messages[ $status ][0] ); ?>" role="alert"><?php echo esc_html( $messages[ $status ][1] ); ?></p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="isha-form">
				<input type="hidden" name="action" value="isha_booking">
				<?php wp_nonce_field( 'isha_booking', 'isha_booking_nonce' ); ?>
				<div class="isha-hp" aria-hidden="true"><label>Website <input type="text" name="isha_website" tabindex="-1" autocomplete="off"></label></div>

				<div class="isha-form__row">
					<p><label for="isha_name"><?php esc_html_e( 'Họ và tên', 'isha-core' ); ?> *</label>
						<input type="text" id="isha_name" name="isha_name" required maxlength="100" autocomplete="name"></p>
					<p><label for="isha_phone"><?php esc_html_e( 'Số điện thoại', 'isha-core' ); ?> *</label>
						<input type="tel" id="isha_phone" name="isha_phone" required pattern="[0-9+()\s.\-]{8,20}" autocomplete="tel"></p>
				</div>

				<p><label for="isha_email"><?php esc_html_e( 'Email', 'isha-core' ); ?></label>
					<input type="email" id="isha_email" name="isha_email" autocomplete="email"></p>

					<p><label for="isha_service"><?php esc_html_e( 'Dịch vụ', 'isha-core' ); ?></label>
						<select id="isha_service" name="isha_service">
							<option value=""><?php esc_html_e( '— Chưa chọn / cần tư vấn —', 'isha-core' ); ?></option>
							<?php foreach ( $services as $s ) : ?>
								<option value="<?php echo esc_attr( $s->ID ); ?>" <?php selected( $pre_service, $s->ID ); ?>><?php echo esc_html( $s->post_title ); ?></option>
							<?php endforeach; ?>
						</select></p>

				<div class="isha-form__row">
					<p><label for="isha_date"><?php esc_html_e( 'Ngày hẹn', 'isha-core' ); ?> *</label>
						<input type="date" id="isha_date" name="isha_date" required min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></p>
					<p><label for="isha_time"><?php esc_html_e( 'Giờ hẹn', 'isha-core' ); ?> *</label>
						<select id="isha_time" name="isha_time" required>
							<option value=""><?php esc_html_e( '— Chọn giờ —', 'isha-core' ); ?></option>
							<?php foreach ( ISHA_Core_Booking::time_slots() as $slot ) : ?>
								<option value="<?php echo esc_attr( $slot ); ?>"><?php echo esc_html( $slot ); ?></option>
							<?php endforeach; ?>
						</select></p>
				</div>

				<p><label for="isha_note"><?php esc_html_e( 'Ghi chú (tình trạng da, mong muốn...)', 'isha-core' ); ?></label>
					<textarea id="isha_note" name="isha_note" rows="4" maxlength="1000"></textarea></p>

				<?php // Đồng ý xử lý dữ liệu cá nhân (bắt buộc từ 18/11/2026, ISHA_Core_Booking::process()). ?>
				<p><label for="isha_consent"><input type="checkbox" id="isha_consent" name="isha_consent" value="1" required> <?php esc_html_e( 'Tôi đồng ý để ISHA lưu và dùng thông tin trên để liên hệ xác nhận lịch hẹn.', 'isha-core' ); ?></label>
					<?php if ( '' !== ISHA_Core_Forms::privacy_url() ) : ?>
						<a href="<?php echo esc_url( ISHA_Core_Forms::privacy_url() ); ?>"><?php esc_html_e( 'Chính sách bảo mật', 'isha-core' ); ?></a>
					<?php endif; ?></p>

				<p><button type="submit" class="isha-btn isha-btn--primary"><?php esc_html_e( 'Gửi yêu cầu đặt lịch', 'isha-core' ); ?></button></p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/* [isha_contact_info] ------------------------------------------------- */
	public static function contact_info( $atts ) {
		$a    = shortcode_atts( array( 'show' => 'phone,email,address' ), $atts, 'isha_contact_info' );
		$show = array_map( 'trim', explode( ',', $a['show'] ) );
		$out  = '';

		if ( in_array( 'phone', $show, true ) && isha_core_get( 'phone' ) ) {
			$phone = isha_core_get( 'phone' );
			$out  .= '<li><span class="isha-info__label">' . esc_html__( 'Hotline', 'isha-core' ) . '</span> <a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a></li>';
		}
		if ( in_array( 'email', $show, true ) && isha_core_get( 'email' ) ) {
			$email = isha_core_get( 'email' );
			$out  .= '<li><span class="isha-info__label">Email</span> <a href="mailto:' . esc_attr( antispambot( $email ) ) . '">' . esc_html( antispambot( $email ) ) . '</a></li>';
		}
		if ( in_array( 'address', $show, true ) && isha_core_get( 'address' ) ) {
			$out .= '<li><span class="isha-info__label">' . esc_html__( 'Địa chỉ', 'isha-core' ) . '</span> ' . nl2br( esc_html( isha_core_get( 'address' ) ) ) . '</li>';
		}
		if ( in_array( 'hours', $show, true ) && isha_core_get( 'hours' ) ) {
			$out .= '<li><span class="isha-info__label">' . esc_html__( 'Giờ làm việc', 'isha-core' ) . '</span> ' . nl2br( esc_html( isha_core_get( 'hours' ) ) ) . '</li>';
		}
		return $out ? '<ul class="isha-info">' . $out . '</ul>' : '';
	}

	/* [isha_opening_hours] ------------------------------------------------ */
	public static function opening_hours() {
		$hours = isha_core_get( 'hours' );
		if ( ! $hours ) {
			return '';
		}
		$lines = array_filter( array_map( 'trim', explode( "\n", $hours ) ) );
		$out   = '<ul class="isha-hours">';
		foreach ( $lines as $line ) {
			$out .= '<li>' . esc_html( $line ) . '</li>';
		}
		return $out . '</ul>';
	}

	/* [isha_map] ---------------------------------------------------------- */
	public static function map( $atts ) {
		$a   = shortcode_atts( array( 'height' => 400 ), $atts, 'isha_map' );
		$url = isha_core_get( 'map_url' );
		if ( ! $url ) {
			return '';
		}
		// Chỉ cho phép nhúng Google Maps.
		$parts = wp_parse_url( $url );
		if ( empty( $parts['host'] ) || ! in_array( $parts['host'], array( 'www.google.com', 'maps.google.com' ), true ) || 'https' !== ( $parts['scheme'] ?? '' ) ) {
			return '';
		}
		return sprintf(
			'<div class="isha-map"><iframe src="%s" height="%d" style="border:0;width:100%%" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen title="%s"></iframe></div>',
			esc_url( $url ),
			absint( $a['height'] ),
			esc_attr__( 'Bản đồ phòng khám', 'isha-core' )
		);
	}

	/* [isha_social] ------------------------------------------------------- */
	public static function social() {
		$networks = array(
			'facebook'  => 'Facebook',
			'instagram' => 'Instagram',
			'tiktok'    => 'TikTok',
			'youtube'   => 'YouTube',
			'zalo'      => 'Zalo',
		);
		$out = '';
		foreach ( $networks as $key => $label ) {
			if ( isha_core_get( $key ) ) {
				$out .= '<li><a href="' . esc_url( isha_core_get( $key ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ) . '</a></li>';
			}
		}
		return $out ? '<ul class="isha-social">' . $out . '</ul>' : '';
	}

	/* [isha_button] ------------------------------------------------------- */
	public static function button( $atts ) {
		$a = shortcode_atts(
			array(
				'url'   => '#',
				'text'  => __( 'Đặt lịch', 'isha-core' ),
				'style' => 'primary',
			),
			$atts,
			'isha_button'
		);
		$style = 'outline' === $a['style'] ? 'outline' : 'primary';
		return '<a class="isha-btn isha-btn--' . esc_attr( $style ) . '" href="' . esc_url( $a['url'] ) . '">' . esc_html( $a['text'] ) . '</a>';
	}

	/* [isha_cta] ---------------------------------------------------------- */
	public static function cta( $atts ) {
		$a = shortcode_atts(
			array(
				'title'       => __( 'Bắt đầu hành trình làn da khỏe đẹp', 'isha-core' ),
				'text'        => '',
				'button_text' => __( 'Đặt lịch hẹn', 'isha-core' ),
				'button_url'  => '#',
			),
			$atts,
			'isha_cta'
		);
		$out  = '<div class="isha-cta"><h2 class="isha-cta__title">' . esc_html( $a['title'] ) . '</h2>';
		$out .= $a['text'] ? '<p>' . esc_html( $a['text'] ) . '</p>' : '';
		$out .= '<a class="isha-btn isha-btn--primary" href="' . esc_url( $a['button_url'] ) . '">' . esc_html( $a['button_text'] ) . '</a></div>';
		return $out;
	}
}
