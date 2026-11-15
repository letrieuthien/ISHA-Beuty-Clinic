/**
 * Bầu trời: Sky (Preetham) của three/addons, cập nhật uniform từ sampleStops mỗi khung hình.
 *
 * Chèn thêm vào fragment shader của Sky (chuỗi GLSL, trước gl_FragColor):
 * - uSkyGain: hệ số khuếch đại bức xạ trời (Preetham ở độ cao thấp cho trời tối, đỏ đậm);
 * - "sàn sáng" (token floor): trộn gradient --isha-skin-soft (chân trời) → --isha-daylight (đỉnh) theo uFloorMix (CONFIG.colors.skyFloor),
 *   giữ một chế độ SÁNG cho cả rạng đông;
 * - dưới đường chân trời: thay bằng màu sương (Preetham vẽ nửa dưới rất tối), để mép xa của địa hình hoà vào sương.
 * Lớp mây có sẵn của Sky r187 dùng ở mức mỏng (mây hồng billboard thêm ở giai đoạn 5).
 */

const INJECT_UNIFORMS = /* glsl */ `
uniform float uSkyGain;
uniform float uFloorMix;
uniform vec3 uFloorTop;
uniform vec3 uFloorHorizon;
uniform vec3 uBelowColor;
`;

const INJECT_BODY = /* glsl */ `
			texColor *= uSkyGain;
			float upness = clamp( direction.y, 0.0, 1.0 );
			vec3 floorCol = mix( uFloorHorizon, uFloorTop, smoothstep( 0.0, 0.55, upness ) );
			texColor = mix( texColor, floorCol, uFloorMix );
			texColor = mix( texColor, uBelowColor, smoothstep( 0.01, -0.04, direction.y ) );
			gl_FragColor = vec4( texColor, 1.0 );`;

export function buildSky( THREE, Sky ) {
	const sky = new Sky();
	sky.name = 'sky';
	sky.scale.setScalar( 15000 );
	const mat = sky.material;
	const u = mat.uniforms;
	u.uSkyGain = { value: 1 };
	u.uFloorMix = { value: 0.25 };
	u.uFloorTop = { value: new THREE.Color() };
	u.uFloorHorizon = { value: new THREE.Color() };
	u.uBelowColor = { value: new THREE.Color() };

	const marker = 'gl_FragColor = vec4( texColor, 1.0 );';
	if ( mat.fragmentShader.indexOf( marker ) < 0 ) {
		console.warn( '[ISHA svc] Không tìm thấy điểm chèn trong shader Sky (đổi phiên bản three?). Bỏ lớp sàn sáng.' );
	} else {
		mat.fragmentShader = INJECT_UNIFORMS + mat.fragmentShader.replace( marker, INJECT_BODY.trim() );
		mat.needsUpdate = true;
	}

	// Mây mỏng có sẵn của Sky.
	u.cloudCoverage.value = 0.22;
	u.cloudDensity.value = 0.35;
	u.cloudScale.value = 0.00022;
	u.cloudSpeed.value = 0.000012;

	const sunDir = new THREE.Vector3();

	return {
		mesh: sky,
		sunDir,
		/**
		 * @param {object} s   Kết quả sampleStops.
		 * @param {object} c   Màu tuyến tính (tokens.js): daylight, skinSoft.
		 * @param {object} opt { floorMix, belowColor (THREE.Color), time, gain }
		 */
		update( s, c, opt ) {
			const phi = THREE.MathUtils.degToRad( 90 - s.elevation );
			const theta = THREE.MathUtils.degToRad( s.azimuth );
			sunDir.setFromSphericalCoords( 1, phi, theta );
			u.sunPosition.value.copy( sunDir );
			u.turbidity.value = s.turbidity;
			u.rayleigh.value = s.rayleigh;
			u.mieCoefficient.value = s.mieCoefficient;
			u.mieDirectionalG.value = s.mieG;
			u.uSkyGain.value = opt.gain;
			u.uFloorMix.value = opt.floorMix;
			u.uFloorTop.value.copy( c.daylight );
			u.uFloorHorizon.value.copy( c.skinSoft );
			u.uBelowColor.value.copy( opt.belowColor );
			u.time.value = opt.time;
		},
		dispose() {
			sky.geometry.dispose();
			mat.dispose();
		},
	};
}
