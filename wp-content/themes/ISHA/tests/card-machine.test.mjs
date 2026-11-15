// Kiểm tra state machine của card (assets/js/services-cinematic/ui/card-machine.js). Chạy: node --test tests/ (từ thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { cardReducer, visibleIndex, INITIAL } from '../assets/js/services-cinematic/ui/card-machine.js';

const run = ( events, start = INITIAL ) => events.reduce( cardReducer, start );

test( 'vào section thì hiện card của index hiện tại; ra thì ẩn', () => {
	let s = run( [ { type: 'ENTER_SECTION', index: 0 } ] );
	assert.deepEqual( { ...s }, { phase: 'shown', index: 0, inView: true } );
	assert.equal( visibleIndex( s ), 0 );
	s = cardReducer( s, { type: 'LEAVE_SECTION' } );
	assert.equal( s.phase, 'idle' );
	assert.equal( visibleIndex( s ), -1 );
	s = cardReducer( s, { type: 'ENTER_SECTION' } );
	assert.equal( visibleIndex( s ), 0, 'vào lại giữ index cũ' );
} );

test( 'đổi index khi đang hiện: card mới hiện', () => {
	const s = run( [ { type: 'ENTER_SECTION', index: 0 }, { type: 'INDEX_CHANGED', index: 2 } ] );
	assert.equal( visibleIndex( s ), 2 );
} );

test( 'dismiss chỉ có hiệu lực cho điểm dừng hiện tại', () => {
	let s = run( [ { type: 'ENTER_SECTION', index: 1 }, { type: 'DISMISS' } ] );
	assert.equal( s.phase, 'dismissed' );
	assert.equal( visibleIndex( s ), -1 );
	s = cardReducer( s, { type: 'INDEX_CHANGED', index: 2 } );
	assert.equal( s.phase, 'shown' );
	assert.equal( visibleIndex( s ), 2 );
} );

test( 'reopen hiện lại card đã dismiss; reopen khi không dismiss thì không đổi', () => {
	let s = run( [ { type: 'ENTER_SECTION', index: 0 }, { type: 'DISMISS' }, { type: 'REOPEN' } ] );
	assert.equal( visibleIndex( s ), 0 );
	const same = cardReducer( s, { type: 'REOPEN' } );
	assert.equal( same, s );
} );

test( 'không thay đổi thì trả về chính state cũ (===)', () => {
	const s = run( [ { type: 'ENTER_SECTION', index: 0 } ] );
	assert.equal( cardReducer( s, { type: 'INDEX_CHANGED', index: 0 } ), s );
	assert.equal( cardReducer( s, { type: 'ENTER_SECTION', index: 0 } ), s );
	assert.equal( cardReducer( s, { type: 'UNKNOWN' } ), s );
} );

test( 'ngoài section: đổi index không làm hiện card; dismiss không có tác dụng', () => {
	let s = run( [ { type: 'INDEX_CHANGED', index: 3 } ] );
	assert.equal( s.index, 3 );
	assert.equal( visibleIndex( s ), -1 );
	s = cardReducer( s, { type: 'DISMISS' } );
	assert.equal( s.phase, 'idle' );
} );

test( 'cuộn rất nhanh qua nhiều điểm dừng: state cuối chỉ phụ thuộc index cuối (latest-wins)', () => {
	const s = run( [ { type: 'ENTER_SECTION', index: 0 }, { type: 'INDEX_CHANGED', index: 1 }, { type: 'INDEX_CHANGED', index: 2 }, { type: 'INDEX_CHANGED', index: 3 } ] );
	assert.deepEqual( { ...s }, { phase: 'shown', index: 3, inView: true } );
} );
