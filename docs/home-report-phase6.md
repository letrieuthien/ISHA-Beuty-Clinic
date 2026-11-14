# Báo cáo Giai đoạn 6: trang chi tiết liệu trình

Ngày: 2026-11-10. Trạng thái: **xong, CHỜ BẠN DUYỆT** (điểm duyệt của Giai đoạn 6). Sau khi duyệt: Giai đoạn 7 (kiểm thử tổng thể và bàn giao, gồm Safari/iOS thật).

Trước khi làm Giai đoạn 6, theo ý bạn tôi đã gỡ hẳn section "Bốn tinh thần" (trang chủ vẫn 13 section; `docs/home-brand-script.md` chỉ còn là tài liệu tham khảo ý tưởng từ Brand Story) và cập nhật lại các bài kiểm, tài liệu, báo cáo Update 1.

## 1. Đã làm

Trang `single-isha_service.php` được dựng lại hoàn toàn theo hệ thiết kế của trang chủ, giữ nguyên dữ liệu và luồng đặt lịch (nút "Đặt lịch liệu trình này" vẫn là trang đặt lịch kèm `?service=<ID>`).

Thứ tự trên trang (ảnh minh hoạ trong `docs/phase6-shots/`):

1. **Mở đầu** (`01`, điện thoại `08`): tên nhóm (liên kết về trang nhóm), tên liệu trình (chữ Garamond lớn, hiện bằng mặt nạ trượt lên), mô tả ngắn, thời gian liệu trình; ảnh đại diện dọc 4:5 đặt lệch phải, tiến chậm khi cuộn. Trên điện thoại: chữ trên, ảnh dưới.
2. **Nội dung biên tập** (`02`): chữ thân, tiêu đề, trích dẫn có đường kẻ Gold, danh sách; dòng khoảng 67 ký tự.
3. **Các bước liệu trình** (`03`): danh sách đánh số trên một đường kẻ dọc mảnh, các bước hiện lần lượt.
4. **Hình ảnh liệu trình** (`04`, điện thoại `09`): hàng vuốt ngang có nút Trước/Sau và chỉ báo; bấm ảnh mở **hộp xem lớn ngay trong trang** (`05`, điện thoại `10`): Esc đóng, mũi tên đổi ảnh, có chữ "Đóng", "Trước", "Sau", bộ đếm "2 / 6", chú thích; không mở tab mới.
5. **Thẻ thông tin** (`02`, `03`, `04`): dính bên phải khi cuộn trên desktop; nằm cuối vùng nội dung trên điện thoại (`09`); có thời gian, nút "Đặt lịch liệu trình này", nút "Nhắn Zalo" (chỉ hiện khi đã nhập URL Zalo).
6. **Liệu trình khác trong nhóm** (`06`): tối đa 5 bài cùng nhóm theo thứ tự (menu_order), ít hơn thì hiện đúng số đang có; kèm liên kết "Xem tất cả liệu trình trong nhóm".
7. **Kết trang** (`07`): dùng lại shortcode `[isha_home_cta]` (cảnh kết của trang chủ).

Các biến thể dữ liệu thiếu (đều không để lại khoảng trống): chỉ có thời gian (`11`), chưa phân nhóm và chưa có ảnh (`12`), tên rất dài (`13`), cỡ 1280×720 (`14`).

## 2. Đối chiếu đặc tả

| Yêu cầu trong prompt | Kết quả |
|---|---|
| Mở đầu: ảnh dọc 4:5 lệch phải; tên (Garamond), tên nhóm (liên kết trang nhóm), mô tả ngắn, thời gian | Đủ |
| Khung ảnh tiến chậm theo cuộn; tên hiện bằng mặt nạ trượt lên, chừa chỗ cho dấu tiếng Việt | Đủ; đã chụp thử Ẫ Ậ Ế Ổ Ữ Ặ Ỷ không bị cắt |
| Không dùng iris hay tia sáng | Đủ |
| Nội dung `the_content()`: 62–70 ký tự mỗi dòng, trích dẫn có đường kẻ Gold | Đủ (đo thật 67–68 ký tự); **chữ Montserrat** theo chỉ dẫn của bạn, xem mục 3 |
| Các bước trên một đường kẻ dọc mảnh | Đủ |
| Hình ảnh: hàng vuốt có nút mũi tên, mở xem lớn trong trang (dialog, Esc), không tab mới | Đủ |
| Thẻ thông tin dính (desktop) / cuối trang (điện thoại), thời gian, đặt lịch `?service=<ID>`, Nhắn Zalo | Đủ; điện thoại: xem mục 3 |
| Liệu trình khác: tối đa 5, theo menu_order | Đủ (đã kiểm: bỏ bài hiện tại, bỏ nháp, ít hơn 5 thì đúng số có) |
| Kết trang `[isha_home_cta]` | Đủ |
| `service-single.css`, `service-single.js` dùng lõi `isha-motion.js` | Đủ |
| Không để `main.page > .page-header` ẩn phần mở đầu | Đủ (khung mở đầu không dùng class `page-header`) |
| Một h1, `aria-labelledby`, focus nhìn thấy, vùng chạm 48px, giảm chuyển động, chế độ gọn | Đủ, đã kiểm |

## 3. Chỗ lệch prompt, cần bạn biết

