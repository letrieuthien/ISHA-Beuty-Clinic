/**
 * Entry của section "Bình minh lan tỏa" (nạp bằng wp_enqueue_script_module, inc/services-cinematic.php).
 *
 * Luồng: đọc + kiểm dữ liệu JSON (#isha-svc-data) → khi section còn cách khung nhìn một màn hình (IntersectionObserver rootMargin 100%) thì
 * chọn chế độ (tier.js) → STATIC: giữ lưới thẻ SSR, không tải thư viện → các chế độ khác: thêm .is-enhanced, dựng cuộn (scroll.js), card
 * (ui/card-view.js) và trợ năng (ui/a11y.js); FULL/FULL_HIGH/LITE thì dựng thêm cảnh WebGL (scene/index.js, import động); 2D tạm dùng poster
 * (fallback line-art động ở giai đoạn 8). Lỗi bất kỳ ở bước nâng cấp: gỡ .is-enhanced, quay về lưới tĩnh. Bảng debug (lil-gui) khi
 * data.debug (quản trị viên + ?isha-debug=1).
 */
import { CONFIG } from './config.js';
import { computeStops } from './stops.js';
import { createStore } from './store.js';
import { visibleIndex } from './ui/card-machine.js';
import { detectMode } from './tier.js';

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
	const det = detectMode( { reduced, debug: !! data.debug } );
	console.info( LOG, 'chế độ', det.mode, det.reasons.join( '; ' ) );
	if ( det.mode === 'STATIC' ) {
		return null;
	}

	const stage = section.querySelector( '.isha-svc__stage' );
	const canvas = section.querySelector( '.isha-svc__canvas' );
	const live = section.querySelector( '#isha-svc-live' );
	const dayLabel = section.querySelector( '.isha-svc__daylabel' );
	const cards = Array.from( section.querySelectorAll( '.isha-card' ) );
	const N = data.services.length;
	if ( ! stage || ! canvas || ! live || cards.length !== N ) {
		console.warn( LOG, 'Markup thiếu phần tử, giữ lưới tĩnh.' );
		return null;
	}

	const stops = computeStops( N, CONFIG.stops );
	const store = createStore( N );
	store.state.mode = det.mode;
	const cleanups = [];

	section.classList.add( 'is-enhanced' );
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

	cleanups.push( store.on( 'card', () => view.render( visibleIndex( store.state.card ) ) ) );
	cleanups.push( store.on( 'index', () => a11y.onIndex( store.state.index ) ) );
	view.render( visibleIndex( store.state.card ) );
	a11y.onIndex( store.state.index );

	// Bấm ra ngoài card (trên stage) thì ẩn card của điểm dừng hiện tại; bấm nhãn thời điểm thì hiện lại.
	const onDown = ( e ) => {
		if ( dayLabel && dayLabel.contains( e.target ) ) {
			store.dispatch( { type: 'REOPEN' } );
			return;
		}
		if ( ! e.target.closest( '.isha-card' ) ) {
			store.dispatch( { type: 'DISMISS' } );
		}
	};
	stage.addEventListener( 'pointerdown', onDown );
	cleanups.push( () => stage.removeEventListener( 'pointerdown', onDown ) );

	// Cảnh WebGL.
	let scene = null;
	if ( det.mode === 'FULL_HIGH' || det.mode === 'FULL' || det.mode === 'LITE' ) {
		try {
			const mod = await import( './scene/index.js' );
			scene = await mod.createScene( { canvas, section, stage, store, stops, data, mode: det.mode, mobile: det.mobile, reduced } );
			section.classList.add( 'is-ready' );
			cleanups.push( () => scene.destroy() );
		} catch ( err ) {
			console.warn( LOG, 'Không dựng được cảnh WebGL, dùng poster.', err );
			store.state.mode = '2D';
		}
	}

	if ( data.debug ) {
		try {
			const { createDebug } = await import( './debug.js' );
			const dbg = await createDebug( { store, stops, scene, det, data } );
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
