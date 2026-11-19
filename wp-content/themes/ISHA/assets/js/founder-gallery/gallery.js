/**
 * Gallery 3D chiều sâu của section Founder (three.js thuần, viết lại từ bản React Three Fiber docs/founder-section.md).
 *
 * Mô hình (toả ra từ tâm): tiến độ `pos` tính bằng ảnh. Mỗi ảnh nhú ra ở TÂM khung (nhỏ ~150px, opacity 0,15) rồi đi theo một hướng riêng
 * (góc vàng + lệch ngẫu nhiên) ra mép khung, lớn dần và rõ dần (opacity 1 từ 40% quãng đường), từ 80% quãng đường mờ lại và biến mất khi
 * chạm mép. Tiến độ đi của ảnh i: t = 0,5 + (pos - i) / LIFE (xem config.js); chỉ ảnh có t trong [0, 1) mới hiện nên trên khung có tối đa LIFE
 * (= 3) ảnh cùng lúc. pos chạy 0..END với END = N - 1 + LIFE / 2: tới END ảnh cuối đã đi hết, ảnh chân dung (HTML, .isha-founder__portrait)
 * hiện ở giữa khung cùng chức danh và tên (biến CSS --fp, --fc) và hạt bụi ánh sáng (dust.js) toả quanh khung chân dung. Không lặp vô hạn.
 *
 * Điều khiển (tự trôi, lăn chuột và phím dùng CHUNG một tốc độ AUTO_SPEED):
 *   - Lăn chuột trong khung (desktop): luôn thuộc về gallery, kể cả ở hai đầu hoặc khi khung mới hiện một phần (đã bỏ hai quy tắc trả quyền
 *     cuộn); mỗi lần lăn dời đích đi WHEEL_TRAVEL x px ảnh, ảnh đi tới đích với tốc độ AUTO_SPEED. Muốn cuộn trang: đưa con trỏ ra ngoài khung.
 *   - Lăn ngang trackpad, Shift + lăn, kéo chuột ngang, vuốt ngang (điện thoại): đi tới / lùi; vuốt dọc luôn cuộn trang (touch-action: pan-y).
 *   - Phím mũi tên khi khung có focus (không nhận khi chỉ hover); Home / End; Enter mở ảnh đang ở vùng lấy nét.
 *   - Ngừng thao tác: ảnh trượt nốt tới nhịp gần nhất theo hướng vừa đi. 3 giây không tương tác: tự trôi tới ảnh chân dung rồi dừng.
 *   - Rê chuột lên ảnh (desktop): hãm tự trôi, viền vàng + vệt ánh kim chạy quanh chu vi, con trỏ pointer. Click / chạm: mở Lightbox.
 * Vòng đời: rAF riêng, chỉ chạy khi khung trong khung nhìn, tab đang hiện và Lightbox đóng; khi đứng yên không vẽ lại. Mất context WebGL
 * lần đầu: dừng, chờ khôi phục; lần hai: onFail (main.js quay về bản tĩnh). Texture tải theo cửa sổ quanh pos, giải phóng khi đi xa.
 */
import * as THREE from 'three';
import { CONFIG as C } from './config.js';
import { VERTEX, FRAGMENT } from './shader.js';
import { createDust } from './dust.js';

const clamp = ( v, a, b ) => Math.min( b, Math.max( a, v ) );
const smooth = ( a, b, x ) => {
	const t = clamp( ( x - a ) / ( b - a ), 0, 1 );
	return t * t * ( 3 - 2 * t );
};
const damp = ( rate, dt ) => 1 - Math.exp( -rate * dt );
const now = () => performance.now();
const GOLDEN = 2.39996323;

// Hướng đi của ảnh i (radian): bước góc vàng làm hai ảnh liền nhau luôn đi hai hướng rất khác nhau, cộng lệch ngẫu nhiên cố định theo SEED.
function pathAngle( i ) {
	const r = Math.sin( ( i + 1 ) * 127.1 + C.SEED * 311.7 ) * 43758.5453;
	return C.SEED + i * GOLDEN + ( r - Math.floor( r ) - 0.5 ) * 2 * C.ANGLE_JITTER;
}

/**
 * @param {HTMLElement} stage  Khung gallery (.isha-founder__stage, đã có class is-3d).
 * @param {Array}       images Dữ liệu ảnh: { src, lite, full, w, h, alt }.
 * @param {Object}      opts   { openImage( i ), onFail( reason ) }.
 * @return {{ pause( on: boolean ): void, destroy(): void }}
 */
