# Báo cáo Update 1: chỉnh sửa theo duyệt Giai đoạn 4 và 5

Ngày: 2026-11-10. Trạng thái: **đã được người dùng duyệt (cùng Giai đoạn 4 và 5)**, chuyển sang Giai đoạn 6. Sau khi duyệt, người dùng yêu cầu: **gỡ section "Bốn tinh thần"** (chỉ dùng Brand Story để tham khảo), không thêm lại nút tạm dừng video, không cần subset phông, ảnh trước/sau sẽ do admin tự tải. Các mục dưới đây đã được chỉnh theo trạng thái cuối. Giai đoạn 7 (kiểm thử tổng thể và bàn giao, gồm Safari/iOS thật) làm sau.

## 1. Tóm tắt theo từng ý của bạn

| # | Ý của bạn | Kết quả |
|---|-----------|---------|
| 1 | Dùng `ISHA BRAND STORY` làm tham khảo | **Chỉ tham khảo, không thêm section.** Bản làm thử section "Bốn tinh thần" đã được gỡ hẳn theo yêu cầu. Ý tưởng nội dung và thiết kế rút từ tài liệu nằm ở `docs/home-brand-script.md` (chưa áp dụng vào trang). Mục 3. |
| 2 | Giữ chú thích rút gọn, tăng JS thêm 20KB, rút nốt chú thích còn lại | **Ngân sách 110KB.** Mọi hàm có tên đều có một dòng tiêu đề chức năng; các chú thích dài đã rút xuống cỡ tiêu đề. JS hiện **92,6KB** (home.js 71,7KB + isha-motion.js 21,0KB), còn khoảng 17KB. Mục 4. |
| 3 | Thêm/bớt section có ảnh hưởng cấu trúc hay gây lỗi JS không? | **Không ảnh hưởng lớn.** Bớt: không cần sửa mã. Thêm: sáu chỗ, không đụng lõi chuyển động. Đã kiểm khói bảy tập con. Mục 5. |
| 4a | Video có tiếng thì sao? | Không bao giờ ra tiếng (`muted`); chỉ tốn thêm dung lượng nếu còn kênh âm thanh. Mục 6. |
| 4b | **Ảnh trước/sau điều trị trong mục dịch vụ (ưu tiên)** | **Đã làm** (hai ô ảnh mỗi nhóm dịch vụ, thanh kéo so sánh). Mục 2. |
| 5 | Preload font + màn hình chờ có chữa LCP và hiện tượng xê dịch ở 6 giây? | Màn hình chờ **không** giảm LCP; hiện tượng xê dịch đã **sửa tận gốc bằng CSS** (không cần màn hình chờ). Mục 7. |
| 6 | Vàng kim loại từ Brown + Pale Gold + Glowing Gold | Đã làm bản **dẫn xuất từ Gold của hệ thống**, áp dụng cho nút chính, năm, chữ cái triết lý, vòng cảnh kết. Mục 8. |
| 7 | Test Safari/iOS ở giai đoạn cuối, không cần Firefox/Edge | Đã rà soát tĩnh (mã đã tương thích, không phải sửa) và lập danh sách việc cần thử trên iPhone thật cho Giai đoạn 7. Mục 9. |
| 8 | Video lặp vô hạn, không nút tạm dừng/phát | **Đã làm** ở hero và cảnh kết. Mục 6. |

## 2. Ảnh trước/sau điều trị (yêu cầu ưu tiên)

Ảnh minh hoạ: `docs/update1-shots/01` (một cặp), `02` (ba cặp, có chấm chọn), `03` (điện thoại).

**Cách dùng (cho người nhập nội dung):** Dịch vụ › Nhóm dịch vụ › sửa một nhóm › có thêm hai ô **"Ảnh trước điều trị"** và **"Ảnh sau điều trị"** (mỗi ô tối đa 6 ảnh). Ảnh thứ n của ô trước ghép với ảnh thứ n của ô sau. Chú thích (Caption) của ảnh SAU trong Thư viện media hiện dưới cặp ảnh. Dòng ghi chú miễn trừ kết quả sửa ở Trang chủ › thẻ Dịch vụ › "Ghi chú dưới ảnh trước/sau" (có sẵn nội dung mặc định).

