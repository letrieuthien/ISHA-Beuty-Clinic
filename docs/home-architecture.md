# Kiến trúc trang chủ điện ảnh ISHA: hợp đồng giữa các file

Đặc tả gốc: [prompt-trang-chu.md](prompt-trang-chu.md). Tài liệu này ghi các quyết định đã chốt, quy ước và API thực tế để các phần code khớp nhau. Khi đổi API, cập nhật tại đây.

**Thứ tự ưu tiên khi các mục mâu thuẫn:** mục có số lớn hơn thắng mục nhỏ hơn (mục 11 thắng mục 8, mục 8 thắng các mục 2 đến 7 và prompt gốc), trừ khi mục đó ghi khác. Riêng video nền và ngân sách JS lấy theo mục 11.1: video hero và cảnh kết **lặp vô hạn, không nút tạm dừng/phát**; ngân sách JS là 110KB cho `isha-motion.js` + `home.js` (các mức 60KB và 90KB ở mục 8 và 10 là lịch sử, đã thay thế). Chỗ nào còn nhắc nút tạm dừng video (`.isha-hero__toggle`, `.isha-home-cta__toggle`) là nội dung cũ đã gỡ ở mục 11.1.

**Công cụ và bài kiểm** (`assemble.js`, `lint74`, `wpstyle`, `boot.php`, `tests/…`, `tools/browser/…`) KHÔNG nằm trong thư mục dự án mà ở bộ QA tách riêng (xem `docs/home-handover.md`, mục "Bộ công cụ kiểm thử"); các đường dẫn `scratchpad/…` trong tài liệu này chỉ đúng trên máy dựng.

Đường dẫn gốc dùng trong tài liệu:
- Theme: `C:/xampp/htdocs/ISHA_Clinic/wp-content/themes/ISHA` (viết tắt `THEME`)
- Plugin: `C:/xampp/htdocs/ISHA_Clinic/wp-content/plugins/isha-core` (viết tắt `PLUGIN`)

## 1. Quyết định đã chốt (người dùng xác nhận 2026-11-09)

1. Trang chủ là Page "Trang chủ" (ID 10, rỗng, không Elementor). DB thật hiện đã đặt `page_on_front = 10` và tắt chế độ landing. Trang Chính sách (ID 7, Elementor) là trang riêng.
2. Trường MỚI làm native (meta box + picker tự viết, khoá meta `_isha_*`). ACF free chỉ giữ cho dịch vụ và chính sách (không sửa phần ACF hiện có).
3. 6 bài `isha_service` cũ (tên trùng 6 nhóm leaflet) chuyển THÙNG RÁC khi seed. `isha_service` được thêm hỗ trợ `thumbnail` và `editor`.
4. Đối tác chỉ có MỘT gallery ảnh (không trường link, không tên riêng). Tên lấy từ alt.
5. Header trang chủ: `get_header('home')` + `header-home.php`; `header.php` và `footer.php` giữ nguyên.
6. Class cảnh kết là `isha-home-cta` (không dùng `isha-cta`, trùng CSS plugin). Nút Zalo ẩn khi chưa có URL Zalo. Section Bài viết ẩn khi chưa có `isha_article` (KHÔNG rơi về `post`).
7. Customizer cũ (hero_*, cta_*) chỉ là fallback khi người dùng thực sự đã đặt giá trị (theme_mod có tồn tại); nếu không thì dùng nội dung Phụ lục A.

## 2. Quy ước chung (BẮT BUỘC)

