<?php
/**
 * Header: bộ chuyển ngôn ngữ (TranslatePress), ẩn mục "English" của TranslatePress khỏi menu chính, nút mở menu con.
 * Markup thanh header ở template-parts/header/masthead.php; CSS ở assets/css/main.css (khối Header); JS ở assets/js/main.js.
 *
 * - Site dùng TranslatePress (vi mặc định, en_US). Danh sách ngôn ngữ và URL lấy từ component `settings`, `languages` và
 *   `url_converter` của TranslatePress (cùng nguồn với trp_custom_language_switcher() và shortcode [language-switcher] của plugin).
 *   Không có TranslatePress, hoặc dưới hai ngôn ngữ thì không in gì.
 * - Mục menu loại `language_switcher` (TranslatePress thêm vào menu) bị ẩn ở vị trí menu `primary` bằng bộ lọc, không sửa menu trong
 *   DB; vị trí menu khác không bị ảnh hưởng.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Các ngôn ngữ để in bộ chuyển (theo thứ tự cấu hình của TranslatePress), mỗi phần tử: code, label (VI, EN), name (tên bản ngữ),
 * lang (mã BCP 47 cho lang/hreflang), url (trang hiện tại ở ngôn ngữ đó), current. Mảng rỗng nếu không dùng được.
 *
 * @return array<int,array{code:string,label:string,name:string,lang:string,url:string,current:bool}>
 */
function isha_header_languages() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();

	global $TRP_LANGUAGE;
	if ( empty( $TRP_LANGUAGE ) || ! class_exists( 'TRP_Translate_Press' ) || ! method_exists( 'TRP_Translate_Press', 'get_trp_instance' ) ) {
		return $cache;
	}

	$trp       = TRP_Translate_Press::get_trp_instance();
	$settings  = $trp ? $trp->get_component( 'settings' ) : null;
	$languages = $trp ? $trp->get_component( 'languages' ) : null;
	$converter = $trp ? $trp->get_component( 'url_converter' ) : null;
	if ( ! $settings || ! $languages || ! $converter ) {
		return $cache;
	}

	$options = (array) $settings->get_settings();
	// Như shortcode của TranslatePress: người có quyền dịch thấy cả ngôn ngữ chưa xuất bản (để xem thử).
	$key   = current_user_can( apply_filters( 'trp_translating_capability', 'manage_options' ) ) ? 'translation-languages' : 'publish-languages';
	$codes = isset( $options[ $key ] ) ? array_values( (array) $options[ $key ] ) : array();
	if ( count( $codes ) < 2 ) {
		return $cache;
	}

	$names = (array) $languages->get_language_names( $codes, 'native_name' );

	foreach ( $codes as $code ) {
		$code = (string) $code;
		$tag  = str_replace( '_', '-', str_replace( array( '_formal', '_informal' ), '', $code ) );

		$cache[] = array(
			'code'    => $code,
			'label'   => strtoupper( (string) $converter->get_url_slug( $code, false ) ),
			'name'    => isset( $names[ $code ] ) ? (string) $names[ $code ] : $code,
			'lang'    => $tag,
			// Tham số thứ ba rỗng: không gắn đuôi #TRPLINKPROCESSED của TranslatePress vào URL.
			'url'     => (string) $converter->get_url_for_language( $code, null, '' ),
			'current' => $code === $TRP_LANGUAGE,
		);
	}

	return $cache;
}

/**
 * Cờ tròn của ngôn ngữ (assets/icons), theo tiền tố mã BCP 47 (vi, en). Chuỗi rỗng nếu chưa có cờ cho ngôn ngữ đó.
 *
 * @param string $lang Mã BCP 47, ví dụ "vi", "en-US".
 * @return string
 */
function isha_header_flag_uri( $lang ) {
	$files  = array(
		'vi' => 'vietnam-flag-round-circle-icon.svg',
		'en' => 'uk-flag-round-circle-icon.svg',
	);
	$prefix = strtolower( explode( '-', (string) $lang )[0] );

	return isset( $files[ $prefix ] ) ? get_theme_file_uri( 'assets/icons/' . $files[ $prefix ] ) : '';
}

/**
 * In bộ chuyển dạng quả cầu khi hai ngôn ngữ đều có cờ. Mặt ngôn ngữ đối lập ở trước; click thì main.js lật quả cầu rồi chuyển sang
 * trang đó, nên mặt trước sau khi lật đúng là cờ mà trang mới sẽ hiện.
 *
 * @param array  $items   Kết quả isha_header_languages().
 * @param string $context 'bar' hoặc 'drawer'.
 * @return bool True nếu đã in quả cầu.
 */
