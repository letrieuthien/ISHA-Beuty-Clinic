/**
 * Thảm thực vật: cỏ instanced, cây instanced và bóng cây trên mặt đất (mỗi loại một draw call). Mật độ theo bậc chất lượng (CONFIG.presets:
 * cỏ 60k / 30k / 0 với lite, cây 400 / 250 / 80); lite không có cỏ instanced, mặt đất tự vẽ thớ cỏ (terrain.js).
 *
 * Cỏ: lá 9 đỉnh (4 đoạn thon dần), cong tĩnh theo một hướng ngẫu nhiên, đặt dọc cả đường ray, dày ở gần và thưa dần ra xa. Pháp tuyến bo
 * tròn ngang lá (lá có khối, sáng tối theo hướng của từng lá thay vì phẳng một màu). Màu: gốc sẫm → ngọn sáng, mỗi lá lệch sáng / tối, mảng
 * cỏ tươi / cỏ khô theo noise thế giới; ngọn trong mờ ánh vàng khi ngược sáng; gió theo đợt (noise trôi) uốn ngọn và làm ngọn sáng lên khi
 * đợt gió đi qua (vệt gió lướt).
 * Cây: tán gồm 6 khối lá gồ ghề (icosahedron chia 1 lần, dịch đỉnh theo hàm sóng cố định nên các mặt khớp nhau) trên thân thon; một phần tư
 * là dáng cao thon. Mỗi cây một sắc (xanh sẫm → xanh ngả vàng), mỗi khối lá lệch nhẹ; cụm lá bằng noise 3D trong toạ độ cây (pháp tuyến +
 * màu), tự che tối phía dưới / trong tán, viền vàng và trong mờ khi ngược sáng (mặt trời sau cây), ngọn lay nhẹ. Bóng cây: elip mềm nghiêng
 * theo mặt đất, kéo về phía camera (nắng ngược), mờ dần trong sương.
 * Vị trí sinh bằng PRNG có seed nên mọi lần tải giống nhau. Ánh sáng và sương dùng chung (shading.js).
 */
import { mulberry32 } from './noise.js';
import { SHARED_GLSL } from './shading.js';

