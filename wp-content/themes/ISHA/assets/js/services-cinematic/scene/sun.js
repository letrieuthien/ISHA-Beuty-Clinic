/**
 * Mặt trời và ánh sáng thể tích của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 10). Bỏ hẳn sprite tia bánh răng và vòng lens-flare cũ.
 *
 * Che khuất: không dùng depth test cứng (cắt quầng / tia theo đường đồng độ sâu giữa sườn núi thành mép sắc); mỗi pixel tự tính lưới ảnh
 * nằm trước hay sau mình (OCCLUSION_GLSL của plate.js) và mờ dần trong khoảng mềm. Đĩa dùng khoảng mềm hẹp nên ở đường viền rặng vẫn bị cắt
 * gọn ("nhô lên từ sau núi"), quầng và tia mềm hơn.
 * Tia và vệt ngang còn nhân với ĐỘ LỘ của đĩa (sunVisibility: 5 mẫu trên đĩa, tính trong vertex shader): mặt trời còn khuất sau núi thì
 * không có tia (lỗi khách chụp: tia hiện xuyên sườn núi khi mặt trời chưa ló), ló dần ra thì tia hiện dần; chiều dài tia cũng bắt đầu từ 0
 * và dài ra theo độ cao mặt trời (CONFIG.rise.rayLength).
 *
 * Vị trí: mỗi khung đặt tâm trên tia từ camera hiện tại qua điểm (sunX, sunY) của keyframe, ở mặt phẳng z = −sun.z (1050): mặt trời luôn
 * đúng chỗ trên khung, và vì nằm giữa dải độ sâu của lưới ảnh (700 – 1300) nên rặng nào gần hơn tự che nó bằng depth test, tạo khoảnh khắc
 * "nhô lên từ sau núi" mà không cần mặt nạ. Mọi thành phần là quad dựng trong clip space quanh tâm (cùng chiều sâu với tâm), kích thước
 * theo chiều cao khung nên tròn trên mọi tỉ lệ.
 *   - Đĩa: tròn mềm, bán kính 2.2% chiều cao khung, lõi gần trắng (mix( Golden, White, 0.75 )), viền Golden tan vào quầng.
 *   - Quầng: radial rộng gấp 6 lần đĩa, alpha thấp, Golden.
 *   - Vệt ngang kiểu anamorphic: mảnh, dài 18% chiều rộng khung, alpha 0.12, màu Metal light.
 *   - Tia thể tích: 3 – 5 quad hình nêm toả từ tâm (z = −raysZ, hơi trước mặt trời nhưng vẫn sau các đỉnh gần), cộng sáng, không ghi chiều
 *     sâu, góc lệch nhau, mỗi tia xoay rất chậm với tốc độ riêng. Hình nêm tính thẳng trong shader (đặc tả gợi ý texture vẽ bằng
 *     OffscreenCanvas: tương đương, không cần texture). Độ đậm theo keyframe volumetric × nhịp thở (idle, bước 5).
 * Cộng sáng trong không gian hiển thị (màu token sRGB, không tone mapping): lõi chồng lên trời sáng thành trắng, viền giữ sắc vàng.
 */
import { OCCLUSION_GLSL } from './plate.js';

// Toạ độ thế giới của một điểm NDC trên mặt phẳng z của tâm (camera chỉ tịnh tiến, nhìn −Z): để tính che khuất mềm.
const PLANE_GLSL = /* glsl */ `
uniform vec3 uCamPos;
uniform vec2 uCamTan;
vec3 onPlane( vec2 ndc, float z ) {
	float d = uCamPos.z - z;
	return vec3( uCamPos.xy + ndc * uCamTan * d, z );
}
`;

// Độ lộ của đĩa mặt trời (0 khuất hẳn → 1 lộ hết): 5 mẫu che khuất trên đĩa (tâm và 4 điểm ở 0.7 bán kính), cần PLANE_GLSL, OCCLUSION_GLSL
// và uniform uAspect khai báo trước.
const SUN_VIS_GLSL = /* glsl */ `
uniform vec3 uSunCenter;
uniform float uSunR;
float sunVisibility() {
	vec4 sc = projectionMatrix * viewMatrix * vec4( uSunCenter, 1.0 );
	vec2 sn = sc.xy / sc.w;
	vec2 rr = vec2( uSunR * 2.0 / uAspect, uSunR * 2.0 ) * 0.7;
	float z = uSunCenter.z;
	return 0.4 * plateVisible( onPlane( sn, z ), 18.0 )
		+ 0.15 * ( plateVisible( onPlane( sn + vec2( rr.x, 0.0 ), z ), 18.0 ) + plateVisible( onPlane( sn - vec2( rr.x, 0.0 ), z ), 18.0 )
		+ plateVisible( onPlane( sn + vec2( 0.0, rr.y ), z ), 18.0 ) + plateVisible( onPlane( sn - vec2( 0.0, rr.y ), z ), 18.0 ) );
}
`;

