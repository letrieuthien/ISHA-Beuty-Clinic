# Danh sách media cần cung cấp cho trang chủ ISHA

Cập nhật: 2026-11-10, Giai đoạn 7 (bàn giao). Cột **Hiện trạng** ghi rõ mục nào đang là placeholder, mục nào đã có. Số liệu lấy từ site thật (DB `isha`) lúc bàn giao: thư viện chỉ có 14 ảnh (logo, ảnh thẻ chính sách), **chưa có video, poster, ảnh bìa nhóm hay ảnh liệu trình nào**, trang "Trang chủ" (ID 10) chưa lưu nội dung nào (mọi chữ đang là mặc định của Phụ lục A), 17 liệu trình đều ở trạng thái Nháp và chưa có ảnh đại diện.

## Cách tải lên
1. Vào **Media → Thêm mới** (`wp-admin/upload.php`), kéo thả tệp. Giới hạn hiện tại 40 MB/tệp, đủ cho mọi mục bên dưới.
2. Với mỗi ảnh có nội dung, điền **Văn bản thay thế (Alt)**. Ảnh Không gian điền thêm **Chú thích (Caption)**, vì chú thích hiện ra dưới ảnh. Ảnh chỉ để trang trí thì để trống Alt.
3. Đặt tên tệp không dấu, có nghĩa (ví dụ `hero-ngang.mp4`, `nhom-1-cham-soc-da.jpg`) để dễ chọn sau này.
4. Việc **gán** media vào từng chỗ làm ở màn hình quản trị (trang "Trang chủ", từng nhóm dịch vụ, từng liệu trình); cách làm chi tiết ở `docs/home-admin.md`. Bạn chỉ cần upload trước vào Thư viện.

## Nguyên tắc chung cho mọi ảnh/video
- Tông **đen trắng hoặc da ấm**, có hạt nhẹ; không ảnh stock bóng bẩy, không icon minh họa.
- **Không có chữ, logo hay watermark** trong ảnh/video.
- Đặt **chủ thể ở giữa khung**: nhiều khung bị cắt thành hình tròn (hero, cảnh kết) hoặc cắt theo tỉ lệ khác nhau trên điện thoại.
- Video: **xuất không có kênh âm thanh**, 24–30 fps, H.264 (MP4), bật "fast start / web optimized". Cắt vòng lặp liền mạch. Video luôn **lặp vô hạn, không tiếng, không có nút tạm dừng/phát**. Nếu lỡ tải video có tiếng lên: trình duyệt vẫn tắt tiếng hoàn toàn (thuộc tính muted), người xem không nghe gì, chỉ tốn thêm dung lượng tải vì kênh âm thanh; nên xuất lại không âm thanh.
- Ảnh: JPG hoặc WebP, chất lượng khoảng 80%, mỗi ảnh nên ≤ ~500 KB. Không nén quá tay làm mất hạt phim. **Ảnh poster hero ≤ 250 KB** vì thường là phần tử hiển thị lớn nhất (ảnh hưởng tốc độ tải trên điện thoại).
- **Không dùng SVG** để upload (hệ thống chặn SVG vì lý do bảo mật). Chữ ký và logo dùng PNG/WebP nền trong.
- Ảnh trước và sau điều trị chỉ dùng ở mục 6b (cặp ảnh trong nhóm dịch vụ), và chỉ khi khách hàng đã đồng ý bằng văn bản cho công khai hình ảnh; không đặt chúng ở nơi khác.
- **Poster và video hero sáng** (nền trắng, da sáng, ga giường): trên điện thoại chữ hero nằm trên ảnh nên trang phủ một lớp tối 74% để chữ luôn đọc được; ảnh càng sáng thì càng bị che nhiều. Nếu muốn ảnh hero hiện rõ hơn, chọn khung hình tông trung bình đến tối.

## Bảng media

### A. Cần để trang chủ có hình

