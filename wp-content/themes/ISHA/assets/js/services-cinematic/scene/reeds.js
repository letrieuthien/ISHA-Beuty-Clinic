/**
 * Cỏ lau tiền cảnh của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 13). Hoàn toàn do WebGL vẽ, không lấy từ ảnh.
 *
 * Làm theo lối điện ảnh, không minh hoạ (tiêu chí khách: sang trọng hiện đại, không quê): bóng tối MẤT NÉT ở mép dưới khung như tiền cảnh
 * của ống kính tele, mép mềm rộng, không có chi tiết lá; chỉ ngọn (bông lau) bắt chút nắng vàng ngược sáng. Thưa ở giữa, dày và cao hơn ở
 * hai góc dưới: viền khung, chừa chỗ cho nội dung. Tắt được (CONFIG.reeds.enabled).
 *
 * InstancedMesh, 180 lá desktop / 70 điện thoại (tầng chất lượng), trong dải 12% dưới cùng của khung (hai góc cao tới khoảng 14%). Mỗi lá là một dải thon (thân
 * mảnh, bông lau mềm ở 35% trên), cong và nghiêng riêng, đung đưa bằng vertex shader theo x thế giới cộng thời gian, biên độ nhỏ, chu kỳ
 * 6 – 9 giây. Màu mix( fog, ridgeBase, 0.86 ) × shade, tối dần về chân. Cách camera khoảng 80 đơn vị (đặc tả: z −80). Lệch đặc tả có chủ ý:
 * với z −80 cố định và dolly 95, camera đi xuyên qua lớp lau, và trước đó phép phóng từ tâm khung đẩy lau ở mép dưới trôi khỏi khung; nên
 * lớp lau đi theo camera theo chiều sâu (follow 0.95: chỉ tiến lại khoảng 5%), parallax mạnh đến từ chuyển động ngang (camera lệch về khối
 * neo 6 đơn vị: lau trượt khoảng 120 px, núi vài px; chuột, nhịp thở). Giảm chuyển động: không đung đưa.
 */

const VS = /* glsl */ `
attribute vec4 aBlade;  // x (khung tham chiếu, −1.45 … 1.45: rộng hơn khung để camera lệch ngang không làm hụt góc), chiều cao (tỉ lệ chiều cao khung), nghiêng (radian), pha
attribute vec4 aBlade2; // độ rộng (tỉ lệ), có bông (0 / 1), độ đậm (0.8 … 1.2), lệch khoảng cách (đơn vị)
uniform vec3 uCamPos;
uniform vec2 uRefTan;
uniform float uDist;
uniform float uFollow;
uniform float uTime;
uniform float uSway;
uniform float uWidth;
varying vec2 vST;
varying float vPlume;
varying float vShade;
varying vec2 vNdc;
void main() {
	float s = position.y;          // 0 chân → 1 ngọn
	float t = position.x;          // −1 … 1 ngang lá
	float travel = -uCamPos.z;
	float d0 = uDist + aBlade2.w;  // khoảng cách lúc camera ở gốc
	float zWorld = -( d0 + uFollow * travel );
	// Toạ độ theo đơn vị chiều cao khung ở khoảng cách d0 (khung tham chiếu), rồi đổi ra thế giới.
	float unit = uRefTan.y * d0 * 2.0;
	float h = aBlade.y;
	float sway = uSway * sin( uTime * 6.2832 / ( 6.0 + 3.0 * fract( aBlade.w * 0.159 ) ) + aBlade.x * 4.0 + aBlade.w );
	float bend = ( aBlade.z + sway ) * s * s * h;
	// Thân mảnh, bông lau phình mềm ở 35% trên.
	float stem = mix( 0.012, 0.005, s );
	float plume = aBlade2.y * smoothstep( 0.6, 0.8, s ) * ( 1.0 - smoothstep( 0.88, 1.0, s ) ) * 0.014;
	float w = ( stem + plume ) * aBlade2.x * uWidth;
	float xLocal = aBlade.x * uRefTan.x * d0 * 1.0 + ( bend + t * w ) * unit;
	float yLocal = ( -1.06 + 2.0 * s * h ) * uRefTan.y * d0;
	vec3 world = vec3( xLocal, yLocal, zWorld );
	vST = vec2( s, t );
	vPlume = aBlade2.y * smoothstep( 0.55, 0.8, s );
	vShade = aBlade2.z;
	vec4 c = projectionMatrix * viewMatrix * vec4( world, 1.0 );
	vNdc = c.xy / c.w;
	gl_Position = c;
}
`;

const FS = /* glsl */ `
uniform vec3 uColor;
uniform vec3 uGlow;
uniform vec2 uSunNdc;
uniform float uGlowAmount;
uniform float uAlpha;
uniform float uSoft;
varying vec2 vST;
varying float vPlume;
varying float vShade;
varying vec2 vNdc;
void main() {
	float s = vST.x;
	float at = abs( vST.y );
	// Mất nét: mép mềm rộng; bông lau mềm hơn nữa và lốm đốm như sợi.
	float edge = exp( -at * at * mix( 6.0, 2.0, uSoft ) ) * ( 1.0 - smoothstep( 0.75, 1.0, at ) );
	float a = edge * uAlpha * mix( 1.0, 0.8, vPlume );
	vec3 col = uColor * vShade * mix( 0.32, 1.0, smoothstep( 0.0, 0.7, s ) );
	// Bông lau ngược sáng: viền vàng, mạnh hơn khi ở gần phương mặt trời theo chiều ngang.
	float toward = exp( -abs( vNdc.x - uSunNdc.x ) * 1.1 );
	col += uGlow * vPlume * smoothstep( 0.7, 0.95, s ) * uGlowAmount * ( 0.35 + 0.65 * toward ) * ( 1.0 - at * 0.6 );
	gl_FragColor = vec4( col, a );
}
`;

