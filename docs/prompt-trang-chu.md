# Prompt dựng trang chủ điện ảnh ISHA Beauty Clinic

Nov 9, 2026 · @Thien Le Trieu

## Cách dùng tài liệu này

Đây là prompt hoàn chỉnh để giao cho một agent có quyền đọc và sửa repo WordPress của dự án (Claude Code hoặc Cursor). Người giao việc chỉ cần gửi kèm một dòng: “Đọc và thực hiện prompt sau, bắt đầu từ Giai đoạn 0.”

- Agent phải dừng sau Giai đoạn 0 và ở mỗi điểm duyệt trong mục Kế hoạch để chờ xác nhận.
- Phụ lục A là nội dung mặc định, có thể gửi riêng cho khách duyệt; nội dung sửa sau vẫn nhập được trong CMS.
- Phụ lục B là danh sách tài nguyên chủ dự án tự chuẩn bị và import.
- Có thể xuất tài liệu sang Markdown và đặt vào repo (ví dụ `docs/prompt-trang-chu.md`) để agent đọc trực tiếp.
- Người viết prompt chưa đọc mã nguồn plugin ISHA Core; mọi chỗ nhắc tới API của plugin được suy ra từ cách theme gọi, agent phải đối chiếu ở Giai đoạn 0.

## Vai trò, mục tiêu và phạm vi

Bạn là kỹ sư WordPress kiêm design engineer cấp cao, chuyên dựng trải nghiệm web điện ảnh cho thương hiệu xa xỉ, với chuyển động tiết chế và chính xác. Nhiệm vụ: dựng trang chủ phía client của ISHA Beauty Clinic thành một bộ phim ngắn gồm 13 cảnh, mỗi cảnh là một shortcode riêng, rồi dựng lại trang chi tiết liệu trình theo cùng ngôn ngữ thiết kế.

**Khách hàng mục tiêu.** ISHA phục vụ riêng các quý bà sang trọng: phụ nữ thành đạt, quen dịch vụ cao cấp, xem web chủ yếu trên điện thoại. Họ coi trọng chuyên môn y khoa, sự riêng tư và cảm giác được chăm sóc tận tâm; họ khó chịu với giao diện ồn ào, khuyến mãi và chữ nhỏ khó đọc. Mọi quyết định UI/UX phải trả lời được ba chữ: sang trọng, quý phái, điện ảnh.

**Hành động chính** của trang là “Đặt lịch tư vấn riêng”, dẫn tới trang đặt lịch hiện có qua `isha_booking_url()`. Hành động phụ: nhắn Zalo và gọi hotline. Trang chỉ dùng tiếng Việt.

**Trong phạm vi**

- Trang chủ: intro, 13 section shortcode, header biến thể cho trang chủ, dock liên hệ, lớp bầu trời đổi màu, chế độ xem trước cho admin.
- Lõi chuyển động dùng chung `assets/js/isha-motion.js`.
- Dữ liệu và CMS tối thiểu: các trường nội dung trên trang Home, ba CPT mới (`isha_event`, `isha_testimonial`, `isha_article`), trường cho nhóm dịch vụ, lệnh tạo dữ liệu dịch vụ từ leaflet.
- Trang chi tiết liệu trình `single-isha_service.php`.

**Ngoài phạm vi (không sửa):** trang Chính sách và mọi file `policy-single.*`, trang đặt lịch, trang danh sách và chi tiết của bài viết và sự kiện, trang nhóm dịch vụ, header và footer ở các trang khác, Elementor, song ngữ.

## Bối cảnh mã nguồn hiện có

Theme `ISHA` là classic theme: text domain `isha`, tiền tố hàm `isha_`, hằng `ISHA_VERSION`, `ISHA_DIR`, `ISHA_URI`; mọi file PHP mở đầu bằng chặn `ABSPATH` và docblock `@package ISHA`. Các điểm dưới đây đã đọc từ theme, cần xác minh lại ở Giai đoạn 0.

**Theme**

- `functions.php` nạp lần lượt `inc/helpers.php`, `setup.php`, `enqueue.php`, `template-tags.php`, `policy-single.php`, `customizer.php`, `demo-content.php`, `plugins/recommended.php`, `plugins/compat.php`.
- Token trong `:root` của `assets/css/main.css`: màu `--isha-daylight` #e7e9e6, `--isha-skin` #d9c5ad, `--isha-gold` #8e6837, `--isha-shadow` #1c1b19 cùng các biến dẫn xuất `--isha-gold-ink` #6f5129, `--isha-gold-hover` #765328, `--isha-skin-soft` #e6d8c6, `--isha-line`, `--isha-white`; chữ `--font-heading` (Montserrat), `--font-body` (Garamond Premier Pro); bố cục `--container` 1160px, `--container-narrow` 760px, `--radius` 2px, `--header-h` 84px. Đây là nguồn màu duy nhất.
- Font tự host trong `assets/fonts` dạng OTF/TTF: Montserrat 400/500/600 và Garamond Premier Pro bản Display 400, 400 italic, 500, 600. Đã kiểm tra đủ ký tự tiếng Việt.
- Breakpoint đang dùng: 1024, 900 (menu thu gọn), 782 (admin bar), 680px.
- `header.php`: header dính, nền Shadow, logo qua `isha_logo()`, menu `primary`, nút “Đặt lịch” tới `isha_booking_url()`; ẩn menu và nút khi `isha_landing_only()`.
- `front-page.php`: khi bật chế độ landing (`isha_landing_only()`, mặc định bật) chỉ in `template-parts/landing-policy`; khi tắt thì in hero, giới thiệu, `[isha_services]`, CTA và 3 bài `post` mới nhất.
- Dữ liệu dùng chung: `isha_mod()` đọc Customizer (hero, giới thiệu, CTA, footer); `isha_opt( $key )` đọc option `isha_core_settings` của ISHA Core (phone, email, address, hours, facebook, instagram, tiktok, youtube, zalo).
- `isha_is_inner_page()` dùng `is_page()`, nên đang coi trang chủ tĩnh là trang con: nạp thừa `page.css` và thêm class `page` vào `<main>`. Cần sửa để loại trừ `is_front_page()`.
- `isha_body_classes()` thêm `has-hero` trên trang chủ nhưng chưa có CSS nào dùng.
- `main.css` có `html { scroll-behavior: smooth; }` và một khối `prefers-reduced-motion` tắt mọi transition và animation.
- Trang chi tiết Chính sách (`single-isha_policy.php`, `template-parts/policy-screens.php`, `assets/css/policy-single.css`, `assets/js/policy-single.js`) có engine chuyển động tự viết, không thư viện. Đây là tài liệu tham khảo về cơ chế; mục Ngôn ngữ chuyển động quy định phần nào được dùng lại, phần nào bị cấm lặp lại.

**Plugin ISHA Core** (`wp-content/plugins/isha-core`), suy ra từ cách theme gọi:

- CPT `isha_service` và taxonomy `isha_service_cat`; trường đọc qua `ISHA_Core_Service_Fields::get( $id )` gồm `duration`, `short_desc`, `gallery` (mảng ID ảnh), `steps` (mảng chuỗi).
- CPT `isha_policy`, `ISHA_Core_Policy_Fields::get()`, `ISHA_Core_Shortcodes::policy_cards()` và `::lp_waves()`.
- Shortcode `[isha_services]`, `[isha_policies]`, `[isha_booking_form]`, `[isha_contact_info]`, `[isha_opening_hours]`, `[isha_map]`. Shortcode mới của trang chủ dùng tiền tố `isha_home_` để không trùng.
- Trang đặt lịch nhận tham số `?service=<ID>`.

**Không được phá**

- Trang Chính sách và các file `policy-single.*`, `policy-screens.php`: không sửa, không import.
- Chế độ landing: giữ nguyên hành vi khi đang bật.
- Các trang, shortcode, Customizer hiện có và các class `isha-lp-*` dành cho Elementor.
- Không thêm thư viện JS hay CSS (không GSAP, Lenis, Swiper, jQuery mới), không thêm bước build (npm, bundler).

## Giai đoạn 0: khảo sát bắt buộc trước khi viết code

Không viết dòng code nào cho tới khi xong giai đoạn này và được xác nhận.

1. Đọc toàn bộ theme `ISHA` (nhất là các file ở mục Bối cảnh) và toàn bộ plugin `isha-core`: cách đăng ký CPT và taxonomy, các class Fields và cách lưu meta (tên khóa, sanitize, nonce), UI chọn ảnh, gallery, video trong admin, trang cài đặt, shortcode, hàm `lp_waves`.
2. Đọc kỹ `policy-single.js` và `policy-single.css` để nắm cơ chế: ghim bằng sticky, tiến độ `p`, hàm `seg()`, làm mượt theo hàm mũ, vòng rAF chỉ chạy khi cần, bộ tween, nhánh giảm chuyển động, chốt an toàn khi JS lỗi, vào trang bằng liên kết #.
3. Kiểm tra môi trường: phiên bản WordPress và PHP, plugin đang bật (Elementor, Yoast…), có WP-CLI không, trang nào đang là trang chủ (`page_on_front`), chế độ landing đang bật hay tắt, Elementor đã tắt cho trang chủ chưa.
4. Gửi lại một báo cáo ngắn gồm:
   - Quy ước tìm thấy (đặt tên, cấu trúc file, cách lưu meta) và chỗ nào khác với prompt này.
   - Danh sách file sẽ thêm hoặc sửa, theo cấu trúc ở mục Kiến trúc.
   - Rủi ro và câu hỏi còn mở.
5. Dừng lại, chờ xác nhận.

Khi prompt mâu thuẫn với quy ước thực tế của plugin, ưu tiên quy ước của plugin và ghi rõ trong báo cáo.

## Định hướng thiết kế: sang trọng, quý phái, điện ảnh

