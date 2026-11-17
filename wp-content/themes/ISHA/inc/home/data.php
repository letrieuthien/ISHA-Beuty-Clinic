<?php
/**
 * Lớp dữ liệu của trang chủ điện ảnh: nội dung mặc định (Phụ lục A), trộn với giá trị đã lưu trong CMS và dựng sẵn
 * dữ liệu từng section cho template.
 *
 * Bốn hàm công khai (hợp đồng ở docs/home-architecture.md, mục 4):
 *   isha_home_page_id()                 trang chứa dữ liệu trang chủ
 *   isha_home_defaults( $section )      nội dung mặc định (khoá khớp plugins/isha-core/includes/home-schema.php)
 *   isha_home_data( $section, $args )   dữ liệu đã trộn và dựng sẵn; array() nghĩa là ẨN section
 *   isha_home_contact()                 thông tin liên hệ cho nút đặt lịch, Zalo, hotline
 *
 * Mọi giá trị trả về là văn bản thô (chưa escape); template tự escape khi in. Media là ID tệp đính kèm (0 = không có).
 * Các hàm isha_home_data_* còn lại là hàm nội bộ của tệp này, không thuộc hợp đồng.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ID trang chứa dữ liệu trang chủ.
 *
 * Dùng ISHA_Core_Home_Fields::page_id() nếu có; không thì cùng thứ tự ưu tiên ở cục bộ: (1) bộ lọc isha_core/home_page_id
 * trả ID của một page; (2) trang tĩnh đang đặt làm trang chủ, nếu không phải trang dựng bằng Elementor; (3) trang có
 * slug trang-chu; (4) 0.
 *
 * @return int
 */
function isha_home_page_id() {
	if ( isha_home_data_has_method( 'ISHA_Core_Home_Fields', 'page_id' ) ) {
		return absint( ISHA_Core_Home_Fields::page_id() );
	}

	$forced = apply_filters( 'isha_core/home_page_id', 0 );
	$forced = is_numeric( $forced ) ? absint( $forced ) : 0;
	if ( $forced && 'page' === get_post_type( $forced ) ) {
		return $forced;
	}

	if ( 'page' === get_option( 'show_on_front' ) ) {
		$front = absint( get_option( 'page_on_front' ) );
		if ( $front && 'page' === get_post_type( $front ) && 'builder' !== get_post_meta( $front, '_elementor_edit_mode', true ) ) {
			return $front;
		}
	}

	$page = get_page_by_path( 'trang-chu', OBJECT, 'page' );
	return $page ? (int) $page->ID : 0;
}

/**
 * Nội dung mặc định của trang chủ (Phụ lục A của docs/prompt-trang-chu.md).
 *
 * Khoá khớp từng trường và nhóm trong home-schema.php của ISHA Core: chuỗi cho text/textarea, true cho bool, 0 cho
 * image/video, array() cho gallery, danh sách đủ số phần tử cho nhóm lặp. Không có khoá `enabled` (công tắc do ISHA Core
 * tự thêm). Chỉ gọi sau `init` vì chuỗi được dịch ngay khi gọi.
 *
 * @param string $section Mã section; để trống để lấy mảng của tất cả section.
 * @return array Mặc định của section (array() nếu mã lạ), hoặc array( section => mặc định ) khi $section để trống.
 */
