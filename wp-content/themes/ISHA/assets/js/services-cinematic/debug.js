/**
 * Bảng debug (chỉ quản trị viên đăng nhập + ?isha-debug=1): lil-gui + stats. Chỉnh trực tiếp CONFIG. Nút "Copy config JSON" chép CONFIG hiện
 * tại để dán vào config.js. Nhóm "Khung cảnh" có các núm chính của cảnh v3 (điểm dừng, mặt trời, sương, hậu kỳ, camera); bộ núm đầy đủ
 * (độ sâu, cắt trời, viền sáng, đóng khung, mây, chế độ xem bản đồ độ sâu) ở tools/lookdev.html. getScene() trả null ở chế độ 2D.
 * Ép chế độ: thêm ?isha-mode=FULL_HIGH|FULL|LITE|2D|STATIC vào URL (tải lại trang).
 */
import { CONFIG, applyRingTimes } from './config.js';
import { RING } from '../card-ring/config.js';
import { clearGovStep } from './tier.js';

export async function createDebug( { store, getScene, det, auto = null, ring = null } ) {
	// Cảnh có thể được dựng lại (khôi phục context WebGL) hoặc huỷ (xuống 2D): luôn lấy cảnh hiện tại.
	const scene = getScene();
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

	const gpu = det.gpu;
	const info = {
		mode: store.state.mode,
		lý_do: det.reasons.join( '; ' ),
		gpu: gpu ? `${ gpu.type } · tier ${ gpu.tier }${ gpu.fps ? ` · ${ gpu.fps } fps` : '' } · ${ gpu.gpu || '?' }` : '—',
	};

	const fInfo = gui.addFolder( 'Thông tin' );
	fInfo.add( info, 'mode' ).disable().listen();
	fInfo.add( info, 'lý_do' ).disable();
	fInfo.add( info, 'gpu' ).name( 'detect-gpu' ).disable();

	// Hiệu năng: governor (tier.js) và bậc chất lượng. Chọn bậc tay để so sánh (nâng lại được, không vượt số lượng đã dựng).
	const fPerf = gui.addFolder( 'Hiệu năng' );
	const perf = { khung_ms: '—', bậc: scene ? scene.step : 0, chất_lượng: '—' };
	const describe = ( q ) => ( q.mode === '2D' ? '2D' : `${ q.mode } · DPR ${ q.dpr } · lưới ${ q.grid } · sương ${ q.mist } · bụi ${ q.dust } · tia ${ q.rays }${ q.bloom ? ' · bloom' : '' }` );
	fPerf.add( perf, 'khung_ms' ).name( 'khung TB cửa sổ trước (ms)' ).disable().listen();
	fPerf.add( perf, 'chất_lượng' ).name( 'bậc hiện tại' ).disable().listen();
	if ( scene ) {
		const options = {};
		scene.ladder.slice( 0, -1 ).forEach( ( q, i ) => {
			options[ `${ i }: ${ describe( q ) }` ] = i;
		} );
		fPerf.add( perf, 'bậc', options ).name( 'chọn bậc' ).listen().onChange( ( i ) => {
			if ( Number.isInteger( i ) ) {
				const sc = getScene();
				if ( sc ) {
					sc.setStep( i );
				}
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
		if ( ring ) {
			const g = ring.governor;
			ringGov.bậc = `${ g.step } ${ g.name }${ g.defer && g.step < 2 ? ' (nhường cảnh)' : '' }`;
			ringGov.fps = g.fps ? g.fps.toFixed( 1 ) : '…'; // cửa sổ 2 giây gần nhất (đo liên tục)
		}
		info.mode = store.state.mode;
		const sc = getScene();
		if ( sc ) {
			const avg = sc.governor.lastAvg;
			perf.khung_ms = avg ? `${ avg.toFixed( 1 ) } (${ ( 1000 / avg ).toFixed( 0 ) } fps)` : '—';
			perf.bậc = sc.step;
			perf.chất_lượng = describe( sc.quality );
		}
	}, 500 );

	// Bình minh tự chạy (autoplay.js): trạng thái, thời lượng, ngưỡng phủ màn hình, chạy lại; ép progress để xem một thời điểm.
	const fAuto = gui.addFolder( 'Bình minh tự chạy' );
	const autoInfo = { trạng_thái: '' };
	fAuto.add( autoInfo, 'trạng_thái' ).name( 'trạng thái / progress' ).disable().listen();
	fAuto.add( CONFIG.autoplay, 'duration', 3, 40, 0.5 ).name( 'thời lượng (giây)' );
	fAuto.add( CONFIG.autoplay, 'cover', 0.5, 1, 0.01 ).name( 'chạy khi section phủ ≥ (phần)' );
	if ( auto ) {
		fAuto.add( { r: () => auto.replay() }, 'r' ).name( 'chạy lại từ đầu' );
	}
	fAuto.add( CONFIG, 'debugProgress', -0.01, 1, 0.001 ).name( 'ép progress (-0.01 = tự chạy)' ).onChange( ( v ) => {
		if ( v < 0 ) {
			CONFIG.debugProgress = -1;
		}
	} );
	fAuto.add( CONFIG, 'hold', 0, 0.8, 0.01 ).name( 'vùng giữ mặt trời' );
	fAuto.add( CONFIG.damping, 'sun', 0.3, 8, 0.1 ).name( 'độ trễ mặt trời (k, nhỏ = chậm)' );
	const autoTimer = setInterval( () => {
		autoInfo.trạng_thái = auto ? `${ auto.phase } · ${ store.state.progress.toFixed( 3 ) }` : '—';
	}, 250 );
	const fCard = gui.addFolder( 'Vòng thẻ & tia chạm thẻ' );
	const ringTimes = () => applyRingTimes( document.querySelector( '[data-isha-svc]' ) );
	fCard.add( CONFIG.card, 'enter', 0.2, 2, 0.05 ).name( 'vòng hiện (giây)' ).onChange( ringTimes );
	fCard.add( CONFIG.card, 'hide', 0.1, 1.5, 0.05 ).name( 'vòng ẩn (giây)' ).onChange( ringTimes );
	fCard.add( CONFIG.sun, 'reachGap', 0, 120, 1 ).name( 'khoảng hở đầu tia (px)' );
	fCard.add( CONFIG.sun, 'reachAt', 0.05, 1, 0.01 ).name( 'tia với tới khi độ cao ≥' );
	fCard.add( CONFIG.sun, 'rayThrough', 0, 1, 0.01 ).name( 'tia đi qua trước núi' );
	fCard.add( CONFIG.sun, 'reachBoost', 0, 4, 0.05 ).name( 'tia sáng thêm gần thẻ' );
	fCard.add( CONFIG.sun, 'reachWidth', 0.1, 1, 0.01 ).name( 'bề ngang tia với tới' );
	fCard.add( RING, 'HIT_GAIN', 0, 20, 0.5 ).name( 'độ đậm điểm sáng trên viền' );
	// Governor của vòng thẻ (card-ring/governor.js): bậc hiện tại, FPS cửa sổ 2 giây gần nhất, ép bậc để so.
	const ringGov = { bậc: '', fps: '', ép: 'tự đo' };
	if ( ring ) {
		fCard.add( ringGov, 'bậc' ).name( 'governor vòng: bậc' ).disable().listen();
		fCard.add( ringGov, 'fps' ).name( 'governor vòng: FPS 2 giây' ).disable().listen();
		// Đo chi phí của vòng: ẩn hẳn (display: none, vòng rAF tự dừng vì ra khỏi khung nhìn) rồi so FPS của trang.
		const ringEl = document.querySelector( '[data-isha-ring]' );
		fCard.add( { off: false }, 'off' ).name( 'đo: tắt vòng thẻ' ).onChange( ( v ) => ringEl && ( ringEl.style.display = v ? 'none' : '' ) );
		fCard.add( ringGov, 'ép', [ 'tự đo', 'FULL', 'NO_REFLECT', 'CENTER_GLINT' ] ).name( 'governor vòng: ép bậc' ).onChange( ( v ) => ring.setGov( v === 'tự đo' ? null : [ 'FULL', 'NO_REFLECT', 'CENTER_GLINT' ].indexOf( v ) ) );
	}

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

	if ( scene ) {
		const fScene = gui.addFolder( 'Khung cảnh (đủ núm: tools/lookdev.html)' );
		const restops = () => getScene() && getScene().refreshStops();
		const st = CONFIG.stops;
		for ( const [ k, lo, hi ] of [ [ 'volumetric', 0, 1.5 ], [ 'sunIntensity', 0, 2 ], [ 'fogDensity', 0, 2 ], [ 'rimIntensity', 0, 2 ], [ 'exposure', 0.5, 2 ] ] ) {
			fScene.add( st[ k ], '0', lo, hi, 0.01 ).name( `${ k } · đầu` ).onChange( restops );
			fScene.add( st[ k ], '1', lo, hi, 0.01 ).name( `${ k } · cuối` ).onChange( restops );
		}
		const path = st.path[ det.mobile ? 'portrait' : 'desktop' ];
		[ 'x đầu', 'y đầu', 'x cuối', 'y cuối' ].forEach( ( n, i ) => fScene.add( path, String( i ), 0, 1, 0.005 ).name( `mặt trời ${ n }` ).onChange( restops ) );
		fScene.add( CONFIG.sun, 'rays', 0, 1, 0.01 ).name( 'tia: độ sáng' );
		fScene.add( CONFIG.sun, 'raySlope', 0.05, 1, 0.01 ).name( 'tia: nửa rộng tối đa / dài' );
		fScene.add( CONFIG.rise.rayTurn, '1', 0, 1, 0.005 ).name( 'tia: nhịp lắc ở đỉnh' );
		fScene.add( CONFIG.sun, 'halo', 0, 1.5, 0.01 ).name( 'quầng' );
		fScene.add( CONFIG.sun, 'haloVeil', 0, 1, 0.01 ).name( 'quầng sau núi' );
		fScene.add( CONFIG.mist, 'sunLit', 0, 2, 0.01 ).name( 'sương: ửng nắng' );
		fScene.add( CONFIG.post, 'bloom', 0, 1, 0.01 ).name( 'bloom (tầng có bloom)' );
		fScene.add( CONFIG.post, 'grainScale', 0, 3, 0.05 ).name( 'grain (nhân)' );
		fScene.add( CONFIG.camera, 'mouse', 0, 2, 0.01 ).name( 'parallax chuột' );
	}

	window.ishaSvcDebug = { CONFIG, store, getScene };

	return {
		destroy() {
			cancelAnimationFrame( raf );
			clearInterval( perfTimer );
			clearInterval( autoTimer );
			stats.dom.remove();
			gui.destroy();
			delete window.ishaSvcDebug;
		},
	};
}
