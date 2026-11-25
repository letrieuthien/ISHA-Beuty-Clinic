/**
 * Bố cục của màn chờ "Bình minh" (intro.js), khớp khung cảnh canvas chính (khách, 25/11/2026): (1) đỉnh núi đầu tiên bên trái của hình núi nằm
 * đúng tầm đỉnh núi trái của ảnh canvas; (2) mặt trời ở bên phải, đúng vị trí điểm đầu của mặt trời canvas. HÀM THUẦN, không DOM (test:
 * tests/intro-layout.test.mjs).
 *
 * Vì sao phải tính bằng JS: canvas đóng khung theo đường chân trời (scene/plate.js layout: ảnh vừa đúng chiều rộng khung, chân trời ở
 * framing.horizonLandscape → horizonPortrait theo tỉ lệ khung, nội suy smoothstep 0.7 … 1.4), nên đỉnh núi nằm ở độ cao phụ thuộc cả chiều
 * rộng lẫn chiều cao khung. Neo đáy khung (bản đầu) làm đỉnh lệch hàng trăm px ở cửa sổ thấp. CSS thuần không chia được chiều dài cho chiều dài
 * (va = W / H) nên không dựng lại được công thức đó.
 *
 * Hai hình khác nhau (ảnh núi thật và hình núi thanh dọc) chỉ có thể khớp ở MỘT điểm neo: đỉnh núi trái. Khung ảnh thanh dọc được đặt sao cho
 * đỉnh đầu tiên của nó trùng đỉnh trái của ảnh canvas, rồi phóng thêm (≥ 1 lần chiều rộng khung) tới mức phủ kín đáy và hai bên.
 */

// Ảnh canvas (assets/img/scene/scene-source*.webp, tỉ lệ 3840 × 2165) và đỉnh núi trái của nó: đường viền trời / núi theo độ sáng
// (ngưỡng 0.655, giữa trời ≥ 0.69 và núi ≤ 0.62 của CONFIG.cut), đỉnh cao nhất nửa trái. Đo bằng Chrome headless trên scene-source.webp.
export const CANVAS_IMAGE = Object.freeze( { aspect: 3840 / 2165, peakU: 0.151, peakV: 0.116 } );

// Hình núi thanh dọc (assets/img/intro/ridge.webp, tỉ lệ 6458 × 4020): đỉnh đầu tiên bên trái (x, y theo tỉ lệ chiều rộng / chiều cao khung
// ảnh), bán kính đĩa mặt trời theo chiều rộng khung ảnh, và độ cao đường đỉnh (hợp ba lớp) tại 33 điểm đều 0 … 1 theo chiều rộng
// (y / chiều cao; từ ridge-mask.svg, tools/bake-intro.mjs in ra khi chạy lại).
export const RIDGE_ART = Object.freeze( {
	aspect: 6458 / 4020,
	peakX: 0.17,
	peakY: 0.395,
	sunRadius: 0.0245,
	top: Object.freeze( [ 0.314, 0.346, 0.385, 0.414, 0.411, 0.398, 0.398, 0.422, 0.449, 0.476, 0.498, 0.516, 0.514, 0.515, 0.498, 0.465, 0.439, 0.433, 0.427, 0.43, 0.449, 0.48, 0.515, 0.542, 0.558, 0.569, 0.561, 0.553, 0.556, 0.56, 0.542, 0.505, 0.48 ] ),
} );

const clamp = ( v, a, b ) => Math.min( b, Math.max( a, v ) );
const smooth01 = ( a, b, x ) => {
	const t = clamp( ( x - a ) / ( b - a ), 0, 1 );
	return t * t * ( 3 - 2 * t );
};

/** Độ cao đường đỉnh (tỉ lệ chiều cao khung ảnh) tại vị trí f (0 … 1, theo chiều rộng khung ảnh), nội suy tuyến tính. */
export function ridgeTop( f, table = RIDGE_ART.top ) {
	const x = clamp( f, 0, 1 ) * ( table.length - 1 );
	const i = Math.min( table.length - 2, Math.floor( x ) );
	return table[ i ] + ( table[ i + 1 ] - table[ i ] ) * ( x - i );
}