function isha_home_defaults( $section = '' ) {
	if ( '' === $section ) {
		$all = array();
		foreach ( isha_home_data_section_ids() as $id ) {
			$all[ $id ] = isha_home_defaults( $id );
		}
		return $all;
	}
	if ( ! is_string( $section ) ) {
		return array();
	}

	switch ( $section ) {
		case 'hero':
			return array(
				'intro_enabled'    => true,
				'intro_text'       => __( 'Một khoảng lặng bình tâm', 'isha' ),
				'video_landscape'  => 0,
				'video_portrait'   => 0,
				'poster_landscape' => 0,
				'poster_portrait'  => 0,
				'eyebrow'          => __( 'Thẩm mỹ chuẩn y khoa', 'isha' ),
				'title'            => __( 'Vẻ đẹp là một dấu ấn riêng', 'isha' ),
				'description'      => __( 'Tôn vinh nét đẹp tự nhiên theo tiêu chuẩn thẩm mỹ cân bằng, toàn diện và bền vững theo thời gian.', 'isha' ),
				'button_label'     => __( 'Đặt lịch tư vấn riêng', 'isha' ),
			);

		case 'space':
			return array(
				'title'      => __( 'Không gian ISHA', 'isha' ),
				'lead'       => __( 'Một nơi nghỉ ngơi và hồi phục cho mọi giác quan.', 'isha' ),
				'link_label' => __( 'Khám phá không gian ISHA', 'isha' ),
				'gallery'    => array(),
			);

		case 'results':
			return array(
				'title'   => __( 'Hiệu quả liệu trình', 'isha' ),
				'lead'    => __( 'Kết quả sau liệu trình, ghi lại bằng hình ảnh trước và sau.', 'isha' ),
				'gallery' => array(),
				'note'    => __( 'Hình ảnh thực tế của khách hàng đã đồng ý chia sẻ. Kết quả có thể khác nhau tùy cơ địa và phác đồ.', 'isha' ),
			);

		case 'services':
			return array(
				'title'      => __( 'Dịch vụ tiêu biểu', 'isha' ),
				'lead'       => __( 'Những hành trình chăm sóc tiêu biểu, cùng một chuẩn mực y khoa.', 'isha' ),
				'link_label' => __( 'Xem tất cả dịch vụ', 'isha' ),
			);

		case 'founder-story':
			// Câu trích và câu chuyện mặc định lấy từ section Tầm nhìn cũ (đã được duyệt); chờ nội dung chính thức của khách hàng.
			return array(
				'title'     => __( 'Câu chuyện nhà sáng lập', 'isha' ),
				'portrait'  => 0,
				'quote'     => __( 'ISHA tin rằng vẻ đẹp đích thực được kiến tạo từ những lựa chọn tinh tế, sự thấu hiểu bản thân và những giá trị bền vững vượt lên trên mọi xu hướng nhất thời.', 'isha' ),
				'story'     => __( 'ISHA mong muốn trở thành một khoảng lặng bình tâm, nơi vẻ đẹp được nâng niu bằng sự thấu hiểu, để mỗi người tìm thấy sự tự tin, nét đẹp riêng và phiên bản rạng rỡ nhất của chính mình.', 'isha' ),
				'name'      => __( 'Bác sĩ Linh Võ', 'isha' ),
				'role'      => __( 'Medical Director', 'isha' ),
				'signature' => 0,
			);

		// Ba section dưới đây không còn ở trang chủ (đã gỡ khỏi registry ngày 17/11/2026) nhưng giữ nội dung mặc định cho trang Giới thiệu
		// (dựng ở giai đoạn sau, dùng lại template và dữ liệu đã lưu _isha_home_story, _isha_home_philosophy, _isha_home_values).
		case 'story':
			return array(
				'title'     => __( 'Câu chuyện ISHA', 'isha' ),
				'year'      => __( '2024', 'isha' ),
				'main'      => __( 'Thành lập vào năm 2024, ISHA tin rằng vẻ đẹp của mỗi người là một dấu ấn riêng. Với triết lý tôn vinh nét đẹp tự nhiên của từng khách hàng, ISHA hướng đến tiêu chuẩn thẩm mỹ cân bằng, toàn diện và bền vững theo thời gian.', 'isha' ),
				'secondary' => __( 'Lấy danh xưng “Bác sĩ” để đặt tên cho thương hiệu như một lời cam kết chân thành về sự tận tâm và chuẩn mực y khoa. Từ chuyên môn, con người đến từng trải nghiệm, mỗi giá trị đều được chăm chút bằng sự chỉn chu và trách nhiệm. Bởi vì tại ISHA, vẻ đẹp không đơn thuần là sự hoàn thiện diện mạo, mà là hành trình nuôi dưỡng sự hài hòa giữa ngoại hình, nội tâm và phong cách sống.', 'isha' ),
			);

		case 'philosophy':
			return array(
				'title' => __( 'Triết lý ISHA', 'isha' ),
				'lead'  => __( 'Bốn chữ cái làm nên tên gọi, cũng là bốn cam kết của ISHA.', 'isha' ),
				'items' => array(
					array(
						'letter' => __( 'I', 'isha' ),
						'en'     => __( 'Inner Beauty', 'isha' ),
						'vi'     => __( 'Vẻ đẹp từ bên trong', 'isha' ),
						'desc'   => __( 'Vẻ đẹp bắt đầu từ sự bình an và tự tin bên trong; mọi liệu trình tại ISHA đều khởi đầu bằng sự lắng nghe.', 'isha' ),
					),
					array(
						'letter' => __( 'S', 'isha' ),
						'en'     => __( 'Sustainable', 'isha' ),
						'vi'     => __( 'Đẹp bền vững', 'isha' ),
						'desc'   => __( 'Không chạy theo xu hướng nhất thời, ISHA chọn những giải pháp giữ vẻ đẹp tự nhiên theo thời gian.', 'isha' ),
					),
					array(
						'letter' => __( 'H', 'isha' ),
						'en'     => __( 'Holistic', 'isha' ),
						'vi'     => __( 'Đẹp toàn diện', 'isha' ),
						'desc'   => __( 'Chăm sóc sự hài hòa giữa ngoại hình, nội tâm và phong cách sống, thay vì một chi tiết riêng lẻ.', 'isha' ),
					),
					array(
						'letter' => __( 'A', 'isha' ),
						'en'     => __( 'Aesthetic', 'isha' ),
						'vi'     => __( 'Thẩm mỹ chuẩn y khoa', 'isha' ),
						'desc'   => __( 'Mỗi quyết định thẩm mỹ đều dựa trên thăm khám của bác sĩ và chuẩn mực y khoa.', 'isha' ),
					),
				),
			);

		case 'values':
			return array(
				'title' => __( 'Giá trị cốt lõi', 'isha' ),
				'items' => array(
					array(
						'name'  => __( 'Chăm sóc da chuẩn y khoa', 'isha' ),
						'desc'  => __( 'Mỗi khách hàng đều được bác sĩ trực tiếp thăm khám, đánh giá tình trạng và xây dựng phác đồ điều trị.', 'isha' ),
						'image' => 0,
					),
					array(
						'name'  => __( 'Cá nhân hóa', 'isha' ),
						'desc'  => __( 'Mỗi gương mặt, làn da đều có nhu cầu khác nhau, và luôn được cung cấp giải pháp phù hợp nhất.', 'isha' ),
						'image' => 0,
					),
					array(
						'name'  => __( 'Công nghệ & sản phẩm chính hãng', 'isha' ),
						'desc'  => __( 'ISHA luôn cam kết mỹ phẩm và vật liệu thẩm mỹ uy tín nhất, kết hợp cùng công nghệ hiện đại luôn được nâng cấp và bảo trì.', 'isha' ),
						'image' => 0,
					),
					array(
						'name'  => __( 'Bác sĩ đồng hành', 'isha' ),
						'desc'  => __( 'Từ tư vấn đến theo dõi sau liệu trình, khách hàng luôn được bác sĩ và đội ngũ chuyên môn đồng hành xuyên suốt.', 'isha' ),
						'image' => 0,
					),
					array(
						'name'  => __( 'Trải nghiệm đa giác quan', 'isha' ),
						'desc'  => __( 'Không chỉ là phòng khám hiện đại, ISHA được xây dựng như một nơi nghỉ ngơi và hồi phục cho mọi giác quan.', 'isha' ),
						'image' => 0,
					),
				),
			);

		case 'process':
			return array(
				'title' => __( 'Quy trình thăm khám da tại ISHA', 'isha' ),
				'items' => array(
					array(
						'name' => __( 'Soi da – phân tích da', 'isha' ),
						'desc' => __( 'Bác sĩ soi và phân tích để đánh giá chính xác tình trạng làn da.', 'isha' ),
					),
					array(
						'name' => __( 'Tư vấn phác đồ điều trị cá nhân', 'isha' ),
						'desc' => __( 'Phác đồ được xây dựng riêng theo tình trạng da và mong muốn của từng người.', 'isha' ),
					),
					array(
						'name' => __( 'Thực hiện liệu trình điều trị', 'isha' ),
						'desc' => __( 'Liệu trình do bác sĩ và đội ngũ chuyên môn thực hiện theo đúng phác đồ.', 'isha' ),
					),
					array(
						'name' => __( 'Chăm sóc da sau liệu trình', 'isha' ),
						'desc' => __( 'Hướng dẫn chăm sóc giúp làn da phục hồi và giữ kết quả lâu dài.', 'isha' ),
					),
					array(
						'name' => __( 'Thăm khám định kỳ sau liệu trình', 'isha' ),
						'desc' => __( 'Tái khám định kỳ để theo dõi tiến triển và điều chỉnh kịp thời.', 'isha' ),
					),
				),
			);

		case 'events':
			return array(
				'title' => __( 'Workshop & sự kiện', 'isha' ),
				'lead'  => __( 'Những buổi gặp gỡ dành riêng cho cộng đồng ISHA.', 'isha' ),
			);

		case 'articles':
			return array(
				'title'      => __( 'Tin tức', 'isha' ),
				'link_label' => __( 'Xem tất cả tin tức', 'isha' ),
			);

		case 'testimonials':
			return array(
				'title' => __( 'Những câu chuyện', 'isha' ),
			);

		case 'cta':
			return array(
				'title'            => __( 'Khởi đầu bằng một buổi tư vấn riêng', 'isha' ),
				'description'      => __( 'Bác sĩ trực tiếp thăm khám và cùng Quý khách xây dựng phác đồ phù hợp nhất.', 'isha' ),
				'background_image' => 0,
				'background_video' => 0,
			);

		case 'partners':
			return array(
				'title'   => __( 'Đồng hành cùng ISHA', 'isha' ),
				'gallery' => array(),
			);
	}

	return array();
}

