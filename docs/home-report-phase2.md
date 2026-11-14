# Báo cáo Giai đoạn 2: khung trang chủ tĩnh

| | |
| --- | --- |
| Ngày lập | 2026-11-10 |
| Trạng thái | Dựng xong, kiểm thử tự động, **đã được duyệt** (các giai đoạn sau đã hoàn thành; báo cáo cuối ở `home-report-phase7.md`). |
| Tiêu chí duyệt theo prompt | Trang đọc được trọn vẹn khi chưa có chuyển động |
| Xem ngay | http://localhost/ISHA_clinic/ |
| Giai đoạn kế tiếp | Giai đoạn 3: intro và hero (có điểm duyệt riêng) |

## 1. Kết luận ngắn

1. Trang chủ mới đang chạy thật tại http://localhost/ISHA_clinic/ (chế độ landing đã tắt, trang chủ là Page "Trang chủ" ID 10). Hiện có **8 section** với nội dung mặc định của Phụ lục A: hero, câu chuyện, triết lý, dịch vụ, giá trị, quy trình, tầm nhìn, cảnh kết. Năm section dùng dữ liệu riêng (không gian, sự kiện, bài viết, lời kể, đối tác) tự ẩn cho đến khi có dữ liệu; chúng đã được dựng và kiểm thử đầy đủ trên DB mẫu.
2. Đúng phạm vi Giai đoạn 2: khung tĩnh có nội dung thật, header trang chủ, dock liên hệ, lớp bầu trời đổi tông theo cuộn, hàng vuốt ngang cho chế độ gọn, lõi chuyển động `isha-motion.js`. **Chưa có** intro, hero iris co giãn, cảnh ghim, carousel, đường kẻ chạy theo cuộn: các phần đó thuộc Giai đoạn 3 đến 5.
3. Không có gì ngoài phạm vi bị đụng tới: chế độ landing, trang Chính sách, trang đặt lịch, plugin ISHA Core và mọi file `policy-single.*` giữ nguyên. Ở chế độ landing, đầu ra của trang chủ giống hệt bản cũ từng byte.
4. Ngoài kiểm thử của các tác tử dựng, tôi đã chạy lại độc lập toàn bộ và thêm một đợt kiểm thử tự động độc lập (4 người kiểm thử, 9 người phản biện). Đợt này tìm ra 5 lỗi thật mức cao hoặc trung bình (chữ trong thẻ sự kiện bị cắt; header và dock che điều khiển đang focus; vòng focus quá nhạt; dock ẩn vĩnh viễn khi JS lỗi; tải 242 KB tài nguyên chặn hiển thị mà trang chủ không dùng) cùng một số lỗi nhỏ. **Tất cả đã sửa và kiểm lại** (mục 4.3), trừ vài mục mức thấp ghi ở mục 8.
5. Có **6 quyết định cần bạn** ở mục 5. Quan trọng nhất là ngân sách JS (còn khoảng 2,4 đến 3,8 KB trong 60 KB cho toàn bộ cảnh của các giai đoạn sau).
6. Chưa kiểm được: Safari, Firefox, iPhone và máy cảm ứng thật, Lighthouse, ảnh chụp thật (mọi ảnh trong kiểm thử là ảnh GD mẫu), và các thao tác trong wp-admin (xem mục 7).

## 2. Cách xem

| Muốn xem | Làm thế nào |
| --- | --- |
| Trang chủ như khách thấy | Mở http://localhost/ISHA_clinic/ |
| Điện thoại | Mở cùng địa chỉ bằng DevTools, chế độ thiết bị 390 x 844. Dưới 900px bề ngang (hoặc dưới 560px chiều cao) trang chuyển sang chế độ gọn: các section nhiều khung thành hàng vuốt ngang có nút Trước, Sau và chỉ báo vị trí. |
| Xem như người sửa trang | Đăng nhập rồi mở `http://localhost/ISHA_clinic/?isha_preview=home`. Hiện thêm liệu trình nháp và nhãn của khối giữ chỗ, có `noindex`, không cache. Khi chế độ landing đang BẬT, đường dẫn này là cách duy nhất để quản trị viên xem trang chủ mới trong lúc khách vẫn thấy landing. |
| Quay lại landing Chính sách | Tùy biến > ISHA – Giao diện > Chế độ landing > tích "Chỉ hiển thị Chính sách khách hàng ở trang chủ". **Cần thêm một bước (đính chính Giai đoạn 7):** landing in nội dung của trang chủ tĩnh (`the_content()`), mà trang chủ tĩnh hiện là "Trang chủ" (ID 10, nội dung rỗng); nội dung chính sách nằm ở trang "Chính sách khách hàng thân thiết ISHA" (ID 7). Nên phải đặt Cài đặt › Đọc › Trang chủ về trang ID 7 (hoặc chép nội dung sang trang ID 10), nếu không landing hiện trang trắng. |
| Ảnh chụp | Thư mục `docs/phase2-shots/` (bộ chọn lọc, xem mục 6). Bộ đầy đủ 116 ảnh kèm `INDEX.md` nằm trong thư mục tạm của phiên làm việc và có thể bị xóa. |

## 3. Đã làm gì

### 3.1 Đối chiếu với mục tiêu Giai đoạn 2 trong prompt

