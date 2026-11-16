// Kiểm tra chọn chế độ và governor (assets/js/services-cinematic/tier.js). Chạy: node --test "tests/*.test.mjs" (từ thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { modeFromGpu, buildLadder, createGovernor, LADDER_DIMS } from '../assets/js/services-cinematic/tier.js';
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

const DIMS = [ 'dpr', ...LADDER_DIMS ];
function assertMonotone( ladder, label ) {
	assert.equal( ladder[ ladder.length - 1 ].mode, '2D', label + ': bậc cuối là 2D' );
	for ( let i = 1; i < ladder.length - 1; i++ ) {
		const a = ladder[ i - 1 ];
		const b = ladder[ i ];
		for ( const d of DIMS ) {
			assert.ok( b[ d ] <= a[ d ], `${ label }: bậc ${ i } không được nâng ${ d }` );
		}
		assert.ok( ! ( b.bloom && ! a.bloom ), `${ label }: bậc ${ i } không bật lại bloom` );
		assert.equal( b.mode, a.mode, `${ label }: cùng một cảnh, chỉ khác tham số` );
	}
}
const fmt = ( q ) => ( q.mode === '2D' ? '2D' : `${ q.dpr }/${ +q.bloom }/${ q.grid }/${ q.mist }/${ q.dust }/${ q.rays }/${ q.reeds }` );

test( 'buildLadder: FULL_HIGH trên màn hình DPR 2, theo đúng thứ tự v3 (DPR → bloom → lưới → sương → bụi → tia → cỏ lau → DPR 1)', () => {
	const l = buildLadder( 'FULL_HIGH', P, { deviceDpr: 2, dprSteps: G.dprSteps } );
	assertMonotone( l, 'FULL_HIGH dpr2' );
	assert.deepEqual( l.map( fmt ), [
		'1.75/1/320/4/300/5/180',
		'1.5/1/320/4/300/5/180', // DPR
		'1.5/0/320/4/300/5/180', // bloom
		'1.5/0/160/4/300/5/180', // mật độ lưới
		'1.5/0/160/2/300/5/180', // dải sương
		'1.5/0/160/2/150/5/180', // hạt bụi
		'1.5/0/160/2/150/3/180', // quad tia
		'1.5/0/160/2/150/3/90', // cỏ lau
		'1/0/160/2/150/3/90', // DPR 1.0
		'2D',
	] );
} );

test( 'buildLadder: bỏ bậc không đổi gì (DPR 1, chưa có bloom)', () => {
	const full = buildLadder( 'FULL', P, { deviceDpr: 1, dprSteps: G.dprSteps } );
	assertMonotone( full, 'FULL dpr1' );
	assert.deepEqual( full.map( fmt ), [
		// Màn hình DPR 1: hạ DPR không đổi gì thật nên gộp vào bậc kế (giá trị dpr ghi trong bậc vẫn giảm).
		'1.5/0/320/4/300/5/180', '1.25/0/160/4/300/5/180', '1.25/0/160/2/300/5/180', '1.25/0/160/2/150/5/180', '1.25/0/160/2/150/3/180',
		'1.25/0/160/2/150/3/90', '2D',
	] );
} );

test( 'buildLadder: LITE (điện thoại DPR 3) vẫn hạ được dưới cấu hình LITE, không dưới một nửa; 2D / STATIC không có thang', () => {
	const l = buildLadder( 'LITE', P, { deviceDpr: 3, dprSteps: G.dprSteps } );
	assertMonotone( l, 'LITE dpr3' );
	assert.deepEqual( l.map( fmt ), [
		'1.5/0/160/2/120/3/70', '1.25/0/160/2/120/3/70', '1.25/0/80/2/120/3/70', '1.25/0/80/1/120/3/70', '1.25/0/80/1/60/3/70',
		'1.25/0/80/1/60/2/70', '1.25/0/80/1/60/2/35', '1/0/80/1/60/2/35', '2D',
	] );
	assert.equal( buildLadder( '2D', P ).length, 1 );
	assert.equal( buildLadder( 'STATIC', P ).length, 1 );
	assert.ok( Object.isFrozen( l ) && Object.isFrozen( l[ 0 ] ) );
} );

test( 'buildLadder: không bậc nào có khoá ngoài tham số v3 (grain, vignette không nằm trong thang)', () => {
	for ( const mode of [ 'FULL_HIGH', 'FULL', 'LITE' ] ) {
		for ( const q of buildLadder( mode, P, { deviceDpr: 2, dprSteps: G.dprSteps } ).slice( 0, -1 ) ) {
			assert.deepEqual( Object.keys( q ).sort(), [ 'bloom', 'dpr', 'mode', ...LADDER_DIMS ].sort() );
		}
	}
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
