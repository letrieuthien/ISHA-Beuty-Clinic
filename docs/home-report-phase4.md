# Báo cáo Giai đoạn 4: sáu cảnh điện ảnh (điểm duyệt)

| | |
| --- | --- |
| Ngày lập | 2026-11-10 |
| Trạng thái | Dựng xong cả sáu cảnh, kiểm tra nhẹ như bạn yêu cầu. Chờ bạn duyệt, rồi làm Giai đoạn 5 (giá trị, quy trình, sự kiện, bài viết, lời kể, đối tác) và đợt chỉnh sửa theo duyệt của bạn. |
| Xem ngay | http://localhost/ISHA_clinic/ (cuộn xuống sau phần hero; lần đầu trong một phiên sẽ chạy intro) |
| Tài liệu kỹ thuật | `docs/home-architecture.md` mục 9 |
| Ảnh chụp | `docs/phase4-shots/` (22 ảnh) |
| Sao lưu | `C:\xampp\isha-backups\2026-11-10_after-phase4\` |

## 1. Kết luận ngắn

1. **Cả sáu cảnh đã chạy** trên desktop, điện thoại, và có trạng thái tĩnh khi bạn bật "giảm chuyển động" của hệ điều hành hoặc khi JS không chạy được:
   - **Câu chuyện:** vệt sáng quét xuống đoạn chữ lớn; chữ chưa quét mờ ở 22%, quét xong khi đỉnh section chạm 35% màn hình.
   - **Triết lý:** cảnh ghim 4 đoạn; từng chữ I, S, H, A "đầy nắng" từ chân lên (màu Gold), cả logo trượt ngang để chữ đang kể nằm ở 38% bề ngang, phần mô tả chuyển bằng crossfade; có chỉ báo I S H A ở mép dưới.
   - **Dịch vụ:** cảnh ghim kiểu truck shot; sáu khung trượt ngang theo cuộn (mỗi khung 0,8 màn hình cuộn), ảnh khung mới vào mở qua 11 lá rèm dọc so le, ảnh trượt ngược chiều tạo chiều sâu, 6 vạch chỉ mục ở chân.
   - **Không gian:** dải ảnh trôi ngang theo cuộn như lia máy, chú thích trôi nhanh hơn 1,15 lần, kéo bằng chuột được.
   - **Tầm nhìn:** khung hình tiến chậm (1,04 về 1) và chữ ký tự viết từ trái sang phải.
   - **Cảnh kết:** khung hình thu về vòng tròn quanh logo (iris khép) kèm vòng mảnh Gold; video nền (khi có) chỉ phát lúc cảnh đang hiện và luôn có nút tạm dừng.
2. **Điện thoại và máy tính bảng dọc không ghim:** triết lý và dịch vụ là hàng vuốt ngang như Giai đoạn 2, chữ cái đầy nắng và rèm mở theo thời gian một lần khi thẻ hiện ra; câu chuyện quét 1,4 giây; cảnh kết khép trong 1,2 giây.
3. **Đã áp dụng đúng chỉ dẫn của bạn:** JS dưới 90 KB (đang 87,1 KB, mục 5), giữ font OTF/TTF, vai trò font Garamond cho tiêu đề và nhãn, Montserrat cho đoạn văn và nút, kiểm tra nhẹ, giữ nguyên nhịp intro và iris của hero, giữ chỗ trống cho menu.
4. **Chưa thử được với ảnh, video và chữ ký thật** vì chưa có media: ảnh chụp dùng dữ liệu mẫu (ảnh giả tạo bằng máy). Trên site thật, section Không gian đang ẩn vì chưa có gallery; chữ ký và ảnh nền cảnh kết chưa có.
5. **Có 5 điểm cần bạn nhìn và quyết** ở mục 6.

## 2. Cách xem

| Muốn xem | Làm thế nào |
| --- | --- |
| Toàn bộ | Mở http://localhost/ISHA_clinic/ và cuộn từ trên xuống. Triết lý ghim khoảng 3,4 màn hình, Dịch vụ khoảng 5 màn hình cuộn. |
| Dịch vụ: nhảy tới một nhóm | Bấm một trong 6 vạch ở chân khung, hoặc mở địa chỉ có `#isha-service-<tên nhóm>`, hoặc Tab vào một liên kết liệu trình (trang tự cuộn tới khung đó). |
| Không gian | Cần thêm ảnh vào thẻ Không gian (Trang chủ > Nội dung trang chủ), section mới hiện. Xem thử trước ở ảnh `10-space-dai-anh-troi.png`. |
| Chữ ký, ảnh nền cảnh kết | Chữ ký: ảnh PNG nền trong suốt trong thẻ Tầm nhìn. Ảnh hoặc video nền: thẻ Cảnh kết. Ảnh `11` và `12` trong `docs/phase4-shots/` là trang mẫu có đủ dữ liệu. |
| Giảm chuyển động | Bật "giảm chuyển động" của hệ điều hành rồi tải lại: mọi cảnh về bố cục tĩnh, không ghim, không tự phát video (ảnh `19`, `20`). |
| Điện thoại | DevTools, chế độ thiết bị 390 x 844 (ảnh `15` tới `18`). |

