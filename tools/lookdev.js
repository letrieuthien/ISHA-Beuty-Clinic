/**
 * Trang lookdev của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 17). Nạp đúng các module scene của theme, lil-gui đầy đủ, nút "Copy config
 * JSON". Bước 2: renderer + bản đồ độ sâu + chế độ xem bản đồ độ sâu. Các bước sau thêm lưới địa hình, trời, mặt trời... vào đây trước khi
 * tích hợp vào theme.
 *
 * Tham số URL (tools/shot.mjs dùng): view, source, p, gui=0, dpr. Khi khung đầu với tham số hiện tại đã vẽ xong: window.__lookdev.ready = true.
 */
import * as THREE from 'three';
import { CONFIG } from '../wp-content/themes/ISHA/assets/js/services-cinematic/config.js';
import { createRenderer } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/renderer.js';
import { createDepth, VIEW_MODES } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/depth.js';

const THEME = '../wp-content/themes/ISHA/';
const PLATES = 'refs/plates/';
const q = new URLSearchParams( location.search );
const showGui = q.get( 'gui' ) !== '0';
const label = document.getElementById( 'label' );
const canvas = document.getElementById( 'c' );
window.__lookdev = { ready: false, errors: [] };
window.addEventListener( 'error', ( e ) => window.__lookdev.errors.push( String( e.message ) ) );

// Nguồn độ sâu: auto = dò bản đồ của theme (assets/img/scene/depth.webp, đặc tả 3.2), không có thì suy từ độ sáng; còn lại: ép nguồn để so.
const SOURCES = {
	'auto (dò depth.webp)': 'auto',
	'suy từ độ sáng': 'lum',
	'depth.png (vẽ tay)': 'depth.png',
	'depth-1.png (vẽ tay)': 'depth-1.png',
	'depth-2.png (vẽ tay)': 'depth-2.png',
};
const VIEW_NAMES = {
	'ảnh gốc': 'photo',
	'độ sâu (thang xám)': 'depth',
	'đường đồng mức trên ảnh': 'contour',
	'địa hình nổi (lộ gồ ghề)': 'relief',
	'chia đôi ảnh | độ sâu': 'split',
};
const state = {
	view: VIEW_MODES.includes( q.get( 'view' ) ) ? q.get( 'view' ) : 'depth',
	source: Object.values( SOURCES ).includes( q.get( 'source' ) ) ? q.get( 'source' ) : 'auto',
	progress: Math.min( 1, Math.max( 0, parseFloat( q.get( 'p' ) ) || 0 ) ),
};

async function resolveMapUrl( source ) {
	if ( source === 'lum' ) {
		return null;
	}
	if ( source !== 'auto' ) {
		return PLATES + source;
	}
	const url = THEME + 'assets/img/scene/depth.webp';
	try {
		const r = await fetch( url, { method: 'HEAD', cache: 'no-store' } );
		return r.ok ? url : null;
	} catch ( err ) {
		return null;
	}
}

