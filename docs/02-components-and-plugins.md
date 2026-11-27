# 02. Thành phần, dữ liệu và component (lập từ code)

Lập ngày 27/11/2026 bằng cách đọc trực tiếp `themes/ISHA/` và `plugins/isha-core/`. Không chép từ docs cũ. `file:dòng` tính từ `wp-content/` (theme: `themes/ISHA/...`, plugin: `plugins/isha-core/includes/...`); nơi nào chưa kiểm chứng ghi "(chưa xác minh)". Tài liệu đi kèm: `docs/01-source-structure.md` (cây thư mục, luồng nạp CSS/JS, template hierarchy, danh sách plugin).

Không có trong code: `register_rest_route`, `register_post_meta`, `register_meta`, `register_block_type`, `wp_schedule_event`/cron, `add_rewrite_rule`, `add_submenu_page`. REST chỉ là endpoint mặc định của WordPress cho các CPT/taxonomy có `show_in_rest`.

## 1. Custom Post Type (8)

| Slug | Nhãn | Supports | Rewrite slug | has_archive | show_in_rest | Ghi chú | Đăng ký |
|---|---|---|---|---|---|---|---|
| `isha_service` | Dịch vụ | title, editor, thumbnail, page-attributes | `dich-vu` | false | true | public; vị trí menu 21; taxonomy `isha_service_cat` | `class-post-types.php:20-39` |
| `isha_policy` | Chính sách khách hàng | title, editor, page-attributes | `chinh-sach` | false | true | public; vị trí 27 | `class-post-types.php:58-77` |
| `isha_appointment` | Lịch hẹn | title | - (không public) | - | false (mặc định) | `public=false`, `show_ui=true`, `create_posts=do_not_allow`, vị trí 28 | `class-post-types.php:80-99` |
| `isha_space` | Không gian ISHA | title, editor, thumbnail, page-attributes | `khong-gian-isha` | false | true | public; vị trí menu do `ISHA_Core_Space_Fields::admin_menu()` đặt 20.7 | `class-post-types.php:116-160` |
| `isha_event` | Sự kiện | title, editor, excerpt, thumbnail, **elementor** | `su-kien` | false | true | public; vị trí 22; taxonomy `isha_event_type` | `class-post-types.php:168-217` |
| `isha_testimonial` | Lời kể khách hàng | title, thumbnail | - (`rewrite=false`) | false | false | `public=false`, `show_ui=true`, `publicly_queryable=false`; vị trí 24 | `class-post-types.php:264-315` |
| `isha_article` | Tin tức (số ít "Bài viết") | title, editor, excerpt, thumbnail, **elementor** | `tin-tuc` | false | true | public; vị trí 23; taxonomy `isha_article_cat` | `class-post-types.php:325-374` |
| `isha_contact` | Liên hệ (hộp thư) | title | - (`rewrite=false`) | - | false | `public=false`, `show_ui=true`, `create_posts=do_not_allow`, vị trí 29; đăng ký ở `init` | `class-contact.php:26,42-66` |

Tất cả đều `with_front=false` (trừ ba CPT không public). Danh sách của Tin tức/Sự kiện/Không gian là **Trang** (Page) chứ không phải archive; xem hierarchy ở file 01.

## 2. Taxonomy (3) và term meta

| Slug | Nhãn | Gắn với | Hierarchical | Rewrite slug | show_in_rest | Khác | Đăng ký |
|---|---|---|---|---|---|---|---|
| `isha_service_cat` | Nhóm dịch vụ | `isha_service` | true | `nhom-dich-vu` | true | có trang riêng `taxonomy-isha_service_cat.php` | `class-post-types.php:41-54` |
| `isha_event_type` | Loại sự kiện | `isha_event` | false | `loai-su-kien` | true | `show_admin_column`; archive bị 301 về danh sách `?chuyen-muc=` (`editorial-list.php:818-828`) | `class-post-types.php:219-256` |
| `isha_article_cat` | Chủ đề | `isha_article` | true | `chu-de` | true | `show_admin_column`; archive bị 301 như trên | `class-post-types.php:376-413` |

| Term meta (taxonomy) | Giá trị lưu | Nhãn trong admin | Sanitize khi lưu/đọc | Nơi ghi | Nơi đọc |
|---|---|---|---|---|---|
| `_isha_featured` (service_cat) | `'1'` hoặc không có | Dịch vụ tiêu biểu | so `'1'` | `ISHA_Core_Term_Fields::save` (`class-term-fields.php`, hook `created_/edited_isha_service_cat`) | `home/data.php:577,583` |
| `_isha_cover_id` | ID ảnh (int) | Ảnh bìa (4:5) | `ISHA_Core_Admin_Media::sanitize('image')` | như trên | `services-hero.php:105,166`, `home/data.php` |
| `_isha_cover_video_id` | ID video | Video bìa (tuỳ chọn) | `sanitize('video')` | như trên | `home/data.php` |
| `_isha_intro` | HTML gọn (không `<p>`, hiển thị qua `wpautop`) | Nội dung giới thiệu (wp_editor gọn) | `::sanitize_intro()` → `wp_kses` theo `intro_allowed_html()`; lọc lại ở theme | như trên (chỉ màn "Sửa") | `service-group.php:46-58` |
| `_isha_ba_desc` | text nhiều dòng ≤ 600 ký tự | Mô tả ngắn hiệu quả liệu trình | `sanitize_textarea_field` | như trên | `service-group.php` |
| `_isha_ba_before`, `_isha_ba_after` | mảng ID ảnh có thứ tự, ≤ 20 | Ảnh trước / sau điều trị | `sanitize('gallery')`; ID hỏng giữ vị trí (0) | như trên | `service-group.php:60-72` |
| `_isha_order` | int ≤ ±999999999 | Thứ tự hiển thị | `::sanitize_order()` | như trên | `ISHA_Core_Term_Fields::ordered_terms()` (hero nan quạt `services-hero.php:86`, ô chọn dịch vụ `forms.php:126`, `service-single.php:18,41`) |
| `_isha_hero_image_desktop`, `_isha_hero_image_mobile`, `_isha_hero_video_desktop`, `_isha_hero_video_mobile` (service_cat) | ID tệp | Ảnh/Video nền (rộng/điện thoại) | `sanitize('image'/'video')` | `ISHA_Core_Page_Hero_Fields::save_term` (`class-page-hero-fields.php`, hook `edited_isha_service_cat`) | `services-hero.php:159` (`get_term`) |
| `_isha_service_group` (article_cat) | ID term `isha_service_cat` | Nhóm dịch vụ gợi ý | số nguyên + kiểm term còn tồn tại | `ISHA_Core_Article_Fields::save_term` (nonce `isha_save_article_term`/`isha_article_cat_nonce`) | `post-single.php:119` (`service_group_of`) |
| `_isha_seed_key` (term, và cả post `isha_service`); `_isha_seed_legacy` (chỉ post) | chuỗi mã seed / `'1'` | (không hiện) | `wp_slash` | `class-seed.php:509,630,722,842` | `class-seed.php:627,667,795` (chống tạo trùng) |

## 3. Post meta thực tế

### 3.1 Bảng theo CPT

Mô hình chung: meta box native, khoá bắt đầu bằng `_` (không hiện ở Custom Fields), nonce + `current_user_can('edit_post')`, bỏ qua autosave/revision, giá trị rỗng thì `delete_post_meta`, ghi bằng `update_post_meta( ..., wp_slash() )`. Hàm đọc làm sạch lại.

