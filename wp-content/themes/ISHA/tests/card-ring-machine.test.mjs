// Máy trạng thái tương tác của vòng thẻ (assets/js/card-ring/machine.js). Chạy: node --test "tests/*.test.mjs" (thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { reduce, initialState, isSelected, SPINNING, HOVER_PAUSED, SEEKING, FOCUSED, SNAPPING, WAIT, DRAGGING } from '../assets/js/card-ring/machine.js';

const ctx = { nReal: 6, canSeek: true, center: 2, resumeMs: 3000 };
/** Chạy chuỗi sự kiện, trả trạng thái cuối và mọi fx. */
function run( events, s = initialState(), c = ctx ) {
	const fx = [];
	for ( const e of events ) {
		const r = reduce( s, e, c );
		s = r.s;
		fx.push( ...r.fx );
	}
	return { s, fx };
}

test( 'hover: SPINNING ↔ HOVER_PAUSED; rời thẻ khác không thoát; đổi thẻ hover', () => {
	let r = run( [ { type: 'HOVER', i: 3 } ] );
	assert.deepEqual( r.s, { mode: HOVER_PAUSED, hover: 3, focus: -1 } );
	r = run( [ { type: 'HOVER', i: 3 }, { type: 'UNHOVER', i: 4 } ] );
	assert.equal( r.s.mode, HOVER_PAUSED );
	r = run( [ { type: 'HOVER', i: 3 }, { type: 'HOVER', i: 4 }, { type: 'UNHOVER', i: 3 } ] );
	assert.equal( r.s.hover, 4 );
	r = run( [ { type: 'HOVER', i: 3 }, { type: 'UNHOVER', i: 3 } ] );
	assert.deepEqual( r.s, initialState() );
} );

test( 'click: SEEKING (fx seek) → tới nơi FOCUSED; hover thẻ khác bị bỏ qua khi SEEKING và FOCUSED', () => {
	let r = run( [ { type: 'HOVER', i: 1 }, { type: 'ACTIVATE', i: 1 } ] );
	assert.deepEqual( r.s, { mode: SEEKING, hover: -1, focus: 1 } );
	assert.deepEqual( r.fx, [ { type: 'seek', i: 1 } ] );
	r = run( [ { type: 'ACTIVATE', i: 1 }, { type: 'HOVER', i: 2 } ] );
	assert.equal( r.s.mode, SEEKING );
	r = run( [ { type: 'ACTIVATE', i: 1 }, { type: 'ARRIVED' }, { type: 'HOVER', i: 2 } ] );
	assert.deepEqual( r.s, { mode: FOCUSED, hover: -1, focus: 1 } );
	assert.ok( isSelected( r.s, 1 ) && ! isSelected( r.s, 2 ) );
} );

test( 'click lần hai vào thẻ FOCUSED: mở trang chi tiết; click lại khi còn SEEKING: không seek lại', () => {
	let r = run( [ { type: 'ACTIVATE', i: 1 }, { type: 'ARRIVED' }, { type: 'ACTIVATE', i: 1 } ] );
	assert.deepEqual( r.fx.at( -1 ), { type: 'open', i: 1 } );
	assert.equal( r.s.mode, FOCUSED );
	r = run( [ { type: 'ACTIVATE', i: 1 }, { type: 'ACTIVATE', i: 1 } ] );
	assert.equal( r.fx.length, 1 );
} );

test( 'focus-out: click ngoài / Esc / ra khỏi khung / Tab ra ngoài về SPINNING; click thẻ khác seek thẳng sang thẻ đó', () => {
	for ( const out of [ { type: 'FOCUS_OUT' }, { type: 'KEY', key: 'Escape' } ] ) {
		const r = run( [ { type: 'ACTIVATE', i: 1 }, { type: 'ARRIVED' }, out ] );
		assert.deepEqual( r.s, initialState() );
		const r2 = run( [ { type: 'ACTIVATE', i: 1 }, out ] ); // thoát cả khi đang SEEKING
		assert.equal( r2.s.mode, SPINNING );
	}
	const r = run( [ { type: 'ACTIVATE', i: 1 }, { type: 'ARRIVED' }, { type: 'ACTIVATE', i: 4 } ] );
	assert.deepEqual( r.s, { mode: SEEKING, hover: -1, focus: 4 } );
	assert.deepEqual( r.fx.at( -1 ), { type: 'seek', i: 4 } );
	// FOCUS_OUT khi không có thẻ chọn: không đổi (giữ hover).
	assert.equal( run( [ { type: 'HOVER', i: 2 }, { type: 'FOCUS_OUT' } ] ).s.mode, HOVER_PAUSED );
} );

test( 'bàn phím: mũi tên đi trên thẻ thật (vòng lại), seek + chuyển focus; bắt đầu từ thẻ gần giữa', () => {
	let r = run( [ { type: 'KEY', key: 'ArrowRight' } ] );
	assert.deepEqual( r.s, { mode: SEEKING, hover: -1, focus: 3 } );
	assert.deepEqual( r.fx, [ { type: 'seek', i: 3 }, { type: 'focusEl', i: 3 } ] );
	r = run( [ { type: 'ACTIVATE', i: 0 }, { type: 'ARRIVED' }, { type: 'KEY', key: 'ArrowLeft' } ] );
	assert.equal( r.s.focus, 5 );
	r = run( [ { type: 'ACTIVATE', i: 11 }, { type: 'KEY', key: 'ArrowRight' } ] ); // bản sao 11 = thẻ thật 5 → thẻ 0
	assert.equal( r.s.focus, 0 );
	assert.equal( run( [ { type: 'KEY', key: 'a' } ] ).s.mode, SPINNING );
	// Tab vào liên kết của thẻ: chọn thẻ đó, lặp lại không seek lại.
	r = run( [ { type: 'FOCUS_CARD', i: 2 }, { type: 'FOCUS_CARD', i: 2 } ] );
	assert.equal( r.fx.length, 1 );
} );

test( 'vuốt: DRAGGING → thả SNAPPING (fx snap) → tới nơi WAIT (hẹn 3 giây) → RESUME về SPINNING; click khi đang kéo bị bỏ qua', () => {
	const r = run( [ { type: 'ACTIVATE', i: 1 }, { type: 'DRAG_START' }, { type: 'ACTIVATE', i: 2 }, { type: 'DRAG_END', spin: 36 } ] );
	assert.equal( r.s.mode, SNAPPING );
	assert.deepEqual( r.fx.slice( 1 ), [ { type: 'clearTimer' }, { type: 'snap', spin: 36 } ] );
	const r2 = run( [ { type: 'ARRIVED' } ], r.s );
	assert.equal( r2.s.mode, WAIT );
	assert.deepEqual( r2.fx, [ { type: 'timer', ms: 3000 } ] );
	assert.equal( run( [ { type: 'HOVER', i: 1 } ], r2.s ).s.mode, WAIT );
	assert.deepEqual( run( [ { type: 'RESUME' } ], r2.s ).s, initialState() );
	assert.equal( run( [ { type: 'DRAG_START' }, { type: 'RESUME' } ] ).s.mode, DRAGGING );
} );

test( 'vòng đứng yên (canSeek = false): chọn thẻ thì FOCUSED tại chỗ, không seek', () => {
	const r = run( [ { type: 'ACTIVATE', i: 2 } ], initialState(), { ...ctx, canSeek: false } );
	assert.deepEqual( r.s, { mode: FOCUSED, hover: -1, focus: 2 } );
	assert.equal( r.fx.length, 0 );
} );
