# Hướng dẫn quản trị trang chủ ISHA (dành cho người nhập nội dung)

Cập nhật: 2026-11-17 (tái cấu trúc giai đoạn 1: CMS và trang chủ; trước đó: 2026-11-10, Giai đoạn 7, bàn giao). Tài liệu ngắn, theo từng việc bạn sẽ làm trong wp-admin. Danh sách ảnh/video cần chuẩn bị nằm ở `docs/home-assets.md`; thông tin kỹ thuật nằm ở `docs/home-architecture.md` (mục 15 cho đợt tái cấu trúc) và `docs/home-handover.md`.

## 1. Trang chủ hoạt động thế nào

- Trang chủ gồm **11 section** xếp cố định từ trên xuống: Hero (banner không gian ISHA), Không gian ISHA, Hiệu quả liệu trình, Dịch vụ tiêu biểu, Founder, Workshop & sự kiện, Tin tức, Lời kể khách hàng, Quy trình (**đang ẩn**), Đối tác, Cảnh kết. Kèm một thanh liên hệ nhanh (đặt lịch, Zalo, hotline; từ 20/11/2026 chỉ hiện trên điện thoại và máy tính bảng, máy tính dùng nút Đặt lịch trên header) và nút "Lên đầu trang".
- Từ 17/11/2026, **Câu chuyện, Triết lý, Giá trị** không còn ở trang chủ mà nằm ở **trang Giới thiệu** (mục 4.6) và **Tầm nhìn** được thay bằng **Founder**. Nội dung bạn đã nhập cho các section này được chép sang chỗ mới; bản cũ vẫn giữ nguyên trong hệ thống.
- **Section nào chưa có dữ liệu hoặc bị tắt thì tự biến mất**, không để lại khoảng trống. Vì vậy bạn không cần nhập đủ mọi thứ mới xem được trang.
- Ô nào để trống thì dùng **nội dung mặc định** (chữ mờ hiện sẵn trong ô). Muốn đổi thì gõ nội dung mới đè lên; muốn quay về mặc định thì xoá sạch ô.
- Công tắc **Chế độ landing** (Giao diện › Tùy biến › ISHA – Giao diện, xem mục 5) quyết định khách thấy gì ở trang chủ: **BẬT** = chỉ trang Chính sách khách hàng (mặc định của theme, giống bản cũ từng byte); **TẮT** = trang chủ mới hiển thị cho mọi người. **Trên site thử hiện tại công tắc đang TẮT**, tức trang chủ mới đang chạy thật (chưa có ảnh/video nên nhiều chỗ còn là khối giữ chỗ). Khi chế độ landing BẬT, bạn vẫn xem được trang chủ mới bằng đường dẫn xem trước (mục 2).

## 2. Xem trước trang chủ mới (kể cả khi chế độ landing đang bật)

1. Đăng nhập wp-admin bằng tài khoản có quyền sửa trang (Quản trị viên, Biên tập viên).
2. Mở trình duyệt, vào địa chỉ trang chủ kèm đuôi **`?isha_preview=home`**, ví dụ `https://tên-miền-của-bạn/?isha_preview=home` (trên máy thử: `http://localhost/ISHA_clinic/?isha_preview=home`).
3. Chế độ xem trước hiện **cả liệu trình đang ở trạng thái Nháp** (kèm nhãn nháp) và **nhãn của các khối giữ chỗ** (chỗ chưa có ảnh/video) để bạn biết cần bổ sung gì. Khách và người chưa đăng nhập **không** thấy gì khác với trang hiện tại; trang xem trước không được công cụ tìm kiếm lưu (noindex).
4. Sau mỗi lần lưu nội dung, tải lại trang xem trước (Ctrl + F5) để thấy thay đổi.

## 3. Sửa nội dung từng section

Bấm mục **Trang chủ** ở menu bên trái wp-admin (ngay dưới **Trang**), hoặc vào **Trang › Tất cả trang › "Trang chủ" › Sửa**. Khung **"Nội dung trang chủ"** có một thẻ (tab) cho mỗi section, đúng thứ tự trên trang. Đầu mỗi thẻ có công tắc **"Hiển thị section này"**: bỏ tick thì section ẩn khỏi trang (nội dung đã nhập vẫn được giữ). Bấm **Cập nhật** để lưu.

