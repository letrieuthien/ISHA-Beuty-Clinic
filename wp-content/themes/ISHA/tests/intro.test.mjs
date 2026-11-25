// Màn chờ "Bình minh" (assets/js/services-cinematic/intro.js). Chạy: node --test "tests/*.test.mjs" (thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { bindIntro, introPlayFor } from '../assets/js/services-cinematic/intro.js';

const CFG = { rise: 1.8, nearAt: 1.0, nearFor: 1.2, farAt: 1.2, farFor: 1.0, hold: 0, out: 0.6, readyWait: 6 };
const PLAY_MS = 2200; // max(1.8, 2.2, 2.2) + 0 = 2.2 giây (tia nở ngay khi mặt trời còn đang mọc)

// Môi trường giả: đồng hồ thủ công, IntersectionObserver, document, section.
function env( { urls = [ 'a.webp' ], decode = () => Promise.resolve() } = {} ) {
	let now = 0;
	let seq = 0;
	const timers = new Map();
	let ioCb = null;
	const docL = {};
	const secL = {};
	const win = {
		setTimeout( fn, ms ) {
			const id = ++seq;
			timers.set( id, { at: now + ms, fn } );
			return id;
		},
		clearTimeout( id ) {
			timers.delete( id );
		},
		Image: class {
			decode() {
				return decode();
			}
		},
		IntersectionObserver: class {
			constructor( cb ) {
				ioCb = cb;
			}
			observe() {}
			disconnect() {
				ioCb = null;
			}
		},
	};
	const doc = {
		hidden: false,
		addEventListener( t, fn ) {
			( docL[ t ] = docL[ t ] || [] ).push( fn );
		},
		removeEventListener( t, fn ) {
			docL[ t ] = ( docL[ t ] || [] ).filter( ( f ) => f !== fn );
		},
	};
	const props = {};
	const section = {
		dataset: {},
		offsetWidth: 0,
		style: {
			setProperty: ( k, v ) => ( props[ k ] = v ),
			removeProperty: ( k ) => delete props[ k ],
		},
		addEventListener( t, fn ) {
			( secL[ t ] = secL[ t ] || [] ).push( fn );
		},
		removeEventListener( t, fn ) {
			secL[ t ] = ( secL[ t ] || [] ).filter( ( f ) => f !== fn );
		},
	};
	return {
		win,
		doc,
		section,
		props,
		urls,
		io: ( ratio ) => ioCb && ioCb( [ { isIntersecting: ratio > 0, intersectionRatio: ratio } ] ),
		down: () => ( secL.pointerdown || [] ).forEach( ( f ) => f( {} ) ),
		key: ( key ) => ( docL.keydown || [] ).forEach( ( f ) => f( { key } ) ),
		vis: ( hidden ) => {
			doc.hidden = hidden;
			( docL.visibilitychange || [] ).forEach( ( f ) => f() );
		},
		// Tiến đồng hồ: chạy các hẹn giờ tới hạn theo thứ tự, rồi để các promise đang chờ chạy.
		async advance( ms ) {
			const end = now + ms;
			for ( ;; ) {
				const due = [ ...timers.entries() ].filter( ( [ , t ] ) => t.at <= end ).sort( ( a, b ) => a[ 1 ].at - b[ 1 ].at )[ 0 ];
				if ( ! due ) {
					break;
				}
				now = due[ 1 ].at;
				timers.delete( due[ 0 ] );
				due[ 1 ].fn();
				await tick();
			}
			now = end;
			await tick();
		},
		pending: () => timers.size,
	};
}
const tick = () => new Promise( ( r ) => setImmediate( r ) );

function make( e, ready, o = {} ) {
	return bindIntro( { section: e.section, cfg: CFG, cover: 0.95, urls: e.urls, ready, win: e.win, doc: e.doc, ...o } );
}

test( 'introPlayFor: hoạt ảnh dài nhất cộng thời gian nghỉ', () => {
	assert.ok( Math.abs( introPlayFor( CFG ) - 2.2 ) < 1e-9 );
	assert.equal( introPlayFor( { rise: 5, nearAt: 0, nearFor: 1, farAt: 0, farFor: 1, hold: 0 } ), 5 );
} );