Trang chủ kể “Một ngày của ánh sáng”: mở bằng bình minh, đi qua ban ngày và giờ vàng, khép lại trong đêm. Bảng màu thương hiệu chính là các giờ trong ngày: Shadow là đêm, Daylight là ngày, Skin và Gold là buổi chiều và giờ vàng. Biểu tượng Light Gloom (vòng tròn với các tia sáng) đóng vai ống kính iris: mở ra ở hero, khép lại ở cảnh kết.

Điện ảnh ở đây là nhịp chậm, ánh sáng, khung hình và chuyển động máy quay, không phải dồn hiệu ứng. Phần táo bạo nhất dành cho intro và hero; mọi thứ xung quanh giữ yên tĩnh, kỷ luật.

**Nguyên tắc quý phái**

- Mỗi khung hình chỉ một ý; khoảng trắng rộng là chất liệu chính, không phải chỗ trống cần lấp.
- Chữ là nhân vật: Garamond cỡ lớn cho những câu cần cảm xúc; Montserrat nhỏ, chữ hoa giãn rộng cho tiêu đề section theo bộ nhận diện. Không thêm nhãn nhỏ phía trên mọi tiêu đề.
- Gold là điểm nhấn hiếm (đường kẻ, chi tiết nhỏ, nút chính), không phủ diện rộng.
- Đường nét mảnh 1px lấy từ Liner pattern; bo góc theo `--radius` (2px); không đổ bóng đậm, không gradient trang trí, trừ ánh sáng mặt trời và vệt sáng.
- Ảnh đen trắng hoặc tông da ấm, có hạt nhẹ như ảnh trong deck; không ảnh stock bóng bẩy, không icon minh họa.
- Không dùng: huy hiệu giảm giá, đồng hồ đếm ngược, pop-up, số liệu kiểu “1000+ khách hàng”, sao đánh giá, emoji, mũi tên “→” gắn sau chữ, chuỗi thông tin nối bằng dấu chấm giữa, nhấn một chữ trong tiêu đề bằng màu khác hay in nghiêng.

**Trải nghiệm cho quý bà**

- Dễ đọc trước, đẹp sau: chữ thân tối thiểu 19px trên desktop và 18px trên điện thoại, dòng 60–72 ký tự, tương phản đạt WCAG AA và ưu tiên cao hơn mức tối thiểu.
- Nút và vùng chạm tối thiểu 48 × 48px; nút luôn có chữ, không chỉ có icon.
- Không có thông tin chỉ hiện khi rê chuột; mọi hàng vuốt ngang đều có nút mũi tên và chỉ báo vị trí.
- Dùng con trỏ chuột mặc định, không con trỏ tùy biến.
- Nhịp chậm và êm: không có gì nháy, rung, nảy; video không có tiếng.
- Kín đáo: không pop-up, không khung chat tự bật, không thu thập thông tin ngoài trang đặt lịch.

**Giọng văn**

- Câu ngắn, điềm tĩnh, tôn trọng; không tiếng lóng, không dấu chấm than, không ngôn ngữ khuyến mãi.
- Tránh đại từ “bạn” trong tiêu đề; ưu tiên câu trung tính và dùng “Quý khách” ở văn bản giao dịch. Cách xưng hô cuối cùng chờ khách duyệt.
- Nút nói đúng việc sẽ xảy ra: “Đặt lịch tư vấn riêng”, “Nhắn Zalo”, “Gọi hotline”, “Xem liệu trình”.

**Skill thiết kế.** Nếu môi trường của bạn có các skill Emil Kowalski, Impeccable hoặc Taste, hãy đọc và áp dụng; khi có mâu thuẫn, prompt này thắng.

**Tự phê bình trước mỗi điểm duyệt.** Nếu có công cụ trình duyệt, chụp màn hình ở 1440 × 900 và 390 × 844, đối chiếu với các nguyên tắc trên. Theo lời khuyên của Chanel: nhìn lại một lần nữa và bỏ đi một chi tiết trang trí.

## Token mở rộng và typography

Chỉ thêm token mới vào `:root` trong `assets/css/main.css`, không sửa token cũ; CSS trang chủ chỉ dùng token, không viết mã màu trực tiếp.

```css
:root {
  /* Tông theo giờ trong ngày, dẫn xuất từ token gốc; ưu tiên color-mix, hex là fallback */
  --isha-dawn: #84796a;   /* color-mix(in oklab, var(--isha-skin) 55%, var(--isha-shadow)) */
  --isha-golden: #ccb498; /* color-mix(in oklab, var(--isha-skin) 82%, var(--isha-gold)) */
  --isha-dusk: #3e3222;   /* color-mix(in oklab, var(--isha-gold) 30%, var(--isha-shadow)) */

  /* Chuyển động */
  --isha-ease-out: cubic-bezier(0.23, 1, 0.32, 1);
  --isha-ease-in-out: cubic-bezier(0.77, 0, 0.175, 1);
  --isha-ease-cine: cubic-bezier(0.22, 1, 0.36, 1);
  --isha-dur-press: 120ms;
  --isha-dur-ui: 200ms;
  --isha-dur-ui-lg: 300ms;
  --isha-dur-cine: 1100ms;

  /* Chữ: thang H của bộ nhận diện, H = chữ thân */
  --isha-fs-label: 0.8125rem;
  --isha-fs-body: 1.1875rem;
  --isha-fs-note: 1.0625rem;
  --isha-fs-sub: clamp(1.125rem, 0.95rem + 0.6vw, 1.5rem);
  --isha-fs-header: clamp(1.5rem, 1.05rem + 1.5vw, 2.375rem);
  --isha-fs-accent: clamp(2.5rem, 1.3rem + 4vw, 4.75rem);
  --isha-fs-display: clamp(3.25rem, 1.2rem + 7.5vw, 8.5rem);

  /* Khoảng cách, chiều cao, lớp */
  --isha-section-y: clamp(96px, 14vh, 200px);
  --isha-vh: 100vh;       /* ghi đè bằng 100svh trong @supports */
  --isha-z-dock: 90;
  --isha-z-header: 100;   /* trùng z-index header hiện có */
  --isha-z-intro: 1000;
}
```

| Vai trò trong deck | Token | Font | Dùng cho |
| --- | --- | --- | --- |
| Display (mở rộng cho điện ảnh) | `--isha-fs-display` | Garamond 500 | Tiêu đề hero, chữ I·S·H·A |
| Accent 4H | `--isha-fs-accent` | Garamond 500 | Câu cảm xúc, tên nhóm dịch vụ |
| Header 2H | `--isha-fs-header` | Montserrat 600, chữ hoa, giãn 0.06em | Tiêu đề section |
| Sub header 1.25H | `--isha-fs-sub` | Montserrat 500 | Tiêu đề phụ, tên liệu trình |
| Section H | `--isha-fs-label` | Montserrat 600, chữ hoa, giãn 0.24em | Nhãn mang thông tin: loại sự kiện, ngày |
| Body H | `--isha-fs-body` | Garamond 400 | Đoạn văn |
| Note H | `--isha-fs-note` | Garamond 400 italic | Chú thích, chữ ký |

Montserrat trông to hơn Garamond ở cùng cỡ chữ, nên nhãn Montserrat đặt nhỏ hơn chữ thân để cân thị giác.

**Typography tiếng Việt**

- Garamond cỡ hiển thị dùng line-height 1.08–1.15; Montserrat chữ hoa nhiều dòng dùng line-height từ 1.35 vì có dấu chồng (Ẫ, Ậ, Ữ).
- Mọi mặt nạ bọc chữ (overflow hidden, clip-path, mask) phải chừa `padding-block: 0.2em` hoặc nới vùng cắt, để không cắt dấu phía trên chữ hoa và dấu nặng phía dưới.
- Không dùng `text-transform: uppercase` cho Garamond; dữ liệu nhập ở dạng chữ thường có dấu, CSS lo chữ hoa cho Montserrat.
- Tiêu đề dùng `text-wrap: balance`, đoạn văn dùng `text-wrap: pretty` (tăng cường dần).
- Theme chỉ có Garamond bản Display, nét mảnh ở cỡ nhỏ: giữ chữ thân từ 19px, không dùng Garamond Light ở cỡ nhỏ.
- Chữ đặt trên video hoặc ảnh luôn có lớp phủ tối, bảo đảm tương phản từ 4.5:1 cho chữ thường.

**Quy tắc màu chữ (tương phản đã tính theo WCAG)**

- Trên Daylight: chữ nhỏ dùng Shadow hoặc `--isha-gold-ink` (5.9:1); Gold thuần chỉ cho chữ từ 24px hoặc đường kẻ (4.1:1).
- Trên Skin và golden: chữ nhỏ dùng Shadow (khoảng 10:1); không dùng gold-ink cho chữ nhỏ (4.35:1, không đạt).
- Trên Shadow và dusk: chữ nhỏ dùng Daylight hoặc Skin; Gold chỉ cho chữ từ 24px hoặc đường kẻ (3.4:1).
- Nút chính nền Gold, chữ trắng: 5:1, đạt.

## Ngôn ngữ chuyển động

Mỗi khung lớn có đúng một chữ ký chuyển động riêng; không section nào lặp lại chữ ký của section khác hay hình thức của trang Chính sách.

### Lõi `assets/js/isha-motion.js`

Viết mới một lõi dùng chung, học cơ chế từ `policy-single.js` nhưng không sửa hay import file đó.

