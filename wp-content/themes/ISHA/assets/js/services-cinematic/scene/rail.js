/**
 * Đường ray camera: CatmullRomCurve3 'centripetal' 6 điểm, xuất phát thấp giữa đồng cỏ rồi tiến chậm về phía đỉnh chính (khoảng 30% khoảng
 * cách tới đỉnh, không bao giờ "bay" tới núi). Hướng nhìn đi theo một curve thứ hai luôn neo vào đỉnh chính (hơi ngước dần lên theo mặt trời).
 * Vị trí lấy theo độ dài cung (getPointAt) nên tốc độ đều; t = easeInOutSine( smoothed ). Camera luôn cao hơn mặt đất ít nhất CLEARANCE.
 */
const CLEARANCE = 22;

export function buildRail( THREE, terrainHeight, peakZ ) {
	const path = new THREE.CatmullRomCurve3( [
		new THREE.Vector3( 0, 42, 900 ),
		new THREE.Vector3( -40, 46, 800 ),
		new THREE.Vector3( -10, 52, 700 ),
		new THREE.Vector3( 30, 58, 610 ),
		new THREE.Vector3( 10, 64, 530 ),
		new THREE.Vector3( 0, 70, 460 ),
	], false, 'centripetal' );
	const look = new THREE.CatmullRomCurve3( [
		new THREE.Vector3( 0, 520, peakZ ),
		new THREE.Vector3( -10, 570, peakZ ),
		new THREE.Vector3( 0, 650, peakZ ),
		new THREE.Vector3( 10, 760, peakZ ),
	], false, 'centripetal' );

	const p = new THREE.Vector3();
	const l = new THREE.Vector3();
	const ease = ( t ) => -( Math.cos( Math.PI * t ) - 1 ) / 2;

	let helper = null;

	return {
		path,
		look,
		/**
		 * @param {THREE.PerspectiveCamera} camera
		 * @param {number} t     smoothed progress [0, 1].
		 * @param {number} yawDeg, pitchDeg Lệch nhỏ theo chuột (desktop).
		 */
		apply( camera, t, yawDeg = 0, pitchDeg = 0 ) {
			const k = ease( Math.min( 1, Math.max( 0, t ) ) );
			path.getPointAt( k, p );
			look.getPointAt( k, l );
			const ground = terrainHeight( p.x, p.z );
			p.y = Math.max( p.y, ground + CLEARANCE );
			camera.position.copy( p );
			camera.lookAt( l );
			if ( yawDeg || pitchDeg ) {
				camera.rotateY( THREE.MathUtils.degToRad( yawDeg ) );
				camera.rotateX( THREE.MathUtils.degToRad( pitchDeg ) );
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
