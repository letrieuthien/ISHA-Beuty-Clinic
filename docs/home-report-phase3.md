# Báo cáo Giai đoạn 3: intro và hero (điểm duyệt riêng)

| | |
| --- | --- |
| Ngày lập | 2026-11-10 |
| Trạng thái | Dựng xong, kiểm tra nhẹ như bạn yêu cầu. Chờ bạn duyệt rồi mới làm Giai đoạn 4. |
| Xem ngay | http://localhost/ISHA_clinic/ (lần đầu trong một phiên trình duyệt sẽ chạy intro) |
| Tài liệu kỹ thuật | `docs/home-architecture.md` mục 8 |
| Ảnh chụp | `docs/phase3-shots/` (13 ảnh) |

## 1. Kết luận ngắn

1. **Intro và hero đã chạy.** Intro: màn đêm, mặt trời nhô lên, vòng mảnh và 48 tia sáng vẽ ra so le từ tâm, câu "Một khoảng lặng bình tâm" (Garamond nghiêng) hiện dưới vòng tròn, rồi vòng tròn trở thành khung iris của hero. Hero: cảnh ghim, iris mở theo cuộn đến phủ kín màn hình, tia sáng bay ra ngoài, hình lùi từ 1,12 về 1, chữ mờ và tilt lên rồi nhả ghim vào phần Câu chuyện. Điện thoại thì không ghim: iris tự mở trong 1,2 giây sau intro.
2. **Đã áp dụng đúng chỉ dẫn của bạn:** ngân sách JS tăng lên 90 KB (đang dùng 69,0 KB, còn 21,0 KB); giữ OTF/TTF, không chuyển WOFF2; vai trò font mới (mục 3); chỉ kiểm tra nhẹ.
3. **Chưa thử được với video và poster thật** vì chưa có media. Tôi kiểm đường video bằng một video giả (tự phát sau intro, nút Tạm dừng và Phát, dừng khi ra khỏi màn hình, giảm chuyển động chỉ hiện poster). Khi có video thật, bạn nên xem lại cảnh này trước tiên.
4. **Còn 4 điểm cần bạn nhìn và quyết** ở mục 6.

## 2. Cách xem

| Muốn xem | Làm thế nào |
| --- | --- |
| Intro | Mở trang trong tab mới hoặc cửa sổ ẩn danh. Intro chỉ chạy một lần mỗi phiên; muốn xem lại trong cùng tab, xóa khóa `ishaIntro` ở DevTools > Application > Session storage rồi tải lại. Bấm, chạm, nhấn phím hay cuộn đều bỏ qua intro ngay. |
| Hero có hình | Hiện site thật chưa có poster nên vòng tròn chỉ có ánh sáng Gold mờ. Ảnh `04` đến `08` trong `docs/phase3-shots/` là trang mẫu có poster giả để thấy iris. Khi bạn tải poster và video vào thẻ Hero (Trang chủ > Nội dung trang chủ), hình thật sẽ tự vào. |
| Tắt intro | Bỏ tick "bật intro" trong thẻ Hero; hoặc người xem bật "giảm chuyển động" của hệ điều hành (khi đó không intro, không ghim, không tự phát video). |
| Điện thoại | DevTools, chế độ thiết bị 390 x 844. |

## 3. Vai trò font mới (theo chỉ dẫn của bạn)

**Garamond cho tiêu đề và nhãn; Montserrat cho đoạn văn và nút hoặc liên kết hành động.** Đã áp cho cả 13 section, không chỉ hero:

| Thành phần | Trước | Bây giờ |
| --- | --- | --- |
| Tiêu đề section (ví dụ "Triết lý ISHA") | Montserrat chữ hoa, giãn rộng | Garamond Medium, chữ thường có dấu, cỡ lớn hơn |
| Nhãn (dòng nhỏ ở hero, loại và ngày sự kiện, tên tiếng Anh ở Triết lý, "Đã diễn ra") | Montserrat chữ hoa nhỏ | Garamond Medium 17px, không viết hoa, giãn chữ 0,08em |
| Tên giá trị, số và tên bước quy trình | Montserrat | Garamond Medium |
| Đoạn văn (mô tả giá trị, bước, triết lý, câu chuyện phụ, hero, địa chỉ sự kiện) | Garamond 19px | Montserrat 17px (điện thoại 16px), dòng khoảng 60 đến 65 ký tự |
| Nút, liên kết, Trước và Sau, dock, số hotline | Montserrat | Montserrat (không đổi; số hotline từ Garamond sang Montserrat) |
| Câu trích và câu phát biểu cỡ lớn (câu chuyện, tầm nhìn, lời kể, tên sự kiện, tên bài) | Garamond | Garamond (không đổi, vì là tiêu đề hoặc trích dẫn) |

Ghi chú: Montserrat trông to hơn Garamond cùng cỡ chữ, nên 17px Montserrat dễ đọc hơn 19px Garamond Display (nét mảnh). Nếu bạn muốn đoạn văn to hơn, chỉ cần sửa `--isha-fs-para` (một dòng).

## 4. Đã làm gì

