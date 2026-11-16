/**
 * Bản đồ độ sâu của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 3.1, 3.2). Quy ước: 0 = gần, 1 = xa.
 *
 * Với ảnh núi chìm sương, rặng càng xa càng nhiều sương chắn nên càng sáng: độ sáng tỉ lệ thuận với khoảng cách. Tiền xử lý MỘT LẦN lúc
 * nạp (và khi debug đổi bán kính làm mượt), không mỗi khung:
 *   1. ảnh nguồn → render target 1/4: độ sáng Rec. 709 trên giá trị sRGB (cùng cách tính với số đo ở bước 1, README mục 6 – 8, để ngưỡng đo được dùng
 *      thẳng); mỗi texel là trung bình 4 × 4 texel gốc (4 lượt lấy mẫu song tuyến);
 *   2. Gaussian tách trục (ngang rồi dọc), bán kính CONFIG.depth.blur × chiều rộng (khoảng 1.5%), sigma = bán kính / 2: khử vân đá và nhiễu
 *      để lưới không gồ ghề;
 *   3. `texture` (kênh R) = độ sáng đã làm mượt. Ánh xạ sang độ sâu nằm trong shader (DEPTH_GLSL: smoothstep + gamma) nên ba núm uLumNear,
 *      uLumFar, uDepthGamma chỉnh trực tiếp, không phải tiền xử lý lại.
 * Bản đồ vẽ sẵn (setMap(url), ví dụ assets/img/scene/depth.webp, đặc tả 3.2): thay cho độ sáng, giá trị dùng thẳng làm độ sâu, vẫn làm
 * mượt nhẹ (CONFIG.depth.mapBlur). Nguồn đang dùng được log ra console.
 *
 * createView(): chế độ xem để duyệt bản đồ (đặc tả 17.4): ảnh gốc, độ sâu thang xám toàn khung, đường đồng mức độ sâu chồng lên ảnh, địa
 * hình nổi (hillshade: lộ ngay chỗ gồ ghề), chia đôi ảnh | độ sâu. Ảnh luôn hiện trọn (contain).
 */

const LOG = '[ISHA svc]';

/** GLSL dùng chung cho mọi shader đọc độ sâu (lưới địa hình, chế độ xem). textureLod: hợp lệ cả trong vertex shader. */
export const DEPTH_GLSL = /* glsl */ `
uniform sampler2D uDepthTex;
uniform float uLumNear;
uniform float uLumFar;
uniform float uDepthGamma;
uniform float uDepthIsMap;
float sceneDepth( vec2 uv ) {
	float s = textureLod( uDepthTex, uv, 0.0 ).r;
	float d = uDepthIsMap > 0.5 ? s : smoothstep( uLumNear, uLumFar, s );
	return pow( clamp( d, 0.0, 1.0 ), uDepthGamma );
}
`;

/** Tuyến tính → sRGB (chuẩn IEC 61966-2-1), để tính độ sáng trên giá trị ảnh như người xem thấy. */
export const SRGB_GLSL = /* glsl */ `
vec3 toSrgb( vec3 c ) {
	c = max( c, 0.0 );
	return mix( c * 12.92, 1.055 * pow( c, vec3( 1.0 / 2.4 ) ) - 0.055, step( 0.0031308, c ) );
}
`;

const FULLSCREEN_VS = /* glsl */ `
varying vec2 vUv;
void main() {
	vUv = position.xy * 0.5 + 0.5;
	gl_Position = vec4( position.xy, 0.0, 1.0 );
}
`;

const LUM_FS = /* glsl */ `
uniform sampler2D uSrc;
uniform vec2 uSrcTexel;
uniform float uIsMap;
varying vec2 vUv;
${ SRGB_GLSL }
float val( vec2 uv ) {
	vec3 c = textureLod( uSrc, uv, 0.0 ).rgb;
	return uIsMap > 0.5 ? c.r : dot( toSrgb( c ), vec3( 0.2126, 0.7152, 0.0722 ) );
}
void main() {
	vec2 o = uSrcTexel;
	float v = 0.25 * ( val( vUv - o ) + val( vUv + vec2( o.x, -o.y ) ) + val( vUv + vec2( -o.x, o.y ) ) + val( vUv + o ) );
	gl_FragColor = vec4( v, v, v, 1.0 );
}
`;