| CPT / Page | Meta key | Kiểu / định dạng lưu | Nhãn trong admin | Sanitize | Ghi (hộp, nonce) | Đọc |
|---|---|---|---|---|---|---|
| `isha_service` | `isha_duration` | text | Thời gian liệu trình | do ACF | ACF local group `group_isha_service` (`class-service-fields.php:67-127`, vị trí `acf_after_title`) | `ISHA_Core_Service_Fields::get()` (`:43-59`) → `home/data.php:682`, `service-group.php:251`, `service-single.php:134`, shortcode `[isha_services]` |
| `isha_service` | `isha_short_desc` | textarea (3 dòng) | Mô tả ngắn | do ACF | như trên | như trên |
| `isha_service` | `isha_gallery` | mảng ID ảnh | Ảnh liệu trình | `absint`, chỉ ảnh hợp lệ, bỏ trùng | ACF PRO (gallery) hoặc meta box dự phòng `isha_gallery_box` (nonce `isha_save_gallery`/`isha_gallery_nonce`, input `isha_gallery_ids`) (`:140-182`) | như trên |
| `isha_service` | `isha_steps` | textarea, mỗi dòng một bước | Các bước liệu trình | do ACF | ACF | như trên |
| `isha_policy` | `isha_card_front`, `isha_card_back` | ID ảnh | Ảnh thẻ mặt trước / sau (bắt buộc) | ACF; khi đọc `absint` + `wp_attachment_is_image` | ACF local group `group_isha_policy` (`class-policy-fields.php:58-96`) | `ISHA_Core_Policy_Fields::get()` → `policy-single.php:75`, `[isha_policies]`, `[isha_lp_*]` |
| `isha_policy` | `isha_card_color` | `#rrggbb` | Màu thẻ | khi đọc `sanitize_hex_color` | ACF color_picker | `[isha_lp_cards_member]` |
| `isha_event` | `_isha_event_start`, `_isha_event_end` | `Y-m-d H:i` theo múi giờ site | Bắt đầu / Kết thúc (tuỳ chọn) | `parse_local()` (kiểm ngày thật, năm 2000-2100, kết thúc phải sau bắt đầu) | hộp `isha_event_fields` "Thông tin sự kiện", nonce `isha_save_event`/`isha_event_nonce` (`class-event-fields.php:268` thêm hộp, `:277` render, `:378` save) | `ISHA_Core_Event_Fields::get()` → `home/data.php:1021`, `editorial-list.php:458,480`, `post-single.php:225,268` |
| `isha_event` | `_isha_event_start_ts`, `_isha_event_end_ts` | timestamp UTC | (không hiện) | số nguyên dương | cùng hộp | sắp xếp / so sánh |
| `isha_event` | `_isha_event_location` | text ≤ 200 | Địa điểm | `sanitize_text_field` + cắt | cùng hộp | trang chi tiết, thẻ trang chủ |
| `isha_event` | `_isha_event_map_url` | URL http/https ≤ 2048 | Link bản đồ (tuỳ chọn) | `sanitize_url()` tự viết (http(s), host hợp lệ) | cùng hộp | nút "Chỉ đường" (`post-single.php:171`) |
| `isha_event` | `_isha_event_register_url` | URL | Link đăng ký | như trên | cùng hộp | chỉ banner trang chủ (`home/data.php:1072`); trang chi tiết không dùng |
| `isha_event` | `_isha_event_featured` | `'1'` | Nổi bật | so `'1'` | cùng hộp | chọn sự kiện nổi bật ở trang chủ |
| `isha_event` | `_isha_event_portrait_id` | ID ảnh | Ảnh dọc cho điện thoại (4:5) | `ISHA_Core_Admin_Media::sanitize('image')` | cùng hộp | ảnh bìa dọc ở trang chi tiết sự kiện (`post-single.php:251,269`); trang chủ không còn dùng (`home/data.php:1074`) |
| `isha_event` | `_isha_event_summary` | text 1 dòng ≤ 200 | Nội dung ngắn | `sanitize_textarea_field` + gộp khoảng trắng + cắt | cùng hộp | thẻ bài (`editorial-list.php:119`) |
| `isha_article` | `_isha_article_featured` | `'1'` | Nổi bật | so `'1'` | hộp `isha_article_fields` "Thông tin bài viết", nonce `isha_save_article`/`isha_article_nonce` (`class-article-fields.php:233` thêm hộp, `:272` render, `:331` save) | `ISHA_Core_Article_Fields::get()` → `home/data.php:1223`, `post-single.php:82` |
| `isha_article` | `_isha_article_summary` | text 1 dòng ≤ 200 | Nội dung ngắn | như event | cùng hộp | thẻ bài |
| `isha_article` | `_isha_article_banner` | ID ảnh | Ảnh banner ngang (16:10) | `sanitize('image')` | cùng hộp | bài nổi bật trang chủ, ảnh bìa chi tiết |
| `isha_testimonial` | `_isha_tm_quote` | text ≤ 300 | Lời kể | `sanitize_quote()` = `sanitize_textarea_field` + cắt | hộp `isha_testimonial_fields` "Lời kể khách hàng", nonce `isha_save_testimonial`/`isha_testimonial_nonce` (`class-testimonial-fields.php:118` thêm hộp, `:127` render, `:203` save) | `home/data.php:1310` |
| `isha_testimonial` | `_isha_tm_service_id` | ID `isha_service` | Liệu trình đã dùng | `clean_service_id()` (kiểm post type/trạng thái) | cùng hộp | như trên |
| `isha_space` | `_isha_space_home` | `'1'` | Xuất hiện bài này ở trang chủ | so `'1'` | hộp `isha_space_fields` "Thông tin không gian", nonce `isha_save_space`/`isha_space_nonce` (`class-space-fields.php:141` thêm hộp, `:150` render, `:220` save) | `ISHA_Core_Space_Fields::get/posts()` → `space.php:91,108`, `home/data.php` |
| `isha_space` | `_isha_space_subtitle` | text 1 dòng ≤ 120 | Tiêu đề nhỏ | `clean_line` | cùng hộp | trang danh sách/chi tiết |
| `isha_space` | `_isha_space_summary` | text ≤ 400 | Mô tả ngắn | `clean_line` + `sanitize_textarea_field` | cùng hộp | như trên |
| `isha_space` | `_isha_space_gallery` | mảng ID ảnh ≤ 12 | Ảnh không gian | `sanitize('gallery')` + cắt | cùng hộp | trang chi tiết |
| `isha_appointment` | `_isha_name`, `_isha_phone`, `_isha_email`, `_isha_service_id`, `_isha_date`, `_isha_time`, `_isha_note`, `_isha_consent_at`, `_isha_source` (`popup`/`page`), `_isha_status` (`new/confirmed/done/cancelled`) | chuỗi/ID | Hiển thị chỉ đọc ở hộp "Chi tiết lịch hẹn"; chỉ **Trạng thái** sửa được | xem `ISHA_Core_Booking::process()` (`class-booking.php:103-110`) | `process()` (`wp_insert_post meta_input`, `:150-167`); hộp `isha_appt`, nonce `isha_appt_status`/`isha_appt_nonce` (`:271-316`) | admin + email thông báo |
| `isha_contact` | `_isha_name`, `_isha_phone`, `_isha_email`, `_isha_topic` (khoá `tu-van/thanh-vien/phan-hoi/hop-tac/khac`), `_isha_message` (10 đến 1500 ký tự), `_isha_consent_at`, `_isha_status` (`new/replied/done`) | chuỗi | Hộp "Chi tiết lời nhắn" (chỉ Trạng thái sửa được) | `ISHA_Core_Contact::process()` (`class-contact.php:129-133`) | `process()` (`:169-184`); hộp `isha_contact_box`, nonce `isha_contact_status`/`isha_contact_status_nonce` (`:277-323`) | admin + email |

Lưu ý: không có khoá "ngày đăng ký/đồng ý" nào khác; `_isha_status = 'new'` được dùng để đếm bong bóng menu (`class-forms.php:131-176`).

### 3.2 Page (trang) có meta box