/**
 * Dữ liệu một section: giá trị đã lưu trộn với mặc định, kèm phần dựng sẵn từ CPT/media.
 *
 * Thứ tự ưu tiên từng trường: giá trị đã lưu ở trang chủ > Customizer cũ (chỉ hero và cta, chỉ khi đã đặt thật) > mặc định
 * Phụ lục A. Trường rỗng, chỉ khoảng trắng, "0" hoặc mảng rỗng thì rơi về mức kế tiếp; trường bool dùng giá trị đã lưu nếu có.
 * Nhóm lặp trộn theo từng phần tử và từng trường con, luôn đủ số phần tử của mặc định.
 *
 * Trả array() (ẨN section) khi: mã section lạ; section bị tắt trong CMS; section dùng CPT mà ISHA Core tắt hoặc thiếu CPT/class
 * tương ứng; hoặc không có dữ liệu bắt buộc (không có nhóm dịch vụ được tick "Dịch vụ tiêu biểu", sự kiện, bài viết, lời kể, ảnh không
 * gian, ảnh trước/sau, logo đối tác). Còn lại luôn có 'section' => mã và đúng các khoá của hợp đồng (docs/home-architecture.md, mục 4
 * và 15). Giá trị là văn bản thô. Kết quả đi qua bộ lọc "isha/home/data/{$section}" ( $data, $section ); bộ lọc trả giá trị không phải
 * mảng thì bỏ qua.
 *
 * @param string $section Mã section (xem isha_home_sections_base()): hero, space, results, services, founder-story, events, articles,
 *                        testimonials, process, partners, cta.
 * @param array  $args    Tuỳ chọn: count (events 5, articles 4, testimonials 6; tối đa 50).
 * @return array
 */
function isha_home_data( $section, $args = array() ) {
	if ( ! is_string( $section ) || ! in_array( $section, isha_home_data_section_ids(), true ) ) {
		return array();
	}
	$args = is_array( $args ) ? $args : array();

	// Section dùng CPT: ISHA Core tắt thì ẩn hẳn.
	if ( in_array( $section, array( 'services', 'events', 'articles', 'testimonials' ), true ) && ! isha_core_active() ) {
		return array();
	}

	$stored = isha_home_data_stored( $section );
	if ( ! isha_home_data_is_enabled( $stored ) ) {
		return array();
	}

	// Customizer đã đặt thật đứng giữa giá trị đã lưu và mặc định.
	$defaults = array_merge( isha_home_defaults( $section ), isha_home_data_customizer( $section ) );
	$data     = isha_home_data_build( $section, isha_home_data_merge( $defaults, $stored ), $args );
	if ( ! $data ) {
		return array();
	}

	$filtered = apply_filters( "isha/home/data/{$section}", $data, $section );
	return is_array( $filtered ) ? $filtered : $data;
}

/**
 * Thông tin liên hệ cho nút đặt lịch, Zalo và hotline.
 *
 * - booking_url: isha_booking_url().
 * - phone: số điện thoại để hiển thị (isha_opt( 'phone' )).
 * - tel: chỉ chữ số, dấu + chỉ giữ nếu đứng đầu; rỗng nếu không có chữ số.
 * - tel_url: 'tel:' . tel, hoặc rỗng.
 * - zalo_url: isha_opt( 'zalo' ) qua esc_url_raw, http đổi thành https; rỗng nếu trống, giao thức lạ hoặc không phải địa chỉ
 *   có tên miền (Zalo là URL, không phải số điện thoại).
 *
 * @return array array( booking_url, phone, tel, tel_url, zalo_url ).
 */
function isha_home_contact() {
	$phone = isha_opt( 'phone' );
	$phone = is_scalar( $phone ) ? trim( (string) $phone ) : '';

	$tel = (string) preg_replace( '/[^0-9+]/', '', $phone );
	$tel = ( 0 === strpos( $tel, '+' ) ? '+' : '' ) . str_replace( '+', '', $tel );
	if ( ! preg_match( '/[0-9]/', $tel ) ) {
		$tel = '';
	}

	return array(
		'booking_url' => (string) isha_booking_url(),
		'phone'       => $phone,
		'tel'         => $tel,
		'tel_url'     => '' !== $tel ? 'tel:' . $tel : '',
		'zalo_url'    => isha_home_data_zalo_url( isha_opt( 'zalo' ) ),
	);
}

/* ------------------------------------------------------------------------------------------------------------------
 * Hàm nội bộ (không thuộc hợp đồng)
 * --------------------------------------------------------------------------------------------------------------- */

/**
 * Mã các section theo thứ tự trên trang: lấy từ registry gốc (isha_home_sections_base()), nên thêm hoặc bớt section chỉ cần sửa registry
 * (cùng schema ở ISHA Core và mặc định ở isha_home_defaults()); khớp home-schema.php.
 *
 * @return string[]
 */
function isha_home_data_section_ids() {
	return array_keys( isha_home_sections_base() );
}

/**
 * Class có tồn tại và có phương thức tĩnh công khai này không.
 *
 * @param string $class_name Tên class.
 * @param string $method     Tên phương thức.
 * @return bool
 */
function isha_home_data_has_method( $class_name, $method ) {
	return class_exists( $class_name ) && is_callable( array( $class_name, $method ) );
}

/**
 * Chuẩn hoá một giá trị văn bản đã lưu: cắt khoảng trắng hai đầu, bỏ byte UTF-8 lỗi. Rỗng ('') nếu không phải chuỗi/số,
 * rỗng, "0" hoặc chỉ gồm khoảng trắng (kể cả NBSP và ký tự rộng bằng không). Giữ nguyên xuống dòng bên trong.
 *
 * @param mixed $value Giá trị thô.
 * @return string
 */
function isha_home_data_text( $value ) {
	if ( is_int( $value ) || is_float( $value ) ) {
		$value = (string) $value;
	}
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = trim( wp_check_invalid_utf8( $value, true ) );
	if ( '' === $value || '0' === $value || preg_match( '/^[\s\p{Z}\x{200B}-\x{200D}\x{2060}\x{FEFF}]+$/u', $value ) ) {
		return '';
	}
	return $value;
}

/**
 * Văn bản thuần một dòng cho tiêu đề, tên, địa điểm...: bỏ thẻ HTML, giải mã thực thể (&amp; thành &), gộp khoảng trắng.
 *
 * @param mixed $value Giá trị thô.
 * @return string
 */
function isha_home_data_plain( $value ) {
	if ( is_int( $value ) || is_float( $value ) ) {
		$value = (string) $value;
	}
	if ( ! is_string( $value ) ) {
		return '';
	}
	$text = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = (string) preg_replace( '/[\s\x{00A0}]+/u', ' ', wp_check_invalid_utf8( $text, true ) );
	return trim( $text );
}

