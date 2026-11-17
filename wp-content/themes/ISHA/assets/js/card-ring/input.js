/**
 * Đầu vào của vòng thẻ (prompt mục 7): chuyển sự kiện DOM thành sự kiện của máy trạng thái (machine.js) và thao tác vuốt.
 *   - Chuột: hover thẻ (HOVER / UNHOVER), click thẻ (ACTIVATE). Nút "Xem chi tiết" là liên kết thật: click vào nó đi thẳng, không qua máy.
 *   - Cảm ứng / bút: chạm = ACTIVATE (không có hover); vuốt ngang quay vòng bằng tay (ngưỡng TAP_SLOP_PX phân biệt chạm / vuốt; vuốt dọc
 *     để trình duyệt cuộn trang: touch-action pan-y), thả tay giữ quán tính (vận tốc 100 ms cuối).
 *   - Bàn phím: Tab vào liên kết của thẻ = FOCUS_CARD; ← / → = KEY (seek thẻ trước / sau); Esc = KEY Escape (cả khi focus DOM ở ngoài).
 *   - Focus-out: pointerdown ngoài mọi thẻ của vòng; Esc; khung còn hiện < 20% (IntersectionObserver 0.2); focus bàn phím rời thẻ đang chọn
 *     ra ngoài. Click thẻ khác đi qua ACTIVATE (seek thẳng sang thẻ đó).
 * Lắng nghe uỷ quyền trên khung (thẻ được dựng lại khi đổi kích thước không cần gắn lại).
 */

const CARD = '.isha-ring__card';
const MORE = '.isha-ring__more';

/**
 * @param {object}   o
 * @param {HTMLElement} o.stage    .isha-ring.
 * @param {HTMLElement} o.pad      Lớp nhận vuốt phủ phần lõi của khung (sau thẻ).
 * @param {(li:Element)=>number} o.indexOf  Chỉ số trên vòng của thẻ (−1 nếu không thuộc vòng).
 * @param {(e:object)=>void} o.dispatch
 * @param {()=>boolean} o.hasSelection  Có thẻ đang SEEKING / FOCUSED.
 * @param {()=>number}  o.selected      Chỉ số thẻ đang chọn (−1 nếu không).
 * @param {{can:()=>boolean, begin:()=>void, move:(dx:number)=>void, end:(vx:number)=>void}} o.drag  Vuốt: dx (px) từ lúc bắt đầu, vx (px/s).
 * @param {object}   o.cfg  RING (TAP_SLOP_PX).
 * @returns {{destroy:()=>void}}
 */