export function buildGrass( THREE, { count, height, colors, shared, seed, camZ, camEndZ = camZ } ) {
	if ( ! count ) {
		return null;
	}
	// Lá cỏ đơn vị: rộng 1, cao 1; 4 đoạn thon dần tới ngọn.
	const pos = new Float32Array( [
		-0.5, 0, 0, 0.5, 0, 0,
		-0.43, 0.3, 0, 0.43, 0.3, 0,
		-0.32, 0.58, 0, 0.32, 0.58, 0,
		-0.18, 0.82, 0, 0.18, 0.82, 0,
		0, 1, 0,
	] );
	const idx = [ 0, 1, 2, 1, 3, 2, 2, 3, 4, 3, 5, 4, 4, 5, 6, 5, 7, 6, 6, 7, 8 ];
	const geo = new THREE.InstancedBufferGeometry();
	geo.setAttribute( 'position', new THREE.BufferAttribute( pos, 3 ) );
	geo.setIndex( idx );
	geo.instanceCount = count;

	const rand = mulberry32( seed ^ 0x9e3779b9 );
	const offsets = new Float32Array( count * 3 );
	const params = new Float32Array( count * 4 );
	const vars = new Float32Array( count * 2 );
	// Phân bố theo cả đường ray: chọn một vị trí camera ảo dọc ray (camZ → camEndZ), rồi lấy khoảng cách phía trước lệch về gần (d^2.2)
	// trong khung nhìn (nửa bề ngang khoảng 0.8·d; d^2.6: dày ở gần, lá mảnh). Mép dưới khung nhìn thấy đất từ khoảng 25 đơn vị (camera ngẩng, chân trời 70% khung) nên
	// bắt đầu từ đó. Lá ở xa rộng và cao hơn để giữ độ phủ trên màn hình, sương làm chúng tan vào nền.
	for ( let i = 0; i < count; i++ ) {
		const zc = camEndZ + ( camZ - camEndZ ) * rand();
		const d = 24 + 470 * Math.pow( rand(), 2.6 );
		const z = zc - d;
		const x = ( rand() * 2 - 1 ) * 0.82 * ( d + 8 );
		offsets[ i * 3 ] = x;
		offsets[ i * 3 + 1 ] = height( x, z ) - 0.1;
		offsets[ i * 3 + 2 ] = z;
		params[ i * 4 ] = ( 0.7 + 1.5 * Math.pow( rand(), 1.4 ) ) * ( 1 + d / 300 ); // cao (đa số thấp, vài lá vươn cao)
		params[ i * 4 + 1 ] = ( 0.04 + 0.05 * rand() ) * ( 1 + d / 36 );          // rộng (lá mảnh ở gần)
		params[ i * 4 + 2 ] = rand() * Math.PI;                                  // xoay
		params[ i * 4 + 3 ] = rand() * Math.PI * 2;                              // pha gió
		vars[ i * 2 ] = rand();                                                  // lệch màu
		vars[ i * 2 + 1 ] = rand() * 2 - 1;                                      // độ cong tĩnh
	}
	geo.setAttribute( 'aOffset', new THREE.InstancedBufferAttribute( offsets, 3 ) );
	geo.setAttribute( 'aParams', new THREE.InstancedBufferAttribute( params, 4 ) );
	geo.setAttribute( 'aVar', new THREE.InstancedBufferAttribute( vars, 2 ) );

	const uniforms = {
		...shared,
		uRoot: { value: colors.green.clone() },
		uTip: { value: colors.greenLight.clone() },
		uGold: { value: colors.golden.clone() },
		uDry: { value: colors.greenLight.clone().lerp( colors.golden, 0.45 ) },
		uWind: { value: 1 },
	};

	const material = new THREE.ShaderMaterial( {
		name: 'IshaGrass',
		uniforms,
		side: THREE.DoubleSide,
		vertexShader: SHARED_GLSL + /* glsl */ `
			attribute vec3 aOffset;
			attribute vec4 aParams;
			attribute vec2 aVar;
			uniform float uWind;
			varying float vT;
			varying vec3 vWorld;
			varying vec3 vN;
			varying float vVar;
			varying float vGust;
			void main() {
				float t = position.y;
				float c = cos( aParams.z );
				float s = sin( aParams.z );
				vec3 p = vec3( position.x * aParams.y, t * aParams.x, 0.0 );
				// Cong tĩnh: ngọn ngả theo pháp tuyến của lá (lá không thẳng đứng như cọc).
				p.z += t * t * aParams.x * 0.3 * aVar.y;
				p.y -= t * t * aParams.x * 0.06 * abs( aVar.y );
				p.xz = mat2( c, -s, s, c ) * p.xz;
				// Gió: đợt gió là noise trôi chậm trên mặt đất (các lá gần nhau lay cùng đợt), cộng sóng nhỏ; chỉ uốn ngọn (t^2).
				float gust = ishaNoise( aOffset.xz * 0.012 + vec2( uTime * 0.16, uTime * 0.05 ) );
				float wave = sin( dot( aOffset.xz, vec2( 0.045, 0.028 ) ) + uTime * 1.7 + aParams.w * 0.35 ) * 0.5 + 0.5;
				float bend = ( 0.2 + 0.8 * gust ) * ( 0.65 + 0.35 * wave ) * t * t * aParams.x * 0.42 * uWind;
				p.x += bend * 0.8;
				p.z += bend * 0.35;
				p.y -= bend * 0.2;
				vec3 world = aOffset + p;
				// Pháp tuyến bo tròn: pháp tuyến mặt lá nghiêng sang hai mép theo position.x, cộng thành phần hướng lên.
				vec3 side = vec3( c, 0.0, -s );
				vec3 face = vec3( s, 0.0, c );
				vN = normalize( face * 0.45 + side * position.x * 1.4 + vec3( 0.0, 0.85, 0.0 ) );
				vT = t;
				vWorld = world;
				vVar = aVar.x;
				vGust = gust;
				gl_Position = projectionMatrix * viewMatrix * vec4( world, 1.0 );
			}
		`,
		fragmentShader: SHARED_GLSL + /* glsl */ `
			uniform vec3 uRoot;
			uniform vec3 uTip;
			uniform vec3 uGold;
			uniform vec3 uDry;
			varying float vT;
			varying vec3 vWorld;
			varying vec3 vN;
			varying float vVar;
			varying float vGust;
			void main() {
				vec3 n = normalize( gl_FrontFacing ? vN : vec3( -vN.x, vN.y, -vN.z ) );
				// Mảng cỏ tươi / cỏ khô theo noise thế giới + lệch riêng từng lá.
				float patchN = ishaNoise( vWorld.xz * 0.02 ) * 0.65 + ishaNoise( vWorld.xz * 0.11 ) * 0.35;
				vec3 tip = mix( uTip, uDry, smoothstep( 0.5, 0.95, patchN * 0.75 + vVar * 0.45 ) );
				vec3 root = uRoot * mix( 0.5, 0.78, vVar );
				vec3 base = mix( root, tip, smoothstep( 0.0, 0.95, vT ) ) * mix( 0.84, 1.1, fract( vVar * 5.31 ) );
				vec3 V = normalize( cameraPosition - vWorld );
				float back = pow( max( dot( -V, uSunDir ), 0.0 ), 3.0 );
				// Gốc tối (lá che nhau), ngọn sáng.
				float ao = mix( 0.42, 1.0, smoothstep( 0.0, 0.65, vT ) );
				vec3 col = ishaLight( base, n, ao );
				// Trong mờ: lá mỏng ngược sáng sáng lên ánh vàng về phía ngọn.
				col += uSunColor * uSunIntensity * mix( uTip, uGold, 0.65 ) * back * vT * vT * 0.2 * uLightScale;
				// Vệt gió lướt: đợt gió mạnh làm ngọn sáng lên (lá lật mặt).
				col *= 1.0 + smoothstep( 0.55, 0.95, vGust ) * vT * 0.16;
				col = ishaAerial( col, vWorld, 0.0 );
				gl_FragColor = vec4( col, 1.0 );
				#include <tonemapping_fragment>
				#include <colorspace_fragment>
			}
		`,
	} );

	const mesh = new THREE.Mesh( geo, material );
	mesh.name = 'grass';
	mesh.frustumCulled = false;

	return {
		mesh,
		uniforms,
		// Governor: thưa cỏ bằng instanceCount. Vị trí lá sinh ngẫu nhiên đều dọc ray nên cắt đuôi = thưa đều, không dựng lại.
		setCount( c ) {
			geo.instanceCount = Math.max( 0, Math.min( count, c ) );
			mesh.visible = geo.instanceCount > 0;
		},
		setColors( c ) {
			uniforms.uRoot.value.copy( c.green );
			uniforms.uTip.value.copy( c.greenLight );
			uniforms.uGold.value.copy( c.golden );
			uniforms.uDry.value.copy( c.greenLight ).lerp( c.golden, 0.45 );
		},
		dispose() {
			geo.dispose();
			material.dispose();
		},
	};
}

