/**
 * Chọn chế độ hiển thị và governor hiệu năng runtime (đặc tả mục "CHẾ ĐỘ & PHÁT HIỆN THIẾT BỊ").
 *
 * detectMode, thứ tự quyết định:
 *   0) quản trị viên ép ?isha-mode=FULL_HIGH|FULL|LITE|2D|STATIC (chỉ khi bảng debug bật);
 *   1) prefers-reduced-motion → STATIC (không tải thư viện 3D);
 *   2) không tạo được WebGL2 → 2D; WebGL2 chỉ chạy bằng phần mềm (tạo context với failIfMajorPerformanceCaveat thất bại: SwiftShader,
 *      llvmpipe, WARP) → 2D;
 *   3) phân loại GPU bằng @pmndrs/detect-gpu với benchmark tự host (assets/vendor/detect-gpu@6.0.28/dist/benchmarks), chờ tối đa
 *      CONFIG.gpu.timeoutMs;
 *   4) modeFromGpu: điện thoại / máy tính bảng / màn hình hẹp → LUÔN LITE, trừ tier 0 thật (BENCHMARK tier 0, BLOCKLISTED) → 2D. Desktop:
 *      tier 3 → FULL_HIGH, 2 → FULL, 1 → LITE, 0 thật → 2D. Không có số liệu (BENCHMARK_FETCH_FAILED; FALLBACK = GPU chưa có trong dữ liệu
 *      benchmark, gồm GPU ra đời từ 2026 vì nguồn gfxbench ngừng cập nhật; hết giờ; lỗi import) → KHÔNG coi là máy yếu, dùng heuristics
 *      (hardwareConcurrency >= 8 và deviceMemory >= 8 → FULL, còn lại → LITE) rồi để governor quyết định tiếp.
 *
 * Governor (buildLadder + createGovernor: thuần, có test trong tests/tier.test.mjs): thang chất lượng hạ dần theo thứ tự của v3: DPR → bloom →
 * mật độ lưới → dải sương → hạt bụi → quad tia, rồi DPR 1.0, cuối cùng 2D. Đo khoảng cách giữa các khung hình
 * đã render; trung bình một cửa sổ (60 khung hoặc 2.5 giây) vượt 22ms hai lần liên tiếp thì hạ một bậc. Xuống 2D chỉ khi đã ở bậc thấp nhất mà vẫn trên 50ms (dưới 20 khung
 * hình/giây) ba lần liên tiếp. Không bao giờ tự nâng bậc; bậc đã hạ được nhớ trong sessionStorage nên các trang nhóm khác trong cùng phiên
 * của tab bắt đầu luôn ở bậc đó.
 *
 * Module này không chạm DOM khi import (test Node import trực tiếp).
 */
export const MODES = Object.freeze( [ 'FULL_HIGH', 'FULL', 'LITE', '2D', 'STATIC' ] );
export const DETECT_GPU_VERSION = '6.0.28';
const BENCHMARKS_URL = new URL( `../../vendor/detect-gpu@${ DETECT_GPU_VERSION }/dist/benchmarks`, import.meta.url ).href.replace( /\/$/, '' );
const SESSION_KEY = 'isha-svc-gov';

export function isMobileLike() {
	const coarse = window.matchMedia && window.matchMedia( '(pointer: coarse)' ).matches;
	const ua = /Mobi|Android|iPhone|iPad|iPod/i.test( navigator.userAgent || '' );
	return coarse || ua || window.innerWidth < 900;
}

// Một context WebGL2 dùng cho cả kiểm tra hỗ trợ lẫn detect-gpu (không tạo thêm context). software = chỉ tạo được khi chấp nhận "major
// performance caveat", tức WebGL đang chạy bằng CPU.
function probeWebGL2() {
	const make = ( caveat ) => {
		try {
			return document.createElement( 'canvas' ).getContext( 'webgl2', {
				failIfMajorPerformanceCaveat: caveat,
				alpha: false,
				antialias: false,
				depth: false,
				stencil: false,
				powerPreference: 'high-performance',
			} );
		} catch ( err ) {
			return null;
		}
	};
	const gl = make( true );
	if ( gl ) {
		return { gl, software: false };
	}
	const soft = make( false );
	return { gl: soft, software: !! soft };
}

function release( gl ) {
	const lose = gl && gl.getExtension( 'WEBGL_lose_context' );
	if ( lose ) {
		lose.loseContext();
	}
}

/**
 * Chế độ từ kết quả detect-gpu (thuần).
 *
 * @param {{mobile:boolean, gpu:?{tier:number,type:string}, cores:number, mem:number}} p gpu null = không có kết quả.
 * @return {{mode:string, reason:string}}
 */
