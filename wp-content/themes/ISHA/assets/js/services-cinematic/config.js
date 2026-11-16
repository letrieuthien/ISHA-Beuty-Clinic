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
	// bước 3); sun: mặt trời, trời, sương trôi theo sau cuộn. 1.5: tới khoảng 95% sau 2 giây (bản trước 0.9: 3.3 giây, mặt trời còn trôi lâu
	// sau khi ngừng cuộn, lệch nhịp với bánh xe chuột).
	damping: { desktop: 5, mobile: 8, sun: 1.5 },
	// Dãy card ở chân núi (ui/card-view.js): hiện thì các card dâng lên `lift` px trong `enter` giây (power3.out), lệch pha `stagger` giây;
	// ẩn trong `hide` giây. Thời gian lật card nằm ở CSS (--isha-card-flip, mặc định 0.8s; bảng debug chỉnh được).
	card: { enter: 0.7, hide: 0.4, stagger: 0.08, lift: 24 },
	announceDelayMs: 400,         // aria-live chỉ đọc khi index ổn định quá thời gian này

	// Điểm dừng của khung cảnh (stops.js computeStops, đặc tả mục 6): đường đi của mặt trời (path.desktop / path.portrait) và [đầu, cuối] của
	// từng trường. Bản sao sâu (DEFAULT_STOP_CFG đóng băng) để bảng debug chỉnh được; cảnh tính lại điểm dừng khi debug đổi.
	stops: JSON.parse( JSON.stringify( DEFAULT_STOP_CFG ) ),

	debugProgress: -1,            // >= 0: ép progress (thanh trượt debug), -1: theo cuộn

	// Cảnh v3 (docs/isha-lookdev-prompt-v3.md). Ảnh nguồn: một tấm ảnh núi thật; số đo ở bước 1 (README mục 6 – 8, tools/refs/NOTES.md).
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
		haze: 0.45,          // không khí: lớp càng xa càng phủ màu sương (× d^1.4 × keyframe fogDensity); "núi chìm trong sương"
		stretchLo: 1.6,      // chống kéo giãn (3.5): tam giác giãn quá stretchLo lần so với lúc ở vị trí tham chiếu thì mờ dần,
		stretchHi: 3,        //   tới stretchHi thì trong suốt hẳn (zoom do dolly chỉ khoảng 1.16)
	},
	// Cắt trời của ảnh theo độ sáng (3.4): trời là thứ sáng nhất của ảnh sương nên mép cắt tự bám đường viền rặng. Khe histogram trời / núi
	// quanh 0.63 (trời 0.70 – 0.86, núi ≤ 0.67). bandY / band: thêm một mép mềm theo hàng ảnh (tính từ trên) để bỏ hẳn phần trời trên cùng.
	cut: { lumLow: 0.62, lumHigh: 0.69, bandY: 0.07 }, // band (uBand): theo độ cao mặt trời, xem rise.band
	// Viền sáng rặng núi (7): pixel trên sườn dốc của bản đồ độ sâu và hướng về mặt trời. lo / hi: độ dốc độ sâu (độ sâu trên một chiều
	// rộng ảnh; đường viền rặng khoảng 10 – 15, trong sườn khoảng 1) bắt đầu / đủ viền; falloff: tắt dần theo khoảng cách tới mặt trời
	// (tính bằng chiều cao khung).
	rim: { lo: 3, hi: 10, falloff: 0.25, strength: 0.28 },
	// Đóng khung (5.2): ảnh fit theo chiều rộng, neo horizonUV vào vị trí này (tỉ lệ chiều cao khung từ trên); khung ngang → dọc nội suy.
	framing: { horizonLandscape: 0.47, horizonPortrait: 0.57 },
	// Camera (scene/camera.js, đặc tả 9): FOV dọc; khung dọc mở thêm. Dolly về phía trước, lệch nhẹ về khối neo. maxMagnification: ràng buộc
	// cứng, phần gần nhất của ảnh không được phóng quá 18% (assert trong console).
	camera: { fov: 38, fovPortrait: 10, dollyMax: 95, anchorDrift: 6, mouse: 0.35, mouseDamping: 3, maxMagnification: 1.18 },
	// Trời (scene/sky.js, đặc tả 7, 8): gradient 4 chặng, dither 1/255, tối đa hai đám mây đơn lẻ từ PNG nền trong suốt. Mỗi đám: x (tỉ lệ
	// chiều rộng khung), y (tâm, chiều cao khung từ trên, ở khung ngang chân trời 47%), width (tỉ lệ chiều rộng khung, khung hẹp thu theo),
	// speed (trôi ngang, chiều cao khung / giây; ra khỏi mép thì vòng lại). cloudGain nhân keyframe cloudOpacity.
	sky: {
		sunGlow: 0.45,
		sunGlowRadius: 0.35,
		cloudGain: 1,
		horizonGolden: 0.75,
		clouds: [
			{ x: 0.2, y: 0.15, width: 0.34, speed: 0.004 },
			{ x: 0.82, y: 0.09, width: 0.4, speed: 0.0028 },
		],
	},
	// Mặt trời (scene/sun.js, đặc tả 10). Kích thước theo chiều cao khung. z: khoảng cách (đơn vị cảnh) của mặt phẳng đặt mặt trời. Đặc tả gợi
	// ý 1050 (giữa dải độ sâu 700 – 1300); đặt 1290 để mặt trời ở sau mọi lớp núi (đúng vật lý khi điểm đầu nằm ngay dưới đường chân trời
	// xa). Đĩa, quầng, vệt ngang, tia cộng sáng trong không gian hiển thị; haloVeil: phần quầng không bị che (tán xạ trong không khí trước núi).
	sun: {
		z: 1290,
		haloVeil: 0.3,
		radius: 0.022,       // bán kính đĩa
		haloScale: 6,        // quầng rộng gấp 6 lần đĩa
		disc: 0.8,           // độ sáng đĩa (cộng vào trời)
		halo: 0.42,          // độ sáng quầng
		streakLength: 0.18,  // vệt ngang anamorphic: dài theo chiều rộng khung
		streakAlpha: 0.12,
		rays: 0.2,           // độ sáng tia thể tích (nhân keyframe volumetric)
		rayLength: 0.8,      // chiều dài tia (chiều cao khung)
		// Nhịp lắc, khoảng cách mặt phẳng tia, dài, độ toả: theo độ cao mặt trời (rise.rayTurn, raysZ, rayLength, raySpread, rayWidth).
	},
	// Sương (scene/mist.js, đặc tả 11). Mỗi dải: [ khoảng cách z, tâm theo hàng ảnh (0 = mép trên ảnh), chiều cao (tỉ lệ chiều cao ảnh), alpha,
	// tốc độ trôi (đơn vị / giây) ]. Neo theo ảnh (thung lũng nằm ở đâu trong ảnh) và đặt ở độ sâu z: rặng gần hơn z tự che dải sương, nên sương
	// tự xen giữa các lớp núi. Tầng chất lượng dùng `count` dải đầu theo thứ tự ưu tiên `priority`.
	mist: {
		bands: [
			[ 1200, 0.25, 0.14, 0.34, 0.004 ],
			[ 1000, 0.43, 0.2, 0.48, 0.007 ],
			[ 850, 0.6, 0.22, 0.48, 0.009 ],
			[ 720, 0.86, 0.26, 0.4, 0.012 ],
		],
		priority: [ 1, 2, 3, 0 ],
		sunLit: 0.7,         // sương gần mặt trời sáng và ấm lên (tán xạ thuận)
		scale: 1,            // cỡ vân fbm
		soft: 90,            // che khuất mềm: khoảng (đơn vị cảnh) sương mờ dần trước khi bị rặng gần che
	},
	// Chuyển động độc lập với cuộn (camera.js idleValues, đặc tả 12): thở của khung hình ±breath (tỉ lệ khoảng cách gần nhất: phóng ±0.15%,
	// chu kỳ 12 giây), tia × ( 1 + rays · sin( t / 8 ) ), quầng ±halo chu kỳ 10 giây.
	idle: { breath: 0.0015, rays: 0.08, halo: 0.03 },
	// Bụi sáng (scene/dust.js, đặc tả 12): sâu near … far trước camera; cỡ điểm = clamp( sizeK / khoảng cách, 1, 4 ) px; rise: tốc độ trôi lên
	// (phần chiều cao khung / giây); reach: độ lan của vùng sáng quanh mặt trời (chiều cao khung); gain: nhân keyframe dustIntensity.
	dust: { near: 40, far: 200, sizeK: 420, rise: 0.004, reach: 0.24, gain: 1.4 },
	// Hậu kỳ (scene/post.js, đặc tả 14). Grain theo keyframe (2.2 – 3.0%) × grainScale; vignette theo độ cao mặt trời (rise.vignette).
	// Mọi tầng, governor không tắt.
	// Bloom (chỉ tầng có bloom: desktop): bloom = độ mạnh, bloomThreshold = ngưỡng độ sáng hiển thị (cao: chỉ đĩa mặt trời và chỗ sáng nhất),
	// bloomRadius = hệ số bán kính làm mờ.
	post: { grainScale: 1, bloom: 0.28, bloomThreshold: 0.96, bloomRadius: 1.1 },
	// Theo độ cao mặt trời (stops.js riseValues, góp ý khách sau bước 4): [ giá trị khi mặt trời ở điểm đầu, giá trị ở điểm cuối ], nội suy
	// tuyến tính theo h = phần đường đã đi. vignette: tối góc (đặc tả 0.25 cố định); band: uBand, mép mềm của cắt trời theo hàng ảnh
	// (> 0.035 bắt đầu làm mờ nhẹ đỉnh cao nhất của ảnh ở 0.107); fov: độ cộng vào FOV dọc (zoom nhẹ, không gây xé hình vì phóng đều);
	// halo: nhân độ sáng quầng; sunScale: nhân bán kính đĩa (đặc tả 1.00 → 0.78; khách muốn to nhẹ); rayLength / rayWidth: nhân dài / rộng
	// tia (dài bắt đầu từ 0: chưa có tia khi mặt trời ở điểm đầu; tia còn tắt theo độ lộ của đĩa, sun.js); raySpread: nhân góc giữa các tia quanh hướng xuống; raysZ: khoảng cách mặt phẳng tia (xa hơn = lùi sau nhiều lớp núi hơn).
	rise: {
		vignette: [ 0.32, 0.14 ],
		band: [ 0.025, 0.05 ],
		fov: [ 0, -1.5 ],
		halo: [ 0.8, 1.25 ],
		sunScale: [ 1, 1.12 ],
		rayLength: [ 0, 1.05 ],
		raySpread: [ 0.55, 1.2 ],
		rayWidth: [ 0.7, 1.3 ],
		raysZ: [ 1000, 1180 ],
		// Nhịp lắc của tia (tốc độ pha, radian / giây; biên độ ±6°, mỗi tia một hệ số riêng): 0.18 lúc mặt trời ló (chu kỳ khoảng 35 giây),
		// 0.5 ở đỉnh (khoảng 13 giây). Pha tích luỹ theo từng khung trong sun.js nên đổi nhịp khi cuộn không làm tia giật.
		rayTurn: [ 0.18, 0.5 ],
	},
	// Màu phái sinh từ token (tokens.js, đặc tả 7).
	colors: { fogSkinMix: 0.35, greenHue: 112, greenSat: 0.28, ridgeGreenMix: 0.4, rimMetalMix: 0.4, sunWhiteMix: 0.75 },
	// Độ sâu suy từ độ sáng (scene/depth.js, đặc tả 3.1): d = pow( smoothstep( lumNear, lumFar, lum ), gamma ); 0 = gần, 1 = xa. lum: độ sáng
	// Rec. 709 trên giá trị sRGB của ảnh thu nhỏ 1/4, làm mượt Gaussian tách trục bán kính `blur` (tỉ lệ chiều rộng ảnh). Bản đồ vẽ sẵn (nếu
	// dùng) làm mượt nhẹ hơn: `mapBlur`. Ba núm lumNear, lumFar, gamma quyết định cả cảnh: chỉnh trong lil-gui.
	depth: { lumNear: 0.11, lumFar: 0.81, gamma: 1, blur: 0.015, mapBlur: 0.005 },
	toneMapping: 'Neutral',       // Neutral | AgX (không dùng ACESFilmic: lệch hue của vàng)

	// Phát hiện GPU (tier.js, @pmndrs/detect-gpu với benchmark tự host): quá thời gian này thì dùng heuristics.
	gpu: { timeoutMs: 2500 },

	// Governor runtime (tier.js): trung bình khoảng cách khung hình trên một cửa sổ (windowFrames khung hoặc windowMs, cần ít nhất minSamples);
	// vượt thresholdMs `strikes` lần liên tiếp thì hạ một bậc (DPR → bloom → mật độ lưới → dải sương → hạt bụi → quad tia → DPR 1.0).
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
	// số hạt bụi; rays: số quad tia thể tích; bloom: chỉ desktop. LITE là cấu hình điện thoại. (Cỏ lau của đặc tả đã bỏ theo góp ý khách.)
	presets: {
		FULL_HIGH: { dpr: 1.75, grid: 320, mist: 4, dust: 300, rays: 5, bloom: true },
		FULL: { dpr: 1.5, grid: 320, mist: 4, dust: 300, rays: 5, bloom: false },
		LITE: { dpr: 1.5, grid: 160, mist: 2, dust: 120, rays: 3, bloom: false },
	},
};
