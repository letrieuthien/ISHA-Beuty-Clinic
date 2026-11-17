// Chuyển động của vòng thẻ (assets/js/card-ring/spring.js). Chạy: node --test "tests/*.test.mjs" (thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { shortestDelta, dampOmega, omegaForSettle, springStep, springDone, createMotion } from '../assets/js/card-ring/spring.js';
import { RING } from '../assets/js/card-ring/config.js';

test( 'shortestDelta: luôn đi cung ngắn nhất, trong [−chu kỳ/2, chu kỳ/2)', () => {
	assert.equal( shortestDelta( 10, 20, 360 ), 10 );
	assert.equal( shortestDelta( 350, 10, 360 ), 20 );
	assert.equal( shortestDelta( 10, 350, 360 ), -20 );
	assert.equal( shortestDelta( 0, 100, 160 ), -60, 'chu kỳ là span của vòng ảo, không phải 360' );
	for ( let i = 0; i < 200; i++ ) {
		const d = shortestDelta( i * 7.3, i * -11.1, 200 );
		assert.ok( d >= -100 && d < 100 );
	}
} );

test( 'dampOmega: tiến dần về đích, không vượt, độc lập tốc độ khung', () => {
	let a = 0;
	let b = 0;
	for ( let i = 0; i < 60; i++ ) {
		a = dampOmega( a, 4, 4, 1 / 60 );
	}
	for ( let i = 0; i < 30; i++ ) {
		b = dampOmega( b, 4, 4, 1 / 30 );
	}
	assert.ok( Math.abs( a - b ) < 1e-9, 'cùng 1 giây: 60 khung = 30 khung' );
	assert.ok( a < 4 && a > 3.9 );
	// Dừng (ω_target = 0): sau 600 ms còn < 10%.
	let w = 4;
	for ( let t = 0; t < 0.6; t += 1 / 60 ) {
		w = dampOmega( w, 0, 4, 1 / 60 );
	}
	assert.ok( w < 0.4 && w > 0 );
} );

test( 'spring tới hạn: tới đích trong SEEK_MS, không vượt đích, ổn định với mọi dt', () => {
	const w = omegaForSettle( RING.SEEK_MS / 1000 );
	for ( const dt of [ 1 / 120, 1 / 60, 1 / 30 ] ) {
		const s = { x: 0, v: 0, target: 40, w };
		let t = 0;
		let max = 0;
		while ( t < RING.SEEK_MS / 1000 ) {
			springStep( s, dt );
			max = Math.max( max, s.x );
			t += dt;
		}
		assert.ok( Math.abs( s.x - 40 ) < 40 * 0.0015, `dt ${ dt }: còn lệch ${ 40 - s.x }` );
		assert.ok( max <= 40 + 1e-9, 'không nảy quá đích' );
	}
} );

test( 'createMotion: auto xoay đều ω; seek đi cung ngắn nhất, dừng hẳn đúng đích, báo tới nơi một lần', () => {
	const m = createMotion( RING );
	m.omegaTarget = 4;
	for ( let i = 0; i < 600; i++ ) {
		m.step( 1 / 60 );
	}
	assert.ok( Math.abs( m.omega - 4 ) < 1e-3 );
	const before = m.spin;
	m.seek( before + 150 + 3 * 160, 160 ); // chu kỳ 160: đích +150 ≡ −10
	m.omegaTarget = 0; // FOCUSED: không xoay tiếp sau khi tới nơi
	assert.ok( Math.abs( m.seeking.target - ( before - 10 ) ) < 1e-9, 'cung ngắn nhất: lùi 10° chứ không tiến 150°' );
	let arrived = 0;
	for ( let i = 0; i < 120; i++ ) {
		if ( m.step( 1 / 60 ) ) {
			arrived++;
		}
	}
	assert.equal( arrived, 1 );
	assert.ok( Math.abs( m.spin - ( before - 10 ) ) < 1e-9, 'dừng đúng đích' );
	assert.equal( m.seeking, null );
} );

test( 'springDone: cần cả sai lệch lẫn vận tốc nhỏ', () => {
	assert.equal( springDone( { x: 1, v: 0, target: 1 } ), true );
	assert.equal( springDone( { x: 1, v: 5, target: 1 } ), false );
} );