| Thẻ | Bạn nhập | Giới hạn / ghi chú |
|---|---|---|
| Hero và intro | Bật intro (chạy một lần mỗi phiên truy cập); câu intro; video ngang và dọc; poster ngang và dọc; lời định vị (tiêu đề h1); khẩu hiệu; mô tả; nhãn nút chính | Banner không gian ISHA: video ghép từ ảnh không gian của phòng khám. **Lời định vị** (tiếng Việt, ví dụ "Phòng khám chuyên khoa da liễu thẩm mỹ", ≤ 80 ký tự, nên khoảng 40 ký tự) là tiêu đề h1 lớn nằm trong vòng tròn; **khẩu hiệu** (ví dụ "Beyond beauty, within you", ≤ 120) là dòng nghiêng nhỏ dưới logo (từ 20/11/2026; để trống lời định vị thì khẩu hiệu làm tiêu đề). Câu intro ≤ 80 ký tự, mô tả ≤ 240 (đó là mức tối đa của ô). **Nên giữ lời định vị ≤ 40 ký tự (tối đa hai dòng) và mô tả ≤ 120 ký tự (hai dòng):** hero nằm trọn một màn hình, chữ dài hơn có thể chồng lên dòng nhỏ hoặc đẩy nút Đặt lịch xuống dưới mép màn hình ở laptop thấp (cao dưới khoảng 700 px). Tiêu đề tự nhỏ lại khi màn hình thấp. Video MP4 H.264 không tiếng (ngang 1920×1080, 8–15 giây, ≤ 8 MB; dọc 1080×1350, ≤ 3 MB). Poster JPG/WebP ≤ 250 KB là khung đầu của video. Video luôn lặp, không có nút phát/tạm dừng |
| Không gian ISHA | Tiêu đề, câu dẫn, chữ liên kết tới trang Không gian ISHA, ảnh dự phòng | Trên máy tính, section nằm **chồng lên màn cuối của banner**: cuộn hết banner thì rèm lá dọc mở ra để hiện ảnh không gian, cuộn ngược lên thì rèm đóng, trả lại banner (từ 21/11/2026). Trên điện thoại, section đứng ngay sau banner. Ảnh tự chuyển mỗi 12 giây. **Ảnh trong section do ô "Xuất hiện bài này ở trang chủ" của từng bài Không gian quyết định** (mục 4.8): mỗi bài được đánh dấu hiện ảnh đại diện và tên bài, bấm vào mở bài; tối đa 12 bài theo ô Thứ tự. Ô **Ảnh dự phòng** chỉ dùng khi chưa có bài nào được đánh dấu (6–10 ảnh, cạnh dài từ 2000 px; chú thích lấy từ ô **Chú thích** trong Thư viện phương tiện). Dưới dải ảnh là liên kết tới trang Không gian ISHA |
| Hiệu quả liệu trình | Tiêu đề, câu dẫn, **bộ ảnh trước và sau (đã ghép)**, ghi chú dưới ảnh | **Tối đa 5 ảnh.** Mỗi ảnh là **một tấm đã ghép sẵn** hai hình trước và sau (không ghép cặp trong hệ thống). Mọi ảnh cùng một tỉ lệ, khuyến nghị ngang 3:2 (ví dụ 2400×1600), JPG/WebP ≤ 400 KB; nên ghép sẵn nhãn "Trước"/"Sau" trong ảnh. Điền **Văn bản thay thế (Alt)** cho từng ảnh (ví dụ "Trước và sau liệu trình điều trị mụn"); **Chú thích** của ảnh hiện dưới ảnh. Ảnh đổi lần lượt mỗi 5 giây. Chưa có ảnh nào thì section ẩn. Ghi chú ≤ 160 ký tự |
| Dịch vụ tiêu biểu | Tiêu đề, câu dẫn, nhãn liên kết tới trang Dịch vụ | **Nhóm nào hiện ở đây do ô tick "Dịch vụ tiêu biểu" trong màn Sửa nhóm dịch vụ quyết định** (mục 4.1). Chưa nhóm nào được tick thì section ẩn. Liên kết "Xem tất cả dịch vụ" dẫn tới trang Dịch vụ |
| Founder | Tiêu đề, ảnh chân dung, câu trích, câu chuyện, tên, chức danh, cụm ảnh gallery | Bên trái là **thư viện ảnh 3D**: khách lăn chuột, kéo hoặc vuốt ngang để đi qua từng ảnh theo đúng thứ tự trong gallery; mỗi lúc trên khung chỉ có tối đa 3 ảnh; hết ảnh thì **ảnh chân dung** hiện ở giữa (khung viền đồng có vệt ánh kim, vầng sáng ấm phía sau) kèm **chức danh** và **tên**, quanh ảnh có bụi sáng vàng. **Ảnh chân dung nên là ảnh đã tách nền** (PNG hoặc WebP nền trong suốt, chủ thể sát mép dưới). Cả section nền tối #141414; cuối cột chữ có nút "Đặt lịch tư vấn riêng cùng Bác sĩ" mở popup đặt lịch. Khi rê chuột vào khung gallery, lăn chuột chỉ điều khiển gallery (cuộn trang bằng chuột thì đưa con trỏ ra ngoài khung). Bấm vào ảnh mở ảnh gốc cỡ lớn. Bên phải là tiêu đề, câu trích (≤ 300 ký tự, không gõ dấu ngoặc kép) và câu chuyện (≤ 1200 ký tự, dòng trống để tách đoạn). Gallery tối đa 20 ảnh, chọn nhiều ảnh một lần; dọc, ngang hay vuông đều được, không giới hạn kích thước (nên JPG/WebP). Ảnh có tỉ lệ ngoài khoảng 2:3 đến 16:9 bị cắt bớt mép: giữ chủ thể ở giữa ảnh. **Hãy nhập Văn bản thay thế (Alt) cho từng ảnh**: trống thì trình đọc màn hình chỉ đọc "Ảnh 3 trong thư viện của nhà sáng lập". Chân dung dọc 4:5, tối thiểu 1200×1500. Chưa có ảnh gallery thì khung trái chỉ hiện chân dung. Khách bật giảm chuyển động hoặc máy không có WebGL thì thấy chân dung và lưới ảnh tĩnh. |
| Workshop & sự kiện | Tiêu đề, câu dẫn, nhãn liên kết "Xem tất cả" | Sự kiện nhập ở mục 4.2. Trang chủ hiện **3 sự kiện**: sự kiện tick **Nổi bật** đứng trước, thiếu thì bổ sung bằng sự kiện mới nhất. Bộ lọc theo Loại sự kiện hiện khi có từ 2 loại, mỗi loại cũng hiện tối đa 3. Nút "Xem tất cả" (nằm dưới ảnh bên trái) dẫn tới trang Danh sách sự kiện |
| Tin tức | Dòng nhỏ trên tiêu đề (mặc định "ISHA Journal"), tiêu đề, nhãn liên kết "Xem tất cả" | Bài nhập ở mục 4.3. Section nền tối #141414 như Founder: **1 bài nổi bật** (ảnh ngang bên trái, chữ bên phải) rồi **3 bài phụ có ảnh**. Bài tick **Nổi bật** đứng đầu và dùng **Ảnh banner** 16:10 của bài (chưa có thì dùng Ảnh đại diện, cắt phần giữa); 3 bài phụ dùng Ảnh đại diện. Ở bài nổi bật chỉ nút "Đọc bài viết" mở bài; mỗi bài phụ bấm vào đâu trên thẻ cũng mở bài. Liên kết "Xem tất cả" dẫn tới trang Tin tức |
| Lời kể khách hàng | Dòng nhỏ trên tiêu đề (mặc định "Cảm nhận khách hàng"), tiêu đề | Lời kể nhập ở mục 4.4. Trang chủ hiện **tối đa 20** lời kể đã **Xuất bản** và có nội dung (mới nhất trước), không còn ô "đồng ý công khai". Máy tính: từ 6 lời kể trở lên thì chia thành **hai dãy thẻ trôi ngược chiều** (dãy trên nhận nửa đầu, nhiều hơn một thẻ khi tổng số lẻ); rê chuột vào một thẻ thì cả hai dãy dừng và thẻ đó nổi lên. Điện thoại: một hàng vuốt ngang kèm thanh tiến trình "01 / 20". Ảnh tròn ở chân thẻ là **Ảnh chân dung** của lời kể; không có ảnh thì chân thẻ chỉ hiện tên và liệu trình (không còn vòng tròn chữ cái đầu). Rê chuột vào một thẻ thì thẻ đó đảo sang nền tối #141414, các thẻ khác mờ đi |
| Quy trình | Tiêu đề; năm bước (tên, mô tả) | **Đang ẩn** (công tắc "Hiển thị section này" đã bỏ tick theo yêu cầu); nội dung vẫn giữ để bật lại khi cần. Mô tả ≤ 240 ký tự |
| Đối tác | Tiêu đề, bộ logo đối tác | Tối đa 16 logo PNG/WebP nền trong. **Tên đối tác lấy từ ô Văn bản thay thế (Alt) của ảnh**, hãy điền Alt cho từng logo. Logo hiện trong các huy hiệu tròn xếp thành **3 vòng quanh họa tiết tia mặt trời** vàng ánh kim (từ 9 logo là 3 vòng, 4 đến 8 logo là 2 vòng, ít hơn là 1 vòng); thứ tự trong bộ ảnh đi từ vòng trong ra vòng ngoài. Tiêu đề nằm bên trái như các section khác. Nên dùng 12 hoặc 15 logo để các vòng đều nhau; số lẻ khác thì huy hiệu tự nhỏ lại một chút |
| Cảnh kết | Tiêu đề, mô tả, ảnh nền, video nền (tuỳ chọn) | Có cả ảnh và video thì video được dùng. Video MP4 không tiếng ≤ 8 MB. Cảnh kết cũng hiện ở cuối trang chi tiết liệu trình |

