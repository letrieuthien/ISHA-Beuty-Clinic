# 01. Cấu trúc mã nguồn: Theme ISHA + Plugin isha-core

Lập ngày 27/11/2026 bằng cách đọc trực tiếp code (không chép từ docs cũ). Đường dẫn tính từ `wp-content/`, `file:dòng` là số dòng tại thời điểm lập. Nhánh git lúc lập: `claude/leaflet-business-document-0e4ecd`. Mục nào chưa kiểm chứng ghi "(chưa xác minh)". Tài liệu đi kèm: `docs/02-components-and-plugins.md`.

Quy ước viết tắt: **F+D** = nạp ở chân trang (`in_footer`) và `strategy: defer`. **HOME-SET** = bộ tài nguyên trang chủ do `isha_home_enqueue_assets()` nạp (mục 3.3).

## 1. Cây thư mục Theme `themes/ISHA/`

```
themes/ISHA/
├─ style.css                  Chỉ có header theme (Name ISHA, v1.0.0, Text Domain isha); không có luật CSS, nhưng vẫn được nạp (handle isha-style)
├─ theme.json                 Palette 4 màu (daylight/skin/gold/shadow #141414), 2 font (Montserrat, Garamond), layout 760/1160px
├─ functions.php (52)         Khai ISHA_VERSION/ISHA_DIR/ISHA_URI, rồi require_once 30 file inc/ theo thứ tự (functions.php:16-47)
├─ header.php, header-home.php  Mở <html> đến <main id="content">; cùng gọi masthead; header-home thêm meta theme-color #141414 và <main> không class `page`
├─ footer.php                 Đóng </main>, footer 3 tầng (dải mời đặt lịch, lưới 3 cột, thanh dưới) + wp_footer()
├─ front-page.php             Trang chủ: landing-policy (landing bật) hoặc isha_home_render() (landing tắt / ?isha_preview=home)
├─ home.php                   "Trang bài viết" (Cài đặt › Đọc): danh sách Tin tức dạng Bento; còn lại rơi về index.php
├─ index.php, page.php, single.php, archive.php, search.php, searchform.php, sidebar.php, comments.php, 404.php   Mẫu mặc định
├─ single-isha_{article,event,policy,service,space}.php   Mẫu chi tiết từng CPT
├─ taxonomy-isha_service_cat.php   Trang nhóm dịch vụ
├─ page-tin-tuc.php, page-danh-sach-su-kien.php   Mẫu trang (có Template Name), danh sách Bento; WP tự áp theo slug
├─ templates/                 7 mẫu trang chọn tay: template-{about,booking,contact,fullwidth,policies,services,space}.php
├─ inc/                       30 file logic (chi tiết bên dưới)
├─ template-parts/            44 file markup (chi tiết bên dưới)
├─ assets/                    css, js, vendor, fonts, icons, images, img (chi tiết bên dưới)
├─ tests/                     12 test Node (*.test.mjs): autoplay, card-machine, card-ring-{governor,light,machine,ring,spring}, intro, intro-layout, lifecycle, stops, tier
├─ languages/README.txt       Chưa có .po/.mo (chuỗi gốc tiếng Việt, textdomain isha)
└─ README-card-ring.md, README-services-cinematic.md   Ghi chú quyết định của hai module lớn
```

### inc/ (nạp bởi functions.php theo đúng thứ tự dưới đây)

| File (dòng) | Vai trò |
|---|---|
| `helpers.php` (195) | `isha_opt` (đọc option `isha_core_settings`), `isha_mod`, `isha_is_inner_page`, `isha_landing_only`, `isha_home_is_active/preview`, `isha_booking_url` |
| `setup.php` (208) | theme supports, `register_nav_menus`, `add_image_size`, sidebar, tắt emoji và `wp-embed`, lọc thuộc tính ảnh, tắt Google Fonts/optimized-loading của Elementor |
| `enqueue.php` (148) | CSS/JS nền, trang Chính sách, 404, preload font |
| `template-tags.php` (94) | `isha_logo`, `isha_page_banner`, `isha_cta_section`, `isha_social_links`, `isha_posted_on` |
| `script-modules.php` (76) | Đăng ký ES module tự host (three, stats, lil-gui, detect-gpu) vào importmap |
| `policy-single.php` (137) | Dữ liệu hai màn hình đầu của trang chi tiết Chính sách |
| `home/data.php` (1384) | Dữ liệu trang chủ: mặc định, trộn với meta CMS, dựng sẵn từng section |
| `home/registry.php` (89) | Danh sách **11 section** (hero, space, results, services, founder-story, events, articles, testimonials, process, partners, cta) |
| `home/shortcodes.php` (119) | Đăng ký shortcode `[isha_home_<id>]` cho từng section |
| `home/assets.php` (251) | Nạp CSS/JS trang chủ, script gate trong head, preload poster, noindex khi xem trước |
| `home/helpers.php` (585) | Markup dùng chung (`isha_home_image/media/button/pager/section_open`) và `isha_home_render()` |
| `service-wheel.php` (184) | Shortcode `[isha_service_wheel]` (vòng thẻ của một nhóm dịch vụ) |
| `service-single.php` (219) | Dữ liệu + nạp tài nguyên trang chi tiết liệu trình |
| `about.php` (184) | Dữ liệu + nạp tài nguyên trang Giới thiệu (cũng dùng cho Không gian ISHA) |
| `space.php` (308) | Trang danh sách và chi tiết Không gian ISHA; View Transitions |
| `forms.php` (315) | Popup đặt lịch, helper form, nạp booking/contact |
| `post-single.php` (500) | Dữ liệu + nạp tài nguyên chi tiết Tin tức và Sự kiện |
| `editorial-list.php` (828) | Danh sách Bento dùng chung cho Tin tức/Sự kiện; 301 chuyên mục |
| `services-hero.php` (380) | Hero nan quạt trang Dịch vụ/nhóm dịch vụ; dữ liệu; nạp tài nguyên |
| `service-group.php` (360) | Section Giới thiệu + Hiệu quả liệu trình của trang nhóm |
| `services-cinematic.php` (240) | Section "Bình minh lan tỏa" (WebGL): dữ liệu, transient, nạp module |
| `header.php` (237) | Bộ chuyển ngôn ngữ TranslatePress, ẩn mục `language_switcher`, nút menu con |
| `live-search.php` (373) | Tìm kiếm nhanh: nút, 2 AJAX, nạp CSS/JS, popup |
| `scroll-top.php` (107) | Nút Lên đầu trang |
| `footer.php` (198) | Hàm cho footer: hotline, giờ, chỉ đường, menu dự phòng, điều kiện dải mời |
| `admin.php` (47) | Ẩn mục Bình luận ở wp-admin |
| `customizer.php` (229) | Panel `isha_panel` (landing, hero, home, footer, 404) + live preview |
| `demo-content.php` (145) | Tạo 9 trang + menu khi kích hoạt theme (một lần, option `isha_default_content_installed`) |
| `plugins/recommended.php` (56) | Nhắc kích hoạt ISHA Core (hàm `isha_recommended_plugins()` không được gọi ở đâu) |
| `plugins/compat.php` (43) | CF7 autop, breadcrumb Yoast, nạp `elementor.css` |