| Page / điều kiện | Meta key | Cấu trúc | Hộp (nhãn) | Sanitize | Ghi | Đọc |
|---|---|---|---|---|---|---|
| Page dùng `template-services.php`, `template-about.php`, `template-space.php` (`ISHA_Core_Page_Hero_Fields::TEMPLATES`) | `_isha_hero_image_desktop`, `_isha_hero_image_mobile`, `_isha_hero_video_desktop`, `_isha_hero_video_mobile` (ID tệp), `_isha_hero_desc` (text ≤ 280) | 5 khoá | `isha_page_hero` "Hero banner", nonce `isha_save_page_hero`/`isha_page_hero_nonce`, input `isha_page_hero[...]` | `ISHA_Core_Admin_Media::sanitize`, `clean_desc` | `save_post_page` (`class-page-hero-fields.php:48-50`) | `about.php:67`, `services-hero.php:135` |
| Page dùng `template-about.php` | `_isha_about_story`, `_isha_about_philosophy`, `_isha_about_values` | **mảng** `{enabled '1'/'0', title ≤ 120, subtitle ≤ 200, content (HTML gọn), image (ID)}`; chỉ lưu khoá có giá trị | `isha_about_fields` "Nội dung trang Giới thiệu", nonce `isha_save_about`/`isha_about_nonce`, input `isha_about[<section>][...]` | `sanitize_content()` (wp_kses), `sanitize_text_field`, media | `save_post_page` (`class-about-fields.php:61`) | `about.php:109` |
| Page là **trang chủ** (`ISHA_Core_Home_Fields::page_id()`, xem dưới) | `_isha_home_<id>` cho 11 id (hero, space, results, services, `founder-story`, events, articles, testimonials, process, partners, cta). Giữ nguyên dấu gạch ngang trong id | **mảng** `{enabled, <field>..., <group>: [ {..}×count ]}`; chỉ lưu giá trị khác rỗng; ô trống = dùng mặc định trong `isha_home_defaults()` | `isha_home_fields` "Nội dung trang chủ" (tab theo section), nonce `isha_save_home`/`isha_home_nonce`, input `isha_home[<section>][...]` | `sanitize_section()` theo lược đồ (`class-home-fields.php:251-283,462-515`) | `save_post_page`, chỉ khi `page_id() === $post_id` (`:935-966`) | `home/data.php:412-427` |
| (dữ liệu cũ) | `_isha_home_story`, `_isha_home_philosophy`, `_isha_home_values`, `_isha_home_vision` | không còn hộp nhập | - | - | - | không còn nơi đọc (còn trong DB theo `home-schema.php:9-11`) |

**Trang chủ: cách xác định trang** (`class-home-fields.php:174-196`): filter `isha_core/home_page_id` → `page_on_front` (khi `show_on_front=page` và trang đó **không** dựng Elementor) → Page slug `trang-chu` → 0. Hộp này tắt trình soạn khối của trang chủ (`filter_block_editor`, `:596-598`); menu admin cấp 1 "Trang chủ" vị trí 20.5 (`:103`).

**Lược đồ "Nội dung trang chủ"** (`isha-core/includes/home-schema.php`, nguồn duy nhất; mặc định ở `themes/ISHA/inc/home/data.php:63`, phải khớp khoá). Mỗi section có công tắc `enabled` do code tự thêm.

| Tab (section id) | Nhãn | Số trường | Khoá ví dụ | Media / giới hạn |
|---|---|---|---|---|
| `hero` | Hero và intro | 10 | `intro_enabled` (bool), `intro_text`, `eyebrow` (h1), `title`, `description`, `button_label` | `video_landscape`, `video_portrait`, `poster_landscape`, `poster_portrait` |
| `space` | Không gian ISHA | 4 | `title`, `lead`, `link_label`, `gallery` | gallery tối đa 12 (ảnh dự phòng) |
| `results` | Hiệu quả liệu trình | 4 | `title`, `lead`, `note`, `gallery` | gallery tối đa 5 (ảnh trước/sau đã ghép) |
| `services` | Dịch vụ tiêu biểu | 3 | `title`, `lead`, `link_label` | nhóm lấy từ term meta `_isha_featured` |
| `founder-story` | Founder | 7 | `title`, `quote`, `story`, `name`, `role`, `portrait`, `gallery` | gallery tối đa 20 |
| `events` | Workshop & sự kiện | 3 | `title`, `lead`, `link_label` | sự kiện lấy từ CPT |
| `articles` | Tin tức | 3 | `eyebrow`, `title`, `link_label` | bài lấy từ CPT |
| `testimonials` | Lời kể khách hàng | 2 | `eyebrow`, `title` | lời kể lấy từ CPT |
| `process` | Quy trình | 1 + nhóm `items` ×5 `{name, desc}` | `title` | ghi chú trong schema: đang ẩn |
| `partners` | Đối tác | 2 | `title`, `gallery` | gallery tối đa 16 (logo) |
| `cta` | Cảnh kết | 4 | `title`, `description`, `background_image`, `background_video` | - |

Tổng: 43 trường lẻ + 1 nhóm lặp (5 mục × 2 trường) + 11 công tắc `enabled`. Kiểu: `text`, `textarea`, `bool`, `image`, `video`, `gallery` (`class-home-fields.php:49`).

### 3.3 Meta của core / bên thứ ba mà code đọc

`_wp_page_template` (tìm trang theo mẫu: `helpers.php:164`, `forms.php:34`, `space.php:58`), `_elementor_edit_mode` (`builder`), `_thumbnail_id`, `_wp_attachment_image_alt` (`service-single.php:97`, `services-cinematic.php:83`, `space.php:234`, `founder-story.php:60,102`), `menu_order` (thứ tự dịch vụ/chính sách/không gian).

## 4. Option, theme_mod, transient, cron

| Tên | Loại | Ai ghi | Ai đọc | Ghi chú |
|---|---|---|---|---|
| `isha_core_settings` | option (mảng: `phone, email, notify_email, address, hours, map_url, facebook, instagram, tiktok, youtube, zalo`) | `register_setting('isha_core_group', ...)` + `sanitize` theo kiểu (`class-settings.php:55-91`), trang `ISHA Clinic` (slug `isha-core`, `manage_options`, vị trí 24, `:43-51`) | `isha_opt()` (`helpers.php:19-25`), `isha_core_get()` (`isha-core.php:120-123`), `ISHA_Core_Forms::notify` (`notify_email`) | Xoá khi gỡ plugin (`uninstall.php:13`). Autoload: mặc định WP (code không truyền); chưa xác minh DB |
| `isha_core_rewrite_ver` | option (`'3'`) | `isha-core.php:91,101` | `isha-core.php:87` | Flush rewrite một lần khi `ISHA_CORE_REWRITE_VERSION` đổi; xoá khi gỡ plugin |
| `isha_default_content_installed` | option (`1`) | `demo-content.php:142` | `demo-content.php:83` | Chỉ tạo 9 trang + menu `ISHA – Menu chính` khi kích hoạt theme lần đầu, đặt `show_on_front/page_on_front/page_for_posts` (`:111-113`) |
| `show_on_front`, `page_on_front`, `page_for_posts` | option core | `demo-content.php:111-113` (cài mới) | `class-home-fields.php:180-181`, `class-legacy-urls.php:112` | Giá trị hiện tại: chưa xác minh (dump 18/11: `page`, 10, 15) |
| theme_mods của ISHA | `theme_mods_ISHA` | Customizer, panel `isha_panel` (`customizer.php:21-205`) | `isha_mod()` (`helpers.php:33-36`), `isha_landing_only()` (`:86`) | Khoá: `isha_landing_only` (mặc định **true**), `isha_hero_{eyebrow,title,subtitle,btn_text,btn2_text,image}`, `isha_{intro_title,intro_text,cta_title,cta_text}`, `isha_{footer_about,footer_invite,footer_credit}`, `isha_404_image`; thêm `custom_logo`, `nav_menu_locations.primary` |
| `isha_svc_{term_id}` | transient 12 giờ (`ISHA_SVC_CACHE_TTL`) | `isha_svc_data()` (`services-cinematic.php:130`) | cùng hàm (`:63`) | Xoá khi `save_post_isha_service`, `before_delete_post`, `created/edited_isha_service_cat`, `set_object_terms` (`:148-183`) |
| `isha_rl_{form}_{md5}` | transient 1 giờ (bộ đếm gửi form) | `ISHA_Core_Forms::bump` (`class-forms.php:75-78`) | `::limited` (`:66`) | Khoá băm theo IP + salt, không lưu IP thô |
| `isha_seed_result_{user_id}` | transient 300 giây | `class-seed.php:985` | `:861` | Kết quả nút seed |
| `option_elementor_optimized_image_loading` | filter ép `false` | `setup.php:208` | Elementor | Không ghi DB |
| Cron | không có | - | - | Không có `wp_schedule_*` trong theme/plugin |

