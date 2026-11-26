/**
 * Trang chi tiết Tin tức ISHA: post-article.js (docs/news.md, 25/11/2026). IIFE ES5, không thư viện. Chỉ là phần THÊM: không có JS thì bài vẫn đọc trọn vẹn.
 * Trang chi tiết Sự kiện (docs/events.md) dùng lại: hộp xem lớn (kể cả ảnh bìa [data-isha-cover]) và nút sao chép liên kết; không có mục lục, thanh tiến trình.
 * Việc: (1) thanh tiến trình đọc; (2) mục lục tự quét h2/h3 của bài (desktop: cột phải dính, điện thoại: accordion đầu thân bài), đánh dấu mục đang đọc bằng
 * IntersectionObserver; (3) xem lớn ảnh trong bài bằng dialog (Esc đóng, mũi tên đổi ảnh); (4) nút sao chép liên kết.
 * Nội dung bài soạn bằng trình soạn thảo mặc định HOẶC Elementor: script chỉ đọc DOM của vùng [data-isha-article-body], tự bỏ qua ảnh mà Elementor
 * đã gắn hộp xem lớn riêng (data-elementor-open-lightbox) và không chạy trong khung soạn/xem trước của Elementor.
 * Chuỗi hiển thị lấy từ window.ISHA.postArticle.strings (inc/post-single.php).
 */