export function modeFromGpu( { mobile, gpu, cores, mem } ) {
	const realZero = !! gpu && ( gpu.type === 'BLOCKLISTED' || gpu.type === 'WEBGL_UNSUPPORTED' || ( gpu.type === 'BENCHMARK' && gpu.tier <= 0 ) );
	if ( realZero ) {
		return { mode: '2D', reason: `GPU tier 0 (${ gpu.type })` };
	}
	if ( mobile ) {
		return { mode: 'LITE', reason: 'thiết bị di động / màn hình hẹp: luôn LITE' };
	}
	if ( gpu && gpu.type === 'BENCHMARK' ) {
		const mode = gpu.tier >= 3 ? 'FULL_HIGH' : gpu.tier === 2 ? 'FULL' : 'LITE';
		return { mode, reason: `GPU tier ${ gpu.tier }` };
	}
	const why = gpu ? gpu.type : 'không có kết quả detect-gpu';
	return { mode: cores >= 8 && mem >= 8 ? 'FULL' : 'LITE', reason: `${ why } → heuristics: ${ cores } luồng, ${ mem } GB` };
}

function withTimeout( promise, ms ) {
	promise.catch( () => {} );
	return Promise.race( [ promise, new Promise( ( resolve ) => setTimeout( () => resolve( { tier: 1, type: 'TIMEOUT' } ), ms ) ) ] );
}

/**
 * @return {Promise<{mode:string, mobile:boolean, forced:boolean, reasons:string[], gpu:?object}>}
 */
export async function detectMode( { reduced, debug, timeoutMs = 2500, loadDetectGpu = () => import( '@pmndrs/detect-gpu' ) } ) {
	const mobile = isMobileLike();
	const result = ( mode, reasons, gpu = null, forced = false ) => ( { mode, mobile, forced, reasons, gpu } );
	if ( debug ) {
		const q = new URLSearchParams( window.location.search ).get( 'isha-mode' );
		if ( q && MODES.includes( q ) ) {
			return result( q, [ 'ép bằng ?isha-mode' ], null, true );
		}
	}
	if ( reduced ) {
		return result( 'STATIC', [ 'prefers-reduced-motion' ] );
	}
	const { gl, software } = probeWebGL2();
	if ( ! gl ) {
		return result( '2D', [ 'không có WebGL2' ] );
	}
	if ( software ) {
		release( gl );
		return result( '2D', [ 'WebGL2 chạy bằng phần mềm (major performance caveat)' ] );
	}

	const reasons = [];
	let gpu = null;
	try {
		const { getGPUTier } = await loadDetectGpu();
		// detect-gpu dùng context đồng bộ trước lần await đầu tiên (đọc tên GPU), sau đó chỉ tải benchmark: giải phóng context ngay sau đó
		// là an toàn kể cả khi hết giờ.
		gpu = await withTimeout( getGPUTier( { glContext: gl, benchmarksURL: BENCHMARKS_URL } ), timeoutMs );
	} catch ( err ) {
		reasons.push( 'detect-gpu lỗi: ' + ( err && err.message ? err.message : err ) );
	} finally {
		release( gl );
	}
	const cores = navigator.hardwareConcurrency || 4;
	const mem = navigator.deviceMemory || 8; // Safari/Firefox không có deviceMemory: coi như đủ, governor sẽ hạ nếu chậm.
	const pick = modeFromGpu( { mobile, gpu, cores, mem } );
	reasons.unshift( pick.reason );
	if ( gpu && gpu.gpu ) {
		reasons.push( `GPU: ${ gpu.gpu }${ gpu.fps ? ` (${ gpu.fps } fps benchmark)` : '' }` );
	}
	return result( pick.mode, reasons, gpu );
}

// Các số lượng governor hạ dần, theo thứ tự (sau DPR và bloom).
export const LADDER_DIMS = Object.freeze( [ 'grid', 'mist', 'dust', 'rays' ] );

/**
 * Thang chất lượng của governor (thuần). Bậc 0 là preset của chế độ đầu; mỗi bậc sau hạ đúng một thứ theo thứ tự của docs/isha-lookdev-prompt-v3.md
 * mục 16 (bỏ cỏ lau theo góp ý khách): DPR → (tắt bloom) → mật độ lưới → số dải sương → số hạt bụi → số quad tia, rồi DPR 1.0, cuối cùng 2D. Mỗi bậc giảm số
 * lượng còn một nửa (làm tròn lên) nhưng không dưới một nửa cấu hình LITE. Bậc không làm gì khác trên thiết bị này thì bỏ (hạ DPR khi màn hình
 * vốn DPR thấp; tắt bloom khi chưa bật). Grain và vignette không nằm trong thang: không bao giờ bị tắt.
 *
 * @param {string} mode Chế độ đầu (FULL_HIGH | FULL | LITE).
 * @param {object} presets CONFIG.presets.
 * @param {{deviceDpr?:number, dprSteps?:number[]}} opts
 * @return {ReadonlyArray<object>} Mỗi bậc: { mode, dpr, grid, mist, dust, rays, bloom } (bậc 2D chỉ có mode).
 */
