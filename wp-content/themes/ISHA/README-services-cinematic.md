# Section "Bình minh lan tỏa" (services cinematic)

Section thay cho "Các liệu trình trong nhóm" trên trang nhóm dịch vụ. Mỗi liệu trình là một điểm dừng khi cuộn: mặt trời mọc từ chân đỉnh núi chính lên trên đỉnh, card kính mờ của liệu trình lật vào. Đặc tả gốc: `docs/ISHA Beauty Clinic_ productsList.md`.

## Trạng thái

| Bước | Nội dung | Trạng thái |
|---|---|---|
| 1 | PHP dữ liệu + transient, template SSR, CSS lưới tĩnh, thư viện tự host | Xong |
| 2 | `stops.js`, `card-machine.js` + test | Xong |
| 3 | `store`, `scroll` (sticky + ScrollTrigger + Lenis + phím), `card-view`, `a11y` | Xong |
| 4 | `tokens`, renderer, `sky`, `terrain`, sương, `rail`, vòng đời, debug lil-gui | Xong |
| 4b | Theo góp ý khách: mặt trời là chủ thể (neo màn hình, đĩa lớn vàng, hào quang, vòng tia FOCUS-MINDFUL, trôi chậm theo sau cuộn), núi thấp/xa/chìm sương, dãy card coverflow ở trung tâm | Xong, **chờ duyệt** |
| 5 | Mây hồng billboard, cỏ, cây, tinh chỉnh màu | Chưa |
| 6 | `@pmndrs/detect-gpu` (benchmark tự host), governor runtime | Chưa |
| 7 | Hậu kỳ desktop (god rays, bloom), tia nắng line-art, mặt sau card | Chưa |
| 8 | Fallback 2D line-art động | Chưa (hiện chế độ 2D dùng poster tĩnh) |
| 9 | Đo hiệu năng thiết bị thật, checklist nghiệm thu | Chưa |

## Tệp

- `inc/services-cinematic.php`: dữ liệu `isha_svc_data()` (transient `isha_svc_{term_id}` 12 giờ, xoá khi lưu liệu trình / sửa nhóm / gán nhóm), đăng ký script modules, nạp có điều kiện. Tắt section: bộ lọc `isha_svc_should_load` trả `false` (trang nhóm quay về lưới thẻ editorial).
- `template-parts/services-cinematic.php`: markup SSR (nội dung thật cho SEO / no-JS / giảm chuyển động) + JSON `#isha-svc-data`.
- `assets/css/services-cinematic.css`: chế độ tĩnh và `.is-enhanced`. Chỉ token màu, không mã hex.
- `assets/js/services-cinematic/`: `main.js` (entry), `config.js`, `stops.js`, `store.js`, `scroll.js`, `tier.js`, `tokens.js`, `debug.js`, `scene/` (`index`, `sky`, `terrain`, `noise`, `rail`, `lifecycle`), `ui/` (`card-machine`, `card-view`, `a11y`).
- `assets/vendor/`: three 0.187.0, gsap 3.15.1, lenis 1.3.27, lil-gui 0.21.0 (chép nguyên file ESM từ jsDelivr, không build).
- `tests/`: `node --test "tests/*.test.mjs"` (chạy trong thư mục theme; Node 24 cần glob, không nhận tham số thư mục).

## Debug

Đăng nhập quản trị, mở trang nhóm dịch vụ với `?isha-debug=1`. Bảng lil-gui: ép progress, vùng giữ mặt trời, damping, độ cao/phương vị mặt trời, tone mapping Neutral/AgX, exposure, sàn sáng của trời, sương, hệ số màu xanh/hồng, địa hình (sinh lại), parallax chuột, hiện đường ray. Nút "Copy config JSON" chép cấu hình để dán vào `config.js`. Ép chế độ: thêm `&isha-mode=FULL_HIGH|FULL|LITE|2D|STATIC`. Console log chế độ và lý do chọn.

## Quyết định và chỗ lệch đặc tả

