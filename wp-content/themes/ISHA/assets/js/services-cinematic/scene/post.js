/**
 * Hậu kỳ desktop (chỉ bậc chất lượng có post: FULL, FULL_HIGH; governor tắt được): god rays radial blur, bloom ngưỡng cao, grain rất nhẹ (khi
 * bloom bật, tức FULL_HIGH). Tự viết, không thêm thư viện.
 *
 * Cảnh vẫn render THẲNG ra canvas như khi không có hậu kỳ (tone mapping, antialias của canvas giữ nguyên). Sau đó:
 *   1) chép framebuffer vào một texture (copyFramebufferToTexture, không đọc về CPU);
 *   2) god rays (1/4 độ phân giải): lọc vùng sáng quanh mặt trời (núi tối thành vật che), hai lượt radial blur về phía mặt trời;
 *   3) bloom (1/2 → 1/4 → 1/8): lọc ngưỡng cao (chỉ đĩa mặt trời, viền mây rực), Gaussian tách hai chiều ở 1/4 và 1/8;
 *   4) độ sâu trường ảnh: cỏ ở mép dưới khung (gần camera nhất) mềm dần như ống kính điện ảnh lấy nét xa (ảnh 1/4 làm mờ hai lượt, trộn
 *      theo dải dưới khung);
 *   5) CỘNG god rays và bloom lên canvas; grain trộn kiểu nhân đôi (sáng / tối quanh 0.5).
 * Tắt hậu kỳ là bỏ qua các bước này: ảnh gốc không đổi màu. Toàn bộ xử lý trong không gian hiển thị (sau tone mapping), texture 8 bit.
 */
const VERT = /* glsl */ `
	varying vec2 vUv;
	void main() {
		vUv = position.xy * 0.5 + 0.5;
		gl_Position = vec4( position.xy, 0.0, 1.0 );
	}
`;

const BRIGHT = /* glsl */ `
	uniform sampler2D tSrc;
	uniform vec2 uTexel;
	uniform float uThreshold;
	uniform float uKnee;
	uniform vec2 uSun;
	uniform float uMask;
	uniform float uAspect;
	varying vec2 vUv;
	void main() {
		// 4 mẫu lệch nửa texel nguồn: hộp lọc 4 x 4 khi thu nhỏ, không bỏ sót điểm sáng nhỏ.
		vec3 c = texture2D( tSrc, vUv + uTexel * vec2( -1.0, -1.0 ) ).rgb + texture2D( tSrc, vUv + uTexel * vec2( 1.0, -1.0 ) ).rgb
			+ texture2D( tSrc, vUv + uTexel * vec2( -1.0, 1.0 ) ).rgb + texture2D( tSrc, vUv + uTexel * vec2( 1.0, 1.0 ) ).rgb;
		c *= 0.25;
		float l = dot( c, vec3( 0.2126, 0.7152, 0.0722 ) );
		float w = smoothstep( uThreshold, uThreshold + uKnee, l );
		if ( uMask > 0.0 ) {
			vec2 d = ( vUv - uSun ) * vec2( uAspect, 1.0 );
			w *= exp( -length( d ) / uMask );
		}
		gl_FragColor = vec4( c * w, 1.0 );
	}
`;

const RADIAL = /* glsl */ `
	uniform sampler2D tSrc;
	uniform vec2 uSun;
	uniform float uStep;
	uniform float uDecay;
	varying vec2 vUv;
	void main() {
		vec2 dir = ( uSun - vUv ) * uStep / 24.0;
		vec2 uv = vUv;
		float w = 1.0;
		float tot = 0.0;
		vec3 acc = vec3( 0.0 );
		for ( int i = 0; i < 24; i++ ) {
			acc += texture2D( tSrc, uv ).rgb * w;
			tot += w;
			w *= uDecay;
			uv += dir;
		}
		gl_FragColor = vec4( acc / tot, 1.0 );
	}
`;

