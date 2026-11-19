/**
 * Shader của mỗi tấm ảnh trong gallery 3D (viết lại từ createClothMaterial của bản mẫu docs/founder-section.md, tiết chế theo phong cách
 * Zen). Cờ biên dịch (defines của ShaderMaterial):
 *   FG_CLOTH  uốn cong mặt phẳng theo lực cuộn và gợn vải, biên độ nhân uZen (khoảng 30% bản mẫu). Bản điện thoại tắt.
 *   FG_BLUR   làm mờ chiều sâu 3×3 mẫu (bản mẫu 5×5), bán kính uBlur texel. Bản điện thoại tắt (chỉ giữ mờ dần theo trục Z).
 *   FG_HOVER  viền vàng ôm sát bốn cạnh (bám theo góc bo) + một vệt ánh kim chạy dọc chu vi khi rê chuột (desktop, chuột). Không có sóng lá cờ.
 * Bo góc (luôn bật): khoảng cách có dấu tới hình chữ nhật bo (uRadius, cùng đơn vị với uSize); phần ngoài bị cắt bằng alpha, mép khử răng
 * cưa theo fwidth.
 * Màu: texture sRGB được GPU giải mã về tuyến tính; uniform màu là THREE.Color (tuyến tính); colorspace_fragment đổi về sRGB khi xuất.
 */
export const VERTEX = /* glsl */ `
uniform float uForce;
uniform float uZen;
varying vec2 vUv;

void main() {
	vUv = uv;
	vec3 p = position;
#ifdef FG_CLOTH
	// Toạ độ cục bộ trong [-0,5; 0,5] (mesh co giãn bằng scale), giống bản mẫu; dịch chuyển theo trục z cục bộ.
	float ci = uForce * 0.3 * uZen;
	float r = length( p.xy );
	float curve = r * r * ci;
	float ripple = sin( p.x * 2.0 + uForce * 3.0 ) * 0.02 + sin( p.y * 2.5 + uForce * 2.0 ) * 0.015;
	p.z -= curve + ripple * abs( ci ) * 2.0;
#endif
	gl_Position = projectionMatrix * modelViewMatrix * vec4( p, 1.0 );
}
`;

export const FRAGMENT = /* glsl */ `
uniform sampler2D uMap;
uniform float uOpacity;   // mờ dần theo chiều sâu x đã tải xong
uniform float uForce;
uniform float uZen;
uniform float uDim;       // 0..1: hoà vào màu nền (sương chiều sâu)
uniform vec3 uBg;
uniform vec2 uUvScale;    // cắt ảnh (cover) về tỉ lệ hiển thị đã kẹp
uniform vec2 uUvOffset;
uniform vec2 uSize;       // kích thước tấm ảnh (đơn vị thế giới): bo góc, viền đều, vệt sáng chạy đều theo chu vi
uniform float uRadius;    // bán kính bo góc (đơn vị thế giới)
#ifdef FG_BLUR
uniform float uBlur;
#endif
#ifdef FG_HOVER
uniform float uHover;     // 0..1
uniform float uTime;
uniform vec3 uGold;
uniform vec3 uGlint;
uniform float uLinePx;
uniform float uGlintSpeed;
uniform float uGlintLen;
#endif
varying vec2 vUv;

void main() {
	vec2 uv = vUv * uUvScale + uUvOffset;
	vec4 color = texture2D( uMap, uv );
#ifdef FG_BLUR
	if ( uBlur > 0.01 ) {
		vec2 ts = uBlur / vec2( textureSize( uMap, 0 ) );
		vec4 acc = color * 0.25;
		acc += texture2D( uMap, uv + vec2( ts.x, 0.0 ) ) * 0.125;
		acc += texture2D( uMap, uv - vec2( ts.x, 0.0 ) ) * 0.125;
		acc += texture2D( uMap, uv + vec2( 0.0, ts.y ) ) * 0.125;
		acc += texture2D( uMap, uv - vec2( 0.0, ts.y ) ) * 0.125;
		acc += texture2D( uMap, uv + ts ) * 0.0625;
		acc += texture2D( uMap, uv - ts ) * 0.0625;
		acc += texture2D( uMap, uv + vec2( ts.x, -ts.y ) ) * 0.0625;
		acc += texture2D( uMap, uv + vec2( -ts.x, ts.y ) ) * 0.0625;
		color = acc;
	}
#endif
	// Sáng nhẹ theo độ uốn (bản mẫu), nhân uZen.
	color.rgb += vec3( abs( uForce ) * 0.005 * uZen );
	color.rgb = mix( color.rgb, uBg, uDim );

	// Hình chữ nhật bo góc: sd < 0 ở trong. inside = khoảng cách từ điểm tới mép, dương ở trong.
	vec2 pc = ( vUv - 0.5 ) * uSize;
	vec2 rq = abs( pc ) - 0.5 * uSize + uRadius;
	float sd = length( max( rq, 0.0 ) ) + min( max( rq.x, rq.y ), 0.0 ) - uRadius;
	float px = max( fwidth( sd ), 1e-6 );
	float cover = clamp( 0.5 - sd / px, 0.0, 1.0 );
#ifdef FG_HOVER
	if ( uHover > 0.001 ) {
		vec2 q = vUv * uSize;
		float db = q.y;
		float dr = uSize.x - q.x;
		float dt = uSize.y - q.y;
		float dl = q.x;
		float edge = min( min( dl, dr ), min( db, dt ) );
		float inside = -sd;
		float line = 1.0 - smoothstep( px * uLinePx, px * ( uLinePx + 1.0 ), inside );
		// Vị trí trên chu vi (ngược chiều kim đồng hồ từ góc dưới trái), 0..1.
		float s;
		if ( edge == db ) {
			s = q.x;
		} else if ( edge == dr ) {
			s = uSize.x + q.y;
		} else if ( edge == dt ) {
			s = uSize.x + uSize.y + dr;
		} else {
			s = 2.0 * uSize.x + uSize.y + dt;
		}
		float t = s / ( 2.0 * ( uSize.x + uSize.y ) );
		float dd = abs( fract( t - fract( uTime * uGlintSpeed ) + 0.5 ) - 0.5 );
		float glint = 1.0 - smoothstep( 0.0, uGlintLen, dd );
		glint *= glint;
		float glow = ( 1.0 - smoothstep( 0.0, px * 7.0, inside ) ) * glint * 0.35;
		color.rgb = mix( color.rgb, mix( uGold, uGlint, glint ), line * uHover );
		color.rgb += uGlint * glow * uHover;
	}
#endif
	gl_FragColor = vec4( color.rgb, color.a * uOpacity * cover );
	#include <colorspace_fragment>
}
`;