- Không thư viện, không build; IIFE và `'use strict'`, phong cách code giống `policy-single.js`; đặt trong namespace `window.ISHA.motion`.
- Một vòng requestAnimationFrame duy nhất cho mọi cảnh, chỉ chạy khi còn việc (cảnh đang đuổi theo vị trí cuộn, tween đang chạy); dừng khi tab ẩn.
- Cảnh ghim: section có `track` cao hơn màn hình và `stage` dùng `position: sticky`. Tiến độ `p` (0 → 1) đo bằng `getBoundingClientRect`, làm mượt theo `p += (target − p) × (1 − e^(−dt/τ))` với τ = 0,18 giây, giữ đúng cảm giác quán tính của trang Chính sách.
- Cảnh đi ngang (không ghim): `p` là tiến độ section đi qua màn hình, cùng cơ chế làm mượt.
- Tiện ích: `clamp`, `lerp`, `seg( p, a, b )`, các hàm ease (power1–3 out, inOut, cine), `tween()` theo thời gian, `inView()` dùng IntersectionObserver cho tầng nhẹ.
- Hàm vẽ của mỗi cảnh chỉ ghi transform, opacity, clip-path, mask-position hoặc stroke-dashoffset, và bỏ qua khi trạng thái không đổi.
- Đo lại khi resize và qua ResizeObserver; khởi tạo lại khi chuyển giữa chế độ đầy đủ và chế độ gọn (matchMedia).
- Tăng cường dần: một script nhỏ trong `<head>` (chỉ trang chủ) thêm class `isha-motion-on` lên `<html>`. CSS chỉ ẩn trạng thái ban đầu khi có class này; sau 6 giây nếu lõi chưa khởi động thì thêm `isha-motion-fail` và hiện toàn bộ nội dung.
- Vào trang bằng liên kết `#section`: bỏ intro, đặt mọi cảnh ở trạng thái đúng với vị trí cuộn.

API gợi ý; có thể tinh chỉnh nhưng giữ một điểm vào duy nhất:

```js
ISHA.motion.scene( rootEl, {
  pin: true,          // false = cảnh đi ngang
  length: 1.5,        // quãng cuộn thêm khi ghim, tính theo chiều cao màn hình
  smooth: 0.18,
  compact: 'static',  // cách chạy ở chế độ gọn: 'static' | 'same' | hàm riêng
  update: function ( p, state ) {},
  enter: function () {},
  leave: function () {}
} );
ISHA.motion.tween( { delay: 0, duration: 1.1, from: 0, to: 1, ease: 'cine', update: fn, done: fn } );
ISHA.motion.inView( el, fn, { once: true, threshold: 0.35 } );
ISHA.motion.reduced(); // prefers-reduced-motion: reduce
ISHA.motion.compact(); // (max-width: 900px), (max-height: 560px)
```

### Hai tầng chuyển động

|  | Tầng điện ảnh | Tầng nhẹ |
| --- | --- | --- |
| Áp dụng | Khung lớn chiếm gần trọn màn hình: intro, hero, câu chuyện, triết lý, dịch vụ, không gian, tầm nhìn, cảnh kết | Thành phần: giá trị, quy trình, carousel sự kiện, bài viết, testimonials, đối tác, nút, menu, dock |
| Kích hoạt | Theo cuộn (có làm mượt) hoặc một lần theo thời gian | Theo hành động người dùng hoặc khi vào khung nhìn |
| Thời lượng | 0,9–1,6 giây khi chạy theo thời gian | 120–300ms |
| Easing | `--isha-ease-cine` | `--isha-ease-out` khi vào; khi ra nhanh hơn khoảng 25% |
| Thuộc tính | transform, opacity, clip-path, mask, stroke-dashoffset | transform, opacity |
| Từ vựng | Máy quay: dolly (tiến, lùi), truck (trượt ngang), tilt, iris; ánh sáng | Mờ dần, dịch 8–12px, scale 0.97 → 1 |

Quy tắc tầng nhẹ, theo tinh thần Emil Kowalski:

- Không bao giờ scale từ 0; bắt đầu từ 0.96–0.98 kèm opacity.
- Không bounce, không elastic; thay đổi trạng thái dùng transition CSS để ngắt giữa chừng được, không dùng keyframes.
- Hiệu ứng hover chỉ trong `@media (hover: hover) and (pointer: fine)`; nút khi nhấn `scale(0.98)` trong `--isha-dur-press`.
- Stagger tối đa 40ms mỗi phần tử, tối đa 6 phần tử.
- Thao tác lặp lại (mở menu, chuyển slide bằng phím) phải tức thì, không bắt người dùng chờ.

### Chữ ký riêng của từng section

| Section | Tầng | Chữ ký (chỉ section này dùng) | Chế độ gọn |
| --- | --- | --- | --- |
| Intro | Điện ảnh | Mặt trời mọc: vòng tròn nhô lên giữa màn đêm, tia Light Gloom vẽ ra từ tâm, câu intro hiện dưới | Như desktop, rút ngắn |
| Hero | Điện ảnh | Iris mở: vòng tròn chứa video nở ra phủ kín màn hình, tia sáng bay ra ngoài, máy quay lùi (video scale 1.12 → 1) | Iris mở theo thời gian, không ghim |
| Câu chuyện | Điện ảnh | Vệt sáng quét qua chữ: mask gradient chạy theo cuộn, chữ sáng dần từ trái sang phải | Quét một lần khi vào khung |
| Triết lý | Điện ảnh | Bốn chữ I·S·H·A lần lượt “đầy nắng” từ chân chữ lên; máy quay trượt để chữ đang kể vào vị trí chính | Hàng vuốt ngang 4 khung |
| Dịch vụ | Điện ảnh | Truck shot qua 6 khung; ảnh mỗi khung mở ra qua các lá rèm dọc của Liner pattern | Hàng vuốt ngang 6 khung, rèm mở đơn giản |
| Giá trị | Nhẹ | Dải phim: 5 khung dọc trượt vào như cuộn phim được kéo; khung được chọn sáng lên và hiện mô tả | Hàng vuốt ngang |
| Quy trình | Nhẹ | Một đường kẻ mảnh chạy ngang theo cuộn, 5 mốc sáng lần lượt | Hàng vuốt ngang, đường kẻ chạy theo vị trí vuốt |
| Không gian | Điện ảnh | Lia máy khi đi ngang: dải ảnh trôi ngang theo cuộn, không ghim; chú thích trôi nhanh hơn ảnh tạo chiều sâu | Hàng vuốt ngang |
| Sự kiện | Nhẹ | Khung màn ảnh rộng 2.39:1; đổi slide bằng crossfade; thanh tiến trình là các vạch dọc | Khung 4:5, vuốt |
| Bài viết | Nhẹ | Bố cục tạp chí; khi rê chuột, đường gạch mảnh chạy dưới tiêu đề và ảnh phóng 1.03 | Hàng vuốt ngang |
| Testimonials | Nhẹ | Phụ đề: từng dòng lời kể hiện như phụ đề phim, tên khách là dòng credit | Vuốt |
| Tầm nhìn | Điện ảnh | Chữ ký tay của bác sĩ tự viết; khung hình tiến chậm khi vào giữa màn hình | Như desktop |
| Cảnh kết | Điện ảnh | Iris khép: khung hình thu về vòng tròn quanh logo, đối xứng với hero, không có tia sáng | Iris khép theo thời gian khi vào khung |
| Đối tác | Nhẹ | Credits: logo hiện lần lượt bằng opacity, đứng yên, không chạy marquee | Lưới 2 cột |

### Được dùng lại và bị cấm từ trang Chính sách

- **Được dùng lại (hành vi):** cơ chế ghim sticky, tiến độ và `seg()`, làm mượt τ = 0,18 giây, hành vi máy quay (dolly, trượt), đường cong `cubic-bezier(.22, 1, .36, 1)`, cách xử lý giảm chuyển động, không JS, chốt an toàn và vào trang bằng liên kết #.
- **Bị cấm (hình thức):** thẻ kính và `backdrop-filter`, cổng ánh sáng phủ kín màn hình, chữ hiện từng từ bằng `filter: blur`, preloader chữ khổng lồ mờ kèm bộ đếm phần trăm, con trỏ kính hay bất kỳ con trỏ tùy biến nào, cảnh sóng `lp_waves` làm nền, các màu `#d8b886` và `#b88d58`.

### Giảm chuyển động và không có JS

- `prefers-reduced-motion: reduce`: không intro, không ghim, không chuyển động theo cuộn, không tự chạy video hay carousel (video hiện ảnh poster kèm nút phát); chỉ cho phép opacity trong tối đa 200ms.
- Không có JS hoặc có `isha-motion-fail`: mọi section hiển thị tĩnh, đủ nội dung, đúng thứ tự; hàng vuốt ngang vẫn cuộn được bằng CSS.

## Kiến trúc kỹ thuật

Dữ liệu (CPT, trường, cách lưu meta) nằm trong ISHA Core; shortcode, giao diện và chuyển động nằm trong theme. Trang chủ vẫn là trang “Trang chủ” hiện có (đang rỗng, đã tắt Elementor); không tạo trang mới.

### File thêm và sửa trong theme

| File | Việc |
| --- | --- |
| `front-page.php` | Sửa: định tuyến giữa landing và trang chủ mới |
| `inc/home/registry.php` | Danh sách 13 section theo thứ tự: id, tag shortcode, template part, tông màu |
| `inc/home/data.php` | `isha_home_defaults()`, `isha_home_data()` |
| `inc/home/shortcodes.php` | Đăng ký 13 shortcode `isha_home_*` |
| `inc/home/assets.php` | Nạp CSS và JS trang chủ, preload poster, script gate trong `<head>` |
| `inc/home/helpers.php` | Hàm in ảnh responsive, video, SVG logo chữ, nút liên hệ |
| `template-parts/home/*.php` | `intro.php`, `dock.php` và 13 file section |
| `assets/css/home.css` | Toàn bộ CSS trang chủ, chia khối theo thứ tự section |
| `assets/js/isha-motion.js` | Lõi chuyển động dùng chung |
| `assets/js/home.js` | Các cảnh của trang chủ, mỗi section một module đăng ký vào lõi |
| `assets/images/isha-wordmark.svg` | Logo chữ, mỗi chữ cái một path riêng (placeholder nếu chưa có file) |
| `single-isha_service.php`, `assets/css/service-single.css`, `assets/js/service-single.js` | Trang chi tiết liệu trình |
| `functions.php` | Thêm các include `inc/home/*` |
| `inc/helpers.php` | Sửa `isha_is_inner_page()` để loại trừ trang chủ; thêm `isha_home_is_preview()`; `isha_landing_only()` trả về false khi đang xem trước |
| `inc/setup.php` | Thêm image size và body class `isha-home` |
| `assets/css/main.css` | Chỉ thêm token vào `:root` |

