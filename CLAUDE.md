# ISHA Beauty Clinic: onboarding cho phiên bảo trì

Site WordPress của phòng khám da liễu thẩm mỹ ISHA, chạy local trên XAMPP (`http://localhost/ISHA-Beuty-Clinic`, DB MySQL `isha`, tiền tố bảng `isha_`). Nhánh làm việc `develop/feature`, nhánh chính `main`. **Code là nguồn sự thật**; tài liệu ở `docs/` lập từ code, đọc [docs/README.md](docs/README.md) trước khi suy đoán. Quy tắc UI/UX chi tiết nằm ở `.claude/CLAUDE.md` (ưu tiên cao nhất, đọc trước mọi việc giao diện).

## Stack

- Theme cổ điển `wp-content/themes/ISHA` (v1.0.0) + plugin `wp-content/plugins/isha-core` (v1.1.0, PHP 7.4+, chứa CPT, meta box, form, shortcode).
- Plugin ngoài: ACF (chỉ Dịch vụ và Chính sách), Elementor (trang Chính sách khách hàng, Tin tức, Sự kiện), TranslatePress (VI/EN). Còn lại xem 01 mục 5.
- **Không có bước build** (không `package.json`, `composer.json`, bundler). CSS viết tay, mỗi trang/section một file ở `assets/css/`; JS front-end là IIFE ES5, không jQuery. WebGL dùng ES module `three@0.187.0`, `lil-gui`, `detect-gpu` tự host ở `assets/vendor/`, đăng ký qua `inc/script-modules.php`.
- Phiên bản file = `filemtime()`; module ES con không có `?ver` nên `assets/js/.htaccess` đặt `no-cache`. Bản cũ trong trình duyệt là nghi phạm số một khi số đo "lạ".
- Chuỗi gốc tiếng Việt bọc `__()`, chưa có `.po/.mo`; bản Anh do TranslatePress dịch trên HTML đầu ra.

## Quy ước bắt buộc

1. **Trả lời người dùng bằng tiếng Việt.**
2. **UI/UX:** chỉ dùng token đã có (`main.css:17-96`), không tự sinh mã màu; tái dùng class sẵn có; mốc responsive là **900/901px và cao 560/561px** (không 768/1024); quy tắc theme thắng đặc tả trong prompt; vi phạm thì **hỏi dev trước**. Tra [03](docs/03-ui-ux-design-system.md).
3. **Chữ:** Garamond cho tiêu đề, nhãn, lead, ghi chú (không chữ hoa); Montserrat cho đoạn văn và nút. Chữ vàng nhỏ trên nền tối dùng `#c9a46a`; `--isha-gold` chỉ cho nét/viền; chữ vàng nhỏ trên nền sáng dùng `--isha-gold-ink`.
4. **Chuyển động:** mặc định tĩnh, đọc trọn vẹn khi không có JS, không có hover, hoặc khi giảm chuyển động. Hiệu ứng đặt trong `html.isha-motion-on` hoặc `prefers-reduced-motion: no-preference`; `:hover` chỉ trong `(hover: hover) and (pointer: fine)`; chỉ animate `transform`/`opacity`.
5. **Thẩm mỹ:** sang trọng, hiện đại, không "quê". Mặt trời là chủ đề chính: **không đề xuất bỏ**, quá nổi thì làm chìm màu/nét. Viền vàng mảnh; chữ đè ảnh không dùng hộp nền.
6. **Hiệu năng:** giữ độ nét; khách chấp nhận ~45-50 FPS trên GPU tích hợp, **không** đề xuất hạ độ phân giải.
7. **Ngưỡng phức tạp 40%:** đề xuất kỹ thuật có độ phức tạp từ 40% trở lên (viết lại lớn, giữ song song đường dự phòng) thì không triển khai, báo lại kèm ước lượng.
8. **Nội dung:** không đặt cam kết y khoa cố định ("kiểm duyệt bởi bác sĩ"...) vào template; dùng dữ kiện thật của bài (tác giả, chủ đề, ngày). Giọng văn xưng "chúng tôi", ấm, không hứa hẹn kết quả (05 phụ lục D).
9. **WordPress:** theo WordPress Coding Standards; escape đầu ra, nonce + `current_user_can` cho mọi ghi; việc WordPress ưu tiên dùng agent `wordpress-master`. Theme chỉ đọc meta bằng `get_post_meta`, không gọi `get_field()`.
10. **Git:** máy dev đặt `core.autocrlf=true`. Không đổi kiểu xuống dòng của tệp không sửa; tệp mới hoặc đang sửa giữ LF; **tránh `git stash`**. Chỉ commit khi được yêu cầu; không commit `wp-config.php`, `.env`, `*.sql`. `docs/` và `.claude/` bị `.gitignore`.
11. **Thêm CSS/JS cho trang mới:** khai báo trong hàm `*_assets()` của file `inc/` tương ứng (kiểm `is_file()`, version bằng `filemtime()`); thêm trang vào bộ QA (xem mục Kiểm tra).

## Bản đồ "sửa X → mở file Y"

Đường dẫn tính từ `wp-content/themes/ISHA/`; plugin ghi `isha-core/` (= `wp-content/plugins/isha-core/includes/`).