const BLUR = /* glsl */ `
	uniform sampler2D tSrc;
	uniform vec2 uDir;
	varying vec2 vUv;
	void main() {
		vec3 c = texture2D( tSrc, vUv ).rgb * 0.2270270270;
		c += texture2D( tSrc, vUv + uDir * 1.3846153846 ).rgb * 0.3162162162;
		c += texture2D( tSrc, vUv - uDir * 1.3846153846 ).rgb * 0.3162162162;
		c += texture2D( tSrc, vUv + uDir * 3.2307692308 ).rgb * 0.0702702703;
		c += texture2D( tSrc, vUv - uDir * 3.2307692308 ).rgb * 0.0702702703;
		gl_FragColor = vec4( c, 1.0 );
	}
`;

const COMPOSITE = /* glsl */ `
	uniform sampler2D tRays;
	uniform sampler2D tBloomA;
	uniform sampler2D tBloomB;
	uniform float uRays;
	uniform vec3 uRayTint;
	uniform float uBloom;
	varying vec2 vUv;
	void main() {
		vec3 c = texture2D( tRays, vUv ).rgb * uRayTint * uRays;
		c += ( texture2D( tBloomA, vUv ).rgb * 0.65 + texture2D( tBloomB, vUv ).rgb * 0.55 ) * uBloom;
		gl_FragColor = vec4( c, 1.0 );
	}
`;

const DOF = /* glsl */ `
	uniform sampler2D tBlur;
	uniform float uEnd;
	uniform float uStrength;
	varying vec2 vUv;
	void main() {
		float m = ( 1.0 - smoothstep( 0.0, uEnd, vUv.y ) ) * uStrength;
		gl_FragColor = vec4( texture2D( tBlur, vUv ).rgb, m );
	}
`;

const GRAIN = /* glsl */ `
	uniform float uGrain;
	uniform float uSeed;
	void main() {
		vec2 p = gl_FragCoord.xy + uSeed * vec2( 37.0, 17.0 );
		float n = fract( sin( dot( p, vec2( 12.9898, 78.233 ) ) ) * 43758.5453 );
		gl_FragColor = vec4( vec3( 0.5 + ( n - 0.5 ) * uGrain ), 1.0 );
	}
`;