const BILLBOARD_VS = /* glsl */ `
${ PLANE_GLSL }
${ OCCLUSION_GLSL }
uniform vec3 uCenter;
uniform float uAspect;
uniform vec2 uHalf;
${ SUN_VIS_GLSL }
varying vec2 vP;
varying vec3 vWorld;
varying float vVis;
void main() {
	vVis = sunVisibility();
	vec4 c = projectionMatrix * viewMatrix * vec4( uCenter, 1.0 );
	vP = position.xy * uHalf; // đơn vị: chiều cao khung
	vec2 off = vec2( vP.x * 2.0 / uAspect, vP.y * 2.0 );
	vWorld = onPlane( c.xy / c.w + off, uCenter.z );
	gl_Position = c + vec4( off * c.w, 0.0, 0.0 );
}
`;

const DISC_FS = /* glsl */ `
${ OCCLUSION_GLSL }
uniform float uRadius;
uniform float uHaloRadius;
uniform float uIntensity;
uniform float uDisc;
uniform float uHalo;
uniform float uHaloBreath;
uniform float uVeil;
uniform vec3 uCore;
uniform vec3 uRim;
uniform vec3 uHaloColor;
varying vec2 vP;
varying vec3 vWorld;
void main() {
	float r = length( vP );
	float disc = 1.0 - smoothstep( uRadius * 0.45, uRadius * 1.08, r );
	vec3 discCol = mix( uRim, uCore, 1.0 - smoothstep( uRadius * 0.1, uRadius, r ) );
	float hr = uHaloRadius * uHaloBreath;
	float halo = exp( -pow( r / hr, 2.0 ) * 3.0 ) * 0.55 + exp( -r / ( hr * 0.22 ) ) * 0.45;
	vec3 col = discCol * disc * uDisc * plateVisible( vWorld, 18.0 ) + uHaloColor * halo * uHalo * mix( plateVisible( vWorld, 70.0 ), 1.0, uVeil );
	gl_FragColor = vec4( col * uIntensity, 1.0 );
}
`;

const STREAK_FS = /* glsl */ `
${ OCCLUSION_GLSL }
uniform float uHalfLen;
uniform float uThick;
uniform float uAlpha;
uniform float uIntensity;
uniform vec3 uColor;
varying vec2 vP;
varying vec3 vWorld;
varying float vVis;
void main() {
	float ax = clamp( abs( vP.x ) / uHalfLen, 0.0, 1.0 );
	float along = pow( 1.0 - ax, 2.2 );
	float thin = exp( -pow( vP.y / uThick, 2.0 ) );
	float soft = exp( -pow( vP.y / ( uThick * 5.0 ), 2.0 ) ) * 0.25;
	gl_FragColor = vec4( uColor * ( thin + soft ) * along * uAlpha * uIntensity * plateVisible( vWorld, 40.0 ) * smoothstep( 0.1, 0.7, vVis ), 1.0 );
}
`;

const RAYS_VS = /* glsl */ `
${ PLANE_GLSL }
${ OCCLUSION_GLSL }
attribute vec4 aRay; // góc gốc (radian), dài (tỉ lệ rayLength), nửa rộng ở đầu xa (chiều cao khung), hệ số tốc độ xoay
attribute float aPhase;
uniform vec3 uCenter;
uniform float uAspect;
uniform float uLength;
uniform float uTime;
uniform float uTurn;
uniform float uSpread;
uniform float uWidth;
${ SUN_VIS_GLSL }
varying vec2 vST;
varying float vGain;
varying vec3 vWorld;
void main() {
	vec4 c = projectionMatrix * viewMatrix * vec4( uCenter, 1.0 );
	// Góc giữa các tia quanh hướng xuống (−95°) giãn theo độ cao mặt trời (uSpread), cộng nhịp lắc chậm riêng từng tia.
	float a = -1.658 + ( aRay.x + 1.658 ) * uSpread + 0.11 * sin( uTime * uTurn * aRay.w + aPhase );
	vec2 dir = vec2( cos( a ), sin( a ) );
	vec2 nrm = vec2( -dir.y, dir.x );
	float s = position.x * 0.5 + 0.5;
	float t = position.y;
	vec2 p = dir * s * uLength * aRay.y + nrm * t * aRay.z * 1.4 * uWidth * mix( 0.12, 1.0, s );
	vST = vec2( s, t );
	// Mỗi tia sáng / mờ theo nhịp riêng (khoảng 10 – 20 giây), không đồng loạt: màn sáng "thở", không đứng như ảnh tĩnh.
	vGain = ( 0.72 + 0.28 * sin( uTime * 0.45 * abs( aRay.w ) + aPhase * 1.7 ) ) * smoothstep( 0.05, 0.6, sunVisibility() );
	vec2 off = vec2( p.x * 2.0 / uAspect, p.y * 2.0 );
	vWorld = onPlane( c.xy / c.w + off, uCenter.z );
	gl_Position = c + vec4( off * c.w, 0.0, 0.0 );
}
`;

