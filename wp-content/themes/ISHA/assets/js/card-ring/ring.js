/**
 * Hình học của vòng cung lõm (prompt mục 3.1): người xem đứng ở TÂM một hình trụ bán kính R, thẻ dựng trên mặt trụ, quay mặt vào tâm.
 *
 *   stage   : perspective: P; perspective-origin: 50% ORIGIN_Y%
 *   ring    : transform-style: preserve-3d; transform: translateZ(R)      // đưa tâm trụ tới vị trí mắt
 *   card[i] : transform: rotateY(-θᵢ) translateZ(-R)                       // θ > 0: bên phải
 *
 * Với P = R, thẻ ở giữa (θ = 0) có tỉ lệ 1; thẻ ở góc θ cách mắt R · cos θ nên to hơn 1 / cos θ lần (độ cong lõm của Curve Reel); tâm
 * thẻ trên màn hình ở x = R · tan θ.
 *
 * Vòng lặp: thẻ i ở θᵢ = wrap( i · slot − spin ), wrap về [−span / 2, span / 2) với span = n · slot. Thẻ chỉ "quay vòng" ở ±span / 2,
 * luôn ngoài góc culling (ringCount bảo đảm span ≥ 2 · CULL + slot), nên không cần nhân bản DOM lúc chạy. |θ| > CULL: visibility hidden
 * (thẻ quá gần mặt phẳng camera vỡ hình trên Safari).
 *
 * Điện thoại (khách chọn): thẻ PHẲNG, một hàng ngang cùng kích thước, không 3D. Vẫn chạy bằng cùng biến spin (độ) để chuyển động, seek
 * và máy trạng thái dùng chung: thẻ ở x = θ / slot · pitch (pitch = chiều rộng thẻ · (1 + GAP)); ẩn khi ra ngoài khung quá một thẻ.
 *
 * Phần tính toán là hàm thuần (test: tests/card-ring-ring.test.mjs); createRing ghi DOM, không đọc layout trong vòng lặp.
 */

const RAD = Math.PI / 180;

/** Góc (độ) một thẻ chiếm trên cung: (chiều rộng · (1 + gap)) / R. */
export function slotDeg( cardW, R, gap ) {
	return ( ( cardW * ( 1 + gap ) ) / R ) / RAD;
}

/** Đưa góc về [−span / 2, span / 2). */
export function wrapDeg( a, span ) {
	const h = span / 2;
	return ( ( ( ( a + h ) % span ) + span ) % span ) - h;
}

/**
 * Số thẻ trên vòng (thẻ thật lặp lại nguyên bộ): ít nhất minCards và đủ để cung rộng hơn 2 · cull + slot (điểm quay vòng nằm ngoài vùng
 * nhìn thấy). Trả về bội của nReal.
 */
export function ringCount( nReal, slot, cull, minCards ) {
	const need = Math.max( minCards, Math.ceil( ( 2 * cull + slot ) / slot ) );
	return Math.max( 1, nReal ) * Math.ceil( need / Math.max( 1, nReal ) );
}

/**
 * Số thẻ cho hàng phẳng: bội của nReal, ≥ minCards, hàng dài hơn khung + 2 thẻ (điểm quay vòng nằm ngoài khung).
 */
export function flatCount( nReal, pitch, viewW, minCards ) {
	const need = Math.max( minCards, Math.ceil( viewW / pitch ) + 3 );
	return Math.max( 1, nReal ) * Math.ceil( need / Math.max( 1, nReal ) );
}

/** Có xoay vòng không: chỉ khi số thẻ thật ≥ SPIN_MIN_CARDS (khách: trên 5). */
export function spins( nReal, cfg ) {
	return nReal >= cfg.SPIN_MIN_CARDS;
}

/** Góc spin để n thẻ đứng yên canh giữa (thẻ giữa, hoặc khe giữa hai thẻ giữa, ở θ = 0). */
export function centerSpin( n, slot ) {
	return ( ( n - 1 ) * slot ) / 2;
}

