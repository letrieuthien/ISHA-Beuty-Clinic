/**
 * Hằng số tinh chỉnh của gallery 3D section Founder (trang chủ). Đơn vị:
 *   - "ảnh": tiến độ gallery tính bằng số ảnh (pos = 0 là ảnh đầu đang ở giữa đường đi; pos = END là đã đi hết, ảnh chân dung hiện);
 *   - t: tiến độ đi của MỘT ảnh, 0 = vừa nhú ở tâm khung, 1 = chạm mép khung (đã biến mất);
 *   - px: điểm ảnh CSS của khung gallery; giây (s), mili giây (ms).
 * Chỉnh ở đây, không cần đụng code khác. Đặc tả: docs/founder-section-wp.md; ghi chú quyết định: docs/home-architecture.md mục 21.
 *
 * Mô hình (từ 19/11/2026): ảnh nhú ra ở TÂM khung, nhỏ và mờ, rồi đi theo một hướng riêng ra mép khung; càng ra xa càng lớn và rõ; từ
 * 80% quãng đường thì mờ dần, biến mất khi chạm mép. Ảnh i có t = 0,5 + (pos - i) / LIFE: ảnh "hiện hành" (pos = i) ở giữa đường đi.
 * Một ảnh đi hết đường trong LIFE bước và các ảnh cách nhau 1 bước, nên cùng lúc trên khung có tối đa LIFE ảnh.
 */
