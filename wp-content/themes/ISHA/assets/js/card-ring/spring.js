/**
 * Chuyển động của vòng (prompt mục 6). HÀM THUẦN (test: tests/card-ring-spring.test.mjs). Toàn bộ chuyển động là một biến spin (độ).
 *
 * - Xoay tự động: spin += ω · dt; ω đổi tốc độ luôn nội suy: ω += ( ω_target − ω ) · ( 1 − exp( −k · dt ) ), k = DAMP_K. Dừng: ω_target
 *   = 0, vòng chậm dần như bánh xe có quán tính.
 * - Seek (đưa một thẻ vào giữa): spring giảm chấn TỚI HẠN trên spin, đích theo cung ngắn nhất (chênh lệch chuẩn hoá về
 *   [−chu kỳ / 2, chu kỳ / 2), chu kỳ = span của vòng ảo, xem ring.js), dừng trong khoảng SEEK_MS, không vượt đích. Nghiệm giải tích mỗi
 *   bước nên ổn định với mọi dt.
 */

/** Chênh lệch nhỏ nhất từ a tới b trên vòng chu kỳ period. */
export function shortestDelta( a, b, period ) {
	const d = ( ( ( b - a ) % period ) + period ) % period;
	return d >= period / 2 ? d - period : d;
}

/** ω tiến về ω_target, độc lập tốc độ khung. */
export function dampOmega( omega, target, k, dt ) {
	return omega + ( target - omega ) * ( 1 - Math.exp( -k * dt ) );
}

/**
 * Tần số riêng ω₀ để spring tới hạn còn sai lệch tol (tỉ lệ quãng) sau settleS giây: ( 1 + ω₀t ) · e^(−ω₀t) = tol.
 * tol 0.001 → ω₀ · t ≈ 9.23.
 */
export function omegaForSettle( settleS, tol = 0.001 ) {
	let x = 5;
	for ( let i = 0; i < 40; i++ ) {
		// Newton trên f( x ) = ( 1 + x ) e^(−x) − tol; f'( x ) = −x e^(−x)
		const f = ( 1 + x ) * Math.exp( -x ) - tol;
		x -= f / ( -x * Math.exp( -x ) );
	}
	return x / settleS;
}

/**
 * Spring tới hạn một chiều. state: { x, v, target, w } (w = ω₀). step( state, dt ) cập nhật tại chỗ bằng nghiệm giải tích:
 * x( t ) = target + ( c1 + c2 t ) e^(−w t), c1 = x − target, c2 = v + w · c1.
 */
export function springStep( s, dt ) {
	const c1 = s.x - s.target;
	const c2 = s.v + s.w * c1;
	const e = Math.exp( -s.w * dt );
	s.x = s.target + ( c1 + c2 * dt ) * e;
	s.v = ( c2 - s.w * ( c1 + c2 * dt ) ) * e;
	return s;
}

/** Spring đã tới nơi (sai lệch và vận tốc đủ nhỏ). */
export function springDone( s, eps = 0.01 ) {
	return Math.abs( s.x - s.target ) < eps && Math.abs( s.v ) < eps * 10;
}

/**
 * Bộ điều khiển spin: auto (ω, có quán tính) hoặc seek (spring). Thuần, không DOM.
 *
 * @param {object} cfg RING (DAMP_K, SEEK_MS).
 */
export function createMotion( cfg ) {
	const m = {
		spin: 0,
		omega: 0,
		omegaTarget: 0,
		seeking: null, // { x, v, target, w } khi đang seek
		/** Bắt đầu seek tới góc đích (theo cung ngắn nhất trên chu kỳ period). Vận tốc hiện tại được giữ để không giật. */
		seek( targetSpin, period ) {
			const target = m.spin + shortestDelta( m.spin, targetSpin, period );
			m.seeking = { x: m.spin, v: m.omega, target, w: omegaForSettle( cfg.SEEK_MS / 1000 ) };
			m.omega = 0;
		},
		/** @returns {boolean} true đúng lúc seek vừa tới nơi. */
		step( dt ) {
			if ( m.seeking ) {
				springStep( m.seeking, dt );
				m.spin = m.seeking.x;
				if ( springDone( m.seeking ) ) {
					m.spin = m.seeking.target;
					m.seeking = null;
					return true;
				}
				return false;
			}
			m.omega = dampOmega( m.omega, m.omegaTarget, cfg.DAMP_K, dt );
			m.spin += m.omega * dt;
			return false;
		},
	};
	return m;
}