/**
 * Số nguyên dương từ số nguyên, số thực nguyên hoặc chuỗi chỉ gồm chữ số; mọi trường hợp khác là 0.
 *
 * @param mixed $value Giá trị thô.
 * @return int
 */
function isha_home_data_id( $value ) {
	if ( is_int( $value ) ) {
		return $value > 0 ? $value : 0;
	}
	if ( is_float( $value ) ) {
		return ( $value >= 1 && $value < 9.0E+15 && floor( $value ) === $value ) ? (int) $value : 0;
	}
	if ( is_string( $value ) ) {
		$value = trim( $value );
		return preg_match( '/^[0-9]+$/', $value ) ? (int) $value : 0;
	}
	return 0;
}

/**
 * ID của một tệp đính kèm có thật, hoặc 0.
 *
 * @param mixed $value Giá trị thô.
 * @return int
 */
function isha_home_data_attachment( $value ) {
	$id = isha_home_data_id( $value );
	return ( $id > 0 && 'attachment' === get_post_type( $id ) ) ? $id : 0;
}

/**
 * Timestamp hợp lệ (0 nếu không có, âm hoặc quá năm 2100).
 *
 * @param mixed $value Giá trị thô.
 * @return int
 */
function isha_home_data_timestamp( $value ) {
	$timestamp = isha_home_data_id( $value );
	return $timestamp <= 4102444800 ? $timestamp : 0;
}

/**
 * Giá trị bool đã lưu: true/false nếu nhận ra được, null nếu không (để dùng mặc định).
 *
 * @param mixed $value Giá trị thô (bool, số, hoặc '1'/'0'/'true'/'false'/'on'/'off'/'yes'/'no').
 * @return bool|null
 */
function isha_home_data_bool( $value ) {
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( is_int( $value ) || is_float( $value ) ) {
		return (bool) $value;
	}
	if ( is_string( $value ) ) {
		$flag = strtolower( trim( $value ) );
		if ( in_array( $flag, array( '1', 'true', 'on', 'yes' ), true ) ) {
			return true;
		}
		if ( in_array( $flag, array( '0', 'false', 'off', 'no' ), true ) ) {
			return false;
		}
	}
	return null;
}

/**
 * Số mục cần lấy từ $args['count']: số nguyên dương, tối đa 50; sai hoặc thiếu thì dùng mặc định.
 *
 * @param array $args    Tham số của isha_home_data().
 * @param int   $default Số mặc định của section.
 * @return int
 */
function isha_home_data_count( array $args, $default ) {
	$count = isset( $args['count'] ) ? isha_home_data_id( $args['count'] ) : 0;
	return $count > 0 ? min( $count, 50 ) : (int) $default;
}

/**
 * Giá trị đã lưu của một section: ISHA_Core_Home_Fields::get() nếu có, không thì đọc thẳng meta của trang chủ.
 *
 * @param string $section Mã section.
 * @return array array() nếu chưa lưu gì hoặc dữ liệu không phải mảng.
 */
function isha_home_data_stored( $section ) {
	$page_id = isha_home_page_id();
	if ( $page_id < 1 ) {
		return array();
	}
	if ( isha_home_data_has_method( 'ISHA_Core_Home_Fields', 'get' ) ) {
		$stored = ISHA_Core_Home_Fields::get( $section, $page_id );
	} else {
		$stored = get_post_meta( $page_id, '_isha_home_' . $section, true );
	}
	return is_array( $stored ) ? $stored : array();
}

/**
 * Section còn bật không: chỉ tắt khi giá trị `enabled` đã lưu là false ('0'); không có khoá nghĩa là bật.
 *
 * @param array $stored Giá trị đã lưu.
 * @return bool
 */
function isha_home_data_is_enabled( array $stored ) {
	return ! ( array_key_exists( 'enabled', $stored ) && false === isha_home_data_bool( $stored['enabled'] ) );
}

/**
 * Giá trị Customizer cũ đã đặt thật cho hero và cta (theme_mod tồn tại và không rỗng sau khi cắt khoảng trắng).
 * Không dùng isha_mod() vì hàm đó chèn giá trị mặc định của Customizer.
 *
 * @param string $section Mã section.
 * @return array trường => chuỗi; rỗng với section khác hoặc khi chưa đặt gì.
 */
function isha_home_data_customizer( $section ) {
	$map = array(
		'hero' => array(
			'eyebrow'      => 'isha_hero_eyebrow',
			'title'        => 'isha_hero_title',
			'description'  => 'isha_hero_subtitle',
			'button_label' => 'isha_hero_btn_text',
		),
		'cta'  => array(
			'title'       => 'isha_cta_title',
			'description' => 'isha_cta_text',
		),
	);

	$out = array();
	if ( isset( $map[ $section ] ) ) {
		foreach ( $map[ $section ] as $field => $mod ) {
			$text = isha_home_data_text( get_theme_mod( $mod, null ) );
			if ( '' !== $text ) {
				$out[ $field ] = $text;
			}
		}
	}
	return $out;
}

/**
 * Trộn giá trị đã lưu vào mặc định, theo từng trường. Chỉ giữ các khoá của mặc định (khoá lạ trong dữ liệu lưu bị bỏ).
 *
 * @param array $defaults Mặc định (đã gồm Customizer nếu có).
 * @param array $stored   Giá trị đã lưu.
 * @return array
 */
function isha_home_data_merge( array $defaults, array $stored ) {
	$out = array();
	foreach ( $defaults as $key => $default ) {
		$value = array_key_exists( $key, $stored ) ? $stored[ $key ] : null;

		if ( is_bool( $default ) ) {
			$flag        = isha_home_data_bool( $value );
			$out[ $key ] = null === $flag ? $default : $flag;
		} elseif ( is_int( $default ) ) {
			$id          = isha_home_data_attachment( $value );
			$out[ $key ] = $id > 0 ? $id : $default;
		} elseif ( array() === $default ) {
			// Mặc định là mảng rỗng: danh sách ID ảnh (gallery); việc kiểm tệp và giới hạn số ảnh do hàm dựng section làm.
			$out[ $key ] = isha_home_data_gallery_candidates( $value );
		} elseif ( is_array( $default ) ) {
			$out[ $key ] = isha_home_data_merge_group( $default, $value );
		} else {
			$text        = isha_home_data_text( $value );
			$out[ $key ] = '' !== $text ? $text : $default;
		}
	}
	return $out;
}

/**
 * Trộn nhóm lặp: theo từng chỉ số và từng trường con; luôn trả đủ số phần tử của mặc định.
 *
 * @param array $default_items Mặc định: danh sách các mảng trường con.
 * @param mixed $stored        Giá trị đã lưu của nhóm (danh sách mảng; giá trị khác bị bỏ qua).
 * @return array
 */
