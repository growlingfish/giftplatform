<?php
/**
 * Plugin Name:       GIFT platform plugin
 * Plugin URI:        https://github.com/growlingfish/giftplatform
 * Description:       WordPress admin and server for GIFT project digital gifting platform
 * Version:           0.0.2.5
 * Author:            Ben Bedwell
 * License:           GNU General Public License v3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       giftplatform
 * GitHub Plugin URI: https://github.com/growlingfish/giftplatform
 * GitHub Branch:     master
 */
 
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
 
register_activation_hook( __FILE__, 'giftplatform_activate' );
function giftplatform_activate () {
	flush_rewrite_rules();
}

/*
*	Place in Wrap custom post type
*/

function giftplatform_enqueue_admin ($hook) {
    wp_enqueue_script( 'google_maps', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyD9lN8Xdj31lA9w4DwpuZ1SwzQliwOI21I&libraries=drawing', array( 'jquery' ) );
}
add_action( 'admin_enqueue_scripts', 'giftplatform_enqueue_admin' );

function adding_custom_meta_boxes_wrap ( $post ) {
    add_meta_box( 
        'wrap_geo_meta_box',
        __( 'Place' ),
        'render_wrap_geo_meta_box',
        'wrap',
        'advanced',
        'high'
    );
}
add_action( 'add_meta_boxes_wrap', 'adding_custom_meta_boxes_wrap' );

function render_wrap_geo_meta_box ( $post ) { ?>
<div id="wrap_geo_meta_box_map" style="width: 100%; height: 400px;"></div>
<script>	
	jQuery(document).ready(function( $ ) {
		var displayMarker;
		
		// display blank map
		var map = new google.maps.Map(
			document.getElementById('wrap_geo_meta_box_map'), {
				center: {lat: 52.938597, lng: -1.195291},
				zoom: 15
			}
		);

		// show drawing manager
		var drawingManager = new google.maps.drawing.DrawingManager({
			drawingMode: google.maps.drawing.OverlayType.MARKER,
			drawingControl: true,
			drawingControlOptions: {
				position: google.maps.ControlPosition.TOP_CENTER,
				drawingModes: [
					google.maps.drawing.OverlayType.MARKER
				]
			},
			markerOptions: {
				clickable: false,
				editable: false
			}
		});
		drawingManager.setMap(map);

		if (jQuery('#acf-field-place').val()) {
			try {
				var point = new google.maps.Data.Point(
					JSON.parse(jQuery('#acf-field-place').val())
				);
				displayMarker = new google.maps.Marker({
				  	position: point.get(),
				  	map: map
				});
			} catch (e) { // JSON syntax error
				console.log('Bad JSON syntax in ACF field Place: ' + jQuery('#acf-field-place').val());
				jQuery('#acf-field-place').val('');
			}
		}

		google.maps.event.addListener(drawingManager, 'overlaycomplete', function(event) {
			if (typeof displayMarker !== "undefined") {
				displayMarker.setMap(null);
			}
							
			switch (event.type) {
				case google.maps.drawing.OverlayType.MARKER:
					displayMarker = event.overlay;
					jQuery('#acf-field-place').val(JSON.stringify(displayMarker.getPosition()));
					break;
			}
		});
	});
</script>
<?php	
}

/*
*	Custom API end-points
*/

$namespace = 'gift/v1';

add_action( 'rest_api_init', 'gift_register_api_hooks' );
function gift_register_api_hooks () {
	global $namespace;
	
	register_rest_route( $namespace, '/auth/(?P<user>.+)/(?P<pass>.+)', array(
		'methods'  => 'GET',
		'callback' => 'gift_auth',
		'args' => array(
			'user' => array(
				'validate_callback' => function ($param, $request, $key) {
					return email_exists($param);
				}
			),
			'pass' => array(
				'validate_callback' => function($param, $request, $key) {
					$user = get_user_by('email', $request['user']);
					return wp_check_password($request['pass'], $user->data->user_pass, $user->ID);
				}
			)
		)
	) );
	register_rest_route( $namespace, '/gifts/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'get_gifts',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				}
			)
		)
	) );
	register_rest_route( $namespace, '/new/receiver/(?P<email>.+)/(?P<from>.+)', array(
		'methods'  => 'GET',
		'callback' => 'setup_receiver',
		'args' => array(
			'email' => array(
				'validate_callback' => function ($param, $request, $key) {
					return filter_var($param, FILTER_VALIDATE_EMAIL);
				}
			),
			'from' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				}
			)
		)
	) );
	register_rest_route( $namespace, '/unwrapped/gift/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'unwrap_gift',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && is_string( get_post_status( $param ) );
				}
			)
		)
	) );
}

function gift_auth ($request) {
	$user = get_user_by('email', $request['user']);

	$result = array(
		'name' => $user->data->user_nicename,
		'id' => $user->ID,
		'success' => true
	);

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function get_gifts ($request) {
	$user = get_user_by('ID', $request['id']);

	$result = array(
		'success' => true,
		'gifts' => array()
	);

	$query = array(
		'numberposts'   => -1,
		'post_type'     => 'gift',
		'post_status'   => 'publish'
	);
	$all_gifts = get_posts( $query );
	foreach ($all_gifts as $gift) {
		$recipients = get_field( 'recipient', $gift->ID );
		foreach ($recipients as $recipient) {
			if ($recipient['ID'] == $user->ID) {
				$gift->post_author_data = get_user_by('ID', $gift->post_author)->data;
				$gift->wraps = get_field('wrap', $gift->ID);
				foreach ($gift->wraps as &$wrap) {
					$wrap->unwrap_date = get_field('date', $wrap->ID);
					$wrap->unwrap_key = get_field('key', $wrap->ID);
					$wrap->unwrap_place = html_entity_decode(get_field('place', $wrap->ID));
					$wrap->unwrap_artcode = get_field('artcode', $wrap->ID);
					$wrap->unwrap_personal = get_field('personal', $wrap->ID);
					$wrap->unwrap_object = get_field('object', $wrap->ID);
					if ($wrap->unwrap_object) {
						$wrap->unwrap_object->post_image = get_the_post_thumbnail_url($wrap->unwrap_object->ID, 'large');
					}
				}
				$gift->payloads = get_field('payload', $gift->ID);
				$result['gifts'][] = $gift;
				break;
			}
		}
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function setup_receiver ($request) {
	$email = $request['email'];

	$result = array(
		'success' => true,
		'new' => array()
	);

	if (email_exists($email)) {
		$result['success'] = false;
		$result['existing'] = get_user_by('email', $email);
	} else {
		require_once('lib/rest.php');
		$random_word = curl_get('http://setgetgo.com/randomword/get.php', array('len' => 8));
		$password = 'abcdefgh';
		if ($random_word && is_string($random_word) && strlen($random_word) == 8) {
			$password = $random_word;
		} else {
			$password = wp_generate_password( $length=8, $include_standard_special_chars=false );
		}
		$result['new'] = array(
			'id' => wp_create_user( $email, $password, $email )
		);
		
		// Leave to the client to hit notification server
/*
		$gifter = get_user_by('ID', $request['from']);

		$endpoints = parse_ini_file('giftplatform-common/endpoints.ini');
		$typebook = parse_ini_file('giftplatform-common/types.ini');
		curl_post($endpoints->notifications, array(
			'type' => $typebook->newReceiver,
			'receiver' => $email,
			'password' => $password
		));*/
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function unwrap_gift ($request) {
	$id = $request['id'];

	$result = array(
		'success' => true
	);

	update_field('unwrapped', 1, $id);

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

?>