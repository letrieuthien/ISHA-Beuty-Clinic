# 03. Hệ thống thiết kế UI/UX (lập từ code)

Lập ngày 27/11/2026 bằng cách đọc trực tiếp `themes/ISHA/assets/css/`, `assets/js/` và `plugins/isha-core/assets/`. Đường dẫn tính từ `wp-content/themes/ISHA/` (plugin ghi rõ). `file:dòng` là số dòng tại thời điểm lập. Nguồn sự thật là **code**; bảng màu cũ `bang-ma-mau.md` đã cũ và **đã xoá ngày 27/11/2026** (phiên 3), file này thay thế nó (xem mục 8). Tài liệu đi kèm: `01-source-structure.md`, `02-components-and-plugins.md`, `04-technical-decisions.md`, `05-pages-status-audit.md`.

**Đổi màu thương hiệu:** sửa token ở khối `:root` của `main.css` (mục 1 bên dưới) là đổi toàn site; tông theo giờ nằm cùng khối (`main.css:52-59,98-104`), đổi xong phải kiểm lại từng section; đồng bộ `theme.json` (palette 4 màu); không viết mã HEX trực tiếp trong selector, luôn dùng biến; kiểm tương phản trước khi thêm màu mới.

**Quy tắc bắt buộc của dự án (UI/UX chung):** chỉ dùng token đã có, không tự sinh mã màu; tái dùng class sẵn có; theo breakpoint của theme (901px, cao 561px), không theo breakpoint của prompt; quy tắc theme thắng đặc tả bên ngoài, lệch thì hỏi trước.

## 1. Màu

Token gốc nằm ở `:root` đầu `main.css` (`main.css:17-45`) và khối mở rộng (`main.css:52-96`). Trùng với bảng màu `theme.json:11-14`.

| Nhóm | Token | Giá trị | Vai trò |
|---|---|---|---|
| Gốc | `--isha-daylight` | `#e7e9e6` | Nền chính, chữ trên nền tối |
| | `--isha-skin` | `#d9c5ad` | Nền ấm, thẻ |
| | `--isha-gold` | `#8e6837` | Nhấn, viền, đường kẻ, nút chính. **Không** làm chữ nhỏ trên nền sáng |
| | `--isha-shadow` | `#141414` | Chữ chính, mọi nền tối (Soft Dark, đổi từ `#1c1b19` ngày 20/11/2026) |
| Dẫn xuất | `--isha-gold-ink` | `#6f5129` | Chữ vàng nhỏ và liên kết trên nền sáng (đạt AA) |
| | `--isha-gold-hover` | `#765328` | Hover nút/liên kết vàng |
| | `--isha-skin-soft` | `#e6d8c6` | Nền thẻ lồng, tông `day-warm` |
| | `--isha-paper` | `#f4f1ea` | Nền sáng ấm (tông `paper`), chữ trên Shadow |
| | `--isha-line` | `rgba(28,27,25,.14)` | Đường kẻ mảnh |
| | `--isha-white` | `#fff` | Nền thẻ, chữ trên nút Gold (5,02:1) |
| Kim loại (chỉ trang trí) | `--isha-metal-light` | `#eadcbc` | Ánh champagne: viền, tia, chữ hover trong header |
| | `--isha-metal` / `-warm` / `-deep` | `#b59466` / `#a3814f` / `#8e6837` | Nét trang trí |
| | `--metal-light`, `--metal-dark` (`home.css:190-195`) | gradient 115° | Viền, vạch; không dùng cho chữ, nền lớn, nút (nút nền Gold phẳng từ 20/11) |
| Tông theo giờ | `--isha-dawn #84796a`, `--isha-golden #ccb498`, `--isha-evening #a48b6c`, `--isha-sunset #bba07e`, `--isha-twilight #64533e`, `--isha-dusk #3e3222` | Sáu hex khai ở `main.css:52-59`; riêng `dawn`, `golden`, `dusk` được `color-mix` từ token gốc ghi đè trong `@supports` (`main.css:98-104`) | Nền section trang chủ, vòng focus trên nền tối (`--isha-golden`) |

