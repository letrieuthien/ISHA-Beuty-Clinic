/**
 * Trời của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 7, 8). Vẽ đầu tiên, phủ kín khung, theo toạ độ màn hình (trời ở vô cực: camera
 * dolly không làm trời dịch). Toàn bộ trời của ảnh nguồn đã bị cắt (plate.js) nên trời chuyển sáng theo hành trình mặt trời và mây trôi được.
 *
 * - Gradient 4 chặng từ trên xuống tới đường chân trời của khung: Daylight → Skin soft → mix( Skin, Golden, 0.5 ) → Golden. Chặng dịch
 *   theo skyMix (0 rạng đông: tông ấm lan cao; 1 toàn quang: Daylight phủ nhiều hơn). Quanh mặt trời Golden đậm hơn (sunGlow).
 * - Mây: tối đa hai ĐÁM MÂY ĐƠN LẺ từ ảnh PNG nền trong suốt (CONFIG.sky.clouds: vị trí, cỡ, tốc độ trôi), không phải lớp phủ lặp kín trời.
 *   Mỗi đám: độ phủ = alpha của ảnh × cửa sổ mờ dần về 0 ở 4% sát mép ảnh (mép quad không bao giờ thành hình chữ nhật, kể cả khi ảnh có
 *   điểm ảnh sót ở viền), sáng tối theo độ sáng của chính ảnh (phía tối của mây ngả màu trời ấm), gần mặt trời viền ấm lên; độ đậm theo
 *   keyframe cloudOpacity; trôi ngang rất chậm, ra khỏi mép thì vòng lại phía bên kia. Không có ảnh: không có mây.
 *   (Bản trước dùng mặt nạ mây ti tách từ ảnh trời xanh clouds.jpg; bỏ theo yêu cầu khách: vết vá watermark của ảnh đó dễ lộ thành mảng.)
 * - Dither ±0.5 / 255 sau khi mã hoá sRGB (interleaved gradient noise): hết banding mà không cần ảnh trời.
 * Màu từ token (tokens.js, sRGB hiển thị); trộn trong không gian hiển thị rồi tuyến tính hoá, qua tone mapping như mọi thứ khác.
 */

const VS = /* glsl */ `
varying vec2 vUv;
void main() {
	vUv = position.xy * 0.5 + 0.5;
	gl_Position = vec4( position.xy, 1.0, 1.0 );
}
`;