| # | Media | SL | Quy cách | Gán ở | Nếu thiếu | Hiện trạng (10/11/2026) |
|---|-------|----|----------|-------|-----------|--------------------------|
| 1 | Video hero ngang | 1 | MP4 H.264, 1920×1080, 8–15 giây lặp liền mạch, không tiếng, ≤ 8 MB | Trang chủ › thẻ Hero và intro | Hiện poster, có chuyển động chậm | **Chưa có.** Hero đang dùng placeholder (xem mục 3) |
| 2 | Video hero dọc | 1 | MP4 H.264, 1080×1350 (4:5) hoặc 1080×1920, ≤ 3 MB, cùng không khí với video ngang | Trang chủ › Hero và intro | Dùng poster dọc | **Chưa có** |
| 3 | Poster hero ngang | 1 | JPG/WebP 1920×1080, ≤ 250 KB, là khung đầu của video ngang | Trang chủ › Hero và intro | Vòng tròn chỉ có ánh sáng Gold mờ | **Chưa có: đang là placeholder** (nền tối với ánh sáng Gold mờ trong vòng tròn; ở chế độ xem trước có nhãn "Poster hero ngang") |
| 4 | Poster hero dọc | 1 | JPG/WebP 1080×1350, ≤ 250 KB, là khung đầu của video dọc | Trang chủ › Hero và intro | Như trên | **Chưa có: đang là placeholder** (như mục 3) |
| 5 | Ảnh bìa 6 nhóm dịch vụ | 6 | Dọc 4:5, tối thiểu 1200×1500 (nên 1600×2000), JPG/WebP | Dịch vụ › Nhóm dịch vụ › từng nhóm | Khối màu thay thế, vẫn đọc được chữ | **0/6: đang là placeholder** (khối màu trung tính). 6 nhóm đã có đủ tên, mô tả, thứ tự 10–60 |
| 6 | Video bìa nhóm (tuỳ chọn) | tối đa 6 | MP4 lặp 4–8 giây, không tiếng, 4:5 (1080×1350), ≤ 3 MB | Cùng màn hình với mục 5 | Dùng ảnh bìa | **Chưa có. Lưu ý: ô này lưu được nhưng trang chủ CHƯA phát video bìa nhóm** (chỉ dùng ảnh bìa); xem `docs/home-handover.md`, mục "Quyết định còn mở" |
| 6b | **Ảnh trước/sau điều trị** (ưu tiên) | tối đa 6 cặp / nhóm | Mỗi cặp 2 ảnh **dọc 4:5**, cùng kích thước (≥ 1200×1500), **cùng góc chụp, khoảng cách và ánh sáng**, không chữ/logo. Chú thích (Caption) của ảnh SAU hiện dưới cặp ảnh (hiện tối đa 4 dòng, nên viết ≤ 2 dòng). Ảnh thứ n ở ô "Ảnh trước điều trị" ghép với ảnh thứ n ở ô "Ảnh sau điều trị"; hai ô phải cùng số ảnh, cùng thứ tự | Dịch vụ › Nhóm dịch vụ › từng nhóm (hai ô gallery) | Nhóm dùng ảnh bìa như cũ | **Chưa có.** Tính năng đã xong và đã kiểm thử; admin tải ảnh ở màn hình nhóm dịch vụ |
| 7 | Ảnh Không gian | 6–10 | Cạnh dài ≥ 2000 px, có cả ảnh dọc và ngang, kèm Chú thích + Alt | Trang chủ › thẻ Không gian | Ẩn cả section | **Chưa có: section Không gian đang ẩn** |
| 8 | Chữ ký Bác sĩ Linh Võ | 1 | PNG/WebP **nền trong**, nét mảnh, rộng ≥ 1200 px | Trang chủ › thẻ Tầm nhìn | Chỉ hiện chức danh và tên | **Chưa có** (chỉ hiện chức danh và tên) |
| 9 | Logo đối tác | 4–12 | PNG/WebP nền trong, ưu tiên một màu trắng. Nên xuất mỗi logo trên cùng một khung (ví dụ 600×300, lề đệm đều). Logo màu thì CSS tự chuyển thành trắng. Mỗi ảnh điền Alt = tên đối tác | Trang chủ › thẻ Đối tác (một gallery) | Ẩn cả section | **Chưa có: section Đối tác đang ẩn** |
| 10 | Nền cảnh kết (tuỳ chọn) | 1 | Video như mục 1 (≤ 8 MB) hoặc ảnh 2400×1350 | Trang chủ › thẻ Cảnh kết | Nền Shadow với ánh Gold | **Chưa có** (nền Shadow với ánh Gold, đọc tốt) |

