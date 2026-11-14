# Hướng dẫn quản trị trang chủ ISHA (dành cho người nhập nội dung)

Cập nhật: 2026-11-10 (Giai đoạn 7, bàn giao). Tài liệu ngắn, theo từng việc bạn sẽ làm trong wp-admin. Danh sách ảnh/video cần chuẩn bị nằm ở `docs/home-assets.md`; thông tin kỹ thuật nằm ở `docs/home-architecture.md` và `docs/home-handover.md`.

## 1. Trang chủ hoạt động thế nào

- Trang chủ mới gồm **13 section** xếp cố định từ trên xuống: Hero, Câu chuyện, Triết lý, Dịch vụ, Giá trị, Quy trình, Không gian, Sự kiện, Bài viết, Lời kể, Tầm nhìn, Cảnh kết, Đối tác. Kèm một thanh liên hệ nhanh (đặt lịch, Zalo, hotline) và nút "Lên đầu trang".
- **Section nào chưa có dữ liệu hoặc bị tắt thì tự biến mất**, không để lại khoảng trống. Vì vậy bạn không cần nhập đủ mọi thứ mới xem được trang.
- Ô nào để trống thì dùng **nội dung mặc định** (chữ mờ hiện sẵn trong ô). Muốn đổi thì gõ nội dung mới đè lên; muốn quay về mặc định thì xoá sạch ô.
- Công tắc **Chế độ landing** (Giao diện › Tùy biến › ISHA – Giao diện, xem mục 5) quyết định khách thấy gì ở trang chủ: **BẬT** = chỉ trang Chính sách khách hàng (mặc định của theme, giống bản cũ từng byte); **TẮT** = trang chủ mới hiển thị cho mọi người. **Trên site thử hiện tại công tắc đang TẮT**, tức trang chủ mới đang chạy thật (chưa có ảnh/video nên nhiều chỗ còn là khối giữ chỗ). Khi chế độ landing BẬT, bạn vẫn xem được trang chủ mới bằng đường dẫn xem trước (mục 2).

## 2. Xem trước trang chủ mới (kể cả khi chế độ landing đang bật)

1. Đăng nhập wp-admin bằng tài khoản có quyền sửa trang (Quản trị viên, Biên tập viên).
2. Mở trình duyệt, vào địa chỉ trang chủ kèm đuôi **`?isha_preview=home`**, ví dụ `https://tên-miền-của-bạn/?isha_preview=home` (trên máy thử: `http://localhost/ISHA_clinic/?isha_preview=home`).
3. Chế độ xem trước hiện **cả liệu trình đang ở trạng thái Nháp** (kèm nhãn nháp) và **nhãn của các khối giữ chỗ** (chỗ chưa có ảnh/video) để bạn biết cần bổ sung gì. Khách và người chưa đăng nhập **không** thấy gì khác với trang hiện tại; trang xem trước không được công cụ tìm kiếm lưu (noindex).
4. Sau mỗi lần lưu nội dung, tải lại trang xem trước (Ctrl + F5) để thấy thay đổi.

## 3. Sửa nội dung từng section

Vào **Trang › Tất cả trang › "Trang chủ" › Sửa**. Khung **"Nội dung trang chủ"** có một thẻ (tab) cho mỗi section. Đầu mỗi thẻ có công tắc **"Hiển thị section này"**: bỏ tick thì section ẩn khỏi trang (nội dung đã nhập vẫn được giữ). Bấm **Cập nhật** để lưu.

