// Kiểm tra ui/card-view.js (dãy card trượt ngang ở chân núi) với DOM và GSAP giả lập. Chạy: node --test "tests/*.test.mjs" (thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createCardView } from '../assets/js/services-cinematic/ui/card-view.js';
import { CONFIG } from '../assets/js/services-cinematic/config.js';

// --- DOM giả tối thiểu ----------------------------------------------------------------------------------------------------------------
class El {
	constructor( tag, cls = '', parent = null ) {
		this.tag = tag;
		this.classes = new Set( cls.split( ' ' ).filter( Boolean ) );
		this.parentNode = parent;
		this.children = [];
		this.attrs = new Map();
		this.listeners = new Map();
		this.hidden = false;
		this.props = { opacity: 1, visibility: 'visible', y: 0 };
		if ( parent ) {
			parent.children.push( this );
		}
		const self = this;
		this.classList = {
			add: ( ...c ) => c.forEach( ( x ) => self.classes.add( x ) ),
			remove: ( ...c ) => c.forEach( ( x ) => self.classes.delete( x ) ),
			toggle: ( c, on = ! self.classes.has( c ) ) => ( on ? self.classes.add( c ) : self.classes.delete( c ), on ),
			contains: ( c ) => self.classes.has( c ),
		};
	}
	set className( v ) {
		this.classes = new Set( v.split( ' ' ).filter( Boolean ) );
	}
	matches( sel ) {
		return sel.split( ',' ).map( ( s ) => s.trim() ).some( ( s ) => ( s.startsWith( '.' ) ? this.classes.has( s.slice( 1 ) ) : this.tag === s ) );
	}
	closest( sel ) {
		for ( let n = this; n; n = n.parentNode ) {
			if ( n.matches && n.matches( sel ) ) {
				return n;
			}
		}
		return null;
	}
	querySelector( sel ) {
		for ( const c of this.children ) {
			if ( c.matches( sel ) ) {
				return c;
			}
			const d = c.querySelector( sel );
			if ( d ) {
				return d;
			}
		}
		return null;
	}
	contains( n ) {
		for ( ; n; n = n.parentNode ) {
			if ( n === this ) {
				return true;
			}
		}
		return false;
	}
	setAttribute( k, v ) {
		this.attrs.set( k, String( v ) );
	}
	getAttribute( k ) {
		return this.attrs.has( k ) ? this.attrs.get( k ) : null;
	}
	addEventListener( type, fn ) {
		if ( ! this.listeners.has( type ) ) {
			this.listeners.set( type, new Set() );
		}
		this.listeners.get( type ).add( fn );
	}
	removeEventListener( type, fn ) {
		this.listeners.get( type )?.delete( fn );
	}
	dispatch( type, ev = {} ) {
		const e = { target: this, defaultPrevented: false, preventDefault() {
			e.defaultPrevented = true;
		}, ...ev };
		for ( let n = this; n; n = n.parentNode ) {
			[ ...( n.listeners.get( type ) || [] ) ].forEach( ( fn ) => fn( e ) );
		}
		return e;
	}
	insertBefore( node, ref ) {
		node.parentNode = this;
		const i = ref ? this.children.indexOf( ref ) : -1;
		this.children.splice( i < 0 ? this.children.length : i, 0, node );
	}
	remove() {
		if ( this.parentNode ) {
			this.parentNode.children = this.parentNode.children.filter( ( c ) => c !== this );
			this.parentNode = null;
		}
	}
	focus() {
		doc.activeElement = this;
	}
}

const doc = { activeElement: null, createElement: ( tag ) => new El( tag ) };