Ghi chú cho mục 6b: nhóm có ít nhất một cặp hợp lệ thì khung hình 4:5 của nhóm hiện cặp ảnh thay cho ảnh bìa (chia đôi trái/phải, kéo thanh để so sánh; nhiều cặp thì có chấm chọn). Cặp thiếu một ảnh hoặc có ảnh đã bị xoá khỏi thư viện thì không hiển thị, và các cặp còn lại vẫn ghép đúng ở trang công khai. **Khi mở màn Sửa nhóm**, cặp có ảnh đã bị xoá sẽ được bỏ khỏi CẢ HAI ô (kèm dòng báo ở ô ảnh trước) để các cặp còn lại luôn thẳng hàng; bấm Cập nhật thì lưu đúng như đã thấy. Muốn thay một ảnh: xoá cả hai ảnh của cặp đó rồi thêm lại cặp mới ở cuối, hoặc sửa tại chỗ bằng nút Trước/Sau cho đúng vị trí. Dòng ghi chú miễn trừ kết quả dưới danh sách liệu trình ("Hình ảnh thực tế của khách hàng đã đồng ý chia sẻ. Kết quả có thể khác nhau tùy cơ địa và phác đồ.") sửa ở Trang chủ › thẻ Dịch vụ › "Ghi chú dưới ảnh trước/sau". Việc dùng ảnh trước/sau trong quảng cáo dịch vụ y tế có quy định pháp lý; nên để pháp chế/khách hàng xác nhận trước khi đăng.

Ghi chú cho mục 9: CSS sẽ ép mọi logo vào ô cùng kích thước (`aspect-ratio` + `object-fit: contain`), nên logo khác tỉ lệ vẫn không bị méo hay vỡ. Không có trường link/tên riêng cho từng logo.

### B. Tuỳ chọn, nâng chất lượng

| # | Media | SL | Quy cách | Gán ở | Nếu thiếu | Hiện trạng (10/11/2026) |
|---|-------|----|----------|-------|-----------|--------------------------|
| 11 | Ảnh 5 giá trị cốt lõi | 5 | Dọc 3:4, tối thiểu 900×1200, đen trắng/da ấm, ít chi tiết và hơi tối (hiện mờ 35–60% phía sau chữ) | Trang chủ › thẻ Giá trị | Chỉ có chữ | **Chưa có** (chỉ có chữ, đọc tốt) |
| 12 | Ảnh đại diện liệu trình | 17 | Dọc 4:5, ≥ 1200×1500; hiện ở khung mở đầu của trang chi tiết liệu trình (lệch phải) | Từng liệu trình › Ảnh đại diện | Tự dùng ảnh bìa của nhóm; thiếu cả hai thì khối giữ chỗ | **0/17: đang là placeholder.** 17 liệu trình còn ở trạng thái Nháp (khách chưa thấy trang) |
| 13 | Ảnh liệu trình (gallery) | 0–8 / bài | Cạnh dài ≥ 1600 px, dọc hoặc ngang; hiện ở hàng vuốt (khung 4:3 cắt giữa, nên đặt chủ thể ở giữa) và mở nguyên vẹn trong hộp xem lớn; Alt đọc cho trình đọc màn hình, Chú thích hiện dưới ảnh trong hộp xem lớn | Từng liệu trình › Ảnh liệu trình | Ẩn khối hình ảnh | **Chưa có** (khối hình ảnh ẩn) |