test( 'khởi tạo: data-intro = wait, thời gian của cfg thành biến CSS, chưa chạy', async () => {
	const e = env();
	const intro = make( e );
	await tick();
	assert.equal( e.section.dataset.intro, 'wait' );
	assert.equal( intro.phase, 'wait' );
	assert.equal( intro.revealed, false );
	assert.equal( e.props[ '--isha-intro-rise' ], '1.8s' );
	assert.equal( e.props[ '--isha-intro-near-at' ], '1s' );
	assert.equal( e.props[ '--isha-intro-far-for' ], '1s' );
	assert.equal( e.props[ '--isha-intro-out' ], '0.6s' );
} );

test( 'chưa phủ kín (< cover) thì chưa chạy; phủ ≥ cover và ảnh đã tải thì chạy', async () => {
	const e = env();
	const intro = make( e );
	await tick();
	e.io( 0.5 );
	e.io( 0.94 );
	assert.equal( intro.phase, 'wait' );
	e.io( 0.96 );
	assert.equal( intro.phase, 'play' );
	assert.equal( e.section.dataset.intro, 'play' );
} );

test( 'phủ kín trước khi ảnh tải xong thì đợi ảnh; ảnh lỗi hoặc quá hạn vẫn chạy', async () => {
	let finish;
	const e = env( { decode: () => new Promise( ( r ) => ( finish = r ) ) } );
	const intro = make( e );
	e.io( 1 );
	await tick();
	assert.equal( intro.phase, 'wait' );
	finish();
	await tick();
	assert.equal( intro.phase, 'play' );

	// Ảnh không bao giờ xong: quá 2.5 giây thì chạy.
	const e2 = env( { decode: () => new Promise( () => {} ) } );
	const intro2 = make( e2 );
	e2.io( 1 );
	await e2.advance( 2400 );
	assert.equal( intro2.phase, 'wait' );
	await e2.advance( 200 );
	assert.equal( intro2.phase, 'play' );

	// Ảnh lỗi (decode từ chối): vẫn chạy.
	const e3 = env( { decode: () => Promise.reject( new Error( 'lỗi ảnh' ) ) } );
	const intro3 = make( e3 );
	e3.io( 1 );
	await tick();
	assert.equal( intro3.phase, 'play' );
} );

test( 'hết lượt chạy và cảnh đã sẵn sàng: mờ dần (revealed), rồi gỡ (done)', async () => {
	const e = env();
	const intro = make( e, Promise.resolve() );
	await tick();
	e.io( 1 );
	await e.advance( PLAY_MS - 50 );
	assert.equal( intro.phase, 'play' );
	assert.equal( intro.revealed, false );
	await e.advance( 100 );
	assert.equal( intro.phase, 'out' );
	assert.equal( intro.revealed, true );
	assert.equal( e.section.dataset.intro, 'out' );
	await intro.whenRevealed;
	await e.advance( 600 );
	assert.equal( intro.phase, 'out' ); // còn 120 ms đệm cho transition
	await e.advance( 200 );
	assert.equal( intro.phase, 'done' );
	assert.equal( e.section.dataset.intro, undefined );
	await intro.done;
} );

test( 'cảnh chưa sẵn sàng sau lượt toả nắng: chờ tới khi sẵn sàng thì mới mờ dần', async () => {
	const e = env();
	let ready;
	const intro = make( e, new Promise( ( r ) => ( ready = r ) ) );
	await tick();
	e.io( 1 );
	await e.advance( PLAY_MS + 3000 );
	assert.equal( intro.phase, 'play' ); // vẫn che
	ready();
	await tick();
	assert.equal( intro.phase, 'out' );
} );

test( 'cảnh không bao giờ sẵn sàng: quá readyWait thì mờ dần dù sao', async () => {
	const e = env();
	const intro = make( e, new Promise( () => {} ) );
	await tick();
	e.io( 1 );
	await e.advance( PLAY_MS + 5900 );
	assert.equal( intro.phase, 'play' );
	await e.advance( 200 );
	assert.equal( intro.phase, 'out' );
} );