const FS = /* glsl */ `
uniform vec3 uTop;
uniform vec3 uMid1;
uniform vec3 uMid2;
uniform vec3 uLow;
uniform vec3 uCloudLight;
uniform vec3 uCloudShade;
uniform float uSkyMix;
uniform float uHorizonY;
uniform float uAspect;
uniform vec2 uSunNdc;
uniform float uSunGlow;
uniform float uSunGlowRadius;
uniform float uHorizonGolden;
uniform sampler2D uCloudA;
uniform sampler2D uCloudB;
uniform vec4 uRectA; // tâm (x, y theo chiều cao khung, gốc trên trái), nửa rộng, nửa cao
uniform vec4 uRectB;
uniform vec2 uCloudOn;
uniform float uCloudAmount;
varying vec2 vUv;
vec3 toLinear( vec3 c ) {
	return mix( c / 12.92, pow( ( max( c, 0.0 ) + 0.055 ) / 1.055, vec3( 2.4 ) ), step( 0.04045, c ) );
}
// Một đám mây: trả ( độ phủ, độ sáng của mây ). Texture nhân alpha sẵn (premultiplyAlpha) nên lọc song tuyến không tạo viền tối.
vec2 cloud( sampler2D tex, vec4 rect, vec2 p ) {
	vec2 l = ( p - rect.xy ) / ( 2.0 * rect.zw ) + 0.5;
	// Không rẽ nhánh trước khi lấy mẫu (lấy mẫu có mipmap trong nhánh không đồng nhất tạo viền khối); cửa sổ đã bằng 0 ngoài ảnh.
	vec4 t = texture( tex, clamp( vec2( l.x, 1.0 - l.y ), 0.0, 1.0 ) );
	float win = smoothstep( 0.0, 0.04, l.x ) * smoothstep( 0.0, 0.04, 1.0 - l.x ) * smoothstep( 0.0, 0.04, l.y ) * smoothstep( 0.0, 0.04, 1.0 - l.y );
	float a = smoothstep( 0.03, 1.0, t.a ) * win;
	float lum = dot( t.rgb / max( t.a, 1e-3 ), vec3( 0.2126, 0.7152, 0.0722 ) );
	return vec2( a, lum );
}
void main() {
	float yTop = 1.0 - vUv.y;
	float t = yTop / max( uHorizonY, 1e-3 ); // 0 mép trên → 1 đường chân trời của khung
	float s1 = mix( 0.2, 0.5, uSkyMix );
	float s2 = mix( 0.52, 0.84, uSkyMix );
	vec3 c = mix( uTop, uMid1, smoothstep( 0.0, s1, t ) );
	c = mix( c, uMid2, smoothstep( s1, s2, t ) );
	c = mix( c, uLow, smoothstep( s2, 1.0, t ) * mix( 1.0, uHorizonGolden, uSkyMix ) );

	// Quanh mặt trời: Golden đậm hơn (đơn vị khoảng cách: chiều cao khung).
	vec2 dv = ( ( vUv * 2.0 - 1.0 ) - uSunNdc ) * vec2( uAspect, 1.0 ) * 0.5;
	float r = length( dv );
	float glow = exp( -r / uSunGlowRadius );
	c = mix( c, uLow, uSunGlow * glow );

	// Mây đơn lẻ (toạ độ theo chiều cao khung, gốc trên trái).
	vec2 p = vec2( vUv.x * uAspect, yTop );
	vec2 ca = uCloudOn.x > 0.5 ? cloud( uCloudA, uRectA, p ) : vec2( 0.0 );
	vec2 cb = uCloudOn.y > 0.5 ? cloud( uCloudB, uRectB, p ) : vec2( 0.0 );
	float cover = 1.0 - ( 1.0 - ca.x ) * ( 1.0 - cb.x );
	float lum = ( ca.x * ca.y + cb.x * cb.y ) / max( ca.x + cb.x, 1e-4 );
	vec3 cloudCol = mix( uCloudShade, uCloudLight, smoothstep( 0.45, 0.95, lum ) );
	cloudCol = mix( cloudCol, uLow, 0.5 * glow );
	c = mix( c, cloudCol, cover * uCloudAmount );

	gl_FragColor = vec4( toLinear( c ), 1.0 );
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
	// Dither sau khi mã hoá: interleaved gradient noise, biên độ một bậc 8 bit.
	float ign = fract( 52.9829189 * fract( dot( gl_FragCoord.xy, vec2( 0.06711056, 0.00583715 ) ) ) );
	gl_FragColor.rgb += ( ign - 0.5 ) / 255.0;
}
`;

/**
 * @param {object} THREE
 * @param {{palette:object, cfg:object, clouds?:Array<object|null>}} opts palette: toVectors( derivePalette ); cfg: CONFIG.sky (đọc mỗi
 *   khung); clouds: tối đa hai texture PNG nền trong suốt, theo thứ tự của cfg.clouds.
 */
