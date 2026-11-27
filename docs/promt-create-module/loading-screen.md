# TASK: Xây dựng Loading Screen "Cánh Cửa Phòng Trị Liệu" (Split Curtain + Etched Ridge + Mist Parting + Sunburst Logo)

## 1. Bối cảnh & Tài nguyên ảnh (TẤT CẢ ĐÃ TÁCH NỀN TRONG SUỐT)
Trước khi viết code, hãy kiểm tra `header.php`, `functions.php` của theme `ISHA` và xác nhận các file ảnh đã có sẵn tại đúng đường dẫn sau (chuyển sang URL bằng `get_template_directory_uri()`):
- **Dãy núi khắc chìm trên cánh cửa:** `assets/img/intro/ridge.webp`
- **Mây/sương dưới chân cửa:**
  - Trái: `assets/img/scene/clouds-4.webp`
  - Phải: `assets/img/scene/clouds-5.webp`
- **Cụm biểu tượng trung tâm:**
  - Logo chính giữa: `assets/images/Alternative-Logo-1.png`
  - Vầng tia bao quanh logo: `assets/images/vector-2.png`

**Lưu ý quan trọng:**
- Toàn bộ 5 ảnh trên đều **đã tách nền trong suốt**, KHÔNG cần dùng mẹo `invert(1) + mix-blend-mode: screen` để xóa nền trắng.
- Kiểm tra nhanh màu sắc thực tế của `Alternative-Logo-1.png`: nếu logo gốc đang là màu tối, hãy điều chỉnh CSS `filter` (hoặc `mask-image`) sang màu sáng `#F4F1EA` / vàng ánh kim để nổi rõ trên nền cửa `#141414`; nếu logo đã là màu sáng/vàng đồng thì giữ nguyên sắc gốc.
- **Ràng buộc kỹ thuật:** 100% **PHP (7.4+), CSS, Vanilla JS thuần** — không thư viện ngoài, không build step, ưu tiên mượt 60fps trên Mobile (`< 768px`).

---

## 2. Cấu trúc lớp hiển thị (`#isha-preloader`)
Tạo `<div id="isha-preloader" class="isha-preloader" aria-hidden="true">` phủ toàn màn hình (`position: fixed; inset: 0; z-index: 99999; height: 100vh; height: 100dvh; overflow: hidden;`):

### Lớp 1: Hai cánh cửa tách ngang + Dãy núi khắc chìm (`ridge.webp`)
- `.isha-preloader__door--left` (`left: 0; width: 50vw; height: 100%; background: #141414; overflow: hidden;`)
- `.isha-preloader__door--right` (`right: 0; width: 50vw; height: 100%; background: #141414; overflow: hidden;`)
- Phủ 1 lớp ambient glow vàng đồng cực dịu (`radial-gradient`, opacity `~0.07`) ở vùng trung tâm để mặt cửa có chiều sâu.
- **Khắc chìm `ridge.webp` khớp đường cắt giữa:**
  - Bên trong **mỗi cánh cửa**, đặt một lớp `.isha-preloader__ridge` rộng **`100vw`**, neo sát đáy (`bottom: 0`), chiều cao `clamp(190px, 38vh, 420px)`, `background-size: cover; background-position: center bottom; pointer-events: none;`.
  - Cánh trái neo `left: 0;`, cánh phải neo `right: 0;` để khi đóng 2 nửa ghép khít thành 1 bức tranh liền mạch, khi mở sẽ tách đôi sắc nét theo mép cửa.
  - Vì `ridge.webp` đã tách nền và mang sẵn tông nâu đồng, chỉ cần hạ `opacity: 0.20` (có thể tinh chỉnh `0.18–0.24`), thêm `filter: brightness(0.95) contrast(1.05)` và làm mờ nhẹ phần đỉnh bằng:
    ```css
    -webkit-mask-image: linear-gradient(to top, rgba(0, 0, 0, 1) 48%, rgba(0, 0, 0, 0) 100%);
    mask-image: linear-gradient(to top, rgba(0, 0, 0, 1) 48%, rgba(0, 0, 0, 0) 100%);
    ```
