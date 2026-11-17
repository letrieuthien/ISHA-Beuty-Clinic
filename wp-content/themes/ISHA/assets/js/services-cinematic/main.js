/**
 * Entry của section "Bình minh lan tỏa" (nạp bằng wp_enqueue_script_module, inc/services-cinematic.php).
 *
 * Luồng: đọc + kiểm dữ liệu JSON (#isha-svc-data) → khi section còn cách khung nhìn một màn hình (IntersectionObserver rootMargin 100%) thì
 * chọn chế độ (tier.js: detect-gpu + heuristics) và bậc chất lượng của governor (bậc đã hạ trong phiên áp ngay) → STATIC: giữ lưới thẻ SSR,
 * không tải thư viện → các chế độ khác: thêm .is-enhanced (section cao một khung nhìn), bình minh tự chạy (autoplay.js: khi section phủ kín
 * màn hình, camera tiến tới và mặt trời mọc tự động; khuất hẳn thì đặt lại, quay lại thì chạy lại), hiện / ẩn vòng thẻ theo state card và
 * trợ năng (ui/a11y.js) →
 * FULL_HIGH / FULL / LITE: dựng cảnh v3 (scene/index.js: một ảnh thật + độ sâu tự suy + camera 2.5D, docs/isha-lookdev-prompt-v3.md),
 * poster SSR che tới khi khung đầu sẵn sàng (.is-ready mờ dần sang canvas); vòng render chỉ chạy khi section trong khung nhìn và tab đang
 * hiện (lifecycle.js, kèm khôi phục khi mất context WebGL). Chế độ 2D, hoặc cảnh lỗi / governor xuống 2D / mất context lần hai: giữ poster
 * (phóng nhẹ theo progress của bình minh). Bảng
 * debug (lil-gui) khi data.debug (quản trị viên + ?isha-debug=1).
 *
 * Vòng cung ánh kim (assets/js/card-ring/, README-card-ring.md) dựng ở boot, ĐỘC LẬP với chế độ cảnh: mọi chế độ (kể cả STATIC) đều có vòng;
 * giảm chuyển động thì vòng tự thành hàng cuộn ngang (theo dõi cài đặt). Cảnh WebGL nối tia nắng của mặt trời với viền thẻ (scene/index.js).
 */
import { CONFIG, applyRingTimes } from './config.js';
import { createStore } from './store.js';
import { isShown } from './ui/card-machine.js';
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

/**
 * Hiện / ẩn vòng thẻ theo state card của section (vào / rời section, Esc hoặc chạm khung cảnh để ẩn, Enter hoặc chạm lại để hiện). Ẩn: mờ
 * dần, inert (không nhận focus / chuột), tia nắng thôi nối tới thẻ.
 */
function ringVisibility( el ) {
	let shown = true;
	return {
		set( on ) {
			if ( on === shown ) {
				return;
			}
			shown = on;
			el.classList.toggle( 'is-hidden', ! on );
			el.inert = ! on;
			if ( ! on && el.contains( document.activeElement ) ) {
				el.closest( '.isha-svc__stage' )?.focus( { preventScroll: true } );
			}
		},
		get shown() {
			return shown;
		},
	};
}

