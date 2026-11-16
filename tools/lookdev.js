/**
 * Trang lookdev của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 17). Nạp đúng các module scene của theme, lil-gui đầy đủ, nút "Copy config
 * JSON". Làm và duyệt từng bước ở đây trước khi tích hợp vào theme (bước 8).
 *
 * Chế độ xem: scene (cảnh), alpha / stretch / rim (gỡ lỗi lưới ảnh: độ đục, độ giãn so với vị trí tham chiếu, viền sáng), photo / depth /
 * contour / relief / split (bản đồ độ sâu, bước 2).
 * Tham số URL (tools/shot.mjs dùng): view, source, p, gui=0, dpr. Khi khung với tham số hiện tại đã vẽ xong: window.__lookdev.ready = true.
 *
 * Keyframe tạm (previewStop) tới khi stops.js v3 được viết ở bước 8: giá trị đầu / cuối theo đặc tả mục 5.4, 6.
 */
import * as THREE from 'three';
import { CONFIG } from '../wp-content/themes/ISHA/assets/js/services-cinematic/config.js';
import { readTokens, derivePalette, toVectors } from '../wp-content/themes/ISHA/assets/js/services-cinematic/tokens.js';
import { createRenderer } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/renderer.js';
import { createDepth, VIEW_MODES } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/depth.js';
import { createPlate } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/plate.js';
import { createSky } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/sky.js';
import { createCamera } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/camera.js';

const THEME = '../wp-content/themes/ISHA/';
const PLATES = 'refs/plates/';
const q = new URLSearchParams( location.search );
const showGui = q.get( 'gui' ) !== '0';
const label = document.getElementById( 'label' );
const canvas = document.getElementById( 'c' );
window.__lookdev = { ready: false, errors: [] };
window.addEventListener( 'error', ( e ) => window.__lookdev.errors.push( String( e.message ) ) );

const SCENE_VIEWS = [ 'scene', 'alpha', 'stretch', 'rim' ];
const SOURCES = {
	'auto (dò depth.webp)': 'auto',
	'suy từ độ sáng': 'lum',
	'depth.png (vẽ tay)': 'depth.png',
	'depth-1.png (vẽ tay)': 'depth-1.png',
	'depth-2.png (vẽ tay)': 'depth-2.png',
};
const VIEW_NAMES = {
	'cảnh': 'scene',
	'gỡ lỗi: độ đục lưới (alpha)': 'alpha',
	'gỡ lỗi: độ giãn so với tham chiếu': 'stretch',
	'gỡ lỗi: viền sáng': 'rim',
	'ảnh gốc': 'photo',
	'độ sâu (thang xám)': 'depth',
	'đường đồng mức trên ảnh': 'contour',
	'địa hình nổi (lộ gồ ghề)': 'relief',
	'chia đôi ảnh | độ sâu': 'split',
};
const qView = q.get( 'view' );
const state = {
	view: SCENE_VIEWS.includes( qView ) || VIEW_MODES.includes( qView ) ? qView : 'scene',
	source: Object.values( SOURCES ).includes( q.get( 'source' ) ) ? q.get( 'source' ) : 'auto',
	progress: Math.min( 1, Math.max( 0, parseFloat( q.get( 'p' ) ) || 0 ) ),
	play: false,
};

// Keyframe tạm (thay bằng stops.js v3 ở bước 8). Mặt trời theo đặc tả 5.4 (desktop / khung dọc), các trường khác theo mục 6.
const lerp = ( a, b, t ) => a + ( b - a ) * t;
function previewStop( p, portrait ) {
	const e = Math.sin( ( Math.min( 1, Math.max( 0, p ) ) * Math.PI ) / 2 );
	const s = portrait ? [ 0.5, 0.68, 0.56, 0.15 ] : [ 0.52, 0.74, 0.58, 0.18 ];
	return {
		sunX: lerp( s[ 0 ], s[ 2 ], e ),
		sunY: lerp( s[ 1 ], s[ 3 ], e ),
		rimIntensity: lerp( 1, 0.35, e ),
		skyMix: e,
		cloudOpacity: lerp( 0.18, 0.3, e ),
		exposure: lerp( 1.15, 1, e ),
	};
}

async function probe( url ) {
	try {
		const r = await fetch( url, { method: 'HEAD', cache: 'no-store' } );
		return r.ok;
	} catch ( err ) {
		return false;
	}
}
async function resolveMapUrl( source ) {
	if ( source === 'lum' ) {
		return null;
	}
	if ( source !== 'auto' ) {
		return PLATES + source;
	}
	const url = THEME + 'assets/img/scene/depth.webp';
	return ( await probe( url ) ) ? url : null;
}