| Hạng mục | Kết quả | File chính |
| --- | --- | --- |
| Token mở rộng | Xong. Chỉ **thêm** một khối vào `main.css` (0 dòng cũ bị sửa so với bản sao lưu). Hai chỗ khác prompt có chủ ý: `color-mix` dùng không gian sRGB thay vì oklab để màu thật trùng đúng hex dự phòng ở mọi trình duyệt; `--isha-z-intro` là 999 thay vì 1000 để skip-link (1000) vẫn nằm trên intro. | `assets/css/main.css` |
| Lõi `isha-motion.js` | Xong. API `window.ISHA.motion`: tiến độ cuộn làm mượt, cảnh ghim, chế độ gọn và giảm chuyển động, cuộn tới, một vòng `requestAnimationFrame` và một listener cuộn thụ động. Nặng 27,8 KB, **to hơn mục tiêu 15 đến 20 KB** (xem quyết định 1). | `assets/js/isha-motion.js` |
| Định tuyến và xem trước | Xong. Trang chủ mới hiện khi landing tắt hoặc khi quản trị viên có quyền `edit_pages` thêm `?isha_preview=home`. Landing bật: giữ nguyên hành vi cũ. | `front-page.php`, `inc/helpers.php`, `inc/home/assets.php` |
| Registry | Xong. 13 section theo thứ tự cố định, mỗi section có tông nền theo giờ trong ngày; có bộ lọc `isha/home/sections`. | `inc/home/registry.php` |
| 13 shortcode | Xong. `[isha_home_hero]` đến `[isha_home_partners]`, mỗi cái chạy độc lập, kể cả khi nhúng vào trang khác (khi đó CSS/JS tự được nạp). Chỉ in markup khi có dữ liệu; section rỗng hoặc bị tắt không để lại khoảng trống. | `inc/home/shortcodes.php`, `inc/home/helpers.php`, `template-parts/home/*.php` |
| Header trang chủ | Xong. Trong suốt trên hero, đặc nền sau hero, ẩn khi cuộn xuống và hiện lại khi cuộn lên; menu điện thoại. Dùng `get_header('home')`, `header.php` không bị sửa. | `header-home.php`, `home.css`, `home.js` |
| Dock liên hệ | Xong. Cụm góc dưới phải ở desktop, thanh 64px ở điện thoại: Đặt lịch, Zalo (ẩn cho đến khi bạn nhập URL Zalo), Hotline. | `template-parts/home/dock.php`, `home.css`, `home.js` |
| Lớp bầu trời | Xong. Lớp cố định đổi màu theo section đang xem. Ở các mối nối đổi cực sáng/tối, hai section kề nhau tự tô nền riêng để chữ không bao giờ lọt vào vùng tương phản thấp (lỗi này đã được tìm ra và sửa trong lúc tích hợp). | `home.css`, `home.js` |
| Hàng vuốt ngang (chế độ gọn) | Xong. Cuộn bắt khung, nút Trước và Sau, chỉ báo vị trí, điều khiển bằng bàn phím; chỉ có điểm dừng Tab khi hàng thật sự tràn. | `home.js` |
| Kích thước ảnh mới | `isha-frame` 1200 x 1500, `isha-cinema` 2400 x 1004, `isha-strip` 1600 x 1100. Ảnh tải lên từ nay sẽ tự có các kích thước này. | `inc/setup.php` |

### 3.2 File đã thêm hoặc sửa (theme ISHA; plugin ISHA Core không đổi trong giai đoạn này)

Sửa (5 file, so với bản sao lưu trước khi bắt đầu):

| File | Thay đổi |
| --- | --- |
| `assets/css/main.css` | Thêm khối token (49 dòng). Không sửa dòng cũ. |
| `front-page.php` | Định tuyến giữa landing và trang chủ mới. Nhánh landing giữ nguyên. Phần hero cũ, dịch vụ nổi bật, CTA và 3 bài viết kiểu cũ (từng hiện khi landing tắt) được thay bằng trang chủ mới. |
| `functions.php` | Thêm 5 dòng `require` cho `inc/home/*` (một dòng từ Giai đoạn 1). |
| `inc/helpers.php` | `isha_landing_only()` và `isha_is_inner_page()` tính thêm trạng thái xem trước; thêm `isha_home_is_preview()` và `isha_home_is_active()`. |
| `inc/setup.php` | Thêm 3 kích thước ảnh và class `isha-home` trên `<body>` khi trang chủ mới đang dựng. |

Thêm mới (24 file):

| File | Việc |
| --- | --- |
| `inc/home/data.php` | Lớp dữ liệu (Giai đoạn 1): nội dung mặc định Phụ lục A trộn với nội dung CMS. |
| `inc/home/registry.php` | Danh sách 13 section, tông nền, class. |
| `inc/home/shortcodes.php` | 13 shortcode `isha_home_*`. |
| `inc/home/assets.php` | Nạp CSS/JS, cấu hình JS, script gate trong `<head>`, preload poster và font, `noindex` khi xem trước. |
| `inc/home/helpers.php` | Hàm in markup dùng chung (nút, ảnh, giữ chỗ, wordmark, điều khiển hàng vuốt) và `isha_home_render()`. |
| `header-home.php` | Header của trang chủ mới. |
| `template-parts/home/intro.php`, `dock.php` | Khung intro (chưa chạy, Giai đoạn 3) và dock. |
| `template-parts/home/hero.php`, `story.php`, `philosophy.php`, `services.php` | Bốn section đầu. |
| `template-parts/home/values.php`, `process.php`, `space.php`, `events.php`, `articles.php` | Năm section giữa. |
| `template-parts/home/testimonials.php`, `vision.php`, `cta.php`, `partners.php` | Bốn section cuối. Cảnh kết dùng class `isha-home-cta` vì `isha-cta` đã có CSS trong plugin. |
| `assets/css/home.css` | 101,0 KB (gzip 24,0 KB), 18 khối, mọi selector nằm dưới `.isha-home` (trừ một quy tắc `scroll-padding` đặt ở `html`), không có mã màu trực tiếp. |
| `assets/js/isha-motion.js` | Lõi chuyển động, 27,8 KB. |
| `assets/js/home.js` | Vỏ trang chủ, 29,8 KB: hàng vuốt, header, dock, lớp bầu trời; có sẵn 17 vị trí trống cho các cảnh của Giai đoạn 3 đến 5. |

Không file nào trong danh sách bảo vệ bị sửa: `header.php`, `footer.php`, `policy-single.js`, `policy-single.css`, `policy-screens.php`, `single-isha_policy.php`, `inc/policy-single.php`, `page.css`, `landing-policy.php` (đã đối chiếu băm nội dung với bản sao lưu).

## 4. Kết quả kiểm thử

Mọi kiểm thử chạy trên DB sandbox, DB mẫu hoặc chỉ đọc qua HTTP công khai; DB thật không bị ghi. Có ba lớp: (1) các tác tử dựng tự kiểm và tác tử tích hợp chạy lại tất cả; (2) tôi chạy lại độc lập toàn bộ; (3) một đợt kiểm thử độc lập thêm (4 người kiểm thử và 9 người phản biện) để tìm lỗi mà các tác tử dựng bỏ sót.

### 4.1 Tôi chạy lại độc lập (sau khi sửa lỗi ở mục 4.3)