- Thêm 1 viền ánh kim siêu mảnh (`1px solid rgba(142, 104, 55, 0.35)`) ở mép trong của mỗi cánh cửa (mép phải cánh trái, mép trái cánh phải) để tạo cảm giác cánh cửa có độ dày tinh tế khi trượt mở.

### Lớp 2: Sương mây dưới chân cửa (`clouds-4.webp`, `clouds-5.webp`)
Đặt `.isha-preloader__mist` sát đáy màn hình (`position: absolute; inset: auto 0 0 0; height: clamp(170px, 32vh, 330px); pointer-events: none; overflow: hidden; z-index: 3;`):
- **Mây trái (`clouds-4.webp`):** Neo góc dưới trái (`left: -8%; bottom: -6%; width: min(68vw, 760px)`), nhuộm ấm thành sương trầm bằng `filter: sepia(0.35) brightness(0.75); mix-blend-mode: screen; opacity: 0.12;`. Khi cửa đang đóng, trôi ngang cực chậm sang phải (`translate3d(0,0,0) → translate3d(24px,0,0)`).
- **Mây phải (`clouds-5.webp`):** Neo góc dưới phải (`right: -8%; bottom: -8%; width: min(64vw, 720px)`), cùng bộ lọc tông ấm, `opacity: 0.11;`. Khi cửa đang đóng, trôi ngược chiều sang trái (`translate3d(0,0,0) → translate3d(-24px,0,0)`).

### Lớp 3: Đường khe cửa & Cụm Logo lồng trong `vector-2.png` (`z-index: 4`)
- **Đường khe cửa dọc (`.isha-preloader__seam`):** Rộng `1px`, cao `100%`, đặt tại `left: 50%`, tô gradient ánh kim mờ 2 đầu (`transparent → rgba(142, 104, 55, 0.65) → rgba(252, 246, 186, 0.85) → rgba(142, 104, 55, 0.65) → transparent`).
- **Cụm Biểu tượng trung tâm (`.isha-preloader__emblem`):** Căn chính giữa màn hình (`position: absolute; top: 46%; left: 50%; transform: translate(-50%, -50%)`), kích thước khung vuông `clamp(180px, 24vw, 260px)`:
  1. **Vòng tia bao ngoài (`vector-2.png`):**
     - Phủ đầy khung vuông (`width: 100%; height: 100%`).
     - Vì nét gốc của `vector-2.png` có màu xanh lam, hãy chuyển sang **sắc vàng đồng ánh kim** bằng CSS `mask-image: url(.../vector-2.png)` (`mask-size: contain; mask-repeat: no-repeat; mask-position: center;`, kèm tiền tố `-webkit-`) trên một lớp nền `background: linear-gradient(135deg, #8E6837 0%, #FCF6BA 50%, #8E6837 100%); opacity: 0.72;`.
     - Thêm chuyển động xoay tròn cực chậm và tĩnh tại (`@keyframes isha-halo-spin`: `rotate(0deg) → rotate(360deg)` trong `28s linear infinite`).
  2. **Logo chính giữa (`Alternative-Logo-1.png`):**
     - Đặt đồng tâm tuyệt đối bên trong vùng tròn trống ở giữa `vector-2.png` (`position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 38%; height: auto; object-fit: contain;`).
     - Thêm nền tròn tối nhẹ (`radial-gradient(circle, rgba(20, 20, 20, 0.88) 45%, transparent 72%)`) ngay sau cụm logo để đường khe dọc không cắt ngang gây rối chữ/biểu tượng.

---

## 3. Kịch bản Chuyển động (Choreography — Tổng thời gian ~1.1s)
Chỉ animate trên GPU (`transform`, `opacity`), dùng đường cong Quiet Luxury `cubic-bezier(0.22, 1, 0.36, 1)`:

1. **Nhịp 1 — Hiện diện tĩnh tại (`0ms → 600ms`):**
   - Đường khe cửa `.isha-preloader__seam` vẽ từ tâm tỏa lên/xuống (`scaleY(0) → scaleY(1)`, `500ms`).
   - Vòng tia `vector-2.png` nở nhẹ (`scale(0.88) → scale(1)`, `opacity: 0 → 0.72`) và bắt đầu xoay chậm.
   - Logo `Alternative-Logo-1.png` hiện mờ êm ái (`opacity: 0 → 1`, `scale(0.94) → scale(1)`).
   - Lớp núi khắc `ridge.webp` và sương mây dưới chân cửa khẽ nổi lên từ `opacity: 0`.
2. **Nhịp 2 — Mở cửa & Rẽ mây (thêm class `.is-opening` khi trang sẵn sàng, tối thiểu sau `~680ms`):**
   - Đường khe cửa và cụm Emblem (`vector-2.png` + `Alternative-Logo-1.png`) thu nhẹ và tan mờ (`opacity: 0`, `scale(0.94)`, `300ms`).
   - Hai cánh cửa trượt tách ngang (`translate3d(-100%, 0, 0)` và `translate3d(100%, 0, 0)`) trong **`780ms`**, kéo theo 2 nửa của `ridge.webp` tách đôi mượt mà để lộ Hero bên dưới.
   - Hai lớp mây rẽ dạt nhanh hơn cửa một nhịp, phóng nhẹ và tan biến (`720ms`):
     - `clouds-4.webp`: `transform: translate3d(-38%, 6%, 0) scale(1.14); opacity: 0;`
     - `clouds-5.webp`: `transform: translate3d(38%, 6%, 0) scale(1.14); opacity: 0;`
3. **Nhịp 3 — Hoàn tất:**
   - Sau `transitionend` của cánh cửa, gỡ khóa cuộn `body` và ẩn/xóa hẳn `#isha-preloader` khỏi DOM.

---

## 4. Tối ưu Hiệu năng, Mobile & Chống lỗi bắt buộc
- **Chỉ chạy 1 lần mỗi phiên (`sessionStorage`):**
  - Lưu `sessionStorage.setItem('isha_preloader_seen', '1')`.
  - **Chống nhấp nháy (Anti-FOUC):** Chèn inline `<script>` siêu ngắn ngay trong `<head>` — nếu đã có `isha_preloader_seen` hoặc thiết bị bật `prefers-reduced-motion: reduce`, gắn ngay class `isha-preloader-skip` lên `<html>` để ẩn `#isha-preloader { display: none !important; }` trước khi trình duyệt vẽ frame đầu tiên.
- **Preload ảnh quan trọng:** Có thể thêm `<link rel="preload" as="image">` nhẹ cho `ridge.webp` và `Alternative-Logo-1.png` (chỉ khi cần) để tránh giật ảnh trên 4G.
- **Không ẩn `<main>`:** Giữ nguyên nội dung trang để bảo toàn điểm SEO và LCP.
- **Cơ chế chống kẹt (Fail-safe):** Luôn có `setTimeout` tối đa **`2400ms`** buộc kích hoạt mở cửa nếu `window.load` chậm.
- **Tối ưu Mobile (`< 768px`):**
  - Thu gọn chiều cao `.isha-preloader__ridge` (`clamp(160px, 28vh, 250px)`) và `.isha-preloader__mist` (`160px–200px`, mỗi đám mây `width: 85vw`) để không đè lên cụm Emblem ở giữa trên màn hình dọc.
  - Tuyệt đối không animate `filter: blur()` trong lúc chuyển động để giữ 60fps ổn định.

---

## 5. Quy trình thực hiện
1. Kiểm tra `header.php`, `functions.php` và sự tồn tại của đủ 5 file ảnh tại các đường dẫn trên; báo ngắn gọn các file sẽ thêm/sửa.
2. Triển khai PHP, CSS và Vanilla JS sạch.
3. Gửi lại câu lệnh Console (`sessionStorage.removeItem('isha_preloader_seen'); location.reload();`) để tôi test lại nhanh trên cả Desktop và Mobile.