/* ISHA – main.js: header (nổi khi cuộn, drawer di động, menu con, hover từ tính nút Đặt lịch). JS thuần, không thư viện. */
( function ( win, doc ) {
	'use strict';

	var html = doc.documentElement;
	var header = doc.getElementById( 'masthead' );

	if ( ! header ) {
		return;
	}

	var LIMIT = 900; // trùng điểm gãy của CSS: lớn hơn 900px là menu ngang, từ 900px trở xuống là drawer
	var toggle = header.querySelector( '.menu-toggle' );
	var nav = doc.getElementById( 'primary-nav' );
	var drawer = null; // { isOpen(), close() } do initDrawer() gán
	var submenus = null; // { closeAll(), dismiss() } do initSubmenus() gán

	// Header nổi: .is-scrolled khi cuộn quá 40px. Scroll thụ động + rAF; kiểm cả lúc tải trang vì trình duyệt có thể khôi phục vị trí cuộn.
	function initScrollState() {
		var queued = false;

		function paint() {
			queued = false;
			header.classList.toggle( 'is-scrolled', ( win.pageYOffset || html.scrollTop || 0 ) > 40 );
		}

		function request() {
			if ( ! queued ) {
				queued = true;
				win.requestAnimationFrame( paint );
			}
		}

		win.addEventListener( 'scroll', request, { passive: true } );
		win.addEventListener( 'load', paint );
		win.addEventListener( 'pageshow', paint );
		paint();
	}

	// Drawer menu di động: mở/đóng bằng nút menu, đóng bằng Esc (trả focus về nút), khoá cuộn nền, gỡ khoá khi rộng hơn 900px.
	function initDrawer() {
		if ( ! toggle || ! nav ) {
			return;
		}

		function isOpen() {
			return 'true' === toggle.getAttribute( 'aria-expanded' );
		}

		function setOpen( open ) {
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			nav.classList.toggle( 'is-open', open );
			html.classList.toggle( 'isha-nav-open', open );
		}

		// Phần tử có thể focus đang hiện trong header (drawer mở: focus chỉ xoay vòng trong header).
		function focusables() {
			return Array.prototype.filter.call( header.querySelectorAll( 'a[href], button' ), function ( el ) {
				return ! el.disabled && el.getClientRects().length > 0 && 'hidden' !== win.getComputedStyle( el ).visibility;
			} );
		}

		function trapTab( e ) {
			var list = focusables();
			var first = list[ 0 ];
			var last = list[ list.length - 1 ];
			var inside = header.contains( doc.activeElement );

			if ( ! first ) {
				return;
			}
			if ( e.shiftKey && ( ! inside || doc.activeElement === first ) ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && ( ! inside || doc.activeElement === last ) ) {
				e.preventDefault();
				first.focus();
			}
		}

		toggle.addEventListener( 'click', function () {
			setOpen( ! isOpen() );
		} );

		// Bấm một liên kết trong drawer: đóng để gỡ khoá cuộn (liên kết neo trong cùng trang không tải lại trang).
		nav.addEventListener( 'click', function ( e ) {
			var a = e.target && e.target.closest ? e.target.closest( 'a' ) : null;

			if ( a && isOpen() ) {
				setOpen( false );
			}
		} );

		doc.addEventListener( 'keydown', function ( e ) {
			if ( 'Tab' === e.key && isOpen() ) {
				trapTab( e );
			}
		} );

		win.addEventListener( 'resize', function () {
			if ( win.innerWidth > LIMIT && isOpen() ) {
				setOpen( false );
			}
		} );

		drawer = {
			isOpen: isOpen,
			close: function () {
				setOpen( false );
				toggle.focus();
			}
		};
	}

	// Menu con: nút chevron (in từ PHP) bật/tắt .is-open + aria-expanded. Desktop: hover/focus-within do CSS mở, nút cho cảm ứng và bàn phím;
	// di động: accordion (CSS chuyển max-height tới --sub-h đo ở đây).
	function initSubmenus() {
		var parents = header.querySelectorAll( '.primary-menu .menu-item-has-children' );
		var mobile = win.matchMedia( '(max-width: ' + LIMIT + 'px)' );
		var uid = 0;

		if ( ! parents.length ) {
			return;
		}

		function child( li, cls ) {
			var i;

			for ( i = 0; i < li.children.length; i++ ) {
				if ( li.children[ i ].classList.contains( cls ) ) {
					return li.children[ i ];
				}
			}
			return null;
		}

		function measure( li ) {
			var sub = child( li, 'sub-menu' );

			if ( sub ) {
				li.style.setProperty( '--sub-h', sub.scrollHeight + 'px' );
			}
		}

		function setItem( li, open ) {
			var btn = child( li, 'submenu-toggle' );

			li.classList.toggle( 'is-open', open );
			if ( btn ) {
				btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			}
			if ( open ) {
				measure( li );
			}
		}

		function closeAll( except ) {
			Array.prototype.forEach.call( parents, function ( li ) {
				if ( li !== except && li.classList.contains( 'is-open' ) ) {
					setItem( li, false );
				}
			} );
		}

		// Esc (desktop): đóng mục đã bấm mở; mục đang mở nhờ :hover / :focus-within thì thêm .is-dismissed tới khi rời khỏi mục (WCAG 1.4.13).
		function dismiss() {
			Array.prototype.forEach.call( parents, function ( li ) {
				var btn = child( li, 'submenu-toggle' );

				if ( li.classList.contains( 'is-open' ) ) {
					setItem( li, false );
				} else if ( ! mobile.matches && ( li.matches( ':hover' ) || li.matches( ':focus-within' ) ) ) {
					li.classList.add( 'is-dismissed' );
				} else {
					return;
				}
				if ( btn && li.contains( doc.activeElement ) && doc.activeElement !== btn ) {
					btn.focus();
				}
			} );
		}

		Array.prototype.forEach.call( parents, function ( li ) {
			var btn = child( li, 'submenu-toggle' );
			var sub = child( li, 'sub-menu' );

			if ( btn && sub ) {
				uid++;
				sub.id = sub.id || 'primary-sub-' + uid;
				btn.setAttribute( 'aria-controls', sub.id );
				btn.addEventListener( 'click', function () {
					var open = ! li.classList.contains( 'is-open' );

					li.classList.remove( 'is-dismissed' );
					if ( open && ! mobile.matches ) {
						closeAll( li );
					}
					setItem( li, open );
				} );
			}
			li.addEventListener( 'mouseleave', function () {
				li.classList.remove( 'is-dismissed' );
			} );
			li.addEventListener( 'focusout', function ( e ) {
				if ( ! li.contains( e.relatedTarget ) ) {
					li.classList.remove( 'is-dismissed' );
				}
			} );
		} );

		// Desktop: bấm ra ngoài mục đang mở thì đóng.
		doc.addEventListener( 'click', function ( e ) {
			if ( ! mobile.matches && e.target && e.target.closest && ! e.target.closest( '.menu-item-has-children' ) ) {
				closeAll();
			}
		} );

		// Đổi bề ngang (xoay máy, đổi cỡ chữ): đo lại chiều cao accordion của các mục đang mở.
		win.addEventListener( 'resize', function () {
			Array.prototype.forEach.call( parents, function ( li ) {
				if ( li.classList.contains( 'is-open' ) ) {
					measure( li );
				}
			} );
		} );

		submenus = { closeAll: closeAll, dismiss: dismiss };
	}

	// Esc: đóng drawer (trả focus về nút menu); không có drawer mở thì đóng/ẩn menu con.
	doc.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' !== e.key ) {
			return;
		}
		if ( drawer && drawer.isOpen() ) {
			drawer.close();
		} else if ( submenus ) {
			submenus.dismiss();
		}
	} );

	// Nút "Đặt lịch" trên thanh: hút nhẹ theo con trỏ (lực 0.16, tối đa 4.5px ngang, 3px dọc; chữ bên trong dịch tối đa 2px). Chỉ khi có chuột
	// thật và không giảm chuyển động. mousemove chỉ gắn trong lúc chuột ở trên nút; rời nút thì trả về 0 bằng transition của CSS (420ms).
	function initMagnet() {
		var cta = header.querySelector( '.header-cta--bar' );
		var label = cta ? cta.querySelector( '.header-cta__label' ) : null;
		var x = 0;
		var y = 0;
		var cx = 0;
		var cy = 0;

		if ( ! cta || ! win.matchMedia ) {
			return;
		}

		var fine = win.matchMedia( '(hover: hover) and (pointer: fine)' );
		var calm = win.matchMedia( '(prefers-reduced-motion: reduce)' );

		function limit( v, max ) {
			return Math.max( -max, Math.min( max, v ) );
		}

		function put( el, px, py ) {
			el.style.transform = 'translate3d(' + px.toFixed( 2 ) + 'px,' + py.toFixed( 2 ) + 'px,0)';
		}

		function move( e ) {
			x = limit( ( e.clientX - cx ) * 0.16, 4.5 );
			y = limit( ( e.clientY - cy ) * 0.16, 3 );
			put( cta, x, y );
			if ( label ) {
				put( label, limit( x * 0.45, 2 ), limit( y * 0.65, 2 ) );
			}
		}

		cta.addEventListener( 'mouseenter', function () {
			var r;

			if ( ! fine.matches || calm.matches ) {
				return;
			}
			r = cta.getBoundingClientRect();
			cx = r.left + r.width / 2 - x; // tâm gốc của nút: trừ phần đang dịch để không tự cộng dồn
			cy = r.top + r.height / 2 - y;
			cta.classList.add( 'is-magnet' );
			cta.addEventListener( 'mousemove', move );
		} );

		cta.addEventListener( 'mouseleave', function () {
			cta.removeEventListener( 'mousemove', move );
			cta.classList.remove( 'is-magnet' );
			x = 0;
			y = 0;
			cta.style.transform = '';
			if ( label ) {
				label.style.transform = '';
			}
		} );
	}

	// Quả cầu cờ: lật, nảy nhẹ, rồi mới chuyển trang. Giảm chuyển động hoặc bấm có phím tắt thì đi thẳng.
	function initCoin() {
		var coins = doc.querySelectorAll( '.header-lang__coin' );
		var calm = win.matchMedia( '(prefers-reduced-motion: reduce)' );

		Array.prototype.forEach.call( coins, function ( coin ) {
			var flip = coin.querySelector( '.header-lang__flip' );
			coin.addEventListener( 'click', function ( e ) {
				if ( calm.matches || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || coin.classList.contains( 'is-flipping' ) ) {
					return;
				}
				e.preventDefault();
				coin.classList.add( 'is-flipping' );
				coin.setAttribute( 'aria-disabled', 'true' );

				var done = false;
				var go = function () {
					if ( ! done ) {
						done = true;
						win.location.href = coin.href;
					}
				};
				flip.addEventListener( 'transitionend', function () {
					coin.classList.add( 'is-settling' );
				}, { once: true } );
				coin.addEventListener( 'animationend', function ( ev ) {
					if ( ev.target === coin && ev.animationName === 'isha-lang-settle' ) {
						go();
					}
				} );
				win.setTimeout( go, 1000 );
			} );
		} );

		win.addEventListener( 'pageshow', function ( e ) {
			if ( e.persisted ) {
				Array.prototype.forEach.call( coins, function ( coin ) {
					coin.classList.remove( 'is-flipping', 'is-settling' );
					coin.removeAttribute( 'aria-disabled' );
				} );
			}
		} );
	}

	initScrollState();
	initDrawer();
	initSubmenus();
	initMagnet();
	initCoin();
}( window, document ) );
