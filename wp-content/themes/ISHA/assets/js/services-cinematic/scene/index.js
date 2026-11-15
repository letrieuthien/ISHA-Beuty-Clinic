/**
 * Cảnh WebGL của section "Bình minh lan tỏa": bầu trời + mặt trời (sky.js), địa hình (terrain.js), mây hồng (clouds.js), cỏ và cây
 * (vegetation.js), sương khí quyển, camera rail; sau cảnh là hậu kỳ (post.js: god rays, bloom, grain, chỉ bậc có post) rồi sun glare
 * (glare.js, mọi chế độ). Độ lộ của mặt trời sau núi tính trên CPU (horizon.js): glare mờ khi mặt trời nằm sau đỉnh, lóe khi vừa ló ra.
 *
 * Renderer: WebGLRenderer, outputColorSpace sRGB, NeutralToneMapping (AgX chọn được trong debug; KHÔNG ACES vì lệch hue của vàng), DPR giới
 * hạn theo bậc chất lượng. Mỗi khung hình: làm mượt progress (store.damp) → sampleStops → cập nhật trời/mặt trời/sương + bộ uniform dùng chung
 * (shading.js) → camera theo ray. LITE chỉ render khi progress đổi hoặc theo nhịp nền 24 khung hình/giây. Không cấp phát object trong vòng render.
 *
 * Chất lượng: dựng theo một bậc của thang governor (tier.js buildLadder; bậc đã hạ trong phiên áp ngay từ đầu). Governor đo khoảng cách giữa
 * các khung hình đã render và hạ bậc khi chậm: DPR (setPixelRatio), số cỏ / cây / mây (instanceCount, không dựng lại), LITE (render theo nhu
 * cầu). Bậc 2D: báo main.js qua onStep để huỷ cảnh và hiện poster. Không đổi được lúc chạy: antialias, lưới địa hình (giữ như lúc dựng).
 * Shader được biên dịch trước (compileAsync) trong lúc poster còn che; main.js thêm .is-ready để canvas hiện dần.
 */
import { CONFIG } from '../config.js';
import { sampleStops } from '../stops.js';
import { readTokens, derivePalette, toLinearColors, sunToneRGB } from '../tokens.js';
import { buildSky } from './sky.js';
import { buildTerrain } from './terrain.js';
import { buildClouds } from './clouds.js';
import { buildGrass, buildTrees } from './vegetation.js';
import { createSharedUniforms } from './shading.js';
import { buildRail } from './rail.js';
import { createLifecycle } from './lifecycle.js';
import { createGovernor } from '../tier.js';
import { buildGlare } from './glare.js';
import { buildHorizon } from './horizon.js';
import { createPost } from './post.js';

const BASE_EXPOSURE = 0.62;

