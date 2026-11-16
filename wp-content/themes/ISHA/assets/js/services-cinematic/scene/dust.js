/**
 * Bụi sáng của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 12). Tắt khi giảm chuyển động. Đàn chim của đặc tả (birds.js) đã làm rồi bỏ
 * theo góp ý khách: motif tranh quê, không hợp tiêu chí sang trọng hiện đại.
 *
 * BỤI (createDust): THREE.Points, 300 hạt desktop / 120 điện thoại, sâu 40 … 200 đơn vị trước camera.
 *   - Cố định trong thế giới theo x, y; theo z thì cuộn vòng quanh camera: camera dolly tiến tới thì hạt trôi về phía người xem, ra ngoài
 *     khung rồi xuất hiện lại ở phía xa (mờ dần ở hai đầu dải độ sâu nên không nhảy). Lệch ngang của camera (về khối neo, chuột) sinh
 *     parallax mạnh vì hạt rất gần: bù cho dải ảnh nền bị giới hạn phóng 18%.
 *   - Chấm tròn mềm vẽ thẳng trong shader (gl_PointCoord; đặc tả gợi ý sprite vẽ bằng OffscreenCanvas: tương đương, không cần texture),
 *     cộng sáng, không ghi chiều sâu. Cỡ 1.5 – 7 px theo độ gần (đặc tả 1 – 4 px: ảnh chụp cho thấy cỡ đó ra chấm sao sắc, không phải
 *     bụi bắt nắng; mặt cắt Gauss mềm nên phần sáng thật vẫn nhỏ).
 *   - Trôi lên rất chậm, lắc ngang hình sin chu kỳ 8 – 16 giây, mỗi hạt lệch pha; nhấp nháy rất nhẹ.
 *   - Độ sáng theo khoảng cách màn hình tới mặt trời: gần mặt trời sáng rõ, xa gần như vô hình (bụi bắt nắng, không phải tuyết rơi).
 *     Màu fog → nắng vàng (sunCore pha Golden) theo độ gần mặt trời. Tổng theo keyframe dustIntensity.
 */

const DUST_VS = /* glsl */ `
attribute vec4 aSeed;  // x, y (−1.25 … 1.25, toạ độ khung ở độ sâu gốc), pha độ sâu (0 … 1), pha dao động
attribute vec2 aRate;  // chu kỳ lắc (giây), hệ số nhấp nháy
uniform vec3 uCamPos;
uniform vec2 uRefTan;
uniform float uNear;
uniform float uFar;
uniform float uTime;
uniform float uRise;
uniform float uSizeK;
uniform float uPixelRatio;
varying float vFade;
varying float vTwinkle;
varying vec2 vNdc;
void main() {
	float span = uFar - uNear;
	float travel = -uCamPos.z;
	float rel = uNear + mod( aSeed.z * span - travel, span );
	float rel0 = rel + travel;
	float ny = fract( aSeed.y * 0.4 + 0.5 + uTime * uRise ) * 2.5 - 1.25;
	float nx = aSeed.x + 0.035 * sin( uTime * 6.2832 / aRate.x + aSeed.w );
	vec3 world = vec3( nx * uRefTan.x * rel0, ny * uRefTan.y * rel0, uCamPos.z - rel );
	vec4 c = projectionMatrix * viewMatrix * vec4( world, 1.0 );
	vNdc = c.xy / c.w;
	vFade = smoothstep( uNear, uNear + 25.0, rel ) * ( 1.0 - smoothstep( uFar - 40.0, uFar, rel ) );
	vTwinkle = 0.75 + 0.25 * sin( uTime * aRate.y + aSeed.w * 3.0 );
	gl_PointSize = clamp( uSizeK / rel, 1.5, 7.0 ) * uPixelRatio;
	gl_Position = c;
}
`;

const DUST_FS = /* glsl */ `
uniform float uIntensity;
uniform float uGain;
uniform float uAspect;
uniform float uReach;
uniform vec2 uSunNdc;
uniform vec3 uFog;
uniform vec3 uSun;
varying float vFade;
varying float vTwinkle;
varying vec2 vNdc;
void main() {
	float d = length( gl_PointCoord - 0.5 ) * 2.0;
	float spot = exp( -d * d * 4.0 ) * ( 1.0 - smoothstep( 0.8, 1.0, d ) ); // Gauss mềm: đốm sáng, không phải chấm sao
	vec2 dv = ( vNdc - uSunNdc ) * vec2( uAspect, 1.0 ) * 0.5;
	float near = exp( -length( dv ) / uReach );
	float a = spot * vFade * vTwinkle * ( 0.02 + 0.98 * near ) * uIntensity * uGain;
	gl_FragColor = vec4( mix( uFog, uSun, near ) * a, 1.0 );
}
`;

