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
| 5 | Mây hồng billboard, cỏ instanced có gió, cây, camera sát mặt đất, ánh sáng dùng chung, tinh chỉnh màu (trời cuối ấm hơn) | Xong |
| 6 | `@pmndrs/detect-gpu` (benchmark tự host), governor runtime hạ bậc chất lượng, vòng đời (khung nhìn, tab ẩn, mất context) | Xong |
| 7 | Hậu kỳ desktop (god rays, bloom), tia nắng line-art, mặt sau card | Chưa |
| 8 | Fallback 2D line-art động | Chưa (hiện chế độ 2D dùng poster tĩnh) |
| 9 | Đo hiệu năng thiết bị thật, checklist nghiệm thu | Chưa |

## Tệp

- `inc/services-cinematic.php`: dữ liệu `isha_svc_data()` (transient `isha_svc_{term_id}` 12 giờ, xoá khi lưu liệu trình / sửa nhóm / gán nhóm), đăng ký script modules, nạp có điều kiện. Tắt section: bộ lọc `isha_svc_should_load` trả `false` (trang nhóm quay về lưới thẻ editorial).
- `template-parts/services-cinematic.php`: markup SSR (nội dung thật cho SEO / no-JS / giảm chuyển động) + JSON `#isha-svc-data`.
- `assets/css/services-cinematic.css`: chế độ tĩnh và `.is-enhanced`. Chỉ token màu, không mã hex.
- `assets/js/services-cinematic/`: `main.js` (entry), `config.js`, `stops.js`, `store.js`, `scroll.js`, `tier.js`, `tokens.js`, `debug.js`, `scene/` (`index`, `shading`, `sky`, `terrain`, `clouds`, `vegetation`, `noise`, `rail`, `lifecycle`), `ui/` (`card-machine`, `card-view`, `a11y`).
- `assets/vendor/`: three 0.187.0, gsap 3.15.1, lenis 1.3.27, lil-gui 0.21.0 (chép nguyên file ESM từ jsDelivr, không build); `detect-gpu@6.0.28` (`dist/index.mjs`, 16 tệp benchmark JSON trong `dist/benchmarks`, LICENSE; lấy từ tarball npm, đã đối chiếu integrity sha512).
- `tests/`: `node --test "tests/*.test.mjs"` (chạy trong thư mục theme; Node 24 cần glob, không nhận tham số thư mục). `tier.test.mjs`: chọn chế độ theo kết quả detect-gpu, thang chất lượng, governor.

## Debug

Đăng nhập quản trị, mở trang nhóm dịch vụ với `?isha-debug=1`. Bảng lil-gui: ép progress, vùng giữ mặt trời, damping, độ cao/phương vị mặt trời, tone mapping Neutral/AgX, exposure, sàn sáng và độ ấm của trời, sương, hệ số màu xanh, nhóm "Mây, cỏ, cây" (độ hồng, hue, độ đậm và độ sáng mây, gió, độ sáng cảnh vật), địa hình (sinh lại), parallax chuột, hiện đường ray. Nhóm "Hiệu năng": kết quả detect-gpu, khung hình trung bình của cửa sổ đo gần nhất, bậc chất lượng hiện tại, chọn bậc bằng tay (nâng lại được, không vượt số cỏ / cây / mây đã dựng), bật/tắt governor, ngưỡng hạ bậc và ngưỡng xuống 2D, nút xoá bậc đã nhớ trong phiên. Nút "Copy config JSON" chép cấu hình để dán vào `config.js`. Ép chế độ: thêm `&isha-mode=FULL_HIGH|FULL|LITE|2D|STATIC` (luôn bắt đầu từ bậc 0, không ghi bậc vào phiên). Console log chế độ, lý do chọn, tên GPU, và mỗi lần governor hạ bậc.

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

