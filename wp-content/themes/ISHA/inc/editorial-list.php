<?php
/**
 * Trang danh sách bài viết dạng Editorial Bento Grid, dùng chung cho Tin tức (isha_article) và Sự kiện (isha_event).
 *
 * MỘT template dùng chung (template-parts/editorial/list.php + tile.php + assets/css/editorial-list.css + file này). Hai trang chỉ
 * khác nhau ở nguồn dữ liệu (post type), do hai mẫu trang mỏng chọn: page-tin-tuc.php (isha_article) và
 * page-danh-sach-su-kien.php (isha_event). Thêm danh sách thứ ba = thêm một mẫu trang gọi isha_editorial_render_page( '<post type>' )
 * và khai báo post type đó trong isha_editorial_sources().
 *
 * Mỗi thẻ bài có đúng 5 phần cho cả hai post type: ảnh đại diện, tiêu đề, nội dung ngắn, tác giả, ngày viết (ngày đăng bài).
 *
 * Bố cục một "màn bento" gồm đúng 3 thành phần, không bài nào xuất hiện hai lần:
 *   1. Tiêu điểm: bài mới nhất còn lại (hero).
 *   2. Cùng chuyên mục: tối đa 3 bài chung term (taxonomy của post type) với hero.
 *   3. Cùng tác giả: tối đa 3 bài chung tác giả với hero, KHÔNG trùng 3 bài ở mục 2.
 * Phân trang đi qua từng màn bento: mỗi trang lấy bài mới nhất còn lại làm hero rồi gom nhóm trong số bài còn lại, nên mọi bài đều
 * xuất hiện đúng một lần qua các trang. Một nhóm thiếu bài thì chỉ hiện số bài thật sự liên quan (không lấy bài không liên quan
 * để đủ 3, vì nhãn nhóm sẽ sai); lưới tự co giãn theo số bài của từng nhóm (CSS: class isha-bento--cN và isha-bento--aN).
 * Tab chuyên mục và phân trang là liên kết thường (?chuyen-muc=slug, ?trang=N): không dùng JavaScript.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Số bài mỗi nhóm "Cùng chuyên mục" và "Cùng tác giả" trong một màn bento (khớp bố cục CSS, không đổi).
 */
if ( ! defined( 'ISHA_EDITORIAL_GROUP_SIZE' ) ) {
	define( 'ISHA_EDITORIAL_GROUP_SIZE', 3 );
}

/**
 * Cấu hình theo post type: taxonomy dùng làm "chuyên mục", khoá meta của Nội dung ngắn, câu chữ khi chưa có bài.
 *
 * @return array<string,array<string,string>> Khoá là post type.
 */
function isha_editorial_sources() {
	$sources = array(
		'isha_event'   => array(
			'taxonomy'     => 'isha_event_type',
			'summary_meta' => '_isha_event_summary',
			'empty'        => __( 'Chưa có sự kiện nào.', 'isha' ),
		),
		'isha_article' => array(
			'taxonomy'     => 'isha_article_cat',
			'summary_meta' => '_isha_article_summary',
			'empty'        => __( 'Chưa có bài viết nào.', 'isha' ),
		),
	);

	/**
	 * Bộ lọc cấu hình nguồn dữ liệu của trang danh sách bento.
	 *
	 * @param array $sources Cấu hình theo post type (taxonomy, summary_meta, empty).
	 */
	$filtered = apply_filters( 'isha/editorial/sources', $sources );
	return is_array( $filtered ) ? $filtered : $sources;
}

/**
 * Cấu hình của một post type (mảng rỗng nếu post type không được hỗ trợ).
 *
 * @param string $post_type Post type.
 * @return array<string,string>
 */
function isha_editorial_source( $post_type ) {
	$sources = isha_editorial_sources();
	if ( ! is_string( $post_type ) || ! isset( $sources[ $post_type ] ) || ! is_array( $sources[ $post_type ] ) ) {
		return array();
	}
	return wp_parse_args(
		$sources[ $post_type ],
		array(
			'taxonomy'     => '',
			'summary_meta' => '',
			'empty'        => __( 'Chưa có bài viết nào.', 'isha' ),
		)
	);
}