function isha_header_language_coin( $items, $context ) {
	if ( 2 !== count( $items ) ) {
		return false;
	}

	$current = null;
	$target  = null;
	foreach ( $items as $item ) {
		if ( $item['current'] ) {
			$current = $item;
		} else {
			$target = $item;
		}
	}
	if ( null === $current || null === $target ) {
		return false;
	}

	$target_flag  = isha_header_flag_uri( $target['lang'] );
	$current_flag = isha_header_flag_uri( $current['lang'] );
	if ( '' === $target_flag || '' === $current_flag ) {
		return false;
	}

	printf(
		'<div class="header-lang header-lang--coin header-lang--%1$s" role="group" aria-label="%2$s" data-no-translation>'
		. '<a class="header-lang__coin" href="%3$s" hreflang="%4$s" lang="%4$s" title="%5$s" aria-label="%5$s">'
		. '<span class="header-lang__flip">'
		. '<img class="header-lang__face header-lang__face--target" src="%6$s" alt="" width="36" height="36" decoding="async">'
		. '<img class="header-lang__face header-lang__face--current" src="%7$s" alt="" width="36" height="36" decoding="async">'
		. '</span><span class="header-lang__sheen" aria-hidden="true"></span></a>'
		. '<span class="screen-reader-text">%8$s</span></div>',
		esc_attr( $context ),
		esc_attr__( 'Ngôn ngữ', 'isha' ),
		esc_url( $target['url'] ),
		esc_attr( $target['lang'] ),
		esc_attr( $target['name'] ),
		esc_url( $target_flag ),
		esc_url( $current_flag ),
		esc_html( sprintf( /* translators: %s: tên ngôn ngữ đang xem. */ __( 'Ngôn ngữ hiện tại: %s', 'isha' ), $current['name'] ) )
	);

	return true;
}

/**
 * In bộ chuyển ngôn ngữ: quả cầu cờ khi có cờ cho cả hai ngôn ngữ, nếu không thì chữ mảnh "VI / EN" trên thanh, "VI | EN" trong drawer.
 * Ngôn ngữ hiện tại là chữ sáng (aria-current), ngôn ngữ khác là liên kết (hreflang, lang đúng) tới trang hiện tại ở ngôn ngữ đó.
 *
 * Phần danh sách bọc data-no-translation: TranslatePress không dịch chữ và không ép tiền tố ngôn ngữ của trang hiện tại vào các liên kết
 * này (nếu không, liên kết về tiếng Việt từ trang tiếng Anh sẽ bị gắn lại /en/).
 *
 * @param string $context 'bar' (thanh) hoặc 'drawer'.
 */
function isha_header_language_switcher( $context = 'bar' ) {
	$items = isha_header_languages();
	if ( ! $items ) {
		return;
	}

	$context = 'drawer' === $context ? 'drawer' : 'bar';
	if ( isha_header_language_coin( $items, $context ) ) {
		return;
	}
	$sep = 'drawer' === $context ? '|' : '/';

	printf(
		'<div class="header-lang header-lang--%1$s" role="group" aria-label="%2$s"><span class="header-lang__list" data-no-translation>',
		esc_attr( $context ),
		esc_attr__( 'Ngôn ngữ', 'isha' )
	);
	foreach ( $items as $i => $item ) {
		if ( $i > 0 ) {
			echo '<span class="header-lang__sep" aria-hidden="true">' . esc_html( $sep ) . '</span>';
		}
		if ( $item['current'] ) {
			printf(
				'<span class="header-lang__item is-current" aria-current="true" lang="%1$s" title="%2$s">%3$s</span>',
				esc_attr( $item['lang'] ),
				esc_attr( $item['name'] ),
				esc_html( $item['label'] )
			);
			continue;
		}
		printf(
			'<a class="header-lang__item" href="%1$s" hreflang="%2$s" lang="%2$s" title="%3$s" aria-label="%3$s">%4$s</a>',
			esc_url( $item['url'] ),
			esc_attr( $item['lang'] ),
			esc_attr( $item['name'] ),
			esc_html( $item['label'] )
		);
	}
	echo '</span></div>';
}

/**
 * Ẩn mục menu loại language_switcher (mục "English" do TranslatePress thêm) khỏi menu chính: ngôn ngữ do bộ chuyển trong header đảm nhiệm,
 * nếu để lại thì menu thêm một mục và header rớt dòng. Chỉ áp dụng cho vị trí `primary`.
 *
 * @param array    $items Các mục menu đã sắp xếp.
 * @param stdClass $args  Tham số wp_nav_menu().
 * @return array
 */
function isha_header_hide_language_items( $items, $args ) {
	if ( ! is_object( $args ) || ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $items;
	}
	foreach ( $items as $key => $item ) {
		if ( isset( $item->object ) && 'language_switcher' === $item->object ) {
			unset( $items[ $key ] );
		}
	}

	return array_values( $items );
}
add_filter( 'wp_nav_menu_objects', 'isha_header_hide_language_items', 5, 2 );

/**
 * Thêm nút mở menu con (chevron) sau liên kết của mục cha cấp 1 ở menu chính. main.js nối aria-controls và bật/tắt aria-expanded.
 *
 * @param string   $item_output Markup của mục (liên kết).
 * @param WP_Post  $item        Mục menu.
 * @param int      $depth       Cấp của mục.
 * @param stdClass $args        Tham số wp_nav_menu().
 * @return string
 */
function isha_header_submenu_toggle( $item_output, $item, $depth, $args ) {
	if ( 0 !== (int) $depth || ! is_object( $args ) || ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $item_output;
	}
	if ( ! in_array( 'menu-item-has-children', (array) $item->classes, true ) ) {
		return $item_output;
	}

	return $item_output . sprintf(
		'<button type="button" class="submenu-toggle" aria-expanded="false"><span class="submenu-toggle__icon" aria-hidden="true"></span><span class="screen-reader-text">%s</span></button>',
		esc_html__( 'Mở menu con', 'isha' )
	);
}
add_filter( 'walker_nav_menu_start_el', 'isha_header_submenu_toggle', 10, 4 );
