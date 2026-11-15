/**
 * Bộ uniform ánh sáng / sương DÙNG CHUNG cho mọi vật thể tự chiếu sáng (địa hình, cỏ, cây, mây) và đoạn GLSL tương ứng. scene/index.js cập
 * nhật bộ uniform này MỘT lần mỗi khung hình; mỗi module gộp nó vào uniform của mình (cùng object nên đổi một chỗ là đổi mọi nơi).
 */

export function createSharedUniforms( THREE ) {
	return {
		uSunDir: { value: new THREE.Vector3( 0, 0.2, -1 ).normalize() },
		uSunColor: { value: new THREE.Color() },
		uSunIntensity: { value: 1 },
		uSkyColor: { value: new THREE.Color() },
		uGroundColor: { value: new THREE.Color() },
		uFogColor: { value: new THREE.Color() },
		uFogSunColor: { value: new THREE.Color() },
		uFogDensity: { value: 1 },
		uLightScale: { value: 1 },
		uTime: { value: 0 },
	};
}

// Khai báo uniform dùng chung + hàm tiện ích (value noise, chiếu sáng, phối cảnh khí quyển).
export const SHARED_GLSL = /* glsl */ `
uniform vec3 uSunDir;
uniform vec3 uSunColor;
uniform float uSunIntensity;
uniform vec3 uSkyColor;
uniform vec3 uGroundColor;
uniform vec3 uFogColor;
uniform vec3 uFogSunColor;
uniform float uFogDensity;
uniform float uLightScale;
uniform float uTime;

float ishaHash( vec2 p ) {
	p = fract( p * vec2( 123.34, 456.21 ) );
	p += dot( p, p + 45.32 );
	return fract( p.x * p.y );
}
float ishaNoise( vec2 p ) {
	vec2 i = floor( p );
	vec2 f = fract( p );
	vec2 u = f * f * ( 3.0 - 2.0 * f );
	return mix( mix( ishaHash( i ), ishaHash( i + vec2( 1.0, 0.0 ) ), u.x ), mix( ishaHash( i + vec2( 0.0, 1.0 ) ), ishaHash( i + vec2( 1.0, 1.0 ) ), u.x ), u.y );
}
float ishaFbm( vec2 p ) {
	float s = 0.0;
	float a = 0.5;
	for ( int i = 0; i < 5; i++ ) {
		s += ishaNoise( p ) * a;
		p = p * 2.03 + vec2( 17.1, 9.2 );
		a *= 0.5;
	}
	return s;
}
// Nắng wrap + trời bán cầu; uLightScale (CONFIG.lightScale) nâng cả cảnh vật cho khớp độ sáng của trời.
vec3 ishaLight( vec3 base, vec3 n, float ao ) {
	float wrap = clamp( ( dot( n, uSunDir ) + 0.35 ) / 1.35, 0.0, 1.0 );
	vec3 hemi = mix( uGroundColor, uSkyColor, n.y * 0.5 + 0.5 );
	return base * ( uSunColor * uSunIntensity * wrap * 0.42 + hemi * 0.78 ) * ao * uLightScale;
}
// Phối cảnh khí quyển: sương theo khoảng cách, nhạt dần theo độ cao; màu ngả về mặt trời theo hướng nhìn.
vec3 ishaAerial( vec3 col, vec3 worldPos, float heightFade ) {
	vec3 V = normalize( cameraPosition - worldPos );
	float dist = length( worldPos - cameraPosition );
	float fogAmt = 1.0 - exp( -dist * uFogDensity * 0.00036 );
	fogAmt *= mix( 1.0, 0.62, heightFade );
	float sunAmt = pow( max( dot( -V, uSunDir ), 0.0 ), 6.0 );
	return mix( col, mix( uFogColor, uFogSunColor, sunAmt ), clamp( fogAmt, 0.0, 1.0 ) );
}
`;