### Định tuyến và xem trước

```php
// front-page.php
get_header();
if ( isha_landing_only() && ! isha_home_is_preview() ) {
	get_template_part( 'template-parts/landing-policy' ); // giữ nguyên hành vi cũ
} else {
	isha_home_render(); // intro, 13 section theo registry, dock
}
get_footer();
```

- `isha_home_is_preview()` trả về true khi URL có `?isha_preview=home` và người xem có quyền `edit_pages`. Admin xem trước trang chủ mới trong lúc khách vẫn thấy landing; khi ra mắt chỉ cần tắt chế độ landing trong Customizer.
- `isha_home_render()` duyệt registry, bỏ qua section bị tắt trong CMS, gọi `do_shortcode()` cho từng section, bọc tất cả trong khối `isha-film` cùng lớp bầu trời.
- Trên trang chủ mới: body có class `isha-home`; `<main>` không có class `page`.

### Mẫu shortcode

- Mỗi shortcode lấy dữ liệu qua `isha_home_data( $section )` và trả về chuỗi HTML bằng output buffering với `get_template_part( 'template-parts/home/<section>', null, $data )`; không `echo` trực tiếp.
- Section không có dữ liệu trả về chuỗi rỗng: ẩn hẳn, không để khoảng trống.
- Thuộc tính tối thiểu qua `shortcode_atts`: `count` cho events (mặc định 5), articles (4), testimonials (6).
- Mỗi section là một thẻ `section` có `id` bằng id section, class `isha-<id>`, các thuộc tính `data-isha-scene`, `data-tone` và `aria-labelledby` trỏ tới tiêu đề. Chỉ hero có `h1`; các section khác dùng `h2`.
- Class CSS theo BEM của từng section, ví dụ `.isha-hero__iris`, `.isha-services__frame`; mọi selector trang chủ nằm dưới `.isha-home` để không ảnh hưởng trang khác. Chú ý độ ưu tiên selector với `.section`, `.hero`, `.cta` sẵn có trong `main.css`.

### Lớp dữ liệu

- `isha_home_data( $section )` đọc meta `_isha_home_<section>` của trang đang là trang chủ (`page_on_front`), trộn với `isha_home_defaults( $section )` (nội dung ở Phụ lục A), rồi chạy `apply_filters( "isha/home/data/{$section}", $data )`.
- Section dùng CPT tự truy vấn trong hàm dữ liệu của mình, với `no_found_rows` và chỉ lấy trường cần dùng.
- Khi ISHA Core không bật: section dùng CPT ẩn; section dùng trường vẫn hiện bằng nội dung mặc định.

### Nạp tài nguyên

- Chỉ trên trang chủ mới hoặc khi xem trước: `home.css` (phụ thuộc `isha-main`), `isha-motion.js` rồi `home.js`, đều `defer` và ở footer, phiên bản theo `filemtime` như `enqueue.php` hiện có.
- Preload ảnh poster hero với `fetchpriority="high"`, bản dọc và bản ngang theo thuộc tính `media`.
- Không nạp `page.css` trên trang chủ.

## Dữ liệu và CMS tối thiểu

Admin chỉnh toàn bộ nội dung trang chủ ngay trên trang “Trang chủ”; không thêm trang cài đặt hay menu mới ngoài ba CPT. Không cài ACF: ISHA Core đã có UI chọn ảnh và gallery, còn ACF bản miễn phí không có Gallery và Repeater.

### Meta box “Nội dung trang chủ” (ISHA Core)

- Chỉ hiện trên trang đang là trang chủ. Ẩn trình soạn thảo nội dung của trang này (cả block editor) và hiện dòng hướng dẫn: “Trang chủ do theme dựng; chỉnh nội dung ở các thẻ bên dưới.”
- Mỗi section một thẻ (tab), đúng thứ tự trên trang; dòng đầu mỗi thẻ là ô “Hiển thị section này”, mặc định bật.
- Mỗi section lưu vào một meta dạng mảng `_isha_home_<section>`. Ô để trống thì dùng nội dung mặc định; placeholder hiện sẵn nội dung mặc định đó.
- Dùng lại UI chọn ảnh, gallery, video sẵn có của ISHA Core. Nhóm lặp có số lượng cố định (4 triết lý, 5 giá trị, 5 bước) để admin không phải thêm hay xóa dòng.
- Bảo mật: nonce, `current_user_can( 'edit_post', $post_id )`, sanitize theo loại trường (text, textarea, `absint` cho ID, `esc_url_raw` cho link, boolean).

| Thẻ | Trường |
| --- | --- |
| Hero và intro | Bật intro; câu intro; video ngang; video dọc; poster ngang; poster dọc; dòng nhỏ; tiêu đề; mô tả; nhãn nút chính |
| Câu chuyện | Tiêu đề; năm thành lập; đoạn chính; đoạn phụ |
| Triết lý | Tiêu đề; câu dẫn; 4 nhóm gồm chữ cái, tên tiếng Anh, tên tiếng Việt, mô tả |
| Dịch vụ | Tiêu đề; câu dẫn (dữ liệu nhóm lấy từ taxonomy) |
| Giá trị | Tiêu đề; 5 nhóm gồm tên, mô tả, ảnh tùy chọn |
| Quy trình | Tiêu đề; 5 nhóm gồm tên bước, mô tả |
| Không gian | Tiêu đề; câu dẫn; gallery (chú thích lấy từ caption của ảnh) |
| Sự kiện | Tiêu đề; câu dẫn |
| Bài viết | Tiêu đề; nhãn liên kết “Xem tất cả” |
| Testimonials | Tiêu đề |
| Tầm nhìn | Câu trích; đoạn tầm nhìn; chức danh; tên; ảnh chữ ký (SVG) |
| Cảnh kết | Tiêu đề; mô tả; ảnh hoặc video nền tùy chọn |
| Đối tác | Tiêu đề; gallery logo (tên lấy từ alt, link lấy từ trường “Liên kết đối tác” thêm vào ảnh trong thư viện) |

### Nhóm dịch vụ và liệu trình

- 6 nhóm là 6 term của `isha_service_cat`; mô tả của term chính là tagline.
- Thêm term meta: ảnh bìa dọc 4:5, video bìa tùy chọn (vòng lặp ngắn, không tiếng), thứ tự hiển thị.
- Mỗi liệu trình là một bài `isha_service` gán vào nhóm, thứ tự theo `menu_order`, dùng các trường sẵn có (`duration`, `short_desc`, `gallery`, `steps`) và ảnh đại diện.

### CPT mới, theo đúng khuôn của `isha_service` và `isha_policy`

| CPT | Hiển thị | Trường | Taxonomy |
| --- | --- | --- | --- |
| `isha_event` (Sự kiện) | Public, chưa cần trang lưu trữ | Tiêu đề, mô tả ngắn (excerpt), ảnh đại diện 2.39:1; ngày giờ bắt đầu, kết thúc (tùy chọn), địa điểm, link đăng ký, cờ nổi bật, ảnh dọc cho điện thoại | `isha_event_type`: Workshop, Design Talk, Charity |
| `isha_testimonial` (Lời kể khách hàng) | Không public, chỉ trong quản trị | Tên hiển thị (tiêu đề), lời kể tối đa 300 ký tự, liệu trình đã dùng (chọn bài `isha_service`), ảnh chân dung tùy chọn, ô “Khách đã đồng ý công khai” | Không |
| `isha_article` (Bài viết) | Public, có trang lưu trữ, slug `bai-viet` | Tiêu đề, nội dung, excerpt, ảnh đại diện; tiêu đề phụ, thời gian đọc (tự tính khi lưu), cờ nổi bật | `isha_article_cat`, phân cấp, slug `chu-de` |

- Mỗi CPT có class Fields riêng (`ISHA_Core_Event_Fields`, `ISHA_Core_Testimonial_Fields`, `ISHA_Core_Article_Fields`) với hàm `get( $id )` như các class hiện có.
- Testimonial chỉ hiện khi đã có đồng ý công khai; không dùng ảnh trước và sau.
- Flush rewrite khi kích hoạt plugin hoặc khi thêm CPT.

### Dữ liệu dịch vụ khởi tạo

- Viết lệnh WP-CLI `wp isha seed services`, chạy nhiều lần không tạo trùng: tạo 6 nhóm kèm tagline và thứ tự, cùng các liệu trình ở trạng thái nháp theo Phụ lục A.
- Nếu môi trường không có WP-CLI, thêm nút “Tạo dữ liệu dịch vụ từ leaflet” trong thông báo admin, chỉ hiện với quản trị viên khi chưa có nhóm nào.
- Không tạo dữ liệu giả cho sự kiện, testimonials, bài viết hay đối tác.

## Thành phần toàn trang

Bốn thành phần này đi xuyên suốt trang chủ và chỉ tồn tại dưới `.isha-home`.

### Intro

Intro là cảnh mở đầu, chạy trước hero và nằm trong shortcode hero (`template-parts/home/intro.php`).

- Chạy một lần mỗi phiên (`sessionStorage`), tối đa 2,8 giây; bấm, chạm, nhấn Esc hoặc cuộn đều bỏ qua ngay.
- Diễn biến: màn Shadow → vòng tròn mặt trời nhô lên giữa màn hình → 48–64 tia Light Gloom vẽ ra từ tâm (stroke-dashoffset, so le, dài ngắn khác nhau và có khoảng đứt như bìa deck) → câu intro bằng Garamond italic hiện dưới vòng tròn → vòng tròn trở thành khung iris của hero.
- Chờ video hero đủ dữ liệu để phát (`canplay`), tối đa 2,8 giây rồi chuyển cảnh; không khóa cuộn lâu hơn thời gian này.
- Không chạy khi: bật giảm chuyển động, đã xem trong phiên, vào trang bằng liên kết #, admin tắt intro.
- Toàn bộ intro có `aria-hidden="true"`; nội dung trang có trong DOM ngay từ đầu, tiêu điểm bàn phím không bị giữ lại.