export async function createScene( { canvas, section, stage, store, stops, data, ladder, step: startStep = 0, mobile, reduced, onStep = () => {}, onFatal = () => {} } ) {
	const [ THREE, { Sky } ] = await Promise.all( [ import( 'three' ), import( 'three/addons/objects/Sky.js' ) ] );
	// Bậc hiện tại trên thang governor (bậc cuối của thang là 2D, không dựng cảnh). Số cỏ / cây / mây dựng theo bậc này là mức tối đa trong
	// phiên; lưới địa hình và antialias theo chế độ của bậc.
	let step = Math.max( 0, Math.min( startStep, ladder.length - 2 ) );
	let quality = ladder[ step ];
	const base = CONFIG.presets[ quality.mode ];
	const deviceDpr = () => window.devicePixelRatio || 1;

	const renderer = new THREE.WebGLRenderer( { canvas, antialias: quality.mode !== 'LITE', alpha: false, powerPreference: 'high-performance' } );
	renderer.outputColorSpace = THREE.SRGBColorSpace;
	renderer.toneMapping = CONFIG.toneMapping === 'AgX' ? THREE.AgXToneMapping : THREE.NeutralToneMapping;
	renderer.setPixelRatio( Math.min( deviceDpr(), quality.dpr ) );

	const scene = new THREE.Scene();
	const camera = new THREE.PerspectiveCamera( 45, 1, 1, 20000 );

	// Màu từ token.
	let palette = derivePalette( readTokens(), CONFIG.colors );
	let colors = toLinearColors( palette, THREE );

	const sky = buildSky( THREE, Sky );
	scene.add( sky.mesh );

	// Ánh sáng / sương / thời gian dùng chung cho địa hình, cỏ, cây, mây (cập nhật một lần mỗi khung hình trong apply()).
	const shared = createSharedUniforms( THREE );

	let terrain = buildTerrain( THREE, { seed: data.seed, cfg: CONFIG.terrain, grid: base.grid, octaves: base.octaves, colors, shared } );
	scene.add( terrain.mesh );
	console.info( '[ISHA svc] địa hình', terrain.info );

	const rail = buildRail( THREE, ( x, z ) => terrain.height( x, z ) );
	const height = ( x, z ) => terrain.height( x, z );
	const camStart = rail.path.getPointAt( 0 );
	const camEnd = rail.path.getPointAt( 1 );
	const portrait = stage.clientWidth < stage.clientHeight;
	// Góc ngẩng để đường chân trời nằm ở CONFIG.horizonY trên khung (camera.fov đổi theo tỉ lệ khung trong resize()).
	const pitchFor = ( fovDeg ) => THREE.MathUtils.radToDeg( Math.atan( ( 2 * CONFIG.horizonY - 1 ) * Math.tan( THREE.MathUtils.degToRad( fovDeg ) / 2 ) ) );
	// Khung của trời (sky.js): nghiêng về bố cục camera 2.5° đã duyệt + ánh xạ độ cao mặt trời từ điểm dừng đầu tới cuối về CONFIG.sun.skyElevDeg.
	const skyOut = { tiltRad: 0, nomA: 0, nomB: 1 };
	const skyFrame = () => {
		const D = THREE.MathUtils.DEG2RAD;
		const pitch = pitchFor( camera.fov );
		const tilt = pitch - 2.5;
		const halfTan = Math.tan( camera.fov * D / 2 );
		const tiltedAt = ( y ) => pitch - tilt + Math.atan( ( 0.5 - y ) * 2 * halfTan ) / D;
		const tS = tiltedAt( CONFIG.stops.sunYStart );
		const tE = tiltedAt( CONFIG.stops.sunYEnd );
		const [ nS, nE ] = CONFIG.sun.skyElevDeg;
		const k = ( nE - nS ) / Math.max( 0.5, tE - tS );
		skyOut.tiltRad = tilt * D;
		skyOut.nomB = k;
		skyOut.nomA = ( nS - k * tS ) * D;
		return skyOut;
	};

	const clouds = buildClouds( THREE, { count: quality.clouds, origin: camStart, colors, shared, seed: data.seed, elOffsetDeg: pitchFor( portrait ? 56 : 45 ) - 2.5 } );
	scene.add( clouds.mesh );
	const grass = buildGrass( THREE, { count: quality.grass, height, colors, shared, seed: data.seed, camZ: camStart.z, camEndZ: camEnd.z } );
	if ( grass ) {
		scene.add( grass.mesh );
	}
	const trees = buildTrees( THREE, { count: quality.trees, height, colors, shared, seed: data.seed, camZ: camStart.z, azMin: portrait ? 5 : 14 } );
	if ( trees ) {
		scene.add( trees.mesh );
	}
	const extras = [ clouds, grass, trees ].filter( Boolean );

	// Đường chân trời địa hình từ ba vị trí camera trên ray (đầu, giữa, cuối): độ lộ của mặt trời mỗi khung hình chỉ là tra bảng.
	const tH = performance.now();
	const horizon = buildHorizon( height, [ camStart, rail.path.getPointAt( 0.5 ), camEnd ], { half: CONFIG.terrain.size / 2 } );
	console.info( '[ISHA svc] đường chân trời', Math.round( performance.now() - tH ) + 'ms' );
	const glare = buildGlare( THREE, { colors } );
	// Hậu kỳ chỉ dựng khi bậc đầu có post (FULL, FULL_HIGH); governor tắt dần (bloom rồi god rays), không bật lại.
	const post = quality.post ? createPost( THREE ) : null;
	const postParams = { sunUv: { x: 0.5, y: 0.5 }, aspect: 1, rays: 0, rayTint: new THREE.Color(), rayThreshold: 0.6, bloom: 0, bloomThreshold: 0.86, grain: 0, seed: 0, dof: 0, dofEnd: 0.2 };
	const sunNdc = { x: 0, y: 0 };
	const glareNdc = { x: 0, y: 0 };
	const glareDir = new THREE.Vector3();
	const occ = { vis: 1, el: 0, az: 0 };
	let visSlow = -1;
	let pulse = 0;
	const offIndex = store.on( 'index', () => {
		if ( ! reduced ) {
			pulse = Math.max( pulse, CONFIG.glare.pulse );
		}
	} );

	// Trạng thái dùng lại mỗi khung hình (không cấp phát trong vòng render).
	const sample = {};
	const sunColor = new THREE.Color();
	const fogColor = new THREE.Color();
	const fogSun = new THREE.Color();
	const ground = new THREE.Color();
	const sunDir = new THREE.Vector3();
	const tmp = new THREE.Vector3();
	const size = { w: 1, h: 1 };
	let stopsRef = stops;
	const timer = new THREE.Timer();
	const mouse = { x: 0, y: 0, tx: 0, ty: 0 };
	let lastRender = 0;

	// Governor (tier.js): đo khoảng cách giữa các khung hình đã render, hạ một bậc khi chậm kéo dài. Không bao giờ tự nâng bậc.
	const gov = createGovernor( CONFIG.governor );
	let prevTs = -1;
	let prevRendered = false;
	let dead = false;
	let starts = 0;
	let ready = false; // đã biên dịch shader và vẽ khung đầu (resize lúc dựng không vẽ)

	function setQuality( q ) {
		quality = q;
		const dpr = Math.min( deviceDpr(), q.dpr );
		if ( dpr !== renderer.getPixelRatio() ) {
			renderer.setPixelRatio( dpr );
		}
		clouds.setCount( q.clouds );
		if ( grass ) {
			grass.setCount( q.grass );
		}
		if ( trees ) {
			trees.setCount( q.trees );
		}
		// q.post / q.bloom: hậu kỳ (giai đoạn 7, post.js) đọc từ đây.
		store.state.mode = q.mode;
		lastRender = 0;
	}

	function degrade() {
		const avg = gov.lastAvg;
		step++;
		const q = ladder[ step ];
		if ( q.mode === '2D' ) {
			// Dừng vẽ ngay; main.js huỷ cảnh ngoài vòng render.
			dead = true;
			setTimeout( () => onStep( step, q, avg ), 0 );
			return;
		}
		setQuality( q );
		onStep( step, q, avg );
	}

	function apply( time, dt = 0 ) {
		// Mặt trời, trời, sương theo sunSmoothed (chậm, trôi theo sau cuộn); camera theo smoothed.
		sampleStops( stopsRef, store.state.sunSmoothed, sample, CONFIG.hold );
		const rgb = sunToneRGB( palette, sample.sunTone );
		sunColor.setRGB( rgb[ 0 ], rgb[ 1 ], rgb[ 2 ], THREE.SRGBColorSpace );

		// Camera theo ray (chưa lệch chuột) → hướng mặt trời = tia từ camera qua điểm (sunX, sunY) trên màn hình. Lệch chuột áp SAU nên mặt
		// trời là hướng cố định trong thế giới và dịch đúng theo parallax.
		rail.apply( camera, store.state.smoothed, pitchFor( camera.fov ) );
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
			warm: CONFIG.colors.skyWarm,
			...skyFrame(),
		} );

		// Vị trí mặt trời trên màn hình (sau lệch chuột) cho glare và god rays.
		tmp.copy( camera.position ).addScaledVector( sunDir, 1000 ).project( camera );
		sunNdc.x = tmp.x;
		sunNdc.y = tmp.y;

		// Độ lộ của đĩa mặt trời sau núi; flash = lóe khi độ lộ tăng nhanh hơn bản trễ của nó (mặt trời vừa ló qua đỉnh) + pulse khi đổi liệu
		// trình. Lần đầu (tải giữa section) không lóe.
		const vis = horizon.visibility( sunDir, THREE.MathUtils.degToRad( CONFIG.sun.radiusDeg ), camera.position.z, occ );
		// Glare đặt ở tâm phần đĩa đang lộ: mặt trời còn nấp sau núi thì tia sao bật ra từ mép núi (không xuyên qua núi).
		glareDir.set( Math.sin( occ.az ) * Math.cos( occ.el ), Math.sin( occ.el ), -Math.cos( occ.az ) * Math.cos( occ.el ) );
		tmp.copy( camera.position ).addScaledVector( glareDir, 1000 ).project( camera );
		glareNdc.x = tmp.x;
		glareNdc.y = tmp.y;
		if ( visSlow < 0 ) {
			visSlow = vis;
		}
		if ( dt > 0 ) {
			visSlow += ( vis - visSlow ) * ( 1 - Math.exp( -1.2 * dt ) );
			pulse *= Math.exp( -2.2 * dt );
		}
		const flash = reduced ? 0 : Math.min( 1, Math.max( 0, vis - visSlow ) * 3 * CONFIG.glare.flash + pulse );
		glare.update( {
			sunNdc: glareNdc,
			aspect: size.w / size.h,
			vis: vis > 0 ? Math.min( 1, Math.pow( vis, 0.6 ) ) : 0,
			flash,
			time: reduced ? 0 : time,
			rot: reduced ? 0 : time * CONFIG.glare.rotation,
			tone: sample.sunTone,
			intensity: sample.glare * CONFIG.glare.intensity,
			streaks: CONFIG.glare.streaks,
			ghosts: CONFIG.glare.ghosts,
		} );
		if ( post ) {
			postParams.sunUv.x = ( sunNdc.x + 1 ) / 2;
			postParams.sunUv.y = ( sunNdc.y + 1 ) / 2;
			postParams.aspect = size.w / size.h;
			postParams.rays = quality.post ? sample.godRays * CONFIG.post.godRays : 0;
			postParams.rayTint.copy( colors.sunset ).lerp( colors.golden, 0.35 + 0.5 * sample.sunTone ).convertLinearToSRGB(); // hậu kỳ ở không gian hiển thị
			postParams.rayThreshold = CONFIG.post.rayThreshold;
			postParams.bloom = quality.post && quality.bloom ? CONFIG.post.bloom : 0;
			postParams.bloomThreshold = CONFIG.post.bloomThreshold;
			postParams.grain = quality.post && quality.bloom ? CONFIG.post.grain : 0;
			postParams.seed = reduced ? 0 : ( time * 60 ) % 97;
			postParams.dof = quality.post && grass && grass.mesh.visible ? CONFIG.post.dof : 0;
			postParams.dofEnd = CONFIG.post.dofEnd;
		}

		const u = shared;
		u.uTime.value = reduced ? 0 : time; // giảm chuyển động: cỏ không lay, mây và bóng mây đứng yên
		clouds.uniforms.uPinkMix.value = sample.cloudPinkMix;
		clouds.uniforms.uOpacity.value = CONFIG.cloudOpacity;
		clouds.uniforms.uGain.value = CONFIG.cloudGain;
		u.uLightScale.value = CONFIG.lightScale;
		terrain.uniforms.uGrassDepth.value = grass && grass.mesh.visible ? 1 : 0; // đất dưới cỏ instanced sẫm hơn
		if ( grass ) {
			grass.uniforms.uWind.value = reduced ? 0 : CONFIG.wind;
		}
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

	// Cảnh → hậu kỳ (nếu bậc có post) → sun glare. Hậu kỳ và glare vẽ chồng lên canvas nên tắt autoClear trong lúc đó.
	function draw() {
		renderer.render( scene, camera );
		const ac = renderer.autoClear;
		renderer.autoClear = false;
		if ( post && quality.post ) {
			post.render( renderer, postParams );
		}
		glare.render( renderer );
		renderer.autoClear = ac;
	}

	// Đổi kích thước canvas (gán canvas.width) luôn XOÁ hình đang có, kể cả khi kích thước không đổi; ResizeObserver lại chạy sau
	// requestAnimationFrame nên khung đó sẽ hiện canvas trống (chớp đen, ví dụ khi thanh địa chỉ điện thoại ẩn / hiện). Vì vậy: bỏ qua khi
	// kích thước và DPR không đổi; đổi thật thì vẽ lại ngay trong cùng lượt.
	function resize( w, h ) {
		if ( ! w || ! h ) {
			return;
		}
		// Kéo cửa sổ sang màn hình có DPR khác: áp lại giới hạn của bậc hiện tại.
		const dpr = Math.min( deviceDpr(), quality.dpr );
		if ( w === size.w && h === size.h && dpr === renderer.getPixelRatio() ) {
			return;
		}
		if ( dpr !== renderer.getPixelRatio() ) {
			renderer.setPixelRatio( dpr );
		}
		renderer.setSize( w, h, false );
		size.w = w;
		size.h = h;
		camera.aspect = w / h;
		camera.fov = w < h ? 56 : 45; // khung dọc mở rộng FOV để vẫn thấy đỉnh
		camera.updateProjectionMatrix();
		if ( ready && ! dead ) {
			apply( timer.getElapsed() );
			draw();
		}
	}

	function frame( ts ) {
		if ( dead ) {
			return;
		}
		// Khoảng cách tới khung trước chỉ phản ánh chi phí render khi khung trước có render (LITE bỏ qua khung khi cảnh đứng yên).
		if ( prevTs >= 0 && prevRendered && step < ladder.length - 1 && gov.sample( ts - prevTs, ts, step + 1 === ladder.length - 1 ) ) {
			degrade();
			if ( dead ) {
				return;
			}
		}
		prevTs = ts;
		prevRendered = false;
		timer.update( ts );
		const dt = Math.min( timer.getDelta(), 0.1 );
		const camMoving = store.damp( dt, mobile ? CONFIG.damping.mobile : CONFIG.damping.desktop );
		const sunMoving = store.dampSun( dt );
		const changed = camMoving || sunMoving;
		let mouseMoving = false;
		if ( ! mobile ) {
			const km = 1 - Math.exp( -4 * dt );
			mouse.x += ( mouse.tx - mouse.x ) * km;
			mouse.y += ( mouse.ty - mouse.y ) * km;
			mouseMoving = Math.abs( mouse.tx - mouse.x ) + Math.abs( mouse.ty - mouse.y ) > 1e-3;
		}
		if ( quality.mode === 'LITE' && ! changed && ! mouseMoving && ts - lastRender < 1000 / 24 ) {
			return;
		}
		lastRender = ts;
		prevRendered = true;
		apply( timer.getElapsed(), dt );
		draw();
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
	if ( post ) {
		post.compile( renderer );
	}
	draw();

	// Vòng render (chạy lại): khoảng cách khung đầu không tính; lần đầu bỏ qua warmupMs (biên dịch, giải mã ảnh), các lần sau settleMs.
	const onStart = () => {
		prevTs = -1;
		gov.reset( performance.now(), starts++ ? CONFIG.governor.settleMs : CONFIG.governor.warmupMs );
	};
	ready = true;
	const life = createLifecycle( { renderer, section, stage, canvas, frame, onResize: resize, onStart, onFatal } );

	function dispose() {
		dead = true;
		life.destroy();
		window.removeEventListener( 'pointermove', onMouse );
		offIndex();
		glare.dispose();
		if ( post ) {
			post.dispose();
		}
		rail.toggleHelper( scene, false );
		sky.dispose();
		terrain.dispose();
		extras.forEach( ( e ) => e.dispose() );
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
		get ladder() {
			return ladder;
		},
		get step() {
			return step;
		},
		get quality() {
			return quality;
		},
		get governor() {
			return gov;
		},
		// Debug: chọn bậc bất kỳ (kể cả nâng lại; số cỏ / cây / mây không vượt mức đã dựng). Bậc 2D không chọn được ở đây.
		setStep( i ) {
			step = Math.max( 0, Math.min( i, ladder.length - 2 ) );
			setQuality( ladder[ step ] );
			gov.reset( performance.now(), CONFIG.governor.settleMs );
			apply( timer.getElapsed() );
			draw(); // setPixelRatio vừa xoá canvas
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
			extras.forEach( ( e ) => e.setColors( colors ) );
			glare.setColors( colors );
		},
		rebuildTerrain() {
			scene.remove( terrain.mesh );
			terrain.dispose();
			terrain = buildTerrain( THREE, { seed: data.seed, cfg: CONFIG.terrain, grid: base.grid, octaves: base.octaves, colors, shared } );
			scene.add( terrain.mesh );
			return terrain.info;
		},
		showRail( on ) {
			rail.toggleHelper( scene, on, colors.gold );
		},
		renderNow() {
			apply( timer.getElapsed() );
			draw();
		},
		get sunVisibility() {
			return visSlow;
		},
		destroy: dispose,
	};
}