## 5. Shortcode (23)

| Tag | Tham số (mặc định) | Render ở | Dùng ở |
|---|---|---|---|
| `[isha_services]` | `count`=6, `columns`=3 (kẹp 1-4), `category`="" (slug, phân cách phẩy), `orderby`=menu_order (cho phép menu_order/title/date/rand) | `class-shortcodes.php:44-109` | (chưa xác minh trang nào dùng; nội dung trang lưu trong DB) |
| `[isha_policies]` | `count`=-1, `view`=full/list | `:145-213` | `templates/template-policies.php:26` với `view="list"` |
| `[isha_lp_card_circle]` | không | `:224-267` (cần từ 2 chính sách có ảnh; nạp `isha-lp-stack.js`) | (chưa xác minh) |
| `[isha_lp_cards_member]` | `order`=asc/desc | `:339-424` (nạp `isha-lp-ladder.js`) | (chưa xác minh; CSS `policy-membership.css` cho trang `chinh-sach-khach-hang`) |
| `[isha_booking_form]` | `title`="" | `:427-505` (form cũ gửi `admin-post.php`, không AJAX) | (chưa xác minh) |
| `[isha_contact_info]` | `show`="phone,email,address" (thêm `hours`) | `:508-528` | (chưa xác minh) |
| `[isha_opening_hours]` | không | `:531-542` | (chưa xác minh) |
| `[isha_map]` | `height`=400; chỉ nhận host `www.google.com`/`maps.google.com` qua https | `:545-562` | (chưa xác minh) |
| `[isha_social]` | không | `:565-580` | (chưa xác minh) |
| `[isha_button]` | `url`="#", `text`, `style`=primary/outline | `:583-595` | (chưa xác minh) |
| `[isha_cta]` | `title`, `text`, `button_text`, `button_url` | `:598-613` | (chưa xác minh) |
| `[isha_home_hero]`, `_space`, `_results`, `_services`, `_founder_story`, `_events`, `_articles`, `_testimonials`, `_process`, `_partners`, `_cta` (11 tag) | `count` chỉ với events (3), articles (4), testimonials (20); kẹp 1-12, riêng testimonials 1-20 | đăng ký ở `init` (`home/shortcodes.php:109-119`) → `template-parts/home/<id>.php`; lỗi một section được cách ly (`:62-73`) | `isha_home_render()` (`home/helpers.php:560`); `[isha_home_cta]` qua `isha_do_shortcode` trong 6 template: about, space, `single-isha_{space,service,article,event}` |
| `[isha_service_wheel]` | `group`/`taxonomy_id` (term ID), `exclude` (post ID), `title`, `lead`, `all`=0 | `inc/service-wheel.php:97-184` | `single-isha_service.php:24` (gọi thẳng hàm) |

## 6. AJAX / admin-post / REST

Không có REST tuỳ biến. Bảng đã đối chiếu 100% với code.

| Action | Quyền | Nonce | Tham số vào | JSON trả về | Chống lạm dụng | PHP | JS gọi |
|---|---|---|---|---|---|---|---|
| `isha_form_nonce` (GET) | `wp_ajax_` và `_nopriv_` | không (là nơi cấp) | không | `{success:true,data:{isha_booking:"<nonce>",isha_contact:"<nonce>"}}`; có `nocache_headers()` | không (nonce của khách không đăng nhập là công khai) | `class-forms.php:31-32,39-46` | `booking.js:29` (nhớ 10 phút) |
| `isha_booking` (POST) | public + đăng nhập | action `isha_booking`; tham số `isha_booking_nonce`; popup lấy qua `isha_form_nonce`, form trang in sẵn `wp_nonce_field` (`booking-form.php:35`) | `isha_name*`, `isha_phone*` (8-20 ký tự, ≥ 8 chữ số), `isha_email`, `isha_service` (ID `isha_service` đã publish), `isha_date*` (`Y-m-d`, ≥ hôm nay), `isha_time*` (09:00 đến 18:00 mỗi 30 phút, lọc `isha_core/booking_slots`), `isha_note` (≤ 1000), `isha_consent*` (`'1'`), `isha_source` (popup/page), `isha_website` (bẫy) | Thành công: `{success:true,data:{status:"success",message,errors:[]}}`. Lỗi: `{success:false,data:{status:"invalid"/"limit"/"error",message,errors:{<field>:<câu báo>}}}`. Luôn HTTP 200. `error` = nonce sai hoặc lỗi lưu | Honeypot `isha_website` (có chữ thì trả `success` giả, không lưu); giới hạn 5 lần thành công/giờ/IP (`MAX_PER_HOUR`, `class-booking.php:20,98`); không có captcha | `class-booking.php:24-27,82-207` | `booking.js:76-146` (`data-isha-form="isha_booking"`) |
| `isha_contact` (POST) | public + đăng nhập | action `isha_contact`; tham số `isha_contact_nonce` (in sẵn ở `contact-form.php:28`, `booking.js` ghi đè bằng nonce mới) | `isha_name*`, `isha_phone*`, `isha_email`, `isha_topic` (khoá `topics()`), `isha_message*` (10-1500), `isha_consent*`, `isha_website` | như trên (`message` theo `ISHA_Core_Contact::messages()`) | như trên: honeypot, 5/giờ/IP (`class-contact.php:21,124`) | `class-contact.php:27-30,109-239` | `booking.js` (`data-isha-form="isha_contact"`) |
| `isha_booking`, `isha_contact` qua `admin-post.php` (POST, không JS) | `admin_post_` và `_nopriv_` | như hai dòng trên | như trên | Không trả JSON: `wp_safe_redirect` về trang trước kèm `?isha_booking=<status>` (`#isha-booking`) hoặc `?isha_contact=<status>` (`#isha-contact-form`) | như trên | `class-booking.php:24-25,184-189`; `class-contact.php:27-28,218-223` | form HTML (`action=admin-post.php`) |
| `isha_live_search_nonce` (GET) | public + đăng nhập | không | không | `{success:true,data:{nonce}}`; `nocache_headers()` | không | `live-search.php:253-258` | `live-search.js:220` (chỉ khi gặp 403) |
| `isha_live_search` (POST) | public + đăng nhập | action `isha_live_search` (`check_ajax_referer`, tham số `nonce`); tạo ở `live-search.php:351` rồi in vào `window.ISHA.search.nonce` | `q` (≤ 80 ký tự), `lang` (≤ 12, `sanitize_key`), `nonce` | Thành công: `{success:true,data:{groups:[{key:"service/article/event",label,count,items:[{title,url,thumb,badge}]}],total}}` (rỗng: `groups:[]`, `total:0`). Nonce sai: `{success:false,data:{code:"nonce"}}` với HTTP **403** | không có rate-limit/honeypot; tối đa 6 bài mỗi nhóm (lọc `isha/live_search/per_group`, kẹp 1-12); chỉ post_status publish, không mật khẩu | `live-search.php:263-320` | `live-search.js:208-215` (FormData) |
| `isha_seed_services` (POST, `admin_post_` chỉ đăng nhập) | `manage_options` | `isha_seed_services`/`isha_seed_nonce` | không (nút admin) | Không JSON: `wp_safe_redirect` về trang trước; kết quả lưu transient 300 giây | quyền + nonce | `class-seed.php:57,963-992` | form admin |

## 7. UI/JS component dùng lại

Quy ước: "Trigger" là selector/ID/data-attribute mà JS tìm. Mọi JS front-end của theme là IIFE ES5 không thư viện (trừ các module ES ghi rõ). **Thư viện ngoài**: `three` 0.187.0 (founder-gallery, services-cinematic), `detect-gpu` 6.0.28 (tier.js), `lil-gui` 0.21.0 + `stats.module.js` (chỉ bảng debug `?isha-debug=1` của quản trị viên, `debug.js:14`), jQuery + jQuery UI Sortable (chỉ admin, `admin-gallery.js`). Tất cả tự host ở `assets/vendor/` (xem file 01 mục 3.4). Không có GSAP, Lenis, Swiper, jQuery ở front-end (các chữ "GSAP/swiper" trong `policy-single.js:131,249` và `post-article.js:286` chỉ là comment/loại trừ).

