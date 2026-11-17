/**
 * Trợ năng của vòng thẻ (prompt mục 7, bước 6):
 *   - nút tạm dừng / phát dưới vòng (WCAG 2.2.2: nội dung tự chuyển động quá 5 giây): dừng xoay tự động và ánh kim chạy; chỉ hiện khi
 *     vòng tự xoay. Nhãn đổi theo trạng thái (aria-label), không dùng aria-pressed cùng lúc;
 *   - aria-current="true" trên thẻ thật đang ở giữa (giảm chuyển động: thẻ đang chọn);
 *   - vùng aria-live="polite" (ẩn khỏi mắt) đọc tên thẻ khi thẻ được chọn (FOCUSED).
 * Nút và vùng live nằm trong khung .isha-ring (không đổi bố cục quanh vòng); index.js gọi các hàm dưới.
 */

const ICONS = '<svg data-icon="pause" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><rect x="3.5" y="2.5" width="3" height="11" rx="1"/><rect x="9.5" y="2.5" width="3" height="11" rx="1"/></svg>' +
	'<svg data-icon="play" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M4.5 2.8v10.4a.6.6 0 0 0 .9.5l8.2-5.2a.6.6 0 0 0 0-1L5.4 2.3a.6.6 0 0 0-.9.5z"/></svg>';

export const LABELS = { pause: 'Tạm dừng vòng thẻ', play: 'Cho vòng thẻ xoay tiếp' };

/**
 * @param {object} o
 * @param {HTMLElement} o.stage
 * @param {(paused:boolean)=>void} o.onToggle  Người dùng bấm nút.
 * @returns {{setPaused:(p:boolean)=>void, setSpinning:(on:boolean)=>void, setCurrent:(li:Element|null)=>void, announce:(text:string)=>void, destroy:()=>void}}
 */
export function createA11y( { stage, onToggle, labels = LABELS, doc = document } ) {
	let paused = false;
	let current = null;

	const btn = doc.createElement( 'button' );
	btn.type = 'button';
	btn.className = 'isha-ring__pause';
	btn.innerHTML = ICONS;
	const live = doc.createElement( 'div' );
	live.className = 'isha-ring__sr';
	live.setAttribute( 'aria-live', 'polite' );
	live.setAttribute( 'aria-atomic', 'true' );
	stage.append( btn, live );

	const sync = () => {
		btn.setAttribute( 'aria-label', paused ? labels.play : labels.pause );
		stage.classList.toggle( 'is-user-paused', paused );
	};
	const onClick = () => {
		paused = ! paused;
		sync();
		onToggle( paused );
	};
	btn.addEventListener( 'click', onClick );
	sync();

	return {
		setPaused( p ) {
			paused = !! p;
			sync();
		},
		setSpinning( on ) {
			btn.hidden = ! on;
		},
		setCurrent( li ) {
			if ( li === current ) {
				return;
			}
			current?.removeAttribute( 'aria-current' );
			li?.setAttribute( 'aria-current', 'true' );
			current = li;
		},
		/** Đọc text (đặt lại sau một nhịp để trình đọc màn hình đọc cả khi trùng tên lần trước). */
		announce( text ) {
			live.textContent = '';
			setTimeout( () => ( live.textContent = text ), 50 );
		},
		destroy() {
			btn.removeEventListener( 'click', onClick );
			current?.removeAttribute( 'aria-current' );
			btn.remove();
			live.remove();
		},
	};
}
