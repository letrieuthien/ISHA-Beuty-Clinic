/**
 * Máy trạng thái tương tác của vòng thẻ (prompt mục 7). REDUCER THUẦN, không DOM (test: tests/card-ring-machine.test.mjs); index.js đọc
 * trạng thái để đặt ω / lớp CSS và thực hiện các hiệu ứng (fx) trả về.
 *
 *   SPINNING ──hover──▶ HOVER_PAUSED ──rời──▶ SPINNING
 *      │                    │
 *      └──click / chạm / Tab / mũi tên──┴──▶ SEEKING ──tới nơi──▶ FOCUSED ──focus-out──▶ SPINNING
 *   (bất kỳ) ──vuốt (cảm ứng)──▶ DRAGGING ──thả──▶ SNAPPING ──tới thẻ gần nhất──▶ WAIT ──RESUME_AFTER_SWIPE_MS──▶ SPINNING
 *
 * Chỉ số thẻ là chỉ số trên vòng (gồm bản sao); ctx.nReal để quy về thẻ thật (bàn phím chỉ đi trên thẻ thật: bản sao aria-hidden).
 * ctx.canSeek = false (vòng lõm ít thẻ, đứng yên canh giữa): chọn thẻ thì FOCUSED tại chỗ, không seek.
 *
 * fx: { type: 'seek', i } đưa thẻ i vào giữa · { type: 'snap', spin } vào góc spin (sau vuốt) · { type: 'open', i } mở trang chi tiết ·
 *     { type: 'focusEl', i } chuyển focus bàn phím sang thẻ i · { type: 'timer', ms } hẹn RESUME · { type: 'clearTimer' }.
 */

export const SPINNING = 'SPINNING';
export const HOVER_PAUSED = 'HOVER_PAUSED';
export const SEEKING = 'SEEKING';
export const FOCUSED = 'FOCUSED';
export const DRAGGING = 'DRAGGING';
export const SNAPPING = 'SNAPPING';
export const WAIT = 'WAIT';

export function initialState() {
	return { mode: SPINNING, hover: -1, focus: -1 };
}

/** Thẻ i đang được chọn (đang tới giữa hoặc đã ở giữa). */
export const isSelected = ( s, i ) => ( s.mode === SEEKING || s.mode === FOCUSED ) && s.focus === i;

function select( s, i, ctx, fx ) {
	if ( isSelected( s, i ) ) {
		return s;
	}
	if ( ctx.canSeek === false ) {
		return { mode: FOCUSED, hover: -1, focus: i };
	}
	fx.push( { type: 'seek', i } );
	return { mode: SEEKING, hover: -1, focus: i };
}

/**
 * @param {{mode:string, hover:number, focus:number}} s
 * @param {{type:string, i?:number, key?:string, spin?:number}} e
 * @param {{nReal?:number, canSeek?:boolean, center?:number, resumeMs?:number}} [ctx]
 * @returns {{s:object, fx:object[]}}
 */
export function reduce( s, e, ctx = {} ) {
	const fx = [];
	let n = s;
	switch ( e.type ) {
		case 'HOVER': // chuột vào thẻ (chỉ khi đang xoay / đang dừng vì hover; SEEKING, FOCUSED, vuốt: bỏ qua)
			if ( s.mode === SPINNING || ( s.mode === HOVER_PAUSED && s.hover !== e.i ) ) {
				n = { mode: HOVER_PAUSED, hover: e.i, focus: -1 };
			}
			break;
		case 'UNHOVER':
			if ( s.mode === HOVER_PAUSED && s.hover === e.i ) {
				n = { mode: SPINNING, hover: -1, focus: -1 };
			}
			break;
		case 'ACTIVATE': // click / chạm vào thẻ (không phải nút "Xem chi tiết": nút là liên kết thật)
			if ( s.mode === DRAGGING ) {
				break;
			}
			if ( s.mode === FOCUSED && s.focus === e.i ) {
				fx.push( { type: 'open', i: e.i } ); // lần hai vào thẻ đang FOCUSED: mở trang chi tiết
				break;
			}
			n = select( s, e.i, ctx, fx );
			break;
		case 'FOCUS_CARD': // focus bàn phím vào liên kết của thẻ (Tab)
			if ( s.mode !== DRAGGING ) {
				n = select( s, e.i, ctx, fx );
			}
			break;
		case 'ARRIVED':
			if ( s.mode === SEEKING ) {
				n = { mode: FOCUSED, hover: -1, focus: s.focus };
			} else if ( s.mode === SNAPPING ) {
				n = { mode: WAIT, hover: -1, focus: -1 };
				fx.push( { type: 'timer', ms: ctx.resumeMs ?? 3000 } );
			}
			break;
		case 'FOCUS_OUT': // click ngoài, Esc, ra khỏi khung nhìn, Tab ra ngoài (trường hợp 5, click thẻ khác, là ACTIVATE)
			if ( s.mode === SEEKING || s.mode === FOCUSED ) {
				n = { mode: SPINNING, hover: -1, focus: -1 };
			}
			break;
		case 'KEY': {
			if ( e.key === 'Escape' ) {
				return reduce( s, { type: 'FOCUS_OUT' }, ctx );
			}
			const step = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
			const nr = ctx.nReal || 1;
			if ( ! step || s.mode === DRAGGING ) {
				break;
			}
			const base = s.focus >= 0 ? s.focus % nr : ( ctx.center ?? 0 ) % nr;
			const next = ( ( ( base + step ) % nr ) + nr ) % nr;
			n = select( s, next, ctx, fx );
			fx.push( { type: 'focusEl', i: next } );
			break;
		}
		case 'DRAG_START':
			n = { mode: DRAGGING, hover: -1, focus: -1 };
			fx.push( { type: 'clearTimer' } );
			break;
		case 'DRAG_END':
			if ( s.mode === DRAGGING ) {
				n = { mode: SNAPPING, hover: -1, focus: -1 };
				fx.push( { type: 'snap', spin: e.spin } );
			}
			break;
		case 'RESUME':
			if ( s.mode === WAIT ) {
				n = { mode: SPINNING, hover: -1, focus: -1 };
			}
			break;
	}
	return { s: n, fx };
}
