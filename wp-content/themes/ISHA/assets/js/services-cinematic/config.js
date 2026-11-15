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
	damping: { desktop: 5, mobile: 8 }, // k của smoothed += ( p - smoothed ) * ( 1 - exp( -k * dt ) )
	announceDelayMs: 400,         // aria-live chỉ đọc khi index ổn định quá thời gian này

	// Keyframe mặt trời (computeStops): độ cao, phương vị, sương gốc
	stops: { ...DEFAULT_STOP_CFG },

	// Màu phái sinh từ token (tokens.js)
	colors: {
		kPink: 0.3,       // độ bão hoà hồng mây = độ bão hoà vàng x kPink
		pinkHue: 14,      // hue hồng mây (độ)
		kGreen: 0.33,     // độ bão hoà xanh cỏ/cây = độ bão hoà vàng x kGreen
		greenHue: 112,    // hue xanh cỏ/cây (độ)
		skyFloor: 0.4,   // tỉ lệ trộn gradient token (Daylight → Skin nhạt) vào bầu trời: giữ một chế độ SÁNG
	},

	// Hiển thị
	toneMapping: 'Neutral', // Neutral | AgX (không dùng ACES: lệch hue của vàng)
	exposureScale: 1,       // nhân thêm vào exposure của keyframe
	fogScale: 1,            // nhân thêm vào mật độ sương
	mouseParallaxDeg: 0.6,  // lệch camera theo chuột (desktop)
	showRail: false,        // vẽ đường ray camera (debug)
	debugProgress: -1,      // >= 0: ép progress (thanh trượt debug), -1: theo cuộn

	// Địa hình
	terrain: {
		size: 4200,          // cạnh mặt phẳng địa hình (đơn vị thế giới)
		peakZ: -900,         // vị trí đỉnh chính (z), x = 0
		peakHeight: 640,
		peakRadius: 360,
		detailAmp: 14,
		meadowRadius: 520,   // bán kính đồng cỏ phẳng quanh camera
	},

	// Tầng chất lượng
	presets: {
		FULL_HIGH: { dpr: 1.75, grid: 256, octaves: 6, grass: 60000, trees: 400, clouds: 12, post: true, bloom: true },
		FULL: { dpr: 1.25, grid: 256, octaves: 6, grass: 30000, trees: 250, clouds: 8, post: true, bloom: false },
		LITE: { dpr: 1.25, grid: 128, octaves: 4, grass: 0, trees: 80, clouds: 4, post: false, bloom: false },
	},
};
