// Hình học vòng cung lõm (assets/js/card-ring/ring.js). Chạy: node --test "tests/*.test.mjs" (thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { slotDeg, wrapDeg, ringCount, cardAngles, project } from '../assets/js/card-ring/ring.js';
import { RING } from '../assets/js/card-ring/config.js';

test( 'slotDeg: thẻ cách nhau GAP chiều rộng trên cung', () => {
	const s = slotDeg( 200, 1100, 0.22 );
	assert.ok( Math.abs( ( s * Math.PI ) / 180 * 1100 - 244 ) < 1e-9 );
} );

test( 'wrapDeg: luôn trong [−span/2, span/2)', () => {
	for ( const a of [ -1000, -60, -50, 0, 49.9, 50, 359, 12345.6 ] ) {
		const w = wrapDeg( a, 100 );
		assert.ok( w >= -50 && w < 50, `${ a } → ${ w }` );
		assert.ok( Math.abs( ( ( w - a ) / 100 ) - Math.round( ( w - a ) / 100 ) ) < 1e-9, 'chỉ dịch bội của span' );
	}
} );

test( 'ringCount: bội của số thẻ thật, ≥ MIN_CARDS, cung đủ rộng để điểm quay vòng nằm ngoài culling', () => {
	for ( const n of [ 1, 2, 3, 4, 5, 6 ] ) {
		for ( const [ w, R ] of [ [ 200, 1100 ], [ 180, 900 ], [ 172, 700 ] ] ) {
			const slot = slotDeg( w, R, RING.GAP );
			const c = ringCount( n, slot, RING.CULL_DEG, RING.MIN_CARDS );
			assert.equal( c % n, 0 );
			assert.ok( c >= RING.MIN_CARDS );
			assert.ok( ( c * slot ) / 2 >= RING.CULL_DEG + slot / 2, `n=${ n } R=${ R }: nửa cung ${ ( c * slot ) / 2 }°` );
		}
	}
} );

test( 'cardAngles: thứ tự liên tiếp cách nhau đúng một slot; quay đều không nhảy trong vùng nhìn thấy', () => {
	const slot = slotDeg( 200, 1100, RING.GAP );
	const n = ringCount( 4, slot, RING.CULL_DEG, RING.MIN_CARDS );
	const a = new Float64Array( n );
	const b = new Float64Array( n );
	for ( let spin = 0; spin < 720; spin += 0.37 ) {
		cardAngles( n, slot, spin, a );
		cardAngles( n, slot, spin + 0.37, b );
		for ( let i = 0; i < n; i++ ) {
			if ( Math.abs( a[ i ] ) <= RING.CULL_DEG ) {
				assert.ok( Math.abs( b[ i ] - ( a[ i ] - 0.37 ) ) < 1e-9, `thẻ ${ i } nhảy ở spin ${ spin }` );
			}
		}
	}
	cardAngles( n, slot, 0, a );
	assert.equal( a[ 0 ], 0 );
	assert.ok( Math.abs( a[ 1 ] - slot ) < 1e-9, 'thẻ kế bên phải' );
} );

test( 'project: lõm — thẻ ở rìa to hơn thẻ giữa (người xem đứng ở tâm)', () => {
	assert.equal( project( 0, 1100 ).scale, 1 );
	assert.ok( project( 30, 1100 ).scale > 1.15 );
	assert.ok( project( 30, 1100 ).x > 0 && project( -30, 1100 ).x < 0 );
} );

import { flatCount } from '../assets/js/card-ring/ring.js';
test( 'flatCount (điện thoại, hàng phẳng): bội số thẻ thật, hàng dài hơn khung + 2 thẻ', () => {
	for ( const n of [ 1, 2, 3, 4, 5, 6 ] ) {
		const pitch = 172 * 1.22;
		const c = flatCount( n, pitch, 390, 8 );
		assert.equal( c % n, 0 );
		assert.ok( c >= 8 && c * pitch >= 390 + 2 * pitch );
	}
} );
