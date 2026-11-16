/**
 * Trang lookdev của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 17). Nạp đúng các module scene của theme, lil-gui đầy đủ, nút "Copy config
 * JSON". Làm và duyệt từng bước ở đây trước khi tích hợp vào theme (bước 8).
 *
 * Chế độ xem: scene (cảnh), alpha / stretch / rim (gỡ lỗi lưới ảnh: độ đục, độ giãn so với vị trí tham chiếu, viền sáng), photo / depth /
 * contour / relief / split (bản đồ độ sâu, bước 2).
 * Tham số URL (tools/shot.mjs dùng): view, source, p, gui=0, dpr, reduced=1 (giảm chuyển động: tắt bụi, nhịp thở), grain=0 (tắt grain khi
 * đo chuyển động), off=mist,clouds,sun,dust,bloom,grain (tắt từng lớp để truy lỗi hình), cards=0 (tắt card thật của bước 7, xem
 * lookdev-cards.js). Khi khung với tham số hiện tại đã vẽ xong: window.__lookdev.ready = true.
 *
 * Điểm dừng thật (stops.js, như trang thật): n=<số liệu trình> (mặc định 4), p = progress (camera và mặt trời cùng theo p, không trễ).
 * Ảnh: bản webp của theme (assets/img/scene/, tools/bake-scene.mjs sinh); photo=png dùng ảnh gốc tools/refs/plates/ để so.
 */
import * as THREE from 'three';
import { CONFIG } from '../wp-content/themes/ISHA/assets/js/services-cinematic/config.js';
import { readTokens, derivePalette, toVectors } from '../wp-content/themes/ISHA/assets/js/services-cinematic/tokens.js';
import { createRenderer } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/renderer.js';
import { createDepth, VIEW_MODES } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/depth.js';
import { createPlate } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/plate.js';
import { createSky } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/sky.js';
import { createCamera, idleValues } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/camera.js';
import { createDust } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/dust.js';
import { createSun } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/sun.js';
import { createMist } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/mist.js';
import { createPost } from '../wp-content/themes/ISHA/assets/js/services-cinematic/scene/post.js';
import { computeStops, sampleStops, fullSpan, riseValues, sunHeight } from '../wp-content/themes/ISHA/assets/js/services-cinematic/stops.js';
import { mountCards } from './lookdev-cards.js';

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
	reduced: q.get( 'reduced' ) === '1',
};
// grain=0: tắt grain (đo chuyển động thật của cảnh bằng tools/motion-check.mjs, không lẫn hạt phim đổi mẫu mỗi khung).
if ( q.get( 'grain' ) === '0' ) {
	CONFIG.post.grainScale = 0;
}
// off=mist,clouds,sun,dust,bloom,grain: tắt từng lớp (truy lỗi hình: chụp có / không một lớp rồi so).
const off = new Set( ( q.get( 'off' ) || '' ).split( ',' ).filter( Boolean ) );
if ( off.has( 'grain' ) ) {
	CONFIG.post.grainScale = 0;
}
if ( off.has( 'clouds' ) ) {
	CONFIG.sky.cloudGain = 0;
}

