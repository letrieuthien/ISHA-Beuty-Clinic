// Kiểm tra keyframe mặt trời (assets/js/services-cinematic/stops.js). Chạy: node --test tests/ (từ thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { computeStops, sampleStops, activeIndex, plateau, LABELS, DEFAULT_STOP_CFG } from '../assets/js/services-cinematic/stops.js';

test( 'computeStops: số điểm dừng bằng N với N = 1..6, đóng băng', () => {
	for ( let n = 1; n <= 6; n++ ) {
		const s = computeStops( n );
		assert.equal( s.length, n );
		assert.ok( Object.isFrozen( s ) );
		assert.ok( Object.isFrozen( s[ 0 ] ) );
	}
} );

test( 'computeStops: mặt trời dâng lên (sunY giảm đơn điệu trên màn hình), từ sunYStart tới sunYEnd', () => {
	for ( let n = 2; n <= 6; n++ ) {
		const s = computeStops( n );
		for ( let i = 1; i < n; i++ ) {
			assert.ok( s[ i ].sunY < s[ i - 1 ].sunY, `N=${ n } i=${ i }` );
		}
		assert.equal( s[ 0 ].sunY, DEFAULT_STOP_CFG.sunYStart );
		assert.ok( Math.abs( s[ n - 1 ].sunY - DEFAULT_STOP_CFG.sunYEnd ) < 1e-9 );
	}
} );

test( 'computeStops: trời sáng dần, hào quang mạnh dần; glare và god rays dịu dần theo thời điểm', () => {
	const s = computeStops( 5 );
	for ( let i = 1; i < 5; i++ ) {
		for ( const k of [ 'skyFloor', 'halo' ] ) {
			assert.ok( s[ i ][ k ] > s[ i - 1 ][ k ], `${ k } i=${ i }` );
		}
		for ( const k of [ 'glare', 'godRays' ] ) {
			assert.ok( s[ i ][ k ] < s[ i - 1 ][ k ], `${ k } i=${ i }` );
		}
	}
} );

test( 'computeStops: exposure giảm dần (rạng đông được bù sáng)', () => {
	const s = computeStops( 5 );
	for ( let i = 1; i < 5; i++ ) {
		assert.ok( s[ i ].exposure <= s[ i - 1 ].exposure );
	}
} );

test( 'computeStops: nhãn thời điểm đúng với N = 1, 3, 4, 5, 6', () => {
	assert.deepEqual( computeStops( 1 ).map( ( x ) => x.label ), [ 'Toàn quang' ] );
	assert.deepEqual( computeStops( 3 ).map( ( x ) => x.label ), [ 'Rạng đông', 'Nắng sớm', 'Toàn quang' ] );
	assert.deepEqual( computeStops( 4 ).map( ( x ) => x.label ), [ 'Rạng đông', 'Ban mai', 'Nắng sớm', 'Toàn quang' ] );
	// N = 5: t = 0.25 → round( 1.25 ) = 1 (Bình minh); t = 0.75 → round( 3.75 ) = 4 (Nắng vàng), theo công thức của đặc tả.
	assert.deepEqual( computeStops( 5 ).map( ( x ) => x.label ), [ 'Rạng đông', 'Bình minh', 'Nắng sớm', 'Nắng vàng', 'Toàn quang' ] );
	assert.deepEqual( computeStops( 6 ).map( ( x ) => x.label ), [ ...LABELS ] );
} );

test( 'sampleStops: p = 0 cho điểm dừng đầu, p = 1 cho điểm dừng cuối, tâm đoạn cho đúng điểm dừng', () => {
	for ( let n = 1; n <= 6; n++ ) {
		const s = computeStops( n );
		const out = {};
		sampleStops( s, 0, out );
		assert.equal( out.sunY, s[ 0 ].sunY );
		sampleStops( s, 1, out );
		assert.equal( out.sunY, s[ n - 1 ].sunY );
		for ( let i = 0; i < n; i++ ) {
			sampleStops( s, ( i + 0.5 ) / n, out );
			assert.ok( Math.abs( out.sunY - s[ i ].sunY ) < 1e-9, `N=${ n } i=${ i }` );
			assert.equal( out.label, s[ i ].label );
		}
	}
} );

test( 'sampleStops: mặt trời không tụt xuống khi p tăng (sunY không tăng), có vùng giữ quanh điểm dừng', () => {
	const s = computeStops( 4 );
	const out = {};
	let prev = Infinity;
	for ( let k = 0; k <= 1000; k++ ) {
		sampleStops( s, k / 1000, out );
		assert.ok( out.sunY <= prev + 1e-12 );
		prev = out.sunY;
	}
	// Ngay sau tâm đoạn 1 (trong vùng giữ) mặt trời vẫn ở điểm dừng 1.
	sampleStops( s, 1.5 / 4 + 0.03, out );
	assert.ok( Math.abs( out.sunY - s[ 1 ].sunY ) < 1e-9 );
} );

