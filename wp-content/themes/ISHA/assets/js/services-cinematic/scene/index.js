/**
 * Cảnh WebGL của section "Bình minh lan tỏa" (giai đoạn 4: bầu trời, địa hình, sương, camera rail; mây hồng, cỏ, cây, hậu kỳ ở giai đoạn sau).
 *
 * Renderer: WebGLRenderer, outputColorSpace sRGB, NeutralToneMapping (AgX chọn được trong debug; KHÔNG ACES vì lệch hue của vàng), DPR giới
 * hạn theo tầng chất lượng. Mỗi khung hình: làm mượt progress (store.damp) → sampleStops → cập nhật trời/mặt trời/sương/địa hình → camera
 * theo ray. LITE chỉ render khi progress đổi hoặc theo nhịp nền 24 khung hình/giây. Không cấp phát object trong vòng render.
 * Shader được biên dịch trước (compileAsync) trong lúc poster còn che; main.js thêm .is-ready để canvas hiện dần.
 */
import { CONFIG } from '../config.js';
import { sampleStops } from '../stops.js';
import { readTokens, derivePalette, toLinearColors, sunToneRGB } from '../tokens.js';
import { buildSky } from './sky.js';
import { buildTerrain } from './terrain.js';
import { buildRail } from './rail.js';
import { createLifecycle } from './lifecycle.js';

const BASE_EXPOSURE = 0.62;

