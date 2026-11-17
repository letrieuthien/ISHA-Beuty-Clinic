/**
 * Hiện / ẩn vòng thẻ của section (reducer THUẦN, không DOM: chạy được bằng node --test). Vòng không gắn với điểm dừng của khung cảnh.
 *
 * State: { phase: 'idle' | 'shown' | 'dismissed', inView: boolean }
 * Sự kiện:
 *   { type: 'ENTER_SECTION' }  bình minh bắt đầu (section phủ kín màn hình, autoplay.js): hiện vòng
 *   { type: 'LEAVE_SECTION' }  section khuất hẳn: ẩn (idle)
 *   { type: 'DISMISS' }        Esc hoặc chạm khung cảnh: ẩn vòng để ngắm cảnh
 *   { type: 'REOPEN' }         Enter trên stage hoặc chạm lại khung cảnh: hiện lại
 * Không có thay đổi thì trả về CHÍNH state cũ (so sánh bằng ===) để view bỏ qua.
 */

export const INITIAL = Object.freeze( { phase: 'idle', inView: false } );

function next( state, patch ) {
	const merged = { ...state, ...patch };
	if ( merged.phase === state.phase && merged.inView === state.inView ) {
		return state;
	}
	return Object.freeze( merged );
}

export function cardReducer( state = INITIAL, event = {} ) {
	switch ( event.type ) {
		case 'ENTER_SECTION':
			return next( state, { inView: true, phase: 'shown' } );
		case 'LEAVE_SECTION':
			return next( state, { inView: false, phase: 'idle' } );
		case 'DISMISS':
			return state.phase === 'shown' ? next( state, { phase: 'dismissed' } ) : state;
		case 'REOPEN':
			return state.inView && state.phase === 'dismissed' ? next( state, { phase: 'shown' } ) : state;
		default:
			return state;
	}
}

/** Vòng thẻ đang hiện. */
export const isShown = ( state ) => state.inView && state.phase === 'shown';