/* --- Văn bản và tác giả ------------------------------------------------------------------------------------------------ */

/**
 * Văn bản thuần: bỏ thẻ HTML, giải mã thực thể, gộp mọi khoảng trắng thành một dấu cách.
 *
 * @param mixed $value Giá trị thô.
 * @return string
 */
function isha_editorial_plain( $value ) {
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
 * Nội dung ngắn do người dùng nhập ở trường "Nội dung ngắn" của bài ('' nếu post type không có trường này hoặc chưa nhập).
 * Trang chủ (isha_home_data_excerpt) dùng chung hàm này để mọi nơi hiện cùng một đoạn.
 *
 * @param WP_Post $post Bài.
 * @return string Văn bản thuần.
 */
function isha_editorial_stored_summary( $post ) {
	if ( ! ( $post instanceof WP_Post ) ) {
		return '';
	}
	$config = isha_editorial_source( $post->post_type );
	if ( empty( $config['summary_meta'] ) ) {
		return '';
	}
	return isha_editorial_plain( get_post_meta( $post->ID, $config['summary_meta'], true ) );
}

/**
 * Nội dung ngắn hiển thị trên thẻ: trường "Nội dung ngắn", không có thì Đoạn trích (Excerpt), không có nữa thì 26 từ đầu của
 * nội dung bài. Bài đặt mật khẩu thì không lộ nội dung.
 *
 * @param WP_Post $post Bài.
 * @return string Văn bản thuần ('' nếu bài rỗng hoặc đặt mật khẩu).
 */
function isha_editorial_summary( $post ) {
	if ( ! ( $post instanceof WP_Post ) || post_password_required( $post ) ) {
		return '';
	}
	$text = isha_editorial_stored_summary( $post );
	if ( '' === $text ) {
		$text = isha_editorial_plain( $post->post_excerpt );
	}
	if ( '' === $text ) {
		$text = wp_trim_words( isha_editorial_plain( strip_shortcodes( (string) $post->post_content ) ), 26, '…' );
	}
	return $text;
}

/**
 * Tên tác giả hiển thị công khai. Tên hiển thị trùng tên đăng nhập (mặc định của WordPress khi chưa đặt) KHÔNG được in ra vì sẽ
 * lộ tên đăng nhập quản trị: khi đó dùng họ tên (nếu có), không có nữa thì dùng tên website. Đặt "Hiển thị công khai là" ở
 * Người dùng > Hồ sơ để hiện tên thật.
 *
 * @param int $user_id ID người dùng.
 * @return string
 */
function isha_editorial_author_name( $user_id ) {
	static $cache = array();
	$user_id = absint( $user_id );
	if ( isset( $cache[ $user_id ] ) ) {
		return $cache[ $user_id ];
	}

	$name = '';
	$user = $user_id > 0 ? get_userdata( $user_id ) : false;
	if ( $user instanceof WP_User ) {
		$display = isha_editorial_plain( $user->display_name );
		if ( '' !== $display && 0 !== strcasecmp( $display, (string) $user->user_login ) ) {
			$name = $display;
		} else {
			$name = isha_editorial_plain( trim( $user->first_name . ' ' . $user->last_name ) );
		}
	}
	if ( '' === $name ) {
		$name = isha_editorial_plain( get_bloginfo( 'name' ) );
	}

	/**
	 * Bộ lọc tên tác giả hiển thị trên thẻ bài.
	 *
	 * @param string $name    Tên đã chọn.
	 * @param int    $user_id ID người dùng.
	 */
	$filtered = apply_filters( 'isha/editorial/author_name', $name, $user_id );

	$cache[ $user_id ] = is_string( $filtered ) ? $filtered : $name;
	return $cache[ $user_id ];
}

/* --- Truy vấn và gom nhóm ---------------------------------------------------------------------------------------------- */

/**
 * Số bài tối đa nạp vào để gom nhóm (bài cũ nhất vượt ngưỡng sẽ không có trong danh sách). Đủ lớn cho một website phòng khám.
 *
 * @return int
 */
function isha_editorial_pool_max() {
	$max = (int) apply_filters( 'isha/editorial/pool_max', 500 );
	return $max > 0 ? $max : 500;
}

/**
 * Term đang chọn ở tab (tham số ?chuyen-muc=slug), null nếu không có hoặc slug không thuộc taxonomy.
 *
 * @param string $taxonomy Taxonomy của post type.
 * @return WP_Term|null
 */
function isha_editorial_requested_term( $taxonomy ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc bộ lọc hiển thị công khai.
	if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) || ! isset( $_GET['chuyen-muc'] ) || is_array( $_GET['chuyen-muc'] ) ) {
		return null;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- như trên.
	$slug = sanitize_title( wp_unslash( $_GET['chuyen-muc'] ) );
	if ( '' === $slug ) {
		return null;
	}
	$term = get_term_by( 'slug', $slug, $taxonomy );
	return ( $term instanceof WP_Term ) ? $term : null;
}

