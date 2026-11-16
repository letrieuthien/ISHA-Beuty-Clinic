/**
 * Hậu kỳ của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 14). Bước 4: grain + vignette cho MỌI tầng (governor không bao giờ tắt); bloom
 * desktop thêm ở bước 6.
 *
 * Không cần render target: một quad phủ khung vẽ cuối cùng với phép trộn nhân đôi (src · dst + dst · src = 2 · src · dst). Shader xuất
 * src = 0.5 · hệ số, nên điểm ảnh đích được nhân với hệ số = vignette · ( 1 + nhiễu · grain ): vừa tối góc vừa có hạt phim, trong không gian
 * hiển thị, rẻ như một lượt vẽ phẳng. Grain đổi mẫu 24 lần / giây (nhịp phim), phân bố tam giác (tổng hai giá trị ngẫu nhiên), biên độ theo
 * keyframe grain (2.2 – 3.0%) × grainScale. Vignette 0.25: góc tối đi 25%, mềm, theo khoảng cách elip của khung.
 */

const VS = /* glsl */ `
varying vec2 vUv;
void main() {
	vUv = position.xy * 0.5 + 0.5;
	gl_Position = vec4( position.xy, 0.0, 1.0 );
}
`;

const FS = /* glsl */ `
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

export function createPost( THREE, { cfg } ) {
	const geo = new THREE.BufferGeometry();
	geo.setAttribute( 'position', new THREE.Float32BufferAttribute( [ -1, -1, 0, 3, -1, 0, -1, 3, 0 ], 3 ) );
	const uniforms = { uGrain: { value: 0.03 }, uVignette: { value: cfg.vignette }, uFrame: { value: 0 } };
	const material = new THREE.ShaderMaterial( {
		uniforms,
		vertexShader: VS,
		fragmentShader: FS,
		transparent: true,
		depthTest: false,
		depthWrite: false,
		blending: THREE.CustomBlending,
		blendEquation: THREE.AddEquation,
		blendSrc: THREE.DstColorFactor,
		blendDst: THREE.SrcColorFactor,
	} );
	material.name = 'grain-vignette';
	const overlay = new THREE.Mesh( geo, material );
	overlay.name = 'grain-vignette';
	overlay.frustumCulled = false;
	overlay.renderOrder = 1000;
	return {
		overlay,
		/** @param {{time:number, grain:number}} f */
		update( f ) {
			uniforms.uGrain.value = f.grain * cfg.grainScale;
			uniforms.uVignette.value = cfg.vignette;
			uniforms.uFrame.value = Math.floor( f.time * 24 ) % 997;
		},
		dispose() {
			geo.dispose();
			material.dispose();
		},
	};
}
