/**
 * Vòng cung ánh kim: nối các module (prompt 8.2). Nhận danh sách thẻ THẬT đã có trong DOM (SSR ở bước 7; lookdev dựng bằng JS) và nâng cấp:
 *   - hình học theo bề rộng khung (≥ 1200 desktop, 768 – 1199 tablet: vòng lõm; < 768: hàng phẳng), nhân bản thẻ khi được xoay vòng
 *     (≥ SPIN_MIN_CARDS thẻ thật; bản sao aria-hidden, liên kết tabindex −1, bỏ id), ít hơn thì đứng yên canh giữa;
 *   - một vòng rAF (loop.js) chạy chuyển động (spring.js), chỉ khi trong khung nhìn và tab hiện; .is-paused dừng cả glint CSS;
 *   - tương tác (bước 5): máy trạng thái machine.js (SPINNING / HOVER_PAUSED / SEEKING / FOCUSED, vuốt: DRAGGING / SNAPPING / WAIT), đầu
 *     vào input.js. Hover chuột: lật ra mặt sau, phóng nhẹ, thẻ khác mờ, vòng dừng có quán tính. Chọn thẻ (click, chạm, Tab, ← / →): seek
 *     vào giữa rồi FOCUSED (lật ra mặt sau, phóng FOCUS_SCALE, thẻ khác mờ DIM_OTHERS + phủ tối nhẹ); click lần hai mở trang chi tiết;
 *     glint nhanh / sáng hơn trên thẻ hover / chọn;
 *   - điểm sáng ánh kim ở chỗ tia nắng chạm viền (setHit, light.js tính);
 *   - ánh sáng (bước 4): độ đục lớp ánh kim chạy theo góc thẻ (cardSpec, ghi mỗi khung, bỏ qua thay đổi < 0.004), hover sáng thêm
 *     SPEC_HOT lần (chuyển mượt SPEC_EASE_MS); phản chiếu sàn (.has-reflect) trên vòng lõm.
 * Không đọc layout trong vòng lặp: kích thước và vị trí khung đo khi đổi kích thước (ResizeObserver).
 */
import { RING } from './config.js';
import { createRing, ringCount, flatCount, slotDeg, spins, centerSpin } from './ring.js';
import { createMotion } from './spring.js';
import { createLoop } from './loop.js';
import { cardQuad, cardSpec } from './light.js';
import { reduce, initialState, SPINNING, HOVER_PAUSED, SEEKING, FOCUSED, DRAGGING, SNAPPING } from './machine.js';
import { bindInput } from './input.js';

/**
 * @param {object}      o
 * @param {HTMLElement} o.stage  .isha-ring (khung có perspective, mask hai mép).
 * @param {HTMLElement} o.list   .isha-ring__ring (ul), con là li.isha-ring__card thật.
 * @param {object}      [o.cfg]  RING.
 * @param {boolean}     [o.reduced]
 */