/**
 * Trang đang xem (tham số ?trang=N, mặc định 1). Giá trị vượt tổng số trang được isha_editorial_compose() đưa về trang cuối.
 *
 * @return int Số nguyên từ 1.
 */
function isha_editorial_requested_page() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc số trang hiển thị công khai.
	if ( ! isset( $_GET['trang'] ) || is_array( $_GET['trang'] ) ) {
		return 1;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- như trên.
	$page = absint( wp_unslash( $_GET['trang'] ) );
	return max( 1, min( $page, 100000 ) );
}

/**
 * Các bài đã xuất bản của một post type, mới nhất trước (ngày đăng rồi ID giảm dần), có thể lọc theo một term.
 *
 * @param string $post_type Post type.
 * @param int    $term_id   ID term cần lọc (0 = tất cả).
 * @return WP_Post[]
 */
function isha_editorial_pool( $post_type, $term_id = 0 ) {
	$config = isha_editorial_source( $post_type );
	if ( ! $config || ! post_type_exists( $post_type ) ) {
		return array();
	}

	$args = array(
		'post_type'              => $post_type,
		'post_status'            => 'publish',
		'posts_per_page'         => isha_editorial_pool_max(),
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
	if ( (int) $term_id > 0 && '' !== $config['taxonomy'] && taxonomy_exists( $config['taxonomy'] ) ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'taxonomy' => $config['taxonomy'],
				'field'    => 'term_id',
				'terms'    => array( (int) $term_id ),
			),
		);
	}

	$query = new WP_Query( $args );
	return is_array( $query->posts ) ? array_values( $query->posts ) : array();
}

/**
 * Hàng gọn (ID, tác giả, các term) của từng bài, giữ nguyên thứ tự: đầu vào của isha_editorial_compose().
 *
 * @param WP_Post[] $posts    Bài, mới nhất trước.
 * @param string    $taxonomy Taxonomy dùng làm chuyên mục.
 * @return array<int,array{id:int,author:int,terms:int[]}>
 */
function isha_editorial_rows( array $posts, $taxonomy ) {
	$has_tax = ( '' !== $taxonomy && taxonomy_exists( $taxonomy ) );
	$rows    = array();
	foreach ( $posts as $post ) {
		$term_ids = array();
		$terms    = $has_tax ? get_the_terms( $post, $taxonomy ) : false;
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $term instanceof WP_Term ) {
					$term_ids[] = (int) $term->term_id;
				}
			}
		}
		$rows[] = array(
			'id'     => (int) $post->ID,
			'author' => (int) $post->post_author,
			'terms'  => $term_ids,
		);
	}
	return $rows;
}

