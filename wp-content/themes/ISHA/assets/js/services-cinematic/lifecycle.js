/**
 * Vòng đời của cảnh WebGL (đặc tả v3 mục 16, bước 9).
 *
 * - Dựng cảnh (build() → scene/index.js createScene), vẽ khung đầu rồi báo onReady (main.js thêm .is-ready: poster mờ dần sang canvas).
 * - Vòng render chỉ chạy khi section nằm trong khung nhìn (IntersectionObserver) và tab đang hiện (visibilitychange); ngoài ra dừng hẳn.
 * - Mất context WebGL (webglcontextlost; GPU reset, tab nền lâu, hệ điều hành thu hồi): dừng vẽ, hiện lại poster, chờ khôi phục; khi
 *   webglcontextrestored thì huỷ cảnh cũ (tài nguyên GPU đã mất) và dựng lại trên chính canvas đó. Mất lần HAI thì chuyển hẳn sang 2D
 *   (on2D): thiết bị không giữ nổi context, dựng lại nữa chỉ tốn pin.
 * - Governor của cảnh quyết định xuống 2D (scene gọi fallback) hoặc dựng lỗi: cũng qua on2D.
 *
 * Không phụ thuộc three.js; IntersectionObserver và document truyền vào được (test: tests/lifecycle.test.mjs).
 */

/**
 * @param {object}   o
 * @param {Element}  o.section
 * @param {EventTarget} o.canvas
 * @param {(fallback: Function) => Promise<object>} o.build Dựng cảnh; nhận hàm fallback() để governor gọi khi cần xuống 2D.
 * @param {(scene: object) => void} [o.onReady]    Cảnh (mới dựng / dựng lại) đã vẽ khung đầu.
 * @param {(why: string) => void}   [o.onLost]     Mất context (chờ khôi phục): main.js gỡ .is-ready để poster che.
 * @param {(why: string) => void}   o.on2D         Chuyển hẳn sang 2D.
 * @param {object}   [o.doc]   document.
 * @param {Function} [o.IO]    IntersectionObserver.
 */
export function createLifecycle( { section, canvas, build, onReady, onLost, on2D, doc = document, IO = IntersectionObserver } ) {
	let scene = null;
	let inView = false;
	let losses = 0;
	let dead = false;
	let pending = null;

	function sync() {
		if ( ! scene ) {
			return;
		}
		if ( inView && ! doc.hidden ) {
			scene.start();
		} else {
			scene.stop();
		}
	}

	function drop() {
		if ( scene ) {
			try {
				scene.destroy();
			} catch ( err ) {
				// Context đã mất: dispose có thể ném lỗi, tài nguyên đằng nào cũng đã mất cùng context.
			}
			scene = null;
		}
	}

	function to2D( why ) {
		if ( dead ) {
			return;
		}
		dead = true;
		drop();
		on2D( why );
	}

	async function make() {
		pending = build( () => to2D( 'governor' ) );
		try {
			const s = await pending;
			if ( dead ) {
				s.destroy();
				return null;
			}
			scene = s;
			scene.renderOnce();
			if ( onReady ) {
				onReady( scene );
			}
			sync();
			return scene;
		} catch ( err ) {
			console.warn( '[ISHA svc] Không dựng được cảnh WebGL, giữ poster.', err );
			to2D( 'lỗi khi dựng cảnh' );
			return null;
		} finally {
			pending = null;
		}
	}

	const io = new IO( ( entries ) => {
		inView = entries.some( ( e ) => e.isIntersecting );
		sync();
	} );
	io.observe( section );
	doc.addEventListener( 'visibilitychange', sync );

	const onLostEvt = ( e ) => {
		e.preventDefault(); // cho phép trình duyệt khôi phục context
		losses++;
		if ( scene ) {
			scene.stop();
		}
		if ( losses >= 2 ) {
			to2D( 'mất context WebGL lần hai' );
			return;
		}
		if ( onLost ) {
			onLost( 'mất context WebGL, chờ khôi phục' );
		}
	};
	const onRestored = () => {
		if ( dead || pending ) {
			return;
		}
		drop();
		make();
	};
	canvas.addEventListener( 'webglcontextlost', onLostEvt );
	canvas.addEventListener( 'webglcontextrestored', onRestored );

	const ready = make();

	return {
		/** Promise: cảnh đầu tiên (null nếu không dựng được). */
		ready,
		get scene() {
			return scene;
		},
		get losses() {
			return losses;
		},
		destroy() {
			dead = true;
			io.disconnect();
			doc.removeEventListener( 'visibilitychange', sync );
			canvas.removeEventListener( 'webglcontextlost', onLostEvt );
			canvas.removeEventListener( 'webglcontextrestored', onRestored );
			drop();
		},
	};
}
