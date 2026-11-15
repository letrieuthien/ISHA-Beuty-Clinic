/**
 * Đường chân trời địa hình nhìn từ camera, để biết mặt trời bị núi che bao nhiêu (glare mờ đi khi mặt trời nằm sau đỉnh, lóe lên khi ló ra).
 *
 * Tính trên CPU MỘT lần lúc dựng: với vài vị trí camera dọc ray (đầu, giữa, cuối), mỗi phương vị trong ±halfRangeDeg quanh hướng nhìn (−z)
 * lấy góc ngẩng lớn nhất của mặt đất (height(x, z) của terrain.js, đi từ dMin tới mép địa hình). Mỗi khung hình chỉ nội suy bảng: chia đĩa
 * mặt trời thành 9 cột, cộng phần cột nằm trên đường chân trời. Không đọc lại GPU (không readPixels), chạy được ở mọi chế độ.
 * Mây không che (mây đặt cao hơn đường đi của mặt trời); cây ở xa hai bên (phương vị ≥ 5°) nên bỏ qua.
 */
const DEG = Math.PI / 180;

export function buildHorizon( height, points, { half = 3000, halfRangeDeg = 14, stepDeg = 0.5, dMin = 250 } = {} ) {
	const n = Math.round( ( 2 * halfRangeDeg ) / stepDeg ) + 1;
	const profiles = points.map( ( P ) => {
		const out = new Float32Array( n );
		for ( let i = 0; i < n; i++ ) {
			const az = ( -halfRangeDeg + i * stepDeg ) * DEG;
			const sx = Math.sin( az );
			const sz = -Math.cos( az );
			let best = -Math.PI / 2;
			for ( let d = dMin; ; d *= 1.04 ) {
				const x = P.x + sx * d;
				const z = P.z + sz * d;
				if ( Math.abs( x ) > half || Math.abs( z ) > half ) {
					break;
				}
				const e = Math.atan2( height( x, z ) - P.y, d );
				if ( e > best ) {
					best = e;
				}
			}
			out[ i ] = best;
		}
		return { z: P.z, out };
	} ).sort( ( a, b ) => b.z - a.z ); // z giảm dần (camera tiến về −z)

	function at( profile, az ) {
		const f = Math.min( n - 1, Math.max( 0, ( az / DEG + halfRangeDeg ) / stepDeg ) );
		const i = Math.min( n - 2, Math.floor( f ) );
		const w = f - i;
		return profile.out[ i ] * ( 1 - w ) + profile.out[ i + 1 ] * w;
	}

	// Góc ngẩng của đường chân trời tại phương vị az khi camera ở độ sâu camZ (nội suy giữa các profile).
	function horizon( az, camZ ) {
		const p = profiles;
		if ( camZ >= p[ 0 ].z ) {
			return at( p[ 0 ], az );
		}
		for ( let k = 1; k < p.length; k++ ) {
			if ( camZ >= p[ k ].z ) {
				const w = ( p[ k - 1 ].z - camZ ) / ( p[ k - 1 ].z - p[ k ].z );
				return at( p[ k - 1 ], az ) * ( 1 - w ) + at( p[ k ], az ) * w;
			}
		}
		return at( p[ p.length - 1 ], az );
	}

	return {
		/**
		 * Tỉ lệ đĩa mặt trời không bị địa hình che, [0, 1].
		 *
		 * @param {{x:number,y:number,z:number}} dir    Hướng mặt trời (đã chuẩn hoá).
		 * @param {number}                       radius Bán kính góc của đĩa (radian).
		 * @param {number}                       camZ   Vị trí z của camera.
		 * @param {?{vis:number,el:number,az:number}} out Nếu có: ghi thêm tâm (độ cao, phương vị) của PHẦN ĐĨA ĐANG LỘ, để đặt glare ở đó
		 *                                       (mặt trời nấp sau núi thì tia sao bật ra từ mép núi, không xuyên qua núi).
		 */
		visibility( dir, radius, camZ, out = null ) {
			const el = Math.asin( Math.max( -1, Math.min( 1, dir.y ) ) );
			const az = Math.atan2( dir.x, -dir.z );
			const cosEl = Math.max( 0.2, Math.cos( el ) );
			let vis = 0;
			let tot = 0;
			let wy = 0;
			let wx = 0;
			for ( let k = 0; k < 9; k++ ) {
				const x = ( ( ( k + 0.5 ) / 9 ) * 2 - 1 ) * radius;
				const c = Math.sqrt( radius * radius - x * x );
				const hz = horizon( az + x / cosEl, camZ );
				const lo = Math.max( el - c, hz );
				const len = Math.min( 2 * c, Math.max( 0, el + c - lo ) );
				vis += len;
				wy += len * ( lo + el + c ) / 2;
				wx += len * x;
				tot += 2 * c;
			}
			const v = tot > 0 ? vis / tot : 1;
			if ( out ) {
				out.vis = v;
				out.el = vis > 0 ? wy / vis : el;
				out.az = az + ( vis > 0 ? wx / vis / cosEl : 0 );
			}
			return v;
		},
		horizon,
	};
}
