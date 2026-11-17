/**
 * Lookdev "Vòng cung ánh kim" (tools/lookdev-card-ring.html): vòng thẻ thật (lookdev-cards.js mountRing → card-ring/index.js) trên nền
 * poster của cảnh, lil-gui cho mọi hằng số, "Copy config JSON". Tia nắng chạm thẻ cần cảnh WebGL thật: xem tools/lookdev.html.
 * URL: n=<số liệu trình> (n=6 thử xoay vòng), spin=<độ>, auto=0, seek=<thẻ>&seekAt=<giây>, hover=<thẻ>, reduced=1 (ép giảm chuyển động),
 * gov=0|1|2 (ép bậc governor), gui=0. window.__ring.fps( ms ) đo FPS. window.__lookdev.ready = true khi đã vẽ (tools/shot.mjs chờ cờ này).
 */
import { RING } from '../wp-content/themes/ISHA/assets/js/card-ring/config.js';
import { project } from '../wp-content/themes/ISHA/assets/js/card-ring/ring.js';
import { mountRing } from './lookdev-cards.js';

window.__lookdev = { ready: false, errors: [] };
window.addEventListener( 'error', ( e ) => window.__lookdev.errors.push( String( e.message ) ) );

const q = new URLSearchParams( location.search );
const label = document.getElementById( 'label' );
const host = document.getElementById( 'ring-host' );
const { ring, stage } = await mountRing( { host, q, reduced: q.get( 'reduced' ) === '1' || undefined } );
const motion = ring.motion;
const state = { auto: q.get( 'auto' ) !== '0', seekTo: 0, spin: parseFloat( q.get( 'spin' ) ) };
if ( Number.isFinite( state.spin ) && ring.spinning ) {
	motion.spin = state.spin;
}
ring.setPaused( ! state.auto );

function updateLabel() {
	const g = ring.geometry;
	const edge = ( Math.atan( window.innerWidth / 2 / g.R ) * 180 ) / Math.PI;
	const mode = motion.seeking ? 'SEEK' : Math.abs( motion.omega ) > 0.01 ? 'xoay' : 'đứng';
	label.textContent = `${ window.innerWidth }×${ window.innerHeight } · ${ g.tier }${ g.flat ? ' (phẳng)' : '' } · R ${ g.R } · thẻ ${ g.cardW }×${ g.cardH } · slot ${ g.slot.toFixed( 2 ) }° · ${ ring.count } thẻ${ ring.spinning ? '' : ' (đứng yên: < ' + RING.SPIN_MIN_CARDS + ' thẻ thật)' }\n` +
		`mép khung ở ${ edge.toFixed( 1 ) }°: ×${ project( edge, g.R ).scale.toFixed( 2 ) } so với thẻ giữa · spin ${ motion.spin.toFixed( 2 ) }° · ω ${ motion.omega.toFixed( 2 ) }°/s · ${ mode }\n` +
		`governor ${ ring.governor.step } ${ ring.governor.name }${ ring.governor.auto ? '' : ' (ép)' } · FPS 2 giây ${ ring.governor.fps ? ring.governor.fps.toFixed( 1 ) : '…' }`;
}
let acc = 0;
ring.onFrame( ( dt ) => {
	acc += dt;
	if ( acc > 0.25 ) {
		acc = 0;
		updateLabel();
	}
} );
updateLabel();

const seekQ = parseInt( q.get( 'seek' ), 10 );
if ( Number.isFinite( seekQ ) ) {
	setTimeout( () => ring.seek( seekQ ), ( parseFloat( q.get( 'seekAt' ) ) || 0 ) * 1000 );
}

/** FPS trung bình và khung chậm nhất trong ms mili giây. */
function fps( ms = 3000 ) {
	return new Promise( ( r ) => {
		const t0 = performance.now();
		let n = 0;
		let last = t0;
		let worst = 0;
		const f = ( t ) => {
			n++;
			worst = Math.max( worst, t - last );
			last = t;
			if ( t - t0 < ms ) {
				requestAnimationFrame( f );
			} else {
				r( { fps: Math.round( ( n * 1000 ) / ( t - t0 ) ), worstMs: Math.round( worst * 10 ) / 10 } );
			}
		};
		requestAnimationFrame( f );
	} );
}
window.__ring = { ring, motion, fps, seekCard: ( k ) => ring.seek( k ), setHover: ( i ) => ring.setHover( i ) };