| Component | Mô tả | PHP / template-part | CSS | JS | Trigger | Khởi tạo | Trang dùng |
|---|---|---|---|---|---|---|---|
| Header Floating Glass | Thanh cố định, nổi khi cuộn >40px (`.is-scrolled`), lưới logo/menu/tools, nút Đặt lịch hút nam châm | `template-parts/header/masthead.php`, `header.php`, `header-home.php` | `main.css` khối Header (`:211-792`, token `--hd-*`) | `main.js` | `#masthead`, `.site-header`, `.header-cta--bar` | IIFE `main.js:2`; gọi `initScrollState/initDrawer/initSubmenus/initMagnet/initCoin` (`:345-349`); mốc `LIMIT=900` | mọi trang (landing bật: chỉ logo) |
| Menu mobile (drawer) và menu con | Drawer toàn màn ≤900px, khoá cuộn `html.isha-nav-open`, bẫy Tab, Esc; chevron menu con; accordion ở mobile | `masthead.php`; `inc/header.php:224-237` | `main.css` | `main.js` `initDrawer` (`:41`), `initSubmenus` (`:117`) | `.menu-toggle[aria-expanded]`, `#primary-nav`, `.submenu-toggle`, `.menu-item-has-children` | như trên | mọi trang |
| Bộ chuyển ngôn ngữ | Quả cầu cờ VI/EN (lật rồi điều hướng); dự phòng chữ "VI / EN" | `inc/header.php:98-191` (`isha_header_language_switcher('bar'/'drawer')`), icon `assets/icons/{vietnam,uk}-flag-*.svg` | `main.css` | `main.js` `initCoin` (`:302`) | `.header-lang__coin` (`data-no-translation`) | như trên; không JS vẫn là liên kết | mọi trang khi TranslatePress có ≥ 2 ngôn ngữ |
| Tìm kiếm nhanh (live search) | Popup `<dialog>` tìm theo tiêu đề Dịch vụ/Tin tức/Sự kiện, Ctrl/Cmd+K | `inc/live-search.php`, `template-parts/search/live-search-dialog.php` | `live-search.css` | `live-search.js` (fetch, debounce 250ms, AbortController) | `[data-isha-search-open]`, `dialog#isha-search`, chip `[data-isha-search-chip]` | IIFE, `dlg.showModal()`; không có `<dialog>` thì liên kết `/?s=` | mọi trang khi landing tắt |
| Popup đặt lịch | `dialog#isha-book`; bắt mọi liên kết tới trang Đặt lịch hẹn, chọn sẵn `?service=ID` | `inc/forms.php:254-259`, `forms/booking-dialog.php`, `forms/booking-form.php` | `booking.css` | `booking.js` (`wireDialog` `:193`) | `[data-isha-booking]` hoặc `a[href]` có path = `bookingPath`; đóng `[data-isha-book-close]` | `boot()` ở DOMContentLoaded (`:294-303`); cần fetch/FormData/Promise | mọi trang có trang Đặt lịch hẹn |
| Form AJAX (đặt lịch, liên hệ) | Gửi AJAX, nonce mới, lỗi dưới từng ô | `forms/booking-form.php`, `forms/contact-form.php`, `isha_forms_field()` (`forms.php:202`) | `booking.css` (`.isha-form-lux*`), `contact.css` | `booking.js` `wireForms` (`:166`) | `[data-isha-form="isha_booking"/"isha_contact"]`, sự kiện `isha:sent` | `boot()` | popup, Liên hệ, Đặt lịch hẹn |
| Lightbox (3 bản riêng) | (a) Founder: `dialog.isha-founder-lb`; (b) liệu trình: `dialog.isha-svc__viewer`; (c) Tin tức/Sự kiện: dialog tạo bằng JS `.isha-art__viewer` | `home/founder-story.php:254`; `template-parts/service-single.php:213`; (c) không có markup PHP | `home.css`; `service-single.css`; `post-article.css` | `founder-gallery/lightbox.js` (`createLightbox`); `service-single.js` `viewer()` (`:71`); `post-article.js` (`:278-376`) | (a) `.isha-founder__item`; (b) `[data-isha-zoom]`; (c) ảnh trong `[data-isha-article-body]`, `[data-isha-cover] img` | (a) module; (b),(c) IIFE; đều `showModal()` | Trang chủ; chi tiết liệu trình; chi tiết Tin tức/Sự kiện |
| Mục lục (TOC) + thanh tiến trình đọc | Quét h2/h3 của bài; desktop cột phải dính, mobile accordion | `template-parts/post-article.php` | `post-article.css` | `post-article.js` (`:64` progress, `:135` TOC) | `[data-isha-toc]`, `[data-isha-toc-m]`, `[data-isha-toc-link]`, `[data-isha-progress]`, `[data-isha-article-body]` | IIFE; chạy khi có `.isha-art` (`:462`); không chạy trong khung soạn Elementor | Chi tiết Tin tức (Sự kiện chỉ dùng lightbox + copy link) |
| Nút sao chép liên kết | Sao chép URL bài | `post-article.php:165-168`, `post-event.php:241-244` | `post-article.css` | `post-article.js` (`:428`) | `[data-isha-copy]`, `[data-isha-copy-text]`, `[data-isha-copy-status]` | như TOC | Tin tức, Sự kiện |
| Accordion | (1) chương nội dung liệu trình chia theo h2 ở chế độ gọn; (2) TOC mobile; (3) menu con drawer | `service-single.php` | `service-single.css`, `post-article.css`, `main.css` | `service-single.js` (`:141`), `post-article.js`, `main.js` | `.isha-svc__content`; `[data-isha-toc-m]`; `.submenu-toggle` | IIFE | liệu trình, Tin tức, header |
| Card Fan 360° | Chùm thẻ chính sách quay 360° rồi xoè theo chuột | plugin `[isha_lp_card_circle]` (`class-shortcodes.php:224-267`) | `isha-core.css` | `plugins/isha-core/assets/js/isha-lp-stack.js` | `.isha-lp-stack` | IIFE; chỉ > 767px, hover, không giảm chuyển động | trang chứa shortcode (chưa xác minh) |
| Nan quạt dịch vụ (bánh xe quay) | Hero trang Dịch vụ/nhóm: bánh xe thẻ nhóm quay quanh tâm, video nền theo khổ màn hình | `template-parts/services-hero.php` | `services-hero.css` | `services-hero.js` | `[data-isha-sh]`, `[data-isha-sh-wheel]`, `[data-isha-sh-video]` | IIFE; thoát nếu thiếu rAF/matchMedia (`:16-19`) | Dịch vụ tổng, nhóm dịch vụ |
| Card Ring (vòng cung ánh kim) | Vòng thẻ 3D lõm/lồi, hover lật mặt sau, tab/chọn thẻ, governor FPS, `.is-reduced` thành hàng cuộn | `home/services.php`, `services-cinematic.php` (markup `.isha-ring`, `ul.isha-ring__ring > li.isha-ring__card`) | `card-ring.css` | `js/card-ring/index.js` (`createCardRing({stage,list,cfg,...})`) + 9 module con | `[data-isha-ring]`, `.isha-ring` | `import()` động trong `home-wheel/main.js:102` và `services-cinematic/main.js:302` | Trang chủ (Dịch vụ tiêu biểu), nhóm dịch vụ, chi tiết liệu trình (`[isha_service_wheel]`) |
| Bánh xe tĩnh lặng (home-wheel) | Section Dịch vụ tiêu biểu: tab nhóm + khối chữ + Card Ring lồi một mặt | `home/services.php`, `inc/service-wheel.php` | `home.css` (khối services), `card-ring.css` | `home-wheel/main.js` (module `isha-home-wheel`) | `[data-isha-wheel]`, `.isha-wheel__tab`, `.isha-wheel__panel`; `window.ISHA.wheel` | tự dựng khi section cách khung nhìn ~800px (`main.js` đầu file) | Trang chủ, chi tiết liệu trình |
| Marquee lời kể | Hai dãy thẻ trôi ngược chiều 35 px/s, dừng khi hover/focus; mobile hàng vuốt | `home/testimonials.php` | `home.css` (khối testimonials) | `home.js` khối testimonials (`:1960-2196`) | `[data-isha-tm]`, `[data-isha-tm-row]`, `[data-marquee-clone]` | `register('testimonials', fn)` (`:1977`); 3 chế độ marquee/swipe/static | Trang chủ |
| Băng chuyền (APG deck) | Ảnh trước/sau đã ghép đổi tự động 5 giây, nút/chấm/kéo | `home/results.php` | `home.css` | `home.js` `carousel()` (`:203`), `deck()` (`:290`), `register('results')` (`:1935`) | `[data-isha-results]`, `[data-isha-results-controls]` | scene `results` | Trang chủ |
| Hàng vuốt + pager | Hàng cuộn ngang ở chế độ gọn, nút Trước/Sau + chấm | `isha_home_pager()` (`home/helpers.php:505`); dùng ở `home/articles.php:155`, `home/process.php:58` | `home.css` | `home.js` khối rows (`:639-856`) | `[data-isha-row]`, `[data-isha-pager]` | tự gắn cho mọi `[data-isha-row]` | Trang chủ |
| Tabs | (a) tab nhóm dịch vụ, (b) bộ lọc loại sự kiện, (c) tab chuyên mục danh sách Bento (liên kết thường), (d) tab admin trang chủ | `home/services.php:101`; `home/events.php`; `editorial/list.php` + `isha_editorial_tabs()` (`editorial-list.php:506`) | `home.css`, `editorial-list.css` | `home-wheel/main.js`; `events-list/main.js`; (c) không JS; `plugins/isha-core/assets/js/admin-home.js` | `.isha-wheel__tab`; `[data-isha-events-filter]`, `.isha-events__tab`; `?chuyen-muc=`; `[role=tab][data-section]` | module / IIFE | Trang chủ; danh sách Tin tức/Sự kiện; admin |
| Danh sách Sự kiện tương tác | Rê chuột/focus dòng thì đổi ảnh bên trái; lọc theo loại | `home/events.php` | `home.css` | `events-list/main.js` | `[data-isha-events]`, `.isha-events__item`, `.isha-events__shot` | module `isha-events-list` (`assets.php:60`) | Trang chủ |
| So sánh trước/sau | Khung kéo `role=slider` + dải thẻ băng chuyền vòng lặp | `service-group/compare.php`, `service-group/results.php`, `service-group/intro.php` | `service-group.css` | `service-group.js` | `.isha-sg-ba`, `.isha-sg-strip` | IIFE | Nhóm dịch vụ |
| Founder 3D gallery | Gallery WebGL (three) ảnh nhú từ tâm; bản tĩnh + lightbox khi không WebGL/giảm chuyển động; đọc tiếp câu chuyện | `home/founder-story.php` (JSON `.isha-founder__data`) | `home.css` (khối founder, 123 dòng chứa `isha-founder`) | `founder-gallery/{main,gallery,shader,dust,lightbox,readmore,config}.js` | `[data-isha-founder]`, class `is-3d` | module `isha-founder-gallery`; `main.js` đầu `doc.querySelectorAll('[data-isha-founder]').forEach(boot)`; `import('./gallery.js')` khi gần khung nhìn (`NEAR_MARGIN '900px 0px'`) | Trang chủ |
| Services-cinematic "Bình minh lan tỏa" | Section WebGL theo cuộn (ảnh núi + độ sâu + camera 2.5D), vòng thẻ ở chân núi; ladder chất lượng (FULL_HIGH/FULL/LITE/2D/STATIC) | `template-parts/services-cinematic.php` (JSON `#isha-svc-data`) | `services-cinematic.css` | `services-cinematic/main.js` + `scene/*`, `tier.js`, `autoplay.js`, `lifecycle.js`... | `section#lieu-trinh[data-isha-svc]`; debug `?isha-debug=1` (admin) | module `isha-svc-main` (`services-cinematic.php:237`); `detectMode` rồi `import()` động | Nhóm dịch vụ có liệu trình |
| Màn chờ "Bình minh" | Lớp phủ ba lớp núi + mặt trời mọc, chạy một lần mỗi lượt xem | trong `services-cinematic.php` | `services-cinematic.css` (khối MÀN CHỜ) | `services-cinematic/intro.js` (`bindIntro`), `intro-layout.js` | `.is-enhanced`; bỏ qua bằng bấm/Esc | gọi từ `main.js` | Nhóm dịch vụ |
| Dock liên hệ nhanh | Thanh Đặt lịch / Zalo / Hotline; hiện sau 80% hero, ẩn khi `#cta` > 40% | `template-parts/home/dock.php` (`isha_home_contact()`) | `home.css` | `home.js` khối dock (`:857-904`) | `aside.isha-dock[data-isha-dock]`, class `is-visible` | `boot('dock', ...)` | Trang chủ; chi tiết liệu trình/Tin tức/Sự kiện (`template-parts/service-single.php:284`, `post-article.php:274`, `post-event.php:303`) |
| Nút Lên đầu trang | Nút cố định, hiện khi cuộn quá max(600px, 3/4 chiều cao màn hình) (`scroll-top.js:20-23`); đứng trên dock | `inc/scroll-top.php:96-107` | `scroll-top.css` | `scroll-top.js` (`MutationObserver` theo dock) | `button[data-isha-top]`; body class `isha-top-compact` | IIFE (`:9`) | mọi trang trừ single `isha_policy` |
| Footer | 3 tầng: dải mời đặt lịch, lưới (nhận diện/liên hệ/khám phá), thanh dưới | `footer.php`, `inc/footer.php` | `main.css` khối Footer (`:910-1093`, token `--sf-*`) | không (dải mời dùng `data-isha-booking` của popup) | `footer#colophon.site-footer` | - | mọi trang (landing bật: chỉ nhận diện + bản quyền) |
| Lõi chuyển động `isha-motion` | Một vòng rAF cho mọi cảnh: `scene()` ghim, `tween`, `inView`, `scrollTo`, `reduced()`, `compact()` | - | `home.css` | `isha-motion.js` | `[data-isha-track]` > `[data-isha-stage]`, `html.isha-motion-on/-fail` | IIFE; thoát nếu thiếu rAF/matchMedia/IntersectionObserver (`:17-19`); `window.ISHA.motion` | Trang chủ, Giới thiệu, Không gian, liệu trình, Tin tức, Sự kiện |
| Cảnh trang chủ (home.js) | Scene đăng ký bằng `register(id, fn(root, ctx))`: hero (iris + intro), space (rèm lá + Ken Burns), results, articles, testimonials, process, partners, cta; cùng `sky` | `home/{hero,space,...}.php` | `home.css` (202 KB) | `home.js` (`:1119,1419,1513,1935,1945,1977,2200,2309`) | `section[data-isha-scene]`, `[data-isha-sky]`, `[data-isha-intro]` | IIFE; cuối file gọi `startScenes()` hoặc `pollScenes()` (`:2365-2371`) | Trang chủ (chạy cả ở các trang nạp HOME-SET, chỉ scene có section mới hoạt động) |
| Reveal khi vào khung nhìn | Thêm `.reveal-animate` khi phần tử vào khung 35% | `isha_home_section_open()` gắn `data-reveal` cho mọi section trừ hero và articles (`home/helpers.php:114`) | `home.css:5687-5708` | `home.js` khối reveal (`:2333-2363`) | `[data-reveal]`; `[data-isha-reveal]` (articles, `.is-pre`) | `ready(initRevealAnimations)` | Trang chủ, trang dùng `.isha-home` |
| Hero video / ảnh nền | Video chọn bản dọc/ngang, nạp sau `load`, tạm dừng khi ngoài màn; tắt khi giảm chuyển động hoặc Save-Data | `about/hero.php`, `services-hero.php`, `home/hero.php`, `home/cta.php` | `about.css`, `services-hero.css`, `home.css` | `about.js` (`:53`), `services-hero.js`, `home.js` | `.isha-about-hero__video`, `[data-isha-sh-video]`, `[data-isha-hero-video]` | IIFE | Giới thiệu, Không gian, Dịch vụ, trang chủ |
| Giới thiệu / Không gian: reveal + ảnh phóng | Section hiện dần, ảnh phóng nhẹ theo cuộn | `about/page.php`, `about/section.php`, `space/*.php` | `about.css`, `space.css` | `about.js` | `.isha-about`, `.isha-about-sec` | IIFE (`:134`) | Giới thiệu, Không gian |
| Cổng thẻ kính (policy gate) | Trang Chính sách: preloader, thẻ kính, ghim 2 màn, lật thẻ | `policy-screens.php`, `single-isha_policy.php` | `policy-single.css` | `policy-single.js` | `[data-isha-ps]` | IIFE; chốt an toàn 9 giây ở `<head>` | Chi tiết Chính sách |
| Thang hạng thẻ thành viên | Scrollytelling: thẻ + 6 lớp sóng theo hạng | plugin `[isha_lp_cards_member]` | `isha-core.css`, `policy-membership.css` | `isha-lp-ladder.js` | `.isha-lp-ladder` | IIFE; cần IntersectionObserver; mobile ≤ 900px | trang `chinh-sach-khach-hang` (chưa xác minh) |
| Danh sách Bento | Lưới Tiêu điểm / Cùng chuyên mục / Cùng tác giả, tab và phân trang bằng liên kết | `editorial/{list,tile}.php`, `inc/editorial-list.php` | `editorial-list.css` | không | `?chuyen-muc=`, `?trang=` | PHP | Danh sách Tin tức/Sự kiện |
| Bản đồ | Iframe lazy, khung viền vàng | `contact/page.php:145-151`; shortcode `[isha_map]` | `contact.css`, `isha-core.css` | không | iframe `src` = `map_url` | PHP | Liên hệ, Đặt lịch hẹn |
| 404 nghiêng theo con trỏ | Ảnh "404" nghiêng ≤ 7°/4° theo chuột | `404.php` | `404.css` | `404.js` | `.isha-404__code` | IIFE (`:11`) | 404 |
| View Transitions | Chuyển cảnh trang chủ ↔ Không gian, ảnh phòng "mở ra" thành hero | `space.php:302-308` (`<style id="isha-view-transition">`) | `space.css` | `home.js` (`pageswap`, `:1904-1916`) | `view-transition-name: isha-room` | CSS `@view-transition` | Trang chủ, trang Không gian |
| Admin: ô chọn media | Ảnh/video/gallery có thứ tự bằng `wp.media` | `ISHA_Core_Admin_Media::render()` (`class-admin-media.php:139`) | `admin-media.css` | `admin-media.js` | `div.isha-media[data-type]`, sự kiện `isha-media:change` | `::enqueue()` (`:48`) | admin |
| Helper PHP dùng lại | Ảnh responsive, placeholder, nút, pager, logo chữ; ô form; banner; CTA | `home/helpers.php` (`isha_home_image/placeholder/media/button/pager/wordmark/p`), `forms.php:202` (`isha_forms_field`), `template-tags.php` (`isha_page_banner/isha_cta_section/isha_social_links/isha_logo`) | `home.css`, `booking.css`, `main.css` | - | - | PHP | toàn theme |
| Elementor widget/class | Widget "ZigZag Section" (Angie snippet) và lớp tiện ích `isha-elementor-*` | `wp-content/angie-snippets/{dev,prod}/snippet-57/` | `themes/ISHA/assets/css/elementor.css` | - | lớp CSS trong tab "Nâng cao" của Elementor | hook `elementor/widgets/register` | trang dựng bằng Elementor |