Mẹo: ô ảnh/video có nút **Chọn** mở Thư viện phương tiện; trong bộ ảnh, mỗi ảnh có nút **Trước**, **Sau** (đổi thứ tự) và **Xoá**. Mọi ảnh có nội dung phải có **Văn bản thay thế (Alt)** để người dùng trình đọc màn hình nghe được; ảnh chỉ để trang trí thì để trống Alt.

## 4. Nội dung nhập ở nơi khác

### 4.1. Dịch vụ (section Dịch vụ tiêu biểu, trang nhóm và trang chi tiết liệu trình)
- **Dịch vụ › Nhóm dịch vụ**: mỗi nhóm có ô tick **"Dịch vụ tiêu biểu"** (tick thì nhóm hiện ở section Dịch vụ tiêu biểu của trang chủ; danh sách nhóm có cột **Tiêu biểu** để xem nhanh), tên, mô tả (hiện làm câu dẫn của nhóm), **ảnh bìa** (dọc 4:5), video bìa (tuỳ chọn; **hiện trang chủ chưa phát video bìa**, chỉ dùng ảnh bìa), nội dung giới thiệu, nhóm **Hiệu quả liệu trình** (mô tả ngắn, ảnh trước điều trị, ảnh sau điều trị) và **thứ tự hiển thị** (số nhỏ đứng trước; để trống thì đứng cuối).
  - Ngày 17/11/2026 cả 6 nhóm đã được tick "Dịch vụ tiêu biểu" để trang chủ giữ như trước; **bỏ tick những nhóm không muốn hiện**.
  - Ảnh trước/sau của nhóm chỉ hiện ở **trang nhóm dịch vụ** (`/nhom-dich-vu/…`), không còn hiện ở trang chủ (trang chủ dùng bộ ảnh riêng ở thẻ **Hiệu quả liệu trình**, mục 3). Ảnh thứ *n* ở ô "trước" ghép cặp với ảnh thứ *n* ở ô "sau"; cặp thiếu một ảnh sẽ không hiện. Chỉ dùng ảnh khách đã đồng ý bằng văn bản cho công khai.
