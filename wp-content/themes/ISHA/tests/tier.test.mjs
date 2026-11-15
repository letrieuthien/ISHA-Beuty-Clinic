// Kiểm tra chọn chế độ và governor (assets/js/services-cinematic/tier.js). Chạy: node --test "tests/*.test.mjs" (từ thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { modeFromGpu, buildLadder, createGovernor } from '../assets/js/services-cinematic/tier.js';
import { CONFIG } from '../assets/js/services-cinematic/config.js';

const P = CONFIG.presets;
const G = { ...CONFIG.governor };
const desk = ( gpu, cores = 8, mem = 8 ) => modeFromGpu( { mobile: false, gpu, cores, mem } ).mode;

test( 'modeFromGpu: desktop theo tier của benchmark', () => {
	assert.equal( desk( { type: 'BENCHMARK', tier: 3 } ), 'FULL_HIGH' );
	assert.equal( desk( { type: 'BENCHMARK', tier: 2 } ), 'FULL' );
	assert.equal( desk( { type: 'BENCHMARK', tier: 1 } ), 'LITE' );
	assert.equal( desk( { type: 'BENCHMARK', tier: 0 } ), '2D' );
	assert.equal( desk( { type: 'BLOCKLISTED', tier: 0 } ), '2D' );
} );

test( 'modeFromGpu: không có số liệu thì KHÔNG coi là máy yếu, dùng heuristics', () => {
	for ( const type of [ 'BENCHMARK_FETCH_FAILED', 'FALLBACK', 'TIMEOUT' ] ) {
		assert.equal( desk( { type, tier: type === 'BENCHMARK_FETCH_FAILED' ? 0 : 1 } ), 'FULL', type );
		assert.equal( desk( { type, tier: 1 }, 4, 8 ), 'LITE', type );
		assert.equal( desk( { type, tier: 1 }, 8, 4 ), 'LITE', type );
	}
	assert.equal( desk( null ), 'FULL' );
	assert.equal( desk( null, 6, 16 ), 'LITE' );
} );

test( 'modeFromGpu: thiết bị di động luôn LITE, trừ tier 0 thật', () => {
	const mob = ( gpu ) => modeFromGpu( { mobile: true, gpu, cores: 8, mem: 8 } ).mode;
	assert.equal( mob( { type: 'BENCHMARK', tier: 3 } ), 'LITE' );
	assert.equal( mob( { type: 'BENCHMARK', tier: 1 } ), 'LITE' );
	assert.equal( mob( { type: 'FALLBACK', tier: 1 } ), 'LITE' );
	assert.equal( mob( null ), 'LITE' );
	assert.equal( mob( { type: 'BENCHMARK', tier: 0 } ), '2D' );
	assert.equal( mob( { type: 'BLOCKLISTED', tier: 0 } ), '2D' );
} );

const DIMS = [ 'dpr', 'grass', 'trees', 'clouds' ];
function assertMonotone( ladder, label ) {
	assert.equal( ladder[ ladder.length - 1 ].mode, '2D', label + ': bậc cuối là 2D' );
	for ( let i = 1; i < ladder.length - 1; i++ ) {
		const a = ladder[ i - 1 ];
		const b = ladder[ i ];
		for ( const d of DIMS ) {
			assert.ok( b[ d ] <= a[ d ], `${ label }: bậc ${ i } không được nâng ${ d }` );
		}
		assert.ok( ! ( b.post && ! a.post ) && ! ( b.bloom && ! a.bloom ), `${ label }: bậc ${ i } không bật lại hậu kỳ` );
		assert.ok( ! ( a.mode === 'LITE' && b.mode !== 'LITE' ), `${ label }: đã LITE thì không quay lại FULL` );
	}
}

test( 'buildLadder: FULL_HIGH trên màn hình DPR 2, theo đúng thứ tự đặc tả', () => {
	const l = buildLadder( 'FULL_HIGH', P, { deviceDpr: 2, hasPost: true } );
	assertMonotone( l, 'FULL_HIGH dpr2' );
	const seq = l.map( ( q ) => ( q.mode === '2D' ? '2D' : `${ q.mode }/${ q.dpr }/${ q.grass }/${ q.clouds }/${ +q.post }${ +q.bloom }` ) );
	assert.deepEqual( seq, [
		'FULL_HIGH/1.75/60000/12/11',
		'FULL_HIGH/1.25/60000/12/11', // DPR
		'FULL_HIGH/1.25/30000/12/11', // mật độ cỏ
		'FULL_HIGH/1.25/30000/12/10', // bloom
		'FULL_HIGH/1.25/30000/12/00', // god rays
		'FULL_HIGH/1.25/30000/8/00', // số mây
		'LITE/1.25/0/4/00', // LITE
		'LITE/1/0/4/00', // DPR 1.0
		'2D',
	] );
	assert.equal( l[ 6 ].trees, P.LITE.trees );
} );

test( 'buildLadder: bỏ bậc không đổi gì (DPR 1, chưa có hậu kỳ)', () => {
	const l = buildLadder( 'FULL_HIGH', P, { deviceDpr: 1, hasPost: false } );
	assertMonotone( l, 'FULL_HIGH dpr1' );
	assert.deepEqual( l.map( ( q ) => q.mode === '2D' ? '2D' : `${ q.mode }/${ q.grass }/${ q.clouds }` ), [
		'FULL_HIGH/60000/12', 'FULL_HIGH/30000/12', 'FULL_HIGH/30000/8', 'LITE/0/4', '2D',
	] );
	const full = buildLadder( 'FULL', P, { deviceDpr: 1.5, hasPost: false } );
	assertMonotone( full, 'FULL dpr1.5' );
	assert.deepEqual( full.map( ( q ) => q.mode === '2D' ? '2D' : `${ q.mode }/${ q.dpr }/${ q.grass }/${ q.clouds }` ), [
		'FULL/1.25/30000/8', 'FULL/1/30000/8', 'FULL/1/15000/8', 'FULL/1/15000/6', 'LITE/1/0/4', '2D',
	] );
} );