function isha_home_data_merge_group( array $default_items, $stored ) {
	$out = array();
	foreach ( $default_items as $index => $default_item ) {
		$stored_item   = ( is_array( $stored ) && isset( $stored[ $index ] ) && is_array( $stored[ $index ] ) ) ? $stored[ $index ] : array();
		$out[ $index ] = isha_home_data_merge( $default_item, $stored_item );
	}
	return $out;
}

/**
 * Dựng dữ liệu cuối cùng của section từ phần đã trộn.
 *
 * @param string $section Mã section (đã kiểm tra).
 * @param array  $merged  Dữ liệu đã trộn với mặc định.
 * @param array  $args    Tham số của isha_home_data().
 * @return array array() nếu section phải ẩn.
 */
function isha_home_data_build( $section, array $merged, array $args ) {
	switch ( $section ) {
		case 'services':
			return isha_home_data_build_services( $merged );
		case 'space':
			return isha_home_data_build_space( $merged );
		case 'results':
			return isha_home_data_build_results( $merged );
		case 'events':
			return isha_home_data_build_events( $merged, $args );
		case 'articles':
			return isha_home_data_build_articles( $merged, $args );
		case 'testimonials':
			return isha_home_data_build_testimonials( $merged, $args );
		case 'cta':
			return array( 'section' => 'cta' ) + $merged + isha_home_contact();
		case 'partners':
			return isha_home_data_build_partners( $merged );
	}
	// hero, founder-story, process: chỉ gồm các trường đã trộn.
	return array( 'section' => $section ) + $merged;
}

/* --- Dịch vụ ------------------------------------------------------------------------------------------------------ */

/**
 * Có đang xem trước bởi người sửa được bài không (khi đó mới hiện thêm liệu trình nháp).
 * isha_home_is_preview() được thêm ở giai đoạn sau nên luôn bọc function_exists.
 *
 * @return bool
 */
function isha_home_data_preview_drafts() {
	return function_exists( 'isha_home_is_preview' ) && isha_home_is_preview() && current_user_can( 'edit_posts' );
}

/**
 * Section Dịch vụ tiêu biểu: chỉ các nhóm (term isha_service_cat) được tick "Dịch vụ tiêu biểu" (ISHA_Core_Term_Fields::get()['featured']),
 * theo thứ tự ISHA_Core_Term_Fields::ordered_terms(); mỗi nhóm kèm liệu trình theo menu_order rồi tên. Nhóm chưa có liệu trình vẫn được
 * giữ với items rỗng. Ảnh trước/sau của nhóm không còn hiện ở đây (trang chủ có section Hiệu quả liệu trình riêng); trang nhóm dịch vụ
 * vẫn dùng chúng.
 *
 * @param array $merged Dữ liệu đã trộn (title, lead, link_label).
 * @return array array() nếu thiếu CPT/taxonomy/class hoặc chưa nhóm nào được tick.
 */
function isha_home_data_build_services( array $merged ) {
	if ( ! post_type_exists( 'isha_service' ) || ! taxonomy_exists( 'isha_service_cat' ) || ! isha_home_data_has_method( 'ISHA_Core_Term_Fields', 'ordered_terms' ) || ! isha_home_data_has_method( 'ISHA_Core_Term_Fields', 'get' ) ) {
		return array();
	}

	$terms  = ISHA_Core_Term_Fields::ordered_terms();
	$picked = array();
	foreach ( is_array( $terms ) ? $terms : array() as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		$extra = ISHA_Core_Term_Fields::get( $term->term_id );
		if ( is_array( $extra ) && ! empty( $extra['featured'] ) ) {
			$picked[] = array(
				'term'  => $term,
				'extra' => $extra,
			);
		}
	}
	if ( ! $picked ) {
		return array();
	}

	$items = isha_home_data_service_items( array_map( fn( $row ) => (int) $row['term']->term_id, $picked ) );

	$groups = array();
	foreach ( $picked as $row ) {
		$term  = $row['term'];
		$extra = $row['extra'];
		$link  = get_term_link( $term );

		$groups[] = array(
			'id'             => (int) $term->term_id,
			'slug'           => (string) $term->slug,
			'name'           => isha_home_data_plain( $term->name ),
			'tagline'        => isha_home_data_plain( $term->description ),
			'cover_id'       => isha_home_data_attachment( isset( $extra['cover_id'] ) ? $extra['cover_id'] : 0 ),
			'cover_video_id' => isha_home_data_attachment( isset( $extra['cover_video_id'] ) ? $extra['cover_video_id'] : 0 ),
			'url'            => is_wp_error( $link ) ? '' : (string) $link,
			'items'          => isset( $items[ $term->term_id ] ) ? $items[ $term->term_id ] : array(),
		);
	}

	return array(
		'section'    => 'services',
		'title'      => $merged['title'],
		'lead'       => $merged['lead'],
		'link_label' => $merged['link_label'],
		'all_url'    => isha_home_data_services_url(),
		'groups'     => $groups,
	);
}

/**
 * Địa chỉ trang Dịch vụ tổng (trang đã xuất bản đầu tiên dùng mẫu "Dịch vụ", xem isha_services_page_id()); '' nếu không có.
 *
 * @return string
 */
function isha_home_data_services_url() {
	$page_id = function_exists( 'isha_services_page_id' ) ? (int) isha_services_page_id( 'templates/template-services.php' ) : 0;
	$url     = $page_id > 0 ? get_permalink( $page_id ) : '';
	return is_string( $url ) ? $url : '';
}

/**
 * Liệu trình của các nhóm, gom theo term. Chỉ bài đã xuất bản; thêm bài nháp/chờ duyệt/hẹn giờ (draft true) khi đang
 * xem trước bởi người có quyền edit_posts. Một bài thuộc nhiều nhóm xuất hiện ở từng nhóm.
 *
 * @param int[] $term_ids ID các term isha_service_cat.
 * @return array term_id => danh sách array( id, title, url, draft ).
 */
function isha_home_data_service_items( array $term_ids ) {
	$statuses = isha_home_data_preview_drafts() ? array( 'publish', 'draft', 'pending', 'future' ) : array( 'publish' );

	$query = new WP_Query(
		array(
			'post_type'              => 'isha_service',
			'post_status'            => $statuses,
			'posts_per_page'         => 200,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
				'ID'         => 'ASC',
			),
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => true,
			'lazy_load_term_meta'    => false,
			'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy'         => 'isha_service_cat',
					'field'            => 'term_id',
					'terms'            => $term_ids,
					'include_children' => false,
				),
			),
		)
	);

	$by_term = array();
	foreach ( $query->posts as $post ) {
		$title      = isha_home_data_plain( get_the_title( $post ) );
		$post_terms = get_the_terms( $post, 'isha_service_cat' );
		if ( '' === $title || ! is_array( $post_terms ) ) {
			continue;
		}
		$item = array(
			'id'    => (int) $post->ID,
			'title' => $title,
			'url'   => (string) get_permalink( $post ),
			'draft' => 'publish' !== $post->post_status,
		);
		foreach ( $post_terms as $post_term ) {
			if ( in_array( (int) $post_term->term_id, $term_ids, true ) ) {
				$by_term[ (int) $post_term->term_id ][] = $item;
			}
		}
	}
	return $by_term;
}