export function createSky( THREE, { palette, cfg, clouds = [] } ) {
	const geo = new THREE.BufferGeometry();
	geo.setAttribute( 'position', new THREE.Float32BufferAttribute( [ -1, -1, 0, 3, -1, 0, -1, 3, 0 ], 3 ) );
	const uniforms = {
		uTop: { value: new THREE.Vector3() },
		uMid1: { value: new THREE.Vector3() },
		uMid2: { value: new THREE.Vector3() },
		uLow: { value: new THREE.Vector3() },
		uCloudLight: { value: new THREE.Vector3() },
		uCloudShade: { value: new THREE.Vector3() },
		uSkyMix: { value: 0 },
		uHorizonY: { value: 0.47 },
		uAspect: { value: 1 },
		uSunNdc: { value: new THREE.Vector2() },
		uSunGlow: { value: cfg.sunGlow },
		uSunGlowRadius: { value: cfg.sunGlowRadius },
		uHorizonGolden: { value: cfg.horizonGolden },
		uCloudA: { value: null },
		uCloudB: { value: null },
		uRectA: { value: new THREE.Vector4() },
		uRectB: { value: new THREE.Vector4() },
		uCloudOn: { value: new THREE.Vector2() },
		uCloudAmount: { value: 0.2 },
	};
	const material = new THREE.ShaderMaterial( { uniforms, vertexShader: VS, fragmentShader: FS, depthTest: false, depthWrite: false } );
	material.name = 'sky';
	const mesh = new THREE.Mesh( geo, material );
	mesh.name = 'sky';
	mesh.frustumCulled = false;
	mesh.renderOrder = -10;
	const texAspect = [ 1, 1 ];

	function setColors( pal ) {
		uniforms.uTop.value.copy( pal.daylight );
		uniforms.uMid1.value.copy( pal.skinSoft );
		uniforms.uMid2.value.copy( pal.skyMid );
		uniforms.uLow.value.copy( pal.golden );
		uniforms.uCloudLight.value.copy( pal.daylight ).lerp( pal.white, 0.7 );
		uniforms.uCloudShade.value.copy( pal.skyMid ).lerp( pal.daylight, 0.35 );
	}
	function setClouds( list ) {
		[ 'uCloudA', 'uCloudB' ].forEach( ( k, i ) => {
			const tex = list[ i ] || null;
			uniforms[ k ].value = tex;
			uniforms.uCloudOn.value.setComponent( i, tex ? 1 : 0 );
			if ( tex && tex.image ) {
				tex.wrapS = THREE.ClampToEdgeWrapping;
				tex.wrapT = THREE.ClampToEdgeWrapping;
				tex.premultiplyAlpha = true;
				tex.needsUpdate = true;
				texAspect[ i ] = tex.image.width / tex.image.height;
			}
		} );
	}
	setColors( palette );
	setClouds( clouds );

	return {
		mesh,
		uniforms,
		setColors,
		setClouds,
		/**
		 * @param {{skyMix:number, sunNdc:{x:number,y:number}, cloudOpacity:number, time:number, aspect:number, horizonY:number}} f
		 */
		update( f ) {
			uniforms.uSkyMix.value = f.skyMix;
			uniforms.uSunNdc.value.set( f.sunNdc.x, f.sunNdc.y );
			uniforms.uAspect.value = f.aspect;
			uniforms.uHorizonY.value = f.horizonY;
			uniforms.uSunGlow.value = cfg.sunGlow;
			uniforms.uSunGlowRadius.value = cfg.sunGlowRadius;
			uniforms.uHorizonGolden.value = cfg.horizonGolden;
			uniforms.uCloudAmount.value = f.cloudOpacity * cfg.cloudGain;
			// Vị trí từng đám: x theo tỉ lệ chiều rộng khung, y và cỡ theo chiều cao khung (cỡ thu theo khung hẹp), trôi ngang rồi vòng lại.
			[ 'uRectA', 'uRectB' ].forEach( ( k, i ) => {
				const c = cfg.clouds[ i ];
				if ( ! c ) {
					return;
				}
				const halfW = ( Math.min( c.width * f.aspect, c.width * 1.78 ) ) / 2;
				const halfH = halfW / texAspect[ i ];
				const span = f.aspect + 4 * halfW;
				let x = c.x * f.aspect + f.time * c.speed;
				x = ( ( ( x + 2 * halfW ) % span ) + span ) % span - 2 * halfW;
				uniforms[ k ].value.set( x, c.y * f.horizonY / 0.47, halfW, halfH );
			} );
		},
		dispose() {
			geo.dispose();
			material.dispose();
		},
	};
}