**Tông section** (`home.css:~80-170`): mỗi `data-tone` đặt 4 biến `--tone-bg/-fg/-accent/-ink`, còn `--tone-soft/-line/-ph` dẫn xuất bằng `color-mix`. 11 giá trị: `night`, `noir`, `paper`, `day`, `day-warm`, `afternoon`, `golden`, `late-afternoon`, `evening`, `twilight`, `dusk`. `evening` hiện không section nào dùng. Màu chữ phải theo tông (bảng tương phản ở đầu `home.css`): chữ trên `twilight`/`dusk`/`night` là Daylight hoặc Golden, không dùng Shadow.

**Chữ vàng trên nền tối:** `#c9a46a` (8,4:1 trên `#141414`); `#8e6837` chỉ 3,7:1 nên chỉ cho nét, viền. Giá trị `#c9a46a` **chưa có token gốc**: lặp ở 5 file CSS (`home.css` 14, `404.css` 2, `live-search.css` 2, `main.css` 2, `booking.css` 1) và qua biến cục bộ `--sf-gold-text` (`main.css:920`), `--ls-gold-text` (`live-search.css:20`). Muốn đổi phải đổi từng chỗ.

## 2. Typography

Font tự host (`main.css:8-18`, `font-display: swap`, WOFF2 kèm OTF/TTF dự phòng): **Montserrat** 400/500/600; **Garamond Premier Pro** (chỉ bản Display) 400, 400 nghiêng, 500, 600. Preload `GaramondPremrPro-Disp.woff2` và `Montserrat-SemiBold.woff2` ở `<head>` (`enqueue.php:121-134`).

| Token | Giá trị |
|---|---|
| `--font-heading` | `"Montserrat", "Helvetica Neue", Arial, sans-serif` |
| `--font-body` | `"Garamond Premier Pro", Garamond, "Times New Roman", serif` |

**Tên biến ngược với vai trò thực tế.** `--font-heading` là Montserrat, `--font-body` là Garamond. Vai trò đã chốt (20/11/2026): **Garamond cho tiêu đề, nhãn, lead, ghi chú** (không chữ hoa); **Montserrat cho đoạn văn và nút/liên kết hành động**. Nền tảng `main.css` (h1-h6 Montserrat 600, `body` Garamond) khác với lớp `.isha-home` (tiêu đề Garamond 500); trang con dùng lại lớp `.isha-home` nên theo quy tắc của `.isha-home`.

| Thang chữ (`main.css:77-83`) | Giá trị | Dùng cho |
|---|---|---|
| `--isha-fs-label` | `0.8125rem` | Nút, nhãn nhỏ |
| `--isha-fs-body` | `1.1875rem` | Chữ thân (`body`) |
| `--isha-fs-note` | `1.0625rem` | Ghi chú, chữ ký (Garamond 400 nghiêng) |
| `--isha-fs-sub` | `clamp(1.125rem, 0.95rem + 0.6vw, 1.5rem)` | `.isha-lead` |
| `--isha-fs-header` | `clamp(1.5rem, 1.05rem + 1.5vw, 2.375rem)` | Tiêu đề trung |
| `--isha-fs-accent` | `clamp(2.5rem, 1.3rem + 4vw, 4.75rem)` | `.isha-accent` |
| `--isha-fs-display` | `clamp(3.25rem, 1.2rem + 7.5vw, 8.5rem)` | Chữ khổng lồ |
| Trong `.isha-home` (`home.css:243-248`; `--isha-fs-title` ở dòng 244) | `--isha-fs-title` `clamp(2rem, 1.3rem + 2.4vw, 3.25rem)`; `--isha-fs-tag` và `--isha-fs-para` `1.0625rem`; `--isha-measure-para` `36em` | Tiêu đề section, nhãn, đoạn văn |

Class chữ dùng lại (`home.css:249-330`): `.isha-sec__title` (Garamond 500, dòng 1.12), `.isha-accent`, `.isha-lead` (Garamond nghiêng), `.isha-text` (Montserrat 400, dòng 1.7), `.isha-note` (Garamond nghiêng), `.isha-label` (Garamond 500, giãn chữ .08em). Nút `.isha-btn` (`home.css:349`): Montserrat 600, hoa, giãn .12em, cao tối thiểu 48px, bo `--radius` (4px); nút chính nền Gold phẳng, chữ trắng.

