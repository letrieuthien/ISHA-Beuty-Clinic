/**
 * Hậu kỳ của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 14).
 *
 * GRAIN + VIGNETTE (mọi tầng, governor không bao giờ tắt): một quad phủ khung vẽ cuối cùng với phép trộn nhân đôi (src · dst + dst · src =
 * 2 · src · dst). Shader xuất src = 0.5 · hệ số, nên điểm ảnh đích được nhân với hệ số = vignette · ( 1 + nhiễu · grain ): vừa tối góc vừa có
 * hạt phim, trong không gian hiển thị, rẻ như một lượt vẽ phẳng. Grain đổi mẫu 24 lần / giây (nhịp phim), phân bố tam giác, biên độ theo
 * keyframe grain (2.2 – 3.0%) × grainScale. Vignette theo độ cao mặt trời (CONFIG.rise.vignette 0.32 → 0.14; đặc tả 0.25 cố định).
 *
 * BLOOM (chỉ desktop, tầng có bloom; governor tắt được): threshold cao, chỉ chạm đĩa mặt trời và chỗ sáng nhất. Cảnh vẫn vẽ thẳng lên màn
 * hình như mọi tầng (không đưa cả cảnh vào render target: three bỏ tone mapping và mã hoá sRGB khi vẽ vào render target, sẽ lệch toàn bộ
 * màu đã duyệt). Sau khi vẽ cảnh: chép framebuffer ra texture (copyFramebufferToTexture), lọc vùng sáng (độ sáng hiển thị > threshold, đầu
 * gối mềm) xuống 1/4 độ phân giải, làm mờ Gauss tách trục ở 1/4 và 1/8, cộng ngược lên màn hình. Rồi mới tới grain + vignette.
 */

const FULLSCREEN_VS = /* glsl */ `
varying vec2 vUv;
void main() {
	vUv = position.xy * 0.5 + 0.5;
	gl_Position = vec4( position.xy, 0.0, 1.0 );
}
`;

const GRAIN_FS = /* glsl */ `
uniform float uGrain;
uniform float uVignette;
uniform float uFrame;
varying vec2 vUv;
float hash( vec2 p ) {
	vec3 p3 = fract( vec3( p.xyx ) * 0.1031 );
	p3 += dot( p3, p3.yzx + 33.33 );
	return fract( ( p3.x + p3.y ) * p3.z );
}
void main() {
	vec2 d = vUv - 0.5;
	float v = 1.0 - uVignette * smoothstep( 0.2, 0.78, length( d * vec2( 1.0, 0.82 ) ) * 1.25 );
	vec2 seed = gl_FragCoord.xy + uFrame * vec2( 37.0, 17.0 );
	float n = hash( seed ) + hash( seed + 71.3 ) - 1.0; // tam giác, −1 .. 1
	float f = v * ( 1.0 + n * uGrain );
	gl_FragColor = vec4( vec3( 0.5 * f ), 1.0 );
}
`;

// Lọc vùng sáng + thu nhỏ: 4 mẫu song tuyến quanh tâm (mỗi mẫu trung bình 2 × 2 texel nguồn).
const BRIGHT_FS = /* glsl */ `
uniform sampler2D uSrc;
uniform vec2 uSrcTexel;
uniform float uThreshold;
uniform float uKnee;
varying vec2 vUv;
vec3 pick( vec2 uv ) {
	vec3 c = texture( uSrc, uv ).rgb;
	float l = dot( c, vec3( 0.2126, 0.7152, 0.0722 ) );
	return c * smoothstep( uThreshold, uThreshold + uKnee, l );
}
void main() {
	vec2 o = uSrcTexel;
	vec3 c = 0.25 * ( pick( vUv - o ) + pick( vUv + vec2( o.x, -o.y ) ) + pick( vUv + vec2( -o.x, o.y ) ) + pick( vUv + o ) );
	gl_FragColor = vec4( c, 1.0 );
}
`;

const DOWN_FS = /* glsl */ `
uniform sampler2D uSrc;
uniform vec2 uSrcTexel;
varying vec2 vUv;
void main() {
	vec2 o = uSrcTexel;
	vec3 c = 0.25 * ( texture( uSrc, vUv - o ).rgb + texture( uSrc, vUv + vec2( o.x, -o.y ) ).rgb + texture( uSrc, vUv + vec2( -o.x, o.y ) ).rgb + texture( uSrc, vUv + o ).rgb );
	gl_FragColor = vec4( c, 1.0 );
}
`;