async function enhance( section, data, reduced, ringP, hold ) {
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
	const ringEl = section.querySelector( '[data-isha-ring]' );
	const N = data.services.length;
	if ( ! stage || ! live || ! ringEl || ringEl.querySelectorAll( '.isha-ring__card:not([aria-hidden])' ).length !== N /* thẻ thật; bản sao của vòng mang aria-hidden */ ) {
		console.warn( LOG, 'Markup thiếu phần tử, giữ lưới tĩnh.' );
		return null;
	}

	const store = createStore( N );
	store.state.mode = mode;
	store.state.tier = det.gpu;
	const cleanups = [];

	section.classList.add( 'is-enhanced' );
	// Bình minh tự chạy (khách: camera / mặt trời không còn theo cuộn chuột): section phủ kín màn hình thì chạy, khuất hẳn thì đặt lại.
	const { bindAutoplay } = await import( './autoplay.js' );
	const auto = bindAutoplay( { section, store, cfg: CONFIG.autoplay } );
	cleanups.push( () => auto.destroy() );

	// Phím trong section: Esc ẩn vòng thẻ (vòng đang có thẻ được chọn thì nó tự xử lý và chặn phím trước), Enter trên stage hiện lại. Mũi
	// tên / PageUp / PageDown là cuộn gốc của trình duyệt (không còn điểm dừng theo cuộn).
	const onKey = ( e ) => {
		if ( e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey ) {
			return;
		}
		if ( e.key === 'Escape' ) {
			store.dispatch( { type: 'DISMISS' } );
			e.preventDefault();
		} else if ( e.key === 'Enter' && e.target === stage ) {
			store.dispatch( { type: 'REOPEN' } );
			e.preventDefault();
		}
	};
	section.addEventListener( 'keydown', onKey );
	cleanups.push( () => section.removeEventListener( 'keydown', onKey ) );

	const { createA11y } = await import( './ui/a11y.js' );
	const a11y = createA11y( { stage, live, services: data.services } );
	cleanups.push( () => a11y.destroy() );
	const ring = await ringP;
	// Section vừa chuyển bố cục (vòng neo đáy stage dính): đo lại vị trí vòng cho phép nối tia nắng.
	requestAnimationFrame( () => ring && ring.measure() );

	// Vòng thẻ ở chân núi: hiện khi state card là 'shown'.
	applyRingTimes( section );
	const vis = ringVisibility( ringEl );
	const renderRing = () => vis.set( isShown( store.state.card ) );
	cleanups.push( store.on( 'card', renderRing ) );
	cleanups.push( store.on( 'card', ( prev ) => a11y.onCard( prev, store.state.card ) ) );
	renderRing();
	cleanups.push( () => vis.set( true ) );

	// Chạm / bấm vào khung cảnh (ngoài vòng thẻ): vòng đang ẩn thì hiện lại; đang có thẻ được chọn thì chỉ bỏ chọn (vòng tự lo); còn lại thì
	// ẩn vòng để ngắm khung cảnh. Pha capture trên window: đọc trạng thái vòng TRƯỚC khi vòng xử lý focus-out (capture trên document).
	const onDown = ( e ) => {
		if ( ! stage.contains( e.target ) || e.target.closest( '.isha-ring' ) ) {
			return;
		}
		const sel = ring && ( ring.state.mode === 'SEEKING' || ring.state.mode === 'FOCUSED' );
		if ( store.state.card.phase === 'dismissed' ) {
			store.dispatch( { type: 'REOPEN' } );
		} else if ( ! sel ) {
			store.dispatch( { type: 'DISMISS' } );
		}
	};
	window.addEventListener( 'pointerdown', onDown, true );
	cleanups.push( () => window.removeEventListener( 'pointerdown', onDown, true ) );

	// Cảnh WebGL v3 (lifecycle.js: chỉ vẽ khi trong khung nhìn và tab hiện, dựng lại khi khôi phục context WebGL). Chế độ 2D, dựng lỗi,
	// governor xuống 2D hoặc mất context lần hai: giữ poster (ảnh cảnh đã tô màu, phóng nhẹ theo progress, grain + vignette CSS); bình minh tự chạy / card /
	// trợ năng vẫn chạy đủ.
	const canvas = section.querySelector( '.isha-svc__canvas' );
	let life = null;
	let offProgress = null;
	const poster2D = ( why ) => {
		console.info( LOG, 'chế độ 2D:', why );
		section.classList.remove( 'is-ready' );
		store.state.mode = '2D';
		if ( ! offProgress ) {
			// Poster phóng nhẹ theo progress của bình minh (CSS: --isha-svc-p). Chỉ ở 2D: chế độ WebGL không cần ghi style này.
			offProgress = store.on( 'progress', ( p ) => section.style.setProperty( '--isha-svc-p', p.toFixed( 4 ) ) );
			section.style.setProperty( '--isha-svc-p', store.state.progress.toFixed( 4 ) );
			cleanups.push( () => {
				offProgress();
				section.style.removeProperty( '--isha-svc-p' );
			} );
		}
	};
	if ( mode !== '2D' && canvas ) {
		const [ { createScene }, { createLifecycle } ] = await Promise.all( [ import( './scene/index.js' ), import( './lifecycle.js' ) ] );
		life = createLifecycle( {
			section,
			canvas,
			build: ( fallback ) => createScene( {
				canvas,
				store,
				N,
				ladder,
				step,
				mobile: det.mobile,
				reduced,
				cards: ring ? { ring, on: () => vis.shown } : null,
				imgBase: new URL( '../../img/scene/', data.base ).href,
				onStep: ( s ) => {
					step = s;
					if ( ! det.forced ) {
						saveGovStep( det.mode, s );
					}
				},
				onFallback: () => {
					if ( ! det.forced ) {
						saveGovStep( det.mode, ladder.length - 1 );
					}
					fallback();
				},
			} ),
			// Khung đầu đã vẽ: poster mờ dần sang canvas (một khung sau để trình duyệt kịp vẽ canvas).
			onReady: () => requestAnimationFrame( () => section.classList.add( 'is-ready' ) ),
			onLost: ( why ) => {
				console.info( LOG, why );
				section.classList.remove( 'is-ready' );
			},
			on2D: poster2D,
		} );
		cleanups.push( () => life.destroy() );
		// Governor của vòng nhường cảnh: cảnh đang dựng (biên dịch shader, giải mã ảnh: khung khựng) hoặc còn bậc 3D thấp hơn để hạ thì vòng
		// không hạ. Cảnh đã xuống 2D (poster): vòng tự lo.
		hold.defer = () => {
			const sc = life.scene;
			if ( ! sc ) {
				return store.state.mode !== '2D';
			}
			return sc.step < sc.ladder.length - 2;
		};
		cleanups.push( () => ( hold.defer = () => false ) );
		await life.ready;
	} else {
		poster2D( mode === '2D' ? det.reasons.join( '; ' ) : 'thiếu canvas' );
	}

	if ( data.debug ) {
		try {
			const { createDebug } = await import( './debug.js' );
			const dbg = await createDebug( { store, getScene: () => ( life ? life.scene : null ), det, auto, ring } );
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

/**
 * Vòng cung ánh kim: một lần cho cả trang, theo dõi giảm chuyển động tự nó. Lỗi: giữ hàng cuộn SSR. govDefer: governor của vòng nhường
 * cảnh WebGL (hold.defer do enhance gán khi đã có cảnh): cảnh còn bậc để hạ thì vòng chỉ đo, không hạ (đo trên GPU tích hợp: vòng gần như
 * không tốn gì, cảnh mới là phần nặng; hạ vòng chỉ làm mất phản chiếu / ánh kim mà trang không nhanh hơn).
 */
function bootRing( section, hold ) {
	const el = section.querySelector( '[data-isha-ring]' );
	const list = el && el.querySelector( '.isha-ring__ring' );
	if ( ! list || ! list.children.length ) {
		return Promise.resolve( null );
	}
	return import( '../card-ring/index.js' )
		.then( ( { createCardRing } ) => createCardRing( { stage: el, list, origin: section.querySelector( '.isha-svc__stage' ), govDefer: () => hold.defer() } ) )
		.catch( ( err ) => {
			console.warn( LOG, 'Không dựng được vòng thẻ, giữ hàng cuộn.', err );
			return null;
		} );
}

function boot( section ) {
	const data = readData( section );
	if ( ! data ) {
		return;
	}
	const mq = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	let app = null;
	let starting = null;
	let ringP = null;
	const hold = { defer: () => false };

	const start = () => {
		if ( app || starting ) {
			return;
		}
		ringP = ringP || bootRing( section, hold );
		starting = enhance( section, data, mq.matches, ringP, hold )
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