| Nhóm | Kết quả |
| --- | --- |
| Cú pháp | `php -l` 75 file PHP sạch; `node --check` 10 file JS sạch; công cụ ghép khối báo đủ 18 khối CSS và 17 cặp marker JS |
| Quy ước mã | `lint74` (tương thích PHP 7.4) sạch trên 58 file theme và 17 file plugin; `wpstyle` sạch trên plugin, trên theme chỉ còn 2 phát hiện cũ ở `landing-policy.php` (cố ý không sửa để landing giữ nguyên từng byte) |
| Bộ test PHP trên DB sandbox | 10 bộ, **1.339 phép kiểm, 0 trượt**: smoke 5, hạt giống nhóm dịch vụ 127, khoá seed 11, lớp dữ liệu 82, lược đồ mặc định 41, CPT 109, meta box trang chủ 101, tích hợp 105, picker media 349, lint 409 |
| Hạ tầng trang chủ (DB mẫu) | 389 phép kiểm: kích hoạt và xem trước theo vai trò, nạp tài nguyên, cổng 6 giây chạy thật bằng node, preload, header-home, dock, ISHA Core tắt |
| Bộ test trình duyệt và DB mẫu (chạy lại toàn bộ sau các bản sửa) | Tất cả đạt: vỏ trang `home.js` 168 và 34 phép kiểm, CSS nền 142, lõi chuyển động 152, tự kiểm công cụ audit 44, tự kiểm bộ điều khiển trình duyệt 215, picker media trong trình duyệt 246, nhúng shortcode 21 và 2, dữ liệu độc hại 42 và 28, quy tắc CSS 50 cùng bộ kiểm không vi phạm, tương phản 17 cặp và 81 dòng, kiểm dữ liệu mẫu 143 và 4, kiểm lớp bầu trời 5, cắt chữ 0 phần tử ở 1440 và 390 |
| Audit toàn trang ở 1440 x 900 và 390 x 844 | Site thật, tắt JS: đạt hoàn toàn (0 lỗi tương phản, đúng 1 h1, 0 vùng chạm dưới 48px, không tràn ngang, 0 lỗi console). Giảm chuyển động: chỉ còn các nút dock bị đo lúc đang mờ dần (số đo giả; giá trị ổn định 5,02:1 và 8,65:1). Bật JS: công cụ đo đồng bộ khi lớp bầu trời đang đổi màu nên báo giả, đã kiểm riêng bằng quét cuộn từng 30px (kết quả ở dòng dưới). Trang mẫu: tắt JS 0 lỗi tương phản; 7 lỗi console là lỗi CORS font của trang mở bằng `file://`, không phải của site |
| Tương phản với lớp bầu trời thật khi cuộn | Quét từng 30px trên site thật. Desktop: 397 vị trí, 52 phần tử, 0 vi phạm. Điện thoại: 58 phần tử, chỉ còn nút Trước vô hiệu ở đầu hàng vuốt (1,8 đến 2,3:1; trạng thái vô hiệu được WCAG miễn trừ). Phần tử sát ngưỡng nhất: chữ lớn 3,58:1 (yêu cầu 3) và chữ thường 5,2:1 (yêu cầu 4,5) |
| Landing so với bản gốc | Tắt phim, bật landing: HTML đầu ra **giống hệt từng byte** bản `front-page.php` gốc cho khách (29.858 byte) và cho quản trị viên (29.356 byte) |
| File đã đổi | So với bản sao lưu trước Giai đoạn 1: theme có đúng 5 file sửa và 24 file thêm; không file bảo vệ nào đổi; plugin không đổi trong Giai đoạn 2 (không file nào có thời gian sửa sau 00:41) |
| DB thật | Dump DB thật khác bản sao lưu sau seed chỉ ở bộ nhớ đệm và lịch cron của WordPress (không có nội dung nào đổi) |
| Trang công khai | 14 địa chỉ (`/`, `/dich-vu/`, `/dat-lich-hen/`, trang Chính sách, `/wp-login.php`, tìm kiếm, 404, feed, REST, sitemap...) trả mã đúng và không có chữ lỗi PHP; nhật ký lỗi Apache không có dòng mới |
| Ký tự và mẫu bị cấm | Quét 25 file mã trang chủ: 0 emoji, mũi tên, chấm giữa, gạch đầu dòng; CSS không có `backdrop-filter`, `blur`, `@keyframes`, con trỏ tuỳ chỉnh, mã màu ngoài chú thích |

### 4.2 Đợt kiểm thử độc lập thêm

Bốn người kiểm thử chạy song song, mỗi người tự viết và chạy script riêng, chỉ đọc và báo cáo; sau đó mỗi lỗi mức trung bình trở lên được một người khác chạy lại và cố bác bỏ (không lỗi nào bị bác bỏ; một số được hạ mức).

| Người kiểm thử | Phạm vi | Lỗi thật tìm ra |
| --- | --- | --- |
| Khung nhìn | 126 tổ hợp: 21 khung nhìn (320 đến 2560, xoay ngang, đúng các ngưỡng 900/901 và 560/561) x JS bật, tắt, giảm chuyển động x trang mẫu và site thật; tràn ngang, cắt chữ và dấu tiếng Việt, chồng lấn, phóng 200% và 400%, giãn chữ WCAG 1.4.12, ảnh méo | Chữ trong thẻ sự kiện bị cắt (cao); tiêu đề dài bị cắt khi chữ phóng 200% ở 320px (trung bình). Đã sửa. |
| Biến thể dữ liệu và môi trường | 416 lượt dựng dữ liệu biên cho 13 section, 46.080 ca so sánh `isha_landing_only` và `isha_is_inner_page` cũ với mới, 17 địa chỉ kiểm rò rỉ tài nguyên, ISHA Core tắt, ACF tắt, ma trận vai trò x landing x xem trước, 13 shortcode đứng riêng | Khách thấy tên liệu trình nháp hoặc riêng tư trong dòng credit của lời kể (đã sửa); 4 lỗi mức thấp (mục 8). Không có tài nguyên trang chủ nào rò sang trang khác. |
| Chạy thật | Cuộn tự động có giả lập CPU chậm 4 lần và 4G, LCP, CLS, độ trễ bấm, rò rỉ bộ nhớ, đếm listener và vòng `requestAnimationFrame`, chặn hoặc làm lỗi từng file JS, đổi kích thước và xoay màn hình, bfcache | Dock ẩn vĩnh viễn khi `home.js` lỗi hoặc bị chặn (đã sửa); 242 KB tài nguyên chặn hiển thị trang chủ không dùng (CSS block editor 141 KB, jQuery và jquery-migrate 101 KB; đã gỡ); trang xê dịch một lần khi JS khởi động chậm hơn 6 giây (ghi ở mục 8). LCP 1,8 giây (CPU thường) đến 2,36 giây (CPU chậm 4 lần) trên 4G nhanh, CLS dưới 0,1, độ trễ bấm 16 đến 32 ms, không khung nào quá 50 ms. |
| Trợ năng và bàn phím | Cây trợ năng, đi Tab xuôi và ngược, focus bị header hoặc dock che (WCAG 2.4.11), chế độ tương phản cao, vuốt cảm ứng, tương phản với lớp bầu trời thật ở 6 chiều rộng và với ảnh nền trắng hoặc đen thuần | Header và dock che điều khiển đang focus (cao, đã sửa); vòng focus của nút Giá trị chỉ 1,7 đến 3,6:1 (đã sửa); vòng focus mờ ở nút vô hiệu (đã sửa); dock không có viền ở chế độ tương phản cao (đã sửa); 3 lỗi mức thấp (mục 8). |