export function bindInput( { stage, pad, indexOf, dispatch, hasSelection, selected, drag, cfg, win = window, doc = document } ) {
	const off = [];
	const on = ( el, type, fn, opt ) => {
		el.addEventListener( type, fn, opt );
		off.push( () => el.removeEventListener( type, fn, opt ) );
	};
	const cardOf = ( el ) => ( el && el.closest ? el.closest( CARD ) : null );
	const inRing = ( li ) => li && stage.contains( li );
	let suppressClick = false;

	// Hover (chỉ chuột): uỷ quyền pointerover / pointerout, bỏ qua di chuyển giữa các phần tử con của cùng một thẻ.
	on( stage, 'pointerover', ( e ) => {
		const li = cardOf( e.target );
		if ( e.pointerType === 'mouse' && inRing( li ) && li !== cardOf( e.relatedTarget ) ) {
			dispatch( { type: 'HOVER', i: indexOf( li ) } );
		}
	} );
	on( stage, 'pointerout', ( e ) => {
		const li = cardOf( e.target );
		if ( e.pointerType === 'mouse' && inRing( li ) && li !== cardOf( e.relatedTarget ) ) {
			dispatch( { type: 'UNHOVER', i: indexOf( li ) } );
		}
	} );

	// Click / chạm thẻ. Liên kết "Xem chi tiết" chạy mặc định (mở trang). Click ngay sau khi vuốt: bỏ.
	on( stage, 'click', ( e ) => {
		const li = cardOf( e.target );
		if ( ! inRing( li ) ) {
			return;
		}
		if ( suppressClick ) {
			e.preventDefault();
			return;
		}
		if ( e.target.closest( MORE ) ) {
			return;
		}
		e.preventDefault();
		dispatch( { type: 'ACTIVATE', i: indexOf( li ) } );
	} );

	// Focus-out 1: chạm / click ở bất kỳ đâu ngoài các thẻ của vòng (pha capture: trước mọi xử lý khác).
	on( doc, 'pointerdown', ( e ) => {
		if ( hasSelection() && ! inRing( cardOf( e.target ) ) ) {
			dispatch( { type: 'FOCUS_OUT' } );
		}
	}, true );

	// Bàn phím. ← / → chỉ khi focus DOM đang trong vòng; Esc ở bất kỳ đâu khi có thẻ đang chọn (focus-out 2).
	// preventDefault: phím đã thuộc về vòng (trang chứa vòng, ví dụ cuộn theo điểm dừng của section, bỏ qua phím đã bị chặn).
	on( stage, 'keydown', ( e ) => {
		if ( e.key === 'ArrowLeft' || e.key === 'ArrowRight' ) {
			e.preventDefault();
			dispatch( { type: 'KEY', key: e.key } );
		} else if ( e.key === 'Escape' && hasSelection() ) {
			e.preventDefault();
			dispatch( { type: 'KEY', key: 'Escape' } );
		}
	} );
	on( doc, 'keydown', ( e ) => {
		if ( e.key === 'Escape' && hasSelection() && ! e.defaultPrevented ) {
			dispatch( { type: 'KEY', key: 'Escape' } );
		}
	} );
	on( stage, 'focusin', ( e ) => {
		const li = cardOf( e.target );
		if ( inRing( li ) && li.getAttribute( 'aria-hidden' ) !== 'true' ) {
			dispatch( { type: 'FOCUS_CARD', i: indexOf( li ) } );
		}
	} );
	// Focus-out 4: focus bàn phím rời thẻ đang chọn ra ngoài thẻ đó (sang thẻ khác: focusin của thẻ kia seek tiếp).
	on( stage, 'focusout', ( e ) => {
		const li = cardOf( e.target );
		if ( inRing( li ) && indexOf( li ) === selected() && cardOf( e.relatedTarget ) !== li && e.relatedTarget ) {
			dispatch( { type: 'FOCUS_OUT' } );
		}
	} );

	// Focus-out 3: khung còn hiện dưới 20%.
	let io = null;
	if ( win.IntersectionObserver ) {
		io = new win.IntersectionObserver( ( entries ) => {
			const en = entries[ entries.length - 1 ];
			if ( en.intersectionRatio < 0.2 && hasSelection() ) {
				dispatch( { type: 'FOCUS_OUT' } );
			}
		}, { threshold: [ 0, 0.2 ] } );
		io.observe( stage );
	}

	// Vuốt (cảm ứng / bút): trên thẻ hoặc lớp pad. Chưa vượt ngưỡng: vẫn là chạm. Dọc trước: nhường trình duyệt cuộn.
	let g = null;
	on( stage, 'pointerdown', ( e ) => {
		if ( e.pointerType === 'mouse' || ! e.isPrimary || ! drag.can() ) {
			return;
		}
		g = { id: e.pointerId, x0: e.clientX, y0: e.clientY, on: false, samples: [ [ e.timeStamp, e.clientX ] ] };
	} );
	on( stage, 'pointermove', ( e ) => {
		if ( ! g || e.pointerId !== g.id ) {
			return;
		}
		const dx = e.clientX - g.x0;
		const dy = e.clientY - g.y0;
		if ( ! g.on ) {
			if ( Math.abs( dy ) > cfg.TAP_SLOP_PX && Math.abs( dy ) >= Math.abs( dx ) ) {
				g = null; // cuộn trang
				return;
			}
			if ( Math.abs( dx ) <= cfg.TAP_SLOP_PX ) {
				return;
			}
			g.on = true;
			g.x0 = e.clientX; // tính từ đây: không nhảy một đoạn bằng ngưỡng
			try {
				pad.setPointerCapture( e.pointerId );
			} catch ( err ) {}
			drag.begin();
		}
		g.samples.push( [ e.timeStamp, e.clientX ] );
		while ( g.samples.length > 2 && e.timeStamp - g.samples[ 0 ][ 0 ] > 100 ) {
			g.samples.shift();
		}
		drag.move( e.clientX - g.x0 );
	} );
	const up = ( e ) => {
		if ( ! g || e.pointerId !== g.id ) {
			return;
		}
		if ( g.on ) {
			const a = g.samples[ 0 ];
			const dt = ( e.timeStamp - a[ 0 ] ) / 1000;
			drag.end( e.type === 'pointercancel' || dt <= 0 ? 0 : ( e.clientX - a[ 1 ] ) / dt );
			suppressClick = true;
			win.setTimeout( () => ( suppressClick = false ), 0 );
		}
		g = null;
	};
	on( stage, 'pointerup', up );
	on( stage, 'pointercancel', up );

	return {
		destroy() {
			off.forEach( ( f ) => f() );
			if ( io ) {
				io.disconnect();
			}
		},
	};
}