- **Dịch vụ › Tất cả dịch vụ** (mỗi bài là một liệu trình): tên, nhóm, **Ảnh đại diện** (dọc 4:5, ≥ 1200×1500), **Mô tả ngắn**, **Thời gian liệu trình**, **Các bước liệu trình** (mỗi dòng một bước), **Ảnh liệu trình** (bộ ảnh, có Alt và Chú thích) và nội dung trong trình soạn thảo (tuỳ chọn). Các ô nhập này cần plugin **Advanced Custom Fields** đang bật.
- **Hiện trạng:** cả **17 liệu trình đã được xuất bản** và đang có **nội dung mẫu** do trợ lý soạn: thời gian, mô tả ngắn, các bước liệu trình (5 đến 6 bước) và bài mô tả gồm giới thiệu, **Phù hợp với**, **Hiệu quả mong đợi**, **Lưu ý** (bổ sung ngày 18/11/2026). Hãy để bác sĩ đọc và sửa lại cho đúng chuyên môn, rồi thay bằng ảnh đại diện thật. Muốn ẩn một liệu trình, mở bài và chuyển về **Nháp**.
- Mọi phần thiếu tự ẩn: thiếu ảnh đại diện thì dùng ảnh bìa nhóm; thiếu cả hai thì hiện khối giữ chỗ trung tính.

