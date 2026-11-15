/**
 * Thảm thực vật: cỏ instanced và cây impostor (một draw call mỗi loại). Mật độ theo tầng chất lượng (CONFIG.presets: cỏ 60k / 30k / 0 với
 * lite, cây 400 / 250 / 80); lite không có cỏ instanced, mặt đất tự vẽ sọc cỏ (terrain.js).
 *
 * Cỏ: lá 7 đỉnh (3 đoạn thon dần), đặt trên đồng cỏ trước camera, dày ở gần và thưa dần ra xa (khoảng cách lấy mẫu lệch về gần). Gió ở
 * vertex shader theo toạ độ thế giới XZ (các lá gần nhau lay cùng đợt), chỉ uốn phần ngọn. Màu gốc → ngọn: xanh phái sinh → xanh nhạt,
 * ngọn ngả vàng khi ngược sáng. Có ánh sáng và sương dùng chung (shading.js).
 * Cây: cụm tán ellipsoid low-poly (IcosahedronGeometry) ở trung cảnh hai bên lối nhìn (tránh cột giữa của mặt trời và dãy card), pháp tuyến
 * nhiễu theo vị trí cho tán có khối, viền vàng phía ngược sáng.
 * Vị trí sinh bằng PRNG có seed nên mọi lần tải giống nhau.
 */
import { mulberry32 } from './noise.js';
import { SHARED_GLSL } from './shading.js';

