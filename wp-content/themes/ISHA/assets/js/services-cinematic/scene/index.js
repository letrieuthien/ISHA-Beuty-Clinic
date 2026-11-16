/**
 * Cảnh v3 trên trang thật (bước 8): một ảnh thật + độ sâu tự suy từ độ sáng + camera 2.5D (docs/isha-lookdev-prompt-v3.md), cùng các module
 * đã duyệt trên tools/lookdev.html. Khác lookdev: progress lấy từ store (camera theo store.smoothed, mặt trời / trời / sương theo
 * store.sunSmoothed, chậm hơn để mặt trời trôi theo sau cuộn), điểm dừng thật (stops.js, hai bộ: khung ngang và khung dọc), bậc chất
 * lượng theo thang của tier.js và governor hạ bậc khi khung hình chậm.
 *
 * Ảnh: assets/img/scene/scene-source.webp (3840 px) cho màn hình lớn, scene-source-m.webp (1920 px) cho điện thoại / màn hình nhỏ; mây
 * clouds-4.webp, clouds-5.webp (tools/bake-scene.mjs sinh từ ảnh nguồn). Không dò depth.webp (độ sâu tự suy, README mục 9).
 *
 * Vòng render chỉ chạy giữa start() và stop() (main.js gọi theo IntersectionObserver và visibilitychange; bước 9 gom vào lifecycle.js
 * cùng xử lý mất context WebGL).
 */
import * as THREE from 'three';
import { CONFIG } from '../config.js';
import { computeStops, sampleStops, fullSpan, riseValues, sunHeight } from '../stops.js';
import { readTokens, derivePalette, toVectors } from '../tokens.js';
import { createGovernor } from '../tier.js';
import { createRenderer } from './renderer.js';
import { createDepth } from './depth.js';
import { createPlate } from './plate.js';
import { createSky } from './sky.js';
import { createCamera, idleValues } from './camera.js';
import { createDust } from './dust.js';
import { createSun } from './sun.js';
import { createMist } from './mist.js';
import { createPost } from './post.js';

/**
 * @param {object}   o
 * @param {HTMLCanvasElement} o.canvas
 * @param {object}   o.store       store.js (đọc smoothed / sunSmoothed, gọi damp / dampSun).
 * @param {number}   o.N           Số liệu trình (số điểm dừng).
 * @param {ReadonlyArray<object>} o.ladder Thang chất lượng (tier.js buildLadder); bậc cuối là 2D.
 * @param {number}   o.step        Bậc bắt đầu.
 * @param {boolean}  o.mobile      Thiết bị cảm ứng / di động (không parallax chuột, damping mobile).
 * @param {boolean}  o.reduced     Giảm chuyển động (bình thường không tới đây: chế độ tĩnh).
 * @param {string}   o.imgBase     URL thư mục assets/img/scene/ (có / cuối).
 * @param {Function} [o.onStep]    Gọi khi governor hạ bậc (main.js lưu bậc vào phiên).
 * @param {Function} [o.onFallback] Gọi khi governor quyết định xuống 2D.
 */