// Tán cây: các khối lá [tâm x, y, z, bán kính] trong toạ độ cây (gốc ở mặt đất, cao khoảng 2.6).
const LOBES = [
	[ 0, 1.4, 0, 0.95 ],
	[ 0.62, 1.25, 0.22, 0.66 ],
	[ -0.58, 1.27, -0.25, 0.68 ],
	[ 0.18, 1.2, -0.6, 0.6 ],
	[ -0.22, 1.22, 0.58, 0.6 ],
	[ 0.06, 1.98, 0.04, 0.62 ],
];

// Mặt khối lá gồ ghề: hàm sóng cố định theo hướng đỉnh (cùng một điểm luôn ra cùng độ dịch nên các mặt khớp nhau, không nứt).
function bump( x, y, z, k ) {
	return 0.17 * Math.sin( 4.1 * x + 1.3 + k ) * Math.sin( 3.7 * y + 0.4 ) * Math.sin( 4.4 * z + 2.2 - k ) +
		0.08 * Math.sin( 9.3 * x + 0.7 ) * Math.sin( 8.1 * z + 1.9 + k ) * Math.sin( 7.7 * y + 2.6 );
}

function buildTreeGeometry( THREE ) {
	const position = [];
	const normal = [];
	const part = [];
	const lobe = [];
	LOBES.forEach( ( [ cx, cy, cz, r ], li ) => {
		const g = new THREE.IcosahedronGeometry( 1, 1 );
		const p = g.attributes.position;
		for ( let i = 0; i < p.count; i++ ) {
			const x = p.getX( i );
			const y = p.getY( i );
			const z = p.getZ( i );
			const k = r * ( 1 + bump( x, y, z, li * 1.7 ) );
			position.push( cx + x * k, cy + y * k, cz + z * k );
			normal.push( x, y, z );
			part.push( 1 );
			lobe.push( ( li * 0.37 ) % 1 );
		}
		g.dispose();
	} );
	const trunk = new THREE.CylinderGeometry( 0.07, 0.12, 1.2, 6, 1, true ).toNonIndexed();
	trunk.translate( 0, 0.4, 0 );
	const tp = trunk.attributes.position;
	const tn = trunk.attributes.normal;
	for ( let i = 0; i < tp.count; i++ ) {
		position.push( tp.getX( i ), tp.getY( i ), tp.getZ( i ) );
		normal.push( tn.getX( i ), tn.getY( i ), tn.getZ( i ) );
		part.push( 0 );
		lobe.push( 0 );
	}
	trunk.dispose();
	const geo = new THREE.BufferGeometry();
	geo.setAttribute( 'position', new THREE.Float32BufferAttribute( position, 3 ) );
	geo.setAttribute( 'normal', new THREE.Float32BufferAttribute( normal, 3 ) );
	geo.setAttribute( 'aPart', new THREE.Float32BufferAttribute( part, 1 ) );
	geo.setAttribute( 'aLobe', new THREE.Float32BufferAttribute( lobe, 1 ) );
	return geo;
}

