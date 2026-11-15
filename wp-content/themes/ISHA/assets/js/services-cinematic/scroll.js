/**
 * Cuộn: ScrollTrigger chỉ ĐO progress (start 'top top', end 'bottom bottom'), KHÔNG pin; stage là position: sticky trong CSS. Lenis chỉ trên
 * thiết bị không cảm ứng (cảm ứng: cuộn gốc của trình duyệt). Không dùng normalizeScroll (hỏng cuộn iOS); ignoreMobileResize để thanh địa
 * chỉ iOS ẩn/hiện không làm refresh.
 *
 * Bàn phím khi focus nằm trong section: ↓ → PageDown sang điểm dừng kế, ↑ ← PageUp về trước, Home/End về đầu/cuối, Esc đóng card,
 * Enter trên stage mở lại card.
 */
import { CONFIG } from './config.js';

export async function setupScroll( { section, stage, store, isTouch, reduced } ) {
	const [ { gsap }, { ScrollTrigger } ] = await Promise.all( [ import( 'gsap' ), import( 'gsap/ScrollTrigger' ) ] );
	gsap.registerPlugin( ScrollTrigger );
	ScrollTrigger.config( { ignoreMobileResize: true } );

	let lenis = null;
	let tick = null;
	if ( ! isTouch && ! reduced ) {
		try {
			const { default: Lenis } = await import( 'lenis' );
			lenis = new Lenis( { autoRaf: false } );
			lenis.on( 'scroll', ScrollTrigger.update );
			tick = ( t ) => lenis.raf( t * 1000 );
			gsap.ticker.add( tick );
			gsap.ticker.lagSmoothing( 0 );
		} catch ( err ) {
			lenis = null; // Không có Lenis vẫn cuộn gốc bình thường.
		}
	}

	const N = store.state.N;
	// "Trong section" tính theo vị trí cuộn, gồm cả hai mép (isActive của ScrollTrigger là false đúng tại start, khi stage vừa chạm đỉnh).
	const sync = ( self ) => {
		const y = self.scroll();
		store.setProgress( self.progress );
		store.setInView( y >= self.start - 1 && y <= self.end + 1 );
	};
	const st = ScrollTrigger.create( {
		trigger: section,
		start: 'top top',
		end: 'bottom bottom',
		onUpdate: sync,
		onToggle: sync,
		onRefresh: sync,
		snap: CONFIG.SNAP && ! isTouch ? { snapTo: Array.from( { length: N }, ( _, i ) => ( i + 0.5 ) / N ), duration: { min: 0.3, max: 0.8 }, delay: 0.15 } : false,
	} );
	sync( st );

	// Toạ độ cuộn của tâm đoạn i.
	function yOf( i ) {
		const k = Math.min( N - 1, Math.max( 0, i ) );
		return st.start + ( ( k + 0.5 ) / N ) * ( st.end - st.start );
	}

	function go( i ) {
		const y = yOf( i );
		if ( lenis ) {
			lenis.scrollTo( y, { duration: 1.2 } );
		} else {
			window.scrollTo( { top: y, behavior: reduced ? 'auto' : 'smooth' } );
		}
	}

	function onKey( e ) {
		if ( e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey ) {
			return;
		}
		const cur = Math.max( 0, store.state.index );
		let to = null;
		switch ( e.key ) {
			case 'ArrowDown':
			case 'ArrowRight':
			case 'PageDown':
				to = cur + 1;
				break;
			case 'ArrowUp':
			case 'ArrowLeft':
			case 'PageUp':
				to = cur - 1;
				break;
			case 'Home':
				to = 0;
				break;
			case 'End':
				to = N - 1;
				break;
			case 'Escape':
				store.dispatch( { type: 'DISMISS' } );
				e.preventDefault();
				return;
			case 'Enter':
				if ( e.target === stage ) {
					store.dispatch( { type: 'REOPEN' } );
					e.preventDefault();
				}
				return;
			default:
				return;
		}
		// Ra ngoài hai đầu thì để trình duyệt cuộn tiếp như bình thường (rời section).
		if ( to < 0 || to > N - 1 ) {
			return;
		}
		e.preventDefault();
		go( to );
	}
	section.addEventListener( 'keydown', onKey );

	// Resize / xoay màn hình: refresh sau 150ms.
	let rt = 0;
	function onResize() {
		window.clearTimeout( rt );
		rt = window.setTimeout( () => ScrollTrigger.refresh(), 150 );
	}
	window.addEventListener( 'orientationchange', onResize );

	return {
		gsap,
		ScrollTrigger,
		lenis,
		go,
		destroy() {
			section.removeEventListener( 'keydown', onKey );
			window.removeEventListener( 'orientationchange', onResize );
			st.kill();
			if ( tick ) {
				gsap.ticker.remove( tick );
			}
			if ( lenis ) {
				lenis.destroy();
			}
		},
	};
}