/** Góc θᵢ (độ) của từng thẻ, ghi vào out (Float64Array dài n). */
export function cardAngles( n, slot, spin, out ) {
	const span = n * slot;
	for ( let i = 0; i < n; i++ ) {
		out[ i ] = wrapDeg( i * slot - spin, span );
	}
	return out;
}

/** Tâm thẻ trên màn hình (px từ giữa khung) và tỉ lệ phóng khi P = R. */
export function project( theta, R ) {
	return { x: R * Math.tan( theta * RAD ), scale: 1 / Math.cos( theta * RAD ) };
}

/**
 * @param {object}      o
 * @param {HTMLElement} o.stage  Khung có perspective.
 * @param {HTMLElement} o.ring   Vòng (preserve-3d).
 * @param {HTMLElement[]} o.cards Thẻ (đã nhân bản đủ ringCount).
 * @param {object}      o.cfg    RING.
 */
export function createRing( { stage, ring, cards, cfg } ) {
	const n = cards.length;
	const theta = new Float64Array( n );
	const hidden = new Uint8Array( n ).fill( 2 ); // 2 = chưa ghi lần nào
	let geo = { R: 1000, P: 1000, slot: 13, cardW: 200, cardH: 280 };
	let lastSpin = NaN;

	/** Gọi khi đổi kích thước (đo một lần, không đo trong vòng lặp). */
	/** g: { R, P, cardW, cardH, flat, viewW }. */
	function setGeometry( g ) {
		geo = { ...g, slot: slotDeg( g.cardW, g.R, cfg.GAP ), pitch: g.cardW * ( 1 + cfg.GAP ) };
		stage.classList.toggle( 'is-flat', !! g.flat );
		stage.style.perspective = g.flat ? 'none' : `${ g.P }px`;
		// ORIGIN_Y tính trên phần lõi của khung (trừ đệm --ring-bleed trên / dưới, card-ring.css).
		stage.style.perspectiveOrigin = `50% calc(var(--ring-bleed, 0px) + (100% - 2 * var(--ring-bleed, 0px)) * ${ cfg.ORIGIN_Y / 100 })`;
		stage.style.setProperty( '--ring-card-w', `${ g.cardW }px` );
		stage.style.setProperty( '--ring-card-h', `${ g.cardH }px` );
		// Lớp glint (card-ring.css): hình vuông 1.5 lần đường chéo thẻ, xoay quanh tâm.
		stage.style.setProperty( '--ring-glint-size', `${ Math.ceil( 1.5 * Math.hypot( g.cardW, g.cardH ) ) }px` );
		ring.style.transform = g.flat ? 'none' : `translateZ(${ g.R }px)`;
		lastSpin = NaN;
	}

	/** Ghi transform / visibility cho góc quay spin (độ). Bỏ qua nếu spin đổi < 0.01°. */
	function render( spin, force = false ) {
		if ( ! force && Math.abs( spin - lastSpin ) < 0.01 ) {
			return;
		}
		lastSpin = spin;
		cardAngles( n, geo.slot, spin, theta );
		for ( let i = 0; i < n; i++ ) {
			const t = theta[ i ];
			const x = geo.flat ? ( t / geo.slot ) * geo.pitch : 0;
			const hide = ( geo.flat ? Math.abs( x ) > geo.viewW / 2 + geo.cardW : Math.abs( t ) > cfg.CULL_DEG ) ? 1 : 0;
			if ( hide !== hidden[ i ] ) {
				hidden[ i ] = hide;
				cards[ i ].style.visibility = hide ? 'hidden' : '';
			}
			if ( ! hide ) {
				cards[ i ].style.transform = geo.flat ? `translate3d(${ x.toFixed( 2 ) }px, 0, 0)` : `rotateY(${ ( -t ).toFixed( 3 ) }deg) translateZ(${ -geo.R }px)`;
			}
		}
	}

	return {
		setGeometry,
		render,
		get geometry() {
			return geo;
		},
		/** Góc hiện tại của thẻ i (độ), sau render gần nhất. */
		angle: ( i ) => theta[ i ],
	};
}