const RAYS_FS = /* glsl */ `
${ OCCLUSION_GLSL }
uniform float uStrength;
uniform vec3 uColor;
varying vec2 vST;
varying float vGain;
varying vec3 vWorld;
void main() {
	float s = vST.x;
	// Màn sáng mềm, không phải tia sắc: mặt cắt ngang rộng, đầu gần tâm mờ (quầng đã lo), tắt dần về cuối.
	float across = exp( -vST.y * vST.y * 4.0 ); // Gauss thuần: không có mép thẳng
	float along = smoothstep( 0.02, 0.22, s ) * pow( 1.0 - s, 1.5 );
	gl_FragColor = vec4( uColor * across * along * vGain * uStrength * plateVisible( vWorld, 90.0 ), 1.0 );
}
`;

// Tia: [ góc (độ, 0 = sang phải, ngược chiều kim đồng hồ), dài (tỉ lệ), nửa rộng đầu xa (chiều cao khung), hệ số tốc độ ]. Toả chủ yếu xuống
// và sang hai bên như nắng sớm qua sương xuống thung lũng; tầng thấp dùng 3 tia đầu.
const RAYS = [
	[ -100, 1, 0.2, 1 ],
	[ -58, 0.85, 0.16, -0.8 ],
	[ -142, 0.9, 0.17, 0.7 ],
	[ -22, 0.7, 0.13, -1.2 ],
	[ -168, 0.65, 0.13, 1.1 ],
];

function quad( THREE ) {
	return new THREE.PlaneGeometry( 2, 2 );
}

/**
 * @param {object} THREE
 * @param {{palette:object, cfg:object, occlusion:object, rays:number}} opts palette: toVectors( derivePalette ) (sRGB); cfg: CONFIG.sun
 *   (đọc mỗi khung); occlusion: plate.occlusionUniforms; rays: số tia (tầng chất lượng, 3 – 5).
 */