/**
 * Lấy các hàng ở vị trí $positions ra khỏi $rows (và đánh lại chỉ số), trả về theo đúng thứ tự của $positions.
 *
 * @param array $rows      Danh sách hàng (tham chiếu, bị rút bớt).
 * @param int[] $positions Chỉ số trong $rows.
 * @return array
 */
function isha_editorial_take( array &$rows, array $positions ) {
	$taken = array();
	foreach ( $positions as $pos ) {
		$taken[] = $rows[ $pos ];
	}
	foreach ( $positions as $pos ) {
		unset( $rows[ $pos ] );
	}
	$rows = array_values( $rows );
	return $taken;
}

/**
 * Gom nhóm các hàng thành các "màn bento" rồi trả về màn thứ $page (hàm thuần, không gọi WordPress).
 *
 * Mỗi màn: hero = hàng mới nhất còn lại; "cùng chuyên mục" = tối đa 3 hàng chung term với hero (chung nhiều term hơn thì xếp
 * trước, rồi bài mới hơn); "cùng tác giả" = tối đa 3 hàng cùng tác giả với hero trong số còn lại (nên không trùng nhóm trước).
 * Các hàng đã dùng không xuất hiện ở màn sau. $page vượt tổng số màn thì lấy màn cuối.
 *
 * @param array<int,array{id:int,author:int,terms:int[]}> $rows Hàng đã sắp mới nhất trước (xem isha_editorial_rows()).
 * @param int                                             $page Số trang bắt đầu từ 1.
 * @return array{hero:array,cat:array,author:array,cat_term:int,page:int,pages:int}|array Mảng rỗng nếu không có hàng nào.
 */
function isha_editorial_compose( array $rows, $page = 1 ) {
	$remaining = array_values( $rows );
	$screens   = array();
	$size      = (int) ISHA_EDITORIAL_GROUP_SIZE;

	while ( $remaining ) {
		$hero = array_shift( $remaining );

		// Cùng chuyên mục.
		$ranked = array();
		foreach ( $remaining as $pos => $row ) {
			$shared = count( array_intersect( $hero['terms'], $row['terms'] ) );
			if ( $shared > 0 ) {
				$ranked[] = array( $shared, $pos );
			}
		}
		usort(
			$ranked,
			static function ( $a, $b ) {
				return ( $b[0] <=> $a[0] ) ?: ( $a[1] <=> $b[1] );
			}
		);
		$cat_positions = array();
		foreach ( array_slice( $ranked, 0, $size ) as $pair ) {
			$cat_positions[] = $pair[1];
		}
		$cat = isha_editorial_take( $remaining, $cat_positions );

		// Cùng tác giả, trong số bài chưa dùng (sau khi đã rút nhóm chuyên mục).
		$author_positions = array();
		foreach ( $remaining as $pos => $row ) {
			if ( $row['author'] === $hero['author'] ) {
				$author_positions[] = $pos;
				if ( count( $author_positions ) >= $size ) {
					break;
				}
			}
		}
		$author = isha_editorial_take( $remaining, $author_positions );

		// Term dùng làm nhãn nhóm chuyên mục: term của hero xuất hiện ở nhiều bài của nhóm nhất (hoà thì term đứng trước).
		$cat_term = 0;
		$best     = 0;
		foreach ( $hero['terms'] as $term_id ) {
			$hits = 0;
			foreach ( $cat as $row ) {
				if ( in_array( $term_id, $row['terms'], true ) ) {
					++$hits;
				}
			}
			if ( $hits > $best ) {
				$best     = $hits;
				$cat_term = (int) $term_id;
			}
		}

		$screens[] = array(
			'hero'     => $hero,
			'cat'      => $cat,
			'author'   => $author,
			'cat_term' => $cat_term,
		);
	}

	if ( ! $screens ) {
		return array();
	}
	$index           = min( max( 1, (int) $page ), count( $screens ) );
	$screen          = $screens[ $index - 1 ];
	$screen['page']  = $index;
	$screen['pages'] = count( $screens );
	return $screen;
}

