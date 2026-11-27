# 05. Audit tình trạng các trang (lập từ code)

Lập ngày 27/11/2026 bằng cách đọc template, `inc/`, `template-parts/`, CSS, JS của theme và plugin `isha-core`; quét tự động mọi chuỗi tiếng Việt trong PHP/JS; đối chiếu `git log`; và 4 câu `SELECT` đọc DB local `isha` (trang chủ tĩnh, mẫu trang, theme_mod landing, ngôn ngữ TranslatePress). **Không** chạy trình duyệt, không chạy bộ QA hồi quy trong phiên này. Đường dẫn tính từ `wp-content/themes/ISHA/`; riêng plugin ghi `isha-core/` (= `wp-content/plugins/isha-core/`). `file:dòng` là số dòng tại thời điểm lập. Tài liệu đi kèm: `01` (cấu trúc), `02` (thành phần), `03` (hệ thiết kế), `04` (quyết định kỹ thuật).

Nhãn trạng thái: **Ổn định** = dựng theo hệ thiết kế hiện hành, không còn việc dở trong code · **Chờ khách** = code xong, chờ khách duyệt hoặc cung cấp thứ gì · **Mẫu mặc định** = vẫn là khung WordPress gốc, chưa dựng lại. Mốc commit lấy từ `git log` (nhánh `develop/feature`).

## 1. File phụ trách từng trang

`HOME-SET` = bộ tài nguyên trang chủ (`home.css`, `card-ring.css`, `isha-motion.js`, `home.js`) được nạp lại ở nhiều trang (xem 01 mục 3.3).

| # | Trang | URL / mẫu | PHP | CSS | JS |
|---|---|---|---|---|---|
| 1 | **Trang chủ** | `/` (Page ID 10), `front-page.php` | `front-page.php`, `header-home.php`, `inc/home/{data,registry,shortcodes,assets,helpers}.php`, `template-parts/home/*.php` (11 section + `dock.php`); admin: `isha-core/includes/{class-home-fields,home-schema}.php` | `home.css`, `card-ring.css` | `isha-motion.js`, `home.js`, `home-wheel/`, `founder-gallery/`, `events-list/`, `card-ring/` |
| 2 | **Giới thiệu** | `/gioi-thieu/` (ID 11), `templates/template-about.php` | `inc/about.php`, `template-parts/about/{page,hero,section}.php`; `isha-core/includes/{class-about-fields,class-page-hero-fields}.php` | `about.css` + HOME-SET | `about.js` + HOME-SET |
| 3 | **Không gian ISHA** | `/khong-gian-isha/` (ID 308), `/khong-gian-isha/{slug}/` | `templates/template-space.php`, `single-isha_space.php`, `inc/space.php`, `template-parts/space/{list,single}.php` (dùng lại `about/{hero,section}.php`); `class-space-fields.php` | `space.css` (deps `about.css`) | `about.js` |
| 4a | **Dịch vụ** (tổng) | `/dich-vu/` (ID 12), `templates/template-services.php` | `inc/services-hero.php`, `template-parts/{services-page,services-hero,services-groups}.php` | `services-hero.css`, `services-groups.css` | `services-hero.js` |
| 4b | Dịch vụ: **nhóm** | `/nhom-dich-vu/{slug}/`, `taxonomy-isha_service_cat.php` | `inc/{services-hero,service-group,services-cinematic}.php`, `template-parts/{services-listing,services-cinematic}.php`, `service-group/{intro,results,compare,list}.php`; `class-term-fields.php` | `services-hero.css`, `service-group.css`, `services-cinematic.css`, `card-ring.css` | `services-hero.js`, `service-group.js`, `services-cinematic/**`, `card-ring/**` |
| 4c | Dịch vụ: **liệu trình** | `/dich-vu/{slug}/`, `single-isha_service.php` | `inc/{service-single,service-wheel}.php`, `template-parts/service-single.php`; `class-service-fields.php` | `service-single.css` + HOME-SET | `service-single.js` + HOME-SET, `home-wheel/` |
| 5 | **Tin tức** | `/tin-tuc/` (ID 15 = Trang bài viết) → `home.php`; chi tiết `/tin-tuc/{slug}/` | `home.php`, `inc/editorial-list.php`, `template-parts/editorial/{list,tile}.php`; chi tiết: `single-isha_article.php`, `inc/post-single.php`, `template-parts/post-article.php`; `class-article-fields.php` | `editorial-list.css`; chi tiết: `post-article.css` + HOME-SET | danh sách: không JS; chi tiết: `post-article.js` + HOME-SET |
| 6 | **Sự kiện** | `/danh-sach-su-kien/` (ID 206, WP tự áp theo slug); chi tiết `/su-kien/{slug}/` | `page-danh-sach-su-kien.php`, `inc/editorial-list.php`; chi tiết: `single-isha_event.php`, `inc/post-single.php`, `template-parts/post-event.php`; `class-event-fields.php` | `editorial-list.css`; chi tiết: `post-article.css` + `post-event.css` | chi tiết: `post-article.js` |
| 7 | **Chính sách** | `/chinh-sach/{slug}/` (CPT); Page `chinh-sach-khach-hang` (ID 7, Elementor); mẫu `template-policies.php`; landing | `single-isha_policy.php`, `inc/policy-single.php`, `template-parts/{policy-screens,landing-policy}.php`, `templates/template-policies.php`; `isha-core/includes/{class-policy-fields,class-shortcodes}.php` | `policy-single.css`, `policy-membership.css`; `isha-core/assets/css/isha-core.css` | `policy-single.js`; plugin `isha-lp-stack.js`, `isha-lp-ladder.js` |
| 8 | **Liên hệ** | `/lien-he/` (ID 16), `templates/template-contact.php` | `template-parts/contact/page.php`, `forms/contact-form.php`, `inc/forms.php`; `class-contact.php` | `contact.css` (+ `home.css`, `booking.css`) | `booking.js` |
| 9 | **Header** | mọi trang | `header.php`, `header-home.php`, `template-parts/header/masthead.php`, `inc/header.php` (+ nút tìm kiếm từ `inc/live-search.php`) | `main.css` khối Header (`:211-792`) | `main.js` |
| 10 | **Footer** | mọi trang | `footer.php`, `inc/footer.php` | `main.css` khối Footer (`:910-1093`) | không |
| 11 | **Search** | popup Ctrl/Cmd+K; `/?s=` | popup: `inc/live-search.php`, `template-parts/search/live-search-dialog.php`; trang kết quả: `search.php`, `searchform.php`, `template-parts/content-{card,none}.php`, `sidebar.php` | popup: `live-search.css`; trang kết quả: chỉ `main.css` (`.grid`, `.layout--sidebar`, `.search-form`) | `live-search.js` |
| 12 | **Popup đặt lịch** | `dialog#isha-book` (mọi trang) + trang `/dat-lich-hen/` (ID 14) | `template-parts/forms/{booking-dialog,booking-form}.php`, `templates/template-booking.php`, `inc/forms.php`; `isha-core/includes/{class-booking,class-forms}.php` | `booking.css` | `booking.js` |
| - | Khác: 404 · Lên đầu trang · Dock liên hệ | `404.php`; `inc/scroll-top.php`; `template-parts/home/dock.php` | | `404.css`; `scroll-top.css`; `home.css` | `404.js`; `scroll-top.js`; `home.js` |

