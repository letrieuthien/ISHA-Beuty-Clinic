/**
 * Keyframe mặt trời / ánh sáng / sương của section "Bình minh lan tỏa". HÀM THUẦN: không DOM, không three.js (chạy được bằng node --test).
 *
 * - computeStops( N, cfg ): N điểm dừng (một liệu trình một điểm), tính MỘT lần lúc tải, đóng băng (Object.freeze).
 * - sampleStops( stops, p, out ): giá trị nội suy tại progress p ∈ [0, 1], có "plateau" (mặt trời gần như đứng yên khi đọc card).
 *   Ghi vào out (không cấp phát object mỗi khung hình).
 * - activeIndex( p, N, prev, hysteresis ): chỉ số liệu trình đang xem, có trễ quanh biên để không nhấp nháy.
 *
 * Quy ước progress: đoạn i chiếm [i / N, (i + 1) / N]; điểm dừng i nằm ở tâm đoạn (i + 0.5) / N.
 * Màu không có mã hex: sunColor là đường đi qua tên token (tokens.js đổi sang THREE.Color); sampleStops trả sunTone ∈ [0, 1] trên đường đó.
 */

export const LABELS = Object.freeze( [ 'Rạng đông', 'Bình minh', 'Ban mai', 'Nắng sớm', 'Nắng vàng', 'Toàn quang' ] );

// Đường màu của mặt trời theo thời điểm: vàng golden lúc rạng đông, qua vàng chiều (sunset) tới ánh ngày (daylight).
export const SUN_TOKENS = Object.freeze( [ '--isha-golden', '--isha-sunset', '--isha-daylight' ] );

// Mặt trời NEO THEO MÀN HÌNH: sunX, sunY là vị trí tâm mặt trời trên khung (0..1, gốc trên trái). Cảnh chiếu điểm đó ra hướng 3D, nên
// mặt trời luôn nằm giữa phía trên dãy card ở mọi tỉ lệ khung; độ cao thật (khoảng 2° → 12°) tự suy ra từ camera.
export const DEFAULT_STOP_CFG = Object.freeze( {
	sunYStart: 0.47,    // điểm dừng đầu: ló nửa trên sau đỉnh chính (đỉnh khoảng 44%, chân trời khoảng 55% chiều cao khung)
	sunYEnd: 0.3,       // điểm dừng cuối: cao giữa trời, dưới đầu section
	sunXStart: 0.48,
	sunXEnd: 0.5,
	fogBase: 1,         // hệ số mật độ sương gốc
	hold: 0.35,         // tỉ lệ đoạn "giữ" quanh mỗi điểm dừng
} );

const clamp = ( v, a, b ) => Math.min( b, Math.max( a, v ) );
const lerp = ( a, b, t ) => a + ( b - a ) * t;
const easeOutSine = ( t ) => Math.sin( ( t * Math.PI ) / 2 );
const smoothstep = ( t ) => t * t * ( 3 - 2 * t );

// Các trường số được nội suy giữa hai điểm dừng.
export const NUMERIC_KEYS = Object.freeze( [
	'sunX', 'sunY', 'sunTone', 'skyFloor', 'halo', 'raysOpacity', 'sunIntensity', 'exposure', 'turbidity', 'rayleigh', 'mieCoefficient', 'mieG',
	'fogDensity', 'cloudPinkMix', 'godRays',
] );

/**
 * @param {number} N   Số liệu trình (1 tới 6).
 * @param {object} cfg Ghi đè DEFAULT_STOP_CFG.
 * @returns {ReadonlyArray<object>}
 */
export function computeStops( N, cfg = {} ) {
	const c = { ...DEFAULT_STOP_CFG, ...cfg };
	const n = Math.max( 1, Math.floor( N ) || 1 );
	const stops = [];

	for ( let i = 0; i < n; i++ ) {
		const t = n === 1 ? 1 : i / ( n - 1 );
		const e = easeOutSine( t );
		stops.push( Object.freeze( {
			index: i,
			t,
			sunX: lerp( c.sunXStart, c.sunXEnd, e ),
			sunY: lerp( c.sunYStart, c.sunYEnd, e ),
			skyFloor: lerp( 0.3, 0.62, e ),               // trời sáng dần
			halo: lerp( 0.8, 1.2, e ),                    // hào quang mạnh dần
			raysOpacity: lerp( 0.18, 0.34, e ),           // tia line-art rõ dần
			sunTone: t,                                   // vị trí trên SUN_TOKENS
			sunColor: Object.freeze( { tokens: SUN_TOKENS, t } ),
			sunIntensity: lerp( 2.2, 3.6, e ),
			exposure: lerp( 1.35, 1.0, e ),               // bù sáng: rạng đông vẫn SÁNG (một chế độ sáng)
			turbidity: lerp( 6, 3, e ),
			rayleigh: lerp( 2.2, 0.8, e ),        // thấp ở cuối: trời bớt xanh lạnh
			mieCoefficient: lerp( 0.006, 0.003, e ),
			mieG: 0.8,
			fogDensity: lerp( 1.0, 0.6, e ) * c.fogBase,
			cloudPinkMix: lerp( 0.9, 0.35, e ),
			godRays: lerp( 0.7, 0.25, e ),
			label: LABELS[ Math.round( t * ( LABELS.length - 1 ) ) ],
		} ) );
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
	return smoothstep( clamp( ( local - h / 2 ) / ( 1 - h ), 0, 1 ) );
}

/**
 * @param {ReadonlyArray<object>} stops Kết quả computeStops.
 * @param {number} p   Progress [0, 1].
 * @param {object} out Object nhận kết quả (tái sử dụng mỗi khung hình).
 * @param {number} hold Tỉ lệ giữ (mặc định DEFAULT_STOP_CFG.hold).
 * @returns {object} out, có thêm i0, i1, w (để debug).
 */
export function sampleStops( stops, p, out = {}, hold = DEFAULT_STOP_CFG.hold ) {
	const n = stops.length;
	const u = clamp( p, 0, 1 ) * n - 0.5;
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
	for ( const k of NUMERIC_KEYS ) {
		out[ k ] = lerp( a[ k ], b[ k ], w );
	}
	out.label = w < 0.5 ? a.label : b.label;
	out.i0 = i0;
	out.i1 = i1;
	out.w = w;
	return out;
}

/**
 * Chỉ số liệu trình đang xem: floor( p * N ), nhưng chỉ đổi khỏi prev khi p vượt biên đoạn của prev một khoảng hysteresis / N
 * (cuộn nhanh qua nhiều đoạn thì nhảy thẳng tới đoạn cuối).
 *
 * @param {number} p    Progress.
 * @param {number} N    Số điểm dừng.
 * @param {number} prev Chỉ số trước đó (-1 nếu chưa có).
 * @param {number} hysteresis Độ trễ tính theo phần của một đoạn.
 */
export function activeIndex( p, N, prev = -1, hysteresis = 0.02 ) {
	const n = Math.max( 1, N );
	const pc = clamp( p, 0, 1 );
	const raw = clamp( Math.floor( pc * n ), 0, n - 1 );
	if ( prev < 0 || prev >= n || raw === prev ) {
		return raw;
	}
	const h = hysteresis / n;
	if ( raw > prev ) {
		return pc >= ( prev + 1 ) / n + h ? raw : prev;
	}
	return pc <= prev / n - h ? raw : prev;
}