### 4.3 Đã sửa sau đợt kiểm thử độc lập, và kiểm lại

| Lỗi | Cách sửa | Kiểm lại |
| --- | --- | --- |
| Chữ trong thẻ sự kiện bị cắt ở 667 x 375, phóng 200% và 400%, giãn chữ (WCAG 1.4.4, 1.4.12) | `overflow: clip` thay cho `hidden` để thẻ cao thêm theo chữ | Chạy lại script của người kiểm thử ở 4 khung nhìn x 3 chế độ: 36 phép đo, thẻ nào cũng đủ chữ |
| Tiêu đề section dài bị cắt ở 320px khi phóng chữ 200% | `overflow-wrap: anywhere` cho tiêu đề và đoạn dẫn | Chạy lại: 0 px mất chữ ở cả 9 section, không có vấn đề cấp trang |
| Header cố định và dock che điều khiển đang focus (WCAG 2.4.11) | `scroll-padding` trên `html` (trên bằng chiều cao header cộng thanh admin; dưới bằng chiều cao dock) và bỏ `scroll-margin-top` của section để không cộng dồn | Đi Tab xuôi và ngược toàn trang, trên site thật (điện thoại) và trang mẫu (điện thoại, desktop 1280 và 1440): 0 điều khiển bị che hoàn toàn, 0 bị che một phần (trừ vùng cuộn Dịch vụ cao hơn cả màn hình, vốn luôn lọt ra ngoài). Trước đó 8 bị che hoàn toàn ở điện thoại và 2 bị che một phần ở desktop |
| Vòng focus của nút Giá trị chỉ 1,7 đến 3,6:1 trên ảnh nền | Vòng focus dùng màu chữ của tông | Đo từng điểm ảnh của vòng: thấp nhất 5,4:1, mọi điểm từ 3:1 trở lên, cả desktop và điện thoại |
| Dock ẩn vĩnh viễn nếu `home.js` lỗi hoặc bị chặn | Cổng 6 giây cũng kiểm `ISHA.home.register` | Bộ test hạ tầng chạy cổng bằng node: có thêm ca lõi chạy nhưng `home.js` thiếu thì vẫn thêm `isha-motion-fail` |
| 242 KB tài nguyên chặn hiển thị không dùng (CSS block editor 141 KB; jQuery và jquery-migrate 101 KB do plugin WPFront kéo vào) | `isha_home_trim_assets()` gỡ chúng ở trang chủ; các trang khác giữ nguyên | Bộ test hạ tầng: landing giữ, trang chủ gỡ; xem thêm kết quả đo ở mục 4.4 |
| Khách thấy tên liệu trình nháp trong lời kể | Chỉ hiện tên liệu trình đã xuất bản (người sửa bài xem trước vẫn thấy) | Bộ test lớp dữ liệu thêm 2 ca; chạy lại script của người kiểm thử: không còn lộ tên |
| Chữ h1 hero đổi font khi tải (CLS 0,039 ở desktop, dưới ngưỡng 0,1) | Đã thử preload Garamond Medium rồi **bỏ**: LCP chậm hơn 190 đến 380 ms mà CLS không giảm | Đo so sánh ở mục 4.4; bộ test hạ tầng khẳng định không preload font này |
| Dòng ngày giờ sự kiện hiện hai dấu phẩy | Nối bằng chữ "lúc" | Dựng lại trang mẫu: xem ảnh `desktop-08-events.png` |
| Scrim của header mất ở trình duyệt trước 2023 (không có `color-mix`) | Thêm khai báo dự phòng | Đọc mã |
| Dock và nút Trước, Sau không có ranh giới ở chế độ tương phản cao | Viền `ButtonText` trong `@media (forced-colors: active)` | Đọc mã (không có công cụ giả lập tương phản cao để đo lại) |
| Vòng focus của nút Trước hoặc Sau vô hiệu bị mờ theo opacity | `opacity: 1` khi đang focus | Đọc mã |

### 4.4 Số đo hiệu năng và dung lượng sau khi sửa

Đo trên site thật bằng Chrome 156 headless, điện thoại 390 x 844 (dpr 2), 4G giả lập (9 Mbit/s xuống, RTT 170 ms), tải lạnh, trung vị của 3 lần; máy chủ cục bộ không nén gzip nên số tuyệt đối còn bi quan hơn thực tế.

| Chỉ số | Trước khi sửa | Sau khi sửa |
| --- | --- | --- |
| LCP, CPU chậm 4 lần | 2.344 ms | **1.876 ms** (nhanh hơn 20%; mục tiêu của prompt là dưới 2.500 ms) |
| LCP, CPU thường | 1.828 ms | **1.448 ms** (nhanh hơn 21%) |
| LCP khi thử thêm preload Garamond Medium (đã bỏ) | | 2.252 ms và 1.640 ms, tệ hơn nên không giữ |
| Phần tử LCP | h1 của hero | h1 của hero |
| CLS (desktop 1440 x 900) | 0,039 | 0,039 (một lần dịch bố cục khi h1 đổi font; dưới ngưỡng 0,1) |
| CLS (điện thoại) | 0 đến 0,004 | 0 đến 0,004 |
| Độ trễ bấm (INP gần đúng), khung hình dài nhất khi cuộn | 16 đến 32 ms; không khung nào quá 50 ms | không đổi (đo trước khi sửa, các bản sửa không đụng tới mã chạy khi cuộn) |
| CSS chặn hiển thị | 5 file | 4 file (bỏ 140,8 KB CSS block editor) |
| JS chặn trình phân tích | 2 file, 101,1 KB (jQuery, jquery-migrate) | 0 |
| HTML trang chủ | 57,3 KB | 52,2 KB |
| Lỗi và cảnh báo console, yêu cầu lỗi | 0 | 0 (cả desktop và điện thoại) |