async function main() {
	const dprParam = parseFloat( q.get( 'dpr' ) );
	const ctx = createRenderer( THREE, { canvas, dprCap: dprParam > 0 ? dprParam : CONFIG.presets.FULL_HIGH.dpr, toneMapping: CONFIG.toneMapping } );
	const t0 = performance.now();
	const depth = await createDepth( THREE, ctx, { photoUrl: PLATES + 'scene-source.png', mapUrl: await resolveMapUrl( state.source ), cfg: CONFIG.depth } );
	const tDepth = performance.now() - t0;
	const view = depth.createView();
	view.setMode( state.view );
	await ctx.renderer.compileAsync( view.scene, view.camera );

	let dirty = true;
	const requestRender = () => {
		dirty = true;
	};
	const updateLabel = () => {
		const d = CONFIG.depth;
		label.textContent = [
			`view ${ state.view } · p ${ state.progress.toFixed( 2 ) } · ảnh ${ depth.size.width }×${ depth.size.height } · bản đồ ${ depth.depthSize.width }×${ depth.depthSize.height }`,
			`nguồn độ sâu: ${ depth.source }`,
			`lumNear ${ d.lumNear.toFixed( 3 ) } · lumFar ${ d.lumFar.toFixed( 3 ) } · gamma ${ d.gamma.toFixed( 2 ) } · blur ${ ( d.blur * 100 ).toFixed( 2 ) }% · tiền xử lý ${ tDepth.toFixed( 0 ) } ms`,
		].join( '\n' );
	};

	new ResizeObserver( () => {
		if ( ctx.resize( canvas.clientWidth, canvas.clientHeight ) ) {
			view.render(); // canvas vừa bị xoá: vẽ lại ngay trong cùng lượt
		}
	} ).observe( canvas );
	ctx.resize( canvas.clientWidth, canvas.clientHeight );

	let stats = null;
	if ( showGui ) {
		const [ { default: GUI }, { default: Stats } ] = await Promise.all( [ import( 'lil-gui' ), import( 'three/addons/libs/stats.module.js' ) ] );
		stats = new Stats();
		stats.dom.style.cssText = 'position:fixed;left:0;bottom:0;z-index:10;';
		document.body.appendChild( stats.dom );
		const gui = new GUI( { title: 'ISHA lookdev · v3' } );
		const onDepth = () => {
			depth.sync();
			updateLabel();
			requestRender();
		};
		const fDepth = gui.addFolder( 'Độ sâu (đặc tả 3.1)' );
		fDepth.add( CONFIG.depth, 'lumNear', 0, 0.6, 0.005 ).name( 'uLumNear (sáng ≤ → gần)' ).onChange( onDepth );
		fDepth.add( CONFIG.depth, 'lumFar', 0.3, 1, 0.005 ).name( 'uLumFar (sáng ≥ → xa)' ).onChange( onDepth );
		fDepth.add( CONFIG.depth, 'gamma', 0.25, 3, 0.01 ).name( 'uDepthGamma' ).onChange( onDepth );
		const reblur = () => {
			depth.rebuild();
			onDepth();
		};
		fDepth.add( CONFIG.depth, 'blur', 0, 0.06, 0.001 ).name( 'làm mượt (tỉ lệ rộng)' ).onFinishChange( reblur );
		fDepth.add( CONFIG.depth, 'mapBlur', 0, 0.03, 0.001 ).name( 'làm mượt bản đồ vẽ sẵn' ).onFinishChange( reblur );
		fDepth.add( state, 'source', SOURCES ).name( 'nguồn độ sâu' ).onChange( async ( s ) => {
			await depth.setMap( await resolveMapUrl( s ) );
			onDepth();
		} );

		const fView = gui.addFolder( 'Xem' );
		fView.add( state, 'view', VIEW_NAMES ).name( 'chế độ' ).onChange( ( v ) => {
			view.setMode( v );
			updateLabel();
			requestRender();
		} );
		const u = view.uniforms;
		fView.add( u.uMix, 'value', 0, 1, 0.01 ).name( 'trộn ảnh gốc (đồng mức / nổi)' ).onChange( requestRender );
		fView.add( u.uStep, 'value', 0.01, 0.2, 0.005 ).name( 'bước đồng mức (độ sâu)' ).onChange( requestRender );
		fView.add( u.uRelief, 'value', 5, 300, 1 ).name( 'độ dốc địa hình nổi' ).onChange( requestRender );
		fView.add( u.uSplit, 'value', 0, 1, 0.01 ).name( 'vị trí chia đôi' ).onChange( requestRender );
		fView.add( CONFIG, 'toneMapping', [ 'Neutral', 'AgX' ] ).name( 'tone mapping (từ bước 3)' ).onChange( ( v ) => ctx.setToneMapping( v ) );

		gui.add( {
			copy: async () => {
				const json = JSON.stringify( CONFIG, null, '\t' );
				try {
					await navigator.clipboard.writeText( json );
					console.info( '[ISHA lookdev] Đã chép config JSON.' );
				} catch ( err ) {
					console.info( json );
				}
			},
		}, 'copy' ).name( 'Copy config JSON' );
	}
	updateLabel();

	const timer = new THREE.Timer();
	let frames = 0;
	const loop = ( now ) => {
		timer.update( now );
		if ( dirty ) {
			dirty = false;
			view.render();
			if ( ++frames === 2 ) {
				window.__lookdev.ready = true; // khung thứ hai: chắc chắn đã có hình (khung đầu có thể trùng lượt đổi kích thước)
			}
			if ( frames < 2 ) {
				dirty = true;
			}
		}
		if ( stats ) {
			stats.update();
		}
		requestAnimationFrame( loop );
	};
	requestAnimationFrame( loop );
	window.__lookdev.depth = depth;
	window.__lookdev.ctx = ctx;
}

main().catch( ( err ) => {
	console.error( '[ISHA lookdev]', err );
	window.__lookdev.errors.push( String( err && err.stack ? err.stack : err ) );
	label.textContent = 'Lỗi: ' + ( err && err.message ? err.message : err );
	window.__lookdev.ready = true;
} );