### template-parts/ (gom theo nhóm)

| Thư mục/File | Nội dung |
|---|---|
| `header/masthead.php` | Thanh header dùng chung (logo, menu, ngôn ngữ, tìm kiếm, Đặt lịch, nút menu) |
| `home/{hero,intro,space,results,services,founder-story,events,articles,testimonials,process,partners,cta,dock}.php` | 11 section trang chủ + khung intro (trong hero) + dock liên hệ |
| `about/{page,hero,section}.php`, `space/{list,single}.php` | Trang Giới thiệu, Không gian ISHA (chung hero/section) |
| `service-single.php`, `service-group/{intro,results,compare,list}.php`, `services-{page,listing,hero,groups,cinematic}.php` | Trang chi tiết liệu trình, nhóm dịch vụ, trang Dịch vụ tổng |
| `post-article.php`, `post-event.php`, `editorial/{list,tile}.php` | Chi tiết Tin tức/Sự kiện; danh sách Bento |
| `policy-screens.php`, `landing-policy.php` | Hai màn hình đầu trang Chính sách; landing giai đoạn 1 |
| `forms/{booking-dialog,booking-form,contact-form}.php`, `contact/page.php`, `search/live-search-dialog.php` | Popup/form đặt lịch, liên hệ, popup tìm kiếm |
| `content-card.php`, `content-none.php`, `page-banner.php`, `cta.php` | Thẻ bài, trạng thái rỗng, banner trang con, CTA đặt lịch (cũ) |

### assets/

| Đường dẫn | Nội dung |
|---|---|
| `css/` (23 file) | `main.css` (65 KB: token, header, footer, layout), `home.css` (202 KB: hệ thiết kế trang chủ, dùng lại ở nhiều trang), `card-ring.css`, mỗi trang/section một file riêng (about, space, contact, booking, post-article, post-event, service-single, service-group, services-hero, services-groups, services-cinematic, editorial-list, policy-single, policy-membership, live-search, scroll-top, 404), `elementor.css`, `editor.css`, `page.css` |
| `js/*.js` | Tệp phẳng, IIFE ES5: `main.js`, `isha-motion.js`, `home.js` (2372 dòng), `booking.js`, `live-search.js`, `scroll-top.js`, `about.js`, `post-article.js`, `service-single.js`, `service-group.js`, `services-hero.js`, `policy-single.js`, `404.js`, `customizer.js` |
| `js/card-ring/` (10 file) | Vòng cung ánh kim (module ES): index, ring, spring, loop, light, machine, input, governor, a11y, config |
| `js/services-cinematic/` (+ `scene/*.js`, `ui/*.js`) | Cảnh WebGL "Bình minh lan tỏa": main, intro, autoplay, tier, lifecycle, stops, tokens, store, config, debug; `scene/` gồm 10 file (camera, depth, dust, index, mist, plate, post, renderer, sky, sun) |
| `js/founder-gallery/` (7 file) | Gallery 3D section Founder: main, gallery, shader, dust, lightbox, readmore, config |
| `js/home-wheel/main.js`, `js/events-list/main.js` | Module của section Dịch vụ tiêu biểu và Sự kiện |
| `js/.htaccess` | `Cache-Control: no-cache` cho `*.js/*.mjs` (module con không có `?ver`) |
| `vendor/` | `three@0.187.0` (`build/three.module.js`, `three.core.js`, `examples/jsm/libs/stats.module.js`), `lil-gui@0.21.0`, `detect-gpu@6.0.28` (+ `dist/benchmarks/*.json`). Không có GSAP/Lenis/jQuery ở theme |
| `fonts/`, `icons/`, `images/`, `img/` | Garamond Premier Pro + Montserrat (OTF/TTF + WOFF2), icon SVG mạng xã hội/cờ, ảnh vector/mặt trời, `img/intro` và `img/scene` cho cảnh WebGL |