/* --- Không gian và đối tác (gallery) ------------------------------------------------------------------------------ */

/**
 * ID ảnh ứng viên từ giá trị gallery đã lưu (mảng hoặc chuỗi CSV): số nguyên dương, không trùng, giữ thứ tự, tối đa 50.
 * Chưa kiểm tệp có thật hay không.
 *
 * @param mixed $raw Giá trị thô.
 * @return int[]
 */
function isha_home_data_gallery_candidates( $raw ) {
	if ( is_string( $raw ) ) {
		$raw = explode( ',', $raw );
	}
	if ( ! is_array( $raw ) ) {
		return array();
	}
	$ids = array();
	foreach ( array_slice( array_values( $raw ), 0, 50 ) as $value ) {
		$id = isha_home_data_id( $value );
		if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
			$ids[] = $id;
		}
	}
	return $ids;
}

/**
 * ID các ảnh hợp lệ của một gallery: bỏ tệp không tồn tại hoặc không phải ảnh, giữ thứ tự, tối đa $max.
 *
 * @param int[] $candidates ID ứng viên.
 * @param int   $max        Số ảnh tối đa (khớp `max` của trường trong home-schema.php).
 * @return int[]
 */
function isha_home_data_gallery_images( array $candidates, $max ) {
	$ids = array();
	foreach ( $candidates as $id ) {
		if ( wp_attachment_is_image( $id ) ) {
			$ids[] = (int) $id;
			if ( count( $ids ) >= $max ) {
				break;
			}
		}
	}
	return $ids;
}

/**
 * Section Không gian: các bài Không gian ISHA được đánh dấu "Xuất hiện bài này ở trang chủ" (từ 18/11/2026; ảnh đại diện, tên bài là chú
 * thích, bấm vào mở bài), theo thứ tự hiển thị của bài, tối đa 12. Chưa đánh dấu bài nào thì dùng ảnh dự phòng ở ô gallery (chú thích lấy
 * từ Chú thích của ảnh trong Thư viện phương tiện, không có liên kết). all_url là trang Không gian ISHA ( nếu chưa có trang).
 *
 * @param array $merged Dữ liệu đã trộn (title, lead, link_label, gallery).
 * @return array array() nếu không có ảnh hợp lệ.
 */