Lưu ý: Garamond chỉ có bản Display, nét mảnh ở cỡ nhỏ; việc đổi chữ nhỏ sang Montserrat hay mua bản Text đang chờ khách quyết định.

## 3. Breakpoint

**Chuẩn của theme** (một điều kiện dùng chung cho CSS và JS):

| Chế độ | Điều kiện | Nơi dùng |
|---|---|---|
| **Gọn** (điện thoại, máy tính bảng, màn thấp) | `(max-width: 900px), (max-height: 560px)` | CSS: ≥ 33 khối `@media` chứa `max-width: 900px`, ≥ 33 chứa `max-height: 560px`. JS: `isha-motion.js:30`, `home.js` (`mq.compact`), `service-single.js:230`, `main.js` (`LIMIT=900`) |
| **Desktop** | `(min-width: 901px) and (min-height: 561px)` | 17 khối chứa `min-width: 901px`, 12 chứa `min-height: 561px` |
| Phần bù chính xác | `min-width: 900.02px`, `min-height: 560.02px` | Dùng khi cần "ngược" của `max 900/560` mà không chồng mốc |
| Chuột thật | `(hover: hover) and (pointer: fine)` | 51 khối; mọi `:hover` phải nằm trong đây |

Header: menu ngang từ 901px; ≤ 900px là drawer. Từ 901px đến 1280px thu dần khoảng cách và cỡ chữ menu qua các bậc 1280/1180/1080/975px để 8 mục luôn vừa một dòng (`main.css:~660-700`).

**Đề bài ghi `<768px`, `<1024px`: không phải chuẩn của theme.** Theo quy tắc dự án (theme thắng prompt), tài liệu này lấy 900/901 và 560/561. Hai mốc đó chỉ còn lại ở các chỗ sau (không đổi trong phiên này):

| Mốc | Số khối | Nơi | Ghi chú |
|---|---|---|---|
| `767px` / `768px` | 6 / 3 | `card-ring.css`, `home.css`, `services-hero.css`, `services-cinematic.css`; plugin `isha-core.css`, `isha-lp-stack.js` | Thẻ vòng, hero dịch vụ, chùm thẻ chính sách |
| `1024px` / `1025px` | 3 / 2 | `main.css:1223` (lưới `.grid--4`, `.layout--sidebar`), `service-group.css:138,172,739,1075` | Phần tử chung cũ, trang nhóm dịch vụ |
| `640`/`641`/`1100` | - | `editorial-list.css:212,230,369` | Danh sách Bento: **mobile-first thật** (1 cột ≤ 640, 2 cột từ 641, 4 cột từ 1100) |
| `600`/`780` | - | `booking.css:292,600` | Form đặt lịch |
| `680`, `782` | - | `main.css` | Cỡ chữ body; thanh admin-bar (`782px`) |
| `max-width: 900px` | - | plugin `isha-lp-ladder.js:18` | Cùng giá trị 900 nhưng không có `max-height: 560px` |

**"Mobile-first" trong dự án hiểu thế nào.** CSS cơ sở **không** viết từ nhỏ lên bằng `min-width`; phần lớn là `max-width: 900px` ghi đè. Điều được giữ là *trạng thái mặc định tĩnh, đọc được trọn vẹn, không cần JS và không cần hover*; JS và chuyển động chỉ thêm lên trên (`html.isha-motion-on`). Chi tiết ở `04-technical-decisions.md` (ADR-06).

Điểm chạm: vùng chạm tối thiểu 44-48px (`.isha-btn` 48px, nút menu 44px, nút kính lúp 40px mở rộng bằng `::before`). Chiều cao khung nhìn dùng `--isha-vh` (`100svh` khi có). Lề an toàn: `env(safe-area-inset-*)` ở dock, nút Lên đầu trang.

## 4. Quy chuẩn kính (Liquid / Floating Glass)

Trong code **không có token tên "Liquid Glass"**. Chữ "Floating Glass" là header (`main.css:212`); chữ "liquid glass" chỉ nhắc về bo góc mềm của popup tìm kiếm (`live-search.css:8`). Quy chuẩn dưới đây rút từ các khối đang chạy.

