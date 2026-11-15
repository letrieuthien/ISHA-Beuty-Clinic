/**
 * Bảng debug (chỉ quản trị viên đăng nhập + ?isha-debug=1): lil-gui + stats. Chỉnh trực tiếp CONFIG; thay đổi cần tính lại thì gọi API của
 * cảnh (setStops, refreshColors, rebuildTerrain, setToneMapping). Nút "Copy config JSON" chép CONFIG hiện tại để dán vào config.js.
 * Ép chế độ: thêm ?isha-mode=FULL_HIGH|FULL|LITE|2D|STATIC vào URL (tải lại trang).
 */
import { CONFIG } from './config.js';
import { computeStops } from './stops.js';

export async function createDebug( { store, scene, det, data } ) {
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
	const info = { mode: det.mode, lý_do: det.reasons.join( '; ' ), địa_hình: scene ? JSON.stringify( scene.terrainInfo ) : '—' };

	const fInfo = gui.addFolder( 'Thông tin' );
	fInfo.add( info, 'mode' ).disable();
	fInfo.add( info, 'lý_do' ).disable();
	fInfo.add( info, 'địa_hình' ).disable().listen();

	const fScroll = gui.addFolder( 'Cuộn' );
	fScroll.add( CONFIG, 'debugProgress', -0.01, 1, 0.001 ).name( 'ép progress (-0.01 = theo cuộn)' ).onChange( ( v ) => {
		if ( v < 0 ) {
			CONFIG.debugProgress = -1;
		}
	} );
	fScroll.add( CONFIG, 'hold', 0, 0.8, 0.01 ).name( 'vùng giữ mặt trời' );
	fScroll.add( CONFIG.damping, 'desktop', 1, 12, 0.5 ).name( 'damping desktop (k)' );
	fScroll.add( CONFIG.damping, 'mobile', 1, 12, 0.5 ).name( 'damping mobile (k)' );

	const fSun = gui.addFolder( 'Mặt trời' );
	fSun.add( CONFIG.stops, 'elevMin', -2, 20, 0.5 ).name( 'độ cao đầu (°)' ).onChange( restops );
	fSun.add( CONFIG.stops, 'elevMax', 10, 70, 0.5 ).name( 'độ cao cuối (°)' ).onChange( restops );
	fSun.add( CONFIG.stops, 'peakAzimuth', 150, 210, 0.5 ).name( 'phương vị đỉnh (°)' ).onChange( restops );
	fSun.add( CONFIG.stops, 'azFrom', -30, 10, 0.5 ).name( 'lệch phương vị lúc mọc' ).onChange( restops );
	fSun.add( CONFIG.stops, 'azTo', -10, 30, 0.5 ).name( 'lệch phương vị cuối' ).onChange( restops );

	const fLook = gui.addFolder( 'Màu và ánh sáng' );
	fLook.add( CONFIG, 'toneMapping', [ 'Neutral', 'AgX' ] ).name( 'tone mapping' ).onChange( ( v ) => scene && scene.setToneMapping( v ) );
	fLook.add( CONFIG, 'exposureScale', 0.3, 2.5, 0.01 ).name( 'exposure (nhân)' );
	fLook.add( CONFIG.colors, 'skyFloor', 0, 0.8, 0.01 ).name( 'sàn sáng của trời' );
	fLook.add( CONFIG, 'fogScale', 0, 3, 0.01 ).name( 'sương (nhân)' );
	fLook.add( CONFIG.stops, 'fogBase', 0, 3, 0.01 ).name( 'sương gốc (keyframe)' ).onChange( restops );
	const recolor = () => scene && scene.refreshColors();
	fLook.add( CONFIG.colors, 'kGreen', 0.05, 1, 0.01 ).name( 'k xanh (bão hoà)' ).onChange( recolor );
	fLook.add( CONFIG.colors, 'greenHue', 70, 160, 1 ).name( 'hue xanh' ).onChange( recolor );
	fLook.add( CONFIG.colors, 'kPink', 0.05, 1, 0.01 ).name( 'k hồng (mây, giai đoạn 5)' ).onChange( recolor );
	fLook.add( CONFIG.colors, 'pinkHue', 0, 40, 1 ).name( 'hue hồng' ).onChange( recolor );

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
			stats.dom.remove();
			gui.destroy();
			delete window.ishaSvcDebug;
		},
	};
}