/* --- Thẻ bài và dữ liệu cho template ------------------------------------------------------------------------------------ */

/**
 * Dữ liệu một thẻ bài: 5 phần ảnh, tiêu đề, nội dung ngắn, tác giả, ngày viết.
 *
 * @param WP_Post $post Bài.
 * @return array{id:int,url:string,title:string,summary:string,author:string,date_iso:string,date:string,image_id:int}
 */
function isha_editorial_card( $post ) {
	$title = isha_editorial_plain( get_the_title( $post ) );
	if ( '' === $title ) {
		$title = __( '(Chưa có tiêu đề)', 'isha' );
	}

	/**
	 * Định dạng ngày viết trên thẻ bài (cú pháp date của PHP). Mặc định gọn để vừa các ô nhỏ.
	 *
	 * @param string  $format Định dạng.
	 * @param WP_Post $post   Bài.
	 */
	$format = apply_filters( 'isha/editorial/date_format', 'd/m/Y', $post );
	$format = ( is_string( $format ) && '' !== $format ) ? $format : 'd/m/Y';

	$image_id = (int) get_post_thumbnail_id( $post );
	if ( $image_id > 0 && ! wp_attachment_is_image( $image_id ) ) {
		$image_id = 0;
	}

	return array(
		'id'       => (int) $post->ID,
		'url'      => (string) get_permalink( $post ),
		'title'    => $title,
		'summary'  => isha_editorial_summary( $post ),
		'author'   => isha_editorial_author_name( $post->post_author ),
		'date_iso' => (string) get_the_date( DATE_W3C, $post ),
		'date'     => (string) get_the_date( $format, $post ),
		'image_id' => $image_id,
	);
}

/**
 * Danh sách tab chuyên mục: "Tất cả" rồi các term có bài. Mỗi mục: label, url, current (bool).
 *
 * @param string       $taxonomy Taxonomy dùng làm chuyên mục.
 * @param WP_Term|null $active   Term đang chọn.
 * @return array<int,array{label:string,url:string,current:bool}> Rỗng nếu chưa có term nào có bài.
 */
function isha_editorial_tabs( $taxonomy, $active ) {
	if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		)
	);
	if ( ! is_array( $terms ) || ! $terms ) {
		return array();
	}

	$base = (string) get_permalink();
	$tabs = array(
		array(
			'label'   => __( 'Tất cả', 'isha' ),
			'url'     => $base,
			'current' => ! ( $active instanceof WP_Term ),
		),
	);
	foreach ( $terms as $term ) {
		if ( ! ( $term instanceof WP_Term ) ) {
			continue;
		}
		$tabs[] = array(
			'label'   => isha_editorial_plain( $term->name ),
			'url'     => add_query_arg( 'chuyen-muc', $term->slug, $base ),
			'current' => ( $active instanceof WP_Term ) && (int) $active->term_id === (int) $term->term_id,
		);
	}
	return $tabs;
}

/**
 * Các liên kết phân trang (mảng chuỗi <a>/<span> của paginate_links), giữ bộ lọc chuyên mục đang chọn.
 *
 * @param int          $page   Trang hiện tại.
 * @param int          $pages  Tổng số trang.
 * @param WP_Term|null $active Term đang chọn.
 * @return string[] Rỗng nếu chỉ có một trang.
 */
function isha_editorial_pagination( $page, $pages, $active ) {
	if ( $pages < 2 ) {
		return array();
	}
	// Trang dùng tham số ?trang=N thay vì /page/N/: trên trang "Bài viết" của WordPress, /page/N/ chạy vào truy vấn blog chính
	// (không có bài ở trang N) nên bị 404.
	$permalink = (string) get_permalink();
	$args      = array(
		'current'   => max( 1, (int) $page ),
		'total'     => (int) $pages,
		'mid_size'  => 1,
		'end_size'  => 1,
		'prev_text' => __( '‹ Trước', 'isha' ),
		'next_text' => __( 'Sau ›', 'isha' ),
		'type'      => 'array',
	);
	if ( false === strpos( $permalink, '?' ) ) {
		$args['base']   = $permalink . '%_%';
		$args['format'] = '?trang=%#%';
	} else {
		$args['base']   = add_query_arg( 'trang', '%#%', $permalink );
		$args['format'] = '';
	}
	if ( $active instanceof WP_Term ) {
		$args['add_args'] = array( 'chuyen-muc' => $active->slug );
	}

	$links = paginate_links( $args );
	return is_array( $links ) ? $links : array();
}