## 2. Cây thư mục Plugin `plugins/isha-core/` (v1.1.0, PHP 7.4+)

```
plugins/isha-core/
├─ isha-core.php (123)        Header plugin, 4 hằng ISHA_CORE_*, require 19 file includes/, init ở plugins_loaded, flush rewrite theo ISHA_CORE_REWRITE_VERSION ('3')
├─ uninstall.php (14)         Chỉ xoá option isha_core_settings và isha_core_rewrite_ver (giữ nội dung)
├─ languages/README.txt       Chưa có .po/.mo (textdomain isha-core)
├─ includes/
│  ├─ class-post-types.php    7 CPT + 3 taxonomy (isha_contact đăng ký riêng ở class-contact.php)
│  ├─ class-service-fields.php, class-policy-fields.php   Trường dịch vụ/chính sách bằng ACF local field group (+ gallery dự phòng khi không có ACF PRO)
│  ├─ class-event-fields.php, class-article-fields.php, class-testimonial-fields.php, class-space-fields.php   Meta box native
│  ├─ class-term-fields.php   Term meta nhóm dịch vụ (cover, intro, trước/sau, thứ tự...)
│  ├─ class-page-hero-fields.php, class-about-fields.php, class-home-fields.php + home-schema.php   Meta box trên Page: Hero banner, Nội dung trang Giới thiệu, Nội dung trang chủ (lược đồ nằm ở home-schema.php)
│  ├─ class-admin-media.php   Ô chọn ảnh/video/gallery dùng chung trong admin
│  ├─ class-settings.php      Trang "ISHA Clinic" (option isha_core_settings)
│  ├─ class-forms.php, class-booking.php, class-contact.php   Form công khai: nonce AJAX, rate-limit, CPT hộp thư, email
│  ├─ class-shortcodes.php, lp-waves-data.php   11 shortcode + dữ liệu 6 lớp sóng SVG
│  ├─ class-legacy-urls.php   301 /bai-viet/... sang /tin-tuc/...
│  └─ class-seed.php          Seed 6 nhóm + 17 liệu trình + 3 loại sự kiện (nút admin, WP-CLI `wp isha seed services`)
└─ assets/
   ├─ css/isha-core.css (360)  CSS front-end của shortcode (nạp ở MỌI trang), admin-home.css, admin-media.css
   └─ js/                      Front: isha-lp-ladder.js, isha-lp-stack.js · Admin: admin-media.js, admin-home.js, admin-gallery.js
```

## 3. Luồng nạp CSS/JS theo trang

### 3.1 Hook và thứ tự (`wp_enqueue_scripts`)

| Ưu tiên | Hàm | Khai báo |
|---|---|---|
| 10 | `isha_enqueue_assets` | `inc/enqueue.php:100` |
| 10 | `isha_home_maybe_enqueue_assets` | `inc/home/assets.php:119` |
| 10 | `ISHA_Core_Shortcodes::assets` | `isha-core/includes/class-shortcodes.php:31` |
| 20 | `isha_about_assets` · `isha_space_assets` · `isha_forms_assets` · `isha_post_single_assets` · `isha_services_hero_assets` · `isha_service_group_assets` · `isha_service_single_assets` | `about.php:150` · `space.php:294` · `forms.php:297` · `post-single.php:500` · `services-hero.php:294` · `service-group.php:360` · `service-single.php:219` |
| 21 | `isha_contact_page_assets` | `forms.php:315` |
| 25 | `isha_svc_assets` | `services-cinematic.php:240` |
| 30 | `isha_live_search_assets` · `isha_scroll_top_assets` | `live-search.php:363` · `scroll-top.php:91` |
| 100 | `isha_home_trim_assets` · `isha_dequeue_wp_embed` | `home/assets.php:133` · `setup.php:160` |
| Hook khác | `isha_elementor_overrides_style` (`elementor/frontend/after_enqueue_styles`) | `plugins/compat.php:43` |
| Hook khác | `isha_customize_preview_js` (`customize_preview_init`) | `customizer.php:229` |
| Không qua hook | `isha_editorial_enqueue()` gọi trong `isha_editorial_render_page()` trước `get_header()` | `editorial-list.php:731-751` |
| Không qua hook | `isha_home_enqueue_assets()` gọi từ shortcode `[isha_home_*]` và `[isha_service_wheel]` (style khi đó in muộn ở chân trang) | `home/shortcodes.php:100`, `service-wheel.php:172` |

Mọi file CSS/JS riêng của theme/plugin đều dùng `filemtime()` làm phiên bản (trừ `isha-style` dùng `ISHA_VERSION` và `isha-customizer`). Các hàm nạp trong `inc/` (home, about, space, forms, post-single, service-*, services-*, live-search, scroll-top) kiểm `is_file()` trước nên thiếu file thì bỏ qua êm; còn `enqueue.php`, `plugins/compat.php` và `class-shortcodes.php` gọi `filemtime()` thẳng (thiếu file sẽ phát cảnh báo PHP).

### 3.2 Phần chạy ở MỌI trang phía khách