### 4.2. Sự kiện
Site hiện có **5 sự kiện mẫu** (sửa hoặc xoá như bài thường, nhớ thay bằng sự kiện thật). Trang chủ hiện 3 sự kiện; từng loại sự kiện cũng có bộ 3 riêng khi khách bấm tab lọc.

**Sự kiện › Thêm mới**: tên, nội dung, **Ảnh đại diện** (ảnh duy nhất dùng ở trang chủ; nên ngang 2.39:1, 2400×1004, giữ chủ thể ở giữa vì ảnh bị cắt vào khung 4:5 trên máy tính), **Bắt đầu** (ngày giờ theo múi giờ website; ngày này hiện thành số lớn kiểu thiệp mời), Kết thúc (tuỳ chọn), Địa điểm, tick **Sự kiện nổi bật** để được ưu tiên đưa lên trang chủ. Loại sự kiện chọn ở **Sự kiện › Loại sự kiện** (mỗi loại là một tab lọc). Ở trang chủ mỗi sự kiện chỉ dẫn sang trang chi tiết (nút đăng ký nằm ở trang chi tiết); không còn nhãn "đã diễn ra" ở trang chủ. Ảnh dọc cho điện thoại không còn dùng ở trang chủ.

### 4.3. Tin tức (trước 17/11/2026 tên menu là "Kiến thức làn da")
Site hiện có **5 bài viết mẫu** trong 3 chủ đề (sửa hoặc xoá được; nhớ để bác sĩ duyệt nội dung và thêm ảnh đại diện dọc 4:5).

**Tin tức › Thêm mới**: tiêu đề, tiêu đề phụ (tuỳ chọn), nội dung, **Ảnh đại diện** dọc 4:5, chủ đề (**Chủ đề**), tick **Bài nổi bật** để đứng đầu ở trang chủ. Thời gian đọc tự tính khi lưu.

Hộp **Thông tin bài viết** (dưới nội dung bài) gồm **Nội dung ngắn**, **Bài nổi bật** và **Ảnh banner** (từ 20/11/2026): ảnh ngang tỉ lệ 16:10, khuyến nghị 1600 × 1000 px, dùng khi bài đứng đầu section Tin tức trên trang chủ. Nên tải ảnh banner mới lên (WordPress chỉ tạo bản cắt 16:10 cho ảnh tải lên từ ngày này; chọn ảnh cũ trong thư viện vẫn hiện được nhưng không được cắt sẵn). Hộp này luôn nằm dưới nội dung, không kéo sang thanh bên được (trước đây hộp bị kéo sang thanh bên và thu gọn nên vùng dưới nội dung trống).

**Đường dẫn (từ 18/11/2026):** bài nằm dưới trang Tin tức, dạng `/tin-tuc/ten-bai/`. Đường dẫn cũ `/bai-viet/…` tự chuyển (301) sang đường dẫn mới, nên liên kết đã chia sẻ trước đây vẫn mở được. Bấm vào một chủ đề sẽ mở trang Tin tức đã lọc theo chủ đề đó (trước là trang lưu trữ `/chu-de/…`, nay cũng tự chuyển).

### 4.4. Lời kể khách hàng
**Lời kể khách hàng › Thêm mới**: tiêu đề là **tên hiển thị** (tên người kể trong lời), ô **Lời kể** (≤ 300 ký tự) và **Liệu trình đã dùng** (chọn từ danh sách). Site hiện có 20 lời kể mẫu (5 lời kể gốc và 15 lời kể bổ sung ngày 20/11/2026; sửa hoặc xoá được như bài thường). Lời kể nên dài 150 đến 250 ký tự để các thẻ cao đều nhau. Không còn ô "Khách đã đồng ý công khai": nội dung là lời kể của doanh nghiệp, nên bài nào **Xuất bản** và có lời kể thì hiện ra trang chủ (bài Nháp thì không hiện).

