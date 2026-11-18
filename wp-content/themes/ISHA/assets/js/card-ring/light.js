/**
 * Tia nắng chạm thẻ (khách): đoán chỗ trục mỗi tia nắng của mặt trời (scene/sun.js) gặp viền thẻ trên màn hình, để
 *   - tia dừng NGAY TRƯỚC thẻ (gần chạm, không chạm: cách viền reachGap px), dài dần theo độ cao mặt trời;
 *   - chỗ tiếp xúc trên viền sáng lên màu ánh kim (index.js setHit). Tia lắc, mặt trời lên, vòng xoay đều làm điểm này trượt theo.
 * HÀM THUẦN (test: tests/card-ring-light.test.mjs), toạ độ màn hình px, y hướng xuống.
 *
 * Chiếu thẻ: đúng phép chiếu CSS 3D của ring.js. Mắt cách mặt phẳng màn hình P, gốc phối cảnh O (perspective-origin), tâm vòng C. Thẻ ở góc
 * θ: tâm ( R sin θ, 0, R ( 1 − cos θ ) ), trục ngang của thẻ ( cos θ, 0, sin θ ) (rotateY( −θ ) của CSS); điểm ( x, y, z ) hiện ở
 * O + k · ( C − O + ( x, y ) ), k = P / ( P − z ). Hàng phẳng (điện thoại): không phối cảnh. Bỏ qua phóng / lật khi hover (lệch vài px).
 */

const RAD = Math.PI / 180;

/**
 * 4 góc của thẻ trên màn hình (TL, TR, BR, BL) và w = 1 / k của từng góc (nội suy đúng phối cảnh dọc cạnh).
 *
 * @param {number} theta Góc thẻ (độ).
 * @param {{R:number, P:number, cardW:number, cardH:number, flat:boolean, slot:number, pitch:number}} g ring.geometry.
 * @param {{x:number, y:number}} C Tâm vòng (px).
 * @param {{x:number, y:number}} O Gốc phối cảnh (px).
 * @param {object[]} [out] 4 điểm { x, y, w }.
 */
export function cardQuad( theta, g, C, O, out = [ {}, {}, {}, {} ] ) {
	const hw = g.cardW / 2;
	const hh = g.cardH / 2;
	const U = [ -1, 1, 1, -1 ];
	const V = [ -1, -1, 1, 1 ];
	if ( g.flat ) {
		const cx = C.x + ( theta / g.slot ) * g.pitch;
		for ( let j = 0; j < 4; j++ ) {
			out[ j ].x = cx + U[ j ] * hw;
			out[ j ].y = C.y + V[ j ] * hh;
			out[ j ].w = 1;
		}
		return out;
	}
	const c = Math.cos( theta * RAD );
	const s = Math.sin( theta * RAD );
	// Vòng lồi (g.convex, ring.js): rotateY( θ ) translateZ( R ) sau translateZ( −R ): tâm ( R sin θ, 0, R ( cos θ − 1 ) ), trục ngang
	// ( cos θ, 0, −sin θ ).
	const zc = g.convex ? g.R * ( c - 1 ) : g.R * ( 1 - c );
	const zs = g.convex ? -s : s;
	for ( let j = 0; j < 4; j++ ) {
		const X = g.R * s + U[ j ] * hw * c;
		const Z = zc + U[ j ] * hw * zs;
		const Y = V[ j ] * hh;
		const k = g.P / ( g.P - Z );
		out[ j ].x = O.x + k * ( C.x - O.x + X );
		out[ j ].y = O.y + k * ( C.y - O.y + Y );
		out[ j ].w = 1 / k;
	}
	return out;
}

/**
 * Giao điểm đầu tiên của tia S + t · d (t > 0) với 4 cạnh của tứ giác. null nếu không cắt.
 *
 * @returns {{t:number, edge:number, u:number}|null} edge: 0 trên, 1 phải, 2 dưới, 3 trái; u: vị trí trên cạnh (màn hình, 0 … 1).
 */
export function hitQuad( S, d, q ) {
	let best = null;
	for ( let e = 0; e < 4; e++ ) {
		const A = q[ e ];
		const B = q[ ( e + 1 ) % 4 ];
		const ex = B.x - A.x;
		const ey = B.y - A.y;
		const den = d.x * ey - d.y * ex;
		if ( Math.abs( den ) < 1e-9 ) {
			continue;
		}
		const ax = A.x - S.x;
		const ay = A.y - S.y;
		const t = ( ax * ey - ay * ex ) / den;
		const u = ( ax * d.y - ay * d.x ) / den;
		if ( t > 0 && u >= 0 && u <= 1 && ( ! best || t < best.t ) ) {
			best = { t, edge: e, u };
		}
	}
	return best;
}

