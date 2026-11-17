/**
 * Form công khai và popup Đặt lịch ISHA: booking.js (docs/home-architecture.md mục 17). IIFE ES5, không thư viện; cần fetch, FormData, Promise
 * (thiếu thì để nguyên đường gửi thường, liên kết mở trang Đặt lịch hẹn).
 * - Form [data-isha-form="<action>"]: gửi AJAX tới admin-ajax.php, nonce lấy mới (action isha_form_nonce, nhớ 10 phút), lỗi hiện dưới từng ô.
 * - Popup dialog#isha-book: mở khi bấm liên kết tới trang Đặt lịch hẹn hoặc [data-isha-booking]; ?service=ID chọn sẵn dịch vụ.
 */
( function () {
	'use strict';

	var win = window, doc = document, html = doc.documentElement;
	var cfg = ( win.ISHA && win.ISHA.forms ) || {};
	var str = cfg.strings || {};
	var nonces = null, nonceAt = 0, NONCE_TTL = 600000;

	if ( ! cfg.ajax || ! win.fetch || ! win.FormData || ! win.Promise ) {
		return;
	}

	// NodeList thành mảng.
	function all( root, sel ) {
		return Array.prototype.slice.call( root.querySelectorAll( sel ) );
	}

	// Lấy nonce mới của mọi form (nhớ trong 10 phút; lỗi phiên thì xoá để lấy lại).
	function getNonces( fresh ) {
		if ( ! fresh && nonces && Date.now() - nonceAt < NONCE_TTL ) {
			return win.Promise.resolve( nonces );
		}
		return win.fetch( cfg.ajax + '?action=isha_form_nonce', { credentials: 'same-origin', cache: 'no-store' } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				if ( ! res || ! res.success ) {
					throw new Error( 'nonce' );
				}
				nonces = res.data;
				nonceAt = Date.now();
				return nonces;
			} );
	}

	// Xoá mọi báo lỗi của form.
	function clearErrors( form ) {
		all( form, '[aria-invalid="true"]' ).forEach( function ( el ) {
			el.removeAttribute( 'aria-invalid' );
		} );
		all( form, '.isha-form-lux__err' ).forEach( function ( p ) {
			p.textContent = '';
			p.hidden = true;
		} );
	}

	// Hiện lỗi dưới từng ô (khoá là tên trường), focus ô sai đầu tiên.
	function showErrors( form, errors ) {
		var first = null;

		Object.keys( errors || {} ).forEach( function ( name ) {
			var input = form.querySelector( '[name="' + name + '"]' ), box = form.querySelector( '[data-field="' + name + '"]' ), p = box && box.querySelector( '.isha-form-lux__err' );

			if ( input ) {
				input.setAttribute( 'aria-invalid', 'true' );
				first = first || input;
			}
			if ( p ) {
				p.textContent = errors[ name ];
				p.hidden = false;
			}
		} );
		if ( first ) {
			first.focus();
		}
	}

	// Gửi một form qua AJAX.
	function submit( form ) {
		var action = form.getAttribute( 'data-isha-form' ), btn = form.querySelector( '[type="submit"]' ), status = form.querySelector( '.isha-form-lux__status' );
		var label = btn ? btn.textContent : '';

		if ( form.classList.contains( 'is-busy' ) ) {
			return;
		}
		form.classList.add( 'is-busy' );
		form.setAttribute( 'aria-busy', 'true' );
		if ( btn ) {
			btn.disabled = true;
			btn.textContent = str.sending || '…';
		}
		clearErrors( form );
		if ( status ) {
			status.textContent = '';
			status.className = 'isha-form-lux__status';
		}

		getNonces( false )
			.then( function ( n ) {
				var field = form.querySelector( '[name="' + action + '_nonce"]' ), data;

				if ( field && n[ action ] ) {
					field.value = n[ action ];
				}
				data = new win.FormData( form );
				data.set( 'action', action );
				return win.fetch( cfg.ajax, { method: 'POST', body: data, credentials: 'same-origin' } );
			} )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				var d = ( res && res.data ) || {};

				if ( res && res.success ) {
					form.reset();
					form.dispatchEvent( new win.CustomEvent( 'isha:sent', { bubbles: true, detail: d } ) );
					if ( status ) {
						status.textContent = d.message || '';
						status.className = 'isha-form-lux__status is-ok';
					}
					return;
				}
				if ( 'error' === d.status ) {
					nonces = null;   // phiên hết hạn: lần sau lấy nonce mới
				}
				showErrors( form, d.errors );
				if ( status ) {
					status.textContent = d.message || str.network || '';
					status.className = 'isha-form-lux__status is-err';
				}
			} )
			.catch( function () {
				nonces = null;
				if ( status ) {
					status.textContent = str.network || '';
					status.className = 'isha-form-lux__status is-err';
				}
			} )
			.then( function () {
				form.classList.remove( 'is-busy' );
				form.removeAttribute( 'aria-busy' );
				if ( btn ) {
					btn.disabled = false;
					btn.textContent = label;
				}
			} );
	}

	// Nối mọi form: tự kiểm tra ở máy chủ (câu báo tiếng Việt thống nhất), bỏ báo lỗi của ô khi người dùng sửa.
	function wireForms() {
		all( doc, '[data-isha-form]' ).forEach( function ( form ) {
			form.noValidate = true;
			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
				submit( form );
			} );
			form.addEventListener( 'input', function ( e ) {
				var box = e.target.closest ? e.target.closest( '[data-field]' ) : null, p = box && box.querySelector( '.isha-form-lux__err' );

				if ( 'true' === e.target.getAttribute( 'aria-invalid' ) ) {
					e.target.removeAttribute( 'aria-invalid' );
					if ( p ) {
						p.hidden = true;
					}
				}
			} );
		} );
	}

	// Popup Đặt lịch.
	function wireDialog() {
		var dlg = doc.getElementById( 'isha-book' ), form, done, message, opener = null, onBookingPage;

		if ( ! dlg || 'function' !== typeof dlg.showModal || ! cfg.bookingPath ) {
			return;
		}
		form = dlg.querySelector( '[data-isha-form]' );
		done = dlg.querySelector( '[data-isha-book-done]' );
		message = dlg.querySelector( '[data-isha-book-message]' );
		onBookingPage = norm( win.location.pathname ) === norm( cfg.bookingPath );

		// Đường dẫn chuẩn hoá (bỏ dấu / cuối) để so sánh.
		function norm( p ) {
			return String( p || '' ).replace( /\/+$/, '' ).toLowerCase();
		}

		// Mở popup, chọn sẵn dịch vụ.
		function open( service, trigger ) {
			var select = form && form.querySelector( '[name="isha_service"]' ), first;

			opener = trigger || doc.activeElement;
			if ( done ) {
				done.hidden = true;
			}
			if ( form ) {
				form.hidden = false;
				form.parentNode.hidden = false;
			}
			if ( select && service && select.querySelector( 'option[value="' + String( service ).replace( /\D/g, '' ) + '"]' ) ) {
				select.value = String( service ).replace( /\D/g, '' );
			}
			html.classList.add( 'isha-book-open' );
			dlg.showModal();
			first = form && form.querySelector( '.isha-form-lux__control' );
			if ( first ) {
				first.focus();
			}
			getNonces( false ).catch( function () {} );
		}

		// Đóng popup (sự kiện close trả focus và gỡ khoá cuộn).
		function close() {
			if ( dlg.open ) {
				dlg.close();
			}
		}

		dlg.addEventListener( 'close', function () {
			html.classList.remove( 'isha-book-open' );
			if ( opener && opener.focus ) {
				opener.focus();
			}
		} );
		dlg.addEventListener( 'click', function ( e ) {
			if ( e.target === dlg || ( e.target.closest && e.target.closest( '[data-isha-book-close]' ) ) ) {
				close();
			}
		} );
		if ( form && done ) {
			form.addEventListener( 'isha:sent', function ( e ) {
				if ( message ) {
					message.textContent = ( e.detail && e.detail.message ) || '';
				}
				form.parentNode.hidden = true;
				done.hidden = false;
				done.focus();
			} );
		}

		// Chặn liên kết tới trang Đặt lịch hẹn (bỏ qua khi giữ phím Ctrl/Cmd/Shift/Alt, bấm chuột giữa hoặc mở thẻ mới).
		doc.addEventListener( 'click', function ( e ) {
			var el = e.target.closest ? e.target.closest( 'a[href], [data-isha-booking]' ) : null, url;

			if ( ! el || e.defaultPrevented || 0 !== e.button || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || dlg.contains( el ) ) {
				return;
			}
			if ( el.hasAttribute( 'data-isha-booking' ) ) {
				e.preventDefault();
				open( el.getAttribute( 'data-isha-booking' ), el );
				return;
			}
			if ( el.target && '_self' !== el.target ) {
				return;
			}
			try {
				url = new win.URL( el.href, win.location.href );
			} catch ( err ) {
				return;
			}
			if ( url.origin !== win.location.origin || norm( url.pathname ) !== norm( cfg.bookingPath ) || onBookingPage ) {
				return;
			}
			e.preventDefault();
			open( url.searchParams.get( 'service' ), el );
		} );
	}

	// Khởi chạy khi DOM sẵn sàng.
	function boot() {
		wireForms();
		wireDialog();
	}

	if ( 'loading' === doc.readyState ) {
		doc.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