### 4.5. Liên hệ và mạng xã hội
**ISHA Clinic** (menu bên trái, mục cài đặt chung): Hotline, Zalo URL (địa chỉ đầy đủ dạng `https://zalo.me/…`, không phải số điện thoại), Facebook, Instagram, TikTok, YouTube, địa chỉ, giờ làm việc, bản đồ, **Email nhận thông báo** (nơi nhận mail khi có lịch hẹn hoặc lời nhắn mới; để trống thì gửi email quản trị). Thanh liên hệ nhanh, popup đặt lịch, trang Liên hệ và trang Đặt lịch hẹn đều lấy thông tin từ đây. Ô nào trống thì phần tương ứng không hiện. Địa chỉ đã chốt ngày 18/11/2026: **77-79 Nguyễn Văn Cừ, P. Cái Khế, Cần Thơ**; ô Bản đồ là link nhúng Google Maps theo đúng địa chỉ này. Đổi địa chỉ thì đổi cả hai ô.

### 4.6. Trang Giới thiệu (`/gioi-thieu/`, từ 17/11/2026)
Bấm mục **Giới thiệu** ở menu bên trái wp-admin (ngay dưới **Trang chủ**). Trang do theme dựng (mẫu trang "Giới thiệu"), không dùng trình soạn thảo của trang:
- **Tiêu đề trang** là tiêu đề lớn của banner đầu trang.
- Khung **Hero banner**: mô tả ngắn dưới tiêu đề (≤ 280 ký tự; để trống thì dùng câu mặc định), ảnh nền màn hình rộng (ngang, rộng ≥ 2400 px) và điện thoại (dọc), video nền (tuỳ chọn; có video thì ảnh làm hình chờ). Chưa có ảnh/video thì banner là nền tối có vòng tròn mặt trời vàng kim.
- Khung **Nội dung trang Giới thiệu**: ba section theo thứ tự **Câu chuyện thương hiệu**, **Triết lý và sứ mệnh**, **Giá trị cốt lõi**. Mỗi section có công tắc "Hiển thị section này", **Tiêu đề** (để trống thì dùng tên section), **Tiêu đề nhỏ** (một câu, hiện chữ nghiêng có vạch vàng, ≤ 200 ký tự), **Nội dung** (trình soạn thảo: đoạn văn, tiêu đề phụ, đậm, nghiêng, danh sách, trích dẫn, liên kết) và **Ảnh minh hoạ** (dọc 4:5, ≥ 1200×1500; tuỳ chọn, để trống thì section chỉ có chữ). Ảnh tự xen kẽ trái, phải.
- Mẹo danh sách: trong mỗi dòng danh sách, phần **chữ đậm ở đầu dòng** hiện thành tên mục (cỡ lớn, riêng một dòng), phần *chữ nghiêng* ngay sau hiện thành dòng phụ; ví dụ **Chăm sóc da chuẩn y khoa** rồi mô tả.
- **Hiện trạng:** nội dung ba section đã được chép từ Câu chuyện, Triết lý, Giá trị và Tầm nhìn cũ của trang chủ; tiêu đề nhỏ của Câu chuyện và Giá trị do trợ lý đặt tạm; ảnh là ảnh tạm lấy từ bộ ảnh Giá trị cũ. Hãy thay bằng nội dung và ảnh chính thức.
- Mục **Giới thiệu** đứng thứ hai ở menu chính của website (đổi vị trí ở **Giao diện › Trình đơn**).