**Công thức:** nền Shadow trong suốt một phần (`color-mix(in srgb, var(--isha-shadow) N%, transparent)`) + `backdrop-filter: blur(Npx) saturate(...)` + viền 1px sáng mảnh + vệt sáng `inset 0 1px 0` bằng `--isha-metal-light` + bóng đổ.

| Lớp kính | Nền | Blur | Bo góc | Nơi |
|---|---|---|---|---|
| Header nổi (`.is-scrolled`) | Shadow 74% (`--hd-glass`); không có blur thì 92% | `22px saturate(160%)` | 18px ở desktop; **≤ 900px thành thanh kính full-width, bo 0, không đệm** (`main.css:702-704`) | `main.css:316,326,344-348` |
| Dropdown menu con (≥ 901px) | Shadow 88% (`--hd-panel`) | `24px` | 14px (mục 10px) | `main.css:586,605,631-636` |
| Drawer (≤ 900px) | Shadow 84% (`--hd-drawer`); không blur 96% | `28px` | - | `main.css:631,639-643` |
| Popup tìm kiếm (nền) | `rgb(10 10 10 / .62)` | `14px` | khung 20px, mục 12px | `live-search.css:99-102` |
| Khung popup tìm kiếm | gradient Shadow 90-94% | **không** | 20px | `live-search.css:117-130` |
| Hộp xem lớn (Tin tức, liệu trình) | Shadow 84% (94% khi thiếu blur); `::backdrop` Shadow 60% | `12px` | - | `post-article.css:1145-1150`, `service-single.css:968-973` |
| Hộp xem lớn Founder | `rgb(20 20 20 / .88)` | `12px` | - | `home.css:5082-5083` |
| Nhãn Trước/Sau, nút kéo so sánh | Shadow 24% / trắng 12% | `8px saturate(140%/120%)` | viên thuốc / tròn 44px | `service-group.css:469-479,519-542` |
| Thẻ hero dịch vụ (pill) | trắng 18% → nâu 16% | `7px saturate(1.15)`; **bỏ** ở màn thấp/yếu | pill | `services-hero.css:171-172,336-340` |
| Thẻ kính trang Chính sách | gradient trắng .03-.26 | `9px saturate(1.3) brightness(1.1)` | - | `policy-single.css:139-145` |

**Luật khi dùng kính:**
1. Tấm nền vẽ ở `::before`/`::after`, **không** đặt `backdrop-filter` hay `transform` lên chính thanh có phần tử con `position: fixed` (drawer sẽ bám theo thanh thay vì khung nhìn) (`main.css:219-221`).
2. Blur chỉ bật khi cần: header chỉ khi cuộn (đầu trang là gradient, không blur); dropdown/drawer chỉ khi mở (đóng là `visibility: hidden`).
3. Luôn bọc `@supports ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px)))`, viết cả bản `-webkit-`; không có thì nền đậm hơn (header 92%, hộp xem lớn 94%).
4. Không blur lần hai lên khung đã nằm trên lớp nền mờ (`live-search.css:117-118`); không `backdrop-filter` cho khối chữ đè ảnh (`home.css:2375`, nguyên tắc "không hộp nền"); 404 chỉ dùng `filter: blur()` trên lớp ảnh tĩnh.
5. Màu chỉ từ token Shadow/Paper/Metal qua `color-mix`; không hex mới. Có `color-mix` thì mới có tông kính; không có thì giá trị dự phòng đặc.
6. `@media (scripting: none)`: header giữ nền kính cố định (không có JS thì không có `.is-scrolled`) (`main.css:352`).
7. Cân nhắc GPU tích hợp: blur lớn là thứ đầu tiên bị bỏ ở thiết bị yếu (xem `services-hero.css:336`).

Vòng focus trên nền tối: `outline: 2px solid var(--isha-golden)`, `outline-offset: 3px` (header `main.css:293`; mặc định toàn site `2px solid var(--isha-gold)`; trang chủ đổi theo `--tone-accent`, `home.css:~220`).

## 5. Easing và thời lượng

Token ở `main.css:62-74,93`:

| Token | Giá trị | Dùng cho |
|---|---|---|
| `--isha-ease-cine` | `cubic-bezier(0.22, 1, 0.36, 1)` | **Easing chính**: header nổi, vào cảnh, thanh trượt, 47 lần qua `var()` trong 9 file CSS |
| `--isha-ease-out` | `cubic-bezier(0.23, 1, 0.32, 1)` | Phản hồi chạm, hover nhỏ, reveal (`revealIn`) |
| `--isha-ease-in-out` | `cubic-bezier(0.77, 0, 0.175, 1)` | Chuyển pha đối xứng |
| `--isha-ease-btn` | `cubic-bezier(0.25, 0.1, 0.25, 1)` | **Chỉ nút** (đổi màu); `ease-out` quá gắt, mắt thấy như đổi tức thì |
| `--isha-dur-press` | `120ms` | `:active` nút (`scale(.98)`) |
| `--isha-dur-ui` / `--isha-dur-ui-lg` | `200ms` / `300ms` | UI nhỏ / lớn |
| `--isha-dur-btn` | `400ms` | Nút đổi màu (`--isha-btn-transition`) |
| `--isha-dur-header` | `420ms` | Header đầu trang ↔ nổi |
| `--isha-dur-cine` | `1100ms` | Chuyển cảnh điện ảnh |

Quy ước: hover chỉ trong `(hover: hover) and (pointer: fine)`; nhấn nút `scale(.98)` trong `--isha-dur-press`; chỉ animate `transform`/`opacity` (header là ngoại lệ có chủ ý: `max-width`, `height`, `padding`). Giá trị `cubic-bezier(.22, 1, .36, 1)` còn viết thẳng ở 6 file (`home.css` 3, `service-single.css` 3, `about.css`, `main.css`, `policy-single.css`, `space.css` mỗi file 1) và trong plugin (`isha-core.css`); đồng nghĩa với `--isha-ease-cine`, có thể gộp về token.

## 6. `prefers-reduced-motion` và các cổng khả năng

**Lớp 1, toàn cục** (`main.css:1239-1242`): `html { scroll-behavior: auto }` và `*, *::before, *::after { transition: none !important; animation: none !important }`.

**Lớp 2, theo thành phần:** 23 khối `@media (prefers-reduced-motion: reduce)` và 5 khối `no-preference` trong CSS (các hiệu ứng lặp như nhịp thở chấm trạng thái, xoay thẻ bọc trong `no-preference`). Mọi cảnh/module có nhánh tĩnh:

| Thành phần | Khi giảm chuyển động |
|---|---|
| Lõi `isha-motion.js` | `mqReduced`; cảnh ghim thành bố cục tĩnh |
| Card Ring | `.is-reduced`: hàng cuộn `scroll-snap`, không transform/perspective |
| Founder 3D, Bình minh WebGL | Bản tĩnh (ảnh + lightbox / section tĩnh) |
| Quả cầu cờ ngôn ngữ (`main.js:initCoin`) | Bỏ lật, chuyển trang thẳng |
| Chùm thẻ chính sách (`isha-lp-stack.js:14-15`) | Không chạy vào trang và nghiêng theo chuột |
| Cuộn mục lục, `scrollIntoView` | `behavior: auto` |

**Cổng khả năng khác** (đều có trong code): `(hover: hover) and (pointer: fine)` (51), `(forced-colors: active)` (16), `print` (5), `(scripting: none)` (1, header), `Save-Data` (video hero), `prefers-reduced-motion` đọc cả trong JS (19 file). Cổng `<head>` của trang chủ đặt `html.isha-motion-on` (hoặc `isha-motion-fail` sau 6 giây); trang Chính sách có chốt 9 giây.

**Quy tắc khi thêm chuyển động mới:** mặc định tĩnh, đọc được, không phụ thuộc JS; bật hiệu ứng trong `html.isha-motion-on:not(.isha-motion-fail)` hoặc `@media (prefers-reduced-motion: no-preference)`; không dựa vào hover để lộ thông tin.

## 7. Thành phần dùng lại (tên class đã có)

Dùng lại trước khi đặt class mới (quy tắc dự án). Danh sách đầy đủ ở `02-components-and-plugins.md` mục 7.

