// Kiểm tra ui/card-view.js với DOM và GSAP giả lập (đặc tả v3 mục 15: "ở mọi progress, đúng một card có display khác none").
// Chạy: node --test tests/ (từ thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createCardView } from '../assets/js/services-cinematic/ui/card-view.js';
import { cardReducer, visibleIndex, INITIAL } from '../assets/js/services-cinematic/ui/card-machine.js';
import { activeIndex } from '../assets/js/services-cinematic/stops.js';
import { CONFIG } from '../assets/js/services-cinematic/config.js';

// --- DOM giả: <li> chứa <article> chứa <img> -----------------------------------------------------------------------------------------
function classList() {
	const set = new Set();
	return {
		add: ( c ) => set.add( c ),
		remove: ( c ) => set.delete( c ),
		toggle: ( c, on ) => ( on ? set.add( c ) : set.delete( c ) ),
		contains: ( c ) => set.has( c ),
	};
}

function makeCards( n ) {
	return Array.from( { length: n }, () => {
		const attrs = new Map();
		const li = {
			style: { display: '' },
			classList: classList(),
			setAttribute: ( k, v ) => attrs.set( k, v ),
			removeAttribute: ( k ) => attrs.delete( k ),
			getAttribute: ( k ) => ( attrs.has( k ) ? attrs.get( k ) : null ),
		};
		const img = { loading: 'lazy' };
		const card = {
			props: { opacity: 1, rotationY: 0, y: 0, scale: 1 },
			maxOpacity: 0,
			inert: false,
			offsetWidth: 420,
			classList: classList(),
			closest: ( sel ) => ( sel === 'li' ? li : null ),
			querySelector: ( sel ) => ( sel === 'img' ? img : null ),
			li,
			img,
		};
		return card;
	} );
}

// --- GSAP giả: set, timeline().to(target, vars, at), kill; thời gian tiến bằng advance(giây). Giá trị đầu của tween lấy lúc tween bắt đầu
// (như GSAP thật), nên "đảo chiều từ trạng thái hiện tại" được kiểm tra đúng nghĩa. ----------------------------------------------------
function makeGsap() {
	const live = new Set();
	const apply = ( target, vars ) => {
		for ( const [ k, v ] of Object.entries( vars ) ) {
			const key = k === 'autoAlpha' ? 'opacity' : k;
			if ( key in target.props ) {
				target.props[ key ] = v;
			}
		}
		target.maxOpacity = Math.max( target.maxOpacity, target.props.opacity );
	};
	const gsap = {
		set( target, vars ) {
			if ( vars.clearProps ) {
				return;
			}
			apply( target, vars );
		},
		timeline( { onComplete } = {} ) {
			const tl = {
				time: 0,
				tweens: [],
				to( target, vars, at = 0 ) {
					tl.tweens.push( { target, vars, at, started: false, from: null } );
					return tl;
				},
				kill() {
					live.delete( tl );
				},
				step( dt ) {
					tl.time += dt;
					let done = true;
					for ( const tw of tl.tweens ) {
						if ( tl.time < tw.at ) {
							done = false;
							continue;
						}
						if ( ! tw.started ) {
							tw.started = true;
							tw.from = { ...tw.target.props };
							if ( tw.vars.onStart ) {
								tw.vars.onStart();
							}
						}
						const k = Math.min( 1, ( tl.time - tw.at ) / tw.vars.duration );
						const now = {};
						for ( const key of [ 'rotationY', 'y', 'scale', 'autoAlpha' ] ) {
							if ( key in tw.vars ) {
								const p = key === 'autoAlpha' ? 'opacity' : key;
								now[ p ] = tw.from[ p ] + ( tw.vars[ key ] - tw.from[ p ] ) * k;
							}
						}
						apply( tw.target, now );
						if ( k < 1 ) {
							done = false;
						}
					}
					if ( done ) {
						live.delete( tl );
						if ( onComplete ) {
							onComplete();
						}
					}
				},
			};
			live.add( tl );
			return tl;
		},
		advance( seconds, frame = 1 / 60 ) {
			for ( let t = 0; t < seconds - 1e-9; t += frame ) {
				for ( const tl of [ ...live ] ) {
					tl.step( frame );
				}
			}
		},
		settle() {
			gsap.advance( 5 );
		},
		get running() {
			return live.size;
		},
	};
	return gsap;
}

const displayed = ( cards ) => cards.filter( ( c ) => c.li.style.display !== 'none' );
const visible = ( cards ) => displayed( cards ).filter( ( c ) => c.props.opacity > 0.001 );

