/**
 * Điểm dừng của khung cảnh "Bình minh lan tỏa" (đặc tả v3 mục 6). HÀM THUẦN: không DOM, không three.js (chạy được bằng node --test).
 *
 * - computeStops( N, cfg, portrait ): N điểm dừng (một liệu trình một điểm), tính một lần, đóng băng. t_i = N === 1 ? 1 : i / (N − 1),
 *   qua cfg.ease: 'linear' (mặc định: mặt trời đi đều theo progress, khách yêu cầu) hoặc 'sine' (easeOutSine của đặc tả: nhanh lúc đầu, chậm
 *   dần). Mỗi trường nội suy giữa [đầu, cuối] của cfg. Vị trí mặt trời (sunX, sunY: tỉ lệ khung, y từ trên) theo đường đi
 *   cfg.path.desktop hoặc cfg.path.portrait (khung dọc): hai bố cục khác nhau nên cảnh tính sẵn cả hai bộ điểm dừng.
 * - sampleStops( stops, p, out, hold ): giá trị tại progress p ∈ [0, 1]; hold > 0 thì có vùng giữ (plateau) quanh mỗi điểm dừng, hold = 0
 *   thì nội suy tuyến tính (tốc độ đều). Ghi vào out.
 * - fullSpan( p, N ): đổi progress của section sang progress lấy mẫu sao cho p = 0 là điểm dừng đầu và p = 1 là điểm dừng cuối (khung cảnh
 *   chuyển động trên toàn bộ progress 0 … 1 của bình minh, không đứng yên ở nửa đoạn đầu / cuối).
 * - riseValues( h, cfg, out ), sunHeight( sunY, path ): các giá trị theo ĐỘ CAO mặt trời (góp ý khách sau bước 4).
 *
 * Quy ước progress: đoạn i chiếm [i / N, (i + 1) / N]; điểm dừng i nằm ở tâm đoạn (i + 0.5) / N.
 * Không còn nhãn thời điểm ("Rạng đông" … "Toàn quang"): khách bỏ khỏi kịch bản (lệch đặc tả mục 6).
 */

export const DEFAULT_STOP_CFG = Object.freeze( {
	// Đường đi của mặt trời [x đầu, y đầu, x cuối, y cuối] (tỉ lệ khung, y từ trên). Đặc tả 5.4 ghi desktop (52%, 74%) → (58%, 18%), khung
	// dọc (50%, 68%) → (56%, 15%), nhưng tính cho một ảnh có đỉnh khối neo ở 64% khung; ảnh thật có đỉnh khối neo ở khoảng 45% khung và
	// (52%, 74%) nằm sâu trong thung lũng, trước các lớp núi giữa. Điểm đầu chỉnh về ngay dưới đường chân trời xa trong khe thung lũng bên
	// trái khối neo: đĩa bị rặng xa che một phần, rồi nhô lên (README mục 31).
	path: Object.freeze( { desktop: Object.freeze( [ 0.575, 0.483, 0.6, 0.18 ] ), portrait: Object.freeze( [ 0.56, 0.58, 0.58, 0.15 ] ) } ),
	// Tốc độ mặt trời theo progress (khách: đi đều). Đặc tả: easeOutSine + giữ 0.35 quanh điểm dừng, để đọc card đơn;
	// đo ở 1440 × 900, N = 4: 35/76 nấc bánh xe mặt trời đứng yên, rồi có nấc vọt 16 px; đoạn 2 đi 118 px, đoạn cuối 19 px. Card nay là dãy
	// trượt (không gắn điểm dừng) nên bỏ vùng giữ: tuyến tính, đều khoảng 3.6 px mỗi nấc trên cả section (cùng fullSpan).
	ease: 'linear',
	hold: 0, // tỉ lệ đoạn "giữ" quanh mỗi điểm dừng; 0 = không giữ, nội suy tuyến tính
	// [ điểm dừng đầu, điểm dừng cuối ]
	sunScale: Object.freeze( [ 1, 1 ] ),           // đặc tả 1.00 → 0.78; khách muốn đĩa to nhẹ khi lên: CONFIG.rise.sunScale lo phần đó
	sunIntensity: Object.freeze( [ 0.75, 1 ] ),
	rimIntensity: Object.freeze( [ 1, 0.35 ] ),
	volumetric: Object.freeze( [ 0.9, 0.32 ] ),
	fogDensity: Object.freeze( [ 1, 0.62 ] ),
	skyMix: Object.freeze( [ 0, 1 ] ),             // vị trí trong gradient trời (rạng đông → toàn quang)
	cloudOpacity: Object.freeze( [ 0.18, 0.3 ] ),
	dustIntensity: Object.freeze( [ 0.45, 1 ] ),
	exposure: Object.freeze( [ 1.15, 1 ] ),
	grain: Object.freeze( [ 0.03, 0.022 ] ),
} );

// Trường có giá trị [đầu, cuối] trong cfg; sunX, sunY tính từ đường đi.
export const RANGE_FIELDS = Object.freeze( [
	'sunScale', 'sunIntensity', 'rimIntensity', 'volumetric', 'fogDensity', 'skyMix', 'cloudOpacity', 'dustIntensity', 'exposure', 'grain',
] );
export const STOP_FIELDS = Object.freeze( [ 'sunX', 'sunY', ...RANGE_FIELDS ] );

const clamp = ( v, a, b ) => Math.min( b, Math.max( a, v ) );
const lerp = ( a, b, t ) => a + ( b - a ) * t;
const easeOutSine = ( t ) => Math.sin( ( t * Math.PI ) / 2 );
const smoothstep = ( t ) => t * t * ( 3 - 2 * t );

