<?php
/**
 * Lược đồ trường của meta box "Nội dung trang chủ".
 *
 * Đây là NGUỒN DUY NHẤT cho giao diện admin, sanitize và kiểm thử. Giá trị mặc định (nội dung Phụ lục A) nằm ở theme:
 * isha_home_defaults() trong inc/home/data.php; mọi khoá ở đó phải có mặt ở đây và ngược lại.
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
	'hero'         => array(
		'label'  => __( 'Hero và intro', 'isha-core' ),
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
	'story'        => array(
		'label'  => __( 'Câu chuyện', 'isha-core' ),
		'fields' => array(
			'title'     => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
			'year'      => array(
				'type'  => 'text',
				'label' => __( 'Năm thành lập', 'isha-core' ),
				'max'   => 8,
			),
			'main'      => array(
				'type'  => 'textarea',
				'label' => __( 'Đoạn chính', 'isha-core' ),
				'max'   => 600,
				'rows'  => 5,
			),
			'secondary' => array(
				'type'  => 'textarea',
				'label' => __( 'Đoạn phụ', 'isha-core' ),
				'max'   => 900,
				'rows'  => 6,
			),
		),
	),
	'philosophy'   => array(
		'label'  => __( 'Triết lý', 'isha-core' ),
		'fields' => array(
			'title' => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
			'lead'  => array(
				'type'  => 'textarea',
				'label' => __( 'Câu dẫn', 'isha-core' ),
				'max'   => 200,
				'rows'  => 2,
			),
		),
		'groups' => array(
			'items' => array(
				/* translators: %d: số thứ tự chữ cái */
				'label'  => __( 'Chữ cái %d', 'isha-core' ),
				'count'  => 4,
				'fields' => array(
					'letter' => array(
						'type'  => 'text',
						'label' => __( 'Chữ cái', 'isha-core' ),
						'max'   => 1,
					),
					'en'     => array(
						'type'  => 'text',
						'label' => __( 'Tên tiếng Anh', 'isha-core' ),
						'max'   => 40,
					),
					'vi'     => array(
						'type'  => 'text',
						'label' => __( 'Tên tiếng Việt', 'isha-core' ),
						'max'   => 60,
					),
					'desc'   => array(
						'type'  => 'textarea',
						'label' => __( 'Mô tả', 'isha-core' ),
						'max'   => 240,
						'rows'  => 3,
					),
				),
			),
		),
	),
	'services'     => array(
		'label'  => __( 'Dịch vụ', 'isha-core' ),
		'note'   => __( 'Nhóm dịch vụ, lời dẫn từng nhóm, ảnh bìa, ảnh trước/sau điều trị và thứ tự nhập tại Dịch vụ › Nhóm dịch vụ. Liệu trình nhập tại Dịch vụ. Ghi chú dưới ảnh trước/sau (miễn trừ kết quả) dùng chung cho mọi nhóm.', 'isha-core' ),
		'fields' => array(
			'title' => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
			'lead'  => array(
				'type'  => 'textarea',
				'label' => __( 'Câu dẫn', 'isha-core' ),
				'max'   => 200,
				'rows'  => 2,
			),
			'ba_note' => array(
				'type'  => 'text',
				'label' => __( 'Ghi chú dưới ảnh trước/sau', 'isha-core' ),
				'max'   => 160,
			),
		),
	),
	'values'       => array(
		'label'  => __( 'Giá trị', 'isha-core' ),
		'fields' => array(
			'title' => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
		),
		'groups' => array(
			'items' => array(
				/* translators: %d: số thứ tự giá trị */
				'label'  => __( 'Giá trị %d', 'isha-core' ),
				'count'  => 5,
				'fields' => array(
					'name'  => array(
						'type'  => 'text',
						'label' => __( 'Tên giá trị', 'isha-core' ),
						'max'   => 80,
					),
					'desc'  => array(
						'type'  => 'textarea',
						'label' => __( 'Mô tả', 'isha-core' ),
						'max'   => 300,
						'rows'  => 3,
					),
					'image' => array(
						'type'  => 'image',
						'label' => __( 'Ảnh (tuỳ chọn)', 'isha-core' ),
						'help'  => __( 'Dọc 3:4, đen trắng hoặc tông da, ít chi tiết và hơi tối (hiện mờ phía sau chữ).', 'isha-core' ),
					),
				),
			),
		),
	),
	'process'      => array(
		'label'  => __( 'Quy trình', 'isha-core' ),
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
	'space'        => array(
		'label'  => __( 'Không gian', 'isha-core' ),
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
				'label' => __( 'Ảnh không gian', 'isha-core' ),
				'max'   => 12,
				'help'  => __( '6–10 ảnh, cạnh dài từ 2000 px, cả dọc và ngang. Chú thích hiện dưới ảnh lấy từ trường Chú thích trong Thư viện phương tiện.', 'isha-core' ),
			),
		),
	),
	'events'       => array(
		'label'  => __( 'Sự kiện', 'isha-core' ),
		'note'   => __( 'Sự kiện nhập tại Sự kiện; chỉ sự kiện được đánh dấu Nổi bật mới hiện ở đây.', 'isha-core' ),
		'fields' => array(
			'title' => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
			'lead'  => array(
				'type'  => 'textarea',
				'label' => __( 'Câu dẫn', 'isha-core' ),
				'max'   => 200,
				'rows'  => 2,
			),
		),
	),
	'articles'     => array(
		'label'  => __( 'Bài viết', 'isha-core' ),
		'note'   => __( 'Bài viết nhập tại Kiến thức làn da. Bài được đánh dấu Nổi bật đứng đầu.', 'isha-core' ),
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
	'testimonials' => array(
		'label'  => __( 'Testimonials', 'isha-core' ),
		'note'   => __( 'Lời kể nhập tại Lời kể khách hàng; mọi lời kể đã xuất bản và có nội dung đều hiện (mới nhất trước).', 'isha-core' ),
		'fields' => array(
			'title' => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề', 'isha-core' ),
				'max'   => 80,
			),
		),
	),
	'vision'       => array(
		'label'  => __( 'Tầm nhìn', 'isha-core' ),
		'fields' => array(
			'title'     => array(
				'type'  => 'text',
				'label' => __( 'Tiêu đề (ẩn, dành cho trình đọc màn hình)', 'isha-core' ),
				'max'   => 40,
			),
			'quote'     => array(
				'type'  => 'textarea',
				'label' => __( 'Câu trích (không cần gõ dấu ngoặc kép)', 'isha-core' ),
				'max'   => 400,
				'rows'  => 4,
			),
			'body'      => array(
				'type'  => 'textarea',
				'label' => __( 'Đoạn tầm nhìn', 'isha-core' ),
				'max'   => 500,
				'rows'  => 4,
			),
			'role'      => array(
				'type'  => 'text',
				'label' => __( 'Chức danh', 'isha-core' ),
				'max'   => 60,
			),
			'name'      => array(
				'type'  => 'text',
				'label' => __( 'Tên', 'isha-core' ),
				'max'   => 60,
			),
			'signature' => array(
				'type'  => 'image',
				'label' => __( 'Ảnh chữ ký', 'isha-core' ),
				'help'  => __( 'PNG hoặc WebP nền trong, nét mảnh, rộng từ 1200 px. Hệ thống không nhận SVG.', 'isha-core' ),
			),
		),
	),
	'cta'          => array(
		'label'  => __( 'Cảnh kết', 'isha-core' ),
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
	'partners'     => array(
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
);
