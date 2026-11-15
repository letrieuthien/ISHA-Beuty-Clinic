// Bất biến địa hình: đỉnh chính luôn là điểm cao nhất (assets/js/services-cinematic/scene/terrain.js, createHeightField).
// Chạy: node --test "tests/*.test.mjs" (từ thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createHeightField } from '../assets/js/services-cinematic/scene/terrain.js';
import { CONFIG } from '../assets/js/services-cinematic/config.js';

function scan( seed, octaves, grid ) {
	const cfg = CONFIG.terrain;
	const f = createHeightField( seed, cfg, octaves );
	let max = -Infinity;
	let at = null;
	for ( let i = 0; i <= grid; i++ ) {
		for ( let j = 0; j <= grid; j++ ) {
			const x = -cfg.size / 2 + ( cfg.size * i ) / grid;
			const z = -cfg.size / 2 + ( cfg.size * j ) / grid;
			const h = f.height( x, z );
			if ( h > max ) {
				max = h;
				at = [ x, z ];
			}
		}
	}
	return { max, at, dist: Math.hypot( at[ 0 ], at[ 1 ] - cfg.peakZ ) };
}

test( 'đỉnh cao nhất nằm trong bán kính đỉnh chính với seed của các nhóm hiện có và nhiều seed khác (desktop 6 octave, lite 4 octave)', () => {
	const seeds = [ 14, 15, 16, 17, 18, 19 ].map( ( id ) => ( id * 7919 ) % 100000 ).concat( [ 1, 7, 42, 12345, 99999 ] );
	for ( const seed of seeds ) {
		for ( const oct of [ 6, 4 ] ) {
			const r = scan( seed, oct, 128 );
			assert.ok( r.dist <= CONFIG.terrain.peakRadius, `seed=${ seed } oct=${ oct } max=${ r.max.toFixed( 1 ) } tại ${ r.at } cách đỉnh ${ r.dist.toFixed( 0 ) }` );
		}
	}
} );

test( 'đồng cỏ quanh camera gần phẳng (thấp hơn 30 đơn vị)', () => {
	const f = createHeightField( 42, CONFIG.terrain, 6 );
	for ( const [ x, z ] of [ [ 0, 900 ], [ 0, 700 ], [ -40, 800 ], [ 30, 610 ], [ 0, 460 ] ] ) {
		assert.ok( Math.abs( f.height( x, z ) ) < 30, `(${ x }, ${ z }) = ${ f.height( x, z ).toFixed( 1 ) }` );
	}
} );