// Điểm dừng thật (stops.js). Tính lại mỗi khung (N ≤ 6, rẻ) để bảng GUI đổi CONFIG.stops thấy ngay.
const N_STOPS = Math.max( 1, Math.min( 6, parseInt( q.get( 'n' ), 10 ) || 4 ) );
const IMG = q.get( 'photo' ) === 'png' ? { photo: PLATES + 'scene-source.png', clouds: [ PLATES + 'clouds-4.png', PLATES + 'clouds-5.png' ] } : { photo: THEME + 'assets/img/scene/scene-source.webp', clouds: [ THEME + 'assets/img/scene/clouds-4.webp', THEME + 'assets/img/scene/clouds-5.webp' ] };
const pathFor = ( portrait ) => ( portrait ? CONFIG.stops.path.portrait : CONFIG.stops.path.desktop );
function previewStop( p, portrait, out = {} ) {
	return sampleStops( computeStops( N_STOPS, CONFIG.stops, portrait ), fullSpan( p, N_STOPS ), out, CONFIG.hold );
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
	const depth = await createDepth( THREE, ctx, { photoUrl: IMG.photo, mapUrl: await resolveMapUrl( state.source ), cfg: CONFIG.depth } );
	const tDepth = performance.now() - t0;
	const depthView = depth.createView();

	const palette = toVectors( derivePalette( readTokens(), CONFIG.colors ), THREE );
	const portraitStart = canvas.clientWidth < canvas.clientHeight;
	const preset = portraitStart ? CONFIG.presets.LITE : CONFIG.presets.FULL_HIGH;
	const cam = createCamera( THREE, { cfg: CONFIG.camera, plateCfg: CONFIG.plate, mobile: portraitStart } );
	const plate = createPlate( THREE, { depth, palette, cfg: CONFIG.plate, cut: CONFIG.cut, rim: CONFIG.rim, framing: CONFIG.framing, grid: preset.grid } );
	// Mây đơn lẻ: nền trong suốt (bỏ clouds.jpg: vết vá watermark hiện thành khung chữ nhật trôi trên trời).
	const clouds = [];
	for ( const url of IMG.clouds ) {
		if ( await probe( url ) ) {
			const t = await new THREE.TextureLoader().loadAsync( url );
			t.colorSpace = THREE.NoColorSpace;
			clouds.push( t );
		}
	}
	const sky = createSky( THREE, { palette, cfg: CONFIG.sky, clouds } );
	const sun = createSun( THREE, { palette, cfg: CONFIG.sun, occlusion: plate.occlusionUniforms, rays: preset.rays } );
	const mist = createMist( THREE, { palette, cfg: CONFIG.mist, occlusion: plate.occlusionUniforms, count: preset.mist, sunZ: CONFIG.sun.z } );
	const post = createPost( THREE, { cfg: CONFIG.post, bloom: preset.bloom && ! off.has( 'bloom' ), rtType: ctx.rtType } );
	const scene = new THREE.Scene();
	const dust = createDust( THREE, { palette, cfg: CONFIG.dust, count: preset.dust } );
	scene.add( sky.mesh, plate.group, sun.group, mist.group, dust.points );
	const idle = {};

	const buffer = new THREE.Vector2();
	let layout = null;
	const stop = previewStop( state.progress, portraitStart );
	const sunNdc = { x: 0, y: 0 };
	const rise = {};
	const relayout = () => {
		const fov = cam.resize( canvas.clientWidth, canvas.clientHeight );
		renderer.getDrawingBufferSize( buffer );
		layout = plate.layout( canvas.clientWidth, canvas.clientHeight, fov, buffer );
		mist.layout( layout );
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

	let cardsNote = '';
	const updateLabel = () => {
		const d = CONFIG.depth;
		const lines = [ `view ${ state.view } · p ${ state.progress.toFixed( 2 ) } · ${ canvas.clientWidth }×${ canvas.clientHeight } · nguồn độ sâu: ${ depth.source }` ];
		if ( isScene() && layout ) {
			lines.push( `chân trời ${ ( layout.horizonY * 100 ).toFixed( 1 ) }% · ảnh từ ${ ( layout.imageTop * 100 ).toFixed( 1 ) }% tới ${ ( layout.imageBottom * 100 ).toFixed( 1 ) }% khung · FOV ${ cam.camera.fov.toFixed( 2 ) }° (gốc ${ cam.baseFov.toFixed( 1 ) }°) · phóng gần nhất do dolly tối đa ${ ( ( cam.magnification - 1 ) * 100 ).toFixed( 1 ) }%` );
			lines.push( `độ cao mặt trời ${ ( rise.vignette === undefined ? 0 : sunHeight( stop.sunY, pathFor( canvas.clientWidth < canvas.clientHeight ) ) ).toFixed( 2 ) } · vignette ${ ( rise.vignette ?? 0 ).toFixed( 2 ) } · uBand ${ ( rise.band ?? 0 ).toFixed( 3 ) } · quầng ×${ ( rise.halo ?? 1 ).toFixed( 2 ) } · đĩa ×${ ( rise.sunScale ?? 1 ).toFixed( 2 ) } · tia dài ×${ ( rise.rayLength ?? 1 ).toFixed( 2 ) } toả ×${ ( rise.raySpread ?? 1 ).toFixed( 2 ) } rộng ×${ ( rise.rayWidth ?? 1 ).toFixed( 2 ) } z ${ ( rise.raysZ ?? 0 ).toFixed( 0 ) }` );
			if ( cardsNote ) {
				lines.push( cardsNote );
			}
			lines.push( `mặt trời (${ ( stop.sunX * 100 ).toFixed( 1 ) }%, ${ ( stop.sunY * 100 ).toFixed( 1 ) }%) · skyMix ${ stop.skyMix.toFixed( 2 ) } · rim ${ stop.rimIntensity.toFixed( 2 ) } · volumetric ${ stop.volumetric.toFixed( 2 ) } · sương ${ stop.fogDensity.toFixed( 2 ) } · exposure ${ stop.exposure.toFixed( 2 ) } · grain ${ ( stop.grain * 100 ).toFixed( 1 ) }% · mây: ${ clouds.length } đám${ off.size ? ' · TẮT: ' + [ ...off ].join( ', ' ) : '' }` );
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
		previewStop( state.progress, portrait, stop );
		sunNdc.x = stop.sunX * 2 - 1;
		sunNdc.y = 1 - stop.sunY * 2;
		const time = timer.getElapsed();
		const aspect = canvas.clientWidth / canvas.clientHeight;
		riseValues( sunHeight( stop.sunY, pathFor( portrait ) ), CONFIG.rise, rise );
		idleValues( time, CONFIG.idle, CONFIG.plate.near, state.reduced, idle );
		cam.update( state.progress, timer.getDelta(), idle.offset, rise.fov );
		plate.update( { sunNdc, rimIntensity: stop.rimIntensity, skyMix: stop.skyMix, fogDensity: stop.fogDensity, cutBand: rise.band } );
		sky.update( { skyMix: stop.skyMix, sunNdc, cloudOpacity: stop.cloudOpacity, time, aspect, horizonY: layout.horizonY } );
		sun.update( { camera: cam.camera, sunNdc, aspect, time, sunScale: stop.sunScale, sunIntensity: stop.sunIntensity, volumetric: stop.volumetric * idle.rays, haloBreath: idle.halo, rise } );
		mist.update( { time, sunNdc, aspect, fogDensity: stop.fogDensity } );
		dust.update( { camera: cam.camera, refTan: { x: layout.refTanX, y: layout.refTanY }, time, sunNdc, aspect, pixelRatio: renderer.getPixelRatio(), intensity: stop.dustIntensity } );
		const scenePass = state.view === 'scene';
		sun.group.visible = scenePass && ! off.has( 'sun' );
		mist.group.visible = scenePass && ! off.has( 'mist' );
		dust.points.visible = scenePass && ! state.reduced && ! off.has( 'dust' );
		renderer.toneMappingExposure = stop.exposure;
		renderer.setRenderTarget( null );
		renderer.render( scene, cam.camera );
		if ( scenePass ) {
			post.render( renderer, { time, grain: stop.grain, vignette: rise.vignette } );
		}
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

	// Card thật (bước 7): lỗi tải trang WordPress không chặn cảnh, chỉ báo trên nhãn.
	let cards = null;
	if ( q.get( 'cards' ) !== '0' ) {
		try {
			cards = await mountCards( { q, state } );
		} catch ( err ) {
			cardsNote = 'card: ' + err.message;
			console.warn( '[ISHA lookdev] card', err );
		}
	}
	const cardUi = { show: true };

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
		gui.add( state, 'reduced' ).name( 'giảm chuyển động' );

		const fPlate = gui.addFolder( 'Lưới ảnh (3.3 – 3.5, 7)' );
		fPlate.add( CONFIG.plate, 'near', 300, 1200, 10 ).name( 'uNearDist' );
		fPlate.add( CONFIG.plate, 'far', 600, 3000, 10 ).name( 'uFarDist' );
		fPlate.add( CONFIG.plate, 'horizonUV', 0.05, 0.4, 0.005 ).name( 'uHorizonUV (ảnh, từ trên)' ).onChange( relayout );
		fPlate.add( CONFIG.plate, 'mixNear', 0, 1, 0.01 ).name( 'depthMix gần' );
		fPlate.add( CONFIG.plate, 'mixFar', 0, 1, 0.01 ).name( 'depthMix xa' );
		fPlate.add( CONFIG.plate, 'gain', 0.5, 2, 0.01 ).name( 'độ sáng (gain)' );
		fPlate.add( CONFIG.plate, 'aerial', 0, 1, 0.01 ).name( 'xa ngả màu trời (aerial)' );
		fPlate.add( CONFIG.plate, 'haze', 0, 1, 0.01 ).name( 'không khí phủ lớp xa (haze)' );
		fPlate.add( CONFIG.plate, 'stretchLo', 1, 4, 0.05 ).name( 'chống giãn: bắt đầu mờ (lần)' );
		fPlate.add( CONFIG.plate, 'stretchHi', 1.2, 8, 0.05 ).name( 'chống giãn: trong suốt (lần)' );
		const fCut = gui.addFolder( 'Cắt trời (3.4)' );
		fCut.add( CONFIG.cut, 'lumLow', 0.4, 0.9, 0.005 ).name( 'uSkyLumLow' );
		fCut.add( CONFIG.cut, 'lumHigh', 0.4, 0.95, 0.005 ).name( 'uSkyLumHigh' );
		fCut.add( CONFIG.cut, 'bandY', 0, 0.4, 0.005 ).name( 'uHorizonY (mép cắt theo hàng)' );
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
		const fSunPath = gui.addFolder( 'Điểm dừng (6): đường mặt trời, [đầu, cuối]' );
		[ 'desktop', 'portrait' ].forEach( ( k ) => {
			fSunPath.add( CONFIG.stops.path[ k ], '0', 0, 1, 0.005 ).name( k + ': x đầu' );
			fSunPath.add( CONFIG.stops.path[ k ], '1', 0, 1, 0.005 ).name( k + ': y đầu' );
			fSunPath.add( CONFIG.stops.path[ k ], '2', 0, 1, 0.005 ).name( k + ': x cuối' );
			fSunPath.add( CONFIG.stops.path[ k ], '3', 0, 1, 0.005 ).name( k + ': y cuối' );
		} );
		fSunPath.add( CONFIG, 'hold', 0, 0.8, 0.01 ).name( 'vùng giữ quanh điểm dừng' );
		for ( const k of [ 'sunIntensity', 'rimIntensity', 'volumetric', 'fogDensity', 'cloudOpacity', 'dustIntensity', 'exposure', 'grain' ] ) {
			const hi = k === 'grain' ? 0.1 : 2;
			fSunPath.add( CONFIG.stops[ k ], '0', 0, hi, hi / 200 ).name( k + ' · đầu' );
			fSunPath.add( CONFIG.stops[ k ], '1', 0, hi, hi / 200 ).name( k + ' · cuối' );
		}
		const fSun = gui.addFolder( 'Mặt trời (10)' );
		fSun.add( CONFIG.sun, 'z', 700, 1300, 5 ).name( 'sunZ (khoảng cách)' );
		fSun.add( CONFIG.sun, 'radius', 0.005, 0.06, 0.001 ).name( 'bán kính đĩa (chiều cao khung)' );
		fSun.add( CONFIG.sun, 'haloScale', 2, 12, 0.1 ).name( 'quầng / đĩa' );
		fSun.add( CONFIG.sun, 'disc', 0, 2, 0.01 ).name( 'độ sáng đĩa' );
		fSun.add( CONFIG.sun, 'halo', 0, 1.5, 0.01 ).name( 'độ sáng quầng' );
		fSun.add( CONFIG.sun, 'haloVeil', 0, 1, 0.01 ).name( 'quầng không bị che (khí quyển)' );
		fSun.add( CONFIG.sun, 'streakLength', 0, 0.5, 0.005 ).name( 'vệt ngang: dài' );
		fSun.add( CONFIG.sun, 'streakAlpha', 0, 0.5, 0.005 ).name( 'vệt ngang: alpha' );
		fSun.add( CONFIG.sun, 'rays', 0, 1, 0.01 ).name( 'tia thể tích' );
		fSun.add( CONFIG.sun, 'rayLength', 0.2, 1.5, 0.01 ).name( 'tia: dài' );
		const fMist = gui.addFolder( 'Sương (11)' );
		fMist.add( CONFIG.mist, 'sunLit', 0, 2, 0.01 ).name( 'sáng lên gần mặt trời' );
		fMist.add( CONFIG.mist, 'scale', 0.3, 3, 0.01 ).name( 'cỡ vân' );
		CONFIG.mist.bands.forEach( ( b, i ) => {
			const f = fMist.addFolder( 'dải ' + i + ' (z ' + b[ 0 ] + ')' ).close();
			f.add( b, '1', 0, 1, 0.005 ).name( 'tâm (hàng ảnh)' ).onChange( relayout );
			f.add( b, '2', 0.02, 0.6, 0.005 ).name( 'cao (tỉ lệ ảnh)' ).onChange( relayout );
			f.add( b, '3', 0, 1, 0.01 ).name( 'alpha' );
			f.add( b, '4', 0, 0.05, 0.001 ).name( 'trôi' );
		} );
		if ( cards ) {
			const fCard = gui.addFolder( 'Dãy card' );
			fCard.add( cardUi, 'show' ).name( 'hiện lớp card' );
			fCard.add( CONFIG.card, 'enter', 0.2, 2, 0.05 ).name( 'dãy hiện (giây)' );
			fCard.add( CONFIG.card, 'hide', 0.1, 1.5, 0.05 ).name( 'dãy ẩn (giây)' );
			fCard.add( CONFIG.card, 'stagger', 0, 0.4, 0.01 ).name( 'lệch pha giữa các card (giây)' );
			fCard.add( CONFIG.card, 'lift', 0, 80, 1 ).name( 'dâng lên (px)' );
			fCard.add( { flip: 0.8 }, 'flip', 0.2, 2, 0.05 ).name( 'lật card (giây, CSS)' ).onChange( ( v ) => cards.root.style.setProperty( '--isha-card-flip', `${ v }s` ) );
			fCard.add( { d: () => cards.dispatch( { type: 'DISMISS' } ) }, 'd' ).name( 'ẩn dãy (dismiss)' );
			fCard.add( { r: () => cards.dispatch( { type: 'REOPEN' } ) }, 'r' ).name( 'hiện lại (reopen)' );
		}

		const fIdle = gui.addFolder( 'Bụi, nhịp thở (12)' ).close();
		fIdle.add( CONFIG.dust, 'gain', 0, 5, 0.05 ).name( 'bụi: độ sáng (nhân)' );
		fIdle.add( CONFIG.dust, 'reach', 0.05, 1.5, 0.01 ).name( 'bụi: vùng sáng quanh mặt trời' );
		fIdle.add( CONFIG.dust, 'sizeK', 50, 800, 5 ).name( 'bụi: cỡ' );
		fIdle.add( CONFIG.dust, 'rise', 0, 0.03, 0.0005 ).name( 'bụi: trôi lên' );
		fIdle.add( CONFIG.idle, 'breath', 0, 0.01, 0.0001 ).name( 'khung hình thở (tỉ lệ)' );
		fIdle.add( CONFIG.idle, 'rays', 0, 0.3, 0.005 ).name( 'tia thở' );
		fIdle.add( CONFIG.idle, 'halo', 0, 0.15, 0.005 ).name( 'quầng thở' );
		const fPost = gui.addFolder( 'Hậu kỳ (14)' );
		fPost.add( CONFIG.post, 'grainScale', 0, 3, 0.01 ).name( 'grain (nhân)' );
		fPost.add( { bloomOn: post.bloom }, 'bloomOn' ).name( 'bloom (tầng desktop)' ).onChange( ( v ) => post.setBloom( v ) );
		fPost.add( CONFIG.post, 'bloom', 0, 2, 0.01 ).name( 'bloom: độ mạnh' );
		fPost.add( CONFIG.post, 'bloomThreshold', 0.5, 0.99, 0.005 ).name( 'bloom: ngưỡng' );
		fPost.add( CONFIG.post, 'bloomRadius', 0.3, 4, 0.05 ).name( 'bloom: bán kính' );

		// Theo độ cao mặt trời: mỗi giá trị có cặp [đầu, cuối].
		const fRise = gui.addFolder( 'Khi mặt trời nhô lên [đầu → cuối]' );
		const RISE_UI = [
			[ 'vignette', 'vignette', 0, 0.8, 0.01 ],
			[ 'band', 'uBand (mép cắt trời)', 0.001, 0.15, 0.001 ],
			[ 'fov', 'FOV dọc cộng thêm (°)', -6, 3, 0.1 ],
			[ 'halo', 'quầng (nhân)', 0, 3, 0.01 ],
			[ 'sunScale', 'bán kính đĩa (nhân)', 0.5, 2, 0.01 ],
			[ 'rayLength', 'tia: dài (nhân)', 0, 2, 0.01 ],
			[ 'raySpread', 'tia: độ toả góc (nhân)', 0, 2, 0.01 ],
			[ 'rayWidth', 'tia: độ rộng (nhân)', 0, 3, 0.01 ],
			[ 'raysZ', 'tia: khoảng cách', 700, 1300, 5 ],
			[ 'rayTurn', 'tia: nhịp lắc', 0, 1, 0.005 ],
		];
		for ( const [ k, n, lo, hi, st ] of RISE_UI ) {
			fRise.add( CONFIG.rise[ k ], '0', lo, hi, st ).name( n + ' · đầu' );
			fRise.add( CONFIG.rise[ k ], '1', lo, hi, st ).name( n + ' · cuối' );
		}
		const fSky = gui.addFolder( 'Trời (8)' );
		fSky.add( CONFIG.sky, 'sunGlow', 0, 1, 0.01 ).name( 'Golden quanh mặt trời' );
		fSky.add( CONFIG.sky, 'sunGlowRadius', 0.05, 1, 0.01 ).name( 'bán kính quầng trời' );
		fSky.add( CONFIG.sky, 'horizonGolden', 0, 1, 0.01 ).name( 'Golden chân trời lúc toàn quang' );
		fSky.add( CONFIG.sky, 'cloudGain', 0, 3, 0.01 ).name( 'mây (nhân)' );
		CONFIG.sky.clouds.forEach( ( c, i ) => {
			const f = fSky.addFolder( 'mây ' + ( i + 1 ) ).close();
			f.add( c, 'x', -0.3, 1.3, 0.005 ).name( 'x (tỉ lệ rộng)' );
			f.add( c, 'y', 0, 0.5, 0.005 ).name( 'y (tỉ lệ cao)' );
			f.add( c, 'width', 0.05, 1, 0.005 ).name( 'cỡ (tỉ lệ rộng)' );
			f.add( c, 'speed', 0, 0.05, 0.0005 ).name( 'trôi' );
		} );
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
		if ( cards ) {
			cards.setVisible( isScene() && cardUi.show );
			cards.update( state.progress );
		}
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
	Object.assign( window.__lookdev, { depth, ctx, plate, sky, cam, sun, mist, post, dust, cards, CONFIG } );
}

main().catch( ( err ) => {
	console.error( '[ISHA lookdev]', err );
	window.__lookdev.errors.push( String( err && err.stack ? err.stack : err ) );
	label.textContent = 'Lỗi: ' + ( err && err.message ? err.message : err );
	window.__lookdev.ready = true;
} );
