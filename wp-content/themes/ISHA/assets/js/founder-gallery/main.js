/**
 * Entry của section Founder (trang chủ, template-parts/home/founder-story.php). Script module, nạp ở mọi lượt xem trang chủ; nhẹ: chỉ
 * gắn Lightbox cho lưới ảnh tĩnh và quyết định có dựng gallery 3D hay không.
 *
 *   - Bản tĩnh (mặc định của HTML): ảnh chân dung + lưới ảnh; bấm ảnh mở Lightbox (không có JS thì mở thẳng tệp ảnh). Giữ nguyên khi
 *     bật giảm chuyển động, trình duyệt không có WebGL2 / IntersectionObserver, chưa có ảnh gallery, hoặc dựng 3D thất bại.
 *   - Bản 3D: thêm class is-3d (CSS đổi khung thành sân khấu cao cố định), gỡ src của ảnh lưới để không tải trùng (ảnh lưới thành danh
 *     sách chỉ dành cho trình đọc màn hình, ra khỏi thứ tự Tab: khung nhận phím mũi tên thay thế). Khi khung còn cách khung nhìn
 *     CONFIG.NEAR_MARGIN mới import gallery.js, và chính nó mới import three (phụ thuộc động trong importmap của WordPress).
 *   - Bật giảm chuyển động trong lúc xem: huỷ cảnh, trả lại bản tĩnh.
 */
import { CONFIG } from './config.js';
import { createLightbox } from './lightbox.js';
import { initReadMore } from './readmore.js';

const win = window;
const doc = document;

function readImages( stage ) {
	const el = stage.querySelector( '.isha-founder__data' );
	if ( ! el ) {
		return [];
	}
	try {
		const data = JSON.parse( el.textContent || '{}' );
		const list = Array.isArray( data.images ) ? data.images : [];
		return list.every( ( img ) => img && img.src && img.lite && img.full && img.w > 0 && img.h > 0 ) ? list : [];
	} catch ( err ) {
		return [];
	}
}

function boot( stage ) {
	if ( stage.hasAttribute( 'data-isha-founder-on' ) ) {
		return;
	}
	stage.setAttribute( 'data-isha-founder-on', '' );

	const section = stage.closest( 'section' ) || doc;
	const images = readImages( stage );
	const links = Array.from( stage.querySelectorAll( '.isha-founder__item' ) );
	const thumbs = Array.from( stage.querySelectorAll( '.isha-founder__grid img' ) );
	let gallery = null;
	let mode = 'static';
	let io = null;

	const lightbox = createLightbox( section.querySelector( '.isha-founder-lb' ), {
		onOpen: () => gallery && gallery.pause( true ),
		onClose: () => gallery && gallery.pause( false ),
	} );
	const openImage = ( i, from ) => {
		const img = images[ i ];
		if ( ! lightbox || ! img ) {
			return false;
		}
		lightbox.open( img.full, img.alt, from );
		return true;
	};

	links.forEach( ( a ) => {
		a.addEventListener( 'click', ( e ) => {
			if ( openImage( parseInt( a.getAttribute( 'data-i' ), 10 ), '3d' === mode ? stage : a ) ) {
				e.preventDefault();
			}
		} );
	} );

	if ( ! images.length || images.length !== links.length ) {
		return;
	}
	const reduce = win.matchMedia( '(prefers-reduced-motion: reduce)' );
	if ( reduce.matches || ! win.WebGL2RenderingContext || ! ( 'IntersectionObserver' in win ) || ! ( 'ResizeObserver' in win ) ) {
		return;
	}

	function toggleThumbs( on ) {
		thumbs.forEach( ( img ) => {
			[ 'srcset', 'src' ].forEach( ( attr ) => {
				const keep = 'data-' + attr;
				if ( on && img.hasAttribute( keep ) ) {
					img.setAttribute( attr, img.getAttribute( keep ) );
					img.removeAttribute( keep );
				} else if ( ! on && img.hasAttribute( attr ) ) {
					img.setAttribute( keep, img.getAttribute( attr ) );
					img.removeAttribute( attr );
				}
			} );
		} );
	}

	function to3D() {
		mode = '3d';
		toggleThumbs( false );
		links.forEach( ( a ) => a.setAttribute( 'tabindex', '-1' ) );
		stage.classList.add( 'is-3d' );
		stage.setAttribute( 'role', 'group' );
		stage.setAttribute( 'aria-label', stage.getAttribute( 'data-label' ) || '' );
		stage.setAttribute( 'tabindex', '0' );
	}

	function toStatic() {
		if ( 'static' === mode ) {
			return;
		}
		mode = 'static';
		if ( io ) {
			io.disconnect();
		}
		if ( gallery ) {
			gallery.destroy();
			gallery = null;
		}
		stage.classList.remove( 'is-3d', 'is-used' );
		[ 'role', 'aria-label', 'tabindex' ].forEach( ( attr ) => stage.removeAttribute( attr ) );
		links.forEach( ( a ) => a.removeAttribute( 'tabindex' ) );
		toggleThumbs( true );
	}

	to3D();
	io = new IntersectionObserver(
		( entries ) => {
			if ( ! entries.some( ( e ) => e.isIntersecting ) ) {
				return;
			}
			io.disconnect();
			import( './gallery.js' )
				.then( ( mod ) => {
					if ( '3d' !== mode ) {
						return;
					}
					gallery = mod.createGallery( stage, images, {
						openImage: ( i ) => openImage( i, stage ),
						onFail: () => {
							gallery = null;
							toStatic();
						},
					} );
				} )
				.catch( ( err ) => {
					if ( win.console ) {
						// Nếu thông báo là "caused context loss and was blocked": Chrome đã chặn WebGL của trang này sau một lần GPU bị reset; lỗi
						// tự hết sau vài phút hoặc khi khởi động lại trình duyệt. Bản tĩnh (chân dung + lưới ảnh) vẫn dùng bình thường.
						win.console.warn( '[ISHA founder] không dựng được gallery 3D, dùng bản tĩnh.', err );
					}
					toStatic();
				} );
		},
		{ rootMargin: CONFIG.NEAR_MARGIN }
	);
	io.observe( stage );

	const onReduce = ( e ) => {
		if ( e.matches ) {
			toStatic();
		}
	};
	if ( reduce.addEventListener ) {
		reduce.addEventListener( 'change', onReduce );
	} else if ( reduce.addListener ) {
		reduce.addListener( onReduce );
	}
}

doc.querySelectorAll( '[data-isha-founder]' ).forEach( boot );
initReadMore();