test( 'sampleStops: ghi vào object out, không cấp phát object mới', () => {
	const s = computeStops( 3 );
	const out = {};
	assert.equal( sampleStops( s, 0.4, out ), out );
} );

test( 'plateau: 0 ở đầu, 1 ở cuối, 0.5 ở giữa', () => {
	assert.equal( plateau( 0, 0.35 ), 0 );
	assert.equal( plateau( 1, 0.35 ), 1 );
	assert.ok( Math.abs( plateau( 0.5, 0.35 ) - 0.5 ) < 1e-9 );
} );

test( 'activeIndex: đúng đoạn, giữ nguyên quanh biên (không nhấp nháy), cuộn nhanh nhảy thẳng tới đoạn cuối', () => {
	const N = 4;
	assert.equal( activeIndex( 0, N ), 0 );
	assert.equal( activeIndex( 1, N ), 3 );
	assert.equal( activeIndex( 0.6, N, -1 ), 2 );
	// Dao động quanh biên 0.25 giữa đoạn 0 và 1: không đổi khỏi prev khi chưa vượt trễ.
	let idx = 0;
	for ( const p of [ 0.249, 0.251, 0.2495, 0.252, 0.2499 ] ) {
		idx = activeIndex( p, N, idx );
		assert.equal( idx, 0, `p=${ p }` );
	}
	idx = activeIndex( 0.26, N, idx );
	assert.equal( idx, 1 );
	// Lùi lại sát biên: vẫn giữ 1.
	assert.equal( activeIndex( 0.249, N, 1 ), 1 );
	assert.equal( activeIndex( 0.24, N, 1 ), 0 );
	// Cuộn nhanh từ 0 tới cuối.
	assert.equal( activeIndex( 0.95, N, 0 ), 3 );
} );

// Giá trị theo độ cao mặt trời (góp ý khách sau bước 4 của v3).
import { riseValues, sunHeight, RISE_FIELDS } from '../assets/js/services-cinematic/stops.js';
import { CONFIG } from '../assets/js/services-cinematic/config.js';

test( 'riseValues: đúng chiều khách yêu cầu khi mặt trời lên cao', () => {
	const lo = riseValues( 0, CONFIG.rise );
	const hi = riseValues( 1, CONFIG.rise );
	assert.ok( hi.vignette < lo.vignette, 'vignette giảm' );
	assert.ok( hi.band > lo.band, 'uBand tăng' );
	assert.ok( hi.fov < lo.fov && lo.fov === 0, 'FOV giảm nhẹ, ở điểm đầu không lệch (trùng camera tham chiếu)' );
	assert.ok( hi.halo > lo.halo, 'quầng sáng dần' );
	assert.ok( hi.sunScale > lo.sunScale, 'đĩa to nhẹ' );
	for ( const k of [ 'rayLength', 'raySpread', 'rayWidth', 'raysZ' ] ) {
		assert.ok( hi[ k ] > lo[ k ], `tia: ${ k } tăng` );
	}
} );

test( 'riseValues: đơn điệu, hữu hạn, kẹp ngoài [0, 1], ghi vào out không cấp phát', () => {
	const out = {};
	let prev = null;
	for ( let i = 0; i <= 20; i++ ) {
		const r = riseValues( i / 20, CONFIG.rise, out );
		assert.equal( r, out );
		for ( const k of RISE_FIELDS ) {
			assert.ok( Number.isFinite( r[ k ] ), k );
			if ( prev ) {
				const dir = Math.sign( CONFIG.rise[ k ][ 1 ] - CONFIG.rise[ k ][ 0 ] );
				assert.ok( ( r[ k ] - prev[ k ] ) * dir >= -1e-12, `${ k } đơn điệu` );
			}
		}
		prev = { ...r };
	}
	assert.deepEqual( { ...riseValues( -1, CONFIG.rise ) }, { ...riseValues( 0, CONFIG.rise ) } );
	assert.deepEqual( { ...riseValues( 2, CONFIG.rise ) }, { ...riseValues( 1, CONFIG.rise ) } );
	assert.deepEqual( { ...riseValues( NaN, CONFIG.rise ) }, { ...riseValues( 0, CONFIG.rise ) } );
} );

test( 'sunHeight: 0 ở điểm đầu, 1 ở điểm cuối, kẹp, đường đi phẳng thì 0', () => {
	const path = [ 0.5, 0.5, 0.6, 0.2 ];
	assert.equal( sunHeight( 0.5, path ), 0 );
	assert.equal( sunHeight( 0.2, path ), 1 );
	assert.ok( Math.abs( sunHeight( 0.35, path ) - 0.5 ) < 1e-12 );
	assert.equal( sunHeight( 0.9, path ), 0 );
	assert.equal( sunHeight( 0.0, path ), 1 );
	assert.equal( sunHeight( 0.3, [ 0, 0.4, 1, 0.4 ] ), 0 );
} );