/**
 * @param {number} W Chiều rộng stage (CSS px).
 * @param {number} H Chiều cao stage (CSS px).
 * @param {object} o
 * @param {{horizonLandscape:number, horizonPortrait:number}} o.framing  CONFIG.framing (cùng đóng khung với canvas).
 * @param {number} o.horizonUV   CONFIG.plate.horizonUV (chân trời trong ảnh canvas, 0 = mép trên).
 * @param {{desktop:number[], portrait:number[]}} o.path  CONFIG.stops.path ([x đầu, y đầu, x cuối, y cuối]; chỉ lấy x đầu).
 * @param {number} o.sunClear    Khoảng trống giữa đáy đĩa và đường núi khi mặt trời đã mọc xong (tỉ lệ chiều cao khung ảnh núi).
 * @returns {{artW:number, artH:number, x:number, y:number, sunX:number, sunY:number, r:number, rise:number, peakX:number, peakY:number}}
 *   artW / artH: kích thước khung ảnh núi; x, y: góc trên trái của nó trong stage; sunX, sunY: tâm đĩa lúc mọc xong; r: bán kính đĩa;
 *   rise: quãng đĩa chìm xuống lúc xuất phát (đĩa nằm hẳn sau núi); peakX, peakY: đỉnh núi trái của canvas trong stage (để kiểm thử).
 */
export function introLayout( W, H, { framing, horizonUV, path, sunClear } ) {
	const va = W / Math.max( 1, H );
	// Canvas (scene/plate.js layout): ảnh phủ đúng chiều rộng, chân trời ở hY · H.
	const hY = framing.horizonPortrait + ( framing.horizonLandscape - framing.horizonPortrait ) * smooth01( 0.7, 1.4, va );
	const imgH = W / CANVAS_IMAGE.aspect;
	const imgTop = hY * H - horizonUV * imgH;
	const peakX = CANVAS_IMAGE.peakU * W;
	const peakY = imgTop + CANVAS_IMAGE.peakV * imgH;

	// Khung ảnh núi: đỉnh đầu tiên trùng ( peakX, peakY ); phóng tới mức phủ kín đáy, bên phải và bên trái (không nhỏ hơn chiều rộng khung).
	const A = RIDGE_ART;
	const artW = Math.max(
		W,
		( A.aspect * ( H - peakY ) ) / ( 1 - A.peakY ),
		( W - peakX ) / ( 1 - A.peakX ),
		peakX / A.peakX
	);
	const artH = artW / A.aspect;
	const x = peakX - A.peakX * artW;
	const y = peakY - A.peakY * artH;

	// Mặt trời: x theo điểm đầu của mặt trời canvas; mọc xong thì đáy đĩa cách đường núi sunClear · artH.
	const r = A.sunRadius * artW;
	const sunX = ( va < 1 ? path.portrait[ 0 ] : path.desktop[ 0 ] ) * W;
	const topAt = ( px ) => y + ridgeTop( ( px - x ) / artW ) * artH;
	const sunY = topAt( sunX ) - r - sunClear * artH;
	// Xuất phát: đĩa nằm hẳn sau núi (qua cả đường núi cao nhất trong bề ngang đĩa), thêm 6 px dự phòng.
	let low = topAt( sunX );
	for ( let k = -4; k <= 4; k++ ) {
		low = Math.max( low, topAt( sunX + ( k / 4 ) * r ) );
	}
	const rise = low + r + 6 - sunY;
	return { artW, artH, x, y, sunX, sunY, r, rise, peakX, peakY };
}

/** Ghi bố cục thành biến CSS px trên lớp phủ (services-cinematic.css, khối "MÀN CHỜ"; inline nên thắng giá trị dự phòng của stylesheet). */
export function applyIntroLayout( el, L ) {
	const px = ( v ) => `${ +v.toFixed( 2 ) }px`;
	el.style.setProperty( '--ia-w', px( L.artW ) );
	el.style.setProperty( '--ia-h', px( L.artH ) );
	el.style.setProperty( '--ia-x', px( L.x ) );
	el.style.setProperty( '--ia-y', px( L.y ) );
	el.style.setProperty( '--is-x', px( L.sunX ) );
	el.style.setProperty( '--is-y', px( L.sunY ) );
	el.style.setProperty( '--isha-intro-r', px( L.r ) );
	el.style.setProperty( '--ia-rise', px( L.rise ) );
}
