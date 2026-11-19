/**
 * Thu gọn khối văn bản CMS của section Founder ("Đọc trọn vẹn câu chuyện"). Chỉ JS mới thu gọn: không JS thì cả bài hiển thị đầy đủ.
 *
 *   - Vùng thu gọn cao đúng `--fs-lines` dòng (CSS đặt 5, màn hình thấp 4) tính theo line-height thật của đoạn đầu; mép dưới mờ dần vào nền
 *     bằng mask (CSS: biến `--fs-fade`, chuyển 72px -> 0px khi mở). Nội dung ngắn hơn vùng đó thì không thu gọn và nút vẫn ẩn.
 *   - Bấm nút: bung mượt tại chỗ (max-height chạy tới chiều cao thật rồi gỡ giới hạn), nút đổi thành "Thu gọn câu chuyện" và mũi tên quay
 *     180°; bấm lại thì thu về. aria-expanded theo trạng thái. Giảm chuyển động: đổi tức thì.
 *   - Đổi kích thước cửa sổ: tính lại số dòng và chiều cao (khi đang thu gọn).
 */
const doc = document;
const win = window;

function setup( box ) {
	const btn = box.parentElement && box.parentElement.querySelector( '.isha-founder__more' );
	const label = btn && btn.querySelector( '.isha-founder__more-text' );
	const firstP = box.querySelector( 'p' );
	if ( ! btn || ! label || ! firstP ) {
		return;
	}
	const reduce = win.matchMedia( '(prefers-reduced-motion: reduce)' );
	let open = false;
	let busy = null;

	// Chiều cao vùng thu gọn (px): số dòng x line-height của đoạn đầu.
	const clampHeight = () => {
		const lines = parseFloat( win.getComputedStyle( box ).getPropertyValue( '--fs-lines' ) ) || 5;
		const lh = parseFloat( win.getComputedStyle( firstP ).lineHeight ) || 28;
		return Math.round( lines * lh );
	};

	// Nội dung dài hơn vùng thu gọn bao nhiêu thì mới đáng thu gọn (dư tối thiểu 1 dòng, tránh nút cho đoạn chỉ dài hơn chút).
	const needed = () => box.scrollHeight > clampHeight() + ( parseFloat( win.getComputedStyle( firstP ).lineHeight ) || 28 );

	const wake = () => win.dispatchEvent( new Event( 'resize' ) ); // lớp sky của trang chủ đo lại chiều cao các section

	const finish = ( isOpen ) => {
		box.style.removeProperty( 'max-height' );
		busy = null;
		box.classList.toggle( 'is-open', isOpen );
		wake();
	};

	// Chạy chuyển max-height; xong thì gọi done (có chốt an toàn nếu transitionend không tới).
	const run = ( done ) => {
		let ended = false;
		const end = () => {
			if ( ended ) {
				return;
			}
			ended = true;
			box.removeEventListener( 'transitionend', onEnd );
			win.clearTimeout( timer );
			done();
		};
		const onEnd = ( e ) => {
			if ( e.target === box && 'max-height' === e.propertyName ) {
				end();
			}
		};
		const timer = win.setTimeout( end, 1100 );
		box.addEventListener( 'transitionend', onEnd );
		busy = end;
	};

	const apply = ( want ) => {
		if ( busy ) {
			busy(); // đang chạy dở: chốt trạng thái trước rồi mới đổi
		}
		open = want;
		btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		label.textContent = btn.getAttribute( open ? 'data-less' : 'data-more' );
		btn.classList.toggle( 'is-open', open );

		if ( reduce.matches ) {
			box.classList.toggle( 'is-open', open );
			box.style.removeProperty( 'max-height' );
			wake();
			return;
		}
		if ( open ) {
			box.style.maxHeight = clampHeight() + 'px';
			void box.offsetHeight; // buộc tính layout để chuyển bắt đầu từ chiều cao thu gọn
			box.classList.add( 'is-open' ); // --fs-fade về 0
			box.style.maxHeight = box.scrollHeight + 'px';
			run( () => {
				box.style.maxHeight = 'none';
				busy = null;
				wake();
			} );
		} else {
			box.style.maxHeight = box.scrollHeight + 'px';
			void box.offsetHeight;
			box.classList.remove( 'is-open' );
			box.style.maxHeight = clampHeight() + 'px';
			run( () => finish( false ) );
		}
	};

	const refresh = () => {
		if ( open || busy ) {
			return;
		}
		if ( needed() ) {
			box.style.setProperty( '--fs-clamp-h', clampHeight() + 'px' );
			box.classList.add( 'is-clamp' );
			btn.hidden = false;
		} else {
			box.classList.remove( 'is-clamp' );
			box.style.removeProperty( '--fs-clamp-h' );
			btn.hidden = true;
		}
	};

	btn.addEventListener( 'click', () => apply( ! open ) );
	refresh();
	let t = 0;
	win.addEventListener( 'resize', () => {
		win.clearTimeout( t );
		t = win.setTimeout( refresh, 150 );
	} );
	// Chữ (font) tải xong làm đổi line-height / số dòng: đo lại.
	if ( doc.fonts && doc.fonts.ready ) {
		doc.fonts.ready.then( refresh );
	}
}

export function initReadMore() {
	doc.querySelectorAll( '.isha-founder__story' ).forEach( setup );
}