### 4.7. Đặt lịch, Liên hệ và hộp thư (từ 18/11/2026)
- **Popup Đặt lịch:** mọi nút "Đặt lịch" trên website (header, trang chủ, liệu trình, sự kiện, trang Elementor…) mở một cửa sổ đặt lịch ngay trên trang; nút ở trang liệu trình chọn sẵn liệu trình đó. Trang **Đặt lịch hẹn** vẫn còn, dùng khi khách mở liên kết ở thẻ mới hoặc trình duyệt không chạy JavaScript.
- **Trang Liên hệ:** thông tin liên hệ, form gửi lời nhắn và bản đồ. Lời dẫn dưới tiêu đề là nội dung trang (sửa trong trình soạn thảo của trang Liên hệ, tương tự với trang Đặt lịch hẹn).
- **Hộp thư:** menu **Lịch hẹn** (yêu cầu đặt lịch) và **Liên hệ** (lời nhắn) có bong bóng số mục **Mới**. Mở một mục để xem chi tiết (kể cả thời điểm khách đồng ý xử lý dữ liệu), bấm **Gọi lại** hoặc **Trả lời email**, rồi đổi **Trạng thái** (Lịch hẹn: Mới, Đã xác nhận, Đã hoàn thành, Đã huỷ; Liên hệ: Mới, Đã liên hệ lại, Đã xong) và bấm Cập nhật.
- Khung giờ đặt lịch: 09:00 đến 18:00, mỗi 30 phút (theo giờ mở cửa 09:00 – 19:00).
- Mỗi form có ô **đồng ý xử lý dữ liệu cá nhân** (bắt buộc) dẫn tới trang **Chính sách bảo mật** (Cài đặt › Quyền riêng tư). Nội dung trang này là bản soạn mẫu, cần pháp chế duyệt.
- **Email thông báo:** website đã gửi mail cho mỗi lịch hẹn và lời nhắn mới, nhưng cần cấu hình SMTP trên host (làm sau bàn giao) thì mail mới tới hộp thư ổn định. Dù mail chưa đi, dữ liệu vẫn nằm trong hộp thư ở wp-admin.

### 4.8. Không gian ISHA (`/khong-gian-isha/`, từ 18/11/2026)
Bấm mục **Không gian ISHA** ở menu bên trái wp-admin (ngay dưới **Giới thiệu**). Mỗi bài là một khu của phòng khám (sảnh, phòng trị liệu, phòng VIP…):
- **Tiêu đề** là tên không gian; **nội dung** soạn trong trình soạn thảo (đoạn văn, tiêu đề phụ, danh sách) hiện ở trang của bài.
- **Ảnh đại diện** (dọc 4:5, ≥ 1200×1500): hiện ở trang chủ, trang Không gian ISHA và khối giới thiệu trong trang của bài. Chưa đặt thì dùng ảnh đầu tiên của bộ ảnh.
- Khung **Thông tin không gian**: ô **"Xuất hiện bài này ở trang chủ"** (tick thì bài hiện ở section Không gian ISHA của trang chủ), **Tiêu đề nhỏ** (một câu, chữ nghiêng, ≤ 120 ký tự), **Mô tả ngắn** (2–3 câu, ≤ 400 ký tự, hiện ở trang Không gian ISHA), **Ảnh không gian** (3–12 ảnh, cạnh dài từ 2000 px, cả dọc và ngang; điền **Chú thích** và **Văn bản thay thế** trong Thư viện phương tiện). Ảnh ngang rộng từ 1600 px đầu tiên trong bộ ảnh làm nền cho đầu trang của bài.
- Ô **Thứ tự** (khung Thứ tự hiển thị ở thanh bên) sắp xếp bài ở trang Không gian ISHA, ở trang chủ và ở khối "Không gian khác": số nhỏ đứng trước. Danh sách bài có cột **Trang chủ** và **Thứ tự** để xem nhanh.
- **Trang Không gian ISHA** (Trang › "Không gian ISHA", mẫu trang "Không gian ISHA"): tiêu đề trang và khung **Hero banner** (mô tả, ảnh hoặc video nền) như trang Giới thiệu; nội dung soạn trong trang không hiển thị. Các bài tự xếp bên dưới, mỗi bài một khối có ảnh, tên, tiêu đề nhỏ, mô tả ngắn và liên kết "Khám phá không gian".
- **Hiện trạng:** 5 bài mẫu (Sảnh đón tiếp, Phòng trị liệu đôi, Phòng VIP riêng tư, Khu điều trị công nghệ cao, Phòng thư giãn sau liệu trình), 4 bài hiện ở trang chủ. Nội dung do trợ lý soạn và ảnh là ảnh tạm trong Thư viện: **hãy thay bằng mô tả và ảnh chụp thật của phòng khám**.

