/**
 * Hằng số của "Vòng cung ánh kim" (docs/prompt-section-the-vong-cung.md, phụ lục). Giá trị khởi đầu, chỉnh qua lil-gui
 * (tools/lookdev-card-ring.html, nút "Copy config JSON"). Không đóng băng: bảng debug ghi trực tiếp.
 *
 * Lệch prompt (README-card-ring.md):
 * - Vòng nằm trong section "Bình minh lan tỏa" (thay dãy card ở chân núi, khách chọn), trên cảnh WebGL: thẻ nhỏ hơn prompt
 *   (200 × 280 desktop thay 260 × 360) để vẫn thấy mặt trời và núi; R nhỏ theo (1100 thay 1400) để độ cong lõm như cũ (thẻ ở mép khung
 *   to hơn thẻ giữa khoảng 1.2 lần).
 * - Thẻ cách nhau 0.22 chiều rộng trên cung nên mỗi thẻ chiếm khoảng 13°: n thẻ trải trên cung n · 13° (không phải 360°). Thẻ quay vòng
 *   ở mép cung, nằm ngoài góc culling (sau lưng người xem); số thẻ nhân bản đủ để cung rộng hơn 2 · CULL_DEG.
 */
export const RING = {
	// Bán kính trụ (px) theo bề rộng khung; P (perspective) = R · P_RATIO.
	R_DESKTOP: 1100, R_TABLET: 900, R_MOBILE: 700,
	P_RATIO: 1,
	ORIGIN_Y: 45,                 // perspective-origin dọc (%): mắt hơi cao hơn tâm vòng thẻ
	// Thẻ (px; mobile theo vw), tỉ lệ khoảng 5 : 7.
	CARD_W_DESKTOP: 200, CARD_W_TABLET: 180, CARD_W_MOBILE_VW: 44,
	CARD_RATIO: 1.4,
	GAP: 0.22,                    // khoảng hở giữa hai thẻ trên cung, theo chiều rộng thẻ
	CULL_DEG: 72,
	MIN_CARDS: 8,
	EDGE_FADE: 12,                // % mỗi bên của mask mờ hai mép
	// Các bước sau (chuyển động, vật liệu, ánh sáng, tương tác): giữ đúng tên của prompt.
	OMEGA_DESKTOP: 4, OMEGA_MOBILE: 3,
	DAMP_K: 4,
	SEEK_MS: 900,
	HOVER_SCALE: 1.06, HOVER_Z: 24, FOCUS_SCALE: 1.1,
	DIM_OTHERS: 0.55,
	SPEC_MAX: 0.85, SPEC_POW: 3,
	GLINT_PERIOD_S: 6, GLINT_PERIOD_HOVER_S: 2.4, GLINT_STAGGER_S: 0.7,
	BORDER_PX_DESKTOP: 1.5, BORDER_PX_MOBILE: 1,
	RADIUS_DESKTOP: 14, RADIUS_MOBILE: 12,
	RESUME_AFTER_SWIPE_MS: 3000,
	TAP_SLOP_PX: 8,
	GOVERNOR_FPS: 45,
};
