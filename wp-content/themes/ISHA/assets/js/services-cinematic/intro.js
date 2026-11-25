/**
 * Màn chờ "Bình minh" của section #lieu-trinh (khách, 25/11/2026): khi người dùng cuộn tới section, một nền chờ phủ kín khung cảnh: ba lớp
 * núi thanh dọc (Visual_1-1.png), mặt trời nhô lên khỏi đỉnh núi, rồi ánh nắng toả ra; xong thì mờ dần để lộ khung cảnh canvas chính (kèm vòng
 * thẻ). Hình và chuyển động nằm ở CSS (services-cinematic.css, khối "MÀN CHỜ"; chỉ transform / opacity nên chạy trên compositor, không
 * khựng khi cảnh WebGL đang biên dịch shader); tệp này chỉ điều phối thời điểm:
 *
 *   wait  nền chờ phủ stage ngay khi section được nâng cấp (.is-enhanced): chưa có mặt trời, người dùng chưa thấy canvas / poster
 *   play  section phủ kín màn hình (cùng ngưỡng với bình minh tự chạy) và ảnh đã tải: mặt trời mọc, tia toả
 *   out   hết lượt toả nắng VÀ khung cảnh đã sẵn sàng (cảnh WebGL vẽ xong khung đầu, hoặc chế độ 2D): overlay mờ dần
 *   done  overlay gỡ khỏi bố cục (display: none), data-intro bị xoá
 *
 * Bình minh tự chạy của canvas (autoplay.js) và vòng thẻ chỉ bắt đầu từ lúc `out` (revealed): người dùng không bỏ lỡ mặt trời của cảnh chính
 * dưới lớp phủ. Cảnh chưa sẵn sàng sau lượt toả nắng thì chờ thêm tối đa cfg.readyWait giây rồi mờ dần dù sao (poster hoặc canvas lúc đó).
 *
 * Bỏ qua được khi đang chạy: bấm / chạm khung cảnh hoặc Esc (mờ dần nhanh). Rời khỏi section giữa chừng (khuất hẳn): kết thúc ngay không hoạt ảnh, lần
 * quay lại thấy thẳng khung cảnh. Giảm chuyển động và chế độ tĩnh: main.js không tạo màn chờ (CSS cũng ẩn). Mỗi lần xem trang chỉ chạy một
 * lần (cfg.once; main.js giữ cờ), debug có replay().
 *
 * Bố cục (khung ảnh núi, vị trí mặt trời) tính bằng JS theo đúng công thức đóng khung của canvas để đỉnh núi trái và mặt trời khớp khung cảnh
 * chính (intro-layout.js), tính lại khi stage đổi kích thước.
 *
 * Không phụ thuộc DOM thật ngoài những gì truyền vào (test: tests/intro.test.mjs).
 */
import { introLayout, applyIntroLayout } from './intro-layout.js';

/** Tổng thời gian lượt hoạt ảnh (giây) từ lúc `play` tới lúc đủ điều kiện mờ dần. */
export function introPlayFor( cfg ) {
	return Math.max( cfg.rise, cfg.nearAt + cfg.nearFor, cfg.farAt + cfg.farFor ) + cfg.hold;
}

/** Ghi thời gian của cfg thành biến CSS trên section (services-cinematic.css đọc; có giá trị dự phòng nên thiếu biến vẫn chạy). */
export function applyIntroTimes( section, cfg ) {
	const set = ( k, v ) => section.style.setProperty( k, `${ v }s` );
	set( '--isha-intro-rise', cfg.rise );
	set( '--isha-intro-near-at', cfg.nearAt );
	set( '--isha-intro-near-for', cfg.nearFor );
	set( '--isha-intro-far-at', cfg.farAt );
	set( '--isha-intro-far-for', cfg.farFor );
	set( '--isha-intro-out', cfg.out );
}