function setup( { n = 4, coarse = false, reduced = false, imgs = 'complete', width = 900 } = {} ) {
	const stage = new El( 'div', 'isha-svc__stage' );
	const list = new El( 'ol', 'isha-svc__list', stage );
	list.clientWidth = width;
	list.scrollLeft = 0;
	list.scrolls = [];
	list.scrollBy = ( o ) => list.scrolls.push( o.left );
	Object.defineProperty( list, 'scrollWidth', { get: () => n * 200 + ( n - 1 ) * 16 + 2 * 24 } );
	const cards = [];
	for ( let i = 0; i < n; i++ ) {
		const li = new El( 'li', 'isha-svc__item', list );
		li.offsetWidth = 200;
		const card = new El( 'article', 'isha-card', li );
		const inner = new El( 'div', 'isha-card__inner', card );
		const front = new El( 'div', 'isha-card__face isha-card__front', inner );
		const media = new El( 'div', 'isha-card__media', front );
		const img = new El( 'img', 'isha-card__img', media );
		const state = Array.isArray( imgs ) ? imgs[ i ] : imgs;
		img.complete = state === 'complete';
		img.naturalWidth = state === 'complete' ? 800 : 0;
		const back = new El( 'div', 'isha-card__face isha-card__back', inner );
		card.more = new El( 'a', 'isha-btn isha-card__more', back );
		card.media = media;
		card.img = img;
		cards.push( card );
	}
	const timers = [];
	const winListeners = new El( 'window' );
	const win = {
		document: doc,
		matchMedia: () => ( { matches: coarse } ),
		requestAnimationFrame: ( fn ) => ( fn(), 1 ),
		cancelAnimationFrame: () => {},
		setTimeout: ( fn ) => timers.push( fn ),
		clearTimeout: () => {},
		getComputedStyle: () => ( { columnGap: '16px' } ),
		addEventListener: ( t, f ) => winListeners.addEventListener( t, f ),
		removeEventListener: ( t, f ) => winListeners.removeEventListener( t, f ),
	};
	const gsap = makeGsap();
	const view = createCardView( { cards, gsap, reduced, win } );
	const items = cards.map( ( c ) => c.parentNode );
	const arrows = () => stage.children.filter( ( c ) => c.classes.has( 'isha-svc__arrow' ) );
	return { stage, list, cards, items, gsap, view, arrows, timers };
}

// --- GSAP giả: set / timeline().to( targets, { stagger } ) / kill; advance( giây ) -----------------------------------------------------
function makeGsap() {
	const live = new Set();
	const apply = ( t, vars ) => {
		for ( const [ k, v ] of Object.entries( vars ) ) {
			if ( k === 'autoAlpha' ) {
				t.props.opacity = v;
				t.props.visibility = v === 0 ? 'hidden' : 'visible';
			} else if ( k in t.props ) {
				t.props[ k ] = v;
			}
		}
	};
	const arr = ( x ) => ( Array.isArray( x ) ? x : [ x ] );
	return {
		set( targets, vars ) {
			if ( ! vars.clearProps ) {
				arr( targets ).forEach( ( t ) => apply( t, vars ) );
			}
		},
		timeline( { onComplete } = {} ) {
			const tl = {
				time: 0,
				tweens: [],
				to( targets, vars ) {
					arr( targets ).forEach( ( t, i ) => tl.tweens.push( { t, vars, at: i * ( vars.stagger || 0 ), from: null } ) );
					return tl;
				},
				kill: () => live.delete( tl ),
				step( dt ) {
					tl.time += dt;
					let done = true;
					for ( const tw of tl.tweens ) {
						if ( tl.time < tw.at ) {
							done = false;
							continue;
						}
						tw.from ??= { ...tw.t.props };
						const k = Math.min( 1, ( tl.time - tw.at ) / tw.vars.duration );
						const now = {};
						if ( 'autoAlpha' in tw.vars ) {
							now.autoAlpha = tw.from.opacity + ( tw.vars.autoAlpha - tw.from.opacity ) * k;
							if ( k < 1 && now.autoAlpha === 0 ) {
								now.autoAlpha = 1e-6;
							}
						}
						if ( 'y' in tw.vars ) {
							now.y = tw.from.y + ( tw.vars.y - tw.from.y ) * k;
						}
						apply( tw.t, now );
						done &&= k >= 1;
					}
					if ( done ) {
						live.delete( tl );
						onComplete?.();
					}
				},
			};
			live.add( tl );
			return tl;
		},
		advance( s ) {
			for ( let t = 0; t < s - 1e-9; t += 1 / 60 ) {
				[ ...live ].forEach( ( tl ) => tl.step( 1 / 60 ) );
			}
		},
		get running() {
			return live.size;
		},
	};
}