| Thẻ | Bạn nhập | Giới hạn / ghi chú |
|---|---|---|
| Hero và intro | Bật intro (chạy một lần mỗi phiên truy cập); câu intro; video ngang và dọc; poster ngang và dọc; dòng nhỏ; tiêu đề (h1); mô tả; nhãn nút chính | Câu intro ≤ 80 ký tự, tiêu đề ≤ 120, mô tả ≤ 240 (đó là mức tối đa của ô). **Nên giữ tiêu đề ≤ 30 ký tự (tối đa hai dòng) và mô tả ≤ 120 ký tự (hai dòng):** hero nằm trọn một màn hình, chữ dài hơn có thể chồng lên dòng nhỏ hoặc đẩy nút Đặt lịch xuống dưới mép màn hình ở laptop thấp (cao dưới khoảng 700 px). Tiêu đề tự nhỏ lại khi màn hình thấp. Video MP4 H.264 không tiếng (ngang 1920×1080, 8–15 giây, ≤ 8 MB; dọc 1080×1350, ≤ 3 MB). Poster JPG/WebP ≤ 250 KB là khung đầu của video. Video luôn lặp, không có nút phát/tạm dừng |
| Câu chuyện | Tiêu đề, năm thành lập, đoạn chính, đoạn phụ | Đoạn chính ≤ 600 ký tự, đoạn phụ ≤ 900 |
| Triết lý | Tiêu đề, câu dẫn; bốn chữ cái I, S, H, A (chữ cái, tên tiếng Anh, tên tiếng Việt, mô tả) | Mô tả mỗi chữ ≤ 240 ký tự |
| Dịch vụ | Tiêu đề, câu dẫn, ghi chú dưới ảnh trước/sau | Nhóm dịch vụ và liệu trình nhập ở mục 4.1, không nhập ở đây |
| Giá trị | Tiêu đề; năm giá trị (tên, mô tả, ảnh tuỳ chọn) | Mô tả ≤ 300 ký tự. Ảnh dọc 3:4, đen trắng hoặc tông da, hơi tối |
| Quy trình | Tiêu đề; năm bước (tên, mô tả) | Mô tả ≤ 240 ký tự |
| Không gian | Tiêu đề, câu dẫn, bộ ảnh không gian | 6–10 ảnh (tối đa 12), cạnh dài từ 2000 px. Chú thích hiện dưới ảnh lấy từ ô **Chú thích** trong Thư viện phương tiện |
| Sự kiện | Tiêu đề, câu dẫn | Sự kiện nhập ở mục 4.2; chỉ sự kiện tick **Nổi bật** mới hiện |
| Bài viết | Tiêu đề, nhãn liên kết "Xem tất cả" | Bài nhập ở mục 4.3; bài tick **Nổi bật** đứng đầu |
| Lời kể | Tiêu đề | Lời kể nhập ở mục 4.4; mọi lời kể đã **Xuất bản** và có nội dung đều hiện (mới nhất trước), không còn ô "đồng ý công khai" |
| Tầm nhìn | Tiêu đề ẩn (cho trình đọc màn hình), câu trích, đoạn tầm nhìn, chức danh, tên, ảnh chữ ký | Câu trích ≤ 400 ký tự (không gõ dấu ngoặc kép). Chữ ký PNG/WebP nền trong, rộng từ 1200 px (không dùng SVG) |
| Cảnh kết | Tiêu đề, mô tả, ảnh nền, video nền (tuỳ chọn) | Có cả ảnh và video thì video được dùng. Video MP4 không tiếng ≤ 8 MB |
| Đối tác | Tiêu đề, bộ logo đối tác | Tối đa 16 logo PNG/WebP nền trong. **Tên đối tác lấy từ ô Văn bản thay thế (Alt) của ảnh**, hãy điền Alt cho từng logo |

Mẹo: ô ảnh/video có nút **Chọn** mở Thư viện phương tiện; trong bộ ảnh, mỗi ảnh có nút **Trước**, **Sau** (đổi thứ tự) và **Xoá**. Mọi ảnh có nội dung phải có **Văn bản thay thế (Alt)** để người dùng trình đọc màn hình nghe được; ảnh chỉ để trang trí thì để trống Alt.

## 4. Nội dung nhập ở nơi khác

### 4.1. Dịch vụ (section Dịch vụ và trang chi tiết liệu trình)
- **Dịch vụ › Nhóm dịch vụ**: mỗi nhóm có tên, mô tả (hiện làm câu dẫn của nhóm), **ảnh bìa** (dọc 4:5), video bìa (tuỳ chọn; **hiện trang chủ chưa phát video bìa**, chỉ dùng ảnh bìa), **ảnh trước điều trị**, **ảnh sau điều trị** (tối đa 6 ảnh mỗi ô) và **thứ tự hiển thị** (số nhỏ đứng trước; để trống thì đứng cuối).
  - Ảnh thứ *n* ở ô "trước" ghép cặp với ảnh thứ *n* ở ô "sau"; cặp thiếu một ảnh sẽ không hiện. Nhóm có ít nhất một cặp thì khung ảnh của nhóm hiện cặp so sánh (kéo thanh để xem). Chú thích của ảnh **sau** hiện dưới cặp ảnh. Chỉ dùng ảnh khách đã đồng ý bằng văn bản cho công khai.
