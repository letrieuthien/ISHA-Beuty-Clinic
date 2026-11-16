/**
 * Entry của section "Bình minh lan tỏa" (nạp bằng wp_enqueue_script_module, inc/services-cinematic.php).
 *
 * Luồng: đọc + kiểm dữ liệu JSON (#isha-svc-data) → khi section còn cách khung nhìn một màn hình (IntersectionObserver rootMargin 100%) thì
 * chọn chế độ (tier.js: detect-gpu + heuristics) và bậc chất lượng của governor (bậc đã hạ trong phiên áp ngay) → STATIC: giữ lưới thẻ SSR,
 * không tải thư viện → các chế độ khác: thêm .is-enhanced, dựng cuộn (scroll.js), card
 * (ui/card-view.js) và trợ năng (ui/a11y.js). Cảnh nền v3 (một ảnh thật + bản đồ độ sâu + camera 2.5D, docs/isha-lookdev-prompt-v3.md) đang
 * dựng trên tools/lookdev.html và chỉ nối vào đây ở bước 8; tới lúc đó mọi chế độ hiện poster SSR. Lỗi bất kỳ ở bước nâng cấp: gỡ
 * .is-enhanced, quay về lưới tĩnh. Bảng debug (lil-gui) khi data.debug (quản trị viên + ?isha-debug=1).
 */
import { CONFIG } from './config.js';
import { computeStops } from './stops.js';
import { createStore } from './store.js';
import { visibleIndex } from './ui/card-machine.js';
import { detectMode, buildLadder, loadGovStep } from './tier.js';

const LOG = '[ISHA svc]';

function readData( section ) {
	const el = section.querySelector( '#isha-svc-data' );
	if ( ! el ) {
		return null;
	}
	try {
		const d = JSON.parse( el.textContent );
		const ok = d && d.version === 1 && Array.isArray( d.services ) && d.services.length >= 1 && d.services.length <= 6 &&
			d.services.every( ( s ) => s && typeof s.title === 'string' && typeof s.url === 'string' ) && d.category && typeof d.seed === 'number';
		if ( ! ok ) {
			console.warn( LOG, 'Dữ liệu không đúng hợp đồng, giữ lưới tĩnh.', d );
			return null;
		}
		return d;
	} catch ( err ) {
		console.warn( LOG, 'Không đọc được dữ liệu JSON, giữ lưới tĩnh.', err );
		return null;
	}
}