## 3. Đã làm gì

| Cảnh | Desktop (cuộn) | Điện thoại / giảm chuyển động |
| --- | --- | --- |
| Câu chuyện | Mask dọc trên đoạn chính: vệt chuyển 18% chiều cao khung, quét theo cuộn, xong khi đỉnh section ở 35% màn hình; cuộn ngược thì mờ lại. | Quét một lần 1,4 giây khi đoạn chính hiện 40%. Giảm chuyển động: chữ sáng sẵn. |
| Triết lý | Ghim 3,4 màn hình; logo rộng 70vw, chữ đang kể ở 38% bề ngang; chữ Gold dâng từ chân; mô tả crossfade kèm dịch lên 12px. | Hàng 4 thẻ; chữ cái lớn của thẻ đầy nắng một lần khi hiện đủ. Giảm chuyển động: danh sách dọc như Giai đoạn 2. |
| Dịch vụ | Ghim; dải khung `translateX`; 11 lá rèm; parallax; chỉ mục 6 vạch; phím Tab và địa chỉ `#` đều đưa tới đúng khung. | Hàng vuốt ngang, rèm mở đơn giản một lần khi ảnh hiện 60%. Giảm chuyển động: các khung xếp dọc, ảnh sáng sẵn. |
| Không gian | Không ghim; dải trôi bằng `scrollLeft` nên nút Trước/Sau, bàn phím và vuốt vẫn dùng được; kéo chuột; chú thích 1,15 lần. | Hàng vuốt ngang `scroll-snap`. |
| Tầm nhìn | Khung hình tiến chậm 1,04 về 1 tới khi section ở giữa màn hình; chữ ký viết 2,2 giây khi hiện 60%. | Chữ ký viết một lần; không scale. Giảm chuyển động: chữ ký hiện sẵn. |
| Cảnh kết | Iris khép theo cuộn tới 36vmin (xong lúc section ở giữa); vòng mảnh Gold theo mép iris; video nền có nút tạm dừng. | Iris khép 1,2 giây khi section hiện 50% (bỏ vòng mảnh vì nội dung cao hơn vòng tròn). Giảm chuyển động: không cắt, không tự phát video. |

Mã nguồn đã thêm: khối `story`, `philosophy`, `services`, `space`, `vision`, `cta` của `home.js` và `home.css`; ba template có thêm khối bọc hoặc nút (`philosophy.php`, `services.php`, `cta.php`); ba hàm dùng chung (`seg`, `all`, `ease`) trong vỏ `home.js`. Chi tiết từng thông số ở `docs/home-architecture.md` mục 9.

## 4. Chỗ khác với đặc tả (có chủ ý)

1. **Chữ ký tự viết dùng mask quét, không dùng `stroke-dashoffset`.** Chữ ký là ảnh `<img>` (site chặn tải SVG lên), nên không có nét vẽ để chạy `stroke-dashoffset`; đặc tả đã dự phòng "hình đặc thì quét mask từ trái sang phải" và đó là cách dùng cho mọi chữ ký.
2. **Cảnh kết có thêm vòng mảnh Gold** đi theo mép iris. Lý do: site thật chưa có ảnh nền, nếu không có vòng này thì iris khép không nhìn thấy gì. Hiện chỉ desktop; nếu bạn không thích, bỏ bằng một quy tắc CSS (`.isha-home-cta__ring { display: none }`).
3. **Cảnh kết có nút Tạm dừng/Phát video** (đặc tả Giai đoạn 2 đã dặn phải có khi video chạy, theo WCAG 2.2.2). Nút chỉ in khi có video; chưa thử với video thật, chỉ thử bằng video giả.
4. **Dịch vụ không "bám" vào từng khung** khi dừng cuộn giữa hai khung (đặc tả không yêu cầu). Xem quyết định số 2 ở mục 6.
5. **Triết lý:** hai chữ cái còn lại của logo (mờ 12%) nằm phía sau cột chữ bên phải ở các khung đầu; chữ vẫn đủ tương phản (khoảng 11:1) nhưng nhìn hơi dày. Nếu muốn thoáng hơn, đẩy cột chữ sang phải hoặc thu nhỏ logo (một dòng CSS).

## 5. Kiểm tra đã làm (nhẹ) và chưa làm

