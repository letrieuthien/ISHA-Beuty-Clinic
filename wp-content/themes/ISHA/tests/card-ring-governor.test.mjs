// Governor của vòng thẻ (assets/js/card-ring/governor.js). Chạy: node --test "tests/*.test.mjs" (thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRingGovernor, loadGovStep, saveGovStep, GOV_STEPS, GOV_MAX } from '../assets/js/card-ring/governor.js';

const CFG = { GOVERNOR_FPS: 45, GOVERNOR_WINDOW_MS: 2000, GOVERNOR_WARMUP_MS: 1500, GOVERNOR_STALL_MS: 250, GOVERNOR_MIN_FRAMES: 20 };

/** Chạy fps khung / giây trong ms mili giây từ t0, trả số lần báo hạ bậc và thời điểm cuối. */
function run( gov, fps, ms, t0 = 0 ) {
	const dt = 1000 / fps;
	let t = t0;
	let drops = 0;
	while ( t < t0 + ms ) {
		t += dt;
		if ( gov.sample( dt, t ) ) {
			drops++;
		}
	}
	return { drops, t };
}

test( 'thang bậc: đủ hiệu ứng → tắt phản chiếu → ánh kim chỉ thẻ giữa', () => {
	assert.deepEqual( GOV_STEPS, [ 'FULL', 'NO_REFLECT', 'CENTER_GLINT' ] );
	assert.equal( GOV_MAX, 2 );
} );

test( '60 fps: không bao giờ hạ; đo được FPS của cửa sổ', () => {
	const g = createRingGovernor( CFG );
	g.reset( 0 );
	assert.equal( run( g, 60, 20000 ).drops, 0 );
	assert.ok( Math.abs( g.lastFps - 60 ) < 1 );
} );

test( '30 fps: hạ sau một cửa sổ 2 giây (sau warmup), rồi warmup lại trước lần hạ kế', () => {
	const g = createRingGovernor( CFG );
	g.reset( 0 );
	let r = run( g, 30, 3400 ); // warmup 1.5 s + một cửa sổ 2 s chưa đủ
	assert.equal( r.drops, 0 );
	r = run( g, 30, 300, r.t ); // cửa sổ đủ 2 s
	assert.equal( r.drops, 1 );
	r = run( g, 30, 1400, r.t ); // đang warmup sau khi hạ
	assert.equal( r.drops, 0 );
	r = run( g, 30, 2400, r.t );
	assert.equal( r.drops, 1 );
} );

test( 'đúng ngưỡng 45 fps không hạ; 44 fps thì hạ', () => {
	let g = createRingGovernor( CFG );
	g.reset( 0 );
	assert.equal( run( g, 46, 6000 ).drops, 0 );
	g = createRingGovernor( CFG );
	g.reset( 0 );
	assert.ok( run( g, 44, 6000 ).drops >= 1 );
} );

test( 'khung khựng hẳn (tab nền, GC) bỏ cả cửa sổ, không tính là chậm', () => {
	const g = createRingGovernor( CFG );
	g.reset( 0 );
	let t = 2000;
	let drops = 0;
	for ( let k = 0; k < 20; k++ ) {
		t += 1000; // một khung 1 giây rồi chạy mượt
		drops += g.sample( 1000, t ) ? 1 : 0;
		const r = run( g, 60, 500, t );
		drops += r.drops;
		t = r.t;
	}
	assert.equal( drops, 0 );
} );

test( 'lưu / đọc bậc trong phiên: kẹp trong thang, lỗi storage thì 0', () => {
	const mem = new Map();
	const st = { getItem: ( k ) => ( mem.has( k ) ? mem.get( k ) : null ), setItem: ( k, v ) => mem.set( k, v ) };
	assert.equal( loadGovStep( st ), 0 );
	saveGovStep( st, 2 );
	assert.equal( loadGovStep( st ), 2 );
	mem.set( 'isha-ring-gov', '9' );
	assert.equal( loadGovStep( st ), GOV_MAX );
	const bad = { getItem() { throw new Error( 'blocked' ); }, setItem() { throw new Error( 'blocked' ); } };
	assert.equal( loadGovStep( bad ), 0 );
	assert.doesNotThrow( () => saveGovStep( bad, 1 ) );
} );