export const CONFIG = Object.freeze( {
	/* --- Nạp ------------------------------------------------------------------------------------------------------------------- */
	NEAR_MARGIN: '900px 0px',  // Bắt đầu tải three.js khi khung gallery còn cách khung nhìn chừng này.

	/* --- Camera ---------------------------------------------------------------------------------------------------------------- */
	FOV: 50,                   // Góc nhìn dọc (độ).
	FOCUS_DIST: 6,             // Khoảng cách camera tới mặt phẳng ảnh (đv thế giới). Kích thước và vị trí tính bằng px khung rồi đổi sang đv.
	Z_LIFT: 0.25,              // Ảnh càng đi xa tâm càng gần camera chừng này (đv): xếp lớp ảnh cũ nằm trên ảnh mới, cho hit-test đúng.

	/* --- Đường đi ---------------------------------------------------------------------------------------------------------------- */
	LIFE: 3,                   // Số ảnh tối đa xuất hiện cùng lúc (desktop). 3 ảnh: mỗi ảnh đi hết đường trong 3 bước.
	LIFE_LITE: 3,              // Điện thoại (Mobile Lite).
	EDGE_REACH: 1.0,           // Tâm ảnh tới đúng mép khung khi t = 1 (1 = sát mép; 0,9 = dừng cách mép 10%).
	PATH_EASE: 1.3,            // > 1: nhú chậm ở tâm rồi nhanh dần khi ra xa (cảm giác bay ra); 1 = đều.
	SEED: 7,                   // Hạt giống hướng đi. Hướng ảnh i = i x góc vàng + SEED + lệch ngẫu nhiên: hai ảnh liền nhau luôn đi hai hướng rất khác nhau.
	ANGLE_JITTER: 0.5,         // Độ lệch ngẫu nhiên của hướng (radian, +/-).
	ASPECT_MIN: 2 / 3,         // Tỉ lệ hiển thị được kẹp trong 2:3 (dọc) .. 16:9 (ngang); ảnh ngoài khoảng bị cắt mép (cover).
	ASPECT_MAX: 16 / 9,

	/* --- Kích thước (cạnh lớn nhất của ảnh) --------------------------------------------------------------------------------------- */
	START_PX: 150,             // Ở tâm: 150px.
	START_PX_LITE: 120,        // Điện thoại.
	END_FILL: 0.5,             // Lớn nhất (t = 1) = 50% chiều cao khung gallery (đặc tả: 45–55%)...
	END_FILL_W: 0.62,          // ...và không quá 62% chiều rộng khung (khung hẹp như điện thoại dọc).
	SIZE_EASE: 1.15,           // Độ cong tăng kích thước theo t (1 = tuyến tính; lớn hơn = lớn chậm lúc đầu, nhanh lúc cuối).
	RADIUS_PX: 8,              // Bo góc ảnh trong gallery.

	/* --- Độ mờ (opacity) --------------------------------------------------------------------------------------------------------- */
	OPA_START: 0.15,           // Ở tâm: 15%.
	RISE_END: 0.4,             // Tăng dần tới 100% khi đi được 40% quãng đường.
	FADE_START: 0.8,           // Từ 80% quãng đường thì mờ dần, về 0 khi chạm mép khung.
	PRE_T: 0.04,               // 4% quãng đường đầu: opacity nhân thêm t / 0,04 (0 -> 1) để ảnh không bật hình đột ngột ở tâm.
	DIM_FAR: 0.3,              // Ảnh mới nhú hoà thêm 30% vào màu nền, hết dần khi tới RISE_END.

	/* --- Làm mờ nét (chỉ desktop) ------------------------------------------------------------------------------------------------ */
	MAX_BLUR: 1.6,             // Bán kính mẫu tối đa (texel); bản mẫu là 3–8, ở đây tiết chế cho dịu mắt.
	BLUR_IN: 0.3,              // Mờ nét khi mới nhú, rõ nét hẳn từ t = 0,3.
	BLUR_OUT: 0.6,             // Lúc sắp biến mất mờ nét thêm tới 60% MAX_BLUR.

	/* --- Tốc độ (ảnh/giây): tự trôi và lăn chuột dùng CHUNG một tốc độ ------------------------------------------------------------- */
	AUTO_SPEED: 0.9,           // Tốc độ đi của ảnh khi tự trôi VÀ khi lăn chuột / bấm phím: khoảng 1,1 giây mỗi ảnh (bản cũ 0,3 = 3,3 giây).
	                           // Muốn mỗi ảnh cách nhau 300 ms thì đặt 3,3.
	WHEEL_SPEED: 1.25,         // Lăn chuột đi nhanh hơn tự trôi 0,25 lần (1,25 x AUTO_SPEED = 1,125 ảnh/giây); bấm phím vẫn đúng AUTO_SPEED.
	AUTO_ACCEL: 1.2,           // Tự trôi tăng tốc êm (1/s).
	AUTO_IDLE: 3,              // Tự trôi (Zen) sau 3 giây không tương tác, một chu kỳ rồi dừng ở ảnh chân dung.
	HOVER_DAMP: 3,             // Rê chuột lên ảnh: hãm tự trôi về 0 (1/s), rời chuột thì trôi tiếp.
	WHEEL_TRAVEL: 0.0102,      // Quãng đường mỗi px lăn chuột (ảnh/px): 100px = 1,02 ảnh (bản cũ 1,00: dài hơn 2%). Ảnh chạy tới đích với tốc độ AUTO_SPEED.
	QUEUE_MAX: 2.5,            // Lăn nhanh tới đâu thì đích cũng chỉ nằm cách vị trí hiện tại tối đa 2,5 ảnh (ngừng lăn là ảnh dừng sau tối đa ~3 giây).
	SEEK_ACCEL: 3,             // Gia tốc khi bắt đầu / dừng lại lúc lăn chuột, bấm phím (ảnh/s²).
	FAST_MULT: 4,              // Home / End đi nhanh gấp 4 lần tốc độ thường.
	SETTLE_AFTER: 0.32,        // Ngừng thao tác chừng này (s) thì tự trượt tới vị trí ảnh gần nhất theo hướng vừa đi.
	SETTLE_BIAS: 0.15,         // Đi quá 0,15 ảnh theo một hướng là đủ để vào nhịp kế tiếp theo hướng đó.
	DRAG_GAIN: 3,              // Kéo hoặc vuốt hết bề ngang khung = 3 ảnh (theo ngón tay 1:1).
	FRICTION: 4.2,             // Hãm quán tính sau khi thả tay (1/s, giảm theo hàm mũ).
	V_MAX: 1.8,                // Vận tốc quán tính tối đa sau cú vuốt nhanh (gấp đôi tốc độ thường).
	FORCE_SCALE: 1.5,          // Vận tốc (ảnh/s) đổi ra lực uốn cho shader.
	FORCE_MAX: 4,

	/* --- Biên độ Zen của shader (so với bản mẫu) --------------------------------------------------------------------------------- */
	ZEN: 0.3,                  // Uốn cong theo lực cuộn và gợn vải còn 30% bản mẫu. Không có sóng lá cờ khi hover.
	SEGMENTS: 24,              // Lưới mặt phẳng (desktop) đủ mịn cho độ cong; bản điện thoại dùng 1×1 (không uốn).

	/* --- Hover: viền vàng và vệt ánh kim (desktop) ------------------------------------------------------------------------------- */
	HOVER_MIN_OPACITY: 0.6,    // Chỉ nhận hover / click ảnh đang hiện rõ.
	HOVER_RATE: 8,             // Viền hiện / tắt (1/s).
	LINE_PX: 1.5,              // Độ dày viền hover (px màn hình); trước là 1,25.
	GLINT_SPEED: 0.16,         // Vệt ánh kim chạy quanh chu vi: số vòng mỗi giây.
	GLINT_LEN: 0.1,            // Độ dài vệt theo chu vi (0..0,5).
	GOLD: '#8e6837',           // Antique Gold.
	GLINT: '#fcf6ba',          // Ánh kim.
	BG: '#141414',             // Nền khung gallery (trùng CSS --fs-bg).

	/* --- Ảnh chân dung ở cuối chu kỳ (tính lùi từ END, theo tỉ lệ của LIFE) ------------------------------------------------------ */
	PORTRAIT_FROM: 0.3,        // Ảnh chân dung bắt đầu hiện khi ảnh cuối còn 30% quãng đường (đã đi 70%, đang mờ dần).
	CAPTION_FROM: 0.15,        // Chức danh và tên hiện trong 15% quãng đường cuối.

	/* --- Bụi sáng vàng (Golden Dust) quanh ảnh chân dung, hiện cùng ảnh chân dung (dust.js) ------------------------------------------ */
	DUST_COUNT: 42,            // Số hạt (desktop): ít, tinh tế (bản "bầu trời sao" trước là 120).
	DUST_COUNT_LITE: 20,       // Điện thoại.
	DUST_INNER: 1.15,          // Hạt gần nhất cách tâm 1,15 lần nửa kích thước khung chân dung (ngay ngoài viền).
	DUST_SPREAD: 1.25,         // Vòng hạt rộng thêm 1,25 lần (tới khoảng 2,4 lần): toả thưa quanh khung.
	DUST_SIZE_MIN: 6,          // Cỡ đốm bokeh (px CSS), đa dạng: phần lớn nhỏ, ít đốm lớn.
	DUST_SIZE_MAX: 30,
	DUST_ALPHA_MIN: 0.15,      // Opacity của từng hạt trong khoảng 0,15–0,45 (đặc tả).
	DUST_ALPHA_MAX: 0.45,
	DUST_ORBIT: 0.012,         // Quay quanh khung cực chậm (rad/giây, mỗi hạt một chiều và tốc độ).
	DUST_RISE: 4,              // Nhích lên cực chậm (px/giây, mỗi hạt khác nhau)...
	DUST_CYCLE: 120,           // ...theo nhịp 120px, hiện dần rồi tắt dần trong mỗi nhịp.
	DUST_COLOR: '#fcf6ba',     // Ánh kim.
	DUST_COLOR_WARM: '#8e6837',// Vàng đồng: hạt pha dần giữa hai màu.

	/* --- Chạm và kéo ------------------------------------------------------------------------------------------------------------- */
	AXIS_LOCK_PX: 10,          // Ngón tay đi 10px thì chốt hướng: ngang = gallery, dọc = cuộn trang (touch-action: pan-y).
	EDGE_GUARD_PX: 16,         // Bỏ qua chạm bắt đầu sát mép trái màn hình (cử chỉ "Quay lại" của iOS Safari).
	TAP_PX: 6,                 // Chạm / click: di chuyển dưới 6px và nhanh hơn 450 ms.
	TAP_MS: 450,

	/* --- Renderer ---------------------------------------------------------------------------------------------------------------- */
	DPR: 1.5,                  // Trần tỉ lệ điểm ảnh desktop.
	DPR_LITE: 1.25,            // Trần tỉ lệ điểm ảnh bản điện thoại (đặc tả).
	TEX_LOADS: 3,              // Số ảnh tải song song.
	TEX_KEEP_EXTRA: 2,         // Giữ texture thêm 2 ảnh ngoài vùng đang hiện hoặc sắp hiện, xa hơn thì giải phóng (VRAM).
} );
