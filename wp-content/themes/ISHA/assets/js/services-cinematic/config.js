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
	// Lưới ảnh nền (scene/plate.js, đặc tả 3.3 – 3.5, 5.2, 7). Đỉnh đặt dọc tia từ camera tham chiếu (vị trí p = 0) ở khoảng cách
	// mix( near, far, độ sâu ): nhìn từ đó hình trùng khít ảnh gốc, camera rời chỗ mới sinh parallax.
	plate: {
		horizonUV: 0.2,      // trung vị đường chân trời (silhouette rặng xa) trong texture, 0 = mép trên (dao động 0.107 – 0.261)
		anchorX: 0.65,       // khối núi neo: khối lớn bên phải (sườn trái từ x ≈ 0.62, đỉnh x ≈ 0.85 – 0.90)
		near: 700,           // uNearDist: khoảng cách của độ sâu 0 (đơn vị cảnh)
		far: 1300,           // uFarDist: khoảng cách của độ sâu 1
		extendU: 0.02,       // lưới tràn ra hai bên (texture kẹp mép) để camera lệch ngang không lộ dải trống
		skirt: 1,            // lưới tràn xuống dưới đáy ảnh (tính bằng chiều cao ảnh): khung dọc, ảnh fit ngang không phủ tới đáy
		mixNear: 0.7,        // depthMix ở độ sâu 0: base = mix( fog, ridgeBase, depthMix ); gần đậm
		mixFar: 0.12,        // depthMix ở độ sâu 1: xa tan vào sương
		gain: 1.12,          // nhân sau khi tô màu (base × gray), giữ độ sáng gần ảnh gốc
		aerial: 0.35,        // phía xa ngả màu trời sát chân trời (Golden) để rặng xa hoà với trời; toàn quang giảm còn 60%
		stretchLo: 1.6,      // chống kéo giãn (3.5): tam giác giãn quá stretchLo lần so với lúc ở vị trí tham chiếu thì mờ dần,
		stretchHi: 3,        //   tới stretchHi thì trong suốt hẳn (zoom do dolly chỉ khoảng 1.16)
	},
	// Cắt trời của ảnh theo độ sáng (3.4): trời là thứ sáng nhất của ảnh sương nên mép cắt tự bám đường viền rặng. Khe histogram trời / núi
	// quanh 0.63 (trời 0.70 – 0.86, núi ≤ 0.67). bandY / band: thêm một mép mềm theo hàng ảnh (tính từ trên) để bỏ hẳn phần trời trên cùng.
	cut: { lumLow: 0.62, lumHigh: 0.69, bandY: 0.07, band: 0.025 },
	// Viền sáng rặng núi (7): pixel trên sườn dốc của bản đồ độ sâu và hướng về mặt trời. lo / hi: độ dốc độ sâu (độ sâu trên một chiều
	// rộng ảnh; đường viền rặng khoảng 10 – 15, trong sườn khoảng 1) bắt đầu / đủ viền; falloff: tắt dần theo khoảng cách tới mặt trời
	// (tính bằng chiều cao khung).
	rim: { lo: 3, hi: 10, falloff: 0.3, strength: 0.4 },
	// Đóng khung (5.2): ảnh fit theo chiều rộng, neo horizonUV vào vị trí này (tỉ lệ chiều cao khung từ trên); khung ngang → dọc nội suy.
	framing: { horizonLandscape: 0.47, horizonPortrait: 0.57 },
	// Camera (scene/camera.js, đặc tả 9): FOV dọc; khung dọc mở thêm. Dolly về phía trước, lệch nhẹ về khối neo. maxMagnification: ràng buộc
	// cứng, phần gần nhất của ảnh không được phóng quá 18% (assert trong console).
	camera: { fov: 38, fovPortrait: 10, dollyMax: 95, anchorDrift: 6, mouse: 0.35, mouseDamping: 3, maxMagnification: 1.18 },
	// Trời (scene/sky.js, đặc tả 7, 8): gradient 4 chặng, dither 1/255, mây phủ (texture nếu có, không thì fbm).
	sky: { sunGlow: 0.45, sunGlowRadius: 0.35, cloudDrift: 0.003, cloudGain: 1, horizonGolden: 0.75 },
	// Màu phái sinh từ token (tokens.js, đặc tả 7).
	colors: { fogSkinMix: 0.35, greenHue: 112, greenSat: 0.28, ridgeGreenMix: 0.4, rimMetalMix: 0.4, sunWhiteMix: 0.75 },
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
