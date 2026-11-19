<?php
/**
 * Lược đồ trường của meta box "Nội dung trang chủ".
 *
 * Đây là NGUỒN DUY NHẤT cho giao diện admin, sanitize và kiểm thử. Giá trị mặc định (nội dung Phụ lục A) nằm ở theme:
 * isha_home_defaults() trong inc/home/data.php; mọi khoá ở đó phải có mặt ở đây và ngược lại.
 *
 * Thứ tự thẻ trùng thứ tự section trên trang (tái cấu trúc 17/11/2026, docs/home-architecture.md mục 15): hero, space, results,
 * services, founder-story, events, articles, testimonials, process, partners, cta. Câu chuyện, Triết lý, Giá trị đã chuyển sang trang
 * Giới thiệu (làm ở giai đoạn sau) và Tầm nhìn được thay bằng Founder: dữ liệu cũ của các section này VẪN CÒN trong meta của trang chủ
 * (_isha_home_story, _isha_home_philosophy, _isha_home_values, _isha_home_vision), chỉ không còn thẻ nhập ở đây.
 *
 * Mỗi section:
 *   'label'  => tên thẻ (tab)
 *   'note'   => dòng hướng dẫn (tuỳ chọn)
 *   'fields' => array( khoá => trường )
 *   'groups' => array( khoá => array( 'label' => 'Nhóm %d', 'count' => N, 'fields' => array( khoá => trường ) ) )
 * Mọi section đều có công tắc "Hiển thị section này" (khoá `enabled`, mặc định bật) do code tự thêm, không khai ở đây.
 *
 * Trường:
 *   'type'  => text | textarea | bool | image | video | gallery
 *   'label' => nhãn
 *   'max'   => số ký tự tối đa (text/textarea) hoặc số ảnh tối đa (gallery)
 *   'rows'  => số dòng (textarea)
 *   'help'  => dòng chú thích dưới trường (tuỳ chọn)
 *   'on'    => true nếu mặc định BẬT khi chưa lưu (bool)
 *
 * Kiểu media lưu ID tệp đính kèm (image/video: một số nguyên; gallery: mảng số nguyên theo thứ tự).
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'hero'          => array(
		'label'  => __( 'Hero và intro', 'isha-core' ),
		'note'   => __( 'Banner không gian ISHA: video ngang và dọc ghép từ ảnh không gian của phòng khám.', 'isha-core' ),
		'fields' => array(
			'intro_enabled'    => array(
				'type'  => 'bool',
				'label' => __( 'Bật intro (mặt trời mọc, chạy một lần mỗi phiên truy cập)', 'isha-core' ),
				'on'    => true,
			),
			'intro_text'       => array(
				'type'  => 'text',
				'label' => __( 'Câu intro', 'isha-core' ),
				'max'   => 80,
			),
			'video_landscape'  => array(
				'type'  => 'video',
				'label' => __( 'Video ngang (máy tính)', 'isha-core' ),
				'help'  => __( 'MP4 H.264 1920×1080, 8–15 giây lặp liền mạch, không tiếng, tối đa 8 MB.', 'isha-core' ),
			),
			'video_portrait'   => array(
				'type'  => 'video',
				'label' => __( 'Video dọc (điện thoại)', 'isha-core' ),
				'help'  => __( 'MP4 H.264 1080×1350 hoặc 1080×1920, không tiếng, tối đa 3 MB.', 'isha-core' ),
			),
			'poster_landscape' => array(
				'type'  => 'image',
				'label' => __( 'Poster ngang', 'isha-core' ),
				'help'  => __( 'JPG hoặc WebP 1920×1080, là khung đầu của video ngang, tối đa 250 KB.', 'isha-core' ),
			),
			'poster_portrait'  => array(
				'type'  => 'image',
				'label' => __( 'Poster dọc', 'isha-core' ),
				'help'  => __( 'JPG hoặc WebP 1080×1350, là khung đầu của video dọc, tối đa 250 KB.', 'isha-core' ),
			),
			'eyebrow'          => array(
				'type'  => 'text',
				'label' => __( 'Dòng nhỏ', 'isha-core' ),
				'max'   => 80,
			),
			'title'            => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề (h1)', 'isha-core' ),
				'max'   => 120,
			),
			'description'      => array(
				'type'  => 'textarea',
				'label' => __( 'Mô tả', 'isha-core' ),
				'max'   => 240,
				'rows'  => 3,
			),
			'button_label'     => array(
				'type'  => 'text',
				'label' => __( 'Nhãn nút chính', 'isha-core' ),
				'max'   => 40,
			),
		),
	),
	'space'         => array(
		'label'  => __( 'Không gian ISHA', 'isha-core' ),
		'note'   => __( 'Section hiện các bài ở menu Không gian ISHA có đánh dấu "Xuất hiện bài này ở trang chủ" (ảnh đại diện và tên bài, bấm vào mở bài), theo ô Thứ tự của bài. Chưa đánh dấu bài nào thì section dùng ảnh dự phòng bên dưới.', 'isha-core' ),
		'fields' => array(
			'title'   => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
			'lead'    => array(
				'type'  => 'textarea',
				'label' => __( 'Câu dẫn', 'isha-core' ),
				'max'   => 200,
				'rows'  => 2,
			),
			'link_label' => array(
				'type'  => 'text',
				'label' => __( 'Chữ liên kết tới trang Không gian ISHA', 'isha-core' ),
				'max'   => 60,
			),
			'gallery' => array(
				'type'  => 'gallery',
				'label' => __( 'Ảnh dự phòng', 'isha-core' ),
				'max'   => 12,
				'help'  => __( 'Chỉ dùng khi chưa có bài Không gian nào được đánh dấu hiện ở trang chủ. 6–10 ảnh, cạnh dài từ 2000 px, cả dọc và ngang; chú thích dưới ảnh lấy từ trường Chú thích trong Thư viện phương tiện.', 'isha-core' ),
			),
		),
	),
	'results'       => array(
		'label'  => __( 'Hiệu quả liệu trình', 'isha-core' ),
		'note'   => __( 'Ảnh trước và sau điều trị dành riêng cho trang chủ: mỗi ảnh là MỘT tấm đã ghép sẵn hai hình (trước và sau). Ảnh đổi lần lượt như một băng chuyền; chưa có ảnh nào thì section ẩn.', 'isha-core' ),
		'fields' => array(
			'title'   => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
			'lead'    => array(
				'type'  => 'textarea',
				'label' => __( 'Câu dẫn', 'isha-core' ),
				'max'   => 200,
				'rows'  => 2,
			),
			'gallery' => array(
				'type'  => 'gallery',
				'label' => __( 'Ảnh trước và sau (đã ghép)', 'isha-core' ),
				'max'   => 5,
				'help'  => __( 'Tối đa 5 ảnh, mọi ảnh cùng một tỉ lệ (khuyến nghị ngang 3:2, ví dụ 2400×1600; mỗi nửa là một hình dọc 4:5 hoặc 3:4), JPG hoặc WebP tối đa 400 KB. Nhãn "Trước" và "Sau" nên ghép sẵn trong ảnh. Hãy nhập Văn bản thay thế (Alt) mô tả ảnh, ví dụ "Trước và sau liệu trình điều trị mụn"; chú thích hiện dưới ảnh lấy từ trường Chú thích trong Thư viện phương tiện.', 'isha-core' ),
			),
			'note'    => array(
				'type'  => 'text',
				'label' => __( 'Ghi chú dưới ảnh (miễn trừ kết quả)', 'isha-core' ),
				'max'   => 160,
			),
		),
	),
	'services'      => array(
		'label'  => __( 'Dịch vụ tiêu biểu', 'isha-core' ),
		'note'   => __( 'Chỉ nhóm dịch vụ được tick "Dịch vụ tiêu biểu" (Dịch vụ › Nhóm dịch vụ › Sửa) mới hiện ở đây, theo thứ tự hiển thị của nhóm; chưa nhóm nào được tick thì section ẩn. Lời dẫn từng nhóm, ảnh bìa và thứ tự nhập ở cùng màn hình đó; liệu trình nhập tại Dịch vụ.', 'isha-core' ),
		'fields' => array(
			'title'      => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
			'lead'       => array(
				'type'  => 'textarea',
				'label' => __( 'Câu dẫn', 'isha-core' ),
				'max'   => 200,
				'rows'  => 2,
			),
			'link_label' => array(
				'type'  => 'text',
				'label' => __( 'Nhãn liên kết tới trang Dịch vụ', 'isha-core' ),
				'max'   => 60,
				'help'  => __( 'Liên kết dẫn tới trang dùng mẫu "Dịch vụ"; không có trang đó thì liên kết không hiện.', 'isha-core' ),
			),
		),
	),
	'founder-story' => array(
		'label'  => __( 'Founder', 'isha-core' ),
		'note'   => __( 'Câu chuyện của bác sĩ sáng lập. Bên trái là thư viện ảnh 3D: khách lăn chuột, kéo hoặc vuốt ngang để đi qua từng ảnh; hết ảnh thì ảnh chân dung hiện ở giữa kèm chức danh và tên. Bên phải là tiêu đề, câu trích và câu chuyện. Chưa có ảnh gallery thì khung trái chỉ hiện ảnh chân dung. Câu trích và câu chuyện mặc định lấy từ section Tầm nhìn cũ; hãy thay bằng nội dung chính thức.', 'isha-core' ),
		'fields' => array(
			'title'     => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
			'portrait'  => array(
				'type'  => 'image',
				'label' => __( 'Ảnh chân dung', 'isha-core' ),
				'help'  => __( 'Hiện ở cuối thư viện ảnh, trong khung dọc 4:5 kích thước cố định, nền tối có vầng sáng vàng đồng phía sau. Nên dùng ảnh ĐÃ TÁCH NỀN (PNG hoặc WebP nền trong suốt), chủ thể đứng sát mép dưới ảnh, cạnh dài từ 1500 px, tối đa 600 KB: ảnh được đặt vừa khung và đứng ở đáy, chân ảnh mờ dần vào khung. Ảnh JPG (có nền) sẽ được phủ kín khung. Văn bản thay thế (Alt) để trống thì dùng tên bên dưới.', 'isha-core' ),
			),
			'quote'     => array(
				'type'  => 'textarea',
				'label' => __( 'Câu trích (không cần gõ dấu ngoặc kép)', 'isha-core' ),
				'max'   => 300,
				'rows'  => 3,
			),
			'story'     => array(
				'type'  => 'textarea',
				'label' => __( 'Câu chuyện', 'isha-core' ),
				'max'   => 1200,
				'rows'  => 8,
				'help'  => __( 'Một dòng trống để tách đoạn. Nên viết 2 đến 3 đoạn ngắn.', 'isha-core' ),
			),
			'name'      => array(
				'type'  => 'text',
				'label' => __( 'Tên', 'isha-core' ),
				'max'   => 60,
			),
			'role'      => array(
				'type'  => 'text',
				'label' => __( 'Chức danh', 'isha-core' ),
				'max'   => 80,
			),
			'gallery'   => array(
				'type'  => 'gallery',
				'label' => __( 'Cụm ảnh gallery', 'isha-core' ),
				'max'   => 20,
				'help'  => __( 'Tối đa 20 ảnh, chọn được nhiều ảnh một lần; ảnh hiện theo đúng thứ tự ở đây. Ảnh dọc, ngang hay vuông đều được, không giới hạn kích thước; nên dùng JPG hoặc WebP. Lưu ý: ảnh có tỉ lệ nằm ngoài khoảng 2:3 (dọc) đến 16:9 (ngang), ví dụ ảnh dọc rất cao hay ảnh toàn cảnh rất dài, sẽ bị cắt bớt phần mép cho vừa khung; hãy giữ chủ thể ở giữa ảnh. Bấm vào ảnh trên trang sẽ mở ảnh gốc cỡ lớn. Hãy nhập Văn bản thay thế (Alt) mô tả từng ảnh.', 'isha-core' ),
			),
		),
	),
	'events'        => array(
		'label'  => __( 'Workshop & sự kiện', 'isha-core' ),
		'note'   => __( 'Sự kiện nhập tại Sự kiện. Trang chủ hiện 3 sự kiện: sự kiện đánh dấu Nổi bật đứng trước, thiếu thì bổ sung bằng sự kiện mới nhất. Bộ lọc theo Loại sự kiện chỉ hiện khi có từ 2 loại. Liên kết "Xem tất cả" dẫn tới trang có slug danh-sach-su-kien. Ảnh lấy từ Ảnh đại diện của sự kiện.', 'isha-core' ),
		'fields' => array(
			'title'      => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
			'lead'       => array(
				'type'  => 'textarea',
				'label' => __( 'Câu dẫn', 'isha-core' ),
				'max'   => 200,
				'rows'  => 2,
			),
			'link_label' => array(
				'type'  => 'text',
				'label' => __( 'Nhãn liên kết "Xem tất cả"', 'isha-core' ),
				'max'   => 60,
			),
		),
	),
	'articles'      => array(
		'label'  => __( 'Tin tức', 'isha-core' ),
		'note'   => __( 'Bài viết nhập tại Tin tức. Bài được đánh dấu Nổi bật đứng đầu. Liên kết "Xem tất cả" dẫn tới trang Tin tức.', 'isha-core' ),
		'fields' => array(
			'title'      => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
			'link_label' => array(
				'type'  => 'text',
				'label' => __( 'Nhãn liên kết "Xem tất cả"', 'isha-core' ),
				'max'   => 60,
			),
		),
	),
	'testimonials'  => array(
		'label'  => __( 'Lời kể khách hàng', 'isha-core' ),
		'note'   => __( 'Lời kể nhập tại Lời kể khách hàng; mọi lời kể đã xuất bản và có nội dung đều hiện (mới nhất trước).', 'isha-core' ),
		'fields' => array(
			'title' => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
		),
	),
	'process'       => array(
		'label'  => __( 'Quy trình', 'isha-core' ),
		'note'   => __( 'Section này đang được ẩn theo yêu cầu (bỏ tick "Hiển thị section này"); nội dung vẫn giữ để bật lại khi cần.', 'isha-core' ),
		'fields' => array(
			'title' => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
		),
		'groups' => array(
			'items' => array(
				/* translators: %d: số thứ tự bước */
				'label'  => __( 'Bước %d', 'isha-core' ),
				'count'  => 5,
				'fields' => array(
					'name' => array(
						'type'  => 'text',
						'label' => __( 'Tên bước', 'isha-core' ),
						'max'   => 80,
					),
					'desc' => array(
						'type'  => 'textarea',
						'label' => __( 'Mô tả', 'isha-core' ),
						'max'   => 240,
						'rows'  => 3,
					),
				),
			),
		),
	),
	'partners'      => array(
		'label'  => __( 'Đối tác', 'isha-core' ),
		'fields' => array(
			'title'   => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
			'gallery' => array(
				'type'  => 'gallery',
				'label' => __( 'Logo đối tác', 'isha-core' ),
				'max'   => 16,
				'help'  => __( 'PNG hoặc WebP nền trong, ưu tiên logo một màu trắng. Tên đối tác lấy từ Văn bản thay thế (Alt) của ảnh. Mọi logo được ép vào ô cùng kích thước.', 'isha-core' ),
			),
		),
	),
	'cta'           => array(
		'label'  => __( 'Cảnh kết', 'isha-core' ),
		'note'   => __( 'Cảnh kết cũng được dùng ở cuối trang chi tiết liệu trình.', 'isha-core' ),
		'fields' => array(
			'title'            => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 120,
			),
			'description'      => array(
				'type'  => 'textarea',
				'label' => __( 'Mô tả', 'isha-core' ),
				'max'   => 240,
				'rows'  => 3,
			),
			'background_image' => array(
				'type'  => 'image',
				'label' => __( 'Ảnh nền (tuỳ chọn)', 'isha-core' ),
				'help'  => __( 'Ảnh 2400×1350. Nếu có cả video thì video được ưu tiên.', 'isha-core' ),
			),
			'background_video' => array(
				'type'  => 'video',
				'label' => __( 'Video nền (tuỳ chọn)', 'isha-core' ),
				'help'  => __( 'MP4 H.264 1920×1080, không tiếng, tối đa 8 MB.', 'isha-core' ),
			),
		),
	),
);