| Muốn sửa | Mở |
|---|---|
| Màu, token, easing, độ rộng khung | `assets/css/main.css` khối `:root` (`:17-96`) và `theme.json` (đổi màu thương hiệu thì đồng bộ cả hai) |
| Header, menu, drawer, bộ chuyển ngôn ngữ | `template-parts/header/masthead.php`, `inc/header.php`, `main.css` khối Header (`:211`), `assets/js/main.js` |
| Footer | `footer.php`, `inc/footer.php`, `main.css` khối Footer (`:910`) |
| Chữ trên trang chủ | wp-admin › Trang chủ; mặc định `inc/home/data.php:63`; lược đồ trường `isha-core/home-schema.php` |
| Thêm/bớt section trang chủ | `inc/home/registry.php`, `isha-core/home-schema.php`, `inc/home/data.php`, `template-parts/home/<id>.php`, khối cùng tên trong `home.css`/`home.js` |
| Cảnh/hiệu ứng trang chủ | `assets/js/home.js` (`register(id, fn)`), `isha-motion.js`; nạp tài nguyên: `inc/home/assets.php` |
| Founder 3D, bánh xe dịch vụ, sự kiện trang chủ | `assets/js/founder-gallery/`, `home-wheel/`, `events-list/` |
| Giới thiệu, Không gian ISHA | `inc/about.php`, `inc/space.php`, `template-parts/about/`, `template-parts/space/`, `about.css`, `space.css`; trường: `isha-core/class-about-fields.php`, `class-space-fields.php` |
| Trang Dịch vụ, nhóm dịch vụ | `inc/services-hero.php`, `inc/service-group.php`, `template-parts/services-*.php`, `template-parts/service-group/`; trường: `isha-core/class-term-fields.php` |
| Cảnh WebGL "Bình minh" (`#lieu-trinh`) | `assets/js/services-cinematic/`, `inc/services-cinematic.php`, `README-services-cinematic.md`, `docs/isha-lookdev-prompt-v3.md`; công cụ `tools/bake-*.mjs`; test `tests/` |
| Vòng thẻ Card Ring | `assets/js/card-ring/`, `card-ring.css`, `README-card-ring.md` |
| Chi tiết liệu trình | `inc/service-single.php`, `template-parts/service-single.php`, `service-single.css/js` |
| Danh sách Tin tức / Sự kiện | `inc/editorial-list.php`, `template-parts/editorial/`, `editorial-list.css`; mẫu mỏng `home.php`, `page-danh-sach-su-kien.php` |
| Chi tiết Tin tức / Sự kiện | `inc/post-single.php`, `template-parts/post-article.php`, `post-event.php`, `post-article.css`, `post-event.css`, `post-article.js` |
| Chính sách | `inc/policy-single.php`, `template-parts/policy-screens.php`, `policy-single.css/js`; trang thẻ thành viên: plugin `[isha_lp_*]` + `policy-membership.css` |
| Popup đặt lịch, form liên hệ | `inc/forms.php`, `template-parts/forms/`, `booking.css/js`; xử lý máy chủ: `isha-core/class-booking.php`, `class-contact.php`, `class-forms.php` |
| Tìm kiếm nhanh | `inc/live-search.php`, `template-parts/search/`, `live-search.css/js` |
| CPT, taxonomy, meta box, shortcode | `isha-core/class-post-types.php`, `class-*-fields.php`, `class-shortcodes.php` |
| Hotline, Zalo, địa chỉ, giờ mở cửa | wp-admin › ISHA Clinic (`isha-core/class-settings.php`, đọc bằng `isha_opt()`) |
| Landing / xem trước trang chủ | Customizer `inc/customizer.php`; logic `inc/helpers.php:82-119`; xem trước `/?isha_preview=home` |
| CSS/JS nạp ở trang nào | `inc/enqueue.php` và các hàm `*_assets()` trong `inc/`; bảng đầy đủ: 01 mục 3 |
| Chuỗi nào chưa dịch, nợ kỹ thuật | `docs/05-pages-status-audit.md` mục 3 và 4 |

## Kiểm tra

- Cú pháp PHP: `C:\xampp\php\php.exe -l <file>`. Test WebGL: `node --test "tests/*.test.mjs"` trong thư mục theme (27/11: 104/106 đạt, 2 test của `intro-layout` đang hỏng, xem 05 mục 2).
- Bộ hồi quy ngoài repo: `C:\xampp\isha-backups\2026-11-18_after-phase3-restructure\qa-regression\` (`node run.mjs`, chỉ đọc DB). Thêm trang mới thì bổ sung `pagesOf()`/`PAGE_CHECKS` trong `checks/browser.mjs` và `$facts` trong `php/wp-read.php`.
- Trước khi sửa DB thật: sao lưu vào `C:\xampp\isha-backups\` và hỏi người dùng; truy vấn `SELECT` thì tự do.

## Tài liệu

[docs/README.md](docs/README.md) là mục lục. `docs/01-source-structure.md` (cấu trúc, luồng nạp, URL → template), `02-components-and-plugins.md` (dữ liệu, shortcode, AJAX, component), `03-ui-ux-design-system.md` (màu, chữ, breakpoint, kính, chuyển động), `04-technical-decisions.md` (ADR), `05-pages-status-audit.md` (trạng thái từng trang, nợ kỹ thuật). `docs/home-architecture.md` **chưa rà soát, đừng tin**. `docs/` không có trong git: bản clone mới sẽ không có các link trên.
