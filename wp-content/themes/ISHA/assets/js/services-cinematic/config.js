/**
 * Mọi hằng số chỉnh được của section "Bình minh lan tỏa". Object này được bảng debug (lil-gui, ?isha-debug=1 cho quản trị viên) đọc và ghi
 * trực tiếp; nút "Copy config JSON" xuất lại để dán vào đây khi đã ưng.
 */
import { DEFAULT_STOP_CFG } from './stops.js';

export const CONFIG = {
	// Cuộn
	SCROLL_VH_PER_SERVICE: 100,   // mỗi liệu trình 100svh; section cao (N + 1) x 100svh (thêm 1 khung cho điểm dừng cuối có thời gian đọc)
	SNAP: false,                  // snap mềm về tâm đoạn (chỉ desktop); mặc định tắt
	hysteresis: 0.02,             // trễ khi đổi liệu trình đang xem (phần của một đoạn)
	hold: DEFAULT_STOP_CFG.hold,  // vùng mặt trời đứng yên quanh điểm dừng
	// k của smoothed += ( p - smoothed ) * ( 1 - exp( -k * dt ) ). desktop/mobile: camera; sun: mặt trời, trời, sương (chậm hơn để mặt trời
	// trôi theo sau cuộn: k = 1.5 tới khoảng 95% sau 2 giây).
	damping: { desktop: 5, mobile: 8, sun: 1.5 },
	announceDelayMs: 400,         // aria-live chỉ đọc khi index ổn định quá thời gian này

	// Keyframe mặt trời (computeStops): vị trí trên màn hình đầu/cuối, sương gốc
	stops: { ...DEFAULT_STOP_CFG },

	// Màu phái sinh từ token (tokens.js)
	colors: {
		kPink: 0.6,       // độ bão hoà hồng mây = độ bão hoà vàng x kPink
		pinkHue: 14,      // hue hồng mây (độ)
		kGreen: 0.4,      // độ bão hoà xanh cỏ/cây = độ bão hoà vàng x kGreen
		greenHue: 112,    // hue xanh cỏ/cây (độ)
		skyWarm: 0.3,    // tỉ lệ pha Golden vào sàn sáng của trời (trời cuối ấm, không xanh lạnh)
		skyFloorScale: 1, // nhân vào skyFloor của keyframe (tỉ lệ trộn gradient token Daylight → Skin nhạt vào trời: giữ một chế độ SÁNG)
	},

	// Hiển thị
	toneMapping: 'Neutral', // Neutral | AgX (không dùng ACES: lệch hue của vàng)
	exposureScale: 1,       // nhân thêm vào exposure của keyframe
	fogScale: 1.5,          // nhân thêm vào mật độ sương (núi chìm sương, là yếu tố phụ)
	mouseParallaxDeg: 0.6,  // lệch camera theo chuột (desktop)
	cameraPitchDeg: 2.5,    // góc ngẩng cố định của camera: chân trời khoảng 55%, đỉnh chính khoảng 44% chiều cao khung
	cloudOpacity: 0.9,      // độ đậm mây hồng billboard (clouds.js); độ hồng theo keyframe cloudPinkMix
	cloudGain: 1.35,         // độ sáng mây so với trời (trời Preetham sáng hơn 1 nhiều; thấp quá mây sẽ xám)
	lightScale: 1.25,       // độ sáng chung của cảnh vật (địa hình, cỏ, cây) so với trời
	wind: 1,                // biên độ gió của cỏ (vegetation.js); giảm chuyển động: 0
	showRail: false,        // vẽ đường ray camera (debug)
	debugProgress: -1,      // >= 0: ép progress (thanh trượt debug), -1: theo cuộn

	// Mặt trời (chủ thể): đĩa tự vẽ trong shader trời + hào quang + vòng tia line-art FOCUS-MINDFUL (SVG)
	sun: {
		radiusDeg: 2.2,      // bán kính góc của đĩa (đĩa thật khoảng 0.27°)
		discIntensity: 2,    // cường độ đĩa (vừa phải để Neutral tone mapping không nén thành trắng)
		haloScale: 1,        // nhân vào halo của keyframe
		raysScale: 1,        // nhân vào độ đậm vòng tia line-art (cỡ vòng tia tự theo bán kính đĩa)
	},

	// Địa hình (yếu tố phụ: thấp, xa, chìm sương)
	terrain: {
		size: 6000,          // cạnh mặt phẳng địa hình (đơn vị thế giới)
		peakZ: -1800,        // vị trí đỉnh chính (z), x = 0
		peakHeight: 300,
		peakRadius: 460,
		detailAmp: 12,
		meadowZ: 800,        // tâm đồng cỏ (camera đi z 900 → 760)
		meadowRadius: 700,   // bán kính đồng cỏ phẳng quanh camera
	},

	// Tầng chất lượng
	presets: {
		FULL_HIGH: { dpr: 1.75, grid: 256, octaves: 6, grass: 60000, trees: 400, clouds: 12, post: true, bloom: true },
		FULL: { dpr: 1.25, grid: 256, octaves: 6, grass: 30000, trees: 250, clouds: 8, post: true, bloom: false },
		LITE: { dpr: 1.25, grid: 128, octaves: 4, grass: 0, trees: 80, clouds: 4, post: false, bloom: false },
	},
};