/** Tải sẵn và giải mã ảnh (không bao giờ từ chối: lỗi hoặc quá hạn thì coi như xong, màn chờ vẫn chạy với ảnh tải dần). */
function preload( urls, timeoutMs, win ) {
	const jobs = urls.map( ( url ) => new Promise( ( resolve ) => {
		try {
			const img = new win.Image();
			img.decoding = 'async';
			img.src = url;
			if ( typeof img.decode === 'function' ) {
				img.decode().then( resolve, resolve );
			} else {
				img.onload = img.onerror = () => resolve();
			}
		} catch ( err ) {
			resolve();
		}
	} ) );
	return new Promise( ( resolve ) => {
		const t = win.setTimeout( resolve, timeoutMs );
		Promise.all( jobs ).then( () => {
			win.clearTimeout( t );
			resolve();
		} );
	} );
}

/**
 * @param {object}  o
 * @param {Element} o.section
 * @param {object}  o.cfg    CONFIG.intro { rise, nearAt, nearFor, farAt, farFor, hold, out, readyWait }
 * @param {number}  o.cover  Phần của section phải hiện để bắt đầu (CONFIG.autoplay.cover, cùng ngưỡng với bình minh tự chạy).
 * @param {string[]} [o.urls]   Ảnh cần tải sẵn.
 * @param {Promise} [o.ready]  Khung cảnh chính đã sẵn sàng (không bao giờ cần từ chối; quá cfg.readyWait thì thôi chờ).
 * @param {{stage:Element, framing:object, horizonUV:number, path:object}|null} [o.layout]  Đo stage và đặt khung ảnh núi / mặt trời cho
 *   khớp canvas (framing = CONFIG.framing, horizonUV = CONFIG.plate.horizonUV, path = CONFIG.stops.path). Không có thì giữ giá trị dự phòng của CSS.
 * @returns {{readonly phase:string, readonly revealed:boolean, whenRevealed:Promise<void>, done:Promise<void>, skip:() => void, replay:() => void, destroy:() => void}}
 */