- File: **LF**, UTF-8 **không BOM**, thụt lề bằng **tab**, theo WordPress Coding Standards, docblock tiếng Việt ngắn. (Toàn bộ repo hiện là LF; đừng chuyển sang CRLF.)
- PHP mở đầu bằng `if ( ! defined( 'ABSPATH' ) ) { exit; }` sau docblock có `@package ISHA` (theme) hoặc `@package ISHA_Core` (plugin).
- **PHP 7.4 trở lên**. Cấm: `match`, `?->`, named arguments, union types, `mixed`, constructor promotion, enum, `readonly`, first-class callable `strlen(...)`, `static` làm kiểu trả về, `array_is_list`, `array_find*`, `str_contains/str_starts_with/str_ends_with` (dùng `false !== strpos(...)`), spread mảng có khoá chuỗi, `never`, thuộc tính `#[...]`. Được dùng: arrow function `fn`, typed property, `??`, `??=`, `array_key_first/last`.
- Tên: theme dùng tiền tố hàm `isha_`, text domain `isha`; plugin dùng class `ISHA_Core_*`, text domain `isha-core`. Mọi chuỗi giao diện bọc `__()`, `esc_html__()`, `esc_attr__()`.
- Bảo mật: escape mọi đầu ra (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`); sanitize đầu vào; nonce + `current_user_can()` khi lưu; bỏ qua autosave và revision; `update_post_meta( $id, $key, wp_slash( $value ) )` (core tự `wp_unslash` giá trị meta, nên phải slash trước khi lưu mảng/chuỗi có dấu gạch ngược).
- Không thêm thư viện JS/CSS, không bước build. JS admin viết thuần (vanilla); jQuery chỉ khi thật cần và chỉ ở admin.
- KHÔNG sửa: `THEME/assets/js/policy-single.js`, `THEME/assets/css/policy-single.css`, `THEME/template-parts/policy-screens.php`, `THEME/single-isha_policy.php`, `THEME/inc/policy-single.php`, `THEME/header.php`, `THEME/footer.php`, các shortcode/CSS plugin hiện có, phần ACF của `class-service-fields.php` và `class-policy-fields.php`.
- Mỗi file mới/sửa phải qua `php -l`; kiểm cú pháp 7.4 bằng công cụ trong `scratchpad/tools/` (xem mục 6).

## 3. Plugin `isha-core` (Giai đoạn 1)

Nạp bằng `require_once` trong `PLUGIN/isha-core.php` và `::init()` trong `isha_core_init()` (do bước tích hợp làm). Hằng `ISHA_CORE_VERSION` nâng lên `1.1.0`.

| File | Class | Việc |
| --- | --- | --- |
| `includes/class-admin-media.php` + `assets/js/admin-media.js` + `assets/css/admin-media.css` | `ISHA_Core_Admin_Media` | Picker media dùng chung (ảnh đơn, video, gallery) |
| `includes/home-schema.php` | (trả mảng) | Lược đồ trường trang chủ, đã viết sẵn |
| `includes/class-home-fields.php` + `assets/js/admin-home.js` + `assets/css/admin-home.css` | `ISHA_Core_Home_Fields` | Meta box "Nội dung trang chủ" |
| `includes/class-post-types.php` (sửa) | `ISHA_Core_Post_Types` | 3 CPT + 2 taxonomy mới, thêm supports cho `isha_service` |
| `includes/class-event-fields.php` | `ISHA_Core_Event_Fields` | Trường sự kiện |
| `includes/class-testimonial-fields.php` | `ISHA_Core_Testimonial_Fields` | Trường lời kể |
| `includes/class-article-fields.php` | `ISHA_Core_Article_Fields` | Trường bài viết |
| `includes/class-term-fields.php` | `ISHA_Core_Term_Fields` | Term meta nhóm dịch vụ |
| `includes/class-seed.php` | `ISHA_Core_Seed` (+ lệnh WP-CLI) | Dữ liệu dịch vụ khởi tạo |

### 3.1 `ISHA_Core_Admin_Media`

```php
class ISHA_Core_Admin_Media {
	const MAX_GALLERY = 24;
	public static function enqueue();                          // wp_enqueue_media() + handle 'isha-admin-media' (JS + CSS); idempotent
	public static function render( array $args );              // echo HTML đã escape
	public static function sanitize( $type, $raw );            // image|video => int ; gallery => int[]
	public static function is_valid( $type, $id );             // bool
}
```
- `render()` nhận: `name` (bắt buộc, tên input đầy đủ), `type` (`image`|`video`|`gallery`, mặc định `image`), `value` (int | int[] | chuỗi CSV), `label` (dùng cho aria-label và tiêu đề khung chọn), `help` (tuỳ chọn), `id` (tuỳ chọn, gốc id DOM).
- Một input `hidden` duy nhất mang giá trị: image/video là ID (hoặc rỗng), gallery là chuỗi ID cách nhau dấu phẩy theo thứ tự.
- Markup: `div.isha-media[data-type][data-multiple][data-title][data-button]` chứa input hidden, `div.isha-media__preview`, nút `button.button.isha-media__choose`, nút `button.button-link.isha-media__clear`. Gallery: thẻ ảnh có nút "Trước", "Sau", "Xoá" (bàn phím được, KHÔNG phụ thuộc kéo thả).
- JS phát sự kiện `isha-media:change` (bubbles) trên wrapper khi giá trị đổi. Dùng ủy quyền sự kiện trên `document` (nhiều picker trên một trang). Với màn "Thêm term" (`#addtag`), xoá giá trị picker sau khi AJAX `add-tag` thành công.
- `sanitize()`: image => `absint` và phải là tệp đính kèm ảnh (`wp_attachment_is_image`); video => phải là attachment có mime bắt đầu bằng `video/`; gallery => CSV/mảng, mỗi phần tử phải là ảnh hợp lệ, bỏ trùng, giữ thứ tự, tối đa `MAX_GALLERY`. Giá trị sai trả `0` (hoặc `array()`).
- **Thực tế đã triển khai (đọc docblock đầu `class-admin-media.php` trước khi dùng):** class KHÔNG có `init()` (không gọi khi nối vào `isha-core.php`, chỉ cần `require_once`). `render()` KHÔNG gọi `enqueue()`: mỗi màn hình phải gọi `ISHA_Core_Admin_Media::enqueue()` ở `admin_enqueue_scripts`. Thiếu `name` thì không in gì (và báo `_doing_it_wrong`). `sanitize()` chỉ nhận số nguyên dương hoặc chuỗi số (id, CSV hoặc mảng); với gallery chỉ cắt ở `MAX_GALLERY` (24), nên người gọi tự `array_slice` theo giới hạn riêng của trường (`max` trong schema) khi lưu. `render()` nhận thêm `max` (gallery) và `id` (gốc id DOM; input/preview/nút chọn/chú thích có hậu tố `-input`, `-preview`, `-choose`, `-help`; bỏ trống thì tự sinh). Ô là `role="group"` có `aria-label` lấy từ `label`; đừng dùng `<label for>` trỏ vào nút chọn. Script handle `isha-admin-media` phụ thuộc `media-editor`. Không thể thử khung `wp.media` thật khi chưa đăng nhập; đã thử với bản giả lập API.
- Công cụ lint đã có: `scratchpad/tools/README.md` (`lint74`, `wpstyle`); trình duyệt/fixture: `scratchpad/tools/browser/README.md`.

### 3.2 `ISHA_Core_Home_Fields`

```php
class ISHA_Core_Home_Fields {
	const META_PREFIX = '_isha_home_';
	public static function init();
	public static function schema();                       // mảng từ home-schema.php, qua filter 'isha_core/home_schema'
	public static function sections();                     // array( id => label ) theo thứ tự
	public static function page_id();                      // trang chứa dữ liệu trang chủ (xem dưới); filter 'isha_core/home_page_id'
	public static function meta_key( $section );           // META_PREFIX . $section
	public static function get( $section, $page_id = 0 ); // giá trị ĐÃ LƯU (không trộn mặc định)
	public static function sanitize_section( $section, $raw ); // hàm thuần, dùng khi lưu, test được
}
```
- `page_id()`: (1) `apply_filters( 'isha_core/home_page_id', 0 )` nếu trả ID của một page; (2) `page_on_front` nếu `show_on_front === 'page'` và trang đó KHÔNG phải trang dựng bằng Elementor (`_elementor_edit_mode !== 'builder'`); (3) trang có slug `trang-chu`; (4) `0`.
- Meta lưu ở `_isha_home_<section>` (một mảng/section). Mảng gồm: `enabled` (`'1'`|`'0'`; không có khoá nghĩa là bật), mỗi khoá trường trong `fields`, mỗi nhóm trong `groups` là danh sách `count` mảng con.
- `get()` trả mảng đã lưu và đã sanitize lại khi đọc: `enabled` là `bool` nếu đã lưu (không có khoá nếu chưa lưu), trường text/textarea là chuỗi, bool là `bool`, image/video là `int`, gallery là `int[]`, nhóm là danh sách đủ `count` phần tử. Chỉ có khoá đã lưu; rỗng nghĩa là "dùng mặc định". Không có dữ liệu: `array()`.
- Giao diện: meta box `id=isha_home_fields`, tiêu đề "Nội dung trang chủ", context `normal`, priority `high`, chỉ trên trang `page_id()`. Thẻ (tab) đúng thứ tự schema, accessible (`role=tablist/tab/tabpanel`, mũi tên trái/phải chuyển thẻ). Dòng đầu mỗi thẻ là ô "Hiển thị section này" (hidden `0` + checkbox `1`, mặc định tick). Dòng hướng dẫn `note` nếu có. Ô để trống dùng giá trị mặc định: placeholder lấy từ `apply_filters( 'isha_core/home_defaults', array() )` (theme trả `array( section => defaults )`).
- Ẩn trình soạn thảo của trang chủ (cả block editor): `use_block_editor_for_post` trả false cho trang này, `remove_post_type_support( 'page', 'editor' )` tại `add_meta_boxes_page` khi đúng trang, in dòng "Trang chủ do theme dựng; chỉnh nội dung ở các thẻ bên dưới." (`edit_form_after_title`).
- Lưu: `save_post_page`, nonce `isha_home_nonce` / action `isha_save_home`, bỏ qua autosave/revision, `current_user_can( 'edit_post', $id )`, chỉ khi `$id === page_id()`. Input dạng `isha_home[<section>][...]`. Cắt chuỗi theo `max` bằng `mb_substr`.

### 3.3 Post types và taxonomy (`ISHA_Core_Post_Types::register()`)

- `isha_service`: thêm `editor` và `thumbnail` vào `supports` (giữ `title`, `page-attributes`). Cập nhật comment cho đúng. Không đổi slug, archive, ACF.
- `isha_event` (menu "Sự kiện", vị trí 25, icon `dashicons-calendar-alt`): public, `has_archive` false, rewrite `su-kien` (`with_front` false), `show_in_rest` true, supports `title`, `editor`, `excerpt`, `thumbnail`. Taxonomy `isha_event_type` ("Loại sự kiện", không phân cấp, rewrite `loai-su-kien`, `show_in_rest` true, `show_admin_column` true).
- `isha_testimonial` (menu "Lời kể khách hàng", vị trí 26, icon `dashicons-format-quote`): `public` false, `show_ui` true, `publicly_queryable` false, `exclude_from_search` true, `show_in_rest` false, supports `title`, `thumbnail`. Không taxonomy.
- `isha_article` (menu "Kiến thức làn da", vị trí 27, icon `dashicons-media-document`; tên đặt khác "Bài viết" để không nhầm với Bài viết của WordPress): public, `has_archive` true, rewrite `bai-viet`, `show_in_rest` true, supports `title`, `editor`, `excerpt`, `thumbnail`. Taxonomy `isha_article_cat` ("Chủ đề", phân cấp, rewrite `chu-de`, `show_in_rest` true, `show_admin_column` true).
- Đầy đủ `labels` tiếng Việt cho mỗi CPT/taxonomy.
- Rewrite chỉ flush khi kích hoạt; plugin đang chạy nên cần flush có kiểm phiên bản (do bước tích hợp làm trong `isha-core.php`: option `isha_core_rewrite_ver`, `flush_rewrite_rules( false )` ở `init` ưu tiên 99).

### 3.4 Khoá meta và `get()` của các CPT

Mọi khoá bắt đầu bằng gạch dưới (ẩn khỏi "Custom Fields"). Mọi `::get( $post_id )` chỉ đọc (`get_post_meta`), không phụ thuộc ACF, sanitize lại khi đọc.

**Sự kiện** (`ISHA_Core_Event_Fields`):
- Khoá: `_isha_event_start`, `_isha_event_end` (chuỗi `Y-m-d H:i` theo múi giờ của site), `_isha_event_start_ts`, `_isha_event_end_ts` (số nguyên timestamp UTC, dùng để sắp xếp/so sánh), `_isha_event_location`, `_isha_event_register_url`, `_isha_event_featured` (`'1'`), `_isha_event_portrait_id`, `_isha_event_summary` (nội dung ngắn một dòng, tối đa 200 ký tự; thêm 2026-11-11, xem `docs/editorial-list.md`).
- Nhập ngày giờ bằng `<input type="datetime-local">`; chuyển sang timestamp bằng `DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $v, wp_timezone() )`. Kết thúc phải sau bắt đầu, nếu không thì xoá kết thúc.
- `get()` trả: `start` (string), `start_ts` (int), `end`, `end_ts`, `location`, `register_url`, `featured` (bool), `portrait_id` (int, ảnh dọc 4:5 cho điện thoại, chọn bằng `ISHA_Core_Admin_Media`), `summary` (string, đặt cuối mảng; '' nếu chưa nhập).
- Nonce action `isha_save_event`, field `isha_event_nonce`. Cột admin: ngày bắt đầu, nổi bật.

**Lời kể** (`ISHA_Core_Testimonial_Fields`):
- Khoá: `_isha_tm_quote` (tối đa 300 ký tự), `_isha_tm_service_id` (ID `isha_service`). Đã gỡ khoá `_isha_tm_consent` và mọi điều kiện "đồng ý công khai" (yêu cầu của người dùng, 10/11/2026: các lời kể là nội dung của doanh nghiệp, không phải của khách thật); khoá cũ còn trong DB thì bị bỏ qua.
- Tên hiển thị = tiêu đề bài. Ảnh chân dung = ảnh đại diện (không dùng ở trang chủ hiện tại; không dùng ảnh trước và sau).
- `get()` trả: `name`, `quote`, `service_id`, `service_title` (4 khoá; không còn `consent`).
- Ô chọn liệu trình: `<select>` gồm các `isha_service` (publish, draft, pending, private) kèm tên nhóm, có lựa chọn "Không chọn". Bộ đếm ký tự cho lời kể. Nonce `isha_save_testimonial` / `isha_testimonial_nonce`. Không có ô đồng ý công khai và không có cột admin tương ứng.

**Bài viết** (`ISHA_Core_Article_Fields`):
- Khoá: `_isha_article_subtitle`, `_isha_article_read_min` (số phút, TỰ TÍNH khi lưu: đếm từ của nội dung đã bỏ thẻ chia 200 từ/phút, làm tròn lên, tối thiểu 1; đếm từ bằng `preg_split( '/\s+/u', ... )`, không dùng `str_word_count`), `_isha_article_featured` (`'1'`), `_isha_article_summary` (nội dung ngắn một dòng, tối đa 200 ký tự; thêm 2026-11-11, xem `docs/editorial-list.md`).
- `get()` trả: `subtitle`, `read_min` (int), `featured` (bool), `summary` (string, đặt cuối mảng; '' nếu chưa nhập).
- Nonce `isha_save_article` / `isha_article_nonce`. Tính lại thời gian đọc ở `save_post_isha_article` (chạy cả khi lưu qua block editor). Cột admin: nổi bật.

### 3.5 Term meta của `isha_service_cat` (`ISHA_Core_Term_Fields`)

- Khoá: `_isha_cover_id` (ảnh bìa dọc 4:5), `_isha_cover_video_id` (video bìa tuỳ chọn), `_isha_order` (số nguyên, nhỏ đứng trước).
- UI ở màn thêm và sửa term (dùng `ISHA_Core_Admin_Media`), lưu ở `created_isha_service_cat` / `edited_isha_service_cat`, nonce `isha_save_term` / `isha_term_nonce`, kiểm tra `current_user_can( 'edit_term', $term_id )`. Cột admin: ảnh bìa nhỏ, thứ tự.
- `get( $term_id )` trả `cover_id` (int), `cover_video_id` (int), `order` (int|null).
- `ordered_terms( array $args = array() )` trả `WP_Term[]` của `isha_service_cat` (mặc định `hide_empty` false), sắp theo `order` tăng dần (chưa đặt đứng cuối) rồi theo tên.
- Mô tả của term chính là tagline.

### 3.6 Seed (`ISHA_Core_Seed`)

```php
public static function init();          // admin_notices + admin_post_isha_seed_services; đăng ký lệnh WP-CLI nếu defined('WP_CLI') && WP_CLI
public static function data();          // dữ liệu seed (6 nhóm, 17 liệu trình, 3 loại sự kiện)
public static function run( array $opts = array() ); // trả mảng tóm tắt; $opts: status (mặc định 'draft'), trash_legacy (true), dry_run (false)
```
- Dữ liệu theo Phụ lục A: 6 nhóm (tên, slug không dấu, tagline = mô tả term, `_isha_order` 10..60) và 17 liệu trình (3+2+3+3+4+2) ở trạng thái `draft`, `menu_order` 10, 20, 30... trong từng nhóm (từ Giai đoạn 7 là số toàn cục theo nhóm 110, 120, 130 | 210, 220 | …, xem mục 14.2), gán vào đúng term. Ba loại sự kiện (Workshop, Design Talk, Charity) trong `isha_event_type`. Không tạo dữ liệu giả khác.
- Idempotent: đánh dấu bằng meta `_isha_seed_key` (term meta và post meta); kiểm tra tồn tại ở MỌI trạng thái kể cả thùng rác; chạy lại không tạo trùng và không ghi đè dữ liệu đã sửa.
- `trash_legacy`: chuyển thùng rác các bài `isha_service` cũ mà tên chuẩn hoá (không phân biệt hoa thường, bỏ dấu cách thừa, "Mĩ" = "Mỹ") trùng một trong 6 tên nhóm, chưa gán term nào, trạng thái publish/draft. Dùng `wp_trash_post()` (không xoá hẳn).
- Kết thúc gọi `flush_rewrite_rules( false )`.
- Thông báo admin: hiện với người có `manage_options` khi `isha_service_cat` chưa có term nào; nút "Tạo dữ liệu dịch vụ từ leaflet" là form POST tới `admin-post.php?action=isha_seed_services` có nonce `isha_seed_services`; xử lý xong chuyển hướng về trang trước kèm thông báo tóm tắt. Mô tả rõ việc sẽ làm (tạo nhóm, tạo liệu trình nháp, chuyển 6 bài cũ vào thùng rác).
- Lệnh WP-CLI: `wp isha seed services [--status=<draft|publish>] [--keep-legacy] [--dry-run]`.

## 4. Theme: lớp dữ liệu (Giai đoạn 1)

File `THEME/inc/home/data.php`, nạp từ `functions.php` (thêm vào mảng `$isha_includes`).

```php
function isha_home_page_id();                       // int; dùng ISHA_Core_Home_Fields::page_id() nếu có, không thì cùng logic cục bộ
function isha_home_defaults( $section = '' );       // nội dung Phụ lục A; $section = '' trả mảng tất cả section
function isha_home_data( $section, $args = array() ); // dữ liệu đã trộn + dựng sẵn; array() nghĩa là ẨN section
function isha_home_contact();                       // array( booking_url, phone, tel, tel_url, zalo_url )
```
- Filter `isha_core/home_defaults` (do theme đăng ký, trả `isha_home_defaults()` đầy đủ) để meta box lấy placeholder.
- `isha_home_data()`: lấy giá trị đã lưu (`ISHA_Core_Home_Fields::get` nếu class tồn tại, không thì `get_post_meta( isha_home_page_id(), '_isha_home_' . $section, true )`), trộn từng trường với mặc định (trường rỗng, `0`, mảng rỗng, chuỗi chỉ khoảng trắng => mặc định; riêng bool dùng giá trị đã lưu nếu có), với hero/cta thì Customizer đã đặt thật (`get_theme_mod( 'isha_hero_title', null )` khác null và khác rỗng) đứng giữa meta và mặc định, rồi chạy `apply_filters( "isha/home/data/{$section}", $data, $section )`.
- Section bị tắt (`enabled` false) hoặc không có dữ liệu bắt buộc: trả `array()`.
- Mọi giá trị trả về là **thô (chưa escape)**; template escape lúc in. Media là ID đính kèm (`int`, 0 = không có).
- ISHA Core tắt (`! isha_core_active()` hoặc thiếu CPT): section dùng CPT trả `array()`; section chỉ dùng trường vẫn trả nội dung mặc định.
- Gọi truy vấn: `no_found_rows` true, `update_post_term_cache` false khi không cần, chỉ lấy trường cần.
- Cảnh báo thứ tự nạp: `isha_home_is_preview()` có thể chưa tồn tại ở Giai đoạn 1, luôn bọc `function_exists`.

### Hình dạng dữ liệu từng section (đã trộn mặc định)

`$data` luôn có `'section' => <id>`. `count` lấy từ `$args['count']` (events 3, articles 4, testimonials 6).

- `hero`: `intro_enabled` bool, `intro_text`, `video_landscape` int, `video_portrait` int, `poster_landscape` int, `poster_portrait` int, `eyebrow`, `title`, `description`, `button_label`.
- `story`: `title`, `year`, `main`, `secondary`.
- `philosophy`: `title`, `lead`, `items` (4 phần tử: `letter`, `en`, `vi`, `desc`).
- `services`: `title`, `lead`, `groups` (danh sách: `id`, `slug`, `name`, `tagline`, `cover_id`, `cover_video_id`, `url` (link term), `items` (danh sách: `id`, `title`, `url`, `draft` bool)). Nhóm sắp theo `ISHA_Core_Term_Fields::ordered_terms()`; liệu trình sắp `menu_order` rồi tên; chỉ bài đã xuất bản, cộng thêm bài nháp (`draft` true) khi đang xem trước (`function_exists('isha_home_is_preview') && isha_home_is_preview()`) và người xem có `edit_posts`. Không có term nào: `array()`. Nhóm chưa có liệu trình: giữ nhóm với `items` rỗng.
- `values`: `title`, `items` (5 phần tử: `name`, `desc`, `image` int).
- `process`: `title`, `items` (5 phần tử: `name`, `desc`).
- `space`: `title`, `lead`, `images` (danh sách: `id`, `caption`, `alt`). Không ảnh: `array()`.
- `events` (**đã thay đổi ở mục 22**, cách chọn sự kiện, khoá `tabs`, `initial`, `day`, `month_label`, `year`, `link_label`, `archive_url`, `types`): `title`, `lead`, `events` (danh sách: `id`, `title`, `excerpt`, `type` (tên loại), `start_ts`, `end_ts`, `date_label`, `time_label`, `end_time_label`, `location`, `register_url`, `url`, `image_id`, `portrait_id`, `past` bool). Chỉ sự kiện nổi bật; sự kiện chưa diễn ra (theo `end_ts` nếu có, không thì `start_ts`) đứng trước theo ngày bắt đầu tăng dần, sau đó là sự kiện đã qua gần nhất; tối đa `count`. Không có: `array()`.
- `articles` (**đã thay đổi ở mục 23**: thêm `eyebrow`, `featured.banner_id`, `list[].image_id`): `eyebrow`, `title`, `link_label`, `archive_url`, `featured` (`id`, `title`, `excerpt`, `image_id`, `banner_id`, `url`, `category`, `date`), `list` (các bài còn lại, tối đa `count - 1`: `id`, `title`, `url`, `category`, `date`, `image_id`). Bài nổi bật đứng đầu rồi mới nhất. Không có `isha_article`: `array()`.
- `testimonials`: `title`, `items` (danh sách: `id`, `name`, `quote`, `service`). Mọi lời kể đã xuất bản có `quote` (không còn điều kiện `consent`). `service` là rỗng khi liệu trình đã dùng chưa xuất bản (nháp, chờ duyệt, riêng tư), trừ khi người sửa bài đang xem trước (cùng quy tắc với section Dịch vụ; ISHA Core vẫn cho chọn liệu trình chưa xuất bản trong trang quản trị). Không có: `array()`.
- `vision`: `title`, `quote`, `body`, `role`, `name`, `signature` int. (Mặc định `quote` KHÔNG kèm dấu ngoặc kép; template tự thêm “ ”.)
- `cta`: `title`, `description`, `background_image` int, `background_video` int, và các khoá của `isha_home_contact()`.
- `partners`: `title`, `images` (danh sách: `id`, `alt`). Không ảnh: `array()`.

`isha_home_contact()`: `booking_url` = `isha_booking_url()`; `phone` = `isha_opt('phone')` (hiển thị); `tel` = `preg_replace( '/[^0-9+]/', '', phone )`; `tel_url` = `'tel:' . tel` hoặc `''`; `zalo_url` = `isha_opt('zalo')` qua `esc_url_raw` (http đổi thành https) hoặc `''`. Zalo là URL, không phải số điện thoại.

## 5. Theme: khung trang chủ (Giai đoạn 2)

Mục tiêu GĐ2: trang chủ ĐỌC ĐƯỢC TRỌN VẸN khi chưa có chuyển động theo cảnh: 13 section tĩnh đúng nội dung và bố cục, header trang chủ, dock, lớp bầu trời, hàng vuốt ngang, lõi chuyển động `isha-motion.js` đã dựng và kiểm thử (các cảnh điện ảnh cụ thể làm ở GĐ 3-5). Không có hiệu ứng ghim/quét ở GĐ2; chỉ chuyển trạng thái nhẹ (header, dock, sky, hàng vuốt).

### 5.1 Tệp và người phụ trách

| Tệp (theme) | Việc |
| --- | --- |
| `assets/css/main.css` | CHỈ thêm một khối token mới ngay sau khối `:root` đầu tiên (prompt, mục Token). Không sửa gì khác |
| `assets/js/isha-motion.js` | Lõi chuyển động (mục 5.8) |
| `inc/helpers.php` (sửa) | `isha_home_is_preview()`, `isha_home_is_active()`; sửa `isha_landing_only()` và `isha_is_inner_page()` |
| `inc/setup.php` (sửa) | 3 image size mới; body class `isha-home` |
| `inc/home/registry.php` | `isha_home_sections()` |
| `inc/home/shortcodes.php` | 13 shortcode |
| `inc/home/assets.php` | Nạp CSS/JS, script gate, preload, noindex/nocache khi xem trước |
| `inc/home/helpers.php` | Hàm in markup dùng chung, `isha_home_render()` |
| `functions.php` (sửa) | Thêm include `inc/home/registry.php`, `shortcodes.php`, `assets.php`, `helpers.php` (sau `inc/home/data.php`) |
| `header-home.php` | Header biến thể (mở `<main>`) |
| `front-page.php` (sửa) | Định tuyến |
| `template-parts/home/{intro,dock}.php`, `template-parts/home/<id>.php` x13 | Markup |
| `assets/css/home.css` | Toàn bộ CSS, chia khối bằng marker (mục 5.7) |
| `assets/js/home.js` | Header, dock, sky, hàng vuốt (mục 5.9); các cảnh sau này thêm module theo marker |

### 5.2 Kích hoạt, xem trước, định tuyến (`inc/helpers.php`, `front-page.php`)

```php
function isha_home_is_preview();  // did_action('wp') && is_front_page() && is_user_logged_in() && current_user_can('edit_pages') && 'home' === sanitize_key( wp_unslash( $_GET['isha_preview'] ) ). Không gọi sớm hơn hook wp (trả false, không _doing_it_wrong).
function isha_home_is_active();   // is_front_page() && ( ! isha_landing_only() || isha_home_is_preview() ); false nếu chưa qua hook wp
function isha_landing_only();     // SỬA: nếu isha_home_is_preview() thì false; còn lại giữ nguyên (theme_mod 'isha_landing_only', mặc định true)
function isha_is_inner_page();    // SỬA: nếu isha_home_is_active() thì false; còn lại giữ nguyên
```
- `isha_body_classes()` (`inc/setup.php`) giữ `has-hero`, thêm `isha-home` CHỈ khi `isha_home_is_active()`.
- `front-page.php`: nhánh landing giữ NGUYÊN hành vi; nhánh còn lại thay bằng phim:

```php
$isha_home = isha_home_is_active();
get_header( $isha_home ? 'home' : null );   // header-home.php khi phim; header.php khi landing
if ( $isha_home ) { isha_home_render(); } else { get_template_part( 'template-parts/landing-policy' ); }
get_footer();
```
(Nhánh hero/giới thiệu/dịch vụ/CTA/3 bài `post` cũ bị bỏ. Customizer cũ vẫn nguyên, chỉ còn là fallback chữ cho hero/cta.) `header.php` và `footer.php` KHÔNG sửa.
- Xem trước: thêm `nocache_headers()`, hằng `DONOTCACHEPAGE` và `noindex` (filter `wp_robots`) khi `isha_home_is_preview()`.

### 5.3 Registry và render (`inc/home/registry.php`, `inc/home/helpers.php`)

```php
function isha_home_sections();   // array( id => array( 'tag' => 'isha_home_<id>', 'template' => 'template-parts/home/<id>', 'tone' => <tông>, 'class' => 'isha-<id>' (cta: 'isha-home-cta'), 'scene' => <id> ) ) theo thứ tự
function isha_home_render();     // echo: <div class="isha-film" data-isha-film> <div class="isha-sky" data-isha-sky aria-hidden="true"></div> + HTML mỗi section (do_shortcode) </div> rồi template-parts/home/dock. Section trả '' thì bỏ qua.
```
Thứ tự và tông: hero `night`; story `day`; philosophy `day`; services `day-warm`; values `day-warm`; process `afternoon`; space `golden`; events `golden`; articles `late-afternoon` (`--isha-sunset` #bba07e, tối hơn golden để nền tối dần đều); testimonials `evening` (`--isha-evening` #a48b6c, chữ tối); vision `twilight` (`--isha-twilight` #64533e, chữ sáng); cta `night`; partners `night`.

Helper in markup (`inc/home/helpers.php`; tất cả hàm `isha_home_*`; template CHỈ dùng các hàm này cho phần dùng chung):
- `isha_home_in_film( $set = null )`: cờ "đang render trong phim".
- `isha_home_section_open( $section, $extra_class = '' )` và `isha_home_section_close()`: in thẻ `<section id="<id>" class="isha-sec <class> <extra>" data-isha-scene="<id>" data-tone="<tông>" aria-labelledby="isha-<id>-title">` (id/class/tone lấy từ registry; riêng vision dùng tiêu đề ẩn).
- `isha_home_title_id( $section )` => `isha-<id>-title`.
- `isha_home_image( $id, $args )`: `size` (mặc định `large`), `sizes`, `class`, `loading` (`lazy` mặc định), `fetchpriority`, `alt` (ghi đè), `decorative` (alt rỗng). Trả chuỗi `<img>` (qua `wp_get_attachment_image`, thêm `decoding="async"`) hoặc `''` nếu ID không hợp lệ. Phải chịu được attachment thiếu metadata.
- `isha_home_placeholder( $label, $args )`: khối trung tính theo tông `<div class="isha-ph" aria-hidden="true" data-ph-label>` (có thể có `ratio` class `isha-r-45` ...). Chữ nhãn chỉ in cho người có `edit_pages` (xem trước/đăng nhập); khách thấy khối màu trơn.
- `isha_home_media( $id, $label, $args )`: `isha_home_image()` nếu có, không thì `isha_home_placeholder()`.
- `isha_home_video_url( $id )`, `isha_home_video_mime( $id )`.
- `isha_home_wordmark( $args )`: nội tuyến `assets/images/isha-wordmark.svg` nếu file tồn tại (chỉ đọc file trong theme; gắn `aria-hidden="true"`), nếu không thì SVG dự phòng dựng bằng 4 phần tử `<text>` (id `isha-i`, `isha-s`, `isha-h`, `isha-a`, font Garamond qua CSS). KHÔNG tạo file SVG giả logo.
- `isha_home_p( $text, $class = '' )`: một hoặc nhiều thẻ `<p>` đã escape (xuống dòng đôi = đoạn mới).
- `isha_home_button( $label, $url, $variant = 'primary', $attrs = array() )`: in `a.isha-btn.isha-btn--<variant>` (variant `primary`|`ghost`).
- `isha_home_row_controls( $args )`: in khối điều khiển hàng vuốt (mục 5.6) với `count`, `label`.

### 5.4 Shortcode (`inc/home/shortcodes.php`)

13 tag `isha_home_<id>`. Mỗi tag: `shortcode_atts` (`count` cho events 3, articles 4, testimonials 6), `isha_home_data( $id, array( 'count' => ... ) )`; rỗng => trả `''`; không rỗng => `ob_start(); get_template_part( 'template-parts/home/<id>', null, $data ); return ob_get_clean()`. Dùng ngoài `isha_home_render()` (ví dụ `[isha_home_cta]` ở trang liệu trình): bọc kết quả trong `<div class="isha-home isha-home--embed">` và nạp asset trang chủ.

### 5.5 Nạp tài nguyên (`inc/home/assets.php`)

- Khi `isha_home_is_active()` (hoặc có embed): style `isha-home` (`assets/css/home.css`, phụ thuộc `isha-main`); script `isha-motion` (`assets/js/isha-motion.js`) rồi `isha-home-js` (`assets/js/home.js`, phụ thuộc `isha-motion`); `defer`, cuối trang, phiên bản `filemtime` có `file_exists`; không nạp `page.css`.
- Cấu hình JS: `wp_add_inline_script( 'isha-home-js', 'window.ISHA=window.ISHA||{};window.ISHA.homeConfig=' . wp_json_encode( $cfg ) . ';', 'before' )` với `$cfg` gồm `preview` (bool), `strings` (đã dịch: `pause` "Tạm dừng video", `play` "Phát video", `prev` "Trước", `next` "Sau", `item` "Khung %d", `rowLabel`...).
- Script gate trong `<head>` (`wp_head` ưu tiên 1, chỉ khi phim): thêm class `isha-motion-on` lên `<html>`; sau 6 giây nếu `window.ISHA.motion.started` chưa true thì thêm `isha-motion-fail`.
- Preload poster hero (`<link rel="preload" as="image" fetchpriority="high" media="(orientation: landscape)|(orientation: portrait)">`) và 2 font phụ (`GaramondPremrPro-ItDisp.otf`, `Montserrat-Medium.ttf`). Chỉ preload khi có dữ liệu.
- Hai image size trong `inc/setup.php`: `isha-frame` 1200x1500 cắt, `isha-cinema` 2400x1004 cắt, `isha-strip` 1600x1100 không cắt.

### 5.6 Khuôn markup chung

- Mọi section dùng `isha_home_section_open/close`, tiêu đề `h2` (hero là `h1`, vision có `h2` ẩn bằng `.isha-sr`), `id` tiêu đề `isha-<id>-title`.
- Class theo BEM `isha-<id>__<phần>`; class dùng chung (do khối CSS `base` định nghĩa): `.isha-sec`, `.isha-wrap` (khung nội dung rộng `--container`), `.isha-sec__head`, `.isha-sec__title` (tiêu đề section: Montserrat 600 chữ hoa), `.isha-accent` (Garamond accent), `.isha-lead`, `.isha-text`, `.isha-note` (Garamond italic), `.isha-label` (Montserrat nhãn), `.isha-btn`, `.isha-btn--primary|ghost`, `.isha-link`, `.isha-sr`, `.isha-ph`, `.isha-img`, tỉ lệ `.isha-r-45` (4:5), `.isha-r-34` (3:4), `.isha-r-239` (2.39:1), `.isha-r-32` (3:2), `.isha-r-11`.
- Hàng vuốt ngang (một thành phần dùng chung):
```html
<div class="isha-row" data-isha-row>
  <ul class="isha-row__track" tabindex="0" aria-label="..."><li class="isha-row__item">...</li>...</ul>
  <!-- isha_home_row_controls() in: -->
  <div class="isha-row__controls" data-isha-row-controls hidden>
    <button type="button" class="isha-row__btn isha-row__btn--prev">Trước</button>
    <ol class="isha-row__dots"><li><button type="button" aria-label="Khung 1" aria-current="true"></button></li>...</ol>
    <button type="button" class="isha-row__btn isha-row__btn--next">Sau</button>
  </div>
</div>
```
`home.js` bỏ `hidden` và nối sự kiện khi hàng tràn; không JS thì hàng vẫn cuộn bằng chạm và bàn phím (`tabindex="0"`). Nút phải có chữ và vùng chạm tối thiểu 48x48px; chỉ báo vị trí dạng vạch mảnh (vùng chạm vẫn 48px).
- Ảnh: luôn có `aspect-ratio` qua class tỉ lệ để không nhảy bố cục; `loading="lazy"` trừ poster hero.
- Chữ escape bằng `esc_html`/`esc_attr`/`esc_url`; không `echo` biến thô; mọi chuỗi giao diện bọc `__()`/`esc_html__()` domain `isha`.
- Chữ ký chuyển động CHƯA làm ở GĐ2: không `position: sticky` pin, không `clip-path`/mask animation; chỉ có trạng thái tĩnh.

### 5.7 CSS: `home.css` theo khối

Mỗi khối nằm giữa hai marker đúng dạng sau (công cụ ghép dựa vào chúng, đừng đổi): `/* >>> tên */` ... `/* <<< tên */`. Thứ tự khối: `base`, `header`, `dock`, `sky`, `hero`, `story`, `philosophy`, `services`, `values`, `process`, `space`, `events`, `articles`, `testimonials`, `vision`, `cta`, `partners`, `compact` (ghi đè chế độ gọn nếu cần). Công cụ ghép: `scratchpad/tools/assemble.js` (xem README ở đầu tệp); mỗi agent soạn khối của mình thành một tệp `scratchpad/home-blocks/<tên>.css` rồi chạy `node assemble.js <đích> <tên> <tệp-khối>`; tương tự cho JS (`home.js`, marker `/* >>> tên */`).
- Mọi selector nằm dưới `.isha-home` (đặc hiệu >= 0,2,0 để thắng `.section`, `.hero`, `.cta`, `body a:hover`, theme.json...). Màu giao diện chỉ dùng token (`var(--isha-*)`). Ngoại lệ có chủ đích (Giai đoạn 7 ghi lại cho đúng mã): bộ vàng kim loại `--metal-*` (mã hex khai ở `:root`), lớp phủ Shadow dạng `rgba( 28, 27, 25, … )` ở khối trước/sau, tia sáng và vòng cảnh kết, và màu giấy/mực của CSS in. Đổi token Shadow thì các chỗ này không đổi theo (cần đổi tay).
- Mối nối đổi cực (`data-sky-solid`): JS ghi `top` | `bottom` | `both` theo cạnh giáp mối nối; chỉ cạnh đó tô đặc, cạnh kia mờ dần từ trong suốt sang `--tone-bg` trong `--sky-fade` (min(160px, 18vh)) để lớp sky pha màu liền mạch. Ở chính mối nối, hai section cùng chuyển qua màu giữa (Oklab 50%, JS đặt `--sky-mid-b` / `--sky-mid-t`) trong dải `--sky-band` (min(110px, 12vh), nằm trong vùng đệm không có chữ) thay vì cắt gọn; mối nối giáp hero giữ cắt gọn.
- Tông nền: thuộc tính `[data-tone]` đặt biến `--tone-bg`, `--tone-fg`, `--tone-soft` (chữ phụ, tương phản >= 4.5:1), `--tone-accent` (đường kẻ, chữ >= 24px), `--tone-ink` (chữ nhỏ nhấn), `--tone-line` (đường 1px). Nền mỗi section = `var(--tone-bg)` (không JS thì tự tô). Quy tắc màu theo prompt: nền `night`/`dusk` dùng `--isha-golden` (KHÔNG dùng Gold thuần trên dusk: 2.49:1), nền `day`/`day-warm`/`late-afternoon` chữ nhỏ nhấn dùng `--isha-gold-ink`, nền `afternoon`/`golden` dùng Shadow cho chữ nhỏ. Mọi cặp (chữ, nền) đã dùng phải được kiểm >= 4.5:1 bằng script.
- Chế độ gọn: `@media (max-width: 900px), (max-height: 560px)`. Cỡ chữ theo token `--isha-fs-*` (thân >= 19px desktop, >= 18px điện thoại), dòng 60-72 ký tự, tiêu đề `text-wrap: balance`, đoạn `text-wrap: pretty`.
- Hover chỉ trong `@media (hover: hover) and (pointer: fine)`; nhấn nút `scale(.98)` trong `--isha-dur-press`; transition chỉ transform/opacity/color/border-color, 120-300 ms, không keyframes cho UI, không bounce.
- `overflow-x: clip` (KHÔNG `hidden`) cho `.isha-film`; không đặt `container-type`, `transform`, `filter`, `contain` lên tổ tiên của phần tử `position: fixed/sticky`.
- Chừa `padding-block: 0.2em` ở mặt nạ chữ để không cắt dấu tiếng Việt; Garamond hiển thị line-height 1.08-1.15, Montserrat chữ hoa nhiều dòng line-height >= 1.35; không `text-transform: uppercase` cho Garamond.
- `prefers-reduced-motion` và `isha-motion-fail`: mọi thứ hiển thị tĩnh (khối CSS `base` chứa trạng thái mặc định; `html.isha-motion-on` chỉ bổ sung hành vi).

### 5.8 Lõi `isha-motion.js`

Namespace `window.ISHA.motion` (IIFE, `'use strict'`, ES5 như `policy-single.js`; KHÔNG sửa hay import file đó). Mục tiêu: nhỏ (khoảng 15-20 KB chưa nén), đúng prompt (mục Lõi). API:
```js
ISHA.motion.started                         // true khi lõi khởi động xong (script gate đọc)
ISHA.motion.scene( rootEl, { pin, length, smooth, compact, update, enter, leave } ) -> { root, progress(), refresh(), destroy() }
ISHA.motion.tween( { delay, duration, from, to, ease, update, done } ) -> { cancel() }
ISHA.motion.inView( el, fn, { once, threshold, rootMargin } ) -> { disconnect() }
ISHA.motion.reduced()  // prefers-reduced-motion: reduce (đọc trực tiếp matchMedia)
ISHA.motion.compact()  // (max-width: 900px), (max-height: 560px)
ISHA.motion.on( 'compact' | 'reduced' | 'resize', fn ) -> off()
ISHA.motion.clamp, lerp, seg( p, a, b ), ease = { power1, power2, power3 (Out), inOut, cine }
ISHA.motion.scrollTo( yOrEl, { duration, offset } )   // dùng ease cine; tức thì khi reduced; không phụ thuộc scroll-behavior CSS
ISHA.motion.hashEntry  // true nếu URL có #hash lúc tải (bỏ intro, đặt mọi cảnh đúng vị trí ngay)
```
- Một vòng rAF DUY NHẤT; chạy khi còn việc (cảnh đang đuổi theo vị trí, tween đang chạy); dừng khi tab ẩn (`visibilitychange`) và khi hội tụ; dùng timestamp của rAF, kẹp `dt` trong [0, 0.1]; làm mượt `p += (target - p) * (1 - exp(-dt/tau))`, tau mặc định 0,18 s.
- Cảnh ghim: `track` cao hơn màn hình, `stage` sticky; `p` đo bằng `getBoundingClientRect` chỉ cho cảnh gần khung nhìn (IntersectionObserver với `rootMargin` ~100% viewport) và chỉ một lần mỗi khung; cảnh đi ngang (`pin: false`): `p` = tiến độ section đi qua màn hình. `update( p, state )` chỉ gọi khi trạng thái đổi (ngưỡng ~0,0004) hoặc khi buộc vẽ lại; `state` gồm `p`, `target`, `width`, `height`, `compact`, `reduced`, `active`.
- `compact` ('static' | 'same' | function): ở chế độ gọn mặc định 'static' = không ghim, gọi `update( 1, state )` một lần khi vào khung (hoặc theo hàm riêng); `reduced` luôn đặt trạng thái cuối, không smoothing, không rAF.
- Đo lại khi `resize`, `ResizeObserver` (gộp vào một rAF, không đo kép), `orientationchange`, thay đổi matchMedia compact/reduced (khởi tạo lại các cảnh), `pageshow` (bfcache). Bọc `try/catch` quanh callback của từng cảnh: một cảnh lỗi không làm chết vòng lặp.
- Không dùng thư viện, không `filter: blur`, không ghi layout trong `update` ngoài transform/opacity/clip-path/mask-position/stroke-dashoffset (đó là việc của từng cảnh).
- Kiểm thử bằng trình duyệt headless (trang thử trong scratchpad): tiến độ ghim tại các vị trí cuộn, hội tụ làm mượt, vòng rAF dừng khi không còn việc (đếm khung), reduced-motion, compact, hash entry, resize, tab ẩn, lỗi trong callback.

### 5.9 `home.js` (GĐ2)

IIFE, `'use strict'`, phụ thuộc `ISHA.motion` (nếu thiếu thì thoát êm, không lỗi). Cấu hình từ `window.ISHA.homeConfig`. Gồm:
1. **Header** `.site-header--home` (do `header-home.php` in): trạng thái bằng class `is-over-hero` (trong suốt, chữ Daylight, khi còn trên hero), `is-solid` (nền Shadow, sau hero), `is-hidden` (cuộn xuống thì ẩn `translateY(-100%)`, cuộn lên thì hiện); luôn hiện khi focus nằm trong header (`:focus-within`); một listener scroll thụ động gộp vào rAF; không JS thì header đặc nền Shadow như bình thường.
2. **Dock** `[data-isha-dock]`: class `is-visible` khi đã qua 80% hero và cảnh kết (`#cta`) hiện dưới 40%; ẩn khi intro đang chạy; không JS thì luôn hiện.
3. **Sky** `[data-isha-sky]`: biến CSS `--sky-bg` nội suy (OKLab hoặc sRGB) giữa tông hai section liền kề theo vị trí cuộn, CHỈ trong khoảng đệm giữa hai section (đệm = phần padding của hai section tại mối nối, không có chữ); khi sky bật thêm `html.isha-sky-on` để các section chuyển sang nền trong suốt; không JS/reduced thì mỗi section tự tô nền (không thêm `isha-sky-on`). Đọc màu từ biến tông đã tính (getComputedStyle một lần khi đo lại).
4. **Hàng vuốt** `[data-isha-row]`: bỏ `hidden` của điều khiển khi hàng tràn, nút Trước/Sau cuộn một khung (`scrollBy`, tức thì khi reduced), chấm chỉ báo theo vị trí cuộn (IntersectionObserver hoặc scroll thụ động), `aria-current` cho chấm hiện tại, nút bị vô hiệu ở hai đầu bằng `aria-disabled`, bàn phím trái/phải khi track được focus, đo lại khi resize.
5. Khung đăng ký module cảnh cho GĐ sau: `window.ISHA.home = { modules: {}, register( id, fn ) }`; khởi tạo mỗi `[data-isha-scene]` có module tương ứng khi `ISHA.motion` sẵn sàng (GĐ2 chưa có module nào).

### 5.10 Bố cục TĨNH từng section (GĐ2)

Đọc thêm đặc tả gốc của từng section ở `prompt-trang-chu.md` (mục "Đặc tả 13 section"). Ở GĐ2 chỉ làm trạng thái tĩnh + chế độ gọn bằng CSS; chuyển động theo cảnh làm sau. Dữ liệu từng section theo mục 4 ("Hình dạng dữ liệu"). Khi thiếu media: `isha_home_placeholder()`; không có dữ liệu thì section đã bị ẩn từ lớp dữ liệu.

- **hero** (`h1`): nền `night`, cao tối thiểu `var(--isha-vh)` (100svh). Giữa khung: vòng tròn ~40vmin chứa poster (`<picture>`: poster dọc khi `orientation: portrait`, ngang còn lại; thiếu poster thì ánh sáng Gold mờ), logo chữ (`isha_home_wordmark`), dòng nhỏ (`.isha-label`), `h1` (Garamond display, `--isha-fs-display`), mô tả (Garamond italic), nút chính tới `booking_url` (`button_label`), vạch dọc gợi ý cuộn. Có `<video class="isha-hero__video" muted playsinline loop preload="none" data-src-landscape data-src-portrait hidden>` (chưa phát ở GĐ2) và nút `.isha-hero__toggle` (chữ "Tạm dừng video"/"Phát video", ẩn khi chưa có video). Kèm `template-parts/home/intro.php`: khung `.isha-intro[data-isha-intro][aria-hidden="true"]` chứa câu intro, mặc định ẩn hoàn toàn (`display: none`); GĐ3 mới làm chuyển động.
- **story**: nền `day`. Bất đối xứng: cột trái hẹp (tiêu đề section + năm bằng Garamond accent), cột phải đoạn chính cỡ 2,4-3rem (Garamond) và đoạn phụ cỡ chữ thân, tối đa 62 ký tự/dòng.
- **philosophy**: nền `day`. Logo chữ lớn (`isha_home_wordmark`, `aria-hidden`) trang trí phía trên (~70% bề ngang, chữ Shadow 12% opacity), rồi `<ol>` 4 mục: chữ cái lớn (Garamond accent, Gold), tên tiếng Anh (`.isha-label`), tên tiếng Việt (`.isha-note`), mô tả. Chế độ gọn: `isha-row` 4 khung.
- **services**: nền `day-warm`. Tiêu đề + câu dẫn, rồi mỗi nhóm là một khung `article` rộng đủ bề ngang: ảnh bìa dọc 4:5 (~55% bề ngang, xen kẽ trái/phải), tên nhóm (`.isha-accent`), tagline (`.isha-note`), danh sách liệu trình (`ul`, mỗi dòng một liên kết, nhãn Montserrat, phân cách đường kẻ mảnh; liệu trình nháp có nhãn "Nháp"), liên kết ghost "Đặt lịch tư vấn riêng". Nhóm chưa có liệu trình: chỉ tagline và nút. Có sẵn `nav.isha-services__index` (6 liên kết neo tới các khung, chỉ báo dạng vạch) ẩn ở GĐ2. Chế độ gọn: `isha-row`, mỗi khung ~88vw.
- **values**: nền `day-warm`. 5 khung dọc bằng nhau (cao ~60svh) trong một dải; mỗi khung: ảnh tuỳ chọn mờ phía sau (đen trắng, opacity 0,35), tên giá trị (`--isha-fs-sub`), mô tả (luôn hiện ở GĐ2), đường kẻ Gold ở đỉnh khung đầu. Khung là `button.isha-values__trigger` với `aria-expanded` và mô tả luôn có trong DOM (tương tác chọn khung làm ở GĐ5). Chế độ gọn: `isha-row`.
- **process**: nền `afternoon`. Đường kẻ ngang mảnh có 5 mốc; dưới mỗi mốc: số thứ tự (Montserrat), tên bước, mô tả. Chế độ gọn: `isha-row` (đường kẻ ở trên). Chữ luôn đạt tương phản (không dùng opacity thấp cho chữ thông tin).
- **space**: nền `golden`. Câu dẫn, rồi dải ảnh ngang bằng `isha-row` ở mọi kích thước (ảnh xen kẽ dọc cao/ngang rộng, ảnh dùng `isha-strip`/`large`), chú thích Garamond italic lệch dưới ảnh.
- **events** (giao diện này đã được thay bằng danh sách tạp chí, xem mục 22): nền `golden`. Mỗi sự kiện là thẻ khung 2.39:1 (ảnh `isha-cinema`, chế độ gọn 4:5 dùng `portrait_id` nếu có) với lớp phủ tối chỉ ở nửa dưới để chữ đạt >= 4.5:1; góc dưới trái: loại (nhãn), tên (Garamond accent), ngày giờ + địa điểm (hai dòng riêng, KHÔNG nối bằng dấu chấm giữa), nút "Đăng ký tham dự" (nếu có `register_url`) hoặc "Xem chi tiết"; sự kiện đã qua có nhãn "Đã diễn ra". Ở GĐ2 hiển thị DANH SÁCH các thẻ xếp dọc (carousel làm ở GĐ5); có sẵn markup điều khiển ẩn (`hidden`).
- **articles**: nền `late-afternoon`. Bài nổi bật (ảnh 4:5, tiêu đề Garamond, excerpt) + danh sách tối đa 3 bài (tiêu đề, chủ đề, ngày; chủ đề và ngày xuống dòng riêng), liên kết "Xem tất cả bài viết" tới `archive_url`. Chế độ gọn: `isha-row` các thẻ.
- **testimonials**: nền `dusk`. Danh sách các lời kể xếp dọc, mỗi lời Garamond italic cỡ lớn, căn giữa; dòng credit: tên, xuống dòng rồi liệu trình. Có sẵn markup điều khiển ẩn.
- **vision**: nền `night`. `h2` ẩn (`.isha-sr`); câu trích Garamond accent italic căn giữa (template thêm dấu “ ”), khoảng 30-40 ký tự/dòng; đoạn tầm nhìn cỡ chữ thân; chữ ký: chức danh (`.isha-label`), tên (Garamond), ảnh chữ ký (PNG/WebP, dùng `filter: invert()` bằng CSS nếu cần cho nền tối).
- **cta** (class `isha-home-cta`): nền `night` có ảnh/video nền tuỳ chọn (poster) với lớp phủ tối; logo (ảnh logo trắng của site qua `get_custom_logo` hoặc `wp_get_attachment_image` với `custom_logo`), tiêu đề, mô tả, nút chính "Đặt lịch tư vấn riêng", nút "Nhắn Zalo" (ẩn nếu `zalo_url` rỗng), liên kết `tel:` số hotline (ẩn nếu rỗng).
- **partners** (nâng cấp 20/11/2026): nền `night`. Tiêu đề căn trái, cỡ tiêu đề section thường, chồng lên góc trái trên của khung tròn (cùng ô lưới nên không tốn thêm chiều cao). Giữa section là MỘT khung vuông `.isha-partners__orbit` (cạnh `min( 100%, 44rem, 100svh - 15rem )`): lớp vector tia mặt trời `assets/images/vector-2.png` tô vàng ánh kim (CSS `mask` + `--metal-dark`, không filter; thiếu mask thì ẩn lớp vector), các logo xếp 3 vòng đồng tâm đè lên (bán kính 25 / 35,5 / 45% cạnh khung; từ 9 logo là 3 vòng, 4 đến 8 là 2 vòng, 1 đến 3 là 1 vòng). Số logo chia gần đều giữa các vòng (12 logo = 4/4/4), vòng sau lệch nửa bước so với vòng kề trong; chỉ khi số logo hai vòng kề khác nhau thì PHP dò góc để khoảng cách ngắn nhất giữa các tâm lớn nhất. Toạ độ tính ở `partners.php` và vào CSS qua `--x`, `--y` (phần trăm khung, huy hiệu căn tâm bằng lề âm, không dùng `transform`). Mỗi logo nằm trong huy hiệu tròn nền `night` viền Golden 50% 1px, cỡ `--pt-d` 14% khung (chế độ gọn 17%) nhưng không vượt `--pt-cap` (90% khoảng cách ngắn nhất giữa hai tâm, PHP đặt trên khung). Logo vẫn về một màu sáng bằng CSS (`filter: brightness(0) invert(1)`), opacity 0,85, ảnh `object-fit: contain`; không liên kết, tên lấy từ alt. Chế độ gọn: tiêu đề trên, khung tròn dưới (tối đa 34rem).
- **dock** (`template-parts/home/dock.php`, `aside.isha-dock[data-isha-dock]`, `aria-label` "Liên hệ nhanh"): ba hành động theo `isha_home_contact()`: "Đặt lịch tư vấn riêng" (nút chính nền Gold chữ trắng), "Nhắn Zalo", "Gọi hotline" (`tel:`); nút nào thiếu dữ liệu thì không in. Desktop: cụm nhỏ góc dưới phải; điện thoại (chế độ gọn): thanh dưới cùng, nút chia đều, luôn có chữ, cao 64px, chừa `env(safe-area-inset-bottom)`. Nút "Lên đầu trang" của theme đứng ngay trên dock khi dock hiện (mục 13); quy tắc ẩn nút của plugin WPFront cũ đã được xoá khi plugin được gỡ (Giai đoạn 7).
- **header-home** (`header-home.php`): sao chép cấu trúc `header.php` (cùng id `masthead`, `isha_logo()`, nút menu, `#primary-nav`, nút "Đặt lịch"; khi `isha_landing_only()` false thì có đủ menu) với class `site-header site-header--home`, vẫn mở `<main id="content" class="site-main">` (KHÔNG có class `page`) và giữ `skip-link`, `wp_body_open()`. CSS: `position: fixed`, nền trong suốt chữ Daylight khi `is-over-hero`, nền Shadow khi `is-solid`; offset thanh admin 32px/46px như `main.css`.

### 5.11 Dữ liệu thử (QA) và ảnh chụp

- Site thật hiện hiển thị trang chủ mới ngay khi `front-page.php` được sửa (DB: `page_on_front` = 10, landing tắt). Section dùng CPT (space, events, articles, testimonials, partners) đang ẩn vì chưa có dữ liệu. Để QA thấy đủ 13 section, dựng "fixture dữ liệu mẫu": script PHP chạy trên DB sandbox (tạo sự kiện, bài, lời kể, ảnh mẫu giả bằng GD, bật filter `wp_get_attachment_url` trỏ tới ảnh mẫu trong scratchpad), render trang chủ qua template loader thành HTML tĩnh (có `wp_head`/`wp_footer`) trong `scratchpad/tools/browser/fixtures/`, rồi chụp bằng harness. Dữ liệu mẫu KHÔNG được tạo trong DB thật.
- Chụp ở 1440x900 và 390x844 (toàn trang bằng `--full` hoặc theo từng section), kèm: không lỗi console, tương phản (script kiểm màu chữ/nền tính toán), một `h1`, thứ tự heading, nút có chữ và >= 48px, ảnh có alt, không cuộn ngang, không JS (tắt JS) vẫn đọc đủ, reduced-motion.

## 6. Môi trường kiểm thử

- PHP CLI: `C:/xampp/php/php.exe` (8.5; code vẫn phải theo 7.4). Không có WP-CLI, git, Python.
- **Sandbox DB**: `isha_sandbox` (bản sao DB thật, tiền tố bảng `isha_`). Script khởi động `scratchpad/tools/boot.php` (đường dẫn đầy đủ ở thư mục scratchpad của phiên) mặc định dùng sandbox và đăng nhập admin ID 1; được tạo/xoá dữ liệu thoải mái trong sandbox. **Tuyệt đối không đặt `ISHA_REAL_DB=1`.** Web server (Apache) luôn dùng DB thật; chỉ đọc qua HTTP, không bấm nút lưu.
- Tạo media thử: `wp_insert_attachment` với `post_mime_type` giả và `update_post_meta( $id, '_wp_attached_file', 'test/fake.jpg' )` (không cần tệp thật; không ghi vào `wp-content/uploads`). Dọn sạch dữ liệu thử sau khi chạy.
- Kiểm cú pháp: `php -l`; công cụ lint PHP 7.4 (`lint74`, dựa trên tokenizer có sẵn của PHP) và kiểm tra quy ước (`wpstyle`) nằm ở `scratchpad/tools/` (có README). Không tải mã từ internet.
- Trình duyệt: Edge/Chrome headless + node (WebSocket có sẵn), công cụ ở `scratchpad/tools/browser/` (nếu đã dựng). Chỉ truy cập trang công khai của site thật (GET, chỉ đọc). Không đăng nhập vào site thật: giao diện admin được kiểm bằng "fixture" (trang HTML dựng từ sandbox, nạp CSS/JS tĩnh của WordPress và plugin qua Apache).

## 7. Quy ước đã chốt ở Giai đoạn 2 (bổ sung và sai lệch so với các mục 5.x, cập nhật 2026-11-10)

Các agent của Giai đoạn 2 đã tự quyết những chỗ hợp đồng còn mơ hồ. Từ nay các mục dưới đây THẮNG mục 5.x khi mâu thuẫn.

### 7.1 PHP (`inc/home/*`, template)

- Thêm bộ lọc `isha/home/sections` trong `isha_home_sections()`; có hàm nội bộ không lọc `isha_home_sections_base()`. 13 shortcode luôn được đăng ký đủ từ danh sách gốc; `isha_home_section_open()` rơi về danh sách gốc với id bị lọc mất.
- In hay trả về: `isha_home_image` luôn trả chuỗi; `isha_home_video_url`, `isha_home_video_mime` trả giá trị thô; `isha_home_title_id` trả id; `isha_home_section_open`, `isha_home_section_close`, `isha_home_button`, `isha_home_row_controls` in ra; `isha_home_media`, `isha_home_placeholder`, `isha_home_wordmark` (khoá `echo` trong `$args`) và `isha_home_p` (đối số thứ ba `$echo`) mặc định in rồi trả chuỗi rỗng, với `echo = false` thì trả chuỗi. `isha_home_in_film( $set )` trả cờ hiện tại sau khi đặt.
- Khối giữ chỗ hiện nhãn qua thuộc tính `data-ph-label` (chỉ người sửa trang thấy chữ; khách thấy thuộc tính rỗng), không có chữ con.
- `isha_home_wordmark` nhận thêm `class` và `ids` (false = bỏ id `isha-i/s/h/a` để khỏi trùng khi logo chữ xuất hiện hai lần). SVG dự phòng có viewBox 0 0 400 100.
- Template section gọi không kèm dữ liệu thì tự lấy `isha_home_data( '<id>' )`; shortcode vẫn truyền dữ liệu qua `$args`.
- Shortcode nhúng ở trang khác: tài nguyên được nạp ở `wp_enqueue_scripts` khi nội dung có `[isha_home_`, gọi muộn thì in ở chân trang. `count` rỗng hoặc không phải số thì dùng mặc định (5, 4, 6); số được ép vào 1..12.
- Preload: poster dùng `isha-hero` (ngang) và `isha-frame` (dọc), `imagesizes="100vw"`, mỗi poster một media query; template hero phải dùng cùng kích thước và `sizes` để trình duyệt dùng lại ảnh đã preload. Font preload ở trang chủ: Garamond nghiêng và Montserrat Medium, cộng hai font đã preload ở `inc/enqueue.php`. KHÔNG preload Garamond Medium (font h1 hero, 388 KB): đo A/B trên 4G giả lập (9 Mbit/s, RTT 170 ms) cho thấy nó tranh băng thông với CSS chặn hiển thị và làm LCP chậm hơn 190 đến 380 ms mà CLS (0,039 do h1 đổi font) không giảm; chỉ cân nhắc lại sau khi chuyển font sang WOFF2.
- Cổng 6 giây trong `<head>` (`isha_home_head_gate()`) thêm `isha-motion-fail` khi lõi chuyển động chưa `started` HOẶC `window.ISHA.home.register` chưa có (`home.js` gán nó ngay dòng đầu của IIFE); nếu chỉ kiểm lõi thì khi `home.js` lỗi hay bị chặn, dock ẩn vĩnh viễn. Các cảnh Giai đoạn 3 đến 5 vì thế không bao giờ khởi động sau một lần nạp chậm hơn 6 giây (nội dung vẫn đọc được).
- `isha_home_trim_assets()` (`wp_enqueue_scripts`, ưu tiên 100, chỉ khi `isha_home_is_active()`): gỡ `wp-block-library` và `wp-block-library-theme` (CSS của block editor, khoảng 141KB, chặn hiển thị; trang chủ không dùng block). Các trang khác giữ nguyên; script của plugin khác không bị đụng tới (Giai đoạn 7: đã bỏ lệnh gỡ script `wpfront-scroll-top` vì plugin đã được gỡ khỏi site). Khi thêm mã cần jQuery hay style block vào trang chủ thì phải bỏ phần gỡ tương ứng.
- `intro.php` không có thuộc tính `hidden` vì `home.css` có `.isha-home [hidden] { display: none !important; }`; khối hero của CSS phải có `.isha-home .isha-intro { display: none }` cho đến khi Giai đoạn 3 bật intro.
- `dock.php` in thẳng 3 liên kết bằng `esc_*`; Zalo mở tab mới với `rel="noopener noreferrer"`. Dock là `aside` trong `<main>` đúng hợp đồng (axe báo mức best-practice `landmark-complementary-is-top-level`).
- `assets.php` và hàm đọc wordmark dùng `get_template_directory()` và `get_template_directory_uri()` thay cho `ISHA_DIR`, `ISHA_URI` (để test hoán đổi được thư mục theme).
- `window.ISHA.homeConfig` có `preview` (bool) và `strings` (`pause`, `play`, `prev`, `next`, `item` = "Khung %d", `rowLabel`).

### 7.2 CSS và token

- `--isha-z-intro` = 999 (không phải 1000) để skip-link (1000) nằm trên intro. Ba tông `dawn`, `golden`, `dusk` khai hex dự phòng rồi ghi đè bằng `color-mix( in srgb, ... )` trong khối `@supports`; `--isha-vh` ghi đè bằng `100svh` cùng cách. Chrome vẽ `golden` với kênh đỏ 203 thay vì 204 (lệch 1/255, test dùng dung sai 1).
- Biến thêm ngoài hợp đồng: `--isha-gutter`, `--isha-measure` (22.5em, cho 60 đến 72 ký tự mỗi dòng), `--isha-row-pad`, `--row-item`, `--row-gap`, `--row-pad`, `--tone-ph`. Bộ biến tông night cũng áp cho `.isha-dock`.
- Gold không đạt 3:1 trên dusk (2,49) và golden (2,52): các tông dùng `--tone-accent`, header dùng `--isha-golden`. Dưới 680px, token chữ thân và chú thích hạ còn 18px và 17px trong `.isha-home`.
- Mọi selector nằm dưới `.isha-home`, `body.isha-home` hoặc `html.isha-*`; màu giao diện dùng token (ngoại lệ ghi ở mục 7: vàng kim loại, lớp phủ rgba, CSS in). Lớp chống lỗi `.isha-home [hidden] { display: none !important; }`; `!important` còn xuất hiện ở `.isha-sr` và chuyển tiếp của dock khi giảm chuyển động.
- `body.isha-home .site-footer { position: relative }` (lớp bầu trời cố định vẽ đè chân trang nếu thiếu; `footer.php` không sửa). Ở trang chủ, liên kết mạng xã hội và menu chân trang có vùng chạm 48 x 48.
- Focus không bị che (WCAG 2.4.11): `html:has( > body.isha-home )` đặt `scroll-padding-top` = `--header-h` cộng chiều cao thanh admin, và `scroll-padding-bottom` 150px (cụm dock desktop) hoặc 72px cộng safe-area (thanh dock chế độ gọn). Đây thay cho `scroll-margin-top` của `.isha-sec` (hai thứ cộng dồn nên chỉ giữ một). Thêm phần tử cố định mới thì phải chừa chỗ tương ứng ở đây. Vòng focus của nút `isha-values__trigger` dùng `--tone-fg` (màu nhấn Gold chỉ còn 1,7 đến 3,6:1 trên ảnh nền mờ); nút vô hiệu `aria-disabled` đang focus đặt `opacity: 1`; chế độ tương phản cao (`forced-colors`) cho nút dock và nút Trước, Sau viền `ButtonText`.
- Thẻ có `aspect-ratio` mà chứa chữ (`.isha-events__card`) phải dùng `overflow: clip` (kèm `overflow: hidden` làm dự phòng đặt trước), KHÔNG chỉ `overflow: hidden`: `hidden` biến thẻ thành vùng cuộn nên `min-height: auto` bằng 0 và thẻ cắt chữ. `.isha-sec__title` và `.isha-lead` có `overflow-wrap: anywhere` (từ dài không chia được ở 320px với chữ phóng 200%). Scrim của header có khai báo `background` dự phòng (không color-mix) đặt trước khai báo dùng color-mix.
- Class tỉ lệ có `height: auto`. Hàng vuốt: `--row-item: min( 88vw, 360px )`; hàng nên nằm NGOÀI `.isha-wrap` để tràn mép, nếu đặt trong wrap phải đặt `--row-pad: 0`.
- Hero: h1 nằm trong đĩa mặt trời; 48 tia Light Gloom tĩnh nằm trong `.isha-hero__rays` (Giai đoạn 3 có thể làm động hoặc thay); chiều cao hero là `100svh` trừ `--header-h` khi chưa có JS, và `100svh` khi `html.isha-motion-on:not(.isha-motion-fail)` (header cố định).

### 7.3 `isha-motion.js`

- DOM ghim: tìm `[data-isha-track]` và `[data-isha-stage]` trong rootEl; `opts.track` và `opts.stage` (phần tử hoặc selector) ghi đè. Không có track thì rootEl là track; không có stage thì coi stage cao bằng khung nhìn và sticky ở top. Với stage, `p` của cảnh ghim là độ dịch sticky chia quãng ghim.
- `scene()` mặc định: `pin` false; `length` giới hạn quãng ghim bằng length x chiều cao khung nhìn; `smooth` false hoặc 0 tắt làm mượt. Cảnh rời vùng gần (khoảng 100%) được đặt chính xác 0 hoặc 1 và cập nhật lần cuối (`state.active = false`) trước `leave()`; cảnh vào vùng gần nhảy `p` tới đích thay vì đuổi từ 0.
- Chế độ tĩnh (compact `'static'`, compact là hàm, reduced) dùng observer chặt hơn (đáy -10%); compact là hàm nhận `state` và thay cho `update( 1 )`, bị bỏ qua khi reduced.
- `inView( fn( visible, entry ) )`: `once` mặc định false; ngưỡng tự hạ cho phần tử cao hơn khung nhìn. `tween`: `update( value, e )`, mặc định from 0, to 1, duration 1, ease `'linear'`; khi reduced nhảy tới `update( to, 1 )` rồi `done` qua `setTimeout 0`. `ease` có thêm `linear`. `scrollTo` nhận `opts.done`, trả `{ cancel() }`, thời lượng mặc định 0,5 đến 1,4 giây theo khoảng cách, dừng khi wheel, touch, mousedown hoặc phím cuộn.
- `on( 'compact' | 'reduced' )` gọi `fn( flag, info )`; `on( 'resize' )` gọi `fn( info )` mỗi lần đo lại đã gộp (khi reduced, không có rAF, gọi thẳng theo từng sự kiện). ResizeObserver được khử trùng với kích thước đọc ở khung gần nhất.
- Lõi KHÔNG khởi động (`started` không đặt, cổng thêm `isha-motion-fail` sau 6 giây) nếu thiếu `requestAnimationFrame`, `matchMedia` hoặc `IntersectionObserver`.

### 7.4 `home.js`

- Mô-đun cảnh: `ISHA.home.register( id, fn )` gọi `fn( root, ctx )` một lần cho mỗi `[data-isha-scene]` khớp, sau khi `ISHA.motion` đã chạy (đăng ký muộn vẫn được, thăm dò tối đa 6 giây); `ctx = { motion, config, str, reduced, compact }`. `ISHA.home` chỉ lộ `{ modules, register }`. Mỗi khối trong `home.js` nằm giữa cặp marker `/* >>> tên */` và `/* <<< tên */`; sửa bằng `assemble.js`.
- Khác mục 5.9: phần vỏ (rows, header, dock, sky) chạy với phương án dự phòng cục bộ khi thiếu `ISHA.motion`; chỉ các mô-đun cảnh chờ lõi. Khi có `isha-motion-fail` thì không nâng cấp gì và không khởi động cảnh. Header chỉ chạy khi có `isha-motion-on` và không `fail`; dock và sky chạy khi không `fail` (không cần `isha-motion-on`); sky tắt khi reduced; header không bao giờ ẩn khi reduced.
- Sky: đường tham chiếu là giữa khung nhìn (0,5), easing smoothstep, vùng đệm tối thiểu 24px cho section không có padding, `--sky-bg` ghi dạng `rgb( r, g, b )` nguyên; tông nào trình duyệt không cho ra `rgb()` hoặc `color( srgb )` thì bỏ qua.
- **Mối nối đổi cực sáng/tối (bổ sung vào 5.9.3):** tại mối nối mà cực sáng/tối đổi (ngưỡng độ sáng 0,18), hai section kề nhau nhận `data-sky-solid` và tự tô tông của mình thay vì pha qua lớp cố định, vì khung nhìn cao hơn vùng đệm nên chữ của cả hai bên cùng hiện trong lúc pha và một màu không thể đủ tương phản cho cả hai. Tập mối nối tính theo dữ liệu (trên site thật: hero, story, process, vision).
- Header: ẩn hoặc hiện theo cuộn cộng dồn một hướng, 12px để ẩn và 8px để hiện, không ẩn trong khoảng cao header ở đầu trang, dải 4px giữa `is-over-hero` và `is-solid`; focus vào header hay menu mở thì hiện ngay.
- Dock: tỉ lệ `#cta` là số pixel nhìn thấy chia `min( cao cta, cao khung nhìn )`; dock ẩn khi cta thấy từ 40% (hiện lại sau cảnh kết). "Hero 80%" nghĩa là đã cuộn qua 0,8 chiều cao hero. Intro coi là đang chạy khi có client rect và `visibility` không `hidden`.
- Hàng vuốt: một lần bấm là sang điểm bắt cuộn kế tiếp (vị trí bị chặn ở cuộn tối đa), bấm lặp cộng dồn; `aria-disabled` được gỡ (không đặt `false`); dot dựng lại theo số mục khi lệch (nhãn từ `strings.item`); track không nhãn nhận `strings.rowLabel`; mũi tên bàn phím chỉ có tác dụng khi chính track được focus; chưa xử lý RTL và Home/End. `tabindex="0"` của track chỉ giữ khi hàng thật sự tràn (lưới desktop thì bỏ). Dot cao 48px; khi không vừa một dòng trên điện thoại thì chia đều thành các dòng.

### 7.5 Ghi chú theo section cho Giai đoạn 3 đến 5

- **hero:** wordmark (`ids = false`) và nhãn nằm trên đĩa, mô tả và nút nằm dưới; nhóm mặt trời `aria-hidden`, poster `alt=""`; video có `aria-hidden`, `tabindex="-1"`, `data-isha-hero-video`, thiếu một hướng thì dùng video còn lại; nút bật tắt video chỉ in khi có video, có `hidden`, không `aria-pressed`, chữ do JS đổi.
- **philosophy, services:** dùng markup `.isha-row` ở mọi cỡ; desktop (`min-width: 900.02px` và `min-height: 560.02px`) track xếp dọc và điều khiển `display: none`; chế độ gọn giới hạn bề rộng thẻ (services 520px, philosophy 440px). Tên nhóm dịch vụ không phải liên kết, video bìa chưa dùng, mục không có URL in bằng `span`, id khung là `isha-service-<slug>`, `nav.isha-services__index` được in với `hidden`.
- **values:** `h3 > button.isha-values__trigger` (aria-controls tới `div#isha-values-desc-N`); cả 5 nút để `aria-expanded="true"` ở trạng thái tĩnh, Giai đoạn 5 phải đặt lại khi ẩn mô tả; khung đầu có class `is-active`. Để làm ảnh nền sáng lên, hạ opacity của lớp phủ `.isha-values__media::after`, không phải của ảnh; `grayscale` ép đen trắng cả ảnh tông ấm.
- **process:** `ol` có `role="list"`; số thứ tự `aria-hidden` kèm `span` sr-only "Bước N"; đường kẻ là `border-top` của từng bước.
- **space:** hình dạng khung theo hướng ảnh gốc (dọc hoặc vuông 4:5, ngang 3:2; thiếu metadata thì xen kẽ).
- **events** (đã thay thế, xem mục 22): tỉ lệ thẻ 2.39:1 ở desktop và điện thoại xoay ngang, 3:2 ở máy tính bảng dọc (601 đến 900px), 4:5 chỉ ở điện thoại dọc (`max-width: 600px` và `orientation: portrait`) cùng `<picture>` ảnh dọc (media của `<source>` trong `events.php` phải khớp khối events của `home.css`). Sự kiện đã qua luôn dùng nút ghost "Xem chi tiết". Khối điều khiển đặt TRƯỚC danh sách, `hidden`, nút Tạm dừng đứng đầu (APG); danh sách chưa có vai trò carousel (Giai đoạn 5 thêm). Dòng ngày giờ ghép bằng chữ "lúc" (ngày của site đã có dấu phẩy).
- **articles:** một `isha-row`; bài nổi bật là `li` đầu tiên.
- **testimonials:** wrapper điều khiển riêng `[data-isha-testimonials-controls][hidden]` (chỉ khi từ 2 mục; trước, vạch, sau; nút tạm dừng đã bỏ ngày 10/11/2026, xem 14.5), `home.js` không đụng; danh sách xếp dọc cả chế độ gọn; dấu trích là `span` `aria-hidden`.
- **vision, partners:** chữ ký và logo đối tác dùng `filter: brightness( 0 ) invert( 1 )`, nên BẮT BUỘC có nền trong suốt (Phụ lục B của prompt). Partners xếp logo thành các vòng tròn đồng tâm (huy hiệu tròn cỡ cố định theo khung), bỏ ô không có ảnh hợp lệ trước khi chia vòng.
- **cta:** khi có `background_video` hợp lệ, template in sẵn `<video muted playsinline loop preload="none" data-src data-type hidden>` (Giai đoạn 2 không tải hay phát; Giai đoạn 4 phải thêm nút tạm dừng khi video chạy). Logo là thẻ `<img>` của `custom_logo` (alt rỗng), không dùng `get_custom_logo()`; thiếu thì dùng `isha_home_wordmark()`.
- Mọi track và danh sách của các section có `role="list"`.

### 7.6 Công cụ kiểm thử bổ sung (thư mục scratchpad, tạm thời)

- `tools/browser/audit.js`: tương phản tính trường hợp xấu nhất khi chữ nằm trên ảnh (ảnh coi là đen hoặc trắng thuần sau các lớp phủ); với JS bật và lớp bầu trời hoạt động nó đo đồng bộ nên báo sai, hãy dùng `integrate-work/sky-sweep.js`. `tools/home-sample.php` dựng dữ liệu mẫu 13 section trong DB `isha_fixture` (có `--reset`); `tools/browser/home-fixture.php` dựng HTML tĩnh để chụp.

## 8. Chỉ dẫn của người dùng sau Giai đoạn 2 và quy ước Giai đoạn 3 (2026-11-10)

Mục này THẮNG mọi mục khác khi mâu thuẫn (kể cả prompt gốc).

### 8.1 Chỉ dẫn

- **Ngân sách JS:** `isha-motion.js` + `home.js` dưới **90KB** chưa nén (prompt gốc 60KB; người dùng tăng thêm 30KB). Cuối Giai đoạn 3: 27.797 + khoảng 41.200 byte.
- **Font:** không chuyển WOFF2, giữ OTF/TTF. **Vai trò font:** Garamond cho TIÊU ĐỀ và NHÃN; Montserrat cho ĐOẠN VĂN và NÚT/LIÊN KẾT hành động. Cụ thể trong `home.css`: `.isha-sec__title` (tiêu đề section), `.isha-label` (nhãn: dòng nhỏ hero, loại và ngày sự kiện, tên tiếng Anh ở Triết lý, "Đã diễn ra"), `.isha-accent`, `.isha-lead`, `.isha-note`, tên giá trị, số và tên bước quy trình, tiêu đề bài viết, câu trích và lời kể (chữ lớn) đều Garamond, KHÔNG viết hoa, giãn chữ tối đa 0,08em. `.isha-text` (đoạn văn), mô tả hero, địa chỉ sự kiện, số hotline, `.isha-btn`, `.isha-link`, nút Trước/Sau, dock và liên kết liệu trình đều Montserrat. Montserrat trông to hơn Garamond cùng cỡ chữ nên đoạn văn là 17px (`--isha-fs-para`, điện thoại 16px) thay cho 19px/18px của Garamond Display; độ rộng dòng đoạn văn `--isha-measure-para: 36em` (khoảng 60 đến 65 ký tự). Token mới (khai ở `.isha-home` trong khối base): `--isha-fs-title`, `--isha-fs-tag`, `--isha-fs-para`, `--isha-measure-para`. Câu trích và câu phát biểu cỡ lớn (story main, vision, testimonial, tên sự kiện) vẫn là Garamond vì là tiêu đề hoặc trích dẫn.
- **Kiểm thử:** bỏ các bước nặng (bộ trình duyệt đầy đủ, đợt kiểm thử độc lập, nhiều agent); chỉ `php -l`, `lint74`, `wpstyle`, `node --check`, kiểm hạ tầng nhanh (`tests/infra`) và vài ảnh chụp. Người dùng tự kiểm thêm.

### 8.2 Giai đoạn 3: intro và hero

- **DOM hero (đổi so với Giai đoạn 2):** `section.isha-hero` > `.isha-intro` + `.isha-hero__track [data-isha-track]` > `.isha-hero__stage [data-isha-stage]` > (`.isha-hero__film [data-isha-hero-film]` lớp hình toàn khung, chứa `.isha-hero__media [data-isha-hero-media]` với poster hoặc ánh sáng Gold và `<video>`, cùng `.isha-hero__veil`), `.isha-hero__inner` (cột chữ: `.isha-hero__top`, `h1`, `.isha-hero__bottom`, `.isha-hero__sun` chỉ còn vòng mảnh và tia sáng), nút `.isha-hero__toggle`, vạch `.isha-hero__scroll`. Không còn `.isha-hero__iris`.
- **Iris:** `.isha-hero__film` có `clip-path: circle( var(--iris-r) at var(--iris-x) var(--iris-y) )`; mặc định bằng đúng vòng tròn mặt trời. `home.js` đo tâm và bán kính thật (`--iris-x`, `--iris-y`, R0 = nửa đường kính mặt trời, R1 = khoảng cách tới góc xa nhất), rồi ghi `--iris-r`. Cảnh ghim (desktop, > 900 x 560, chuyển động bật, không giảm chuyển động): track cao (1 + 1,4) màn hình, stage dính đỉnh cao 100svh; p 0 đến 0,6 mở iris (easing inOut), tia sáng phóng lên 2,6 lần và mờ, video thu từ 1,12 về 1; chữ mờ và tilt lên từ p 0,35 đến 0,6, lớp phủ tối nhạt từ 0,6 xuống 0,14; p 0,6 đến 1 giữ khung toàn màn hình rồi nhả ghim. Chế độ gọn: không ghim, iris mở theo thời gian 1,2 giây sau intro (`motion.tween`, ease `cine`), chữ giữ nguyên (lớp phủ tối 0,6 bảo đảm tương phản). Giảm chuyển động hoặc không JS: tĩnh (vòng tròn chứa poster, đủ chữ).
- **Kích thước cột chữ:** `--sun: clamp( 176px, calc( ( var(--isha-vh) - 411px ) / 1.4 ), min( 40vmin, 520px ) )` để cả cột chữ vừa 100svh (laptop 1366 x 768 và 1280 x 720 đã kiểm). Hai hàng ngoài của lưới là `minmax( auto, 1fr )` nên tâm mặt trời trùng tâm vùng nội dung.
- **Video:** `home.js` chọn `data-src-portrait` hoặc `data-src-landscape` theo `(orientation: portrait)`, gán `src` và phát SAU intro; dừng khi hero ra khỏi khung nhìn (`leave` của cảnh) hoặc tab ẩn; chọn lại nguồn khi xoay máy; `saveData` hoặc giảm chuyển động thì chỉ hiện poster (nút hiện "Phát video", bấm thì tải và phát). Nút `.isha-hero__toggle` luôn hiện khi có video, chữ đổi "Tạm dừng video" và "Phát video". Người dùng bấm tạm dừng thì không tự phát lại khi cuộn quay lại.
- **Intro:** có khi hero bật intro và có câu intro. Cổng trong `<head>` (`isha_home_head_gate()`) thêm `html.isha-intro-on` ngay từ đầu (trừ giảm chuyển động, URL có #neo, hoặc `sessionStorage.ishaIntro` đã đặt) để lớp phủ che từ khung hình đầu; chốt an toàn 3,5 giây luôn gỡ. `home.js` (khối hero): đặt `sessionStorage.ishaIntro`, đo vị trí mặt trời của hero rồi đặt `--sx`, `--sy`, `--sd` (px) cho `.isha-intro`, dựng tia (một `<path pathLength="1">` mỗi tia, tách từ đường vẽ tia của hero, cộng một `<circle>` vòng mảnh, `--i` là thứ tự), tải sớm font nghiêng bằng `document.fonts.load()`, thêm `.is-run` (CSS transition: mặt trời nhô 1,0 giây, tia vẽ ra so le 11ms, câu intro hiện lúc 1,0 giây). Kết thúc lúc 2,0 giây (hoặc khi video `canplay` trong khoảng 2,0 đến 2,2 giây; tối đa 2,2 giây), hoặc ngay khi bấm, chạm, phím, cuộn: thêm `html.isha-intro-end` (lớp phủ mờ 600ms, hero hiện so le 100ms, header hiện lại), 900ms sau gỡ cả hai class. Trong intro header ẩn (opacity 0). Chuyển tiếp CSS của chữ hero CHỈ bật khi có `html.isha-intro-on` (ngoài intro JS ghi style từng khung nên không được có transition).
- **Header:** `header-home.php` mang sẵn class `is-over-hero` để trong suốt từ khung hình đầu (hết nháy nền đặc).
- **Preload:** không preload font nào ở trang chủ (đo A/B cho thấy làm LCP chậm hơn); chỉ preload poster hero.
- **Chưa làm (giai đoạn sau):** hình học của chế độ an toàn `isha-motion-fail` vẫn khác chế độ thường (xê dịch 42 đến 80px nếu JS khởi động chậm hơn 6 giây); video thật và poster thật chưa có nên các đường video mới chỉ được kiểm bằng video giả.

## 9. Giai đoạn 4: sáu cảnh điện ảnh còn lại (2026-11-10)

Mục này bổ sung cho mục 8 (mục 8 vẫn THẮNG khi mâu thuẫn). Giai đoạn 4 làm các cảnh: câu chuyện, triết lý, dịch vụ, không gian, tầm nhìn, cảnh kết. Giai đoạn 5 (giá trị, quy trình, sự kiện, bài viết, lời kể, đối tác) chưa làm.

### 9.1 Quy ước chung

- **Tĩnh là mặc định.** Hiệu ứng chỉ có khi `home.js` thêm class lên section hoặc phần tử; không JS, `prefers-reduced-motion`, `isha-motion-fail` hoặc script lỗi giữa chừng thì không class nào được thêm (hoặc bị gỡ) và trang là bố cục tĩnh của Giai đoạn 2. Mỗi cảnh nghe `onMode( 'compact' )` và `onMode( 'reduced' )` để bật/tắt đúng lúc đổi cỡ cửa sổ hoặc đổi tuỳ chọn hệ điều hành (đã kiểm: desktop sang điện thoại và về lại, class và style inline được gỡ sạch).
- **Class do JS thêm:** `is-sweep` (story: `.isha-story__main` chưa quét xong), `is-live` (philosophy, services: bố cục cảnh ghim; chỉ khi desktop `(min-width: 900.02px) and (min-height: 560.02px)`, chuyển động bật, không giảm chuyển động; CSS còn kèm chính media query đó), `is-pan` (space), `is-iris` (cta), `is-write` (chữ ký ở vision, chỉ khi chưa viết xong), `is-sun` (chữ cái thẻ triết lý ở chế độ gọn), `is-blind` và `is-open` (ảnh bìa dịch vụ ở chế độ gọn), `is-playing` (video cta).
- **Markup đã thêm (Giai đoạn 4):** `philosophy.php` bọc toàn bộ nội dung trong `.isha-philosophy__track[data-isha-track] > .isha-philosophy__stage[data-isha-stage]`; `services.php` bọc hàng `.isha-row` cộng `nav.isha-services__index` trong `.isha-services__pin[data-isha-track] > .isha-services__stage[data-isha-stage]` (tiêu đề và câu dẫn vẫn nằm ngoài, trên đầu); `cta.php` in `button.isha-link.isha-home-cta__toggle[data-isha-cta-toggle][hidden]` sau khối nội dung khi có video (ngoài `.isha-home-cta__media` vì khối đó `aria-hidden`). Các khối bọc không có kiểu riêng khi chưa `is-live`.
- **Helper dùng chung thêm vào vỏ `home.js`:** `seg( v, a, b )`, `all( root, sel )` (mảng từ `querySelectorAll`), `ease( name )` (easing của lõi, đọc lúc gọi).
- **Hai cạm bẫy đã gặp, đừng lặp lại:** (1) IntersectionObserver của Chrome coi `clip-path` của CHÍNH phần tử được quan sát là vùng nhìn thấy: khối bị cắt thành khe rộng 0 sẽ không bao giờ báo "đã hiện". Quan sát phần tử không bị cắt, hoặc cắt phần tử con (dịch vụ ở chế độ gọn cắt ảnh con, quan sát khối media). (2) `align-content: center` trên hộp cuộn làm phần tràn ở đầu không cuộn tới được: hộp có `max-height` + `overflow-y: auto` phải dùng `align-content: start`.
- **Nhiều khung ghim:** stage dùng `overflow: clip` (không phải hộp cuộn nên focus bàn phím không làm lệch stage) với `overflow: hidden` làm dự phòng; `home.js` đặt lại `scrollLeft` của stage dịch vụ khi nó cuộn.
- **Ngân sách JS** (mục 8.1: dưới 90KB chưa nén): cuối Giai đoạn 4 là 87.101 byte (`home.js` 63.397 + `isha-motion.js` 23.704), còn 2.899 byte. Để vừa, đã rút gọn chú thích: đầu tệp `isha-motion.js` còn bản tóm tắt (đặc tả đầy đủ ở mục 5.8 của tài liệu này) và chú thích đầu mỗi khối của `home.js`. Giai đoạn 5 ước cần khoảng 10KB: phải rút thêm khoảng 7KB chú thích (còn lại trong khối `sky`, `rows`, `hero`, các hàm con) hoặc người dùng nâng ngân sách. (Đã làm và cập nhật số liệu ở mục 10.1.)

### 9.2 Từng cảnh

- **story:** `motion.scene( root )` không ghim. Mask dọc trên `.isha-story__main` (CSS khối story): ảnh mask cao gấp ba khung, phần sáng (0 tới 40%), vệt chuyển 18% chiều cao khung, phần chưa quét opacity .22; `--sweep` (100% tới 0%) là `mask-position` trục dọc. Desktop: quét theo cuộn từ lúc đỉnh section ở 90% khung nhìn tới 35% (hệ số tính theo `state.height` và chiều cao section đo khi bố cục đổi). Chế độ gọn: `compact: function` đợi `inView( main, 40% )` rồi `tween` 1,4 giây (`inOut`). Quét xong thì gỡ `is-sweep` (không còn mask). Cuộn ngược thì mờ lại (quét theo cuộn).
- **philosophy:** cảnh ghim `length: 2.4` (CSS: track cao `calc( var(--isha-vh) * 3.4 )` khi `is-live`). p chia n đoạn (n = số mục, hiện là 4); trong đoạn f với u là phần lẻ: chữ đầy nắng ở u từ 0,05 tới 0,55 (`inOut`), giữ, rồi u từ 0,8 tới 1 là cửa sổ chuyển: logo trượt tới chữ kế, lớp Gold của chữ cũ mờ đi, mô tả cũ mờ và dịch lên 12px, mô tả mới hiện từ dưới. Lớp Gold = bản sao `cloneNode` của chữ (`#isha-i|s|h|a`, bỏ id, thêm class `isha-philosophy__sun`) cắt bằng `clipPath` có `rect` (toạ độ người dùng, đo bằng `getBBox` của chữ gốc; chữ dự phòng là `<text>` nên chỉ lấy 16% tới 80% chiều cao hộp chữ). Độ dịch logo đo bằng `getBoundingClientRect` của chữ so với svg (không phụ thuộc phép dịch hiện tại) để chữ k có tâm ở 38% bề ngang stage; logo rộng `min( 70vw, 150vh )` neo trái stage. Chỉ báo I S H A (div `aria-hidden`) ở mép dưới trái. Chữ gốc mờ bằng `fill-opacity: .12` (không dùng `opacity` của khối). Chế độ gọn: `is-sun` làm chữ cái thẻ trong suốt với nền gradient cắt theo chữ (`--fill`), `inView( chữ, 60% )` rồi `tween` 1,4 giây (`cine`) một lần. Điều kiện bật cảnh ghim: SVG có đủ chữ cái bằng số mục; thiếu thì chỉ còn trạng thái tĩnh và hiệu ứng chế độ gọn.
- **services:** cảnh ghim `length: 0.8 x ( n - 1 )` (CSS: pin cao `calc( var(--isha-vh) * ( 1 + 0.8 * ( var(--n) - 1 ) ) )`, `--n` do JS đặt). Dải khung (`ul`) rộng `n` lần stage, `translate3d( -100 * P / n %, 0, 0 )` với P = p x (n - 1); mỗi khung cao một màn hình. Với d = k - P: ảnh bìa trượt ngược chiều `translateX( -9% x d )` kèm `scale( 1.2 )`; rèm là 11 lá `i` (`span.isha-services__blinds`) `scaleX` 1 về 0 so le `0,045` của cửa sổ d từ 0,92 tới 0,12, chồng nhau 1px. Chỉ mục 6 vạch (bỏ `hidden`, `aria-current` theo `Math.round( P )`), bấm vạch hoặc Tab tới liên kết ở khung khuất hoặc đổi `#isha-service-...` thì `motion.scrollTo` tới đúng p của khung. Cột chữ co theo chiều cao màn hình (`vh`) và có `max-height` + cuộn dọc bên trong làm dự phòng khi một nhóm có quá nhiều liệu trình. Chế độ gọn: `is-blind` (ảnh con `clip-path: inset( 0 50% )` và opacity 0) mở bằng `is-open` khi khối media hiện 60%.
- **space:** `motion.scene( root )` không ghim, desktop có `is-pan`. Dải trôi bằng `scrollLeft` của track (không dùng transform) = `clamp( hidden x seg( p, 0.1, 0.9 ) + bias, 0, hidden )` với `hidden = scrollWidth - clientWidth` đo khi bố cục đổi; mọi dịch chuyển tay (nút Trước/Sau, bàn phím, vuốt, kéo chuột) cộng vào `bias` nên không giật; scroll-snap tắt khi `is-pan`. Chú thích dịch `transform` thêm `-0,15 x ( scrollLeft - hidden / 2 )` (trôi nhanh hơn 1,15 lần, thẳng hàng với ảnh khi dải ở giữa). Kéo chuột: pointer events (chỉ `pointerType === 'mouse'`), `setPointerCapture`, `.is-drag`. Chế độ gọn: hàng vuốt thường.
- **vision:** `motion.scene( root )` không ghim: `.isha-vision__inner` `scale( 1 + 0.04 x ( 1 - inOut( seg( p, 0.08, 0.5 ) ) ) )`, gỡ transform khi về 1; chế độ gọn bỏ scale. Chữ ký tự viết: ảnh là `<img>` nên không có nét để chạy `stroke-dashoffset`, luôn dùng mask `linear-gradient( 90deg )` (`--w` từ 0 tới 112%) trong `tween` 2,2 giây khi chữ ký hiện 60%; xong thì gỡ `is-write`. Giảm chuyển động: chữ ký hiện sẵn, không scale.
- **cta:** `motion.scene( root )` không ghim. Khung hình nền `.isha-home-cta__media` có `clip-path: circle( var(--iris-r) at 50% 50% )` khi `is-iris`; bán kính từ nửa đường chéo section về `0,36 x vmin` (chế độ gọn `0,46 x min( w, h )`). Desktop: khép theo cuộn `inOut( seg( p, 0.06, 0.52 ) )`; chế độ gọn: `inView( root, 50% )` rồi `tween` 1,2 giây (`cine`). Vòng mảnh Gold `span.isha-home-cta__ring` (trang trí, chỉ desktop) đi theo mép iris (co giãn bằng transform) nên cảnh vẫn thấy được khi chưa có ảnh nền. Video nền: `data-src` chỉ gán và phát khi cảnh đang hiện (`enter`/`leave` của cảnh, `visibilitychange`), không phát khi giảm chuyển động hoặc `saveData` (nút "Phát video" vẫn bấm được), nút tạm dừng/phát dùng chuỗi `pause` và `play` của `homeConfig.strings` như hero; người dùng tạm dừng thì không tự phát lại.

### 9.3 Kiểm thử đã làm (nhẹ, theo chỉ dẫn mục 8.1)

`php -l`, `lint74` và `wpstyle` sạch trên các tệp đã sửa; `node --check` và kiểm marker (`assemble.js --check`) sạch; `tests/infra` (ALL PASS) và `tests/theme/data-layer.php` (ALL PASS). Trình duyệt headless: ảnh chụp 6 cảnh ở nhiều mốc cuộn (1440 x 900, 1366 x 768, 1280 x 720, 390 x 844), trạng thái giảm chuyển động, ca xấu nhất (7 liệu trình và tagline dài) ở 720px; kiểm tương tác bằng script: bấm vạch, focus liên kết ở khung khuất, đổi hash, kéo chuột, đổi cỡ cửa sổ desktop sang điện thoại và về, điều khiển video cta bằng video giả (có và không giảm chuyển động); không lỗi console. Chưa kiểm: Safari, Firefox, thiết bị cảm ứng thật, video thật, bộ test trình duyệt cũ, đo hiệu năng.

## 10. Giai đoạn 5: sáu section tầng nhẹ (2026-11-10)

Mục này bổ sung cho mục 8 và 9 (mục 8 vẫn THẮNG khi mâu thuẫn). Giai đoạn 5 làm: giá trị, quy trình, sự kiện, bài viết, lời kể, đối tác. Sau Giai đoạn 5 là đợt chỉnh sửa theo duyệt của người dùng, rồi Giai đoạn 6 và 7.

### 10.1 Chỉ dẫn và ngân sách JS

- Người dùng xác nhận (sau báo cáo Giai đoạn 4): rút thêm khoảng 7KB chú thích để đủ ngân sách, làm Giai đoạn 5, xong thì xuất báo cáo và tắt máy.
- **Đã rút 10,3KB chú thích** (`home.js` 6,3KB, `isha-motion.js` 4,0KB): mọi chú thích giữa hàm và cuối dòng bị xoá trừ những chú thích giải thích điều không hiển nhiên, mỗi khối còn MỘT dòng đầu khối, đầu hai tệp còn bản tóm tắt. Cách làm có kiểm chứng: bộ tách từ ES5 (chuỗi, regex, chú thích) cắt chú thích rồi so sánh chuỗi token của tệp trước và sau, phải GIỐNG HỆT (mã không đổi). Công cụ nằm ở scratchpad (`p5/jsscan.js`, `p5/trim.js`, `p5/rules-home2.js`), tạm thời.
- Giai đoạn 5 thêm khoảng 12KB. Cuối Giai đoạn 5: `isha-motion.js` 19.677 + `home.js` 68.510 = **88.187 byte**, còn **1.813 byte** so với 90.000. Nếu cần thêm chỗ: dùng chung logic video của hero và cảnh kết (khoảng 1KB), rút nốt chú thích (còn khoảng 5,7KB trong `home.js`), hoặc hỏi người dùng nâng ngân sách.

### 10.2 Quy ước chung

- **Tĩnh là mặc định** (như mục 9.1). Class do JS thêm: values `is-live`, `is-pre`, `is-active` (khung); process `is-live`, `is-lit` (bước); events `is-carousel` (trên `.isha-events__carousel`); testimonials `is-carousel` (trên section); partners `is-pre` (trên khung tròn `.isha-partners__orbit`). Hàm `carousel()` thêm `is-active`, `is-in` (trên `li` của slide) và `is-playing` (trên khối gốc).
- **Hàm `carousel( o )` ở vỏ `home.js`** dùng chung cho sự kiện và lời kể, theo mẫu APG: `o = { root, list, slides ( phần tử li ), ticks, prev, next, pause }`. Một slide hiện; slide khác `visibility: hidden` bằng CSS (không focus được, ẩn khỏi trình đọc màn hình). Vai trò: `root` có `aria-roledescription`; phần tử con đầu của mỗi `li` (article hoặc figure) có `role="group"`, `aria-roledescription`, `aria-label` "1 trên 3"; danh sách có `aria-live` ("off" khi đang tự chạy, "polite" khi dừng). Chuỗi nhãn nằm ở `homeConfig.strings`: `carousel`, `slide`, `slideOf` (`%1$d trên %2$d`), cả `FALLBACK` trong `home.js` và `isha_home_config_json()` trong `inc/home/assets.php`.
- **Đồng hồ là CSS.** Vạch của slide hiện tại (`[aria-current="true"]`) có `::after` chạy animation `isha-bar-y` (dọc, sự kiện, 7 giây) hoặc `isha-bar-x` (ngang, lời kể, 8 giây), `forwards`, mặc định `paused` và `running` khi khối gốc có `.is-playing`; `animationend` (tên bắt đầu bằng `isha-bar`) làm `carousel()` chuyển slide kế. Một nguồn thời gian duy nhất nên thanh tiến trình và chuyển slide không bao giờ lệch. Hệ quả: `main.css` đặt `animation: none !important` khi giảm chuyển động nên băng chuyền KHÔNG tự chạy (vẫn bấm được), và CSS đặt vạch hiện tại đầy sẵn trong trường hợp này.
- **Điều kiện chạy:** `is-playing` khi KHÔNG có: người dùng bấm tạm dừng, con trỏ chuột trên khối, focus trong khối, ngoài khung nhìn (IntersectionObserver 20%), tab ẩn, giảm chuyển động. Bấm "Phát" xoá cờ rê chuột và focus để chạy ngay dù con trỏ hay focus còn ở trong. Nút Tạm dừng đứng đầu thứ tự Tab. Vuốt ngang (chạm hoặc bút, không phải chuột) trên danh sách đổi slide; `touch-action: pan-y` cho phép cuộn dọc.
- **`.is-in`:** `carousel()` chỉ thêm `.is-in` cho slide hiện khi khối đã được nhìn thấy. CSS dùng `.is-active.is-in` cho phần "chữ hiện vào" (lời kể: từng dòng; sự kiện: chữ dịch 8px) để hiệu ứng của slide đầu không chạy xong lúc còn ngoài màn hình. Chữ chưa hiện chỉ trong suốt hoặc dịch 8px, không `visibility`, nên trình đọc màn hình vẫn đọc.
- **Cạm bẫy đã gặp:** (1) hover chọn khung phải dùng `pointerenter` gắn từng khung, KHÔNG dùng `pointerover` uỷ quyền: khi lớp con dưới con trỏ đổi (mô tả hiện hay ẩn), Chrome gửi `pointerover` mới không cần con trỏ di chuyển và chọn lại khung cũ, ghi đè lựa chọn bằng focus hoặc chạm. (2) Con trỏ vẫn nằm trên băng chuyền sau khi bấm "Phát" thì phải coi như không hover, nếu không người dùng bấm Phát mà không chạy.

### 10.3 Từng section

- **values:** desktop (`is-live`): khung được chọn bằng `pointerenter` (chỉ chuột), `focusin`, `click`: `is-active` + `aria-expanded` đúng; mô tả các khung khác `opacity 0`, `translateY( 8px )`, `visibility: hidden` (vẫn trong DOM, `aria-controls` đúng); khung chọn có tấm nền đặc của tông ở chân (mô tả luôn 12,3:1 trên mọi ảnh) và lớp phủ ảnh hạ từ 0,65 xuống 0,5 (ảnh 0,35 lên 0,5; không xuống 0,4 vì ca xấu nhất, ảnh đen hoàn toàn, chữ Shadow chỉ còn 2,4:1; ở 0,5 tên khung là chữ lớn 3,3:1 đạt ngưỡng chữ lớn). `.is-pre` (đặt cùng `is-live`, gỡ khi section vào khung nhìn 25%): 5 khung ở bên phải 56px và trong suốt; gỡ thì trượt vào so le 40ms bằng `--i`. Chế độ gọn, giảm chuyển động: mọi khung hiện đủ, `aria-expanded` true.
- **process:** `is-live` khi chuyển động bật và không giảm chuyển động (cả desktop lẫn gọn). Mỗi bước tự vẽ đoạn kẻ riêng (`::after` Gold, `scaleX( var(--f) )`) trên viền trên mờ, các bước liền nhau nên các đoạn nối thành một đường; `is-lit` (chấm Gold, chữ 0,75 lên 1) khi đầu đường kẻ tới bước. Desktop: đầu đường kẻ = `( vị trí cuối bước cuối ) x seg( p, 0.12, 0.5 )` của cảnh không ghim (xong lúc section ở giữa màn hình). Chế độ gọn (hàng vuốt): đầu đường kẻ = mép phải của hàng so với mép trái bước đầu, đo bằng `getBoundingClientRect` mỗi lần vuốt (job `frame.once`). Vị trí và bề rộng bước đo bằng hiệu `getBoundingClientRect` (không phụ thuộc cuộn), đo lại khi bố cục đổi.
- **events:** băng chuyền từ 2 sự kiện khi có JS (`enhanced()`): bỏ `hidden` của khối điều khiển, `role="group"` + `aria-labelledby` (tiêu đề section) cho `.isha-events__carousel`. Các thẻ xếp chồng một ô lưới (thẻ giãn đủ ô), crossfade 300ms, chữ dịch vào 8px. Điều khiển là viên nang nền Shadow đặc ở góc trên phải thẻ (Tạm dừng/Phát bằng `data-label-*`, Trước/Sau chỉ mũi tên vẽ bằng CSS, 3 vạch dọc); trên điện thoại dọc thẻ 4:5, vuốt để đổi slide. Giảm chuyển động: vẫn là băng chuyền nhưng không tự chạy.
- **articles:** KHÔNG đổi, không có JS: hiệu ứng rê chuột (đường gạch `scaleX`, ảnh phóng 1,03 trong 300ms, cả focus bàn phím) đã làm bằng CSS từ Giai đoạn 2 và được giữ nguyên.
- **testimonials:** băng chuyền từ 2 lời kể, tự chạy 8 giây theo vạch ngang (từ 10/11/2026 chạy liên tục và lặp lại, không nút tạm dừng, xem 14.5); các lời xếp chồng một ô lưới, căn giữa dọc. Phụ đề: mỗi từ và hai dấu ngoặc là `span.w`, `--l` là số thứ tự dòng của từ (so `offsetTop`, đo lại mỗi khi bố cục đổi vì chữ ngắt dòng theo bề rộng và font); lời hiện (`.is-active.is-in`) thì từng dòng hiện bằng `opacity` 250ms cách nhau 120ms, bắt đầu sau 150ms. Điều khiển (Tạm dừng có `aria-pressed`, Trước, 3 vạch, Sau) dùng lại kiểu `.isha-row__btn` và `.isha-row__dots`; điện thoại hẹp (dưới 560px) chia hai hàng: vạch trên, Trước, Tạm dừng, Sau dưới.
- **partners:** khi khung tròn vào khung nhìn 30%, vector hiện ra (`opacity` 1,2 giây, từ `scale( .92 ) rotate( -6deg )` về nguyên vị trí) và các huy hiệu hiện lần lượt từ vòng trong ra vòng ngoài (`opacity` 600ms và `scale( .85 )` về 1): `--i` = `floor( k x nhóm / số ô )` với tối đa 6 nhóm, so le 90ms (chậm nhất 450ms), `.is-pre` gỡ khi vào khung nhìn. Không marquee, không xoay liên tục. Giảm chuyển động hoặc không JS: hiện sẵn.

### 10.4 Kiểm thử đã làm (nhẹ, theo chỉ dẫn mục 8.1)

`php -l`, `lint74`, `wpstyle` sạch; `node --check` và kiểm marker sạch; `tests/infra` và `tests/theme/data-layer.php` ALL PASS. Trình duyệt headless trên trang mẫu đủ dữ liệu (3 sự kiện, 3 lời kể, 8 logo, 5 giá trị, 5 bước) và trang thật (values, process): script kiểm tương tác (tất cả đạt) cho sự kiện (cấu trúc và aria, tự chạy 7 giây, rê chuột dừng và chạy lại, Tạm dừng và Phát, Trước, Sau, vạch), lời kể (bọc từ, dòng, tự chạy 8 giây, tạm dừng, vòng lặp), giá trị (trạng thái đầu, trượt vào, rê chuột, focus, chạm), quy trình (đường kẻ theo cuộn và theo vuốt), đối tác, giảm chuyển động, đổi cỡ cửa sổ, điện thoại (vuốt ngang đổi slide, vuốt dọc không đổi), thứ tự Tab và vòng focus; kiểm lại hồi quy của Giai đoạn 4 (dịch vụ, không gian, video cảnh kết); không lỗi console. Chưa kiểm: Safari, Firefox, thiết bị cảm ứng thật, trình đọc màn hình thật (NVDA, VoiceOver) cho `aria-live` và `aria-roledescription`.

## 11. Update 1 (2026-11-10): chỉnh sửa theo duyệt Giai đoạn 4 và 5

Mục này bổ sung và THẮNG các mục 8, 9, 10 khi mâu thuẫn. Chỉ dẫn của người dùng sau khi đọc báo cáo 5 giai đoạn.

### 11.1 Quy ước mới

- **Ngân sách JS: 110KB** chưa nén cho `isha-motion.js` + `home.js` (trước đó 90KB; người dùng cho thêm 20KB, ưu tiên tiết kiệm, chỉ giãn khi cần). Cuối Update 1: `home.js` 71,7KB + `isha-motion.js` 21,0KB = **92,6KB** (còn khoảng 17KB).
- **Chú thích:** mọi hàm có tên đều có MỘT dòng tiêu đề chức năng ngay phía trên (tiếng Việt, ngắn); chú thích đầu khối và đầu tệp chỉ một đến hai dòng, không giải thích dài. Hàm mới viết sau này phải theo quy ước này (tiêu đề đủ để người bảo trì biết hàm làm gì). Đã rút nốt các chú thích dài còn lại; việc chèn và rút luôn kiểm bằng so chuỗi token trước/sau phải giống hệt (công cụ tạm ở scratchpad `p6/add-titles.js`, `p6/trim2.js`).
- **Video nền (hero, cảnh kết): luôn lặp vô hạn, không tiếng, KHÔNG có nút tạm dừng/phát.** Đã gỡ `isha-hero__toggle` và `isha-home-cta__toggle` khỏi PHP, CSS, JS; `homeConfig.strings` còn `prev`, `next`, `item`, `rowLabel`, `carousel`, `slide`, `slideOf` (bỏ `pause`, `play`). Logic video dùng chung ở `bgVideo( video, src, want )` (vỏ `home.js`): phát khi cảnh cần; giảm chuyển động, tiết kiệm dữ liệu (`saveData`) hoặc tab ẩn thì chỉ hiện poster; trình duyệt chặn tự phát (Safari chế độ tiết kiệm pin) thì giữ poster và thử lại ở lần chạm đầu tiên. Đánh đổi: bỏ nút tạm dừng trái tinh thần WCAG 2.2.2 (nội dung chuyển động quá 5 giây cần cách dừng); chấp nhận theo quyết định của chủ dự án, bù bằng giảm chuyển động và `saveData` chỉ poster. Video có tiếng: thuộc tính `muted` và `video.muted = true` nên không bao giờ ra tiếng; khuyến nghị xuất không kênh âm thanh cho nhẹ.

### 11.2 Ảnh trước/sau điều trị trong nhóm dịch vụ (yêu cầu ưu tiên)

- **Dữ liệu (plugin):** `ISHA_Core_Term_Fields` thêm term meta `_isha_ba_before` và `_isha_ba_after` (mảng ID có thứ tự, tối đa `BA_MAX` = 6), hai ô gallery ở màn Thêm và Sửa nhóm dịch vụ (`isha_term_ba_before`, `isha_term_ba_after`). Lưu: nonce và quyền `edit_term`, làm sạch bằng `ISHA_Core_Admin_Media::sanitize( 'gallery' )` (bỏ ID hỏng và trùng), danh sách rỗng thì xoá meta. `::get()` trả `ba_before`, `ba_after` **giữ nguyên vị trí** (ảnh đã bị xoá khỏi thư viện thành 0, không bị loại) để các cặp phía sau không bị lệch: nếu loại bỏ thì ảnh trước của người này sẽ ghép nhầm với ảnh sau của người khác. **Giai đoạn 7:** ô gallery không thể hiện chỗ trống, nên `render_fields()` bỏ cặp hỏng khỏi CẢ HAI danh sách (`drop_broken_pairs()`: cùng một tập vị trí, ảnh dư ở cuối được giữ) trước khi dựng ô, kèm dòng báo; nhờ vậy lần lưu kế tiếp không làm lệch các cặp còn lại (trước đó, mở màn Sửa rồi bấm Cập nhật làm nén danh sách và ghép nhầm cặp). Bài kiểm khứ hồi (xoá ảnh, mở màn Sửa, lưu) ở `scratchpad/p6/ba-admin.php`.
- **Schema và dữ liệu (theme):** thẻ Dịch vụ có thêm trường `ba_note` (ghi chú miễn trừ, tối đa 160 ký tự, có mặc định). `isha_home_data( 'services' )` trả thêm `ba_note`, và mỗi nhóm có `pairs` = danh sách `array( before, after, caption )` (ghép ảnh thứ n của hai danh sách; cặp thiếu ảnh bị bỏ; chú thích lấy từ ảnh sau, trống thì từ ảnh trước).
- **Markup (`services.php`):** nhóm có ít nhất một cặp thì khung 4:5 (`.isha-services__media`) chứa `div.isha-ba[data-isha-ba]` thay cho ảnh bìa: mỗi cặp là `figure.isha-ba__pair` (cặp sau cặp đầu có `hidden`) gồm hai `div.isha-ba__layer` (ảnh và nhãn Trước/Sau) và `figcaption`; rồi `span.isha-ba__line`, `input[type=range].isha-ba__range[hidden]` và (từ 2 cặp) `div.isha-ba__pager[hidden]` gồm các nút chấm. `alt` ảnh nêu rõ "Ảnh trước/sau điều trị, tên nhóm, cặp n". Ghi chú miễn trừ `p.isha-services__ba-note` nằm dưới danh sách liệu trình.
- **CSS:** lớp sau bị `clip-path: inset( 0 0 0 var(--ba) )`, `--ba` mặc định 50% nên không JS vẫn là khung chia đôi tĩnh. Thanh kéo là dải cao 48px ở chân khung (rộng hơn khung 20px mỗi bên để tâm nút kéo trùng lằn chia ở hai đầu), nên phần ảnh phía trên vẫn vuốt được hàng ở chế độ gọn; `touch-action: pan-y`. Tương phản: nhãn và chấm trên nền Shadow 72% (>= 6,8:1), chú thích trên nền Shadow 82% (>= 6,9:1).
- **JS (`home.js`, khối services, hàm `ba( el, motion )`):** bỏ `hidden` của thanh kéo và chấm chọn; sự kiện `input` đặt `--ba`; chấm đổi cặp (`hidden`, `aria-current`); một lần gợi ý kéo (tween 1,8 giây quanh 50%) khi khung hiện 60%, huỷ ngay khi người dùng kéo. Không parallax lên khối này (`img` của khung được thay bằng đối tượng giả trong `frames`). Bàn phím dùng chính input range (mũi tên đổi 1%).
- **Giới hạn đã biết:** pháp lý (đăng ảnh trước/sau của dịch vụ y tế có quy định, cần sự đồng ý của khách và có thể cần pháp chế xác nhận); ảnh trước và sau phải cùng khung chụp, cùng góc và ánh sáng (nếu không, so sánh vô nghĩa).

### 11.3 ISHA BRAND STORY chỉ để tham khảo (không thêm section)

Người dùng chốt: không thêm section nào từ `ISHA BRAND STORY ver1- 290926.pdf`; tài liệu chỉ để tham khảo ý tưởng thiết kế và nội dung (xem `docs/home-brand-script.md`). Section `spirit` (Bốn tinh thần) đã thêm trong Update 1 rồi được GỠ HẲN theo yêu cầu (schema, mặc định, tông registry, template, CSS, đăng ký JS, bài kiểm); trang chủ vẫn là 13 section và không có dấu vết nào của nó trong mã. Nội dung các section hiện có không đổi.

### 11.4 Thêm hoặc bớt section sau này

- `isha_home_data_section_ids()` giờ lấy từ `isha_home_sections_base()` (một nguồn thật); hết danh sách cứng thứ hai.
- **Bớt một section:** xoá khỏi registry (hoặc tắt bằng công tắc của thẻ trong CMS, hoặc bỏ dữ liệu: section rỗng tự ẩn). Không cần sửa JS hay CSS: bầu trời, header, dock suy ra từ DOM (`[data-isha-scene]`, `.isha-hero`, `#cta`). Đã kiểm khói trên bảy tập con (không hero, không cảnh kết, không cả hai, chỉ 2 section, chỉ hero, chỉ quy trình, xen kẽ): không lỗi console, không tràn ngang, lõi chuyển động chạy bình thường.
- **Thêm một section** (ví dụ `abc`): (1) registry: một dòng `'abc' => '<tông>'`; (2) schema ISHA Core: khoá `abc` ở đúng vị trí; (3) `isha_home_defaults()`: `case 'abc'`; (4) `template-parts/home/abc.php`; (5) khối CSS `abc` trong `home.css` (giữa marker); (6) nếu cần chuyển động riêng: `register( 'abc', function ( root, ctx ) {} )` trong `home.js` (không có thì section vẫn hiện tĩnh). Không đụng lõi chuyển động. Cập nhật các bài kiểm có liệt kê section (`tests/infra`, `tests/theme/schema-defaults.php`, `tests/home/test-home-fields.php`).
- Lưu ý duy nhất: section thêm vào phải có `data-isha-scene` (do `isha_home_section_open()` in sẵn), tên cảnh trùng id section.

### 11.5 Vàng kim loại

Token ở khối đầu `home.css` (`.isha-home`): `--metal-deep` #6b4524, `--metal-hi` #936f34, `--metal-light` (gradient 115 độ: đồng sẫm, Gold, vàng sáng, Gold, đồng sẫm; mọi nấc đạt chữ trắng >= 4,60:1 và >= 3,29:1 trên Skin nhạt nên dùng được cho nút và chữ lớn) và `--metal-dark` (đồng #9b5b31, nhạt #eddbc0, rực #f7c664, trung #c48945; chỉ dùng cho nét trang trí lớn trên nền tối). Các màu dẫn xuất từ Gold #8e6837 của hệ thống (kéo sắc độ về 24 tới 40) chứ không dùng nguyên bộ Brown, Pale Gold, Glowing Gold. Áp dụng: nút chính (hero, nút trong cảnh kết, nút trong dock, nút Đặt lịch của header: vệt sáng trượt khi rê chuột, viền sáng mảnh bên trong), năm ở "Câu chuyện" và chữ cái lớn của "Triết lý" ở chế độ tĩnh (`background-clip: text`), vòng mảnh của cảnh kết (mặt nạ vòng 1,5px). Mọi chỗ đều có màu phẳng dự phòng khi trình duyệt thiếu tính năng, và `forced-colors` dùng màu hệ thống. Giới hạn vật lý: trên nền sáng, nấc sáng của vàng bị chặn bởi yêu cầu tương phản nên ánh kim ở đó chỉ là sắc đồng nhẹ; ánh vàng rực chỉ thể hiện rõ trên nền tối.

### 11.6 Căn chỉnh hero khi cổng 6 giây chuyển sang chế độ tĩnh (và quyết định về màn hình chờ)

- **Nguyên nhân trang xê dịch:** chế độ phim đặt header `fixed` đè lên hero (stage cao `100vh`, đệm trên 0,5 header + 48px); chế độ tĩnh (không JS, hoặc `isha-motion-fail` sau 6 giây) để header trong luồng (stage cao `100vh - header`) nhưng vẫn giữ đệm trên cũ, nên mặt trời và chữ tụt xuống nửa chiều cao header khi cổng chuyển chế độ.
- **Sửa tận gốc (CSS):** đệm trên của chế độ tĩnh là `max( 0px, calc( 48px - var(--header-h) * 0.5 ) )` (bớt đúng một header), đệm của chế độ phim giữ `0,5 header + 48px`. Đã đo bằng trình duyệt headless (trang không có JS, giây 2 so với giây 7, ba cỡ màn hình 1440x900, 1024x700, 390x844): tâm mặt trời, tiêu đề và nút hero lệch 0px; mép section kế tiếp chỉ khác ở desktop vì mất quãng ghim 2,4 màn hình (không thể có nếu không có JS).
- **Màn hình chờ (loading screen): KHÔNG làm.** Lớp phủ không đổi số đo LCP (Chrome đo phần tử vẽ lớn nhất kể cả khi bị phủ) nên không giải quyết "LCP chậm"; nguyên nhân thật là phông OTF/TTF nặng (khoảng 2MB), và preload thêm phông đã đo là làm LCP tệ hơn (tranh băng thông với poster). Thay vào đó: intro vẫn chờ font (tối đa 2,2 giây) để không đổi font giữa chừng. Cách giảm LCP hiệu quả nhất là subset phông chỉ giữ chữ La-tinh và tiếng Việt (giữ định dạng OTF/TTF, không dùng WOFF2 theo chỉ dẫn); việc này cần công cụ ngoài (pyftsubset hoặc công cụ tương đương), không thực hiện trong Update 1.

### 11.7 Safari và iOS

- Rà soát tĩnh mã hiện có (không cần thay đổi): `clip-path`, `mask` (có `-webkit-` và `@supports`), `background-clip: text` (có `-webkit-`), `color-mix` (có `@supports` và giá trị dự phòng), `text-wrap: balance` (chỉ cải thiện, không bắt buộc), `overflow: clip` (stage ghim và thẻ Sự kiện có `overflow: hidden` dự phòng; `.isha-film` và `.isha-hero` không có, nên từ Giai đoạn 7 `@supports not (overflow: clip)` ẩn tia hero trang trí để Safari/iOS dưới 16 không bị kéo ngang), `scrollTo( { behavior: 'instant' } )` (luôn có `try/catch` rồi `scrollTo( x, y )`), video có `muted` và `playsinline`, giảm chuyển động và `saveData` chỉ poster, thử phát lại ở lần chạm đầu (chế độ tiết kiệm pin của iOS chặn tự phát), thanh kéo dùng `::-webkit-slider-thumb` và `touch-action: pan-y`. Không dùng `backdrop-filter`, `dvh`, `@property`, `?.`, `??`.
- **Việc làm ở Giai đoạn 7 (thiết bị thật, chưa làm):** iPhone với Safari: (1) cuộn đầu trang: thanh địa chỉ co giãn có làm cảnh ghim giật hay xê dịch không; (2) cuộn đàn hồi (bounce) ở đầu và cuối trang có làm header ẩn hoặc hiện sai không; (3) video hero và cảnh kết có tự phát ở chế độ bình thường và còn poster ở chế độ tiết kiệm pin không; (4) thanh kéo trước/sau: kéo ngón tay có cuộn trang theo không; (5) dock dưới cùng có đè lên thanh home của iPhone không (viewport chưa khai báo `viewport-fit=cover` nên `env( safe-area-inset-* )` bằng 0; nếu bị đè thì thêm `viewport-fit=cover`); (6) font Garamond và Montserrat OTF/TTF hiện đúng; (7) iOS dưới 16.2 dùng giá trị dự phòng của `color-mix`. Không kiểm Firefox và Edge theo chỉ dẫn.

### 11.8 Kiểm thử đã làm

`php -l`, `lint74`, `wpstyle` sạch; `node --check` và kiểm marker sạch; `tests/infra`, `tests/theme/data-layer.php`, `tests/theme/schema-defaults.php`, `tests/home/test-home-fields.php` ALL PASS (giữ 13 section, thêm bài kiểm ghép cặp, xoá ảnh giữa chừng, cắt 6 ảnh). Trình duyệt headless: trước/sau (desktop, gọn, giảm chuyển động), tinh thần (cả ba chế độ), video (thường, giảm chuyển động, saveData, bị chặn tự phát), căn chỉnh hero (ba cỡ màn hình), bảy tập con section, hồi quy các nhóm kiểm Giai đoạn 5 và 4.

### 11.9 Quyết định chốt sau khi duyệt Update 1 (2026-11-10)

Người dùng duyệt Giai đoạn 4, 5 và Update 1, và chốt: gỡ hẳn section `spirit` (xem 11.3); ảnh trước/sau do admin tự tải lên trong phần quản lý (không cần dev cung cấp ảnh); không subset phông; không thêm lại nút tạm dừng video; sang Giai đoạn 6; các vấn đề khác (kiểm thử Safari/iOS thật…) làm sau.

## 12. Giai đoạn 6: trang chi tiết liệu trình (2026-11-10)

Mục này bổ sung cho các mục trước (mục 8 và 11 vẫn THẮNG khi mâu thuẫn). Dựng lại `single-isha_service.php` theo hệ thiết kế của trang chủ, giữ nguyên dữ liệu và luồng đặt lịch (`ISHA_Core_Service_Fields::get()`, `isha_booking_url()` kèm `?service=<ID>`). Có điểm duyệt.

### 12.1 Tệp

| Tệp | Việc |
| --- | --- |
| `single-isha_service.php` | Viết lại, rất mỏng: header, vòng lặp gọi template-part, cảnh kết, footer |
| `template-parts/service-single.php` | Markup toàn trang (nhận `$args` = `isha_service_single_data()`) |
| `inc/service-single.php` (nạp ở `functions.php`) | Dữ liệu dựng sẵn (nhóm chính, ảnh đại diện dự phòng, liệu trình khác, gallery) và nạp tài nguyên |
| `assets/css/service-single.css` | Phần riêng của trang (bố cục, nội dung biên tập, các bước, hộp xem lớn, chế độ gọn) |
| `assets/js/service-single.js` | Ảnh khung mở đầu tiến chậm, các bước hiện lần lượt, hộp xem lớn (dialog) |
| `assets/css/home.css` | Chỉ chuyển token vàng kim loại từ `.isha-home` ra `:root` (để header của trang liệu trình dùng được) |

Không sửa: plugin, `header.php`, `footer.php`, `page.css`, `main.css`, trang nhóm dịch vụ (`taxonomy-isha_service_cat.php`), mọi tệp `policy-single.*`.

### 12.2 Dữ liệu (`inc/service-single.php`)

- `isha_service_single_data( $post_id )` trả: `id`, `title`, `short_desc`, `duration`, `steps`, `gallery`, `group`, `image_id`, `others`, `book_url`, `zalo_url` (chuỗi thuần, template tự escape).
- **Nhóm chính** (`isha_service_single_group`): bài thuộc nhiều nhóm thì lấy nhóm đứng trước theo thứ tự hiển thị (`ISHA_Core_Term_Fields::ordered_terms()`); chưa có nhóm thì không có nhãn nhóm và không có khối "Liệu trình khác".
- **Ảnh đại diện** (`isha_service_single_image_id`): ảnh đại diện của bài, không có (hoặc hỏng, hoặc không phải ảnh) thì ảnh bìa của nhóm, không có nữa thì khối giữ chỗ.
- **Liệu trình khác** (`isha_service_single_others`): cùng nhóm chính, đã xuất bản, `menu_order` rồi tên, bỏ bài hiện tại, tối đa `ISHA_SERVICE_OTHERS_MAX` = 5; ít hơn thì hiện đúng số đang có.
- **Gallery** (`isha_service_single_gallery`): bỏ ảnh hỏng, URL xem lớn là size `isha-strip` (1600×1100, không cắt), alt và chú thích là văn bản thuần.
- **Tài nguyên** (`isha_service_single_assets`, hook `wp_enqueue_scripts` ưu tiên 20, chỉ `is_singular( 'isha_service' )`): gọi `isha_home_enqueue_assets()` (home.css, isha-motion.js, home.js) rồi nạp `service-single.css` (phụ thuộc `isha-main`, `isha-home`) và `service-single.js` (phụ thuộc `isha-motion`, `isha-home-js`, defer, chân trang). CSS nằm trong `<head>`, không in muộn.

### 12.3 Markup và bố cục

- Bọc trong `div.isha-home.isha-svc` để thừa hưởng từ home.css: tông màu (`data-tone`), chữ (`isha-accent`, `isha-lead`, `isha-label`, `isha-sec__title`), nút (`isha-btn`), ảnh (`isha-img`, `isha-ph`, `isha-r-45`), hàng vuốt (`isha-row`). Cảnh kết `[isha_home_cta]` nằm NGOÀI khối này (shortcode tự bọc `isha-home--embed`); nếu cảnh kết bị tắt trong CMS thì dùng khối CTA cũ `isha_cta_section()`.
- **Mở đầu** (`section.isha-svc__open`, tông `day-warm`): nhóm (liên kết về trang nhóm) → tên (h1, `isha-accent`, Garamond; tên trên 44 ký tự dùng cỡ nhỏ hơn) → mô tả ngắn → "Thời gian liệu trình"; ảnh đại diện dọc 4:5 đặt lệch phải (bề rộng theo cả chiều ngang lẫn chiều cao để vừa một màn hình). Chế độ gọn: chữ trên, ảnh dưới.
- **Vùng nội dung** (`div.isha-svc__body`, tông `day`): hai cột. Cột chính theo thứ tự: nội dung biên tập, các bước, ảnh liệu trình (khối nào thiếu thì bỏ). Thẻ thông tin (`aside`, tông `day-warm`) dính bên phải (`position: sticky`, dưới header), có "Thời gian liệu trình", nút "Đặt lịch liệu trình này" và "Nhắn Zalo" (chỉ khi đã nhập URL Zalo). Không có nội dung/bước/ảnh thì bố cục `--solo`: chỉ còn thẻ, đặt giữa một cột hẹp. Chế độ gọn: một cột, thẻ không dính, nằm cuối vùng nội dung (trước "Liệu trình khác").
- **Nội dung biên tập**: `get_the_content()` rồi bộ lọc `the_content` (bài đặt mật khẩu ra biểu mẫu mật khẩu, không lộ nội dung); khối bị bỏ nếu chỉ có thẻ rỗng. Đoạn văn Montserrat 17px (16px điện thoại), dòng đo thật 67 đến 68 ký tự (36em); tiêu đề h2/h3/h4 và trích dẫn Garamond; trích dẫn có đường kẻ Gold bên trái; liên kết gạch chân.
- **Các bước**: `ol.isha-svc__steps` trên một đường kẻ dọc mảnh, số thứ tự trong vòng Gold (số `aria-hidden`, danh sách đã đánh số).
- **Ảnh liệu trình**: hàng vuốt `isha-row` (home.js khối rows dựng nút Trước/Sau, chấm, `aria-disabled`, hiện khi tràn), mỗi khung 4:3 cắt giữa, bấm mở hộp xem lớn; không có JS thì liên kết mở tệp ảnh trong cùng tab (không có `target`, không mở tab mới).
- **Liệu trình khác trong nhóm**: danh sách dòng như ở section Dịch vụ (tên gạch chân mảnh, thời gian nghiêng bên phải), kèm liên kết "Xem tất cả liệu trình trong nhóm".
- Tiêu đề: một h1; các khối h2 (`aria-labelledby`); thẻ thông tin h2.

### 12.4 Chuyển động

- **Tên hiện bằng mặt nạ trượt lên**: CSS animation (`isha-svc-rise`, 1000ms, `--isha-ease-cine`, trễ 120ms) trên `span.isha-svc__name` trong `span.isha-svc__mask` (`overflow: hidden`, `padding-block: 0.2em` bù bằng lề âm để chừa dấu tiếng Việt; đã chụp thử Ẫ Ậ Ế Ổ Ữ Ặ Ỷ không bị cắt). Nhóm, mô tả, thời gian hiện mờ dần (`isha-svc-fade`). Không phụ thuộc JS; `main.css` tắt animation khi giảm chuyển động nên tên luôn hiện đủ. Ảnh đại diện (ứng viên LCP) KHÔNG animate độ mờ, `loading="eager"` và `fetchpriority="high"`.
- **Khung ảnh tiến chậm theo cuộn** (`service-single.js`, `dolly`): `motion.scene( opening, { compact: 'same' } )`, scale 1 đến 1,12 theo tiến độ (mốc đầu lấy lúc vào cảnh, nên đầu trang không phóng); làm mượt tau 0,18 của lõi; giảm chuyển động: không phóng; `will-change` chỉ khi `.is-live`.
- **Các bước hiện lần lượt** (`steps`): `.is-pre` (mờ, dịch 14px) gỡ khi danh sách vào khung nhìn 15%, so le 90ms theo `--i` (tối đa 6 nhóm); không áp dụng khi giảm chuyển động hay lõi chuyển động không chạy.
- Không dùng iris hay tia sáng (chữ ký của trang chủ). Cảnh kết là shortcode tĩnh (trang này không có cổng `isha-motion-on`, nên `enhanced()` false: không iris, video nền vẫn phát khi cảnh hiện).

### 12.5 Hộp xem lớn (`viewer`)

`dialog.isha-svc__viewer` (tông `night`, phủ kín, in sẵn bằng PHP) dùng `showModal()`: Esc đóng (native), mũi tên trái/phải đổi ảnh (vòng tròn), nút Đóng/Trước/Sau cao 48px có chữ, bộ đếm "n / N" (`aria-live`), chú thích (nếu có), bấm vùng trống quanh ảnh cũng đóng; mở thì khoá cuộn nền (`html.isha-svc-lock`), đóng thì trả focus về ảnh đã bấm; một ảnh thì ẩn Trước/Sau. Trình duyệt chưa có `showModal` (Safari trước 15.4) thì giữ liên kết mở tệp ảnh. Liên kết ảnh có `aria-label` ("Xem lớn ảnh: <alt>" hoặc "Xem lớn ảnh n trên N") và `aria-haspopup="dialog"`.

### 12.6 Chỗ lệch prompt gốc (có chủ đích)

- **Đoạn văn Montserrat, không phải Garamond**: prompt ghi "Garamond, 62 đến 70 ký tự mỗi dòng" cho `the_content()`; chỉ dẫn của người dùng sau Giai đoạn 2 (đoạn văn Montserrat, tiêu đề/nhãn Garamond) thắng. Giữ đúng 62 đến 70 ký tự mỗi dòng.
- **Thẻ thông tin ở chế độ gọn** nằm cuối vùng nội dung (sau cùng của phần chính, trước "Liệu trình khác" và cảnh kết) thay vì cuối tuyệt đối của trang, để cảnh kết (đã có nút đặt lịch) vẫn là phần kết.
- **Trang này nạp tài nguyên trang chủ** (home.css, isha-motion.js, home.js) vì dùng lại hệ thiết kế, hàng vuốt và shortcode `[isha_home_cta]` theo đúng chỉ định của prompt; quy tắc "không nạp tài nguyên trang chủ ở trang khác" áp dụng cho các trang không dùng chúng.
- `page.css` có `main.page > .page-header { display: none }` (ẩn banner tiêu đề cũ): khung mở đầu mới KHÔNG dùng class `page-header` nên không bị ẩn; `isha_is_inner_page()` vẫn trả true cho `isha_service` (giữ `<main class="page">`).

### 12.7 Kiểm thử đã làm

`php -l`, `lint74`, `wpstyle` sạch; `node --check` sạch. PHP (`tests/theme/service-single.php`, 59 phép kiểm): nhóm chính, liệu trình khác (5, thứ tự, bỏ nháp/chờ duyệt, ít hơn 5), ảnh dự phòng, gallery, dữ liệu, markup (một h1, escape, solo, bỏ khối thiếu, mật khẩu), nạp tài nguyên đúng handle và phụ thuộc. Trình duyệt headless trên trang mẫu (công cụ scratchpad `p7/`: `service-fixture.php`, `svc-test.js` ba chế độ desktop, gọn, giảm chuyển động; `svc-variants.js` solo, chỉ nội dung, chưa nhóm, tên dài; `svc-viewports.js` tám cỡ màn hình 1920×1080 tới 844×390; `svc-keys.js` đi phím Tab; chụp dấu chồng): cấu trúc, tương tác, hộp xem lớn, chuyển động, không tràn ngang, thẻ dính vừa khung nhìn, thứ tự Tab theo thứ tự đọc. Trang thật qua HTTP (bản nháp, phiên đăng nhập tạm đã huỷ): 200, không lỗi PHP, tài nguyên 200.

### 12.8 Điều cần biết khi nhập nội dung

- Trên site thật cả 17 liệu trình đang là **nháp**: xem bằng "Xem trước" trong wp-admin, hoặc xuất bản. Bài mới tạo chưa có ảnh đại diện sẽ dùng ảnh bìa của nhóm.
- Mỗi liệu trình nhập: Ảnh đại diện (4:5, ≥ 1200×1500), Mô tả ngắn, Thời gian, Các bước (mỗi dòng một bước), Ảnh liệu trình (alt và Chú thích lấy từ Thư viện), Nội dung (editor, tuỳ chọn).
- Muốn đổi thứ tự "Liệu trình khác": sửa Thứ tự (menu_order) của bài.

## 13. Nút "Lên đầu trang" (2026-11-10)

Người dùng đã gỡ plugin WPFront Scroll Top nên theme có nút riêng.

- **Tệp:** `inc/scroll-top.php` (nạp ở `functions.php`), `assets/css/scroll-top.css`, `assets/js/scroll-top.js` (khoảng 3,5KB chưa nén, defer, chân trang).
- **Phạm vi:** mọi trang phía khách, kể cả trang chủ điện ảnh. Ở trang chủ, dock liên hệ chiếm cùng góc dưới phải (desktop: cụm 340×130; gọn: thanh 64px), nên `scroll-top.js` (hàm `lift`) đặt biến `--isha-top-bottom` để nút đứng ngay trên dock, cách 12px, khi dock đang hiện (`.is-visible` của dock khi html có `isha-motion-on`, ngoài ra dock luôn hiện) và hạ về cách đáy 16px khi dock ẩn (hero, cảnh kết); theo dõi bằng MutationObserver trên class của dock và của html. Bộ lọc `isha/scroll_top/enabled` ( bool ) có quyền quyết định cuối: `add_filter( 'isha/scroll_top/enabled', '__return_false' )` tắt hẳn; trả false tắt cả ở trang chủ.
- **Giao diện:** nút cố định góc dưới phải (cách mép 16px, chừa safe-area iPhone), nền Shadow, chữ Daylight 14,09:1, viền Gold 1px, bo `--radius`, cao 48px, luôn có chữ "Lên đầu trang" (mũi tên vẽ bằng CSS, `aria-hidden`); hover chỉ chuột thật (nền gold-hover, chữ trắng 6,91:1); vòng focus theo `:focus-visible` chung; z-index 90 (dưới header 100); không in.
- **Hành vi:** ẩn bằng `visibility` (không nhận focus, không có trong cây trợ năng) cho tới khi cuộn quá 600px hoặc 3/4 chiều cao màn hình; bấm thì cuộn mượt về đầu (dùng `ISHA.motion.scrollTo` nếu lõi chuyển động có trên trang, huỷ được khi người dùng cuộn tay hoặc chạm; không thì `scrollTo` mượt của trình duyệt; giảm chuyển động thì nhảy tức thì), rồi đưa focus về liên kết đầu của header. Không có JS thì nút không hiện.
- **Dọn dẹp (xong ở Giai đoạn 7):** đã xoá quy tắc `.isha-home #wpfront-scroll-top-container` khỏi `home.css` và lệnh `wp_dequeue_script( 'wpfront-scroll-top' )` khỏi `inc/home/assets.php`; theme không còn nhắc tới plugin WPFront.
- **Kiểm thử:** `tests/theme/scroll-top.php` (logic, markup, bộ lọc, trang chủ); trình duyệt headless ở desktop, điện thoại, giảm chuyển động (ẩn đầu trang, hiện khi cuộn, bấm và Enter đều về đầu và trả focus, cuộn tay giữa chừng thì dừng, kích thước, màu); trang thật: trang nhóm và trang chủ đều có nút. Giai đoạn 7: nút KHÔNG in ở trang chi tiết Chính sách (`is_singular( 'isha_policy' )`), vì hai màn hình ghim của trang đó có liên kết "Cuộn xuống" và nút Đặt lịch ở góc dưới mà nút đè lên (đo bằng trình duyệt: che 33% đến 100% liên kết ở 1440×900 đến 844×390); bộ lọc `isha/scroll_top/enabled` vẫn có quyền quyết định cuối. Trên trang chủ đã đo: nút nằm trên dock (cách 12px), hạ xuống mép khi dock ẩn, bấm về đầu trang.

## 14. Giai đoạn 7: kiểm thử tổng thể và các sửa đổi (2026-11-10)

Mục này bổ sung và THẮNG các mục trước khi mâu thuẫn. Kết quả đo và bảng kiểm: `home-report-phase7.md`; bàn giao: `home-handover.md`.

### 14.1 Cách kiểm
- Ma trận Chrome (công cụ `tools/browser/audit.js`): trang chủ 8 cỡ màn hình (1920×1080, 1440×900, 1280×720, 1024×768, 768×1024, 390×844, 360×780, 844×390) × 4 chế độ (thường, giảm chuyển động, tắt JS, `isha-motion-fail` đợi hơn 6 giây) = 32 cấu hình; trang liệu trình 5 biến thể dữ liệu. Mỗi cấu hình kiểm console, tiêu đề, id, tham chiếu, ảnh, tên truy cập, vùng chạm 48px, tràn ngang, font.
- axe-core 4.14 chạy **từng section sau khi cuộn tới thật** (nền các section do lớp bầu trời cố định vẽ nên chạy ở đầu trang sẽ báo sai tương phản của section ở xa; Lighthouse cũng bị hiện tượng này). Lighthouse 12 mobile (giả lập 4G chậm/4G thường) và bản desktop; đo CDP cho CLS, TBT, INP gần đúng.
- Bổ sung sau kiểm toán lần hai: 18 cỡ laptop cao 561 đến 1000 px (nút hero trong khung, không tràn ngang); mô phỏng người đã đăng nhập (thanh admin 32 px/46 px, `html` đẩy xuống, biến `--wp-admin--admin-bar--height`) ở 8 cỡ khung so với bản không có thanh; reflow 320×568, 320×256 (tương đương thu phóng 400%), 640×360 và 720×450 (tương đương 200%) × 3 chế độ (12 cấu hình, 0 vấn đề); `inView()` với phần tử cao tới 14 màn hình và ca xoay máy.
- WebKit (Playwright, Windows) cho luồng chính; không phải Safari thật (xem `home-handover.md`, mục 5 và 7).
- Kiểm toán độc lập bằng 8 tác tử chuyên trách (bảo mật, PHP/WordPress, JS, CSS và tương thích Safari, trợ năng, đối chiếu đặc tả, hồi quy trang cũ, tài liệu), mỗi phát hiện được 2 đến 3 tác tử xác minh phản biện; phát hiện được xác nhận thì sửa kèm bài kiểm.

### 14.2 Lỗi đã tìm và sửa (nơi sửa)
- **Vùng chạm 48px:** vạch chỉ báo Sự kiện 24px → 48px (`.isha-events__bars button`), chấm chọn cặp trước/sau 28px → 48px với viên thuốc nền vẽ bằng `::before` (`.isha-ba__pager`, chấm xuống dòng khi khung hẹp, không đè nhãn Trước/Sau); liên kết liệu trình ở màn thấp `clamp(48px, …)`. Cụm điều khiển Sự kiện cho `flex-wrap` và `max-width` để 5 sự kiện không tràn mép điện thoại (lỗi do chính việc nới vạch 48px, do kiểm toán phát hiện).
- **Trợ năng:** nút Đặt lịch của hero ẩn (`visibility`) khi chữ mờ hẳn (`show()` trong khối hero của `home.js`), trước đó vẫn nhận Tab và bấm được khi vô hình; `lang="en"` cho tên tiếng Anh ở Triết lý và chức danh mặc định ở Tầm nhìn; vòng focus của nút chính trong dock nằm ngoài nút; nhãn "n trên N" của băng chuyền gộp với tên sự kiện (`carousel()`), `role="group"` chỉ đặt trên phần tử trung tính (axe `aria-allowed-role`); ba nút tiêu đề của Giá trị `aria-disabled` + `tabindex="-1"` khi không ở chế độ phim; chú thích ảnh trước/sau cho tối đa 4 dòng.
- **Tương phản hero ở chế độ gọn:** lớp phủ 0,6 → 0,74, nhãn dòng nhỏ và mô tả đổi sang Daylight (đo ca xấu nhất trên ảnh trắng bằng `audit.js` ở 4 cỡ gọn: chữ 5,97:1, nút 4,64:1; trước khi sửa nhãn 2,26:1 và mô tả 2,47:1; sau chỉnh lớp phủ riêng thì mô tả còn 3,98:1 nên phải đổi cả màu); lớp phủ cũng in khi hero chỉ có video (`hero.php`).
- **Lõi chuyển động:** `inView()` quan sát bằng 21 ngưỡng và tính lại mức cần đạt mỗi lần (xoay điện thoại sau khi tải làm ảnh bìa dịch vụ chưa mở không bao giờ hiện).
- **Video nền:** thử phát lại ở `pointerdown`, `pointerup`, `touchend`, `click` (pointerdown trên cảm ứng không phải cử chỉ kích hoạt của iOS), gỡ khi video đã phát.
- **Safari/iOS và hoàn thiện:** `-webkit-user-select`, hover của header chỉ chuột thật, `@supports not (overflow: clip)` ẩn tia hero, `theme-color` + nền `html` tối ở chế độ phim, CSS in ra giấy, panel menu di động cuộn được (`.main-navigation` của trang chủ và trang liệu trình), `input:focus` của `main.css` không vẽ khung quanh thanh kéo trước/sau.
- **Dữ liệu (PHP):** trang liệu trình đặt mật khẩu không lộ mô tả/thời gian/các bước/ảnh (`isha_service_single_data()`, `isha_service_single_others()`); sự kiện nhiều ngày in cả ngày kết thúc (`end_time_label` kèm ngày, hợp đồng 15 khoá không đổi); ảnh trước/sau: `drop_broken_pairs()` ở màn Sửa nhóm (xem 11.2) và `get()` trả danh sách rỗng khi term chưa có meta (trước đó `[0]`); `menu_order` của seed toàn cục theo nhóm (17 bài hiện có trên DB thật đã được cập nhật theo cho phép của người dùng ngày 10/11/2026, xem `home-handover.md` mục 3.3); nút Lên đầu trang không in ở trang chi tiết Chính sách (xem mục 13); Customizer ghi chú 4 điều khiển cũ không còn tác dụng.
- **CLS khi JS về chậm hơn CSS:** `.isha-intro__rays` ẩn cho tới khi JS đo xong hình học (xem 14.3).
- **Hero ở màn hình thấp (phát hiện khi đo 18 cỡ laptop, `laptop-heights.js`):** hằng 411px trong `--sun` giả định h1 không cao hơn mặt trời; ở 1536×660 và 1536×740 h1 mặc định xuống hai dòng (317px) nên nút Đặt lịch bị đẩy ra ngoài khung (đáy nút 689/660 và 757/740). Sửa trong khối hero của `home.css` (chỉ desktop, cả chế độ phim và tĩnh): hàng giữa của lưới cố định bằng `var(--sun)` (h1 tràn vào hai khoảng đệm quanh mặt trời thay vì kéo giãn hàng) và `font-size: min(var(--isha-fs-display), calc((var(--sun) + 24px) / 2.36))` để h1 hai dòng cao tối đa mặt trời + 24px. Chỉ có tác dụng khi cao dưới khoảng 820px. Đo lại: nút trong khung ở cả 18 cỡ, với tiêu đề mặc định và tiêu đề hai dòng (khoảng cách chữ tới dòng nhỏ và mô tả từ 16px). Giới hạn: tiêu đề từ ba dòng hoặc mô tả từ ba dòng ở màn hình thấp (xem `home-handover.md` mục 5).
- **Hoàn thiện sau kiểm toán lần hai (khoảng trống do tác tử phản biện chỉ ra):** `inView()` quan sát lưới 101 ngưỡng cộng đúng mức cần đạt (lưới 21 ngưỡng bỏ sót phần tử cao hơn khoảng 3,3 màn hình) và dựng lại bộ quan sát khi cỡ khung đổi (`on( 'resize' )`: xoay máy làm mức cần đạt của phần tử cao hơn 10 màn hình lệch khỏi lưới; bài `inview-tall.js`: phần tử cao tới 14 màn hình ở 3 cỡ khung và ca xoay máy, 4/4 đạt); video nền giữ các người nghe cử chỉ thử phát lại suốt vòng đời (không gỡ sau lần phát đầu); `lang="en"` của chức danh Tầm nhìn chỉ khi đúng bản mặc định; `isha_home_shortcode()` cách ly lỗi từng section khi WP_DEBUG tắt (bộ lọc `isha/home/isolate_errors`, hành động `isha/home/section_error`).
- **Dọn dẹp:** bỏ quy tắc CSS và lệnh gỡ script của WPFront; `docs/.htaccess` chặn truy cập tài liệu qua web.

### 14.3 Đo hiệu năng (tóm tắt; chi tiết ở báo cáo)
Lighthouse mobile trên bản dựng có đủ 13 section và ảnh mẫu, máy chủ mô phỏng host thật (nén brotli, cache dài): 4G thường FCP 2,05 s, LCP 2,74 s, CLS 0, TBT 320 ms, điểm 85; 4G chậm LCP 7,96 s; desktop LCP 1,45 s, điểm 94. Không nén (Apache cục bộ): 4G chậm LCP 14,9 s. CLS 0,16 xuất hiện khi font không preload không phải do chữ đổi font mà do SVG tia của lớp intro (`.isha-intro__rays`) dời chỗ khi JS đặt `--sx/--sy/--sd` sau lần sơn đầu: đã sửa gốc (SVG `visibility: hidden` tới khi `.is-run`), nay CLS 0 cả khi không preload; hai thẻ preload của `inc/enqueue.php` vẫn giữ (bỏ chỉ ở trang chủ cho điểm Lighthouse 86 → 95 nhưng lượt xem lại có thể thấy chữ đổi font; quyết cùng việc chuyển WOFF2). 74% dung lượng là 6 font OTF/TTF; WOFF2 cắt Latin + tiếng Việt (ước lượng) cho LCP 2,39 s.

### 14.4 Còn lại
Danh sách kiểm Safari/iOS thiết bị thật, quyết định còn mở và đề xuất: `home-handover.md`.

### 14.5 Băng chuyền Lời kể chạy liên tục, không nút tạm dừng (10/11/2026, theo yêu cầu của người dùng)
- **Yêu cầu:** phần Testimonials ở trang chủ tự động chạy, lặp lại, bỏ nút "Tạm dừng". Mục này THẮNG mục 10 (mục 10 tả nút tạm dừng đứng đầu theo APG).
- **Làm gì:** template `testimonials.php` bỏ nút (thứ tự Tab nay là Trước, các vạch, Sau); `home.css` bỏ quy tắc `.isha-testimonials__btn--pause`, ở điện thoại hẹp khối điều khiển còn hai hàng (vạch, rồi Trước và Sau, lưới 2 cột); `carousel( o )` của `home.js` có thêm tuỳ chọn `o.always` (nút `o.pause` thành tuỳ chọn). Lời kể truyền `always: true`; băng chuyền Sự kiện giữ nguyên (còn nút Tạm dừng và dừng khi rê chuột).
- **Hành vi:** tự sang lời kế mỗi 8 giây theo vạch tiến trình, hết lời cuối thì quay về lời đầu (`(i + n) % n`). KHÔNG dừng khi rê chuột (trước đây rê chuột, hoặc cuộn trang khi con trỏ nằm trên khối, làm băng chuyền đứng lại nên nhìn như không tự chạy) và KHÔNG dừng khi bấm chuột vào nút Trước/Sau hay vạch (focus bằng chuột không phải `:focus-visible`). Vẫn dừng khi: focus bàn phím trong băng chuyền (`aria-live` chuyển `polite`; bỏ focus thì chạy lại), ngoài khung nhìn, tab bị ẩn, và giảm chuyển động (không tự chạy, nút Trước/Sau vẫn dùng được). Trình duyệt không hiểu `:focus-visible` thì coi mọi focus là bàn phím.
- **Đánh đổi cần biết:** nội dung tự chuyển động quá 5 giây mà không có nút dừng trái với tinh thần WCAG 2.2.2 (giống quyết định bỏ nút của video ở Update 1). Bù lại: dừng khi focus bàn phím và khi giảm chuyển động; mỗi lời hiện 8 giây (lời dài nhất khoảng 190 ký tự). Nếu sau này cần đạt 2.2.2 chặt chẽ thì thêm lại nút (một dòng trong template và `pause:` trong `home.js`).
- **Kiểm thử:** `autoplay-test.js` (trang chủ thật, chỉ đọc): không còn nút; tự chạy; rê chuột 18 giây vẫn chạy; bấm Sau bằng chuột rồi vẫn chạy; lặp từ lời 5 về lời 1; focus bàn phím dừng 10 giây rồi bỏ focus chạy lại; giảm chuyển động không tự chạy mà nút Sau dùng được; điện thoại 390 px không tràn ngang, vùng chạm ≥ 48 px, tự chạy: 12/12 đạt. Đã cập nhật `p5-test.js` (nhóm testimonials) và `focus-test.js`.

### 14.6 Giá trị cốt lõi: khung tối chữ sáng (11/11/2026, theo phản hồi của người dùng)
- **Vấn đề:** chữ Shadow trên ảnh phủ mờ (lớp phủ màu nền 65%) lẫn màu với ảnh, khung chìm vào nền sáng nên khó đọc và không nổi bật (số đo tương phản đạt nhưng nhìn vẫn mờ).
- **Sửa (`home.css`, khối values):** mỗi khung là một tấm tối: nền và viền Shadow, bóng đổ nhẹ, ảnh đen trắng phủ lớp Shadow 74% (khung được chọn ở chế độ phim 62%), tên và mô tả chữ Daylight, đường kẻ đỉnh khung được chọn vàng golden dày 2px, tấm nền mô tả của khung được chọn là Shadow đặc, vòng focus màu Daylight. Ca xấu nhất (ảnh trắng): chữ Daylight khoảng 5:1 ở trạng thái thường (tên khoảng 3,6:1 ở khung được chọn, chữ lớn từ 24px).
- **Kiểm:** ảnh chụp trên trang thật (desktop có rê chuột, điện thoại), `p5 values` 6/6, audit tương phản, vùng chạm và tràn ngang phạm vi #values ở 5 cỡ × 2 chế độ (10 cấu hình sạch), axe 0 vi phạm desktop và điện thoại. Ảnh nền thật do người dùng tải lên nên tương phản đo theo ca xấu nhất, không phụ thuộc ảnh.

### 14.7 Dịch vụ: bỏ cuộn ngang theo chuột, điều hướng bằng mũi tên (11/11/2026, theo yêu cầu của người dùng)
- **Yêu cầu:** section Dịch vụ ở trang chủ không còn hành vi cuộn chuột làm dải khung nhóm dịch vụ chạy ngang; thay vào đó cụm `.isha-services__index` có thêm hai mũi tên Trước/Sau để bấm đổi khung, giống Workshop & sự kiện. Mục này THẮNG mục 9 (cảnh truck shot ghim).
- **Desktop có chuyển động (`.is-live`):** không còn ghim (`.isha-services__pin` cao đúng một stage; stage `position: relative`, cao một màn hình) và không còn `motion.scene`. `home.js` giữ vị trí khung liên tục `pos`; `goTo( k )` trượt `pos` tới `k` bằng `motion.tween` (0,9 giây, tối đa 1,6 giây cho quãng xa) và gọi lại `show()` cũ nên dải khung, rèm 11 lá và parallax ảnh vẫn như trước. Mũi tên (`button.isha-services__btn--prev/--next`, in sẵn trong `services.php`, chữ trong `.isha-sr`, 48x48) và vạch chỉ mục đều gọi `goTo`; mũi tên **lặp vòng** (Trước ở khung đầu sang khung cuối, Sau ở khung cuối về khung đầu). Tab vào liên kết ở khung khuất và địa chỉ `#neo` vẫn đưa tới đúng khung (không cuộn trang).
- **Không đổi:** chế độ gọn (hàng vuốt có nút Trước/Sau riêng), giảm chuyển động và không JS (các khung xếp dọc, chỉ mục ẩn).
- **Kiểm:** `svc-nav-test.js` trên trang thật: 11/11 đạt (không dính, cuộn trang không đổi khung, bấm Sau/Trước, vòng hai chiều, bấm vạch nhảy khung, không tràn ngang, điện thoại không có chỉ mục phim); `p4 interact`, `p6 ba` (3 chế độ), `p5 modes`, `p7 diacritics`, `p7 top` đạt. JS 96.787 byte (trần 110.000).
- **Ghi chú:** người dùng đã tự bỏ nút Tạm dừng khỏi `events.php` (sáng 11/11): `carousel()` chịu được việc thiếu nút (`o.pause` tuỳ chọn), băng chuyền Sự kiện vẫn dừng khi rê chuột; hai bài kiểm `p5-test.js` và `focus-test.js` đã sửa để chịu được cả hai trạng thái.

### 14.8 Tự chạy mỗi 3,5 giây cho bốn slider (12/11/2026, theo yêu cầu của người dùng)
- **Hiện trạng trước khi sửa:** Sự kiện (7 giây) và Lời kể (8 giây) tự chạy; Dịch vụ (vừa chuyển sang bấm mũi tên) và Không gian (chỉ trôi theo cuộn chuột) thì không.
- **Sửa:** thời gian vạch tiến trình của Sự kiện và Lời kể đổi thành 3,5 giây (\`isha-bar-y\`, \`isha-bar-x\` trong \`home.css\`); Sự kiện chạy liên tục như Lời kể (\`always: true\`, không dừng khi rê chuột). Dịch vụ và Không gian dùng hàm mới \`auto( root, step )\` trong \`home.js\`: gọi \`step\` mỗi 3,5 giây khi section trong khung nhìn; dừng khi focus bàn phím, tab ẩn hoặc giảm chuyển động; bấm vào section thì đếm lại. Dịch vụ: desktop sang khung kế (vòng lại khung đầu), điện thoại cuộn hàng tới thẻ kế; Không gian: cuộn dải tới ảnh kế (hết thì về đầu), cả desktop lẫn điện thoại (\`rowNext()\`, trừ phần đệm trái của hàng).
- **Kiểm:** \`auto35.js\` đo vị trí mỗi 3,5 giây trên trang thật, desktop và điện thoại, 4 mục đều đạt (6 mốc, đổi mỗi lần); \`p5\` (events, testimonials, focus, modes, mobile, reduced), \`p4 interact\`, \`p6 ba\`, lõi chuyển động, \`svc-nav-test\` đạt (đã cập nhật mốc thời gian trong bài kiểm). JS 98.949 byte (trần 110.000).

### 14.9 Component phân trang dùng chung `.isha-pager` (12/11/2026, theo yêu cầu của người dùng)
- **Vấn đề:** nút Trước/Sau và chấm chỉ báo được viết riêng ở 4 nơi (hàng vuốt `isha-row__*`, Sự kiện `isha-events__*`, Lời kể `isha-testimonials__*`, Dịch vụ `isha-services__*`), hai dạng nút (chữ "Trước/Sau" và biểu tượng) và hai dạng chấm.
- **Làm gì:** một helper PHP \`isha_home_pager()\` (\`isha_home_row_controls()\` giữ làm tên cũ) và một khối CSS \`PAGER\` trong \`home.css\`: nút biểu tượng < > (chữ trong \`.isha-sr\`), cột chấm xếp DỌC (chấm tròn, chấm hiện tại là viên thuốc đứng, vạch tiến trình của slider tự chạy vẽ trong viên thuốc), nút sát chấm, đặt dưới slider (Dịch vụ: ở chân stage). Điện thoại / chế độ gọn: ẩn cả cụm (vuốt tay). Mọi section dùng chung; JS tìm \`[data-isha-pager]\`, \`.isha-pager__btn--prev/--next\`, \`.isha-pager__dots\`. Đã xoá CSS riêng của 4 nơi, \`fitDots()\` (chấm không còn xuống dòng) và mã nút Tạm dừng/rê chuột trong \`carousel()\`. \`home.css\` 156.135 → khoảng 151 KB.
- **Không gian ISHA:** cụm điều khiển nằm dưới dải ảnh (trong luồng, không absolute), nút sát chấm. Dịch vụ (desktop): pager ở chân stage, đệm dưới của khung tăng 80 → 140px.
- **Kiểm nhanh (không chuyên sâu, theo yêu cầu):** trang thật desktop và điện thoại không lỗi console; bấm Sau ở Lời kể, Sự kiện, Dịch vụ, Không gian đều đổi; điện thoại ẩn cụm. **Các bài kiểm trình duyệt cũ trong bộ QA (p5, p6, p7…) còn dùng tên class cũ nên cần cập nhật trước khi chạy lại.**

### 14.10 Nhịp 5 giây, vạch active cứng, kéo chuột (12/11/2026, theo yêu cầu của người dùng)
- Nhịp tự chạy của cả bốn slider là 5 giây (một đồng hồ duy nhất: \`auto()\` trong \`home.js\`; \`carousel()\` của Sự kiện và Lời kể dùng lại \`auto()\`, không còn đồng hồ bằng animation CSS). Đã bỏ hiệu ứng vạch tiến trình chạy trong vạch đang chọn (keyframes và \`animation\`): vạch active luôn là màu nhấn cứng. Thao tác bấm, thả chuột, vuốt đặt lại đồng hồ.
- Desktop kéo bằng chuột như vuốt trên điện thoại: hàm \`drag()\` (kéo ngang quá 40px và gấp 1,5 lần kéo dọc thì sang mục kế/trước, rồi nuốt cú bấm để không mở liên kết) cho Sự kiện, Lời kể (trong \`carousel()\`) và Dịch vụ (trên stage); Không gian đã có kéo trôi sẵn. CSS: con trỏ \`grab\`, không chọn chữ (chỉ chuột thật).
- Kiểm: kéo chuột hai chiều ở ba slider, Lời kể đổi 4,7 giây rồi 5,1 giây, cả bốn slider tự chạy ở desktop và điện thoại, không lỗi console.

### 14.11 Cuộn vòng Dịch vụ và Không gian, chuyển cảnh Sự kiện và Lời kể (12/11/2026, theo yêu cầu của người dùng)
- **Dịch vụ (desktop):** cuộn vòng thật. \`home.js\` không còn dịch cả dải mà mỗi khung tự dịch \`translateX\` theo khoảng cách vòng \`wrapD( k, pos )\` tới vị trí hiện tại, nên bấm Sau ở khung cuối thì khung đầu đi vào từ bên phải, khung cuối đi sang trái (cùng chiều), thay vì kéo ngược cả dải; Trước ở khung đầu thì ngược lại. \`goTo( t )\` nhận vị trí tuyệt đối (có thể vượt 0 và n - 1) rồi chuẩn hoá theo vòng khi xong; vạch chỉ mục chọn đường ngắn nhất; kéo chuột và tự chạy cũng đi cùng chiều.
- **Không gian (desktop):** bỏ hiệu ứng dải trôi theo cuộn chuột (theo lựa chọn của người dùng) và bỏ \`data-isha-row\` khỏi template (hàng này do khối space tự điều khiển). Chế độ \`.is-loop\`: ảnh bản sao nối đuôi cho đủ phủ khung nhìn cộng hai lề, mỗi ảnh tự dịch \`translateX\` theo vị trí vòng; MỘT ảnh mỗi lần bấm Trước/Sau, kéo chuột hay tự chạy 5 giây; ảnh cuối nối liền ảnh đầu cùng chiều. Bản sao là \`aria-hidden\` và \`inert\`. Điện thoại giữ hàng vuốt cuộn thật (snap), tự chạy bằng \`rowNext()\`, không cuộn vòng (theo lựa chọn).
- **Sự kiện:** chuyển cảnh parallax nghệ thuật, đúng chiều (\`data-dir\` = next | prev, \`.is-leave\` 1,2 giây cho slide vừa rời): slide mới mở ra bằng nét lau \`clip-path\` 1,0 giây từ cạnh phải (hoặc trái); ảnh của slide mới trôi ngược chiều và thu từ phóng 1,25 về 1 chậm hơn (1,4 giây) nên có độ trễ giữa khung và ảnh; ảnh slide cũ trôi nhẹ đi chỗ khác; chữ hiện lần lượt từng dòng, trượt lên 28px, so le 90ms.
- **Lời kể:** lời cũ mờ dần và trượt nhẹ lên (600 đến 700ms) khi lời mới hiện sau 250ms; từng dòng của lời mới nổi lên 14px (900ms, so le 140ms), dòng tên và liệu trình hiện sau cùng (1 giây).
- **Kiểm:** Dịch vụ đi vòng 1,2,3,4,5,0,1,2 và chụp giữa chừng khi vòng cuối → đầu (khung đầu x = 1018 đi vào từ phải, khung cuối -407 đi trái); Không gian mỗi lần một ảnh, vòng 1,2,0,1,2,0,1; kéo chuột, nhịp 5 giây, ảnh chụp giữa chuyển cảnh Sự kiện và Lời kể, không lỗi console. Chưa chạy lại ma trận và axe.

### 14.12 Tải ảnh mượt (lazy load)

- `home.js` `warm()` / `warmNear()`: đổi `loading="lazy"` sang `eager` cho ảnh của slide kế bên (trước 1, sau 2) khi slide đổi: băng chuyền sự kiện (`carousel.go`), dịch vụ và không gian (`show`, khi chỉ số khung đổi). Lazy gốc của trình duyệt vẫn là mặc định, không dùng thư viện ngoài.
- `fadeImages()`: ảnh chưa tải xong nhận `.is-wait` (ẩn), tải xong đổi `.is-pop` (mờ dần 700ms, tắt khi giảm chuyển động). Không có JS thì ảnh hiện bình thường.

### 14.13 Lời kể dạng hàng thẻ trôi (marquee)

Thay cho băng chuyền và cụm phân trang ở các mục 14.5 đến 14.11 (phần lời kể của các mục đó không còn áp dụng). **Mục 24 (20/11/2026) thay mục này** (hai dãy ngược chiều, spotlight, nền day-warm, hàng vuốt ở chế độ gọn); phần dưới chỉ còn giá trị lịch sử.

- Tham khảo UI/UX phần đánh giá của Aurel Med Spa, chỉ dùng dữ liệu `isha_testimonial` sẵn có (lời kể, tên, liệu trình): không sao, không điểm đánh giá, không ảnh. Vòng tròn đầu thẻ là "Ảnh chân dung" (ảnh đại diện của lời kể, cỡ thumbnail, `alt=""`) nếu có, không thì chữ cái đầu của tên (trang trí, `aria-hidden`).
- Một hàng thẻ trôi sang trái 40px/giây, dừng khi rê chuột (hoặc chạm, hoặc focus bên trong). Hai mép hàng mờ dần (`mask-image`).
- `home.js` (khối testimonials) nhân bản `ul.isha-testimonials__list` (bản sao `aria-hidden` + `inert`) cho đủ phủ khung nhìn cộng một nhóm, đặt `--tm-shift` (bề rộng một nhóm kể cả khoảng cách) và `--tm-dur`; dựng lại khi bề rộng khung đổi hoặc khi đổi chế độ giảm chuyển động.
- Không JS, lõi lỗi hoặc giảm chuyển động: hàng vuốt ngang tĩnh (cuộn thật, snap), không bản sao.
- Font (lưu ý tên biến của theme: `--font-body` = Garamond, `--font-heading` = Montserrat): lời kể và liệu trình Montserrat; tên và chữ cái đầu Garamond.

## 15. Tái cấu trúc giai đoạn 1: CMS và trang chủ (17/11/2026)

Mục này THẮNG mọi mục trước khi mâu thuẫn. Căn cứ: tài liệu "Cấu trúc các page/section" (15/11/2026) và các quyết định của người dùng ngày 17/11/2026. Các trang khác (Giới thiệu, Không gian ISHA, chi tiết Sự kiện/Tin tức, Liên hệ, popup đặt lịch, gộp đường dẫn) thuộc các giai đoạn sau.

### 15.1 Quyết định đã chốt

- Trang chủ còn **11 section**, theo thứ tự: `hero` (banner không gian ISHA, giữ hero video hiện tại; khách tự ghép ảnh không gian thành video), `space`, `results`, `services` (Dịch vụ tiêu biểu), `founder-story`, `events`, `articles` (Tin tức), `testimonials`, `process` (giữ nhưng ẩn bằng công tắc CMS), `partners`, `cta`. Đối tác đứng trên Cảnh kết.
- Gỡ khỏi trang chủ: `story`, `philosophy`, `values` (chuyển sang trang Giới thiệu do theme dựng, giai đoạn sau) và `vision` (thay bằng `founder-story`). **Không xoá dữ liệu:** meta `_isha_home_story`, `_isha_home_philosophy`, `_isha_home_values`, `_isha_home_vision` vẫn còn ở trang chủ (ID 10). Template, khối CSS và khối JS của story, philosophy, values được GIỮ (không chạy vì không có trong DOM) để trang Giới thiệu dùng lại; mặc định của ba section này vẫn ở `isha_home_defaults()`. Template, CSS, JS của vision đã được thay bằng founder-story.
- **Dịch vụ tiêu biểu** chọn bằng ô tick "Dịch vụ tiêu biểu" trong màn Sửa nhóm dịch vụ (term meta `_isha_featured` = `'1'`). Chưa nhóm nào được tick thì section ẩn.
- **Hiệu quả liệu trình** dùng bộ ảnh riêng của trang chủ: tối đa 5 ảnh, mỗi ảnh là một tấm khách đã ghép sẵn hai hình trước và sau (không ghép cặp trong hệ thống). Ảnh trước/sau theo nhóm (`_isha_ba_before`, `_isha_ba_after`) chỉ còn hiện ở trang nhóm dịch vụ.
- **Không gian:** tạm giữ nguồn gallery của trang chủ. Khi dựng trang Không gian ISHA (template riêng, giai đoạn sau), section sẽ lấy các bài có cờ "Xuất hiện ở trang chủ".
- Id section do người dùng đặt: `founder-story` (có gạch ngang). Tên shortcode đổi gạch ngang thành gạch dưới (`isha_home_tag()`), vì WordPress khuyên không dùng gạch ngang trong tên shortcode.

### 15.2 Registry và tông (`inc/home/registry.php`)

| Section | Tông | Class | Shortcode | Ghi chú |
| --- | --- | --- | --- | --- |
| hero | night | isha-hero | isha_home_hero | |
| space | day | isha-space | isha_home_space | trước là golden |
| results | day | isha-results | isha_home_results | mới |
| services | day-warm | isha-services | isha_home_services | chỉ nhóm được tick |
| founder-story | afternoon | isha-founder-story | isha_home_founder_story | mới, thay vision |
| events | golden | isha-events | isha_home_events | |
| articles | late-afternoon | isha-articles | isha_home_articles | |
| testimonials | evening | isha-testimonials | isha_home_testimonials | |
| process | twilight | isha-process | isha_home_process | đang tắt trong CMS; trước là afternoon |
| partners | night | isha-partners | isha_home_partners | đứng trên cta |
| cta | night | isha-home-cta | isha_home_cta | |

Mọi màu của section lấy từ biến tông nên đổi tông không phải sửa CSS của section; lớp bầu trời (`home.js` khối sky) tự đo `--tone-bg` và tự đặt mối nối đổi cực sáng/tối.

### 15.3 CMS (plugin ISHA Core)

- `includes/home-schema.php`: thẻ đúng thứ tự trang. Thẻ mới **Hiệu quả liệu trình** (`results`: title, lead, gallery tối đa 5, note ≤ 160 ký tự) và **Founder** (`founder-story`: title, portrait, quote ≤ 300, story ≤ 1200 nhiều đoạn, name, role, signature). Thẻ Dịch vụ đổi tên "Dịch vụ tiêu biểu", bỏ `ba_note` (chuyển sang `results.note`), thêm `link_label`. Đổi nhãn thẻ: Không gian ISHA, Workshop & sự kiện, Tin tức, Lời kể khách hàng. Thẻ Quy trình có ghi chú đang ẩn theo yêu cầu.
- `includes/class-term-fields.php`: hằng `KEY_FEATURED` (`_isha_featured`) và `FIELD_FEATURE` (`isha_term_featured`); ô tick ở màn Thêm và Sửa nhóm (ô ẩn `0` đứng trước checkbox `1`); lưu khi có nonce và quyền `edit_term` (tick thì `'1'`, bỏ tick thì xoá meta); `::get()` trả thêm `featured` (bool); cột danh sách "Tiêu biểu".
- `includes/class-home-fields.php`: mục menu cấp một **Trang chủ** (vị trí 20.5, ngay dưới Trang) mở thẳng màn sửa trang chủ; khi đang sửa trang chủ thì mục này được đánh dấu (bộ lọc `parent_file`).
- `includes/class-post-types.php`: `isha_article` đổi nhãn "Kiến thức làn da" thành **Tin tức** (post type và slug `bai-viet` giữ nguyên tới bước gộp đường dẫn). Thứ tự menu theo trang: Trang (20), Trang chủ (20.5), Dịch vụ (21), Sự kiện (22), Tin tức (23), Lời kể khách hàng (24), Chính sách khách hàng (27), Lịch hẹn (28; biểu tượng đổi thành đồng hồ để khác Sự kiện). Vị trí 26 bị "Mẫu Elementor" chiếm (Elementor tự đặt) nên Chính sách và Lịch hẹn ở 27, 28.

### 15.4 Lớp dữ liệu (`inc/home/data.php`)

- `results`: `title`, `lead`, `note` (văn bản thuần), `images` (danh sách `id`, `caption`, `alt`; tối đa 5 ảnh hợp lệ). Không có ảnh: `array()` (section ẩn).
- `founder-story`: `title`, `portrait` (int), `quote`, `story`, `name`, `role`, `signature` (int). Mặc định câu trích và câu chuyện lấy từ Tầm nhìn cũ (đã duyệt); chức danh mặc định "Medical Director" (in `lang="en"` khi đúng bản mặc định).
- `services`: chỉ nhóm có `featured`; mỗi nhóm bỏ khoá `pairs`; section bỏ `ba_note`, thêm `link_label` và `all_url` (trang dùng mẫu "Dịch vụ", qua `isha_services_page_id()`). Đã gỡ `isha_home_data_ba_pairs()`.
- `articles.archive_url` = `isha_home_data_news_url()`: Trang bài viết (Cài đặt › Đọc; ở website này là trang Tin tức dạng bento), không có thì trang slug `tin-tuc`, không có nữa thì trang lưu trữ của `isha_article`. Mặc định tiêu đề "Tin tức", nhãn "Xem tất cả tin tức".

### 15.5 Template, CSS, JS

- Template mới `template-parts/home/results.php`, `template-parts/home/founder-story.php`; `services.php` bỏ khung so sánh trước/sau và ghi chú, thêm liên kết "Xem tất cả dịch vụ" (`.isha-services__all`); `vision.php` đã xoá.
- `home.css`: khối mới `results` (ảnh giữ tỉ lệ gốc, cao tối đa 60% màn hình, khung viền vàng kim loại qua `border-image: var(--metal-light)`; băng chuyền nét lau như Sự kiện) và `founder-story` (lưới 5/7, chân dung 4:5 có khung vàng kim lệch phía sau, chữ ký `filter: brightness(0)`); đã gỡ khối ảnh trước/sau `.isha-ba*`, `.isha-services__ba-note` (khoảng 6 KB) và khối VISION.
- `home.js`: hàm dùng chung `deck( root, name )` dựng băng chuyền cho khối `[data-isha-<name>]` (Sự kiện và Hiệu quả liệu trình dùng chung, thay đoạn mã riêng của Sự kiện); khối `founder-story` (ảnh chân dung phóng 1,08 về 1 theo cuộn, chữ ký tự viết); đã gỡ `ba()` khỏi khối services.
- **Ngân sách JS:** `home.js` 80.734 + `isha-motion.js` 22.069 = **102.803 byte** trên trần 110.000 (còn khoảng 7,2 KB; trước đợt này 103.556). Khối JS của story, philosophy, values (khoảng 12 KB) vẫn nằm trong `home.js` để dành cho trang Giới thiệu; khi dựng trang đó nên tách sang tệp riêng để trang chủ nhẹ hơn.

### 15.6 Chuyển dữ liệu một lần trên DB `isha` (17/11/2026)

Người dùng đã sao lưu DB vào `isha_backup` trước khi làm. Script chạy thử rồi mới ghi; chạy lại báo 0 thay đổi. Chỉ thêm hoặc sửa, không xoá meta nào (9 thay đổi):

1. `_isha_home_process`: `enabled` = `'0'` (ẩn Quy trình, giữ nội dung).
2. `_isha_home_results` (mới): `note` chép từ `ba_note` của `_isha_home_services` (khoá cũ vẫn còn trong meta services; lần lưu kế tiếp của trang chủ sẽ bỏ khoá này vì không còn trong lược đồ).
3. `_isha_home_founder-story` (mới): chép `quote`, `body` (thành `story`), `role`, `name`, `signature` (ảnh ID 145) từ `_isha_home_vision`; meta vision giữ nguyên.
4. `_isha_featured` = `'1'` cho cả 6 nhóm dịch vụ (term 14 đến 19), để trang chủ giữ như trước; khách bỏ tick nhóm không muốn hiện.

Khôi phục (nếu cần): bật lại công tắc Quy trình ở thẻ Quy trình; xoá `_isha_home_results`, `_isha_home_founder-story` của trang 10 và `_isha_featured` của các nhóm (bỏ tick ở màn Sửa nhóm); hoặc nạp lại từ `isha_backup`.

### 15.7 Kiểm thử đã làm

- `php -l` mọi tệp PHP đã sửa (cú pháp giữ chuẩn PHP 7.4); `node --check home.js`; marker `>>>`/`<<<` của `home.css` và `home.js` khớp.
- HTTP: trang chủ 200, đúng thứ tự section, không có lỗi mới trong log Apache; liên kết "Xem tất cả dịch vụ" tới `/dich-vu/`, "Xem tất cả tin tức" tới `/tin-tuc/`.
- Chrome headless (công cụ tạm ở scratchpad): desktop 1440×900, điện thoại 390×844 (dpr 2), giảm chuyển động. Không lỗi JS hay console, không tràn ngang; băng chuyền Hiệu quả liệu trình chạy (vai trò, nút Sau, tự chạy; giảm chuyển động thì không tự chạy, đổi tức thì); Founder hiển thị, chữ ký tự viết và hiện sẵn khi giảm chuyển động. Ảnh của Hiệu quả liệu trình được mượn tạm bằng một mu-plugin chỉ có tác dụng với `?isha_qa=1` (không ghi DB, đã xoá ngay sau khi kiểm).
- Quản trị (PHP CLI, đăng nhập giả lập quản trị viên): thứ tự 11 thẻ, Quy trình bỏ tick, ô Founder và chữ ký giữ ID 145, làm sạch gallery Hiệu quả liệu trình (bỏ ID không phải ảnh, cắt 5), ô và cột "Tiêu biểu", thứ tự menu quản trị.
- Chưa làm: Safari/iOS thật, axe, Lighthouse, bộ QA trình duyệt cũ (đã lỗi thời, sẽ làm lại theo quyết định ngày 17/11/2026).

### 15.8 Việc cho các bước sau

- Trang Giới thiệu: lược đồ CMS riêng cho story, philosophy, values (dữ liệu cũ đọc từ meta trang chủ hoặc chép sang trang Giới thiệu), dùng lại template, CSS, JS; tách JS ba section khỏi `home.js`.
- Trang Không gian ISHA (template riêng, bài có cờ "Xuất hiện ở trang chủ") rồi đổi nguồn của section `space`.
- Gộp `/bai-viet/` về `/tin-tuc/` (chuyển hướng 301).
- Nội dung chờ khách: chân dung và câu chuyện chính thức của bác sĩ Linh Võ, chữ ký thật (ảnh ID 145 hiện là logo), 1 đến 5 ảnh trước/sau đã ghép, video hero ghép từ ảnh không gian.

## 16. Trang Giới thiệu (17/11/2026)

Mục này bổ sung mục 15 (THẮNG mục 15.8 ở phần trang Giới thiệu). Theo chỉ dẫn của người dùng ngày 17/11/2026: trang Giới thiệu có template riêng; ba mục Câu chuyện thương hiệu, Triết lý và sứ mệnh, Giá trị cốt lõi là ba section, mỗi section có tiêu đề, tiêu đề nhỏ, nội dung; giao diện tinh tế, doanh nghiệp, sang trọng theo phong cách section "Câu chuyện nhà sáng lập" (founder-story) của trang chủ.

### 16.1 Tệp

| Tệp | Việc |
| --- | --- |
| `plugins/isha-core/includes/class-about-fields.php` (mới) | `ISHA_Core_About_Fields`: meta box "Nội dung trang Giới thiệu", lưu, mục menu quản trị "Giới thiệu" |
| `plugins/isha-core/includes/class-page-hero-fields.php` | `TEMPLATES` thêm `templates/template-about.php`: trang Giới thiệu dùng meta box "Hero banner" chung (mô tả, ảnh/video nền desktop và điện thoại) |
| `plugins/isha-core/isha-core.php` | Nạp và khởi tạo class mới |
| `themes/ISHA/inc/about.php` (mới, nạp ở `functions.php`) | `isha_about_section_defs()`, `isha_about_is_active()`, `isha_about_data()`, `isha_about_assets()` |
| `themes/ISHA/templates/template-about.php` (viết lại) | Mẫu "Giới thiệu": header, `template-parts/about/page.php`, cảnh kết `[isha_home_cta]` (tắt trong CMS thì khối CTA cũ), footer |
| `themes/ISHA/template-parts/about/page.php` (mới) | Markup hero và ba section |
| `themes/ISHA/assets/css/about.css` (mới, khoảng 18 KB) | Giao diện riêng của trang |
| `themes/ISHA/assets/js/about.js` (mới, khoảng 4,7 KB) | Video nền hero, hiện dần, ảnh phóng nhẹ theo cuộn |

### 16.2 CMS (`ISHA_Core_About_Fields`)

- Áp dụng cho trang dùng mẫu `templates/template-about.php` (`::applies()`); `::page_id()` là trang Giới thiệu đầu tiên (ưu tiên đã xuất bản).
- Ba section theo thứ tự: `story` (Câu chuyện thương hiệu), `philosophy` (Triết lý và sứ mệnh), `values` (Giá trị cốt lõi). Mỗi section MỘT meta mảng `_isha_about_<id>`: `enabled` ('1'|'0', không có khoá là bật), `title` (≤ 120), `subtitle` (tiêu đề nhỏ, ≤ 200), `content` (HTML gọn, lọc bằng `::sanitize_content()` với `::allowed_html()`: p, br, h3, h4, strong, b, em, i, ul, ol, li, blockquote, a; lưu như bài viết, theme in qua `wpautop`), `image` (ID ảnh). Chỉ lưu khoá có giá trị; section không còn gì (và không tắt) thì xoá meta.
- Giao diện: ba khung (fieldset) xếp dọc, không dùng thẻ (TinyMCE không dựng được trong khung đang ẩn); mỗi khung: công tắc "Hiển thị section này", Tiêu đề (placeholder là tên section), Tiêu đề nhỏ, Nội dung (`wp_editor` gọn, không chèn media, định dạng Đoạn văn / Tiêu đề phụ h3 / Tiêu đề nhỏ h4), Ảnh minh hoạ (ô chọn media dùng chung).
- Trang dùng mẫu này tắt block editor và bỏ trình soạn thảo của trang (nội dung trang không được dùng); dòng hướng dẫn dưới ô tiêu đề. Lưu ở `save_post_page`: nonce `isha_save_about` / `isha_about_nonce`, quyền `edit_post`, bỏ qua autosave và revision.
- Menu quản trị: mục cấp một **Giới thiệu** (vị trí 20.6, ngay dưới Trang chủ) mở thẳng màn sửa trang; được đánh dấu khi đang sửa trang đó.

### 16.3 Giao diện (`template-parts/about/page.php`, `about.css`)

- Bao trong `div.isha-home.isha-about` và nạp tài nguyên trang chủ (`isha_home_enqueue_assets()`) như trang chi tiết liệu trình, nên dùng chung tông màu, chữ, ảnh, token vàng kim loại. Không có `data-isha-scene` ở trang này (chỉ cảnh kết có), nên home.js chỉ chạy khối cảnh kết.
- **Hero** (`section.isha-about-hero`, tông night): tên website (nhãn), tiêu đề trang (h1, Garamond, hiện bằng mặt nạ trượt lên), vạch vàng kim loại, mô tả (từ "Hero banner"; trống thì câu mặc định), mục lục 01 / 02 / 03 tới các section (khi có từ 2 section). Nền: ảnh hoặc video từ "Hero banner" kèm lớp phủ Shadow 72% (chữ Daylight khoảng 5,5:1 cả trên ảnh trắng); không có thì nền đêm với quầng nắng ấm. Trang trí: vòng tròn mặt trời mảnh 1,5px tô `--metal-dark` (mặt nạ vòng như cảnh kết), xoay rất chậm (48 giây một vòng) nên ánh kim chạy quanh vòng.
- **Section** (`section.isha-about-sec`, tông lần lượt day, day-warm, afternoon; id neo `cau-chuyen-thuong-hieu`, `triet-ly-va-su-menh`, `gia-tri-cot-loi`): phong cách founder-story: lưới 5/7, ảnh dọc 4:5 trong khung viền vàng kim loại lệch 18px phía sau (section lẻ ảnh trái, section chẵn ảnh phải, khung lệch về phía cột chữ), ảnh dính dưới header khi cột chữ dài hơn ảnh; cột chữ: số thứ tự Garamond nghiêng có vạch vàng, tiêu đề (h2), tiêu đề nhỏ (Garamond nghiêng, vạch accent bên trái), nội dung (đoạn Montserrat, tiêu đề phụ Garamond, danh sách có đường kẻ mảnh và dấu thoi vàng; chữ đậm đầu dòng danh sách thành tên mục Garamond, chữ nghiêng ngay sau thành dòng phụ). Không có ảnh: một cột chữ rộng tối đa 52rem. Section tắt, hoặc không có tiêu đề nhỏ, nội dung và ảnh thì không in; số thứ tự đánh theo section đang hiện.
- Chế độ gọn: một cột, ảnh trên (rộng tối đa 420px; điện thoại xoay ngang 280px), không dính; mục lục xếp dọc.
- **Chuyển động** (`about.js`, ISHA.motion là phụ thuộc mềm): video nền hero gán nguồn theo khung (điện thoại dọc dùng bản dọc), không phát khi giảm chuyển động hoặc tiết kiệm dữ liệu, dừng khi tab ẩn; section chưa thấy lúc tải nhận `.is-pre` rồi hiện dần một lần khi vào khung nhìn (ảnh mở từ dưới lên bằng clip-path, khung vàng hiện sau, chữ so le 110ms); ảnh phóng 1,08 về 1 theo cuộn (`motion.scene`). Không JS: tĩnh, đọc được trọn vẹn; giảm chuyển động: main.css tắt mọi animation và transition, about.js không gắn `.is-pre`.

### 16.4 Dữ liệu đã ghi (DB `isha`, 17/11/2026)

Script chạy thử rồi mới ghi; chạy lại báo 0 thay đổi. Chỉ thêm hoặc sửa, không xoá meta nào:

1. Trang 11 "Giới thiệu" (mẫu `templates/template-about.php`, đang ở thùng rác từ 11/11/2026, nội dung là đoạn giữ chỗ): khôi phục, xuất bản, slug `gioi-thieu` (`/gioi-thieu/`).
2. Chép nội dung cũ của trang chủ sang trang 11 (meta của trang chủ giữ nguyên): `_isha_about_story` (tiêu đề nhỏ "Vẻ đẹp của mỗi người là một dấu ấn riêng.", nội dung là đoạn chính và đoạn phụ của Câu chuyện), `_isha_about_philosophy` (tiêu đề nhỏ là câu dẫn của Triết lý; nội dung là danh sách bốn chữ cái I, S, H, A và mục "Sứ mệnh" lấy đoạn Tầm nhìn cũ), `_isha_about_values` (tiêu đề nhỏ "Năm giá trị định hình mỗi trải nghiệm tại ISHA."; nội dung là danh sách năm giá trị). Ảnh tạm lấy từ bộ ảnh Giá trị cũ: story 233, philosophy 230, values 238.
3. Tác dụng phụ của WordPress: hai menu bật "Tự động thêm trang mới" (Menu chính, Chính sách khách hàng thân thiết) nhận mục "Giới thiệu" khi trang được xuất bản. Giữ mục ở **Menu chính** (đứng cuối, sắp lại ở bước menu); đã gỡ mục ở menu "Chính sách khách hàng thân thiết" (về đúng một mục như trước).

Khôi phục (nếu cần): chuyển trang 11 về thùng rác (meta `_isha_about_*` đi theo trang), gỡ mục "Giới thiệu" ở Giao diện › Trình đơn; hoặc nạp lại từ `isha_backup`.

### 16.5 Kiểm thử đã làm

- `php -l` mọi tệp PHP mới/sửa; `node --check about.js`.
- HTTP `/gioi-thieu/`: 200, một h1, ba section đúng thứ tự, tông và bố cục (ảnh trái, phải, trái), cảnh kết ở cuối, tài nguyên home.css, home.js, about.css, about.js đều nạp; không có lỗi mới trong log Apache.
- Chrome headless: desktop 1440×900, điện thoại 390×844 (dpr 2), giảm chuyển động. Không lỗi JS hay console, không tràn ngang; hiện dần đúng (section đã thấy lúc tải không bị ẩn, mọi section hiện sau khi cuộn); ảnh dính khi cột chữ dài; giảm chuyển động: không animation, không phóng ảnh, không `.is-pre`.
- Quản trị (PHP CLI, quản trị viên giả lập): `applies()`, `page_id()` = 11, "Hero banner" áp dụng, block editor chỉ tắt ở trang 11; ba khung, ba trình soạn thảo, ba ô ảnh; vòng lưu thử (gửi lại đúng dữ liệu đang có qua `save()` với nonce thật) giữ nguyên dữ liệu; lọc nội dung bỏ `<script>`, thuộc tính `on*`, `<img>`, giao thức `javascript:`; menu "Giới thiệu" ở vị trí 20.6.

### 16.6 Còn lại

- Template, khối CSS và khối JS cũ của story, philosophy, values trong `home.css`/`home.js` không còn được dùng (trang Giới thiệu dùng thiết kế mới): khoảng 10,3 KB JS (ngân sách trang chủ sẽ còn khoảng 92,5 KB) và 22,8 KB CSS; gỡ khi rà soát tối ưu (theo quyết định ngày 17/11/2026). Mặc định của ba section vẫn ở `isha_home_defaults()`, meta cũ vẫn ở trang chủ.
- Nội dung chờ khách: câu chữ chính thức cho ba section (tiêu đề nhỏ của Câu chuyện và Giá trị hiện do trợ lý đặt từ nội dung cũ), ảnh thật cho ba section, mô tả và ảnh/video nền hero.
- Thứ tự mục "Giới thiệu" trong menu chính (hiện đứng cuối) làm cùng bước menu và đường dẫn.

## 17. Giai đoạn 2: chi tiết Tin tức và Sự kiện, Liên hệ, popup Đặt lịch (18/11/2026)

Mục này bổ sung các mục trước. Quyết định áp dụng (17/11/2026): popup lấy mã bảo mật mới lúc mở và lúc gửi; ô đồng ý xử lý dữ liệu cá nhân và trang Chính sách bảo mật; cấu hình SMTP để sau bàn giao. Trường của form chưa được chốt (người dùng để sau), dùng bộ trường dưới đây; đổi chủ đề liên hệ bằng bộ lọc `isha_core/contact_topics`, khung giờ bằng `isha_core/booking_slots`.

### 17.1 Tệp

| Tệp | Việc |
| --- | --- |
| `plugins/isha-core/includes/class-forms.php` (mới) | `ISHA_Core_Forms`: AJAX `isha_form_nonce` trả nonce mới của mọi form (không cache), giới hạn tần suất theo IP băm cùng salt, kiểm ô đồng ý, `privacy_url()`, `phone()`, `notify()` gửi mail báo phòng khám, bong bóng số thư mới (trạng thái `new`) cạnh menu Lịch hẹn và Liên hệ |
| `plugins/isha-core/includes/class-booking.php` (viết lại phần xử lý) | `process()` dùng chung cho admin-post (không JS, chuyển hướng `?isha_booking=`) và AJAX `isha_booking` (JSON `status`, `message`, `errors` theo từng ô); khung giờ 09:00 đến 18:00 mỗi 30 phút (giờ mở cửa 09:00 – 19:00; trước đó 08:00 – 17:30); thêm ô đồng ý bắt buộc, meta `_isha_consent_at`, `_isha_source` (popup/page); meta box hiện thêm hai dòng này |
| `plugins/isha-core/includes/class-contact.php` (mới) | `ISHA_Core_Contact`: CPT `isha_contact` (hộp thư "Liên hệ", menu 29, không có trang ngoài website, không tạo tay), `process()` cho admin-post và AJAX `isha_contact`, mail báo, cột (người gửi, điện thoại, email, chủ đề, trạng thái), meta box chi tiết có nút Gọi lại, Trả lời email và trạng thái (Mới, Đã liên hệ lại, Đã xong) |
| `plugins/isha-core/includes/class-shortcodes.php` | Shortcode cũ `[isha_booking_form]` thêm ô đồng ý (để vẫn gửi được) |
| `themes/ISHA/inc/forms.php` (mới) | `isha_forms_booking_url()`, `isha_forms_contact_info()`, `isha_forms_service_groups()`, `isha_forms_field()`, `isha_forms_consent_label()`, in popup ở `wp_footer`, nạp `booking.css`/`booking.js` (mọi trang có popup hoặc form) và `home.css` + `contact.css` (trang Liên hệ, Đặt lịch hẹn) |
| `themes/ISHA/template-parts/forms/booking-form.php`, `contact-form.php`, `booking-dialog.php` (mới) | Hai form và popup |
| `themes/ISHA/templates/template-contact.php`, `template-booking.php` (viết lại) + `template-parts/contact/page.php` (mới) | Trang Liên hệ và Đặt lịch hẹn, cùng bố cục |
| `themes/ISHA/inc/post-single.php`, `template-parts/post-single.php`, `single-isha_article.php`, `single-isha_event.php` (mới) | Trang chi tiết Tin tức và Sự kiện |
| `themes/ISHA/inc/helpers.php` | `isha_is_inner_page()` tính cả chi tiết Tin tức, Sự kiện |
| `themes/ISHA/assets/css/booking.css` (khoảng 15 KB), `contact.css` (7 KB), `post-single.css` (13 KB), `assets/js/booking.js` (8,7 KB) | Giao diện và hành vi |

### 17.2 Popup Đặt lịch

- `dialog#isha-book` in ở chân MỌI trang phía khách khi có trang dùng mẫu "Đặt lịch hẹn". `booking.js` chặn cú bấm (chuột trái, không phím bổ trợ, không `target`) vào mọi liên kết cùng tên miền có đường dẫn trùng trang Đặt lịch hẹn (header, hero, dock, nút trong nhóm dịch vụ, cảnh kết, nút trong trang Elementor) và mọi `[data-isha-booking]` (giá trị là ID dịch vụ, tuỳ chọn), rồi mở popup bằng `showModal()`; `?service=ID` chọn sẵn dịch vụ. Đang ở chính trang Đặt lịch hẹn thì không chặn. Không JS, thiếu `fetch`/`<dialog>`: liên kết mở trang Đặt lịch hẹn như cũ.
- Mở: khoá cuộn nền (`html.isha-book-open`), focus ô Họ và tên, lấy nonce trước. Đóng: nút X, Esc (native), bấm vùng tối quanh khung; trả focus về nút đã mở. Gửi xong: thay form bằng khung cảm ơn (focus vào khung).
- Bố cục: desktop hai cột flex (trái tối có vòng mặt trời vàng kim, lời dẫn, ba ý chính, hotline, giờ làm việc, địa chỉ; phải là form tự cuộn khi cao hơn màn hình). Màn hình thấp hơn 820px bỏ ba ý chính. Điện thoại (≤ 780px): phủ kín màn hình, cột trái thu thành phần đầu.

### 17.3 Form (đặt lịch, liên hệ)

- Trường đặt lịch: Họ và tên*, Số điện thoại*, Email, Dịch vụ quan tâm (nhóm theo nhóm dịch vụ), Ngày hẹn* (từ hôm nay), Giờ hẹn*, Ghi chú, ô đồng ý*. Trường liên hệ: Họ và tên*, Số điện thoại*, Email, Chủ đề (Tư vấn liệu trình, Chính sách khách hàng thành viên, Góp ý, Hợp tác, Khác), Lời nhắn* (10 đến 1500 ký tự), ô đồng ý*.
- Có JS: gửi AJAX, nonce lấy mới qua `isha_form_nonce` (nhớ 10 phút; lỗi phiên thì lấy lại), kiểm tra ở máy chủ, lỗi hiện dưới từng ô (`aria-invalid`, `aria-describedby`), focus ô sai đầu tiên, câu báo chung ở vùng `role="status"`. Không JS: gửi thường tới admin-post.php (form ở trang có nonce in sẵn), quay lại kèm câu báo.
- Chống spam: ô bẫy ẩn, nonce, tối đa 5 lần gửi thành công mỗi giờ mỗi IP (mỗi form).
- Mail báo phòng khám: tới "Email nhận thông báo" ở ISHA Clinic (trống thì email quản trị), Reply-To là email khách. Chưa cấu hình SMTP nên trên XAMPP mail không đi; dữ liệu vẫn được lưu.

### 17.4 Trang Liên hệ và Đặt lịch hẹn

Cùng bố cục (`template-parts/contact/page.php`), nạp `home.css`, `booking.css`, `contact.css` (không cần `home.js`): mở đầu tông day, cột trái (tên website, tiêu đề h1 mặt nạ trượt lên, vạch vàng, lời dẫn là nội dung trang, thông tin: địa chỉ có liên kết Chỉ đường, hotline, email, giờ làm việc, mạng xã hội; nút Đặt lịch tư vấn mở popup hoặc Gọi hotline, Nhắn Zalo khi có), cột phải là thẻ form trắng trong khung vàng kim lệch; dưới là bản đồ (link nhúng ở ISHA Clinic) trong khung vàng, ảnh bản đồ giảm bão hoà cho hợp tông.

### 17.5 Trang chi tiết Tin tức và Sự kiện

`single-isha_article.php`, `single-isha_event.php` gọi `template-parts/post-single.php` với `isha_post_single_data()`; nạp tài nguyên trang chủ (cảnh kết ở cuối cần `home.js`) và `post-single.css`.

- Mở đầu (day-warm, căn giữa): liên kết quay lại trang danh sách (Tin tức: `isha_home_data_news_url()`; Sự kiện: trang slug `danh-sach-su-kien`), chủ đề hoặc loại sự kiện kèm nhãn Sắp diễn ra / Đã diễn ra, tiêu đề h1, tóm tắt (trường Nội dung ngắn, rồi Excerpt), dòng thông tin (Tin tức: ngày, tác giả, số phút đọc tính từ số từ / 200; Sự kiện: thứ ngày, giờ, địa điểm), ảnh bìa rộng (Tin tức 3:2, Sự kiện 2.39:1) trong khung vàng lệch.
- Nội dung (day): bài viết 36em bên trái; thẻ dính bên phải. Sự kiện: thời gian, địa điểm, hình thức; còn diễn ra thì nút Đăng ký tham dự (link đăng ký; ngoài website thì mở thẻ mới) hoặc Gọi đăng ký, kèm Đặt lịch tư vấn riêng; đã diễn ra thì mời xem sự kiện sắp tới. Tin tức: lời mời đặt lịch tư vấn và gọi hotline.
- Liên quan (day-warm): ba thẻ ảnh 4:5 (Tin tức cùng chủ đề rồi mới nhất; Sự kiện sắp diễn ra gần nhất rồi đã qua gần nhất), cả thẻ bấm được qua liên kết ở tiêu đề. Chế độ gọn: hàng vuốt ngang.
- Kết trang: cảnh kết `[isha_home_cta]`.

### 17.6 Dữ liệu đã ghi (DB `isha`, 18/11/2026)

Chỉ thêm hoặc sửa, giá trị cũ sao lưu ở scratchpad (tạm):

1. Trang 3 "Chính sách bảo mật" (đang ở thùng rác, vốn là trang quyền riêng tư của Cài đặt › Quyền riêng tư): khôi phục, xuất bản, slug `chinh-sach-bao-mat`, nội dung soạn theo Nghị định 13/2023/NĐ-CP (dữ liệu thu thập, mục đích, lưu trữ 24 tháng, chia sẻ, quyền, bảo mật, liên hệ). Lần này tắt cơ chế tự thêm trang vào menu khi xuất bản: không có mục menu mới.
2. Lời dẫn của trang Liên hệ (16) và Đặt lịch hẹn (14).
3. Nội dung mẫu khớp địa chỉ thật ở Cần Thơ (cài đặt ISHA Clinic): mô tả hero và mốc 2024 của trang Giới thiệu (trước là TP.HCM), địa điểm của 5 sự kiện mẫu.
4. Dữ liệu thử của QA (2 lịch hẹn, 1 lời nhắn) và bộ đếm giới hạn tần suất của máy thử đã xoá sau khi kiểm.

### 17.7 Kiểm thử đã làm

- `php -l` mọi tệp PHP, `node --check booking.js`; mọi tệp LF.
- HTTP: 8 trang chính 200, popup có ở mọi trang, không lỗi mới trong log Apache.
- AJAX (curl): dữ liệu sai trả lỗi đủ 6 ô; dữ liệu đúng lưu lịch hẹn và lời nhắn kèm thời điểm đồng ý; nonce sai bị chặn (`error`).
- Chrome headless: mở popup từ nút Đặt lịch của header (focus ô họ tên, khoá cuộn), gửi trống (5 ô lỗi, focus ô đầu), gửi đúng (lưu đúng tiếng Việt, khung cảm ơn), đóng trả focus và gỡ khoá cuộn; mở từ trang liệu trình chọn sẵn đúng dịch vụ; laptop 1366×768 cột form cuộn được; điện thoại phủ kín màn hình; trang Liên hệ desktop và điện thoại, chi tiết Tin tức và Sự kiện (sắp diễn ra, đã diễn ra): một h1, không tràn ngang, không lỗi JS.
- Quản trị (PHP CLI): bong bóng số thư mới ở Lịch hẹn và Liên hệ, meta box Liên hệ.

### 17.8 Lưu ý

- **Ngân sách JS:** `home.js` + `isha-motion.js` không đổi (102.803 byte). `booking.js` (8.657 byte) nạp ở mọi trang kể cả trang chủ; tính chung thì trang chủ 111.460 byte, vượt trần 110.000 khoảng 1,5 KB. Gỡ khối JS không còn dùng của story, philosophy, values (khoảng 10,3 KB, mục 16.6) thì về khoảng 101 KB.
- Bản đồ nhúng (link ở ISHA Clinic) trỏ tới "143 Phạm Ngũ Lão, Ninh Kiều, Cần Thơ", trong khi ô Địa chỉ ghi "77-79 Nguyễn Văn Cừ, P. Cái Khế, Cần Thơ": cần khách xác nhận địa chỉ đúng.
- Nội dung trang Chính sách bảo mật là bản soạn mẫu, cần pháp chế duyệt trước khi ra mắt.

### 17.9 Quyết định sau giai đoạn 2 (18/11/2026)

- **Ngân sách JS nâng thêm 50 KB: 160 KB** chưa nén (thay cho mức 110 KB ở mục 11.1). Tính chung mọi script của trang chủ: `home.js` + `isha-motion.js` + `booking.js`.
- **Đã gỡ khối JS của story và philosophy** khỏi `home.js` (7.984 byte; không hàm dùng chung nào thành thừa). `home.js` còn 72.570 byte; tổng JS trang chủ 72.570 + 22.069 + 8.657 = **103.296 byte** trên 160.000. Khối JS của values (khoảng 2,1 KB), template và CSS của ba section cũ vẫn còn (người dùng chỉ chọn gỡ JS của Câu chuyện, Triết lý).
- **Địa chỉ chốt: 77-79 Nguyễn Văn Cừ, P. Cái Khế, Cần Thơ.** Ô Địa chỉ ở ISHA Clinic chuẩn hoá dấu câu; bản đồ nhúng đổi sang truy vấn theo địa chỉ này (`https://www.google.com/maps?q=…&output=embed`, không cần khoá API; trước đó trỏ tới 143 Phạm Ngũ Lão); cập nhật theo ở địa điểm 5 sự kiện mẫu, trang Chính sách bảo mật và mốc 2024 của trang Giới thiệu.

## 18. Giai đoạn 3: Không gian ISHA, đường dẫn Tin tức, trang Dịch vụ, nội dung 17 liệu trình (18/11/2026)

Quyết định áp dụng (17/11/2026): Không gian ISHA là một trang trên menu có mẫu riêng, mỗi khu là một bài có ô "Xuất hiện bài này ở trang chủ" quyết định nội dung section Không gian của trang chủ; gộp đường dẫn Tin tức `/bai-viet/` về `/tin-tuc/` kèm 301; trang Dịch vụ theo thứ tự Hero banner, vùng Elementor, lưới 6 nhóm dịch vụ lấy tự động; nội dung mẫu là thông tin giả lập theo chuyên ngành, không dùng chữ giữ chỗ.

### 18.1 Tệp

| Tệp | Việc |
| --- | --- |
| `plugins/isha-core/includes/class-post-types.php` | CPT `isha_space` ("Không gian ISHA": tiêu đề, nội dung, ảnh đại diện, thứ tự; `/khong-gian-isha/ten-bai/`, không có archive). `isha_article` đổi slug `bai-viet` → `tin-tuc`, `has_archive` false |
| `plugins/isha-core/includes/class-space-fields.php` (mới) | `ISHA_Core_Space_Fields`: meta box "Thông tin không gian" (ô "Xuất hiện bài này ở trang chủ" `_isha_space_home`, Tiêu đề nhỏ `_isha_space_subtitle` ≤ 120, Mô tả ngắn `_isha_space_summary` ≤ 400, Ảnh không gian `_isha_space_gallery` ≤ 12); `get()`, `posts( $home_only )` (menu_order rồi tiêu đề; trang chủ tối đa 12); cột Trang chủ, Thứ tự; danh sách admin sắp theo thứ tự; mục menu admin dời về 20.7 (dưới Giới thiệu) bằng cách đổi khoá trong `$menu` vì `menu_position` của CPT chỉ nhận số nguyên |
| `plugins/isha-core/includes/class-legacy-urls.php` (mới) | `ISHA_Core_Legacy_Urls`: ở `template_redirect` ưu tiên 1, chỉ khi 404: `/bai-viet/` → trang Tin tức, `/bai-viet/page/N/` → `?trang=N`, `/bai-viet/ten-bai/…` → `/tin-tuc/ten-bai/` (bài còn xuất bản, không thì trang Tin tức). Bộ lọc `isha_core/news_url` |
| `plugins/isha-core/includes/class-page-hero-fields.php` | Meta box "Hero banner" áp cho cả mẫu `templates/template-space.php` |
| `plugins/isha-core/includes/home-schema.php` | Thẻ Không gian ISHA: thêm "Chữ liên kết tới trang Không gian ISHA" (`link_label`), ô gallery thành "Ảnh dự phòng" |
| `plugins/isha-core/isha-core.php` | `ISHA_CORE_REWRITE_VERSION` 2 → 3 (tự flush một lần) |
| `themes/ISHA/template-parts/about/hero.php`, `section.php` (mới) | Hero và section theo phong cách trang Giới thiệu, tách khỏi `about/page.php` để trang Không gian dùng chung. HTML trang Giới thiệu sau khi tách trùng khớp từng ký tự với bản trước. `section.php` nhận thêm `title_url`, `link` (kèm phần chỉ trình đọc màn hình đọc), `filtered` (nội dung đã qua `the_content`), `label` |
| `themes/ISHA/inc/about.php` | Tách `isha_about_hero_data()` (hero từ meta box Hero banner) và `isha_about_enqueue()` (home.css, about.css, about.js) để dùng chung |
| `themes/ISHA/inc/space.php` (mới) | `isha_space_list_page()`, `isha_space_list_url()`, `isha_space_posts()`, `isha_space_item()`, `isha_space_list_data()`, `isha_space_single_data()`, nạp `space.css` |
| `themes/ISHA/templates/template-space.php`, `single-isha_space.php`, `template-parts/space/list.php`, `single.php` (mới) | Trang danh sách và trang chi tiết |
| `themes/ISHA/assets/css/space.css` (mới, 6,4 KB) | Lưới ảnh so le, thẻ "Không gian khác" |
| `themes/ISHA/assets/css/about.css` | Kiểu cho nhãn hero là liên kết, tiêu đề section là liên kết, liên kết hành động, `h2` trong nội dung |
| `themes/ISHA/inc/home/data.php`, `template-parts/home/space.php`, `home.css`, `home.js` | Section Không gian lấy bài được đánh dấu; mỗi ảnh là liên kết tới bài; liên kết tới trang Không gian dưới dải ảnh. Bản sao ảnh của dải vòng (desktop) không còn `inert` mà đặt `tabindex="-1"` cho liên kết, nên bấm chuột vào ảnh sao vẫn mở bài |
| `themes/ISHA/inc/editorial-list.php` | `term_link` của Chủ đề Tin tức và Loại sự kiện trỏ thẳng tới danh sách đã lọc (`?chuyen-muc=slug`); trang lưu trữ `/chu-de/…`, `/loai-su-kien/…` chuyển 301 về đó |
| `themes/ISHA/inc/services-hero.php`, `template-parts/services-page.php`, `template-parts/services-groups.php` (mới), `assets/css/services-groups.css` (mới, 6 KB) | Lưới nhóm dịch vụ sau vùng Elementor của trang Dịch vụ. `isha_services_hero_groups()` trả thêm `desc` (mô tả nhóm), `count` (số liệu trình) |
| `themes/ISHA/inc/helpers.php`, `functions.php` | `isha_is_inner_page()` tính cả bài Không gian; nạp `inc/space.php` |

### 18.2 Không gian ISHA

- **Trang danh sách** (`/khong-gian-isha/`, mẫu "Không gian ISHA"): hero tông night (tên trang, mô tả và ảnh hoặc video nền từ meta box Hero banner; mục lục tới từng không gian khi có từ 2 bài), rồi mỗi bài một section kiểu Founder: ảnh đại diện 4:5 xen kẽ trái phải trong khung vàng kim lệch, số thứ tự, tên bài là liên kết, tiêu đề nhỏ, mô tả ngắn, liên kết "Khám phá không gian". Tông section lặp day, day-warm, afternoon. Nội dung soạn trong Trang không hiển thị. Kết trang là cảnh kết `[isha_home_cta]`.
- **Trang chi tiết** (`/khong-gian-isha/ten-bai/`): hero (nhãn là liên kết về trang danh sách, tên bài, tiêu đề nhỏ; ảnh nền là ảnh ngang đầu tiên của bộ ảnh có cạnh ngang từ 1600 px, không có thì nền tối với mặt trời), section giới thiệu (ảnh đại diện, mô tả ngắn nghiêng, nội dung bài), "Hình ảnh không gian" (ảnh còn lại của bộ, hai cột so le, khung 3:2 hoặc 4:5 theo hướng ảnh gốc, chú thích từ Thư viện phương tiện; một cột ở màn hình ≤ 700 px), "Không gian khác" (3 bài kế tiếp theo thứ tự, vòng về đầu), cảnh kết.
- Ảnh đại diện thiếu thì dùng ảnh đầu tiên của bộ ảnh. Lớp bao trang là `isha-space-page` (không dùng `isha-space` vì trùng lớp của section trang chủ).
- **Section trang chủ:** bài có ô "Xuất hiện bài này ở trang chủ", theo thứ tự, tối đa 12; mỗi mục là ảnh đại diện (trang trí, `alt` rỗng) và tên bài làm chú thích, cả khối là một liên kết. Chưa bài nào được đánh dấu thì dùng "Ảnh dự phòng" như trước (không có liên kết).

### 18.3 Đường dẫn

| Cũ | Mới |
| --- | --- |
| `/bai-viet/` | `/tin-tuc/` (301) |
| `/bai-viet/ten-bai/` | `/tin-tuc/ten-bai/` (301) |
| `/bai-viet/page/N/` | `/tin-tuc/?trang=N` (301) |
| `/chu-de/slug/` | `/tin-tuc/?chuyen-muc=slug` (301; liên kết chủ đề trong trang cũng trỏ thẳng tới đây) |
| `/loai-su-kien/slug/` | `/danh-sach-su-kien/?chuyen-muc=slug` (301) |

Trang Tin tức (trang 15) vẫn là "Trang bài viết" ở Cài đặt › Đọc và phân trang bằng `?trang=` nên không đụng quy tắc `tin-tuc/([^/]+)` của bài. DB không còn chỗ nào chứa `/bai-viet/`. Đường dẫn không tồn tại dưới `/tin-tuc/` trả 404 bình thường.

### 18.4 Trang Dịch vụ

Thứ tự: hero nan quạt, vùng nội dung (Elementor), **"Các nhóm dịch vụ"**, CTA đặt lịch. Lưới lấy mọi nhóm theo thứ tự hiển thị ở Nhóm dịch vụ: ba cột ở desktop (cột giữa hạ thấp), hai cột ở máy tính bảng, một cột (tối đa 420 px) ở điện thoại. Thẻ: ảnh bìa 4:5 trong khung vàng kim lệch, số thứ tự, tên nhóm, mô tả nhóm (nghiêng), số liệu trình; cả thẻ là liên kết tới trang nhóm. Trang này không nạp `home.css` nên `services-groups.css` chỉ dùng token của `main.css` (gradient vàng kim có giá trị dự phòng).

### 18.5 Dữ liệu đã ghi (DB `isha`, 18/11/2026)

Giá trị cũ sao lưu ở scratchpad (tạm):

1. Trang 308 "Không gian ISHA" (mẫu Không gian ISHA, mô tả hero). Năm bài Không gian 309 đến 313: Sảnh đón tiếp, Phòng trị liệu đôi, Phòng VIP riêng tư, Khu điều trị công nghệ cao, Phòng thư giãn sau liệu trình (thứ tự 10 đến 50); bốn bài hiện ở trang chủ, trừ Khu điều trị công nghệ cao. Ảnh lấy từ Thư viện (4 ảnh nội thất 174, 239, 240, 241 và ảnh trị liệu): **cần thay bằng ảnh thật của phòng khám**.
2. Chú thích và Văn bản thay thế cho 10 ảnh được dùng (171, 172, 173, 174, 230, 236, 238, 239, 240, 241; trước đó trống, riêng 173 có chú thích "Evoto").
3. Menu chính: Trang chủ, Giới thiệu, Không gian ISHA, Dịch vụ, Tin tức, Sự kiện, Chính sách, Liên hệ (thêm mục 314, Giới thiệu chuyển từ cuối lên thứ hai).
4. 17 liệu trình: nội dung bài (giới thiệu, Phù hợp với, Hiệu quả mong đợi, Lưu ý) và các bước (5 đến 6 bước thật thay cho "Bước 1/2/3"). Thời gian, mô tả ngắn, ảnh, nhóm giữ nguyên. **Bác sĩ cần duyệt chuyên môn trước khi ra mắt.**

### 18.6 Kiểm thử đã làm

- `php -l` mọi tệp PHP, `node --check home.js`; mọi tệp LF.
- HTTP: 15 trang 200 (trang không tồn tại 404); 6 đường dẫn cũ chuyển 301 đúng đích (bảng 18.3).
- Chrome headless, desktop 1440×900 và điện thoại 390×844: trang chủ, Giới thiệu, Không gian (danh sách, chi tiết), Dịch vụ, nhóm dịch vụ, chi tiết liệu trình, Tin tức (danh sách, chi tiết), Sự kiện, Chính sách khách hàng, Liên hệ, Đặt lịch hẹn, Chính sách bảo mật: không lỗi JS, không cuộn ngang. Trang Chính sách khách hàng báo `scrollWidth` 1540 ở desktop do chồng thẻ nghiêng, nhưng `body` đã `overflow-x: clip` nên không cuộn được; có từ trước giai đoạn này.
- Section Không gian ở trang chủ: 4 bài, dải vòng chạy, nút Sau chuyển ảnh, liên kết "Khám phá không gian ISHA" đúng trang.
- Quản trị (PHP CLI): meta box hiện đúng giá trị, lưu và bỏ ô trang chủ, `posts( true )` đổi theo, menu admin đúng thứ tự, cột Trang chủ và Thứ tự.

### 18.7 Ngân sách và lưu ý

- **JS trang chủ:** `home.js` 72.745 + `isha-motion.js` 22.069 + `booking.js` 8.657 = **103.471 byte** trên 160.000 (thêm 175 byte cho bản sao ảnh bấm được). Trang Không gian dùng thêm `about.js` (4,7 KB) như trang Giới thiệu.
- Section Không gian của trang chủ thôi đọc ô gallery khi đã có bài được đánh dấu; bộ ảnh cũ vẫn nằm trong ô "Ảnh dự phòng".
- Khối JS của values (khoảng 2,1 KB), template và CSS của story, philosophy, values ở trang chủ vẫn còn (mục 17.9).

## 19. Giai đoạn 4: rà soát phần thừa, bộ kiểm hồi quy mới (18/11/2026)

Ba giai đoạn trong kế hoạch ban đầu đã xong. Giai đoạn này làm hai việc người dùng đã đồng ý từ đầu (17/11/2026): rà soát loại bỏ phần thừa để tối ưu (rủi ro 7) và làm lại bộ kiểm hồi quy đã lỗi thời (rủi ro 9).

### 19.1 Đã gỡ

| Phần | Lý do | Tiết kiệm |
| --- | --- | --- |
| `template-parts/home/story.php`, `philosophy.php` | Không còn trong registry, không shortcode, không nội dung nào gọi (đã kiểm DB) | 2 tệp |
| Khối `story`, `philosophy` trong `home.css` | Chỉ chứa quy tắc của chính hai section (đã kiểm từng selector, không keyframes dùng chung) | 12.674 byte; `home.css` 154.612 → 140.861 byte (tính cả phần thêm ở giai đoạn 3) |
| Mặc định `story`, `philosophy` trong `isha_home_defaults()` | Không còn nơi đọc | |
| `template-parts/home/values.php`, khối `values` trong `home.css` và `home.js`, mặc định `values` (gỡ sau khi chủ dự án đồng ý, cùng ngày) | Không còn trong registry; khối CSS chỉ chứa quy tắc của chính nó; khối JS chỉ dùng hàm chung vẫn còn nơi khác gọi | CSS 9.064 byte (`home.css` còn 131.797), JS 2.099 byte (`home.js` còn 70.646) |

Ban đầu giữ phần Giá trị cốt lõi theo lựa chọn ở mục 17.9 (chỉ gỡ Câu chuyện, Triết lý); chủ dự án đồng ý gỡ nốt cùng ngày. Trang chủ không còn mã của ba section cũ. **Ngân sách JS trang chủ:** `home.js` 70.646 + `isha-motion.js` 22.069 + `booking.js` 8.657 = **101.372 byte** trên 160.000. Dữ liệu cũ trong meta trang chủ (`_isha_home_story`, `_philosophy`, `_values`, `_vision`) giữ nguyên theo quyết định sao chép, không xoá.

Các phần khác đã rà, **giữ lại** vì còn dùng hoặc là dự phòng có chủ đích: `page-tin-tuc.php` (mẫu cho trang slug `tin-tuc` khi trang đó không là "Trang bài viết"), `templates/template-policies.php` (danh sách chính sách tự động; website dùng trang Elementor ID 7 nhưng trình cài đặt mặc định cần mẫu này), `template-parts/home/intro.php`, `dock.php` (hero và thanh liên hệ nhanh dùng).

### 19.2 Đã sửa

- `inc/demo-content.php` (chỉ chạy khi kích hoạt theme trên website chưa có option `isha_default_content_installed`; website hiện tại và DB chuyển lên host không chạy lại): thêm trang Không gian ISHA (mẫu Không gian ISHA) và Danh sách sự kiện, menu mặc định theo thứ tự đã chốt.
- Ghi chú trong `service-group.css`, `inc/service-group.php`, `template-parts/service-group/intro.php` thôi trỏ tới `#story` (đã gỡ).

### 19.3 Bộ kiểm hồi quy mới

Ở `C:\xampp\isha-backups\2026-11-18_after-phase3-restructure\qa-regression\` (ngoài dự án, như bộ cũ; `README-QA.md` hướng dẫn). Chạy `node run.mjs` (khoảng 5 phút), chỉ đọc DB thật. Bốn nhóm: `static`, `php`, `http`, `browser` (mô tả ở `docs/home-handover.md` mục 8). Không dùng DB thử, không ghi cứng đường dẫn scratchpad, không cần Playwright; Chrome điều khiển qua DevTools Protocol bằng `lib/cdp.mjs`.

Kết quả lần chạy đủ (18/11/2026, 283 giây): **564 PASS, 3 FAIL, 1 WARN**. Tĩnh 7, PHP 122 (dữ liệu 109, quản trị 13), HTTP 16 (64 URL trong sitemap đều 200), trình duyệt 419 trên 422; 1 cảnh báo (chưa có ảnh trước/sau cho section Hiệu quả liệu trình) và **1 lỗi nội dung thật** (3 FAIL là cùng lỗi ở ba cỡ màn hình): bài Tin tức "Nền da khoẻ bắt đầu từ đâu?" (ID 199) được dựng lại bằng Elementor sáng 18/11/2026 và có widget tiêu đề cấp **H1** "Làn Da Đẹp", nên trang có hai `h1` (tiêu đề bài là `h1` của theme). Người dùng đã đổi widget sang H2; lần chạy lại đạt (mục 19.6).

Đã thử trên chính lỗi có thật: bài kiểm "mô tả nhóm không mất ký tự" bắt được lỗi `/s+/` của giai đoạn 3 (mục 18), bài "đúng một h1" bắt được lỗi trên.

### 19.4 Lưu ý về xuống dòng

Máy phát triển đặt `core.autocrlf=true`: git viết CRLF khi checkout, nên nhiều tệp cũ (card-ring, services-cinematic, vendor…) có CRLF ở bản làm việc trong khi git lưu LF. Đổi tay các tệp đó về LF chỉ làm `git status` báo sửa ảo (nội dung không đổi). Quy ước LF áp cho tệp mới và tệp đang sửa; bộ kiểm chỉ kiểm nội dung lưu trong git (`git ls-files --eol`, cột `i/`).

### 19.5 Sao lưu

`C:\xampp\isha-backups\2026-11-18_after-phase3-restructure\db.sql` (4 MB): DB thật sau giai đoạn 3, mã nguồn tương ứng là commit `19d8e8e`.

### 19.6 Chạy lại sau khi gỡ Giá trị cốt lõi và sửa H1 (18/11/2026)

`node run.mjs`: **567 PASS, 0 FAIL, 1 WARN** (353 giây). Cảnh báo duy nhất: section Hiệu quả liệu trình chưa có ảnh trước/sau (chờ khách). Bài "Nền da khoẻ bắt đầu từ đâu?" còn một `h1`.

## 20. Founder tối giản và cụm ảnh gallery (19/11/2026)

> Phần giao diện của mục này đã được thay bởi mục 21 (gallery 3D) trong cùng ngày; phần CMS (bỏ `signature`, thêm `gallery` tối đa 20) vẫn giữ.

- Template `template-parts/home/founder-story.php` chỉ còn tiêu đề và câu trích trong hai thẻ `div` không class (div tiêu đề mang id `isha-founder-story-title` cho `aria-labelledby`). Nền vẫn theo tông `afternoon` qua `isha_home_section_open()` (class `isha-sec`, `data-tone`), nên dải màu giữa các section không đứt. Câu trích in nguyên văn, không còn tự thêm dấu “ ”.
- Gỡ khối `founder-story` khỏi `home.css` (khoảng 145 dòng) và `home.js` (khoảng 55 dòng: dolly ảnh chân dung, chữ ký tự viết). Section không có module cảnh, `startScenes()` bỏ qua.
- CMS (`includes/home-schema.php`): bỏ trường `signature`; thêm `gallery` (kiểu gallery, `max` 20; không ràng buộc tỉ lệ hay kích thước ảnh). Mặc định ở `isha_home_defaults()`: `gallery => array()`. Giá trị `signature` cũ trong meta `_isha_home_founder-story` bị bỏ qua khi đọc và mất ở lần lưu kế tiếp.
- Các trường `portrait`, `story`, `name`, `role`, `gallery` vẫn lưu nhưng template chưa dùng.

## 21. Founder: gallery 3D chiều sâu (19/11/2026)

Đặc tả: `docs/founder-section-wp.md` (viết lại bản React Three Fiber `docs/founder-section.md` bằng three.js thuần). Các quyết định chốt với chủ dự án trước khi làm:

- **Nền:** ~~`#141414` chỉ là nền của khung gallery; section vẫn mang tông `afternoon`~~. Thay bởi đợt đánh bóng Quiet Luxury cùng ngày (cuối mục này): toàn section tông `noir` #141414 full-bleed.
- **Bố cục:** ngược Dịch vụ tiêu biểu: lưới 2fr | 1fr, khung gallery tràn tới mép trái màn hình, cột chữ bên phải thẳng mép phải `.isha-wrap` (tiêu đề h2, câu trích, câu chuyện). Chế độ gọn (≤ 900px hoặc cao ≤ 560px): chữ trên, khung dưới tràn hai mép.
- **Chu kỳ:** không lặp vô hạn. Tiến độ `pos` chạy 0..END với `END = N - 1 + LIFE / 2`; tới END là ảnh chân dung (HTML, kích thước cố định) hiện giữa khung, chức danh và tên hiện mờ dần bên dưới; lùi lại thì mờ đi. Sau ảnh cuối chỉ có một nhịp dừng (ảnh chân dung); các bước giữa là lúc ảnh cuối đi nốt ra mép.
- **Chuyển động toả ra từ tâm (điều chỉnh 19/11/2026, thay mô hình bay theo chiều sâu):** mỗi ảnh nhú ở TÂM khung rồi đi theo hướng riêng ra mép khung (mọi số trong `config.js`). Tiến độ đi của ảnh i: `t = 0,5 + (pos - i) / LIFE` (`t = 0` ở tâm, `t = 1` chạm mép; `pos = i` là ảnh "hiện hành": Enter mở Lightbox). **Tối đa 3 ảnh cùng lúc** (cập nhật 19/11/2026, trước là 8 desktop / 6 điện thoại): `LIFE = 3` ở cả desktop và điện thoại, chỉ ảnh có `t` trong [0, 1) mới hiện, nên `END = N - 1 + 1,5`. Đã kiểm bằng mô phỏng quét `pos` với N = 1, 2, 5, 11, 20: không bao giờ quá 3. Hướng đi = `SEED + i x 2,39996 (góc vàng) + lệch ngẫu nhiên +/-0,5 rad`: hai ảnh liền nhau luôn đi hai hướng rất khác nhau, cố định giữa các lần tải. Tâm ảnh chạy tới đúng mép khung theo hướng đó (`EDGE_REACH` 1,0), nhú chậm rồi nhanh dần (`PATH_EASE` 1,3).
  - **Kích thước (cạnh lớn nhất):** 150px ở tâm (120px điện thoại) tăng dần tới 50% chiều cao khung (không quá 62% chiều rộng) ở mép.
  - **Opacity:** 0,15 ở tâm, tăng mượt tới 1 khi đi được 40% quãng đường, giữ 1, từ 80% quãng đường mờ dần về 0 khi chạm mép (4% đầu opacity nhân thêm `t / 0,04` để không bật hình đột ngột). Ảnh mới nhú còn hoà thêm 30% vào màu nền và mờ nét nhẹ, hết dần khi tới 40% quãng đường.
  - **Bo góc và viền:** ảnh bo 8px (khoảng cách có dấu tới hình chữ nhật bo trong fragment shader); viền hover vàng 1,5px (trước là 1,25px) bám theo góc bo, kèm vệt ánh kim chạy quanh chu vi.
  - **Ảnh chân dung:** khung vàng ánh kim viền 1,5px bo 12px (`.isha-founder__frame::after`: dải `--metal-dark` đổ vào viền nhờ mask, vì `border-image` không đi cùng `border-radius`). Lưới ảnh tĩnh bo 8px, viền hover 1,5px.
  - Ảnh cũ nằm trên ảnh mới (`renderOrder` theo `t`); hit-test lấy ảnh trên cùng. Kéo hết bề ngang khung đi 3 ảnh (theo ngón tay 1:1).
- **Lăn chuột (desktop):** listener `wheel` chỉ gắn trên khung và **luôn thuộc về gallery** khi con trỏ ở trong khung: kể cả khi khung mới hiện một phần, và kể cả ở ảnh đầu / ảnh chân dung (chủ dự án yêu cầu bỏ hai quy tắc "Trả quyền cuộn ở hai đầu" và "Chỉ giữ cuộn khi section đã vào vị trí" vì trải nghiệm thực tế không tốt; đã gỡ `CAPTURE_VISIBLE`, `SCROLL_QUIET`, `EDGE_HOLD` và mã liên quan). Muốn cuộn trang bằng chuột thì đưa con trỏ ra ngoài khung (cột chữ bên phải, hai mép). Lăn ngang trackpad, Shift + lăn, kéo chuột ngang cũng điều khiển gallery.
- **Tốc độ chung:** tự trôi, lăn chuột và phím dùng cùng một tốc độ `AUTO_SPEED` = 0,9 ảnh/giây (khoảng 1,1 giây mỗi ảnh; trước là 0,3). Mỗi lần lăn chỉ dời ĐÍCH đi `WHEEL_TRAVEL` x px ảnh (0,0102 ảnh/px: 100px = 1,02 ảnh, dài hơn bản trước 2%), ảnh đi tới đích với tốc độ trần `AUTO_SPEED`, tăng và giảm tốc êm (`SEEK_ACCEL`); đích không cách vị trí hiện tại quá `QUEUE_MAX` = 2,5 ảnh. Home / End nhanh gấp 4 (`FAST_MULT`). Thả tay sau khi kéo: quán tính (`V_MAX` 1,8) rồi trượt tới nhịp gần nhất với cùng tốc độ.
- **Điện thoại:** vuốt ngang đi tới / lùi (vuốt sang trái = tới), vuốt dọc luôn cuộn trang (`touch-action: pan-y`, chốt hướng sau 10px). Chạm bắt đầu trong 16px sát mép trái bị bỏ qua (cử chỉ "Quay lại" của iOS Safari). Không có nút nhảy section.
- **Tự trôi:** sau 3 giây không tương tác (tính từ lúc khung vào khung nhìn), trôi 0,9 ảnh/giây (xem "Tốc độ chung") tới ảnh chân dung rồi dừng (WCAG 2.2.2: chuyển động tự dừng). Rê chuột lên ảnh thì hãm êm về 0.
- **Ảnh lệch tỉ lệ:** tỉ lệ hiển thị kẹp trong 2:3 .. 16:9, ngoài khoảng thì cắt mép đều hai bên (ghi chú ở trường "Cụm ảnh gallery" trong CMS).
- **Phím:** chỉ khi khung có focus (không nhận khi chỉ hover): mũi tên, Home / End, Enter mở ảnh đang ở vùng lấy nét; ở hai đầu mũi tên lên / xuống trả cho trang. Vùng `aria-live` đọc "Ảnh i trên N: alt".

Tệp:

- `inc/script-modules.php` (mới): danh sách thư viện tự host và `isha_register_vendor_module()` / `isha_vendor_dynamic_deps()`, đăng ký một lần mỗi request. `inc/services-cinematic.php` chuyển sang dùng chung (`isha_svc_vendor_modules()` giờ chỉ trả danh sách id).
- `inc/home/assets.php`: `wp_enqueue_script_module( 'isha-founder-gallery', .../founder-gallery/main.js )` với `three` là phụ thuộc `'import' => 'dynamic'`. Ngoài ngân sách JS 160 KB (như home-wheel).
- `template-parts/home/founder-story.php`: viết lại. Khung có hai trạng thái: tĩnh (mặc định: chân dung, chức danh, tên, lưới ảnh `medium_large`, mỗi ảnh là liên kết tới ảnh gốc) và `.is-3d`. Dữ liệu WebGL trong `<script type="application/json" class="isha-founder__data">`: `src` (texture desktop: `isha-strip` 1600×1100 không cắt, thiếu thì `large` rồi `full`), `lite` (điện thoại: `large`), `full` (Lightbox), `w`/`h` gốc, `alt` (trống thì "Ảnh i trong thư viện của nhà sáng lập"). Không thêm `add_image_size` mới: `isha-strip` đã đáp ứng (cạnh dài ≤ 1600, giữ tỉ lệ). Lightbox là thẻ `<dialog class="isha-founder-lb">`.
- `assets/js/founder-gallery/`: `config.js` (mọi hằng số tinh chỉnh, có chú thích), `main.js` (entry nhẹ: Lightbox cho lưới, quyết định 3D, gỡ `src` ảnh lưới để không tải trùng, `import('./gallery.js')` khi khung còn cách 900px), `gallery.js` (three.js: cảnh, texture theo cửa sổ, vòng rAF, điều khiển), `shader.js` (uốn vải và làm mờ chiều sâu tiết chế, viền vàng + vệt ánh kim khi hover), `lightbox.js` (`showModal()`: giữ focus, Esc, bấm vùng tối để đóng, trả focus, khoá cuộn bằng `html.isha-lb-open`).
- `assets/css/home.css` khối `founder-story`: bố cục, khung, bản tĩnh, bản 3D (HUD gợi ý thao tác và vạch tiến độ vàng; đã bỏ bộ đếm số ảnh "01 / 11" ở góc phải), Lightbox.
- `assets/js/founder-gallery/dust.js` (mới, 19/11/2026): hạt bụi ánh sáng quanh ảnh chân dung. Một `THREE.Points` cộng sáng (additive), chuyển động tính hết trong vertex shader theo thời gian (CPU không cập nhật từng hạt): 120 hạt (48 điện thoại) quay chậm quanh ellipse ôm khung chân dung (mỗi hạt một chiều, tốc độ, bán kính), phập phồng, trôi lên theo nhịp 60px và hiện / tắt dần mỗi nhịp, nhấp nháy theo pha riêng; cỡ 3,5–9px gồm lõi sáng và quầng mềm, khoảng 1/8 số hạt là bokeh to gấp 3 và mờ hơn; màu ánh kim `#fcf6ba`, một phần ngả vàng ấm `#f0c46a`. Hiện theo mức hiện của ảnh chân dung (`--fp`), ẩn hẳn (không vẽ) khi chưa tới. Hạt nằm trong canvas, dưới ảnh chân dung HTML nên chỉ thấy phần quanh khung. Giảm chuyển động hoặc không WebGL: không có hạt (bản tĩnh).
- Plugin `includes/home-schema.php`: ghi chú thẻ Founder, trường chân dung và trường gallery (thứ tự hiển thị, ảnh bị cắt khi ngoài 2:3..16:9).

Hiệu năng và vòng đời:

- three.js (r187, `three.core.js` 1,5 MB + `three.module.js` 0,68 MB chưa nén) chỉ tải khi khung sắp vào khung nhìn, không giảm chuyển động và có WebGL2; trang nhóm dịch vụ dùng cùng tệp nên trình duyệt chỉ tải một lần.
- rAF riêng, không đụng vòng của `isha-motion.js`; chỉ chạy khi khung trong khung nhìn, tab đang hiện và Lightbox đóng; khi đứng yên không vẽ lại.
- Texture tải 3 ảnh song song, ưu tiên ảnh gần vùng lấy nét, giải mã trước (`img.decode()`), đưa lên GPU một ảnh mỗi khung hình; ảnh đã đi hết đường (hoặc chưa tới) quá 2 ảnh ngoài cửa sổ `LIFE / 2 + 1,5` thì giải phóng.
- Desktop: DPR tối đa 1,5, antialias, lưới 24×24, tối đa 3 ảnh cùng lúc, làm mờ 3×3, uốn vải 30% bản mẫu, hover. Mobile Lite (`pointer: coarse` hoặc rộng < 768px): DPR 1,25, tối đa 3 ảnh, mặt phẳng 1×1, không mờ, không uốn, không hover.
- Mất context WebGL lần đầu: dừng và chờ khôi phục; lần hai: về bản tĩnh. Bật giảm chuyển động trong lúc xem: huỷ cảnh, về bản tĩnh.

Kiểm thử (19/11/2026, Chrome không giao diện, ảnh thật: 11 ảnh gallery và chân dung của bác sĩ Linh Võ):

- Desktop 1440×900: 17/17 phép thử. three nằm trong importmap và nạp được; ảnh lưới không tải trùng; lăn chuột trong khung chạy gallery mà trang đứng yên; hover hiện con trỏ pointer và viền vàng; click mở Lightbox ảnh gốc, Esc đóng và trả focus; mũi tên phải sang ảnh kế, có đọc `aria-live`; kéo chuột sang trái đi tới; lăn hết ảnh thì tới ảnh chân dung (trang chưa cuộn), lăn tiếp thì trang cuộn; khung mới hiện một nửa thì lăn chuột cuộn trang; tự trôi sau 3 giây; không có lỗi console.
- Điện thoại 390×844 (sự kiện chạm qua `Input.dispatchTouchEvent`; `synthesizeScrollGesture` không cuộn được trang ở chế độ giả lập này nên không dùng): không tràn ngang; vuốt ngang đi tới, trang đứng yên; vuốt dọc trong khung cuộn trang; vuốt bắt đầu sát mép trái bị bỏ qua; chạm ảnh mở Lightbox.
- Giảm chuyển động: giữ bản tĩnh, không tải three.js, bấm ảnh lưới mở Lightbox.
- Bộ kiểm hồi quy: thêm kiểm Founder vào `PAGE_CHECKS.home` (số ảnh khớp giữa `data-count`, lưới và JSON; có h2; giảm chuyển động thì không bật 3D; bản tĩnh đủ ảnh). Kết quả 529 PASS ở nhóm trình duyệt; còn 5 FAIL cũ của Không gian ISHA (DB chưa có bài nào được đánh dấu hiện ở trang chủ), không liên quan.
- Chưa đo: FPS trên GPU tích hợp và trên điện thoại thật; cảm giác trackpad macOS và iOS Safari thật.

Cập nhật 19/11/2026 (đợt chỉnh thứ hai), kiểm thử lại: desktop 21/21 (gồm: không còn đánh số ảnh; lăn 200px đi 2,00 ảnh mà trang đứng yên; tốc độ lớn nhất khi lăn 0,96 và trung bình khi tự trôi 0,90 ảnh/giây; ở ảnh chân dung lăn tiếp trong khung thì trang KHÔNG cuộn, lăn ngược thì gallery lùi; khung mới hiện một phần vẫn do gallery giữ cuộn; lăn ngoài khung ở cột chữ thì trang cuộn), điện thoại và giảm chuyển động đạt, bộ kiểm hồi quy 565 PASS (5 FAIL cũ của Không gian ISHA). Phím mũi tên lên / xuống ở hai đầu vẫn trả cho trang (chỉ áp dụng khi khung đang có focus bàn phím; không bị ảnh hưởng bởi việc bỏ hai quy tắc của chuột).

### 21.1 Đánh bóng Quiet Luxury / Cinematic (19/11/2026)

Theo prompt của chủ dự án (giữ nguyên three.js r187 ES Module, dữ liệu CMS, Lightbox, Mobile Lite):

- **Tông `noir` cho cả section:** `inc/home/registry.php` đổi `founder-story` từ `afternoon` sang `noir`; `home.css` thêm `[data-tone="noir"]` (`--tone-bg` #141414, `--tone-fg` #f4f1ea, accent Gold #8e6837 chỉ cho đường kẻ / viền). Lớp sky coi đây là cực tối (độ sáng < 0,18) nên tự đặt `data-sky-solid="both"` và làm dải chuyển màu trong vùng đệm ở hai mối nối với Dịch vụ tiêu biểu và Sự kiện (đã chụp kiểm). Padding dọc của section tăng lên `clamp(96px, 11vw, 168px)`. Bộ kiểm hồi quy (`php/wp-read.php`) đổi kỳ vọng tông tương ứng.
- **Không còn khung hộp:** khung gallery không nền riêng; canvas mờ dần ở mép phải (giáp cột chữ, từ 86%), mép trên và mép dưới (mép dưới từ 84% để ảnh bay ra không chạy dưới cụm điều hướng) bằng `mask-image` hai lớp `intersect`. Chế độ gọn chỉ mờ trên / dưới.
- **Chân dung độc tôn:** `gallery.js` nhân opacity mọi ảnh gallery với `1 - mức hiện chân dung`, nên khi chân dung hiện đủ không còn ảnh nào phía sau. Khung 4:5 bo 3px, nền `radial-gradient` đồng ấm (#3a2a17 ở tâm tan ra #141414), viền đồng 1px với vệt ánh kim #fcf6ba chạy chậm quanh chu vi (`::after` conic-gradient xoay `--fs-sweep` bằng `@property`, 11 giây một vòng, chỉ chạy khi chân dung đang hiện: bản tĩnh hoặc `.is-end`; trình duyệt không có `@property` thì viền tĩnh), chân ảnh mờ dần vào khung (`mask-image` từ 74%). Ảnh tách nền (mime PNG / WebP / AVIF / GIF: class `is-cutout`) đặt vừa khung và đứng ở đáy (`object-fit: contain`, `50% 100%`); JPG phủ kín khung. Ảnh chân dung dùng size `large` (không cắt cứng 4:5 như `isha-frame`). Ghi chú trường chân dung trong CMS khuyên dùng ảnh đã tách nền.
- **Chức danh / tên:** chức danh Montserrat 11px chữ hoa giãn 0,28em màu kem 70% (không dùng #8e6837 cho chữ nhỏ: chỉ 3,7:1), tên Garamond #f4f1ea.
- **Bụi sáng vàng (Golden Dust)** thay "bầu trời sao" (`dust.js` viết lại): 42 hạt (20 điện thoại), toàn đốm bokeh mép mềm không lõi sắc, cỡ 6–30px (đa số nhỏ), opacity riêng 0,15–0,45, màu pha giữa #fcf6ba và #8e6837, quay quanh khung 0,012 rad/giây và nhích lên 4px/giây theo nhịp 120px (hiện / tắt dần trong mỗi nhịp). Vùng chức danh + tên được đo khi layout: bụi trong vùng đó còn 10%, ra ngoài 48px thì đủ.
- **Cụm điều hướng** (thay dòng chữ gợi ý cũ): bộ đếm Editorial `01 — 11` (ảnh hiện hành, chữ số đều nhau; tới chân dung thành "Nhà sáng lập"), thanh tiến trình 1px (nền kem 15%, vạch #8e6837), gợi ý "Cuộn để khám phá" kèm icon chuột nét mảnh (điện thoại: "Vuốt ngang" kèm icon mũi tên hai chiều) có chấm "thở" 2,8 giây; gợi ý tắt sau lần tương tác đầu. Cụm thẳng mép trái container, chữ có quầng tối mềm để đọc được khi ảnh sáng lướt qua.
- **Cột chữ:** tiêu đề #f4f1ea, câu trích nghiêng #e8dfc8 với vạch trái #8e6837 1,5px, đoạn văn CMS (giữ nguyên `isha_home_p`) kem 78%, `line-height` 1,75, `max-width` 56ch, cách đoạn 1,25em.
- **Khối chốt** (`.isha-founder__action`, ô lưới riêng: desktop dưới khối chữ; chế độ gọn sau gallery): đường kẻ 1px `rgb(142 104 55 / 0.35)` rồi nút "Đặt lịch tư vấn riêng cùng Bác sĩ" (viền #8e6837 1px, nền trong suốt, chữ Montserrat hoa giãn 0,2em màu kem; hover: nền đồng 10%, một vệt ánh kim lướt qua, mũi tên mảnh trượt 6px; nhãn dài tự cân hai dòng). Là liên kết tới trang Đặt lịch hẹn kèm `data-isha-booking`: `booking.js` mở popup `dialog#isha-book` tại chỗ (không JS thì tới trang); không có trang đó thì là `<button>` chỉ mở popup.
- **Responsive:** điện thoại xếp dọc chữ, gallery, nút; không tràn ngang; không có nút fixed nhảy section (đã bỏ từ trước). Nút CTA rộng hết cột dưới 480px.

Kiểm thử: kịch bản cũ 21/21 desktop (đổi một phép: phải CÓ bộ đếm), điện thoại và giảm chuyển động đạt; kịch bản đánh bóng 10/10 (tông noir và nền #141414, CTA có `data-isha-booking`, bộ đếm `01—11` rồi `03` sau hai lần mũi tên phải, cuối gallery `.is-end` + "Nhà sáng lập" + vệt ánh kim chạy + bo 3px, bấm CTA mở popup đặt lịch mà không rời trang, điện thoại xếp đúng thứ tự, không lỗi console); bộ kiểm hồi quy 565 PASS (5 FAIL cũ của Không gian ISHA).

### 21.2 Chỉnh nhỏ (19/11/2026)

- **Căn đều:** đoạn văn CMS và câu trích căn đều hai bên (`text-align: justify`, dòng cuối đoạn căn trái); nút CTA rộng đúng bằng cột chữ (mép thẳng hàng với khối chữ); cột chữ (khối chữ + khối chốt) căn giữa theo chiều dọc so với khung gallery nhờ hai hàng đệm co giãn (`grid-template-rows: 1fr auto auto 1fr`; chế độ gọn bỏ hàng đệm).
- **Tốc độ lăn chuột:** `WHEEL_SPEED` 1,25: lăn chuột đi nhanh hơn tự trôi 0,25 lần (1,125 ảnh/giây so với 0,9); bấm phím và quán tính sau khi thả tay vẫn theo `AUTO_SPEED`.
- **Viền chân dung:** dày 2,5px (trước 1px), vệt ánh kim vẫn chạy trên viền.

### 21.3 Thu gọn nội dung cột phải + "Đọc trọn vẹn câu chuyện" (19/11/2026)

- **Thu gọn** (`assets/js/founder-gallery/readmore.js`, mới, được `main.js` gọi): chỉ khi JS chạy và nội dung dài hơn vùng thu gọn ít nhất một dòng thì khối văn bản CMS (`.isha-founder__story`) thêm class `is-clamp`: cao đúng `--fs-lines` dòng (5 dòng; màn hình thấp ≤ 820px: 4 dòng) tính theo line-height thật của đoạn đầu, mép dưới mờ tan vào nền #141414 bằng `mask-image: linear-gradient(to bottom, #000 calc(100% - var(--fs-fade)), transparent)` (`--fs-fade` 76px; biến có kiểu `@property` nên chuyển 76px -> 0px mượt). Không JS thì cả bài hiển thị đầy đủ (không mất nội dung với SEO / trình đọc màn hình). Cấu trúc đoạn văn CMS không đổi.
- **Nút "Đọc trọn vẹn câu chuyện ↗"** (`button.isha-founder__more`, ẩn cho tới khi JS thấy cần): ngay dưới vùng mờ, text mảnh chữ hoa giãn 0,16em, màu #b08a52 (vàng đồng sáng hơn #8e6837 một nấc: 5,7:1 trên #141414; #8e6837 chỉ 3,7:1) với gạch chân 1px #8e6837. Bấm: bung mượt tại chỗ (max-height chạy 750ms tới chiều cao thật rồi gỡ giới hạn, mask mờ về 0), nhãn đổi thành "Thu gọn câu chuyện", mũi tên quay 180°, `aria-expanded` theo trạng thái; bấm lại thì thu về. Giảm chuyển động: đổi tức thì. Sau khi chạy xong phát `resize` để lớp sky đo lại chiều cao section.
- **Cột phải vừa khung nhìn:** `.isha-founder` cao tối thiểu `100svh - header`, đệm trên / dưới 40px (khối chữ cách mép trên cột tối thiểu 40px); tiêu đề nhỏ gọn lại (`clamp(1.5rem, 1.15rem + 0.9vw, 2.125rem)`, một dòng). Câu trích giữ nguyên. Đo: 1440×900 nội dung 636px / 736px khả dụng, 1920×1080 593px, 1366×768 599px / 604px; chế độ gọn bỏ `min-height`.
- Kiểm thử: 1440×900, 1920×1080, 1366×768 (4 dòng), điện thoại (5 dòng, không tràn ngang), bung / thu / Enter trên nút / giảm chuyển động đều đạt; bộ kiểm hồi quy 565 PASS (5 FAIL cũ của Không gian ISHA).

### 21.4 Lỗi "Web page caused context loss and was blocked" (19/11/2026)

- **Triệu chứng:** console báo `THREE.WebGLRenderer: A WebGL context could not be created. Reason: Web page caused context loss and was blocked`, gallery về bản tĩnh. Đây là cơ chế của Chrome: sau khi GPU bị reset do trang, Chrome chặn WebGL của nguồn đó một lúc (khoảng vài phút, hoặc tới khi khởi động lại trình duyệt); mọi lần tải sau đều bị từ chối kể cả khi đã sửa code. Đã đo: code chỉ tạo đúng 1 context (không tạo trùng khi bung / thu nội dung, đổi cỡ cửa sổ, tải lại) và không tự mất context nào trong môi trường thử.
- **Giảm tải GPU:** (1) bỏ `mask-image` hai lớp trên canvas (buộc trình duyệt vẽ lại cả canvas WebGL qua lớp trung gian mỗi khung hình, nặng với GPU tích hợp), thay bằng lớp phủ gradient màu nền #141414 đặt trên canvas (`.isha-founder__stage.is-3d::after`, một lớp tĩnh, hình ảnh y hệt); (2) bỏ `powerPreference: 'high-performance'` (ép máy hai GPU chuyển sang GPU rời, nguồn gây reset thường gặp).
- **Đường lui:** tạo context thất bại thì về bản tĩnh (đã có); mất context không được khôi phục trong 3 giây thì về bản tĩnh thay vì để khung đen; khôi phục kịp thì chạy tiếp và đặt lại màu nền xóa #141414 (three dựng lại trạng thái GL nên màu về đen mặc định).
- **Kiểm thử:** giả lập WebGL bị chặn, mất context không khôi phục, mất rồi khôi phục sau 0,6 giây: đều đúng; các bộ kiểm cũ đạt.

### 21.5 Sửa lỗi tăng bộ nhớ / treo trình duyệt, mũi tên "Đọc trọn vẹn", nút đặt lịch (19/11/2026)

- **Lỗi treo trình duyệt (nghiêm trọng):** vòng vẽ của gallery bị nhân bản. `kick()` được gọi từ bên trong `frame()` (qua `setHovered()` khi ảnh trôi qua dưới con trỏ) lên lịch thêm một vòng rAF, rồi cuối `frame()` lại lên lịch vòng nữa; mỗi lần đổi hover số vòng song song tăng thêm. Đo trên Chrome không giao diện: rê chuột 6 giây đã lên 2436 lần rAF/giây (khoảng 40 vòng, vòng nào cũng render WebGL), gây tăng bộ nhớ, treo và có thể là nguyên nhân GPU reset dẫn tới lỗi "Web page caused context loss and was blocked" ở mục 21.4. Sửa: cờ `inFrame` (kick() không lên lịch khi đang trong frame), phần thân tách thành `tick()`, cuối `frame()` chỉ lên lịch khi chưa có vòng nào và vẫn đang chạy. Đo lại: luôn đúng 1 lần gọi mỗi khung hình (rê chuột 9 giây, lăn, cuộn trang, đổi mobile / desktop 3 lần), heap JS 3–4MB không đổi.
- **Ghi nhận ngoài phạm vi Founder:** sau mỗi lần đổi mobile / desktop có 4 phần tử đã tách khỏi trang vẫn bị giữ trong bộ nhớ, đều thuộc danh sách Lời kể (`isha-testimonials__list`, home.js). Mức rất nhỏ, không gây treo; chưa sửa.
- **Mũi tên "Đọc trọn vẹn câu chuyện":** khi thu gọn chỉ xuống (↙, nội dung sẽ bung xuống), khi mở chỉ lên (↗, thu lại); rê chuột thì nhích theo hướng mũi tên.
- **Nút "Đặt lịch tư vấn riêng cùng Bác sĩ":** dùng đúng class nút chính của trang chủ `.isha-btn.isha-btn--primary` (nền vàng đồng, lớp ánh kim `--metal-light` trượt khi rê chuột, chữ trắng) và cùng cỡ nút khu Dịch vụ tiêu biểu (`.isha-founder__action .isha-btn` = `.isha-wheel__actions .isha-btn`: cao 44px, đệm 10px 16px, chữ 0,75rem giãn 0,08em). So bằng `getComputedStyle` với nút "Khám phá nhóm": không khác thuộc tính nào. Bỏ style nút riêng cũ (viền mảnh, mũi tên); điện thoại ≤ 480px cho xuống dòng.

## 22. Workshop & Sự kiện: danh sách tạp chí tương tác (19/11/2026)

Thay hoàn toàn kiểu băng chuyền một thẻ phủ tối (mục 8, 14, 15 về `events`) bằng bố cục **Editorial Split List**. Phần "events" ở các mục cũ chỉ còn giá trị lịch sử; mục này THẮNG khi mâu thuẫn. Yêu cầu gốc: `docs/events.md`.

### 22.1 Dữ liệu (`isha_home_data_build_events()`, `inc/home/data.php`)

- **Chọn sự kiện (cho tab "Tất cả" và cho từng loại):** sự kiện tick Nổi bật trước (thứ tự hiển thị bên dưới), thiếu thì bù bằng sự kiện mới nhất (theo ngày đăng) chưa tick; không sự kiện nào được tick thì là các sự kiện mới nhất. Mặc định `count` = 3 (`isha_home_shortcode_counts()` cũng 3).
- **Mỗi tab một bộ tối đa `count` sự kiện:** tab "Tất cả" và mỗi loại (`isha_event_type`, không phân cấp, một sự kiện có thể nhiều loại; chỉ loại có bài, nhiều bài nhất trước). Dữ liệu trả về là HỢP của các tab; mỗi sự kiện có `tabs` (danh sách slug tab chứa nó, gồm `all`) và `initial` (thuộc "Tất cả"). Chỉ có 0 hoặc 1 loại thì không có bộ lọc (`types` = `array()`).
- **Thứ tự hiển thị** (`isha_home_data_sort_events()`): chưa diễn ra (theo `end_ts` nếu có, không thì `start_ts`) theo ngày bắt đầu tăng dần; rồi chưa có ngày; sau cùng đã qua, gần nhất trước.
- **Khoá mới của mỗi sự kiện:** `type_slugs`, `tabs`, `initial`, `day` ("22", hai chữ số), `month_label` ("Th11"), `year`. Giữ các khoá cũ (`register_url`, `portrait_id`, `past`...) cho bộ lọc `isha/home/data/events` nhưng template không dùng. `image_id` là **Ảnh đại diện (featured image)**; ảnh dọc không còn dùng ở trang chủ.
- **Khoá mới cấp section:** `link_label` (CMS, mặc định "Xem tất cả sự kiện"), `archive_url` (`isha_home_data_events_url()`: trang đã xuất bản có slug `danh-sach-su-kien`; `isha_event` không có trang lưu trữ; rỗng thì ẩn liên kết), `types` (`slug`, `name`).

### 22.2 Template (`template-parts/home/events.php`)

- Đầu section: tiêu đề + câu dẫn bên trái; bộ lọc (`.isha-events__filter`, nút `.isha-events__tab`, `aria-pressed`) bên phải, căn đáy, ở dạng `hidden` cho tới khi JS chạy.
- `.isha-events__layout[data-isha-events]` gồm `.isha-events__stage` (khung ảnh trái, `aria-hidden`, mỗi sự kiện một `<img class="isha-events__shot" data-event>`) và `.isha-events__side` (danh sách + nút chính "Xem tất cả sự kiện").
- Mỗi sự kiện: `li.isha-events__item[data-event][data-tabs]` chứa `a.isha-events__row` (cả dòng là liên kết tới trang chi tiết; không nút đăng ký, không nhãn "Đã diễn ra") gồm ảnh thẻ `.isha-events__thumb`, ô ngày `time.isha-events__date` (`__day` + `__month` "Th12 · 2026"), chữ (`__type` nhãn loại, `h3.isha-events__name`, `__meta` "giờ · địa điểm"), mũi tên SVG. Sự kiện không thuộc "Tất cả" nằm trong `hidden` (cả ảnh trái của nó), nên không JS thì chỉ thấy 3 dòng của "Tất cả" và ảnh của chúng.
- Ảnh: `isha-cinema` (2.39:1) cắt giữa vào khung; `sizes` báo bề rộng hiển thị thật (khung 4:5 cắt từ ảnh ngang nên ảnh hiển thị rộng ~3 lần khung) để trình duyệt không chọn bản quá nhỏ. Ảnh trong vùng `display: none` không tải (`loading="lazy"`), nên mỗi chế độ chỉ tải bộ ảnh của nó.

### 22.3 Giao diện (khối `events` của `home.css`)

- **Chế độ chia đôi** (`min-width: 768px` và `hover: hover` và `pointer: fine`): cột trái 5fr là khung ảnh 4:5 sạch (không chữ, không lớp phủ, viền vàng đồng 1px lệch ra ngoài 10px bằng `outline-offset`), cột phải 7fr là danh sách. Dòng `.is-active`: nền kem nhẹ, vạch trái 2px vàng đồng, số ngày và mũi tên vàng đồng, mũi tên trượt 6px. Ảnh chuyển mờ 520ms + thu 1,03 → 1 (700ms); ảnh mới hiện đè lên ảnh cũ rồi ảnh cũ mới tắt (không lộ nền giữa hai ảnh).
- **Chế độ thẻ** (mặc định: điện thoại, và máy tính bảng cảm ứng vì không có hover): thẻ dọc, ảnh 16:10 phía trên, cột ngày cố định 6,5rem để tên các thẻ thẳng hàng; một cột dưới 640px, hai cột từ 640px; bộ lọc là một hàng cuộn ngang, ẩn thanh cuộn.
- **Màu theo quy tắc tương phản của theme** (nền golden `#ccb498`: chữ nhỏ chỉ dùng `--tone-fg` 8,65 hoặc `--tone-soft` 5,66; `--tone-accent` = gold-ink chỉ 3,66 nên chỉ cho nét và chữ lớn): tab đang chọn đổi sang màu chữ đậm + gạch chân 1px vàng đồng (KHÔNG đổi chữ nhỏ sang vàng đồng), nhãn loại là chữ fg có vạch vàng đồng đứng trước, số ngày lớn (Garamond) chuyển vàng đồng khi dòng được chọn. Tháng · năm dùng Montserrat hoa nhỏ vì chữ số "1" của Garamond dễ đọc nhầm thành "l".
- Đã bỏ: lớp phủ tối, nhãn "Đã diễn ra", nút "Đăng ký tham dự", băng chuyền (class `is-carousel`, vạch tiến trình, khối `isha_home_pager` của events). `deck()` trong `home.js` giờ chỉ còn `results` dùng.

### 22.4 JS (`assets/js/events-list/main.js`, script module mới)

Đăng ký ở `inc/home/assets.php` (`wp_enqueue_script_module( 'isha-events-list' )`), không import gì, ngoài ngân sách JS của `home.js`. `mouseenter` và `focusin` trên dòng thì dòng đó thành `.is-active` và ảnh trái đổi theo; bấm tab thì danh sách mờ 180ms, chỉ hiện dòng có tab đó trong `data-tabs` (đủ tối đa 3 nhờ PHP), ảnh trái về dòng đầu của danh sách mới, vùng `role="status"` báo "Đang hiển thị N sự kiện". Ảnh của dòng chưa hiện được bỏ `hidden` khi cần (mới tải) và ẩn lại sau khi ảnh cũ mờ xong. Giảm chuyển động: đổi tức thì. Không JS hoặc lỗi JS: danh sách tĩnh 3 dòng, dòng đầu đang chọn, mọi liên kết dùng được.

### 22.5 Kiểm thử

`php -l` và `node --check` sạch. Chrome headless trên trang thật (5 sự kiện đều tick Nổi bật, 3 loại): tab Tất cả 4 sự kiện, Design Talk 2 (sự kiện thứ 5 chỉ hiện ở tab này), Charity 1; rê chuột và focus bàn phím đổi ảnh đúng; bấm tab bằng chuột và Enter; không JS vẫn 4 dòng và 1 ảnh hiện; giảm chuyển động đổi tức thì; desktop 1440, máy tính bảng cảm ứng 820 (hai cột), điện thoại 390 (một cột, không tràn ngang, bộ lọc cuộn ngang); không lỗi console. Ghi nhận: ảnh đại diện của một số sự kiện mẫu nhỏ (600×400, 750×525) nên hơi mờ khi cắt vào khung 4:5; cần ảnh thật 2400×1004.

### 22.6 Điều chỉnh: 3 sự kiện, nút chính dưới ảnh (19/11/2026, theo yêu cầu của người dùng)

- **3 sự kiện** thay cho 4 (section cao quá màn hình): `count` mặc định 3 ở `isha_home_data_build_events()` và `isha_home_shortcode_counts()`; mỗi tab loại cũng 3. Khoảng đệm trên và dưới của section là nhịp chung của theme (126px ở 1440x900) nên giữ nguyên; thu gọn thêm: đệm dòng 16 đến 24px (trước 20 đến 32px), `margin-bottom` của đầu section 28 đến 48px, cột ảnh còn 4fr (cột chữ 7fr). Đo trên trang thật: phần nội dung (từ tiêu đề tới nút) cao 763px ở 1440x900 và 764px ở 1366x768 (bản 4 dòng: cả section cao 1405px ở 1440 rộng); cả section kể đệm 1015px ở 1440x900.
- **Nút "Xem tất cả sự kiện"** là nút chính của theme (`isha_home_button( ..., 'primary' )`, class `.isha-btn--primary`), đã bỏ kiểu liên kết gạch chân và mũi tên. Template đặt nó là con trực tiếp của `.isha-events__layout` (ngoài cột danh sách); desktop chia đôi dùng `grid-template-areas` ("stage side" / "all side") để nó đứng dưới khung ảnh, thẳng mép trái; thẻ dọc thì đứng sau danh sách.
- **Máy tính bảng cảm ứng** rộng từ 900px: 3 cột (3 thẻ không bị lẻ một thẻ); 640 đến 899px vẫn 2 cột.

## 23. Tin tức: Dark Cinematic Editorial Grid, Ảnh banner, sửa meta box (20/11/2026)

Thay bố cục tạp chí nền be (mục 9, 15, 19 về `articles`) bằng **Dark Cinematic Editorial Grid**: nền noir như Founder, 1 bài nổi bật nằm ngang + 3 bài phụ có ảnh. Phần "articles" ở các mục cũ chỉ còn giá trị lịch sử; mục này THẮNG khi mâu thuẫn. Yêu cầu gốc: `docs/news.md`.

**Quyết định của người dùng (20/11/2026):** chữ nhỏ màu vàng dùng `#c9a46a` (8,4:1 trên `#141414`), `#8e6837` (3,7:1) chỉ cho nét; font nhãn theo quy tắc theme (Garamond, không chữ hoa); dòng nhỏ "ISHA Journal" là trường CMS; mốc chế độ gọn theo theme (`max-width: 900px` hoặc `max-height: 560px`); ở bài nổi bật **chỉ nút "Đọc bài viết" là liên kết** (ảnh và tiêu đề không bấm được); "Xem tất cả tin tức" là một liên kết duy nhất đổi chỗ bằng CSS; nhịp tối (Founder), sáng (Sự kiện), tối (Tin tức) là có chủ ý; ảnh bài nổi bật lấy từ một trường **Ảnh banner** 16:10 mới của bài.

### 23.1 Sửa lỗi: vùng meta box của bài Tin tức trống

- **Nguyên nhân:** thứ tự hộp đã lưu của tài khoản quản trị (`meta-box-order_isha_article`) đặt hộp `isha_article_fields` ở cột `side`, kèm trạng thái thu gọn (`closedpostboxes_isha_article`). `the_block_editor_meta_boxes()` dựng cột `side` trước, nên hộp bị dời vào thanh bên của trình soạn thảo khối (đang thu gọn) và vùng meta chính dưới nội dung trống.
- **Sửa:** `ISHA_Core_Article_Fields::keep_in_main()` (bộ lọc `get_user_option_meta-box-order_isha_article`) bỏ hộp khỏi `side` trong thứ tự đã lưu, nên hộp luôn ở vị trí đăng ký (`normal`, `high`). Đã xoá hai dòng tuỳ chọn cũ của người dùng 1 trong `isha_usermeta` (chỉ là tuỳ chọn hiển thị).

### 23.2 Ảnh banner (ISHA Core + theme)

- Meta `_isha_article_banner` (ID ảnh), ô chọn ảnh `ISHA_Core_Admin_Media` trong hộp Thông tin bài viết (`isha_article_banner_id`); `ISHA_Core_Article_Fields::get()` trả thêm `banner_id` (0 nếu chưa chọn hoặc tệp không còn là ảnh). Lưu: thiếu khoá thì giữ giá trị cũ, ID không hợp lệ thì xoá meta.
- Kích thước ảnh mới (`inc/setup.php`): `isha-banner` 1600×1000 và `isha-banner-sm` 800×500, cắt. Chỉ ảnh tải lên từ nay có hai bản này; ảnh cũ được chọn làm banner thì WordPress dùng ảnh gốc kèm srcset của nó (trình duyệt vẫn chọn bản vừa theo `sizes`).
- Template: bài nổi bật dùng banner (`isha-banner`), không có thì Ảnh đại diện (`large`, cắt giữa vào khung 16:10), không có nữa thì khối giữ chỗ tối.

### 23.3 Dữ liệu và CMS

- `isha_home_data_build_articles()`: thêm `eyebrow` (cấp section), `featured.banner_id`, `list[].image_id` (Ảnh đại diện). Bỏ `read_min` khỏi mô tả hợp đồng (không còn được trả từ lâu).
- `home-schema.php`, thẻ Tin tức: trường mới `eyebrow` ("Dòng nhỏ trên tiêu đề", ≤ 40 ký tự); mặc định `__( 'ISHA Journal' )` ở `isha_home_defaults( 'articles' )`.
- `registry.php`: tông `articles` đổi `late-afternoon` → `noir`. Lớp sky đọc `--tone-bg` nên tự làm dải chuyển ở hai mối nối (Sự kiện → Tin tức, Tin tức → Lời kể).

### 23.4 Template (`template-parts/home/articles.php`)

- Section là lưới (CSS): `__head` (dòng nhỏ `.isha-label`, `h2`), `__all` (liên kết `.isha-link` + mũi tên SVG), `article.__feature` (`__frame--wide` aria-hidden; `__body`: chủ đề, `h3` KHÔNG liên kết, đoạn trích một `<p>`, ngày, `a.__cta` "Đọc bài viết" kèm tên bài trong `.isha-sr`), `__rule`, rồi `.isha-row.__row` với `ul.__track` gồm 3 `article.__card` (khung 4:3, chủ đề, `h3 > a.__link` phủ cả thẻ bằng `::after`, ngày). Không còn cụm `isha_home_pager` (desktop là lưới, chế độ gọn ẩn pager).
- Các khối hiện dần mang `data-isha-reveal`.

### 23.5 Giao diện (khối `articles` của `home.css`)

- Lưới section: `[full-start] var(--isha-row-pad) [main-start] 1fr [split] auto [main-end] var(--isha-row-pad) [full-end]`; tiêu đề ở `main-start / split`, "Xem tất cả" ở `split / main-end` căn đáy; bài nổi bật và đường kẻ ở cột main; hàng bài phụ ở cột full (lề trong của track = `--isha-row-pad`, thẳng hàng nội dung). Chế độ gọn: "Xem tất cả" xuống hàng 5.
- Bài nổi bật 55fr / 45fr, chữ căn giữa theo chiều dọc; tiêu đề Garamond `clamp(1.875rem … 3rem)`; đoạn trích Montserrat kem 72%, cắt 3 dòng; ngày kem 50% (4,9:1). Vầng sáng đồng `radial-gradient` 0,11 ở `::before` của section (`isolation: isolate`, `z-index: -1`).
- Khung ảnh: bo 3px; `::before` là viền tĩnh 1px vàng đồng 35% (khung banner thêm vignette mảnh); `::after` là viền conic-gradient xoay bằng `@property --ar-sweep` (vệt `#fcf6ba`), ẩn và tạm dừng cho tới khi rê chuột (chuột thật) hoặc focus bàn phím trong bài. Ảnh phóng 1,03 trong 700ms (`--isha-ease-cine`).
- 3 bài phụ: tiêu đề tối đa 3 dòng, ngày ở đáy (`margin-top: auto`) nên thẳng hàng chân; ở desktop có chuột thật (`min-width: 901px` và `min-height: 561px` và hover) ảnh `saturate(0.72) brightness(0.85)`, rê vào thì về nguyên bản, tiêu đề chuyển `#fcf6ba`. Vòng focus vẽ quanh cả thẻ (`::after` của liên kết).
- Chế độ gọn: bài nổi bật xếp dọc; hàng bài phụ vuốt ngang `scroll-snap`, mỗi thẻ `min(82vw, 360px)` để lộ mép thẻ sau; ảnh giữ màu.

### 23.6 JS (`home.js`, khối `articles`)

`register( 'articles' )`: khi chuyển động bật và không giảm chuyển động, đặt `.is-pre` (CSS: `[data-isha-reveal]` mờ và dịch 24px) rồi gỡ khi section vào khung nhìn (`motion.inView`, `once`, ngưỡng 0,1); so le 0/120/240ms qua `--ar-i`. Không JS, lõi lỗi hoặc giảm chuyển động: hiện sẵn.

### 23.7 Kiểm thử

`php -l`, `node --check` sạch. PHP CLI: dữ liệu và HTML section đúng; giả lập thứ tự hộp đặt ở `side` thì hộp vẫn dựng ở `normal` (mở, có ô Ảnh banner); giả lập meta banner thì khung dùng ảnh banner. Chrome headless trên trang thật (1440×900, 1366×768, máy tính bảng cảm ứng 820, điện thoại 390): tông noir `#141414`, khung 16:10, 3 thẻ đều nhau và thẳng chân, đoạn trích 3 dòng, chữ vàng `#c9a46a` Garamond, rê thẻ: ảnh về màu gốc + phóng 1,03 + viền ánh kim chạy, rê ảnh bài nổi bật: hiệu ứng có nhưng không phải liên kết; focus "Đọc bài viết" bật viền; điện thoại: thẻ 320px vuốt ngang, "Xem tất cả" cuối section, không cuộn ngang trang; giảm chuyển động: không ẩn nội dung; không lỗi console. Bộ kiểm hồi quy: tông `articles` cập nhật thành `noir`, thêm 2 mục kiểm meta box Tin tức (`php/wp-admin-read.php`).

### 23.8 Điều chỉnh: tối ưu chiều cao, tăng vignette (20/11/2026, theo yêu cầu của người dùng)

Section quá cao để chụp trọn một màn hình desktop (phải zoom 75%). Bốn thay đổi, tất cả chỉ ở CSS và template, không đổi dữ liệu:

- **Cột ảnh bài nổi bật 47%/53%** (từ 55%/45%) và **tỉ lệ 16:9** (từ 16:10) cho cả ảnh banner lẫn 3 ảnh phụ (từ 4:3). `.isha-articles__frame--card` không còn override riêng (cùng tỉ lệ với `.isha-articles__frame` gốc). Ảnh cũ (banner, isha-card) vẫn dùng được: `object-fit: cover` tự cắt vào khung mới, không cần sinh lại kích thước.
- **Gộp Chuyên mục + Ngày** của 3 bài phụ thành một dòng `.isha-articles__meta` ("CHUYÊN MỤC · NGÀY", nối bằng `implode( ' · ', array_filter( [...], 'strlen' ) )`); bỏ vạch ngắn dưới chuyên mục và dòng ngày riêng ở đáy thẻ. Bài nổi bật không đổi (vẫn chủ đề + vạch ngắn riêng, ngày riêng dưới đoạn trích).
- **Thu gọn khoảng thở dọc:** `padding-block` của section còn `clamp(48px, 6vh, 72px)` (riêng cho `.isha-articles`, không đổi `--isha-section-y` chung của theme); khoảng cách Header → bài nổi bật còn `clamp(24px, 3vw, 36px)` (từ `clamp(40px, 5vw, 72px)`); margin trên của đường kẻ phân cách và margin trên của hàng 3 bài phụ đều còn `clamp(24px, 3vw, 28px)` (từ ~56–96px và ~40–64px).
- **Tăng vignette khung ảnh bài nổi bật:** ảnh nền sáng/trắng (ví dụ ảnh chân dung nền studio) bị cắt gắt với nền `#141414`. Đổi từ một lớp inset (`56px blur, 10px spread, 50%`) thành hai lớp: lớp ngoài rộng và mềm (`80px blur, 20px spread, 70%`) phủ cả 4 mép, lớp trong hẹp và đậm hơn (`18px blur, 2px spread, 55%`) ngay sát viền, để ảnh tan mềm vào nền thay vì có ranh giới cứng.

Đo trên trang thật (1440×900): tổng chiều cao nội dung section (từ đầu tiêu đề tới chân hàng 3 bài phụ) giảm từ 1152px xuống 842px (**-310px**, vượt mục tiêu ~200–250px ban đầu). Tỉ lệ khung ảnh đo được đúng 16:9 (1,778) ở mọi cỡ màn hình đã kiểm (1440, 1366, 820, 390); không cuộn ngang, không lỗi console.

### 23.9 Nút hành động (20/11/2026, theo yêu cầu của người dùng)

- "Xem tất cả tin tức" là `a.isha-btn.isha-btn--ghost.isha-articles__more`; "Đọc bài viết" là `a.isha-btn.isha-btn--primary.isha-articles__cta` (cùng kiểu nút chính của theme: viền/nền vàng đồng, hover lấy từ `.isha-btn--*`). Mũi tên SVG và chữ `.isha-sr` giữ nguyên trong nút; màu mũi tên theo `currentColor`. Đã bỏ kiểu gạch chân `.isha-link` của hai liên kết này.
- Đo trên trang thật ở 1440, 1366, 820, 390: không lỗi console, không cuộn ngang; nút "Xem tất cả" cao 48px (vùng chạm đủ).

## 24. Lời kể: Quiet Luxury / Editorial, hai dãy ngược chiều (20/11/2026)

Thay mục 14.13 (một hàng trôi, nền evening). Yêu cầu gốc: `docs/testimonial.md` (máy tính) và `docs/testimonial-mobile.md` (chế độ gọn).

**Quyết định của người dùng (20/11/2026):** trang chủ hiện tối đa **20** lời kể (trước là 6); nền **day-warm** `#e6d8c6` thay tông evening `#a48b6c`; chế độ gọn theo `docs/testimonial-mobile.md` (gộp một hàng vuốt, không trôi). Áp dụng quy tắc theme như Tin tức (mục 23): dòng nhỏ là `.isha-label` (Garamond, không chữ hoa) và là trường CMS; chữ vàng nhỏ trên nền sáng là `--isha-gold-ink` `#6f5129` (5,2:1), `#8e6837` (3,6:1 trên day-warm) chỉ cho viền, đường kẻ, dấu ngoặc kép, vòng ảnh; mốc chế độ gọn theo theme (`max-width: 900px` hoặc `max-height: 560px`) thay cho 768px của đặc tả.

### 24.1 Dữ liệu, CMS, shortcode

- `isha_home_data_build_testimonials()`: `count` mặc định 20 (trước 6), trả thêm `eyebrow`. `isha_home_defaults( 'testimonials' )`: `eyebrow` = "Cảm nhận khách hàng".
- `home-schema.php`, thẻ Lời kể: trường `eyebrow` ("Dòng nhỏ trên tiêu đề", ≤ 40 ký tự); ghi chú admin nêu trần 20 và hành vi hai dãy.
- `inc/home/shortcodes.php`: `[isha_home_testimonials]` mặc định `count` 20, trần riêng 20 (Sự kiện, Tin tức giữ trần 12). **Lỗi đã gặp:** trang chủ render qua shortcode nên trước khi sửa vẫn chỉ ra 6 lời kể dù lớp dữ liệu đã nâng lên 20.
- `registry.php`: tông `testimonials` đổi `evening` → `day-warm`. Tông evening không còn section nào dùng (khối CSS giữ lại trong bảng tông).
- DB `isha`: 15 lời kể mẫu (ID 346 đến 360, ngày 23/8 đến 18/10/2026, cũ hơn 5 lời kể gốc 183 đến 187), mỗi bài gắn một liệu trình đã xuất bản (bài 360 không gắn, để kiểm trường hợp thiếu liệu trình); lời kể dài 108 đến 274 ký tự. Tổng 20 lời kể đã xuất bản.

### 24.2 Template (`template-parts/home/testimonials.php`)

- Đầu mục trong `.isha-wrap`: `p.isha-label.__eyebrow` + `h2`.
- `div.__stage[data-isha-tm][role=group][tabindex=0][aria-label]` chứa 1 dãy (n ≤ 5) hoặc 2 dãy (n > 5; dãy 1 = `array_slice( 0, ceil( n / 2 ) )`, dãy 2 = phần còn lại, có `--rev`). Mỗi dãy: `div.__row[data-isha-tm-row] > div.__track > ul.__list[role=list] > li.__item > figure.__card`.
- Thẻ: `span.__mark` (dấu “ trang trí, `aria-hidden`), `blockquote > p` (bỏ dấu ngoặc kép trong dòng của bản cũ), `figcaption.__credit` (ảnh chân dung NẾU CÓ, tên, liệu trình). **Từ đợt đánh bóng thứ hai (24.6) lời kể không có ảnh chân dung không dựng vòng tròn nữa** (trước đây hiện chữ cái đầu của tên).
- `div.__meter[data-isha-tm-meter][aria-hidden]` (thanh 2px + bộ đếm "01 / 20") khi n > 1; chỉ hiện ở chế độ gọn có JS.

### 24.3 Giao diện (khối `testimonials` của `home.css`)

- Section: `padding-block: clamp(48px, 6vh, 72px)` (như Tin tức), `.isha-sec__head` bỏ margin dưới, vùng thẻ cách đầu mục `clamp(20px, 2.4vw, 28px)`. Dải chuyển ở hai mối nối sáng / tối (`--sky-band`) rút còn `min(40px, 5vh)` phía section này để nằm gọn trong padding (dòng nhỏ không nằm trên dải xám).
- Dãy: tràn ngang, mask `8% / 92%`, `padding-block: var(--tm-lift)` (10px, chừa chỗ cho thẻ nâng lên) nên hai dãy cách nhau 20px. (Đã đổi ở 24.6: đang trôi thì mask nằm ở cả khối stage.)
- Thẻ (kích thước và font lời kể đã đổi ở 24.6): nền trắng 38%, viền `rgba(142, 104, 55, 0.22)`, bo 4px; đường kẻ `rgba(142, 104, 55, 0.18)`; ảnh 42px; tên Garamond 19px; liệu trình Montserrat 13px gold-ink.
- `.is-marquee`: track trôi `isha-tm-marquee` (0 → `--tm-shift`), dãy `--rev` `animation-direction: reverse`; track không đệm hai bên để chu kỳ đúng bằng một nhóm. `.is-paused`: `animation-play-state: paused` cho mọi dãy.
- Spotlight, chỉ `@media (hover: hover) and (pointer: fine)`: `.is-spot` làm mọi thẻ mờ, thẻ `.is-lit` nổi lên (hiện tại: nền tối `#141414`, xem 24.6). Giảm chuyển động: không nâng thẻ, không chuyển tiếp.
- Chế độ gọn: stage là hàng vuốt `scroll-snap` (snap `start`, lề thẳng `.isha-wrap`), `__row` và `__track` là `display: contents` (không mask), `[data-marquee-clone]` ẩn, thẻ `min(84vw, 380px)`; `.has-meter` hiện thanh tiến trình (nền `rgba(142, 104, 55, 0.2)`, vạch Gold, `scaleX`) và bộ đếm `.isha-label` số đều.
- Đo trên trang thật 1440×900 (đợt đầu): section 1269 → 951px, nội dung (dòng nhỏ tới chân dãy 2) 1017 → 843px (ngang Tin tức 842px). Số đo sau đợt đánh bóng thứ hai ở 24.6.

### 24.4 JS (`home.js`, khối `testimonials`)

- `SPEED = 35` px/giây. Chế độ: `swipe` (gọn), `marquee` (máy tính, chuyển động bật, không giảm chuyển động), `static`.
- `marquee`: đo bề rộng một nhóm của MỌI dãy trước (dãy nào bằng 0 thì không bật), rồi mỗi dãy nhân bản danh sách `ceil( bề rộng dãy / nhóm ) + 1` lần (`aria-hidden`, `data-marquee-clone`, **không `inert`** vì `inert` chặn rê chuột nên thẻ bản sao không có spotlight; thẻ không chứa phần tử bấm được), đặt `--tm-shift` = -nhóm px và `--tm-dur` = nhóm / SPEED trên phần tử dãy, nên hai dãy cùng tốc độ dù số thẻ khác nhau (khác với cách -50% của đặc tả: cách này không hở khi một nhóm hẹp hơn màn hình).
- Dừng (`.is-paused`) khi: rê chuột vào thẻ (`pointerover` loại `mouse`, `.is-spot` + `.is-lit`), chạm giữ (`pointerdown` không phải chuột, tới `pointerup`/`pointercancel`), focus bàn phím vào vùng thẻ (`:focus-visible`), hoặc section ra khỏi khung nhìn (`motion.inView`). Rời chuột khỏi thẻ (vào khe giữa thẻ hoặc ra ngoài) thì chạy tiếp từ đúng vị trí.
- `swipe`: không bản sao; vị trí cuộn của từng thẻ gốc đo theo `offsetLeft` (stage `position: relative`) và kẹp ở quãng cuộn tối đa; `scroll` (passive, gom bằng `requestAnimationFrame`) cập nhật thanh `scaleX( ( p + 1 ) / n )` và bộ đếm.
- Dựng lại khi bề rộng stage đổi (`frame.onLayout`), khi đổi chế độ gọn hoặc giảm chuyển động. `home.js` 63.975 → khoảng 69,5 KB (chưa nén).

### 24.5 Kiểm thử

`php -l`, `node --check` sạch; PHP CLI: dữ liệu (20 lời kể, eyebrow, tông) và cách chia (1, 5 → một dãy; 6 → 3 + 3; 7 → 4 + 3; 20 → 10 + 10, dãy 2 nối tiếp đúng thứ tự). Chrome headless trên trang thật (kịch bản tạm ở scratchpad, 36/36 đạt): hai dãy 10 + 10, bản sao đúng thuộc tính, tốc độ khai báo và đo thật −35 / +35 px/giây; 7 lời kể trên màn 2560px (dãy 4 + 3, phủ kín, cùng tốc độ); 5 lời kể một dãy (tham số thử `?tm_n=` qua một mu-plugin tạm, đã xoá); rê chuột vào thẻ bản sao ở dãy 2 thì cả hai dãy đứng yên, thẻ nâng −4px viền `#8e6837`, thẻ khác 0,55; rời chuột chạy tiếp; focus vùng thẻ thì dừng; ra/vào khung nhìn; giảm chuyển động: hàng cuộn tĩnh; điện thoại 390: một hàng 20 thẻ gốc, không bản sao, thẻ 328px, bộ đếm 01 → 05 → 20 / 20 khớp thanh 5% → 25% → 100%; đổi cỡ 1440 ↔ 820 ↔ 1280 ↔ 390 ↔ 1440 trên cùng trang: không mất, không lặp thẻ, trôi lại khi mở rộng; không lỗi console, không cuộn ngang. Bộ kiểm hồi quy: tông `testimonials` cập nhật thành `day-warm` (`php/wp-read.php`).

### 24.6 Đánh bóng lần hai: font Garamond, Dark Card Spotlight, bỏ avatar chữ cái, thẻ rộng hơn (20/11/2026, theo yêu cầu của người dùng)

Giữ nguyên logic chia dãy, tốc độ px/giây, dừng cả hai dãy, hàng vuốt chế độ gọn. Chỉ đổi 4 mục; mục này THẮNG 24.2 và 24.3 khi mâu thuẫn.

- **Font lời kể:** Garamond Premier Pro 400 (bản Disp; đã so sánh với 500 và giãn chữ 0,012em ở ảnh phóng ×2, bản 400 giữ nét thanh nhất), `clamp(17px, 1.25vw, 18.5px)` (18px ở 1440, 17px điện thoại), line-height 1,65, màu `--tone-fg` `#1c1b19` (≈ `#1c1a17` của đặc tả). Dấu “ Garamond 64px, `#8e6837`, opacity 0,45 (mảnh hơn bản 92px). Liệu trình giữ Montserrat 13px, `letter-spacing: 0.02em`.
- **Dark Card Spotlight** (chỉ `@media (hover: hover) and (pointer: fine)`, chuyển 360ms): thẻ `.is-lit` đảo sang nền `#141414`, lời kể và tên `#f4f1ea`, dấu “ và liệu trình `#c9a46a`, đường kẻ chân thẻ `rgba(142, 104, 55, 0.35)`, viền `#8e6837`, nâng 4px, bóng `0 18px 40px rgba(20, 20, 20, 0.22)`; thẻ khác `opacity: 0.5`. Vệt ánh kim `#fcf6ba` chạy quanh viền bằng `::after` (conic-gradient xoay `@property --tm-sweep`, mask cắt thành viền, cùng kỹ thuật khung ảnh Tin tức; tạm dừng và ẩn khi không rê chuột). Giảm chuyển động: nền vẫn đảo tối nhưng không nâng, không vệt chạy, không chuyển tiếp, bóng nhỏ.
- **Avatar:** `testimonials.php` chỉ dựng `.__avatar` khi lời kể có ảnh chân dung (`wp_get_attachment_image` trả chuỗi không rỗng). Không ảnh thì chân thẻ chỉ có tên và liệu trình căn trái. `.__credit` `min-height: 61px` nên chân thẻ có ảnh, không ảnh và chỉ có tên cao bằng nhau (đo: một giá trị duy nhất 61px). Viền ảnh `1px rgba(142, 104, 55, 0.45)`.
- **Thẻ rộng hơn:** `--tm-card: clamp(410px, 30vw, 440px)` (432px ở 1440), `min-height: clamp(220px, 16vw, 250px)`, đệm `24px 28px`, hai dãy cách 20px (`--tm-lift: 10px`). Đo ở 1440×900: section 951 → 859px, nội dung 843 → 743px (xem trọn trong một khung nhìn; 1366×768 nội dung 728px).
- **Bóng đổ không bị cắt (thay đổi cấu trúc CSS):** bóng `0 18px 40px` tràn ra ngoài dãy nên không thể để mỗi dãy `overflow: hidden` kèm mask (bị cắt cứng ở đệm 10px). Khi đang trôi (`.is-marquee`, ngoài chế độ gọn: `@media (min-width: 901px) and (min-height: 561px)`): mask 8%/92% và `overflow-x: clip` chuyển lên `.__stage`, dãy `overflow: visible` không mask, stage chừa `--tm-bleed-top: 30px` và `--tm-bleed-bottom: 56px`, bù bằng margin âm nên thẻ nằm đúng chỗ như chế độ tĩnh (không nhảy bố cục khi JS bật trôi). `.__stage` có `grid-template-columns: minmax(0, 1fr)` và dãy `min-width: 0` (không thì dãy không cắt sẽ nở theo track rộng và làm sai phép đo phủ khung). Dãy có thẻ nổi `position: relative; z-index: 2` (`:has(.is-lit)`) để bóng phủ lên dãy kia. Chế độ tĩnh (dãy cuộn thật, vẫn cắt tràn) dùng bóng nhỏ `0 8px 12px -10px`. Vòng focus bàn phím của stage khi đang trôi: `::after` cách mép thẻ 6px (ngoài `outline` thường), tránh vẽ quanh cả vùng chừa bóng.
- **JS (`home.js`, khối testimonials):** dấu vết kích thước (`fingerprint()`: bề rộng stage và bề rộng một nhóm thẻ của từng dãy) thay cho riêng bề rộng stage, nên đổi `--tm-card` mà stage không đổi vẫn dựng lại; `document.fonts.ready` đo lại và dựng lại một lần (quãng và tốc độ từng dãy không lệch dù lúc đầu chữ là font dự phòng). Bề rộng thẻ cố định (`flex: 0 0 var(--tm-card)`) nên bản thân quãng không phụ thuộc font; bước đo lại này theo đúng yêu cầu và vô hại.
- **Lệch có chủ đích so với đặc tả:** (1) liệu trình trên nền sáng dùng `--isha-gold-ink` `#6f5129` (5,2:1) thay `#8e6837` (3,6:1), `#8e6837` chỉ cho nét; (2) chữ vàng nhỏ trên nền tối dùng `#c9a46a` như Tin tức (8,4:1), không `#d4af6a`; (3) màu lời kể dùng token Shadow `#1c1b19`; (4) mốc chế độ gọn là 900px / cao 560px của theme, không 768px.
- **Kiểm thử** (Chrome headless trên trang thật, 53/53 đạt, kịch bản tạm ở scratchpad): thẻ 432px, đệm 24px 28px, min-height 230px, hai dãy cách 20px, lời kể Garamond 18px / 1,65; 15/15 vòng tròn avatar đều có ảnh, không còn chữ cái; chân thẻ một chiều cao duy nhất; hover đo đúng các giá trị ở trên (nền, chữ, liệu trình, dấu “, đường kẻ, viền, bóng, 0,5, chuyển 360ms, vệt chạy `running`); cả hai dãy đứng yên khi hover; giảm chuyển động; điện thoại cảm ứng: chạm giữ thẻ không dính nền tối, không làm mờ thẻ khác (`(hover: hover)` không khớp); chạy lại toàn bộ các kiểm cũ (số lẻ 7 = 4 + 3 ở màn 2560px, 5 lời kể một dãy, đổi cỡ qua lại mốc gọn, không lỗi console). Dữ liệu thử `?tm_n=` dùng mu-plugin tạm, đã xoá.

## 25. Review UI trang chủ: định vị "Bác sĩ", bảng màu brief, chữ, View Transitions, WOFF2 (20/11/2026)

Theo bộ tiêu chí review của người dùng (định vị "Bác sĩ", Quiet Luxury, nền sáng #F4F1EA / #E7E9E6, nền tối #141414, Antique Gold, Garamond Premier Pro + Montserrat). Mục này THẮNG các mục trước khi mâu thuẫn. Bản sao lưu trước khi sửa: `C:\xampp\isha-backups\2026-11-20_before-review-fixes`.

### 25.1 Hero: lời định vị là h1

- `template-parts/home/hero.php`: trường `eyebrow` (lời định vị tiếng Việt, "Phòng khám chuyên khoa da liễu thẩm mỹ") là **h1** nằm trong mặt trời; trường `title` (khẩu hiệu "Beyond beauty, within you") hạ xuống `p.isha-hero__tagline` dưới logo chữ, Garamond nghiêng `--isha-fs-sub`, màu `--tone-ink`; chuỗi chỉ gồm ASCII thì gắn `lang="en"`. Thiếu lời định vị thì khẩu hiệu làm h1. Nhãn trường trong ISHA Core (`home-schema.php`) đổi theo: "Lời định vị (tiêu đề h1, nằm trong vòng tròn)", "Khẩu hiệu (dòng phụ dưới logo)".
- `home.css` khối hero: h1 cỡ `--isha-fs-accent` (40 tới 76px) thay `--isha-fs-display`, weight 400, line-height 1,18 (dấu chồng ễ, ỹ), `max-width: 8.8em` để ngắt sau "chuyên khoa" (không dùng `balance`); desktop chặn `(--sun + 24px) / 2.52`; chế độ gọn `(100vw - 2 x gutter) / 9`. `.isha-hero__eyebrow` bỏ, thay bằng `.isha-hero__tagline`.

### 25.2 Khoảng chuyển tiếp hero → Không gian: lời cam kết

- Thay dấu logo ISHA + "Beauty Clinic" (lặp logo header) bằng `.isha-space__pledge` trong `space.php`: câu cam kết (trường mới `pledge`, mặc định "Trước mỗi liệu trình là một buổi khám: bác sĩ lắng nghe làn da của bạn, rồi mới đề xuất điều cần làm.") và người ký (trường mới `pledge_by`; trống thì `isha_home_data_build_space()` lấy `name`, `role` đã lưu của section Founder). Để trống câu cam kết thì khoảng này ẩn.
- Giao diện: căn trái theo `.isha-wrap`, vạch Gold 1px bên trái chạy suốt câu và người ký (DNA câu trích Founder), câu Garamond `clamp(1.625rem, 1.05rem + 1.9vw, 2.75rem)` / 1,32, `max-width: 21em`; tên Garamond 500, chức danh Montserrat 300. Section có lời cam kết thì đệm trên `clamp(88px, 13vh, 168px)`.

### 25.3 Không gian: bỏ hộp xám

`.isha-space__head` không còn nền, viền, bo, `backdrop-filter`; chữ thẳng mép trái với `.isha-wrap` (`max-width: 34rem`). Độ đọc nhờ hai lớp thêm vào `.isha-space__frame::before` (desktop): vùng tối mềm `radial-gradient` sau khối chữ và lớp tối dần từ mép trái (0,58 → 0 ở 62%). Chế độ gọn giữ lớp phủ cũ (chữ không đè ảnh).

### 25.4 Mối nối sáng / tối: cắt gọn

`home.js` khối sky không còn đặt `--sky-mid-b`, `--sky-mid-t` (màu pha 50%); `home.css` bỏ `--sky-band`: cạnh giáp mối nối đổi cực là nền đặc tới mép, mối nối là một nhát cắt. Cạnh giáp section cùng cực vẫn mờ dần (`--sky-fade`) cho lớp sky pha màu.

### 25.5 Dock liên hệ: ẩn trên desktop

`@media (hover: hover) and (pointer: fine) and (min-width: 901px) and (min-height: 561px)`: `.isha-dock { display: none }`. Header đã có nút Đặt lịch; nút Lên đầu trang tự hạ về mép (scroll-top.js thấy dock `display: none`). Máy cảm ứng và chế độ gọn giữ dock.

### 25.6 Màu

- `--isha-shadow` #1c1b19 → **#141414** (`main.css`, `editor.css`, `theme.json`, `theme-color` ở `header-home.php`); token mới `--isha-paper: #f4f1ea`, tông mới `[data-tone="paper"]` (chữ #141414 17,4:1, gold-ink 6,6:1).
- Registry: `space`, `events`, `testimonials` → `paper`; `services` → `day`. Bỏ dùng `day-warm` #e6d8c6 và `golden` #ccb498 ở trang chủ (định nghĩa tông vẫn giữ). Hàng sự kiện đang chọn dùng `--tone-ph` thay màu Daylight 28%. Bộ kiểm hồi quy (`php/wp-read.php`) đổi kỳ vọng tông.
- Vàng: bộ foil `--isha-metal-*` (#fcf6ba, #bf953f, #aa771c, #b38728) → vàng cổ (#eadcbc, #b59466, #8e6837, #a3814f); `--metal-dark` → `#8e6837, #e2d2ad, #c9a46a, #a3814f, #e2d2ad` (cả `--lux-metal-dark` ở `booking.css`); vệt ánh kim `#fcf6ba` → `var(--isha-metal-light)`.
- Nút: `.isha-btn--primary` nền Gold **phẳng** (rê chuột: gold-hover), bỏ gradient trượt và vạch sáng trong; gỡ lớp phủ của nút Đặt lịch trên header ở `home.css`, `about.css`, `contact.css`, `post-single.css`, `service-single.css` (dùng `.btn--primary` phẳng của `main.css`).

### 25.7 Chữ

- Chữ hoa chỉ còn ở nút (`.isha-btn`, `.btn`, dock, `.isha-ring__more`) và menu, giãn chung **0,12em**. Chữ thường (giãn 0,02em, cỡ tăng nhẹ cho bù): `.isha-link`, nhóm dịch vụ trên thẻ vòng, tab và tháng của Sự kiện, "Đọc trọn vẹn câu chuyện" (#c9a46a), chức danh và HUD của Founder, nhãn ô giữ chỗ, tên thương hiệu và tiêu đề cột ở chân trang (`main.css`), nhãn liên hệ `.isha-info__label` (ISHA Core).
- Bỏ căn đều hai bên: câu trích và câu chuyện Founder, `card-ring.css` (`text-align: left`, `text-wrap: pretty`).
- Garamond bản Display vẫn dùng cho chữ nhỏ (17–24px), theo tài liệu (docs/prompt-trang-chu.md, bảng cỡ chữ): chữ thân bình thường (normal), loại Note (chú thích, chữ ký) Garamond 400 nghiêng `--isha-fs-note`. Chờ ý kiến khách hàng về bản Text/Subhead rồi cập nhật sau (quyết định của người dùng 20/11/2026). Theo đó tên người ký lời cam kết (`.isha-space__pledge-name`) là Note: Garamond 400 nghiêng `--isha-fs-note`.

### 25.8 View Transitions trang chủ ↔ Không gian ISHA

- `inc/space.php` (`isha_space_view_transition`, `wp_head`): trang chủ và trang danh sách Không gian in `@view-transition { navigation: auto }` (chỉ khi không giảm chuyển động).
- `home.js` khối space: lúc `pageswap` sang đích của nút CTA, nếu khung ảnh đang trong tầm nhìn thì khung ảnh đang hiện nhận `view-transition-name: isha-room` (gỡ tên cũ trước).
- `space.css`: hero trang danh sách mang `isha-room` (có ảnh: `.isha-about-hero__media`; chưa có ảnh: cả `.isha-about-hero`); nhóm `isha-room` 900ms `cubic-bezier(0.22, 1, 0.36, 1)`, cả trang chuyển mờ 450ms. Hiện trang Không gian chưa có ảnh hero trong CMS nên ảnh phòng giãn phủ khối hero rồi tan vào nền tối; đặt ảnh ở khung "Hero banner" của trang thì ảnh phòng giãn đúng thành ảnh hero.

### 25.9 Font WOFF2

8 tệp (Garamond Disp, ItDisp, MedDisp, SmbdDisp; Montserrat Light, Regular, Medium, SemiBold) subset bằng HarfBuzz (harfbuzzjs 0.4.7) rồi nén bằng wawoff2 2.0.1: Basic Latin, Latin-1, Latin Extended-A/B, dấu tổ hợp, Latin Extended Additional (tiếng Việt), dấu câu, tiền tệ, mũi tên, toán, hình học; giữ tính năng mặc định của HarfBuzz (kern, liga, ccmp, mark, mkmk, locl...) cùng lnum, onum, pnum, tnum, frac, sups, ordn, case, zero. Garamond khoảng 380 → 70 KB mỗi tệp, Montserrat 193 → 25 KB. Đủ cho tiếng Anh (chỉ cần Basic Latin); ngôn ngữ khác bảng chữ Latin (Hàn, Trung, Nga) sẽ cần làm lại subset. `@font-face` ở `main.css`, `editor.css`, `home.css` đặt WOFF2 trước, OTF/TTF gốc làm dự phòng; preload ở `inc/enqueue.php` đổi sang WOFF2. Script subset nằm ở scratchpad của phiên, không trong dự án.

### 25.10 Đối tác: giữ mặt trời, làm chìm màu và nét

Mặt trời là dấu ấn chủ đề của ISHA (người dùng cố ý đặt ở Đối tác), không bỏ. `.isha-partners__vector`: opacity 0,78; gradient vàng cổ tối (#6f5129, #a3814f, #c2a273, #8e6837) thay `--metal-dark`; nét mảnh lại bằng mặt nạ chồng 4 bản `vector-2.png` lệch ±`--pt-thin` theo hai trục rồi lấy phần giao (`mask-composite: intersect`, `-webkit-mask-composite: source-in`), `--pt-thin: 0.17cqi` (khung `.isha-partners__orbit` là `container-type: inline-size`; chế độ gọn `0.1cqi`): nét khoảng 4,3 → 2px ở khung 660px, khoảng 1,6px ở khung 350px; lớp mặt nạ thứ năm `radial-gradient` làm đầu tia tan dần. `--pt-line` (viền huy hiệu, vòng tâm) 50% → 34%, vầng sáng nền 0,16 → 0,08.

### 25.11 CSS scroll-driven animations: không triển khai

Quy tắc của người dùng: độ phức tạp từ 40% trở lên thì không làm. Hiệu ứng theo cuộn ở trang chủ: iris hero (ghim, quán tính 0,18, tâm đo bằng JS, nối intro và video), lớp sky (pha Oklab theo mối nối đo động), bộ ảnh 3D Founder (`founder-gallery/gallery.js` 33 KB, shader), iris cảnh kết, parallax Không gian. Chỉ parallax Không gian (khoảng 12 dòng) chuyển sang `animation-timeline: view()` đơn giản; phần còn lại phải viết lại và giữ song song đường JS cho trình duyệt chưa hỗ trợ (Firefox), ước tính trên 90% khối lượng là phức tạp. Giữ nguyên lõi chuyển động JS.

### 25.12 Kiểm thử

Chrome headless trên trang thật (1440×900, 390×844): đúng một h1 ("Phòng khám chuyên khoa da liễu thẩm mỹ"), khẩu hiệu `lang="en"`, dock `display: none` ở desktop, tông từng section đúng, mối nối đổi cực không còn gradient, trong `main` không còn chữ hoa ngoài nút, không còn căn đều, 7 tệp font tải đều là WOFF2, không lỗi console. View Transition: `pageswap` gắn `isha-room` cho ảnh đang hiện (ảnh 0), `pagereveal` ở trang Không gian có `viewTransition` và chạy xong. Bộ kiểm hồi quy: 568 PASS, 5 FAIL, 1 WARN, giống hệt hai lần chạy trước khi sửa (5 FAIL do chưa bài Không gian nào được đánh dấu "hiện ở trang chủ").