Dung lượng file của giai đoạn: `home.css` 100.985 byte (gzip 23.960; tăng 2,3 KB do các bản sửa), `main.css` 27.239 byte (gzip 6.806), `isha-motion.js` 27.797 byte và `home.js` 29.839 byte (cộng lại 57.636 byte, gzip 19.634; xem quyết định 1). Lần tải đầu của trang chủ vẫn nặng chủ yếu vì 4 đến 6 file font OTF và TTF (xem quyết định 2).

Lưu ý: số "trước khi sửa" của LCP lấy từ người phản biện đo cùng điều kiện; số CLS và độ trễ bấm lấy từ người kiểm thử chạy thật.

## 5. Cần bạn quyết định hoặc duyệt

Bạn có thể trả lời gọn "duyệt, theo đề xuất" để chấp nhận hết các đề xuất bên dưới.

### Quyết định 1: Ngân sách JS (prompt: `isha-motion.js` cộng `home.js` dưới 60 KB chưa nén)

- **Hiện tại:** 27.797 + 29.839 = **57.636 byte**. Còn khoảng 2,4 KB (nếu 1 KB = 1000 byte) đến 3,8 KB (nếu 1 KB = 1024 byte) cho toàn bộ cảnh của Giai đoạn 3 đến 5, trong khi ước tính các cảnh cần thêm khoảng 15 đến 25 KB.
- **Nguyên nhân:** chú thích tiếng Việt dài trong mã chiếm khoảng 17 KB (riêng các dòng chú thích: 10,0 KB trong lõi chuyển động và 6,7 KB trong `home.js`); bỏ chú thích thì tổng còn khoảng 40 đến 41 KB. Ngoài ra lõi chuyển động to hơn mục tiêu 15 đến 20 KB của prompt. Nén gzip hiện khoảng 19,6 KB cả hai file.
- **Đề xuất (a), nên chọn:** giữ đúng tiêu chí "chưa nén", nhưng ngay đầu Giai đoạn 3 rút gọn chú thích ở hai file (chuyển phần mô tả API dài sang `docs/`), lấy lại khoảng 12 đến 15 KB mà không đổi dòng mã nào. Không làm mất tính năng.
- Lựa chọn (b): đổi tiêu chí thành "sau khi bỏ chú thích" hoặc "gzip". Nhẹ việc hơn nhưng là thay đổi cam kết hiệu năng trong prompt, nên cần bạn đồng ý.
- Không đề xuất (c): cắt bớt tính năng của lõi.

### Quyết định 2: Font

- Sáu file OTF/TTF nặng khoảng 1,78 MB trong tổng 2,33 MB tải lần đầu của trang chủ. Prompt cho phép ghi vào báo cáo nếu chưa chuyển sang WOFF2 trong phạm vi này, và đây là lúc ghi.
- Việc chuyển sang WOFF2 và cắt còn bộ Latin cộng tiếng Việt (giảm khoảng 70% dung lượng) cần một công cụ như fonttools mà máy này chưa có. Tôi không tự tải mã từ internet khi chưa được bạn cho phép.
- **Đề xuất:** bạn (hoặc tôi, nếu bạn cho phép dùng một công cụ chuyển đổi cụ thể) chuyển 7 font đang dùng sang WOFF2.
- **Đã thử trong lượt này:** preload Garamond Medium (font tiêu đề hero) để bớt dịch bố cục khi chữ lớn đổi font (CLS 0,039 ở desktop). Đo so sánh trên 4G giả lập cho thấy preload làm LCP chậm hơn 190 đến 380 ms mà CLS không giảm (font 388 KB tranh băng thông với CSS), nên **đã bỏ**; chỉ đáng làm lại sau khi font đã nhỏ hơn nhiều nhờ WOFF2. Cũng trong lượt này đã gỡ 242 KB CSS block editor và jQuery mà trang chủ không dùng, giúp LCP trên 4G chậm giảm từ 2.344 xuống 1.876 ms (mục 4.4).

### Quyết định 3: Cỡ chữ thân

- Chữ thân Garamond Premier Pro bản Display ở 19px (18px trên điện thoại) đúng token của prompt, nhưng nét mảnh nên trông nhỏ. Một số cột hẹp cũng ngắn (mô tả giá trị và quy trình khoảng 20 đến 28 ký tự mỗi dòng ở desktop).
- **Đề xuất:** giữ 19px ở Giai đoạn này, xem lại khi đã có ảnh thật và xem trên điện thoại thật; nếu vẫn thấy nhỏ thì nâng `--isha-fs-body` lên 20 đến 21px cho đoạn dài (một dòng sửa).

### Quyết định 4: Menu chính đang rỗng

- Vị trí menu `primary` đang gán cho menu rỗng, nên header ở **mọi trang** (không riêng trang chủ) chỉ có logo và nút Đặt lịch. Menu đầy đủ đã có sẵn trong hệ thống.
- **Đề xuất:** vào Giao diện > Trình đơn, gán menu đầy đủ cho vị trí Primary. Việc này đổi giao diện toàn site nên tôi không tự làm.

### Quyết định 5: Mức độ rà soát

- Theo lựa chọn "nhẹ và nhanh" của bạn, quy trình dựng không có bước review mã độc lập; chỉ có kiểm thử tự động. Ở lượt này tôi đã bổ sung thêm một đợt kiểm thử tự động độc lập (mục 4.2) nhưng vẫn chưa có người hoặc agent đọc mã để review.
- **Đề xuất:** giữ nguyên; nếu bạn muốn chắc hơn thì thêm một đợt review mã độc lập trước Giai đoạn 3 hoặc ở Giai đoạn 7.

### Quyết định 6: Duyệt Giai đoạn 2

- Nếu khung này đúng hướng, hãy duyệt để chuyển sang Giai đoạn 3 (intro và hero). Nếu có chỗ muốn sửa (bố cục, chữ, tông màu, thứ tự section), nêu theo tên section và ảnh chụp ở mục 6.

## 6. Ảnh chụp

Bộ chọn lọc nằm trong `docs/phase2-shots/` (mở bằng trình xem Markdown của VS Code để thấy ảnh ngay trong báo cáo). Chụp bằng Chrome headless, hai nguồn:

- **Site thật** (`real-*`): DB thật, 8 section, chưa có media nào nên khung ảnh là khối giữ chỗ trung tính.
- **Dữ liệu mẫu** (còn lại): DB `isha_fixture` đủ 13 section nhưng ảnh là ảnh GD vẽ sẵn, **không phải ảnh chụp thật**. Dùng để xem bố cục các section chưa có dữ liệu thật.

Mọi ảnh "cả trang" chụp ở trạng thái tĩnh (giảm chuyển động, mỗi section tự tô nền theo tông). Trạng thái có JS, header, dock và lớp bầu trời chỉ thấy khi cuộn thật trên trình duyệt, nên các ảnh `*-live*` nằm trong bộ đầy đủ ở thư mục tạm.

| Ảnh | Nội dung |
| --- | --- |
| ![Hero desktop, site thật](phase2-shots/real-desktop-viewport.png) | Site thật, desktop 1440 x 900: hero (nền đêm, vòng mặt trời, 48 tia sáng, tiêu đề Garamond, nút Đặt lịch). |
| ![Hero điện thoại, site thật](phase2-shots/real-mobile-viewport.png) | Site thật, điện thoại 390 x 844: hero. |
| ![Trang thật, lát 1](phase2-shots/real-desktop-00-full-1.png) | Site thật, desktop: từ hero qua câu chuyện, triết lý đến đầu mục dịch vụ. |
| ![Trang thật, lát 2](phase2-shots/real-desktop-00-full-2.png) | Site thật, desktop: nửa sau dịch vụ, giá trị cốt lõi, quy trình, tầm nhìn, đầu cảnh kết. |
| ![Trang thật, lát 3](phase2-shots/real-desktop-00-full-3.png) | Site thật, desktop: cuối cảnh kết (nút Đặt lịch, hotline) và chân trang. Cột "Liên kết" của chân trang đang trống vì menu rỗng (quyết định 4). |
| ![Trang thật điện thoại, lát 1](phase2-shots/real-mobile-00-full-1.png) | Site thật, điện thoại: nửa đầu trang. |
| ![Trang thật điện thoại, lát 2](phase2-shots/real-mobile-00-full-2.png) | Site thật, điện thoại: nửa sau trang. |
| ![Giá trị](phase2-shots/desktop-05-values.png) | Dữ liệu mẫu: giá trị cốt lõi, 5 khung dọc, nền ảnh mờ đen trắng. |
| ![Quy trình](phase2-shots/desktop-06-process.png) | Dữ liệu mẫu: quy trình 5 bước trên đường kẻ 1px có mốc Gold. |
| ![Không gian](phase2-shots/desktop-07-space.png) | Dữ liệu mẫu: không gian, dải ảnh ngang xen ảnh dọc, chú thích Garamond nghiêng. |
| ![Sự kiện](phase2-shots/desktop-08-events.png) | Dữ liệu mẫu: sự kiện, ba thẻ khung màn ảnh rộng 2.39:1; thẻ cuối là sự kiện đã diễn ra. Dòng ngày giờ đã sửa thành "22 Tháng 11, 2026 lúc 09:30 – 11:30". |
| ![Bài viết](phase2-shots/desktop-09-articles.png) | Dữ liệu mẫu: bài viết kiểu tạp chí (bài nổi bật và 3 bài). |
| ![Lời kể](phase2-shots/desktop-10-testimonials.png) | Dữ liệu mẫu: testimonials trên nền chạng vạng. |
| ![Tầm nhìn](phase2-shots/desktop-11-vision.png) | Dữ liệu mẫu: tầm nhìn, câu trích lớn, chữ ký, Bác sĩ Linh Võ. |
| ![Cảnh kết](phase2-shots/desktop-12-cta.png) | Dữ liệu mẫu: cảnh kết có ảnh nền và lớp phủ tối, nút Đặt lịch, Zalo, hotline. |
| ![Đối tác](phase2-shots/desktop-13-partners.png) | Dữ liệu mẫu: đối tác, lưới logo cùng kích thước ô. |
| ![Dịch vụ điện thoại](phase2-shots/mobile-04-services.png) | Dữ liệu mẫu, điện thoại: dịch vụ thành hàng vuốt ngang, nút Trước, Sau và 6 vạch vị trí. |
| ![Không gian điện thoại](phase2-shots/mobile-07-space.png) | Dữ liệu mẫu, điện thoại: không gian thành hàng vuốt. |
| ![Cảnh kết điện thoại](phase2-shots/mobile-12-cta.png) | Dữ liệu mẫu, điện thoại: cảnh kết. |
| ![Mối nối hero và câu chuyện](phase2-shots/desktop-seam-story-45.png) | Mối nối đổi cực tối sang sáng (hero sang câu chuyện) khi cuộn tới 45% khung nhìn: hai bên đều đủ tương phản sau khi sửa lỗi chữ biến mất. |
| ![Mối nối bài viết và lời kể](phase2-shots/desktop-seam-testimonials-45.png) | Mối nối sáng sang tối (bài viết sang lời kể), cùng cách đo. |

## 7. Việc cần bạn làm và những gì chưa kiểm được

### 7.1 Việc cần bạn làm (không chặn việc duyệt Giai đoạn 2, nhưng cần cho các giai đoạn sau)

1. **Chuẩn bị media** theo `docs/home-assets.md`. Ưu tiên cho Giai đoạn 3: video hero (ngang và dọc) kèm poster, logo chữ ISHA dạng SVG cho theme, ảnh bìa 4:5 cho 6 nhóm dịch vụ.
2. **Nhập URL Zalo** trong cài đặt ISHA Core (trường `zalo`). Cho đến lúc đó nút Zalo ở dock và cảnh kết tự ẩn.
3. **Xuất bản các liệu trình nháp** (17 liệu trình) khi nội dung sẵn sàng. Hiện `/dich-vu/` và danh sách dịch vụ trong form đặt lịch đang trống vì 6 bài dịch vụ cũ đã chuyển vào thùng rác theo quyết định của bạn.
4. **Đặt múi giờ WordPress** thành Asia/Ho_Chi_Minh (Cài đặt > Chung). Hiện là UTC+0, sẽ làm lệch giờ sự kiện và nhãn "Đã diễn ra" ở Giai đoạn 5.
5. **Gán menu chính** (xem quyết định 4).
6. **Thử trong wp-admin** các phần mà máy kiểm thử không đăng nhập được: chọn ảnh hoặc video bằng hộp thoại thư viện, ẩn trình soạn thảo ở trang chủ, nút tạo dữ liệu mẫu (seed), trình soạn thảo khối của dịch vụ, thêm nhóm dịch vụ bằng AJAX. Nếu có gì lạ, gửi lại ảnh chụp hoặc mô tả.

