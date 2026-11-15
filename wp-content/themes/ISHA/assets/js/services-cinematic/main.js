/**
 * Entry của section "Bình minh lan tỏa" (nạp bằng wp_enqueue_script_module, inc/services-cinematic.php).
 *
 * Luồng: đọc + kiểm dữ liệu JSON (#isha-svc-data) → khi section còn cách khung nhìn một màn hình (IntersectionObserver rootMargin 100%) thì
 * chọn chế độ (tier.js: detect-gpu + heuristics) và bậc chất lượng của governor (bậc đã hạ trong phiên áp ngay) → STATIC: giữ lưới thẻ SSR,
 * không tải thư viện → các chế độ khác: thêm .is-enhanced, dựng cuộn (scroll.js), card
 * (ui/card-view.js) và trợ năng (ui/a11y.js); FULL/FULL_HIGH/LITE thì dựng thêm cảnh WebGL (scene/index.js, import động); 2D tạm dùng poster
 * (fallback line-art động ở giai đoạn 8). Lỗi bất kỳ ở bước nâng cấp: gỡ .is-enhanced, quay về lưới tĩnh. Bảng debug (lil-gui) khi
 * data.debug (quản trị viên + ?isha-debug=1).
 */
import { CONFIG } from './config.js';
import { computeStops } from './stops.js';
import { createStore } from './store.js';
import { visibleIndex } from './ui/card-machine.js';
import { detectMode, buildLadder, loadGovStep, saveGovStep } from './tier.js';

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
	// không lưu. hasPost: có hậu kỳ (scene/post.js) nên thang có hai bậc tắt bloom rồi god rays.
	const ladder = buildLadder( det.mode, CONFIG.presets, { deviceDpr: window.devicePixelRatio || 1, dprSteps: CONFIG.governor.dprSteps, hasPost: true } );
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

	// Coverflow: card đang xem = index của state card; cả dãy hiện khi state là 'shown'.
	const renderCards = () => view.render( store.state.card.index, visibleIndex( store.state.card ) >= 0 );
	cleanups.push( store.on( 'card', renderCards ) );
	cleanups.push( store.on( 'index', () => a11y.onIndex( store.state.index ) ) );
	renderCards();
	a11y.onIndex( store.state.index );

	// Bấm ra ngoài dãy card (trên stage) thì ẩn dãy; bấm nhãn thời điểm thì hiện lại; bấm card bên cạnh thì cuộn tới điểm dừng của nó.
	const items = cards.map( ( c ) => c.closest( 'li' ) );
	const onDown = ( e ) => {
		if ( dayLabel && dayLabel.contains( e.target ) ) {
			store.dispatch( { type: 'REOPEN' } );
			return;
		}
		if ( ! e.target.closest( '.isha-svc__item' ) ) {
			store.dispatch( { type: 'DISMISS' } );
		}
	};
	const onClick = ( e ) => {
		const item = e.target.closest( '.isha-svc__item' );
		const i = item ? items.indexOf( item ) : -1;
		if ( i >= 0 && i !== store.state.card.index ) {
			e.preventDefault();
			scroll.go( i );
		}
	};
	const onResize = () => view.relayout();
	stage.addEventListener( 'pointerdown', onDown );
	stage.addEventListener( 'click', onClick );
	window.addEventListener( 'resize', onResize );
	cleanups.push( () => {
		stage.removeEventListener( 'pointerdown', onDown );
		stage.removeEventListener( 'click', onClick );
		window.removeEventListener( 'resize', onResize );
	} );

	// Cảnh WebGL.
	let scene = null;
	const toPoster = () => {
		section.classList.remove( 'is-ready' );
		store.state.mode = '2D';
		if ( scene ) {
			scene.destroy();
			scene = null;
		}
	};
	if ( mode === 'FULL_HIGH' || mode === 'FULL' || mode === 'LITE' ) {
		try {
			const mod = await import( './scene/index.js' );
			const onFatal = ( err ) => {
				console.warn( LOG, 'Cảnh WebGL dừng hẳn, chuyển sang poster.', err );
				toPoster();
			};
			// Governor hạ bậc: nhớ cho phiên; bậc 2D thì huỷ cảnh, hiện poster (card, cuộn, trợ năng vẫn chạy).
			const onStep = ( i, q, avg ) => {
				if ( ! det.forced ) {
					saveGovStep( det.mode, i );
				}
				const what = q.mode === '2D' ? '2D (poster)' : `${ q.mode }, DPR ${ q.dpr }, cỏ ${ q.grass }, cây ${ q.trees }, mây ${ q.clouds }, ${ q.post ? ( q.bloom ? 'god rays + bloom' : 'god rays' ) : 'không hậu kỳ' }`;
				console.info( LOG, `governor: khung hình trung bình ${ avg.toFixed( 1 ) }ms → bậc ${ i }: ${ what }` );
				if ( q.mode === '2D' ) {
					toPoster();
				}
			};
			scene = await mod.createScene( { canvas, section, stage, store, stops, data, ladder, step, mobile: det.mobile, reduced, onStep, onFatal } );
			section.classList.add( 'is-ready' );
			cleanups.push( () => scene && scene.destroy() );
		} catch ( err ) {
			console.warn( LOG, 'Không dựng được cảnh WebGL, dùng poster.', err );
			store.state.mode = '2D';
		}
	}

	if ( data.debug ) {
		try {
			const { createDebug } = await import( './debug.js' );
			const dbg = await createDebug( { store, stops, scene, det, data, scroll, setSegment, relayoutCards: () => view.relayout() } );
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
