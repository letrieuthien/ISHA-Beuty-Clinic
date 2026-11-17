/**
 * Vòng cung ánh kim: nối các module (prompt 8.2). Nhận danh sách thẻ THẬT đã có trong DOM (SSR ở bước 7; lookdev dựng bằng JS) và nâng cấp:
 *   - hình học theo bề rộng khung (≥ 1200 desktop, 768 – 1199 tablet: vòng lõm; < 768: hàng phẳng), nhân bản thẻ khi được xoay vòng
 *     (≥ SPIN_MIN_CARDS thẻ thật; bản sao aria-hidden, liên kết tabindex −1, bỏ id), ít hơn thì đứng yên canh giữa;
 *   - một vòng rAF (loop.js) chạy chuyển động (spring.js), chỉ khi trong khung nhìn và tab hiện; .is-paused dừng cả glint CSS;
 *   - hover chuột: lật ra mặt sau, phóng nhẹ, thẻ khác mờ, glint nhanh / sáng hơn, vòng dừng có quán tính;
 *   - điểm sáng ánh kim ở chỗ tia nắng chạm viền (setHit, light.js tính).
 * Không đọc layout trong vòng lặp: kích thước và vị trí khung đo khi đổi kích thước (ResizeObserver).
 */
import { RING } from './config.js';
import { createRing, ringCount, flatCount, slotDeg, spins, centerSpin } from './ring.js';
import { createMotion } from './spring.js';
import { createLoop } from './loop.js';
import { cardQuad } from './light.js';

/**
 * @param {object}      o
 * @param {HTMLElement} o.stage  .isha-ring (khung có perspective, mask hai mép).
 * @param {HTMLElement} o.list   .isha-ring__ring (ul), con là li.isha-ring__card thật.
 * @param {object}      [o.cfg]  RING.
 * @param {boolean}     [o.reduced]
 */
export function createCardRing( { stage, list, cfg = RING, reduced = false, win = window } ) {
	const real = Array.from( list.children );
	const nReal = real.length;
	const motion = createMotion( cfg );
	const frameFns = new Set();
	let cards = real.slice();
	let ring = null;
	let geo = null;
	let hovered = -1;
	let paused = false;
	let hitEls = [];
	let rect = { left: 0, top: 0, width: 0, height: 0 };
	const quadBuf = [];

	const isSpinning = () => spins( nReal, cfg ) && ! reduced;
	const omegaAuto = () => ( geo && geo.flat ? cfg.OMEGA_MOBILE : cfg.OMEGA_DESKTOP );
	const wantOmega = () => ( isSpinning() && hovered < 0 && ! paused ? omegaAuto() : 0 );

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

	function bindHover( li, i ) {
		li.addEventListener( 'pointerenter', ( e ) => e.pointerType === 'mouse' && setHover( i ) );
		li.addEventListener( 'pointerleave', ( e ) => e.pointerType === 'mouse' && hovered === i && setHover( -1 ) );
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
	}

	function build( force = false ) {
		const g = geometryFor( stage.clientWidth );
		const slot = slotDeg( g.cardW, g.R, cfg.GAP );
		const n = ! isSpinning() ? nReal : g.flat ? flatCount( nReal, g.cardW * ( 1 + cfg.GAP ), g.viewW, cfg.MIN_CARDS ) : ringCount( nReal, slot, cfg.CULL_DEG, cfg.MIN_CARDS );
		if ( force || n !== cards.length || ! ring ) {
			setHover( -1 );
			cards.slice( nReal ).forEach( ( c ) => c.remove() );
			cards = real.slice();
			for ( let i = nReal; i < n; i++ ) {
				const c = cloneOf( real[ i % nReal ] );
				list.appendChild( c );
				cards.push( c );
			}
			cards.forEach( ( c, i ) => {
				c.style.setProperty( '--i', String( i ) );
				if ( ! c.dataset.ringBound ) {
					c.dataset.ringBound = '1';
					bindHover( c, i );
				}
			} );
			hitEls = cards.map( ( c ) => ( {
				front: c.querySelector( '.isha-ring__side--front .isha-ring__hit' ),
				back: c.querySelector( '.isha-ring__side--back .isha-ring__hit' ),
				flare: c.querySelector( '.isha-ring__side--front .isha-ring__flare' ),
				on: false,
			} ) );
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
		const r = stage.getBoundingClientRect();
		rect = { left: r.left, top: r.top, width: r.width, height: r.height };
	}

	function setHot( i, on ) {
		const li = cards[ i ];
		if ( ! li ) {
			return;
		}
		li.classList.toggle( 'is-hot', on );
		const rate = on ? cfg.GLINT_PERIOD_S / cfg.GLINT_PERIOD_HOVER_S : 1;
		if ( li.getAnimations ) {
			li.getAnimations( { subtree: true } ).forEach( ( a ) => a.updatePlaybackRate && a.updatePlaybackRate( rate ) );
		}
	}

	function setHover( i ) {
		if ( i === hovered ) {
			return;
		}
		if ( hovered >= 0 && cards[ hovered ] ) {
			cards[ hovered ].classList.remove( 'is-hover' );
			setHot( hovered, false );
		}
		hovered = i;
		stage.classList.toggle( 'has-hover', i >= 0 );
		if ( i >= 0 ) {
			cards[ i ].classList.add( 'is-hover' );
			setHot( i, true );
		}
		motion.omegaTarget = wantOmega();
	}

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
			if ( motion.step( dt ) ) {
				motion.omegaTarget = 0; // tới nơi (bước 5: FOCUSED)
			}
			ring.render( motion.spin );
			frameFns.forEach( ( fn ) => fn( dt, now ) );
		},
	} );

	build( true );
	const ro = new win.ResizeObserver( () => build() );
	ro.observe( stage );
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
		setHot,
		/** Đưa thẻ i vào giữa (spring, cung ngắn nhất). */
		seek( i ) {
			motion.seek( i * geo.slot, cards.length * geo.slot );
		},
		/** Tạm dừng / phát xoay tự động (nút tạm dừng, bước 6). */
		setPaused( p ) {
			paused = p;
			motion.omegaTarget = wantOmega();
		},
		resume() {
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
			cards.slice( nReal ).forEach( ( c ) => c.remove() );
		},
	};
}
