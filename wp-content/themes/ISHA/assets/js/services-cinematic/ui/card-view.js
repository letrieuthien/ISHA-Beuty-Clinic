/**
 * Card của section (đặc tả v3 mục 15). ĐÚNG MỘT card hiển thị khi đã yên: card không active mang display: none sau khi hoạt ảnh "ra" kết
 * thúc (không chỉ opacity 0; bản cũ là dãy coverflow ba card hiện cùng lúc). Có test: tests/card-view.test.mjs.
 *
 * Lật: rotateY từ ∓angle (62°, không phải 90°) cộng y 18px và scale 0.97 (perspective ở <li>, backface ẩn). Vào CONFIG.card.in
 * (0.7 giây) power3.out; ra CONFIG.card.out (0.42 giây) power2.in; card mới trễ CONFIG.card.stagger (0.14 giây). Hướng theo chiều đổi liệu
 * trình: đi tới thì card cũ lật sang +62° và card mới vào từ −62°, cuộn ngược thì đảo lại. Chỉ transform và opacity (không animate blur).
 *
 * Transform và opacity đặt trên CHÍNH <article> mang backdrop-filter, <li> chỉ giữ display: Chrome coi tổ tiên có opacity < 1, clip-path,
 * filter hay will-change: opacity là "backdrop root", khi đó lớp kính chỉ thấy nội dung bên trong tổ tiên ấy (blur mất, kính thành trong).
 *
 * Latest-wins: mỗi lần render huỷ timeline đang chạy rồi dựng timeline mới tính TỪ TRẠNG THÁI HIỆN TẠI của từng card (không nhảy): card
 * đang vào dở mà người dùng cuộn ngược thì nó quay ra; card đang ra dở mà trở thành đích thì nó quay vào. Cuộn nhanh qua nhiều điểm dừng:
 * card trung gian chưa kịp hiện (vẫn opacity 0 trong khoảng trễ stagger) nên bị bỏ qua, chỉ card cũ ra và card đích vào.
 *
 * Vệt sáng quét dọc viền ánh kim MỘT lần khi card lật vào (class .is-sweep trong CONFIG.card.sweep giây, CSS dịch background-position).
 * Ảnh: chỉ nạp ảnh của card hiện tại và hai card kề (loading = eager); các card khác giữ loading = lazy (display: none nên chưa tải).
 *
 * Trợ năng: card không active mang aria-hidden (ở li) và inert (ở article). Giảm chuyển động: đổi ngay, không tween, không quét sáng.
 */
import { CONFIG } from '../config.js';

export function createCardView( { cards, gsap, reduced } ) {
	const items = cards.map( ( c ) => ( c.closest && c.closest( 'li' ) ) || c );
	let active = -1;
	let shown = false;
	let tl = null;
	const sweepTimers = new Map();

	const isDisplayed = ( i ) => items[ i ].style.display !== 'none';

	function setA11y( i, on ) {
		cards[ i ].inert = ! on;
		if ( on ) {
			items[ i ].removeAttribute( 'aria-hidden' );
		} else {
			items[ i ].setAttribute( 'aria-hidden', 'true' );
		}
		items[ i ].classList.toggle( 'is-active', on );
	}

	function hideNow( i ) {
		items[ i ].style.display = 'none';
		gsap.set( cards[ i ], { autoAlpha: 0, rotationY: 0, y: 0, scale: 1 } );
		setA11y( i, false );
		clearTimeout( sweepTimers.get( i ) );
		cards[ i ].classList.remove( 'is-sweep' );
	}

	function preload( i ) {
		for ( const k of [ i - 1, i, i + 1 ] ) {
			const img = cards[ k ] ? cards[ k ].querySelector( 'img' ) : null;
			if ( img && img.loading !== 'eager' ) {
				img.loading = 'eager';
			}
		}
	}

	function sweep( i ) {
		const card = cards[ i ];
		card.classList.remove( 'is-sweep' );
		void card.offsetWidth; // khởi động lại hoạt ảnh CSS
		card.classList.add( 'is-sweep' );
		clearTimeout( sweepTimers.get( i ) );
		sweepTimers.set( i, setTimeout( () => card.classList.remove( 'is-sweep' ), CONFIG.card.sweep * 1000 + 100 ) );
	}

	// Khi mọi hoạt ảnh đã xong: chỉ card active (nếu đang hiện) còn trong luồng hiển thị.
	function settle() {
		tl = null;
		items.forEach( ( item, i ) => {
			if ( ! ( shown && i === active ) ) {
				hideNow( i );
			}
		} );
	}

	items.forEach( ( item, i ) => hideNow( i ) );

	/**
	 * @param {number}  index   Liệu trình đang xem.
	 * @param {boolean} visible Card có hiện không (state 'shown').
	 */
	function render( index, visible ) {
		const next = Math.max( 0, Math.min( items.length - 1, index ) );
		const wantShown = !! visible && index >= 0;
		if ( next === active && wantShown === shown ) {
			return;
		}
		const dir = active < 0 || next >= active ? 1 : -1;
		active = next;
		shown = wantShown;
		if ( tl ) {
			tl.kill();
			tl = null;
		}

		if ( reduced ) {
			settle();
			if ( shown ) {
				items[ next ].style.display = '';
				gsap.set( cards[ next ], { autoAlpha: 1, rotationY: 0, y: 0, scale: 1 } );
				setA11y( next, true );
				preload( next );
			}
			return;
		}

		const c = CONFIG.card;
		// Card ra: mọi card đang trong luồng hiển thị trừ card đích (khi đang hiện).
		const leaving = items.map( ( item, i ) => i ).filter( ( i ) => isDisplayed( i ) && ! ( shown && i === next ) );
		if ( ! leaving.length && ! shown ) {
			return;
		}
		tl = gsap.timeline( { onComplete: settle } );
		leaving.forEach( ( i ) => {
			setA11y( i, false );
			tl.to( cards[ i ], { rotationY: c.angle * dir, y: -c.lift * 0.6, scale: c.scale, autoAlpha: 0, duration: c.out, ease: 'power2.in' }, 0 );
		} );
		if ( shown ) {
			const item = items[ next ];
			const fromHidden = ! isDisplayed( next );
			if ( fromHidden ) {
				item.style.display = '';
				gsap.set( cards[ next ], { autoAlpha: 0, rotationY: -c.angle * dir, y: c.lift, scale: c.scale } );
			}
			setA11y( next, true );
			preload( next );
			// Card đang ra dở mà thành đích lại: quay vào ngay, không chờ stagger.
			const at = fromHidden && leaving.length ? c.stagger : 0;
			const vars = { rotationY: 0, y: 0, scale: 1, autoAlpha: 1, duration: c.in, ease: 'power3.out' };
			if ( fromHidden ) {
				vars.onStart = () => sweep( next );
			}
			tl.to( cards[ next ], vars, at );
		}
	}

	return {
		render,
		destroy() {
			if ( tl ) {
				tl.kill();
			}
			sweepTimers.forEach( ( t ) => clearTimeout( t ) );
			items.forEach( ( item, i ) => {
				cards[ i ].inert = false;
				cards[ i ].classList.remove( 'is-sweep' );
				item.removeAttribute( 'aria-hidden' );
				item.classList.remove( 'is-active' );
				item.style.display = '';
				gsap.set( cards[ i ], { clearProps: 'transform,opacity,visibility' } );
			} );
		},
	};
}