function setup( n = 4, { reduced = false } = {} ) {
	const cards = makeCards( n );
	const gsap = makeGsap();
	const view = createCardView( { cards, gsap, reduced } );
	let state = INITIAL;
	const dispatch = ( ev ) => {
		state = cardReducer( state, ev );
		view.render( state.index, visibleIndex( state ) >= 0 );
	};
	return { cards, gsap, view, dispatch, get state() {
		return state;
	} };
}

function assertExactlyOne( cards, index, msg ) {
	const d = displayed( cards );
	assert.equal( d.length, 1, `${ msg }: đúng một card display khác none (đang có ${ d.length })` );
	assert.equal( cards.indexOf( d[ 0 ] ), index, `${ msg }: card hiển thị là card ${ index }` );
	assert.equal( d[ 0 ].props.opacity, 1, `${ msg }: card hiển thị có opacity 1` );
	assert.equal( d[ 0 ].inert, false );
	assert.equal( d[ 0 ].li.getAttribute( 'aria-hidden' ), null );
	cards.filter( ( c ) => c !== d[ 0 ] ).forEach( ( c ) => {
		assert.equal( c.inert, true, `${ msg }: card ẩn mang inert` );
		assert.equal( c.li.getAttribute( 'aria-hidden' ), 'true', `${ msg }: card ẩn mang aria-hidden` );
	} );
}

test( 'trước khi vào section không card nào hiển thị', () => {
	const { cards, dispatch, gsap } = setup();
	dispatch( { type: 'INDEX_CHANGED', index: 0 } );
	gsap.settle();
	assert.equal( displayed( cards ).length, 0 );
} );

test( 'cuộn chậm qua mọi điểm dừng: sau khi yên, đúng một card (card đang xem)', () => {
	const N = 5;
	const { cards, dispatch, gsap } = setup( N );
	dispatch( { type: 'ENTER_SECTION', index: 0 } );
	gsap.settle();
	assertExactlyOne( cards, 0, 'vào section' );
	for ( let i = 1; i < N; i++ ) {
		dispatch( { type: 'INDEX_CHANGED', index: i } );
		gsap.settle();
		assertExactlyOne( cards, i, `điểm dừng ${ i }` );
	}
} );

test( 'ở mọi progress (tiến rồi lùi, nhịp khung ngẫu nhiên): đang chạy không quá hai card nhìn thấy; yên thì đúng một', () => {
	const N = 6;
	const { cards, dispatch, gsap } = setup( N );
	let seed = 7;
	const rand = () => ( ( seed = ( seed * 16807 ) % 2147483647 ) / 2147483647 );
	dispatch( { type: 'ENTER_SECTION', index: 0 } );
	let idx = 0;
	const ps = [];
	for ( let p = 0; p <= 1.0001; p += 0.004 ) {
		ps.push( p );
	}
	for ( const p of [ ...ps, ...ps.slice().reverse() ] ) {
		idx = activeIndex( Math.min( 1, p ), N, idx );
		dispatch( { type: 'INDEX_CHANGED', index: idx } );
		gsap.advance( rand() * 0.05 );
		assert.ok( visible( cards ).length <= 2, `p = ${ p.toFixed( 3 ) }: ${ visible( cards ).length } card nhìn thấy cùng lúc` );
		if ( rand() < 0.15 ) {
			gsap.settle();
			assertExactlyOne( cards, idx, `p = ${ p.toFixed( 3 ) }` );
		}
	}
	gsap.settle();
	assertExactlyOne( cards, 0, 'cuối cùng về đầu' );
} );

test( 'cuộn nhanh 0 → 3: bỏ qua card trung gian (không lúc nào nhìn thấy), chỉ card 3 còn lại', () => {
	const { cards, dispatch, gsap } = setup( 4 );
	dispatch( { type: 'ENTER_SECTION', index: 0 } );
	gsap.settle();
	cards.forEach( ( c ) => {
		c.maxOpacity = c.props.opacity;
	} );
	for ( const i of [ 1, 2, 3 ] ) {
		dispatch( { type: 'INDEX_CHANGED', index: i } );
		gsap.advance( 1 / 60 );
	}
	gsap.settle();
	assertExactlyOne( cards, 3, 'sau cuộn nhanh' );
	assert.equal( cards[ 1 ].maxOpacity, 0, 'card 1 không lúc nào hiện' );
	assert.equal( cards[ 2 ].maxOpacity, 0, 'card 2 không lúc nào hiện' );
	assert.equal( cards[ 1 ].li.style.display, 'none' );
	assert.equal( cards[ 2 ].li.style.display, 'none' );
} );

