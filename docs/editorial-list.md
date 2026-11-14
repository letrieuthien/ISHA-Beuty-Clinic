# Trang danh sách Tin tức / Sự kiện – Editorial Bento Grid

Một template dùng chung cho hai trang, dựng theo `docs/prompt-tin-tuc.md` (Editorial Bento Grid, thuần CSS, không JavaScript).

| Trang (đã có sẵn trong wp-admin) | Dữ liệu | WordPress chọn mẫu nào |
|---|---|---|
| **Danh sách tin tức** (ID 15, slug `tin-tuc`) | `isha_article` | `home.php` – trang này là **Trang bài viết** (Cài đặt › Đọc) nên WordPress bỏ qua mẫu trang chọn tay và `page-{slug}.php` |
| **Danh sách sự kiện** (ID 206, slug `danh-sach-su-kien`) | `isha_event` | `page-danh-sach-su-kien.php` (WordPress tự dùng theo slug, không phải gán mẫu trong DB; vẫn chọn tay được ở mục Mẫu trang: "Danh sách sự kiện (Bento)") |

`page-tin-tuc.php` ("Danh sách tin tức (Bento)") là dự phòng: dùng khi trang tin tức là một Page thông thường (không còn là Trang bài viết).

## Template dùng chung

| File | Vai trò |
|---|---|
| `inc/editorial-list.php` | Toàn bộ logic: cấu hình theo post type, truy vấn, gom nhóm, dữ liệu thẻ bài, tab, phân trang, nạp CSS, `isha_editorial_render_page()` |
| `template-parts/editorial/list.php` | Markup trang: tiêu đề, tab chuyên mục, lưới bento, phân trang |
| `template-parts/editorial/tile.php` | Markup một ô (thẻ bài) |
| `assets/css/editorial-list.css` | Giao diện + hoạt ảnh (không có JS) |
| `home.php`, `page-tin-tuc.php`, `page-danh-sach-su-kien.php` | Mẫu mỏng, mỗi file chỉ gọi `isha_editorial_render_page( '<post type>' )` |

Thêm danh sách thứ ba: khai báo post type trong `isha_editorial_sources()` (taxonomy làm chuyên mục + khoá meta Nội dung ngắn) rồi thêm một mẫu trang gọi `isha_editorial_render_page()`.

## Thẻ bài (5 phần, giống nhau cho cả hai post type)

Ảnh đại diện · Tiêu đề · Nội dung ngắn · Tác giả · Ngày viết (ngày đăng bài, định dạng `d/m/Y`, đổi bằng filter `isha/editorial/date_format`).

- **Nội dung ngắn** lấy theo thứ tự: trường **Nội dung ngắn** (mới) → Đoạn trích (Excerpt) → 26 từ đầu của bài. Bài đặt mật khẩu không lộ nội dung.
- **Tác giả**: nếu tên hiển thị trùng tên đăng nhập (mặc định của WordPress; tài khoản quản trị hiện tại là như vậy) thì KHÔNG in ra (tránh lộ tên đăng nhập quản trị): dùng họ tên, không có nữa thì dùng tên website ("ISHA Clinic"). Muốn hiện tên thật: Người dùng › Hồ sơ › "Hiển thị công khai là".
- Thiếu ảnh đại diện: khối giữ chỗ tông Skin/Golden (hero: tông Dusk/Shadow).

## Trường mới "Nội dung ngắn"

- Nhập ở meta box **Thông tin sự kiện** / **Thông tin bài viết** (hàng đầu tiên), 1–2 câu, tối đa 200 ký tự (bỏ thẻ HTML, gộp xuống dòng).
- Khoá meta: `_isha_event_summary` (sự kiện), `_isha_article_summary` (bài viết); đọc qua `ISHA_Core_Event_Fields::get()['summary']` / `ISHA_Core_Article_Fields::get()['summary']`.
- Trang chủ (`isha_home_data_excerpt()`) dùng chung đoạn này nên mọi nơi hiện cùng một nội dung.
- Mã: `wp-content/plugins/isha-core/includes/class-event-fields.php`, `class-article-fields.php`.

## Bố cục và thuật toán

Một "màn bento" gồm đúng 3 thành phần, không bài nào xuất hiện hai lần:

1. **Mới nhất / Tiêu điểm (hero)**: bài mới nhất còn lại.
2. **Cùng chuyên mục**: tối đa 3 bài chung term (loại sự kiện / chủ đề) với hero; chung nhiều term hơn xếp trước, rồi bài mới hơn.
3. **Cùng tác giả**: tối đa 3 bài cùng tác giả với hero, lấy trong số còn lại sau bước 2 nên không trùng.

- **Phân trang** (`?trang=N`) đi qua từng màn bento: mỗi trang lấy bài mới nhất còn lại làm hero rồi gom nhóm trong số bài chưa dùng, nên mọi bài xuất hiện đúng một lần qua các trang. Không dùng `/page/N/` vì trên Trang bài viết của WordPress nó chạy vào truy vấn blog chính và bị 404.
- **Tab chuyên mục** (`?chuyen-muc=slug`) là liên kết thường (tải lại trang), lọc cả danh sách trước khi gom nhóm.
- **Nhóm thiếu bài**: chỉ hiện số bài thật sự liên quan, không lấy bài không liên quan cho đủ 3 (nhãn nhóm sẽ sai). Lưới tự dàn lại cho đầy ô (class `isha-bento--c0..3` và `isha-bento--a0..3`): không có ô trống với mọi tổ hợp.
- Lấy tối đa 500 bài mới nhất (filter `isha/editorial/pool_max`).

| Cỡ màn hình | Lưới |
|---|---|
| ≥ 1100px | 4 cột bento: hero 2x2; nhóm chuyên mục 1x2 + 1x1 + 1x1 (2 bài: 2x1 + 2x1; 1 bài: 2x2); nhóm tác giả 2x1 + 1x1 + 1x1 (2 bài: 2x1 + 2x1; 1 bài: 4x1) |
| 641–1099px | 2 cột, hero và ô đầu của nhóm lẻ chiếm cả hàng |
| ≤ 640px | 1 cột, giữ thứ tự: hero → cùng chuyên mục → cùng tác giả |

Cấu trúc ô (mỗi nhóm 3 bài dùng 3 cấu trúc khác nhau): `hero`, `stack` (ảnh lớn trên), `side` (ảnh trái), `cover` (ảnh tràn, chữ đè), `strip` (dải ảnh mỏng trên). Khoảng cách ô 16–20px.

## Hệ thống thiết kế

- Chỉ dùng biến ở `:root` của `main.css` (`--isha-white`, `--isha-shadow`, `--isha-skin`, `--isha-skin-soft`, `--isha-golden`, `--isha-dusk`, `--isha-daylight`, `--isha-gold`, `--isha-gold-ink`, `--isha-line`, `--shadow-card`, `--radius`, font, easing, thời lượng): không có mã màu mới.
- Dùng lại class chung: `.container`, `.section`, `.section__header`, `.section__title`, `.btn`/`.btn--sm`/`.btn--primary` (tab), `.pagination`/`.nav-links`/`.page-numbers`, `.entry-content`, `.isha-notice`. Class mới chỉ có tiền tố `isha-bento*` (block mới, không trùng chức năng class cũ).
- Chữ: tiêu đề và nhãn Garamond, nội dung ngắn Montserrat (theo chỉ dẫn vai trò font của dự án).
- Hoạt ảnh: `@keyframes isha-bento-rise` (trượt lên + hiện dần, so le theo `--i`); trình duyệt hỗ trợ `animation-timeline: view()` thì các ô dưới màn hình đầu hiện khi cuộn tới; hover bằng `translate`/`scale` (chỉ thiết bị có chuột). `prefers-reduced-motion` tắt hết (đã có ở `main.css`).

## Đối chiếu `docs/prompt-tin-tuc.md`

- Bento: hero + đúng tối đa 3 bài cùng chuyên mục (3 cấu trúc hiển thị khác nhau) + tối đa 3 bài cùng tác giả không trùng nhóm trên; không bài nào lặp.
- Tỉ lệ ô trộn 2x2 (hero), 1x2, 2x1, 1x1 (và 4x1 / 2x2 khi nhóm thiếu bài); khoảng cách ô 16–20px.
- Hero nền ảnh tràn (hoặc mảng tối Dusk/Shadow khi chưa có ảnh); ô phụ nền trắng, chữ tối; chỉ dùng biến màu có sẵn.
- Thuần CSS (grid, `grid-column/row: span`, transition, `@keyframes`); không JavaScript (tab và phân trang là liên kết).
- Hoạt ảnh trượt lên + hiện dần khi tải trang và (trình duyệt hỗ trợ) khi cuộn tới; hover nhấc ô, phóng ảnh.
- Mobile first: 1 cột (≤ 640px), 2 cột (641–1099px), bento 4 cột (≥ 1100px), giữ thứ tự ưu tiên, không tràn màn hình.
- Không dùng Tailwind (dự án không có): CSS thường với tiền tố `isha-bento*`, có chú thích từng phần.

