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

export async function createScene( { canvas, section, stage, rays, store, stops, data, mode, mobile, reduced, onFatal = () => {} } ) {
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

	const rail = buildRail( THREE, ( x, z ) => terrain.height( x, z ) );

	// Trạng thái dùng lại mỗi khung hình (không cấp phát trong vòng render).
	const sample = {};
	const sunColor = new THREE.Color();
	const fogColor = new THREE.Color();
	const fogSun = new THREE.Color();
	const ground = new THREE.Color();
	const sunDir = new THREE.Vector3();
	const tmp = new THREE.Vector3();
	const size = { w: 1, h: 1 };
	let raysKey = '';
	let stopsRef = stops;
	const timer = new THREE.Timer();
	const k = mobile ? CONFIG.damping.mobile : CONFIG.damping.desktop;
	const mouse = { x: 0, y: 0, tx: 0, ty: 0 };
	let lastRender = 0;

	function apply( time ) {
		// Mặt trời, trời, sương theo sunSmoothed (chậm, trôi theo sau cuộn); camera theo smoothed.
		sampleStops( stopsRef, store.state.sunSmoothed, sample, CONFIG.hold );
		const rgb = sunToneRGB( palette, sample.sunTone );
		sunColor.setRGB( rgb[ 0 ], rgb[ 1 ], rgb[ 2 ], THREE.SRGBColorSpace );

		// Camera theo ray (chưa lệch chuột) → hướng mặt trời = tia từ camera qua điểm (sunX, sunY) trên màn hình. Lệch chuột áp SAU nên mặt
		// trời là hướng cố định trong thế giới và dịch đúng theo parallax.
		rail.apply( camera, store.state.smoothed, CONFIG.cameraPitchDeg );
		camera.updateMatrixWorld();
		sunDir.set( sample.sunX * 2 - 1, 1 - sample.sunY * 2, 0.5 ).unproject( camera ).sub( camera.position ).normalize();
		if ( mouse.x || mouse.y ) {
			camera.rotateY( THREE.MathUtils.degToRad( -mouse.x * CONFIG.mouseParallaxDeg ) );
			camera.rotateX( THREE.MathUtils.degToRad( -mouse.y * CONFIG.mouseParallaxDeg * 0.6 ) );
			camera.updateMatrixWorld();
		}

		// Sương pastel ấm: Skin nhạt → Daylight, chút Golden; ngả về màu mặt trời khi nhìn về phía mặt trời.
		fogColor.copy( colors.skinSoft ).lerp( colors.daylight, 0.35 ).lerp( colors.golden, 0.15 );
		fogSun.copy( fogColor ).lerp( sunColor, 0.55 );
		ground.copy( colors.green ).lerp( colors.shadow, 0.35 );

		sky.update( sample, colors, {
			sunDir,
			sunColor,
			floorMix: sample.skyFloor * CONFIG.colors.skyFloorScale,
			belowColor: fogColor,
			time,
			gain: 1,
			radiusDeg: CONFIG.sun.radiusDeg,
			discIntensity: CONFIG.sun.discIntensity,
			halo: sample.halo * CONFIG.sun.haloScale,
			tone: sample.sunTone,
		} );

		// Vòng tia line-art (SVG) đặt đúng vị trí chiếu của mặt trời.
		if ( rays ) {
			tmp.copy( camera.position ).addScaledVector( sunDir, 1000 ).project( camera );
			const x = ( ( tmp.x + 1 ) / 2 ) * size.w;
			const y = ( ( 1 - tmp.y ) / 2 ) * size.h;
			const o = Math.min( 1, sample.raysOpacity * CONFIG.sun.raysScale );
			const key = x.toFixed( 1 ) + ',' + y.toFixed( 1 ) + ',' + o.toFixed( 3 );
			if ( key !== raysKey ) {
				raysKey = key;
				rays.style.transform = 'translate3d(' + x.toFixed( 1 ) + 'px,' + y.toFixed( 1 ) + 'px,0) translate(-50%,-50%)';
				rays.style.opacity = o.toFixed( 3 );
			}
		}

		const u = terrain.uniforms;
		u.uSunDir.value.copy( sunDir );
		u.uSunColor.value.copy( sunColor );
		u.uSunIntensity.value = sample.sunIntensity;
		u.uSkyColor.value.copy( colors.daylight );
		u.uGroundColor.value.copy( ground );
		u.uFogColor.value.copy( fogColor );
		u.uFogSunColor.value.copy( fogSun );
		u.uFogDensity.value = sample.fogDensity * CONFIG.fogScale;

		renderer.toneMappingExposure = sample.exposure * CONFIG.exposureScale * BASE_EXPOSURE;
	}

	// Cỡ vòng tia theo bán kính đĩa mặt trời trên màn hình: tia trong icon FOCUS-MINDFUL bắt đầu ở bán kính 35/100 của khung, nên đường
	// kính SVG = 2 · bán kính đĩa (px) · 100 / 35 (cộng 4% hở nhỏ).
	function sizeRays() {
		if ( ! rays ) {
			return;
		}
		const pxPerUnit = size.h / ( 2 * Math.tan( THREE.MathUtils.degToRad( camera.fov ) / 2 ) );
		const r = Math.tan( THREE.MathUtils.degToRad( CONFIG.sun.radiusDeg ) ) * pxPerUnit;
		const d = Math.round( 2 * r * ( 100 / 35 ) * 1.04 );
		rays.style.width = d + 'px';
		rays.style.height = d + 'px';
	}

	function resize( w, h ) {
		if ( ! w || ! h ) {
			return;
		}
		renderer.setSize( w, h, false );
		size.w = w;
		size.h = h;
		raysKey = '';
		camera.aspect = w / h;
		camera.fov = w < h ? 56 : 45; // khung dọc mở rộng FOV để vẫn thấy đỉnh
		camera.updateProjectionMatrix();
		sizeRays();
	}

	function frame( ts ) {
		timer.update( ts );
		const dt = Math.min( timer.getDelta(), 0.1 );
		const camMoving = store.damp( dt, k );
		const sunMoving = store.dampSun( dt );
		const changed = camMoving || sunMoving;
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

	// Vào giữa section (tải lại trang, nhảy neo): bắt đầu từ vị trí hiện tại thay vì quét từ đầu.
	store.state.smoothed = store.state.progress;
	store.state.sunSmoothed = store.state.progress;
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
		sizeRays,
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