export function createCardRing( { stage, list, cfg = RING, reduced = false, win = window, doc = document } ) {
	const real = Array.from( list.children );
	const nReal = real.length;
	const motion = createMotion( cfg );
	const frameFns = new Set();
	let cards = real.slice();
	let ring = null;
	let geo = null;
	let state = initialState();
	let hotI = -1;
	let mouseOver = -1;
	let timer = 0;
	let dragSpin0 = 0;
	let paused = false;
	let hitEls = [];
	let glints = [];
	let rect = { left: 0, top: 0, width: 0, height: 0 };
	const quadBuf = [];

	const isSpinning = () => spins( nReal, cfg ) && ! reduced;
	const omegaAuto = () => ( geo && geo.flat ? cfg.OMEGA_MOBILE : cfg.OMEGA_DESKTOP );
	const wantOmega = () => ( isSpinning() && state.mode === SPINNING && ! paused ? omegaAuto() : 0 );
	// Seek được khi vòng xoay (có bản sao) hoặc hàng phẳng (điện thoại: hàng trượt tới thẻ); vòng lõm ít thẻ đứng yên: chọn tại chỗ.
	const canSeek = () => isSpinning() || !! ( geo && geo.flat );
	const period = () => ( isSpinning() ? cards.length * geo.slot : 0 );
	const selectedI = () => ( state.mode === SEEKING || state.mode === FOCUSED ? state.focus : -1 );

	function geometryFor( w ) {
		const tier = win.innerWidth >= 1200 ? 'desktop' : win.innerWidth >= 768 ? 'tablet' : 'mobile';
		const R = tier === 'desktop' ? cfg.R_DESKTOP : tier === 'tablet' ? cfg.R_TABLET : cfg.R_MOBILE;
		const cardW = Math.round( tier === 'desktop' ? cfg.CARD_W_DESKTOP : tier === 'tablet' ? cfg.CARD_W_TABLET : ( win.innerWidth * cfg.CARD_W_MOBILE_VW ) / 100 );
		return { tier, R, P: R * cfg.P_RATIO, cardW, cardH: Math.round( cardW * cfg.CARD_RATIO ), flat: tier === 'mobile', viewW: w };
	}

	function cloneOf( src ) {
		const c = src.cloneNode( true );
		c.setAttribute( 'aria-hidden', 'true' );
		c.classList.remove( 'is-hover', 'is-hot' );
		c.querySelectorAll( 'a, button' ).forEach( ( a ) => ( a.tabIndex = -1 ) );
		c.querySelectorAll( '[id]' ).forEach( ( e ) => e.removeAttribute( 'id' ) );
		return c;
	}

	function applyStyle() {
		const s = stage.style;
		s.setProperty( '--ring-fade', `${ cfg.EDGE_FADE }%` );
		s.setProperty( '--glint-period', `${ cfg.GLINT_PERIOD_S }s` );
		s.setProperty( '--glint-stagger', `${ -cfg.GLINT_STAGGER_S }s` );
		s.setProperty( '--ring-flip-dur', `${ cfg.FLIP_MS }ms` );
		s.setProperty( '--ring-flip-deg', `${ cfg.FLIP_TURNS * 360 + 180 }deg` );
		s.setProperty( '--ring-hover-scale', String( cfg.HOVER_SCALE ) );
		s.setProperty( '--ring-dim', String( cfg.DIM_HOVER ) );
		s.setProperty( '--ring-focus-scale', String( cfg.FOCUS_SCALE ) );
		s.setProperty( '--ring-dim-focus', String( cfg.DIM_OTHERS ) );
	}

	function build( force = false ) {
		const g = geometryFor( stage.clientWidth );
		const slot = slotDeg( g.cardW, g.R, cfg.GAP );
		const n = ! isSpinning() ? nReal : g.flat ? flatCount( nReal, g.cardW * ( 1 + cfg.GAP ), g.viewW, cfg.MIN_CARDS ) : ringCount( nReal, slot, cfg.CULL_DEG, cfg.MIN_CARDS );
		if ( force || n !== cards.length || ! ring ) {
			dispatch( { type: 'RESET' } );
			cards.slice( nReal ).forEach( ( c ) => c.remove() );
			cards = real.slice();
			for ( let i = nReal; i < n; i++ ) {
				const c = cloneOf( real[ i % nReal ] );
				list.appendChild( c );
				cards.push( c );
			}
			cards.forEach( ( c, i ) => c.style.setProperty( '--i', String( i ) ) );
			hitEls = cards.map( ( c ) => ( {
				front: c.querySelector( '.isha-ring__side--front .isha-ring__hit' ),
				back: c.querySelector( '.isha-ring__side--back .isha-ring__hit' ),
				flare: c.querySelector( '.isha-ring__side--front .isha-ring__flare' ),
				on: false,
			} ) );
			glints = cards.map( ( c ) => ( { els: Array.from( c.querySelectorAll( '.isha-ring__glint' ) ), op: -1, boost: 1, hot: false } ) );
			ring = createRing( { stage, ring: list, cards, cfg } );
		}
		applyStyle();
		ring.setGeometry( g );
		geo = ring.geometry;
		if ( ! isSpinning() ) {
			motion.seeking = null;
			motion.spin = centerSpin( n, slot );
			motion.omega = 0;
		}
		motion.omegaTarget = wantOmega();
		ring.render( motion.spin, true );
		stage.classList.toggle( 'has-reflect', !! cfg.REFLECT && ! geo.flat );
		applySpec( 0 );
		// Phần lõi của khung (bỏ đệm trên / dưới --ring-bleed, card-ring.css): gốc phối cảnh và tâm vòng tính trên phần này.
		const r = stage.getBoundingClientRect();
		const bleed = parseFloat( win.getComputedStyle( stage ).getPropertyValue( '--ring-bleed' ) ) || 0;
		rect = { left: r.left, top: r.top + bleed, width: r.width, height: r.height - 2 * bleed };
	}

	/**
	 * Độ đục lớp ánh kim chạy của từng thẻ đang hiện: SPEC_MAX · cos(θ)^SPEC_POW, nhân hệ số hover (dt = 0: tới ngay). Chỉ ghi opacity
	 * (compositor) và chỉ khi đổi ≥ 0.004.
	 */
	function applySpec( dt ) {
		const k = dt > 0 ? 1 - Math.exp( ( -dt * 3000 ) / Math.max( 1, cfg.SPEC_EASE_MS ) ) : 1;
		const cull = geo.flat ? 90 : cfg.CULL_DEG;
		for ( let i = 0; i < glints.length; i++ ) {
			const g = glints[ i ];
			const th = ring.angle( i );
			if ( ! g.els.length || Math.abs( th ) > cull ) {
				continue;
			}
			g.boost += ( ( g.hot ? cfg.SPEC_HOT : 1 ) - g.boost ) * k;
			const op = Math.min( 1, cardSpec( th, cfg.SPEC_MAX, cfg.SPEC_POW ) * g.boost );
			if ( Math.abs( op - g.op ) >= 0.004 ) {
				g.op = op;
				const v = op.toFixed( 3 );
				for ( const el of g.els ) {
					el.style.opacity = v;
				}
			}
		}
	}

	function setHot( i, on ) {
		const li = cards[ i ];
		if ( ! li ) {
			return;
		}
		li.classList.toggle( 'is-hot', on );
		if ( glints[ i ] ) {
			glints[ i ].hot = on;
		}
		const rate = on ? cfg.GLINT_PERIOD_S / cfg.GLINT_PERIOD_HOVER_S : 1;
		if ( li.getAnimations ) {
			li.getAnimations( { subtree: true } ).forEach( ( a ) => a.updatePlaybackRate && a.updatePlaybackRate( rate ) );
		}
	}

	/** Thẻ thật gần giữa nhất (bàn phím bắt đầu từ đây khi chưa chọn thẻ nào). */
	function centerCard() {
		let best = 0;
		for ( let i = 1; i < cards.length; i++ ) {
			if ( Math.abs( ring.angle( i ) ) < Math.abs( ring.angle( best ) ) ) {
				best = i;
			}
		}
		return best % nReal;
	}

	/** Đưa trạng thái của máy lên DOM (lớp CSS, glint nóng) và ω. */
	function apply( prev ) {
		const hv = state.mode === HOVER_PAUSED ? state.hover : -1;
		const fc = selectedI();
		const pHv = prev.mode === HOVER_PAUSED ? prev.hover : -1;
		const pFc = prev.mode === SEEKING || prev.mode === FOCUSED ? prev.focus : -1;
		if ( pHv !== hv ) {
			cards[ pHv ]?.classList.remove( 'is-hover' );
			cards[ hv ]?.classList.add( 'is-hover' );
		}
		if ( pFc !== fc ) {
			cards[ pFc ]?.classList.remove( 'is-focus' );
			cards[ fc ]?.classList.add( 'is-focus' );
		}
		stage.classList.toggle( 'has-hover', hv >= 0 );
		stage.classList.toggle( 'has-focus', fc >= 0 );
		const hot = fc >= 0 ? fc : hv;
		if ( hot !== hotI ) {
			setHot( hotI, false );
			setHot( hot, true );
			hotI = hot;
		}
		motion.omegaTarget = wantOmega();
	}

	function run( fx ) {
		switch ( fx.type ) {
			case 'seek':
				motion.seek( fx.i * geo.slot, period() );
				break;
			case 'snap':
				motion.seek( fx.spin, period() );
				break;
			case 'open': {
				const a = cards[ fx.i ]?.querySelector( 'a[href]' );
				if ( a ) {
					win.location.assign( a.href );
				}
				break;
			}
			case 'focusEl':
				real[ fx.i ]?.querySelector( 'a[href]' )?.focus( { preventScroll: true } );
				break;
			case 'timer':
				win.clearTimeout( timer );
				timer = win.setTimeout( () => dispatch( { type: 'RESUME' } ), fx.ms );
				break;
			case 'clearTimer':
				win.clearTimeout( timer );
				break;
		}
	}

	function dispatch( e ) {
		const prev = state;
		// Thẻ đang nằm dưới chuột (kể cả khi máy bỏ qua hover lúc SEEKING / FOCUSED): thoát chọn mà chuột vẫn trên thẻ thì vào lại
		// HOVER_PAUSED ngay (trình duyệt không bắn lại pointerover khi con trỏ chưa rời thẻ).
		if ( e.type === 'HOVER' ) {
			mouseOver = e.i;
		} else if ( e.type === 'UNHOVER' && mouseOver === e.i ) {
			mouseOver = -1;
		}
		if ( e.type === 'RESET' ) {
			win.clearTimeout( timer );
			motion.seeking = null;
			mouseOver = -1;
			state = initialState();
		} else {
			const r = reduce( state, e, { nReal, canSeek: canSeek(), center: ring ? centerCard() : 0, resumeMs: cfg.RESUME_AFTER_SWIPE_MS } );
			state = r.s;
			// Thoát khi spring đang chạy (focus-out lúc SEEKING): bỏ spring, ω nội suy từ 0 lên tốc độ tự xoay.
			if ( ( prev.mode === SEEKING || prev.mode === SNAPPING ) && state.mode !== prev.mode && state.mode !== FOCUSED && state.mode !== 'WAIT' && ! r.fx.some( ( x ) => x.type === 'seek' || x.type === 'snap' ) ) {
				motion.seeking = null;
				motion.omega = 0;
			}
			r.fx.forEach( run );
			if ( state.mode === SPINNING && prev.mode !== SPINNING && prev.mode !== HOVER_PAUSED && mouseOver >= 0 ) {
				state = reduce( state, { type: 'HOVER', i: mouseOver } ).s;
			}
		}
		if ( state !== prev && cards.length ) {
			apply( prev );
		}
	}

	/** Lật / dừng như hover chuột (lookdev, test): i < 0 = rời. */
	function setHover( i ) {
		if ( i >= 0 ) {
			dispatch( { type: 'HOVER', i } );
		} else if ( state.mode === HOVER_PAUSED ) {
			dispatch( { type: 'UNHOVER', i: state.hover } );
		}
	}

	// Vuốt: quay vòng theo ngón tay (độ / px theo bề ngang thẻ ở giữa), thả tay giữ quán tính rồi vào thẻ gần nhất.
	function degPerPx() {
		return geo.flat ? geo.slot / geo.pitch : 180 / ( Math.PI * geo.R );
	}
	const drag = {
		can: () => canSeek(),
		begin() {
			dispatch( { type: 'DRAG_START' } );
			motion.seeking = null;
			motion.omega = 0;
			motion.omegaTarget = 0;
			dragSpin0 = motion.spin;
		},
		move( dx ) {
			motion.spin = dragSpin0 - dx * degPerPx();
		},
		end( vx ) {
			const lim = 8 * geo.slot; // tối đa ~8 thẻ / giây
			const v = Math.max( -lim, Math.min( lim, -vx * degPerPx() ) );
			let target = Math.round( ( motion.spin + v * 0.3 ) / geo.slot ) * geo.slot;
			if ( ! isSpinning() ) {
				target = Math.max( 0, Math.min( ( cards.length - 1 ) * geo.slot, target ) );
			}
			motion.omega = v;
			dispatch( { type: 'DRAG_END', spin: target } );
		},
	};

	/**
	 * Điểm sáng ánh kim ở chỗ tia nắng chạm viền thẻ i: toạ độ trên thẻ (px), độ mạnh a, bán kính r; null = tắt. Mặt sau lật 180° nên
	 * đối xứng ngang.
	 */
	function setHit( i, hit ) {
		const h = hitEls[ i ];
		if ( ! h || ! h.front ) {
			return;
		}
		if ( ! hit ) {
			if ( h.on ) {
				h.on = false;
				for ( const el of [ h.front, h.back, h.flare ] ) {
					if ( el ) {
						el.style.opacity = '0';
					}
				}
			}
			return;
		}
		h.on = true;
		const s = ( hit.r / 60 ).toFixed( 3 );
		const op = Math.min( 1, hit.a * cfg.HIT_GAIN ).toFixed( 3 );
		h.front.style.transform = `translate3d(${ hit.x.toFixed( 1 ) }px, ${ hit.y.toFixed( 1 ) }px, 0) scale(${ s })`;
		h.front.style.opacity = op;
		if ( h.back ) {
			h.back.style.transform = `translate3d(${ ( geo.cardW - hit.x ).toFixed( 1 ) }px, ${ hit.y.toFixed( 1 ) }px, 0) scale(${ s })`;
			h.back.style.opacity = op;
		}
		if ( h.flare ) {
			h.flare.style.transform = `translate3d(${ hit.x.toFixed( 1 ) }px, ${ hit.y.toFixed( 1 ) }px, 0) scale(${ s })`;
			h.flare.style.opacity = ( op * 0.85 ).toFixed( 3 );
		}
	}

	const loop = createLoop( {
		observe: stage,
		win,
		onPause: ( p ) => stage.classList.toggle( 'is-paused', p ),
		tick( dt, now ) {
			if ( state.mode !== DRAGGING && motion.step( dt ) ) {
				dispatch( { type: 'ARRIVED' } );
			}
			ring.render( motion.spin );
			applySpec( dt );
			frameFns.forEach( ( fn ) => fn( dt, now ) );
		},
	} );

	// Lớp nhận vuốt phủ phần lõi của khung, sau thẻ (khung pointer-events: none vì phần đệm trùm lên nội dung kề bên).
	const pad = doc.createElement( 'div' );
	pad.className = 'isha-ring__pad';
	pad.setAttribute( 'aria-hidden', 'true' );
	stage.insertBefore( pad, stage.firstChild );

	build( true );
	const ro = new win.ResizeObserver( () => build() );
	ro.observe( stage );
	const input = bindInput( {
		stage,
		pad,
		indexOf: ( li ) => cards.indexOf( li ),
		dispatch,
		hasSelection: () => selectedI() >= 0,
		selected: selectedI,
		drag,
		cfg,
		win,
		doc,
	} );
	loop.start();

	return {
		get motion() {
			return motion;
		},
		get geometry() {
			return geo;
		},
		get cards() {
			return cards;
		},
		get count() {
			return cards.length;
		},
		get spinning() {
			return isSpinning();
		},
		/** Khung của vòng (px, theo viewport lúc đo gần nhất). */
		get rect() {
			return rect;
		},
		/**
		 * Tứ giác trên màn hình của các thẻ đang hiện, theo hệ toạ độ có gốc (ox, oy) (ví dụ góc trên trái canvas của cảnh).
		 *
		 * @returns {{i:number, q:object[]}[]}
		 */
		quads( ox = 0, oy = 0 ) {
			const C = { x: rect.left - ox + rect.width / 2, y: rect.top - oy + rect.height / 2 };
			const O = { x: C.x, y: rect.top - oy + ( rect.height * cfg.ORIGIN_Y ) / 100 };
			const out = [];
			for ( let i = 0; i < cards.length; i++ ) {
				const th = ring.angle( i );
				if ( Math.abs( th ) > ( geo.flat ? 90 : cfg.CULL_DEG ) ) {
					continue;
				}
				const q = cardQuad( th, geo, C, O, quadBuf[ i ] || ( quadBuf[ i ] = [ {}, {}, {}, {} ] ) );
				// Ngoài khung của vòng (đã bị mask che) thì bỏ.
				if ( Math.max( q[ 1 ].x, q[ 2 ].x ) < rect.left - ox || Math.min( q[ 0 ].x, q[ 3 ].x ) > rect.left - ox + rect.width ) {
					continue;
				}
				out.push( { i, q } );
			}
			return out;
		},
		setHit,
		setHover,
		/** Trạng thái hiện tại của máy (chỉ đọc). */
		get state() {
			return state;
		},
		/** Gửi sự kiện cho máy trạng thái (lookdev, test). */
		dispatch,
		/** Chọn thẻ i: đưa vào giữa (spring, cung ngắn nhất) rồi FOCUSED, như click. */
		seek( i ) {
			dispatch( { type: 'ACTIVATE', i } );
		},
		/** Tạm dừng / phát xoay tự động (nút tạm dừng, bước 6). */
		setPaused( p ) {
			paused = p;
			motion.omegaTarget = wantOmega();
		},
		/** Thoát thẻ đang chọn, xoay lại. */
		resume() {
			dispatch( { type: 'FOCUS_OUT' } );
			motion.omegaTarget = wantOmega();
		},
		rebuild: () => build( true ),
		applyStyle,
		/** Gọi fn( dt, now ) mỗi khung của vòng (sau khi đã vẽ thẻ). */
		onFrame( fn ) {
			frameFns.add( fn );
			return () => frameFns.delete( fn );
		},
		loop,
		destroy() {
			loop.destroy();
			ro.disconnect();
			input.destroy();
			win.clearTimeout( timer );
			pad.remove();
			cards.slice( nReal ).forEach( ( c ) => c.remove() );
		},
	};
}