const settle = ( gsap ) => gsap.advance( 5 );
const visibleItems = ( items ) => items.filter( ( li ) => li.props.visibility !== 'hidden' && li.props.opacity > 0 );

test( 'chưa vào section: cả dãy ẩn (không focus được)', () => {
	const { items } = setup();
	assert.equal( visibleItems( items ).length, 0 );
	items.forEach( ( li ) => assert.equal( li.props.visibility, 'hidden' ) );
} );

test( 'hiện: MỌI card cùng hiện và cùng trạng thái (không có card "đang xem")', () => {
	const { items, cards, view, gsap } = setup( { n: 5 } );
	view.render( 2, true );
	settle( gsap );
	assert.equal( visibleItems( items ).length, 5 );
	items.forEach( ( li ) => assert.deepEqual( li.props, { opacity: 1, visibility: 'visible', y: 0 } ) );
	cards.forEach( ( c ) => assert.equal( c.classes.has( 'is-flipped' ), false ) );
	// Đổi index (cuộn khung cảnh) không đổi gì ở dãy card.
	view.render( 4, true );
	assert.equal( gsap.running, 0 );
	items.forEach( ( li ) => assert.deepEqual( li.props, { opacity: 1, visibility: 'visible', y: 0 } ) );
} );

test( 'hiện lệch pha theo thứ tự, dâng từ dưới lên; khung có một lượt tia sáng (.is-entering)', () => {
	const { items, list, view, gsap, timers } = setup();
	view.render( 0, true );
	assert.ok( list.classes.has( 'is-entering' ) );
	gsap.advance( CONFIG.card.stagger * 1.5 );
	assert.ok( items[ 0 ].props.opacity > items[ 2 ].props.opacity, 'card đầu hiện trước card sau' );
	assert.ok( items[ 0 ].props.y < CONFIG.card.lift );
	settle( gsap );
	timers.forEach( ( fn ) => fn() );
	assert.ok( ! list.classes.has( 'is-entering' ) );
} );

test( 'latest-wins: ẩn giữa lúc đang hiện thì cuối cùng ẩn hết, không card nào còn lại', () => {
	const { items, view, gsap } = setup();
	view.render( 0, true );
	gsap.advance( 0.2 );
	assert.ok( visibleItems( items ).length > 0 );
	view.render( 0, false );
	settle( gsap );
	assert.equal( visibleItems( items ).length, 0 );
	view.render( 1, true );
	settle( gsap );
	assert.equal( visibleItems( items ).length, items.length );
} );

test( 'cảm ứng: chạm để lật, mỗi lần một card; chạm lại để lật về', () => {
	const { cards, view, gsap } = setup( { coarse: true } );
	view.render( 0, true );
	settle( gsap );
	cards[ 1 ].dispatch( 'click' );
	assert.ok( cards[ 1 ].classes.has( 'is-flipped' ) );
	cards[ 2 ].dispatch( 'click' );
	assert.ok( ! cards[ 1 ].classes.has( 'is-flipped' ), 'card trước tự lật về' );
	assert.ok( cards[ 2 ].classes.has( 'is-flipped' ) );
	cards[ 2 ].dispatch( 'click' );
	assert.equal( cards.filter( ( c ) => c.classes.has( 'is-flipped' ) ).length, 0 );
} );

test( 'cảm ứng: chạm nút "Xem chi tiết" khi card chưa lật thì chỉ lật; đã lật thì để liên kết chạy', () => {
	const { cards } = setup( { coarse: true } );
	const e1 = cards[ 0 ].more.dispatch( 'click' );
	assert.ok( e1.defaultPrevented );
	assert.ok( cards[ 0 ].classes.has( 'is-flipped' ) );
	const e2 = cards[ 0 ].more.dispatch( 'click' );
	assert.equal( e2.defaultPrevented, false );
} );

test( 'thiết bị có chuột: bấm không lật (lật bằng rê chuột / focus trong CSS)', () => {
	const { cards } = setup( { coarse: false } );
	const e = cards[ 0 ].dispatch( 'click' );
	assert.equal( e.defaultPrevented, false );
	assert.ok( ! cards[ 0 ].classes.has( 'is-flipped' ) );
} );

