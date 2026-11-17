// Hiện / ẩn vòng thẻ của section (assets/js/services-cinematic/ui/card-machine.js). Chạy: node --test "tests/*.test.mjs" (thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { cardReducer, isShown, INITIAL } from '../assets/js/services-cinematic/ui/card-machine.js';

const run = ( events, start = INITIAL ) => events.reduce( cardReducer, start );

test( 'bình minh bắt đầu thì hiện vòng; section khuất hẳn thì ẩn; quay lại thì hiện lại', () => {
	let s = run( [ { type: 'ENTER_SECTION' } ] );
	assert.deepEqual( { ...s }, { phase: 'shown', inView: true } );
	assert.ok( isShown( s ) );
	s = cardReducer( s, { type: 'LEAVE_SECTION' } );
	assert.equal( s.phase, 'idle' );
	assert.ok( ! isShown( s ) );
	assert.ok( isShown( cardReducer( s, { type: 'ENTER_SECTION' } ) ) );
} );

test( 'Esc / chạm khung cảnh ẩn vòng, Enter / chạm lại hiện; reopen khi không ẩn thì không đổi', () => {
	let s = run( [ { type: 'ENTER_SECTION' }, { type: 'DISMISS' } ] );
	assert.equal( s.phase, 'dismissed' );
	assert.ok( ! isShown( s ) );
	s = cardReducer( s, { type: 'REOPEN' } );
	assert.ok( isShown( s ) );
	assert.equal( cardReducer( s, { type: 'REOPEN' } ), s );
} );

test( 'vào lại section sau khi đã ẩn thì hiện (lần bình minh mới)', () => {
	const s = run( [ { type: 'ENTER_SECTION' }, { type: 'DISMISS' }, { type: 'LEAVE_SECTION' }, { type: 'ENTER_SECTION' } ] );
	assert.ok( isShown( s ) );
} );

test( 'không thay đổi thì trả về chính state cũ (===)', () => {
	const s = run( [ { type: 'ENTER_SECTION' } ] );
	assert.equal( cardReducer( s, { type: 'ENTER_SECTION' } ), s );
	assert.equal( cardReducer( s, { type: 'UNKNOWN' } ), s );
} );

test( 'ngoài section: dismiss / reopen không có tác dụng', () => {
	let s = run( [ { type: 'DISMISS' } ] );
	assert.equal( s, INITIAL );
	s = cardReducer( s, { type: 'REOPEN' } );
	assert.equal( s, INITIAL );
} );