## 8. Design token và breakpoint

Tổng 267 tên custom property được khai trong CSS của theme; token gốc ở `main.css`. Không liệt kê từng biến.

| Nhóm | Ví dụ | Khai báo |
|---|---|---|
| Màu thương hiệu | `--isha-daylight #e7e9e6`, `--isha-skin #d9c5ad`, `--isha-gold #8e6837`, `--isha-shadow #141414` | `main.css:18-21` (trùng `theme.json` palette) |
| Màu dẫn xuất | `--isha-gold-ink`, `--isha-gold-hover`, `--isha-skin-soft`, `--isha-paper #f4f1ea`, `--isha-line`, `--isha-white` | `main.css:24-29` |
| Kim loại (chỉ trang trí) | `--isha-metal-light/-metal/-metal-deep/-metal-warm`; `--metal-deep/-hi/-light/-dark` (gradient) | `main.css:33-36`; `home.css:190-195` |
| Tông theo giờ | `--isha-dawn/-golden/-evening/-twilight/-sunset/-dusk` (`color-mix` trong `@supports`) | `main.css:54-59,98-104` |
| Tông section | `--tone-bg/-fg/-soft/-accent/-ink/-line/-ph` (65 khai báo) cho 11 giá trị `data-tone`: night, noir, paper, day, day-warm, afternoon, late-afternoon, golden, evening, twilight, dusk | `home.css:~22,80-` |
| Chữ | `--font-heading` (Montserrat), `--font-body` (Garamond Premier Pro), `--isha-fs-label/body/note/sub/header/accent/display`; `--isha-fs-title/tag/para` (trong `.isha-home`) | `main.css:38-39,77-83`; `home.css:243-` |
| Chuyển động | `--isha-ease-out/-in-out/-cine`, `--isha-dur-press/ui/ui-lg/cine/btn/header`, `--isha-btn-transition` | `main.css:62-74,93` |
| Bố cục, lớp | `--container 1160px`, `--container-narrow 760px`, `--radius 4px`, `--header-h 84px`, `--header-h-compact 64px`, `--header-w-float 1240px`, `--isha-section-y`, `--isha-vh`, `--isha-z-dock 90`, `--isha-z-header 100`, `--isha-z-intro 999`; `--isha-gutter`, `--isha-row-pad` | `main.css:41-45,86-95`; `home.css:49-53` |
| Header/Footer | `--hd-*` (63 khai báo), `--sf-*` (11) | `main.css` (Header từ `:211`, Footer từ `:910`) |
| Theo thành phần | `--sg-*` (service-group, 29), `--ps-*` (policy-single, 19), `--tm-*` (testimonials, 17), `--ring-*` (card-ring, 15), `--sh-*` (services-hero, 14), `--space-*`, `--ar-*`, `--svc-*`, `--lux-*`... | từng file CSS tương ứng |