| Việc | Class / mẫu có sẵn |
|---|---|
| Khung | `.container`, `.container--narrow` (nền tảng); `.isha-wrap`, `.isha-sec`, `.isha-sec__head`, `[data-tone]` (trang chủ và các trang dùng lại) |
| Nút | `.btn`, `.btn--primary`, `.btn--outline`, `.btn--sm` (nền tảng); `.isha-btn`, `.isha-btn--primary`, `.isha-btn--ghost` (trong `.isha-home`) |
| Chữ | `.isha-sec__title`, `.isha-accent`, `.isha-lead`, `.isha-text`, `.isha-note`, `.isha-label` |
| Hàng vuốt | `.isha-row` + `isha_home_pager()` |
| Hộp xem lớn | `dialog` (3 bản: Founder, liệu trình, bài viết) |
| Liên hệ nhanh | `aside.isha-dock` |
| Lớp | `--isha-z-dock 90`, `--isha-z-header 100`, `--isha-z-intro 999` (thấp hơn skip-link 1000) |

## 8. Điểm lệch / cần chú ý (tin code, ghi ngắn)

| Nơi nói | Code thực tế |
|---|---|
| `bang-ma-mau.md` (đã xoá 27/11) và comment trỏ tới nó (`main.css:31`, `README-services-cinematic.md:56`): Shadow `#1C1B19`; kim loại `#FCF6BA/#BF953F/#AA771C/#B38728`; bảng section → tông (Câu chuyện, Triết lý, Giá trị...) | Shadow `#141414`; kim loại hạ về `#eadcbc/#b59466/#8e6837/#a3814f` (20/11); section hiện là 11 id khác (xem 01 mục 1). File đó chỉ đúng phần tông theo giờ và quy tắc tương phản, hai phần này đã nằm ở mục 1 |
| Bảng tương phản ở đầu `home.css` ghi nền `night` `#1c1b19` | Giá trị thật `#141414`; tỉ lệ tương phản lệch nhẹ |
| `--isha-line`, `--shadow-card` (`main.css:28,45`), `isha-core.css` dùng `rgba(28,27,25,…)`, `var(--isha-shadow, #1c1b19)` | Màu Shadow cũ; chỉ lệch nhẹ về sắc, không phải lỗi hiển thị |
| `--font-heading` = Montserrat, `--font-body` = Garamond | Tên ngược với vai trò thực tế của `.isha-home` (xem mục 2) |
| `#c9a46a` được coi là token | Chưa khai báo ở `:root` (xem mục 1) |
| (**Đã sửa 27/11/2026**, `home.css:5686-5696`) trước đó `[data-reveal]` đặt `opacity: 0` vô điều kiện; `[data-reveal].reveal-animate` chạy `revealIn` | **Đã xác minh 27/11/2026** bằng Chrome headless (6 loại trang: Giới thiệu, Không gian, liệu trình, Tin tức, Sự kiện, trang chủ xem trước; 4 chế độ): bình thường (desktop 1440 và điện thoại 390) hiện đủ; **giảm chuyển động** và **tắt JS** thì mọi `[data-reveal]` giữ `opacity: 0` (tổng 13 phần tử trên 6 trang, trang chủ 7/7). Nguyên nhân: JS là thứ thêm `.reveal-animate`; luật toàn cục `animation: none !important` (`main.css:1241`) thắng khối `revealInReduced` ở `home.css:5708`, nên dù đã có class phần tử vẫn không hiện. Mọi section trừ hero và articles (gồm `[isha_home_cta]`) có `data-reveal` (`home/helpers.php:114`). Phương án sửa đã thử trong trình duyệt: bọc luật ẩn bằng `@media (scripting: enabled) and (prefers-reduced-motion: no-preference)`; kết quả 0 phần tử ẩn ở cả 24 tổ hợp, hiệu ứng ở chế độ bình thường giữ nguyên. **Đã áp vào `home.css` cùng ngày** và bỏ khối `revealInReduced` (không còn nơi nào dùng); theo yêu cầu, chưa chạy lại bộ QA hồi quy trên bản đã sửa thật (chỉ đã thử bằng bản vá trong trình duyệt). Rủi ro phụ chưa thấy xảy ra: `IntersectionObserver` ngưỡng 0,35 không bao giờ kích hoạt với section cao hơn khoảng 2,86 lần khung nhìn (`founder-story` đang 2,35 lần ở 1440×900) |
| Đề bài: mốc `<768px`, `<1024px` | Không phải chuẩn theme (xem mục 3) |