## 2. Trạng thái và nợ kỹ thuật theo trang

| # | Trang | Trạng thái | Commit gần nhất | Nợ kỹ thuật / điểm cần biết |
|---|---|---|---|---|
| 1 | Trang chủ | **Ổn định**; landing đang **TẮT** trên DB local (theme_mod `isha_landing_only` = false) nên trang chủ điện ảnh chạy thật. Mặc định của code là BẬT (`inc/helpers.php:86`) | `531ad47` 27/11 | (1) `home.css` có **thay đổi chưa commit** (sửa `[data-reveal]` 27/11, chưa chạy lại QA). (2) `isha-wordmark.svg` **chưa có** trong `assets/images/`: hero và cảnh kết dùng 4 `<text>` Garamond dự phòng (`inc/home/helpers.php:328-330`, `template-parts/home/hero.php:158`, `template-parts/home/cta.php:109`). (3) Section Quy trình đang ẩn (`home-schema.php`). (4) `front-page.php:8` còn ghi "13 section" (thực tế 11). (5) `home.css` 202 KB, `home.js` 80 KB, không minify, nạp lại ở 5 loại trang khác. (6) Chữ nhỏ Garamond (bản Display, nét mảnh) chờ khách chọn: mua bản Text hay đổi sang Montserrat. (7) `_isha_event_register_url` chỉ banner trang chủ đọc (`home/data.php:1072`) |
| 2 | Giới thiệu | **Ổn định** | `6075c82` 26/11 | Nội dung soạn trong trình soạn thảo của trang **không hiển thị** (nội dung đi qua meta `_isha_about_*`). Kéo cả HOME-SET (202 KB CSS) chỉ để dùng `.isha-home`. Ảnh/nội dung ba section từng là bản tạm (ghi chú 17/11, chưa xác minh lại) |
| 3 | Không gian ISHA | **Ổn định** (trang cũ nhất trong nhóm đã dựng lại) | `c369551` 20/11 | Phụ thuộc `about.css`/`about.js` (đổi hero Giới thiệu thì ảnh hưởng cả hai). View Transitions (`home.js` `pageswap`, `space.php:302-308`) chỉ chạy ở trình duyệt có hỗ trợ; không có thì chuyển trang thường. Không có archive CPT (danh sách là Page ID 308) |
| 4a | Dịch vụ (tổng) | **Ổn định** | `531ad47` 27/11 | `services-hero.css` còn mốc `767/768` lệch chuẩn theme (03 mục 3). Nhóm hiển thị theo `_isha_order` của term. Thứ tự trang (`template-parts/services-page.php`): hero nan quạt → vùng nội dung → lưới nhóm → khối mời đặt lịch của footer. Trang là trang Elementor (`_elementor_edit_mode = builder`) nhưng nội dung đang **rỗng** (DB 27/11), nên thực tế chỉ có hero + lưới nhóm |
| 4b | Dịch vụ: nhóm | **Chờ khách**: màn chờ "Bình minh" (25/11) chưa được duyệt hình/nhịp, chưa đo FPS trên GPU tích hợp thật | `531ad47` 27/11 | (1) Mốc `767/768` ở `services-cinematic.css`, `card-ring.css`. (2) Nhãn trợ năng tiếng Việt viết cứng trong JS (mục 3, nhóm A). (3) `service-group.php:200,203` nhận diện chữ giữ chỗ bằng regex tiếng Việt. (4) ES module `three` không minify (`three.core.js` 1,5 MB), chỉ nạp ở nhóm có liệu trình. (5) `_isha_cover_video_id` được `services-hero.php:183` dùng nhưng template trang chủ không phát video bìa. (6) Ảnh trước/sau ở `service-group/compare.php` cần khách đồng ý bằng văn bản và pháp chế xác nhận trước khi đăng. (7) Bộ `tests/*.test.mjs` (12 file, 106 test) chỉ phủ phần WebGL/Card Ring, không phủ PHP; chạy 27/11: **104 đạt, 2 hỏng** ở `tests/intro-layout.test.mjs` (đỉnh núi lệch đúng 50px: 404,8 ≠ 354,8), chưa tra nguyên nhân (test hay `intro-layout.js` cần cập nhật) |
| 4c | Dịch vụ: liệu trình | **Ổn định** | `531ad47` 27/11 | Phụ thuộc ACF cho 4 trường (ADR-02) và gallery dự phòng khi không có ACF PRO. Chương nội dung tách bằng JS theo thẻ `H2` (ADR-03): không JS thì không có chương, bài vẫn đọc được. Ảnh đại diện từng là placeholder (ghi chú 10/11, chưa xác minh lại) |
| 5 | Tin tức | **Ổn định** | `531ad47` 27/11 (chi tiết) · `6075c82` (danh sách) | (1) `page-tin-tuc.php` không bao giờ chạy khi trang là Trang bài viết (`home.php` thắng): chỉ là mẫu dự phòng. (2) `editorial-list.css` dùng mốc `640/641/1100` (mobile-first thật), khác chuẩn 900/560. (3) Chi tiết có hai nhánh nội dung (mặc định và `--builder` của Elementor): sửa CSS/JS phải kiểm cả hai. (4) Khối "Liệu trình gợi ý" phụ thuộc term meta `_isha_service_group` của từng Chủ đề; chưa map thì dẫn về trang Dịch vụ (đã map 3 chủ đề trên DB ngày 25/11). (5) Mục lục TOC chỉ có sau khi JS chạy, `#slug` theo chữ đang hiển thị (ADR-03) |
| 6 | Sự kiện | **Ổn định** | `531ad47` 27/11 | (1) `_isha_event_register_url` còn ở admin nhưng trang chi tiết không dùng (chủ ý: trang chỉ cung cấp thông tin). (2) `isha_post_single_is_online` dựa regex chữ Việt/Anh (`post-single.php:158`, đổi bằng filter `isha/event/online_pattern`). (3) `.ics` chưa làm; link lịch Google dựng bằng PHP. (4) Ảnh sự kiện cũ có thể chưa có size `isha-banner` 16:10: cần tạo lại thumbnail khi có ảnh thật (ghi chú 25/11, chưa xác minh) |
| 7 | Chính sách | **Ổn định** (CPT + trang thẻ kính); **Mẫu mặc định** cho Page thường `chinh-sach-bao-mat` (ID 3) dùng `page.php` | `6075c82` 26/11 | (1) Plugin: `isha-lp-stack.js:14` mốc `767px`, `isha-lp-ladder.js:18` mốc `900px` không kèm `max-height: 560px`. (2) `isha-core.css` (360 dòng) nạp **mọi trang**, không điều kiện, còn fallback `#1c1b19` cũ. (3) Các shortcode `[isha_*]` của plugin, trừ `[isha_policies]` ở `template-policies.php`, chưa xác minh trang nào dùng (nội dung nằm trong DB). (4) `policy-single.js` cần chốt 9 giây ở `<head>` (`enqueue.php:107-115`) vì khoá cuộn. (5) `inc/policy-single.php:88` so chữ `'hạng'` cứng. (6) Page `trang-mau` (ID 2, "Sample Page", 1552 ký tự) **đang xuất bản**: nên xóa. (7) Landing BẬT thì `landing-policy.php` in nội dung trang chủ tĩnh ID 10 (đang rỗng): xem phụ lục A |
| 8 | Liên hệ | **Ổn định** | `6075c82` 26/11 | (1) Thân email báo admin viết cứng tiếng Việt (mục 3, nhóm B). (2) Nonce cấp cho khách là công khai, chống lạm dụng chỉ có honeypot + 5 lần/giờ/IP, không captcha. (3) Bản đồ nhúng iframe lấy bất kỳ URL https của `map_url` (`contact/page.php:151`), trong khi shortcode `[isha_map]` chỉ nhận host Google. (4) Chưa có plugin SMTP trong `wp-content/plugins/`: mail gửi bằng `wp_mail` mặc định, trên XAMPP/host chưa cấu hình có thể không tới; dữ liệu vẫn nằm trong CPT `isha_contact` |
| 9 | Header | **Ổn định**; giao diện Floating Glass **chờ khách duyệt** (26/11) | `6075c82` 26/11 | (1) `header.php` và `header-home.php` gần như trùng (khác ~7 dòng: `theme-color`, class của `<main>`, đối số `home` truyền cho masthead). (2) Tám mục menu vừa một dòng nhờ các bậc `975/1080/1180/1280px` (`main.css:~647-700`): thêm mục menu phải kiểm lại ở 1180px. (3) Tìm kiếm nhanh chưa lọc theo TranslatePress (`live-search.php:142-155`). (4) Khách còn phải tắt nút nổi ngôn ngữ của TranslatePress và dịch 3 chuỗi mới "Đặt lịch tư vấn riêng", "Ngôn ngữ", "Mở menu con" (ghi chú 26/11, chưa xác minh) |
| 10 | Footer | **Ổn định** | `531ad47` 27/11 | Action `isha_footer_language_switcher` không có hook nào gắn (`footer.php:26,164`): ô ngôn ngữ ở footer luôn rỗng, bộ chuyển chỉ có ở header. Landing BẬT thì footer rút gọn. `#c9a46a` ở `main.css` chưa là token (03 mục 1) |
| 11 | Search | Popup: **Ổn định**, giao diện **chờ khách duyệt** (bo 2px; khách có thể muốn bo mềm, đổi 3 biến `--ls-r-*`). `/?s=`: **Mẫu mặc định** | `6075c82` 26/11 · `a3a88cc` 14/11 (`search.php`) | (1) `search.php` dùng `isha_page_banner()` và lưới `.grid--2` cũ, không nạp `page.css`/`home.css`, chưa theo ngôn ngữ thiết kế mới: cần xem bằng trình duyệt trước khi quyết định dựng lại. (2) Chỉ tìm theo `post_title`; không rate-limit (ADR-05). (3) Chip gợi ý viết cứng (mục 3, nhóm A) |
| 12 | Popup đặt lịch | **Ổn định** | `6075c82` 26/11 | (1) Popup chỉ bật khi có Page đã xuất bản dùng `template-booking.php` (`forms.php:49-51`); thiếu trang đó thì mọi nút "Đặt lịch" thành liên kết thường. (2) Mốc `780/600` ở `booking.css`. (3) Thân email báo admin viết cứng tiếng Việt. (4) Trang chính sách bảo mật mà ô đồng ý trỏ tới (ID 3) cần pháp chế duyệt (ghi chú 18/11, chưa xác minh) |
| - | 404 · Lên đầu trang · Dock | **Ổn định** | `6075c82` 26/11 | `404.css` có hai chỗ `#c9a46a` lặp. Nút Lên đầu trang tắt ở single `isha_policy` và tự né dock bằng `MutationObserver` |
| - | Mẫu mặc định còn lại | **Mẫu mặc định** | `a3a88cc` 14/11 | `index.php`, `archive.php`, `single.php`, `page.php`, `sidebar.php`, `comments.php`, `content-card.php`, `content-none.php`: khung WordPress gốc của bước setup. `page.php` đang phục vụ Page thường (ID 2, 3). Bình luận đã ẩn ở wp-admin (`inc/admin.php`) |