| Thành phần | Điều kiện thực tế | CSS | JS | Khai báo |
|---|---|---|---|---|
| Style gốc | luôn | `isha-style` ← `style.css` (rỗng) | - | `enqueue.php:18` |
| Main | luôn | `isha-main` ← `css/main.css` (deps `isha-style`) | `isha-main` ← `js/main.js` (không deps, F+D): header nổi, drawer, menu con, hút nút Đặt lịch, quả cầu cờ | `enqueue.php:19-24`, `35-44` |
| CSS plugin | luôn (không điều kiện, không deps) | `isha-core` ← `isha-core/assets/css/isha-core.css` | - | `class-shortcodes.php:36` |
| Booking (popup + form) | `isha_forms_popup_enabled()`: không admin, plugin có `ISHA_Core_Booking`, và có Page đã xuất bản dùng `template-booking.php` (`forms.php:49-51`); hoặc đang ở trang Liên hệ/Đặt lịch hẹn | `isha-booking` ← `css/booking.css` (deps `isha-main`) | `isha-booking` ← `js/booking.js` (F+D), inline `window.ISHA.forms` | `forms.php:264-297` |
| Tìm kiếm nhanh | `isha_live_search_enabled()`: không admin, không embed, **không ở chế độ landing** (`live-search.php:25-29`) | `isha-live-search` ← `css/live-search.css` (deps `isha-main`) | `isha-live-search` ← `js/live-search.js` (F+D), inline `window.ISHA.search` | `live-search.php:325-363` |
| Lên đầu trang | `isha_scroll_top_enabled()`: không admin, không embed, **trừ** single `isha_policy` (`scroll-top.php:25-29`) | `isha-scroll-top` ← `css/scroll-top.css` (deps `isha-main`) | `isha-scroll-top` ← `js/scroll-top.js` (F+D) | `scroll-top.php:67-91` |
| Trang nội dung | `isha_is_inner_page()`: Page, trang bài viết, single `post/isha_service/isha_policy/isha_article/isha_event/isha_space`; **trừ** trang chủ điện ảnh (`helpers.php:68-73`) | `isha-page` ← `css/page.css` (deps `isha-main`) | - | `enqueue.php:26-33` |
| Comment | `is_singular()` + bình luận mở + `thread_comments` | - | `comment-reply` (lõi WP) | `enqueue.php:96-98` |
| Markup chân trang | cùng điều kiện ở trên | - | - | `forms.php:259` (popup đặt lịch, wp_footer 5), `live-search.php:373` (popup tìm kiếm, wp_footer 6), `scroll-top.php:107` (nút, wp_footer 20) |
| Gỡ bớt | luôn / chỉ trang chủ điện ảnh | gỡ `wp-block-library`, `wp-block-library-theme` (chỉ khi `isha_home_is_active()`) | gỡ `wp-embed` (mọi trang khách); emoji tắt ở `init` | `home/assets.php:130-131`, `setup.php:157,144-150` |

### 3.3 Theo loại trang

**HOME-SET** (`isha_home_enqueue_assets()`, `home/assets.php:23-77`, chạy nhiều lần không nạp trùng):
CSS `isha-card-ring` ← `card-ring.css` (deps `isha-main`) rồi `isha-home` ← `home.css` (deps `isha-main`, `isha-card-ring`) · JS `isha-motion` ← `isha-motion.js` (không deps, F+D) rồi `isha-home-js` ← `home.js` (deps `isha-motion`, F+D, inline `window.ISHA.homeConfig` đặt **trước**) · Module (footer): `isha-home-wheel` ← `home-wheel/main.js`, `isha-founder-gallery` ← `founder-gallery/main.js` (phụ thuộc động `three`), `isha-events-list` ← `events-list/main.js`.

