/**
 * Bảng debug (chỉ quản trị viên đăng nhập + ?isha-debug=1): lil-gui + stats. Chỉnh trực tiếp CONFIG; thay đổi cần tính lại thì gọi API của
 * cảnh (setStops, refreshColors, rebuildTerrain, setToneMapping). Nút "Copy config JSON" chép CONFIG hiện tại để dán vào config.js.
 * Ép chế độ: thêm ?isha-mode=FULL_HIGH|FULL|LITE|2D|STATIC vào URL (tải lại trang).
 */
import { CONFIG } from './config.js';
import { computeStops } from './stops.js';
import { clearGovStep } from './tier.js';

export async function createDebug( { store, scene, det, data, scroll, setSegment } ) {
	const [ { default: GUI }, { default: Stats } ] = await Promise.all( [ import( 'lil-gui' ), import( 'three/addons/libs/stats.module.js' ) ] );
	const gui = new GUI( { title: 'ISHA · Bình minh lan tỏa' } );
	gui.domElement.style.zIndex = '10000';
	const stats = new Stats();
	stats.dom.style.cssText = 'position:fixed;left:0;bottom:0;z-index:10000;';
	document.body.appendChild( stats.dom );
	let raf = 0;
	const loop = () => {
		stats.update();
		raf = requestAnimationFrame( loop );
	};
	raf = requestAnimationFrame( loop );

	const N = data.services.length;
	const restops = () => {
		if ( scene ) {
			scene.setStops( computeStops( N, CONFIG.stops ) );
		}
	};
	const gpu = det.gpu;
	const info = {
		mode: store.state.mode,
		lý_do: det.reasons.join( '; ' ),
		gpu: gpu ? `${ gpu.type } · tier ${ gpu.tier }${ gpu.fps ? ` · ${ gpu.fps } fps` : '' } · ${ gpu.gpu || '?' }` : '—',
		địa_hình: scene ? JSON.stringify( scene.terrainInfo ) : '—',
	};

	const fInfo = gui.addFolder( 'Thông tin' );
	fInfo.add( info, 'mode' ).disable().listen();
	fInfo.add( info, 'lý_do' ).disable();
	fInfo.add( info, 'gpu' ).name( 'detect-gpu' ).disable();
	fInfo.add( info, 'địa_hình' ).disable().listen();

	// Hiệu năng: governor (tier.js) và bậc chất lượng. Chọn bậc tay để so sánh (nâng lại được, không vượt số cỏ / cây / mây đã dựng).
	const fPerf = gui.addFolder( 'Hiệu năng' );
	const perf = { khung_ms: '—', bậc: scene ? scene.step : 0, chất_lượng: '—' };
	const describe = ( q ) => ( q.mode === '2D' ? '2D' : `${ q.mode } · DPR ${ q.dpr } · cỏ ${ q.grass } · cây ${ q.trees } · mây ${ q.clouds }` );
	fPerf.add( perf, 'khung_ms' ).name( 'khung TB cửa sổ trước (ms)' ).disable().listen();
	fPerf.add( perf, 'chất_lượng' ).name( 'bậc hiện tại' ).disable().listen();
	if ( scene ) {
		const options = {};
		scene.ladder.slice( 0, -1 ).forEach( ( q, i ) => {
			options[ `${ i }: ${ describe( q ) }` ] = i;
		} );
		fPerf.add( perf, 'bậc', options ).name( 'chọn bậc' ).listen().onChange( ( i ) => {
			if ( Number.isInteger( i ) ) {
				scene.setStep( i );
			}
		} );
	}
	fPerf.add( CONFIG.governor, 'enabled' ).name( 'governor bật' );
	fPerf.add( CONFIG.governor, 'thresholdMs', 10, 60, 1 ).name( 'ngưỡng hạ bậc (ms)' );
	fPerf.add( CONFIG.governor, 'fallbackMs', 25, 150, 1 ).name( 'ngưỡng xuống 2D (ms)' );
	fPerf.add( {
		clear: () => {
			clearGovStep();
			console.info( '[ISHA svc] Đã xoá bậc governor của phiên; tải lại trang để bắt đầu từ bậc 0.' );
		},
	}, 'clear' ).name( 'Xoá bậc đã nhớ (phiên)' );
	const perfTimer = setInterval( () => {
		info.mode = store.state.mode;
		if ( scene ) {
			const avg = scene.governor.lastAvg;
			perf.khung_ms = avg ? `${ avg.toFixed( 1 ) } (${ ( 1000 / avg ).toFixed( 0 ) } fps)` : '—';
			perf.bậc = scene.step;
			perf.chất_lượng = describe( scene.quality );
		}
	}, 500 );

	const fScroll = gui.addFolder( 'Cuộn' );
	fScroll.add( CONFIG, 'debugProgress', -0.01, 1, 0.001 ).name( 'ép progress (-0.01 = theo cuộn)' ).onChange( ( v ) => {
		if ( v < 0 ) {
			CONFIG.debugProgress = -1;
		}
	} );
	const reseg = () => {
		if ( setSegment ) {
			setSegment();
		}
		if ( scroll ) {
			scroll.refresh();
		}
	};
	fScroll.add( CONFIG.SCROLL_VH_PER_SERVICE, 'desktop', 60, 400, 10 ).name( 'độ dài cuộn / liệu trình (svh, desktop)' ).onFinishChange( reseg );
	fScroll.add( CONFIG.SCROLL_VH_PER_SERVICE, 'mobile', 60, 400, 10 ).name( 'độ dài cuộn / liệu trình (svh, cảm ứng)' ).onFinishChange( reseg );
	const relenis = () => {
		if ( scroll && scroll.lenis ) {
			scroll.lenis.options.lerp = CONFIG.lenis.lerp;
			scroll.lenis.options.wheelMultiplier = CONFIG.lenis.wheelMultiplier;
		}
	};
	fScroll.add( CONFIG.lenis, 'lerp', 0.02, 0.3, 0.01 ).name( 'Lenis lerp (nhỏ = trôi chậm)' ).onChange( relenis );
	fScroll.add( CONFIG.lenis, 'wheelMultiplier', 0.3, 2, 0.05 ).name( 'Lenis tốc độ bánh xe' ).onChange( relenis );
	fScroll.add( CONFIG, 'goDuration', 0.5, 4, 0.1 ).name( 'cuộn tới điểm dừng (giây)' );
	fScroll.add( CONFIG, 'cardDuration', 0.2, 2.5, 0.05 ).name( 'chuyển card (giây)' );
	fScroll.add( CONFIG, 'hold', 0, 0.8, 0.01 ).name( 'vùng giữ mặt trời' );
	fScroll.add( CONFIG.damping, 'desktop', 0.5, 12, 0.1 ).name( 'damping camera desktop (k)' );
	fScroll.add( CONFIG.damping, 'mobile', 0.5, 12, 0.1 ).name( 'damping camera cảm ứng (k)' );
	fScroll.add( CONFIG.damping, 'sun', 0.3, 8, 0.1 ).name( 'độ trễ mặt trời (k, nhỏ = chậm)' );

	const fSun = gui.addFolder( 'Mặt trời' );
	fSun.add( CONFIG.stops, 'sunYStart', 0.2, 0.7, 0.005 ).name( 'vị trí dọc lúc mọc (0 = đỉnh khung)' ).onChange( restops );
	fSun.add( CONFIG.stops, 'sunYEnd', 0.1, 0.6, 0.005 ).name( 'vị trí dọc cuối' ).onChange( restops );
	fSun.add( CONFIG.stops, 'sunXStart', 0.2, 0.8, 0.005 ).name( 'vị trí ngang lúc mọc' ).onChange( restops );
	fSun.add( CONFIG.stops, 'sunXEnd', 0.2, 0.8, 0.005 ).name( 'vị trí ngang cuối' ).onChange( restops );
	fSun.add( CONFIG.sun, 'radiusDeg', 0.5, 6, 0.1 ).name( 'bán kính đĩa (°)' ).onChange( () => scene && scene.sizeRays() );
	fSun.add( CONFIG.sun, 'discIntensity', 0.5, 4, 0.05 ).name( 'độ sáng đĩa' );
	fSun.add( CONFIG.sun, 'haloScale', 0, 3, 0.05 ).name( 'hào quang (nhân)' );
	fSun.add( CONFIG.sun, 'raysScale', 0, 3, 0.05 ).name( 'tia line-art (nhân)' );

	const fLook = gui.addFolder( 'Màu và ánh sáng' );
	fLook.add( CONFIG, 'toneMapping', [ 'Neutral', 'AgX' ] ).name( 'tone mapping' ).onChange( ( v ) => scene && scene.setToneMapping( v ) );
	fLook.add( CONFIG, 'exposureScale', 0.3, 2.5, 0.01 ).name( 'exposure (nhân)' );
	fLook.add( CONFIG.colors, 'skyFloorScale', 0, 2, 0.01 ).name( 'sàn sáng của trời (nhân)' );
	fLook.add( CONFIG.colors, 'skyWarm', 0, 0.6, 0.01 ).name( 'trời ấm (pha Golden)' );
	fLook.add( CONFIG, 'fogScale', 0, 3, 0.01 ).name( 'sương (nhân)' );
	fLook.add( CONFIG.stops, 'fogBase', 0, 3, 0.01 ).name( 'sương gốc (keyframe)' ).onChange( restops );
	const recolor = () => scene && scene.refreshColors();
	fLook.add( CONFIG.colors, 'kGreen', 0.05, 1, 0.01 ).name( 'k xanh (bão hoà)' ).onChange( recolor );
	fLook.add( CONFIG.colors, 'greenHue', 70, 160, 1 ).name( 'hue xanh' ).onChange( recolor );

	const fNature = gui.addFolder( 'Mây, cỏ, cây' );
	fNature.add( CONFIG.colors, 'kPink', 0.05, 1, 0.01 ).name( 'k hồng mây (bão hoà)' ).onChange( recolor );
	fNature.add( CONFIG.colors, 'pinkHue', 0, 40, 1 ).name( 'hue hồng mây' ).onChange( recolor );
	fNature.add( CONFIG, 'cloudOpacity', 0, 1, 0.01 ).name( 'độ đậm mây' );
	fNature.add( CONFIG, 'cloudGain', 0.5, 3, 0.05 ).name( 'độ sáng mây' );
	fNature.add( CONFIG, 'lightScale', 0.5, 2, 0.01 ).name( 'độ sáng cảnh vật' );
	fNature.add( CONFIG, 'wind', 0, 3, 0.05 ).name( 'gió (cỏ)' );

	const fTerrain = gui.addFolder( 'Địa hình' ).close();
	const rebuild = () => {
		if ( scene ) {
			info.địa_hình = JSON.stringify( scene.rebuildTerrain() );
		}
	};
	fTerrain.add( CONFIG.terrain, 'peakHeight', 200, 900, 10 ).name( 'cao đỉnh chính' ).onFinishChange( rebuild );
	fTerrain.add( CONFIG.terrain, 'peakRadius', 200, 600, 10 ).name( 'bán kính đỉnh' ).onFinishChange( rebuild );
	fTerrain.add( CONFIG.terrain, 'detailAmp', 0, 40, 1 ).name( 'chi tiết' ).onFinishChange( rebuild );
	fTerrain.add( CONFIG.terrain, 'meadowRadius', 200, 900, 10 ).name( 'bán kính đồng cỏ' ).onFinishChange( rebuild );

	const fCam = gui.addFolder( 'Camera' ).close();
	fCam.add( CONFIG, 'cameraPitchDeg', -5, 15, 0.1 ).name( 'góc ngẩng camera (°)' );
	fCam.add( CONFIG, 'mouseParallaxDeg', 0, 3, 0.1 ).name( 'parallax chuột (°)' );
	fCam.add( CONFIG, 'showRail' ).name( 'hiện đường ray' ).onChange( ( v ) => scene && scene.showRail( v ) );

	gui.add( {
		copy: async () => {
			const json = JSON.stringify( CONFIG, null, '\t' );
			try {
				await navigator.clipboard.writeText( json );
				console.info( '[ISHA svc] Đã chép config JSON.' );
			} catch ( err ) {
				console.info( json );
			}
		},
	}, 'copy' ).name( 'Copy config JSON' );

	window.ishaSvcDebug = { CONFIG, store, scene };

	return {
		destroy() {
			cancelAnimationFrame( raf );
			clearInterval( perfTimer );
			stats.dom.remove();
			gui.destroy();
			delete window.ishaSvcDebug;
		},
	};
}
