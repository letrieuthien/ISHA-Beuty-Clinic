/**
 * Địa hình procedural: PlaneGeometry (256² desktop, 128² lite), cao độ tính trên CPU MỘT lần lúc tải.
 *
 * Cao độ = núi (mặt nạ Gaussian: đỉnh chính ở (0, peakZ) + núi phụ xếp vòng cung hai bên, thấp dần ra ngoài) x (0.8 + ridged multifractal)
 *        x valleyFactor (làm phẳng đồng cỏ quanh camera) + đồi xa (che đường chân trời) + fbm chi tiết.
 * Bất biến: điểm cao nhất nằm trong bán kính peakRadius quanh đỉnh chính; nếu không, tăng biên độ đỉnh chính và sinh lại (tối đa 3 lần).
 *
 * Shader tự chiếu sáng (không dùng light/shadow của three): màu theo độ cao/độ dốc (cỏ xanh phái sinh → đá Dawn/Evening → đỉnh vàng nhạt),
 * ánh nắng wrap + ánh trời bán cầu, AO giả theo độ cao/độ dốc, viền sáng vàng phía ngược sáng, sương khí quyển theo khoảng cách và độ cao
 * (màu sương ngả về màu mặt trời khi nhìn về phía mặt trời). Có tone mapping và colorspace của renderer.
 */
import { createPerlin } from './noise.js';
import { SHARED_GLSL } from './shading.js';

const smooth = ( a, b, x ) => {
	const t = Math.min( 1, Math.max( 0, ( x - a ) / ( b - a ) ) );
	return t * t * ( 3 - 2 * t );
};

export function createHeightField( seed, cfg, octaves, peakBoost = 1 ) {
	const P = createPerlin( seed );
	const pz = cfg.peakZ;
	// Núi phụ: [x, z, hệ số cao so với đỉnh chính, bán kính].
	const secondary = [
		[ -470, pz + 170, 0.6, 300 ], [ 470, pz + 150, 0.58, 300 ],
		[ -900, pz + 380, 0.42, 380 ], [ 880, pz + 400, 0.44, 380 ],
		[ -1350, pz + 620, 0.28, 440 ], [ 1330, pz + 640, 0.27, 440 ],
	];
	const meadowZ = cfg.meadowZ;

	// Ghép các khối núi kiểu "lấy cao hơn" (cộng thêm 15% của khối thấp hơn để chỗ nối mềm), KHÔNG cộng dồn: cộng dồn làm yên ngựa giữa đỉnh
	// chính và núi phụ cao hơn cả hai đỉnh (đã gặp với seed 10866). Đỉnh chính thấp nhất ~0.9 x peakHeight, núi phụ cao nhất ~0.72 x
	// peakHeight + phần nối, nên đỉnh chính luôn cao nhất.
	// Dạng một khối núi: nửa Gaussian (thân rộng) + nửa hàm mũ (đỉnh nhọn như nón) để không thành mái vòm tròn như đồi cát.
	const shape = ( d2, R ) => 0.5 * Math.exp( -d2 / ( 2 * R * R ) ) + 0.5 * Math.exp( -Math.sqrt( d2 ) / ( R * 0.62 ) );

	function height( x, z ) {
		const r = P.ridged( x, z, octaves, 1 / 430 );
		const dx = x;
		const dz = z - pz;
		const main = shape( dx * dx + dz * dz, cfg.peakRadius ) * cfg.peakHeight * peakBoost * ( 0.9 + 0.3 * r );
		let sec = 0;
		for ( const s of secondary ) {
			const sx = x - s[ 0 ];
			const sz = z - s[ 1 ];
			sec = Math.max( sec, shape( sx * sx + sz * sz, s[ 3 ] ) * cfg.peakHeight * s[ 2 ] * ( 0.7 + 0.5 * r ) );
		}
		const mountains = Math.max( main, sec ) + 0.15 * Math.min( main, sec );
		const md = Math.hypot( x * 0.8, z - meadowZ );
		const valley = smooth( cfg.meadowRadius * 0.45, cfg.meadowRadius * 1.6, md );
		const hills = ( P.fbm( x, z, 4, 1 / 900 ) * 0.5 + 0.5 ) * 150 * smooth( 900, 2300, md );
		const detail = P.fbm( x, z, 3, 1 / 170 ) * cfg.detailAmp;
		return Math.max( mountains, hills ) * valley + detail * ( 0.35 + 0.65 * valley );
	}

	return { height };
}