| Loại trang / điều kiện | CSS riêng (handle ← file; deps) | JS riêng (handle ← file; deps) | Khai báo |
|---|---|---|---|
| Trang chủ điện ảnh: `is_front_page()` và (landing tắt hoặc `?isha_preview=home` do admin có `edit_pages`) | HOME-SET. **Không** có `page.css` | HOME-SET. Thêm trong `<head>`: script gate (`isha-motion-on/-fail/-intro-on`), preload 2 poster hero | `home/assets.php:107-110`, `145-161`, `195-222` |
| Trang chủ khi **landing BẬT** (mặc định của code: `get_theme_mod('isha_landing_only', true)`, `helpers.php:86`) | chỉ phần chung + `page.css` (vì là Page); không có live-search (tắt ở chế độ landing) | chỉ phần chung | `helpers.php:82-87`, `front-page.php:18-27` |
| Page dùng `templates/template-about.php` | HOME-SET + `isha-about` ← `about.css` (deps `isha-main`, `isha-home`) | HOME-SET + `isha-about` ← `about.js` (deps `isha-motion`, F+D) | `about.php:145-184` |
| Page `template-space.php` hoặc single `isha_space` | như Giới thiệu + `isha-space` ← `space.css` (deps `isha-about`). Thêm `<style id="isha-view-transition">` ở trang chủ và trang danh sách Không gian | như Giới thiệu | `space.php:284-294`, `302-308` |
| Page `template-contact.php` / `template-booking.php` | `isha-home` ← `home.css` (chỉ CSS) + `isha-contact` ← `contact.css` (deps `isha-main`, `isha-home`, `isha-booking`) | `isha-booking` (đã có ở 3.2); **không** có `home.js` | `forms.php:302-315` |
| Page `template-services.php` (Dịch vụ tổng) | `isha-services-hero` ← `services-hero.css`; `isha-services-groups` ← `services-groups.css` (cả hai deps `isha-main`) | `isha-services-hero` ← `services-hero.js` (không deps, F+D) | `services-hero.php:266-293` |
| Nhóm dịch vụ `is_tax('isha_service_cat')` | `isha-services-hero` (như trên, không có `services-groups`). Nếu nhóm có intro/cặp ảnh/liệu trình: `isha-home` ← `home.css` (chỉ CSS) + `isha-service-group` ← `service-group.css`. Nếu có liệu trình (`isha_svc_active()`): `isha-card-ring` + `isha-services-cinematic` ← `services-cinematic.css`. **Không** có `page.css` | `isha-services-hero`; `isha-service-group` ← `service-group.js` (không deps, F+D); module `isha-svc-main` ← `services-cinematic/main.js` (phụ thuộc động `three`, `three/addons/libs/stats.module.js`, `lil-gui`, `@pmndrs/detect-gpu`) | `services-hero.php:266-293`, `service-group.php:323-359`, `services-cinematic.php:212-239` |
| Single `isha_service` | HOME-SET + `isha-service-single` ← `service-single.css` (deps `isha-main`, `isha-home`) | HOME-SET + `isha-service-single` ← `service-single.js` (deps `isha-motion`, `isha-home-js`, F+D) | `service-single.php:181-218` |
| Single `isha_article` / `isha_event` | HOME-SET + `isha-post-article` ← `post-article.css` (deps `isha-main`, `isha-home`); riêng Sự kiện thêm `isha-post-event` ← `post-event.css` (deps + `isha-post-article`) | HOME-SET + `isha-post-article` ← `post-article.js` (deps `isha-motion`, `isha-home-js`, F+D), inline `window.ISHA.postArticle` | `post-single.php:441-499` |
| Danh sách Tin tức / Sự kiện (`home.php` khi là Trang bài viết; `page-tin-tuc.php`; `page-danh-sach-su-kien.php`) | `isha-editorial-list` ← `editorial-list.css` (deps `isha-main`) | Không có JS | `editorial-list.php:731-751` |
| Single `isha_policy` | `isha-policy-single` ← `policy-single.css` (deps `isha-main`). Inline `<script>` đầu `<head>` thêm `isha-ps-js`, `isha-ps-lock`, chốt 9 giây | `isha-policy-single` ← `policy-single.js` (không deps, F+D); nút Lên đầu trang bị tắt | `enqueue.php:57-74`, `107-115` |
| Page slug `chinh-sach-khach-hang` | `isha-policy-membership` ← `policy-membership.css` (deps `isha-main`); body class `isha-policy-membership` | Shortcode plugin (nếu trang có) tự nạp JS, xem 3.7 | `enqueue.php:47-54`, `142-148` |
| 404 | `isha-404` ← `404.css` (deps `isha-main`) | `isha-404` ← `404.js` (không deps, F+D) | `enqueue.php:77-94` |
| Bài/trang có `[isha_home_` trong `post_content` | HOME-SET | HOME-SET | `home/assets.php:112-117` |
| Có Elementor | `isha-elementor` ← `elementor.css` (deps `elementor-frontend`, `isha-main`) | - | `plugins/compat.php:35-43` |
| Search, archive, taxonomy khác, single `post` | chỉ phần chung (single `post` thêm `page.css`) | chỉ phần chung | - |
| Customizer preview | - | `isha-customizer` ← `customizer.js` (deps `customize-preview`, footer) | `customizer.php:220-229` |

Theme không gọi `wp_register_script/style`; `wp_register_script_module` chỉ gọi trong `script-modules.php:51`. Không có `script_loader_tag`, `wp_localize_script` hay `wp_add_inline_style` ở front-end của theme.

### 3.4 Script modules và importmap

Theme cổ điển, WordPress (6.5+) in `importmap` và `<script type="module">` ở chân trang. Không có filter `script_loader_tag` tự viết.

| Module id (bare specifier) | Tệp | Phiên bản | Đăng ký |
|---|---|---|---|
| `three` | `assets/vendor/three@0.187.0/build/three.module.js` (tự import `./three.core.js`) | 0.187.0 | `script-modules.php:26,51` |
| `three/addons/libs/stats.module.js` | `three@0.187.0/examples/jsm/libs/stats.module.js` | 0.187.0 | `script-modules.php:27` |
| `lil-gui` | `lil-gui@0.21.0/dist/lil-gui.esm.js` | 0.21.0 | `script-modules.php:28` |
| `@pmndrs/detect-gpu` | `detect-gpu@6.0.28/dist/index.mjs` (benchmarks JSON tự host, `tier.js:124`) | 6.0.28 | `script-modules.php:30` |

Nơi dùng: tất cả khai là phụ thuộc `'import' => 'dynamic'` (importmap có, không tải sớm; entry tự `import()`): `isha_vendor_dynamic_deps()` (`script-modules.php:65-76`). Entry: `isha-home-wheel` (`assets.php:46`, tự `import('../card-ring/...')` ở `home-wheel/main.js:102-103`), `isha-founder-gallery` (`assets.php:53`, chỉ phụ thuộc `three`, import `gallery.js` khi khung gần màn hình `main.js:126`), `isha-events-list` (`assets.php:60`), `isha-svc-main` (`services-cinematic.php:237`). Đặc điểm: không có bước build, không minify (three.core.js 1,5 MB, three.module.js 0,68 MB), `assets/js/.htaccess` đặt `Cache-Control: no-cache` (nginx không đọc được file này).

### 3.5 Dữ liệu truyền sang JS