export function buildLadder( mode, presets, { deviceDpr = 1, dprSteps = [ 1.75, 1.5, 1.25, 1 ] } = {} ) {
	const p = presets[ mode ];
	const fallback = Object.freeze( { mode: '2D' } );
	if ( ! p || ( mode !== 'FULL_HIGH' && mode !== 'FULL' && mode !== 'LITE' ) ) {
		return Object.freeze( [ fallback ] );
	}
	const lite = presets.LITE;
	const lower = ( d ) => {
		const next = dprSteps.find( ( s ) => s < d - 1e-6 );
		return next === undefined ? d : next;
	};
	const halve = ( dim ) => ( q ) => ( { ...q, [ dim ]: Math.max( Math.min( q[ dim ], Math.ceil( lite[ dim ] / 2 ) ), Math.ceil( q[ dim ] / 2 ) ) } );
	const steps = [
		( q ) => ( { ...q, dpr: lower( q.dpr ) } ),
		( q ) => ( { ...q, bloom: false } ),
		...LADDER_DIMS.map( halve ),
		( q ) => ( { ...q, dpr: Math.min( q.dpr, dprSteps[ dprSteps.length - 1 ] ) } ),
	];
	const key = ( q ) => [ q.mode, Math.min( deviceDpr, q.dpr ), ...LADDER_DIMS.map( ( d ) => q[ d ] ), q.bloom ].join( '|' );
	let cur = { mode, dpr: p.dpr, bloom: !! p.bloom };
	LADDER_DIMS.forEach( ( d ) => {
		cur[ d ] = p[ d ];
	} );
	const ladder = [ Object.freeze( cur ) ];
	for ( const step of steps ) {
		const next = step( cur );
		if ( key( next ) !== key( cur ) ) {
			ladder.push( Object.freeze( next ) );
		}
		cur = next;
	}
	ladder.push( fallback );
	return Object.freeze( ladder );
}

/**
 * Bộ đo của governor (thuần). sample() nhận khoảng cách (ms) giữa hai khung hình đã render và thời điểm hiện tại; trả true khi cần hạ một
 * bậc. toFallback = bậc kế tiếp là 2D (ngưỡng và số lần riêng).
 *
 * @param {{enabled:boolean, windowFrames:number, windowMs:number, minSamples:number, thresholdMs:number, strikes:number,
 *   fallbackMs:number, fallbackStrikes:number, stallMs:number, warmupMs:number, settleMs:number}} cfg Đọc trực tiếp mỗi lần (debug chỉnh được).
 */
export function createGovernor( cfg ) {
	let sum = 0;
	let n = 0;
	let start = -1;
	let strikes = 0;
	let readyAt = 0;
	let last = 0;
	const clear = () => {
		sum = 0;
		n = 0;
		start = -1;
	};
	return {
		get lastAvg() {
			return last;
		},
		get strikes() {
			return strikes;
		},
		// Bỏ qua một khoảng (biên dịch shader lúc đầu, vòng render vừa chạy lại, vừa hạ bậc).
		reset( now, settleMs = cfg.warmupMs ) {
			clear();
			strikes = 0;
			readyAt = now + settleMs;
		},
		sample( ms, now, toFallback = false ) {
			if ( ! cfg.enabled || now < readyAt ) {
				return false;
			}
			if ( ms > cfg.stallMs ) {
				clear(); // khựng hẳn (tab nền, GC lớn, breakpoint): bỏ cửa sổ này
				return false;
			}
			if ( start < 0 ) {
				start = now;
			}
			sum += ms;
			n++;
			if ( ( n < cfg.windowFrames && now - start < cfg.windowMs ) || n < cfg.minSamples ) {
				return false;
			}
			last = sum / n;
			clear();
			const limit = toFallback ? cfg.fallbackMs : cfg.thresholdMs;
			const need = toFallback ? cfg.fallbackStrikes : cfg.strikes;
			strikes = last > limit ? strikes + 1 : 0;
			if ( strikes >= need ) {
				strikes = 0;
				readyAt = now + cfg.settleMs;
				return true;
			}
			return false;
		},
	};
}

// Bậc governor đã hạ trong phiên của tab (sessionStorage; lỗi truy cập thì coi như chưa hạ).
export function loadGovStep( mode ) {
	try {
		const s = JSON.parse( window.sessionStorage.getItem( SESSION_KEY ) || 'null' );
		return s && s.v === 1 && s.start === mode && Number.isInteger( s.step ) && s.step > 0 ? s.step : 0;
	} catch ( err ) {
		return 0;
	}
}

export function saveGovStep( mode, step ) {
	try {
		window.sessionStorage.setItem( SESSION_KEY, JSON.stringify( { v: 1, start: mode, step } ) );
	} catch ( err ) {
		// Không lưu được (chế độ riêng tư, chặn lưu trữ): trang sau lại bắt đầu từ bậc 0.
	}
}

export function clearGovStep() {
	try {
		window.sessionStorage.removeItem( SESSION_KEY );
	} catch ( err ) {
		// bỏ qua
	}
}
