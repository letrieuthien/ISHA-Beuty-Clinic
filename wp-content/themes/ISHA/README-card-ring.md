# Vòng cung ánh kim (dãy thẻ liệu trình)

Đặc tả: `docs/prompt-section-the-vong-cung.md`. Lookdev: `tools/lookdev-card-ring.html` (lil-gui, "Copy config JSON"; `n=`, `spin=`, `gui=0`).
Mã: `assets/js/card-ring/` (`config.js`, `ring.js`, …), `assets/css/card-ring.css`. Test: `tests/card-ring-*.test.mjs`.

## Trạng thái

| Bước | Nội dung | Trạng thái |
|---|---|---|
| 1 | Hình học vòng lõm + culling + mask hai mép, thẻ là khối màu; điện thoại hàng phẳng | Xong (khách duyệt) |
| 2 | Vòng rAF, xoay tự động, spring seek + test | |
| 3 | Vật liệu thẻ, viền vàng 3 lớp, glint (đo 2 phương án) | |
| 4 | Ánh sáng: spec theo góc, bóng elip, phản chiếu, tia nắng | |
| 5 | Máy trạng thái + input + test | |
| 6 | A11y: nút tạm dừng, reduced-motion, aria | |
| 7 | Tích hợp WordPress, SSR fallback | |
| 8 | Responsive, governor, đo thiết bị thật | |

## Quyết định và chỗ lệch prompt

1. **Vị trí** (khách chọn): vòng thẻ thay dãy card trượt ngang ở chân núi của section "Bình minh lan tỏa", nằm trên cảnh WebGL. Không làm section độc lập: bỏ nền Daylight → Skin, eyebrow và tiêu đề riêng của prompt (section đã có); dữ liệu là các liệu trình của nhóm (`isha_svc_data`, đã có), không cần khối chọn thẻ ở trang chủ của `isha-core` (prompt 8.3). Bước 7 sẽ tích hợp vào `services-cinematic` thay `ui/card-view.js`.
2. **Thẻ một mặt** (khách chọn): bỏ lật hai mặt của dãy cũ; ảnh + tên + số thứ tự, thời gian / mô tả / "Xem chi tiết" hiện khi thẻ FOCUSED.
3. **Kích thước nhỏ hơn prompt** để vẫn thấy mặt trời và núi: thẻ 200 × 280 desktop (prompt 260 × 360), 180 × 252 tablet, 44vw mobile (prompt 64vw); R = P = 1100 / 900 / 700 (prompt 1400 / 1100 / 900) để độ cong lõm giữ như prompt: ở 1440 px, thẻ ở mép khung (33°) to ×1.20 so với thẻ giữa.
4. **Vòng "ảo" thay vòng 360°**: với khoảng hở 0.22 chiều rộng thẻ, mỗi thẻ chiếm khoảng 13° trên cung (R 1100, thẻ 200). Phủ đủ 360° cần khoảng 28 thẻ, mâu thuẫn với "8 – 12 thẻ" của prompt. Thẻ i đặt ở θᵢ = wrap( i · slot − spin ) trên cung span = n · slot; thẻ chỉ quay vòng ở ±span/2, mà `ringCount` bảo đảm span/2 ≥ CULL_DEG + slot/2 (ngoài vùng nhìn thấy, sau lưng người xem), nên không nhảy hình và không nhân bản DOM lúc chạy. Số thẻ là bội của số liệu trình (bản sao `aria-hidden`): 4 liệu trình → 16 thẻ desktop, 12 tablet / mobile.
5. **Chiều quay**: `card: rotateY(−θ) translateZ(−R)`, θ > 0 ở bên phải; spin tăng thì vòng trôi sang trái (như cuộn phim).
6. **Mask hai mép** dùng màu token (mask chỉ đọc alpha) thay `#000` của prompt (quy tắc không hex mới).
7. Bước 1 đo (lookdev, nền là poster của cảnh): 1440 × 900 thấy 5 thẻ + 2 nửa, mép ×1.20; 1024 × 768 ×1.15; 390 × 844 chỉ một thẻ giữa + hai mép thẻ bên, độ cong gần như không thấy (×1.04). Muốn cong rõ trên điện thoại phải giảm R (thẻ bên ra khỏi khung) hoặc thu nhỏ thẻ: chờ khách duyệt.
8. **Điện thoại: thẻ phẳng** (khách chọn, vì vòng 3D trên khung hẹp gần như phẳng, mép ×1.04): một hàng ngang cùng kích thước, không 3D (`.is-flat`, `translate3d`), vẫn chạy bằng cùng biến spin (x = θ / slot · pitch) để chuyển động, seek và máy trạng thái dùng chung; số thẻ `flatCount`: hàng dài hơn khung + 2 thẻ. Desktop và tablet giữ vòng lõm.
