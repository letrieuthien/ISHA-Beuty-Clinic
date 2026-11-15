/**
 * Mọi hằng số chỉnh được của section "Bình minh lan tỏa". Object này được bảng debug (lil-gui, ?isha-debug=1 cho quản trị viên) đọc và ghi
 * trực tiếp; nút "Copy config JSON" xuất lại để dán vào đây khi đã ưng.
 */
import { DEFAULT_STOP_CFG } from './stops.js';

export const CONFIG = {
	// Cuộn
	// Độ dài cuộn của mỗi liệu trình (số svh). Section cao N x đoạn + 100svh (khung của stage dính). Dài hơn = mỗi nấc cuộn đi ít hơn, cảnh
	// chuyển chậm hơn. Cảm ứng ngắn hơn chút vì vuốt có quán tính.
	SCROLL_VH_PER_SERVICE: { desktop: 180, mobile: 150 },
	// Lenis (chỉ chuột / trackpad): lerp nhỏ = trôi mượt và chậm hơn; wheelMultiplier < 1 = mỗi nấc bánh xe cuộn ít hơn.
	lenis: { lerp: 0.06, wheelMultiplier: 0.85 },
	goDuration: 2,                // giây: bấm card bên cạnh / phím mũi tên cuộn tới điểm dừng kế (easeInOutSine)
	SNAP: false,                  // snap mềm về tâm đoạn (chỉ desktop); mặc định tắt
	hysteresis: 0.02,             // trễ khi đổi liệu trình đang xem (phần của một đoạn)
	hold: DEFAULT_STOP_CFG.hold,  // vùng mặt trời đứng yên quanh điểm dừng
	// k của smoothed += ( p - smoothed ) * ( 1 - exp( -k * dt ) ). desktop/mobile: camera (k = 2.5 tới khoảng 95% sau 1.2 giây); sun: mặt
	// trời, trời, sương (chậm hơn để mặt trời trôi theo sau cuộn: k = 0.9 tới khoảng 95% sau 3.3 giây).
	damping: { desktop: 2.5, mobile: 3.5, sun: 0.9 },
	// Card coverflow: thời gian chuyển giữa các liệu trình và thời gian ẩn dãy (giây).
	cardDuration: 1.1,
	cardHideDuration: 0.6,
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

	// Mặt trời (chủ thể): đĩa tự vẽ trong shader trời + hào quang; lóa ống kính ở glare
	sun: {
		radiusDeg: 2.2,      // bán kính góc của đĩa (đĩa thật khoảng 0.27°)
		discIntensity: 2,    // cường độ đĩa (vừa phải để Neutral tone mapping không nén thành trắng)
		haloScale: 1,        // nhân vào halo của keyframe
	},

	// Sun glare / sun flash (scene/glare.js, mọi chế độ WebGL): lõi trắng nóng, quầng cam, vệt sao, lens ghost. Mờ theo độ lộ của mặt trời
	// (núi che), lóe khi mặt trời ló qua đỉnh (flash) và lóe nhẹ khi đổi liệu trình (pulse). Giảm chuyển động: đứng yên, không lóe.
	glare: {
		intensity: 1,        // nhân vào keyframe glare
		streaks: 1,          // độ sáng vệt sao và chùm tia
		ghosts: 1,           // độ sáng lens ghost
		rotation: 0.015,     // tốc độ xoay của vệt sao (radian/giây)
		flash: 1,            // độ mạnh lóe khi mặt trời ló qua đỉnh
		pulse: 0.35,         // lóe nhẹ khi đổi liệu trình
	},

	// Hậu kỳ (scene/post.js; chỉ bậc có post: FULL, FULL_HIGH; governor tắt được). Ngưỡng theo độ sáng sau tone mapping (0 đến 1).
	post: {
		godRays: 0.2,        // nhân vào keyframe godRays
		rayThreshold: 0.86,  // vùng sáng sinh god rays (quanh mặt trời; núi tối thành vật che)
		bloom: 0.22,         // độ mạnh bloom (chỉ khi bậc có bloom: FULL_HIGH)
		bloomThreshold: 0.95,// ngưỡng cao, chỉ quanh mặt trời: đĩa mặt trời, viền mây rực
		grain: 0.035,        // grain (chỉ khi bậc có bloom)
	},

	// Card ở hai bên dãy coverflow: back = lật, lộ mặt sau (hoa văn nét dọc vàng), card lật ra mặt trước khi vào giữa; front = mặt trước
	// nghiêng 20° (như bản 4b).
	cardSideFace: 'back',

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

	// Phát hiện GPU (tier.js, @pmndrs/detect-gpu với benchmark tự host): quá thời gian này thì dùng heuristics.
	gpu: { timeoutMs: 2500 },

	// Governor runtime (tier.js): trung bình khoảng cách khung hình trên một cửa sổ (windowFrames khung hoặc windowMs, cần ít nhất minSamples);
	// vượt thresholdMs `strikes` lần liên tiếp thì hạ một bậc (DPR → mật độ cỏ → bloom → god rays → số mây → LITE → DPR 1.0). Xuống 2D chỉ khi
	// đã ở bậc thấp nhất mà vẫn vượt fallbackMs `fallbackStrikes` lần. Bỏ qua warmupMs đầu (biên dịch shader), settleMs sau mỗi lần hạ, khoảng
	// cách > stallMs (tab nền, khựng). Không bao giờ tự nâng bậc; bậc đã hạ được nhớ trong phiên của tab.
	governor: {
		enabled: true,
		windowFrames: 60,
		windowMs: 2500,
		minSamples: 5,
		thresholdMs: 22,
		strikes: 2,
		fallbackMs: 50,
		fallbackStrikes: 3,
		stallMs: 1000,
		warmupMs: 1500,
		settleMs: 800,
		dprSteps: [ 1.75, 1.25, 1 ],
	},

	// Tầng chất lượng (bậc 0 của thang governor; tier.js buildLadder hạ dần từ đây)
	presets: {
		FULL_HIGH: { dpr: 1.75, grid: 256, octaves: 6, grass: 60000, trees: 400, clouds: 12, post: true, bloom: true },
		FULL: { dpr: 1.25, grid: 256, octaves: 6, grass: 30000, trees: 250, clouds: 8, post: true, bloom: false },
		LITE: { dpr: 1.25, grid: 128, octaves: 4, grass: 0, trees: 80, clouds: 4, post: false, bloom: false },
	},
};
