/**
 * Dãy card trượt ngang ở chân núi (thay card đơn của đặc tả v3 mục 15 theo yêu cầu khách). MỌI card cùng kích thước và cùng hành vi, không
 * có card "đang xem": index cuộn chỉ điều khiển khung cảnh (mặt trời), dãy card hiện / ẩn theo state của card-machine (vào section, Esc /
 * bấm ra ngoài để ẩn, nhãn thời điểm / Enter để hiện lại).
 *
 * - Hiện: các <li> dâng lên CONFIG.card.lift px và rõ dần (CONFIG.card.enter giây, power3.out), lệch pha CONFIG.card.stagger; khung mạ
 *   vàng có một lượt tia sáng (.is-entering trên danh sách, CSS). Ẩn: chìm xuống và mờ (CONFIG.card.hide giây), đóng mọi card đang lật;
 *   focus đang nằm trong dãy thì trả về stage. Latest-wins: mỗi lần đổi huỷ timeline cũ, tween tiếp từ trạng thái hiện tại.
 * - Lật: CSS (rê chuột trên thiết bị có chuột, focus bàn phím vào nút "Xem chi tiết"). Thiết bị cảm ứng ((hover: none)): chạm card để
 *   lật / lật lại, mỗi lần một card; chạm nút "Xem chi tiết" khi card chưa lật thì chỉ lật (nút đang nằm ở mặt khuất).
 * - Ảnh: chưa tải xong thì .is-loading trên .isha-card__media (khung chờ vector, mặt trời thở), xong thì .is-loaded (ảnh hiện dần); lỗi
 *   thì giữ khung chờ tĩnh. Ảnh vẫn loading="lazy" của trình duyệt (cả trong khung cuộn ngang).
 * - Dãy dài hơn khung: .is-overflow / .is-start / .is-end trên danh sách (mép mờ dần), hai nút mũi tên (tạo ở đây, CSS chỉ hiện trên
 *   thiết bị có chuột) cuộn theo từng nhóm card; ở đầu / cuối thì aria-disabled.
 *
 * Không đặt transform / opacity lên phần tử lật (.isha-card__inner) hay cha có preserve-3d; GSAP chỉ chạm <li>.
 */
import { CONFIG } from '../config.js';

const CHEVRON = {
	prev: '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M10 3 5 8l5 5"/></svg>',
	next: '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="m6 3 5 5-5 5"/></svg>',
};