export function buildTerrain( THREE, { seed, cfg, grid, octaves, colors, shared } ) {
	let field;
	let geo;
	let info;
	let boost = 1;
	for ( let attempt = 0; attempt < 3; attempt++ ) {
		field = createHeightField( seed, cfg, octaves, boost );
		geo = new THREE.PlaneGeometry( cfg.size, cfg.size, grid, grid );
		geo.rotateX( -Math.PI / 2 );
		const pos = geo.attributes.position;
		let max = -Infinity;
		let mx = 0;
		let mz = 0;
		for ( let i = 0; i < pos.count; i++ ) {
			const x = pos.getX( i );
			const z = pos.getZ( i );
			const h = field.height( x, z );
			pos.setY( i, h );
			if ( h > max ) {
				max = h;
				mx = x;
				mz = z;
			}
		}
		const ok = Math.hypot( mx, mz - cfg.peakZ ) <= cfg.peakRadius;
		info = { maxHeight: max, at: [ mx, mz ], ok, attempts: attempt + 1, boost };
		if ( ok ) {
			break;
		}
		geo.dispose();
		boost *= 1.2;
	}
	if ( ! info.ok ) {
		console.warn( '[ISHA svc] Đỉnh cao nhất không nằm ở đỉnh chính sau 3 lần sinh.', info );
	}
	geo.computeVertexNormals();

	// Uniform riêng (màu) + bộ dùng chung (ánh sáng, sương, thời gian: cùng object với các vật thể khác).
	const uniforms = {
		...shared,
		uGrass: { value: colors.green.clone() },
		uGrassLight: { value: colors.greenLight.clone() },
		uRock: { value: colors.rock.clone() },
		uPeak: { value: colors.peakLight.clone() },
		uDry: { value: colors.greenLight.clone().lerp( colors.golden, 0.45 ) },
		uPeakH: { value: cfg.peakHeight },
		uGrassDepth: { value: 1 }, // 1 khi có cỏ instanced (scene/index.js đặt theo bậc chất lượng)
	};

	const material = new THREE.ShaderMaterial( {
		name: 'IshaTerrain',
		uniforms,
		vertexShader: /* glsl */ `
			varying vec3 vWorldPos;
			varying vec3 vNormal;
			void main() {
				vec4 wp = modelMatrix * vec4( position, 1.0 );
				vWorldPos = wp.xyz;
				vNormal = normalize( mat3( modelMatrix ) * normal );
				gl_Position = projectionMatrix * viewMatrix * wp;
			}
		`,
		fragmentShader: SHARED_GLSL + /* glsl */ `
			uniform vec3 uGrass;
			uniform vec3 uGrassLight;
			uniform vec3 uRock;
			uniform vec3 uPeak;
			uniform vec3 uDry;
			uniform float uPeakH;
			uniform float uGrassDepth;
			varying vec3 vWorldPos;
			varying vec3 vNormal;

			void main() {
				vec3 n = normalize( vNormal );
				float h = vWorldPos.y;
				float slope = 1.0 - n.y;
				vec2 xz = vWorldPos.xz;
				float v = ishaNoise( xz * 0.018 ) * 0.6 + ishaNoise( xz * 0.13 ) * 0.4;

				// Đồng cỏ nhiều tầng: mảng lớn (cỏ khô ngả vàng / cỏ tươi), mảng vừa, sọc thớ kéo dài theo hướng nhìn, và sợi cỏ mảnh ở gần (tắt dần
				// theo fwidth để không răng cưa ở xa). Không mảng nào đồng màu như "tô nền".
				float big = ishaFbm( xz * 0.0035 );
				float mid = ishaNoise( xz * 0.035 ) * 0.6 + ishaNoise( xz * 0.13 ) * 0.4;
				float streak = ishaNoise( vec2( xz.x * 0.35, xz.y * 0.04 ) ) * 0.5 + ishaNoise( xz * 0.9 ) * 0.25;
				vec2 fw = fwidth( xz );
				float fineFade = 1.0 - smoothstep( 0.15, 0.6, max( fw.x, fw.y ) );
				float fiber = ( ishaNoise( vec2( xz.x * 3.1, xz.y * 0.55 ) ) - 0.5 ) * fineFade;
				vec3 grass = mix( uGrass, uGrassLight, clamp( mid * 0.7 + streak * 0.45, 0.0, 1.0 ) );
				grass = mix( grass, uDry, smoothstep( 0.5, 0.72, big ) * 0.6 );
				grass *= 0.92 + fiber * 0.28 + ( v - 0.5 ) * 0.1;
				float rockAmt = clamp( smoothstep( 0.16, 0.42, slope + ( v - 0.5 ) * 0.12 ) + smoothstep( 0.37 * uPeakH, 0.87 * uPeakH, h ) * 0.85, 0.0, 1.0 );
				// Gần camera (vùng có cỏ instanced) đất sẫm và ấm như lớp cỏ lót dưới gốc lá: khe giữa các lá không lộ nền sáng (cảm giác thảm
				// trải trên sàn phẳng). Ở LITE không có cỏ instanced nên không làm tối (uGrassDepth = 0).
				float nearCam = ( 1.0 - smoothstep( 140.0, 560.0, length( xz - cameraPosition.xz ) ) ) * uGrassDepth;
				grass = mix( grass, mix( uGrass, uDry, 0.25 ) * ( 0.5 + 0.12 * mid ), nearCam * 0.85 );
				vec3 base = mix( grass, uRock * ( 0.85 + 0.3 * v ), rockAmt );
				base = mix( base, uPeak, smoothstep( 0.67 * uPeakH, 1.07 * uPeakH, h ) * 0.55 );

				float ao = mix( 0.7, 1.0, smoothstep( -10.0, 0.3 * uPeakH, h ) ) * mix( 1.0, 0.82, slope );
				vec3 col = ishaLight( base, n, ao );

				// Bóng mây trôi chậm trên mặt đất.
				float cloud = smoothstep( 0.52, 0.74, ishaFbm( xz * 0.0012 + vec2( uTime * 0.006, uTime * 0.002 ) ) );
				col *= 1.0 - cloud * 0.16;

				// Vệt gió lướt trên đồng cỏ (sáng nhẹ, trôi theo hướng nhìn), chỉ ở vùng thấp phẳng; có cả ở LITE (không có cỏ instanced).
				float meadow = ( 1.0 - rockAmt ) * ( 1.0 - smoothstep( 20.0, 0.3 * uPeakH, h ) );
				float sheen = smoothstep( 0.6, 0.92, ishaNoise( xz * vec2( 0.012, 0.03 ) + vec2( uTime * 0.04, uTime * 0.11 ) ) );
				col *= 1.0 + sheen * 0.08 * meadow;

				// Viền sáng vàng phía ngược sáng (camera nhìn về phía mặt trời).
				vec3 V = normalize( cameraPosition - vWorldPos );
				float back = pow( max( dot( -V, uSunDir ), 0.0 ), 3.0 );
				float rim = pow( 1.0 - max( dot( V, n ), 0.0 ), 3.0 );
				col += uSunColor * rim * back * 0.55;

				col = ishaAerial( col, vWorldPos, smoothstep( 0.0, 1.07 * uPeakH, h ) );

				gl_FragColor = vec4( col, 1.0 );
				#include <tonemapping_fragment>
				#include <colorspace_fragment>
			}
		`,
	} );

	const mesh = new THREE.Mesh( geo, material );
	mesh.name = 'terrain';

	return {
		mesh,
		uniforms,
		info,
		height: ( x, z ) => field.height( x, z ),
		setColors( c ) {
			uniforms.uGrass.value.copy( c.green );
			uniforms.uGrassLight.value.copy( c.greenLight );
			uniforms.uRock.value.copy( c.rock );
			uniforms.uPeak.value.copy( c.peakLight );
			uniforms.uDry.value.copy( c.greenLight ).lerp( c.golden, 0.45 );
		},
		dispose() {
			geo.dispose();
			material.dispose();
		},
	};
}
