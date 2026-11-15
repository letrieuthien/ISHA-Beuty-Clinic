/**
 * Nguồn sự thật duy nhất của section: progress (thô, từ ScrollTrigger), smoothed (đã làm mượt trong vòng render), index đang xem, trạng thái
 * card, chế độ/tầng chất lượng. Các hệ thống chỉ ĐỌC store và gửi sự kiện qua các hàm của store; không ghi chéo lẫn nhau.
 */
import { activeIndex } from './stops.js';
import { cardReducer, INITIAL } from './ui/card-machine.js';
import { CONFIG } from './config.js';

export function createStore( N ) {
	const listeners = { index: new Set(), card: new Set(), progress: new Set() };
	const state = {
		N,
		progress: 0,
		smoothed: 0,      // camera
		sunSmoothed: 0,   // mặt trời, trời, sương (chậm hơn)
		index: -1,
		inView: false,
		card: INITIAL,
		mode: 'STATIC',
		tier: null,
	};

	// smoothed += ( đích − smoothed ) · ( 1 − exp( −k·dt ) ); đích là progress thô hoặc progress ép từ debug.
	function dampKey( key, dt, k ) {
		const target = CONFIG.debugProgress >= 0 ? CONFIG.debugProgress : state.progress;
		const prev = state[ key ];
		state[ key ] += ( target - state[ key ] ) * ( 1 - Math.exp( -k * dt ) );
		if ( Math.abs( target - state[ key ] ) < 1e-5 ) {
			state[ key ] = target;
		}
		return Math.abs( state[ key ] - prev ) > 1e-4;
	}

	function emit( type, payload ) {
		listeners[ type ].forEach( ( fn ) => fn( payload, state ) );
	}

	function dispatch( event ) {
		const next = cardReducer( state.card, event );
		if ( next !== state.card ) {
			const prev = state.card;
			state.card = next;
			emit( 'card', prev );
		}
	}

	return {
		state,
		on( type, fn ) {
			listeners[ type ].add( fn );
			return () => listeners[ type ].delete( fn );
		},
		dispatch,
		// Progress thô; cập nhật index (có trễ) và báo card.
		setProgress( p ) {
			const v = Math.min( 1, Math.max( 0, p ) );
			state.progress = v;
			emit( 'progress', v );
			const i = activeIndex( v, N, state.index, CONFIG.hysteresis );
			if ( i !== state.index ) {
				const prev = state.index;
				state.index = i;
				emit( 'index', prev );
				dispatch( { type: 'INDEX_CHANGED', index: i } );
			}
		},
		setInView( inView ) {
			if ( inView === state.inView ) {
				return;
			}
			state.inView = inView;
			dispatch( inView ? { type: 'ENTER_SECTION', index: state.index } : { type: 'LEAVE_SECTION' } );
		},
		// Làm mượt progress, độc lập với tốc độ khung hình. Trả về true nếu smoothed còn thay đổi đáng kể.
		damp( dt, k ) {
			return dampKey( 'smoothed', dt, k );
		},
		// Làm mượt riêng cho mặt trời / trời / sương: chậm hơn camera để mặt trời trôi theo sau cuộn (CONFIG.damping.sun).
		dampSun( dt ) {
			return dampKey( 'sunSmoothed', dt, CONFIG.damping.sun );
		},
	};
}
