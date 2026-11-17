/**
 * Vòng requestAnimationFrame DUY NHẤT của vòng thẻ (prompt mục 6, 9): dt kẹp ≤ 1/30 giây (quay lại tab không nhảy); chỉ chạy khi section
 * trong khung nhìn (IntersectionObserver) và tab đang hiện (visibilitychange); khi dừng thì báo onPause( true ) để CSS dừng cả glint
 * (animation-play-state, class trên section).
 *
 * @param {object}   o
 * @param {(dt: number, now: number) => void} o.tick
 * @param {Element}  [o.observe]  Phần tử theo dõi khung nhìn (bỏ trống: luôn coi là đang hiện).
 * @param {(paused: boolean) => void} [o.onPause]
 */
export function createLoop( { tick, observe, onPause, win = window, doc = document } ) {
	let raf = 0;
	let last = 0;
	let inView = ! observe;
	let wanted = false;
	let io = null;

	function frame( now ) {
		raf = win.requestAnimationFrame( frame );
		const dt = last ? Math.min( ( now - last ) / 1000, 1 / 30 ) : 0;
		last = now;
		tick( dt, now );
	}

	function sync() {
		const run = wanted && inView && ! doc.hidden;
		if ( run && ! raf ) {
			last = 0;
			raf = win.requestAnimationFrame( frame );
		} else if ( ! run && raf ) {
			win.cancelAnimationFrame( raf );
			raf = 0;
		}
		if ( onPause ) {
			onPause( ! run );
		}
	}

	if ( observe && win.IntersectionObserver ) {
		io = new win.IntersectionObserver( ( entries ) => {
			inView = entries.some( ( e ) => e.isIntersecting );
			sync();
		} );
		io.observe( observe );
	} else {
		inView = true;
	}
	doc.addEventListener( 'visibilitychange', sync );

	return {
		start() {
			wanted = true;
			sync();
		},
		stop() {
			wanted = false;
			sync();
		},
		get running() {
			return raf !== 0;
		},
		destroy() {
			wanted = false;
			sync();
			if ( io ) {
				io.disconnect();
			}
			doc.removeEventListener( 'visibilitychange', sync );
		},
	};
}