**Người xem thấy gì:** khung ảnh 4:5 của nhóm hiện cặp ảnh chia đôi (trước bên trái, sau bên phải), có nhãn Trước/Sau; kéo thanh tròn ở chân khung (hoặc dùng phím mũi tên) để so sánh; nhóm có nhiều cặp thì có chấm chọn cặp ở đỉnh khung; lần đầu hiện sẽ tự gợi ý kéo một lần. Không có JS hoặc giảm chuyển động vẫn có khung chia đôi tĩnh. Ảnh bìa nhóm được thay bằng cặp ảnh khi nhóm có ít nhất một cặp hợp lệ.

**Điều cần lưu ý:**
- **Pháp lý:** đăng ảnh trước/sau của dịch vụ y tế có quy định; cần khách hàng đồng ý bằng văn bản cho công khai, và nên để pháp chế hoặc khách hàng xác nhận trước khi đăng. Tôi không tư vấn pháp lý.
- Ảnh trước và sau phải **cùng khung chụp, cùng góc và ánh sáng**, tỉ lệ dọc 4:5 (≥ 1200×1500), nếu không phép so sánh vô nghĩa.
- Nếu một ảnh bị xoá khỏi Thư viện: cặp đó biến mất ở trang công khai, các cặp sau vẫn ghép đúng. (Cập nhật Giai đoạn 7: ở màn Sửa nhóm, cặp hỏng được bỏ khỏi CẢ HAI ô để các cặp còn lại luôn thẳng hàng khi lưu; trước đây mở màn Sửa rồi bấm Cập nhật làm lệch cặp, nay đã sửa và có bài kiểm khứ hồi.)
- Chưa có ảnh thật nên đã thử bằng ảnh mẫu trong DB thử (không phải DB thật). Trang thật chưa có cặp nào cho tới khi bạn nhập.

## 3. ISHA BRAND STORY: chỉ để tham khảo

Theo yêu cầu sau duyệt, **không có section mới nào** được thêm vào trang chủ (13 section như trước). Bản làm thử section "Bốn tinh thần" đã gỡ hoàn toàn khỏi schema, mặc định, registry, template, CSS, JS và bài kiểm; không còn dấu vết trong mã. Nội dung các section hiện có không đổi.

Tài liệu được dùng làm kho ý tưởng: `docs/home-brand-script.md` tóm tắt nội dung cốt lõi (hành trình bốn bước, câu chốt tiếng Anh, bốn tinh thần), gợi ý chỗ có thể dùng câu chữ (câu chuyện, tầm nhìn, quy trình, trang liệu trình ở Giai đoạn 6) và vài ý tưởng thiết kế. Tất cả chỉ là gợi ý, chưa áp dụng.

## 4. Chú thích và ngân sách JS

- Chèn tiêu đề chức năng cho 137 hàm (95 ở home.js, 42 ở isha-motion.js) và rút 2,8KB chú thích dài; mỗi lần sửa đều so chuỗi token trước/sau phải giống hệt nên **không đổi hành vi**.
- Quy ước này đã ghi vào `docs/home-architecture.md` mục 11.1: hàm mới phải có tiêu đề chức năng.

## 5. Thêm hoặc bớt section sau này