// Gauss 9 mẫu tách trục (dùng lọc song tuyến: 5 lượt đọc tương đương 9 texel).
const BLUR_FS = /* glsl */ `
uniform sampler2D uSrc;
uniform vec2 uDir;
varying vec2 vUv;
void main() {
	vec3 c = texture( uSrc, vUv ).rgb * 0.227027;
	c += texture( uSrc, vUv + uDir * 1.384615 ).rgb * 0.316216;
	c += texture( uSrc, vUv - uDir * 1.384615 ).rgb * 0.316216;
	c += texture( uSrc, vUv + uDir * 3.230769 ).rgb * 0.070270;
	c += texture( uSrc, vUv - uDir * 3.230769 ).rgb * 0.070270;
	gl_FragColor = vec4( c, 1.0 );
}
`;

const COMPOSITE_FS = /* glsl */ `
uniform sampler2D uA;
uniform sampler2D uB;
uniform float uStrength;
varying vec2 vUv;
void main() {
	vec3 c = texture( uA, vUv ).rgb * 0.55 + texture( uB, vUv ).rgb * 0.9;
	gl_FragColor = vec4( c * uStrength, 1.0 );
}
`;

/**
 * @param {object} THREE
 * @param {{cfg:object, bloom?:boolean, rtType?:number}} opts cfg: CONFIG.post (đọc mỗi khung); bloom: tầng có bloom; rtType: kiểu render
 *   target (createRenderer().rtType).
 */