### 7.2 Những gì chưa kiểm được

- **Trình duyệt và thiết bị khác:** chỉ có Chrome 156 headless. Chưa thử Safari, Firefox, iPhone hoặc Android thật, màn hình cảm ứng thật; chưa có Lighthouse (mục tiêu LCP, CLS, INP của prompt chỉ đo được bằng công cụ của Chrome, xem mục 4).
- **Ảnh và video thật:** mọi ảnh trong kiểm thử là ảnh GD vẽ sẵn, nên chưa đánh giá được việc cắt ảnh, độ tương phản trên ảnh chụp thật, ảnh nền giá trị bị ép đen trắng, logo đối tác trong suốt.
- **Chế độ xem trước trên site thật** (`?isha_preview=home`): máy kiểm thử không đăng nhập vào site thật; logic được kiểm bằng PHP CLI với quyền quản trị trên DB mẫu.
- **Menu chính có mục:** menu `primary` đang rỗng nên chưa thấy header với menu thật.
- **Preload poster hero:** chưa có poster; logic được kiểm với ảnh giả.
- **wp-admin:** các thao tác ở mục 7.1 điểm 6 chưa ai thử bằng tay.

## 8. Tồn đọng đã biết

Những mục dưới đây đã được biết và chưa sửa. Mục có đề xuất ở mục 5 không lặp lại chi tiết.

### 8.1 Cần xử lý trong giai đoạn sau

| Mục | Mức | Ghi chú |
| --- | --- | --- |
| Trang xê dịch một lần khi JS khởi động chậm hơn 6 giây | Trung bình | Cổng an toàn thêm `isha-motion-fail`, header chuyển từ cố định sang dính trong luồng và nội dung xê dịch khoảng 42 đến 80px (CLS 0,06 đến 0,10 chỉ ở lần xê dịch đó). Nội dung vẫn đọc được. Hướng sửa: làm hình học ở chế độ an toàn giống chế độ thường. Nên làm ở Giai đoạn 3 cùng lúc với intro, vì cổng này sẽ ẩn nhiều thứ hơn. Hệ quả kèm theo: sau lần nạp chậm đó các cảnh Giai đoạn 3 đến 5 không khởi động trong phiên (thiết kế của hợp đồng). |
| Header lần vẽ đầu đặc nền rồi mờ thành trong suốt sau 0,3 đến 0,5 giây | Thấp | Hiện không thấy vì hero tối. Với poster sáng ở Giai đoạn 3, nên đặt sẵn class `is-over-hero` trong HTML của `header-home.php`. |
| Nút ở section Giá trị focus được nhưng chưa làm gì | Thấp | 5 điểm dừng Tab và trình đọc màn hình báo "nút, đã mở rộng" nhưng bấm không có tác dụng. Giai đoạn 5 sẽ kích hoạt. |
| Mọi section dùng dữ liệu riêng (không gian, sự kiện, bài viết, lời kể, đối tác) mới chỉ kiểm bằng dữ liệu mẫu | Thông tin | Trên site thật các section này đang ẩn vì chưa có dữ liệu. |
| Giai đoạn 3 đến 5 chưa có: intro, hero iris, cảnh ghim, carousel, đường kẻ chạy theo cuộn | Thông tin | Đúng phạm vi. Mục Dịch vụ dài khoảng 5.700px ở desktop và sự kiện, lời kể xếp dọc cho đến khi có các cảnh đó. |
| Sự kiện cả ngày hiện "00:00 – 23:59" | Thấp | Giai đoạn 5 xử lý khi làm carousel sự kiện. |
| Lỗi mức thấp ở lớp dữ liệu, chỉ gặp khi nhúng shortcode hoặc nhập dữ liệu bất thường | Thấp | Ảnh đã bỏ vào thùng rác vẫn hiện nếu bật `MEDIA_TRASH`; byte NUL trong dữ liệu đã lưu đi thẳng ra HTML; `[isha_home_*]` nhúng trong nội dung bài trùng thuộc tính `loading` và `fetchpriority` (HTML thiếu chuẩn, trình duyệt bỏ qua); dùng cùng một shortcode hai lần trong một trang thì trùng `id`. Quy ước: mỗi shortcode dùng một lần mỗi trang. |

### 8.2 Hạn chế của thiết kế hiện tại, chấp nhận được

| Mục | Ghi chú |
| --- | --- |
| Nút Trước ở đầu hàng vuốt mờ, chữ chỉ 1,8 đến 2,2:1 | Trạng thái vô hiệu, WCAG miễn trừ. Khi đang focus nút hiện đủ rõ. |
| Dock che góc dưới phải của chân trang ở cuối trang desktop, và che một phần nội dung khi cuộn | Đúng đặc tả (dock hiện lại sau cảnh kết). Điều khiển đang focus không bao giờ bị dock che nữa (mục 4.3). |
| Dock nằm sau phim trong DOM và ẩn khi cảnh kết lộ từ 40% trở lên, nên Tab tiến không vào dock | Các hành động trùng với cảnh kết. |
| 8 liên kết cùng tên "Đặt lịch tư vấn riêng" cùng trỏ `/dat-lich-hen/` | Đạt WCAG 2.4.4 vì cùng đích. Ở Giai đoạn 4 có thể cho mỗi nhóm dịch vụ trỏ tới form đặt lịch kèm nhóm đã chọn. |
| Chữ nút Sau trong hàng vuốt Giá trị còn 4,35:1 trong lúc lớp bầu trời chuyển màu sang section Quy trình (điện thoại, 13px) | Mức thấp. Cách sửa nếu cần: đổi màu chữ nút hàng vuốt sang màu chữ của tông. |
| Chữ phụ ghi chú 17px ở chiều rộng 681 đến 900px | Theo token của prompt (17px); gộp vào quyết định 3. |
| "Quý khách" bị ngắt dòng giữa hai chữ ở mô tả cảnh kết trên desktop | Phụ thuộc độ dài chữ; có thể xử lý bằng khoảng trắng không ngắt trong nội dung mặc định nếu bạn muốn. |
| `home.js` và lõi chuyển động mỗi bên có một vòng `requestAnimationFrame` riêng (hai vòng lặp khung hình khi cuộn) | Đúng hợp đồng mục 5.9; có thể gộp lại ở Giai đoạn 4. |

