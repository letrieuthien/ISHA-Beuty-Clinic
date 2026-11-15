/**
 * Dãy card kiểu coverflow ở trung tâm, điều khiển bởi state machine (latest-wins: mỗi tween dùng overwrite 'auto' nên lệnh mới thay lệnh cũ,
 * không card nào kẹt nửa chừng). Biến đổi áp lên <li> (vị trí trong dãy); <article> bên trong giữ nội dung.
 *
 * Với d = i − active:
 *   d = 0     giữa, scale 1, rõ hẳn, trên cùng;
 *   |d| = 1   lệch ngang d · 0.82 · bề rộng card, lùi sâu 90px, xoay rotateY −d · 20°, scale 0.84, mờ 0.5;
 *   |d| >= 2  trượt xa hơn và ẩn.
 * Tween CONFIG.cardDuration (1.1s) power3.out. Card bị ẩn (dismissed / ngoài section): cả dãy trượt xuống 24px và mờ hết trong
 * CONFIG.cardHideDuration (0.6s).
 * Trợ năng: <li> không active mang aria-hidden, <article> của nó inert (bấm vào vẫn tới <li> vì phần tử inert bị bỏ qua khi xác định đích
 * của sự kiện; main.js bắt bấm trên <li> để cuộn tới điểm dừng đó). Giảm chuyển động: đổi ngay, không tween.
 */
import { CONFIG } from '../config.js';

export function createCardView( { cards, gsap, reduced } ) {
	const items = cards.map( ( c ) => c.closest( 'li' ) || c );
	let active = -1;
	let shown = false;

	function setA11y( i, isActive ) {
		const card = cards[ i ];
		const item = items[ i ];
		card.inert = ! isActive;
		if ( isActive ) {
			item.removeAttribute( 'aria-hidden' );
			card.removeAttribute( 'aria-hidden' );
		} else {
			item.setAttribute( 'aria-hidden', 'true' );
		}
		item.classList.toggle( 'is-active', isActive );
		item.classList.toggle( 'is-side', ! isActive );
	}

	items.forEach( ( item, i ) => {
		setA11y( i, false );
		gsap.set( item, { autoAlpha: 0, x: 0, y: 24, z: 0, rotationY: 0, scale: 0.9, transformOrigin: '50% 50%' } );
	} );

	function layout() {
		const w = items[ 0 ] ? items[ 0 ].offsetWidth : 320;
		const spacing = w * 0.82;
		const dur = reduced ? 0 : CONFIG.cardDuration;
		items.forEach( ( item, i ) => {
			const d = i - active;
			const a = Math.abs( d );
			setA11y( i, shown && d === 0 );
			item.style.zIndex = String( 10 - Math.min( a, 9 ) );
			if ( ! shown ) {
				gsap.to( item, { y: 24, autoAlpha: 0, duration: reduced ? 0 : CONFIG.cardHideDuration, ease: 'power2.in', overwrite: 'auto' } );
				return;
			}
			const side = Math.sign( d );
			gsap.to( item, {
				x: a === 0 ? 0 : side * spacing * ( a === 1 ? 1 : 1.55 ),
				y: 0,
				z: a === 0 ? 0 : -90 * Math.min( a, 2 ),
				rotationY: a === 0 ? 0 : -side * 20,
				scale: a === 0 ? 1 : 0.84,
				autoAlpha: a === 0 ? 1 : a === 1 ? 0.5 : 0,
				duration: dur,
				ease: 'power3.out',
				overwrite: 'auto',
			} );
		} );
	}

	/**
	 * @param {number}  index   Liệu trình đang xem.
	 * @param {boolean} visible Dãy có hiện không (state 'shown').
	 */
	function render( index, visible ) {
		const next = index >= 0 ? index : 0;
		if ( next === active && visible === shown ) {
			return;
		}
		active = next;
		shown = visible && index >= 0;
		layout();
	}

	return {
		render,
		relayout: layout,
		get active() {
			return active;
		},
		get shown() {
			return shown;
		},
		destroy() {
			gsap.killTweensOf( items );
			items.forEach( ( item, i ) => {
				cards[ i ].inert = false;
				item.removeAttribute( 'aria-hidden' );
				item.classList.remove( 'is-active', 'is-side' );
				item.style.zIndex = '';
				gsap.set( item, { clearProps: 'all' } );
			} );
		},
	};
}