| Biến / khối | Tạo ở | Khoá chính | Dùng bởi |
|---|---|---|---|
| `window.ISHA.homeConfig` | `home/assets.php:74` (`wp_add_inline_script`, before) | `preview`, `strings{prev,next,item,rowLabel,carousel,slide,slideOf}` | `home.js` |
| `window.ISHA.forms` | `forms.php:294` | `ajax`, `bookingPath`, `strings{sending,network}` | `booking.js` |
| `window.ISHA.search` | `live-search.php:361` | `ajax`, `nonce`, `fallback`, `icons{service,article,event}`, `strings{empty,count,error,loading}` | `live-search.js` |
| `window.ISHA.postArticle` | `post-single.php:479-496` | `strings{toc,tocTitle,copy,copied,zoom,close,prev,next,viewer}` | `post-article.js` |
| `<script type="application/json" id="isha-svc-data">` | `template-parts/services-cinematic.php:115` | `version, category{id,slug,name}, services[{id,slug,title,excerpt,duration,url,image}], cta{label,url,zalo,hotline,phone}, seed, debug` | `services-cinematic/main.js` (`readData`) |
| `<script type="application/json" class="isha-founder__data">` | `template-parts/home/founder-story.php:235` | `images[{src,lite,full,w,h,...}]` | `founder-gallery/main.js` (`readImages`) |
| Class trên `<html>` | `home/assets.php:152-157`, `enqueue.php:112` | `isha-motion-on`, `isha-motion-fail`, `isha-intro-on`, `isha-ps-js`, `isha-ps-lock`, `isha-ps-fail`; cờ `window.ishaPsGuard`; `sessionStorage 'ishaIntro'` | `home.css`, `home.js`, `policy-single.js` |
| Biến toàn cục do JS tạo | `isha-motion.js` → `window.ISHA.motion`; `home.js` → `window.ISHA.home.register`; `home-wheel/main.js` → `window.ISHA.wheel` | - | các scene, QA |
| Admin | `class-admin-media.php:58` → `ishaAdminMedia` (strings); `class-service-fields.php:191` → `ishaGallery{title,button}` | - | `admin-media.js`, `admin-gallery.js` |

Không dùng `wp_localize_script` ở front-end; mọi cấu hình front-end đi qua `wp_add_inline_script(..., 'before')`.

### 3.6 Đầu ra trong `<head>` ngoài enqueue

| Nội dung | Điều kiện | Khai báo |
|---|---|---|
| Preload font `GaramondPremrPro-Disp.woff2` và `Montserrat-SemiBold.woff2` (wp_head ưu tiên 1) | mọi trang | `enqueue.php:121-134` |
| Script chốt trang Chính sách (khoá cuộn, 9 giây) (ưu tiên 1) | single `isha_policy` | `enqueue.php:107-115` |
| Script gate trang chủ (6 giây; intro 3,5 giây) (ưu tiên 1) | `isha_home_is_active()` | `home/assets.php:145-161` |
| Preload poster hero ngang/dọc, `fetchpriority=high` (ưu tiên 2) | `isha_home_is_active()` và có poster | `home/assets.php:195-222` |
| `<style id="isha-view-transition">` (ưu tiên 2) | trang chủ + trang mẫu Không gian | `space.php:302-308` |
| `nocache_headers()` + `DONOTCACHEPAGE`, `noindex` | chỉ khi xem trước trang chủ | `home/assets.php:228-251` |
| `<meta name="theme-color" content="#141414">` | `header-home.php` (trang chủ điện ảnh) | `header-home.php:21` |

### 3.7 Nạp ở wp-admin (plugin isha-core)

| Màn hình | CSS | JS | Khai báo |
|---|---|---|---|
| Sửa bài `isha_event`, `isha_article`, `isha_space`; Page có Hero/Giới thiệu/trang chủ; sửa term `isha_service_cat` (qua `ISHA_Core_Admin_Media::enqueue()`) | `isha-admin-media` ← `admin-media.css` | `isha-admin-media` ← `admin-media.js` (deps `media-editor`, footer) + `ishaAdminMedia`; gọi `wp_enqueue_media()` | `class-admin-media.php:54-58` |
| Sửa Page trang chủ | `isha-admin-home` ← `admin-home.css` (deps `isha-admin-media`) | `isha-admin-home` ← `admin-home.js` (footer): tab | `class-home-fields.php:641-642` |
| Sửa `isha_service` khi **không** có ACF PRO | inline vào `wp-admin` | `isha-gallery-admin` ← `admin-gallery.js` (deps `jquery`, `jquery-ui-sortable`) + `ishaGallery` | `class-service-fields.php:188-195` |
| Sửa `isha_testimonial` | - | `isha-tm-counter` (handle không file, mã inline đếm ký tự) | `class-testimonial-fields.php:263-265` |
| Sửa Page Giới thiệu / term nhóm dịch vụ | `isha-about-fields`, `isha-term-fields` (handle không file, CSS inline) | - | `class-about-fields.php:322-324`, `class-term-fields.php:390-392` |

### 3.8 Front-end của plugin isha-core (nạp khi shortcode xuất hiện)

| Shortcode | JS | Khai báo |
|---|---|---|
| `[isha_lp_card_circle]` | `isha-lp-stack` ← `isha-lp-stack.js` (không deps, footer; không defer) | `class-shortcodes.php:264` |
| `[isha_lp_cards_member]` | `isha-lp-ladder` ← `isha-lp-ladder.js` (không deps, footer) | `class-shortcodes.php:418` |

Plugin bên thứ ba tự thêm tài nguyên (Elementor, TranslatePress `trp-*`, ACF ở admin): không liệt kê, thuộc plugin đó (điều kiện kích hoạt chưa xác minh).

## 4. Template Hierarchy

Trang chủ `page_on_front` và Trang bài viết `page_for_posts` do option core quyết định (xem mục 5 về bản sao lưu DB). `isha_home_is_active()` = `is_front_page()` và (landing tắt hoặc xem trước) (`helpers.php:114-119`).