### 4.9. Trang Dịch vụ (`/dich-vu/`)
Thứ tự từ trên xuống: banner nan quạt (khung **Hero banner** của trang), phần soạn bằng **Elementor**, khối **"Các nhóm dịch vụ"**, khối mời đặt lịch. Khối "Các nhóm dịch vụ" tự lấy mọi nhóm ở **Dịch vụ › Nhóm dịch vụ** theo thứ tự hiển thị: ảnh bìa, tên, mô tả của nhóm và số liệu trình; bấm vào mở trang nhóm. Không cần dựng lại khối này trong Elementor.

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
| Một section biến mất | Công tắc "Hiển thị section này" đang tắt, hoặc section chưa có dữ liệu (ví dụ chưa có sự kiện nổi bật, chưa có bài viết nào, chưa có lời kể đã xuất bản, chưa nhóm dịch vụ nào được tick "Dịch vụ tiêu biểu", chưa có ảnh ở thẻ Hiệu quả liệu trình) | Bật công tắc / bổ sung dữ liệu / xuất bản bài |
| Section Hiệu quả liệu trình không hiện | Chưa có ảnh nào ở thẻ Hiệu quả liệu trình (mục 3) | Thêm 1 đến 5 ảnh đã ghép trước/sau |
| Khối màu trơn thay cho ảnh | Chưa gán ảnh (khối giữ chỗ). Ở chế độ xem trước có chữ nhãn cho biết cần ảnh nào | Gán ảnh theo `docs/home-assets.md` |
| Video không phát | Quá dung lượng, không phải MP4 H.264, hoặc người xem bật "Tiết kiệm dữ liệu"/"Giảm chuyển động": trang tự giữ poster | Xuất lại video theo quy cách; kiểm poster đã có |
| Ảnh trước/sau ở trang nhóm dịch vụ không hiện | Cặp thiếu một ảnh, hoặc một ảnh của cặp đã bị xoá khỏi Thư viện (cặp đó bị bỏ, các cặp khác vẫn đúng) | Mở Sửa nhóm: cặp hỏng đã được bỏ khỏi cả hai ô (có dòng báo), bấm Cập nhật; rồi thêm lại cả ảnh trước lẫn ảnh sau của cặp đó ở đúng vị trí |
| Video bìa nhóm không chạy | Ô "Video bìa" của nhóm dịch vụ lưu được nhưng trang chủ chưa dùng video bìa (chỉ dùng ảnh bìa) | Dùng ảnh bìa; việc làm tiếp tính năng này là quyết định còn mở (`docs/home-handover.md`) |
| Sửa xong không thấy đổi | Cache trình duyệt hoặc plugin cache | Ctrl + F5; xoá cache plugin |
| Chữ bị cắt dòng/quá dài | Vượt giới hạn ký tự của ô | Rút gọn; giới hạn ghi ở mục 3 |

## 7. Dành cho người phát triển (tóm tắt)

- Trang Không gian ISHA, bài Không gian và trang Giới thiệu dùng chung hero và section ở `template-parts/about/hero.php`, `section.php`. Đường dẫn cũ của Tin tức chuyển 301 ở `class-legacy-urls.php` (ISHA Core). Chi tiết ở `docs/home-architecture.md`, mục 18.
- Mỗi section có một shortcode độc lập `[isha_home_<mã>]` (gạch ngang trong mã đổi thành gạch dưới): `hero`, `space`, `results`, `services`, `founder_story`, `events`, `articles`, `testimonials`, `process`, `partners`, `cta`. Có thể đặt vào trang khác; tài nguyên chỉ nạp khi trang có dùng.
- Bộ lọc: `isha/scroll_top/enabled` (tắt nút Lên đầu trang), `isha_core/home_page_id` (chỉ định trang chủ khác). Danh sách đầy đủ ở `docs/home-handover.md`.
- Thêm hoặc bớt section: sửa registry `inc/home/registry.php`, lược đồ `home-schema.php` (ISHA Core), mặc định `inc/home/data.php`, template `template-parts/home/<mã>.php`, rồi khối CSS/JS cùng tên trong `home.css`/`home.js` (xem `docs/home-architecture.md`, mục 11.4 và 15).

## Ghi chú: mục Bình luận

Mục **Bình luận** đã được ẩn khỏi menu bên trái của wp-admin và khỏi thanh admin (website không dùng bình luận). Đây chỉ là ẩn menu; muốn hiện lại hoặc muốn tắt hẳn tính năng, xem `docs/home-handover.md` mục 5.
