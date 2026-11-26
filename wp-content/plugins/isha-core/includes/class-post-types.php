<?php
/**
 * Custom post types & taxonomy.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Post_Types {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		// Dịch vụ. Không bật archive để slug /dich-vu/ dành cho Trang "Dịch vụ".
		register_post_type(
			'isha_service',
			array(
				'labels'        => array(
					'name'          => __( 'Dịch vụ', 'isha-core' ),
					'singular_name' => __( 'Dịch vụ', 'isha-core' ),
					'add_new_item'  => __( 'Thêm dịch vụ', 'isha-core' ),
					'edit_item'     => __( 'Sửa dịch vụ', 'isha-core' ),
					'all_items'     => __( 'Tất cả dịch vụ', 'isha-core' ),
				),
				'public'        => true,
				'has_archive'   => false,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-heart',
				'menu_position' => 21,
				// Thời gian, mô tả ngắn và các bước nhập qua trường ACF (xem class-service-fields.php); editor cho phần mô tả chi tiết, thumbnail cho ảnh đại diện; page-attributes để sắp xếp thứ tự (menu_order).
				'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
				'rewrite'       => array( 'slug' => 'dich-vu', 'with_front' => false ),
			)
		);

		register_taxonomy(
			'isha_service_cat',
			'isha_service',
			array(
				'labels'       => array(
					'name'          => __( 'Nhóm dịch vụ', 'isha-core' ),
					'singular_name' => __( 'Nhóm dịch vụ', 'isha-core' ),
				),
				'hierarchical' => true,
				'public'       => true,
				'show_in_rest' => true,
				'rewrite'      => array( 'slug' => 'nhom-dich-vu', 'with_front' => false ),
			)
		);

		// Chính sách khách hàng: tiêu đề + nội dung + 2 ảnh thẻ (ACF). Có trang chi tiết /chinh-sach/ten-chinh-sach/.
		// Không bật archive để danh sách nằm ở Trang dùng mẫu "Chính sách dịch vụ".
		register_post_type(
			'isha_policy',
			array(
				'labels'        => array(
					'name'          => __( 'Chính sách khách hàng', 'isha-core' ),
					'singular_name' => __( 'Chính sách khách hàng', 'isha-core' ),
					'add_new_item'  => __( 'Thêm chính sách', 'isha-core' ),
					'edit_item'     => __( 'Sửa chính sách', 'isha-core' ),
					'all_items'     => __( 'Tất cả chính sách', 'isha-core' ),
					'not_found'     => __( 'Chưa có chính sách nào.', 'isha-core' ),
				),
				'public'        => true,
				'has_archive'   => false,
				'show_in_rest'  => true, // Bắt buộc để dùng trình soạn thảo khối cho phần nội dung.
				'menu_icon'     => 'dashicons-id-alt',
				'menu_position' => 27,
				'supports'      => array( 'title', 'editor', 'page-attributes' ),
				'rewrite'       => array( 'slug' => 'chinh-sach', 'with_front' => false ),
			)
		);

		// Lịch hẹn: chỉ dùng trong admin.
		register_post_type(
			'isha_appointment',
			array(
				'labels'              => array(
					'name'          => __( 'Lịch hẹn', 'isha-core' ),
					'singular_name' => __( 'Lịch hẹn', 'isha-core' ),
					'edit_item'     => __( 'Chi tiết lịch hẹn', 'isha-core' ),
					'all_items'     => __( 'Tất cả lịch hẹn', 'isha-core' ),
					'not_found'     => __( 'Chưa có lịch hẹn nào.', 'isha-core' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'exclude_from_search' => true,
				'menu_icon'           => 'dashicons-clock',
				'menu_position'       => 28,
				'supports'            => array( 'title' ),
				'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'        => true,
			)
		);

		// Nội dung của trang chủ điện ảnh: sự kiện, lời kể khách hàng, bài viết kiến thức.
		self::register_event();
		self::register_testimonial();
		self::register_article();
		self::register_space();
	}

	/**
	 * Không gian ISHA (18/11/2026, docs/home-architecture.md mục 18): mỗi bài là một khu của phòng khám (sảnh, phòng trị liệu, phòng VIP…).
	 * Trang danh sách là Trang dùng mẫu "Không gian ISHA" (slug khong-gian-isha); bài chi tiết nằm dưới cùng đường dẫn
	 * /khong-gian-isha/ten-bai/ nên không bật archive. Tiêu đề nhỏ, ảnh và ô "Xuất hiện ở trang chủ" ở meta box (class-space-fields.php);
	 * menu_order sắp thứ tự trên trang danh sách và trang chủ. Vị trí trong menu admin do ISHA_Core_Space_Fields::admin_menu() đặt
	 * (ngay dưới Giới thiệu, menu_position của post type chỉ nhận số nguyên).
	 */
	private static function register_space() {
		register_post_type(
			'isha_space',
			array(
				'labels'       => array(
					'name'                     => __( 'Không gian ISHA', 'isha-core' ),
					'singular_name'            => __( 'Không gian', 'isha-core' ),
					'menu_name'                => __( 'Không gian ISHA', 'isha-core' ),
					'name_admin_bar'           => __( 'Không gian', 'isha-core' ),
					'add_new'                  => __( 'Thêm mới', 'isha-core' ),
					'add_new_item'             => __( 'Thêm không gian', 'isha-core' ),
					'edit_item'                => __( 'Sửa không gian', 'isha-core' ),
					'new_item'                 => __( 'Không gian mới', 'isha-core' ),
					'view_item'                => __( 'Xem không gian', 'isha-core' ),
					'view_items'               => __( 'Xem các không gian', 'isha-core' ),
					'search_items'             => __( 'Tìm không gian', 'isha-core' ),
					'not_found'                => __( 'Chưa có không gian nào.', 'isha-core' ),
					'not_found_in_trash'       => __( 'Không có không gian nào trong thùng rác.', 'isha-core' ),
					'all_items'                => __( 'Tất cả không gian', 'isha-core' ),
					'attributes'               => __( 'Thứ tự hiển thị', 'isha-core' ),
					'insert_into_item'         => __( 'Chèn vào bài', 'isha-core' ),
					'uploaded_to_this_item'    => __( 'Đã tải lên bài này', 'isha-core' ),
					'featured_image'           => __( 'Ảnh đại diện (dọc 4:5)', 'isha-core' ),
					'set_featured_image'       => __( 'Đặt ảnh đại diện', 'isha-core' ),
					'remove_featured_image'    => __( 'Gỡ ảnh đại diện', 'isha-core' ),
					'use_featured_image'       => __( 'Dùng làm ảnh đại diện', 'isha-core' ),
					'filter_items_list'        => __( 'Lọc danh sách không gian', 'isha-core' ),
					'items_list_navigation'    => __( 'Điều hướng danh sách không gian', 'isha-core' ),
					'items_list'               => __( 'Danh sách không gian', 'isha-core' ),
					'item_published'           => __( 'Đã đăng bài.', 'isha-core' ),
					'item_published_privately' => __( 'Đã đăng bài ở chế độ riêng tư.', 'isha-core' ),
					'item_reverted_to_draft'   => __( 'Đã chuyển bài về bản nháp.', 'isha-core' ),
					'item_trashed'             => __( 'Đã chuyển bài vào thùng rác.', 'isha-core' ),
					'item_scheduled'           => __( 'Đã lên lịch đăng bài.', 'isha-core' ),
					'item_updated'             => __( 'Đã cập nhật bài.', 'isha-core' ),
					'item_link'                => __( 'Liên kết không gian', 'isha-core' ),
					'item_link_description'    => __( 'Liên kết tới một không gian.', 'isha-core' ),
				),
				'public'       => true,
				'has_archive'  => false,
				'show_in_rest' => true, // Dùng trình soạn thảo khối cho nội dung bài.
				'menu_icon'    => 'dashicons-building',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
				'rewrite'      => array( 'slug' => 'khong-gian-isha', 'with_front' => false ),
			)
		);
	}

	/**
	 * Sự kiện (Workshop, Design Talk, Charity): có trang chi tiết /su-kien/ten-su-kien/, chưa có trang lưu trữ.
	 * Ảnh đại diện là ảnh ngang 2.39:1; ngày giờ, địa điểm, link đăng ký, cờ nổi bật và ảnh dọc nhập ở meta box (class-event-fields.php).
	 */
	private static function register_event() {
		register_post_type(
			'isha_event',
			array(
				'labels'        => array(
					'name'                     => __( 'Sự kiện', 'isha-core' ),
					'singular_name'            => __( 'Sự kiện', 'isha-core' ),
					'menu_name'                => __( 'Sự kiện', 'isha-core' ),
					'name_admin_bar'           => __( 'Sự kiện', 'isha-core' ),
					'add_new'                  => __( 'Thêm mới', 'isha-core' ),
					'add_new_item'             => __( 'Thêm sự kiện', 'isha-core' ),
					'edit_item'                => __( 'Sửa sự kiện', 'isha-core' ),
					'new_item'                 => __( 'Sự kiện mới', 'isha-core' ),
					'view_item'                => __( 'Xem sự kiện', 'isha-core' ),
					'view_items'               => __( 'Xem các sự kiện', 'isha-core' ),
					'search_items'             => __( 'Tìm sự kiện', 'isha-core' ),
					'not_found'                => __( 'Chưa có sự kiện nào.', 'isha-core' ),
					'not_found_in_trash'       => __( 'Không có sự kiện nào trong thùng rác.', 'isha-core' ),
					'all_items'                => __( 'Tất cả sự kiện', 'isha-core' ),
					'archives'                 => __( 'Lưu trữ sự kiện', 'isha-core' ),
					'attributes'               => __( 'Thuộc tính sự kiện', 'isha-core' ),
					'insert_into_item'         => __( 'Chèn vào sự kiện', 'isha-core' ),
					'uploaded_to_this_item'    => __( 'Đã tải lên sự kiện này', 'isha-core' ),
					'featured_image'           => __( 'Ảnh đại diện (ngang 2.39:1)', 'isha-core' ),
					'set_featured_image'       => __( 'Đặt ảnh đại diện', 'isha-core' ),
					'remove_featured_image'    => __( 'Gỡ ảnh đại diện', 'isha-core' ),
					'use_featured_image'       => __( 'Dùng làm ảnh đại diện', 'isha-core' ),
					'filter_items_list'        => __( 'Lọc danh sách sự kiện', 'isha-core' ),
					'filter_by_date'           => __( 'Lọc theo ngày', 'isha-core' ),
					'items_list_navigation'    => __( 'Điều hướng danh sách sự kiện', 'isha-core' ),
					'items_list'               => __( 'Danh sách sự kiện', 'isha-core' ),
					'item_published'           => __( 'Đã đăng sự kiện.', 'isha-core' ),
					'item_published_privately' => __( 'Đã đăng sự kiện ở chế độ riêng tư.', 'isha-core' ),
					'item_reverted_to_draft'   => __( 'Đã chuyển sự kiện về bản nháp.', 'isha-core' ),
					'item_trashed'             => __( 'Đã chuyển sự kiện vào thùng rác.', 'isha-core' ),
					'item_scheduled'           => __( 'Đã lên lịch đăng sự kiện.', 'isha-core' ),
					'item_updated'             => __( 'Đã cập nhật sự kiện.', 'isha-core' ),
					'item_link'                => __( 'Liên kết sự kiện', 'isha-core' ),
					'item_link_description'    => __( 'Liên kết tới một sự kiện.', 'isha-core' ),
				),
				'public'        => true,
				'has_archive'   => false,
				'show_in_rest'  => true, // Dùng trình soạn thảo khối cho phần mô tả.
				'menu_icon'     => 'dashicons-calendar-alt',
				'menu_position' => 22,
				// 'elementor': luôn bật nút "Sửa bằng Elementor" cho Sự kiện (như Tin tức). Phần đầu trang, thẻ thông tin và sự kiện khác là template
				// cố định; Elementor chỉ chỉnh vùng nội dung (the_content()).
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'elementor' ),
				'rewrite'       => array( 'slug' => 'su-kien', 'with_front' => false ),
			)
		);

		register_taxonomy(
			'isha_event_type',
			'isha_event',
			array(
				'labels'            => array(
					'name'                       => __( 'Loại sự kiện', 'isha-core' ),
					'singular_name'              => __( 'Loại sự kiện', 'isha-core' ),
					'menu_name'                  => __( 'Loại sự kiện', 'isha-core' ),
					'search_items'               => __( 'Tìm loại sự kiện', 'isha-core' ),
					'popular_items'              => __( 'Loại sự kiện phổ biến', 'isha-core' ),
					'all_items'                  => __( 'Tất cả loại sự kiện', 'isha-core' ),
					'edit_item'                  => __( 'Sửa loại sự kiện', 'isha-core' ),
					'view_item'                  => __( 'Xem loại sự kiện', 'isha-core' ),
					'update_item'                => __( 'Cập nhật loại sự kiện', 'isha-core' ),
					'add_new_item'               => __( 'Thêm loại sự kiện', 'isha-core' ),
					'new_item_name'              => __( 'Tên loại sự kiện mới', 'isha-core' ),
					'separate_items_with_commas' => __( 'Phân cách các loại sự kiện bằng dấu phẩy', 'isha-core' ),
					'add_or_remove_items'        => __( 'Thêm hoặc gỡ loại sự kiện', 'isha-core' ),
					'choose_from_most_used'      => __( 'Chọn từ các loại sự kiện dùng nhiều nhất', 'isha-core' ),
					'not_found'                  => __( 'Không tìm thấy loại sự kiện nào.', 'isha-core' ),
					'no_terms'                   => __( 'Chưa có loại sự kiện', 'isha-core' ),
					'items_list_navigation'      => __( 'Điều hướng danh sách loại sự kiện', 'isha-core' ),
					'items_list'                 => __( 'Danh sách loại sự kiện', 'isha-core' ),
					'most_used'                  => __( 'Dùng nhiều nhất', 'isha-core' ),
					'back_to_items'              => __( '&larr; Quay lại Loại sự kiện', 'isha-core' ),
					'item_link'                  => __( 'Liên kết loại sự kiện', 'isha-core' ),
					'item_link_description'      => __( 'Liên kết tới một loại sự kiện.', 'isha-core' ),
					'name_field_description'     => __( 'Tên hiển thị trên website, ví dụ Workshop, Design Talk, Charity.', 'isha-core' ),
					'slug_field_description'     => __( 'Slug là phiên bản dùng trong đường dẫn của tên: thường viết thường, không dấu, chỉ gồm chữ cái, số và dấu gạch ngang.', 'isha-core' ),
					'desc_field_description'     => __( 'Mô tả thường không hiển thị nổi bật; một số giao diện có thể dùng.', 'isha-core' ),
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'loai-su-kien', 'with_front' => false ),
			)
		);
	}

	/**
	 * Lời kể khách hàng: chỉ dùng trong admin (không có URL ngoài website). Tên hiển thị là tiêu đề bài,
	 * ảnh chân dung (tuỳ chọn) là ảnh đại diện; lời kể và liệu trình nhập ở meta box (class-testimonial-fields.php).
	 */
	private static function register_testimonial() {
		register_post_type(
			'isha_testimonial',
			array(
				'labels'              => array(
					'name'                     => __( 'Lời kể khách hàng', 'isha-core' ),
					'singular_name'            => __( 'Lời kể', 'isha-core' ),
					'menu_name'                => __( 'Lời kể khách hàng', 'isha-core' ),
					'name_admin_bar'           => __( 'Lời kể khách hàng', 'isha-core' ),
					'add_new'                  => __( 'Thêm mới', 'isha-core' ),
					'add_new_item'             => __( 'Thêm lời kể', 'isha-core' ),
					'edit_item'                => __( 'Sửa lời kể', 'isha-core' ),
					'new_item'                 => __( 'Lời kể mới', 'isha-core' ),
					'view_item'                => __( 'Xem lời kể', 'isha-core' ),
					'view_items'               => __( 'Xem các lời kể', 'isha-core' ),
					'search_items'             => __( 'Tìm lời kể', 'isha-core' ),
					'not_found'                => __( 'Chưa có lời kể nào.', 'isha-core' ),
					'not_found_in_trash'       => __( 'Không có lời kể nào trong thùng rác.', 'isha-core' ),
					'all_items'                => __( 'Tất cả lời kể', 'isha-core' ),
					'archives'                 => __( 'Lưu trữ lời kể', 'isha-core' ),
					'attributes'               => __( 'Thuộc tính lời kể', 'isha-core' ),
					'insert_into_item'         => __( 'Chèn vào lời kể', 'isha-core' ),
					'uploaded_to_this_item'    => __( 'Đã tải lên lời kể này', 'isha-core' ),
					'featured_image'           => __( 'Ảnh chân dung', 'isha-core' ),
					'set_featured_image'       => __( 'Đặt ảnh chân dung', 'isha-core' ),
					'remove_featured_image'    => __( 'Gỡ ảnh chân dung', 'isha-core' ),
					'use_featured_image'       => __( 'Dùng làm ảnh chân dung', 'isha-core' ),
					'filter_items_list'        => __( 'Lọc danh sách lời kể', 'isha-core' ),
					'filter_by_date'           => __( 'Lọc theo ngày', 'isha-core' ),
					'items_list_navigation'    => __( 'Điều hướng danh sách lời kể', 'isha-core' ),
					'items_list'               => __( 'Danh sách lời kể', 'isha-core' ),
					'item_published'           => __( 'Đã đăng lời kể.', 'isha-core' ),
					'item_published_privately' => __( 'Đã đăng lời kể ở chế độ riêng tư.', 'isha-core' ),
					'item_reverted_to_draft'   => __( 'Đã chuyển lời kể về bản nháp.', 'isha-core' ),
					'item_trashed'             => __( 'Đã chuyển lời kể vào thùng rác.', 'isha-core' ),
					'item_scheduled'           => __( 'Đã lên lịch đăng lời kể.', 'isha-core' ),
					'item_updated'             => __( 'Đã cập nhật lời kể.', 'isha-core' ),
					'item_link'                => __( 'Liên kết lời kể', 'isha-core' ),
					'item_link_description'    => __( 'Liên kết tới một lời kể.', 'isha-core' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-format-quote',
				'menu_position'       => 24,
				'supports'            => array( 'title', 'thumbnail' ),
			)
		);
	}

	/**
	 * Tin tức (trước 17/11/2026 tên menu là "Kiến thức làn da"; post type giữ nguyên). Menu đặt tên "Tin tức" để không nhầm với Bài viết của
	 * WordPress. Từ 18/11/2026 bài nằm dưới đường dẫn của trang Tin tức (/tin-tuc/ten-bai/) và không còn trang lưu trữ riêng: danh sách là Trang
	 * Tin tức (Cài đặt › Đọc › Trang bài viết); đường dẫn cũ /bai-viet/… và /chu-de/… chuyển 301 (class-legacy-urls.php). Nội dung ngắn và cờ
	 * nổi bật ở meta box (class-article-fields.php).
	 */
	private static function register_article() {
		register_post_type(
			'isha_article',
			array(
				'labels'        => array(
					'name'                     => __( 'Tin tức', 'isha-core' ),
					'singular_name'            => __( 'Bài viết', 'isha-core' ),
					'menu_name'                => __( 'Tin tức', 'isha-core' ),
					'name_admin_bar'           => __( 'Tin tức', 'isha-core' ),
					'add_new'                  => __( 'Thêm mới', 'isha-core' ),
					'add_new_item'             => __( 'Thêm bài viết', 'isha-core' ),
					'edit_item'                => __( 'Sửa bài viết', 'isha-core' ),
					'new_item'                 => __( 'Bài viết mới', 'isha-core' ),
					'view_item'                => __( 'Xem bài viết', 'isha-core' ),
					'view_items'               => __( 'Xem các bài viết', 'isha-core' ),
					'search_items'             => __( 'Tìm bài viết', 'isha-core' ),
					'not_found'                => __( 'Chưa có bài viết nào.', 'isha-core' ),
					'not_found_in_trash'       => __( 'Không có bài viết nào trong thùng rác.', 'isha-core' ),
					'all_items'                => __( 'Tất cả bài viết', 'isha-core' ),
					'archives'                 => __( 'Lưu trữ tin tức', 'isha-core' ),
					'attributes'               => __( 'Thuộc tính bài viết', 'isha-core' ),
					'insert_into_item'         => __( 'Chèn vào bài viết', 'isha-core' ),
					'uploaded_to_this_item'    => __( 'Đã tải lên bài viết này', 'isha-core' ),
					'featured_image'           => __( 'Ảnh đại diện (dọc 4:5)', 'isha-core' ),
					'set_featured_image'       => __( 'Đặt ảnh đại diện', 'isha-core' ),
					'remove_featured_image'    => __( 'Gỡ ảnh đại diện', 'isha-core' ),
					'use_featured_image'       => __( 'Dùng làm ảnh đại diện', 'isha-core' ),
					'filter_items_list'        => __( 'Lọc danh sách bài viết', 'isha-core' ),
					'filter_by_date'           => __( 'Lọc theo ngày', 'isha-core' ),
					'items_list_navigation'    => __( 'Điều hướng danh sách bài viết', 'isha-core' ),
					'items_list'               => __( 'Danh sách bài viết', 'isha-core' ),
					'item_published'           => __( 'Đã đăng bài viết.', 'isha-core' ),
					'item_published_privately' => __( 'Đã đăng bài viết ở chế độ riêng tư.', 'isha-core' ),
					'item_reverted_to_draft'   => __( 'Đã chuyển bài viết về bản nháp.', 'isha-core' ),
					'item_trashed'             => __( 'Đã chuyển bài viết vào thùng rác.', 'isha-core' ),
					'item_scheduled'           => __( 'Đã lên lịch đăng bài viết.', 'isha-core' ),
					'item_updated'             => __( 'Đã cập nhật bài viết.', 'isha-core' ),
					'item_link'                => __( 'Liên kết bài viết', 'isha-core' ),
					'item_link_description'    => __( 'Liên kết tới một bài viết.', 'isha-core' ),
				),
				'public'        => true,
				'has_archive'   => false,
				'show_in_rest'  => true, // Dùng trình soạn thảo khối cho nội dung bài.
				'menu_icon'     => 'dashicons-media-document',
				'menu_position' => 23,
				// 'elementor': luôn bật nút "Sửa bằng Elementor" cho bài Tin tức, không phụ thuộc ô chọn loại bài ở Elementor > Cài đặt. Phần đầu trang,
				// thẻ tư vấn, mục lục và bài liên quan là template cố định; Elementor chỉ chỉnh vùng nội dung (the_content()).
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'elementor' ),
				'rewrite'       => array( 'slug' => 'tin-tuc', 'with_front' => false ),
			)
		);

		register_taxonomy(
			'isha_article_cat',
			'isha_article',
			array(
				'labels'            => array(
					'name'                     => __( 'Chủ đề', 'isha-core' ),
					'singular_name'            => __( 'Chủ đề', 'isha-core' ),
					'menu_name'                => __( 'Chủ đề', 'isha-core' ),
					'search_items'             => __( 'Tìm chủ đề', 'isha-core' ),
					'all_items'                => __( 'Tất cả chủ đề', 'isha-core' ),
					'parent_item'              => __( 'Chủ đề cha', 'isha-core' ),
					'parent_item_colon'        => __( 'Chủ đề cha:', 'isha-core' ),
					'edit_item'                => __( 'Sửa chủ đề', 'isha-core' ),
					'view_item'                => __( 'Xem chủ đề', 'isha-core' ),
					'update_item'              => __( 'Cập nhật chủ đề', 'isha-core' ),
					'add_new_item'             => __( 'Thêm chủ đề', 'isha-core' ),
					'new_item_name'            => __( 'Tên chủ đề mới', 'isha-core' ),
					'not_found'                => __( 'Không tìm thấy chủ đề nào.', 'isha-core' ),
					'no_terms'                 => __( 'Chưa có chủ đề', 'isha-core' ),
					'filter_by_item'           => __( 'Lọc theo chủ đề', 'isha-core' ),
					'items_list_navigation'    => __( 'Điều hướng danh sách chủ đề', 'isha-core' ),
					'items_list'               => __( 'Danh sách chủ đề', 'isha-core' ),
					'most_used'                => __( 'Dùng nhiều nhất', 'isha-core' ),
					'back_to_items'            => __( '&larr; Quay lại Chủ đề', 'isha-core' ),
					'item_link'                => __( 'Liên kết chủ đề', 'isha-core' ),
					'item_link_description'    => __( 'Liên kết tới một chủ đề.', 'isha-core' ),
					'name_field_description'   => __( 'Tên hiển thị trên website.', 'isha-core' ),
					'slug_field_description'   => __( 'Slug là phiên bản dùng trong đường dẫn của tên: thường viết thường, không dấu, chỉ gồm chữ cái, số và dấu gạch ngang.', 'isha-core' ),
					'parent_field_description' => __( 'Chọn chủ đề cha để tạo cấp bậc cho chủ đề này.', 'isha-core' ),
					'desc_field_description'   => __( 'Mô tả thường không hiển thị nổi bật; một số giao diện có thể dùng.', 'isha-core' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'chu-de', 'with_front' => false ),
			)
		);
	}
}