( function () {
	'use strict';

	var win = window, doc = document, html = doc.documentElement;
	var cfg = ( win.ISHA && win.ISHA.postArticle ) || {};
	var str = cfg.strings || {};
	var RAIL = 40;            // vạch đọc nằm ở 40% chiều cao khung nhìn (thanh tiến trình)
	var MIN_ZOOM = 160;       // ảnh rộng dưới 160px (biểu tượng, emoji) không mở hộp xem lớn

	// NodeList thành mảng.
	function all( root, sel ) {
		return Array.prototype.slice.call( root.querySelectorAll( sel ) );
	}

	// Phần tử tổ tiên (hoặc chính nó) khớp bộ chọn.
	function closest( el, sel ) {
		if ( el && el.closest ) {
			return el.closest( sel );
		}
		while ( el && 1 === el.nodeType ) {
			if ( ( el.matches || el.msMatchesSelector ).call( el, sel ) ) {
				return el;
			}
			el = el.parentNode;
		}
		return null;
	}

	// Báo lỗi của một khối lên console, không làm hỏng các khối khác.
	function report( name, err ) {
		if ( win.console && win.console.error ) {
			win.console.error( '[ISHA.article] ' + name, err );
		}
	}

	// Giảm chuyển động đang bật?
	function reduced() {
		return !! ( win.matchMedia && win.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function clamp( v, lo, hi ) {
		return Math.min( hi, Math.max( lo, v ) );
	}

	// Chỗ bên dưới header dính (px): header cao thật cộng thanh admin; dùng cho thanh tiến trình và vạch kích hoạt mục lục.
	function headerBottom() {
		var h = doc.querySelector( '.site-header' ), r = h ? h.getBoundingClientRect() : null;
		return r ? Math.max( 0, Math.round( r.bottom ) ) : 0;
	}

	/* ---------------------------------------------------------------------------------------------------- Thanh tiến trình */
	// Sợi chỉ 2px sát mép dưới header: co giãn bằng transform theo vị trí đọc trong vùng nội dung bài (0 khi đầu bài chạm vạch 40% màn hình,
	// 1 khi cuối bài chạm vạch đó). scroll passive + requestAnimationFrame.
	function progress( root, body ) {
		var bar = root.querySelector( '[data-isha-progress]' ), fill = bar && bar.firstElementChild, queued = false, last = -1;

		if ( ! bar || ! fill || ! win.requestAnimationFrame ) {
			return;
		}

		function place() {
			bar.style.top = headerBottom() + 'px';
		}

		function tick() {
			var r = body.getBoundingClientRect(), vh = win.innerHeight || html.clientHeight;
			var p = r.height > 0 ? clamp( ( vh * RAIL / 100 - r.top ) / r.height, 0, 1 ) : 0;

			queued = false;
			if ( Math.abs( p - last ) > 0.0005 ) {
				last = p;
				fill.style.transform = 'scaleX(' + p.toFixed( 4 ) + ')';
			}
		}

		function request() {
			if ( ! queued ) {
				queued = true;
				win.requestAnimationFrame( tick );
			}
		}

		bar.hidden = false;
		place();
		win.addEventListener( 'scroll', request, { passive: true } );
		win.addEventListener( 'resize', function () {
			place();
			request();
		}, { passive: true } );
		win.addEventListener( 'load', function () {
			place();
			request();
		} );
		request();
	}

	/* ---------------------------------------------------------------------------------------------------- Mục lục */
	// Slug sạch từ chữ của tiêu đề: bỏ dấu tiếng Việt, đ thành d, chữ thường, gạch nối; trùng thì thêm hậu tố -2, -3...
	function slugify( text, used ) {
		var s = String( text || '' ).toLowerCase();
		var base, n = 1;

		if ( s.normalize ) {
			s = s.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' );
		}
		base = s.replace( /đ/g, 'd' ).replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' ).slice( 0, 60 ) || 'muc';
		s = base;
		while ( used[ s ] || doc.getElementById( s ) ) {
			n += 1;
			s = base + '-' + n;
		}
		used[ s ] = true;
		return s;
	}

	// Tiêu đề tính vào mục lục: h2 và h3 thấy được, không nằm trong khối ẩn/gập (tab, accordion Elementor, details), trích dẫn hay ghi chú.
	function collectHeadings( body ) {
		return all( body, 'h2, h3' ).filter( function ( h ) {
			return '' !== h.textContent.replace( /\s+/g, ' ' ).trim() && ! closest( h, 'blockquote, details, [data-isha-no-toc], .isha-doctor-note, .elementor-tabs, .elementor-accordion, .elementor-toggle' ) && h.getClientRects().length > 0;
		} );
	}

	function toc( root, body ) {
		var heads = collectHeadings( body ), lists, wrap, mobile, used = {}, links = [], items, active = -1, io;

		wrap = root.querySelector( '[data-isha-toc]' );
		mobile = root.querySelector( '[data-isha-toc-m]' );
		if ( heads.length < 2 || ( ! wrap && ! mobile ) ) {
			return;   // dưới 2 tiêu đề: giữ ẩn mục lục để cột không trống
		}
		heads = heads.slice( 0, 30 );
		heads.forEach( function ( h ) {
			if ( ! h.id ) {
				h.id = slugify( h.textContent, used );
			} else {
				used[ h.id ] = true;
			}
		} );

		lists = [ wrap, mobile ].filter( Boolean ).map( function ( box ) {
			return box.querySelector( '.isha-art__toc-list' );
		} ).filter( Boolean );

		lists.forEach( function ( ol ) {
			heads.forEach( function ( h, k ) {
				var li = doc.createElement( 'li' ), a = doc.createElement( 'a' );

				a.href = '#' + h.id;
				a.textContent = h.textContent.replace( /\s+/g, ' ' ).trim();
				a.setAttribute( 'data-isha-toc-link', String( k ) );
				if ( 'H3' === h.tagName ) {
					li.className = 'is-sub';
				}
				li.appendChild( a );
				ol.appendChild( li );
			} );
		} );
		if ( wrap ) {
			wrap.hidden = false;
		}
		if ( mobile ) {
			mobile.hidden = false;
		}

		items = all( root, '[data-isha-toc-link]' );
		items.forEach( function ( a ) {
			a.addEventListener( 'click', function ( e ) {
				var h = heads[ parseInt( a.getAttribute( 'data-isha-toc-link' ), 10 ) ], det = closest( a, 'details' );

				if ( ! h || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || 0 !== e.button ) {
					return;
				}
				e.preventDefault();
				if ( det ) {
					det.open = false;
				}
				h.scrollIntoView( { behavior: reduced() ? 'auto' : 'smooth', block: 'start' } );
				if ( win.history && win.history.replaceState ) {
					win.history.replaceState( null, '', '#' + h.id );
				}
				h.setAttribute( 'tabindex', '-1' );
				h.focus( { preventScroll: true } );
			} );
		} );

		// Mục đang đọc: tiêu đề cuối cùng đã chạm vạch kích hoạt (dưới header khoảng 40px); IntersectionObserver báo khi tiêu đề qua dải 40px–55% màn hình.
		function mark( k ) {
			if ( k === active ) {
				return;
			}
			active = k;
			items.forEach( function ( a ) {
				var on = parseInt( a.getAttribute( 'data-isha-toc-link' ), 10 ) === k;

				if ( on ) {
					a.setAttribute( 'aria-current', 'true' );
				} else {
					a.removeAttribute( 'aria-current' );
				}
			} );
			// Mục lục desktop dài hơn cột: cuộn riêng trong khung của nó để mục đang đọc luôn thấy được (không động tới cuộn của trang).
			if ( wrap && wrap.scrollHeight > wrap.clientHeight && k >= 0 ) {
				items.some( function ( a ) {
					var top, bot;

					if ( parseInt( a.getAttribute( 'data-isha-toc-link' ), 10 ) !== k || ! wrap.contains( a ) ) {
						return false;
					}
					top = a.offsetTop - wrap.offsetTop;
					bot = top + a.offsetHeight;
					if ( top < wrap.scrollTop ) {
						wrap.scrollTop = top - 8;
					} else if ( bot > wrap.scrollTop + wrap.clientHeight ) {
						wrap.scrollTop = bot - wrap.clientHeight + 8;
					}
					return true;
				} );
			}
		}

		function update() {
			var line = headerBottom() + 40, k = -1, i;

			for ( i = 0; i < heads.length; i += 1 ) {
				if ( heads[ i ].getBoundingClientRect().top <= line ) {
					k = i;
				} else {
					break;
				}
			}
			mark( k );
		}

		if ( win.IntersectionObserver ) {
			io = new win.IntersectionObserver( update, {
				rootMargin: '-' + ( headerBottom() + 40 ) + 'px 0px -45% 0px',
				threshold: [ 0, 1 ]
			} );
			heads.forEach( function ( h ) {
				io.observe( h );
			} );
		} else {
			win.addEventListener( 'scroll', update, { passive: true } );
		}
		update();
	}

	/* ---------------------------------------------------------------------------------------------------- Hộp xem lớn ảnh */
	var IMAGE_HREF = /\.(?:jpe?g|png|gif|webp|avif)(?:[?#].*)?$/i;

	// Địa chỉ ảnh lớn nhất có sẵn của một <img> (srcset lớn nhất; không có thì src).
	function bigSrc( img ) {
		var best = '', bw = 0;

		( img.getAttribute( 'srcset' ) || '' ).split( ',' ).forEach( function ( part ) {
			var bits = part.trim().split( /\s+/ ), w = parseInt( bits[ 1 ], 10 );

			if ( bits[ 0 ] && w > bw ) {
				bw = w;
				best = bits[ 0 ];
			}
		} );
		return best || img.currentSrc || img.src;
	}

	function viewer( root, body ) {
		var imgs, entries = [], dlg, view, cap, num, nav, cur = 0, from = null;

		if ( 'function' !== typeof doc.createElement( 'dialog' ).showModal ) {
			return;
		}

		// Ảnh bìa của Sự kiện ([data-isha-cover], liên kết tới ảnh gốc) đứng đầu danh sách, trước ảnh trong bài.
		imgs = all( root, '[data-isha-cover] img' ).concat( all( body, 'img' ) ).filter( function ( img ) {
			var a = closest( img, 'a[href]' );

			if ( closest( img, '[data-isha-no-zoom], .emoji, .elementor-widget-image-carousel, .swiper' ) || img.classList.contains( 'emoji' ) || img.classList.contains( 'wp-smiley' ) ) {
				return false;
			}
			// Ảnh nằm trong liên kết: chỉ nhận khi liên kết trỏ thẳng tới tệp ảnh và Elementor không tự mở hộp xem lớn của nó.
			if ( a ) {
				return ! a.hasAttribute( 'data-elementor-open-lightbox' ) && ! a.hasAttribute( 'data-elementor-lightbox-slideshow' ) && IMAGE_HREF.test( a.getAttribute( 'href' ) );
			}
			return true;
		} );
		if ( ! imgs.length ) {
			return;
		}

		dlg = doc.createElement( 'dialog' );
		dlg.className = 'isha-art__viewer';
		dlg.setAttribute( 'data-tone', 'night' );
		dlg.setAttribute( 'aria-label', str.viewer || '' );
		dlg.innerHTML =
			'<div class="isha-art__viewer-bar"><p class="isha-art__viewer-count" aria-live="polite"></p>' +
			'<button type="button" class="isha-art__viewer-close" data-isha-close><span aria-hidden="true">&times;</span></button></div>' +
			'<figure class="isha-art__viewer-fig" data-isha-stage><img class="isha-art__viewer-img" alt="" decoding="async">' +
			'<figcaption class="isha-art__viewer-cap" hidden></figcaption></figure>' +
			'<div class="isha-art__viewer-nav"><button type="button" class="isha-btn isha-btn--ghost" data-isha-prev></button>' +
			'<button type="button" class="isha-btn isha-btn--ghost" data-isha-next></button></div>';
		root.appendChild( dlg );
		dlg.querySelector( '[data-isha-close]' ).setAttribute( 'aria-label', str.close || '' );
		dlg.querySelector( '[data-isha-prev]' ).textContent = str.prev || '';
		dlg.querySelector( '[data-isha-next]' ).textContent = str.next || '';
		view = dlg.querySelector( '.isha-art__viewer-img' );
		cap = dlg.querySelector( '.isha-art__viewer-cap' );
		num = dlg.querySelector( '.isha-art__viewer-count' );
		nav = dlg.querySelector( '.isha-art__viewer-nav' );

		imgs.forEach( function ( img, k ) {
			var a = closest( img, 'a[href]' ), trigger = a || img, alt = img.getAttribute( 'alt' ) || '', fig = closest( img, 'figure' ), fc = fig && fig.querySelector( 'figcaption' );
			var label = ( a && a.getAttribute( 'data-isha-label' ) ) || ( alt ? ( ( str.zoom || '' ) + ': ' + alt ) : ( ( str.zoom || '' ) + ' ' + ( k + 1 ) + ' / ' + imgs.length ) );

			entries.push( {
				src: a ? a.getAttribute( 'href' ) : bigSrc( img ),
				alt: alt,
				cap: fc ? fc.textContent.replace( /\s+/g, ' ' ).trim() : '',
				img: img,
				trigger: trigger
			} );
			trigger.setAttribute( 'data-isha-zoom', '' );
			trigger.setAttribute( 'aria-haspopup', 'dialog' );
			if ( ! a ) {
				trigger.setAttribute( 'tabindex', '0' );
				trigger.setAttribute( 'role', 'button' );
			}
			trigger.setAttribute( 'aria-label', label );
			trigger.addEventListener( 'click', function ( e ) {
				if ( e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || 0 !== e.button ) {
					return;
				}
				open( k, e, trigger );
			} );
			if ( ! a ) {
				trigger.addEventListener( 'keydown', function ( e ) {
					if ( 'Enter' === e.key || ' ' === e.key ) {
						open( k, e, trigger );
					}
				} );
			}
		} );
		nav.hidden = entries.length < 2;

		// Hiện ảnh thứ k (vòng tròn).
		function show( k ) {
			var item;

			cur = ( k + entries.length ) % entries.length;
			item = entries[ cur ];
			view.alt = item.alt;
			view.src = item.src;
			cap.textContent = item.cap;
			cap.hidden = ! item.cap;
			num.textContent = entries.length > 1 ? ( cur + 1 ) + ' / ' + entries.length : '';
		}

		function open( k, e, trigger ) {
			var item = entries[ k ];

			// Ảnh quá nhỏ (biểu tượng, nhãn) hoặc chưa tải xong: để nguyên, không mở hộp.
			if ( item.img.naturalWidth && item.img.naturalWidth < MIN_ZOOM ) {
				return;
			}
			e.preventDefault();
			from = trigger;
			show( k );
			dlg.showModal();
			html.classList.add( 'isha-art-lock' );
		}

		dlg.addEventListener( 'close', function () {
			html.classList.remove( 'isha-art-lock' );
			if ( from && from.focus ) {
				from.focus();
			}
		} );
		dlg.addEventListener( 'click', function ( e ) {
			var t = e.target;

			if ( t === dlg || t.hasAttribute( 'data-isha-stage' ) || closest( t, '[data-isha-close]' ) ) {
				dlg.close();
			} else if ( t.hasAttribute( 'data-isha-prev' ) ) {
				show( cur - 1 );
			} else if ( t.hasAttribute( 'data-isha-next' ) ) {
				show( cur + 1 );
			}
		} );
		dlg.addEventListener( 'keydown', function ( e ) {
			if ( entries.length > 1 && ! ( e.altKey || e.ctrlKey || e.metaKey || e.shiftKey ) && ( 'ArrowLeft' === e.key || 'ArrowRight' === e.key ) ) {
				e.preventDefault();
				show( cur + ( 'ArrowRight' === e.key ? 1 : -1 ) );
			}
		} );
	}

	/* ---------------------------------------------------------------------------------------------------- Sao chép liên kết */
	function copyText( text ) {
		var ta, ok = false;

		if ( win.navigator.clipboard && win.isSecureContext ) {
			return win.navigator.clipboard.writeText( text );
		}
		ta = doc.createElement( 'textarea' );
		ta.value = text;
		ta.setAttribute( 'readonly', '' );
		ta.style.cssText = 'position:fixed;top:0;left:0;opacity:0;';
		doc.body.appendChild( ta );
		ta.select();
		try {
			ok = doc.execCommand( 'copy' );
		} catch ( err ) {
			ok = false;
		}
		doc.body.removeChild( ta );
		return ok ? win.Promise.resolve() : win.Promise.reject( new Error( 'copy' ) );
	}

	function copyButton( root ) {
		var btn = root.querySelector( '[data-isha-copy]' ), label, status, timer = 0, idle;

		if ( ! btn || ! win.Promise ) {
			return;
		}
		label = btn.querySelector( '[data-isha-copy-text]' );
		status = btn.querySelector( '[data-isha-copy-status]' );
		idle = label ? label.textContent : '';
		btn.hidden = false;
		btn.addEventListener( 'click', function () {
			copyText( win.location.href ).then( function () {
				if ( label ) {
					label.textContent = str.copied || idle;
				}
				if ( status ) {
					status.textContent = str.copied || '';
				}
				win.clearTimeout( timer );
				timer = win.setTimeout( function () {
					if ( label ) {
						label.textContent = idle;
					}
					if ( status ) {
						status.textContent = '';
					}
				}, 2500 );
			}, function () {
				/* Trình duyệt chặn sao chép: giữ nguyên nhãn, người đọc vẫn sao chép được từ thanh địa chỉ. */
			} );
		} );
	}

	/* ---------------------------------------------------------------------------------------------------- Khởi chạy */
	function boot() {
		var root = doc.querySelector( '.isha-art' ), body;

		if ( ! root ) {
			return;
		}
		// Khung soạn và xem trước của Elementor: không chạm vào DOM (sẽ cản chọn widget và bị Elementor dựng lại).
		if ( doc.body.classList.contains( 'elementor-editor-active' ) || /[?&]elementor-preview=/.test( win.location.search ) ) {
			return;
		}
		body = root.querySelector( '[data-isha-article-body]' );
		if ( ! body ) {
			return;
		}
		try {
			toc( root, body );
		} catch ( err ) {
			report( 'toc', err );
		}
		try {
			progress( root, body );
		} catch ( err2 ) {
			report( 'progress', err2 );
		}
		try {
			viewer( root, body );
		} catch ( err3 ) {
			report( 'viewer', err3 );
		}
		try {
			copyButton( root );
		} catch ( err4 ) {
			report( 'copy', err4 );
		}
	}

	if ( 'loading' === doc.readyState ) {
		doc.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