const BLUR_FS = /* glsl */ `
uniform sampler2D uSrc;
uniform vec2 uDir;
uniform float uRadius;
varying vec2 vUv;
void main() {
	float sigma = max( uRadius * 0.5, 0.5 );
	float k = -0.5 / ( sigma * sigma );
	float sum = textureLod( uSrc, vUv, 0.0 ).r;
	float wsum = 1.0;
	for ( int i = 1; i <= 128; i++ ) {
		float x = float( i );
		if ( x > uRadius ) {
			break;
		}
		float w = exp( x * x * k );
		sum += w * ( textureLod( uSrc, vUv + uDir * x, 0.0 ).r + textureLod( uSrc, vUv - uDir * x, 0.0 ).r );
		wsum += 2.0 * w;
	}
	float v = sum / wsum;
	gl_FragColor = vec4( v, v, v, 1.0 );
}
`;

export const VIEW_MODES = Object.freeze( [ 'photo', 'depth', 'contour', 'relief', 'split' ] );

const VIEW_FS = /* glsl */ `
uniform sampler2D uPhoto;
uniform int uMode;
uniform float uMix;
uniform float uStep;
uniform float uSplit;
uniform float uRelief;
uniform vec2 uDepthTexel;
uniform vec4 uFit;
uniform float uBg;
varying vec2 vUv;
${ SRGB_GLSL }
${ DEPTH_GLSL }
void main() {
	vec2 uv = vUv * uFit.xy + uFit.zw;
	if ( uv.x < 0.0 || uv.x > 1.0 || uv.y < 0.0 || uv.y > 1.0 ) {
		gl_FragColor = vec4( vec3( uBg ), 1.0 );
		return;
	}
	vec3 photo = toSrgb( textureLod( uPhoto, uv, 0.0 ).rgb );
	float pl = dot( photo, vec3( 0.2126, 0.7152, 0.0722 ) );
	float d = sceneDepth( uv );
	vec3 col = vec3( d );
	if ( uMode == 0 ) {
		col = photo;
	} else if ( uMode == 2 ) {
		// Đường đồng mức độ sâu chồng lên ảnh: đường phải bám theo đường viền rặng núi, không cắt ngang sườn.
		float base = mix( d, pl, uMix );
		float t = d / uStep;
		float fw = fwidth( t );
		float f = abs( fract( t - 0.5 ) - 0.5 ) / max( fw, 1e-4 );
		// Vùng độ sâu bị kẹp (đúng 0 hoặc 1: tối hơn uLumNear, sáng hơn uLumFar) là mặt phẳng, không có đường: bỏ để khỏi tô kín cả mảng.
		float line = ( 1.0 - smoothstep( 0.5, 1.5, f ) ) * step( 1e-5, fw );
		col = vec3( mix( base, base > 0.45 ? 0.0 : 1.0, line * 0.9 ) );
	} else if ( uMode == 3 ) {
		// Địa hình nổi: coi độ sâu là bề mặt, chiếu sáng nghiêng; vân đá còn sót hiện thành gợn.
		float dx = sceneDepth( uv + vec2( uDepthTexel.x, 0.0 ) ) - sceneDepth( uv - vec2( uDepthTexel.x, 0.0 ) );
		float dy = sceneDepth( uv + vec2( 0.0, uDepthTexel.y ) ) - sceneDepth( uv - vec2( 0.0, uDepthTexel.y ) );
		vec3 n = normalize( vec3( -dx * uRelief, -dy * uRelief, 1.0 ) );
		float sh = clamp( 0.15 + 0.85 * dot( n, normalize( vec3( 0.55, 0.45, 0.7 ) ) ), 0.0, 1.0 );
		col = vec3( mix( sh, pl, uMix ) );
	} else if ( uMode == 4 ) {
		col = vUv.x < uSplit ? photo : vec3( d );
		col = mix( col, vec3( 1.0 ), 1.0 - smoothstep( 0.0, 1.5, abs( gl_FragCoord.x - uSplit / max( fwidth( vUv.x ), 1e-6 ) ) ) );
	}
	gl_FragColor = vec4( col, 1.0 ); // giá trị hiển thị trực tiếp: không tone mapping, không đổi không gian màu
}
`;