export function createCardView( { cards, gsap, reduced, win = window } ) {
	const doc = win.document;
	const items = cards.map( ( c ) => c.closest( 'li' ) || c );
	const list = items[ 0 ].parentNode;
	const stage = list.closest ? list.closest( '.isha-svc__stage' ) : null;
	const coarse = win.matchMedia ? win.matchMedia( '(hover: none)' ) : { matches: false };
	const cleanups = [];
	let shown = false;
	let tl = null;
	let enterTimer = 0;

	const on = ( el, type, fn, opts ) => {
		el.addEventListener( type, fn, opts );
		cleanups.push( () => el.removeEventListener( type, fn, opts ) );
	};

	// --- Lật -----------------------------------------------------------------------------------------------------------------------------
	function unflipAll( except = null ) {
		let any = false;
		cards.forEach( ( c ) => {
			if ( c !== except && c.classList.contains( 'is-flipped' ) ) {
				c.classList.remove( 'is-flipped' );
				any = true;
			}
		} );
		return any;
	}

	on( list, 'click', ( e ) => {
		if ( ! coarse.matches ) {
			return;
		}
		const card = e.target.closest( '.isha-card' );
		if ( ! card ) {
			return;
		}
		const flipped = card.classList.contains( 'is-flipped' );
		if ( e.target.closest( 'a' ) && flipped ) {
			return; // mặt sau đang hiện: để liên kết chạy
		}
		e.preventDefault();
		unflipAll( card );
		card.classList.toggle( 'is-flipped', ! flipped );
	} );

	// --- Ảnh: khung chờ khi đang tải -------------------------------------------------------------------------------------------------------
	cards.forEach( ( card ) => {
		const media = card.querySelector( '.isha-card__media' );
		const img = media ? media.querySelector( 'img' ) : null;
		if ( ! img ) {
			return;
		}
		const done = ( ok ) => {
			media.classList.remove( 'is-loading' );
			media.classList.toggle( 'is-loaded', ok );
		};
		if ( img.complete && img.naturalWidth > 0 ) {
			done( true );
			return;
		}
		media.classList.add( 'is-loading' );
		on( img, 'load', () => done( true ), { once: true } );
		on( img, 'error', () => done( false ), { once: true } );
		cleanups.push( () => media.classList.remove( 'is-loading', 'is-loaded' ) );
	} );

	// --- Tràn khung: mép mờ, nút mũi tên ------------------------------------------------------------------------------------------------
	if ( ! list.id ) {
		list.id = 'isha-svc-list';
	}
	const arrows = {};
	for ( const dir of [ 'prev', 'next' ] ) {
		const b = doc.createElement( 'button' );
		b.type = 'button';
		b.className = `isha-svc__arrow isha-svc__arrow--${ dir }`;
		b.setAttribute( 'aria-label', dir === 'prev' ? 'Các liệu trình trước' : 'Các liệu trình tiếp theo' );
		b.setAttribute( 'aria-controls', list.id );
		b.innerHTML = CHEVRON[ dir ];
		b.hidden = true;
		on( b, 'click', () => {
			if ( b.getAttribute( 'aria-disabled' ) === 'true' ) {
				return;
			}
			const w = items[ 0 ].offsetWidth;
			const gap = parseFloat( win.getComputedStyle( list ).columnGap ) || 0;
			const per = Math.max( 1, Math.floor( list.clientWidth / ( w + gap ) ) - 1 );
			list.scrollBy( { left: ( dir === 'prev' ? -1 : 1 ) * per * ( w + gap ), behavior: reduced ? 'auto' : 'smooth' } );
		} );
		list.parentNode.insertBefore( b, list.nextSibling );
		arrows[ dir ] = b;
		cleanups.push( () => b.remove() );
	}

	function measure() {
		const max = list.scrollWidth - list.clientWidth;
		const overflow = max > 1;
		const atStart = list.scrollLeft <= 1;
		const atEnd = list.scrollLeft >= max - 1;
		list.classList.toggle( 'is-overflow', overflow );
		list.classList.toggle( 'is-start', atStart );
		list.classList.toggle( 'is-end', atEnd );
		arrows.prev.hidden = ! overflow || ! shown;
		arrows.next.hidden = ! overflow || ! shown;
		arrows.prev.setAttribute( 'aria-disabled', String( atStart ) );
		arrows.next.setAttribute( 'aria-disabled', String( atEnd ) );
	}
	let raf = 0;
	const schedule = () => {
		if ( ! raf ) {
			raf = win.requestAnimationFrame( () => {
				raf = 0;
				measure();
			} );
		}
	};
	on( list, 'scroll', schedule, { passive: true } );
	if ( win.ResizeObserver ) {
		const ro = new win.ResizeObserver( schedule );
		ro.observe( list );
		cleanups.push( () => ro.disconnect() );
	} else {
		on( win, 'resize', schedule );
	}
	cleanups.push( () => win.cancelAnimationFrame( raf ) );

	// --- Hiện / ẩn cả dãy ---------------------------------------------------------------------------------------------------------------
	gsap.set( items, { autoAlpha: 0, y: CONFIG.card.lift } );

	function render( index, visible ) {
		const want = !! visible;
		if ( want === shown ) {
			return;
		}
		shown = want;
		if ( tl ) {
			tl.kill();
			tl = null;
		}
		win.clearTimeout( enterTimer );
		list.classList.remove( 'is-entering' );
		const c = CONFIG.card;
		if ( ! shown ) {
			unflipAll();
			if ( stage && list.contains( doc.activeElement ) ) {
				stage.focus( { preventScroll: true } );
			}
		}
		measure();
		if ( reduced ) {
			gsap.set( items, shown ? { autoAlpha: 1, y: 0 } : { autoAlpha: 0, y: c.lift } );
			return;
		}
		if ( shown ) {
			list.classList.add( 'is-entering' );
			enterTimer = win.setTimeout( () => list.classList.remove( 'is-entering' ), ( 2.8 + items.length * 0.12 ) * 1000 );
		}
		tl = gsap.timeline( { onComplete: () => ( tl = null ) } );
		tl.to( items, shown
			? { autoAlpha: 1, y: 0, duration: c.enter, ease: 'power3.out', stagger: c.stagger }
			: { autoAlpha: 0, y: c.lift * 0.5, duration: c.hide, ease: 'power2.in', stagger: c.stagger * 0.3 } );
	}

	return {
		render,
		/** Lật lại mọi card; trả về true nếu có card đang lật (main.js: chạm ra ngoài lúc đó chỉ lật lại, không ẩn dãy). */
		unflipAll: () => unflipAll(),
		get shown() {
			return shown;
		},
		destroy() {
			if ( tl ) {
				tl.kill();
			}
			win.clearTimeout( enterTimer );
			cleanups.reverse().forEach( ( fn ) => fn() );
			unflipAll();
			list.classList.remove( 'is-entering', 'is-overflow', 'is-start', 'is-end' );
			gsap.set( items, { clearProps: 'opacity,visibility,transform' } );
		},
	};
}