test( 'bấm / chạm khi đang chạy: bỏ qua, mờ dần nhanh bằng nửa thời gian', async () => {
	const e = env();
	const intro = make( e, Promise.resolve() );
	await tick();
	e.io( 1 );
	await e.advance( 500 );
	e.down();
	assert.equal( intro.phase, 'out' );
	assert.equal( e.props[ '--isha-intro-out' ], '0.3s' );
	await e.advance( 600 );
	assert.equal( intro.phase, 'done' );
} );

test( 'chưa chạy (wait) thì bấm / Esc không làm gì', async () => {
	const e = env();
	const intro = make( e, Promise.resolve() );
	await tick();
	e.down();
	e.key( 'Escape' );
	assert.equal( intro.phase, 'wait' );
	assert.equal( e.section.dataset.intro, 'wait' );
} );

test( 'Esc (ở document) khi đang chạy: bỏ qua; phím khác thì không', async () => {
	const e = env();
	const intro = make( e, Promise.resolve() );
	await tick();
	e.io( 1 );
	await e.advance( 300 );
	e.key( 'Enter' );
	e.key( 'a' );
	assert.equal( intro.phase, 'play' );
	e.key( 'Escape' );
	assert.equal( intro.phase, 'out' );
} );

test( 'khuất hẳn giữa chừng: kết thúc ngay không hoạt ảnh; quay lại không chạy lại', async () => {
	const e = env();
	const intro = make( e, Promise.resolve() );
	await tick();
	e.io( 1 );
	await e.advance( 800 );
	e.io( 0 );
	assert.equal( intro.phase, 'out' );
	assert.equal( e.props[ '--isha-intro-out' ], '0s' );
	assert.equal( intro.revealed, true );
	await e.advance( 200 );
	assert.equal( intro.phase, 'done' );
	e.io( 1 );
	assert.equal( intro.phase, 'done' );
	assert.equal( e.section.dataset.intro, undefined );
} );

test( 'rời một phần (vẫn còn hiện) giữa chừng không kết thúc', async () => {
	const e = env();
	const intro = make( e, Promise.resolve() );
	await tick();
	e.io( 1 );
	await e.advance( 800 );
	e.io( 0.4 );
	assert.equal( intro.phase, 'play' );
} );

test( 'chưa tới ngưỡng rồi khuất (không từng chạy): vẫn chờ, không mờ dần', async () => {
	const e = env();
	const intro = make( e, Promise.resolve() );
	await tick();
	e.io( 0.5 );
	e.io( 0 );
	assert.equal( intro.phase, 'wait' );
} );

test( 'tab ẩn lúc tới ngưỡng: chờ tab hiện lại mới chạy', async () => {
	const e = env();
	const intro = make( e, Promise.resolve() );
	await tick();
	e.vis( true );
	e.io( 1 );
	assert.equal( intro.phase, 'wait' );
	e.vis( false );
	assert.equal( intro.phase, 'play' );
} );

test( 'destroy: gỡ data-intro và biến CSS, không còn hẹn giờ, không mờ dần nữa', async () => {
	const e = env();
	const intro = make( e, Promise.resolve() );
	await tick();
	e.io( 1 );
	await e.advance( 500 );
	assert.equal( intro.revealed, false );
	intro.destroy();
	assert.equal( intro.revealed, true, 'đã huỷ thì không chặn cảnh chính / bình minh tự chạy nữa' );
	assert.equal( e.section.dataset.intro, undefined );
	assert.equal( e.props[ '--isha-intro-rise' ], undefined );
	assert.equal( e.pending(), 0 );
	await e.advance( 10000 );
	assert.notEqual( intro.phase, 'out' );
	await intro.done;
	await intro.whenRevealed;
} );

test( 'replay (debug): về wait rồi chạy lại ngay, kể cả sau khi đã done', async () => {
	const e = env();
	const intro = make( e, Promise.resolve() );
	await tick();
	e.io( 1 );
	await e.advance( PLAY_MS + 100 + 1100 );
	assert.equal( intro.phase, 'done' );
	intro.replay();
	assert.equal( intro.phase, 'play' );
	assert.equal( e.section.dataset.intro, 'play' );
	assert.equal( e.props[ '--isha-intro-out' ], '0.6s' );
	await e.advance( PLAY_MS + 100 );
	assert.equal( intro.phase, 'out' );
} );
