/**
 * Card thật trên trang lookdev (bước 7, docs/isha-lookdev-prompt-v3.md mục 15): lấy markup section "Bình minh lan tỏa" của một trang nhóm
 * dịch vụ WordPress (đầu section + danh sách card + JSON dữ liệu), dựng lại trong một khung phủ lên canvas với đúng CSS của theme
 * (main.css, home.css, services-cinematic.css), rồi chạy ui/card-view.js + ui/card-machine.js theo progress của lookdev (giống main.js).
 *
 * Tham số URL: cards=0 (tắt), cardsrc=<đường dẫn trang> (mặc định trang nhóm "nghe-thuat-tao-hinh-guong-mat"), cardanim=1 (khi gui=0
 * vẫn chạy hoạt ảnh lần hiện đầu; mặc định gui=0 hiện ngay để ảnh chụp ổn định), cardflip=1 | <giây> (lật sang card kế; số giây: dừng
 * hình tại mốc đó để chụp giữa lúc lật).
 */
import { gsap } from 'gsap';
import { CONFIG } from '../wp-content/themes/ISHA/assets/js/services-cinematic/config.js';
import { computeStops, activeIndex } from '../wp-content/themes/ISHA/assets/js/services-cinematic/stops.js';
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
	const dayLabel = stage.querySelector( '.isha-svc__daylabel' );
	const stops = computeStops( N, CONFIG.stops );
	const instant = q.get( 'gui' ) === '0' && q.get( 'cardanim' ) !== '1';
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
		if ( dayLabel && card.index >= 0 ) {
			dayLabel.textContent = stops[ card.index ].label;
		}
	};
	dispatch( { type: 'ENTER_SECTION', index: idx } );
	if ( instant ) {
		// Ảnh chụp ổn định: tua lần hiện đầu tới cuối (kể cả vệt sáng viền); các lần đổi card sau vẫn chạy hoạt ảnh thật.
		gsap.globalTimeline.getChildren( false, true, true ).forEach( ( t ) => t.progress( 1 ) );
		cards.forEach( ( c ) => c.classList.remove( 'is-sweep' ) );
	}

	stage.addEventListener( 'pointerdown', ( e ) => {
		if ( dayLabel && dayLabel.contains( e.target ) ) {
			dispatch( { type: 'REOPEN' } );
		} else if ( ! e.target.closest( '.isha-svc__item' ) ) {
			dispatch( { type: 'DISMISS' } );
		}
	} );

	// cardflip=1: lật sang card kế (chạy thật). cardflip=<giây> (ví dụ 0.3): lật rồi DỪNG HÌNH tại mốc đó trên timeline (SwiftShader chụp
	// mất vài giây một khung nên không bắt được giữa lúc lật bằng --times).
	const flip = parseFloat( q.get( 'cardflip' ) );
	if ( flip > 0 ) {
		const before = new Set( gsap.globalTimeline.getChildren( false, false, true ) );
		dispatch( { type: 'INDEX_CHANGED', index: Math.min( N - 1, idx + 1 ) } );
		if ( q.get( 'cardflip' ) !== '1' ) {
			gsap.globalTimeline.getChildren( false, false, true ).filter( ( t ) => ! before.has( t ) ).forEach( ( t ) => t.pause( flip ) );
		}
	}

	return {
		N,
		root,
		/** Gọi mỗi khung: đổi card theo progress (có trễ biên như scroll.js). */
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
