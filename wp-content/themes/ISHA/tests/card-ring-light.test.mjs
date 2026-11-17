// Tia nắng chạm thẻ (assets/js/card-ring/light.js). Chạy: node --test "tests/*.test.mjs" (thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { cardQuad, hitQuad, edgeLocal, linkRays, cardSpec } from '../assets/js/card-ring/light.js';

const G = { R: 1100, P: 1100, cardW: 200, cardH: 280, flat: false, slot: 12.71, pitch: 244 };
const C = { x: 720, y: 700 };
const O = { x: 720, y: 680 };

test( 'cardQuad: thẻ giữa không phối cảnh (k = 1), đúng kích thước; thẻ bên phải to hơn và mép ngoài gần hơn', () => {
	const q = cardQuad( 0, G, C, O );
	assert.ok( Math.abs( q[ 1 ].x - q[ 0 ].x - 200 ) < 1e-9 && Math.abs( q[ 3 ].y - q[ 0 ].y - 280 ) < 1e-9 );
	q.forEach( ( p ) => assert.equal( p.w, 1 ) );
	const r = cardQuad( 25, G, C, O );
	assert.ok( r[ 0 ].x > 720, 'bên phải' );
	assert.ok( r[ 1 ].w < r[ 0 ].w, 'mép ngoài (TR) gần mắt hơn mép trong (TL)' );
	assert.ok( r[ 2 ].y - r[ 1 ].y > r[ 3 ].y - r[ 0 ].y, 'mép gần cao hơn trên màn hình' );
	const f = cardQuad( 12.71, { ...G, flat: true }, C, O );
	assert.ok( Math.abs( f[ 0 ].x - ( 720 + 244 - 100 ) ) < 1e-9, 'hàng phẳng: dịch đúng một pitch' );
} );

test( 'hitQuad: tia thẳng đứng xuống giữa thẻ chạm cạnh trên; tia lệch ra ngoài không chạm', () => {
	const q = cardQuad( 0, G, C, O );
	const h = hitQuad( { x: 720, y: 300 }, { x: 0, y: 1 }, q );
	assert.equal( h.edge, 0 );
	assert.ok( Math.abs( h.u - 0.5 ) < 1e-9 );
	assert.ok( Math.abs( h.t - ( 560 - 300 ) ) < 1e-9 );
	assert.equal( hitQuad( { x: 1000, y: 300 }, { x: 0, y: 1 }, q ), null );
	assert.equal( hitQuad( { x: 720, y: 300 }, { x: 0, y: -1 }, q ), null, 't > 0 mới tính' );
} );

test( 'edgeLocal: nội suy đúng phối cảnh (giữa cạnh trên màn hình của thẻ nghiêng ≠ giữa cạnh thật)', () => {
	const q = cardQuad( 0, G, C, O );
	assert.deepEqual( edgeLocal( 0, 0.5, q, 200, 280 ), { x: 100, y: 0 } );
	assert.deepEqual( edgeLocal( 1, 0.25, q, 200, 280 ), { x: 200, y: 70 } );
	const r = cardQuad( 30, G, C, O );
	const mid = edgeLocal( 0, 0.5, r, 200, 280 );
	assert.ok( mid.x > 100, 'mép gần mắt được phóng to: điểm giữa trên màn hình rơi vào nửa gần của cạnh thật' );
	// Kiểm ngược: chiếu điểm 3D ở x thật rồi so vị trí màn hình.
	const t = mid.x / 200;
	const P0 = r[ 0 ];
	const P1 = r[ 1 ];
	// Điểm 3D ở tham số t chiếu ra màn hình: ( ( 1 − t ) · S_A · w_A + t · S_B · w_B ) / ( ( 1 − t ) · w_A + t · w_B ).
	const sx = ( ( 1 - t ) * P0.x * P0.w + t * P1.x * P1.w ) / ( ( 1 - t ) * P0.w + t * P1.w );
	assert.ok( Math.abs( sx - ( P0.x + P1.x ) / 2 ) < 1e-6 );
} );

test( 'linkRays: tia dừng trước viền một khoảng gap, dài dần theo grow; điểm sáng chỉ khi đã với tới; mỗi thẻ lấy tia mạnh nhất', () => {
	const quads = [ -12.71, 0, 12.71 ].map( ( th, k ) => ( { i: k, q: cardQuad( th, G, C, O ) } ) );
	const rays = [ { angle: -Math.PI / 2, len: 0.5, halfFar: 0.2, gain: 0.2 }, { angle: -Math.PI / 2 - 0.05, len: 0.5, halfFar: 0.2, gain: 0.1 }, { angle: 0, len: 0.5, halfFar: 0.2, gain: 0.3 } ];
	const S = { x: 720, y: 200 };
	const full = linkRays( { S, H: 900, rays, count: 3, quads, cardW: 200, cardH: 280, gap: 14, grow: 1 } );
	assert.ok( Math.abs( full.rays[ 0 ].len * 900 - ( 560 - 200 - 14 ) ) < 1e-6, 'đầu tia cách viền 14 px' );
	assert.equal( full.rays[ 0 ].tip, 1 );
	assert.equal( full.rays[ 2 ].len, 0, 'tia nằm ngang không chạm thẻ nào: độ dài mặc định' );
	const hit = full.hits.get( 1 );
	assert.ok( hit && hit.y === 0 && Math.abs( hit.x - 100 ) < 1e-6 && hit.a === 0.2, 'thẻ giữa sáng ở giữa cạnh trên, lấy tia mạnh hơn' );
	const half = linkRays( { S, H: 900, rays, count: 3, quads, cardW: 200, cardH: 280, gap: 14, grow: 0.5 } );
	assert.ok( Math.abs( half.rays[ 0 ].len - full.rays[ 0 ].len / 2 ) < 1e-9 );
	assert.equal( half.hits.size, 0, 'chưa với tới: viền chưa sáng' );
} );

test( 'cardSpec: mạnh nhất ở giữa (= max), đối xứng, giảm dần ra rìa, 0 từ 90°', () => {
	assert.equal( cardSpec( 0, 0.85, 3 ), 0.85 );
	assert.ok( Math.abs( cardSpec( 30, 0.85, 3 ) - cardSpec( -30, 0.85, 3 ) ) < 1e-12 );
	let prev = Infinity;
	for ( let t = 0; t <= 89; t += 7 ) {
		const s = cardSpec( t, 0.85, 3 );
		assert.ok( s < prev || t === 0 );
		prev = s;
	}
	assert.ok( Math.abs( cardSpec( 60, 1, 3 ) - 0.125 ) < 1e-12 );
	assert.equal( cardSpec( 90, 1, 3 ) < 1e-12, true );
	assert.equal( cardSpec( 120, 1, 3 ), 0 );
} );