async function main() {
	const dprParam = parseFloat( q.get( 'dpr' ) );
	const ctx = createRenderer( THREE, { canvas, dprCap: dprParam > 0 ? dprParam : CONFIG.presets.FULL_HIGH.dpr, toneMapping: CONFIG.toneMapping } );
	const renderer = ctx.renderer;
	const t0 = performance.now();
	const depth = await createDepth( THREE, ctx, { photoUrl: PLATES + 'scene-source.png', mapUrl: await resolveMapUrl( state.source ), cfg: CONFIG.depth } );
	const tDepth = performance.now() - t0;
	const depthView = depth.createView();

	const palette = toVectors( derivePalette( readTokens(), CONFIG.colors ), THREE );
	const portraitStart = canvas.clientWidth < canvas.clientHeight;
	const preset = portraitStart ? CONFIG.presets.LITE : CONFIG.presets.FULL_HIGH;
	const cam = createCamera( THREE, { cfg: CONFIG.camera, plateCfg: CONFIG.plate, mobile: portraitStart } );
	const plate = createPlate( THREE, { depth, palette, cfg: CONFIG.plate, cut: CONFIG.cut, rim: CONFIG.rim, framing: CONFIG.framing, grid: preset.grid } );
	let clouds = null;
	if ( await probe( 'out/clouds-mask.png' ) ) {
		clouds = await new THREE.TextureLoader().loadAsync( 'out/clouds-mask.png' );
		clouds.colorSpace = THREE.NoColorSpace;
	}
	const sky = createSky( THREE, { palette, cfg: CONFIG.sky, clouds } );
	const scene = new THREE.Scene();
	scene.add( sky.mesh, plate.group );

	const buffer = new THREE.Vector2();
	let layout = null;
	let stop = previewStop( state.progress, portraitStart );
	const sunNdc = { x: 0, y: 0 };
	const relayout = () => {
		const fov = cam.resize( canvas.clientWidth, canvas.clientHeight );
		renderer.getDrawingBufferSize( buffer );
		layout = plate.layout( canvas.clientWidth, canvas.clientHeight, fov, buffer );
	};
	const isScene = () => SCENE_VIEWS.includes( state.view );
	const applyView = () => {
		if ( isScene() ) {
			plate.setDebug( state.view === 'scene' ? 'none' : state.view );
		} else {
			depthView.setMode( state.view );
		}
	};
	applyView();

	const updateLabel = () => {
		const d = CONFIG.depth;
		const lines = [ `view ${ state.view } · p ${ state.progress.toFixed( 2 ) } · ${ canvas.clientWidth }×${ canvas.clientHeight } · nguồn độ sâu: ${ depth.source }` ];
		if ( isScene() && layout ) {
			lines.push( `chân trời ${ ( layout.horizonY * 100 ).toFixed( 1 ) }% · ảnh từ ${ ( layout.imageTop * 100 ).toFixed( 1 ) }% tới ${ ( layout.imageBottom * 100 ).toFixed( 1 ) }% khung · FOV ${ cam.camera.fov.toFixed( 1 ) }° · phóng gần nhất tối đa ${ ( ( cam.magnification - 1 ) * 100 ).toFixed( 1 ) }%` );
			lines.push( `mặt trời (${ ( stop.sunX * 100 ).toFixed( 0 ) }%, ${ ( stop.sunY * 100 ).toFixed( 0 ) }%) · skyMix ${ stop.skyMix.toFixed( 2 ) } · rim ${ stop.rimIntensity.toFixed( 2 ) } · exposure ${ stop.exposure.toFixed( 2 ) } · mây ${ clouds ? 'texture' : 'fbm' }` );
		} else {
			lines.push( `lumNear ${ d.lumNear.toFixed( 3 ) } · lumFar ${ d.lumFar.toFixed( 3 ) } · gamma ${ d.gamma.toFixed( 2 ) } · blur ${ ( d.blur * 100 ).toFixed( 2 ) }% · tiền xử lý ${ tDepth.toFixed( 0 ) } ms` );
		}
		label.textContent = lines.join( '\n' );
	};

	const timer = new THREE.Timer();
	timer.connect( document );
	const frame = () => {
		if ( ! isScene() ) {
			depthView.render();
			return;
		}
		const portrait = canvas.clientWidth < canvas.clientHeight;
		stop = previewStop( state.progress, portrait );
		sunNdc.x = stop.sunX * 2 - 1;
		sunNdc.y = 1 - stop.sunY * 2;
		cam.update( state.progress, timer.getDelta() );
		plate.update( { sunNdc, rimIntensity: stop.rimIntensity, skyMix: stop.skyMix } );
		sky.update( { skyMix: stop.skyMix, sunNdc, cloudOpacity: stop.cloudOpacity, time: timer.getElapsed(), aspect: canvas.clientWidth / canvas.clientHeight, horizonY: layout.horizonY } );
		renderer.toneMappingExposure = stop.exposure;
		renderer.setRenderTarget( null );
		renderer.render( scene, cam.camera );
	};

	new ResizeObserver( () => {
		if ( ctx.resize( canvas.clientWidth, canvas.clientHeight ) ) {
			relayout();
			frame(); // canvas vừa bị xoá: vẽ lại ngay trong cùng lượt
		}
	} ).observe( canvas );
	ctx.resize( canvas.clientWidth, canvas.clientHeight );
	relayout();
	await renderer.compileAsync( scene, cam.camera );
	await renderer.compileAsync( depthView.scene, depthView.camera );
	window.addEventListener( 'pointermove', ( e ) => cam.setPointer( ( e.clientX / innerWidth ) * 2 - 1, -( ( e.clientY / innerHeight ) * 2 - 1 ) ) );

	let stats = null;
	if ( showGui ) {
		const [ { default: GUI }, { default: Stats } ] = await Promise.all( [ import( 'lil-gui' ), import( 'three/addons/libs/stats.module.js' ) ] );
		stats = new Stats();
		stats.dom.style.cssText = 'position:fixed;left:0;bottom:0;z-index:10;';
		document.body.appendChild( stats.dom );
		const gui = new GUI( { title: 'ISHA lookdev · v3' } );
		gui.add( state, 'view', VIEW_NAMES ).name( 'chế độ xem' ).onChange( applyView );
		gui.add( state, 'progress', 0, 1, 0.001 ).name( 'progress' ).listen();
		gui.add( state, 'play' ).name( 'chạy qua lại (24 giây)' );

		const fPlate = gui.addFolder( 'Lưới ảnh (3.3 – 3.5, 7)' );
		fPlate.add( CONFIG.plate, 'near', 300, 1200, 10 ).name( 'uNearDist' );
		fPlate.add( CONFIG.plate, 'far', 600, 3000, 10 ).name( 'uFarDist' );
		fPlate.add( CONFIG.plate, 'horizonUV', 0.05, 0.4, 0.005 ).name( 'uHorizonUV (ảnh, từ trên)' ).onChange( relayout );
		fPlate.add( CONFIG.plate, 'mixNear', 0, 1, 0.01 ).name( 'depthMix gần' );
		fPlate.add( CONFIG.plate, 'mixFar', 0, 1, 0.01 ).name( 'depthMix xa' );
		fPlate.add( CONFIG.plate, 'gain', 0.5, 2, 0.01 ).name( 'độ sáng (gain)' );
		fPlate.add( CONFIG.plate, 'aerial', 0, 1, 0.01 ).name( 'xa ngả màu trời (aerial)' );
		fPlate.add( CONFIG.plate, 'stretchLo', 1, 4, 0.05 ).name( 'chống giãn: bắt đầu mờ (lần)' );
		fPlate.add( CONFIG.plate, 'stretchHi', 1.2, 8, 0.05 ).name( 'chống giãn: trong suốt (lần)' );
		const fCut = gui.addFolder( 'Cắt trời (3.4)' );
		fCut.add( CONFIG.cut, 'lumLow', 0.4, 0.9, 0.005 ).name( 'uSkyLumLow' );
		fCut.add( CONFIG.cut, 'lumHigh', 0.4, 0.95, 0.005 ).name( 'uSkyLumHigh' );
		fCut.add( CONFIG.cut, 'bandY', 0, 0.4, 0.005 ).name( 'uHorizonY (mép cắt theo hàng)' );
		fCut.add( CONFIG.cut, 'band', 0.001, 0.15, 0.001 ).name( 'uBand' );
		const fRim = gui.addFolder( 'Viền sáng (7)' );
		fRim.add( CONFIG.rim, 'strength', 0, 3, 0.01 ).name( 'độ mạnh' );
		fRim.add( CONFIG.rim, 'lo', 0, 20, 0.1 ).name( 'độ dốc bắt đầu' );
		fRim.add( CONFIG.rim, 'hi', 0.5, 40, 0.1 ).name( 'độ dốc đủ' );
		fRim.add( CONFIG.rim, 'falloff', 0.05, 2, 0.01 ).name( 'tắt dần theo khoảng cách' );
		const fFrame = gui.addFolder( 'Đóng khung (5.2) và camera (9)' );
		fFrame.add( CONFIG.framing, 'horizonLandscape', 0.3, 0.7, 0.005 ).name( 'chân trời khung ngang' ).onChange( relayout );
		fFrame.add( CONFIG.framing, 'horizonPortrait', 0.3, 0.8, 0.005 ).name( 'chân trời khung dọc' ).onChange( relayout );
		fFrame.add( CONFIG.camera, 'fov', 20, 70, 0.5 ).name( 'FOV dọc' ).onChange( relayout );
		fFrame.add( CONFIG.camera, 'fovPortrait', 0, 20, 0.5 ).name( 'FOV thêm cho khung dọc' ).onChange( relayout );
		fFrame.add( CONFIG.camera, 'dollyMax', 0, 200, 1 ).name( 'uDollyMax' );
		fFrame.add( CONFIG.camera, 'anchorDrift', -30, 30, 0.5 ).name( 'uAnchorDrift' );
		fFrame.add( CONFIG.camera, 'mouse', 0, 3, 0.05 ).name( 'parallax chuột' );
		const fSky = gui.addFolder( 'Trời (8)' );
		fSky.add( CONFIG.sky, 'sunGlow', 0, 1, 0.01 ).name( 'Golden quanh mặt trời' );
		fSky.add( CONFIG.sky, 'sunGlowRadius', 0.05, 1, 0.01 ).name( 'bán kính quầng trời' );
		fSky.add( CONFIG.sky, 'horizonGolden', 0, 1, 0.01 ).name( 'Golden chân trời lúc toàn quang' );
		fSky.add( CONFIG.sky, 'cloudGain', 0, 3, 0.01 ).name( 'mây (nhân)' );
		fSky.add( CONFIG.sky, 'cloudDrift', 0, 0.05, 0.001 ).name( 'mây trôi (đơn vị / giây)' );
		fSky.add( CONFIG, 'toneMapping', [ 'Neutral', 'AgX' ] ).name( 'tone mapping' ).onChange( ( v ) => ctx.setToneMapping( v ) );

		const fDepth = gui.addFolder( 'Độ sâu (3.1)' ).close();
		const onDepth = () => depth.sync();
		fDepth.add( CONFIG.depth, 'lumNear', 0, 0.6, 0.005 ).name( 'uLumNear' ).onChange( onDepth );
		fDepth.add( CONFIG.depth, 'lumFar', 0.3, 1, 0.005 ).name( 'uLumFar' ).onChange( onDepth );
		fDepth.add( CONFIG.depth, 'gamma', 0.25, 3, 0.01 ).name( 'uDepthGamma' ).onChange( onDepth );
		fDepth.add( CONFIG.depth, 'blur', 0, 0.06, 0.001 ).name( 'làm mượt (tỉ lệ rộng)' ).onFinishChange( () => depth.rebuild() );
		fDepth.add( state, 'source', SOURCES ).name( 'nguồn độ sâu' ).onChange( async ( s ) => {
			await depth.setMap( await resolveMapUrl( s ) );
		} );
		const fView = gui.addFolder( 'Xem bản đồ độ sâu' ).close();
		const u = depthView.uniforms;
		fView.add( u.uMix, 'value', 0, 1, 0.01 ).name( 'trộn ảnh gốc' );
		fView.add( u.uStep, 'value', 0.01, 0.2, 0.005 ).name( 'bước đồng mức' );
		fView.add( u.uRelief, 'value', 5, 300, 1 ).name( 'độ dốc địa hình nổi' );
		fView.add( u.uSplit, 'value', 0, 1, 0.01 ).name( 'vị trí chia đôi' );

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

	let frames = 0;
	let playT = 0;
	const loop = ( now ) => {
		timer.update( now );
		if ( state.play ) {
			playT += Math.min( 0.1, timer.getDelta() );
			state.progress = 0.5 - 0.5 * Math.cos( ( playT / 24 ) * Math.PI * 2 );
		}
		frame();
		if ( ++frames === 3 ) {
			window.__lookdev.ready = true; // khung thứ ba: chắc chắn đã có hình (khung đầu có thể trùng lượt đổi kích thước)
		}
		if ( frames % 15 === 0 || frames === 3 ) {
			updateLabel();
		}
		if ( stats ) {
			stats.update();
		}
		requestAnimationFrame( loop );
	};
	updateLabel();
	requestAnimationFrame( loop );
	Object.assign( window.__lookdev, { depth, ctx, plate, sky, cam, CONFIG } );
}

main().catch( ( err ) => {
	console.error( '[ISHA lookdev]', err );
	window.__lookdev.errors.push( String( err && err.stack ? err.stack : err ) );
	label.textContent = 'Lỗi: ' + ( err && err.message ? err.message : err );
	window.__lookdev.ready = true;
} );
