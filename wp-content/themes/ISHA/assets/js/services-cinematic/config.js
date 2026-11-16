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
	// k của smoothed += ( p - smoothed ) * ( 1 - exp( -k * dt ) ). desktop/mobile: camera dolly của cảnh v3 (đặc tả v3 mục 9: 5 / 8; dùng từ
	// bước 3); sun: mặt trời, trời, sương (chậm hơn để mặt trời trôi theo sau cuộn: k = 0.9 tới khoảng 95% sau 3.3 giây).
	damping: { desktop: 5, mobile: 8, sun: 0.9 },
	// Card coverflow: thời gian chuyển giữa các liệu trình và thời gian ẩn dãy (giây).
	cardDuration: 1.1,
	cardHideDuration: 0.6,
	announceDelayMs: 400,         // aria-live chỉ đọc khi index ổn định quá thời gian này

	// Keyframe mặt trời (computeStops): vị trí trên màn hình đầu/cuối, sương gốc
	stops: { ...DEFAULT_STOP_CFG },

	debugProgress: -1,            // >= 0: ép progress (thanh trượt debug), -1: theo cuộn

	// Cảnh v3 (docs/isha-lookdev-prompt-v3.md). Ảnh nguồn: một tấm ảnh núi thật; số đo bằng tools/check-source.mjs (ghi ở tools/refs/NOTES.md).
	plate: {
		horizonUV: 0.2,  // trung vị đường chân trời (silhouette rặng xa) trong texture, 0 = mép trên (dao động 0.107 – 0.261)
		anchorX: 0.65,   // khối núi neo: khối lớn bên phải (sườn trái từ x ≈ 0.62, đỉnh x ≈ 0.85 – 0.90)
	},
	// Độ sâu suy từ độ sáng (scene/depth.js, đặc tả 3.1): d = pow( smoothstep( lumNear, lumFar, lum ), gamma ); 0 = gần, 1 = xa. lum: độ sáng
	// Rec. 709 trên giá trị sRGB của ảnh thu nhỏ 1/4, làm mượt Gaussian tách trục bán kính `blur` (tỉ lệ chiều rộng ảnh). Bản đồ vẽ sẵn (nếu
	// dùng) làm mượt nhẹ hơn: `mapBlur`. Ba núm lumNear, lumFar, gamma quyết định cả cảnh: chỉnh trong lil-gui.
	depth: { lumNear: 0.11, lumFar: 0.81, gamma: 1, blur: 0.015, mapBlur: 0.005 },
	toneMapping: 'Neutral',       // Neutral | AgX (không dùng ACESFilmic: lệch hue của vàng)

	// Card ở hai bên dãy coverflow: back = lật, lộ mặt sau (hoa văn nét dọc vàng), card lật ra mặt trước khi vào giữa; front = mặt trước
	// nghiêng 20° (như bản 4b).
	cardSideFace: 'back',

	// Phát hiện GPU (tier.js, @pmndrs/detect-gpu với benchmark tự host): quá thời gian này thì dùng heuristics.
	gpu: { timeoutMs: 2500 },

	// Governor runtime (tier.js): trung bình khoảng cách khung hình trên một cửa sổ (windowFrames khung hoặc windowMs, cần ít nhất minSamples);
	// vượt thresholdMs `strikes` lần liên tiếp thì hạ một bậc (DPR → bloom → mật độ lưới → dải sương → hạt bụi → quad tia → cỏ lau → DPR 1.0).
	// Xuống 2D chỉ khi đã ở bậc thấp nhất mà vẫn vượt fallbackMs `fallbackStrikes` lần. Bỏ qua warmupMs đầu (biên dịch shader), settleMs sau mỗi
	// lần hạ, khoảng cách > stallMs (tab nền, khựng). Không bao giờ tự nâng bậc; bậc đã hạ được nhớ trong phiên của tab. Grain và vignette
	// không nằm trong thang: không bao giờ bị tắt.
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
		dprSteps: [ 1.75, 1.5, 1.25, 1 ],
	},

	// Tầng chất lượng của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 16; bậc 0 của thang governor, tier.js buildLadder hạ dần từ đây). Mọi
	// thiết bị chạy cùng một cảnh, chỉ khác tham số. grid: số segment ngang của lưới địa hình (dọc = grid · 9 / 16); mist: số dải sương; dust:
	// số hạt bụi; rays: số quad tia thể tích; reeds: số lá cỏ lau; bloom: chỉ desktop. LITE là cấu hình điện thoại.
	presets: {
		FULL_HIGH: { dpr: 1.75, grid: 320, mist: 4, dust: 300, rays: 5, reeds: 180, bloom: true },
		FULL: { dpr: 1.5, grid: 320, mist: 4, dust: 300, rays: 5, reeds: 180, bloom: false },
		LITE: { dpr: 1.5, grid: 160, mist: 2, dust: 120, rays: 3, reeds: 70, bloom: false },
	},
};