export async function createScene( { canvas, section, stage, store, stops, data, mode, mobile, onFatal = () => {} } ) {
	const [ THREE, { Sky } ] = await Promise.all( [ import( 'three' ), import( 'three/addons/objects/Sky.js' ) ] );
	const preset = { ...CONFIG.presets[ mode === 'FULL_HIGH' || mode === 'FULL' ? mode : 'LITE' ] };

	const renderer = new THREE.WebGLRenderer( { canvas, antialias: mode !== 'LITE', alpha: false, powerPreference: 'high-performance' } );
	renderer.outputColorSpace = THREE.SRGBColorSpace;
	renderer.toneMapping = CONFIG.toneMapping === 'AgX' ? THREE.AgXToneMapping : THREE.NeutralToneMapping;
	renderer.setPixelRatio( Math.min( window.devicePixelRatio || 1, preset.dpr ) );

	const scene = new THREE.Scene();
	const camera = new THREE.PerspectiveCamera( 45, 1, 1, 20000 );

	// Màu từ token.
	let palette = derivePalette( readTokens(), CONFIG.colors );
	let colors = toLinearColors( palette, THREE );

	const sky = buildSky( THREE, Sky );
	scene.add( sky.mesh );

	let terrain = buildTerrain( THREE, { seed: data.seed, cfg: CONFIG.terrain, grid: preset.grid, octaves: preset.octaves, colors } );
	scene.add( terrain.mesh );
	console.info( '[ISHA svc] địa hình', terrain.info );

	const rail = buildRail( THREE, ( x, z ) => terrain.height( x, z ), CONFIG.terrain.peakZ );

	// Trạng thái dùng lại mỗi khung hình (không cấp phát trong vòng render).
	const sample = {};
	const sunColor = new THREE.Color();
	const fogColor = new THREE.Color();
	const fogSun = new THREE.Color();
	const ground = new THREE.Color();
	let stopsRef = stops;
	const timer = new THREE.Timer();
	const k = mobile ? CONFIG.damping.mobile : CONFIG.damping.desktop;
	const mouse = { x: 0, y: 0, tx: 0, ty: 0 };
	let lastRender = 0;

	function apply( time ) {
		sampleStops( stopsRef, store.state.smoothed, sample, CONFIG.hold );
		const rgb = sunToneRGB( palette, sample.sunTone );
		sunColor.setRGB( rgb[ 0 ], rgb[ 1 ], rgb[ 2 ], THREE.SRGBColorSpace );

		// Sương: Skin nhạt → Daylight, ngả về màu mặt trời khi nhìn về phía mặt trời.
		fogColor.copy( colors.skinSoft ).lerp( colors.daylight, 0.45 );
		fogSun.copy( fogColor ).lerp( sunColor, 0.55 );
		ground.copy( colors.green ).lerp( colors.shadow, 0.35 );

		sky.update( sample, colors, { floorMix: CONFIG.colors.skyFloor, belowColor: fogColor, time, gain: 1 } );

		const u = terrain.uniforms;
		u.uSunDir.value.copy( sky.sunDir );
		u.uSunColor.value.copy( sunColor );
		u.uSunIntensity.value = sample.sunIntensity;
		u.uSkyColor.value.copy( colors.daylight );
		u.uGroundColor.value.copy( ground );
		u.uFogColor.value.copy( fogColor );
		u.uFogSunColor.value.copy( fogSun );
		u.uFogDensity.value = sample.fogDensity * CONFIG.fogScale;

		renderer.toneMappingExposure = sample.exposure * CONFIG.exposureScale * BASE_EXPOSURE;
		rail.apply( camera, store.state.smoothed, -mouse.x * CONFIG.mouseParallaxDeg, -mouse.y * CONFIG.mouseParallaxDeg * 0.6 );
	}

	function resize( w, h ) {
		if ( ! w || ! h ) {
			return;
		}
		renderer.setSize( w, h, false );
		camera.aspect = w / h;
		camera.fov = w < h ? 56 : 45; // khung dọc mở rộng FOV để vẫn thấy đỉnh
		camera.updateProjectionMatrix();
	}

	function frame( ts ) {
		timer.update( ts );
		const dt = Math.min( timer.getDelta(), 0.1 );
		const changed = store.damp( dt, k );
		let mouseMoving = false;
		if ( ! mobile ) {
			const km = 1 - Math.exp( -4 * dt );
			mouse.x += ( mouse.tx - mouse.x ) * km;
			mouse.y += ( mouse.ty - mouse.y ) * km;
			mouseMoving = Math.abs( mouse.tx - mouse.x ) + Math.abs( mouse.ty - mouse.y ) > 1e-3;
		}
		if ( mode === 'LITE' && ! changed && ! mouseMoving && ts - lastRender < 1000 / 24 ) {
			return;
		}
		lastRender = ts;
		apply( timer.getElapsed() );
		renderer.render( scene, camera );
	}

	const onMouse = ( e ) => {
		mouse.tx = ( e.clientX / window.innerWidth ) * 2 - 1;
		mouse.ty = ( e.clientY / window.innerHeight ) * 2 - 1;
	};
	if ( ! mobile ) {
		window.addEventListener( 'pointermove', onMouse, { passive: true } );
	}

	resize( stage.clientWidth, stage.clientHeight );
	apply( 0 );
	await renderer.compileAsync( scene, camera );
	renderer.render( scene, camera );

	const life = createLifecycle( { renderer, section, stage, canvas, frame, onResize: resize, onFatal } );

	function dispose() {
		life.destroy();
		window.removeEventListener( 'pointermove', onMouse );
		rail.toggleHelper( scene, false );
		sky.dispose();
		terrain.dispose();
		renderer.dispose();
	}

	return {
		THREE,
		renderer,
		scene,
		camera,
		get terrainInfo() {
			return terrain.info;
		},
		get sample() {
			return sample;
		},
		setStops( s ) {
			stopsRef = s;
		},
		setToneMapping( name ) {
			renderer.toneMapping = name === 'AgX' ? THREE.AgXToneMapping : THREE.NeutralToneMapping;
			scene.traverse( ( o ) => {
				if ( o.material ) {
					o.material.needsUpdate = true;
				}
			} );
		},
		refreshColors() {
			palette = derivePalette( readTokens(), CONFIG.colors );
			colors = toLinearColors( palette, THREE );
			terrain.setColors( colors );
		},
		rebuildTerrain() {
			scene.remove( terrain.mesh );
			terrain.dispose();
			terrain = buildTerrain( THREE, { seed: data.seed, cfg: CONFIG.terrain, grid: preset.grid, octaves: preset.octaves, colors } );
			scene.add( terrain.mesh );
			return terrain.info;
		},
		showRail( on ) {
			rail.toggleHelper( scene, on, colors.gold );
		},
		renderNow() {
			apply( timer.getElapsed() );
			renderer.render( scene, camera );
		},
		destroy: dispose,
	};
}