/**
 * @param {number}  N        Số liệu trình (1 tới 6).
 * @param {object}  cfg      Ghi đè DEFAULT_STOP_CFG (CONFIG.stops; debug chỉnh trực tiếp các mảng [đầu, cuối]).
 * @param {boolean} portrait Khung dọc: dùng cfg.path.portrait.
 * @returns {ReadonlyArray<object>}
 */
export function computeStops( N, cfg = {}, portrait = false ) {
	const c = { ...DEFAULT_STOP_CFG, ...cfg };
	const path = portrait ? c.path.portrait : c.path.desktop;
	const n = Math.max( 1, Math.floor( N ) || 1 );
	const stops = [];
	for ( let i = 0; i < n; i++ ) {
		const t = n === 1 ? 1 : i / ( n - 1 );
		const e = c.ease === 'sine' ? easeOutSine( t ) : t;
		const s = { index: i, t, sunX: lerp( path[ 0 ], path[ 2 ], e ), sunY: lerp( path[ 1 ], path[ 3 ], e ) };
		for ( const k of RANGE_FIELDS ) {
			s[ k ] = lerp( c[ k ][ 0 ], c[ k ][ 1 ], e );
		}
		stops.push( Object.freeze( s ) );
	}
	return Object.freeze( stops );
}

/**
 * Hệ số trộn có vùng giữ: bỏ hold/2 ở mỗi đầu, phần giữa đi theo smoothstep.
 *
 * @param {number} local Vị trí trong khoảng giữa hai điểm dừng, [0, 1].
 * @param {number} hold  Tỉ lệ giữ.
 */
export function plateau( local, hold ) {
	const h = clamp( hold, 0, 0.95 );
	const x = clamp( ( local - h / 2 ) / ( 1 - h ), 0, 1 );
	// smoothstep chỉ để vào / ra vùng giữ êm; không giữ thì tuyến tính (tốc độ đều, không nhấp nhả ở mỗi điểm dừng).
	return h > 0 ? smoothstep( x ) : x;
}

/**
 * Progress của section → progress lấy mẫu điểm dừng trải trên toàn progress: p = 0 ứng với tâm đoạn đầu (điểm dừng 0), p = 1 với tâm
 * đoạn cuối (điểm dừng N − 1). Không có nó, khung cảnh đứng yên ở nửa đoạn đầu và nửa đoạn cuối.
 *
 * @param {number} p Progress [0, 1].
 * @param {number} N Số điểm dừng.
 */
export function fullSpan( p, N ) {
	const n = Math.max( 1, N );
	return ( 0.5 + clamp( Number.isFinite( p ) ? p : 0, 0, 1 ) * ( n - 1 ) ) / n;
}

/**
 * @param {ReadonlyArray<object>} stops Kết quả computeStops.
 * @param {number} p    Progress [0, 1].
 * @param {object} out  Object nhận kết quả (tái sử dụng mỗi khung hình).
 * @param {number} hold Tỉ lệ giữ (mặc định DEFAULT_STOP_CFG.hold).
 * @returns {object} out, có thêm i0, i1, w (để debug).
 */
export function sampleStops( stops, p, out = {}, hold = DEFAULT_STOP_CFG.hold ) {
	const n = stops.length;
	const u = clamp( Number.isFinite( p ) ? p : 0, 0, 1 ) * n - 0.5;
	let i0 = Math.floor( u );
	let w = 0;
	if ( i0 < 0 ) {
		i0 = 0;
	} else if ( i0 >= n - 1 ) {
		i0 = n - 1;
	} else {
		w = plateau( u - i0, hold );
	}
	const i1 = Math.min( n - 1, i0 + 1 );
	const a = stops[ i0 ];
	const b = stops[ i1 ];
	for ( const k of STOP_FIELDS ) {
		out[ k ] = lerp( a[ k ], b[ k ], w );
	}
	out.i0 = i0;
	out.i1 = i1;
	out.w = w;
	return out;
}

/**
 * Những giá trị đổi theo ĐỘ CAO mặt trời (góp ý khách sau bước 4 của v3): khi mặt trời nhô lên, vignette giảm, mép cắt trời mềm dần (uBand),
 * FOV dọc giảm nhẹ, quầng sáng dần, đĩa to nhẹ, tia dài ra, toả rộng ra (góc giữa các tia và độ rộng tia), lùi xa dần (raysZ) và lắc
 * nhanh dần (rayTurn: 0.18 → 0.5 ở đỉnh, khách yêu cầu).
 * h ∈ [0, 1]: phần đường mặt trời đã đi (0 điểm đầu, 1 điểm cuối), tính từ vị trí mặt trời đã làm mượt nên bám đúng mặt trời chứ không bám
 * thanh cuộn. Mỗi trường nội suy tuyến tính giữa [đầu, cuối] của cfg (CONFIG.rise). Ghi vào out, không cấp phát (gọi mỗi khung).
 */
export const RISE_FIELDS = Object.freeze( [ 'vignette', 'band', 'fov', 'halo', 'sunScale', 'rayLength', 'raySpread', 'rayWidth', 'raysZ', 'rayTurn' ] );

export function riseValues( h, cfg, out = {} ) {
	const t = clamp( Number.isFinite( h ) ? h : 0, 0, 1 );
	for ( const k of RISE_FIELDS ) {
		const r = cfg[ k ];
		out[ k ] = r[ 0 ] + ( r[ 1 ] - r[ 0 ] ) * t;
	}
	return out;
}

/** Độ cao h của mặt trời trên đường đi [x0, y0, x1, y1] (y từ trên xuống) khi đang ở toạ độ dọc sunY. */
export function sunHeight( sunY, path ) {
	const span = path[ 1 ] - path[ 3 ];
	return Math.abs( span ) < 1e-6 ? 0 : clamp( ( path[ 1 ] - sunY ) / span, 0, 1 );
}
