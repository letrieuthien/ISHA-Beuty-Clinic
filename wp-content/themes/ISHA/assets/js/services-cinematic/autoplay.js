/**
 * Bình minh tự chạy (khách: camera tiến tới và mặt trời mọc KHÔNG còn theo cuộn chuột). Section cao đúng một khung nhìn; khi nó phủ kín
 * màn hình (phần của section đang hiện ≥ CONFIG.autoplay.cover) thì progress tự chạy 0 → 1 trong CONFIG.autoplay.duration giây (easeInOutSine),
 * camera và mặt trời đi theo progress qua bộ làm mượt của store như trước (camera nhanh, mặt trời trôi theo sau).
 *
 * Rời section: CHỈ khi section đã khuất HẲN (không còn điểm ảnh nào trong khung nhìn) thì đặt lại về đầu (progress, camera, mặt trời về 0
 * ngay, không tua ngược: lúc đó không ai thấy), lần sau quay lại và phủ kín màn hình thì chạy lại. Chi phí đặt lại: gán vài con số; không
 * dựng lại cảnh, shader hay texture; vòng render của cảnh vốn dừng khi section khuất (lifecycle.js). Hai ngưỡng cách xa nhau (chạy: phủ
 * ≥ 95%, đặt lại: 0%) nên cuộn lơ lửng quanh ngưỡng không gây chạy / đặt lại liên tục. Tab ẩn: thời gian của lần chạy dừng theo.
 *
 * createAutoplay: bộ điều khiển THUẦN theo thời gian (test: tests/autoplay.test.mjs). bindAutoplay: nối với DOM (IntersectionObserver,
 * visibilitychange, một vòng rAF chỉ chạy khi đang chạy) và store.
 */

export const easeInOutSine = ( t ) => ( 1 - Math.cos( Math.PI * t ) ) / 2;

/**
 * @param {{duration:number, cover:number}} cfg  duration: giây; cover: phần của section phải hiện để bắt đầu (0 … 1). Đọc mỗi lần dùng
 *                                               (bảng debug chỉnh được).
 */
export function createAutoplay( cfg ) {
	let phase = 'idle'; // 'idle' (chưa chạy, progress 0) | 'playing' | 'done' (đã tới 1)
	let t0 = 0;
	let hiddenAt = -1;
	let p = 0;
	const start = ( now ) => {
		phase = 'playing';
		t0 = now;
		hiddenAt = -1;
		p = 0;
	};
	return {
		get phase() {
			return phase;
		},
		get progress() {
			return p;
		},
		/**
		 * Phần của section đang hiện (IntersectionObserver.intersectionRatio).
		 *
		 * @returns {'start'|'reset'|null}
		 */
		observe( ratio, now ) {
			if ( phase === 'idle' && ratio >= cfg.cover ) {
				start( now );
				return 'start';
			}
			if ( phase !== 'idle' && ratio <= 0 ) {
				phase = 'idle';
				p = 0;
				hiddenAt = -1;
				return 'reset';
			}
			return null;
		},
		/** Progress tại now (ms). Đang tạm dừng (tab ẩn) thì đứng yên. */
		tick( now ) {
			if ( phase === 'playing' && hiddenAt < 0 ) {
				const u = ( now - t0 ) / ( Math.max( 0.1, cfg.duration ) * 1000 );
				if ( u >= 1 ) {
					phase = 'done';
					p = 1;
				} else {
					p = easeInOutSine( Math.max( 0, u ) );
				}
			}
			return p;
		},
		/** Tab ẩn: dừng đồng hồ của lần chạy. */
		suspend( now ) {
			if ( phase === 'playing' && hiddenAt < 0 ) {
				hiddenAt = now;
			}
		},
		/** Tab hiện lại: chạy tiếp từ chỗ đã dừng. */
		resume( now ) {
			if ( hiddenAt >= 0 ) {
				t0 += now - hiddenAt;
				hiddenAt = -1;
			}
		},
		/** Chạy lại từ đầu (bảng debug). */
		restart( now ) {
			start( now );
		},
	};
}

/**
 * @param {object} o
 * @param {Element} o.section
 * @param {object}  o.store   store.js: setProgress, snap, setInView.
 * @param {{duration:number, cover:number}} o.cfg  CONFIG.autoplay.
 * @returns {{readonly phase:string, readonly progress:number, replay:() => void, destroy:() => void}}
 */
export function bindAutoplay( { section, store, cfg, win = window, doc = document } ) {
	const ap = createAutoplay( cfg );
	let raf = 0;
	const now = () => win.performance.now();
	store.snap( 0 ); // bình minh ở đầu (index 0) trước khi section phủ kín màn hình
	// Trạng thái lên section (data-dawn: idle | playing | done) cho CSS / kiểm thử; chỉ ghi khi đổi.
	const mark = () => {
		if ( section.dataset.dawn !== ap.phase ) {
			section.dataset.dawn = ap.phase;
		}
	};
	mark();

	function frame( t ) {
		raf = 0;
		store.setProgress( ap.tick( t ) );
		mark();
		if ( ap.phase === 'playing' && ! doc.hidden ) {
			raf = win.requestAnimationFrame( frame );
		}
	}
	const run = () => {
		if ( ! raf && ap.phase === 'playing' && ! doc.hidden ) {
			raf = win.requestAnimationFrame( frame );
		}
	};
	const stop = () => {
		if ( raf ) {
			win.cancelAnimationFrame( raf );
			raf = 0;
		}
	};

	function onStart() {
		mark();
		store.setInView( true ); // vòng thẻ hiện cùng lúc bình minh bắt đầu
		run();
	}
	function onReset() {
		stop();
		store.snap( 0 ); // về đầu NGAY (section đã khuất: không tua ngược trước mắt người xem)
		store.setInView( false );
		mark();
	}

	const io = new win.IntersectionObserver( ( entries ) => {
		const e = entries[ entries.length - 1 ];
		const r = ap.observe( e.isIntersecting ? e.intersectionRatio : 0, now() );
		if ( r === 'start' ) {
			onStart();
		} else if ( r === 'reset' ) {
			onReset();
		}
	}, { threshold: [ 0, cfg.cover, 1 ] } );
	io.observe( section );

	const onVis = () => {
		if ( doc.hidden ) {
			ap.suspend( now() );
			stop();
		} else {
			ap.resume( now() );
			run();
		}
	};
	doc.addEventListener( 'visibilitychange', onVis );

	return {
		get phase() {
			return ap.phase;
		},
		get progress() {
			return ap.progress;
		},
		/** Chạy lại bình minh từ đầu (bảng debug). */
		replay() {
			store.snap( 0 );
			ap.restart( now() );
			store.setInView( true );
			mark();
			run();
		},
		destroy() {
			stop();
			io.disconnect();
			doc.removeEventListener( 'visibilitychange', onVis );
			delete section.dataset.dawn;
		},
	};
}