Breakpoint (đếm `@media` trong `assets/css/*.css`: tổng 193; 99 có điều kiện kích thước, trong đó 61 dùng mốc 900/901 hoặc 560/561):

| Mốc | Số lần | Ghi chú |
|---|---|---|
| `(max-width: 900px), (max-height: 560px)` | 28 | Chế độ **gọn** (compact), cùng điều kiện với `mqCompact` trong `isha-motion.js:30`, `home.js` (`mq.compact`, `:28-29`), `main.js` `LIMIT=900` (`:12`) |
| `(min-width: 901px) and (min-height: 561px)` | 4 (+ 5 biến thể kèm hover/pointer, 1 biến thể khác thứ tự) | Desktop tương ứng |
| `(max-width: 900px)` đứng riêng / `(max-height: 560px)` đứng riêng | 4 / 4 | Tổng số lần mỗi mốc xuất hiện (kể cả trong tổ hợp): `max-width 900px` 39, `max-height 560px` 39, `min-width 901px` 17, `min-height 561px` 12 |
| `min-width: 901px` (+ dải 901-975/1080/1180/1280) | 2 + 5 | `main.css:682` v.v. |
| `min-width: 900.02px`, `min-height: 560.02px` | 4 / 3 | Phần bù chính xác của `max 900/560` |
| `max-width: 767px` / `min-width: 768px` | 6 / 3 | `card-ring.css`, `home.css`, `services-hero.css`, `services-cinematic.css` |
| `max-width: 600px` | 6 | |
| Mốc khác | 1024/1025 (3+2), 640/641, 780, 782 (admin-bar), 680, 520, 480, 1100, 1199, 1180 | ít file |
| `(hover: hover) and (pointer: fine)` | 39 | Chuột thật |
| `(prefers-reduced-motion: reduce)` | 23 (+ 3 `no-preference`); 19 file JS (theme + plugin) kiểm `prefers-reduced-motion` | Tất cả scene/module đều có nhánh tĩnh |
| `(forced-colors: active)` / `print` / `(scripting: none)` | 16 / 5 / 1 | |