1. **Dãy card coverflow ở trung tâm, vùng 58–96% chiều cao** (bản 4 từng đặt card phần ba trái; khách đề nghị về trung tâm). Card gọn khoảng 360×235px: ảnh thành thumbnail 64px, mô tả 2 dòng, hai nút một hàng. Card kề bên nhỏ, mờ, xoay nhẹ; bấm vào thì cuộn tới điểm dừng đó. Góc bo cắt bằng `clip-path` trên `<li>` (backdrop-filter trong lớp 3D không bị border-radius cắt).
2. **Kính mờ Shadow 62%** (đặc tả gợi ý 18–28%). Trên nền trời sáng, 22% cho chữ trắng không đạt AA; đặc tả cũng yêu cầu AA. Chữ nhỏ trên kính dùng Daylight; vàng golden chỉ cho icon, đường kẻ.
3. **Núi ghép kiểu "lấy cao hơn"**, không cộng dồn Gaussian: cộng dồn làm yên ngựa giữa đỉnh chính và núi phụ cao hơn cả hai (test bắt được với seed 10866). Thân núi = nửa Gaussian + nửa hàm mũ để đỉnh nhọn. Bất biến "đỉnh chính cao nhất" có test với seed của 6 nhóm hiện có + 5 seed khác, cả 6 và 4 octave.
4. **Nhãn thời điểm N = 5** là "Rạng đông, Bình minh, Nắng sớm, Nắng vàng, Toàn quang", đúng công thức `round(t × 5)` của đặc tả.
5. **"Trong section"** tính theo vị trí cuộn gồm cả hai mép (`isActive` của ScrollTrigger là false đúng tại start nên card không hiện khi stage vừa chạm đỉnh).
6. **Mây giai đoạn 4** dùng lớp mây có sẵn của `Sky` r187 ở mức mỏng; mây hồng billboard ở giai đoạn 5.
7. **Mặt sau card tạm tắt**: `backdrop-filter` của mặt trước làm phẳng ngữ cảnh 3D nên `backface-visibility` không che được mặt sau. Giai đoạn 7 tách lớp kính ra phần tử riêng.
8. **Ảnh card** nạp `loading="lazy"` cho mọi card (≤ 6 ảnh) thay vì chỉ index ± 1, để chế độ tĩnh/no-JS vẫn đủ ảnh.
9. Module nội bộ import tương đối nên không mang `?ver=`; sau khi sửa JS cần xoá cache trình duyệt khi kiểm tra.

10. **Mặt trời neo theo màn hình** (đặc tả: độ cao/phương vị cố định). Keyframe là vị trí trên khung ( 0.47 → 0.30,  0.48 → 0.5); cảnh chiếu điểm đó ra hướng 3D sau khi đặt camera (trước lệch chuột) nên mặt trời luôn nằm giữa phía trên dãy card ở mọi tỉ lệ khung. Độ cao thật khoảng 2° → 12°, trời ấm kiểu giờ vàng.
11. **Đĩa mặt trời tự vẽ** trong shader Sky (tắt đĩa Preetham 0.5° bị nén thành trắng): bán kính 2.2°, lõi Golden pha Daylight, viền Golden, hào quang Sunset → Golden, luôn vàng ở mọi thời điểm. Vòng tia FOCUS-MINDFUL (SVG từ ) đặt theo hình chiếu mặt trời, cỡ theo bán kính đĩa.
12. **Độ trễ**: mặt trời / trời / sương dùng  (k = 1.5, khoảng 95% sau 1.5–2 giây), camera dùng  (k = 5/8), card đổi ngay theo cuộn.
13. **Núi là yếu tố phụ**: đỉnh chính z = −1800, cao 300; sương ×1.5 màu pastel ấm; camera ngẩng cố định 2.5° chỉ dolly nhẹ.

## Giới hạn đã biết (giai đoạn 4)

- Đây là "matte painting điện ảnh", không phải ảnh chụp (đặc tả đã nêu). Địa hình còn trơn, chưa có cỏ, cây, mây hồng, hậu kỳ.
- Trời ở các điểm dừng cuối còn xanh dương kiểu Preetham; tăng "sàn sáng" hoặc giảm `rayleigh` trong debug nếu muốn ấm hơn.
- Ảnh chụp kiểm tra render bằng WebGL phần mềm (SwiftShader) trong Edge headless; FPS và hiển thị trên GPU thật, iOS Safari, Android cần kiểm trên thiết bị.
