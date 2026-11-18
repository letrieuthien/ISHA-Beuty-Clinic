/**
 * "Bánh xe tĩnh lặng" (trang chủ, section Dịch vụ tiêu biểu; template-parts/home/services.php). Script module, nạp ở mọi lượt xem trang
 * chủ nhưng chỉ dựng vòng thẻ khi section sắp vào khung nhìn (cách 800px).
 *
 *   - Vòng xoay: vòng cung ánh kim dùng chung (assets/js/card-ring/) ở chế độ LỒI, thẻ một mặt (.isha-ring--solo), không có nút tạm dừng.
 *     Vòng trôi chậm; rê chuột vào thẻ thì vòng dừng và thẻ phóng nhẹ; kéo thẻ bằng chuột hoặc vuốt để xoay tới / lùi; click thẻ ở rìa
 *     thì quay vào giữa và mở dải hành động (đặt lịch, xem chi tiết).
 *   - Tab nhóm và khối chữ: bấm tab thì vòng quay tới đoạn của nhóm đó. Khi vòng trôi, khối chữ, tab và nút "Khám phá nhóm" theo nhóm
 *     của thẻ ở giữa.
 */

const HOME_RING = {
	CONVEX: true,
	PAUSE_BUTTON: false,
	MOUSE_DRAG: true,
	R_DESKTOP: 600, R_TABLET: 520, R_MOBILE: 700,
	P_RATIO: 1.9,
	ORIGIN_Y: 46,
	CARD_W_DESKTOP: 245, CARD_W_TABLET: 204, CARD_W_MOBILE_VW: 46.92,
	CARD_RATIO: 1.38,
	GAP: 0.14,
	CULL_DEG: 70,
	MIN_CARDS: 8,
	SPIN_MIN_CARDS: 6,
	OMEGA_DESKTOP: 2.2, OMEGA_MOBILE: 2.4,
	HOVER_SCALE: 1.06, FOCUS_SCALE: 1.12,
	DIM_HOVER: 0.72, DIM_OTHERS: 0.6,
	EDGE_FADE: 9,
	SPEC_MAX: 0.7,
	CARD_MAX_VH: 0.5,
	RESUME_AFTER_SWIPE_MS: 2500,
};

const root = document.querySelector( '[data-isha-wheel]' );
const section = root && root.closest( 'section' );
const stage = root && root.querySelector( '[data-isha-ring]' );

if ( root && section ) {
	const tabs = Array.from( root.querySelectorAll( '.isha-wheel__tab' ) );
	const panels = Array.from( root.querySelectorAll( '.isha-wheel__panel' ) );
	const more = root.querySelector( '.isha-wheel__more-link' );
	let shownK = 0;

	/** Hiện nhóm k: panel (hoà tan bằng CSS), tab aria-current, nút "Khám phá nhóm" trỏ tới trang nhóm đó. */
	const setGroup = ( k ) => {
		if ( k === shownK || ! panels[ k ] ) {
			return;
		}
		shownK = k;
		panels.forEach( ( p, i ) => p.classList.toggle( 'is-active', i === k ) );
		tabs.forEach( ( t ) => {
			if ( Number( t.dataset.k ) === k ) {
				t.setAttribute( 'aria-current', 'true' );
			} else {
				t.removeAttribute( 'aria-current' );
			}
		} );
		if ( more ) {
			const url = panels[ k ].getAttribute( 'data-url' ) || '';
			const name = panels[ k ].getAttribute( 'data-name' ) || '';
			if ( url ) {
				more.setAttribute( 'href', url );
				more.setAttribute( 'aria-label', 'Khám phá nhóm: ' + name );
			}
			more.hidden = ! url;
		}
	};

	let started = false;
	const io = new IntersectionObserver( ( entries ) => {
		if ( ! started && entries.some( ( e ) => e.isIntersecting ) ) {
			started = true;
			io.disconnect();
			boot().catch( ( err ) => console.error( '[ISHA wheel]', err ) );
		}
	}, { rootMargin: '800px 0px' } );
	io.observe( section );

	async function boot() {
		let ring = null;
		let lockK = -1;
		const groupOf = ( li ) => Number( li && li.dataset.k !== undefined ? li.dataset.k : -1 );

		// Tab: không JS (hoặc chưa dựng vòng) là liên kết tới trang nhóm; có vòng: quay tới đoạn của nhóm.
		tabs.forEach( ( tab ) => {
			tab.addEventListener( 'click', ( e ) => {
				if ( ! ring || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey ) {
					return;
				}
				e.preventDefault();
				const k = Number( tab.dataset.k );
				setGroup( k );
				toGroup( k );
			} );
		} );

		if ( ! stage ) {
			return;
		}
		const list = stage.querySelector( '.isha-ring__ring' );
		const [ { createCardRing }, { RING } ] = await Promise.all( [
			import( '../card-ring/index.js' ),
			import( '../card-ring/config.js' ),
		] );

		ring = createCardRing( { stage, list, cfg: { ...RING, ...HOME_RING }, origin: section } );
		const realCount = list.querySelectorAll( '.isha-ring__card:not([aria-hidden])' ).length;
		const byGroup = new Map();
		ring.cards.slice( 0, realCount ).forEach( ( li, i ) => {
			const k = groupOf( li );
			if ( ! byGroup.has( k ) ) {
				byGroup.set( k, [] );
			}
			byGroup.get( k ).push( i );
		} );

		/** Quay vòng để đoạn của nhóm k vào giữa (spring, cung ngắn nhất), rồi trôi tiếp. Giảm chuyển động: chọn thẻ đầu của nhóm. */
		function toGroup( k ) {
			const idx = byGroup.get( k );
			if ( ! idx || ! idx.length ) {
				return;
			}
			if ( ring.reduced ) {
				ring.seek( idx[ 0 ] );
				return;
			}
			const mid = ( idx[ 0 ] + idx[ idx.length - 1 ] ) / 2;
			lockK = k;
			ring.dispatch( { type: 'DRAG_START' } );
			ring.dispatch( { type: 'DRAG_END', spin: mid * ring.geometry.slot } );
		}

		/** Nhóm của thẻ đang gần giữa nhất. */
		function centerGroup() {
			const cards = ring.cards;
			let best = 0;
			let bestA = Infinity;
			for ( let i = 0; i < cards.length; i++ ) {
				const a = Math.abs( ring.angle( i ) );
				if ( a < bestA ) {
					bestA = a;
					best = i;
				}
			}
			return groupOf( cards[ best ] );
		}

		// Mỗi khung của vòng: khối chữ, tab và nút theo nhóm ở giữa (trừ khi đang quay tới một nhóm bấm từ tab).
		ring.onFrame( () => {
			if ( lockK >= 0 ) {
				const mode = ring.state.mode;
				if ( mode !== 'SNAPPING' && mode !== 'DRAGGING' ) {
					lockK = -1;
				}
			} else if ( ! ring.reduced ) {
				setGroup( centerGroup() );
			}
		} );

		setGroup( centerGroup() );

		// Kiểm thử / debug (QA, CDP): trạng thái vòng và nhóm đang hiện.
		window.ISHA = window.ISHA || {};
		window.ISHA.wheel = { ring, toGroup, get group() {
			return shownK;
		} };
	}
}