function isha_home_data_build_space( array $merged ) {
	$images = array();
	$posts  = function_exists( 'isha_space_posts' ) ? isha_space_posts( true ) : array();
	foreach ( $posts as $post ) {
		$item = isha_space_item( $post );
		if ( $item['image'] < 1 || ! wp_attachment_is_image( $item['image'] ) ) {
			continue;
		}
		// Ảnh là trang trí: tên bài trong chú thích đã là tên của liên kết.
		$images[] = array(
			'id'      => $item['image'],
			'caption' => $item['title'],
			'alt'     => '',
			'url'     => $item['url'],
		);
	}
	if ( ! $images ) {
		foreach ( isha_home_data_gallery_images( $merged['gallery'], 12 ) as $id ) {
			$images[] = array(
				'id'      => $id,
				'caption' => isha_home_data_plain( wp_get_attachment_caption( $id ) ),
				'alt'     => isha_home_data_plain( get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
				'url'     => '',
			);
		}
	}
	if ( ! $images ) {
		return array();
	}

	return array(
		'section'    => 'space',
		'title'      => $merged['title'],
		'lead'       => $merged['lead'],
		'link_label' => isha_home_data_plain( $merged['link_label'] ),
		'all_url'    => function_exists( 'isha_space_list_url' ) ? isha_space_list_url() : '',
		'images'     => $images,
	);
}

/**
 * Section Hiệu quả liệu trình: tối đa 5 ảnh trước/sau ĐÃ GHÉP SẴN (mỗi ảnh một tấm, không ghép cặp ở đây). Alt và chú thích lấy từ Thư
 * viện phương tiện; ghi chú miễn trừ là văn bản thuần một dòng.
 *
 * @param array $merged Dữ liệu đã trộn (title, lead, gallery, note).
 * @return array array() nếu không có ảnh hợp lệ.
 */
function isha_home_data_build_results( array $merged ) {
	$images = array();
	foreach ( isha_home_data_gallery_images( $merged['gallery'], 5 ) as $id ) {
		$images[] = array(
			'id'      => $id,
			'caption' => isha_home_data_plain( wp_get_attachment_caption( $id ) ),
			'alt'     => isha_home_data_plain( get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
		);
	}
	if ( ! $images ) {
		return array();
	}

	return array(
		'section' => 'results',
		'title'   => $merged['title'],
		'lead'    => $merged['lead'],
		'note'    => isha_home_data_plain( $merged['note'] ),
		'images'  => $images,
	);
}

/**
 * Section Đối tác: logo từ gallery (chỉ ID và alt; tên đối tác lấy từ alt).
 *
 * @param array $merged Dữ liệu đã trộn (title, gallery).
 * @return array array() nếu không có logo hợp lệ.
 */
function isha_home_data_build_partners( array $merged ) {
	$images = array();
	foreach ( isha_home_data_gallery_images( $merged['gallery'], 16 ) as $id ) {
		$images[] = array(
			'id'  => $id,
			'alt' => isha_home_data_plain( get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
		);
	}
	if ( ! $images ) {
		return array();
	}

	return array(
		'section' => 'partners',
		'title'   => $merged['title'],
		'images'  => $images,
	);
}

/* --- Bài đăng (sự kiện, bài viết, lời kể) ------------------------------------------------------------------------- */

/**
 * Tên của term đầu tiên của bài trong một taxonomy ('' nếu không có hoặc taxonomy chưa đăng ký).
 *
 * @param WP_Post $post     Bài.
 * @param string  $taxonomy Taxonomy.
 * @return string
 */
function isha_home_data_first_term_name( $post, $taxonomy ) {
	$terms = get_the_terms( $post, $taxonomy );
	if ( ! is_array( $terms ) ) {
		return '';
	}
	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term ) {
			return isha_home_data_plain( $term->name );
		}
	}
	return '';
}

/**
 * Đoạn tóm tắt của bài, không phụ thuộc vòng lặp: trường "Nội dung ngắn" (nếu post type có và đã nhập; cùng đoạn với thẻ bài ở
 * trang danh sách, xem isha_editorial_stored_summary()), không có thì post_excerpt, không có nữa thì 28 từ đầu của nội dung đã
 * bỏ thẻ và shortcode. Bài đặt mật khẩu thì không lộ nội dung.
 *
 * @param WP_Post $post Bài.
 * @return string Văn bản thuần.
 */
function isha_home_data_excerpt( $post ) {
	if ( post_password_required( $post ) ) {
		return '';
	}
	if ( function_exists( 'isha_editorial_stored_summary' ) ) {
		$summary = isha_editorial_stored_summary( $post );
		if ( '' !== $summary ) {
			return $summary;
		}
	}
	$excerpt = isha_home_data_plain( $post->post_excerpt );
	if ( '' !== $excerpt ) {
		return $excerpt;
	}
	return wp_trim_words( isha_home_data_plain( strip_shortcodes( (string) $post->post_content ) ), 28, '…' );
}

/**
 * Định dạng ngày của site (rơi về d/m/Y nếu chưa đặt).
 *
 * @return string
 */
function isha_home_data_date_format() {
	$format = get_option( 'date_format' );
	return ( is_string( $format ) && '' !== trim( $format ) ) ? $format : 'd/m/Y';
}

/**
 * Section Sự kiện: chỉ sự kiện nổi bật đã xuất bản. Sự kiện chưa diễn ra (theo end_ts nếu có, không thì start_ts) đứng
 * trước, theo ngày bắt đầu tăng dần; rồi sự kiện chưa có ngày; sau cùng là sự kiện đã qua, gần nhất trước. Tối đa count.
 *
 * @param array $merged Dữ liệu đã trộn (title, lead).
 * @param array $args   Tham số: count (mặc định 5).
 * @return array array() nếu thiếu CPT/class hoặc không có sự kiện.
 */
function isha_home_data_build_events( array $merged, array $args ) {
	if ( ! post_type_exists( 'isha_event' ) || ! isha_home_data_has_method( 'ISHA_Core_Event_Fields', 'get' ) ) {
		return array();
	}

	$count = isha_home_data_count( $args, 5 );
	$now   = time();
	$query = new WP_Query(
		array(
			'post_type'              => 'isha_event',
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'orderby'                => array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			),
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
			'lazy_load_term_meta'    => false,
			'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'   => '_isha_event_featured',
					'value' => '1',
				),
			),
		)
	);

	$upcoming = array();
	$undated  = array();
	$past     = array();
	foreach ( $query->posts as $post ) {
		$fields   = ISHA_Core_Event_Fields::get( $post->ID );
		$fields   = is_array( $fields ) ? $fields : array();
		$start_ts = isha_home_data_timestamp( isset( $fields['start_ts'] ) ? $fields['start_ts'] : 0 );
		$end_ts   = isha_home_data_timestamp( isset( $fields['end_ts'] ) ? $fields['end_ts'] : 0 );
		if ( $start_ts > 0 && $end_ts <= $start_ts ) {
			$end_ts = 0; // Kết thúc phải sau bắt đầu.
		}
		$until = $end_ts > 0 ? $end_ts : $start_ts;
		$is_past = $until > 0 && $until < $now;

		$row = array(
			'key'  => $start_ts > 0 ? $start_ts : $end_ts,
			'id'   => (int) $post->ID,
			'item' => array(
				'id'             => (int) $post->ID,
				'title'          => isha_home_data_plain( get_the_title( $post ) ),
				'excerpt'        => isha_home_data_excerpt( $post ),
				'type'           => isha_home_data_first_term_name( $post, 'isha_event_type' ),
				'start_ts'       => $start_ts,
				'end_ts'         => $end_ts,
				'date_label'     => $start_ts > 0 ? (string) wp_date( isha_home_data_date_format(), $start_ts ) : '',
				'time_label'     => $start_ts > 0 ? (string) wp_date( 'H:i', $start_ts ) : '',
				// Giờ kết thúc; sự kiện kéo dài sang ngày khác thì kèm ngày kết thúc ("14/11/2026 lúc 09:00 – 16/11/2026 17:00"), nếu không ngày kết thúc bị mất.
				'end_time_label' => $end_ts > 0 ? (string) wp_date( ( $start_ts > 0 && wp_date( 'Y-m-d', $start_ts ) !== wp_date( 'Y-m-d', $end_ts ) ) ? isha_home_data_date_format() . ' H:i' : 'H:i', $end_ts ) : '',
				'location'       => isha_home_data_plain( isset( $fields['location'] ) ? $fields['location'] : '' ),
				'register_url'   => isset( $fields['register_url'] ) && is_string( $fields['register_url'] ) ? esc_url_raw( trim( $fields['register_url'] ) ) : '',
				'url'            => (string) get_permalink( $post ),
				'image_id'       => (int) get_post_thumbnail_id( $post ),
				'portrait_id'    => isha_home_data_attachment( isset( $fields['portrait_id'] ) ? $fields['portrait_id'] : 0 ),
				'past'           => $is_past,
			),
		);

		if ( 0 === $until ) {
			$undated[] = $row;
		} elseif ( $is_past ) {
			$past[] = $row;
		} else {
			$upcoming[] = $row;
		}
	}

	// Sắp theo (ngày bắt đầu, ID); sự kiện chưa có ngày giữ thứ tự bài mới nhất trước.
	usort(
		$upcoming,
		static function ( $a, $b ) {
			return array( $a['key'], $a['id'] ) <=> array( $b['key'], $b['id'] );
		}
	);
	usort(
		$past,
		static function ( $a, $b ) {
			return array( $b['key'], $b['id'] ) <=> array( $a['key'], $a['id'] );
		}
	);

	$events = array();
	foreach ( array_slice( array_merge( $upcoming, $undated, $past ), 0, $count ) as $row ) {
		$events[] = $row['item'];
	}
	if ( ! $events ) {
		return array();
	}

	return array(
		'section' => 'events',
		'title'   => $merged['title'],
		'lead'    => $merged['lead'],
		'events'  => $events,
	);
}

/**
 * Section Bài viết: bài đánh dấu nổi bật đứng đầu (mới nhất trước), rồi các bài mới nhất; tổng cộng tối đa count.
 * Bài đầu tiên là `featured`, các bài sau (tối đa count - 1) là `list`.
 *
 * @param array $merged Dữ liệu đã trộn (title, link_label).
 * @param array $args   Tham số: count (mặc định 4).
 * @return array array() nếu thiếu CPT/class hoặc chưa có bài nào.
 */
