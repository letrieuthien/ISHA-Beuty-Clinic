/**
 * Governor của vòng thẻ (prompt mục 9): đo FPS trung bình theo cửa sổ GOVERNOR_WINDOW_MS (2 giây); dưới GOVERNOR_FPS (45) thì hạ một bậc:
 *   0 FULL         đủ hiệu ứng;
 *   1 NO_REFLECT   tắt phản chiếu sàn;
 *   2 CENTER_GLINT ánh kim chạy chỉ ở thẻ giữa ± 1 (thẻ khác giữ viền vàng tĩnh).
 * Không bao giờ tắt viền vàng tĩnh. Chỉ hạ, không nâng lại (index.js lưu bậc trong phiên của tab). Tia nắng CSS (bậc đầu của prompt) không
 * làm (README-card-ring mục 27), nên thang bắt đầu từ phản chiếu.
 * HÀM THUẦN, không DOM (test: tests/card-ring-governor.test.mjs). Bỏ qua: khởi động / vừa chạy lại / vừa hạ bậc (warmup), khung khựng hẳn
 * (> stall ms: tab nền, GC lớn, breakpoint) và cửa sổ quá ít khung.
 */

export const GOV_STEPS = Object.freeze( [ 'FULL', 'NO_REFLECT', 'CENTER_GLINT' ] );
export const GOV_MAX = GOV_STEPS.length - 1;

/**
 * @param {{GOVERNOR_FPS:number, GOVERNOR_WINDOW_MS?:number, GOVERNOR_WARMUP_MS?:number, GOVERNOR_STALL_MS?:number, GOVERNOR_MIN_FRAMES?:number}} cfg
 */
export function createRingGovernor( cfg ) {
	const windowMs = cfg.GOVERNOR_WINDOW_MS ?? 2000;
	const warmupMs = cfg.GOVERNOR_WARMUP_MS ?? 1500;
	const stallMs = cfg.GOVERNOR_STALL_MS ?? 250;
	const minFrames = cfg.GOVERNOR_MIN_FRAMES ?? 20;
	let start = -1;
	let frames = 0;
	let readyAt = 0;
	let lastFps = 0;
	return {
		/** FPS trung bình của cửa sổ đo gần nhất (0: chưa có). */
		get lastFps() {
			return lastFps;
		},
		/** Bỏ qua warmup ms kể từ now (vòng vừa chạy lại sau khi tạm dừng, vừa hạ bậc). */
		reset( now, settle = warmupMs ) {
			start = -1;
			frames = 0;
			readyAt = now + settle;
		},
		/**
		 * Một khung: ms = khoảng cách với khung trước, now = thời điểm khung (ms).
		 *
		 * @returns {boolean} true khi cửa sổ vừa đo dưới ngưỡng: nên hạ một bậc (đã tự warmup lại).
		 */
		sample( ms, now ) {
			if ( now < readyAt ) {
				return false;
			}
			if ( ! ( ms > 0 ) || ms > stallMs ) {
				start = -1; // khựng hẳn: bỏ cửa sổ này
				frames = 0;
				return false;
			}
			if ( start < 0 ) {
				start = now - ms;
				frames = 0;
			}
			frames++;
			const span = now - start;
			if ( span < windowMs ) {
				return false;
			}
			const fps = ( frames * 1000 ) / span;
			const enough = frames >= minFrames || fps < 1000 / stallMs;
			start = -1;
			frames = 0;
			if ( ! enough ) {
				return false;
			}
			lastFps = fps;
			if ( fps < cfg.GOVERNOR_FPS ) {
				readyAt = now + warmupMs;
				return true;
			}
			return false;
		},
	};
}

/** Bậc đã lưu trong phiên (sessionStorage); lỗi / chưa có: 0. */
export function loadGovStep( storage, key = 'isha-ring-gov' ) {
	try {
		const v = parseInt( storage.getItem( key ), 10 );
		return Number.isFinite( v ) ? Math.max( 0, Math.min( GOV_MAX, v ) ) : 0;
	} catch ( err ) {
		return 0;
	}
}

export function saveGovStep( storage, step, key = 'isha-ring-gov' ) {
	try {
		storage.setItem( key, String( step ) );
	} catch ( err ) {}
}
