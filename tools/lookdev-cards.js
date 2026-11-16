/**
 * Card thật trên trang lookdev (bước 7): lấy markup section "Bình minh lan tỏa" của một trang nhóm dịch vụ WordPress (đầu section + dãy
 * card + JSON dữ liệu), dựng lại trong một khung phủ lên canvas với đúng CSS của theme (main.css, home.css, services-cinematic.css), rồi
 * chạy ui/card-view.js + ui/card-machine.js như main.js (dãy hiện khi vào section; chạm khung cảnh để ẩn / hiện lại).
 *
 * Tham số URL: cards=0 (tắt), cardsrc=<đường dẫn trang> (mặc định trang nhóm "nghe-thuat-tao-hinh-guong-mat"), cardanim=1 (khi gui=0
 * vẫn chạy hoạt ảnh hiện dãy; mặc định gui=0 hiện ngay và chờ ảnh tải xong để ảnh chụp ổn định), cardback=<i>|all (lật card i / mọi card
 * ra mặt sau), cardangle=<độ> (giữ card 0 ở góc lật đó: kiểm tra 3D giữa lúc lật), skeleton=1 (bỏ ảnh: chụp khung chờ khi đang tải).
 */
import { gsap } from 'gsap';
import { CONFIG } from '../wp-content/themes/ISHA/assets/js/services-cinematic/config.js';
import { activeIndex } from '../wp-content/themes/ISHA/assets/js/services-cinematic/stops.js';
import { createCardView } from '../wp-content/themes/ISHA/assets/js/services-cinematic/ui/card-view.js';
import { cardReducer, visibleIndex, INITIAL } from '../wp-content/themes/ISHA/assets/js/services-cinematic/ui/card-machine.js';

const DEFAULT_SRC = '../nhom-dich-vu/nghe-thuat-tao-hinh-guong-mat/';

export async function mountCards( { q, state } ) {
	const res = await fetch( q.get( 'cardsrc' ) || DEFAULT_SRC, { cache: 'no-store' } );
	if ( ! res.ok ) {
		throw new Error( `không tải được trang card (${ res.status })` );
	}
	const doc = new DOMParser().parseFromString( await res.text(), 'text/html' );
	const src = doc.querySelector( '[data-isha-svc]' );
	if ( ! src ) {
		throw new Error( 'trang không có section [data-isha-svc]' );
	}
	const data = JSON.parse( src.querySelector( '#isha-svc-data' ).textContent );
	const N = data.services.length;

	// Khung giống trang thật: .isha-home > section.isha-svc.is-enhanced > .isha-svc__stage (sticky 100svh) > đầu section + danh sách.
	const root = document.createElement( 'div' );
	root.className = 'isha-home lookdev-cards';
	const section = document.createElement( 'section' );
	section.className = 'isha-svc is-enhanced';
	section.dataset.tone = 'day';
	section.style.setProperty( '--isha-svc-n', String( N ) );
	const stage = document.createElement( 'div' );
	stage.className = 'isha-svc__stage';
	for ( const sel of [ '.isha-svc__head', '.isha-svc__list' ] ) {
		stage.appendChild( document.importNode( src.querySelector( sel ), true ) );
	}
	// Ảnh card: đường dẫn tuyệt đối từ trang gốc (DOMParser giữ nguyên; importNode không đổi base) — đã là URL đầy đủ của WordPress.
	section.appendChild( stage );
	root.appendChild( section );
	document.body.appendChild( root );

	const cards = Array.from( stage.querySelectorAll( '.isha-card' ) );
	const instant = q.get( 'gui' ) === '0' && q.get( 'cardanim' ) !== '1';
	const imgs = Array.from( stage.querySelectorAll( '.isha-card__img' ) );
	if ( q.get( 'skeleton' ) === '1' ) {
		imgs.forEach( ( img ) => {
			img.removeAttribute( 'srcset' );
			img.removeAttribute( 'src' );
		} );
	} else if ( instant ) {
		// Ảnh chụp: nạp ngay và chờ ảnh xong (tối đa 8 giây) để khung chờ không lẫn vào ảnh duyệt card.
		imgs.forEach( ( img ) => ( img.loading = 'eager' ) );
		await Promise.race( [
			Promise.all( imgs.map( ( img ) => ( img.complete ? null : new Promise( ( r ) => {
				img.addEventListener( 'load', r, { once: true } );
				img.addEventListener( 'error', r, { once: true } );
			} ) ) ) ),
			new Promise( ( r ) => setTimeout( r, 8000 ) ),
		] );
	}
	const view = createCardView( { cards, gsap, reduced: state.reduced } );
	let card = INITIAL;
	let idx = activeIndex( state.progress, N );
	const render = () => view.render( card.index, visibleIndex( card ) >= 0 );
	const dispatch = ( ev ) => {
		const nextState = cardReducer( card, ev );
		if ( nextState !== card ) {
			card = nextState;
			render();
		}
	};
	dispatch( { type: 'ENTER_SECTION', index: idx } );
	if ( instant ) {
		// Ảnh chụp ổn định: tua lần hiện dãy tới cuối, bỏ lượt tia sáng của khung, lật không chuyển tiếp.
		gsap.globalTimeline.getChildren( false, true, true ).forEach( ( t ) => t.progress( 1 ) );
		stage.querySelector( '.isha-svc__list' ).classList.remove( 'is-entering' );
		root.style.setProperty( '--isha-card-flip', '0s' );
	}

	stage.addEventListener( 'pointerdown', ( e ) => {
		if ( e.target.closest( '.isha-svc__list, .isha-svc__arrow' ) ) {
			return;
		}
		if ( card.phase === 'dismissed' ) {
			dispatch( { type: 'REOPEN' } );
		} else if ( ! view.unflipAll() ) {
			dispatch( { type: 'DISMISS' } );
		}
	} );

	const back = q.get( 'cardback' );
	if ( back ) {
		cards.forEach( ( c, i ) => c.classList.toggle( 'is-flipped', back === 'all' || Number( back ) === i ) );
	}
	const angle = parseFloat( q.get( 'cardangle' ) );
	if ( Number.isFinite( angle ) ) {
		const inner = cards[ 0 ].querySelector( '.isha-card__inner' );
		inner.style.transition = 'none';
		inner.style.transform = `rotateY(${ angle }deg)`;
	}

	return {
		N,
		root,
		/** Gọi mỗi khung: đổi điểm dừng theo progress (có trễ biên như scroll.js); dãy card không đổi (đổi điểm dừng hiện lại dãy đã ẩn). */
		update( progress ) {
			const i = activeIndex( progress, N, idx, CONFIG.hysteresis );
			if ( i !== idx ) {
				idx = i;
				dispatch( { type: 'INDEX_CHANGED', index: i } );
			}
		},
		dispatch,
		setVisible( on ) {
			root.style.display = on ? '' : 'none';
		},
	};
}