Không dùng mốc 901/561 (dùng mốc khác): `booking.css` (780/600), `editorial-list.css` (640/641/1100), `card-ring.css` (767/1199), `services-hero.css`, `services-cinematic.css` (767/768), `policy-single.css` (orientation, 782), `isha-core.css` (680/767/900/1024), `isha-lp-ladder.js` (`max-width: 900px`), `isha-lp-stack.js` (767px).

## 9. Gợi ý các nội dung có thể còn sót (xếp theo mức đáng làm tài liệu)

Chỉ những thứ thực sự có trong code, kèm bằng chứng.

| # | Chủ đề | Bằng chứng |
|---|---|---|
| 1 | **Chế độ landing và xem trước trang chủ** (công tắc quyết định header, footer, trang chủ, tìm kiếm; `?isha_preview=home`, `?isha-debug=1`, `?isha-mode=`) | `helpers.php:82-119`, `masthead.php:20`, `live-search.php:25`, `home/assets.php:228-251`, `tier.js` (ép chế độ) |
| 2 | **Luồng form đặt lịch / liên hệ**: nonce công khai, honeypot, rate-limit theo IP, CPT hộp thư, trạng thái, email `notify_email`, bộ lọc `isha_core/booking_slots`, `isha_core/contact_topics` | `class-forms.php`, `class-booking.php`, `class-contact.php`, `booking.js` |
| 3 | **Lớp dữ liệu trang chủ**: lược đồ, mặc định, trộn, filter `isha/home/data/{section}`, `isha_core/home_schema`, `isha_core/home_defaults`, `isha/home/sections`, cách ly lỗi section | `home-schema.php`, `home/data.php:210-235,1381`, `home/registry.php:86`, `home/shortcodes.php:62` |
| 4 | **Rewrite rules và URL cũ**: slug CPT, 301 `/bai-viet/` và `/chu-de/`, `ISHA_CORE_REWRITE_VERSION` | `isha-core.php:27,86-93`, `class-legacy-urls.php`, `editorial-list.php:778-828` |
| 5 | **Đa ngôn ngữ (TranslatePress)**: bộ chuyển header, `data-no-translation`, giới hạn của tìm kiếm, chưa có `.po/.mo` | `inc/header.php`, `live-search.php:142-155`, `languages/README.txt` |
| 6 | **Pipeline WebGL Bình minh** (tier, governor, lookdev, bake, dead-code, test) | `README-services-cinematic.md`, `README-card-ring.md`, `tools/*.mjs`, `tests/*.test.mjs`, `services-cinematic/tier.js` |
| 7 | **Hook/filter tự định nghĩa** (24 tên, kể cả filter động `isha/home/data/{section}`; ví dụ `isha/home/sections`, `isha/home/isolate_errors`, `isha/scroll_top/enabled` và `.../compact`, `isha/live_search/*`, `isha/editorial/*`, `isha/event/online_pattern`, `isha_svc_should_load`, `isha_core/news_url`) | grep `apply_filters/do_action` trong theme và plugin |
| 8 | **Seed, migration, demo content**: seed 6 nhóm + 17 liệu trình (WP-CLI `wp isha seed services`), `after_switch_theme` | `class-seed.php:8,56-57,1000+`, `demo-content.php:83-145` |
| 9 | **Hiệu năng và ngân sách JS**: `home.css` 202 KB + `home.js` 80 KB nạp ở nhiều trang, three không minify, gate 6 giây, preload font/poster, `.htaccess` no-cache | `home/assets.php`, `assets/js/.htaccess`; kích thước ở file 01 mục 1 và 3.4 |
| 10 | **Bộ QA hồi quy** (ngoài repo) | `C:\xampp\isha-backups\2026-11-18_after-phase3-restructure\qa-regression\run.mjs`, `README-QA.md` |
| 11 | **Cache/transient**: `isha_svc_{term_id}`, rate-limit, nocache khi xem trước | `services-cinematic.php:62-183`, `class-forms.php:54-78` |
| 12 | **Admin UX**: menu cấp 1 (Trang chủ 20.5, Giới thiệu 20.6, Không gian 20.7, ISHA Clinic 24), bong bóng thư mới, cột admin, ẩn Bình luận, thông báo ACF | `class-home-fields.php:103`, `class-about-fields.php:419`, `class-space-fields.php:42`, `class-forms.php:131-152`, `inc/admin.php` |
| 13 | **Phân quyền/capabilities**: `manage_options`, `edit_pages`, `edit_post`, `edit_term`, `create_posts=do_not_allow` | `class-settings.php:46`, `class-home-fields.php:103`, `class-post-types.php:96` (appointment), `class-contact.php:63` |
| 14 | **Image sizes + media**: 8 kích thước, dedupe thuộc tính ảnh, preload poster | `setup.php:49-61,175-201` |
| 15 | **Elementor integration**: `supports elementor`, `elementor.css`, nhận biết `builder`, Angie snippet | `class-post-types.php:214,371`, `compat.php`, `post-single.php:96`, `angie-snippets/` |
| 16 | **Menu/widget/Customizer**: `primary`/`footer`, sidebar `sidebar-blog`, panel `isha_panel` | `setup.php:42-47,76-89`, `customizer.php` |
| 17 | **Trợ năng**: `dialog`, ARIA slider/tab/combobox, `forced-colors` (16), `reduced-motion`, skip-link | `header.php:24`, `live-search-dialog.php`, `service-group.js` |
| 18 | **Khoảng trống SEO**: không có meta description, Open Graph, JSON-LD, canonical tự viết; Yoast được nhắc nhưng chưa cài | grep `ld+json`, `og:`, `canonical` không có kết quả trong theme/plugin; `recommended.php:18-23` |
| 19 | **Cấu trúc nội dung seed Phụ lục A**: các chuỗi mặc định trang chủ (`isha_home_defaults`) | `home/data.php:63-205` |

## 10. Ghi chú lệch (tin code, ghi ngắn)

| Nơi nói | Code thực tế |
|---|---|
| `class-seed.php:4` "Phụ lục A của docs/prompt-trang-chu.md" | File đó không còn trong `docs/`; mặc định thực tế nằm ở `home/data.php:63` |
| `home-schema.php:9-11` "Câu chuyện, Triết lý, Giá trị chuyển sang trang Giới thiệu" | Đúng: khoá mới `_isha_about_*`; khoá cũ `_isha_home_story/philosophy/values/vision` còn trong DB nhưng không còn nơi đọc hay nhập |
| `isha-core.css` dùng fallback `var(--isha-shadow, #1c1b19)`, rgba(28,27,25) | Token hiện tại của theme là `#141414` (`main.css:21`); chỉ khác khi theme không nạp |
| `[isha_map]` chỉ cho host Google (`class-shortcodes.php:553`) | `contact/page.php:151` nhúng bất kỳ URL https lấy từ `map_url` (`forms.php:99`); nhãn ô cài đặt hướng dẫn dán `src` của Google Maps |
| `class-forms.php:5` "nonce lấy mới để form gửi được khi cache" | Đúng, nhưng nonce của khách chưa đăng nhập là **công khai** (`isha_form_nonce`, `isha_live_search_nonce` cấp cho mọi người); lớp chặn thực tế là honeypot + rate-limit (form) và không có gì (tìm kiếm) |
| `live-search.php:11` "chưa có Polylang/WPML" | Site dùng TranslatePress; hàm lọc ngôn ngữ chưa hỗ trợ (xem file 01 mục 7) |
| `class-event-fields.php:10` link đăng ký "chỉ banner trang chủ" | Khớp: `home/data.php:1072`; trang chi tiết không đọc |
| `post-types` `isha_service` "Không bật archive để slug /dich-vu/ dành cho Trang" | Khớp: demo-content tạo Page slug `dich-vu` (`demo-content.php:42-47`) |
| Danh sách shortcode ở docblock `class-shortcodes.php:5-15` | Khớp 11 tag (`init` ở `:27`); thiếu mô tả tham số `show=hours` của `[isha_contact_info]` (có ở `:524`) |