const NOISE3 = /* glsl */ `
float ishaHash3( vec3 p ) {
	p = fract( p * 0.3183099 + 0.1 );
	p *= 17.0;
	return fract( p.x * p.y * p.z * ( p.x + p.y + p.z ) );
}
float ishaNoise3( vec3 x ) {
	vec3 i = floor( x );
	vec3 f = fract( x );
	f = f * f * ( 3.0 - 2.0 * f );
	return mix(
		mix( mix( ishaHash3( i ), ishaHash3( i + vec3( 1.0, 0.0, 0.0 ) ), f.x ), mix( ishaHash3( i + vec3( 0.0, 1.0, 0.0 ) ), ishaHash3( i + vec3( 1.0, 1.0, 0.0 ) ), f.x ), f.y ),
		mix( mix( ishaHash3( i + vec3( 0.0, 0.0, 1.0 ) ), ishaHash3( i + vec3( 1.0, 0.0, 1.0 ) ), f.x ), mix( ishaHash3( i + vec3( 0.0, 1.0, 1.0 ) ), ishaHash3( i + vec3( 1.0, 1.0, 1.0 ) ), f.x ), f.y ),
		f.z );
}
`;

export function buildTrees( THREE, { count, height, colors, shared, seed, camZ, azMin = 14 } ) {
	if ( ! count ) {
		return null;
	}
	const geo = buildTreeGeometry( THREE );
	const tints = new Float32Array( count );
	geo.setAttribute( 'aTint', new THREE.InstancedBufferAttribute( tints, 1 ) );

	const leafColors = ( c ) => ( {
		leaf: c.green.clone().lerp( c.shadow, 0.18 ),
		light: c.greenLight.clone(),
		warm: c.greenLight.clone().lerp( c.golden, 0.5 ),
		trunk: c.evening.clone().lerp( c.shadow, 0.55 ),
		shade: c.green.clone().lerp( c.shadow, 0.6 ).multiplyScalar( 0.5 ),
	} );
	const lc = leafColors( colors );
	const uniforms = {
		...shared,
		uLeaf: { value: lc.leaf },
		uLeafLight: { value: lc.light },
		uLeafWarm: { value: lc.warm },
		uTrunk: { value: lc.trunk },
		uWind: { value: 1 },
	};
	const material = new THREE.ShaderMaterial( {
		name: 'IshaTrees',
		uniforms,
		vertexShader: /* glsl */ `
			attribute float aPart;
			attribute float aLobe;
			attribute float aTint;
			uniform float uTime;
			uniform float uWind;
			varying vec3 vWorld;
			varying vec3 vNormal;
			varying vec3 vLocal;
			varying float vPart;
			varying float vLobe;
			varying float vTint;
			void main() {
				vec3 p = position;
				// Ngọn lay nhẹ, mỗi cây một pha (theo vị trí đặt).
				float phase = instanceMatrix[ 3 ].x * 0.05 + instanceMatrix[ 3 ].z * 0.031;
				p.x += sin( uTime * 0.9 + phase ) * 0.04 * max( p.y - 0.8, 0.0 ) * aPart * uWind;
				vec4 wp = modelMatrix * instanceMatrix * vec4( p, 1.0 );
				vWorld = wp.xyz;
				vNormal = normalize( mat3( modelMatrix ) * mat3( instanceMatrix ) * normal );
				vLocal = position;
				vPart = aPart;
				vLobe = aLobe;
				vTint = aTint;
				gl_Position = projectionMatrix * viewMatrix * wp;
			}
		`,
		fragmentShader: SHARED_GLSL + NOISE3 + /* glsl */ `
			uniform vec3 uLeaf;
			uniform vec3 uLeafLight;
			uniform vec3 uLeafWarm;
			uniform vec3 uTrunk;
			varying vec3 vWorld;
			varying vec3 vNormal;
			varying vec3 vLocal;
			varying float vPart;
			varying float vLobe;
			varying float vTint;
			void main() {
				vec3 V = normalize( cameraPosition - vWorld );
				vec3 n = normalize( vNormal );
				vec3 col;
				if ( vPart < 0.5 ) {
					col = ishaLight( uTrunk, n, 0.55 );
				} else {
					// Cụm lá: noise 3D trong toạ độ cây, hai tầng; lệch pháp tuyến để từng cụm có mặt sáng / mặt tối.
					vec3 q = vLocal * 4.2 + vLobe * 11.0 + vTint * 5.0;
					float n1 = ishaNoise3( q );
					float n2 = ishaNoise3( vLocal * 10.0 + 3.7 );
					float cl = n1 * 0.65 + n2 * 0.35;
					vec3 g = vec3( ishaNoise3( q + 3.1 ), ishaNoise3( q + 7.7 ), ishaNoise3( q + 1.3 ) ) - 0.5;
					n = normalize( n + g * 1.1 );
					// Sắc riêng từng cây (xanh sẫm → xanh ngả vàng), lệch theo khối lá và cụm lá.
					vec3 base = mix( uLeaf, uLeafLight, clamp( cl * 0.6 + vLobe * 0.25, 0.0, 1.0 ) );
					base = mix( base, uLeafWarm, smoothstep( 0.55, 1.0, vTint ) * 0.55 );
					base *= mix( 0.8, 1.1, fract( vTint * 7.31 ) );
					// Tự che: dưới tán và khe giữa các cụm tối hơn.
					float ao = mix( 0.42, 1.0, smoothstep( 0.6, 2.4, vLocal.y ) ) * mix( 0.62, 1.0, smoothstep( 0.3, 0.62, cl ) );
					col = ishaLight( base, n, ao );
					// Nắng ngược (mặt trời sau cây): viền vàng ở mép tán + cụm lá mỏng trong mờ.
					float toward = max( dot( -V, uSunDir ), 0.0 );
					float rim = pow( 1.0 - max( dot( V, n ), 0.0 ), 3.0 ) * pow( toward, 2.0 );
					float trans = pow( toward, 6.0 ) * smoothstep( 0.35, 0.8, cl ) * smoothstep( 1.0, 2.2, vLocal.y );
					col += uSunColor * uSunIntensity * mix( uLeafLight, uLeafWarm, 0.6 ) * ( rim * 0.22 + trans * 0.08 ) * uLightScale;
				}
				col = ishaAerial( col, vWorld, 0.0 );
				gl_FragColor = vec4( col, 1.0 );
				#include <tonemapping_fragment>
				#include <colorspace_fragment>
			}
		`,
	} );

	// Bóng cây: elip mềm nghiêng theo mặt đất, kéo về phía camera (nắng ngược), mờ trong sương.
	const shadowGeo = new THREE.PlaneGeometry( 1, 1 );
	shadowGeo.rotateX( -Math.PI / 2 );
	const shadowUniforms = { ...shared, uShade: { value: lc.shade } };
	const shadowMat = new THREE.ShaderMaterial( {
		name: 'IshaTreeShadows',
		uniforms: shadowUniforms,
		transparent: true,
		depthWrite: false,
		polygonOffset: true,
		polygonOffsetFactor: -2,
		polygonOffsetUnits: -2,
		vertexShader: /* glsl */ `
			varying vec2 vUv;
			varying vec3 vWorld;
			void main() {
				vUv = uv;
				vec4 wp = modelMatrix * instanceMatrix * vec4( position, 1.0 );
				vWorld = wp.xyz;
				gl_Position = projectionMatrix * viewMatrix * wp;
			}
		`,
		fragmentShader: /* glsl */ `
			uniform vec3 uShade;
			uniform float uFogDensity;
			varying vec2 vUv;
			varying vec3 vWorld;
			void main() {
				vec2 q = vUv * 2.0 - 1.0;
				float a = smoothstep( 1.0, 0.1, length( q ) ) * smoothstep( -1.0, -0.4, q.y );
				float fogAmt = 1.0 - exp( -length( vWorld - cameraPosition ) * uFogDensity * 0.00036 );
				gl_FragColor = vec4( uShade, a * 0.42 * ( 1.0 - fogAmt ) );
				#include <tonemapping_fragment>
				#include <colorspace_fragment>
			}
		`,
	} );

	const mesh = new THREE.InstancedMesh( geo, material, count );
	mesh.name = 'trees';
	const shadows = new THREE.InstancedMesh( shadowGeo, shadowMat, count );
	shadows.name = 'tree-shadows';
	shadows.renderOrder = 1;
	const rand = mulberry32( seed ^ 0x5bd1e995 );
	const m = new THREE.Matrix4();
	const q = new THREE.Quaternion();
	const up = new THREE.Vector3( 0, 1, 0 );
	const sc = new THREE.Vector3();
	const p = new THREE.Vector3();
	const nrm = new THREE.Vector3();
	let placed = 0;
	// Cụm cây: tâm cụm ở trung cảnh hai bên (lệch phương vị azMin đến 55°; khung dọc hẹp dùng azMin nhỏ để vẫn thấy), cách 380 đến 1700,
	// mỗi cụm 3 đến 9 cây. Cây là yếu tố phụ: nhỏ, xa, chìm sương.
	while ( placed < count ) {
		const side = rand() < 0.5 ? -1 : 1;
		const az = THREE.MathUtils.degToRad( azMin + rand() * ( 55 - azMin ) ) * side;
		const dist = 380 + rand() * 1320;
		const cx = Math.sin( az ) * dist;
		const cz = camZ - Math.cos( az ) * dist;
		const k = 3 + Math.floor( rand() * 7 );
		for ( let j = 0; j < k && placed < count; j++ ) {
			const x = cx + ( rand() - 0.5 ) * 70;
			const z = cz + ( rand() - 0.5 ) * 70;
			const r = 2.4 + rand() * 3.2;
			const tall = rand() < 0.25;
			const ground = height( x, z );
			p.set( x, ground - 0.15 * r, z );
			q.setFromAxisAngle( up, rand() * Math.PI * 2 );
			if ( tall ) {
				sc.set( r * 0.62, r * 1.55, r * 0.62 );
			} else {
				sc.set( r, r * ( 0.95 + rand() * 0.3 ), r * ( 0.9 + rand() * 0.2 ) );
			}
			m.compose( p, q, sc );
			mesh.setMatrixAt( placed, m );
			tints[ placed ] = rand();
			// Bóng: nghiêng theo pháp tuyến mặt đất (sai phân hữu hạn), tâm lệch về phía camera.
			const e = 2;
			nrm.set( height( x - e, z ) - height( x + e, z ), 2 * e, height( x, z - e ) - height( x, z + e ) ).normalize();
			const sz = z + r * 1.3;
			p.set( x, height( x, sz ) + 0.2, sz );
			q.setFromUnitVectors( up, nrm );
			sc.set( r * ( tall ? 1.6 : 2.5 ), 1, r * 4.2 );
			m.compose( p, q, sc );
			shadows.setMatrixAt( placed, m );
			placed++;
		}
	}
	mesh.instanceMatrix.needsUpdate = true;
	shadows.instanceMatrix.needsUpdate = true;
	mesh.computeBoundingSphere();
	shadows.computeBoundingSphere();
	const group = new THREE.Group();
	group.name = 'trees-group';
	group.add( shadows, mesh );

	return {
		mesh: group,
		uniforms,
		// Governor: bớt cây bằng InstancedMesh.count (cụm sinh ngẫu nhiên hai bên nên cắt đuôi vẫn cân), không dựng lại.
		setCount( c ) {
			const n = Math.max( 0, Math.min( count, c ) );
			mesh.count = n;
			shadows.count = n;
			group.visible = n > 0;
		},
		setColors( c ) {
			const l = leafColors( c );
			uniforms.uLeaf.value.copy( l.leaf );
			uniforms.uLeafLight.value.copy( l.light );
			uniforms.uLeafWarm.value.copy( l.warm );
			uniforms.uTrunk.value.copy( l.trunk );
			shadowUniforms.uShade.value.copy( l.shade );
		},
		dispose() {
			geo.dispose();
			material.dispose();
			shadowGeo.dispose();
			shadowMat.dispose();
		},
	};
}