/**
 * Điểm trên cạnh (vị trí màn hình u) → toạ độ trên thẻ (px, gốc trên trái), nội suy đúng phối cảnh.
 */
export function edgeLocal( edge, u, q, w, h ) {
	const wa = q[ edge ].w;
	const wb = q[ ( edge + 1 ) % 4 ].w;
	const t = u / wb / ( ( 1 - u ) / wa + u / wb );
	switch ( edge ) {
		case 0:
			return { x: t * w, y: 0 };
		case 1:
			return { x: w, y: t * h };
		case 2:
			return { x: ( 1 - t ) * w, y: h };
		default:
			return { x: 0, y: ( 1 - t ) * h };
	}
}

const smooth = ( a, b, x ) => {
	const t = Math.min( 1, Math.max( 0, ( x - a ) / ( b - a ) ) );
	return t * t * ( 3 - 2 * t );
};

/**
 * Nối tia và thẻ trong một khung.
 *
 * @param {object} o
 * @param {{x:number, y:number}} o.S   Tâm mặt trời trên màn hình (px).
 * @param {number} o.H                  Chiều cao khung của cảnh (px): đơn vị độ dài / độ rộng tia.
 * @param {object[]} o.rays            sun.rayState().list (góc radian với y hướng lên, len, halfFar, gain).
 * @param {number} o.count             Số tia.
 * @param {{i:number, q:object[]}[]} o.quads Thẻ đang hiện (chỉ số thẻ, 4 góc).
 * @param {number} o.cardW
 * @param {number} o.cardH
 * @param {number} o.gap               Khoảng hở đầu tia dừng trước viền (px).
 * @param {number} o.grow              0 … 1: tia dài dần tới thẻ (theo độ cao mặt trời).
 * @param {object} out { rays: [ { len, tip } ], hits: Map< i, { x, y, a, r } > }
 */
export function linkRays( o, out = { rays: [], hits: new Map() } ) {
	out.hits.clear();
	const d = { x: 0, y: 0 };
	for ( let k = 0; k < o.count; k++ ) {
		const ray = o.rays[ k ];
		d.x = Math.cos( ray.angle );
		d.y = -Math.sin( ray.angle ); // màn hình y hướng xuống
		let best = null;
		let bestI = -1;
		let bestQ = null;
		for ( const c of o.quads ) {
			const hit = hitQuad( o.S, d, c.q );
			if ( hit && ( ! best || hit.t < best.t ) ) {
				best = hit;
				bestI = c.i;
				bestQ = c.q;
			}
		}
		const r = out.rays[ k ] || ( out.rays[ k ] = {} );
		if ( ! best || o.grow <= 0 ) {
			r.len = 0; // độ dài mặc định của shader
			r.tip = 0;
			continue;
		}
		const reach = Math.max( 0, best.t - o.gap );
		r.len = ( reach * o.grow ) / o.H;
		r.tip = o.grow;
		// Ánh kim ở chỗ tiếp xúc: chỉ khi đầu tia đã tới sát viền; mạnh theo độ sáng tia; rộng theo bề ngang tia ở đó.
		const a = ray.gain * smooth( 0.75, 1, o.grow );
		if ( a <= 0.001 ) {
			continue;
		}
		const loc = edgeLocal( best.edge, best.u, bestQ, o.cardW, o.cardH );
		const prev = out.hits.get( bestI );
		if ( ! prev || a > prev.a ) {
			out.hits.set( bestI, { x: loc.x, y: loc.y, a, r: Math.min( 90, Math.max( 30, ray.halfFar * o.H * 0.3 ) ), edge: best.edge } );
		}
	}
	return out;
}

/**
 * Độ ánh kim của thẻ theo góc trên vòng (prompt mục 4.3): nắng từ trên, hơi lệch về phía trước; thẻ quay thẳng về người xem bắt sáng mạnh
 * nhất, trôi ra rìa tối dần. spec = max · cos(θ)^pow, 0 khi |θ| ≥ 90°.
 *
 * @param {number} theta Góc thẻ (độ).
 * @param {number} max   SPEC_MAX.
 * @param {number} pow   SPEC_POW.
 */
export function cardSpec( theta, max, pow ) {
	const c = Math.cos( theta * RAD );
	return c > 0 ? max * Math.pow( c, pow ) : 0;
}
