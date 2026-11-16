/**
 * Trời của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 7, 8). Vẽ đầu tiên, phủ kín khung, theo toạ độ màn hình (trời ở vô cực: camera
 * dolly không làm trời dịch). Toàn bộ trời của ảnh nguồn đã bị cắt (plate.js) nên trời chuyển sáng theo hành trình mặt trời và mây trôi được.
 *
 * - Gradient 4 chặng từ trên xuống tới đường chân trời của khung: Daylight → Skin soft → mix( Skin, Golden, 0.5 ) → Golden. Chặng dịch
 *   theo skyMix (0 rạng đông: tông ấm lan cao; 1 toàn quang: Daylight phủ nhiều hơn). Quanh mặt trời Golden đậm hơn (sunGlow).
 * - Mây: texture mặt nạ mây (xám, mây trắng trên nền đen, ví dụ mây ti tách từ ảnh trời xanh) nếu có, trộn kiểu screen, độ đậm
 *   cloudOpacity, màu theo vị trí (gần mặt trời ấm hơn), trôi ngang cloudDrift đơn vị / giây; không có texture: fbm 2D domain-warp.
 * - Dither ±0.5 / 255 sau khi mã hoá sRGB (interleaved gradient noise): hết banding mà không cần ảnh trời.
 * Màu từ token (tokens.js, sRGB hiển thị); trộn trong không gian hiển thị rồi tuyến tính hoá, qua tone mapping như mọi thứ khác.
 */
import { SRGB_GLSL } from './depth.js';

const VS = /* glsl */ `
varying vec2 vUv;
void main() {
	vUv = position.xy * 0.5 + 0.5;
	gl_Position = vec4( position.xy, 1.0, 1.0 );
}
`;