async function enhance( section, data, reduced ) {
	const det = await detectMode( { reduced, debug: !! data.debug, timeoutMs: CONFIG.gpu.timeoutMs } );
	// Thang chất lượng của governor. Bậc đã hạ trong phiên của tab (sessionStorage) áp ngay từ đầu; ép chế độ bằng debug thì luôn từ bậc 0 và
	// không lưu.
	const ladder = buildLadder( det.mode, CONFIG.presets, { deviceDpr: window.devicePixelRatio || 1, dprSteps: CONFIG.governor.dprSteps } );
	let step = 0;
	if ( ladder.length > 1 && ! det.forced ) {
		step = Math.min( loadGovStep( det.mode ), ladder.length - 1 );
		if ( step > 0 ) {
			det.reasons.push( `governor đã hạ ${ step } bậc trong phiên này` );
		}
	}
	const mode = ladder.length > 1 ? ladder[ step ].mode : det.mode;
	console.info( LOG, 'chế độ', mode, det.reasons.join( '; ' ) );
	if ( mode === 'STATIC' ) {
		return null;
	}

	const stage = section.querySelector( '.isha-svc__stage' );
	const live = section.querySelector( '#isha-svc-live' );
	const dayLabel = section.querySelector( '.isha-svc__daylabel' );
	const cards = Array.from( section.querySelectorAll( '.isha-card' ) );
	const N = data.services.length;
	if ( ! stage || ! live || cards.length !== N ) {
		console.warn( LOG, 'Markup thiếu phần tử, giữ lưới tĩnh.' );
		return null;
	}

	const stops = computeStops( N, CONFIG.stops );
	const store = createStore( N );
	store.state.mode = mode;
	store.state.tier = det.gpu;
	const cleanups = [];

	const setSegment = () => section.style.setProperty( '--isha-svc-seg', String( CONFIG.SCROLL_VH_PER_SERVICE[ det.mobile ? 'mobile' : 'desktop' ] ) );
	setSegment();
	section.classList.add( 'is-enhanced' );
	cleanups.push( () => section.style.removeProperty( '--isha-svc-seg' ) );
	let scroll;
	try {
		const { setupScroll } = await import( './scroll.js' );
		scroll = await setupScroll( { section, stage, store, isTouch: det.mobile, reduced } );
	} catch ( err ) {
		console.warn( LOG, 'Không dựng được cuộn (GSAP), giữ lưới tĩnh.', err );
		section.classList.remove( 'is-enhanced' );
		return null;
	}
	cleanups.push( () => scroll.destroy() );

	const [ { createCardView }, { createA11y } ] = await Promise.all( [ import( './ui/card-view.js' ), import( './ui/a11y.js' ) ] );
	const view = createCardView( { cards, gsap: scroll.gsap, reduced } );
	const a11y = createA11y( { stage, live, dayLabel, services: data.services, stops } );
	cleanups.push( () => view.destroy(), () => a11y.destroy() );

	// Dãy card ở chân núi: hiện khi state card là 'shown' (mọi card như nhau, index chỉ điều khiển khung cảnh).
	const renderCards = () => view.render( store.state.card.index, visibleIndex( store.state.card ) >= 0 );
	cleanups.push( store.on( 'card', renderCards ) );
	cleanups.push( store.on( 'index', () => a11y.onIndex( store.state.index ) ) );
	renderCards();
	a11y.onIndex( store.state.index );

	// Bấm ra ngoài dãy card (trên stage) thì ẩn dãy, trừ khi đang có card lật (lúc đó chỉ lật lại); bấm nhãn thời điểm thì hiện lại.
	const onDown = ( e ) => {
		if ( dayLabel && dayLabel.contains( e.target ) ) {
			store.dispatch( { type: 'REOPEN' } );
			return;
		}
		if ( e.target.closest( '.isha-svc__list, .isha-svc__arrow' ) ) {
			return;
		}
		if ( ! view.unflipAll() ) {
			store.dispatch( { type: 'DISMISS' } );
		}
	};
	stage.addEventListener( 'pointerdown', onDown );
	cleanups.push( () => stage.removeEventListener( 'pointerdown', onDown ) );

	// Cảnh WebGL v3: chưa tích hợp (bước 8). Mọi chế độ hiện poster SSR; cuộn, card và trợ năng vẫn chạy đủ.
	store.state.mode = '2D';
	const scene = null;

	if ( data.debug ) {
		try {
			const { createDebug } = await import( './debug.js' );
			const dbg = await createDebug( { store, scene, det, scroll, setSegment } );
			cleanups.push( () => dbg.destroy() );
		} catch ( err ) {
			console.warn( LOG, 'Không mở được bảng debug.', err );
		}
	}

	return {
		destroy() {
			cleanups.reverse().forEach( ( fn ) => {
				try {
					fn();
				} catch ( err ) {
					console.warn( LOG, err );
				}
			} );
			section.classList.remove( 'is-enhanced', 'is-ready' );
		},
	};
}

function boot( section ) {
	const data = readData( section );
	if ( ! data ) {
		return;
	}
	const mq = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	let app = null;
	let starting = null;

	const start = () => {
		if ( app || starting ) {
			return;
		}
		starting = enhance( section, data, mq.matches )
			.then( ( a ) => {
				app = a;
			} )
			.catch( ( err ) => {
				console.warn( LOG, err );
				section.classList.remove( 'is-enhanced', 'is-ready' );
			} )
			.finally( () => {
				starting = null;
			} );
	};

	const io = new IntersectionObserver( ( entries ) => {
		if ( entries.some( ( e ) => e.isIntersecting ) ) {
			io.disconnect();
			start();
		}
	}, { rootMargin: '100% 0px' } );
	io.observe( section );

	// Người dùng đổi cài đặt giảm chuyển động: dỡ chế độ hiện tại rồi dựng lại theo cài đặt mới.
	mq.addEventListener( 'change', async () => {
		if ( starting ) {
			await starting;
		}
		if ( app ) {
			app.destroy();
			app = null;
		}
		start();
	} );
}

const section = document.querySelector( '[data-isha-svc]' );
if ( section ) {
	boot( section );
}
