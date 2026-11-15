/**
 * Vòng đời của cảnh WebGL: chạy vòng render chỉ khi section trong khung nhìn VÀ tab đang hiện; mất context WebGL thì dừng, hiện lại poster,
 * khôi phục được thì chạy tiếp (three tự nạp lại tài nguyên từ dữ liệu CPU), mất lần thứ hai thì gọi onFatal (main chuyển sang poster/2D);
 * đổi kích thước stage thì gọi onResize; mỗi lần vòng render bắt đầu / chạy lại thì gọi onStart (governor bỏ khoảng cách khung đầu).
 */
export function createLifecycle( { renderer, section, stage, canvas, frame, onResize, onStart = () => {}, onFatal } ) {
	let visible = false;
	let tabVisible = document.visibilityState !== 'hidden';
	let lost = 0;
	let running = false;

	function sync() {
		const want = visible && tabVisible && ! renderer.getContext().isContextLost();
		if ( want && ! running ) {
			onStart();
			renderer.setAnimationLoop( frame );
			running = true;
		} else if ( ! want && running ) {
			renderer.setAnimationLoop( null );
			running = false;
		}
	}

	const io = new IntersectionObserver( ( entries ) => {
		visible = entries.some( ( e ) => e.isIntersecting );
		sync();
	} );
	io.observe( section );

	const onVis = () => {
		tabVisible = document.visibilityState !== 'hidden';
		sync();
	};
	document.addEventListener( 'visibilitychange', onVis );

	const onLost = ( e ) => {
		e.preventDefault();
		lost++;
		renderer.setAnimationLoop( null );
		running = false;
		section.classList.remove( 'is-ready' );
		if ( lost >= 2 ) {
			onFatal( new Error( 'Mất context WebGL lần thứ hai' ) );
		}
	};
	const onRestored = () => {
		if ( lost >= 2 ) {
			return;
		}
		section.classList.add( 'is-ready' );
		sync();
	};
	canvas.addEventListener( 'webglcontextlost', onLost );
	canvas.addEventListener( 'webglcontextrestored', onRestored );

	const ro = new ResizeObserver( () => onResize( stage.clientWidth, stage.clientHeight ) );
	ro.observe( stage );

	return {
		get running() {
			return running;
		},
		destroy() {
			io.disconnect();
			ro.disconnect();
			document.removeEventListener( 'visibilitychange', onVis );
			canvas.removeEventListener( 'webglcontextlost', onLost );
			canvas.removeEventListener( 'webglcontextrestored', onRestored );
			renderer.setAnimationLoop( null );
			running = false;
		},
	};
}
