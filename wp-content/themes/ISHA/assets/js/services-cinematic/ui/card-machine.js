/**
 * State machine của card liệu trình (reducer THUẦN, không DOM: chạy được bằng node --test).
 *
 * State: { phase: 'idle' | 'shown' | 'dismissed', index: -1..N-1, inView: boolean }
 * Sự kiện:
 *   { type: 'ENTER_SECTION', index }  vào section: hiện card của index hiện tại
 *   { type: 'LEAVE_SECTION' }         ra khỏi section: ẩn card (idle)
 *   { type: 'INDEX_CHANGED', index }  sang điểm dừng khác: card mới hiện (kể cả đang dismissed: dismiss chỉ có hiệu lực cho điểm dừng hiện tại)
 *   { type: 'DISMISS' }               bấm ngoài card hoặc Esc: ẩn card của điểm dừng hiện tại
 *   { type: 'REOPEN' }                bấm nhãn thời điểm hoặc Enter trên stage: hiện lại
 * Không có thay đổi thì trả về CHÍNH state cũ (so sánh bằng ===) để view bỏ qua.
 */

export const INITIAL = Object.freeze( { phase: 'idle', index: -1, inView: false } );

function next( state, patch ) {
	const merged = { ...state, ...patch };
	if ( merged.phase === state.phase && merged.index === state.index && merged.inView === state.inView ) {
		return state;
	}
	return Object.freeze( merged );
}

export function cardReducer( state = INITIAL, event = {} ) {
	switch ( event.type ) {
		case 'ENTER_SECTION': {
			const index = Number.isInteger( event.index ) && event.index >= 0 ? event.index : state.index;
			return next( state, { inView: true, phase: index >= 0 ? 'shown' : 'idle', index } );
		}
		case 'LEAVE_SECTION':
			return next( state, { inView: false, phase: 'idle' } );
		case 'INDEX_CHANGED': {
			if ( ! Number.isInteger( event.index ) || event.index < 0 || event.index === state.index ) {
				return state;
			}
			return next( state, { index: event.index, phase: state.inView ? 'shown' : 'idle' } );
		}
		case 'DISMISS':
			return state.phase === 'shown' ? next( state, { phase: 'dismissed' } ) : state;
		case 'REOPEN':
			return state.inView && state.phase === 'dismissed' ? next( state, { phase: 'shown' } ) : state;
		default:
			return state;
	}
}

/**
 * Card nào đang được hiển thị (-1 nếu không có).
 *
 * @param {object} state
 */
export function visibleIndex( state ) {
	return state.inView && state.phase === 'shown' && state.index >= 0 ? state.index : -1;
}