| URL / loại nội dung | Template thực tế | Template-part / hàm được gọi | Ghi chú |
|---|---|---|---|
| `/` landing **bật** | `front-page.php` → `header.php` | `template-parts/landing-policy.php` (the_content của trang) | Header chỉ in logo, footer rút gọn, tìm kiếm nhanh tắt |
| `/` landing **tắt** hoặc `?isha_preview=home` | `front-page.php` → `header-home.php` (`get_header('home')`) | `isha_home_render()` → `home/{hero(+intro),space,results,services,founder-story,events,articles,testimonials,process,partners,cta}.php` qua shortcode, rồi `home/dock.php` | Section trả rỗng (tắt/không dữ liệu) thì bỏ qua |
| Trang Tin tức (là Trang bài viết, slug `tin-tuc`) | `home.php` | `isha_editorial_render_page('isha_article')` → `editorial/list.php`, `editorial/tile.php` | WP dùng `home.php`, bỏ qua `page-tin-tuc.php` và mẫu chọn tay |
| `/danh-sach-su-kien/` | `page-danh-sach-su-kien.php` (tự áp theo slug) | `isha_editorial_render_page('isha_event')` | Cũng chọn tay được ở "Mẫu trang" |
| Page chọn "Danh sách tin tức (Bento)" (khi KHÔNG là Trang bài viết) | `page-tin-tuc.php` | như trên | Mẫu dự phòng |
| `/gioi-thieu/` (mẫu Giới thiệu) | `templates/template-about.php` | `about/page.php` → `about/hero.php` + `about/section.php`; rồi `[isha_home_cta]` (không thì `template-parts/cta.php`) | Nội dung soạn trong trang không hiển thị |
| `/khong-gian-isha/` (mẫu Không gian) | `templates/template-space.php` | `space/list.php` → `about/hero.php` + `about/section.php`; cảnh kết | |
| `/khong-gian-isha/{slug}/` | `single-isha_space.php` | `space/single.php`; cảnh kết | CPT, không có archive |
| `/dich-vu/` (mẫu Dịch vụ) | `templates/template-services.php` | `isha_services_page()` → `services-page.php` → `services-hero.php`, `services-groups.php` | |
| `/nhom-dich-vu/{slug}/` | `taxonomy-isha_service_cat.php` | `isha_services_listing()` → `services-listing.php` → `services-hero.php`, `service-group/intro.php`, `service-group/results.php` (+ `compare.php`), rồi `services-cinematic.php` (hoặc `service-group/list.php` nếu tắt cảnh) | |
| `/dich-vu/{slug}/` (CPT) | `single-isha_service.php` | `service-single.php`, `isha_service_wheel()` (→ `home/services.php`), cảnh kết | Dock liên hệ in trong `template-parts/service-single.php:284` |
| `/tin-tuc/{slug}/` | `single-isha_article.php` | `post-article.php` (+ `home/dock.php`), cảnh kết | |
| `/su-kien/{slug}/` | `single-isha_event.php` | `post-event.php` (+ `home/dock.php`), cảnh kết | Không có nút đăng ký |
| `/chinh-sach/{slug}/` | `single-isha_policy.php` | `policy-screens.php` rồi `the_content()` | |
| Page mẫu "Chính sách dịch vụ" | `templates/template-policies.php` | `isha_page_banner()` + shortcode `[isha_policies view="list"]` | |
| `/lien-he/` | `templates/template-contact.php` | `contact/page.php` (mode contact) → `forms/contact-form.php` | |
| `/dat-lich-hen/` | `templates/template-booking.php` | `contact/page.php` (mode booking) → `forms/booking-form.php` | Popup `dialog#isha-book` in ở chân mọi trang |
| Page mẫu "Toàn chiều rộng" | `templates/template-fullwidth.php` | chỉ `the_content()` | |
| Page thường (ví dụ `chinh-sach-khach-hang`) | `page.php` (hoặc mẫu Elementor nếu chọn: chưa xác minh) | `isha_page_banner()`, `the_content()`, `comments_template()` | |
| Bài core (`post`) | `single.php` | `isha_page_banner()`, `comments_template()` | |
| `/?s=` | `search.php` | `content-card.php` / `content-none.php`, `sidebar.php` | `searchform.php` dùng qua `get_search_form()` trong `content-none.php:15` |
| Archive core, taxonomy khác | `archive.php` | `content-card.php`, `sidebar.php` | |
| `/chu-de/{slug}/`, `/loai-su-kien/{slug}/` | không render: 301 về danh sách đã lọc `?chuyen-muc=slug` | `isha_editorial_term_redirect()` (`editorial-list.php:818-828`) | Nếu chưa có trang danh sách thì rơi vào `archive.php` |
| `/bai-viet/...` (đường dẫn cũ) | không render: 301 về `/tin-tuc/...` | `ISHA_Core_Legacy_Urls::redirect()` (chỉ khi 404) | `class-legacy-urls.php:33-48` |
| 404 | `404.php` | - | |
| Còn lại | `index.php` | `content-card.php`, `sidebar.php` | |

CPT không có URL riêng: `isha_appointment`, `isha_contact`, `isha_testimonial`.

## 5. Plugin bên thứ ba và plugin của dự án

Chỉ ghi dự án có dùng plugin nào và dùng vào việc gì (theo thư mục `wp-content/plugins/` và chỗ code tích hợp). Không kiểm tra trạng thái bật/tắt.