test( 'đảo chiều giữa chừng: card đang vào quay ra, card cũ quay vào TỪ trạng thái hiện tại (không nhảy)', () => {
	const { cards, dispatch, gsap } = setup( 3 );
	dispatch( { type: 'ENTER_SECTION', index: 0 } );
	gsap.settle();
	dispatch( { type: 'INDEX_CHANGED', index: 1 } );
	gsap.advance( 0.3 );
	const mid0 = { ...cards[ 0 ].props };
	const mid1 = { ...cards[ 1 ].props };
	assert.ok( mid0.opacity > 0 && mid0.opacity < 1, 'card 0 đang ra dở' );
	assert.ok( mid1.opacity > 0 && mid1.opacity < 1, 'card 1 đang vào dở' );
	dispatch( { type: 'INDEX_CHANGED', index: 0 } );
	gsap.advance( 1 / 60 );
	// Một khung sau khi đảo chiều: giá trị chỉ dịch một bước nhỏ so với lúc đảo (không reset về góc / opacity đầu).
	assert.ok( Math.abs( cards[ 0 ].props.opacity - mid0.opacity ) < 0.15, 'card 0 không nhảy opacity' );
	assert.ok( Math.abs( cards[ 0 ].props.rotationY - mid0.rotationY ) < 10, 'card 0 không nhảy góc' );
	assert.ok( Math.abs( cards[ 1 ].props.opacity - mid1.opacity ) < 0.15, 'card 1 không nhảy opacity' );
	gsap.settle();
	assertExactlyOne( cards, 0, 'sau khi đảo chiều' );
} );

test( 'hướng lật theo chiều cuộn: tới thì card mới vào từ −góc, lùi thì từ +góc', () => {
	const { cards, dispatch, gsap } = setup( 3 );
	dispatch( { type: 'ENTER_SECTION', index: 1 } );
	gsap.settle();
	dispatch( { type: 'INDEX_CHANGED', index: 2 } );
	assert.equal( cards[ 2 ].props.rotationY, -CONFIG.card.angle );
	gsap.settle();
	dispatch( { type: 'INDEX_CHANGED', index: 0 } );
	assert.equal( cards[ 0 ].props.rotationY, CONFIG.card.angle );
	gsap.settle();
	assertExactlyOne( cards, 0, 'lùi về 0' );
} );

test( 'dismiss: card ra rồi display none; reopen: hiện lại đúng một card', () => {
	const { cards, dispatch, gsap } = setup( 3 );
	dispatch( { type: 'ENTER_SECTION', index: 1 } );
	gsap.settle();
	dispatch( { type: 'DISMISS' } );
	gsap.settle();
	assert.equal( displayed( cards ).length, 0, 'dismiss: không card nào trong luồng hiển thị' );
	dispatch( { type: 'REOPEN' } );
	gsap.settle();
	assertExactlyOne( cards, 1, 'reopen' );
	dispatch( { type: 'LEAVE_SECTION' } );
	gsap.settle();
	assert.equal( displayed( cards ).length, 0, 'rời section' );
} );

test( 'giảm chuyển động: đổi ngay, không timeline, luôn đúng một card', () => {
	const { cards, dispatch, gsap } = setup( 4, { reduced: true } );
	dispatch( { type: 'ENTER_SECTION', index: 0 } );
	for ( const i of [ 2, 1, 3, 0 ] ) {
		dispatch( { type: 'INDEX_CHANGED', index: i } );
		assert.equal( gsap.running, 0 );
		assertExactlyOne( cards, i, `giảm chuyển động, card ${ i }` );
	}
} );

test( 'chỉ nạp ảnh của card hiện tại ± 1', () => {
	const { cards, dispatch, gsap } = setup( 6 );
	dispatch( { type: 'ENTER_SECTION', index: 2 } );
	gsap.settle();
	assert.deepEqual( cards.map( ( c ) => c.img.loading ), [ 'lazy', 'eager', 'eager', 'eager', 'lazy', 'lazy' ] );
} );

test( 'vệt sáng viền chỉ chạy khi card lật vào', () => {
	const { cards, dispatch, gsap } = setup( 3 );
	dispatch( { type: 'ENTER_SECTION', index: 0 } );
	gsap.advance( 0.05 );
	assert.ok( cards[ 0 ].classList.contains( 'is-sweep' ) );
	dispatch( { type: 'INDEX_CHANGED', index: 1 } );
	gsap.advance( 0.05 );
	assert.ok( ! cards[ 0 ].classList.contains( 'is-sweep' ) || cards[ 0 ].li.style.display !== 'none' );
	assert.ok( ! cards[ 1 ].classList.contains( 'is-sweep' ), 'chưa tới lúc card 1 vào (trễ stagger)' );
	gsap.advance( CONFIG.card.stagger );
	assert.ok( cards[ 1 ].classList.contains( 'is-sweep' ) );
	gsap.settle();
	assert.ok( ! cards[ 0 ].classList.contains( 'is-sweep' ), 'card đã ẩn không giữ class quét' );
} );
