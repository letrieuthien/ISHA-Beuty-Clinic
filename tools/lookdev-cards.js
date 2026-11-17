/**
 * Vòng thẻ ánh kim thật trên các trang lookdev (thay dãy card trượt ngang cũ): lấy dữ liệu liệu trình từ JSON #isha-svc-data của một trang
 * nhóm dịch vụ WordPress (lỗi thì dữ liệu giả), dựng markup thẻ hai mặt (giống SSR của bước 7) rồi nâng cấp bằng card-ring/index.js.
 * Dùng chung: tools/lookdev.html (cảnh WebGL thật, nối tia nắng với viền thẻ) và tools/lookdev-card-ring.html (nền poster, đủ núm).
 *
 * Tham số URL: cardsrc=<đường dẫn trang> (mặc định nhóm "nghe-thuat-tao-hinh-guong-mat"), n=<số liệu trình dùng> (n=6 lặp dữ liệu để
 * thử chế độ xoay vòng), hover=<thẻ> (giả lập hover), gov=0|1|2 (ép bậc governor: đủ / tắt phản chiếu / ánh kim chỉ thẻ giữa).
 */
import { RING } from '../wp-content/themes/ISHA/assets/js/card-ring/config.js';
import { createCardRing } from '../wp-content/themes/ISHA/assets/js/card-ring/index.js';

const DEFAULT_SRC = '../nhom-dich-vu/nghe-thuat-tao-hinh-guong-mat/';
const FAKE = [ 'Filler', 'Botox', 'Isha Signature Harmony', 'Nâng cơ chuyên sâu', 'Trẻ hoá vùng mắt', 'Tạo dáng môi' ].map( ( title ) => ( { title, url: '#', image: null, duration: '30 phút', excerpt: 'Mô tả ngắn của liệu trình.' } ) );
const CLOCK = '<svg class="isha-ring__clock" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="6.5"/><path d="M8 4.5V8l2.4 1.6"/></svg>';
const esc = ( t ) => String( t ).replace( /[&<>"]/g, ( c ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ] ) );

/** Liệu trình thật của trang nhóm dịch vụ (hoặc dữ liệu giả). */
export async function loadServices( q ) {
	let list = FAKE;
	try {
		const html = await ( await fetch( q.get( 'cardsrc' ) || DEFAULT_SRC ) ).text();
		const el = new DOMParser().parseFromString( html, 'text/html' ).querySelector( '#isha-svc-data' );
		list = JSON.parse( el.textContent ).services;
	} catch ( err ) {
		console.warn( '[lookdev] dùng dữ liệu giả', err );
	}
	const n = parseInt( q.get( 'n' ), 10 );
	if ( n > 0 ) {
		const out = [];
		for ( let i = 0; i < Math.min( 6, n ); i++ ) {
			out.push( list[ i % list.length ] );
		}
		list = out;
	}
	return list;
}

/** Markup một thẻ hai mặt (giống SSR của bước 7): khung viền có lớp ánh kim chạy (__glint), điểm sáng của tia nắng (__hit trong khung, __flare ngoài khung). */
export function cardHTML( sv, k ) {
	const img = sv.image ? `<img class="isha-ring__img" src="${ esc( sv.image.src ) }"${ sv.image.srcset ? ` srcset="${ esc( sv.image.srcset ) }" sizes="220px"` : '' } width="${ sv.image.w }" height="${ sv.image.h }" alt="" decoding="async"${ k > 2 ? ' loading="lazy"' : '' }>` : '';
	const meta = sv.duration ? `<div class="isha-ring__meta"><p class="isha-ring__meta-label">${ CLOCK }<span>Thời gian liệu trình</span></p><p class="isha-ring__meta-value">${ esc( sv.duration ) }</p></div>` : '';
	const desc = sv.excerpt ? `<p class="isha-ring__desc">${ esc( sv.excerpt ) }</p>` : '';
	return `<li class="isha-ring__card"><div class="isha-ring__flip">` +
		`<div class="isha-ring__side isha-ring__side--front"><div class="isha-ring__frame"><span class="isha-ring__glint"></span><span class="isha-ring__hit"></span><div class="isha-ring__face">${ img }<h3 class="isha-ring__title">${ esc( sv.title ) }</h3></div></div><span class="isha-ring__flare"></span></div>` +
		`<div class="isha-ring__side isha-ring__side--back"><div class="isha-ring__frame"><span class="isha-ring__glint"></span><span class="isha-ring__hit"></span><div class="isha-ring__face isha-ring__face--back">${ meta }${ desc }<a class="isha-ring__more" href="${ esc( sv.url ) }">Xem chi tiết</a></div></div></div>` +
		`</div></li>`;
}

/**
 * Dựng vòng thẻ trong host (vòng là con cuối của host; vị trí do CSS của trang). reduced bỏ trống: theo prefers-reduced-motion.
 *
 * @returns {Promise<{ring: object, stage: HTMLElement, services: object[]}>}
 */
export async function mountRing( { host, q, reduced } ) {
	const services = await loadServices( q );
	const stage = document.createElement( 'div' );
	stage.className = 'isha-ring';
	stage.innerHTML = `<ul class="isha-ring__ring" role="list">${ services.map( cardHTML ).join( '' ) }</ul>`;
	host.appendChild( stage );
	const govQ = parseInt( q.get( 'gov' ), 10 );
	const ring = createCardRing( { stage, list: stage.firstElementChild, cfg: RING, reduced, gov: Number.isFinite( govQ ) ? govQ : undefined } );
	const hoverQ = parseInt( q.get( 'hover' ), 10 );
	if ( Number.isFinite( hoverQ ) ) {
		requestAnimationFrame( () => ring.setHover( hoverQ ) );
	}
	return { ring, stage, services };
}