test( 'unflipAll báo có card đang lật; ẩn dãy thì lật về hết và trả focus cho stage', () => {
	const { stage, cards, view, gsap } = setup( { coarse: true } );
	view.render( 0, true );
	settle( gsap );
	assert.equal( view.unflipAll(), false );
	cards[ 3 ].dispatch( 'click' );
	assert.equal( view.unflipAll(), true );
	assert.ok( ! cards[ 3 ].classes.has( 'is-flipped' ) );
	cards[ 3 ].dispatch( 'click' );
	cards[ 3 ].more.focus();
	view.render( 0, false );
	assert.ok( ! cards[ 3 ].classes.has( 'is-flipped' ) );
	assert.equal( doc.activeElement, stage );
} );

test( 'ảnh: đã có thì hiện ngay; đang tải thì khung chờ (is-loading) tới khi load; lỗi thì giữ khung chờ tĩnh', () => {
	const { cards } = setup( { n: 3, imgs: [ 'complete', 'pending', 'pending' ] } );
	assert.ok( cards[ 0 ].media.classes.has( 'is-loaded' ) );
	assert.ok( cards[ 1 ].media.classes.has( 'is-loading' ) );
	assert.ok( ! cards[ 1 ].media.classes.has( 'is-loaded' ) );
	cards[ 1 ].img.dispatch( 'load' );
	assert.ok( cards[ 1 ].media.classes.has( 'is-loaded' ) );
	assert.ok( ! cards[ 1 ].media.classes.has( 'is-loading' ) );
	cards[ 2 ].img.dispatch( 'error' );
	assert.ok( ! cards[ 2 ].media.classes.has( 'is-loading' ) );
	assert.ok( ! cards[ 2 ].media.classes.has( 'is-loaded' ) );
} );

test( 'dãy vừa khung: không mũi tên, không mép mờ', () => {
	const { list, arrows, view, gsap } = setup( { n: 4, width: 1400 } );
	view.render( 0, true );
	settle( gsap );
	assert.ok( ! list.classes.has( 'is-overflow' ) );
	arrows().forEach( ( a ) => assert.equal( a.hidden, true ) );
} );

test( 'dãy tràn khung: mũi tên hiện, khoá đúng đầu / cuối, bấm thì cuộn theo nhóm card', () => {
	const { list, arrows, view, gsap } = setup( { n: 6, width: 700 } );
	view.render( 0, true );
	settle( gsap );
	const [ prev, next ] = arrows();
	assert.ok( list.classes.has( 'is-overflow' ) && list.classes.has( 'is-start' ) );
	assert.equal( prev.hidden, false );
	assert.equal( prev.getAttribute( 'aria-disabled' ), 'true' );
	assert.equal( next.getAttribute( 'aria-disabled' ), 'false' );
	next.dispatch( 'click' );
	assert.equal( list.scrolls[ 0 ], 2 * 216, 'khung 700px chứa 3 card: cuộn 2 card' );
	list.scrollLeft = list.scrollWidth - list.clientWidth;
	list.dispatch( 'scroll' );
	assert.ok( list.classes.has( 'is-end' ) );
	assert.equal( next.getAttribute( 'aria-disabled' ), 'true' );
	prev.dispatch( 'click' );
	assert.equal( list.scrolls[ 1 ], -2 * 216 );
	view.render( 0, false );
	assert.equal( prev.hidden, true, 'dãy ẩn thì mũi tên ẩn' );
} );

test( 'giảm chuyển động: hiện / ẩn ngay, không timeline', () => {
	const { items, view, gsap } = setup( { reduced: true } );
	view.render( 0, true );
	assert.equal( gsap.running, 0 );
	assert.equal( visibleItems( items ).length, items.length );
	view.render( 0, false );
	assert.equal( visibleItems( items ).length, 0 );
} );

test( 'destroy: gỡ mũi tên và lắng nghe', () => {
	const { cards, arrows, view } = setup( { coarse: true } );
	view.destroy();
	assert.equal( arrows().length, 0 );
	cards[ 0 ].dispatch( 'click' );
	assert.ok( ! cards[ 0 ].classes.has( 'is-flipped' ) );
} );