10. **Mặt trời neo theo màn hình** (đặc tả: độ cao/phương vị cố định). Keyframe là vị trí trên khung (`sunY` 0.47 → 0.30, `sunX` 0.48 → 0.5); cảnh chiếu điểm đó ra hướng 3D sau khi đặt camera (trước lệch chuột) nên mặt trời luôn nằm giữa phía trên dãy card ở mọi tỉ lệ khung. Độ cao thật khoảng 2° → 12°, trời ấm kiểu giờ vàng.
11. **Đĩa mặt trời tự vẽ** trong shader Sky (tắt đĩa Preetham 0.5° bị nén thành trắng): bán kính 2.2°, lõi Golden pha Daylight, viền Golden, hào quang Sunset → Golden, luôn vàng ở mọi thời điểm. Vòng tia FOCUS-MINDFUL (SVG từ `assets/images/vector-1.svg`) đặt theo hình chiếu mặt trời, cỡ theo bán kính đĩa.
12. **Độ trễ**: mặt trời / trời / sương dùng `sunSmoothed` (k = 0.9, khoảng 95% sau 3.3 giây), camera dùng `smoothed` (k = 2.5 desktop / 3.5 cảm ứng), card đổi ngay theo cuộn và chuyển trong 1.1 giây.
13. **Núi là yếu tố phụ**: đỉnh chính z = −1800, cao 300; sương ×1.5 màu pastel ấm; camera ngẩng cố định 2.5° chỉ dolly nhẹ.
14. **Ánh sáng dùng chung** (`scene/shading.js`): một bộ uniform (hướng/màu/cường độ nắng, trời bán cầu, sương, thời gian, `lightScale`) và một đoạn GLSL (`ishaLight`, `ishaAerial`, value noise) cho địa hình, cỏ, cây, mây. `scene/index.js` cập nhật một lần mỗi khung hình. Tổng 5 draw call (trời, địa hình, mây, cỏ, cây).
15. **Mây hồng** là billboard đặt theo ô góc cố định hai bên và cao hơn đường đi của mặt trời (không che chủ thể), xa hơn núi. Màu trộn trắng ấm với hồng phái sinh từ Skin theo keyframe `cloudPinkMix` (rạng đông hồng hơn). Cần hệ số sáng `cloudGain` vì bức xạ trời Preetham lớn hơn 1 nhiều; thiếu hệ số này mây xám đục.
16. **Camera hạ sát đồng cỏ** (y 8 → 11, trước là 40 → 52) để có tiền cảnh cỏ. Đường chân trời không đổi vì góc ngẩng cố định; núi và mặt trời giữ nguyên vị trí trên khung.
17. **Cỏ phân bố dọc cả đường ray**, không chỉ trước điểm đầu: mỗi lá chọn một vị trí camera ảo trên ray rồi lấy khoảng cách phía trước lệch về gần. Lá ở xa rộng hơn để giữ độ phủ trên màn hình; đất gần camera sẫm về màu gốc lá nên khe giữa các lá không lộ nền sáng. LITE (điện thoại) không có cỏ instanced. Giảm chuyển động: cỏ không lay, mây đứng yên.
18. **Tinh chỉnh màu**: ánh sáng cảnh vật +25% (`lightScale` 1.25), xanh cỏ/cây bão hoà +21% (`kGreen` 0.33 → 0.4), hồng mây bão hoà gấp đôi (`kPink` 0.3 → 0.6) và sẫm hơn chút, trời cuối ấm hơn (sàn sáng pha Golden 30%, `skyFloor` cuối 0.55 → 0.62, `rayleigh` cuối 1.0 → 0.8). Mọi màu vẫn phái sinh từ token.
19. **Cuộn dài và chậm hơn** (góp ý sau bước 5: trải nghiệm còn chóng vánh): mỗi liệu trình 180svh trên desktop, 150svh trên cảm ứng (trước là 100svh; đặt qua biến CSS `--isha-svc-seg` từ `CONFIG.SCROLL_VH_PER_SERVICE`). Lenis lerp 0.06 (mặc định 0.1), mỗi nấc bánh xe 0.85. Bấm card bên cạnh / phím mũi tên cuộn tới điểm dừng trong 2 giây (easeInOutSine). Tất cả chỉnh được trong nhóm "Cuộn" của bảng debug.
20. **Phát hiện GPU** (`tier.js`): `@pmndrs/detect-gpu` 6.0.28 (bản phát hành đủ hai tuần; 6.0.31 vừa ra cùng ngày làm việc), `benchmarksURL` trỏ về bản tự host; trình duyệt chỉ tải một tệp theo hãng GPU (1–155 KB chưa nén). Dùng chung một context WebGL2 cho kiểm tra hỗ trợ và detect-gpu, giải phóng ngay sau đó. Chờ tối đa 2.5 giây (`CONFIG.gpu.timeoutMs`). Không có số liệu thì dùng heuristics (đặc tả: 8 luồng và 8 GB → FULL, còn lại LITE), gồm cả `FALLBACK` (GPU không có trong dữ liệu) chứ không chỉ `BENCHMARK_FETCH_FAILED` như đặc tả: nguồn gfxbench ngừng cập nhật từ 12/2025 nên GPU mới đều rơi vào `FALLBACK`, coi là tier 1 sẽ hạ nhầm máy mạnh. Bản 6.x trả tier 1 (không phải 0) khi lỗi tải; code dựa vào `type`, không dựa vào số tier.
21. **WebGL chạy bằng phần mềm → 2D** (thêm so với đặc tả): context tạo với `failIfMajorPerformanceCaveat` thất bại nhưng bỏ cờ thì được, tức GPU bị chặn và trình duyệt vẽ bằng CPU. detect-gpu chỉ chặn tên `swiftshader`, không có llvmpipe hay WARP. Ở mức này cảnh 3D chỉ vài khung hình mỗi giây.
22. **Governor** (`tier.js` `buildLadder` + `createGovernor`, có test): thang bậc theo thứ tự đặc tả DPR → mật độ cỏ (một nửa) → bloom → god rays → số mây (hai phần ba) → LITE → DPR 1.0; bậc không đổi gì trên thiết bị (màn hình DPR 1, chưa có hậu kỳ) thì bỏ. Đo khoảng cách giữa các khung hình đã render (LITE bỏ qua khung khi cảnh đứng yên nên chỉ tính khung có vẽ). Cửa sổ 60 khung **hoặc 2.5 giây** (thêm giới hạn thời gian: máy 3 khung/giây cần 20 giây cho 60 khung), trung bình vượt 22ms hai lần liên tiếp thì hạ một bậc; bỏ 1.5 giây đầu, 0.8 giây sau mỗi lần hạ hoặc khi vòng render chạy lại, khoảng khựng trên 1 giây. Hạ bậc không dựng lại gì: DPR bằng `setPixelRatio`, cỏ / cây / mây bằng `instanceCount` (vị trí sinh ngẫu nhiên nên cắt đuôi là thưa đều; ô mây ưu tiên đứng trước). Không đổi được lúc chạy: antialias, lưới địa hình 256² (LITE 128² chỉ khi bắt đầu ở LITE).
23. **Bậc 2D ở cuối thang** (đặc tả dừng ở LITE): đã ở bậc thấp nhất mà trung bình vẫn trên 50ms (dưới 20 khung hình/giây) ba cửa sổ liên tiếp thì huỷ cảnh, hiện poster; card, cuộn, trợ năng vẫn chạy. Poster tĩnh dễ chịu hơn cảnh giật.
24. **Nhớ bậc trong phiên**: "không tự nâng bậc trong cùng phiên" hiểu là phiên của tab. Bậc đã hạ lưu trong `sessionStorage` (`isha-svc-gov`), trang nhóm khác mở trong cùng tab bắt đầu luôn ở bậc đó (kể cả 2D) thay vì giật vài giây rồi mới hạ; đóng tab là xoá.
25. **Vòng đời**: giữ cơ chế của giai đoạn 4 (dừng vòng render khi section ra khỏi khung nhìn hoặc tab ẩn; mất context lần đầu thì hiện poster rồi để three nạp lại tài nguyên khi khôi phục, lần hai thì chuyển poster). Thêm `onStart` để governor bỏ khoảng cách khung đầu sau mỗi lần chạy lại; đổi DPR màn hình (kéo cửa sổ sang màn hình khác) thì áp lại giới hạn DPR của bậc. Đã thử mất / khôi phục context bằng `WEBGL_lose_context`: lần 1 khôi phục đủ cảnh, lần 2 chuyển poster.