export function buildGrass( THREE, { count, height, colors, shared, seed, camZ, camEndZ = camZ } ) {
	if ( ! count ) {
		return null;
	}
	// Lá cỏ đơn vị: rộng 1, cao 1; 3 đoạn thon dần tới ngọn.
	const pos = new Float32Array( [
		-0.5, 0, 0, 0.5, 0, 0,
		-0.4, 0.4, 0, 0.4, 0.4, 0,
		-0.25, 0.75, 0, 0.25, 0.75, 0,
		0, 1, 0,
	] );
	const idx = [ 0, 1, 2, 1, 3, 2, 2, 3, 4, 3, 5, 4, 4, 5, 6 ];
	const geo = new THREE.InstancedBufferGeometry();
	geo.setAttribute( 'position', new THREE.BufferAttribute( pos, 3 ) );
	geo.setIndex( idx );
	geo.instanceCount = count;

	const rand = mulberry32( seed ^ 0x9e3779b9 );
	const offsets = new Float32Array( count * 3 );
	const params = new Float32Array( count * 4 );
	// Phân bố theo cả đường ray: chọn một vị trí camera ảo dọc ray (camZ → camEndZ), rồi lấy khoảng cách phía trước lệch về gần (d^2.2)
	// trong khung nhìn (nửa bề ngang khoảng 0.8·d). Mọi điểm dừng đều có cỏ dày ngay trước mặt. Lá ở xa rộng và cao hơn một chút để giữ độ
	// phủ trên màn hình (lá thật ở 300 m nhỏ hơn 1 px), sương làm chúng tan vào nền.
	for ( let i = 0; i < count; i++ ) {
		const zc = camEndZ + ( camZ - camEndZ ) * rand();
		const d = 14 + 480 * Math.pow( rand(), 2.2 );
		const z = zc - d;
		const x = ( rand() * 2 - 1 ) * 0.82 * ( d + 8 );
		offsets[ i * 3 ] = x;
		offsets[ i * 3 + 1 ] = height( x, z ) - 0.1;
		offsets[ i * 3 + 2 ] = z;
		params[ i * 4 ] = ( 0.8 + 1.4 * rand() ) * ( 1 + d / 300 );   // cao
		params[ i * 4 + 1 ] = ( 0.08 + 0.08 * rand() ) * ( 1 + d / 45 ); // rộng
		params[ i * 4 + 2 ] = rand() * Math.PI;                       // xoay
		params[ i * 4 + 3 ] = rand() * Math.PI * 2;                   // pha gió
	}
	geo.setAttribute( 'aOffset', new THREE.InstancedBufferAttribute( offsets, 3 ) );
	geo.setAttribute( 'aParams', new THREE.InstancedBufferAttribute( params, 4 ) );

	const uniforms = {
		...shared,
		uRoot: { value: colors.green.clone() },
		uTip: { value: colors.greenLight.clone() },
		uGold: { value: colors.golden.clone() },
		uWind: { value: 1 },
	};

	const material = new THREE.ShaderMaterial( {
		name: 'IshaGrass',
		uniforms,
		side: THREE.DoubleSide,
		vertexShader: /* glsl */ `
			attribute vec3 aOffset;
			attribute vec4 aParams;
			uniform float uTime;
			uniform float uWind;
			varying float vT;
			varying vec3 vWorld;
			void main() {
				float t = position.y;
				vec3 p = vec3( position.x * aParams.y, t * aParams.x, 0.0 );
				float c = cos( aParams.z );
				float s = sin( aParams.z );
				p.xz = mat2( c, -s, s, c ) * p.xz;
				// Gió: sóng theo XZ thế giới (lá gần nhau lay cùng đợt) nhân đợt gió lớn chậm; chỉ uốn ngọn (t^2).
				float wave = sin( dot( aOffset.xz, vec2( 0.045, 0.028 ) ) + uTime * 1.7 + aParams.w * 0.35 ) * 0.5 + 0.5;
				float gust = sin( aOffset.x * 0.012 - aOffset.z * 0.006 + uTime * 0.4 ) * 0.5 + 0.5;
				float bend = ( 0.3 + 0.7 * gust ) * wave * t * t * aParams.x * 0.38 * uWind;
				p.x += bend * 0.8;
				p.z += bend * 0.35;
				p.y -= bend * 0.18;
				vec3 world = aOffset + p;
				vT = t;
				vWorld = world;
				gl_Position = projectionMatrix * viewMatrix * vec4( world, 1.0 );
			}
		`,
		fragmentShader: SHARED_GLSL + /* glsl */ `
			uniform vec3 uRoot;
			uniform vec3 uTip;
			uniform vec3 uGold;
			varying float vT;
			varying vec3 vWorld;
			void main() {
				vec3 base = mix( uRoot * 0.75, uTip, vT );
				vec3 V = normalize( cameraPosition - vWorld );
				float back = pow( max( dot( -V, uSunDir ), 0.0 ), 3.0 );
				base = mix( base, uGold, vT * vT * ( 0.1 + back * 0.4 ) );    // ngọn ngả vàng nhẹ, rõ hơn khi ngược sáng
				vec3 col = ishaLight( base, vec3( 0.0, 1.0, 0.0 ), mix( 0.75, 1.0, vT ) );
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
		setColors( c ) {
			uniforms.uRoot.value.copy( c.green );
			uniforms.uTip.value.copy( c.greenLight );
			uniforms.uGold.value.copy( c.golden );
		},
		dispose() {
			geo.dispose();
			material.dispose();
		},
	};
}

export function buildTrees( THREE, { count, height, colors, shared, seed, camZ, azMin = 14 } ) {
	if ( ! count ) {
		return null;
	}
	const geo = new THREE.IcosahedronGeometry( 1, 2 );
	const uniforms = {
		...shared,
		uLeaf: { value: colors.green.clone().lerp( colors.shadow, 0.12 ) },
		uLeafLight: { value: colors.greenLight.clone() },
	};
	const material = new THREE.ShaderMaterial( {
		name: 'IshaTrees',
		uniforms,
		vertexShader: /* glsl */ `
			varying vec3 vWorld;
			varying vec3 vNormal;
			void main() {
				vec4 wp = modelMatrix * instanceMatrix * vec4( position, 1.0 );
				vWorld = wp.xyz;
				vNormal = normalize( mat3( modelMatrix ) * mat3( instanceMatrix ) * normal );
				gl_Position = projectionMatrix * viewMatrix * wp;
			}
		`,
		fragmentShader: SHARED_GLSL + /* glsl */ `
			uniform vec3 uLeaf;
			uniform vec3 uLeafLight;
			varying vec3 vWorld;
			varying vec3 vNormal;
			void main() {
				// Pháp tuyến nhiễu theo vị trí: tán cây có các khối lá.
				float nx = ishaNoise( vWorld.xy * 0.35 ) - 0.5;
				float nz = ishaNoise( vWorld.zy * 0.35 + 7.0 ) - 0.5;
				vec3 n = normalize( vNormal + vec3( nx, 0.0, nz ) * 0.9 );
				float clump = ishaNoise( vWorld.xz * 0.25 + vWorld.y * 0.3 );
				vec3 base = mix( uLeaf, uLeafLight, clump * 0.6 );
				vec3 col = ishaLight( base, n, mix( 0.7, 1.0, clamp( n.y * 0.5 + 0.5, 0.0, 1.0 ) ) );
				vec3 V = normalize( cameraPosition - vWorld );
				float back = pow( max( dot( -V, uSunDir ), 0.0 ), 3.0 );
				float rim = pow( 1.0 - max( dot( V, n ), 0.0 ), 2.5 );
				col += uSunColor * rim * back * 0.45;
				col = ishaAerial( col, vWorld, 0.0 );
				gl_FragColor = vec4( col, 1.0 );
				#include <tonemapping_fragment>
				#include <colorspace_fragment>
			}
		`,
	} );

	const mesh = new THREE.InstancedMesh( geo, material, count );
	mesh.name = 'trees';
	const rand = mulberry32( seed ^ 0x5bd1e995 );
	const m = new THREE.Matrix4();
	const q = new THREE.Quaternion();
	const sc = new THREE.Vector3();
	const p = new THREE.Vector3();
	let placed = 0;
	// Cụm cây: tâm cụm ở trung cảnh hai bên (lệch phương vị azMin đến 55°; khung dọc hẹp dùng azMin nhỏ để vẫn thấy), cách 380 đến 1700,
	// mỗi cụm 3 đến 9 tán. Cây là yếu tố phụ: nhỏ, xa, chìm sương.
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
			const r = 3 + rand() * 4.5;
			p.set( x, height( x, z ) + r * 0.9, z );
			q.setFromAxisAngle( sc.set( 0, 1, 0 ), rand() * Math.PI );
			sc.set( r, r * ( 1.15 + rand() * 0.4 ), r );
			m.compose( p, q, sc );
			mesh.setMatrixAt( placed++, m );
		}
	}
	mesh.instanceMatrix.needsUpdate = true;
	mesh.computeBoundingSphere();

	return {
		mesh,
		uniforms,
		setColors( c ) {
			uniforms.uLeaf.value.copy( c.green ).lerp( c.shadow, 0.12 );
			uniforms.uLeafLight.value.copy( c.greenLight );
		},
		dispose() {
			geo.dispose();
			material.dispose();
		},
	};
}