test( 'buildLadder: LITE (điện thoại DPR 3) chỉ còn hạ DPR rồi 2D; 2D / STATIC không có thang', () => {
	const l = buildLadder( 'LITE', P, { deviceDpr: 3 } );
	assert.deepEqual( l.map( ( q ) => q.mode === '2D' ? '2D' : `${ q.mode }/${ q.dpr }` ), [ 'LITE/1.25', 'LITE/1', '2D' ] );
	assert.deepEqual( buildLadder( 'LITE', P, { deviceDpr: 1 } ).map( ( q ) => q.mode ), [ 'LITE', '2D' ] );
	assert.equal( buildLadder( '2D', P ).length, 1 );
	assert.equal( buildLadder( 'STATIC', P ).length, 1 );
	assert.ok( Object.isFrozen( l ) && Object.isFrozen( l[ 0 ] ) );
} );

// Chạy governor với khoảng cách khung cố định; trả về thời điểm (ms) của các lần hạ bậc.
function run( gov, intervalMs, durationMs, { toFallback = false, t0 = 0 } = {} ) {
	const hits = [];
	for ( let t = t0 + intervalMs; t <= t0 + durationMs; t += intervalMs ) {
		if ( gov.sample( intervalMs, t, toFallback ) ) {
			hits.push( t );
		}
	}
	return hits;
}

test( 'governor: 60fps ổn định không bao giờ hạ bậc', () => {
	const gov = createGovernor( G );
	gov.reset( 0 );
	assert.deepEqual( run( gov, 16.7, 60000 ), [] );
	assert.ok( gov.lastAvg > 16 && gov.lastAvg < 17.5 );
} );

test( 'governor: 30fps hạ bậc sau hai cửa sổ liên tiếp, rồi chờ settleMs', () => {
	const gov = createGovernor( G );
	gov.reset( 0 ); // warmup 1500ms
	const hits = run( gov, 33.3, 12000 );
	// Cửa sổ 1: từ khoảng 1.5 s, 60 khung x 33.3 ms = 2 s (< windowMs). Cửa sổ 2 kết thúc khoảng 5.5 s → hạ bậc.
	assert.ok( hits.length >= 2, 'hạ nhiều lần khi chậm kéo dài' );
	assert.ok( hits[ 0 ] > 5000 && hits[ 0 ] < 6000, `lần đầu ở ${ hits[ 0 ] }` );
	assert.ok( hits[ 1 ] - hits[ 0 ] >= G.settleMs + 2 * 1900, 'lần sau cần settle + hai cửa sổ mới' );
} );

test( 'governor: một cửa sổ chậm rồi một cửa sổ nhanh thì không hạ (cần hai lần LIÊN TIẾP)', () => {
	const gov = createGovernor( G );
	gov.reset( 0, 0 );
	let t = 0;
	const feed = ( ms, n ) => {
		let hit = false;
		for ( let i = 0; i < n; i++ ) {
			t += ms;
			hit = gov.sample( ms, t ) || hit;
		}
		return hit;
	};
	assert.equal( feed( 40, 60 ), false );
	assert.equal( gov.strikes, 1 );
	assert.equal( feed( 16, 60 ), false );
	assert.equal( gov.strikes, 0 );
	assert.equal( feed( 40, 60 ), false );
	assert.equal( feed( 40, 60 ), true );
} );

test( 'governor: khoảng khựng > stallMs bị bỏ, bỏ qua warm-up, tắt thì không làm gì', () => {
	const gov = createGovernor( G );
	gov.reset( 0 );
	assert.equal( gov.sample( 100, 1000 ), false, 'trong warm-up' );
	assert.equal( gov.lastAvg, 0 );
	gov.reset( 0, 0 );
	for ( let i = 1; i <= 200; i++ ) {
		assert.equal( gov.sample( 5000, i * 5000 ), false, 'khựng không tính' );
	}
	assert.equal( gov.lastAvg, 0 );
	const off = createGovernor( { ...G, enabled: false } );
	off.reset( 0, 0 );
	assert.deepEqual( run( off, 100, 60000 ), [] );
} );

test( 'governor: thiết bị rất chậm (3 fps) vẫn hạ được nhờ cửa sổ theo thời gian', () => {
	const gov = createGovernor( G );
	gov.reset( 0 );
	const hits = run( gov, 333, 30000 );
	assert.ok( hits.length >= 3, `hạ ${ hits.length } lần trong 30 giây` );
	assert.ok( hits[ 0 ] < 8000, `lần đầu ở ${ hits[ 0 ] }` );
} );

test( 'governor: bậc kế là 2D dùng ngưỡng riêng (50ms, ba lần)', () => {
	const slow = createGovernor( G );
	slow.reset( 0, 0 );
	assert.deepEqual( run( slow, 33.3, 30000, { toFallback: true } ), [], '30fps: không xuống 2D' );
	const awful = createGovernor( G );
	awful.reset( 0, 0 );
	const hits = run( awful, 80, 30000, { toFallback: true } );
	assert.ok( hits.length >= 1, '12fps: xuống 2D' );
	assert.ok( hits[ 0 ] >= 3 * G.windowMs - 100, `cần ba cửa sổ (lần đầu ở ${ hits[ 0 ] })` );
} );