export function createPost( THREE ) {
	const geo = new THREE.BufferGeometry();
	geo.setAttribute( 'position', new THREE.Float32BufferAttribute( [ -1, -1, 0, 3, -1, 0, -1, 3, 0 ], 3 ) );
	const mesh = new THREE.Mesh( geo );
	mesh.frustumCulled = false;
	const scene = new THREE.Scene();
	scene.add( mesh );
	const cam = new THREE.Camera();

	const make = ( frag, uniforms, extra = {} ) => new THREE.ShaderMaterial( { vertexShader: VERT, fragmentShader: frag, uniforms, depthTest: false, depthWrite: false, ...extra } );
	const mBright = make( BRIGHT, {
		tSrc: { value: null },
		uTexel: { value: new THREE.Vector2() },
		uThreshold: { value: 0.8 },
		uKnee: { value: 0.1 },
		uSun: { value: new THREE.Vector2() },
		uMask: { value: 0 },
		uAspect: { value: 1 },
	} );
	const mRadial = make( RADIAL, { tSrc: { value: null }, uSun: { value: new THREE.Vector2() }, uStep: { value: 1 }, uDecay: { value: 0.96 } } );
	const mBlur = make( BLUR, { tSrc: { value: null }, uDir: { value: new THREE.Vector2() } } );
	const mComposite = make( COMPOSITE, {
		tRays: { value: null },
		tBloomA: { value: null },
		tBloomB: { value: null },
		uRays: { value: 0 },
		uRayTint: { value: new THREE.Color() },
		uBloom: { value: 0 },
	}, { transparent: true, blending: THREE.AdditiveBlending } );
	const mDof = make( DOF, { tBlur: { value: null }, uEnd: { value: 0.2 }, uStrength: { value: 0 } }, { transparent: true, blending: THREE.NormalBlending } );
	const mGrain = make( GRAIN, { uGrain: { value: 0 }, uSeed: { value: 0 } }, {
		transparent: true,
		blending: THREE.CustomBlending,
		blendEquation: THREE.AddEquation,
		blendSrc: THREE.DstColorFactor,
		blendDst: THREE.SrcColorFactor,
	} );

	const rtOpts = { depthBuffer: false, stencilBuffer: false, minFilter: THREE.LinearFilter, magFilter: THREE.LinearFilter, generateMipmaps: false };
	const rt = {
		q1: new THREE.WebGLRenderTarget( 1, 1, rtOpts ),
		q2: new THREE.WebGLRenderTarget( 1, 1, rtOpts ),
		h: new THREE.WebGLRenderTarget( 1, 1, rtOpts ),
		b4a: new THREE.WebGLRenderTarget( 1, 1, rtOpts ),
		b4b: new THREE.WebGLRenderTarget( 1, 1, rtOpts ),
		b8a: new THREE.WebGLRenderTarget( 1, 1, rtOpts ),
		b8b: new THREE.WebGLRenderTarget( 1, 1, rtOpts ),
		d1: new THREE.WebGLRenderTarget( 1, 1, rtOpts ),
		d2: new THREE.WebGLRenderTarget( 1, 1, rtOpts ),
	};
	let sceneTex = null;
	const dbs = new THREE.Vector2();
	const full = { w: 0, h: 0 };

	function ensureSize( renderer ) {
		renderer.getDrawingBufferSize( dbs );
		const w = Math.max( 1, Math.floor( dbs.x ) );
		const h = Math.max( 1, Math.floor( dbs.y ) );
		if ( w === full.w && h === full.h && sceneTex ) {
			return;
		}
		full.w = w;
		full.h = h;
		if ( sceneTex ) {
			sceneTex.dispose();
		}
		sceneTex = new THREE.FramebufferTexture( w, h );
		sceneTex.minFilter = THREE.LinearFilter;
		sceneTex.magFilter = THREE.LinearFilter;
		const q = ( d ) => [ Math.max( 1, Math.round( w / d ) ), Math.max( 1, Math.round( h / d ) ) ];
		rt.q1.setSize( ...q( 4 ) );
		rt.q2.setSize( ...q( 4 ) );
		rt.h.setSize( ...q( 2 ) );
		rt.b4a.setSize( ...q( 4 ) );
		rt.b4b.setSize( ...q( 4 ) );
		rt.b8a.setSize( ...q( 8 ) );
		rt.b8b.setSize( ...q( 8 ) );
		rt.d1.setSize( ...q( 4 ) );
		rt.d2.setSize( ...q( 4 ) );
	}

	function pass( renderer, material, target ) {
		mesh.material = material;
		renderer.setRenderTarget( target );
		renderer.render( scene, cam );
	}

	function blur( renderer, a, b ) {
		mBlur.uniforms.tSrc.value = a.texture;
		mBlur.uniforms.uDir.value.set( 1 / a.width, 0 );
		pass( renderer, mBlur, b );
		mBlur.uniforms.tSrc.value = b.texture;
		mBlur.uniforms.uDir.value.set( 0, 1 / b.height );
		pass( renderer, mBlur, a );
	}

	return {
		/**
		 * Gọi ngay sau renderer.render( cảnh ) ra canvas, với renderer.autoClear = false.
		 *
		 * @param {THREE.WebGLRenderer} renderer
		 * @param {{sunUv:{x:number,y:number}, aspect:number, rays:number, rayTint:THREE.Color, rayThreshold:number, bloom:number,
		 *   bloomThreshold:number, grain:number, seed:number, dof:number, dofEnd:number}} o
		 */
		render( renderer, o ) {
			if ( o.rays <= 0 && o.bloom <= 0 && o.grain <= 0 && o.dof <= 0 ) {
				return;
			}
			ensureSize( renderer );
			const prev = renderer.getRenderTarget();
			if ( o.rays > 0 || o.bloom > 0 || o.dof > 0 ) {
				renderer.copyFramebufferToTexture( sceneTex );
			}
			const B = mBright.uniforms;
			B.tSrc.value = sceneTex;
			B.uSun.value.set( o.sunUv.x, o.sunUv.y );
			B.uAspect.value = o.aspect;

			if ( o.rays > 0 ) {
				B.uTexel.value.set( 1 / full.w, 1 / full.h );
				B.uThreshold.value = o.rayThreshold;
				B.uKnee.value = 0.12;
				B.uMask.value = 0.3;
				pass( renderer, mBright, rt.q1 );
				const R = mRadial.uniforms;
				R.uSun.value.set( o.sunUv.x, o.sunUv.y );
				R.tSrc.value = rt.q1.texture;
				R.uStep.value = 1;
				R.uDecay.value = 0.965;
				pass( renderer, mRadial, rt.q2 );
				R.tSrc.value = rt.q2.texture;
				R.uStep.value = 0.3;
				R.uDecay.value = 0.98;
				pass( renderer, mRadial, rt.q1 );
			}
			if ( o.bloom > 0 ) {
				B.uTexel.value.set( 0.5 / full.w, 0.5 / full.h );
				B.uThreshold.value = o.bloomThreshold;
				B.uKnee.value = 0.05;
				B.uMask.value = 0.35; // chỉ quanh mặt trời: trời sáng ở chân trời không bị bloom
				pass( renderer, mBright, rt.h );
				B.tSrc.value = rt.h.texture;
				B.uTexel.value.set( 0.5 / rt.h.width, 0.5 / rt.h.height );
				B.uThreshold.value = -1;
				B.uMask.value = 0;
				pass( renderer, mBright, rt.b4a );
				blur( renderer, rt.b4a, rt.b4b );
				B.tSrc.value = rt.b4a.texture;
				B.uTexel.value.set( 0.5 / rt.b4a.width, 0.5 / rt.b4a.height );
				pass( renderer, mBright, rt.b8a );
				blur( renderer, rt.b8a, rt.b8b );
			}

			if ( o.dof > 0 ) {
				// Ảnh 1/4 (hộp 4 x 4) rồi Gaussian hai chiều hai lượt.
				B.tSrc.value = sceneTex;
				B.uTexel.value.set( 1 / full.w, 1 / full.h );
				B.uThreshold.value = -1;
				B.uMask.value = 0;
				pass( renderer, mBright, rt.d1 );
				blur( renderer, rt.d1, rt.d2 );
				blur( renderer, rt.d1, rt.d2 );
				mDof.uniforms.tBlur.value = rt.d1.texture;
				mDof.uniforms.uEnd.value = o.dofEnd;
				mDof.uniforms.uStrength.value = o.dof;
				pass( renderer, mDof, prev );
			}

			const C = mComposite.uniforms;
			C.tRays.value = rt.q1.texture;
			C.tBloomA.value = rt.b4a.texture;
			C.tBloomB.value = rt.b8a.texture;
			C.uRays.value = Math.max( 0, o.rays );
			C.uRayTint.value.copy( o.rayTint );
			C.uBloom.value = Math.max( 0, o.bloom );
			if ( o.rays > 0 || o.bloom > 0 ) {
				pass( renderer, mComposite, prev );
			}
			if ( o.grain > 0 ) {
				mGrain.uniforms.uGrain.value = o.grain;
				mGrain.uniforms.uSeed.value = o.seed;
				pass( renderer, mGrain, prev );
			}
			renderer.setRenderTarget( prev );
		},
		// Dựng trước shader (trong lúc poster còn che).
		compile( renderer ) {
			for ( const m of [ mBright, mRadial, mBlur, mDof, mComposite, mGrain ] ) {
				mesh.material = m;
				renderer.compile( scene, cam );
			}
		},
		dispose() {
			geo.dispose();
			[ mBright, mRadial, mBlur, mDof, mComposite, mGrain ].forEach( ( m ) => m.dispose() );
			Object.values( rt ).forEach( ( t ) => t.dispose() );
			if ( sceneTex ) {
				sceneTex.dispose();
			}
		},
	};
}