function fullscreenGeometry( THREE ) {
	const g = new THREE.BufferGeometry();
	g.setAttribute( 'position', new THREE.Float32BufferAttribute( [ -1, -1, 0, 3, -1, 0, -1, 3, 0 ], 3 ) );
	return g;
}

/**
 * @param {object} THREE
 * @param {{renderer:object, rtType:number}} ctx Kết quả createRenderer.
 * @param {{photoUrl:string, mapUrl?:string|null, cfg:{lumNear:number, lumFar:number, gamma:number, blur:number, mapBlur:number}}} opts
 *   cfg: CONFIG.depth (đọc trực tiếp; gọi sync() sau khi đổi núm, rebuild() sau khi đổi bán kính làm mượt).
 */
export async function createDepth( THREE, ctx, { photoUrl, mapUrl = null, cfg } ) {
	const { renderer, rtType } = ctx;
	const loader = new THREE.TextureLoader();
	const photo = await loader.loadAsync( photoUrl );
	photo.colorSpace = THREE.SRGBColorSpace;
	photo.minFilter = THREE.LinearMipmapLinearFilter;
	photo.magFilter = THREE.LinearFilter;
	photo.generateMipmaps = true;
	photo.anisotropy = Math.min( 8, renderer.capabilities.getMaxAnisotropy() );
	const W = photo.image.width;
	const H = photo.image.height;
	const dw = Math.ceil( W / 4 );
	const dh = Math.ceil( H / 4 );

	const rtOpts = {
		type: rtType,
		format: THREE.RGBAFormat,
		depthBuffer: false,
		stencilBuffer: false,
		generateMipmaps: false,
		minFilter: THREE.LinearFilter,
		magFilter: THREE.LinearFilter,
		wrapS: THREE.ClampToEdgeWrapping,
		wrapT: THREE.ClampToEdgeWrapping,
	};
	const rtA = new THREE.WebGLRenderTarget( dw, dh, rtOpts );
	const rtB = new THREE.WebGLRenderTarget( dw, dh, rtOpts );
	rtA.texture.name = 'isha-depth';

	const geo = fullscreenGeometry( THREE );
	const lumMat = new THREE.ShaderMaterial( {
		uniforms: { uSrc: { value: null }, uSrcTexel: { value: new THREE.Vector2() }, uIsMap: { value: 0 } },
		vertexShader: FULLSCREEN_VS,
		fragmentShader: LUM_FS,
		depthTest: false,
		depthWrite: false,
	} );
	const blurMat = new THREE.ShaderMaterial( {
		uniforms: { uSrc: { value: null }, uDir: { value: new THREE.Vector2() }, uRadius: { value: 1 } },
		vertexShader: FULLSCREEN_VS,
		fragmentShader: BLUR_FS,
		depthTest: false,
		depthWrite: false,
	} );
	const passMesh = new THREE.Mesh( geo, lumMat );
	passMesh.frustumCulled = false;
	const passScene = new THREE.Scene();
	passScene.add( passMesh );
	const passCam = new THREE.Camera();

	const uniforms = {
		uDepthTex: { value: rtA.texture },
		uLumNear: { value: cfg.lumNear },
		uLumFar: { value: cfg.lumFar },
		uDepthGamma: { value: cfg.gamma },
		uDepthIsMap: { value: 0 },
	};

	let map = null;
	let mapLabel = '';

	function pass( material, target ) {
		passMesh.material = material;
		renderer.setRenderTarget( target );
		renderer.render( passScene, passCam );
	}

	/** Chạy lại tiền xử lý (nguồn hiện tại, bán kính hiện tại của cfg). */
	function rebuild() {
		const src = map || photo;
		const prev = renderer.getRenderTarget();
		lumMat.uniforms.uSrc.value = src;
		lumMat.uniforms.uSrcTexel.value.set( 1 / src.image.width, 1 / src.image.height );
		lumMat.uniforms.uIsMap.value = map ? 1 : 0;
		pass( lumMat, rtA );
		const radius = Math.max( 0, ( map ? cfg.mapBlur : cfg.blur ) * dw );
		blurMat.uniforms.uRadius.value = radius;
		blurMat.uniforms.uSrc.value = rtA.texture;
		blurMat.uniforms.uDir.value.set( 1 / dw, 0 );
		pass( blurMat, rtB );
		blurMat.uniforms.uSrc.value = rtB.texture;
		blurMat.uniforms.uDir.value.set( 0, 1 / dh );
		pass( blurMat, rtA );
		renderer.setRenderTarget( prev );
		uniforms.uDepthIsMap.value = map ? 1 : 0;
		return radius;
	}

	function sync() {
		uniforms.uLumNear.value = cfg.lumNear;
		uniforms.uLumFar.value = Math.max( cfg.lumFar, cfg.lumNear + 1e-3 );
		uniforms.uDepthGamma.value = cfg.gamma;
	}

	const api = {
		photo,
		texture: rtA.texture,
		uniforms,
		size: Object.freeze( { width: W, height: H } ),
		aspect: W / H,
		depthSize: Object.freeze( { width: dw, height: dh } ),
		get source() {
			return map ? `bản đồ vẽ sẵn (${ mapLabel })` : 'suy từ độ sáng của ảnh';
		},
		sync,
		rebuild,
		/**
		 * Đổi nguồn độ sâu: url của bản đồ vẽ sẵn, hoặc null = suy từ độ sáng. Lỗi nạp: giữ độ sáng, cảnh báo.
		 * @return {Promise<string>} Nguồn đang dùng.
		 */
		async setMap( url ) {
			if ( map ) {
				map.dispose();
				map = null;
			}
			if ( url ) {
				try {
					const t = await loader.loadAsync( url );
					t.colorSpace = THREE.NoColorSpace;
					t.generateMipmaps = false;
					t.minFilter = THREE.LinearFilter;
					map = t;
					mapLabel = url.split( '/' ).pop();
				} catch ( err ) {
					console.warn( LOG, `Không nạp được bản đồ độ sâu ${ url }, dùng độ sâu suy từ độ sáng.`, err );
				}
			}
			const r = rebuild();
			console.info( LOG, `nguồn độ sâu: ${ api.source }; làm mượt bán kính ${ r.toFixed( 1 ) } texel trên ${ dw } × ${ dh }` );
			return api.source;
		},
		createView() {
			const u = {
				...uniforms,
				uPhoto: { value: photo },
				uMode: { value: 1 },
				uMix: { value: 0.35 },
				uStep: { value: 0.05 },
				uSplit: { value: 0.5 },
				uRelief: { value: 60 },
				uDepthTexel: { value: new THREE.Vector2( 1 / dw, 1 / dh ) },
				uFit: { value: new THREE.Vector4( 1, 1, 0, 0 ) },
				uBg: { value: 0.12 },
			};
			const mat = new THREE.ShaderMaterial( { uniforms: u, vertexShader: FULLSCREEN_VS, fragmentShader: VIEW_FS, depthTest: false, depthWrite: false } );
			const mesh = new THREE.Mesh( geo, mat );
			mesh.frustumCulled = false;
			const scene = new THREE.Scene();
			scene.add( mesh );
			const size = new THREE.Vector2();
			return {
				uniforms: u,
				scene,
				camera: passCam,
				setMode( name ) {
					u.uMode.value = Math.max( 0, VIEW_MODES.indexOf( name ) );
				},
				render() {
					renderer.getDrawingBufferSize( size );
					const va = size.x / size.y;
					const sx = va > api.aspect ? va / api.aspect : 1;
					const sy = va > api.aspect ? 1 : api.aspect / va;
					u.uFit.value.set( sx, sy, 0.5 - 0.5 * sx, 0.5 - 0.5 * sy );
					renderer.setRenderTarget( null );
					renderer.render( scene, passCam );
				},
				dispose() {
					mat.dispose();
				},
			};
		},
		dispose() {
			photo.dispose();
			if ( map ) {
				map.dispose();
			}
			rtA.dispose();
			rtB.dispose();
			geo.dispose();
			lumMat.dispose();
			blurMat.dispose();
		},
	};
	sync();
	await api.setMap( mapUrl );
	return api;
}