/**
 * Tên cấu trúc hiển thị (media/chữ) của một ô thứ $slot (từ 0) trong nhóm có $count bài. Mỗi nhóm 3 bài dùng 3 cấu trúc khác nhau:
 * stack (ảnh lớn trên, chữ dưới), side (ảnh trái, chữ phải), cover (ảnh tràn ô, chữ đè lên), strip (dải ảnh mỏng trên, chữ dưới).
 *
 * @param string $group Nhóm: cat hoặc author.
 * @param int    $slot  Vị trí trong nhóm, từ 0.
 * @param int    $count Số bài của nhóm (1 đến 3).
 * @return string
 */
function isha_editorial_variant( $group, $slot, $count ) {
	$map = array(
		'cat'    => array(
			3 => array( 'stack', 'cover', 'strip' ),
			2 => array( 'side', 'cover' ),
			1 => array( 'stack' ),
		),
		'author' => array(
			3 => array( 'side', 'cover', 'strip' ),
			2 => array( 'cover', 'side' ),
			1 => array( 'side' ),
		),
	);
	return isset( $map[ $group ][ $count ][ $slot ] ) ? $map[ $group ][ $count ][ $slot ] : 'stack';
}

/**
 * Dữ liệu đầy đủ của trang danh sách cho template: tab, hero, hai nhóm, phân trang.
 *
 * @param string $post_type Post type nguồn dữ liệu.
 * @return array Mảng rỗng nếu post type không được hỗ trợ hoặc chưa đăng ký (plugin ISHA Core tắt).
 */
function isha_editorial_view( $post_type ) {
	$config = isha_editorial_source( $post_type );
	if ( ! $config || ! post_type_exists( $post_type ) ) {
		return array();
	}

	$taxonomy = $config['taxonomy'];
	$active   = isha_editorial_requested_term( $taxonomy );
	$posts    = isha_editorial_pool( $post_type, $active ? (int) $active->term_id : 0 );
	$by_id    = array();
	foreach ( $posts as $post ) {
		$by_id[ (int) $post->ID ] = $post;
	}

	$screen = isha_editorial_compose( isha_editorial_rows( $posts, $taxonomy ), isha_editorial_requested_page() );

	$view = array(
		'post_type'    => $post_type,
		'empty'        => $config['empty'],
		'tabs'         => isha_editorial_tabs( $taxonomy, $active ),
		'filtered'     => $active instanceof WP_Term,
		'hero'         => array(),
		'cat'          => array(),
		'author'       => array(),
		'cat_label'    => '',
		'author_label' => '',
		'page'         => 1,
		'pages'        => 1,
		'pagination'   => array(),
	);
	if ( ! $screen || ! isset( $by_id[ $screen['hero']['id'] ] ) ) {
		return $view;
	}

	$hero_post = $by_id[ $screen['hero']['id'] ];

	$view['hero'] = isha_editorial_card( $hero_post );
	foreach ( array( 'cat', 'author' ) as $group ) {
		foreach ( $screen[ $group ] as $row ) {
			if ( isset( $by_id[ $row['id'] ] ) ) {
				$view[ $group ][] = isha_editorial_card( $by_id[ $row['id'] ] );
			}
		}
	}

	if ( $screen['cat_term'] > 0 && $view['cat'] ) {
		$term = get_term( $screen['cat_term'], $taxonomy );
		if ( $term instanceof WP_Term ) {
			$view['cat_label'] = isha_editorial_plain( $term->name );
		}
	}
	if ( $view['author'] ) {
		$view['author_label'] = isha_editorial_author_name( $hero_post->post_author );
	}

	$view['page']       = $screen['page'];
	$view['pages']      = $screen['pages'];
	$view['pagination'] = isha_editorial_pagination( $screen['page'], $screen['pages'], $active );
	return $view;
}