### Header trên trang chủ

- Header `position: fixed`, nền trong suốt, chữ Daylight khi nằm trên hero; sau hero chuyển sang nền Shadow (tầng nhẹ, 300ms).
- Ẩn khi cuộn xuống, hiện lại khi cuộn lên (translateY, `--isha-ease-out`); luôn hiện khi bàn phím đang focus trong header.
- Không đổi markup `header.php` ngoài những gì bắt buộc; giữ đúng hành vi ở các trang khác và khi bật chế độ landing; giữ cách tính offset admin bar như CSS hiện có.

### Dock liên hệ

- Ba hành động: “Đặt lịch tư vấn riêng” (nút chính nền Gold, tới `isha_booking_url()`), “Nhắn Zalo” (`isha_opt( 'zalo' )`), “Gọi hotline” (liên kết `tel:` từ `isha_opt( 'phone' )`). Ẩn nút nào không có dữ liệu.
- Desktop: cụm nhỏ ở góc dưới bên phải. Điện thoại: thanh dưới cùng, ba nút chia đều, luôn có chữ, cao 64px, chừa `env(safe-area-inset-bottom)`.
- Ẩn trong intro và hero; hiện khi đã qua 80% hero; ẩn khi cảnh kết hiện từ 40% trở lên để không trùng nút.
- Tầng nhẹ: opacity và translateY 12px trong 240ms.

### Lớp bầu trời

- Một lớp `position: fixed` nằm sau các section. Mỗi section khai báo `data-tone`; JS nội suy màu nền giữa hai section liền kề theo vị trí cuộn.
- Màu chữ của mỗi section cố định theo tông của chính nó; phần chuyển màu nằm trong khoảng đệm giữa hai section, nơi không có chữ.
- Không JS hoặc giảm chuyển động: mỗi section tự tô nền theo tông của mình.

| Section | Tông nền |
| --- | --- |
| Hero | Đêm sang bình minh, do cảnh hero tự điều khiển |
| Câu chuyện, Triết lý | Ngày: `--isha-daylight` |
| Dịch vụ, Giá trị | Ngày ấm: `--isha-skin-soft` |
| Quy trình | Chiều: `--isha-skin` |
| Không gian, Sự kiện | Giờ vàng: `--isha-golden` |
| Bài viết | Chiều muộn: `--isha-skin-soft` |
| Testimonials | Chạng vạng: `--isha-dusk` |
| Tầm nhìn, Cảnh kết, Đối tác | Đêm: `--isha-shadow`, nối liền với footer |

## Đặc tả 13 section

Thứ tự theo logic landing: thương hiệu (1–3), sản phẩm (4), lý do tin tưởng và quy trình (5–6), trải nghiệm (7), cộng đồng và nội dung (8–9), bằng chứng (10), cảm xúc (11), hành động (12), uy tín (13). Tông nền theo bảng ở mục Thành phần toàn trang; chữ ký chuyển động theo bảng ở mục Ngôn ngữ chuyển động.

### 1. Hero `[isha_home_hero]`

- **Mục đích:** khoảnh khắc đáng nhớ nhất của trang; giới thiệu thương hiệu và hành động chính.
- **Bố cục:** khung 100svh nền đêm. Vòng tròn mặt trời khoảng 40vmin chứa video, xung quanh là tia Light Gloom (SVG). Logo chữ ISHA, dòng nhỏ, tiêu đề `h1` (Garamond display), mô tả (Garamond italic), nút “Đặt lịch tư vấn riêng”, gợi ý cuộn bằng một vạch dọc mảnh.
- **Dữ liệu:** thẻ Hero (video ngang, video dọc, poster ngang, poster dọc, dòng nhỏ, tiêu đề, mô tả, nhãn nút); link nút từ `isha_booking_url()`.
- **Chuyển động:** cảnh ghim, length 1.4. Từ p 0 tới 0.6: `clip-path: circle()` mở từ bán kính vòng tròn tới phủ kín đường chéo màn hình; tia sáng kéo dài và mờ dần; video scale 1.12 → 1; chữ tilt lên và mờ dần sau p 0.35. Từ p 0.6 tới 1: giữ khung video toàn màn hình rồi nhả ghim.
- **Video:** `muted`, `playsinline`, `loop`, chỉ phát sau intro. JS chọn nguồn dọc hoặc ngang theo matchMedia trước khi gán `src`. Khi `saveData` hoặc giảm chuyển động thì chỉ hiện poster. Tạm dừng khi ra khỏi khung nhìn hoặc tab ẩn. Luôn có nút “Tạm dừng video” / “Phát video” (WCAG 2.2.2).
- **Chế độ gọn:** không ghim; iris mở theo thời gian trong 1,2 giây sau intro; khi cuộn chỉ có tilt nhẹ.
- **Thiếu dữ liệu:** không có video thì dùng poster kèm dolly chậm; không có poster thì vòng tròn chỉ có ánh sáng Gold mờ.

### 2. Câu chuyện `[isha_home_story]`

- **Mục đích:** ISHA là ai: thành lập năm 2024, triết lý tôn vinh nét đẹp tự nhiên, lời cam kết về chuẩn mực y khoa.
- **Bố cục:** bất đối xứng kiểu tạp chí. Cột trái hẹp: tiêu đề section và năm 2024 bằng Garamond accent. Cột phải: đoạn chính cỡ khoảng 2.4–3rem, đoạn phụ cỡ chữ thân, tối đa 62 ký tự mỗi dòng.
- **Chuyển động:** vệt sáng quét qua đoạn chính bằng `mask-image` gradient có vùng sáng rộng khoảng 15%, vị trí mask chạy theo p khi section đi qua màn hình. Chữ chưa được quét ở opacity 0.22 và quét xong khi đỉnh section chạm 35% chiều cao màn hình, nên chữ đã sáng hẳn khi người đọc tới giữa khung.
- **Chế độ gọn:** quét một lần trong 1,4 giây khi section vào 40% khung nhìn.

### 3. Triết lý `[isha_home_philosophy]`

- **Mục đích:** bốn chữ cái của tên thương hiệu là bốn cam kết: Inner Beauty, Sustainable, Holistic, Aesthetic.
- **Bố cục:** logo chữ ISHA khổng lồ (khoảng 70vw) ở giữa. Chữ đang được kể có màu Gold; ba chữ còn lại là Shadow ở 12% opacity. Bên cạnh là tên tiếng Anh (Montserrat chữ hoa), tên tiếng Việt (Garamond italic) và mô tả 1–2 câu. Mép khung có chỉ báo nhỏ bốn chữ I S H A.
- **Dữ liệu:** thẻ Triết lý. Inline `assets/images/isha-wordmark.svg`, với các path có `id` là `isha-i`, `isha-s`, `isha-h`, `isha-a`; chỉ đọc file trong theme. Thiếu file thì dựng chữ bằng Garamond Display.
- **Chuyển động:** cảnh ghim, length 2.4, p chia 4 đoạn. Mỗi chữ “đầy nắng” từ chân lên bằng một `rect` trong `clipPath` (Gold phủ dần). Cả logo trượt ngang để chữ đang kể nằm ở khoảng 38% bề ngang. Phần chữ mô tả đổi bằng crossfade tầng nhẹ.
- **Chế độ gọn:** hàng vuốt ngang 4 khung, mỗi khung một chữ cái lớn kèm nội dung; chữ “đầy nắng” một lần khi khung vào giữa.
- **A11y:** nội dung bốn nhóm là danh sách `<ol>` đọc được theo thứ tự; SVG logo có `aria-hidden`.

### 4. Dịch vụ `[isha_home_services]`

- **Mục đích:** trình bày sáu nhóm dịch vụ và dẫn tới trang chi tiết từng liệu trình.
- **Bố cục:** mỗi khung rộng 100vw, cao 100svh. Ảnh bìa dọc 4:5 chiếm khoảng 55%, xen kẽ trái và phải giữa các khung. Tên nhóm (Garamond accent), tagline (Garamond italic), danh sách liệu trình (Montserrat, mỗi dòng một liên kết, phân cách bằng đường kẻ mảnh), liên kết “Đặt lịch tư vấn riêng”. Chỉ báo vị trí là 6 vạch dọc mảnh ở chân; vạch của khung hiện tại cao hơn và có màu Gold. Không dùng số kiểu “01 / 06”.
- **Dữ liệu:** term `isha_service_cat` theo thứ tự trong term meta; liệu trình đã xuất bản của từng term (khi xem trước thì kèm bài nháp có nhãn “Nháp”); link liệu trình tới trang chi tiết.
- **Chuyển động:** cảnh ghim; track trượt ngang bằng `translateX` theo p (truck shot), mỗi khung tốn quãng cuộn bằng 0,8 chiều cao màn hình. Ảnh của khung đang vào mở qua 10–12 lá rèm dọc (Liner pattern), so le 40ms; bên trong khung, ảnh trượt chậm ngược chiều tạo parallax nhẹ.
- **Bàn phím:** khi một liên kết trong khung đang khuất nhận focus, cuộn trang tới vị trí làm khung đó hiện ra.
- **Chế độ gọn:** hàng `scroll-snap` ngang, mỗi khung rộng 88vw để thấy mép khung sau; có nút mũi tên và vạch chỉ báo; rèm mở đơn giản (opacity và inset) khi khung vào giữa.
- **Rỗng:** không có term thì ẩn section; term chưa có liệu trình thì chỉ hiện tagline và nút đặt lịch.

### 5. Giá trị cốt lõi `[isha_home_values]`