- **Bớt:** xoá khỏi registry (hoặc tắt trong CMS: công tắc của thẻ, hoặc để trống dữ liệu thì tự ẩn). Không cần sửa JS/CSS vì bầu trời đổi màu, header và dock suy ra từ DOM.
- **Thêm:** sáu chỗ (registry, schema ISHA Core, mặc định, template, CSS, tuỳ chọn đăng ký JS), không đụng lõi chuyển động (đã thử thêm một section thật rồi gỡ đi, cả hai chiều đều không phải sửa lõi).
- Đã kiểm khói bằng trình duyệt trên bảy tập con (không hero, không cảnh kết, không cả hai, chỉ 2 section, chỉ hero, chỉ quy trình, xen kẽ): không lỗi console, không tràn ngang, lõi chuyển động và dock/header chạy bình thường.
- Một cải tiến: danh sách mã section giờ lấy từ registry (trước đó còn một danh sách cứng thứ hai trong lớp dữ liệu, dễ quên).
- Hướng dẫn từng bước: `docs/home-architecture.md` mục 11.4.

## 6. Video: lặp vô hạn, không nút, có tiếng thì sao

- Đã gỡ nút tạm dừng/phát ở hero và cảnh kết (PHP, CSS, JS); video luôn lặp, không tiếng.
- **Giảm chuyển động và chế độ tiết kiệm dữ liệu chỉ hiện poster, không phát video**; trình duyệt chặn tự phát (iPhone ở chế độ tiết kiệm pin) thì hiện poster và thử lại ở lần chạm đầu tiên.
- **Đánh đổi cần biết:** bỏ nút tạm dừng trái với tinh thần tiêu chuẩn WCAG 2.2.2 (nội dung tự chuyển động quá 5 giây nên có cách dừng). Tôi làm theo quyết định của bạn và bù bằng hai chế độ trên. Nếu sau này có yêu cầu tuân thủ khắt khe thì thêm lại nút chỉ mất khoảng 1KB.
- **Video có tiếng:** trang đặt `muted` nên người xem không nghe gì dù video có kênh âm thanh; chỉ tốn thêm dung lượng tải. Khuyến nghị xuất video **không kênh âm thanh** (đã ghi trong `docs/home-assets.md`).

## 7. Preload font, màn hình chờ, LCP và hiện tượng xê dịch ở 6 giây

- **Màn hình chờ không làm LCP nhanh hơn.** LCP đo thời điểm vẽ phần tử lớn nhất; lớp phủ không thay đổi số đo đó. Nguyên nhân thật là phông OTF/TTF nặng (khoảng 2MB). Phép thử A/B trước đây ở dự án này cho thấy preload thêm phông còn làm LCP tệ hơn (tranh băng thông với poster). Hiện tại intro đã chờ font (tối đa 2,2 giây) nên chữ không đổi font giữa chừng.
- **Xê dịch ở 6 giây: đã sửa tận gốc, không cần màn hình chờ.** Khi JS không khởi động sau 6 giây, trang chuyển sang chế độ tĩnh và trước đây chữ hero tụt xuống nửa chiều cao header. Nay hai chế độ có cùng toạ độ: đo bằng trình duyệt (giây 2 so với giây 7, ba cỡ màn hình) độ lệch của mặt trời, tiêu đề, nút hero là **0px**.
- **Cách giảm LCP thật sự:** subset phông (chỉ chữ La-tinh và tiếng Việt, vẫn giữ OTF/TTF, không dùng WOFF2 theo chỉ dẫn) có thể giảm khoảng 70% dung lượng. Việc này cần công cụ ngoài nên chưa làm; nếu bạn muốn, đây là việc nên làm trước khi go-live.

## 8. Vàng kim loại

Ảnh minh hoạ: `docs/update1-shots/04` đến `07`.