| Plugin | Phiên bản | Dùng để | Tích hợp trong code |
|---|---|---|---|
| Advanced Custom Fields | 6.8.11 | Field group cho Dịch vụ và Chính sách | `class-service-fields.php:67-127`, `class-policy-fields.php:44-96` (`acf_add_local_field_group`); theme đọc bằng `get_post_meta`, không gọi `get_field()` |
| Elementor | 4.3.6 | Dựng trang Chính sách khách hàng; hai CPT khai `supports: elementor` | `class-post-types.php:214,371`; `plugins/compat.php:35-43` (`elementor.css`); nhận biết trang Elementor qua meta `_elementor_edit_mode` |
| TranslatePress | 3.4.1 | Bộ chuyển ngôn ngữ VI/EN trên header | `inc/header.php:25-72`, `201-213` (tìm kiếm nhanh chưa lọc theo TranslatePress, xem mục 7) |
| Image Optimization | 1.7.7 | Chuyển WebP/AVIF khi tải ảnh lên | Không tích hợp trong code |
| Admin Columns | 7.1.8 | Tuỳ chỉnh cột danh sách trong admin | Không tích hợp trong code |
| QR Code Creator, 360 QR | 1.0.0, 1.0.14 | Tạo mã QR | Không tích hợp trong code |
| Copy & Delete Posts | 1.5.8 | Nhân bản / xoá hàng loạt bài | Không tích hợp trong code |

**Plugin của dự án:** `isha-core` 1.1.0 (mục 2). Theme `hello-elementor` 3.5.2 nằm trong `themes/` nhưng không dùng. Không có `mu-plugins` và không có drop-in (`advanced-cache.php`, `object-cache.php`, `db.php`).

## 6. Tài liệu còn lại trong `docs/`

`ls docs` (cập nhật 27/11/2026, sau phiên 3) có: `README.md` (mục lục), năm file mới `01` đến `05` (lập từ code), `home-architecture.md` (khoảng 288 KB, được code trỏ tới 49 lần, **chưa rà soát**, chờ phiên riêng), `isha-lookdev-prompt-v3.md` (giữ vì 16 file `services-cinematic/` trích dẫn), `Visual Element preview.pdf` và `.htaccess`. Năm file `bang-ma-mau.md`, `editorial-list.md`, `home-admin.md`, `home-assets.md`, `home-brand-script.md` đã bị xoá ở phiên 3 (phần còn dùng được chuyển vào `05` và `03`; xem `README.md` mục 4).

Chú ý: comment đầu file trong code còn trỏ tới `docs/*.md` đã bị xoá hoặc không còn trong `docs/` (`news.md`, `events.md`, `testimonial.md`, `testimonial-mobile.md`, `single-service.md`, `prompt-trang-chu.md`, `prompt-section-the-vong-cung.md`, `footer.md`, `header.md`, `search.md`, `founder-section*.md`, `ISHA Beauty Clinic_ productsList.md`, `bang-ma-mau.md`): chỉ là lịch sử, không tin nội dung của chúng. `.gitignore` có dòng `docs/` nên file trong `docs/` **không** hiện trong `git status`.

Ngoài theme/plugin: `tools/` (ở gốc repo) gồm `bake-intro.mjs`, `bake-scene.mjs`, `deadcode.mjs`, `shot.mjs`, `lookdev*.{html,js}`, `out/`, `imgs-research/`; bộ QA hồi quy nằm ngoài repo ở `C:\xampp\isha-backups\2026-11-18_after-phase3-restructure\qa-regression\` (`run.mjs`, `README-QA.md`).

## 7. Ghi chú lệch (tin code, ghi ngắn)

| Nơi nói | Code thực tế |
|---|---|
| `front-page.php:8` "dựng 13 section" | `home/registry.php:46-58` chỉ có **11** section (hero, space, results, services, founder-story, events, articles, testimonials, process, partners, cta); `functions.php:24` ghi đúng 11 |
| `template-parts/header/masthead.php:8` "Đặt lịch là liên kết, không phải popup" | `booking.js:266-290` chặn click vào mọi liên kết tới trang Đặt lịch hẹn và mở `dialog#isha-book` khi popup bật (`forms.php:49-51`) |
| `live-search.php:11` "chưa có Polylang/WPML" | Site dùng TranslatePress (`inc/header.php`), nhưng lọc ngôn ngữ của tìm kiếm chỉ có nhánh Polylang/WPML (`live-search.php:142-155`) |
| `footer.php:7,164` bộ chuyển ngôn ngữ ở footer qua action `isha_footer_language_switcher` | Không có hook nào gắn vào action này; bộ chuyển chỉ có ở header |
| `page-tin-tuc.php:3-6` tự áp cho slug `tin-tuc` | Trang này là "Trang bài viết" nên WP dùng `home.php` (mẫu chỉ là dự phòng) |
| `style.css:17` "toàn bộ CSS ở main.css" | Đúng, nhưng `style.css` vẫn được nạp thành một request riêng (`enqueue.php:18`) |
| `style.css:8` "Requires at least 6.0" | Code dùng `strategy: defer` (WP 6.3+) và Script Modules (6.5+, có `function_exists` bảo vệ); Elementor 4.3.6 yêu cầu WP 6.8 nên không ảnh hưởng |
| `recommended.php` liệt kê Yoast/WP Super Cache/UpdraftPlus | Hàm `isha_recommended_plugins()` không được gọi; chỉ có thông báo ISHA Core |
| `isha-lp-ladder.js:18` mốc `max-width: 900px`; `isha-lp-stack.js:14` mốc `767px` | Khác mốc 900/901 và 560/561 của theme (mục 8 file 02) |