## Giới hạn đã biết (giai đoạn 6)

- Đây là "matte painting điện ảnh", không phải ảnh chụp (đặc tả đã nêu). Chưa có hậu kỳ (god rays, bloom: giai đoạn 7). Cây là tán ellipsoid cách điệu, chưa có thân.
- Trời ở điểm dừng cuối vẫn xanh nhạt ở phần cao (ấm hơn bản 4); tăng "trời ấm" hoặc "sàn sáng" trong debug nếu muốn ấm nữa.
- Sửa địa hình trong debug (sinh lại) không đặt lại cỏ và cây theo mặt đất mới; tải lại trang để thấy đúng.
- Ngưỡng governor (22ms, 50ms) và preset là điểm khởi đầu; cần đo trên iPhone, Android tầm trung và laptop đồ hoạ tích hợp (giai đoạn 9).
- Chrome Energy Saver, iOS Low Power Mode giới hạn 30 khung hình/giây: khoảng cách khung 33ms nên governor hạ dần tới LITE (không xuống 2D vì dưới 50ms). Chấp nhận vì đúng ý tiết kiệm pin.
- XAMPP gửi JSON benchmark không nén (tệp NVIDIA 145 KB). Máy chủ thật nên bật gzip/brotli cho `application/json` (còn khoảng 25 KB).
- LITE chưa dùng `precision mediump` (đặc tả "nếu ổn"): shader sương và noise dễ sọc màu ở mediump; xem lại khi đo thiết bị thật.
- Kiểm thử tự động dùng Edge headless với WebGL phần mềm; detect-gpu chặn `swiftshader` nên trang vào 2D. Để thử nhánh 3D, script kiểm thử giả lập tên GPU và bỏ cờ caveat (governor khi đó hạ bậc liên tục vì SwiftShader rất chậm, đúng hành vi mong đợi).
- Ảnh chụp kiểm tra render bằng WebGL phần mềm (SwiftShader) trong Edge headless; FPS và hiển thị trên GPU thật, iOS Safari, Android cần kiểm trên thiết bị.
