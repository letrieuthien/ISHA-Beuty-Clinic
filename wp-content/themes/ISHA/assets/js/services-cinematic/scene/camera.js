/**
 * Camera của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 9).
 *
 * Camera chỉ TỊNH TIẾN, không xoay: hướng nhìn luôn trùng camera tham chiếu (−Z, đặt tại gốc ở p = 0) mà lưới ảnh (plate.js) dùng để đặt
 * đỉnh, nên ở p = 0 hình trùng khít ảnh gốc và mọi chuyển động sau đó là parallax thuần.
 *   e = easeInOutSine( progress đã làm mượt );  z = −dollyMax · e;  x = anchorDrift · e (về phía khối neo, theo dấu của anchorX − 0.5)
 * Parallax chuột cực nhẹ (±mouse đơn vị, damp riêng, chỉ desktop). Khung dọc mở FOV thêm fovPortrait (nội suy mượt theo tỉ lệ khung).
 * FOV gốc (baseFov) là FOV của camera tham chiếu mà lưới ảnh dùng để đặt đỉnh; update() có thể cộng thêm một độ lệch (zoom nhẹ theo độ cao
 * mặt trời, CONFIG.rise.fov): phóng đều cả khung nên không gây xé hình, không tính vào ràng buộc 18% (chỉ dolly mới sinh parallax).
 * Ràng buộc cứng: phần gần nhất (độ sâu 0, cách near) không được phóng quá maxMagnification (1.18) khi tới dollyMax; vượt thì cảnh báo
 * trong console. Đây là ranh giới giữa "parallax đẹp" và "xé hình".
 */

export function easeInOutSine( t ) {
	return -( Math.cos( Math.PI * t ) - 1 ) / 2;
}

const clamp01 = ( v ) => Math.min( 1, Math.max( 0, v ) );

/** FOV dọc theo tỉ lệ khung: ngang (≥ 1.4) = fov, dọc (≤ 0.7) = fov + fovPortrait. */
export function fovFor( aspect, cfg ) {
	const t = clamp01( ( aspect - 0.7 ) / ( 1.4 - 0.7 ) );
	return cfg.fov + cfg.fovPortrait * ( 1 - t * t * ( 3 - 2 * t ) );
}

/** Tỉ lệ phóng của phần gần nhất khi camera tiến dollyMax về phía nó. */
export function nearMagnification( near, dollyMax ) {
	return near / Math.max( 1e-3, near - dollyMax );
}

/**
 * @param {object} THREE
 * @param {{cfg:object, plateCfg:object, mobile:boolean}} opts cfg: CONFIG.camera; plateCfg: CONFIG.plate (near, anchorX). Đọc trực tiếp mỗi khung.
 */
export function createCamera( THREE, { cfg, plateCfg, mobile } ) {
	const camera = new THREE.PerspectiveCamera( cfg.fov, 1, 5, 8000 );
	const pointer = { x: 0, y: 0, tx: 0, ty: 0 };
	let warned = false;
	let baseFov = cfg.fov;

	function checkMagnification() {
		const m = nearMagnification( plateCfg.near, cfg.dollyMax );
		if ( m > cfg.maxMagnification && ! warned ) {
			warned = true;
			console.warn( `[ISHA svc] dollyMax ${ cfg.dollyMax } làm phần gần nhất phóng ${ ( ( m - 1 ) * 100 ).toFixed( 1 ) }% (> ${ ( ( cfg.maxMagnification - 1 ) * 100 ).toFixed( 0 ) }%): sẽ xé hình. Giảm dollyMax xuống ≤ ${ ( plateCfg.near * ( 1 - 1 / cfg.maxMagnification ) ).toFixed( 0 ) }.` );
		} else if ( m <= cfg.maxMagnification ) {
			warned = false;
		}
		return m;
	}

	return {
		camera,
		/** @return {number} FOV dọc gốc (độ) của camera tham chiếu: lưới ảnh đặt đỉnh theo FOV này. */
		resize( width, height ) {
			camera.aspect = width / Math.max( 1, height );
			baseFov = fovFor( camera.aspect, cfg );
			camera.fov = baseFov;
			camera.updateProjectionMatrix();
			return baseFov;
		},
		get baseFov() {
			return baseFov;
		},
		/** Vị trí con trỏ chuẩn hoá −1..1 (desktop). */
		setPointer( nx, ny ) {
			pointer.tx = Math.max( -1, Math.min( 1, nx ) );
			pointer.ty = Math.max( -1, Math.min( 1, ny ) );
		},
		/**
		 * @param {number} progress Progress đã làm mượt (store.smoothed), 0..1.
		 * @param {number} dt       Giây.
		 * @param {{x:number, y:number}} [offset] Lệch thêm (đơn vị cảnh), ví dụ nhịp thở của khung hình (idle, bước 5).
		 * @param {number} [fovDelta] Độ cộng vào FOV gốc (zoom nhẹ theo độ cao mặt trời).
		 */
		update( progress, dt, offset, fovDelta = 0 ) {
			checkMagnification();
			const fov = baseFov + fovDelta;
			if ( Math.abs( camera.fov - fov ) > 1e-4 ) {
				camera.fov = fov;
				camera.updateProjectionMatrix();
			}
			const e = easeInOutSine( clamp01( progress ) );
			const k = 1 - Math.exp( -cfg.mouseDamping * Math.max( 0, dt ) );
			pointer.x += ( pointer.tx - pointer.x ) * k;
			pointer.y += ( pointer.ty - pointer.y ) * k;
			const m = mobile ? 0 : cfg.mouse;
			const sign = Math.sign( plateCfg.anchorX - 0.5 ) || 1;
			camera.position.set(
				sign * cfg.anchorDrift * e + pointer.x * m + ( offset ? offset.x : 0 ),
				pointer.y * m + ( offset ? offset.y : 0 ),
				-cfg.dollyMax * e
			);
			camera.updateMatrixWorld();
		},
		get magnification() {
			return nearMagnification( plateCfg.near, cfg.dollyMax );
		},
	};
}
