/**
 * Lookdev "Vòng cung ánh kim" (tools/lookdev-card-ring.html). Bước 1: hình học vòng lõm + culling + mask hai mép, thẻ là khối màu.
 * Bước 2: vòng rAF (loop.js), xoay tự động có quán tính, spring seek (spring.js). URL: auto=0 (không tự xoay), seek=<thẻ>&seekAt=<giây>
 * (đưa thẻ vào giữa sau số giây đó: chụp chuỗi khung bằng shot.mjs --times).
 * window.__lookdev.ready = true khi đã vẽ (tools/shot.mjs chờ cờ này).
 */
import { RING } from '../wp-content/themes/ISHA/assets/js/card-ring/config.js';
import { createRing, ringCount, flatCount, slotDeg, project } from '../wp-content/themes/ISHA/assets/js/card-ring/ring.js';
import { createMotion } from '../wp-content/themes/ISHA/assets/js/card-ring/spring.js';
import { createLoop } from '../wp-content/themes/ISHA/assets/js/card-ring/loop.js';

window.__lookdev = { ready: false, errors: [] };
window.addEventListener( 'error', ( e ) => window.__lookdev.errors.push( String( e.message ) ) );

const q = new URLSearchParams( location.search );
const TITLES = [ 'Filler', 'Botox', 'Isha Signature Harmony', 'Nâng cơ chuyên sâu', 'Trẻ hoá vùng mắt', 'Tạo dáng môi' ];
const TONES = [ '--isha-skin', '--isha-golden', '--isha-sunset', '--isha-skin-soft', '--isha-daylight', '--isha-golden' ];
const nReal = Math.max( 1, Math.min( 6, parseInt( q.get( 'n' ), 10 ) || 4 ) );
const state = { spin: parseFloat( q.get( 'spin' ) ) || 0, auto: q.get( 'auto' ) !== '0', seekTo: 0 };
const motion = createMotion( RING );
motion.spin = state.spin;
let geom = null;

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
	return { tier, R, P: R * RING.P_RATIO, cardW: Math.round( cardW ), cardH: Math.round( cardW * RING.CARD_RATIO ), flat: tier === 'mobile', viewW: w };
}

function build() {
	const g = geometryFor( window.innerWidth );
	geom = g;
	const slot = slotDeg( g.cardW, g.R, RING.GAP );
	const n = g.flat ? flatCount( nReal, g.cardW * ( 1 + RING.GAP ), g.viewW, RING.MIN_CARDS ) : ringCount( nReal, slot, RING.CULL_DEG, RING.MIN_CARDS );
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
	ring.render( motion.spin, true );
	updateLabel();
}

function updateLabel() {
	const g = geom;
	const slot = ring.geometry.slot;
	const n = total;
	const edge = Math.atan( window.innerWidth / 2 / g.R ) * 180 / Math.PI;
	const mode = motion.seeking ? 'SEEK' : Math.abs( motion.omega ) > 0.01 ? 'xoay' : 'đứng';
	label.textContent = `${ window.innerWidth }×${ window.innerHeight } · ${ g.tier }${ g.flat ? ' (phẳng)' : '' } · R ${ g.R } · P ${ g.P } · thẻ ${ g.cardW }×${ g.cardH } · slot ${ slot.toFixed( 2 ) }° · ${ n } thẻ (${ nReal } thật) · cung ±${ ( n * slot / 2 ).toFixed( 1 ) }°\n` +
		`mép khung ở ${ edge.toFixed( 1 ) }°: thẻ ở đó to ×${ project( edge, g.R ).scale.toFixed( 2 ) } so với thẻ giữa · spin ${ motion.spin.toFixed( 2 ) }° · ω ${ motion.omega.toFixed( 2 ) }°/s · ${ mode }`;
}

build();
new ResizeObserver( build ).observe( document.body );

// Vòng rAF duy nhất: ω tự động (desktop / tablet 4°/s, điện thoại 3°/s), seek bằng spring; label cập nhật 4 lần / giây.
const omegaAuto = () => ( geom.flat ? RING.OMEGA_MOBILE : RING.OMEGA_DESKTOP );
motion.omegaTarget = state.auto ? omegaAuto() : 0;
let acc = 0;
const loop = createLoop( {
	tick( dt ) {
		if ( motion.step( dt ) ) {
			motion.omegaTarget = 0; // tới nơi: đứng yên (bước 5: FOCUSED)
		}
		ring.render( motion.spin );
		acc += dt;
		if ( acc > 0.25 ) {
			acc = 0;
			updateLabel();
		}
	},
} );
loop.start();
const seekCard = ( k ) => {
	const slot = ring.geometry.slot;
	motion.seek( k * slot, total * slot );
};
const seekQ = parseInt( q.get( 'seek' ), 10 );
if ( Number.isFinite( seekQ ) ) {
	setTimeout( () => seekCard( seekQ ), ( parseFloat( q.get( 'seekAt' ) ) || 0 ) * 1000 );
}
window.__ring = { motion, seekCard, loop };

if ( q.get( 'gui' ) !== '0' ) {
	const { default: GUI } = await import( 'lil-gui' );
	const gui = new GUI( { title: 'Vòng cung ánh kim · bước 1' } );
	const fm = gui.addFolder( 'Chuyển động (6)' );
	fm.add( state, 'auto' ).name( 'tự xoay (tạm dừng / phát)' ).onChange( ( v ) => ( motion.omegaTarget = v ? omegaAuto() : 0 ) );
	fm.add( RING, 'OMEGA_DESKTOP', 0, 20, 0.1 ).name( 'ω desktop (°/s)' ).onChange( () => state.auto && ( motion.omegaTarget = omegaAuto() ) );
	fm.add( RING, 'OMEGA_MOBILE', 0, 20, 0.1 ).name( 'ω điện thoại (°/s)' ).onChange( () => state.auto && ( motion.omegaTarget = omegaAuto() ) );
	fm.add( RING, 'DAMP_K', 0.5, 12, 0.1 ).name( 'k (quán tính)' );
	fm.add( RING, 'SEEK_MS', 200, 3000, 10 ).name( 'seek (ms)' );
	fm.add( state, 'seekTo', 0, 15, 1 ).name( 'thẻ đích' );
	fm.add( { go: () => seekCard( state.seekTo ) }, 'go' ).name( 'đưa thẻ vào giữa (seek)' );
	fm.add( { r: () => {
		motion.omegaTarget = state.auto ? omegaAuto() : 0;
	} }, 'r' ).name( 'xoay lại (sau seek)' );
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