## Dữ liệu demo đã ghi vào DB thật (2026-11-11, theo yêu cầu "thêm field + điền nội dung demo")

Chỉ ghi đúng **10 dòng `isha_postmeta`**: `_isha_event_summary` cho sự kiện ID 194–198 và `_isha_article_summary` cho bài viết ID 199–203 (111–137 ký tự mỗi dòng). Không đụng bảng nào khác (`post_modified` giữ nguyên), không ghi đè ô đã có nội dung.

- Sao lưu và công cụ: `C:\xampp\isha-backups\2026-11-11_before-summary-field\` — `db.sql` (07:59), `db-immediately-before-apply.sql` (08:20, ngay trước khi ghi), `fill-summary.php` (mặc định chạy thử, `--apply` mới ghi, ghi một giao dịch, tự đọc lại), `rollback-summary-field.sql`.
- Đã làm đúng quy trình: sao lưu mới → nạp thử bản sao lưu (13 bảng khớp số dòng) → chạy thử trên DB thật (10 việc, 0 lỗi) → ghi → đối chiếu từng bảng với bản sao lưu bằng EXCEPT hai chiều (chỉ `isha_postmeta` thêm đúng 10 dòng, 0 dòng mất; `isha_posts` giống hệt; `isha_options` chỉ đổi `elementor_log` do Elementor tự ghi khi nạp WordPress) → chạy thử lần hai báo 0 việc → kiểm các trang công khai.
- Khôi phục chỉ phần nội dung mẫu (giữ nội dung bạn đã sửa sau đó, vì chỉ xoá dòng còn đúng nguyên văn): `"C:/xampp/mysql/bin/mysql.exe" -u root --default-character-set=utf8mb4 isha < "C:/xampp/isha-backups/2026-11-11_before-summary-field/rollback-summary-field.sql"`. Về lại hoàn toàn lúc sao lưu: nạp `db.sql` (xoá MỌI thay đổi từ 07:59).
- **Lưu ý khi đang mở màn hình sửa bài/sự kiện** (đã thấy phiên sửa bài 194): nếu màn hình đó được tải sau khi plugin có ô "Nội dung ngắn" nhưng trước khi dữ liệu mẫu được ghi, bấm Cập nhật sẽ gửi ô trống và xoá nội dung mẫu của bài đó. Hãy tải lại màn hình trước khi lưu; nếu lỡ mất, chạy lại `fill-summary.php --apply` (an toàn, chỉ điền ô còn trống). Màn hình mở từ trước khi có ô mới thì không gửi khoá này nên giữ nguyên giá trị.

## Kiểm thử đã làm (mức cơ bản, theo yêu cầu)

| Việc | Kết quả |
|---|---|
| Lớp dữ liệu: 1 + 3 + 3 không trùng bài, mỗi bài đúng một lần qua các trang, hero là bài mới nhất, nhóm đúng chuyên mục/tác giả, vượt trang về trang cuối, markup đủ 5 phần mỗi thẻ, không `<script`, không lộ tên đăng nhập, 0 lỗi/cảnh báo PHP | 1702 phép kiểm đạt trên bản sao DB thật; 2174 đạt trên bộ dữ liệu kiểm thử phong phú (18 bài mỗi loại, 4 tác giả, 4 chuyên mục, ca biên XSS/tiêu đề dài/chuỗi liền 60 ký tự/bài đặt mật khẩu/bản nháp/hẹn giờ/thùng rác) |
| Trình duyệt (Edge headless, giả lập kích thước thiết bị) trên **hai trang thật** ở 1440, 1280, 1024, 768, 390 | 5/5 cỡ sạch: 0 lỗi/cảnh báo console, 0 ngoại lệ, 0 yêu cầu mạng lỗi, 0px tràn ngang, 0 ảnh hỏng, hoạt ảnh không kẹt, lưới không ô trống/chồng lấn/cắt nội dung |
| Như trên trên bộ dữ liệu phong phú ở thêm 320, 640, 641, 1099, 1100, 1920 | Đạt (không tràn ngang, không lỗi console). Mục duy nhất bị đánh dấu là bài đặt mật khẩu, cố ý ẩn nội dung ngắn |
| Đi theo mọi tab và mọi trang bằng HTTP (bộ dữ liệu phong phú) | Mỗi tab: các trang không trùng bài, đủ số bài; trang cuối có "Trước" không có "Sau"; `?trang=99` về trang cuối; `?chuyen-muc=` sai coi như Tất cả |
| Trường "Nội dung ngắn" ở plugin (lưu, đọc, làm sạch HTML, cắt 200 ký tự đúng UTF-8, giữ giá trị cũ khi thiếu khoá, nonce/quyền, render meta box có escape) | 93/93 đạt trên bản sao DB |
| `lint74` và `wpstyle` (bộ kiểm tĩnh sẵn có của dự án) trên các tệp theme mới, `php -l`, quét cú pháp PHP 8 | Sạch |
| Trang công khai thật sau khi ghi dữ liệu mẫu (trang chủ, tin tức, sự kiện, lọc chuyên mục) | 200, 0 lỗi PHP; nội dung ngắn mới hiện ở cả hai trang danh sách và trang chủ |

Không làm: kiểm thử chuyên sâu (Safari/iOS/Firefox thật, trợ năng với trình đọc màn hình, hiệu năng, thử tay trong wp-admin).

Hạ tầng kiểm thử tạm (vhost `C:\xampp\htdocs\_isha_qa`, DB `isha_qa`, `isha_dt`, `isha_cmp2`) đã xoá sau khi kiểm; chỉ còn hai DB thử cũ `isha_sandbox`, `isha_fixture` như trước.

Ảnh chụp: `docs/editorial-shots/` — `real-*` là trang thật (5 sự kiện + 5 bài viết, chưa có ảnh đại diện nên dùng khối giữ chỗ); `kiem-thu-*` là bộ dữ liệu kiểm thử (đủ 1 + 3 + 3, có ảnh; bài tiền tố "[QA]" là ca biên, không phải nội dung thật).

## Giới hạn và việc nên làm tiếp

1. **Dữ liệu thật chưa đủ để thấy đủ 1 + 3 + 3**: mỗi loại mới có 5 bài, một tác giả, chưa có ảnh đại diện. Trang tin tức hiện hiện 1 + 2 + 2 ô, trang sự kiện 1 + 1 + 3 ô (lưới tự dàn lại đủ ô). Cần ≥ 7 bài mỗi loại, nhiều hơn một chuyên mục/tác giả và ảnh đại diện (ngang 2.39:1 cho sự kiện, dọc 4:5 cho bài viết đều được: ảnh phủ kín ô bằng `object-fit: cover`).
2. **Tên tác giả**: tài khoản quản trị có tên hiển thị trùng tên đăng nhập nên thẻ hiện tên website ("ISHA Clinic"). Đặt "Hiển thị công khai là" ở Người dùng › Hồ sơ để hiện tên thật.
3. **Ngày**: theo ràng buộc, thẻ hiện "ngày viết" (ngày đăng), kể cả sự kiện; ngày diễn ra sự kiện không hiện và danh sách sự kiện sắp theo ngày đăng.
4. Liên kết "Xem tất cả bài viết" trên trang chủ vẫn trỏ `/bai-viet/` (trang lưu trữ mặc định cũ của `isha_article`), chưa trỏ về trang Danh sách tin tức.
5. Bộ kiểm thử PHP cũ trong `C:\xampp\isha-backups\2026-11-10_after-phase7\qa-toolkit.zip` (`tests/cpt/test-cpt.php` dòng 414, 437, 863, 866) so nguyên mảng `get()` của sự kiện/bài viết: cần thêm khoá `summary` (đặt cuối mảng).
6. Trang "Bài viết" mặc định của WordPress (`post`, ví dụ "Chào tất cả mọi người!") không còn hiện ở trang Danh sách tin tức vì trang này giờ lấy `isha_article`.
