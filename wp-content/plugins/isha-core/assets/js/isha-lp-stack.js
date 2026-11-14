/**
 * Chùm thẻ [isha_lp_card_circle]: khi con trỏ lại gần thì thẻ xòe rộng ra, cả chùm nghiêng nhẹ theo chuột.
 * Chỉ chạy trên thiết bị có hover, màn hình > 767px, và khi người dùng không bật "giảm chuyển động".
 * Lắng nghe ở section cha (host) vì chùm thẻ nằm dưới lớp nội dung nên không nhận được sự kiện chuột trực tiếp.
 */
( function () {
	'use strict';

	var skip = window.matchMedia( '(prefers-reduced-motion: reduce), (max-width: 767px), (hover: none)' );
	if ( skip.matches ) {
		return;
	}

	var clamp = function ( v ) {
		return Math.max( -1, Math.min( 1, v ) );
	};

	Array.prototype.forEach.call( document.querySelectorAll( '.isha-lp-stack' ), function ( stack ) {
		var host = stack.closest( '.isha-lp-circle-host' ) || stack.parentElement;
		var frame = 0;
		var pointer = null;

		var update = function () {
			frame = 0;
			if ( ! pointer ) {
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
	} );
}() );