- **Dịch vụ › Tất cả dịch vụ** (mỗi bài là một liệu trình): tên, nhóm, **Ảnh đại diện** (dọc 4:5, ≥ 1200×1500), **Mô tả ngắn**, **Thời gian liệu trình**, **Các bước liệu trình** (mỗi dòng một bước), **Ảnh liệu trình** (bộ ảnh, có Alt và Chú thích) và nội dung trong trình soạn thảo (tuỳ chọn). Các ô nhập này cần plugin **Advanced Custom Fields** đang bật.
- **Hiện trạng:** cả **17 liệu trình đã được xuất bản** và đang có **nội dung mẫu** (thời gian, mô tả ngắn, các bước, bài mô tả) do trợ lý soạn: hãy để bác sĩ đọc và sửa lại cho đúng chuyên môn, rồi thay bằng ảnh đại diện thật. Muốn ẩn một liệu trình, mở bài và chuyển về **Nháp**.
- Mọi phần thiếu tự ẩn: thiếu ảnh đại diện thì dùng ảnh bìa nhóm; thiếu cả hai thì hiện khối giữ chỗ trung tính.

### 4.2. Sự kiện
Site hiện có **5 sự kiện mẫu** (4 sắp tới, 1 đã diễn ra; sửa hoặc xoá như bài thường, nhớ thay bằng sự kiện thật).

**Sự kiện › Thêm mới**: tên, nội dung, **Ảnh đại diện** ngang 2.39:1 (2400×1004), **Ảnh dọc cho điện thoại** 4:5 (tuỳ chọn), **Bắt đầu** (ngày giờ theo múi giờ website), Kết thúc (tuỳ chọn), Địa điểm, **Link đăng ký** (để trống thì nút là "Xem chi tiết"), tick **Sự kiện nổi bật** để đưa lên trang chủ. Loại sự kiện chọn ở **Sự kiện › Loại sự kiện**. Sự kiện đã qua hiện đánh dấu "đã diễn ra".

### 4.3. Bài viết (Kiến thức làn da)
Site hiện có **5 bài viết mẫu** trong 3 chủ đề (sửa hoặc xoá được; nhớ để bác sĩ duyệt nội dung và thêm ảnh đại diện dọc 4:5).

**Kiến thức làn da › Thêm mới**: tiêu đề, tiêu đề phụ (tuỳ chọn), nội dung, **Ảnh đại diện** dọc 4:5, chủ đề (**Chủ đề**), tick **Bài nổi bật** để đứng đầu ở trang chủ. Thời gian đọc tự tính khi lưu.

### 4.4. Lời kể khách hàng
**Lời kể khách hàng › Thêm mới**: tiêu đề là **tên hiển thị** (tên người kể trong lời), ô **Lời kể** (≤ 300 ký tự) và **Liệu trình đã dùng** (chọn từ danh sách). Site hiện có 5 lời kể mẫu (sửa hoặc xoá được như bài thường). Không còn ô "Khách đã đồng ý công khai": nội dung là lời kể của doanh nghiệp, nên bài nào **Xuất bản** và có lời kể thì hiện ra trang chủ (bài Nháp thì không hiện).

### 4.5. Liên hệ và mạng xã hội
**ISHA Clinic** (menu bên trái, mục cài đặt chung): Hotline, Zalo URL (địa chỉ đầy đủ dạng `https://zalo.me/…`, không phải số điện thoại), Facebook, Instagram, TikTok, YouTube, địa chỉ, giờ làm việc, bản đồ. Thanh liên hệ nhanh lấy Hotline và Zalo từ đây; nút **Đặt lịch** dẫn tới trang đặt lịch. Ô nào trống thì nút tương ứng không hiện.

## 5. Bật hoặc tắt trang chủ mới cho khách (chế độ landing)

1. Vào **Giao diện › Tùy biến › ISHA – Giao diện › Chế độ landing**.
2. Ô **"Chỉ hiển thị Chính sách khách hàng ở trang chủ"**: **bỏ tick** để trang chủ mới hiển thị cho mọi người; **tick** để quay về trang chính sách. Bấm **Xuất bản**.
3. Mở trang chủ ở cửa sổ ẩn danh để kiểm; xoá cache nếu site có plugin cache. Chuyển qua lại không làm mất dữ liệu nào.