const FS = /* glsl */ `
${ SRGB_GLSL }
uniform vec3 uTop;
uniform vec3 uMid1;
uniform vec3 uMid2;
uniform vec3 uLow;
uniform vec3 uCloudBase;
uniform float uSkyMix;
uniform float uHorizonY;
uniform float uAspect;
uniform vec2 uSunNdc;
uniform float uSunGlow;
uniform float uSunGlowRadius;
uniform float uHorizonGolden;
uniform sampler2D uClouds;
uniform float uHasClouds;
uniform float uCloudTexAspect;
uniform float uCloudOpacity;
uniform float uCloudGain;
uniform float uCloudOffset;
varying vec2 vUv;
vec3 toLinear( vec3 c ) {
	return mix( c / 12.92, pow( ( max( c, 0.0 ) + 0.055 ) / 1.055, vec3( 2.4 ) ), step( 0.04045, c ) );
}
float hash( vec2 p ) {
	return fract( sin( dot( p, vec2( 127.1, 311.7 ) ) ) * 43758.5453 );
}
float vnoise( vec2 p ) {
	vec2 i = floor( p );
	vec2 f = fract( p );
	vec2 u = f * f * ( 3.0 - 2.0 * f );
	return mix( mix( hash( i ), hash( i + vec2( 1.0, 0.0 ) ), u.x ), mix( hash( i + vec2( 0.0, 1.0 ) ), hash( i + vec2( 1.0, 1.0 ) ), u.x ), u.y );
}
float fbm( vec2 p ) {
	float s = 0.0;
	float a = 0.5;
	for ( int i = 0; i < 5; i++ ) {
		s += a * vnoise( p );
		p = p * 2.03 + vec2( 17.1, 9.2 );
		a *= 0.5;
	}
	return s;
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

	// Mây.
	vec2 cuv = vec2( vUv.x * uAspect / ( uHorizonY * uCloudTexAspect ) * 0.62 + uCloudOffset, 1.0 - clamp( t, 0.0, 1.0 ) );
	float m;
	if ( uHasClouds > 0.5 ) {
		m = texture( uClouds, cuv ).r;
	} else {
		vec2 q = vec2( cuv.x * 3.0, cuv.y * 9.0 );
		vec2 w = vec2( fbm( q + vec2( 0.0, uCloudOffset * 2.0 ) ), fbm( q + vec2( 5.2, 1.3 ) ) );
		m = smoothstep( 0.52, 0.82, fbm( q + 1.6 * w ) ) * 0.8;
	}
	m *= uCloudGain * uCloudOpacity * ( 1.0 - smoothstep( 0.72, 1.0, t ) );
	vec3 tint = mix( uCloudBase, uLow, 0.55 * glow );
	c = 1.0 - ( 1.0 - c ) * ( 1.0 - tint * clamp( m, 0.0, 1.0 ) );

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
 * @param {{palette:object, cfg:object, clouds?:object|null}} opts palette: toVectors( derivePalette ); cfg: CONFIG.sky (đọc mỗi khung);
 *   clouds: texture mặt nạ mây (tuỳ chọn).
 */
export function createSky( THREE, { palette, cfg, clouds = null } ) {
	const geo = new THREE.BufferGeometry();
	geo.setAttribute( 'position', new THREE.Float32BufferAttribute( [ -1, -1, 0, 3, -1, 0, -1, 3, 0 ], 3 ) );
	const uniforms = {
		uTop: { value: new THREE.Vector3() },
		uMid1: { value: new THREE.Vector3() },
		uMid2: { value: new THREE.Vector3() },
		uLow: { value: new THREE.Vector3() },
		uCloudBase: { value: new THREE.Vector3() },
		uSkyMix: { value: 0 },
		uHorizonY: { value: 0.47 },
		uAspect: { value: 1 },
		uSunNdc: { value: new THREE.Vector2() },
		uSunGlow: { value: cfg.sunGlow },
		uSunGlowRadius: { value: cfg.sunGlowRadius },
		uHorizonGolden: { value: cfg.horizonGolden },
		uClouds: { value: null },
		uHasClouds: { value: 0 },
		uCloudTexAspect: { value: 1.78 },
		uCloudOpacity: { value: 0.2 },
		uCloudGain: { value: cfg.cloudGain },
		uCloudOffset: { value: 0 },
	};
	const material = new THREE.ShaderMaterial( { uniforms, vertexShader: VS, fragmentShader: FS, depthTest: false, depthWrite: false } );
	material.name = 'sky';
	const mesh = new THREE.Mesh( geo, material );
	mesh.name = 'sky';
	mesh.frustumCulled = false;
	mesh.renderOrder = -10;

	function setColors( pal ) {
		uniforms.uTop.value.copy( pal.daylight );
		uniforms.uMid1.value.copy( pal.skinSoft );
		uniforms.uMid2.value.copy( pal.skyMid );
		uniforms.uLow.value.copy( pal.golden );
		uniforms.uCloudBase.value.copy( pal.daylight ).lerp( pal.white, 0.7 );
	}
	function setClouds( tex ) {
		uniforms.uClouds.value = tex;
		uniforms.uHasClouds.value = tex ? 1 : 0;
		if ( tex && tex.image ) {
			tex.wrapS = THREE.MirroredRepeatWrapping;
			tex.wrapT = THREE.ClampToEdgeWrapping;
			tex.needsUpdate = true;
			uniforms.uCloudTexAspect.value = tex.image.width / tex.image.height;
		}
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
			uniforms.uCloudOpacity.value = f.cloudOpacity;
			uniforms.uAspect.value = f.aspect;
			uniforms.uHorizonY.value = f.horizonY;
			uniforms.uSunGlow.value = cfg.sunGlow;
			uniforms.uSunGlowRadius.value = cfg.sunGlowRadius;
			uniforms.uHorizonGolden.value = cfg.horizonGolden;
			uniforms.uCloudGain.value = cfg.cloudGain;
			uniforms.uCloudOffset.value = f.time * cfg.cloudDrift;
		},
		dispose() {
			geo.dispose();
			material.dispose();
		},
	};
}