### C. Chỉ khi có nội dung thật (không cần làm bây giờ)

| # | Media | Quy cách | Gán ở | Hiện trạng (10/11/2026) |
|---|-------|----------|-------|--------------------------|
| 14 | Ảnh sự kiện ngang | 2.39:1, 2400×1004 | Từng sự kiện › Ảnh đại diện | **Chưa có sự kiện nào**: section Sự kiện đang ẩn |
| 15 | Ảnh sự kiện dọc (cho điện thoại) | 4:5, ≥ 1080×1350 | Từng sự kiện › Ảnh dọc | Như trên |
| 16 | Ảnh bài viết | 4:5, ≥ 1200×1500 | Từng bài viết › Ảnh đại diện | **Chưa có bài viết nào**: section Bài viết đang ẩn |

Testimonials không cần ảnh: thiết kế hiện tại chỉ có lời kể và dòng tên. **Chưa có lời kể nào** (section Lời kể đang ẩn).

### D. Tài nguyên thương hiệu

| # | Media | Hiện trạng (10/11/2026) | Việc cần làm |
|---|-------|--------------------------|--------------|
| 17 | Logo đầy đủ | **Đã có** trong Thư viện: bản trắng (ID 116, đang là logo site), bản đen (ID 117), logo dạng mask trắng/đen (118, 119) | Đủ, không cần thêm |
| 18 | Logo chữ ISHA dạng vector | **Chưa có file; đang là placeholder**: bốn chữ I S H A dựng bằng chữ Garamond trong SVG (không phải logo thật). Thư viện chỉ có PNG (210×95 và 636×380), quá nhỏ để phóng 70% chiều ngang màn hình | Gửi file **SVG/AI/PDF** (4 chữ I-S-H-A tách rời, mỗi chữ một path). Đặt tệp tên đúng `isha-wordmark.svg` vào `wp-content/themes/ISHA/assets/images/` (mỗi chữ cái một `<path>`, id lần lượt `isha-i`, `isha-s`, `isha-h`, `isha-a`; xem `isha_home_wordmark()` trong `inc/home/helpers.php`), **không upload qua Media**; theme tự dùng thay cho placeholder. **Yêu cầu của tệp SVG** (tệp được nhúng thẳng vào trang chứ không phải thẻ `<img>`): có `viewBox`; **không có thẻ `<style>` hoặc `<script>`** (`<style>` sẽ tác động cả trang); **không có thuộc tính `fill` hay `stroke` cố định trên các path** (màu lấy từ theme qua `currentColor` để logo đổi màu theo từng section; `fill` cố định làm logo luôn một màu); nhỏ hơn 256 KB |
| 19 | Biểu tượng Light Gloom và Liner pattern | **Đang là placeholder tự vẽ**: tia sáng và đường kẻ mảnh dựng bằng CSS/SVG đơn giản theo `isha PRESO.pdf` / `Visual Element preview.pdf` | Nếu có file vector gốc từ designer thì gửi để thay |
| 20 | Favicon (tuỳ chọn) | **Đã có**: site icon hiện là Alternative Logo (ID 146, bản cắt vuông của ID 145) | Muốn đổi: PNG vuông 512×512 ở Giao diện › Tùy biến › Nhận dạng trang web |

## Thứ tự ưu tiên gợi ý
1. Mục 3, 4 (poster) và 5 (6 ảnh bìa nhóm): đủ để trang có hình ở những chỗ quan trọng nhất.
2. Mục 1, 2 (video hero), 7 (Không gian), 8 (chữ ký), 18 (logo chữ vector).
3. Mục 9 (đối tác), 10, 11, 12, 13.
4. Mục C khi có sự kiện/bài viết thật.

Các section thiếu dữ liệu tự ẩn, nên không cần tải đủ tất cả trước khi xem thử.