- Không dùng nguyên ba mã Brown #B03E1E, Pale Gold #F1D9A8, Glowing Gold #FFC34D vì chúng ngoài bảng màu hệ thống; dựng **hai bộ ba nấc dẫn xuất từ Gold #8e6837** (đẩy sắc độ về phía đồng và phía vàng rực): bộ cho nền sáng (đồng #6b4524, Gold, #936f34) và bộ cho nền tối (đồng #9b5b31, nhạt #eddbc0, rực #f7c664, trung #c48945).
- Áp dụng: nút chính ở hero, cảnh kết, dock và nút Đặt lịch của header (vệt sáng trượt khi rê chuột, viền sáng mảnh), năm 2024 ở "Câu chuyện", chữ cái lớn của "Triết lý" ở chế độ tĩnh, vòng mảnh của cảnh kết.
- **Giới hạn trung thực:** trên nền sáng, độ sáng của vàng bị chặn bởi yêu cầu tương phản (chữ trắng trên nút phải ≥ 4,5:1, chữ lớn ≥ 3:1) nên ánh kim ở đó chỉ là sắc đồng nhẹ; ánh vàng rực chỉ nổi trên nền tối. Muốn nổi hơn thì phải đổi bản chất nút (ví dụ chữ tối trên vàng sáng), cần bạn quyết.
- Mọi chỗ đều có màu phẳng dự phòng cho trình duyệt cũ, và chế độ tương phản cao của hệ điều hành dùng màu hệ thống.

## 9. Safari và iOS

Rà soát tĩnh: các tính năng dùng đều có nhánh dự phòng (`color-mix`, `mask`, `background-clip: text`, `overflow: clip`, `scrollTo` lệnh `instant`), video có `muted` và `playsinline`, thanh kéo dùng được bằng cảm ứng. **Không phải sửa gì.** Danh sách 7 việc cần thử trên iPhone thật (thanh địa chỉ co giãn, cuộn đàn hồi, tự phát video và chế độ tiết kiệm pin, kéo thanh trước/sau, dock với thanh home, font, `color-mix` trên iOS cũ) ở `docs/home-architecture.md` mục 11.7, để làm ở Giai đoạn 7. Máy này không có Safari/iOS nên chưa thử thật.

## 10. Kiểm thử đã làm

- Mã: `php -l`, `lint74`, `wpstyle` sạch; `node --check` và kiểm marker sạch.
- PHP: `tests/infra`, `tests/theme/data-layer.php`, `tests/theme/schema-defaults.php`, `tests/home/test-home-fields.php` ALL PASS (giữ 13 section; thêm bài kiểm ghép cặp, xoá ảnh giữa chừng, cắt 6 ảnh; thêm bài kiểm lưu/hiển thị ô quản trị).
- Trình duyệt headless: trước/sau (desktop, gọn, giảm chuyển động), video (thường, giảm chuyển động, tiết kiệm dữ liệu, bị chặn tự phát), căn chỉnh hero (ba cỡ màn hình), bảy tập con section, hồi quy Giai đoạn 5 và 4. Tất cả PASS.
- Chưa làm (theo chỉ dẫn): Safari/iOS thật, Firefox, Edge; không chạy các quy trình rà soát độc lập.

## 11. Quyết định của người dùng sau khi duyệt

1. Brand Story chỉ để tham khảo: **gỡ section "Bốn tinh thần"** (đã làm).
2. Ảnh trước/sau do admin tự tải lên trong phần quản lý (không cần tôi cung cấp ảnh).
3. Không cần subset phông.
4. Không thêm lại nút tạm dừng video.
5. Duyệt Giai đoạn 4, 5 và Update 1; **sang Giai đoạn 6**. Các vấn đề khác (kiểm thử Safari/iOS thật, v.v.) làm sau.

## 12. Tệp thay đổi

- Plugin ISHA Core: `class-term-fields.php` (hai ô ảnh trước/sau), `home-schema.php` (trường `ba_note`).
- Theme: `inc/home/data.php` (mặc định, `ba_note`, `pairs`, mã section lấy từ registry), `inc/home/registry.php`, `inc/home/assets.php` (bỏ chuỗi pause/play), `template-parts/home/services.php`, `hero.php`, `cta.php`, `assets/css/home.css`, `assets/js/home.js`, `assets/js/isha-motion.js`.
- Tài liệu: `home-architecture.md` (mục 11), `home-assets.md`, `home-brand-script.md` (mới), báo cáo này, ảnh `update1-shots/` (7).
