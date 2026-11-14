# Báo cáo Giai đoạn 5: sáu section tầng nhẹ

| | |
| --- | --- |
| Ngày lập | 2026-11-10 |
| Trạng thái | Dựng xong, kiểm tra nhẹ như bạn yêu cầu. Cả 13 section của trang chủ đã có chuyển động hoặc trạng thái cuối cùng. Chờ bạn duyệt báo cáo Giai đoạn 4 và 5, rồi đợt chỉnh sửa theo ý bạn trong một tiến trình. |
| Xem ngay | http://localhost/ISHA_clinic/ (trên site thật hiện thấy Giá trị và Quy trình; các section còn lại chờ dữ liệu, xem mục 2) |
| Tài liệu kỹ thuật | `docs/home-architecture.md` mục 10 |
| Ảnh chụp | `docs/phase5-shots/` (18 ảnh) |
| Sao lưu | `C:\xampp\isha-backups\2026-11-10_after-phase5\` |

## 1. Kết luận ngắn

1. **Năm section đã có chuyển động, section thứ sáu giữ nguyên:**
   - **Giá trị:** năm khung dọc trượt vào từ bên phải như cuộn phim khi section hiện ra; rê chuột, focus hoặc chạm vào một khung thì khung đó "mở": ảnh nền sáng lên, hiện mô tả, có đường kẻ Gold ở đỉnh.
   - **Quy trình:** đường kẻ ngang tự vẽ theo cuộn; mỗi mốc sáng lên (chấm Gold, chữ đậm hẳn) khi đường kẻ chạm tới. Trên điện thoại đường kẻ chạy theo vị trí vuốt hàng.
   - **Sự kiện:** băng chuyền một thẻ mỗi lần, mờ chéo 300ms, chữ dịch vào 8px, tự chạy mỗi 7 giây theo thanh tiến trình dọc; có nút Tạm dừng, Trước, Sau và vạch chọn thẻ.
   - **Lời kể:** băng chuyền tự chạy mỗi 8 giây; từng dòng lời kể hiện lần lượt như phụ đề.
   - **Đối tác:** logo hiện lần lượt khi lưới vào khung nhìn, không chạy ngang.
   - **Bài viết:** không thay đổi. Hiệu ứng rê chuột (đường gạch chạy dưới tiêu đề, ảnh phóng 1,03) đã có từ Giai đoạn 2 và giữ nguyên.
2. **Băng chuyền theo mẫu WAI-ARIA APG:** nút Tạm dừng đứng đầu thứ tự Tab; tự chạy dừng khi rê chuột, khi focus trong băng chuyền, khi ra khỏi khung nhìn, khi tab ẩn và khi bật giảm chuyển động; chỉ một thẻ hiện, các thẻ còn lại bị ẩn khỏi trình đọc màn hình và không focus được; người dùng bấm Tạm dừng thì không tự chạy lại. Điện thoại: vuốt ngang để đổi thẻ.
3. **Đã rút 10,3 KB chú thích** (bạn duyệt khoảng 7 KB) để đủ ngân sách: mã chạy y hệt (đã kiểm bằng cách so chuỗi token trước và sau, phải giống hệt). JS cuối Giai đoạn 5: **88.187 byte, còn 1.813 byte** so với 90.000.
4. **Không có thay đổi nào cần bạn thao tác trong wp-admin.** Tôi chưa thử được với dữ liệu thật vì site chưa có sự kiện, bài viết, lời kể (có đồng ý công khai) hoặc gallery đối tác; ảnh chụp dùng trang mẫu đủ dữ liệu.
5. **Có 6 điểm cần bạn xem và quyết** ở mục 6.

## 2. Cách xem

| Muốn xem | Làm thế nào |
| --- | --- |
| Giá trị, Quy trình | Mở trang chủ và cuộn xuống sau Dịch vụ. Quy trình vẽ hết đường kẻ khi section ở giữa màn hình. |
| Sự kiện | Cần ít nhất 2 sự kiện (mục Sự kiện trong wp-admin, đánh dấu nổi bật). Một sự kiện thì vẫn là thẻ đơn, không có điều khiển. Ảnh `01` đến `04`. |
| Bài viết | Cần bài `isha_article` (hiện chưa có nên section ẩn). Xem thử ở trang mẫu. |
| Lời kể | Cần ít nhất 2 lời kể có đồng ý công khai. Ảnh `05` (phụ đề đang hiện dòng đầu), `06`, `07`. |
| Đối tác | Cần ảnh trong gallery Đối tác (nền trong suốt, ảnh logo sẽ được ép về màu sáng). Ảnh `15`, `16`. |
| Giảm chuyển động | Bật "giảm chuyển động" của hệ điều hành rồi tải lại: Giá trị hiện đủ mô tả, Quy trình và Đối tác tĩnh, Sự kiện và Lời kể vẫn là băng chuyền nhưng không tự chạy (ảnh `17`, `18`). |
| Điện thoại | DevTools, chế độ thiết bị 390 x 844 (ảnh `04`, `07`, `10`, `13`, `14`). |

## 3. Đã làm gì

| Section | Desktop | Điện thoại / giảm chuyển động |
| --- | --- | --- |
| Giá trị | Khung đầu được chọn sẵn; chọn bằng rê chuột (chỉ chuột), focus bàn phím, chạm; `aria-expanded` đúng; mô tả khung khác ẩn nhưng vẫn trong DOM. Trượt vào so le 40ms. | Hàng vuốt ngang, mọi khung hiện đủ tên và mô tả, không cần chọn. Giảm chuyển động: như vậy ở mọi cỡ. |
| Quy trình | Đường kẻ vẽ theo cuộn, các bước nối thành một đường liền; mốc và chữ sáng khi đường kẻ tới (chữ chưa sáng ở 0,75). | Đường kẻ chạy theo mép phải của hàng vuốt. Giảm chuyển động: đường kẻ Gold liền và đủ chữ như Giai đoạn 2. |
| Sự kiện | Băng chuyền 7 giây; điều khiển là viên nang nền đặc ở góc trên phải thẻ (Tạm dừng, mũi tên, 3 vạch dọc có thanh tiến trình). | Thẻ 4:5, vuốt ngang đổi thẻ, vuốt dọc vẫn cuộn trang. Giảm chuyển động: không tự chạy, vẫn bấm được. |
| Lời kể | Băng chuyền 8 giây; từng dòng hiện cách nhau 120ms; thanh tiến trình ngang ở vạch hiện tại. | Điều khiển chia hai hàng cân đối (vạch trên, Trước, Tạm dừng, Sau dưới). Giảm chuyển động: không tự chạy, chữ hiện ngay. |
| Đối tác | Logo hiện bằng opacity, so le 40ms, tối đa 6 nhóm (chậm nhất 0,2 giây). | Như desktop. Giảm chuyển động: hiện sẵn. |
| Bài viết | Không đổi. | Không đổi. |

Mã đã thêm: khối `values`, `process`, `events`, `testimonials`, `partners` của `home.js` và `home.css`; hàm dùng chung `carousel()` ở vỏ `home.js`; ba chuỗi cấu hình mới (`carousel`, `slide`, `slideOf`) trong `inc/home/assets.php`; hai nút Trước/Sau của Sự kiện chuyển chữ vào `.isha-sr` (chỉ hiện mũi tên). Chi tiết ở `docs/home-architecture.md` mục 10.

## 4. Chỗ khác với đặc tả (có chủ ý)

1. **Giá trị: ảnh sáng lên đến 0,5 chứ không phải 0,6.** Ca xấu nhất (ảnh đen hoàn toàn) làm chữ Shadow không đạt tương phản khi ảnh sáng tới 0,6 (lớp phủ 0,4): chỉ 2,4:1; ở 0,5 là 3,3:1. Tôi chọn 0,5 và cho phần mô tả nằm trên một tấm nền đặc màu của tông ở chân khung (12,3:1 trên mọi ảnh). Tên khung là chữ lớn nên 3,3:1 đạt ngưỡng chữ lớn. Muốn đúng 0,6, cần đổi sang tấm nền cho cả tên khung (hình ảnh thành dải ngăn); nói tôi nếu bạn muốn.
2. **Quy trình: chữ chưa sáng ở 0,75, không phải 0,5.** Theo quy tắc đã chốt từ Giai đoạn 2 (mọi chữ giữ tương phản đủ): 0,5 chỉ còn khoảng 3:1, 0,75 còn khoảng 5,6:1.
3. **Giảm chuyển động: Sự kiện và Lời kể vẫn là băng chuyền (không tự chạy)** thay vì danh sách tĩnh, đúng như đặc tả ("dừng khi bật giảm chuyển động"). Nếu bạn muốn danh sách đầy đủ cho người bật giảm chuyển động, chỉ cần không dựng băng chuyền khi đó.
4. **Đồng hồ băng chuyền là CSS**: `animationend` của thanh tiến trình chuyển thẻ, nên thanh tiến trình và chuyển thẻ không bao giờ lệch nhau và không có bộ đếm giờ JS.
5. **Chuỗi trợ năng bằng tiếng Việt** do tôi đặt: băng chuyền "băng chuyền", từng thẻ "trang chiếu" ("1 trên 3"). Bạn nên đọc thử xem có tự nhiên không (mục 6).

## 5. Kiểm tra đã làm (nhẹ) và chưa làm

**Đã làm:**
- `php -l`, `lint74` (PHP 7.4) và `wpstyle` sạch; `node --check` sạch; kiểm marker của `home.css` và `home.js` đủ khối.
- `tests/infra` và `tests/theme/data-layer.php`: **ALL PASS**.
- Trình duyệt headless (Chrome 156) trên trang mẫu đủ dữ liệu (3 sự kiện, 3 lời kể, 8 logo, 5 giá trị, 5 bước) và trang thật (Giá trị, Quy trình): script kiểm tương tác, tất cả đạt: băng chuyền sự kiện và lời kể (cấu trúc, aria, tự chạy đúng 7 và 8 giây, rê chuột dừng và chạy lại, Tạm dừng và Phát, Trước, Sau, vạch, vòng lặp), phụ đề từng dòng, Giá trị (trạng thái đầu, trượt vào, rê chuột, focus, chạm), Quy trình (theo cuộn và theo vuốt), Đối tác, giảm chuyển động, đổi cỡ cửa sổ desktop và điện thoại, vuốt ngang trên điện thoại, thứ tự Tab và vòng focus; hồi quy của Giai đoạn 4 (Dịch vụ, Không gian, video cảnh kết). Không có lỗi console.
- Phát hiện và sửa trong lúc kiểm: bấm "Phát" khi con trỏ còn trên băng chuyền vẫn không chạy; chọn khung Giá trị bị rê chuột ghi đè khi dùng `pointerover` uỷ quyền (chuyển sang `pointerenter` từng khung); lời kể đầu hiện xong khi còn ngoài màn hình nên mất hiệu ứng phụ đề; đường kẻ Quy trình chưa vẽ xong khi section ở giữa màn hình (đổi khoảng cuộn); hàng điều khiển Lời kể bị lẻ một nút ở điện thoại hẹp.

**Chưa làm (theo yêu cầu của bạn, bạn tự kiểm):** bộ trình duyệt đầy đủ, đợt kiểm thử độc lập, đo hiệu năng. Chưa thử: Safari, Firefox, iOS, thiết bị cảm ứng thật, trình đọc màn hình thật (NVDA, VoiceOver) cho `aria-live` và `aria-roledescription`, dữ liệu thật.

## 6. Cần bạn xem và quyết

1. **Nhịp:** thời gian tự chạy (7 giây sự kiện, 8 giây lời kể), mờ chéo 300ms, so le 40ms, độ dài khoảng cuộn của đường kẻ Quy trình (đoạn 12% tới 50% khi section đi qua màn hình). Mỗi số chỉnh được một dòng.
2. **Giá trị có đúng 0,6 không** (mục 4, điểm 1).
3. **Điều khiển Sự kiện:** viên nang nền đặc ở góc trên phải thẻ có hợp mắt bạn không; có thể đưa xuống hàng riêng dưới thẻ.
4. **Chữ trợ năng tiếng Việt:** "băng chuyền", "trang chiếu", "1 trên 3", "Sự kiện trước", "Lời kể 1"... có tự nhiên không. Sửa ở `inc/home/assets.php` và các template.
5. **Ngân sách JS còn 1.813 byte.** Nếu đợt chỉnh sửa cần thêm tính năng JS, có ba cách: dùng chung logic video của hero và cảnh kết (khoảng 1 KB), rút nốt chú thích còn lại (khoảng 5,7 KB trong `home.js`), hoặc bạn nâng ngân sách.
6. **Những điều bạn đã hoãn** vẫn giữ nguyên: nhịp intro và iris của hero, độ đọc kiểu chữ, menu chính (trống). Cộng với các quyết định ở báo cáo Giai đoạn 4 (mục 6 của báo cáo đó, đặc biệt: Dịch vụ có "bám khung" khi dừng cuộn không).

## 7. Tồn đọng

| Mục | Ghi chú |
| --- | --- |
| Trang xê dịch một lần nếu JS khởi động chậm hơn 6 giây | Chưa sửa (hình học chế độ an toàn của hero). |
| Safari, Firefox, iOS chưa thử | Đáng chú ý: `pointerenter` trên cảm ứng, `touch-action: pan-y` với vuốt, `visibility` khi chuyển thẻ, `animationend` trên pseudo-element. |
| Media và dữ liệu thật chưa thử | Sự kiện, lời kể, đối tác, bài viết chỉ thử bằng dữ liệu mẫu; video cảnh kết chỉ thử bằng video giả. |
| Bộ test cũ chưa cập nhật | Test trình duyệt của Giai đoạn 2 và 3 chưa chạy lại sau các thay đổi kiểu chữ và hero. |

## 8. Bước tiếp theo

Bạn xem báo cáo Giai đoạn 4 và 5, rồi cho tôi các điều chỉnh để làm trong **một tiến trình cập nhật**. Sau đó là Giai đoạn 6 (trang chi tiết liệu trình, có điểm duyệt) và Giai đoạn 7 (kiểm thử tổng thể và bàn giao).
