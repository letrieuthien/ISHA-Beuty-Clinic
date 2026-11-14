/**
 * Trang chi tiết liệu trình ISHA: service-single.js (docs/home-architecture.md mục 12). IIFE ES5; ISHA.motion là phụ thuộc mềm.
 * Việc: ảnh khung mở đầu tiến chậm theo cuộn, các bước hiện lần lượt, xem lớn ảnh liệu trình trong dialog. Hàng vuốt ngang của ảnh do home.js dựng.
 */
( function () {
	'use strict';

	var win = window, doc = document, html = doc.documentElement;
	var ISHA = win.ISHA = win.ISHA || {};

	// NodeList thành mảng.
	function all( root, sel ) {
		return Array.prototype.slice.call( root.querySelectorAll( sel ) );
	}

	// Báo lỗi của một khối lên console.
	function report( name, err ) {
		if ( win.console && win.console.error ) {
			win.console.error( '[ISHA.service] ' + name, err );
		}
	}

	// Giảm chuyển động đang bật?
	function reduced() {
		return !! ( win.matchMedia && win.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	// Khung ảnh mở đầu tiến chậm (1 tới 1,12) theo cuộn; giảm chuyển động thì giữ nguyên.
	function dolly( root, motion ) {
		var open = root.querySelector( '.isha-svc__open' ), img = open && open.querySelector( '.isha-svc__img' ), p0 = -1;

		if ( ! img ) {
			return;
		}
		motion.scene( open, {
			compact: 'same',
			update: function ( p, state ) {
				var k;

				if ( p0 < 0 ) {
					p0 = p;
				}
				k = state.reduced ? 0 : Math.min( 1, Math.max( 0, ( p - p0 ) / Math.max( 0.001, 1 - p0 ) ) );
				img.style.transform = k > 0.001 ? 'scale(' + ( 1 + 0.12 * k ).toFixed( 4 ) + ')' : '';
			},
			enter: function ( state ) {
				open.classList.toggle( 'is-live', ! state.reduced );
			},
			leave: function () {
				open.classList.remove( 'is-live' );
			}
		} );
	}

	// Các bước hiện lần lượt (so le theo --i) khi danh sách vào khung nhìn.
	function steps( root, motion ) {
		var list = root.querySelector( '.isha-svc__steps' );

		if ( ! list || reduced() ) {
			return;
		}
		all( list, '.isha-svc__step' ).forEach( function ( li, k ) {
			li.style.setProperty( '--i', String( Math.min( k, 5 ) ) );
		} );
		list.classList.add( 'is-pre' );
		motion.inView( list, function () {
			list.classList.remove( 'is-pre' );
		}, { once: true, threshold: 0.15 } );
	}

	// Xem lớn ảnh trong dialog: Esc đóng, mũi tên đổi ảnh, trả focus về ảnh đã mở.
	function viewer( root ) {
		var dlg = root.querySelector( '.isha-svc__viewer' ), links = all( root, '[data-isha-zoom]' );
		var img, cap, num, nav, cur = 0, from = null;

		if ( ! dlg || ! links.length || 'function' !== typeof dlg.showModal ) {
			return;
		}
		img = dlg.querySelector( '.isha-svc__viewer-img' );
		cap = dlg.querySelector( '.isha-svc__viewer-cap' );
		num = dlg.querySelector( '.isha-svc__viewer-count' );
		nav = dlg.querySelector( '.isha-svc__viewer-nav' );
		nav.hidden = links.length < 2;

		// Hiện ảnh thứ k (vòng tròn).
		function show( k ) {
			var a;

			cur = ( k + links.length ) % links.length;
			a = links[ cur ];
			img.alt = a.getAttribute( 'data-alt' ) || '';
			img.src = a.getAttribute( 'href' );
			cap.textContent = a.getAttribute( 'data-cap' ) || '';
			cap.hidden = ! cap.textContent;
			num.textContent = links.length > 1 ? ( cur + 1 ) + ' / ' + links.length : '';
		}

		links.forEach( function ( a, k ) {
			a.addEventListener( 'click', function ( e ) {
				if ( e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || 0 !== e.button ) {
					return;
				}
				e.preventDefault();
				from = a;
				show( k );
				dlg.showModal();
				html.classList.add( 'isha-svc-lock' );
			} );
		} );
		dlg.addEventListener( 'close', function () {
			html.classList.remove( 'isha-svc-lock' );
			if ( from && from.focus ) {
				from.focus();
			}
		} );
		dlg.addEventListener( 'click', function ( e ) {
			var t = e.target;

			if ( t === dlg || t.hasAttribute( 'data-isha-stage' ) || t.hasAttribute( 'data-isha-close' ) ) {
				dlg.close();
			} else if ( t.hasAttribute( 'data-isha-prev' ) ) {
				show( cur - 1 );
			} else if ( t.hasAttribute( 'data-isha-next' ) ) {
				show( cur + 1 );
			}
		} );
		dlg.addEventListener( 'keydown', function ( e ) {
			if ( links.length > 1 && ! ( e.altKey || e.ctrlKey || e.metaKey || e.shiftKey ) && ( 'ArrowLeft' === e.key || 'ArrowRight' === e.key ) ) {
				e.preventDefault();
				show( cur + ( 'ArrowRight' === e.key ? 1 : -1 ) );
			}
		} );
	}

	// Khởi chạy khi DOM sẵn sàng; lõi chuyển động không có thì chỉ còn hộp xem lớn.
	function boot() {
		var root = doc.querySelector( '.isha-svc' ), motion = ISHA.motion && ISHA.motion.started ? ISHA.motion : null;

		if ( ! root ) {
			return;
		}
		try {
			viewer( root );
		} catch ( err ) {
			report( 'viewer', err );
		}
		if ( motion ) {
			try {
				dolly( root, motion );
				steps( root, motion );
			} catch ( err2 ) {
				report( 'motion', err2 );
			}
		}
	}

	if ( 'loading' === doc.readyState ) {
		doc.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