function isha_home_data_build_articles( array $merged, array $args ) {
	if ( ! post_type_exists( 'isha_article' ) || ! isha_home_data_has_method( 'ISHA_Core_Article_Fields', 'get' ) ) {
		return array();
	}

	$count = isha_home_data_count( $args, 4 );
	$base  = array(
		'post_type'              => 'isha_article',
		'post_status'            => 'publish',
		'orderby'                => array(
			'date' => 'DESC',
			'ID'   => 'DESC',
		),
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
		'lazy_load_term_meta'    => false,
	);

	$featured_query = new WP_Query(
		array_merge(
			$base,
			array(
				'posts_per_page' => $count,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'key'   => '_isha_article_featured',
						'value' => '1',
					),
				),
			)
		)
	);
	$posts          = $featured_query->posts;

	$rest = $count - count( $posts );
	if ( $rest > 0 ) {
		$latest_query = new WP_Query(
			array_merge(
				$base,
				array(
					'posts_per_page' => $rest,
					'post__not_in'   => wp_list_pluck( $posts, 'ID' ),
				)
			)
		);
		$posts        = array_merge( $posts, $latest_query->posts );
	}
	if ( ! $posts ) {
		return array();
	}

	$first  = array_shift( $posts );
	$fields = ISHA_Core_Article_Fields::get( $first->ID );

	$featured = array(
		'id'       => (int) $first->ID,
		'title'    => isha_home_data_plain( get_the_title( $first ) ),
		'excerpt'  => isha_home_data_excerpt( $first ),
		'image_id' => (int) get_post_thumbnail_id( $first ),
		'url'      => (string) get_permalink( $first ),
		'category' => isha_home_data_first_term_name( $first, 'isha_article_cat' ),
		'date'     => (string) get_the_date( '', $first ),
	);

	$list = array();
	foreach ( $posts as $post ) {
		$list[] = array(
			'id'       => (int) $post->ID,
			'title'    => isha_home_data_plain( get_the_title( $post ) ),
			'url'      => (string) get_permalink( $post ),
			'category' => isha_home_data_first_term_name( $post, 'isha_article_cat' ),
			'date'     => (string) get_the_date( '', $post ),
		);
	}

	return array(
		'section'     => 'articles',
		'title'       => $merged['title'],
		'link_label'  => $merged['link_label'],
		'archive_url' => isha_home_data_news_url(),
		'featured'    => $featured,
		'list'        => $list,
	);
}

/**
 * Địa chỉ trang danh sách Tin tức cho liên kết "Xem tất cả": trang đặt làm Trang bài viết (Cài đặt › Đọc, ở website này là trang Tin tức
 * dạng bento, xem home.php), không có thì trang có slug `tin-tuc`. Từ 18/11/2026 post type isha_article không còn trang lưu trữ nên
 * nhánh cuối thường trả ''.
 *
 * @return string '' nếu không tìm được địa chỉ nào.
 */
function isha_home_data_news_url() {
	$page_id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_for_posts' ) : 0;
	if ( $page_id < 1 || 'publish' !== get_post_status( $page_id ) ) {
		$page    = get_page_by_path( 'tin-tuc', OBJECT, 'page' );
		$page_id = ( $page instanceof WP_Post && 'publish' === $page->post_status ) ? (int) $page->ID : 0;
	}
	$url = $page_id > 0 ? get_permalink( $page_id ) : get_post_type_archive_link( 'isha_article' );
	return is_string( $url ) ? $url : '';
}

/**
 * Section Testimonials: lời kể đã xuất bản và có nội dung; mới nhất trước, tối đa count. Không có điều kiện "khách đồng ý công khai": nội dung là
 * lời kể của doanh nghiệp, không phải của khách thật (khoá meta cũ _isha_tm_consent nếu còn trong DB thì bị bỏ qua).
 *
 * @param array $merged Dữ liệu đã trộn (title).
 * @param array $args   Tham số: count (mặc định 6).
 * @return array array() nếu thiếu CPT/class hoặc không có lời kể hợp lệ.
 */
function isha_home_data_build_testimonials( array $merged, array $args ) {
	if ( ! post_type_exists( 'isha_testimonial' ) || ! isha_home_data_has_method( 'ISHA_Core_Testimonial_Fields', 'get' ) ) {
		return array();
	}

	$count = isha_home_data_count( $args, 6 );
	$query = new WP_Query(
		array(
			'post_type'              => 'isha_testimonial',
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'orderby'                => array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			),
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'lazy_load_term_meta'    => false,
		)
	);

	$items = array();
	foreach ( $query->posts as $post ) {
		$fields = ISHA_Core_Testimonial_Fields::get( $post->ID );
		if ( ! is_array( $fields ) ) {
			continue;
		}
		$quote = isha_home_data_text( isset( $fields['quote'] ) ? $fields['quote'] : '' );
		if ( '' === $quote ) {
			continue;
		}
		$name = isha_home_data_plain( isset( $fields['name'] ) ? $fields['name'] : '' );

		// Tên liệu trình đã dùng: chỉ hiện khi liệu trình đã xuất bản (cùng quy tắc với section Dịch vụ: bản nháp, chờ duyệt hay riêng tư
		// chỉ thấy khi quản trị xem trước). ISHA Core cho chọn cả liệu trình chưa xuất bản trong trang quản trị, nên chặn ở lớp hiển thị
		// để khách không thấy tên liệu trình chưa công bố. Thiếu service_id thì giữ nguyên tên (không có gì để kiểm tra).
		$service_title = isset( $fields['service_title'] ) ? $fields['service_title'] : '';
		$service_id    = isset( $fields['service_id'] ) ? (int) $fields['service_id'] : 0;
		if ( $service_id > 0 && 'publish' !== get_post_status( $service_id ) && ! isha_home_data_preview_drafts() ) {
			$service_title = '';
		}

		$items[] = array(
			'id'      => (int) $post->ID,
			'name'    => '' !== $name ? $name : isha_home_data_plain( get_the_title( $post ) ),
			'quote'   => $quote,
			'service' => isha_home_data_plain( $service_title ),
			'photo'   => (int) get_post_thumbnail_id( $post ),
		);
		if ( count( $items ) >= $count ) {
			break;
		}
	}
	if ( ! $items ) {
		return array();
	}

	return array(
		'section' => 'testimonials',
		'title'   => $merged['title'],
		'items'   => $items,
	);
}

/* --- Liên hệ ------------------------------------------------------------------------------------------------------ */

/**
 * URL Zalo an toàn: esc_url_raw, http đổi thành https, chỉ nhận địa chỉ có tên miền (có dấu chấm trong host).
 * Rỗng với giá trị trống, giao thức lạ (javascript:...), số điện thoại hoặc chuỗi không phải địa chỉ.
 *
 * @param mixed $value Giá trị thô (isha_opt( 'zalo' )).
 * @return string
 */
function isha_home_data_zalo_url( $value ) {
	if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
		return '';
	}
	$url = (string) preg_replace( '#^http://#i', 'https://', esc_url_raw( trim( (string) $value ) ) );
	if ( 0 !== strpos( $url, 'https://' ) ) {
		return '';
	}
	$host = wp_parse_url( $url, PHP_URL_HOST );
	return ( is_string( $host ) && false !== strpos( $host, '.' ) ) ? $url : '';
}

/* --- Bộ lọc ------------------------------------------------------------------------------------------------------- */

/**
 * Cấp nội dung mặc định cho meta box "Nội dung trang chủ" của ISHA Core (hiện làm placeholder ở ô để trống).
 *
 * @param array $defaults Giá trị đang có (ISHA Core truyền array()).
 * @return array array( section => mặc định ).
 */
function isha_home_data_filter_defaults( $defaults ) {
	return array_merge( is_array( $defaults ) ? $defaults : array(), isha_home_defaults() );
}
add_filter( 'isha_core/home_defaults', 'isha_home_data_filter_defaults' );
