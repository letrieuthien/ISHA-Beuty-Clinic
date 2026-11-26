/**
 * Chùm thẻ [isha_lp_card_circle]:
 * 1. Vào trang (một lần): khi chùm thẻ vào khung nhìn, mỗi thẻ quay đủ 360° quanh tâm chùm rồi hạ xuống góc nghỉ (CSS isha-lp-spin).
 *    Chỉ chạy trên màn hình > 767px và khi người dùng không bật "giảm chuyển động" (cùng điều kiện với CSS).
 * 2. Khi con trỏ lại gần thì thẻ xòe rộng ra, cả chùm nghiêng nhẹ theo chuột. Chỉ chạy trên thiết bị có hover.
 * Lắng nghe ở section cha (host) vì chùm thẻ nằm dưới lớp nội dung nên không nhận được sự kiện chuột trực tiếp.
 */
( function () {
	'use strict';

	var INTRO_MS = 1800;   // thời lượng một thẻ quay (khớp .isha-lp-stack.is-spinning trong isha-core.css)
	var INTRO_STEP = 75;   // độ trễ giữa hai thẻ liền kề (ms)

	var introOk = ! window.matchMedia( '(prefers-reduced-motion: reduce), (max-width: 767px)' ).matches;
	var hoverOk = ! window.matchMedia( '(prefers-reduced-motion: reduce), (max-width: 767px), (hover: none)' ).matches;

	var clamp = function ( v ) {
		return Math.max( -1, Math.min( 1, v ) );
	};

	// Vào trang: giấu chùm (is-pre), chờ tới khi thấy ≥ 25% thì chạy đúng một lần rồi gỡ class.
	var playIntro = function ( stack ) {
		var count = stack.querySelectorAll( '.isha-lp-stack__item' ).length;
		if ( ! count || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}
		stack.classList.add( 'is-pre' );

		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) {
					return;
				}
				io.disconnect();
				run( stack, count );
			} );
		}, { threshold: 0.25 } );
		io.observe( stack );
	};

	var run = function ( stack, count ) {
		var done = 0;
		var timer = 0;

		var finish = function () {
			window.clearTimeout( timer );
			stack.removeEventListener( 'animationend', onEnd );
			stack.classList.remove( 'is-spinning' );
		};
		var onEnd = function ( e ) {
			if ( e.animationName !== 'isha-lp-spin' ) {
				return;
			}
			done++;
			if ( done >= count ) {
				finish();
			}
		};

		stack.classList.remove( 'is-pre' );
		stack.classList.add( 'is-spinning' );
		stack.addEventListener( 'animationend', onEnd );
		// Dự phòng nếu sự kiện animationend không tới (ví dụ tab bị ẩn giữa chừng): gỡ sau khi hết thời gian chạy.
		timer = window.setTimeout( finish, INTRO_MS + ( count - 1 ) * INTRO_STEP + 400 );
	};

	var bindHover = function ( stack ) {
		var host = stack.closest( '.isha-lp-circle-host' ) || stack.parentElement;
		var frame = 0;
		var pointer = null;

		var update = function () {
			frame = 0;
			// Đang chạy vào trang thì bỏ qua, để tilt không giành transform với animation.
			if ( ! pointer || stack.classList.contains( 'is-pre' ) || stack.classList.contains( 'is-spinning' ) ) {
				return;
			}
			var r = stack.getBoundingClientRect();
			var pad = 48;
			var near = pointer.x > r.left - pad && pointer.x < r.right + pad && pointer.y > r.top - pad && pointer.y < r.bottom + pad;

			stack.classList.toggle( 'is-active', near );
			// Nghiêng theo vị trí con trỏ so với tâm chùm thẻ (chỉ khi con trỏ ở gần để không giật khi lướt qua trang).
			var k = near ? 1 : 0;
			stack.style.setProperty( '--px', ( clamp( ( pointer.x - ( r.left + r.width / 2 ) ) / r.width ) * k ).toFixed( 3 ) );
			stack.style.setProperty( '--py', ( clamp( ( pointer.y - ( r.top + r.height / 2 ) ) / r.height ) * k ).toFixed( 3 ) );
		};

		var reset = function () {
			pointer = null;
			stack.classList.remove( 'is-active' );
			stack.style.removeProperty( '--px' );
			stack.style.removeProperty( '--py' );
		};

		host.addEventListener( 'pointermove', function ( e ) {
			pointer = { x: e.clientX, y: e.clientY };
			if ( ! frame ) {
				frame = window.requestAnimationFrame( update );
			}
		} );
		host.addEventListener( 'pointerleave', reset );
	};

	Array.prototype.forEach.call( document.querySelectorAll( '.isha-lp-stack' ), function ( stack ) {
		if ( introOk ) {
			playIntro( stack );
		}
		if ( hoverOk ) {
			bindHover( stack );
		}
	} );
}() );
