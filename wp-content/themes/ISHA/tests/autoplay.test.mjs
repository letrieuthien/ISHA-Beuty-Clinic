// Bình minh tự chạy (assets/js/services-cinematic/autoplay.js). Chạy: node --test "tests/*.test.mjs" (thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createAutoplay, easeInOutSine, easeOutSine } from '../assets/js/services-cinematic/autoplay.js';

const CFG = { duration: 12, cover: 0.95 };

test( 'chưa phủ kín màn hình thì không chạy; phủ ≥ cover thì bắt đầu từ 0', () => {
	const a = createAutoplay( CFG );
	assert.equal( a.observe( 0.5, 0 ), null );
	assert.equal( a.observe( 0.94, 100 ), null );
	assert.equal( a.tick( 5000 ), 0 );
	assert.equal( a.phase, 'idle' );
	assert.equal( a.observe( 0.96, 1000 ), 'start' );
	assert.equal( a.phase, 'playing' );
	assert.equal( a.tick( 1000 ), 0 );
} );

test( 'progress theo thời gian (easeInOutSine), tới 1 sau duration rồi đứng ở done', () => {
	const a = createAutoplay( CFG );
	a.observe( 1, 0 );
	assert.ok( Math.abs( a.tick( 6000 ) - 0.5 ) < 1e-9 );
	assert.ok( Math.abs( a.tick( 3000 ) - easeInOutSine( 0.25 ) ) < 1e-9 );
	assert.equal( a.tick( 12000 ), 1 );
	assert.equal( a.phase, 'done' );
	assert.equal( a.tick( 99999 ), 1 );
} );

test( 'rời một phần (vẫn còn hiện) không đặt lại; khuất hẳn thì đặt lại về 0; quay lại phủ kín thì chạy lại', () => {
	const a = createAutoplay( CFG );
	a.observe( 1, 0 );
	a.tick( 4000 );
	assert.equal( a.observe( 0.3, 4000 ), null );
	assert.ok( a.tick( 5000 ) > 0 );
	assert.equal( a.observe( 0, 5000 ), 'reset' );
	assert.equal( a.phase, 'idle' );
	assert.equal( a.progress, 0 );
	assert.equal( a.tick( 8000 ), 0 );
	assert.equal( a.observe( 0.5, 9000 ), null );
	assert.equal( a.observe( 1, 9500 ), 'start' );
	assert.ok( Math.abs( a.tick( 15500 ) - 0.5 ) < 1e-9 );
} );

test( 'canStart = false (màn chờ còn che): phủ kín cũng chưa bắt đầu; hết chặn thì bắt đầu; khuất hẳn vẫn đặt lại được', () => {
	const a = createAutoplay( CFG );
	assert.equal( a.observe( 1, 0, false ), null );
	assert.equal( a.phase, 'idle' );
	assert.equal( a.tick( 5000 ), 0 );
	assert.equal( a.observe( 1, 6000, true ), 'start' ); // release(): đánh giá lại với phần đang hiện
	assert.equal( a.phase, 'playing' );
	assert.equal( a.observe( 0, 7000, false ), 'reset' );
	assert.equal( a.phase, 'idle' );
	assert.equal( a.observe( 1, 8000 ), 'start' ); // mặc định canStart = true: hành vi cũ không đổi
} );

test( 'lơ lửng quanh ngưỡng không chạy / đặt lại liên tục (hai ngưỡng cách xa nhau)', () => {
	const a = createAutoplay( CFG );
	let events = 0;
	for ( let k = 0; k < 50; k++ ) {
		events += a.observe( k % 2 ? 0.96 : 0.9, k * 100 ) ? 1 : 0;
	}
	assert.equal( events, 1 ); // chỉ một lần 'start'
} );

test( 'tab ẩn: đồng hồ của lần chạy dừng, hiện lại thì chạy tiếp', () => {
	const a = createAutoplay( CFG );
	a.observe( 1, 0 );
	a.tick( 3000 );
	a.suspend( 3000 );
	assert.equal( a.tick( 60000 ), easeInOutSine( 0.25 ) );
	a.resume( 60000 );
	assert.ok( Math.abs( a.tick( 63000 ) - 0.5 ) < 1e-9 );
} );

test( 'đổi duration / cover lúc đang chạy (bảng debug) được đọc ngay; restart chạy lại từ 0', () => {
	const cfg = { ...CFG };
	const a = createAutoplay( cfg );
	a.observe( 1, 0 );
	cfg.duration = 6;
	assert.ok( Math.abs( a.tick( 3000 ) - 0.5 ) < 1e-9 );
	a.restart( 10000 );
	assert.equal( a.phase, 'playing' );
	assert.equal( a.tick( 10000 ), 0 );
} );

test( 'arm (bàn giao từ màn chờ): bắt đầu khi chỉ thấy một phần section, mọc easeOutSine; lần sau về bình thường', () => {
	const a = createAutoplay( CFG );
	a.arm();
	assert.equal( a.observe( 0.4, 0 ), 'start' ); // chưa phủ kín (cover 0.95) nhưng đã trong khung nhìn
	// easeOutSine(0.1) ≈ 0.156: đã lên đáng kể trong 1,2 s đầu, khác hẳn easeInOutSine (≈ 0.017).
	assert.ok( Math.abs( a.tick( 1200 ) - easeOutSine( 0.1 ) ) < 1e-9 );
	assert.ok( a.tick( 1200 ) > easeInOutSine( 0.1 ) * 5 );
	a.arm(); // đang chạy: không đổi đường cong của lần này
	a.restart( 20000 ); // restart: lần này vẫn easeOutSine (đã arm)
	assert.ok( Math.abs( a.tick( 21000 ) - easeOutSine( 1 / 12 ) ) < 1e-9 );
	a.restart( 40000 ); // lần sau: về easeInOutSine và cần phủ kín như cũ
	assert.ok( Math.abs( a.tick( 41000 ) - easeInOutSine( 1 / 12 ) ) < 1e-9 );
} );

test( 'không arm: lệch khỏi vị trí phủ kín (< cover) thì không bắt đầu; chỉ bàn giao mới bắt đầu ở phần nhỏ', () => {
	const a = createAutoplay( CFG );
	assert.equal( a.observe( 0.4, 0 ), null );
	const b = createAutoplay( CFG );
	b.arm();
	assert.equal( b.observe( 0.4, 0 ), 'start' );
	const c = createAutoplay( CFG );
	c.arm();
	assert.equal( c.observe( 0, 0 ), null ); // khuất hẳn: chưa bắt đầu, vẫn chờ tới khi thấy
	assert.equal( c.observe( 0.1, 500 ), 'start' );
} );