## 3. Chuỗi tiếng Việt chưa qua hàm dịch

**Bối cảnh.** Theme và plugin viết chuỗi gốc bằng tiếng Việt và bọc `__()` (textdomain `isha`, `isha-core`), nhưng **chưa có file `.po/.mo`** (`languages/README.txt`). Site chạy TranslatePress với `vi` và `en_US` đều được xuất bản (DB `trp_settings`), nên bản Anh do TranslatePress dịch trên HTML đầu ra; `__()` chỉ chuẩn bị cho `.po` sau này và không quyết định việc TranslatePress có "thấy" chuỗi hay không. Chuỗi do JS gán sau khi trang tải (như `aria-label`) có được TranslatePress dịch hay không là **chưa xác minh**.

**Kết quả quét** (script đọc literal chuỗi, bỏ comment): theme PHP có ~430 literal tiếng Việt đã qua `__()`/`_e()`/`_n()`; plugin PHP ~630. **Không có** đoạn chữ tiếng Việt trần ngoài thẻ PHP trong bất kỳ template nào. Phần còn lại:

| Nhóm | Vị trí | Nội dung | Ghi chú |
|---|---|---|---|
| **A. Khách thấy / nghe** | `inc/live-search.php:38` | 6 chip gợi ý: `Peel, HIFU, Botox, Mụn, Da khỏe, Workshop` | Nhãn và từ khoá là một chuỗi (`live-search-dialog.php:30`), tìm theo tiêu đề tiếng Việt; muốn dịch nhãn phải tách riêng từ khoá. Đổi được bằng filter `isha/live_search/suggestions` |
| A | `assets/js/card-ring/a11y.js:13` | `Tạm dừng vòng thẻ` / `Cho vòng thẻ xoay tiếp` (`aria-label`) | Không đi qua `window.ISHA.*` |
| A | `assets/js/home-wheel/main.js:63` | `Khám phá nhóm: ` + tên nhóm (`aria-label`) | như trên |
| A | `assets/js/services-cinematic/ui/a11y.js:9-10,19-20` | thông báo ẩn/hiện vòng thẻ, `aria-roledescription`, `aria-label` khung cảnh | như trên |
| A (rủi ro thấp) | `assets/js/home.js:19-25` | `FALLBACK` (Trước, Sau, Khung %d, băng chuyền...) | Chỉ dùng khi `homeConfig.strings` thiếu; PHP luôn truyền (`inc/home/assets.php:74`) |
| **B. Email báo admin** | `isha-core/includes/class-booking.php:222-229`, `class-contact.php:196-201` | Nhãn thân thư: "Họ tên:", "Điện thoại:", "Dịch vụ:", "Ngày giờ:", "Ghi chú:", "Chủ đề:" | Tiêu đề thư có `__()`, thân thư thì không. Người nhận là chủ phòng khám nên mức thấp |
| **C. Logic gắn với chữ Việt** | `inc/post-single.php:158` · `inc/service-group.php:200,203` · `inc/policy-single.php:88` · `isha-core/includes/class-seed.php:48-49` | Regex "trực tuyến"/zoom...; regex "đang cập nhật"/"lorem ipsum" và danh sách nhãn "thời gian", "mô tả ngắn"; `'hạng'`; biến thể chính tả "mĩ/mỹ" | Chạy trên dữ liệu gốc tiếng Việt nên đúng; chỉ hỏng nếu ngôn ngữ nguồn đổi |
| **D. Không phải nợ** | `inc/demo-content.php:23-117` · `class-seed.php` · `inc/services-cinematic.php:72` · `assets/js/services-cinematic/{main,lifecycle,tier,debug}.js`, `founder-gallery/*.js`, `policy-single.js:469` · `inc/plugins/recommended.php:22` · `admin-media.js:21-47` | Dữ liệu seed/kích hoạt, nhật ký dev (`console.warn`, `error_log`), panel debug `?isha-debug=1` chỉ admin, comment trong shader GLSL, hàm không được gọi, chuỗi dự phòng trùng khoá với PHP | Bỏ qua khi rà dịch |

