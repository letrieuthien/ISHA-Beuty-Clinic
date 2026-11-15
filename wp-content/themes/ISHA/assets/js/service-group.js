/**
 * Trang nhóm dịch vụ ISHA: service-group.js (template-parts/service-group/results.php). IIFE ES5, không phụ thuộc thư viện.
 *
 * 1. Khung kéo so sánh trước/sau (.isha-sg-ba): đặt --ba (vị trí lằn chia, %) và --ba-n (0..1). Chuột: bấm hoặc kéo ở bất kỳ đâu trên khung.
 *    Chạm: vuốt ngang kéo lằn chia, vuốt dọc vẫn cuộn trang (touch-action: pan-y), chạm nhẹ đưa lằn chia tới điểm chạm; nút kéo
 *    (touch-action: none) kéo ngay. Bàn phím trên nút kéo (role="slider"): mũi tên (2%, giữ Shift 10%), PageUp/PageDown (10%), Home/End.
 *    Hiệu ứng (tắt hết khi giảm chuyển động): lằn chia lướt theo bằng LERP 0.08 (quán tính); gợi ý kéo 50 → 35 → 65 → 50 trong 1,8 giây
 *    khi khung vào khung nhìn; ảnh Sau thu phóng 1.04 → 1.0 theo --ba-n (CSS); nút kéo hút nhẹ về con trỏ chuột trước khi nhấn giữ;
 *    nhãn Trước/Sau mờ đi khi lằn chia đè qua (.is-dim-before / .is-dim-after).
 * 2. Dải thẻ (.isha-sg-strip): băng chuyền vòng lặp. Số thẻ mỗi khung đọc từ --sg-per (CSS: 4 / 3 / 1). Có nhiều thẻ hơn số đó thì thêm
 *    một bộ bản sao ở mỗi đầu (aria-hidden, tabindex -1), track trượt bằng transform; hết hiệu ứng thì nhảy về vị trí tương đương trong bộ thật
 *    (không thấy được vì bản sao giống hệt). Kéo chuột/vuốt để lướt, bấm nút Trước/Sau hoặc vạch của pager (của trang chủ, ẩn ở chế độ gọn).
 *    Bấm thẻ: khung so sánh đổi sang cặp đó (lằn chia về giữa), thẻ được đánh dấu, trình đọc màn hình được báo "Đang xem cặp ảnh n trên N".
 */
