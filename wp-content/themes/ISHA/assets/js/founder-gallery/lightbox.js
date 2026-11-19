/**
 * Lightbox tối giản của section Founder: thẻ <dialog> có sẵn trong template (.isha-founder-lb), mở bằng showModal() nên trình duyệt tự
 * lo giữ focus trong hộp thoại, phím Esc và làm trơ phần còn lại của trang. Chỉ có ảnh lớn và nút đóng, không chú thích.
 * Đóng: nút ×, Esc, hoặc bấm vào vùng tối quanh ảnh. Khi mở: khoá cuộn trang (html.isha-lb-open); đóng thì trả focus về nơi mở.
 * Trình duyệt không có <dialog>.showModal(): trả null, liên kết ảnh mở thẳng tệp ảnh như khi không có JS.
 */
export function createLightbox( dialog, { onOpen, onClose } = {} ) {
	if ( ! dialog || 'function' !== typeof dialog.showModal ) {
		return null;
	}
	const html = document.documentElement;
	const close = dialog.querySelector( '.isha-founder-lb__close' );
	const img = document.createElement( 'img' );
	img.className = 'isha-founder-lb__img';
	img.decoding = 'async';
	img.alt = ''; // luôn có thuộc tính alt (cả khi chưa mở); khi mở gán mô tả của ảnh
	dialog.appendChild( img );

	let opener = null;

	img.addEventListener( 'load', () => img.classList.add( 'is-ready' ) );
	if ( close ) {
		close.addEventListener( 'click', () => dialog.close() );
	}
	// Bấm vào chính thẻ dialog (phần đệm tối quanh ảnh), không phải ảnh hay nút: đóng.
	dialog.addEventListener( 'click', ( e ) => {
		if ( e.target === dialog ) {
			dialog.close();
		}
	} );
	dialog.addEventListener( 'close', () => {
		html.classList.remove( 'isha-lb-open' );
		img.classList.remove( 'is-ready' );
		img.removeAttribute( 'src' );
		if ( onClose ) {
			onClose();
		}
		if ( opener && 'function' === typeof opener.focus && opener.isConnected ) {
			opener.focus( { preventScroll: true } );
		}
		opener = null;
	} );

	return {
		get isOpen() {
			return dialog.open;
		},
		/**
		 * @param {string}      src  URL ảnh lớn.
		 * @param {string}      alt  Mô tả ảnh.
		 * @param {Element|null} from Phần tử nhận lại focus khi đóng.
		 */
		open( src, alt, from ) {
			if ( dialog.open || ! src ) {
				return;
			}
			opener = from || document.activeElement;
			img.classList.remove( 'is-ready' );
			img.alt = alt || '';
			img.src = src;
			if ( img.complete && img.naturalWidth ) {
				img.classList.add( 'is-ready' );
			}
			html.classList.add( 'isha-lb-open' );
			dialog.showModal();
			if ( close ) {
				close.focus( { preventScroll: true } );
			}
			if ( onOpen ) {
				onOpen();
			}
		},
		close() {
			if ( dialog.open ) {
				dialog.close();
			}
		},
	};
}