Hai chuỗi `%d liệu trình` (`template-parts/home/services.php:125`, `services-groups.php:70`) và `%s bình luận` (`comments.php:22`) trông như chưa dịch nhưng là đối số số nhiều thứ hai của `_n()`: không phải nợ.

## 4. Nợ kỹ thuật dùng chung

| # | Nợ | Bằng chứng | Gợi ý |
|---|---|---|---|
| 1 | Mốc responsive lệch chuẩn theme 900/901 và 560/561 | `767/768`: `card-ring.css`, `home.css`, `services-hero.css`, `services-cinematic.css`, `isha-core.css`, `isha-lp-stack.js:14`; `1024/1025`: `main.css:1223`, `service-group.css`; `780/600`: `booking.css` (03 mục 3) | Gộp dần khi sửa từng file; mỗi lần đổi phải chụp lại ở 768/900/901 |
| 2 | `#c9a46a` (chữ vàng trên nền tối) chưa là token, lặp 21 chỗ | `home.css` 14, `404.css` 2, `live-search.css` 2, `main.css` 2, `booking.css` 1 (03 mục 1) | Khai `:root` một token rồi thay dần; phải hỏi dev trước (quy tắc "không tự thêm mã màu") |
| 3 | `cubic-bezier(.22,1,.36,1)` viết thẳng ở 6 file CSS và plugin | 03 mục 5 | Đổi sang `var(--isha-ease-cine)` |
| 4 | Tài nguyên trang chủ nạp ở trang không cần | HOME-SET ở Giới thiệu, Không gian, liệu trình, Tin tức, Sự kiện (01 mục 3.3); `home.css` 202 KB, `home.js` 80 KB; `three.core.js` 1,5 MB không minify | Ghi nhận (ADR-01); tách chỉ khi khách báo chậm, và phải qua ngưỡng phức tạp 40% |
| 5 | `isha-core.css` nạp mọi trang, không điều kiện | `class-shortcodes.php:36` | Chỉ nạp khi trang có shortcode (vẫn phải kiểm Elementor) |
| 6 | `filemtime()` gọi thẳng, thiếu file thì cảnh báo PHP | `enqueue.php`, `plugins/compat.php`, `class-shortcodes.php` (01 mục 3.1) | Bọc `is_file()` như các hàm nạp khác |
| 7 | Không có SEO tự viết (meta description, Open Graph, JSON-LD, canonical); Yoast chỉ được nhắc, chưa cài | grep `ld+json`, `og:`, `canonical` không ra kết quả (02 mục 9 #18) | Quyết định có cài plugin SEO hay không |
| 8 | Endpoint công khai không giới hạn | `isha_live_search`, `isha_form_nonce`, `isha_live_search_nonce` (ADR-05; 02 mục 6) | Thêm rate-limit nếu lưu lượng tăng |
| 9 | Mã chết / mô tả sai | `isha_recommended_plugins()` (`recommended.php:18`); action `isha_footer_language_switcher` (`footer.php:26,164`); `page-tin-tuc.php`; `front-page.php:8` "13 section"; `masthead.php:8` "Đặt lịch là liên kết"; `live-search.php:11` "chưa có Polylang/WPML" (01 mục 7) | Sửa comment khi chạm file; không sửa hàng loạt (rủi ro autocrlf) |
| 10 | Comment trong code trỏ tới docs đã xóa | `main.css:31` và `README-services-cinematic.md:56` → `docs/bang-ma-mau.md`; ~49 chỗ → `docs/home-architecture.md`; nhiều file → `docs/*.md` đã xóa (01 mục 6) | Để nguyên theo quyết định 27/11; tra `03` thay `bang-ma-mau.md` |
| 11 | Dữ liệu sót trong DB | Page `trang-mau` (ID 2) đang publish; meta cũ `_isha_home_story/philosophy/values/vision` không còn nơi đọc (02 mục 3.2) | Xóa trang mẫu; giữ meta cũ cho đến khi khách xác nhận |
| 12 | Kiểm thử | 2/106 test Node đang hỏng (`intro-layout.test.mjs`, xem mục 2 dòng 4b); bộ QA hồi quy nằm ngoài repo (`C:\xampp\isha-backups\2026-11-18_after-phase3-restructure\qa-regression\`); thêm trang mới phải bổ sung `pagesOf()`/`PAGE_CHECKS` trong `checks/browser.mjs` và `$facts` trong `php/wp-read.php`; chưa chạy lại sau sửa `home.css` 27/11 | Chạy `node run.mjs` trước khi commit `home.css` |

## 5. Việc đang chờ

| Loại | Việc |
|---|---|
| Chưa commit | `wp-content/themes/ISHA/assets/css/home.css` (sửa `[data-reveal]`, 03 mục 8) |
| Chờ khách duyệt | Màn chờ Bình minh (hình, nhịp, FPS thật) · header Floating Glass · bo góc popup tìm kiếm · cỡ chữ nhỏ Garamond |
| Chờ khách cung cấp | `assets/images/isha-wordmark.svg` (spec ở phụ lục B) · ảnh/video thật cho hero, bìa nhóm, Không gian, đối tác (tình trạng hiện tại chưa xác minh) |
| Khách tự làm trong wp-admin | Tắt nút nổi ngôn ngữ TranslatePress · dịch 3 chuỗi header mới · cấu hình gửi mail (SMTP) · pháp chế duyệt chính sách bảo mật và việc dùng ảnh trước/sau |
| Phiên riêng | Rà `docs/home-architecture.md` (287 KB, còn mô tả header cũ và 13 section) |

---

## Phụ lục (rút từ tài liệu cũ đã xóa ở phiên 3, đã đối chiếu code)

### A. Vận hành trang chủ

- **Chế độ landing** (Giao diện › Tùy biến › ISHA – Giao diện): BẬT = `landing-policy.php` in `the_content()` của **trang chủ tĩnh** (ID 10 "Trang chủ", nội dung **rỗng** theo DB 27/11). Muốn quay lại landing, ngoài việc tick ô này phải vào Cài đặt › Đọc đặt Trang chủ = trang Chính sách khách hàng (ID 7) hoặc chép nội dung sang ID 10; nếu không, trang chủ hiện trắng. Tắt landing thì menu, tìm kiếm và nút Đặt lịch của header hiện theo cấu hình site.
- **Xem trước khi landing BẬT:** `/?isha_preview=home`, cần đăng nhập có quyền `edit_pages`; trang không cache và `noindex` (`inc/home/assets.php:228-251`).
- **Section thiếu dữ liệu hoặc tắt công tắc "Hiển thị section này" thì tự biến mất**; ô để trống dùng mặc định trong `isha_home_defaults()` (`inc/home/data.php:63`).
- **Xử lý nhanh:**

| Hiện tượng | Nguyên nhân thường gặp | Việc làm |
|---|---|---|
| Một section biến mất | Công tắc tắt; chưa có dữ liệu (sự kiện/bài/lời kể đã xuất bản, nhóm tick "Dịch vụ tiêu biểu", ảnh ở thẻ Hiệu quả liệu trình) | Bật công tắc / bổ sung dữ liệu / xuất bản |
| Video hero không phát | Quá dung lượng, không phải H.264, hoặc khách bật Save-Data / giảm chuyển động (trang chỉ giữ poster, 03 mục 6) | Xuất lại video; kiểm poster |
| Khối màu trơn thay ảnh | Chưa gán ảnh (khối giữ chỗ) | Gán ảnh |
| Cặp ảnh trước/sau không hiện | Cặp thiếu một ảnh hoặc ảnh đã bị xóa khỏi Thư viện | Mở Sửa nhóm, bấm Cập nhật, thêm lại cả hai ảnh đúng vị trí |
| Sửa xong không thấy đổi | Cache trình duyệt/plugin; module ES không có `?ver` | Ctrl+F5; "Empty cache and hard reload" |
| Nội dung trang Giới thiệu không hiện | Soạn trong trình soạn thảo của trang thay vì hộp "Nội dung trang Giới thiệu" | Nhập ở menu Giới thiệu |

### B. Quy cách media (khuyến nghị, không có kiểm tra trong code trừ khi ghi rõ)

| Loại | Quy cách |
|---|---|
| Chung | Tông đen trắng hoặc da ấm, hạt nhẹ; **không** chữ/logo/watermark trong ảnh; chủ thể ở giữa khung (nhiều khung bị cắt tròn hoặc đổi tỉ lệ). Ảnh có nội dung phải có Alt; ảnh trang trí để trống Alt |
| Video | MP4 H.264, **không kênh âm thanh**, 24-30 fps, vòng lặp liền; hero ngang 1920×1080 ≤ 8 MB, hero dọc 1080×1350 ≤ 3 MB. Luôn lặp, không tiếng, không nút phát |
| Ảnh | JPG/WebP ~80%, ≤ ~500 KB; poster hero ≤ 250 KB (phần tử lớn nhất trang). **Không upload SVG** (WordPress chặn mặc định) |
| Poster/video hero sáng | Trang phủ lớp Shadow opacity .74 để chữ đạt tương phản (`home.css:5195`); ảnh càng sáng càng bị che nhiều, nên chọn khung tông trung bình đến tối |
| Tỉ lệ theo vị trí | Bìa nhóm dịch vụ và ảnh liệu trình dọc 4:5 (≥ 1200×1500); cặp trước/sau 4:5 cùng kích thước, ảnh thứ *n* trước ghép ảnh thứ *n* sau; Banner Tin tức 16:10 (1600×1000); ảnh dọc Sự kiện 4:5; Không gian cạnh dài ≥ 2000 px; Founder: ảnh chân dung tách nền, gallery ≤ 20 ảnh; logo đối tác PNG/WebP nền trong, khung vuông, 12 hoặc 15 logo cho các vòng đều |
| **`isha-wordmark.svg`** (đặt tay vào `assets/images/`, không upload Media) | Có `viewBox`; mỗi chữ một `<path>` id `isha-i`, `isha-s`, `isha-h`, `isha-a`; **không** `<style>`/`<script>`; **không** `fill`/`stroke` cố định (màu theo `currentColor`); < 256 KB (`inc/home/helpers.php:303-322`). Có file thì thay 4 `<text>` dự phòng |
| Pháp lý | Ảnh trước/sau chỉ dùng khi khách đã đồng ý bằng văn bản; quảng cáo dịch vụ y tế có quy định, để pháp chế xác nhận trước khi đăng |

### C. Danh sách Bento (Tin tức, Sự kiện)

- Mỗi "màn bento" gồm hero (bài mới nhất còn lại) + tối đa 3 bài **cùng chuyên mục** + tối đa 3 bài **cùng tác giả** lấy từ phần còn lại, không bài nào lặp (`ISHA_EDITORIAL_GROUP_SIZE` = 3, `inc/editorial-list.php:31`). Nhóm thiếu bài thì chỉ hiện số bài thật; lưới tự dàn lại bằng class `isha-bento--c0..3` và `--a0..3`.
- Tab chuyên mục `?chuyen-muc=slug` và phân trang `?trang=N` là **liên kết thường**, không JS. Không dùng `/page/N/` vì trên Trang bài viết nó chạy vào truy vấn blog chính và 404. Lưới: ≥ 1100px 4 cột, 641-1099px 2 cột, ≤ 640px 1 cột.
- **Sự kiện** tách "sắp diễn ra" (gần nhất trước, chưa có ngày thì xuống cuối) và "đã diễn ra" (gần nhất trước), qua giờ kết thúc hoặc hết ngày bắt đầu (`isha_editorial_event_split`, `:470`); **Tin tức** sắp theo ngày đăng. Thẻ bài: ảnh, tiêu đề, nội dung ngắn (trường `_isha_*_summary` → excerpt → 26 từ đầu), tác giả, ngày.
- Tác giả: tên hiển thị trùng tên đăng nhập thì **không in** (tránh lộ tài khoản quản trị); dùng họ tên, không có nữa thì tên website (`isha_editorial_author_name`, `:151`). Đặt "Hiển thị công khai là" ở Người dùng › Hồ sơ.
- Filter: `isha/editorial/sources`, `isha/editorial/author_name`, `isha/editorial/pool_max` (mặc định 500 bài), `isha/editorial/date_format`.
- Thêm danh sách thứ ba: khai post type trong `isha_editorial_sources()` (taxonomy chuyên mục, khoá meta nội dung ngắn, câu "chưa có") rồi thêm mẫu trang gọi `isha_editorial_render_page( '<post type>' )`.

### D. Giọng văn và nội dung thương hiệu

Nguồn là bản `ISHA BRAND STORY ver1` (khách cung cấp, 29/09/2026); **chỉ để tham khảo**, nội dung các section hiện có không đổi và không thêm section "Bốn tinh thần" (đã làm thử rồi gỡ hẳn). Hành trình: thanh lọc → tìm thấy ý nghĩa → không ngừng hoàn thiện → phụng sự bằng cả tấm lòng. Câu chốt: "Beauty becomes more than appearance. It becomes a way of being." Bốn tinh thần: **Kiyomeru** (chắt lọc, biết khi nào dừng), **Ikigai** (vẻ đẹp nội tại, vẫn nhận ra chính mình), **Kaizen** (mỗi ngày tốt hơn một chút), **Omotenashi** (lắng nghe trước khi tư vấn, thấu hiểu trước khi điều trị). Giọng văn: xưng "chúng tôi", ấm, bình tĩnh, **không hứa hẹn kết quả**, không ngôn ngữ khuyến mại; không đặt cam kết y khoa cố định vào template mọi bài. Bản gốc còn thô (dấu câu, ký hiệu `=>`), phải biên tập trước khi đăng nguyên văn; tên Nhật viết La-tinh không dấu.