export function createPost( THREE, { cfg, bloom = false, rtType } ) {
	const geo = new THREE.BufferGeometry();
	geo.setAttribute( 'position', new THREE.Float32BufferAttribute( [ -1, -1, 0, 3, -1, 0, -1, 3, 0 ], 3 ) );
	const cam = new THREE.Camera();
	const quad = ( material ) => {
		const m = new THREE.Mesh( geo, material );
		m.frustumCulled = false;
		const sc = new THREE.Scene();
		sc.add( m );
		return { mesh: m, scene: sc };
	};
	const flat = { depthTest: false, depthWrite: false };

	const grainU = { uGrain: { value: 0.03 }, uVignette: { value: 0.25 }, uFrame: { value: 0 } };
	const grainMat = new THREE.ShaderMaterial( {
		uniforms: grainU,
		vertexShader: FULLSCREEN_VS,
		fragmentShader: GRAIN_FS,
		transparent: true,
		...flat,
		blending: THREE.CustomBlending,
		blendEquation: THREE.AddEquation,
		blendSrc: THREE.DstColorFactor,
		blendDst: THREE.SrcColorFactor,
	} );
	grainMat.name = 'grain-vignette';
	const grain = quad( grainMat );

	// Bloom (dựng khi cần).
	let bloomOn = bloom;
	let b = null;
	const size = new THREE.Vector2();
	function ensureBloom( w, h ) {
		if ( b && b.w === w && b.h === h ) {
			return b;
		}
		disposeBloom();
		const rt = ( rw, rh ) => new THREE.WebGLRenderTarget( Math.max( 1, rw ), Math.max( 1, rh ), {
			type: rtType ?? THREE.UnsignedByteType,
			depthBuffer: false,
			generateMipmaps: false,
			minFilter: THREE.LinearFilter,
			magFilter: THREE.LinearFilter,
		} );
		const fb = new THREE.FramebufferTexture( w, h );
		fb.minFilter = THREE.LinearFilter;
		fb.magFilter = THREE.LinearFilter;
		const qw = Math.ceil( w / 4 );
		const qh = Math.ceil( h / 4 );
		const ew = Math.ceil( w / 8 );
		const eh = Math.ceil( h / 8 );
		const brightMat = new THREE.ShaderMaterial( {
			uniforms: { uSrc: { value: fb }, uSrcTexel: { value: new THREE.Vector2( 1 / w, 1 / h ) }, uThreshold: { value: cfg.bloomThreshold }, uKnee: { value: 0.08 } },
			vertexShader: FULLSCREEN_VS,
			fragmentShader: BRIGHT_FS,
			...flat,
		} );
		const downMat = new THREE.ShaderMaterial( {
			uniforms: { uSrc: { value: null }, uSrcTexel: { value: new THREE.Vector2( 1 / qw, 1 / qh ) } },
			vertexShader: FULLSCREEN_VS,
			fragmentShader: DOWN_FS,
			...flat,
		} );
		const blurMat = new THREE.ShaderMaterial( {
			uniforms: { uSrc: { value: null }, uDir: { value: new THREE.Vector2() } },
			vertexShader: FULLSCREEN_VS,
			fragmentShader: BLUR_FS,
			...flat,
		} );
		const quarterA = rt( qw, qh );
		const quarterB = rt( qw, qh );
		const eighthA = rt( ew, eh );
		const eighthB = rt( ew, eh );
		const compMat = new THREE.ShaderMaterial( {
			uniforms: { uA: { value: quarterA.texture }, uB: { value: eighthA.texture }, uStrength: { value: cfg.bloom } },
			vertexShader: FULLSCREEN_VS,
			fragmentShader: COMPOSITE_FS,
			transparent: true,
			blending: THREE.AdditiveBlending,
			...flat,
		} );
		b = {
			w, h, fb, quarterA, quarterB, eighthA, eighthB, brightMat, downMat, blurMat, compMat,
			pass: quad( brightMat ),
			comp: quad( compMat ),
			q: [ qw, qh ],
			e: [ ew, eh ],
		};
		return b;
	}
	function disposeBloom() {
		if ( ! b ) {
			return;
		}
		for ( const k of [ 'fb', 'quarterA', 'quarterB', 'eighthA', 'eighthB', 'brightMat', 'downMat', 'blurMat', 'compMat' ] ) {
			b[ k ].dispose();
		}
		b = null;
	}
	function run( renderer, material, target ) {
		b.pass.mesh.material = material;
		renderer.setRenderTarget( target );
		renderer.render( b.pass.scene, cam );
	}
	function blur( renderer, src, tmp, w, h, radius ) {
		b.blurMat.uniforms.uSrc.value = src.texture;
		b.blurMat.uniforms.uDir.value.set( radius / w, 0 );
		run( renderer, b.blurMat, tmp );
		b.blurMat.uniforms.uSrc.value = tmp.texture;
		b.blurMat.uniforms.uDir.value.set( 0, radius / h );
		run( renderer, b.blurMat, src );
	}

	return {
		get bloom() {
			return bloomOn;
		},
		setBloom( on ) {
			bloomOn = !! on;
			if ( ! bloomOn ) {
				disposeBloom();
			}
		},
		/**
		 * Gọi NGAY SAU khi đã vẽ cảnh lên màn hình (cùng khung).
		 * @param {object} renderer THREE.WebGLRenderer
		 * @param {{time:number, grain:number, vignette:number}} f
		 */
		render( renderer, f ) {
			const autoClear = renderer.autoClear;
			renderer.autoClear = false;
			if ( bloomOn && cfg.bloom > 0 ) {
				renderer.getDrawingBufferSize( size );
				ensureBloom( size.x, size.y );
				renderer.copyFramebufferToTexture( b.fb );
				b.brightMat.uniforms.uThreshold.value = cfg.bloomThreshold;
				run( renderer, b.brightMat, b.quarterA );
				blur( renderer, b.quarterA, b.quarterB, b.q[ 0 ], b.q[ 1 ], cfg.bloomRadius );
				b.downMat.uniforms.uSrc.value = b.quarterA.texture;
				run( renderer, b.downMat, b.eighthA );
				blur( renderer, b.eighthA, b.eighthB, b.e[ 0 ], b.e[ 1 ], cfg.bloomRadius );
				b.compMat.uniforms.uStrength.value = cfg.bloom;
				renderer.setRenderTarget( null );
				renderer.render( b.comp.scene, cam );
			}
			grainU.uGrain.value = f.grain * cfg.grainScale;
			grainU.uVignette.value = f.vignette;
			grainU.uFrame.value = Math.floor( f.time * 24 ) % 997;
			renderer.setRenderTarget( null );
			renderer.render( grain.scene, cam );
			renderer.autoClear = autoClear;
		},
		dispose() {
			disposeBloom();
			geo.dispose();
			grainMat.dispose();
		},
	};
}