**Lưu ý khi quay lại trang Chính sách:** chế độ landing in *nội dung của trang chủ tĩnh*. Trang chủ tĩnh hiện là "Trang chủ" (ID 10, chỉ dùng để chứa các ô nhập của trang chủ mới, nội dung rỗng), còn nội dung chính sách nằm ở trang "Chính sách khách hàng thân thiết ISHA" (ID 7). Vì vậy muốn quay lại, ngoài việc tick ô trên bạn phải vào **Cài đặt › Đọc**, chọn "Trang chủ" = "Chính sách khách hàng thân thiết ISHA" (hoặc chép nội dung sang trang "Trang chủ"); nếu không, trang chủ hiện trắng. Khi mở lại trang chủ mới thì đặt "Trang chủ" về lại trang "Trang chủ".

**Khi đưa lên site chính thức:** bộ DB hiện tại đang để chế độ landing TẮT, nên ngay khi sao chép DB lên host, trang chủ mới sẽ hiện cho khách. Nếu chưa muốn, tick lại ô trên trước khi chuyển. Danh sách kiểm trước ra mắt ở `docs/home-handover.md`, mục "Trước khi ra mắt".

Khi chế độ landing tắt, menu và nút đặt lịch của header hiện theo cấu hình của site (Giao diện › Trình đơn).

## 6. Việc thường gặp

| Hiện tượng | Nguyên nhân thường gặp | Cách xử lý |
|---|---|---|
| Một section biến mất | Công tắc "Hiển thị section này" đang tắt, hoặc section chưa có dữ liệu (ví dụ chưa có sự kiện nổi bật, lời kể chưa tick đồng ý, chưa có bài nổi bật, nhóm dịch vụ không có liệu trình đã xuất bản) | Bật công tắc / bổ sung dữ liệu / xuất bản bài |
| Khối màu trơn thay cho ảnh | Chưa gán ảnh (khối giữ chỗ). Ở chế độ xem trước có chữ nhãn cho biết cần ảnh nào | Gán ảnh theo `docs/home-assets.md` |
| Video không phát | Quá dung lượng, không phải MP4 H.264, hoặc người xem bật "Tiết kiệm dữ liệu"/"Giảm chuyển động": trang tự giữ poster | Xuất lại video theo quy cách; kiểm poster đã có |
| Ảnh trước/sau không hiện | Cặp thiếu một ảnh, hoặc một ảnh của cặp đã bị xoá khỏi Thư viện (cặp đó bị bỏ, các cặp khác vẫn đúng) | Mở Sửa nhóm: cặp hỏng đã được bỏ khỏi cả hai ô (có dòng báo), bấm Cập nhật; rồi thêm lại cả ảnh trước lẫn ảnh sau của cặp đó ở đúng vị trí |
| Video bìa nhóm không chạy | Ô "Video bìa" của nhóm dịch vụ lưu được nhưng trang chủ chưa dùng video bìa (chỉ dùng ảnh bìa) | Dùng ảnh bìa; việc làm tiếp tính năng này là quyết định còn mở (`docs/home-handover.md`) |
| Sửa xong không thấy đổi | Cache trình duyệt hoặc plugin cache | Ctrl + F5; xoá cache plugin |
| Chữ bị cắt dòng/quá dài | Vượt giới hạn ký tự của ô | Rút gọn; giới hạn ghi ở mục 3 |

## 7. Dành cho người phát triển (tóm tắt)

- Mỗi section có một shortcode độc lập `[isha_home_<mã>]`: `hero`, `story`, `philosophy`, `services`, `values`, `process`, `space`, `events`, `articles`, `testimonials`, `vision`, `cta`, `partners`. Có thể đặt vào trang khác; tài nguyên chỉ nạp khi trang có dùng.
- Bộ lọc: `isha/scroll_top/enabled` (tắt nút Lên đầu trang), `isha_core/home_page_id` (chỉ định trang chủ khác). Danh sách đầy đủ ở `docs/home-handover.md`.
- Thêm hoặc bớt section: sửa registry `inc/home/registry.php`, lược đồ `home-schema.php` (ISHA Core), mặc định `inc/home/data.php`, template `template-parts/home/<mã>.php`, rồi khối CSS/JS cùng tên trong `home.css`/`home.js` (xem `docs/home-architecture.md`, mục 11.9).

## Ghi chú: mục Bình luận

Mục **Bình luận** đã được ẩn khỏi menu bên trái của wp-admin và khỏi thanh admin (website không dùng bình luận). Đây chỉ là ẩn menu; muốn hiện lại hoặc muốn tắt hẳn tính năng, xem `docs/home-handover.md` mục 5.