**Đã làm:**
- `php -l`, `lint74` (tương thích PHP 7.4) và `wpstyle` sạch trên các tệp đã sửa; `node --check` sạch; kiểm marker của `home.css` và `home.js` đủ khối.
- Bộ kiểm hạ tầng nhanh (`tests/infra`) và `tests/theme/data-layer.php`: **ALL PASS**.
- Trình duyệt headless (Chrome 156): ảnh chụp 6 cảnh ở nhiều mốc cuộn trên 1440 x 900, 1366 x 768, 1280 x 720 và 390 x 844; trang thật lẫn trang mẫu đủ dữ liệu; trạng thái giảm chuyển động; không có lỗi console.
- Script kiểm tương tác (tất cả đạt): bấm vạch dịch vụ, Tab vào liên kết ở khung khuất, đổi `#`, kéo chuột ở Không gian (và độ lệch được giữ khi cuộn tiếp), đổi cỡ cửa sổ desktop sang điện thoại và về (class và style inline gỡ sạch), điều khiển video cảnh kết bằng video giả.
- Phát hiện và sửa trong lúc kiểm: rèm dịch vụ ở chế độ gọn không bao giờ mở (Chrome tính `clip-path` của chính phần tử quan sát); cột chữ dịch vụ tràn khỏi màn hình thấp khi nhóm có nhiều liệu trình (đã co chữ theo chiều cao màn hình và cho cuộn bên trong làm dự phòng); vòng mảnh cảnh kết cắt ngang chữ ở điện thoại (đã bỏ ở chế độ gọn).
- Ngân sách JS: `isha-motion.js` 23.704 + `home.js` 63.397 = **87.101 byte**, còn 2.899 byte so với 90.000. Muốn vừa, tôi đã rút gọn chú thích (phần đầu tệp `isha-motion.js` và đầu các khối của `home.js`); không cắt tính năng nào.

**Chưa làm (theo yêu cầu của bạn, bạn tự kiểm):** bộ trình duyệt đầy đủ, đợt kiểm thử độc lập, đo hiệu năng, trợ năng bằng công cụ. Chưa thử: Safari, Firefox, iOS, thiết bị cảm ứng thật (cuộn ghim trên iPad ngang đáng thử), video thật. Các bộ test cũ của Giai đoạn 2 và 3 vẫn chưa cập nhật cho kiểu chữ và hero mới.

## 6. Cần bạn xem và quyết

1. **Nhịp từng cảnh.** Các con số chỉnh được từng cái: vệt quét câu chuyện (đoạn 10% tới 65% khoảng cuộn), độ dài ghim triết lý (2,4) và dịch vụ (0,8 mỗi khung), khoảng cuộn của iris cảnh kết (6% tới 52%), thời gian chữ ký (2,2 giây), thời gian các hiệu ứng ở điện thoại (1,2 đến 1,4 giây). Nói tôi cảnh nào nhanh hoặc chậm.
2. **Dịch vụ có cần "bám khung" không?** Hiện cuộn dừng giữa chừng thì thấy hai khung cùng lúc (ảnh `09`). Có thể thêm hiệu ứng tự trượt về khung gần nhất khi dừng cuộn; đổi lại cuộn sẽ có cảm giác bị kéo. Đề xuất: để như hiện tại.
3. **Media cho Giai đoạn 4** (chi tiết `docs/home-assets.md`): 6 ảnh bìa dọc 4:5 cho 6 nhóm dịch vụ; gallery Không gian 6 đến 10 ảnh (ảnh dọc và ngang xen kẽ); chữ ký tay PNG nền trong suốt (mực tối hay sáng đều được); ảnh hoặc video nền cảnh kết (tuỳ chọn). Khi có thật, nên xem lại Dịch vụ và Cảnh kết trước.
4. **Ngân sách JS cho Giai đoạn 5.** Còn 2.899 byte; Giai đoạn 5 (chọn khung giá trị, đường kẻ quy trình, carousel sự kiện và lời kể, đối tác hiện lần lượt) ước cần khoảng 10 KB. Có hai cách: tôi rút thêm khoảng 7 KB chú thích còn lại (vẫn không cắt tính năng) hoặc bạn nâng ngân sách thêm 10 KB. Đề xuất: rút chú thích, nâng chỉ khi thật sự thiếu.
5. **Những điều bạn đã hoãn** vẫn giữ nguyên: nhịp intro và iris của hero, độ đọc của kiểu chữ, menu chính (trống, bạn tự dựng sau).

## 7. Tồn đọng

| Mục | Ghi chú |
| --- | --- |
| Trang xê dịch một lần nếu JS khởi động chậm hơn 6 giây | Như Giai đoạn 3; ở Giai đoạn 4 hai cảnh ghim chỉ đổi bố cục khi JS đã chạy nên không thêm rủi ro, nhưng vẫn chưa sửa phần hero. |
| Safari, Firefox, iOS chưa thử | Đặc biệt `overflow: clip` (có dự phòng `hidden` và đặt lại `scrollLeft`), `mask-position` có tiền tố `-webkit-`, `background-clip: text`. |
| Media thật chưa thử | Video cảnh kết mới thử bằng video giả; chữ ký thật cần nền trong suốt vì CSS ép về màu sáng. |
| Bộ test cũ chưa cập nhật | Xem mục 5. |

## 8. Bước tiếp theo

Sau khi bạn duyệt: Giai đoạn 5 (giá trị cốt lõi, quy trình, sự kiện, bài viết, lời kể, đối tác) và đợt chỉnh theo ý bạn trong cùng một tiến trình, rồi Giai đoạn 6 (trang chi tiết liệu trình, có điểm duyệt) và Giai đoạn 7 (kiểm thử tổng thể và bàn giao).