- **Mục đích:** năm cam kết tạo niềm tin.
- **Bố cục:** dải phim gồm 5 khung dọc bằng nhau, cao khoảng 60svh, xếp ngang. Mỗi khung có tên giá trị (cỡ sub) và ảnh đen trắng tùy chọn mờ phía sau. Khung được chọn (rê chuột, focus hoặc chạm) có ảnh sáng lên từ 0.35 tới 0.6 opacity, mô tả hiện ra và một đường kẻ Gold mảnh ở đỉnh khung; mặc định chọn khung đầu.
- **Chuyển động:** tầng nhẹ. Khi vào khung nhìn, 5 khung trượt vào từ phải như cuộn phim (translateX, stagger 40ms); mô tả hiện bằng opacity và translateY 8px. Không animate chiều rộng.
- **A11y:** mỗi khung là `button` có `aria-expanded`; mô tả luôn có trong DOM.
- **Chế độ gọn:** hàng vuốt ngang, mỗi khung hiện đủ tên và mô tả, không cần chọn.

### 6. Quy trình `[isha_home_process]`

- **Mục đích:** trấn an bằng quy trình thăm khám da 5 bước rõ ràng.
- **Bố cục:** một đường kẻ ngang mảnh chạy hết bề ngang container, có 5 mốc. Dưới mỗi mốc là số thứ tự (Montserrat; dùng số vì đây là trình tự), tên bước và mô tả ngắn.
- **Chuyển động:** đường kẻ `scaleX` từ 0 tới 1 theo p khi section đi qua màn hình; mỗi mốc sáng lên (chấm Gold, chữ từ 0.5 tới 1 opacity) khi đường kẻ chạm tới.
- **Chế độ gọn:** hàng vuốt ngang; đường kẻ phía trên chạy theo vị trí vuốt.

### 7. Không gian `[isha_home_space]`

- **Mục đích:** cảm nhận không gian phòng khám, gắn với giá trị trải nghiệm đa giác quan.
- **Bố cục:** câu dẫn ở đầu; dải ảnh ngang tràn mép với kích thước xen kẽ (dọc cao, ngang rộng); chú thích Garamond italic đặt lệch dưới ảnh.
- **Chuyển động:** cảnh đi ngang, không ghim. Dải ảnh trôi ngang theo p như lia máy, tổng quãng trôi bằng phần dải đang khuất; chú thích trôi nhanh hơn 1,15 lần. Desktop có thể kéo ngang bằng chuột.
- **Chế độ gọn:** hàng vuốt ngang `scroll-snap`.
- **Rỗng:** gallery trống thì ẩn section.

### 8. Sự kiện `[isha_home_events]`

- **Mục đích:** banner carousel cho workshop và sự kiện nổi bật (Workshop, Design Talk, Charity).
- **Bố cục:** khung 2.39:1 rộng hết container. Góc dưới trái: loại sự kiện (nhãn Montserrat), tên (Garamond accent), ngày giờ và địa điểm, nút “Đăng ký tham dự” (link đăng ký) hoặc “Xem chi tiết” (permalink). Điều khiển: nút trước và sau, nút tạm dừng, vạch tiến trình dọc.
- **Dữ liệu:** `isha_event` có cờ nổi bật. Sự kiện sắp diễn ra đứng trước, theo ngày bắt đầu tăng dần; sau đó là sự kiện đã qua gần nhất kèm nhãn “Đã diễn ra”; tối đa `count`.
- **Chuyển động:** tầng nhẹ; crossfade 300ms, chữ dịch vào 8px. Tự chạy mỗi 7 giây; dừng khi rê chuột, khi focus, khi tab ẩn hoặc khi bật giảm chuyển động. Theo mẫu carousel của WAI-ARIA APG.
- **Chế độ gọn:** khung 4:5 (dùng ảnh dọc nếu có), vuốt để đổi slide.
- **Rỗng:** không có sự kiện thì ẩn section.

### 9. Bài viết `[isha_home_articles]`

- **Mục đích:** kiến thức làn da, củng cố niềm tin chuyên môn.
- **Bố cục:** một bài nổi bật (ảnh 4:5, tiêu đề Garamond, excerpt) và danh sách 3 bài (tiêu đề, chủ đề, ngày). Liên kết “Xem tất cả bài viết” tới trang lưu trữ `isha_article`.
- **Dữ liệu:** `count` bài `isha_article` mới nhất; bài có cờ nổi bật lên đầu.
- **Chuyển động:** tầng nhẹ; khi rê chuột, đường gạch mảnh chạy dưới tiêu đề (scaleX) và ảnh phóng 1.03 trong 300ms.
- **Chế độ gọn:** hàng vuốt ngang các thẻ.
- **Rỗng:** không có bài thì ẩn section.

### 10. Testimonials `[isha_home_testimonials]`

- **Mục đích:** bằng chứng xã hội, kể bằng giọng của khách.
- **Bố cục:** nền chạng vạng. Lời kể Garamond italic cỡ lớn ở giữa, tối đa 2–3 dòng. Bên dưới là dòng credit: tên hiển thị, xuống dòng rồi tới liệu trình (không nối bằng dấu chấm giữa). Điều khiển trước và sau, tạm dừng, vạch tiến trình.
- **Dữ liệu:** `isha_testimonial` đã có đồng ý công khai, tối đa `count`.
- **Chuyển động:** tầng nhẹ; từng dòng lời kể hiện như phụ đề (opacity 250ms, cách nhau 120ms). Tự chạy mỗi 8 giây, với quy tắc tạm dừng như sự kiện.
- **Rỗng:** không có lời kể hợp lệ thì ẩn section.

### 11. Tầm nhìn `[isha_home_vision]`

- **Mục đích:** lời kết cảm xúc ngay trước hành động.
- **Bố cục:** nền đêm. Câu trích Garamond accent italic căn giữa, khoảng 30–40 ký tự mỗi dòng; đoạn tầm nhìn cỡ chữ thân. Chữ ký gồm chức danh “Medical Director” (nhãn Montserrat), tên “Bác sĩ Linh Võ” (Garamond) và ảnh chữ ký tay nếu có.
- **Chuyển động:** khung hình tiến chậm (scale 1.04 → 1) khi section vào giữa màn hình. Chữ ký tay tự viết bằng `stroke-dashoffset` nếu SVG là nét; nếu là hình đặc thì hiện bằng mask quét từ trái sang phải.
- **Thiếu dữ liệu:** không có ảnh chữ ký thì chỉ hiện chức danh và tên.

### 12. Cảnh kết `[isha_home_cta]`

- **Mục đích:** hành động; đối xứng với hero.
- **Bố cục:** khung lớn nền đêm, ảnh hoặc video nền tùy chọn; tiêu đề, mô tả ngắn; nút chính “Đặt lịch tư vấn riêng”, nút phụ “Nhắn Zalo”, số hotline là liên kết `tel:`.
- **Chuyển động:** cảnh đi ngang. Khi section đi vào, khung hình thu về vòng tròn quanh logo (`clip-path: circle()` từ phủ kín về khoảng 36vmin) như cảnh kết phim; không có tia sáng.
- **Chế độ gọn:** iris khép theo thời gian trong 1,2 giây khi section vào 50% khung nhìn.
- **Thiếu dữ liệu:** thiếu Zalo hoặc hotline thì ẩn nút tương ứng.

### 13. Đối tác `[isha_home_partners]`

- **Mục đích:** uy tín, như phần credit cuối phim.
- **Bố cục:** tiêu đề nhỏ; logo đơn sắc sáng (Daylight 70% opacity, 100% khi rê chuột hoặc focus) xếp lưới thoáng, căn giữa. Logo có link thì mở tab mới với `rel="noopener"`.
- **Chuyển động:** tầng nhẹ; logo hiện lần lượt bằng opacity khi vào khung nhìn (stagger 40ms, tối đa 6 nhóm); không marquee.
- **Chế độ gọn:** lưới 2 cột.
- **Rỗng:** gallery trống thì ẩn section.

## Trang chi tiết liệu trình

`single-isha_service.php` được dựng lại theo cùng hệ thiết kế, giữ nguyên dữ liệu và luồng đặt lịch hiện có.

- **Mở đầu:** khung lớn, ảnh đại diện dọc 4:5 đặt lệch phải; tên liệu trình (Garamond display), tên nhóm (liên kết về trang nhóm), `short_desc` và thời gian liệu trình. Chuyển động: khung ảnh tiến chậm theo cuộn; tên hiện bằng mặt nạ trượt lên, có chừa chỗ cho dấu tiếng Việt. Không dùng iris hay tia sáng, vốn là chữ ký của trang chủ.
- **Nội dung:** `the_content()` với typography biên tập: Garamond, 62–70 ký tự mỗi dòng, trích dẫn có đường kẻ Gold.
- **Các bước liệu trình** (`steps`): danh sách đánh số nằm trên một đường kẻ dọc mảnh.
- **Hình ảnh liệu trình** (`gallery`): hàng vuốt ngang có nút mũi tên; ảnh mở ở chế độ xem lớn ngay trong trang (dialog đóng được bằng Esc), không mở tab mới.
- **Thẻ thông tin:** dính bên cạnh trên desktop, nằm cuối trang trên điện thoại; gồm thời gian liệu trình, nút “Đặt lịch liệu trình này” (`isha_booking_url()` kèm `?service=<ID>` như hiện có) và “Nhắn Zalo”.
- **Liệu trình khác trong nhóm:** tối đa 5 bài cùng nhóm theo `menu_order`; ít hơn 5 thì hiện đúng số đang có.
- **Kết trang:** dùng lại shortcode `[isha_home_cta]`.
- **Tài nguyên:** `service-single.css` và `service-single.js` (dùng lõi `isha-motion.js`). Kiểm tra selector `main.page > .page-header` trong `page.css` để phần mở đầu mới không bị ẩn.

## Responsive, accessibility, hiệu năng và bảo mật

Đây là mức sàn chất lượng, áp dụng cho cả trang chủ và trang chi tiết liệu trình.

### Responsive