export function createGallery( stage, images, { openImage, onFail } ) {
	const win = window;
	const doc = document;
	const N = images.length;
	const lite = win.matchMedia( '(pointer: coarse)' ).matches || win.innerWidth < 768;
	const fine = win.matchMedia( '(hover: hover) and (pointer: fine)' ).matches;
	const hoverOn = fine && ! lite;
	const LIFE = lite ? C.LIFE_LITE : C.LIFE; // số ảnh tối đa cùng lúc trên khung (một ảnh đi hết đường trong LIFE bước)
	const END = N - 1 + LIFE / 2;             // tiến độ khi ảnh cuối đã đi hết đường (ảnh chân dung hiện)
	const START_PX = lite ? C.START_PX_LITE : C.START_PX;
	const host = stage.querySelector( '.isha-founder__canvas' ) || stage;
	const live = stage.querySelector( '.isha-founder__live' );
	const portraitFrame = stage.querySelector( '.isha-founder__frame' );
	const caption = stage.querySelector( '.isha-founder__caption' );
	const nowEl = stage.querySelector( '.isha-founder__now' );

	/* --- Renderer, cảnh, camera ------------------------------------------------------------------------------------------------- */
	const canvas = doc.createElement( 'canvas' );
	canvas.className = 'isha-founder__gl';
	// Ném lỗi nếu không tạo được context: main.js bắt và giữ bản tĩnh.
	const renderer = new THREE.WebGLRenderer( { canvas, antialias: ! lite, alpha: false, stencil: false } ); // powerPreference mặc định: 'high-performance' ép máy hai GPU chuyển sang GPU rời, dễ gây reset
	renderer.outputColorSpace = THREE.SRGBColorSpace;
	renderer.setClearColor( new THREE.Color( C.BG ), 1 );
	host.appendChild( canvas );

	const scene = new THREE.Scene();
	const camera = new THREE.PerspectiveCamera( C.FOV, 1, 0.1, 80 );
	const tanHalf = Math.tan( THREE.MathUtils.degToRad( C.FOV / 2 ) );
	const geometry = lite ? new THREE.PlaneGeometry( 1, 1, 1, 1 ) : new THREE.PlaneGeometry( 1, 1, C.SEGMENTS, C.SEGMENTS );
	const defines = {};
	if ( ! lite ) {
		defines.FG_CLOTH = '';
		defines.FG_BLUR = '';
	}
	if ( hoverOn ) {
		defines.FG_HOVER = '';
	}
	const bg = new THREE.Color( C.BG );
	const gold = new THREE.Color( C.GOLD );
	const glint = new THREE.Color( C.GLINT );
	const aniso = Math.min( 4, renderer.capabilities.getMaxAnisotropy() );

	const items = images.map( ( img, i ) => {
		const material = new THREE.ShaderMaterial( {
			uniforms: {
				uMap: { value: null },
				uOpacity: { value: 0 },
				uForce: { value: 0 },
				uZen: { value: C.ZEN },
				uDim: { value: 0 },
				uBg: { value: bg },
				uUvScale: { value: new THREE.Vector2( 1, 1 ) },
				uUvOffset: { value: new THREE.Vector2( 0, 0 ) },
				uBlur: { value: 0 },
				uSize: { value: new THREE.Vector2( 1, 1 ) },
				uRadius: { value: 0 },
				uHover: { value: 0 },
				uTime: { value: 0 },
				uGold: { value: gold },
				uGlint: { value: glint },
				uLinePx: { value: C.LINE_PX },
				uGlintSpeed: { value: C.GLINT_SPEED },
				uGlintLen: { value: C.GLINT_LEN },
			},
			vertexShader: VERTEX,
			fragmentShader: FRAGMENT,
			defines,
			transparent: true,
			depthWrite: false,
			depthTest: false, // three xếp ảnh trong suốt từ xa tới gần; tắt depth test để mặt cong không tự che mình.
		} );
		const mesh = new THREE.Mesh( geometry, material );
		mesh.visible = false;
		mesh.userData.index = i;
		scene.add( mesh );

		// Tỉ lệ hiển thị kẹp trong 2:3 .. 16:9; ảnh ngoài khoảng bị cắt mép đều hai bên (cover).
		const a = img.w / img.h;
		const pa = clamp( a, C.ASPECT_MIN, C.ASPECT_MAX );
		const u = material.uniforms;
		if ( a > pa ) {
			u.uUvScale.value.x = pa / a;
			u.uUvOffset.value.x = ( 1 - pa / a ) / 2;
		} else if ( a < pa ) {
			u.uUvScale.value.y = a / pa;
			u.uUvOffset.value.y = ( 1 - a / pa ) / 2;
		}

		const ang = pathAngle( i );
		return {
			i, img, mesh, material, u, aspect: pa,
			dx: Math.cos( ang ), dy: Math.sin( ang ), reach: 0, // hướng đi và quãng đường tâm -> mép khung (px), tính ở layout()
			tex: null, state: 'idle', token: 0, fade: 0, opacity: 0, hover: 0,
		};
	} );

	/* --- Trạng thái --------------------------------------------------------------------------------------------------------------- */
	// mode: rest (đứng yên) | seek (đi tới đích `target` với tốc độ AUTO_SPEED: lăn chuột, phím, trượt vào nhịp) | free (quán tính sau
	// khi thả tay) | drag (đang kéo) | auto (tự trôi). `cap` là tốc độ trần của seek (Home / End nhanh hơn).
	const st = { pos: 0, v: 0, mode: 'rest', target: 0, cap: C.AUTO_SPEED, dir: 1, last: now(), hovered: -1 };
	let force = 0;
	let raf = 0;
	let prevT = 0;
	let visible = false;
	let paused = false;
	let lost = false;
	let dead = false;
	let dirty = true;
	let losses = 0;
	let W = 0;
	let H = 0;
	let dpr = 0;
	let endPx = 0;
	let startPx = 0;
	let ptr = null;
	let pointer = null; // vị trí chuột (desktop) khi đang trong khung, để dò hover
	let pointerDirty = false;
	let loading = 0;
	const uploads = [];
	const hud = { fp: -1, fc: -1, prog: -1, n: -1 };
	const dust = createDust( scene, { lite } );
	// Mức hiện của ảnh chân dung 0..1 (cũng là mức hiện của hạt bụi): tính theo tỉ lệ LIFE tính lùi từ END.
	const portraitAmount = () => smooth( END - C.PORTRAIT_FROM * LIFE, END, st.pos );

	const running = () => visible && ! paused && ! lost && ! dead && ! doc.hidden;
	// Chỉ MỘT vòng vẽ tại một thời điểm. Trong lúc frame() đang chạy (inFrame), kick() không lên lịch gì: cuối frame() tự lên lịch khung
	// sau. Trước 19/11/2026, kick() gọi từ bên trong frame() (ví dụ setHovered khi ảnh trôi qua dưới con trỏ) lên lịch thêm một vòng, rồi
	// cuối frame() lại lên lịch vòng nữa: số vòng song song nhân lên theo mỗi lần đổi hover (đo được 2400 lần rAF/giây), mỗi vòng đều
	// render WebGL, làm Chrome tăng bộ nhớ, treo và GPU reset.
	let inFrame = false;
	const kick = () => {
		if ( ! raf && ! inFrame && running() ) {
			prevT = 0;
			raf = win.requestAnimationFrame( frame );
		}
	};
	const halt = () => {
		if ( raf ) {
			win.cancelAnimationFrame( raf );
			raf = 0;
		}
	};
	const touchInput = ( dir ) => {
		st.last = now();
		if ( dir ) {
			st.dir = dir;
		}
		stage.classList.add( 'is-used' );
		kick();
	};

	/* --- Bố cục --------------------------------------------------------------------------------------------------------------------- */
	function layout() {
		const r = stage.getBoundingClientRect();
		const nw = Math.max( 1, Math.round( r.width ) );
		const nh = Math.max( 1, Math.round( r.height ) );
		const nd = Math.min( win.devicePixelRatio || 1, lite ? C.DPR_LITE : C.DPR );
		// setSize xoá canvas kể cả khi giá trị như cũ (chớp đen khi thanh địa chỉ điện thoại ẩn / hiện): chỉ gọi khi đổi thật.
		if ( nw === W && nh === H && nd === dpr ) {
			return;
		}
		W = nw;
		H = nh;
		dpr = nd;
		renderer.setPixelRatio( dpr );
		renderer.setSize( W, H, false );
		camera.aspect = W / H;
		camera.updateProjectionMatrix();

		// Kích thước (px, cạnh lớn nhất) từ START_PX ở tâm tới endPx ở mép; quãng đường tâm -> mép theo hướng đi của từng ảnh.
		endPx = Math.min( C.END_FILL * H, C.END_FILL_W * W );
		startPx = Math.min( START_PX, endPx );
		for ( const it of items ) {
			const ax = Math.max( Math.abs( it.dx ), 1e-4 );
			const ay = Math.max( Math.abs( it.dy ), 1e-4 );
			it.reach = Math.min( W / 2 / ax, H / 2 / ay ) * C.EDGE_REACH;
		}

		// Bụi sáng quanh khung chân dung: tâm và nửa kích thước lấy từ chính khung HTML (kích thước layout, không tính scale đang chạy);
		// vùng chức danh và tên bên dưới cũng được đo để bụi mờ đi ở đó, không làm rối chữ.
		if ( portraitFrame ) {
			const cx0 = r.left + r.width / 2;
			const cy0 = r.top + r.height / 2;
			const fr = portraitFrame.getBoundingClientRect();
			const cr = caption ? caption.getBoundingClientRect() : null;
			dust.resize( {
				cx: fr.left + fr.width / 2 - cx0,
				cy: -( fr.top + fr.height / 2 - cy0 ),
				halfW: portraitFrame.offsetWidth / 2,
				halfH: portraitFrame.offsetHeight / 2,
				capX: cr ? cr.left + cr.width / 2 - cx0 : 0,
				capY: cr ? -( cr.top + cr.height / 2 - cy0 ) : 0,
				capHalfW: cr ? cr.width / 2 : 0,
				capHalfH: cr ? cr.height / 2 : 0,
				wpp: ( 2 * C.FOCUS_DIST * tanHalf ) / H,
				dpr,
			} );
		}
		dirty = true;
	}

	/* --- Texture ------------------------------------------------------------------------------------------------------------------ */
	const loader = new THREE.TextureLoader();

	function unload( it ) {
		it.token++;
		if ( it.tex ) {
			it.tex.dispose();
			it.tex = null;
		}
		it.u.uMap.value = null;
		it.state = 'idle';
		it.fade = 0;
		it.mesh.visible = false;
	}

	function load( it ) {
		const token = ++it.token;
		it.state = 'loading';
		loading++;
		loader.load(
			lite ? it.img.lite : it.img.src,
			( tex ) => {
				loading--;
				if ( dead || token !== it.token ) {
					tex.dispose();
					return;
				}
				tex.colorSpace = THREE.SRGBColorSpace;
				tex.anisotropy = aniso;
				it.tex = tex;
				// Giải mã ảnh trước (ngoài luồng chính nếu trình duyệt hỗ trợ), rồi mới đưa lên GPU, mỗi khung hình một ảnh.
				const pre = tex.image && 'function' === typeof tex.image.decode ? tex.image.decode().catch( () => {} ) : Promise.resolve();
				pre.then( () => {
					if ( ! dead && token === it.token ) {
						it.state = 'decoded';
						uploads.push( it );
						kick();
					}
				} );
			},
			undefined,
			() => {
				loading--;
				if ( token === it.token ) {
					it.state = 'error';
				}
			}
		);
	}

	function manageTextures() {
		const want = [];
		for ( const it of items ) {
			const d = it.i - st.pos;
			// Ảnh còn trên đường: -LIFE/2 <= d <= LIFE/2 (d = i - pos). Tải sớm 1,5 ảnh trước khi nhú, giữ thêm TEX_KEEP_EXTRA khi đã đi hết.
			const need = d > -LIFE / 2 - 1 && d < LIFE / 2 + 1.5;
			const keep = d > -LIFE / 2 - C.TEX_KEEP_EXTRA && d < LIFE / 2 + 1.5 + C.TEX_KEEP_EXTRA;
			if ( need && 'idle' === it.state ) {
				want.push( it );
			} else if ( ! keep && 'idle' !== it.state && 'error' !== it.state ) {
				unload( it );
			}
		}
		want.sort( ( a, b ) => Math.abs( a.i - st.pos ) - Math.abs( b.i - st.pos ) );
		while ( loading < C.TEX_LOADS && want.length ) {
			load( want.shift() );
		}
		// Đưa lên GPU một ảnh mỗi khung hình (tránh giật khi nhiều ảnh tải xong cùng lúc).
		while ( uploads.length ) {
			const it = uploads.shift();
			if ( 'decoded' === it.state && it.tex ) {
				renderer.initTexture( it.tex );
				it.u.uMap.value = it.tex;
				it.state = 'ready';
				it.fade = 0;
				dirty = true;
				break;
			}
		}
	}

	/* --- Chuyển động -------------------------------------------------------------------------------------------------------------- */
	function startSettle() {
		const b = C.SETTLE_BIAS;
		let to = st.dir > 0 ? Math.ceil( st.pos - b ) : Math.floor( st.pos + b );
		// Sau ảnh cuối chỉ có một nhịp: ảnh chân dung (END); các bước giữa (ảnh cuối đang đi nốt ra mép) không phải điểm dừng.
		if ( to > N - 1 ) {
			to = st.dir > 0 ? END : N - 1;
		}
		st.target = clamp( to, 0, END );
		st.mode = 'seek';
		st.cap = C.AUTO_SPEED;
	}

	// Vị trí nghỉ "đẹp": số nguyên từ ảnh đầu tới ảnh cuối (ảnh hiện hành ở giữa đường) hoặc ảnh chân dung (END). Quãng giữa ảnh cuối và END
	// là lúc ảnh cuối đang đi nốt ra mép, không dừng ở đó.
	const isNice = ( p ) => p >= END - 1e-3 || ( p <= N - 1 + 1e-3 && Math.abs( p - Math.round( p ) ) < 1e-3 );

	// Đi tới đích `target` với tốc độ tối đa `cap` (= AUTO_SPEED: cùng tốc độ tự trôi), tăng và giảm tốc êm theo SEEK_ACCEL: vận tốc mong
	// muốn là căn bậc hai của quãng đường còn lại (giảm tốc đều) bị kẹp ở `cap`; vận tốc thật bám theo với gia tốc giới hạn.
	function seek( dt ) {
		const diff = st.target - st.pos;
		const want = Math.sign( diff ) * Math.min( st.cap, Math.sqrt( 2 * C.SEEK_ACCEL * Math.abs( diff ) ) );
		const dv = C.SEEK_ACCEL * dt;
		st.v += clamp( want - st.v, -dv, dv );
		st.pos += st.v * dt;
		// Tới nơi hoặc vượt đích: dừng đúng đích.
		if ( Math.abs( diff ) < 0.002 || ( st.target - st.pos ) * diff <= 0 ) {
			st.pos = st.target;
			st.v = 0;
			st.mode = 'rest';
			st.cap = C.AUTO_SPEED;
			if ( ! isNice( st.pos ) ) {
				startSettle(); // lăn chuột dừng ở giữa hai ảnh: trượt nốt tới nhịp gần nhất
			}
		}
	}

	function step( dt, t ) {
		const idle = ( t - st.last ) / 1000;
		const before = st.pos;

		if ( 'free' === st.mode ) {
			st.pos += st.v * dt;
			st.v *= Math.exp( -C.FRICTION * dt );
			if ( idle > C.SETTLE_AFTER && Math.abs( st.v ) < 0.3 ) {
				startSettle();
			}
		} else if ( 'seek' === st.mode ) {
			seek( dt );
		} else if ( 'auto' === st.mode ) {
			const hovered = st.hovered >= 0;
			st.v += ( ( hovered ? 0 : C.AUTO_SPEED ) - st.v ) * damp( hovered ? C.HOVER_DAMP : C.AUTO_ACCEL, dt );
			st.pos += st.v * dt;
		}
		// Tự trôi (Zen): một chu kỳ tới ảnh chân dung rồi dừng.
		if ( 'rest' === st.mode && idle > C.AUTO_IDLE && st.pos < END - 1e-3 && st.hovered < 0 ) {
			st.mode = 'auto';
			st.v = 0;
		}

		// Hai đầu: kẹp (không còn quy tắc trả quyền cuộn cho trang: lăn chuột trong khung luôn thuộc về gallery).
		let edge = 0;
		if ( st.pos <= 0 ) {
			st.pos = 0;
			edge = -1;
		} else if ( st.pos >= END ) {
			st.pos = END;
			edge = 1;
			if ( 'auto' === st.mode ) {
				st.mode = 'rest';
			}
		}
		if ( edge && 'free' === st.mode && Math.sign( st.v ) === edge ) {
			st.v = 0;
		}
		// Vận tốc hiển thị (cho lực uốn) khi pos do kéo quyết định.
		if ( 'drag' === st.mode || 'rest' === st.mode ) {
			st.v = dt > 0 ? ( st.pos - before ) / dt : 0;
		}
		const f = clamp( st.v * C.FORCE_SCALE, -C.FORCE_MAX, C.FORCE_MAX );
		force += ( f - force ) * damp( 10, dt );
		if ( Math.abs( force ) < 1e-4 ) {
			force = 0;
		}
	}

	function updateItems( dt, t ) {
		let anim = false;
		// Chân dung là điểm dừng độc tôn: chân dung hiện tới đâu thì mọi ảnh gallery còn lại tắt tới đó (hết hẳn khi chân dung hiện đủ).
		const veil = 1 - portraitAmount();
		for ( const it of items ) {
			const tp = 0.5 + ( st.pos - it.i ) / LIFE; // tiến độ đi của ảnh: 0 = ở tâm, 1 = chạm mép khung
			let op = 0;
			// Chỉ ảnh có tp trong [0, 1) mới hiện: một ảnh chiếm đúng 1/LIFE quãng đường cho mỗi bước nên không bao giờ quá LIFE ảnh cùng lúc.
			if ( tp >= 0 && tp < 1 ) {
				if ( tp < C.RISE_END ) {
					op = C.OPA_START + ( 1 - C.OPA_START ) * smooth( 0, C.RISE_END, tp );
				} else {
					op = 1 - smooth( C.FADE_START, 1, tp );
				}
				op *= Math.min( 1, tp / C.PRE_T ) * veil; // hiện dần trong PRE_T đầu (không bật hình đột ngột ở tâm)
			}
			if ( 'ready' === it.state && it.fade < 1 ) {
				it.fade = Math.min( 1, it.fade + dt / 0.45 );
				anim = true;
			}
			it.opacity = op * ( 'ready' === it.state ? it.fade : 0 );
			const show = it.opacity > 0.002;
			it.mesh.visible = show;
			if ( ! show ) {
				it.hover = 0;
				continue;
			}
			const tc = clamp( tp, 0, 1 );
			// Mặt phẳng ảnh cách camera dist (ảnh cũ gần hơn một chút để xếp lớp); wpp = đơn vị thế giới trên mỗi px CSS của khung tại độ sâu đó.
			const dist = C.FOCUS_DIST - tc * C.Z_LIFT;
			const wpp = ( 2 * dist * tanHalf ) / H;
			const side = startPx + ( endPx - startPx ) * Math.pow( tc, C.SIZE_EASE );
			const wPx = it.aspect >= 1 ? side : side * it.aspect;
			const hPx = it.aspect >= 1 ? side / it.aspect : side;
			const run = Math.pow( tc, C.PATH_EASE ) * it.reach;
			it.mesh.scale.set( wPx * wpp, hPx * wpp, 1 );
			it.mesh.position.set( it.dx * run * wpp, it.dy * run * wpp, -dist );
			it.mesh.renderOrder = Math.round( tc * 1000 ); // ảnh đã đi xa (cũ) vẽ sau cùng, nằm trên
			const u = it.u;
			u.uSize.value.set( wPx * wpp, hPx * wpp );
			u.uRadius.value = C.RADIUS_PX * wpp;
			u.uOpacity.value = it.opacity;
			u.uDim.value = C.DIM_FAR * ( 1 - smooth( 0, C.RISE_END, tp ) );
			u.uBlur.value = lite ? 0 : C.MAX_BLUR * Math.max( 1 - smooth( 0, C.BLUR_IN, tp ), C.BLUR_OUT * smooth( C.FADE_START, 1, tp ) );
			u.uForce.value = force;
			if ( hoverOn ) {
				const goal = it.i === st.hovered ? 1 : 0;
				if ( it.hover !== goal ) {
					it.hover += ( goal - it.hover ) * damp( C.HOVER_RATE, dt );
					if ( Math.abs( goal - it.hover ) < 0.002 ) {
						it.hover = goal;
					}
					anim = true;
				}
				u.uHover.value = it.hover;
				if ( it.hover > 0 ) {
					u.uTime.value = t / 1000;
					anim = true; // vệt ánh kim chạy liên tục khi đang hover
				}
			}
		}
		// Hạt bụi quanh ảnh chân dung: chuyển động liên tục nên cần vẽ mỗi khung hình khi đang hiện.
		if ( dust.update( portraitAmount(), t / 1000 ) ) {
			anim = true;
		}
		return anim;
	}

	function setVar( key, name, value ) {
		const v = Math.round( value * 1000 ) / 1000;
		if ( hud[ key ] !== v ) {
			hud[ key ] = v;
			stage.style.setProperty( name, String( v ) );
		}
	}

	function updateHud() {
		const fp = portraitAmount();
		setVar( 'fp', '--fp', fp );
		setVar( 'fc', '--fc', smooth( END - C.CAPTION_FROM * LIFE, END, st.pos ) );
		setVar( 'prog', '--fprog', st.pos / END );
		stage.classList.toggle( 'is-end', fp > 0.5 ); // CSS: bộ đếm thành "Nhà sáng lập", vệt ánh kim của khung chân dung chạy
		// Bộ đếm Editorial: ảnh hiện hành (ảnh đang ở giữa đường đi), 01..N.
		const n = Math.min( N, Math.floor( st.pos + 0.5 ) + 1 );
		if ( nowEl && n !== hud.n ) {
			hud.n = n;
			nowEl.textContent = String( n ).padStart( 2, '0' );
		}
	}

	/* --- Dò ảnh dưới con trỏ (hover, click) --------------------------------------------------------------------------------------- */
	const raycaster = new THREE.Raycaster();
	const ndc = new THREE.Vector2();
	function hitAt( x, y ) {
		const r = canvas.getBoundingClientRect();
		if ( ! r.width || ! r.height ) {
			return -1;
		}
		ndc.set( ( ( x - r.left ) / r.width ) * 2 - 1, -( ( y - r.top ) / r.height ) * 2 + 1 );
		raycaster.setFromCamera( ndc, camera );
		const list = [];
		for ( const it of items ) {
			if ( it.mesh.visible && it.opacity >= C.HOVER_MIN_OPACITY ) {
				it.mesh.updateMatrixWorld();
				list.push( it.mesh );
			}
		}
		const hits = raycaster.intersectObjects( list, false );
		return hits.length ? hits[ 0 ].object.userData.index : -1;
	}

	function setHovered( i ) {
		if ( i === st.hovered ) {
			return;
		}
		st.hovered = i;
		if ( i < 0 ) {
			st.last = Math.max( st.last, now() - C.AUTO_IDLE * 1000 ); // rời ảnh: tự trôi (nếu đang trôi) chạy tiếp ngay
		}
		updateCursor();
		kick();
	}

	function updateCursor() {
		if ( fine ) {
			stage.style.cursor = stage.classList.contains( 'is-dragging' ) ? 'grabbing' : st.hovered >= 0 ? 'pointer' : 'grab';
		}
	}

	/* --- Vòng vẽ ------------------------------------------------------------------------------------------------------------------ */
	function frame( t ) {
		raf = 0;
		if ( ! running() ) {
			return;
		}
		inFrame = true;
		try {
			tick( t );
		} finally {
			inFrame = false;
		}
		// Lên lịch khung sau đúng một lần (halt() / destroy() trong khung này thì running() đã false).
		if ( ! raf && running() ) {
			raf = win.requestAnimationFrame( frame );
		}
	}

	function tick( t ) {
		const dt = prevT ? Math.min( 0.05, ( t - prevT ) / 1000 ) : 1 / 60;
		prevT = t;
		const pos0 = st.pos;
		const force0 = force;

		step( dt, t );
		manageTextures();
		const moved = st.pos !== pos0 || force !== force0;
		if ( hoverOn && pointer && ! ptr && ( pointerDirty || moved ) ) {
			pointerDirty = false;
			setHovered( hitAt( pointer.x, pointer.y ) );
		}
		const anim = updateItems( dt, t );
		if ( moved || anim || dirty ) {
			renderer.render( scene, camera );
			dirty = false;
			updateHud();
			if ( ! stage.classList.contains( 'is-ready' ) && items[ 0 ].fade > 0 ) {
				stage.classList.add( 'is-ready' );
			}
		}
	}

	/* --- Lăn chuột ---------------------------------------------------------------------------------------------------------------- */
	// Lăn chuột trong khung LUÔN thuộc về gallery, kể cả khi khung mới hiện một phần hoặc đã ở ảnh đầu / ảnh chân dung (đã bỏ hai quy tắc
	// "trả quyền cuộn ở hai đầu" và "chỉ giữ cuộn khi section vào vị trí"). Muốn cuộn trang thì đưa con trỏ ra ngoài khung (cột chữ bên phải).
	// Mỗi lần lăn dời ĐÍCH đi WHEEL_TRAVEL x px ảnh; ảnh đi tới đích với tốc độ AUTO_SPEED x WHEEL_SPEED (1,25 lần tự trôi), không nhanh hơn theo cú lăn.
	function seekBy( d, dir ) {
		const base = 'seek' === st.mode ? st.target : st.pos;
		// Đích không cách vị trí hiện tại quá QUEUE_MAX ảnh: lăn dồn dập cũng không đẩy ảnh đi mãi sau khi ngừng lăn.
		st.target = clamp( base + d, Math.max( 0, st.pos - C.QUEUE_MAX ), Math.min( END, st.pos + C.QUEUE_MAX ) );
		st.mode = 'seek';
		st.cap = C.AUTO_SPEED * C.WHEEL_SPEED; // lăn chuột nhanh hơn tự trôi WHEEL_SPEED lần
		touchInput( dir );
	}

	function onWheel( e ) {
		if ( dead || lost || e.ctrlKey ) {
			return; // Ctrl + lăn là phóng to trang
		}
		const unit = 1 === e.deltaMode ? 16 : 2 === e.deltaMode ? H || win.innerHeight : 1;
		let dx = e.deltaX * unit;
		let dy = e.deltaY * unit;
		if ( e.shiftKey && Math.abs( dx ) < 0.5 ) {
			dx = dy;
			dy = 0;
		}
		const delta = Math.abs( dx ) > Math.abs( dy ) ? dx : dy;
		if ( ! delta ) {
			return;
		}
		e.preventDefault();
		const dir = delta > 0 ? 1 : -1;
		if ( dir > 0 ? st.pos >= END - 1e-3 : st.pos <= 1e-3 ) {
			st.last = now(); // đã ở đầu hoặc cuối: nuốt sự kiện, không đi
			return;
		}
		seekBy( delta * C.WHEEL_TRAVEL, dir );
	}

	/* --- Bàn phím ----------------------------------------------------------------------------------------------------------------- */
	function announce( to ) {
		if ( ! live ) {
			return;
		}
		if ( to > N - 1 ) {
			live.textContent = stage.getAttribute( 'data-live-end' ) || '';
			return;
		}
		const tpl = stage.getAttribute( 'data-live' ) || '%1$s / %2$s: %3$s';
		live.textContent = tpl.replace( '%1$s', String( to + 1 ) ).replace( '%2$s', String( N ) ).replace( '%3$s', images[ to ].alt || '' );
	}

	function onKey( e ) {
		if ( e.altKey || e.ctrlKey || e.metaKey || e.target !== stage ) {
			return;
		}
		const k = e.key;
		const base = 'seek' === st.mode ? st.target : Math.round( st.pos );
		let to = null;
		if ( 'ArrowRight' === k || 'ArrowDown' === k ) {
			if ( 'ArrowDown' === k && base >= END ) {
				return; // đã ở ảnh chân dung: mũi tên xuống cuộn trang
			}
			to = base + 1;
			if ( to > N - 1 ) {
				to = END; // sau ảnh cuối là ảnh chân dung
			}
		} else if ( 'ArrowLeft' === k || 'ArrowUp' === k ) {
			if ( 'ArrowUp' === k && base <= 0 ) {
				return;
			}
			to = base - 1;
			if ( to > N - 1 ) {
				to = N - 1; // từ ảnh chân dung lùi về ảnh cuối
			}
		} else if ( 'Home' === k ) {
			to = 0;
		} else if ( 'End' === k ) {
			to = END;
		} else if ( 'Enter' === k || ' ' === k ) {
			const i = Math.round( st.pos );
			if ( i < N && Math.abs( st.pos - i ) < 0.35 ) {
				e.preventDefault();
				openImage( i );
			}
			return;
		} else {
			return;
		}
		e.preventDefault();
		to = clamp( to, 0, END );
		st.target = to;
		st.mode = 'seek';
		st.cap = 'Home' === k || 'End' === k ? C.AUTO_SPEED * C.FAST_MULT : C.AUTO_SPEED; // Home / End nhảy xa nên đi nhanh hơn
		touchInput( to >= base ? 1 : -1 );
		announce( to );
	}

	/* --- Kéo, vuốt, chạm ---------------------------------------------------------------------------------------------------------- */
	function onDown( e ) {
		if ( dead || ptr ) {
			return;
		}
		if ( 'mouse' === e.pointerType ? 0 !== e.button : e.clientX < C.EDGE_GUARD_PX ) {
			return;
		}
		const t = now();
		ptr = { id: e.pointerId, x0: e.clientX, y0: e.clientY, t0: t, pos0: st.pos, axis: '', samples: [ [ t, e.clientX ] ] };
	}

	function onMove( e ) {
		if ( 'mouse' === e.pointerType ) {
			pointer = { x: e.clientX, y: e.clientY };
			pointerDirty = true;
			kick();
		}
		if ( ! ptr || e.pointerId !== ptr.id ) {
			return;
		}
		const dx = e.clientX - ptr.x0;
		const dy = e.clientY - ptr.y0;
		if ( ! ptr.axis ) {
			if ( Math.max( Math.abs( dx ), Math.abs( dy ) ) < C.AXIS_LOCK_PX ) {
				return;
			}
			ptr.axis = Math.abs( dx ) > Math.abs( dy ) ? 'x' : 'y';
			if ( 'x' !== ptr.axis ) {
				return; // vuốt dọc: trình duyệt cuộn trang (touch-action: pan-y), pointercancel sẽ tới
			}
			try {
				stage.setPointerCapture( ptr.id );
			} catch ( err ) {
				// Con trỏ đã kết thúc: bỏ qua.
			}
			ptr.x0 = e.clientX;
			ptr.pos0 = st.pos;
			st.mode = 'drag';
			setHovered( -1 );
			stage.classList.add( 'is-dragging' );
			updateCursor();
		}
		if ( 'x' !== ptr.axis ) {
			return;
		}
		e.preventDefault();
		const t = now();
		const ddx = e.clientX - ptr.x0;
		// Kéo sang trái (ngón tay từ phải qua trái) = tiến tới.
		st.pos = clamp( ptr.pos0 - ( ddx / W ) * C.DRAG_GAIN, 0, END );
		ptr.samples.push( [ t, e.clientX ] );
		if ( ptr.samples.length > 6 ) {
			ptr.samples.shift();
		}
		touchInput( ddx < 0 ? 1 : -1 );
	}

	function onUp( e ) {
		if ( ! ptr || e.pointerId !== ptr.id ) {
			return;
		}
		const p = ptr;
		ptr = null;
		if ( 'x' === p.axis ) {
			stage.classList.remove( 'is-dragging' );
			updateCursor();
			const a = p.samples[ 0 ];
			const b = p.samples[ p.samples.length - 1 ];
			const span = ( b[ 0 ] - a[ 0 ] ) / 1000;
			const vx = span > 0.01 && now() - b[ 0 ] < 90 ? ( b[ 1 ] - a[ 1 ] ) / span : 0;
			st.v = clamp( -( vx / W ) * C.DRAG_GAIN, -C.V_MAX, C.V_MAX );
			st.mode = 'free';
			touchInput( st.v > 0 ? 1 : st.v < 0 ? -1 : st.dir );
			return;
		}
		if ( ! p.axis && 'pointerup' === e.type && now() - p.t0 < C.TAP_MS && Math.hypot( e.clientX - p.x0, e.clientY - p.y0 ) < C.TAP_PX ) {
			const i = hitAt( e.clientX, e.clientY );
			if ( i >= 0 ) {
				touchInput( 0 );
				openImage( i );
			}
		}
	}

	function onLeave( e ) {
		if ( 'mouse' === e.pointerType ) {
			pointer = null;
			setHovered( -1 );
		}
	}

	/* --- Vòng đời ----------------------------------------------------------------------------------------------------------------- */
	const io = new IntersectionObserver( ( entries ) => {
		const was = visible;
		visible = entries[ entries.length - 1 ].isIntersecting;
		if ( visible && ! was ) {
			st.last = now(); // tự trôi bắt đầu 3 giây sau khi khung vào khung nhìn
			dirty = true;
			kick();
		} else if ( ! visible ) {
			halt();
		}
	} );
	const ro = new ResizeObserver( () => {
		layout();
		kick();
	} );
	const onVisibility = () => {
		if ( doc.hidden ) {
			halt();
		} else {
			dirty = true;
			kick();
		}
	};
	// Mất context: chờ trình duyệt khôi phục tối đa RESTORE_WAIT ms; quá hạn (hoặc mất lần hai) thì về bản tĩnh thay vì để khung đen.
	const RESTORE_WAIT = 3000;
	let restoreTimer = 0;
	const onLost = ( e ) => {
		e.preventDefault(); // cho phép trình duyệt khôi phục context
		lost = true;
		losses++;
		halt();
		win.clearTimeout( restoreTimer );
		if ( losses >= 2 ) {
			fail( 'mất context WebGL lần hai' );
		} else {
			restoreTimer = win.setTimeout( () => {
				if ( lost && ! dead ) {
					fail( 'context WebGL không được khôi phục' );
				}
			}, RESTORE_WAIT );
		}
	};
	const onRestored = () => {
		win.clearTimeout( restoreTimer );
		if ( dead ) {
			return;
		}
		// three dựng lại trạng thái GL của nó (kể cả màu xóa, về đen mặc định): đặt lại màu nền, đánh dấu texture để nạp lại lên GPU.
		lost = false;
		renderer.setClearColor( bg, 1 );
		for ( const it of items ) {
			if ( it.tex ) {
				it.tex.needsUpdate = true;
			}
		}
		dirty = true;
		kick();
	};

	const listeners = [
		[ stage, 'wheel', onWheel, { passive: false } ],
		[ stage, 'keydown', onKey ],
		[ stage, 'pointerdown', onDown ],
		[ stage, 'pointermove', onMove ],
		[ stage, 'pointerup', onUp ],
		[ stage, 'pointercancel', onUp ],
		[ stage, 'pointerleave', onLeave ],
		[ doc, 'visibilitychange', onVisibility ],
		[ canvas, 'webglcontextlost', onLost ],
		[ canvas, 'webglcontextrestored', onRestored ],
	];
	listeners.forEach( ( [ el, type, fn, opt ] ) => el.addEventListener( type, fn, opt ) );

	function destroy() {
		if ( dead ) {
			return;
		}
		dead = true;
		halt();
		io.disconnect();
		ro.disconnect();
		listeners.forEach( ( [ el, type, fn, opt ] ) => el.removeEventListener( type, fn, opt ) );
		for ( const it of items ) {
			if ( it.tex ) {
				it.tex.dispose();
			}
			it.material.dispose();
		}
		geometry.dispose();
		dust.dispose();
		renderer.dispose();
		renderer.forceContextLoss();
		canvas.remove();
		stage.style.cursor = '';
		[ '--fp', '--fc', '--fprog' ].forEach( ( name ) => stage.style.removeProperty( name ) );
		stage.classList.remove( 'is-ready', 'is-end', 'is-dragging' );
	}

	function fail( reason ) {
		if ( win.console ) {
			win.console.warn( '[ISHA founder] ' + reason + ': chuyển sang bản tĩnh.' );
		}
		destroy();
		if ( onFail ) {
			onFail( reason );
		}
	}

	layout();
	updateCursor();
	io.observe( stage );
	ro.observe( stage );

	return {
		pause( on ) {
			paused = !! on;
			if ( paused ) {
				halt();
				if ( ptr ) {
					ptr = null;
					stage.classList.remove( 'is-dragging' );
				}
			} else {
				st.last = now();
				dirty = true;
				kick();
			}
		},
		destroy,
	};
}