/**
 * @param {object} THREE
 * @param {{palette:object, cfg:object, count:number, seed?:number}} opts cfg: CONFIG.dust (đọc mỗi khung); count: số hạt (tầng chất lượng).
 */
export function createDust( THREE, { palette, cfg, count, seed = 1 } ) {
	const MAX = 400;
	let s = seed * 9301 + 49297;
	const rnd = () => {
		s = ( s * 9301 + 49297 ) % 233280;
		return s / 233280;
	};
	const pos = new Float32Array( MAX * 3 );
	const aSeed = new Float32Array( MAX * 4 );
	const aRate = new Float32Array( MAX * 2 );
	for ( let i = 0; i < MAX; i++ ) {
		aSeed.set( [ rnd() * 2.5 - 1.25, rnd() * 2 - 1, rnd(), rnd() * 6.2832 ], i * 4 );
		aRate.set( [ 8 + rnd() * 8, 0.6 + rnd() * 1.4 ], i * 2 );
	}
	const geo = new THREE.BufferGeometry();
	geo.setAttribute( 'position', new THREE.BufferAttribute( pos, 3 ) );
	geo.setAttribute( 'aSeed', new THREE.BufferAttribute( aSeed, 4 ) );
	geo.setAttribute( 'aRate', new THREE.BufferAttribute( aRate, 2 ) );
	geo.setDrawRange( 0, Math.min( MAX, count ) );
	const uniforms = {
		uCamPos: { value: new THREE.Vector3() },
		uRefTan: { value: new THREE.Vector2( 1, 1 ) },
		uNear: { value: cfg.near },
		uFar: { value: cfg.far },
		uTime: { value: 0 },
		uRise: { value: cfg.rise },
		uSizeK: { value: cfg.sizeK },
		uPixelRatio: { value: 1 },
		uIntensity: { value: 0.5 },
		uGain: { value: cfg.gain },
		uAspect: { value: 1 },
		uReach: { value: cfg.reach },
		uSunNdc: { value: new THREE.Vector2() },
		uFog: { value: palette.fog.clone() },
		uSun: { value: palette.sunCore.clone().lerp( palette.golden, 0.45 ) },
	};
	const material = new THREE.ShaderMaterial( {
		uniforms,
		vertexShader: DUST_VS,
		fragmentShader: DUST_FS,
		transparent: true,
		blending: THREE.AdditiveBlending,
		depthWrite: false,
		depthTest: false,
	} );
	material.name = 'dust';
	const points = new THREE.Points( geo, material );
	points.name = 'dust';
	points.frustumCulled = false;
	points.renderOrder = 20;
	return {
		points,
		setCount( n ) {
			geo.setDrawRange( 0, Math.max( 0, Math.min( MAX, n ) ) );
		},
		/**
		 * @param {{camera:object, refTan:{x:number,y:number}, time:number, sunNdc:{x:number,y:number}, aspect:number, pixelRatio:number,
		 *   intensity:number}} f refTan: tang nửa FOV của camera tham chiếu (plate.layout), để hạt cố định trong thế giới.
		 */
		update( f ) {
			uniforms.uCamPos.value.copy( f.camera.position );
			uniforms.uRefTan.value.set( f.refTan.x, f.refTan.y );
			uniforms.uTime.value = f.time;
			uniforms.uSunNdc.value.set( f.sunNdc.x, f.sunNdc.y );
			uniforms.uAspect.value = f.aspect;
			uniforms.uPixelRatio.value = f.pixelRatio;
			uniforms.uIntensity.value = f.intensity;
			uniforms.uNear.value = cfg.near;
			uniforms.uFar.value = Math.max( cfg.far, cfg.near + 20 );
			uniforms.uRise.value = cfg.rise;
			uniforms.uSizeK.value = cfg.sizeK;
			uniforms.uGain.value = cfg.gain;
			uniforms.uReach.value = cfg.reach;
		},
		setColors( pal ) {
			uniforms.uFog.value.copy( pal.fog );
			uniforms.uSun.value.copy( pal.sunCore ).lerp( pal.golden, 0.45 );
		},
		dispose() {
			geo.dispose();
			material.dispose();
		},
	};
}
