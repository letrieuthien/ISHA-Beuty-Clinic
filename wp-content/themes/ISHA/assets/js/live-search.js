/* ISHA – live-search.js: popup Tìm kiếm nhanh (inc/live-search.php). JS thuần, không thư viện. */
( function ( win, doc ) {
	'use strict';

	var cfg = ( win.ISHA && win.ISHA.search ) || {};
	var dlg = doc.getElementById( 'isha-search' );

	if ( ! dlg || 'function' !== typeof dlg.showModal || ! cfg.ajax ) {
		return; // Không có popup: nút giữ nguyên là liên kết tới trang tìm kiếm mặc định.
	}

	var html = doc.documentElement;
	var input = dlg.querySelector( '.isha-search__input' );
	var list = dlg.querySelector( '.isha-search__list' );
	var clearBtn = dlg.querySelector( '[data-isha-search-clear]' );
	var emptyText = dlg.querySelector( '[data-isha-search-empty]' );
	var status = dlg.querySelector( '[data-isha-search-status]' );
	var strings = cfg.strings || {};
	var icons = cfg.icons || {};
	var triggers = doc.querySelectorAll( '[data-isha-search-open]' );
	var DEBOUNCE = 250;
	var CLOSE_MS = 280;
	var cache = new win.Map();
	var nonce = cfg.nonce || '';
	var timer = 0;
	var controller = null;
	var opener = null;
	var items = [];
	var active = -1;
	var closing = 0;
	var uid = 0;

	// Nút trên header trở thành nút mở popup (chỉ khi có JS).
	Array.prototype.forEach.call( triggers, function ( el ) {
		el.setAttribute( 'role', 'button' );
		el.setAttribute( 'aria-haspopup', 'dialog' );
		el.setAttribute( 'aria-expanded', 'false' );
		el.setAttribute( 'aria-controls', 'isha-search' );
		el.setAttribute( 'aria-keyshortcuts', 'Control+K Meta+K' );
		el.addEventListener( 'click', function ( e ) {
			if ( e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || 0 !== e.button ) {
				return; // mở thẻ mới: để liên kết tới trang tìm kiếm mặc định.
			}
			e.preventDefault();
			open( el );
		} );
		el.addEventListener( 'keydown', function ( e ) {
			if ( ' ' === e.key ) { // role="button": phím cách cũng mở.
				e.preventDefault();
				open( el );
			}
		} );
	} );

	function setState( state ) {
		dlg.setAttribute( 'data-state', state );
		input.setAttribute( 'aria-expanded', 'results' === state ? 'true' : 'false' );
	}

	function announce( text ) {
		if ( status ) {
			status.textContent = text;
		}
	}

	function fmt( str, value ) {
		return String( str || '' ).replace( /%[sd]/, value );
	}

	// --- Mở / đóng -----------------------------------------------------------------
	function setExpanded( value ) {
		Array.prototype.forEach.call( triggers, function ( el ) {
			el.setAttribute( 'aria-expanded', value ? 'true' : 'false' );
		} );
	}

	function open( trigger ) {
		if ( closing ) {
			win.clearTimeout( closing );
			closing = 0;
		}
		if ( dlg.open ) {
			input.focus();
			return;
		}
		opener = trigger || doc.activeElement;
		html.classList.add( 'isha-search-open' );
		dlg.showModal();
		setExpanded( true );
		// Khung hình sau mới thêm class để transition chạy từ trạng thái ẩn.
		win.requestAnimationFrame( function () {
			win.requestAnimationFrame( function () {
				dlg.classList.add( 'is-open' );
			} );
		} );
		input.focus();
		input.select();
	}

	function close() {
		if ( ! dlg.open || closing ) {
			return;
		}
		dlg.classList.remove( 'is-open' );
		closing = win.setTimeout( function () {
			closing = 0;
			dlg.close();
		}, CLOSE_MS );
	}

	dlg.addEventListener( 'close', function () {
		dlg.classList.remove( 'is-open' );
		html.classList.remove( 'isha-search-open' );
		setExpanded( false );
		abort();
		if ( opener && opener.focus ) {
			opener.focus();
		}
	} );

	// Esc: trình duyệt phát "cancel"; chặn đóng tức thì để chạy hiệu ứng đóng.
	dlg.addEventListener( 'cancel', function ( e ) {
		e.preventDefault();
		close();
	} );
	// Esc tự xử lý luôn: một số trình duyệt dùng Esc trong ô nhập để xoá chữ và không phát "cancel".
	dlg.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key ) {
			e.preventDefault();
			close();
		}
	} );

	dlg.addEventListener( 'click', function ( e ) {
		var chip;

		if ( e.target === dlg || ( e.target.closest && e.target.closest( '[data-isha-search-close]' ) ) ) {
			close();
			return;
		}
		chip = e.target.closest ? e.target.closest( '[data-isha-search-chip]' ) : null;
		if ( chip ) {
			input.value = chip.getAttribute( 'data-isha-search-chip' ) || '';
			input.focus();
			onInput( true );
		}
	} );

	// Ctrl/Cmd + K mở nhanh (không chen ngang khi một popup khác đang mở, ví dụ popup Đặt lịch).
	doc.addEventListener( 'keydown', function ( e ) {
		var other;

		if ( ( e.ctrlKey || e.metaKey ) && ! e.altKey && ! e.shiftKey && 'k' === String( e.key ).toLowerCase() ) {
			other = doc.querySelector( 'dialog[open]:not(#isha-search)' );
			if ( other ) {
				return;
			}
			e.preventDefault();
			if ( dlg.open ) {
				input.focus();
				input.select();
			} else {
				open( triggers[ 0 ] || null );
			}
		}
	} );

	// --- Nhập liệu -------------------------------------------------------------------
	function abort() {
		win.clearTimeout( timer );
		if ( controller ) {
			controller.abort();
			controller = null;
		}
	}

	function onInput( immediate ) {
		var q = input.value.trim();

		clearBtn.hidden = '' === input.value;
		abort();
		if ( '' === q ) {
			setState( 'idle' );
			announce( '' );
			return;
		}
		if ( cache.has( q.toLowerCase() ) ) {
			render( q, cache.get( q.toLowerCase() ) );
			return;
		}
		setState( 'loading' );
		announce( strings.loading || '' );
		timer = win.setTimeout( function () {
			search( q, false );
		}, immediate ? 0 : DEBOUNCE );
	}

	input.addEventListener( 'input', function () {
		onInput( false );
	} );

	clearBtn.addEventListener( 'click', function () {
		input.value = '';
		onInput( true );
		input.focus();
	} );

	function post( q, signal ) {
		var body = new win.FormData();

		body.append( 'action', 'isha_live_search' );
		body.append( 'q', q );
		body.append( 'nonce', nonce );
		body.append( 'lang', ( html.getAttribute( 'lang' ) || '' ).split( '-' )[ 0 ] );
		return win.fetch( cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin', signal: signal } );
	}

	// Trang lấy từ bộ nhớ đệm có thể mang nonce đã hết hạn: xin nonce mới một lần rồi thử lại.
	function refreshNonce( signal ) {
		return win.fetch( cfg.ajax + '?action=isha_live_search_nonce', { credentials: 'same-origin', cache: 'no-store', signal: signal } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				if ( ! res || ! res.success || ! res.data || ! res.data.nonce ) {
					throw new Error( 'nonce' );
				}
				nonce = res.data.nonce;
			} );
	}

	function search( q, retried ) {
		var ctl = new win.AbortController();

		controller = ctl;
		post( q, ctl.signal )
			.then( function ( r ) {
				if ( 403 === r.status && ! retried ) {
					return refreshNonce( ctl.signal ).then( function () {
						controller = null;
						search( q, true );
						return null;
					} );
				}
				return r.json().then( function ( res ) {
					if ( ! res || ! res.success || ! res.data ) {
						throw new Error( 'response' );
					}
					return res.data;
				} );
			} )
			.then( function ( data ) {
				if ( ! data || controller !== ctl ) {
					return;
				}
				controller = null;
				cache.set( q.toLowerCase(), data );
				if ( input.value.trim() === q ) {
					render( q, data );
				}
			} )
			.catch( function ( err ) {
				if ( err && 'AbortError' === err.name ) {
					return; // người dùng gõ tiếp: không báo lỗi giả.
				}
				if ( controller === ctl ) {
					controller = null;
					emptyText.textContent = strings.error || '';
					setState( 'empty' );
					announce( strings.error || '' );
				}
			} );
	}

	// --- Hiển thị kết quả --------------------------------------------------------------
	// Bỏ dấu tiếng Việt + chữ thường, giữ bảng vị trí về chuỗi gốc để tô đúng đoạn khớp (máy chủ so khớp không phân biệt dấu).
	function fold( str ) {
		var out = '', map = [], i, ch, f, j;

		for ( i = 0; i < str.length; i++ ) {
			ch = str.charAt( i );
			f = ch.normalize ? ch.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ) : ch;
			f = f.replace( /[đĐ]/g, 'd' ).toLowerCase();
			for ( j = 0; j < f.length; j++ ) {
				out += f.charAt( j );
				map.push( i );
			}
		}
		return { text: out, map: map };
	}

	function titleNode( title, q ) {
		var span = doc.createElement( 'span' ), t = fold( title ), n = fold( q ).text, at, start, end, mark;

		span.className = 'isha-search__title';
		at = n ? t.text.indexOf( n ) : -1;
		if ( at < 0 ) {
			span.textContent = title;
			return span;
		}
		start = t.map[ at ];
		end = t.map[ at + n.length - 1 ] + 1;
		mark = doc.createElement( 'mark' );
		mark.textContent = title.slice( start, end );
		span.appendChild( doc.createTextNode( title.slice( 0, start ) ) );
		span.appendChild( mark );
		span.appendChild( doc.createTextNode( title.slice( end ) ) );
		return span;
	}

	function thumbNode( src ) {
		var box = doc.createElement( 'span' ), img = doc.createElement( 'img' );

		box.className = 'isha-search__thumb';
		img.alt = '';
		img.decoding = 'async';
		img.loading = 'lazy';
		img.width = 68;
		img.height = 44;
		function fallback() {
			img.onerror = null;
			box.classList.add( 'is-fallback' );
			img.src = cfg.fallback || '';
		}
		if ( src ) {
			img.onerror = fallback;
			img.src = src;
		} else {
			fallback();
		}
		box.appendChild( img );
		return box;
	}

	function render( q, data ) {
		var groups = ( data && data.groups ) || [], frag = doc.createDocumentFragment();

		items = [];
		active = -1;
		input.removeAttribute( 'aria-activedescendant' );
		list.textContent = '';

		if ( ! groups.length ) {
			emptyText.textContent = fmt( strings.empty, q );
			setState( 'empty' );
			announce( emptyText.textContent );
			return;
		}

		groups.forEach( function ( g ) {
			var group = doc.createElement( 'div' ), head = doc.createElement( 'p' ), icon = doc.createElement( 'span' ), label = doc.createElement( 'span' ), count = doc.createElement( 'span' ), headId = 'isha-search-g-' + ( ++uid );

			group.className = 'isha-search__group';
			group.setAttribute( 'role', 'group' );
			group.setAttribute( 'aria-labelledby', headId );
			head.className = 'isha-search__group-head';
			head.id = headId;
			icon.className = 'isha-search__group-icon';
			icon.setAttribute( 'aria-hidden', 'true' );
			icon.innerHTML = icons[ g.key ] || ''; // SVG tĩnh của theme (inc/live-search.php), không chứa dữ liệu người dùng.
			label.textContent = g.label;
			count.className = 'isha-search__count';
			count.textContent = String( g.count );
			count.setAttribute( 'aria-label', fmt( strings.count, g.count ) );
			head.appendChild( icon );
			head.appendChild( label );
			head.appendChild( count );
			group.appendChild( head );

			( g.items || [] ).forEach( function ( it ) {
				var a = doc.createElement( 'a' ), badge;

				if ( ! /^https?:\/\//i.test( String( it.url || '' ) ) ) {
					return;
				}
				a.className = 'isha-search__item';
				a.href = it.url;
				a.id = 'isha-search-o-' + ( ++uid );
				a.setAttribute( 'role', 'option' );
				a.setAttribute( 'aria-selected', 'false' );
				a.tabIndex = -1;
				a.appendChild( thumbNode( it.thumb ) );
				a.appendChild( titleNode( String( it.title || '' ), q ) );
				if ( it.badge ) {
					badge = doc.createElement( 'span' );
					badge.className = 'isha-search__badge';
					badge.textContent = it.badge;
					a.appendChild( badge );
				}
				items.push( a );
				group.appendChild( a );
			} );
			frag.appendChild( group );
		} );

		list.appendChild( frag );
		list.scrollTop = 0;
		setState( 'results' );
		setActive( 0, false );
		announce( fmt( strings.count, data.total || items.length ) );
	}

	// --- Bàn phím, chuột ----------------------------------------------------------------
	function setActive( index, scroll ) {
		if ( active > -1 && items[ active ] ) {
			items[ active ].classList.remove( 'is-active' );
			items[ active ].setAttribute( 'aria-selected', 'false' );
		}
		active = index;
		if ( index < 0 || ! items[ index ] ) {
			input.removeAttribute( 'aria-activedescendant' );
			return;
		}
		items[ index ].classList.add( 'is-active' );
		items[ index ].setAttribute( 'aria-selected', 'true' );
		input.setAttribute( 'aria-activedescendant', items[ index ].id );
		if ( scroll ) {
			items[ index ].scrollIntoView( { block: 'nearest' } );
		}
	}

	input.addEventListener( 'keydown', function ( e ) {
		if ( 'results' !== dlg.getAttribute( 'data-state' ) || ! items.length ) {
			if ( 'Enter' === e.key ) {
				e.preventDefault(); // không gửi form/không đi đâu khi chưa có kết quả.
			}
			return;
		}
		if ( 'ArrowDown' === e.key ) {
			e.preventDefault();
			setActive( ( active + 1 ) % items.length, true );
		} else if ( 'ArrowUp' === e.key ) {
			e.preventDefault();
			setActive( ( active - 1 + items.length ) % items.length, true );
		} else if ( 'Home' === e.key && e.ctrlKey ) {
			e.preventDefault();
			setActive( 0, true );
		} else if ( 'End' === e.key && e.ctrlKey ) {
			e.preventDefault();
			setActive( items.length - 1, true );
		} else if ( 'Enter' === e.key && active > -1 ) {
			e.preventDefault();
			if ( e.ctrlKey || e.metaKey ) {
				win.open( items[ active ].href, '_blank', 'noopener' );
			} else {
				win.location.assign( items[ active ].href );
			}
		}
	} );

	// Rê chuột chọn mục (mousemove, không dùng mouseover: danh sách cuộn bằng phím không làm đổi mục dưới con trỏ).
	list.addEventListener( 'mousemove', function ( e ) {
		var a = e.target.closest ? e.target.closest( '.isha-search__item' ) : null, i = a ? items.indexOf( a ) : -1;

		if ( i > -1 && i !== active ) {
			setActive( i, false );
		}
	} );
}( window, document ) );