1. **Chữ thân dùng Montserrat** (prompt ghi Garamond cho phần nội dung): theo chỉ dẫn trước đó của bạn (đoạn văn Montserrat, tiêu đề/nhãn Garamond). Tiêu đề trong nội dung và trích dẫn vẫn Garamond.
2. **Điện thoại: thẻ thông tin nằm cuối vùng nội dung**, tức trước "Liệu trình khác" và cảnh kết, chứ không ở tận cuối trang, vì cảnh kết đã có nút đặt lịch và nên là phần kết. Nếu bạn muốn thẻ nằm sau cả "Liệu trình khác", cho tôi biết.
3. **Trang này nạp tài nguyên trang chủ** (home.css, isha-motion.js, home.js) vì dùng lại hệ thiết kế, hàng vuốt và shortcode cảnh kết đúng như prompt chỉ định; các trang khác không bị ảnh hưởng.
4. **Nút Đặt lịch ở header** của trang này cũng có lớp vàng kim loại như trang chủ (token vàng kim loại giờ khai ở `:root`).

## 4. Cách xem trên site thật

Trên site thật cả **17 liệu trình đang là bản nháp**, nên khách chưa thấy trang. Xem bằng "Xem trước" trong wp-admin (Dịch vụ › chọn một liệu trình › Xem trước thay đổi), hoặc xuất bản. Tôi đã kiểm trang thật qua HTTP bằng bản nháp (phiên tạm, đã huỷ): trả 200, không lỗi PHP, tài nguyên nạp đúng.

Để trang đẹp cần nhập cho mỗi liệu trình: **Ảnh đại diện** (dọc 4:5, ≥ 1200×1500; thiếu thì dùng ảnh bìa nhóm), **Mô tả ngắn**, **Thời gian liệu trình**, **Các bước** (mỗi dòng một bước), **Ảnh liệu trình** (Alt cho trình đọc màn hình, Chú thích hiện trong hộp xem lớn), và nội dung trong trình soạn thảo (tuỳ chọn). Mọi phần thiếu tự ẩn.

## 5. Kiểm thử

- Mã: `php -l`, `lint74`, `wpstyle`, `node --check` sạch.
- PHP: `tests/theme/service-single.php` (59 phép kiểm: nhóm chính, liệu trình khác, ảnh dự phòng, gallery, dữ liệu, markup, mật khẩu, nạp tài nguyên); `tests/infra`, `tests/theme/data-layer.php`, `schema-defaults.php`, `tests/home/test-home-fields.php` vẫn ALL PASS (13 section).
- Trình duyệt headless trên trang mẫu: desktop, điện thoại 390×844, giảm chuyển động; bốn biến thể dữ liệu; tám cỡ màn hình (1920×1080 đến 844×390); đi phím Tab theo thứ tự đọc (không bẫy focus, vòng focus nhìn thấy, không bị header che); hộp xem lớn (mở/đóng, mũi tên, Esc, trả focus); chuyển động (ảnh tiến chậm, các bước); không tràn ngang; không lỗi console.
- Chưa làm (theo chỉ dẫn): Safari/iOS thật (Giai đoạn 7), Firefox, Edge.

## 6. Ghi chú nhỏ

- Plugin WPFront Scroll Top đã được bạn gỡ và thay bằng nút "Lên đầu trang" riêng của theme (mục 13 của `home-architecture.md`); nút này cố định góc dưới phải, nên trên điện thoại vẫn có lúc nằm đè lên nội dung phía sau (như mọi nút nổi).
- **Trang nhóm dịch vụ** (`/nhom-dich-vu/...`, nơi liên kết "Xem tất cả liệu trình trong nhóm" và nhãn nhóm dẫn tới) vẫn là kiểu cũ vì nằm ngoài phạm vi của prompt; khi bạn thấy cần, đó là việc nên làm tiếp để đồng bộ.

## 7. Cần bạn quyết

1. Duyệt bố cục, nhịp chữ và chuyển động của trang liệu trình.
2. Thẻ thông tin trên điện thoại để như hiện tại hay chuyển xuống sau "Liệu trình khác".
3. (Đã giải quyết) nút cuộn lên của WPFront: plugin đã gỡ, thay bằng nút riêng.
4. Có dựng lại trang nhóm dịch vụ theo hệ thiết kế này (ngoài phạm vi ban đầu) không.

## 8. Tệp thay đổi

- Theme: `single-isha_service.php` (viết lại), `template-parts/service-single.php` (mới), `inc/service-single.php` (mới), `functions.php` (thêm một dòng nạp), `assets/css/service-single.css` (mới), `assets/js/service-single.js` (mới), `assets/css/home.css` (chuyển token vàng kim loại ra `:root`).
- Gỡ section "Bốn tinh thần": `template-parts/home/spirit.php` (xoá), `inc/home/registry.php`, `inc/home/data.php`, `assets/css/home.css`, `assets/js/home.js`, `plugins/isha-core/includes/home-schema.php`; chú thích số section ở `functions.php`, `front-page.php`.
- Tài liệu: `home-architecture.md` (mục 11.9 và 12), `home-assets.md`, `home-brand-script.md` (viết lại thành tài liệu tham khảo), `home-report-update1.md`, báo cáo này, ảnh `phase6-shots/` (14).

## 9. Sao lưu

`C:\xampp\isha-backups\2026-11-10_after-phase6\` (theme, plugin, docs, db.sql). Thư mục `2026-11-10_after-update1` đã được làm mới cùng lúc nên hiện có CÙNG nội dung với `after-phase6` (không còn là ảnh chụp riêng của Update 1).