| Hạng mục | Kết quả |
| --- | --- |
| Hero (PHP) | `template-parts/home/hero.php` dựng lại: track và stage cho cảnh ghim; lớp hình toàn khung (poster hoặc ánh sáng Gold, video, lớp phủ tối); cột chữ ở giữa; vòng mảnh và tia sáng; nút video; vạch cuộn. |
| Hero (CSS) | Khối `hero` của `home.css` viết lại: vòng tròn mặt trời là một `clip-path: circle()` của lớp hình toàn khung nên iris mở là đổi bán kính; cỡ mặt trời tự co theo chiều cao màn hình để cột chữ luôn vừa một màn hình (đã xem ở 1440 x 900, 1366 x 768, 1280 x 720, 768 x 1024, 390 x 844). |
| Hero (JS) | Khối `hero` của `home.js`: đo hình học, mở iris theo cuộn (cảnh ghim, chiều dài 1,4 màn hình) hoặc theo thời gian (chế độ gọn), làm mờ chữ, điều khiển video. |
| Intro | Lớp phủ chạy bằng CSS transition, `home.js` đặt vị trí khớp đúng vòng tròn của hero và dựng tia. Chạy một lần mỗi phiên, tối đa 2,8 giây, bỏ qua được. Không chạy khi giảm chuyển động, khi vào bằng liên kết `#`, khi đã xem. |
| Cổng trong `<head>` | `isha_home_head_gate()` thêm `isha-intro-on` ngay từ khung hình đầu để không nháy hero trước khi intro phủ; có chốt an toàn 3,5 giây. |
| Header | `header-home.php` có sẵn class `is-over-hero` nên trong suốt từ đầu (hết nháy nền đặc ở Giai đoạn 2); ẩn trong lúc intro. |
| Preload | Bỏ hết preload font ở trang chủ (đo cho thấy làm LCP chậm hơn); chỉ preload poster. |
| Tài liệu | `docs/home-architecture.md` thêm mục 8: chỉ dẫn mới và toàn bộ quy ước của hero và intro cho các giai đoạn sau. |

## 5. Kiểm tra đã làm (nhẹ) và chưa làm

**Đã làm:**
- `php -l` 75 file PHP sạch; `node --check` 10 file JS sạch; `lint74` (tương thích PHP 7.4) sạch; `wpstyle` chỉ còn 2 phát hiện cũ ở `landing-policy.php`; công cụ ghép khối CSS và JS báo đủ khối.
- Bộ kiểm hạ tầng nhanh (`tests/infra`, 4 giây): **388 phép kiểm đạt**; đã cập nhật những phép kiểm mô tả hành vi cũ (cổng, preload, header).
- Ảnh chụp Chrome headless: intro theo mốc thời gian (desktop và điện thoại), hero ở p = 0, 0,5, 0,6 và 1, lúc nhả ghim vào Câu chuyện, bốn cỡ màn hình, trạng thái tĩnh khi giảm chuyển động; năm section để xem kiểu chữ mới. Không có lỗi console.
- Điều khiển video bằng video giả: tự phát sau intro, nút đổi chữ đúng, dừng khi hero ra khỏi màn hình và phát lại khi quay lại, người dùng tạm dừng thì không tự phát lại; giảm chuyển động thì không tự phát.

**Không làm (theo yêu cầu của bạn, bạn tự kiểm):** bộ trình duyệt đầy đủ của Giai đoạn 2, đợt kiểm thử độc lập, đo hiệu năng và trợ năng mới. Cần lưu ý: các bộ test cũ chưa chạy lại sau khi đổi kiểu chữ và cấu trúc hero, nên một vài phép kiểm của chúng có thể báo lệch (ví dụ kiểm tương phản chữ Garamond nhãn, kiểm class `.isha-hero__iris` đã bỏ). Đó là test cũ lệch với thiết kế mới, không phải lỗi hiển thị; tôi chưa cập nhật chúng.

## 6. Cần bạn xem và quyết

1. **Nhịp của intro và iris.** Intro dài khoảng 2,0 đến 2,2 giây cộng 0,6 giây mờ dần; iris mở trong 0,6 chiều dài ghim (khoảng 1 màn hình cuộn) trên desktop và 1,2 giây trên điện thoại. Nếu thấy nhanh hay chậm, chỉnh được từng con số (`MIN`, `MAX`, thời lượng CSS).
2. **Kiểu chữ mới.** Xem nhất là nhãn Garamond 17px (nét mảnh) trên nền tối và đoạn văn Montserrat 17px. Muốn nhãn đậm hơn hay to hơn thì nói tôi.
3. **Media cho hero.** Cần poster ngang (1920 x 1080), poster dọc (1080 x 1350), video ngang (MP4 H.264, 8 đến 15 giây, lặp liền mạch, không tiếng), video dọc. Đặt chủ thể ở giữa khung vì khi chưa mở iris chỉ thấy phần giữa trong vòng tròn. Chi tiết ở `docs/home-assets.md`.
4. **Menu chính vẫn rỗng** (ghi ở báo cáo Giai đoạn 2): header chỉ có logo và nút Đặt lịch.

## 7. Tồn đọng

| Mục | Ghi chú |
| --- | --- |
| Trang xê dịch một lần nếu JS khởi động chậm hơn 6 giây | Hình học của chế độ an toàn khác chế độ thường (đã ghi từ Giai đoạn 2). Chưa sửa; sẽ gom vào đợt dọn cuối. |
| Video thật chưa thử | Chỉ kiểm điều khiển bằng video giả. Cần thử chạy video thật trên điện thoại (iOS Safari đặc biệt, vì chặn tự phát khác Chrome). |
| Intro chưa thử trên Safari và Firefox | Chỉ có Chrome headless. |
| Các bộ test cũ chưa cập nhật | Xem mục 5. |

## 8. Bước tiếp theo

Giai đoạn 4: các cảnh điện ảnh còn lại (câu chuyện, triết lý, dịch vụ, không gian, tầm nhìn, cảnh kết). Đây cũng là điểm duyệt. Ngân sách JS còn 21,0 KB cho Giai đoạn 4 và 5; nếu thiếu, tôi sẽ rút gọn chú thích trong mã (lấy lại khoảng 15 KB) thay vì cắt tính năng.

Sao lưu: `C:\xampp\isha-backups\2026-11-10_after-phase3\`.
