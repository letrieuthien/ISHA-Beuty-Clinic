/**
 * [isha_lp_cards_member] – thang hạng thẻ thành viên (scrollytelling).
 *
 * - Chương nào nằm trên "đường đọc" (giữa vùng nhìn thấy) thì thẻ và lớp sóng đổi theo hạng đó.
 * - Hạng thứ i trong N hạng (N ≥ 2) bật round(1 + 5·i/(N−1)) trong 6 lớp sóng:
 *   hạng đầu luôn có lớp đáy, hạng cuối luôn đủ 6 lớp, số hạng khác 6 vẫn đúng.
 * - Không dùng thư viện. Không có JS / không có IntersectionObserver thì CSS vẫn hiển thị đủ (thẻ đầu + toàn bộ sóng).
 */
( function () {
	'use strict';

	if ( ! ( 'IntersectionObserver' in window ) ) {
		return;
	}

	var LAYERS = 6;
	var reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var mobile = window.matchMedia( '(max-width: 900px)' );

	function pad( v ) {
		return v < 10 ? '0' + v : String( v );
	}

	function init( sec ) {
		var stage = sec.querySelector( '.isha-lp-ladder__stage' );
		var chapters = [].slice.call( sec.querySelectorAll( '.isha-lp-chapter' ) );
		var cards = [].slice.call( sec.querySelectorAll( '.isha-lp-tier' ) );
		var waves = [].slice.call( sec.querySelectorAll( '.isha-lp-wave' ) );
		var cur = sec.querySelector( '.isha-lp-ladder__cur' );
		var bar = sec.querySelector( '.isha-lp-ladder__bar i' );
		var dots = [].slice.call( sec.querySelectorAll( '.isha-lp-dot' ) );
		var more = sec.querySelector( '.isha-lp-ladder__more' );
		var moreText = more ? more.textContent.trim() : '';
		var n = chapters.length;
		var current = -1;
		var lpTop = 0;
		var readingLine = 0;
		var visible = false;
		var ticking = false;
		var resizeTimer = 0;

		if ( ! n || ! stage ) {
			return;
		}

		function layersOn( i ) {
			return n < 2 ? LAYERS : Math.round( 1 + ( LAYERS - 1 ) * i / ( n - 1 ) );
		}

		function set( i ) {
			if ( i === current ) {
				return;
			}
			var was = current < 0 ? 0 : layersOn( current );
			var on = layersOn( i );
			current = i;

			cards.forEach( function ( card, k ) {
				card.classList.toggle( 'is-active', k === i );
				card.classList.toggle( 'is-before', k < i );
				card.setAttribute( 'aria-hidden', k === i ? 'false' : 'true' );
			} );
			chapters.forEach( function ( chapter, k ) {
				chapter.classList.toggle( 'is-active', k === i );
			} );
			dots.forEach( function ( dot, k ) {
				dot.classList.toggle( 'is-active', k === i );
				if ( k === i ) {
					dot.setAttribute( 'aria-current', 'true' );
				} else {
					dot.removeAttribute( 'aria-current' );
				}
			} );

			// Nút "Xem chi tiết" trỏ tới trang của hạng đang xem.
			if ( more && chapters[ i ].getAttribute( 'data-url' ) ) {
				more.setAttribute( 'href', chapters[ i ].getAttribute( 'data-url' ) );
				more.setAttribute( 'aria-label', moreText + ' – ' + ( dots[ i ] ? dots[ i ].getAttribute( 'data-label' ) : '' ) );
			}

			// Lớp mới nổi lần lượt từ dưới lên; khi hạ hạng thì lớp trên cùng chìm trước.
			waves.forEach( function ( wave, k ) {
				var show = k < on;
				var delay = reduce.matches ? 0 : ( show ? Math.max( 0, k - was ) * 150 : Math.max( 0, was - 1 - k ) * 90 );
				wave.style.setProperty( '--lp-delay', delay + 'ms' );
				wave.classList.toggle( 'is-on', show );
			} );

			// Máy quay trượt dần lên đỉnh tranh theo số lớp đang bật (chỉ có tác dụng khi màn hình thấp hơn tranh).
			sec.style.setProperty( '--pan', ( ( on - 1 ) / ( LAYERS - 1 ) ).toFixed( 3 ) );

			if ( cur ) {
				cur.textContent = pad( i + 1 );
			}
			if ( bar ) {
				bar.style.setProperty( '--p', ( ( i + 1 ) / n ).toFixed( 3 ) );
			}
		}

		// Chiều cao header dính (nếu có) để khung ghim nằm ngay dưới nó; từ đó suy ra đường đọc.
		// Đường đọc: giữa khung ghim (desktop) hoặc giữa phần màn hình bên dưới khung ghim (mobile).
		function measure() {
			var header = document.querySelector( '.site-header' );
			var top = 0;
			if ( header ) {
				var cs = window.getComputedStyle( header );
				if ( 'sticky' === cs.position || 'fixed' === cs.position ) {
					top = ( parseFloat( cs.top ) || 0 ) + header.offsetHeight;
				}
			}
			lpTop = top;
			sec.style.setProperty( '--lp-top', top + 'px' );

			var vh = window.innerHeight;
			var stageH = stage.offsetHeight; // Đọc sau khi đã đặt --lp-top.
			readingLine = mobile.matches ? top + stageH + ( vh - top - stageH ) / 2 : top + stageH / 2;
		}

		// Hạng đang đọc = chương nằm trên đường đọc; đã cuộn qua chương cuối thì giữ hạng cuối.
		// Chưa tới chương đầu thì không đổi (lượt vào khung bên dưới lo việc hiện hạng 1).
		function evaluate() {
			var found = -1;
			for ( var k = 0; k < n; k++ ) {
				var r = chapters[ k ].getBoundingClientRect();
				if ( r.top <= readingLine && r.bottom > readingLine ) {
					found = k;
					break;
				}
			}
			if ( found < 0 && chapters[ n - 1 ].getBoundingClientRect().bottom <= readingLine ) {
				found = n - 1;
			}
			if ( found >= 0 ) {
				set( found );
			}
		}

		function onScroll() {
			if ( ! visible || ticking ) {
				return;
			}
			ticking = true;
			window.requestAnimationFrame( function () {
				ticking = false;
				evaluate();
			} );
		}

		// Bấm nút chọn hạng: nhảy tức thì tới chương đó, đầu chương nằm ngay dưới header (mobile: dưới khung ghim).
		function goTo( i ) {
			var chapter = chapters[ i ];
			if ( ! chapter ) {
				return;
			}
			measure();
			var offset = mobile.matches ? lpTop + stage.offsetHeight : lpTop;
			var y = chapter.getBoundingClientRect().top + window.pageYOffset - offset;
			var root = document.documentElement;
			var prev = root.style.scrollBehavior;
			root.style.scrollBehavior = 'auto'; // Theme bật scroll-behavior: smooth → ép nhảy tức thì.
			window.scrollTo( window.pageXOffset, Math.max( 0, Math.round( y ) ) );
			root.style.scrollBehavior = prev;
			set( i );
		}

		dots.forEach( function ( dot, k ) {
			dot.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				goTo( k );
			} );
		} );

		// Màu trung bình của ảnh thẻ (bỏ góc bo trong suốt) – dùng cho nút chưa được admin chọn màu.
		function sampleColor( img ) {
			try {
				var w = 24;
				var h = 15;
				var canvas = document.createElement( 'canvas' );
				canvas.width = w;
				canvas.height = h;
				var ctx = canvas.getContext( '2d', { willReadFrequently: true } );
				ctx.drawImage( img, 0, 0, w, h );
				var px = ctx.getImageData( 0, 0, w, h ).data;
				var r = 0;
				var g = 0;
				var b = 0;
				var count = 0;
				for ( var p = 0; p < px.length; p += 4 ) {
					if ( px[ p + 3 ] < 200 ) {
						continue;
					}
					r += px[ p ];
					g += px[ p + 1 ];
					b += px[ p + 2 ];
					count++;
				}
				return count ? 'rgb(' + Math.round( r / count ) + ',' + Math.round( g / count ) + ',' + Math.round( b / count ) + ')' : '';
			} catch ( err ) {
				return ''; // Ảnh khác nguồn (canvas bị khoá) → giữ màu mặc định.
			}
		}

		dots.forEach( function ( dot, k ) {
			var img = dot.hasAttribute( 'data-auto' ) && cards[ k ] ? cards[ k ].querySelector( 'img' ) : null;
			if ( ! img ) {
				return;
			}
			var apply = function () {
				var color = sampleColor( img );
				if ( color ) {
					dot.style.setProperty( '--c', color );
				}
			};
			if ( img.complete && img.naturalWidth ) {
				apply();
			} else {
				img.addEventListener( 'load', apply, { once: true } );
			}
		} );

		sec.classList.add( 'is-ready' );
		measure();

		// Chỉ theo dõi cuộn khi section đang ở trong màn hình.
		new IntersectionObserver( function ( entries ) {
			visible = entries[ 0 ].isIntersecting;
			if ( visible ) {
				evaluate();
			}
		} ).observe( sec );
		window.addEventListener( 'scroll', onScroll, { passive: true } );

		// Lần đầu khung ghim lọt vào màn hình (≥30%) mà chưa có hạng nào → bắt đầu từ hạng 1 để thấy lớp sóng đầu tiên nổi lên.
		var entrance = new IntersectionObserver(
			function ( entries ) {
				if ( entries[ 0 ].isIntersecting ) {
					evaluate(); // Vào thẳng giữa / cuối section (tải lại trang, neo #) thì lấy đúng hạng theo vị trí cuộn.
					if ( current < 0 ) {
						set( 0 );
					}
					entrance.disconnect();
				}
			},
			{ threshold: 0.3 }
		);
		entrance.observe( stage );

		window.addEventListener( 'resize', function () {
			window.clearTimeout( resizeTimer );
			resizeTimer = window.setTimeout( function () {
				measure();
				evaluate();
			}, 200 );
		} );
	}

	Array.prototype.forEach.call( document.querySelectorAll( '.isha-lp-ladder' ), init );
}() );