export async function createScene( { canvas, store, N, ladder, step: startStep, mobile, reduced, imgBase, onStep, onFallback } ) {
	const top = ladder[ 0 ];
	let step = Math.min( startStep, ladder.length - 2 );
	let quality = ladder[ step ];
	const ctx = createRenderer( THREE, { canvas, dprCap: quality.dpr, toneMapping: CONFIG.toneMapping } );
	const renderer = ctx.renderer;

	// Ảnh lớn chỉ khi khung đủ rộng để thấy khác biệt (ảnh fit theo chiều ngang khung).
	const wide = Math.max( window.innerWidth, window.innerHeight ) * Math.min( window.devicePixelRatio || 1, quality.dpr );
	const photoUrl = imgBase + ( ! mobile && wide > 1900 ? 'scene-source.webp' : 'scene-source-m.webp' );
	const loader = new THREE.TextureLoader();
	const [ depth, ...clouds ] = await Promise.all( [
		createDepth( THREE, ctx, { photoUrl, mapUrl: null, cfg: CONFIG.depth } ),
		...[ 'clouds-4.webp', 'clouds-5.webp' ].map( ( f ) => loader.loadAsync( imgBase + f ).then( ( t ) => {
			t.colorSpace = THREE.NoColorSpace;
			return t;
		} ).catch( () => null ) ),
	] );

	const palette = toVectors( derivePalette( readTokens(), CONFIG.colors ), THREE );
	const cam = createCamera( THREE, { cfg: CONFIG.camera, plateCfg: CONFIG.plate, mobile } );
	const plate = createPlate( THREE, { depth, palette, cfg: CONFIG.plate, cut: CONFIG.cut, rim: CONFIG.rim, framing: CONFIG.framing, grid: quality.grid } );
	const sky = createSky( THREE, { palette, cfg: CONFIG.sky, clouds: clouds.filter( Boolean ) } );
	// Số tia / dải sương / hạt bụi dựng theo bậc cao nhất của thang (chọn bậc bằng tay trong debug vẫn nâng lại được).
	const sun = createSun( THREE, { palette, cfg: CONFIG.sun, occlusion: plate.occlusionUniforms, rays: top.rays } );
	const mist = createMist( THREE, { palette, cfg: CONFIG.mist, occlusion: plate.occlusionUniforms, count: top.mist, sunZ: CONFIG.sun.z } );
	const dust = createDust( THREE, { palette, cfg: CONFIG.dust, count: top.dust } );
	const post = createPost( THREE, { cfg: CONFIG.post, bloom: !! top.bloom, rtType: ctx.rtType } );
	const scene = new THREE.Scene();
	scene.add( sky.mesh, plate.group, sun.group, mist.group, dust.points );

	// Điểm dừng: khung ngang và khung dọc có đường đi mặt trời khác nhau.
	let stops = { landscape: null, portrait: null };
	const refreshStops = () => {
		stops = { landscape: computeStops( N, CONFIG.stops, false ), portrait: computeStops( N, CONFIG.stops, true ) };
	};
	refreshStops();

	const buffer = new THREE.Vector2();
	let layout = null;
	const relayout = () => {
		const fov = cam.resize( canvas.clientWidth, canvas.clientHeight );
		renderer.getDrawingBufferSize( buffer );
		layout = plate.layout( canvas.clientWidth, canvas.clientHeight, fov, buffer );
		mist.layout( layout );
	};

	function applyQuality( q ) {
		if ( ctx.setDprCap( q.dpr ) ) {
			relayout();
		}
		post.setBloom( !! q.bloom );
		plate.setGrid( q.grid );
		mist.setCount( q.mist );
		dust.setCount( q.dust );
		sun.setRays( q.rays );
	}

	const s = {};
	const rise = {};
	const idle = {};
	const sunNdc = { x: 0, y: 0 };
	const timer = new THREE.Timer();
	timer.connect( document );
	const governor = createGovernor( CONFIG.governor );

	function frame() {
		const dt = Math.min( 0.1, timer.getDelta() );
		const time = timer.getElapsed();
		store.damp( dt, mobile ? CONFIG.damping.mobile : CONFIG.damping.desktop );
		store.dampSun( dt );
		const w = canvas.clientWidth;
		const h = canvas.clientHeight;
		const portrait = w < h;
		sampleStops( portrait ? stops.portrait : stops.landscape, fullSpan( store.state.sunSmoothed, N ), s, CONFIG.hold );
		sunNdc.x = s.sunX * 2 - 1;
		sunNdc.y = 1 - s.sunY * 2;
		const aspect = w / Math.max( 1, h );
		riseValues( sunHeight( s.sunY, portrait ? CONFIG.stops.path.portrait : CONFIG.stops.path.desktop ), CONFIG.rise, rise );
		idleValues( time, CONFIG.idle, CONFIG.plate.near, reduced, idle );
		cam.update( store.state.smoothed, dt, idle.offset, rise.fov );
		plate.update( { sunNdc, rimIntensity: s.rimIntensity, skyMix: s.skyMix, fogDensity: s.fogDensity, cutBand: rise.band } );
		sky.update( { skyMix: s.skyMix, sunNdc, cloudOpacity: s.cloudOpacity, time, aspect, horizonY: layout.horizonY } );
		sun.update( { camera: cam.camera, sunNdc, aspect, time, sunScale: s.sunScale, sunIntensity: s.sunIntensity, volumetric: s.volumetric * idle.rays, haloBreath: idle.halo, rise } );
		mist.update( { time, sunNdc, aspect, fogDensity: s.fogDensity } );
		dust.update( { camera: cam.camera, refTan: { x: layout.refTanX, y: layout.refTanY }, time, sunNdc, aspect, pixelRatio: renderer.getPixelRatio(), intensity: s.dustIntensity } );
		dust.points.visible = ! reduced;
		renderer.toneMappingExposure = s.exposure;
		renderer.setRenderTarget( null );
		renderer.render( scene, cam.camera );
		post.render( renderer, { time, grain: s.grain, vignette: rise.vignette } );
	}

	const ro = new ResizeObserver( () => {
		if ( ctx.resize( canvas.clientWidth, canvas.clientHeight ) ) {
			relayout();
			if ( layout ) {
				frame(); // canvas vừa bị xoá: vẽ lại ngay trong cùng lượt
			}
		}
	} );
	ro.observe( canvas );
	ctx.resize( canvas.clientWidth, canvas.clientHeight );
	relayout();
	applyQuality( quality );
	await renderer.compileAsync( scene, cam.camera );

	const onPointer = ( e ) => cam.setPointer( ( e.clientX / window.innerWidth ) * 2 - 1, -( ( e.clientY / window.innerHeight ) * 2 - 1 ) );
	if ( ! mobile ) {
		window.addEventListener( 'pointermove', onPointer, { passive: true } );
	}

	let raf = 0;
	let last = 0;
	function loop( now ) {
		raf = requestAnimationFrame( loop );
		timer.update( now );
		frame();
		if ( last ) {
			const next = ladder[ step + 1 ];
			if ( next && governor.sample( now - last, now, next.mode === '2D' ) ) {
				if ( next.mode === '2D' ) {
					console.info( '[ISHA svc] governor: khung hình quá chậm, chuyển sang poster 2D.' );
					if ( onFallback ) {
						onFallback();
					}
					return;
				}
				api.setStep( step + 1 );
				console.info( `[ISHA svc] governor hạ xuống bậc ${ step }:`, quality );
				if ( onStep ) {
					onStep( step );
				}
			}
		}
		last = now;
	}

	const api = {
		get ladder() {
			return ladder;
		},
		get step() {
			return step;
		},
		get quality() {
			return quality;
		},
		governor,
		/** Đổi bậc chất lượng (governor hoặc debug chọn tay). Không nhận bậc 2D. */
		setStep( i ) {
			step = Math.max( 0, Math.min( ladder.length - 2, i ) );
			quality = ladder[ step ];
			applyQuality( quality );
			governor.reset( performance.now(), CONFIG.governor.settleMs );
		},
		/** Tính lại điểm dừng sau khi debug đổi CONFIG.stops. */
		refreshStops,
		/** Vẽ một khung ngay (trước khi canvas hiện ra, hoặc khi vòng render đang dừng). */
		renderOnce() {
			timer.update( performance.now() );
			frame();
		},
		start() {
			if ( raf ) {
				return;
			}
			last = 0;
			timer.reset();
			governor.reset( performance.now() );
			raf = requestAnimationFrame( loop );
		},
		stop() {
			cancelAnimationFrame( raf );
			raf = 0;
		},
		get running() {
			return raf !== 0;
		},
		destroy() {
			api.stop();
			ro.disconnect();
			window.removeEventListener( 'pointermove', onPointer );
			timer.dispose();
			for ( const m of [ plate, sky, sun, mist, dust, post, depth ] ) {
				m.dispose();
			}
			clouds.forEach( ( t ) => t && t.dispose() );
			ctx.dispose();
		},
	};
	return api;
}