- Chế độ gọn khi `(max-width: 900px), (max-height: 560px)`: không ghim cảnh nào; mọi section nhiều phần tử thành hàng vuốt ngang; các cảnh điện ảnh chạy theo thời gian như bảng chữ ký.
- Lý do: cảnh ghim trượt ngang vẫn tiêu tốn quãng cuộn dọc, còn hàng vuốt ngang mới thật sự rút ngắn trang trên điện thoại.
- Hàng vuốt ngang: `scroll-snap-type: x mandatory`, `overscroll-behavior-x: contain`, ẩn thanh cuộn nhưng vẫn cuộn được bằng bàn phím, luôn có nút mũi tên và chỉ báo vị trí.
- Dùng `svh` có fallback `vh`; dùng `container-type` khi cần đo bề ngang không tính thanh cuộn.
- Kiểm tra ở 1920 × 1080, 1440 × 900, 1280 × 720, 768 × 1024, 390 × 844, 360 × 780 và điện thoại xoay ngang 844 × 390.

### Accessibility (WCAG 2.2 AA)

- Một `h1` duy nhất, thứ tự heading đúng; mỗi section có `aria-labelledby`.
- Focus luôn nhìn thấy (giữ kiểu `:focus-visible` hiện có); thứ tự tab theo thứ tự đọc; nội dung đang bị cảnh ghim che thì `inert`, hoặc được cuộn tới khi nhận focus.
- Video và mọi nội dung tự chạy quá 5 giây có nút tạm dừng; carousel theo mẫu APG (`aria-roledescription`, nút tạm dừng đứng đầu, dừng khi focus).
- Ảnh có alt lấy từ thư viện; ảnh trang trí dùng `alt=""`; SVG trang trí có `aria-hidden`.
- Màu chữ theo quy tắc tương phản ở mục Token.
- Không có nội dung chỉ hiện khi rê chuột; không có thao tác chỉ làm được bằng cử chỉ vuốt.

### Hiệu năng

- Mục tiêu trên điện thoại 4G: LCP dưới 2,5 giây (poster hero là ứng viên LCP), CLS dưới 0,1, INP dưới 200ms.
- Ảnh dùng `wp_get_attachment_image` với `sizes` đúng, `loading="lazy"` trừ poster hero, giữ tỉ lệ để không nhảy bố cục. Thêm image size `isha-frame` 1200 × 1500 (cắt), `isha-cinema` 2400 × 1004 (cắt), `isha-strip` 1600 × 1100 (không cắt).
- Video hero chỉ tải nguồn hợp thiết bị, tạm dừng khi khuất, không tải khi `saveData`.
- Chỉ animate transform, opacity, clip-path, mask; `will-change` chỉ đặt trong lúc đang chuyển động; không dùng `filter: blur` chạy theo cuộn.
- `isha-motion.js` và `home.js` gộp lại dưới 60KB chưa nén; không nạp tài nguyên trang chủ ở trang khác.
- Font: đề xuất chuyển sang WOFF2 và cắt còn bộ Latin + tiếng Việt, nhẹ hơn nhiều so với OTF/TTF hiện tại; nếu không làm được trong phạm vi này thì ghi vào báo cáo bàn giao.

### Bảo mật và chất lượng code

