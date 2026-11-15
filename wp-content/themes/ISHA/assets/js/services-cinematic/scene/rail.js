/**
 * Đường ray camera: CatmullRomCurve3 'centripetal', camera chỉ tiến nhẹ trên đồng cỏ (z 900 → 760, y 40 → 52): mặt trời là chủ thể, núi
 * là yếu tố phụ, nên camera không ngước theo núi. Hướng nhìn cố định theo góc ngẩng pitchDeg (mặc định 2.5°) về phía đỉnh chính: đường
 * chân trời và đỉnh chính nằm khoảng 53–56% chiều cao khung, bầu trời chiếm phần lớn khung.
 * Vị trí lấy theo độ dài cung (getPointAt) nên tốc độ đều; t = easeInOutSine( smoothed ). Camera luôn cao hơn mặt đất ít nhất CLEARANCE.
 */
const CLEARANCE = 22;
const LOOK_DIST = 3000;

export function buildRail( THREE, terrainHeight ) {
	const path = new THREE.CatmullRomCurve3( [
		new THREE.Vector3( 0, 40, 900 ),
		new THREE.Vector3( -14, 43, 870 ),
		new THREE.Vector3( -6, 46, 830 ),
		new THREE.Vector3( 10, 49, 795 ),
		new THREE.Vector3( 0, 52, 760 ),
	], false, 'centripetal' );

	const p = new THREE.Vector3();
	const l = new THREE.Vector3();
	const ease = ( t ) => -( Math.cos( Math.PI * t ) - 1 ) / 2;

	let helper = null;

	return {
		path,
		/**
		 * @param {THREE.PerspectiveCamera} camera
		 * @param {number} t        smoothed progress [0, 1].
		 * @param {number} pitchDeg Góc ngẩng của hướng nhìn.
		 * @param {number} yawDeg   Lệch nhỏ theo chuột (desktop).
		 * @param {number} tiltDeg  Lệch dọc nhỏ theo chuột (desktop).
		 */
		apply( camera, t, pitchDeg, yawDeg = 0, tiltDeg = 0 ) {
			const k = ease( Math.min( 1, Math.max( 0, t ) ) );
			path.getPointAt( k, p );
			const ground = terrainHeight( p.x, p.z );
			p.y = Math.max( p.y, ground + CLEARANCE );
			camera.position.copy( p );
			l.set( p.x, p.y + LOOK_DIST * Math.tan( THREE.MathUtils.degToRad( pitchDeg ) ), p.z - LOOK_DIST );
			camera.lookAt( l );
			if ( yawDeg || tiltDeg ) {
				camera.rotateY( THREE.MathUtils.degToRad( yawDeg ) );
				camera.rotateX( THREE.MathUtils.degToRad( tiltDeg ) );
			}
		},
		toggleHelper( scene, on, color ) {
			if ( on && ! helper ) {
				const g = new THREE.BufferGeometry().setFromPoints( path.getSpacedPoints( 80 ) );
				helper = new THREE.Line( g, new THREE.LineBasicMaterial( { color } ) );
				helper.position.y = 2;
				scene.add( helper );
			} else if ( ! on && helper ) {
				scene.remove( helper );
				helper.geometry.dispose();
				helper.material.dispose();
				helper = null;
			}
		},
	};
}
