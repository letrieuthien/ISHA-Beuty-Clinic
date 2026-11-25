// Kiểm tra lifecycle.js (vòng đời cảnh WebGL) với IntersectionObserver, document, canvas và cảnh giả. Chạy: node --test "tests/*.test.mjs".
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createLifecycle } from '../assets/js/services-cinematic/lifecycle.js';

class Target {
	constructor() {
		this.l = new Map();
	}
	addEventListener( t, f ) {
		( this.l.get( t ) || this.l.set( t, new Set() ).get( t ) ).add( f );
	}
	removeEventListener( t, f ) {
		this.l.get( t )?.delete( f );
	}
	fire( t, e = {} ) {
		const ev = { defaultPrevented: false, preventDefault() {
			ev.defaultPrevented = true;
		}, ...e };
		[ ...( this.l.get( t ) || [] ) ].forEach( ( f ) => f( ev ) );
		return ev;
	}
}

function setup( { failBuild = false, paused = null } = {} ) {
	const doc = new Target();
	doc.hidden = false;
	const canvas = new Target();
	let ioCb = null;
	let ioOff = false;
	class IO {
		constructor( cb ) {
			ioCb = cb;
		}
		observe() {}
		disconnect() {
			ioOff = true;
		}
	}
	const built = [];
	let fallback = null;
	const events = [];
	const build = async ( fb ) => {
		fallback = fb;
		if ( failBuild ) {
			throw new Error( 'shader' );
		}
		const s = { running: false, destroyed: false, frames: 0, start() {
			this.running = true;
		}, stop() {
			this.running = false;
		}, renderOnce() {
			this.frames++;
		}, destroy() {
			this.destroyed = true;
			this.running = false;
		} };
		built.push( s );
		return s;
	};
	const life = createLifecycle( {
		section: {},
		canvas,
		build,
		onReady: () => events.push( 'ready' ),
		onLost: () => events.push( 'lost' ),
		on2D: ( why ) => events.push( '2D:' + why ),
		paused,
		doc,
		IO,
	} );
	return { life, doc, canvas, built, events, view: ( v ) => ioCb( [ { isIntersecting: v } ] ), get fallback() {
		return fallback;
	}, get ioOff() {
		return ioOff;
	} };
}

test( 'dựng xong: vẽ khung đầu, báo ready; chỉ chạy khi section trong khung nhìn và tab đang hiện', async () => {
	const t = setup();
	const scene = await t.life.ready;
	assert.equal( scene.frames, 1, 'renderOnce trước khi hiện canvas' );
	assert.deepEqual( t.events, [ 'ready' ] );
	assert.equal( scene.running, false, 'chưa vào khung nhìn thì chưa chạy' );
	t.view( true );
	assert.equal( scene.running, true );
	t.doc.hidden = true;
	t.doc.fire( 'visibilitychange' );
	assert.equal( scene.running, false, 'tab ẩn thì dừng' );
	t.doc.hidden = false;
	t.doc.fire( 'visibilitychange' );
	assert.equal( scene.running, true );
	t.view( false );
	assert.equal( scene.running, false, 'ra khỏi khung nhìn thì dừng' );
} );

test( 'paused (màn chờ che kín): vẽ khung đầu nhưng chưa chạy vòng render; refresh() sau khi hết chặn thì chạy', async () => {
	let hold = true;
	const t = setup( { paused: () => hold } );
	const scene = await t.life.ready;
	assert.equal( scene.frames, 1, 'khung đầu vẫn vẽ để mờ dần là thấy cảnh' );
	t.view( true );
	assert.equal( scene.running, false, 'trong khung nhìn nhưng màn chờ còn che: không chạy' );
	t.doc.fire( 'visibilitychange' );
	assert.equal( scene.running, false );
	hold = false;
	t.life.refresh();
	assert.equal( scene.running, true );
	t.view( false );
	assert.equal( scene.running, false, 'ra khỏi khung nhìn vẫn dừng như cũ' );
} );

test( 'mất context lần một: dừng, báo lost (poster che), cho phép khôi phục; khôi phục thì dựng lại cảnh mới', async () => {
	const t = setup();
	await t.life.ready;
	t.view( true );
	const ev = t.canvas.fire( 'webglcontextlost' );
	assert.ok( ev.defaultPrevented, 'preventDefault để trình duyệt khôi phục context' );
	assert.equal( t.built[ 0 ].running, false );
	assert.deepEqual( t.events, [ 'ready', 'lost' ] );
	t.canvas.fire( 'webglcontextrestored' );
	await new Promise( ( r ) => setTimeout( r, 0 ) );
	assert.equal( t.built.length, 2 );
	assert.equal( t.built[ 0 ].destroyed, true, 'cảnh cũ bị huỷ' );
	assert.equal( t.life.scene, t.built[ 1 ] );
	assert.equal( t.built[ 1 ].running, true, 'đang trong khung nhìn: cảnh mới chạy ngay' );
	assert.deepEqual( t.events, [ 'ready', 'lost', 'ready' ] );
} );

test( 'mất context lần hai: chuyển hẳn sang 2D, không dựng lại nữa', async () => {
	const t = setup();
	await t.life.ready;
	t.canvas.fire( 'webglcontextlost' );
	t.canvas.fire( 'webglcontextrestored' );
	await new Promise( ( r ) => setTimeout( r, 0 ) );
	t.canvas.fire( 'webglcontextlost' );
	assert.equal( t.events.at( -1 ), '2D:mất context WebGL lần hai' );
	assert.equal( t.life.scene, null );
	t.canvas.fire( 'webglcontextrestored' );
	await new Promise( ( r ) => setTimeout( r, 0 ) );
	assert.equal( t.built.length, 2, 'không dựng lại sau khi đã xuống 2D' );
} );

test( 'governor gọi fallback: huỷ cảnh, chuyển 2D đúng một lần', async () => {
	const t = setup();
	await t.life.ready;
	t.fallback();
	t.fallback();
	assert.equal( t.built[ 0 ].destroyed, true );
	assert.deepEqual( t.events, [ 'ready', '2D:governor' ] );
} );

test( 'dựng lỗi (WebGL / shader / ảnh): chuyển 2D, ready trả null', async () => {
	const t = setup( { failBuild: true } );
	const warn = console.warn;
	console.warn = () => {};
	const scene = await t.life.ready;
	console.warn = warn;
	assert.equal( scene, null );
	assert.deepEqual( t.events, [ '2D:lỗi khi dựng cảnh' ] );
} );

test( 'destroy: gỡ observer và lắng nghe, huỷ cảnh', async () => {
	const t = setup();
	await t.life.ready;
	t.life.destroy();
	assert.ok( t.ioOff );
	assert.equal( t.built[ 0 ].destroyed, true );
	t.canvas.fire( 'webglcontextrestored' );
	await new Promise( ( r ) => setTimeout( r, 0 ) );
	assert.equal( t.built.length, 1 );
} );