- Escape mọi đầu ra (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` cho đoạn văn có định dạng); sanitize mọi đầu vào; nonce và kiểm tra quyền khi lưu.
- Chỉ inline SVG từ file trong theme; SVG do người dùng tải lên (chữ ký, logo đối tác) in bằng thẻ `<img>`, không inline.
- Mọi chuỗi giao diện bọc `__()` hoặc `esc_html__()` với text domain `isha`.
- Tương thích PHP 7.4 trở lên như header theme; tuân WordPress Coding Standards.
- Không có PHP notice hay lỗi console; chạy `php -l` cho mọi file PHP mới.

### Trường hợp biên

- ISHA Core tắt: section dùng CPT ẩn, các section khác dùng nội dung mặc định, không lỗi.
- Section bị tắt trong CMS hoặc rỗng: không để lại khoảng trống; lớp bầu trời nội suy qua section kế tiếp.
- Chưa có logo SVG thì dùng chữ Garamond; chưa có poster thì dùng khung đêm với ánh sáng Gold.
- Đổi kích thước cửa sổ, xoay máy, quay lại trang từ bfcache: đo lại và đặt mọi cảnh đúng vị trí.

## Kế hoạch triển khai và nghiệm thu

Làm tuần tự theo 8 giai đoạn. Ở mỗi điểm duyệt, dừng lại và gửi tóm tắt kèm ảnh chụp màn hình nếu có công cụ trình duyệt.

1. **Giai đoạn 0:** khảo sát và báo cáo. Điểm duyệt.
2. **Giai đoạn 1, dữ liệu:** CPT, term meta, meta box trang Home, lệnh seed, `isha_home_data()` và nội dung mặc định.
3. **Giai đoạn 2, khung:** token, lõi `isha-motion.js`, định tuyến và xem trước, registry, 13 shortcode trả về khung tĩnh có nội dung thật, header trang chủ, dock, lớp bầu trời. Điểm duyệt: trang đọc được trọn vẹn khi chưa có chuyển động.
4. **Giai đoạn 3:** intro và hero. Điểm duyệt riêng, vì đây là điểm nhấn lớn nhất.
5. **Giai đoạn 4:** các cảnh điện ảnh còn lại: câu chuyện, triết lý, dịch vụ, không gian, tầm nhìn, cảnh kết. Điểm duyệt.
6. **Giai đoạn 5:** các section tầng nhẹ: giá trị, quy trình, sự kiện, bài viết, testimonials, đối tác.
7. **Giai đoạn 6:** trang chi tiết liệu trình. Điểm duyệt.
8. **Giai đoạn 7:** kiểm thử tổng thể và bàn giao.

### Checklist nghiệm thu

- [ ] Trang Chính sách, chế độ landing, trang đặt lịch và các trang khác không đổi hành vi.
- [ ] Không có thư viện mới, không có bước build; không file `policy-single.*` nào bị sửa.
- [ ] 13 shortcode chạy độc lập; section rỗng hoặc bị tắt không để lại khoảng trống.
- [ ] Mỗi section đúng chữ ký chuyển động trong bảng; không chữ ký nào lặp lại; không dùng hình thức bị cấm từ trang Chính sách.
- [ ] Khi giảm chuyển động, không có JS hoặc có `isha-motion-fail`: đủ nội dung, đọc được, không ghim.
- [ ] Ở chế độ gọn: không ghim; mọi hàng vuốt ngang có nút mũi tên và chỉ báo.
- [ ] Chữ tiếng Việt không bị cắt dấu ở mọi mặt nạ và tiêu đề lớn.
- [ ] Tương phản đạt AA theo quy tắc màu; vùng chạm từ 48px; focus luôn nhìn thấy.
- [ ] LCP, CLS, INP đạt mục tiêu trên điện thoại (đo bằng Lighthouse nếu có).
- [ ] Không lỗi PHP và console; `php -l` sạch.

### Bàn giao

- Danh sách file đã thêm hoặc sửa, kèm lý do.
- `docs/home-assets.md`: bảng tài nguyên theo Phụ lục B, ghi rõ file nào đang là placeholder.
- `docs/home-admin.md`: hướng dẫn ngắn bằng tiếng Việt cho admin: chỉnh từng section, xem trước bằng `?isha_preview=home`, tắt chế độ landing khi ra mắt.
- Hạn chế còn lại và đề xuất cho giai đoạn sau: trang nhóm dịch vụ, bài viết, sự kiện; font WOFF2; chuyển trang Chính sách sang lõi chuyển động chung.

## Phụ lục A: nội dung mặc định (bản chờ khách duyệt)

Đây là giá trị mặc định trong `isha_home_defaults()`; admin sửa được trong CMS. Nội dung không gắn nhãn lấy từ leaflet. Nhãn \[nháp\] là nội dung soạn thêm ngoài leaflet. Nhãn \[cần xác nhận\] là chỗ khách cần quyết. Chính tả đã chuẩn hóa: thẩm mỹ, triết lý, hài hòa, ISHA, phẫu thuật, toàn diện.

### Intro và hero

- Câu intro \[nháp\]: Một khoảng lặng bình tâm
- Dòng nhỏ \[nháp\]: Thẩm mỹ chuẩn y khoa
- Tiêu đề h1 \[nháp\]: Vẻ đẹp là một dấu ấn riêng
- Mô tả \[nháp, rút gọn từ leaflet\]: Tôn vinh nét đẹp tự nhiên theo tiêu chuẩn thẩm mỹ cân bằng, toàn diện và bền vững theo thời gian.
- Nút chính: Đặt lịch tư vấn riêng
- Nút video: Tạm dừng video / Phát video

### Câu chuyện

- Tiêu đề: Câu chuyện ISHA
- Năm thành lập: 2024
- Đoạn chính: Thành lập vào năm 2024, ISHA tin rằng vẻ đẹp của mỗi người là một dấu ấn riêng. Với triết lý tôn vinh nét đẹp tự nhiên của từng khách hàng, ISHA hướng đến tiêu chuẩn thẩm mỹ cân bằng, toàn diện và bền vững theo thời gian.
- Đoạn phụ \[cần xác nhận câu đầu: tên hiển thị của thương hiệu có chữ “Bác sĩ” không\]: Lấy danh xưng “Bác sĩ” để đặt tên cho thương hiệu như một lời cam kết chân thành về sự tận tâm và chuẩn mực y khoa. Từ chuyên môn, con người đến từng trải nghiệm, mỗi giá trị đều được chăm chút bằng sự chỉn chu và trách nhiệm. Bởi vì tại ISHA, vẻ đẹp không đơn thuần là sự hoàn thiện diện mạo, mà là hành trình nuôi dưỡng sự hài hòa giữa ngoại hình, nội tâm và phong cách sống.

### Triết lý ISHA

- Tiêu đề: Triết lý ISHA
- Câu dẫn \[nháp\]: Bốn chữ cái làm nên tên gọi, cũng là bốn cam kết của ISHA.

| Chữ | Tiếng Anh | Tiếng Việt | Mô tả \[nháp\] |
| --- | --- | --- | --- |
| I | Inner Beauty | Vẻ đẹp từ bên trong | Vẻ đẹp bắt đầu từ sự bình an và tự tin bên trong; mọi liệu trình tại ISHA đều khởi đầu bằng sự lắng nghe. |
| S | Sustainable | Đẹp bền vững | Không chạy theo xu hướng nhất thời, ISHA chọn những giải pháp giữ vẻ đẹp tự nhiên theo thời gian. |
| H | Holistic | Đẹp toàn diện | Chăm sóc sự hài hòa giữa ngoại hình, nội tâm và phong cách sống, thay vì một chi tiết riêng lẻ. |
| A | Aesthetic | Thẩm mỹ chuẩn y khoa | Mỗi quyết định thẩm mỹ đều dựa trên thăm khám của bác sĩ và chuẩn mực y khoa. |

### Dịch vụ

- Tiêu đề: Dịch vụ
- Câu dẫn \[nháp\]: Sáu hành trình chăm sóc, một chuẩn mực y khoa.

| Thứ tự | Nhóm (term) | Tagline (mô tả term) | Liệu trình (bài `isha_service`) |
| --- | --- | --- | --- |
| 1 | Chăm sóc da chuyên sâu | Chăm sóc nền da – duy trì vẻ tươi khỏe | Điều trị mụn chuẩn y khoa; Chăm sóc da chuẩn Hàn; Chăm sóc da toàn diện chuẩn ISHA |
| 2 | Peel và tái tạo bề mặt da | Cải thiện bề mặt – tái tạo làn da mịn màng và trắng sáng | Peel; Mesopeel |
| 3 | Cấp ẩm và cải thiện chất lượng da | Chăm sóc độ ẩm – trẻ hóa làn da – căng mướt sáng mịn | Mesotherapy; Skin Booster; BAP |
| 4 | Thẩm mỹ công nghệ cao | Chăm sóc sắc da – săn chắc đường nét | Laser; HIFU; RF |
| 5 | Nghệ thuật tạo hình gương mặt | Tinh chỉnh đường nét – chạm đến vẻ đẹp hài hòa | Filler; Botox; ISHA Signature Harmony; Nâng cơ chuyên sâu |
| 6 | Phẫu thuật thẩm mỹ | Cân đối tỉ lệ – tạo hình tự nhiên – tôn vinh nét đẹp độc bản | Thẩm mỹ mắt; Thẩm mỹ mũi |

### Giá trị cốt lõi

- Tiêu đề: Giá trị cốt lõi

| Giá trị | Mô tả |
| --- | --- |
| Chăm sóc da chuẩn y khoa | Mỗi khách hàng đều được bác sĩ trực tiếp thăm khám, đánh giá tình trạng và xây dựng phác đồ điều trị. |
| Cá nhân hóa | Mỗi gương mặt, làn da đều có nhu cầu khác nhau, và luôn được cung cấp giải pháp phù hợp nhất. |
| Công nghệ & sản phẩm chính hãng | ISHA luôn cam kết mỹ phẩm và vật liệu thẩm mỹ uy tín nhất, kết hợp cùng công nghệ hiện đại luôn được nâng cấp và bảo trì. |
| Bác sĩ đồng hành | Từ tư vấn đến theo dõi sau liệu trình, khách hàng luôn được bác sĩ và đội ngũ chuyên môn đồng hành xuyên suốt. |
| Trải nghiệm đa giác quan | Không chỉ là phòng khám hiện đại, ISHA được xây dựng như một nơi nghỉ ngơi và hồi phục cho mọi giác quan. |

### Quy trình thăm khám da tại ISHA

- Tiêu đề: Quy trình thăm khám da tại ISHA

| Bước | Tên bước | Mô tả \[nháp\] |
| --- | --- | --- |
| 1 | Soi da – phân tích da | Bác sĩ soi và phân tích để đánh giá chính xác tình trạng làn da. |
| 2 | Tư vấn phác đồ điều trị cá nhân | Phác đồ được xây dựng riêng theo tình trạng da và mong muốn của từng người. |
| 3 | Thực hiện liệu trình điều trị | Liệu trình do bác sĩ và đội ngũ chuyên môn thực hiện theo đúng phác đồ. |
| 4 | Chăm sóc da sau liệu trình | Hướng dẫn chăm sóc giúp làn da phục hồi và giữ kết quả lâu dài. |
| 5 | Thăm khám định kỳ sau liệu trình | Tái khám định kỳ để theo dõi tiến triển và điều chỉnh kịp thời. |

### Không gian

- Tiêu đề: Không gian ISHA
- Câu dẫn (rút từ giá trị “Trải nghiệm đa giác quan”): Một nơi nghỉ ngơi và hồi phục cho mọi giác quan.

### Sự kiện

- Tiêu đề: Workshop & sự kiện
- Câu dẫn \[nháp\]: Những buổi gặp gỡ dành riêng cho cộng đồng ISHA.
- Nhãn: Đăng ký tham dự; Xem chi tiết; Đã diễn ra; Sự kiện trước; Sự kiện sau; Tạm dừng; Phát

### Bài viết

- Tiêu đề: Kiến thức làn da
- Liên kết: Xem tất cả bài viết

### Testimonials

- Tiêu đề \[nháp\]: Những câu chuyện
- Không có lời kể mặc định; chỉ hiển thị lời kể thật đã được khách đồng ý công khai.

### Tầm nhìn

- Tiêu đề (ẩn, dành cho trình đọc màn hình): Tầm nhìn
- Câu trích: “ISHA tin rằng vẻ đẹp đích thực được kiến tạo từ những lựa chọn tinh tế, sự thấu hiểu bản thân và những giá trị bền vững vượt lên trên mọi xu hướng nhất thời.”
- Đoạn tầm nhìn: ISHA mong muốn trở thành một khoảng lặng bình tâm, nơi vẻ đẹp được nâng niu bằng sự thấu hiểu, để mỗi người tìm thấy sự tự tin, nét đẹp riêng và phiên bản rạng rỡ nhất của chính mình.
- Chức danh: Medical Director
- Tên: Bác sĩ Linh Võ

### Cảnh kết

- Tiêu đề \[nháp\]: Khởi đầu bằng một buổi tư vấn riêng
- Mô tả \[nháp\]: Bác sĩ trực tiếp thăm khám và cùng Quý khách xây dựng phác đồ phù hợp nhất.
- Nút: Đặt lịch tư vấn riêng; Nhắn Zalo; Gọi hotline

### Đối tác

- Tiêu đề \[nháp\]: Đồng hành cùng ISHA

### Dock và trang chi tiết liệu trình

- Dock: Đặt lịch tư vấn riêng; Nhắn Zalo; Gọi hotline
- Trang chi tiết: Thời gian liệu trình; Các bước liệu trình; Hình ảnh liệu trình; Đặt lịch liệu trình này; Liệu trình khác trong nhóm

## Phụ lục B: tài nguyên cần chuẩn bị

Agent dựng sẵn placeholder trung tính (khối màu theo tông, ghi tên tài nguyên), không dùng ảnh stock; chủ dự án tự import theo bảng dưới đây.

| Tài nguyên | Quy cách | Dùng ở | Nơi import |
| --- | --- | --- | --- |
| Video hero ngang | MP4 H.264, 1920 × 1080, 8–15 giây lặp liền mạch, không tiếng, tối đa 8 MB | Hero trên desktop | Trang Home, thẻ Hero |
| Video hero dọc | MP4 H.264, 1080 × 1350 hoặc 1080 × 1920, tối đa 3 MB | Hero trên điện thoại | Trang Home, thẻ Hero |
| Poster hero ngang và dọc | JPG hoặc WebP, là khung đầu của video tương ứng, tối đa 250 KB | Hero, ứng viên LCP | Trang Home, thẻ Hero |
| Logo chữ SVG | Mỗi chữ cái một path với `id` là `isha-i`, `isha-s`, `isha-h`, `isha-a`; `viewBox` sát chữ | Hero, Triết lý | `assets/images/isha-wordmark.svg` trong theme |
| Logo đầy đủ | SVG hoặc PNG, bản sáng và bản tối | Header, cảnh kết | Giao diện › Tùy biến › Nhận dạng trang web |
| Ảnh bìa 6 nhóm dịch vụ | Dọc 4:5, cạnh dài từ 1500px; video lặp ngắn tùy chọn, tối đa 3 MB | Dịch vụ | Từng nhóm trong taxonomy nhóm dịch vụ |
| Ảnh liệu trình | Ảnh đại diện 4:5 và gallery | Trang chi tiết liệu trình | Từng bài liệu trình |
| Ảnh 5 giá trị (tùy chọn) | Dọc 3:4, đen trắng hoặc tông da | Giá trị cốt lõi | Trang Home, thẻ Giá trị |
| Ảnh không gian | 6–10 ảnh, cạnh dài từ 2000px, có cả dọc và ngang, kèm caption | Không gian | Trang Home, thẻ Không gian |
| Ảnh sự kiện | Ngang 2.39:1 (2400 × 1004) và dọc 4:5 cho điện thoại | Sự kiện | Từng bài sự kiện |
| Chân dung khách (tùy chọn) | Vuông, đen trắng, đã có đồng ý của khách | Testimonials | Từng lời kể khách hàng |
| Chữ ký Bác sĩ Linh Võ | SVG dạng nét (stroke) để có hiệu ứng tự viết; dạng hình đặc vẫn dùng được | Tầm nhìn | Trang Home, thẻ Tầm nhìn |
| Ảnh hoặc video cảnh kết (tùy chọn) | Như video hero ngang | Cảnh kết | Trang Home, thẻ Cảnh kết |
| Logo đối tác | SVG hoặc PNG một màu trắng, nền trong suốt; alt là tên đối tác | Đối tác | Trang Home, thẻ Đối tác |
| Font WOFF2 (khuyến nghị) | Montserrat 400/500/600 và Garamond Premier Pro Display 400, 400 italic, 500, 600; cắt bộ Latin + tiếng Việt | Toàn site | `assets/fonts`, cập nhật `@font-face` |
