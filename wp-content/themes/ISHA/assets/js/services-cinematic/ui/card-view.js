/**
 * Hiển thị card theo state machine (latest-wins). Mỗi lần card hiển thị đổi:
 * - dừng mọi tween đang chạy trên các card; card không liên quan ẩn ngay (không kẹt nửa chừng);
 * - card đang hiện "gập" ra: rotateY 0 → ±90°, opacity → 0, 0.45s power2.in, rồi inert + aria-hidden;
 * - card mới "mở" vào: rotateY ∓90° → 0, translateZ −60 → 0, opacity → 1, 0.7s power3.out, trễ 0.15s sau card ra; gỡ inert ngay khi bắt đầu.
 * Hướng xoay theo hướng di chuyển index (cuộn ngược thì đảo). Cuộn rất nhanh qua nhiều điểm dừng: chỉ animate tới index cuối cùng.
 * Giảm chuyển động: không lật, chỉ đổi ngay.
 */
export function createCardView( { cards, gsap, reduced } ) {
	let shown = -1;

	function hide( el ) {
		el.inert = true;
		el.setAttribute( 'aria-hidden', 'true' );
	}

	function show( el ) {
		el.inert = false;
		el.removeAttribute( 'aria-hidden' );
	}

	cards.forEach( ( el ) => {
		hide( el );
		gsap.set( el, { autoAlpha: 0, rotationY: 0, z: 0, transformOrigin: '50% 50%' } );
	} );

	function render( next, hint = 0 ) {
		if ( next === shown ) {
			return;
		}
		const prev = shown;
		shown = next;
		const dir = hint || ( prev >= 0 && next >= 0 ? Math.sign( next - prev ) : 1 ) || 1;

		gsap.killTweensOf( cards );
		cards.forEach( ( el, i ) => {
			if ( i !== prev && i !== next ) {
				hide( el );
				gsap.set( el, { autoAlpha: 0, rotationY: 0, z: 0 } );
			}
		} );

		const out = prev >= 0 ? cards[ prev ] : null;
		const inn = next >= 0 ? cards[ next ] : null;

		if ( reduced ) {
			if ( out ) {
				hide( out );
				gsap.set( out, { autoAlpha: 0 } );
			}
			if ( inn ) {
				show( inn );
				gsap.set( inn, { autoAlpha: 1, rotationY: 0, z: 0 } );
			}
			return;
		}

		if ( out ) {
			hide( out );
			gsap.to( out, { rotationY: 90 * dir, autoAlpha: 0, duration: 0.45, ease: 'power2.in' } );
		}
		if ( inn ) {
			show( inn );
			gsap.fromTo(
				inn,
				{ rotationY: -90 * dir, z: -60, autoAlpha: 0 },
				{ rotationY: 0, z: 0, autoAlpha: 1, duration: 0.7, ease: 'power3.out', delay: out ? 0.15 : 0 }
			);
		}
	}

	return {
		render,
		get shown() {
			return shown;
		},
		destroy() {
			gsap.killTweensOf( cards );
			cards.forEach( ( el ) => {
				el.inert = false;
				el.removeAttribute( 'aria-hidden' );
				gsap.set( el, { clearProps: 'all' } );
			} );
		},
	};
}
