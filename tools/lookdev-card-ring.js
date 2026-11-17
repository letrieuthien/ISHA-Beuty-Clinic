/**
 * Lookdev "Vòng cung ánh kim" (tools/lookdev-card-ring.html). Bước 1: hình học vòng lõm + culling + mask hai mép, thẻ là khối màu.
 * window.__lookdev.ready = true khi đã vẽ (tools/shot.mjs chờ cờ này).
 */
import { RING } from '../wp-content/themes/ISHA/assets/js/card-ring/config.js';
import { createRing, ringCount, slotDeg, project } from '../wp-content/themes/ISHA/assets/js/card-ring/ring.js';

window.__lookdev = { ready: false, errors: [] };
window.addEventListener( 'error', ( e ) => window.__lookdev.errors.push( String( e.message ) ) );

const q = new URLSearchParams( location.search );
const TITLES = [ 'Filler', 'Botox', 'Isha Signature Harmony', 'Nâng cơ chuyên sâu', 'Trẻ hoá vùng mắt', 'Tạo dáng môi' ];
const TONES = [ '--isha-skin', '--isha-golden', '--isha-sunset', '--isha-skin-soft', '--isha-daylight', '--isha-golden' ];
const nReal = Math.max( 1, Math.min( 6, parseInt( q.get( 'n' ), 10 ) || 4 ) );
const state = { spin: parseFloat( q.get( 'spin' ) ) || 0 };

const stage = document.getElementById( 'ring-stage' );
const ringEl = document.getElementById( 'ring' );
const label = document.getElementById( 'label' );
let ring = null;
let total = 0;

/** Bề rộng khung → R, thẻ (prompt mục 9: ≥ 1200 / 768–1199 / < 768). */
function geometryFor( w ) {
	const tier = w >= 1200 ? 'desktop' : w >= 768 ? 'tablet' : 'mobile';
	const R = tier === 'desktop' ? RING.R_DESKTOP : tier === 'tablet' ? RING.R_TABLET : RING.R_MOBILE;
	const cardW = tier === 'desktop' ? RING.CARD_W_DESKTOP : tier === 'tablet' ? RING.CARD_W_TABLET : ( w * RING.CARD_W_MOBILE_VW ) / 100;
	return { tier, R, P: R * RING.P_RATIO, cardW: Math.round( cardW ), cardH: Math.round( cardW * RING.CARD_RATIO ) };
}

function build() {
	const g = geometryFor( window.innerWidth );
	const slot = slotDeg( g.cardW, g.R, RING.GAP );
	const n = ringCount( nReal, slot, RING.CULL_DEG, RING.MIN_CARDS );
	if ( n !== total ) {
		ringEl.textContent = '';
		const cards = [];
		for ( let i = 0; i < n; i++ ) {
			const k = i % nReal;
			const li = document.createElement( 'li' );
			li.className = 'isha-ring__card';
			if ( i >= nReal ) {
				li.setAttribute( 'aria-hidden', 'true' ); // bản sao (prompt 3.2)
			}
			li.innerHTML = `<div class="ld-block" style="background: var(${ TONES[ k ] })"><b>${ String( k + 1 ).padStart( 2, '0' ) }</b><span>${ TITLES[ k ] }</span><i>${ i >= nReal ? 'bản sao ' + i : 'thẻ ' + i }</i></div>`;
			ringEl.appendChild( li );
			cards.push( li );
		}
		ring = createRing( { stage, ring: ringEl, cards, cfg: RING } );
		total = n;
	}
	stage.style.setProperty( '--ring-fade', `${ RING.EDGE_FADE }%` );
	ring.setGeometry( g );
	ring.render( state.spin, true );
	const edge = Math.atan( window.innerWidth / 2 / g.R ) * 180 / Math.PI;
	label.textContent = `${ window.innerWidth }×${ window.innerHeight } · ${ g.tier } · R ${ g.R } · P ${ g.P } · thẻ ${ g.cardW }×${ g.cardH } · slot ${ slot.toFixed( 2 ) }° · ${ n } thẻ (${ nReal } thật) · cung ±${ ( n * slot / 2 ).toFixed( 1 ) }°\n` +
		`mép khung ở ${ edge.toFixed( 1 ) }°: thẻ ở đó to ×${ project( edge, g.R ).scale.toFixed( 2 ) } so với thẻ giữa · spin ${ state.spin.toFixed( 1 ) }°`;
}

build();
new ResizeObserver( build ).observe( document.body );

if ( q.get( 'gui' ) !== '0' ) {
	const { default: GUI } = await import( 'lil-gui' );
	const gui = new GUI( { title: 'Vòng cung ánh kim · bước 1' } );
	gui.add( state, 'spin', -180, 180, 0.1 ).name( 'spin (độ)' ).onChange( () => ring.render( state.spin, true ) );
	const fg = gui.addFolder( 'Hình học (3.1)' );
	for ( const [ k, lo, hi, st ] of [ [ 'R_DESKTOP', 500, 2400, 10 ], [ 'R_TABLET', 400, 2000, 10 ], [ 'R_MOBILE', 300, 1600, 10 ], [ 'P_RATIO', 0.5, 2, 0.01 ], [ 'ORIGIN_Y', 0, 100, 1 ], [ 'CARD_W_DESKTOP', 120, 320, 2 ], [ 'CARD_W_TABLET', 120, 300, 2 ], [ 'CARD_W_MOBILE_VW', 30, 80, 1 ], [ 'CARD_RATIO', 1, 1.8, 0.01 ], [ 'GAP', 0, 1, 0.01 ], [ 'CULL_DEG', 30, 89, 1 ], [ 'MIN_CARDS', 3, 16, 1 ], [ 'EDGE_FADE', 0, 30, 0.5 ] ] ) {
		fg.add( RING, k, lo, hi, st ).onChange( () => {
			total = 0; // số thẻ có thể đổi
			build();
		} );
	}
	gui.add( {
		copy: async () => {
			const json = JSON.stringify( RING, null, '\t' );
			try {
				await navigator.clipboard.writeText( json );
			} catch ( err ) {
				console.info( json );
			}
		},
	}, 'copy' ).name( 'Copy config JSON' );
}
requestAnimationFrame( () => requestAnimationFrame( () => ( window.__lookdev.ready = true ) ) );