( function () {
	'use strict';

	var win = window, doc = document;

	// NodeList thành mảng.
	function all( root, sel ) {
		return Array.prototype.slice.call( root.querySelectorAll( sel ) );
	}

	// Báo lỗi lên console, không làm hỏng phần còn lại.
	function report( name, err ) {
		if ( win.console && win.console.error ) {
			win.console.error( '[ISHA.serviceGroup] ' + name, err );
		}
	}

	// Giảm chuyển động đang bật?
	function reduced() {
		return !! ( win.matchMedia && win.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function clamp( v, a, b ) {
		return Math.min( b, Math.max( a, v ) );
	}

	// Phần dư luôn dương (vị trí theo vòng).
	function mod( v, n ) {
		return ( ( v % n ) + n ) % n;
	}

	// Điền %1$d, %2$d và %% của chuỗi đã dịch.
	function fmt( tpl, a, b ) {
		return String( tpl || '' ).replace( '%1$d', a ).replace( '%2$d', b ).replace( /%%/g, '%' );
	}

	// Bắt con trỏ để kéo tiếp khi ra ngoài phần tử; trình duyệt cũ không có thì bỏ qua.
	function capture( el, id ) {
		try {
			el.setPointerCapture( id );
		} catch ( err ) {}
	}

	/* --- 1. Khung kéo so sánh ---------------------------------------------- */

	// Hàm easing cubic-bezier (giải t theo x bằng Newton), dùng cho nhịp gợi ý kéo.
	function bezier( x1, y1, x2, y2 ) {
		function a( p1, p2 ) {
			return 1 - 3 * p2 + 3 * p1;
		}
		function b( p1, p2 ) {
			return 3 * p2 - 6 * p1;
		}
		function at( t, p1, p2 ) {
			return ( ( a( p1, p2 ) * t + b( p1, p2 ) ) * t + 3 * p1 ) * t;
		}
		function slope( t, p1, p2 ) {
			return 3 * a( p1, p2 ) * t * t + 2 * b( p1, p2 ) * t + 3 * p1;
		}
		return function ( x ) {
			var t = x, i, s;

			if ( x <= 0 || x >= 1 ) {
				return x <= 0 ? 0 : 1;
			}
			for ( i = 0; i < 8; i++ ) {
				s = slope( t, x1, x2 );
				if ( Math.abs( s ) < 1e-6 ) {
					break;
				}
				t -= ( at( t, x1, x2 ) - x ) / s;
			}
			return at( t, y1, y2 );
		};
	}

	var EASE_CINE = bezier( 0.22, 1, 0.36, 1 ); // = --isha-ease-cine
	var SMOOTH = 0.08;                          // hệ số LERP mỗi khung hình (60 fps): lằn chia lướt theo con trỏ với độ trễ vật lý nhẹ
	var MAGNET = 0.14, MAGNET_MAX = 14;         // nút kéo hút về con trỏ: 14% khoảng cách, tối đa 14px
	var HINT = [ 50, 35, 65, 50 ], HINT_MS = 1800;

	function compare( root ) {
		var handle = root.querySelector( '.isha-sg-ba__handle' ), pairs = all( root, '.isha-sg-ba__pair' );
		var tpl = handle ? handle.getAttribute( 'data-valuetext' ) : '';
		var target = 50, value = 50, drag = null, raf = 0, last = 0, hint = 0, hinted = false;
		var mx = 0, my = 0, tmx = 0, tmy = 0, dimB = 0, dimA = 100;

		if ( ! handle ) {
			return null;
		}

		// Vẽ trạng thái hiện tại: --ba (vị trí lằn chia), --ba-n (0..1, cho thu phóng ảnh Sau), độ lệch hút của nút, nhãn bị lằn chia đè thì mờ.
		function paint() {
			root.style.setProperty( '--ba', value.toFixed( 2 ) + '%' );
			root.style.setProperty( '--ba-n', ( value / 100 ).toFixed( 4 ) );
			handle.style.setProperty( '--mx', mx.toFixed( 2 ) + 'px' );
			handle.style.setProperty( '--my', my.toFixed( 2 ) + 'px' );
			root.classList.toggle( 'is-dim-before', value < dimB );
			root.classList.toggle( 'is-dim-after', value > dimA );
		}

		function aria( v ) {
			var r = Math.round( v );

			handle.setAttribute( 'aria-valuenow', String( r ) );
			handle.setAttribute( 'aria-valuetext', fmt( tpl, r, 100 - r ) );
		}

		// Vòng nội suy: value tiến dần tới target (LERP, không phụ thuộc tốc độ khung hình), nút kéo tiến tới độ lệch hút. Đứng yên thì dừng.
		function frame( now ) {
			var dt = last ? Math.min( 64, now - last ) : 16.67, k = reduced() ? 1 : 1 - Math.pow( 1 - SMOOTH, dt / 16.67 ), km = Math.min( 1, k * 2 ), done = true;

			last = now;
			value += ( target - value ) * k;
			mx += ( tmx - mx ) * km;
			my += ( tmy - my ) * km;
			if ( Math.abs( target - value ) < 0.02 ) {
				value = target;
			} else {
				done = false;
			}
			if ( Math.abs( tmx - mx ) < 0.05 && Math.abs( tmy - my ) < 0.05 ) {
				mx = tmx;
				my = tmy;
			} else {
				done = false;
			}
			paint();
			raf = done ? 0 : win.requestAnimationFrame( frame );
			if ( done ) {
				last = 0;
			}
		}

		function kick() {
			if ( ! raf ) {
				last = 0;
				raf = win.requestAnimationFrame( frame );
			}
		}

		// Đặt đích của lằn chia (lướt tới bằng LERP); instant: nhảy ngay (nhịp gợi ý tự vẽ từng khung).
		function set( v, instant ) {
			target = clamp( v, 0, 100 );
			aria( target );
			if ( instant ) {
				value = target;
				paint();
			} else {
				kick();
			}
		}

		// Ngưỡng làm mờ nhãn: lằn chia đi vào vùng nhãn "Trước" (mép phải của nhãn) hoặc nhãn "Sau" (mép trái của nhãn) của cặp đang hiện.
		function measureTags() {
			var rect = root.getBoundingClientRect(), pair = root.querySelector( '.isha-sg-ba__pair:not([hidden])' ), tb, ta, r;

			if ( ! pair || ! rect.width ) {
				return;
			}
			tb = pair.querySelector( '.isha-sg-ba__layer--before .isha-sg-ba__tag' );
			ta = pair.querySelector( '.isha-sg-ba__layer--after .isha-sg-ba__tag' );
			if ( tb ) {
				r = tb.getBoundingClientRect();
				dimB = ( r.right - rect.left ) / rect.width * 100 + 1;
			}
			if ( ta ) {
				r = ta.getBoundingClientRect();
				dimA = ( r.left - rect.left ) / rect.width * 100 - 1;
			}
			paint();
		}

		function fromX( x ) {
			var rect = root.getBoundingClientRect();

			return rect.width ? ( x - rect.left ) / rect.width * 100 : 50;
		}

		function stopHint() {
			hinted = true;
			if ( hint ) {
				win.cancelAnimationFrame( hint );
				hint = 0;
			}
		}

		function end() {
			drag = null;
			root.classList.remove( 'is-dragging' );
		}

		root.classList.add( 'is-live' );
		handle.hidden = false;
		all( root, 'img' ).forEach( function ( img ) {
			img.setAttribute( 'draggable', 'false' );
		} );
		paint();
		measureTags();

		// Vệt sáng chạy quanh viền vàng và nhịp thở của nút (CSS .is-inview) chỉ chạy khi khung đang trong khung nhìn.
		if ( 'IntersectionObserver' in win ) {
			new win.IntersectionObserver( function ( entries ) {
				root.classList.toggle( 'is-inview', entries[ 0 ].isIntersecting );
			} ).observe( root );
		} else {
			root.classList.add( 'is-inview' );
		}

		if ( 'ResizeObserver' in win ) {
			new win.ResizeObserver( function () {
				measureTags();
			} ).observe( root );
		}

		root.addEventListener( 'pointerdown', function ( e ) {
			var onHandle = e.target === handle || handle.contains( e.target );

			if ( 'mouse' === e.pointerType && 0 !== e.button ) {
				return;
			}
			stopHint();
			tmx = 0;
			tmy = 0;
			drag = { id: e.pointerId, x: e.clientX, y: e.clientY, moved: 'mouse' === e.pointerType || onHandle };
			if ( drag.moved ) {
				capture( root, e.pointerId );
				root.classList.add( 'is-dragging' );
				if ( 'mouse' === e.pointerType ) {
					e.preventDefault();   // không bôi đen chữ; focus đưa về nút kéo để dùng phím tiếp
					// Focus do chuột: đánh dấu .is-pointer-focus để CSS không vẽ vòng focus bàn phím (trình duyệt coi focus() bằng script là
					// focus-visible); focusVisible: false cho trình duyệt hỗ trợ. Nhấn phím trên nút thì bỏ dấu để vòng focus hiện lại.
					root.classList.add( 'is-pointer-focus' );
					handle.focus( { preventScroll: true, focusVisible: false } );
					if ( ! onHandle ) {
						set( fromX( e.clientX ) );
					}
				}
			}
		} );

		root.addEventListener( 'pointermove', function ( e ) {
			var dx, dy, rect, cx, cy;

			// Chưa nhấn giữ, chuột thật: nút kéo hút nhẹ về phía con trỏ (magnetic).
			if ( ! drag ) {
				if ( 'mouse' === e.pointerType && ! reduced() ) {
					rect = root.getBoundingClientRect();
					cx = rect.left + rect.width * value / 100;
					cy = rect.top + rect.height / 2;
					tmx = clamp( ( e.clientX - cx ) * MAGNET, -MAGNET_MAX, MAGNET_MAX );
					tmy = clamp( ( e.clientY - cy ) * MAGNET, -MAGNET_MAX, MAGNET_MAX );
					kick();
				}
				return;
			}
			if ( e.pointerId !== drag.id ) {
				return;
			}
			if ( ! drag.moved ) {
				dx = Math.abs( e.clientX - drag.x );
				dy = Math.abs( e.clientY - drag.y );
				if ( dx < 6 && dy < 6 ) {
					return;
				}
				if ( dy > dx ) {      // vuốt dọc: để trình duyệt cuộn trang
					drag = null;
					return;
				}
				drag.moved = true;
				capture( root, e.pointerId );
				root.classList.add( 'is-dragging' );
			}
			set( fromX( e.clientX ) );
		} );

		root.addEventListener( 'pointerleave', function () {
			if ( ! drag ) {
				tmx = 0;
				tmy = 0;
				kick();
			}
		} );

		root.addEventListener( 'pointerup', function ( e ) {
			if ( drag && e.pointerId === drag.id && ! drag.moved ) {
				set( fromX( e.clientX ) );   // chạm nhẹ: lằn chia lướt tới điểm chạm
			}
			end();
		} );
		root.addEventListener( 'pointercancel', end );
		root.addEventListener( 'lostpointercapture', function () {
			if ( drag && drag.moved ) {
				end();
			}
		} );
		root.addEventListener( 'dragstart', function ( e ) {
			e.preventDefault();
		} );

		// Rời nút (Tab đi, bấm chỗ khác): bỏ dấu focus do chuột, lần focus kế bằng bàn phím sẽ có vòng focus.
		handle.addEventListener( 'blur', function () {
			root.classList.remove( 'is-pointer-focus' );
		} );

		handle.addEventListener( 'keydown', function ( e ) {
			root.classList.remove( 'is-pointer-focus' );
			var step = e.shiftKey ? 10 : 2, v = null;

			switch ( e.key ) {
				case 'ArrowLeft':
				case 'ArrowDown':
				case 'Left':
				case 'Down':
					v = target - step;
					break;
				case 'ArrowRight':
				case 'ArrowUp':
				case 'Right':
				case 'Up':
					v = target + step;
					break;
				case 'PageDown':
					v = target - 10;
					break;
				case 'PageUp':
					v = target + 10;
					break;
				case 'Home':
					v = 0;
					break;
				case 'End':
					v = 100;
					break;
			}
			if ( null !== v ) {
				e.preventDefault();
				stopHint();
				set( v );
			}
		} );

		// Gợi ý kéo (Ambient Hint Sweep): lần đầu hơn nửa khung vào khung nhìn, lằn chia lướt 50 → 35 → 65 → 50 trong 1,8 giây, mỗi nhịp theo
		// cubic-bezier(0.22, 1, 0.36, 1). Bỏ khi giảm chuyển động hoặc người dùng đã chạm vào khung.
		if ( ! reduced() && 'IntersectionObserver' in win ) {
			( function () {
				var io = new win.IntersectionObserver( function ( entries ) {
					var t0 = 0, seg = HINT_MS / ( HINT.length - 1 );

					if ( ! entries[ 0 ].isIntersecting ) {
						return;
					}
					io.disconnect();
					if ( hinted || drag || 50 !== target ) {
						return;
					}
					hint = win.requestAnimationFrame( function tick( now ) {
						var t, i, p;

						t0 = t0 || now;
						t = Math.min( HINT_MS, now - t0 );
						i = Math.min( HINT.length - 2, Math.floor( t / seg ) );
						p = EASE_CINE( ( t - i * seg ) / seg );
						set( HINT[ i ] + ( HINT[ i + 1 ] - HINT[ i ] ) * p, true );
						if ( t < HINT_MS ) {
							hint = win.requestAnimationFrame( tick );
						} else {
							hint = 0;
							set( 50, true );
						}
					} );
				}, { threshold: 0.5 } );

				io.observe( root );
			}() );
		}

		// Nạp trước ảnh của cặp k (ảnh lazy trong figure hidden chưa tải): đổi loading sang eager.
		function warm( k ) {
			var p = pairs[ k ];

			if ( p ) {
				all( p, 'img[loading="lazy"]' ).forEach( function ( img ) {
					img.setAttribute( 'loading', 'eager' );
				} );
			}
		}

		// Hiện cặp k, ẩn các cặp khác, lằn chia lướt về giữa.
		function show( k ) {
			pairs.forEach( function ( p, i ) {
				if ( i === k ) {
					if ( p.hidden ) {
						warm( i );
						p.hidden = false;
						p.classList.remove( 'is-in' );
						void p.offsetWidth;
						p.classList.add( 'is-in' );
					}
				} else {
					p.hidden = true;
				}
			} );
			stopHint();
			measureTags();
			set( 50 );
		}

		return { el: root, show: show, warm: warm };
	}

	// Đường kẻ ánh kim giữa hai số của bộ đếm (01 — 03) co giãn nhẹ mỗi lần đổi ca.
	function pulseDash( counter ) {
		var dash = counter && counter.parentNode ? counter.parentNode.querySelector( '.isha-sg-dash' ) : null;

		if ( dash ) {
			dash.classList.remove( 'is-pulse' );
			void dash.offsetWidth;
			dash.classList.add( 'is-pulse' );
		}
	}

	/* --- 2. Dải thẻ: băng chuyền vòng lặp ---------------------------------- */

	function carousel( root, ba, status ) {
		var viewport = root.querySelector( '.isha-sg-strip__viewport' ), track = root.querySelector( '.isha-sg-strip__track' );
		var items = track ? all( track, '.isha-sg-strip__item' ) : [], n = items.length;
		var pager = root.querySelector( '[data-isha-pager]' );
		var prev = pager && pager.querySelector( '.isha-pager__btn--prev' ), next = pager && pager.querySelector( '.isha-pager__btn--next' );
		var dots = pager ? all( pager, '.isha-pager__dots button' ) : [];
		var per = 1, step = 0, pos = 0, loop = false, cloned = false, drag = null, swallow = false, settle = 0, vertical = false;
		// cols: số cột của lưới; R: chu kỳ lặp tính bằng hàng; vis: số hàng thấy được. pos, R, vis đều tính bằng HÀNG (1 cột thì hàng = thẻ).
		var cols = 1, R = n, vis = 1;
		var counter = root.querySelector( '[data-isha-sg-current]' );

		if ( ! viewport || ! track || n < 2 ) {
			return;
		}

		// Bản sao của một thẻ: trình đọc màn hình bỏ qua, Tab bỏ qua, không có id trùng. KHÔNG dùng inert: bản sao nằm trong khung nhìn ở
		// chỗ nối vòng nên vẫn phải bấm được bằng chuột/chạm (bấm bản sao = bấm thẻ thật cùng data-index).
		function copy( item ) {
			var c = item.cloneNode( true );

			c.classList.add( 'is-clone' );
			c.setAttribute( 'aria-hidden', 'true' );
			all( c, '[id]' ).forEach( function ( el ) {
				el.removeAttribute( 'id' );
			} );
			all( c, 'button' ).forEach( function ( b ) {
				b.setAttribute( 'tabindex', '-1' );
				b.removeAttribute( 'aria-current' );
				b.removeAttribute( 'aria-controls' );
			} );
			return c;
		}

		// Hai bộ bản sao trước bộ thật và hai bộ sau: thẻ thật k nằm ở vị trí 2n + k của track (2n chẵn nên thẻ thật đầu tiên luôn ở cột đầu).
		function ensureClones() {
			var head = doc.createDocumentFragment(), tail = doc.createDocumentFragment(), r;

			if ( cloned ) {
				return;
			}
			for ( r = 0; r < 2; r++ ) {
				items.forEach( function ( it ) {
					head.appendChild( copy( it ) );
					tail.appendChild( copy( it ) );
				} );
			}
			track.insertBefore( head, track.firstChild );
			track.appendChild( tail );
			cloned = true;
		}

		// Trục trượt: --sg-axis: y (panel dọc ở desktop hai cột) hoặc ngang (mặc định). Bước trượt = cỡ một thẻ + khe theo trục đó.
		function measure() {
			var css = win.getComputedStyle( root ), ts = win.getComputedStyle( track ), rect, gap;

			vertical = 'y' === css.getPropertyValue( '--sg-axis' ).trim();
			gap = parseFloat( vertical ? ts.rowGap : ts.columnGap ) || 0;
			per = Math.max( 1, parseInt( css.getPropertyValue( '--sg-per' ), 10 ) || 1 );
			cols = vertical ? Math.max( 1, Math.min( 2, parseInt( css.getPropertyValue( '--sg-cols' ), 10 ) || 1 ) ) : 1;
			R = ( 0 === n % cols ? n : n * cols ) / cols;
			vis = Math.ceil( per / cols );
			loop = n > per;
			if ( loop ) {
				ensureClones();
			}
			root.classList.toggle( 'is-static', ! loop );
			if ( pager ) {
				pager.hidden = ! loop;
			}
			rect = items[ 0 ].getBoundingClientRect();
			step = ( vertical ? rect.height : rect.width ) + gap;
		}

		// Vẽ track ở vị trí pos (chỉ số thẻ thật ở mép trái/trên, có thể âm hoặc vượt n khi đang trượt qua bản sao).
		function render( animate ) {
			var d = ( -( 2 * n / cols + pos ) * step ).toFixed( 2 ) + 'px';

			root.classList.toggle( 'is-instant', ! animate || reduced() );
			track.style.transform = loop ? ( vertical ? 'translate3d(0,' + d + ',0)' : 'translate3d(' + d + ',0,0)' ) : '';
		}

		// Vị trí xa nhất còn bản sao để vẽ (hàng).
		function maxPos() {
			return Math.ceil( 5 * n / cols ) - 2 * n / cols - vis;
		}

		// Đưa pos về một chu kỳ (0..R-1 hàng) mà không có hiệu ứng: hình không đổi vì bản sao giống hệt.
		function normalize() {
			win.clearTimeout( settle );
			if ( loop && ( pos < 0 || pos >= R ) ) {
				pos = mod( pos, R );
				render( false );
				void track.offsetWidth;
			}
		}

		function syncDots() {
			var cur = mod( Math.round( pos ), R );

			dots.forEach( function ( d, i ) {
				if ( i === cur ) {
					d.setAttribute( 'aria-current', 'true' );
				} else {
					d.removeAttribute( 'aria-current' );
				}
			} );
		}

		// Trượt tới vị trí to (có hiệu ứng), rồi chuẩn hoá khi xong.
		function go( to ) {
			if ( ! loop ) {
				return;
			}
			pos = clamp( to, -R, maxPos() );
			render( true );
			syncDots();
			win.clearTimeout( settle );
			settle = win.setTimeout( normalize, reduced() ? 0 : 620 );
		}

		function shift( dir ) {
			normalize();
			go( Math.round( pos ) + dir );
		}

		// Đánh dấu thẻ k (cả bản sao) và đổi khung so sánh.
		function select( k, announce ) {
			all( track, '.isha-sg-card' ).forEach( function ( b ) {
				var on = +b.getAttribute( 'data-index' ) === k;

				b.classList.toggle( 'is-active', on );
				if ( ! b.closest( '.is-clone' ) ) {
					b.setAttribute( 'aria-current', on ? 'true' : 'false' );
				}
			} );
			if ( counter ) {
				counter.textContent = ( k + 1 < 10 ? '0' : '' ) + ( k + 1 );
				pulseDash( counter );
			}
			ba.show( k );
			ba.warm( mod( k + 1, n ) );
			if ( announce && status ) {
				status.textContent = fmt( status.getAttribute( 'data-template' ), k + 1, n );
			}
			if ( announce ) {
				reveal( ba.el );
			}
		}

		// Khung so sánh khuất quá nửa thì cuộn tới (để thấy ảnh vừa đổi).
		function reveal( el ) {
			var r = el.getBoundingClientRect(), vh = win.innerHeight || doc.documentElement.clientHeight;
			var seen = Math.min( r.bottom, vh ) - Math.max( r.top, 0 );

			if ( r.height && seen / r.height < 0.5 ) {
				try {
					el.scrollIntoView( { behavior: reduced() ? 'auto' : 'smooth', block: 'start' } );
				} catch ( err ) {
					el.scrollIntoView( true );
				}
			}
		}

		root.classList.add( 'is-live' );
		all( track, 'img' ).forEach( function ( img ) {
			img.setAttribute( 'draggable', 'false' );
		} );
		measure();
		render( false );
		syncDots();

		// Bấm thẻ (thẻ thật hoặc bản sao: cùng data-index).
		track.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest ? e.target.closest( '.isha-sg-card' ) : null;

			if ( btn ) {
				select( +btn.getAttribute( 'data-index' ), true );
			}
		} );

		// Rê chuột hoặc focus vào thẻ: nạp trước ảnh lớn của cặp đó.
		[ 'pointerover', 'focusin' ].forEach( function ( type ) {
			track.addEventListener( type, function ( e ) {
				var btn = e.target.closest ? e.target.closest( '.isha-sg-card' ) : null;

				if ( btn ) {
					ba.warm( +btn.getAttribute( 'data-index' ) );
				}
			} );
		} );

		// Tab tới thẻ thật đang khuất: trượt để thẻ đó vào khung.
		track.addEventListener( 'focusin', function ( e ) {
			var btn = e.target.closest ? e.target.closest( '.isha-sg-card' ) : null, k;

			if ( ! loop || ! btn || btn.closest( '.is-clone' ) ) {
				return;
			}
			k = Math.floor( +btn.getAttribute( 'data-index' ) / cols );   // hàng của thẻ
			normalize();
			if ( k < pos ) {
				go( k );
			} else if ( k > pos + vis - 1 ) {
				go( k - vis + 1 );
			}
		} );

		// overflow: hidden (trình duyệt chưa có clip) vẫn cuộn được khi focus: giữ viewport ở 0, track lo phần trượt.
		viewport.addEventListener( 'scroll', function () {
			if ( loop && ( viewport.scrollLeft || viewport.scrollTop ) ) {
				viewport.scrollLeft = 0;
				viewport.scrollTop = 0;
			}
		} );

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				shift( -1 );
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function () {
				shift( 1 );
			} );
		}
		dots.forEach( function ( d, k ) {
			d.addEventListener( 'click', function () {
				normalize();
				go( k + R * Math.round( ( pos - k ) / R ) );   // bản tương đương gần nhất của thẻ k, đi đường ngắn (vạch chỉ có ở dải 1 hàng)
			} );
		} );

		// Kéo để lướt. Chuột kéo ngay; chạm thì chỉ nhận khi vuốt ngang (vuốt dọc để cuộn trang). Kéo xong thì nuốt cú bấm kế.
		viewport.addEventListener( 'pointerdown', function ( e ) {
			// Panel dọc: cảm ứng để cuộn trang (touch-action: pan-y), chỉ chuột mới kéo được; người dùng cảm ứng dùng nút Lên/Xuống.
			if ( ! loop || ( 'mouse' === e.pointerType && 0 !== e.button ) || ( vertical && 'mouse' !== e.pointerType ) ) {
				return;
			}
			normalize();
			drag = { id: e.pointerId, x: e.clientX, y: e.clientY, start: pos, moved: false, last: vertical ? e.clientY : e.clientX, lastT: Date.now(), v: 0 };
		} );

		viewport.addEventListener( 'pointermove', function ( e ) {
			var dx, dy, now;

			if ( ! drag || e.pointerId !== drag.id ) {
				return;
			}
			// dx: theo trục trượt, dy: trục vuông góc.
			dx = vertical ? e.clientY - drag.y : e.clientX - drag.x;
			dy = vertical ? e.clientX - drag.x : e.clientY - drag.y;
			if ( ! drag.moved ) {
				if ( Math.abs( dx ) < 6 && Math.abs( dy ) < 6 ) {
					return;
				}
				if ( Math.abs( dy ) > Math.abs( dx ) ) {
					drag = null;
					return;
				}
				drag.moved = true;
				capture( viewport, e.pointerId );
				root.classList.add( 'is-grabbing' );
			}
			now = Date.now();
			if ( now > drag.lastT ) {
				drag.v = ( ( vertical ? e.clientY : e.clientX ) - drag.last ) / ( now - drag.lastT );   // px/ms, để nhận cú hất
			}
			drag.last = vertical ? e.clientY : e.clientX;
			drag.lastT = now;
			pos = clamp( drag.start - dx / ( step || 1 ), -R, maxPos() );
			render( false );
		} );

		function release( e ) {
			var target, moved;

			if ( ! drag || ( e && e.pointerId !== drag.id ) ) {
				return;
			}
			moved = drag.moved;
			if ( moved ) {
				swallow = true;
				target = Math.round( pos );
				// Hất nhanh hoặc kéo quá 15% bề rộng thẻ mà vẫn tròn về chỗ cũ: sang thẻ kế theo hướng kéo.
				if ( target === drag.start && ( Math.abs( drag.v ) > 0.4 || Math.abs( pos - drag.start ) > 0.15 ) ) {
					target = drag.start + ( pos > drag.start ? 1 : -1 );
				}
				go( target );
				win.setTimeout( function () {
					swallow = false;
				}, 0 );
			}
			drag = null;
			root.classList.remove( 'is-grabbing' );
		}

		viewport.addEventListener( 'pointerup', release );
		viewport.addEventListener( 'pointercancel', release );
		viewport.addEventListener( 'click', function ( e ) {
			if ( swallow ) {
				swallow = false;
				e.preventDefault();
				e.stopPropagation();
			}
		}, true );
		viewport.addEventListener( 'dragstart', function ( e ) {
			e.preventDefault();
		} );

		// Đổi kích thước: đo lại số thẻ mỗi khung và bước trượt, giữ thẻ đầu đang thấy.
		( function () {
			var raf = 0;

			function relayout() {
				raf = 0;
				normalize();
				pos = Math.round( pos );
				measure();
				if ( ! loop ) {
					pos = 0;
				}
				render( false );
				syncDots();
			}

			function queue() {
				if ( ! raf ) {
					raf = win.requestAnimationFrame( relayout );
				}
			}

			if ( 'ResizeObserver' in win ) {
				new win.ResizeObserver( queue ).observe( viewport );
			} else {
				win.addEventListener( 'resize', queue );
			}
		}() );
	}

	/* --- 3. Khung so sánh ở cột trái của "Giới thiệu dịch vụ" (desktop) ------ */

	// Nút Trước/Sau đổi cặp theo vòng; bộ đếm 01 / N và vùng status báo cặp đang xem. Không có dải thẻ.
	function stepper( root, ba ) {
		var nav = root.querySelector( '.isha-sg-intro__nav' ), counter = root.querySelector( '[data-isha-sg-current]' );
		var status = root.querySelector( '[data-isha-sg-status]' ), n = all( ba.el, '.isha-sg-ba__pair' ).length, cur = 0;

		if ( ! nav || n < 2 ) {
			return;
		}

		function go( k ) {
			cur = mod( k, n );
			ba.show( cur );
			ba.warm( mod( cur + 1, n ) );
			if ( counter ) {
				counter.textContent = ( cur + 1 < 10 ? '0' : '' ) + ( cur + 1 );
				pulseDash( counter );
			}
			if ( status ) {
				status.textContent = fmt( status.getAttribute( 'data-template' ), cur + 1, n );
			}
		}

		nav.hidden = false;
		[ [ '[data-isha-sg-prev]', -1 ], [ '[data-isha-sg-next]', 1 ] ].forEach( function ( b ) {
			var btn = nav.querySelector( b[ 0 ] );

			if ( btn ) {
				btn.addEventListener( 'click', function () {
					go( cur + b[ 1 ] );
				} );
				// Rê chuột hoặc focus vào nút: nạp trước ảnh của cặp kế theo hướng đó.
				[ 'pointerover', 'focus' ].forEach( function ( type ) {
					btn.addEventListener( type, function () {
						ba.warm( mod( cur + b[ 1 ], n ) );
					} );
				} );
			}
		} );
	}

	/* --- Khởi chạy ----------------------------------------------------------- */

	function init() {
		all( doc, '[data-isha-sg-aside]' ).forEach( function ( aside ) {
			var baEl = aside.querySelector( '[data-isha-sg-ba]' ), ba = null;

			try {
				ba = baEl ? compare( baEl ) : null;
				if ( ba ) {
					stepper( aside, ba );
				}
			} catch ( err ) {
				report( 'aside', err );
			}
		} );

		all( doc, '.isha-sg-results' ).forEach( function ( sec ) {
			var baEl = sec.querySelector( '[data-isha-sg-ba]' ), stripEl = sec.querySelector( '[data-isha-sg-strip]' ), ba = null;

			try {
				ba = baEl ? compare( baEl ) : null;
			} catch ( err ) {
				report( 'compare', err );
			}
			if ( ba && stripEl ) {
				try {
					carousel( stripEl, ba, sec.querySelector( '[data-isha-sg-status]' ) );
				} catch ( err ) {
					report( 'carousel', err );
				}
			}
		} );
	}

	if ( 'loading' === doc.readyState ) {
		doc.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