/**
 * @param {object} THREE
 * @param {{palette:object, cfg:object, count:number, seed?:number}} opts cfg: CONFIG.reeds (đọc mỗi khung); count: số lá (tầng chất lượng).
 */
export function createReeds( THREE, { palette, cfg, count, seed = 7 } ) {
	const MAX = 220;
	let s = seed * 7919 + 13;
	const rnd = () => {
		s = ( s * 9301 + 49297 ) % 233280;
		return s / 233280;
	};
	// Lá: dải 2 cột × 9 hàng (thon, cong mượt).
	const SEG = 9;
	const pos = [];
	const idx = [];
	for ( let j = 0; j <= SEG; j++ ) {
		pos.push( -1, j / SEG, 0, 1, j / SEG, 0 );
		if ( j < SEG ) {
			const a = j * 2;
			idx.push( a, a + 1, a + 2, a + 1, a + 3, a + 2 );
		}
	}
	const geo = new THREE.InstancedBufferGeometry();
	geo.setAttribute( 'position', new THREE.Float32BufferAttribute( pos, 3 ) );
	geo.setIndex( idx );
	const aBlade = new Float32Array( MAX * 4 );
	const aBlade2 = new Float32Array( MAX * 4 );
	let n = 0;
	let guard = 0;
	while ( n < MAX && guard++ < MAX * 20 ) {
		const x = rnd() * 2.9 - 1.45;
		const edge = Math.min( 1, Math.abs( x ) );
		// Thưa ở giữa khung, dày ở hai góc.
		if ( rnd() > 0.25 + 0.75 * Math.pow( edge, 1.3 ) ) {
			continue;
		}
		const h = ( 0.035 + 0.125 * Math.pow( edge, 1.2 ) ) * ( 0.65 + 0.35 * rnd() );
		aBlade.set( [ x, h, ( rnd() - 0.5 ) * 0.22 - Math.sign( x ) * 0.05, rnd() * 6.2832 ], n * 4 );
		aBlade2.set( [ 0.7 + rnd() * 0.8, rnd() < 0.55 ? 1 : 0, 0.8 + rnd() * 0.4, ( rnd() - 0.5 ) * 24 ], n * 4 );
		n++;
	}
	geo.setAttribute( 'aBlade', new THREE.InstancedBufferAttribute( aBlade, 4 ) );
	geo.setAttribute( 'aBlade2', new THREE.InstancedBufferAttribute( aBlade2, 4 ) );
	geo.instanceCount = Math.min( n, count );

	const uniforms = {
		uCamPos: { value: new THREE.Vector3() },
		uRefTan: { value: new THREE.Vector2( 1, 1 ) },
		uDist: { value: cfg.distance },
		uFollow: { value: cfg.follow },
		uTime: { value: 0 },
		uSway: { value: cfg.sway },
		uWidth: { value: cfg.width },
		uColor: { value: palette.fog.clone().lerp( palette.ridgeBase, 0.86 ).multiplyScalar( cfg.shade ) },
		uGlow: { value: palette.rim.clone() },
		uSunNdc: { value: new THREE.Vector2() },
		uGlowAmount: { value: cfg.glow },
		uAlpha: { value: cfg.alpha },
		uSoft: { value: cfg.soft },
	};
	const material = new THREE.ShaderMaterial( {
		uniforms,
		vertexShader: VS,
		fragmentShader: FS,
		transparent: true,
		depthWrite: false,
		depthTest: false,
		side: THREE.DoubleSide,
	} );
	material.name = 'reeds';
	const mesh = new THREE.Mesh( geo, material );
	mesh.name = 'reeds';
	mesh.frustumCulled = false;
	mesh.renderOrder = 25;
	let total = n;
	return {
		mesh,
		setCount( c ) {
			geo.instanceCount = Math.max( 0, Math.min( total, c ) );
		},
		/**
		 * @param {{camera:object, refTan:{x:number,y:number}, time:number, sunNdc:{x:number,y:number}, rimIntensity:number, reduced:boolean}} f
		 */
		update( f ) {
			mesh.visible = !! cfg.enabled;
			uniforms.uCamPos.value.copy( f.camera.position );
			uniforms.uRefTan.value.set( f.refTan.x, f.refTan.y );
			uniforms.uDist.value = cfg.distance;
			uniforms.uFollow.value = cfg.follow;
			uniforms.uTime.value = f.reduced ? 0 : f.time;
			uniforms.uSway.value = f.reduced ? 0 : cfg.sway;
			uniforms.uWidth.value = cfg.width;
			uniforms.uSunNdc.value.set( f.sunNdc.x, f.sunNdc.y );
			uniforms.uGlowAmount.value = cfg.glow * ( 0.5 + 0.5 * f.rimIntensity );
			uniforms.uAlpha.value = cfg.alpha;
			uniforms.uSoft.value = cfg.soft;
		},
		setColors( pal ) {
			uniforms.uColor.value.copy( pal.fog ).lerp( pal.ridgeBase, 0.86 ).multiplyScalar( cfg.shade );
			uniforms.uGlow.value.copy( pal.rim );
		},
		dispose() {
			geo.dispose();
			material.dispose();
		},
	};
}