if ( q.get( 'gui' ) !== '0' ) {
	const { default: GUI } = await import( 'lil-gui' );
	const gui = new GUI( { title: 'Vòng cung ánh kim' } );
	const rebuild = () => ring.rebuild();
	const fh = gui.addFolder( 'Hover / lật (khách)' );
	fh.add( RING, 'FLIP_TURNS', 0, 4, 1 ).name( 'số vòng xoay thêm' ).onChange( ring.applyStyle );
	fh.add( RING, 'FLIP_MS', 200, 4000, 50 ).name( 'thời lượng lật (ms)' ).onChange( ring.applyStyle );
	fh.add( RING, 'HOVER_SCALE', 1, 1.2, 0.005 ).name( 'phóng khi hover' ).onChange( ring.applyStyle );
	fh.add( RING, 'DIM_HOVER', 0.2, 1, 0.01 ).name( 'độ đục thẻ khác' ).onChange( ring.applyStyle );
	fh.add( RING, 'SPIN_MIN_CARDS', 1, 10, 1 ).name( 'xoay vòng khi ≥ thẻ' ).onChange( rebuild );
	const fl = gui.addFolder( 'Ánh sáng (bước 4)' );
	fl.add( RING, 'SPEC_MAX', 0, 1.5, 0.01 ).name( 'ánh kim thẻ giữa (spec max)' );
	fl.add( RING, 'SPEC_POW', 0, 8, 0.1 ).name( 'tối dần ra rìa (mũ cos)' );
	fl.add( RING, 'SPEC_HOT', 1, 2, 0.01 ).name( 'sáng thêm khi hover (×)' );
	fl.add( RING, 'SPEC_EASE_MS', 0, 1500, 10 ).name( 'chuyển sáng hover (ms)' );
	fl.add( RING, 'REFLECT' ).name( 'phản chiếu sàn' ).onChange( rebuild );
	const fv = gui.addFolder( 'Vật liệu & glint (5)' );
	fv.add( RING, 'GLINT_PERIOD_S', 1, 15, 0.1 ).name( 'glint: một vòng (giây)' ).onChange( ring.applyStyle );
	fv.add( RING, 'GLINT_PERIOD_HOVER_S', 0.5, 6, 0.1 ).name( 'glint khi hover (giây)' );
	fv.add( RING, 'GLINT_STAGGER_S', 0, 3, 0.05 ).name( 'lệch pha mỗi thẻ (giây)' ).onChange( ring.applyStyle );
	fv.add( { m: async () => ( label.textContent += '\n' + JSON.stringify( await fps() ) ) }, 'm' ).name( 'đo FPS 3 giây' );
	const fm = gui.addFolder( 'Chuyển động (6)' );
	fm.add( state, 'auto' ).name( 'tự xoay (tạm dừng / phát)' ).onChange( ( v ) => ring.setPaused( ! v ) );
	fm.add( RING, 'OMEGA_DESKTOP', 0, 20, 0.1 ).name( 'ω desktop (°/s)' ).onChange( () => ring.setPaused( ! state.auto ) );
	fm.add( RING, 'OMEGA_MOBILE', 0, 20, 0.1 ).name( 'ω điện thoại (°/s)' ).onChange( () => ring.setPaused( ! state.auto ) );
	fm.add( RING, 'DAMP_K', 0.5, 12, 0.1 ).name( 'k (quán tính)' );
	fm.add( RING, 'SEEK_MS', 200, 3000, 10 ).name( 'seek (ms)' );
	fm.add( state, 'seekTo', 0, 15, 1 ).name( 'thẻ đích' );
	fm.add( { go: () => ring.seek( state.seekTo ) }, 'go' ).name( 'đưa thẻ vào giữa (seek)' );
	fm.add( { r: () => ring.resume() }, 'r' ).name( 'xoay lại (sau seek)' );
	const fg = gui.addFolder( 'Hình học (3.1)' ).close();
	for ( const [ k, lo, hi, st ] of [ [ 'R_DESKTOP', 500, 2400, 10 ], [ 'R_TABLET', 400, 2000, 10 ], [ 'R_MOBILE', 300, 1600, 10 ], [ 'P_RATIO', 0.5, 2, 0.01 ], [ 'ORIGIN_Y', 0, 100, 1 ], [ 'CARD_W_DESKTOP', 120, 320, 2 ], [ 'CARD_W_TABLET', 120, 300, 2 ], [ 'CARD_W_MOBILE_VW', 30, 80, 1 ], [ 'CARD_RATIO', 1, 1.8, 0.01 ], [ 'GAP', 0, 1, 0.01 ], [ 'CULL_DEG', 30, 89, 1 ], [ 'MIN_CARDS', 3, 16, 1 ], [ 'EDGE_FADE', 0, 30, 0.5 ] ] ) {
		fg.add( RING, k, lo, hi, st ).onChange( rebuild );
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