### 8.3 Có sẵn từ trước, ngoài phạm vi (đã ghi nhận)

| Mục | Ghi chú |
| --- | --- |
| Skip link không chuyển focus vào `<main>` (thiếu `tabindex="-1"`) | Cùng mẫu ở `header.php`. Nên thêm cho cả `header.php` và `header-home.php` khi bạn cho phép sửa `header.php`. |
| Header dính che điều khiển đang focus ở các trang khác (đặt lịch, chính sách...) | Một dòng `scroll-padding-top` ở `main.css` sẽ sửa cho cả site; chưa làm vì đổi giao diện toàn site. |
| Chân trang cuộn ngang 22px ở 320px khi phóng chữ 200%; đoạn chữ chân trang 16,8px | Mã chân trang có sẵn. |
| jQuery và jquery-migrate vẫn nạp ở các trang khác do plugin WPFront Scroll Top | Có thể cấu hình plugin loại trừ trang, hoặc thay bằng nút cuộn lên riêng của theme. |
| Trang chủ chưa có thẻ meta description | WordPress không tự thêm; nên có ở Giai đoạn 7 (hoặc dùng plugin SEO). |
| Apache (PHP) báo khi khởi động: "Unable to load dynamic library 'curl'" | Cấu hình XAMPP có sẵn, không ảnh hưởng hiển thị; ảnh hưởng các lời gọi HTTP của WordPress (cập nhật, oEmbed). |
| `?pagename=trang-chu` trả 200 thay vì chuyển hướng | Hành vi lõi WordPress, đã có thẻ canonical sạch. |

## 9. Bước tiếp theo

1. Bạn duyệt Giai đoạn 2 và trả lời các quyết định ở mục 5.
2. **Giai đoạn 3: intro và hero** (điểm duyệt riêng, vì là điểm nhấn lớn nhất): intro logo, hero có video với vòng iris, poster, nút tạm dừng video, ghép vào lõi `isha-motion.js` đã có. Cần video và poster thật hoặc tạm dùng khung đêm với ánh sáng Gold như hiện tại.
3. Giai đoạn 4: các cảnh điện ảnh còn lại (câu chuyện, triết lý, dịch vụ, không gian, tầm nhìn, cảnh kết). Giai đoạn 5: các section tầng nhẹ (giá trị, quy trình, sự kiện, bài viết, lời kể, đối tác). Giai đoạn 6: trang chi tiết liệu trình. Giai đoạn 7: kiểm thử tổng thể và bàn giao (kèm `docs/home-admin.md`).
4. Trước khi bước vào Giai đoạn 3, nên xử lý quyết định 1 (ngân sách JS), vì mọi cảnh sau này đều thêm mã vào hai file JS hiện đã gần chạm trần.

## 10. Phụ lục

### 10.1 Sao lưu

- Ngay sau Giai đoạn 2: `C:\xampp\isha-backups\2026-11-10_after-phase2\` gồm `theme-ISHA.zip`, `plugin-isha-core.zip`, `docs.zip` và `db.sql` (DB thật `isha`).
- Các bản trước: `2026-11-09_phase0` (theme và plugin trước khi bắt đầu, cùng DB), `2026-11-10_before-seed` và `2026-11-10_after-seed` (DB trước và sau khi tạo dữ liệu dịch vụ).
- Khôi phục: mỗi zip đã chứa sẵn thư mục cấp một, nên giải nén `theme-ISHA.zip` vào `wp-content\themes\`, `plugin-isha-core.zip` vào `wp-content\plugins\`, `docs.zip` vào thư mục gốc dự án `C:\xampp\htdocs\ISHA_Clinic\` (giải nén vào chính thư mục đích sẽ bị lồng thêm một cấp). DB: chạy trong `cmd.exe` (không phải PowerShell): `C:\xampp\mysql\bin\mysql.exe -u root isha < db.sql`, hoặc `mysql.exe -u root -e "source C:/xampp/isha-backups/<thư mục>/db.sql" isha`. Bản sao lưu mới nhất và đầy đủ nhất ở `docs/home-handover.md`, mục "Sao lưu".

### 10.2 Cơ sở dữ liệu phụ

`isha_sandbox` (bản sao DB thật để test ghi và xóa) và `isha_fixture` (dữ liệu mẫu đủ 13 section) chỉ phục vụ kiểm thử, xóa lúc nào cũng được bằng `DROP DATABASE`. DB tạm của đợt kiểm thử độc lập (`isha_verify_variants`) đã được xóa.

### 10.3 Công cụ kiểm thử

Nằm trong thư mục tạm của phiên làm việc (thư mục `scratchpad`: `tools`, `tests`, `verify`), **không nằm trong dự án** và có thể bị xóa. Gồm bộ khởi động WordPress CLI trên DB sandbox hoặc DB mẫu, `lint74` (tương thích PHP 7.4), `wpstyle` (quy ước mã), bộ điều khiển Chrome headless (chụp ảnh, audit tương phản, trợ năng, vùng chạm), công cụ ghép khối CSS và JS, và bộ dữ liệu mẫu 13 section. Nếu muốn giữ để dùng cho các giai đoạn sau, hãy bảo tôi chép sang thư mục `dev-tools/` ngoài web root.

### 10.4 Môi trường

WordPress 7.1.4, PHP CLI 8.5.12 (mã phải tương thích PHP 7.4), MariaDB 10.11, Apache 2.4, Chrome 156 headless. Không có WP-CLI và git. Site nằm ở thư mục con `/ISHA_clinic/` nên mọi địa chỉ dùng `home_url()`.

### 10.5 Tài liệu liên quan

`docs/prompt-trang-chu.md` (prompt gốc), `docs/home-architecture.md` (hợp đồng kỹ thuật; mục 7 là các quy ước đã chốt ở Giai đoạn 2), `docs/home-assets.md` (danh sách media cần chuẩn bị), `docs/phase2-shots/` (ảnh chụp).

### 10.6 Khối lượng công việc của giai đoạn này

9 tác tử dựng (hạ tầng, token và CSS nền, lõi chuyển động, dữ liệu mẫu, ba nhóm section, vỏ `home.js`, tích hợp) chạy 2 giờ 14 phút; 13 tác tử kiểm thử độc lập (4 người kiểm thử, 9 người phản biện) chạy 34 phút. Tổng cộng khoảng 7,8 triệu token của tác tử con. Theo lựa chọn "nhẹ và nhanh" của bạn, không có bước review mã bằng tác tử; chỉ có kiểm thử tự động.