/* --- Hiển thị ------------------------------------------------------------------------------------------------------------ */

/**
 * Ảnh đại diện của một thẻ (thẻ <img> đã escape, alt rỗng vì tiêu đề đã là liên kết đặt tên cho thẻ), '' nếu không có ảnh.
 * Hero tải ngay (eager, ưu tiên cao vì là ảnh lớn nhất màn hình đầu), các ô còn lại tải lười.
 *
 * @param int    $image_id ID ảnh đính kèm (0 = không có).
 * @param string $role     Vai trò ô: hero, c1..c3, a1..a3.
 * @return string
 */
function isha_editorial_image( $image_id, $role ) {
	$image_id = (int) $image_id;
	if ( $image_id < 1 || ! wp_attachment_is_image( $image_id ) ) {
		return '';
	}

	$is_hero = ( 'hero' === $role );
	$attr    = array(
		'class'    => 'isha-bento__img',
		'alt'      => '',
		'decoding' => 'async',
		'loading'  => $is_hero ? 'eager' : 'lazy',
		'sizes'    => $is_hero
			? '(min-width: 1100px) 580px, 100vw'
			: '(min-width: 1100px) 290px, (min-width: 641px) 50vw, 100vw',
	);
	if ( $is_hero ) {
		$attr['fetchpriority'] = 'high';
	}

	$html = wp_get_attachment_image( $image_id, $is_hero ? 'large' : 'medium_large', false, $attr );

	// `sizes` chỉ hợp lệ khi có `srcset`; tệp thiếu metadata không có srcset.
	if ( '' !== $html && false === strpos( $html, ' srcset="' ) ) {
		$html = (string) preg_replace( '/\s+sizes="[^"]*"/', '', $html, 1 );
	}
	return $html;
}

/**
 * Nạp CSS của trang danh sách (không có JavaScript). Gọi TRƯỚC get_header() để thẻ <link> nằm trong <head>.
 */
function isha_editorial_enqueue() {
	$file = ISHA_DIR . '/assets/css/editorial-list.css';
	wp_enqueue_style(
		'isha-editorial-list',
		ISHA_URI . '/assets/css/editorial-list.css',
		array( 'isha-main' ),
		file_exists( $file ) ? (int) filemtime( $file ) : ISHA_VERSION
	);
}

/**
 * Dựng cả trang danh sách cho một post type: nạp CSS, header, template dùng chung, footer. Các mẫu trang chỉ cần gọi hàm này.
 *
 * Dữ liệu của CHÍNH trang (tiêu đề, nội dung giới thiệu, permalink) lấy từ đối tượng được truy vấn chứ không từ vòng lặp chính:
 * ở trang "Bài viết" của WordPress (Cài đặt › Đọc), vòng lặp chính là danh sách bài blog, không phải trang.
 *
 * @param string $post_type Post type nguồn dữ liệu (xem isha_editorial_sources()).
 */
function isha_editorial_render_page( $post_type ) {
	isha_editorial_enqueue();
	get_header();

	$args = array( 'post_type' => $post_type );
	$page = get_queried_object();
	if ( $page instanceof WP_Post ) {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- dựng dữ liệu bài theo cách chuẩn của vòng lặp tuỳ biến, trả lại bằng wp_reset_postdata().
		$GLOBALS['post'] = $page;
		setup_postdata( $page );
		get_template_part( 'template-parts/editorial/list', null, $args );
		wp_reset_postdata();
	} else {
		while ( have_posts() ) {
			the_post();
			get_template_part( 'template-parts/editorial/list', null, $args );
		}
	}

	get_footer();
}