export function createSun( THREE, { palette, cfg, occlusion, rays = 5 } ) {
	const center = new THREE.Vector3();
	const rayCenter = new THREE.Vector3();
	const common = {
		uAspect: { value: 1 },
		uCamPos: { value: new THREE.Vector3() },
		uCamTan: { value: new THREE.Vector2( 1, 1 ) },
		uSunCenter: { value: center },
		uSunR: { value: cfg.radius },
		...occlusion,
	};
	const add = { transparent: true, blending: THREE.AdditiveBlending, depthWrite: false, depthTest: false };

	const discU = {
		...common,
		uCenter: { value: center },
		uHalf: { value: new THREE.Vector2( 1, 1 ) },
		uRadius: { value: cfg.radius },
		uHaloRadius: { value: cfg.radius * cfg.haloScale },
		uIntensity: { value: 1 },
		uDisc: { value: cfg.disc },
		uHalo: { value: cfg.halo },
		uHaloBreath: { value: 1 },
		uVeil: { value: cfg.haloVeil },
		uCore: { value: palette.sunCore.clone() },
		uRim: { value: palette.golden.clone() },
		uHaloColor: { value: palette.golden.clone() },
	};
	const disc = new THREE.Mesh( quad( THREE ), new THREE.ShaderMaterial( { uniforms: discU, vertexShader: BILLBOARD_VS, fragmentShader: DISC_FS, ...add } ) );

	const streakU = {
		...common,
		uCenter: { value: center },
		uHalf: { value: new THREE.Vector2( 1, 0.02 ) },
		uHalfLen: { value: 0.1 },
		uThick: { value: 0.0016 },
		uAlpha: { value: cfg.streakAlpha },
		uIntensity: { value: 1 },
		uColor: { value: palette.rays.clone() },
	};
	const streak = new THREE.Mesh( quad( THREE ), new THREE.ShaderMaterial( { uniforms: streakU, vertexShader: BILLBOARD_VS, fragmentShader: STREAK_FS, ...add } ) );

	const rayGeo = new THREE.InstancedBufferGeometry();
	const base = quad( THREE );
	rayGeo.index = base.index;
	rayGeo.attributes.position = base.attributes.position;
	const n = Math.max( 1, Math.min( RAYS.length, rays ) );
	const aRay = new Float32Array( RAYS.length * 4 );
	const aPhase = new Float32Array( RAYS.length );
	RAYS.forEach( ( r, i ) => {
		aRay.set( [ ( r[ 0 ] * Math.PI ) / 180, r[ 1 ], r[ 2 ], r[ 3 ] ], i * 4 );
		aPhase[ i ] = i * 2.399;
	} );
	rayGeo.setAttribute( 'aRay', new THREE.InstancedBufferAttribute( aRay, 4 ) );
	rayGeo.setAttribute( 'aPhase', new THREE.InstancedBufferAttribute( aPhase, 1 ) );
	rayGeo.instanceCount = n;
	const rayU = {
		...common,
		uCenter: { value: rayCenter },
		uLength: { value: cfg.rayLength },
		uTime: { value: 0 },
		uTurn: { value: cfg.rayTurn },
		uSpread: { value: 1 },
		uWidth: { value: 1 },
		uStrength: { value: 0 },
		uColor: { value: palette.sunCore.clone().lerp( palette.golden, 0.5 ) },
	};
	const raysMesh = new THREE.Mesh( rayGeo, new THREE.ShaderMaterial( { uniforms: rayU, vertexShader: RAYS_VS, fragmentShader: RAYS_FS, ...add } ) );

	const group = new THREE.Group();
	group.name = 'sun';
	for ( const [ m, order ] of [ [ disc, 4 ], [ streak, 5 ], [ raysMesh, 6 ] ] ) {
		m.frustumCulled = false;
		m.renderOrder = order;
		group.add( m );
	}

	// Điểm trên tia từ camera (chỉ tịnh tiến, nhìn −Z) qua NDC (x, y), ở mặt phẳng z = −zDist.
	function place( out, camera, ndc, zDist ) {
		const tanY = Math.tan( ( camera.fov * Math.PI ) / 360 );
		const d = zDist + camera.position.z;
		return out.set( camera.position.x + ndc.x * tanY * camera.aspect * d, camera.position.y + ndc.y * tanY * d, -zDist );
	}

	return {
		group,
		center,
		/**
		 * @param {{camera:object, sunNdc:{x:number,y:number}, aspect:number, time:number, sunScale:number, sunIntensity:number,
		 *   volumetric:number, haloBreath?:number, rise:object}} f rise: stops.js riseValues (theo độ cao mặt trời): sunScale, halo,
		 *   rayLength, raySpread, rayWidth, raysZ.
		 */
		update( f ) {
			const rise = f.rise;
			place( center, f.camera, f.sunNdc, cfg.z );
			place( rayCenter, f.camera, f.sunNdc, rise.raysZ );
			common.uAspect.value = f.aspect;
			common.uCamPos.value.copy( f.camera.position );
			const tanY = Math.tan( ( f.camera.fov * Math.PI ) / 360 );
			common.uCamTan.value.set( tanY * f.camera.aspect, tanY );
			const r = cfg.radius * f.sunScale * rise.sunScale;
			common.uSunR.value = r;
			discU.uRadius.value = r;
			discU.uHaloRadius.value = r * cfg.haloScale;
			discU.uHalf.value.setScalar( r * cfg.haloScale * 2.2 );
			discU.uIntensity.value = f.sunIntensity;
			discU.uDisc.value = cfg.disc;
			discU.uHalo.value = cfg.halo * rise.halo;
			discU.uHaloBreath.value = f.haloBreath || 1;
			discU.uVeil.value = cfg.haloVeil;
			const halfLen = ( cfg.streakLength * f.aspect ) / 2;
			streakU.uHalfLen.value = halfLen;
			streakU.uHalf.value.set( halfLen, 0.02 );
			streakU.uAlpha.value = cfg.streakAlpha;
			streakU.uIntensity.value = f.sunIntensity;
			rayU.uLength.value = cfg.rayLength * rise.rayLength;
			rayU.uSpread.value = rise.raySpread;
			rayU.uWidth.value = rise.rayWidth;
			rayU.uTime.value = f.time;
			rayU.uTurn.value = cfg.rayTurn;
			rayU.uStrength.value = cfg.rays * f.volumetric;
		},
		setRays( count ) {
			rayGeo.instanceCount = Math.max( 1, Math.min( RAYS.length, count ) );
		},
		setColors( pal ) {
			discU.uCore.value.copy( pal.sunCore );
			discU.uRim.value.copy( pal.golden );
			discU.uHaloColor.value.copy( pal.golden );
			streakU.uColor.value.copy( pal.rays );
			rayU.uColor.value.copy( pal.sunCore ).lerp( pal.golden, 0.5 );
		},
		dispose() {
			for ( const m of [ disc, streak, raysMesh ] ) {
				m.geometry.dispose();
				m.material.dispose();
			}
		},
	};
}