export function bindIntro( { section, cfg, cover, urls = [], ready = Promise.resolve(), layout = null, win = window, doc = document } ) {
	let phase = 'wait';
	let covered = false;
	let loaded = false;
	let dead = false;
	let tPlay = 0;
	let tReady = 0;
	let tOut = 0;
	let resolveRevealed;
	let resolveDone;
	const whenRevealed = new Promise( ( r ) => ( resolveRevealed = r ) );
	const done = new Promise( ( r ) => ( resolveDone = r ) );

	const mark = () => {
		section.dataset.intro = phase;
	};
	// Đo stage và đặt khung ảnh núi / mặt trời (trước khi lớp phủ hiện: mark() mới bật data-intro).
	const relayout = () => {
		const overlay = layout && section.querySelector( '.isha-svc__intro' );
		const W = layout ? layout.stage.clientWidth : 0;
		const H = layout ? layout.stage.clientHeight : 0;
		if ( overlay && W > 0 && H > 0 ) {
			applyIntroLayout( overlay, introLayout( W, H, { framing: layout.framing, horizonUV: layout.horizonUV, path: layout.path, sunClear: cfg.sunClear } ) );
		}
	};
	relayout();
	const ro = layout && win.ResizeObserver ? new win.ResizeObserver( relayout ) : null;
	if ( ro ) {
		ro.observe( layout.stage );
	}
	applyIntroTimes( section, cfg );
	mark();

	function finish() {
		if ( phase === 'done' ) {
			return;
		}
		phase = 'done';
		win.clearTimeout( tOut );
		delete section.dataset.intro;
		resolveDone();
	}

	// Mờ dần. fast: bỏ qua (nửa thời gian); instant: người dùng không còn thấy section (không cần hoạt ảnh).
	function reveal( { fast = false, instant = false } = {} ) {
		if ( dead || phase === 'out' || phase === 'done' ) {
			return;
		}
		win.clearTimeout( tPlay );
		win.clearTimeout( tReady );
		phase = 'out';
		const secs = instant ? 0 : ( fast ? cfg.out * 0.5 : cfg.out );
		section.style.setProperty( '--isha-intro-out', `${ secs }s` );
		mark();
		resolveRevealed();
		tOut = win.setTimeout( finish, secs * 1000 + 120 );
	}

	function afterPlay() {
		if ( dead || phase !== 'play' ) {
			return;
		}
		// Cảnh sẵn sàng thì mờ dần ngay; quá readyWait giây thì mờ dần dù sao (poster hoặc canvas lúc đó).
		const go = () => {
			win.clearTimeout( tReady );
			reveal();
		};
		tReady = win.setTimeout( go, cfg.readyWait * 1000 );
		ready.then( go, go );
	}

	function play() {
		phase = 'play';
		mark();
		tPlay = win.setTimeout( afterPlay, introPlayFor( cfg ) * 1000 );
	}

	function tryPlay() {
		if ( ! dead && phase === 'wait' && covered && loaded && ! doc.hidden ) {
			play();
		}
	}

	preload( urls, 2500, win ).then( () => {
		loaded = true;
		tryPlay();
	} );

	const io = new win.IntersectionObserver( ( entries ) => {
		const e = entries[ entries.length - 1 ];
		const ratio = e.isIntersecting ? e.intersectionRatio : 0;
		if ( ratio >= cover ) {
			covered = true;
			tryPlay();
		} else if ( ratio <= 0 ) {
			covered = false;
			// Khuất hẳn giữa chừng: kết thúc ngay (lần quay lại thấy thẳng khung cảnh).
			if ( phase === 'play' ) {
				reveal( { instant: true } );
			}
		}
	}, { threshold: [ 0, cover, 1 ] } );
	io.observe( section );

	// Tab ẩn lúc tới ngưỡng: chờ tab hiện lại mới chạy (CSS cũng đứng yên khi tab ẩn, nhưng hẹn giờ thì không).
	const onVis = () => tryPlay();
	doc.addEventListener( 'visibilitychange', onVis );

	// Bỏ qua: bấm / chạm khung cảnh hoặc Esc khi lượt chạy đang hiện trước mắt (phase play; chưa tới ngưỡng phủ kín thì người dùng chưa xem
	// được gì để bỏ qua). Esc nghe ở document: tiêu điểm bàn phím chưa nằm trong section vì lớp phủ không nhận tiêu điểm.
	const skip = () => {
		if ( phase === 'play' ) {
			reveal( { fast: true } );
		}
	};
	const onDown = () => skip();
	const onKey = ( e ) => {
		if ( e.key === 'Escape' && ! ( e.altKey || e.ctrlKey || e.metaKey ) ) {
			skip();
		}
	};
	section.addEventListener( 'pointerdown', onDown );
	doc.addEventListener( 'keydown', onKey );

	return {
		get phase() {
			return phase;
		},
		/** Màn chờ đã bắt đầu mờ dần, đã gỡ hoặc đã huỷ: khung cảnh chính được phép chạy (bình minh tự chạy, vòng thẻ, vòng render). */
		get revealed() {
			return dead || phase === 'out' || phase === 'done';
		},
		whenRevealed,
		done,
		skip,
		/** Chạy lại từ đầu (bảng debug): về lớp phủ chờ rồi chạy ngay. */
		replay() {
			win.clearTimeout( tPlay );
			win.clearTimeout( tReady );
			win.clearTimeout( tOut );
			phase = 'wait';
			section.style.removeProperty( '--isha-intro-out' );
			applyIntroTimes( section, cfg );
			mark();
			void section.offsetWidth; // gỡ hoạt ảnh cũ trước khi thêm lại
			play();
		},
		destroy() {
			dead = true;
			win.clearTimeout( tPlay );
			win.clearTimeout( tReady );
			win.clearTimeout( tOut );
			io.disconnect();
			if ( ro ) {
				ro.disconnect();
			}
			doc.removeEventListener( 'visibilitychange', onVis );
			section.removeEventListener( 'pointerdown', onDown );
			doc.removeEventListener( 'keydown', onKey );
			delete section.dataset.intro;
			[ '--isha-intro-rise', '--isha-intro-near-at', '--isha-intro-near-for', '--isha-intro-far-at', '--isha-intro-far-for', '--isha-intro-out' ].forEach( ( k ) => section.style.removeProperty( k ) );
			resolveRevealed();
			resolveDone();
		},
	};
}
