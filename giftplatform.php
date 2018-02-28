<?php
/**
 * Plugin Name:       GIFT platform plugin
 * Plugin URI:        https://github.com/growlingfish/giftplatform
 * Description:       WordPress admin and server for GIFT project digital gifting platform
 * Version:           0.1.1.4
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

// Credentials
include_once('cred.php');

// Utilities
include_once('utilities.php');

// Modifications to WordPress Dashboard
include_once('dashboard.php');

// Notifications
include_once('notifications.php');

// API
$namespace = 'gift';

define ( 'ACF_recipient', 	'field_58e4f6e88f3d7' );
define ( 'ACF_wrap', 		'field_58e4f5da816ac' );
define ( 'ACF_date', 		'field_58e4fb5c55127' );
define ( 'ACF_key',			'field_58e4fb8055128' );
define ( 'ACF_place', 		'field_58e4fae755126' );
define ( 'ACF_artcode',		'field_58ff4bdf23d95' );
define ( 'ACF_personal', 	'field_594d2552e8835' );
define ( 'ACF_object', 		'field_595b4a2bc9c1c' );
define ( 'ACF_payload',		'field_58e4f689655ef' );
define ( 'ACF_giftcard', 	'field_5964a5787eb68' );
define ( 'ACF_received', 	'field_595e186f21668' );
define ( 'ACF_unwrapped', 	'field_595e0593bd980' );
define ( 'ACF_responded', 	'field_595e05c8bd981' );
define ( 'ACF_location', 	'field_59a85fff4be5a' );
define ( 'ACF_gift', 		'field_59c4cdc1f07f6' );
define ( 'ACF_owner', 		'field_5969c3853f8f2' );
define ( 'ACF_freegift', 	'field_5a54cf62fc74f' );

/*
*	Year 1 review
*/

add_action( 'rest_api_init', 'gift_v3_register_api_hooks' );
function gift_v3_register_api_hooks () {
	global $namespace;
	$version = 3;
	
	register_rest_route( $namespace.'/v'.$version, '/auth/(?P<user>.+)/(?P<pass>.+)', array(
		'methods'  => 'GET',
		'callback' => 'v3_gift_auth',
		'args' => array(
			'user' => array(
				'validate_callback' => function ($param, $request, $key) {
					return username_exists($param);
				},
				'required' => true
			),
			'pass' => array(
				'validate_callback' => function($param, $request, $key) {
					$user = get_user_by('login', $request['user']);
					return wp_check_password($request['pass'], $user->data->user_pass, $user->ID);
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/gifts/free/(?P<venue>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v3_get_free_gift',
		'args' => array(
			'venue' => array(
				'validate_callback' => function ($param, $request, $key) {
					if (!is_numeric($param)) {
						return false;
					}
					$venue = get_term_by( 'id', $param, 'venue' );
					if ( $venue ) {
						return true;
					}
					return false;
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/gifts/received/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v3_get_received_gifts',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/gifts/sent/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v3_get_sent_gifts',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/contacts/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v3_get_contacts',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/objects/(?P<venue>.+)/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v3_get_objects',
		'args' => array(
			'venue' => array(
				'validate_callback' => function ($param, $request, $key) {
					if (!is_numeric($param)) {
						return false;
					}
					$venue = get_term_by( 'id', $param, 'venue' );
					if ( $venue ) {
						return true;
					}
					return false;
				},
				'required' => true
			),
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/venues/', array(
		'methods'  => 'GET',
		'callback' => 'v3_get_venues'
	) );
	register_rest_route( $namespace.'/v'.$version, '/locations/(?P<venue>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v3_get_locations',
		'args' => array(
			'venue' => array(
				'validate_callback' => function ($param, $request, $key) {
					if (!is_numeric($param)) {
						return false;
					}
					$venue = get_term_by( 'id', $param, 'venue' );
					if ( $venue ) {
						return true;
					}
					return false;
				},
				'required' => true
			)
		)
	) );
	/*register_rest_route( $namespace.'/v'.$version, '/data/', array(
		'methods'  => 'GET',
		'callback' => 'get_data'
	) );*/
	register_rest_route( $namespace.'/v'.$version, '/responses/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v3_get_responses',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/new/receiver/(?P<email>.+)/(?P<name>.+)/(?P<from>.+)', array(
		'methods'  => 'GET',
		'callback' => 'v3_setup_receiver',
		'args' => array(
			'email' => array(
				'validate_callback' => function ($param, $request, $key) {
					return filter_var($param, FILTER_VALIDATE_EMAIL);
				},
				'required' => true
			),
			'name' => array(
				'required' => true
			),
			'from' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/new/sender/(?P<username>.+)/(?P<pass>.+)/(?P<email>.+)/(?P<name>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v3_setup_sender',
		'args' => array(
			'username' => array(
				'validate_callback' => function ($param, $request, $key) {
					return !username_exists($param);
				},
				'required' => true
			),
			'pass' => array(
				'required' => true
			),
			'email' => array(
				'validate_callback' => function ($param, $request, $key) {
					return filter_var($param, FILTER_VALIDATE_EMAIL) && !email_exists($param);
				},
				'required' => true
			),
			'name' => array(
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/new/object/(?P<owner>.+)/', array(
		'methods'  => 'POST',
		'callback' => 'v3_setup_object',
		'args' => array(
			'owner' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			),
			'object' => array(
				'required' => true
			),
			'name' => array(
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/new/gift/(?P<id>.+)/', array(
		'methods'  => 'POST',
		'callback' => 'v3_setup_gift',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			),
			'gift' => array(
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/unwrapped/gift/(?P<id>.+)/(?P<recipient>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v3_unwrap_gift',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && is_string( get_post_status( $param ) );
				},
				'required' => true
			),
			'recipient' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/respond/gift/(?P<id>.+)/', array(
		'methods'  => 'POST',
		'callback' => 'v3_respond_to_gift',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && is_string( get_post_status( $param ) );
				},
				'required' => true
			),
			'response' => array(
				'required' => true
			),
			'responder' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/received/gift/(?P<id>.+)/(?P<recipient>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v3_received_gift',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && is_string( get_post_status( $param ) );
				},
				'required' => true
			),
			'recipient' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/upload/object/', array(
		'methods'  => 'POST',
		'callback' => 'v3_upload'
	) );
}

function v3_gift_auth ($request) {
	$user = get_user_by('login', $request['user']);

	$result = array(
		'user' => prepare_gift_user($user->data->ID),
		'token' => null,
		'success' => false
	);

	$token = generate_token($user->ID, '3');
	if ($token != null) {
		$result['token'] = $token;
		$result['success'] = true;
	}
	
	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v3_get_contacts ($request) {
	$result = array(
		'contacts' => array(),
		'success' => false
	);

	if (check_token()) {
		$u = get_users( array(
			'exclude'	=> array($request['id']),
			'orderby'	=> 'nicename'
		));
		$users = array();
		foreach ($u as $user) {
			$result['contacts'][] = prepare_gift_user($user->data->ID);
		}
		
		$result['success'] = true;
	}

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v3_get_objects ($request) {
	$user = get_user_by('ID', $request['id']);

	$result = array(
		'success' => true,
		'objects' => array()
	);

	$query = array(
		'numberposts'   => -1,
		'post_type'     => 'object',
		'post_status'   => 'publish'
	);
	$all_objects = get_posts( $query );
	foreach ($all_objects as $object) {
		$location = get_field( ACF_location, $object->ID);
		if ($location && count($location) == 1) { // does object have a location?
			$venues = wp_get_post_terms( $location[0]->ID, 'venue' );
			foreach ($venues as $venue) {
				if ($venue->term_id == $request['venue']) { // is the location in the appropriate venue?
					$owner = get_field( ACF_owner, $object->ID );
					if ($owner == null || $owner['ID'] == $user->ID) { // object belongs to no-one or this user
						$o = prepare_gift_object($object);
						if ($o) {
							$result['objects'][] = $o;
						}
					}
					break;
				}
			}
		}
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v3_get_venues ($request) {
	$result = array(
		'success' => true,
		'venues' => array()
	);

	$venues = get_terms( array(
		'taxonomy' => 'venue',
		'hide_empty' => false,
	) );
	foreach ($venues as $venue) {
		$result['venues'][] = prepare_gift_venue($venue);
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v3_get_locations ($request) {
	$result = array(
		'success' => true,
		'locations' => array()
	);

	$query = array(
		'numberposts'   => -1,
		'post_type'     => 'location',
		'post_status'   => 'publish',
		'tax_query' => array(
			array(
				'taxonomy' => 'venue',
				'field' => 'term_id',
				'terms' => array($request['venue'])
			)
		)
	);
	$locations = get_posts( $query );
	foreach ($locations as $location) {
		$result['locations'][] = prepare_gift_location($location);
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v3_setup_sender ($request) {
	$result = array(
		'success' => false,
		'user' => array()
	);

	$username = $request['username'];
	$email = $request['email'];
	if (email_exists($email)) {
		$result['existing'] = $email;
	} else if (username_exists($username)) {
		$result['existing'] = $username;
	} else {
		$result['success'] = true;

		$id = wp_create_user( $username, $request['pass'], $email );
		update_user_meta($id, 'user_nicename', $request['name']);
		update_user_meta($id, 'first_name', $request['name']);
		update_user_meta($id, 'display_name', $request['name']);
		update_user_meta($id, 'nickname', $request['name']);

		$result['user'] = prepare_gift_user($id);
	}

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 400 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v3_get_sent_gifts ($request) {
	$result = array(
		'success' => false,
		'gifts' => array()
	);

	if ($id = check_token()) {
		if ($id == $request['id']) {
			$result['success'] = true;
			$query = array(
				'numberposts'   => -1,
				'post_type'     => 'gift',
				'post_status'   => array('draft', 'publish'),
				'author'	   	=> $request['id']
			);
			$all_gifts = get_posts( $query );
			foreach ($all_gifts as $giftobject) {
				$gift = prepare_gift($giftobject);
				if ($gift) {
					$result['gifts'][] = $gift;
				}
			}
		}
	}

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v3_get_received_gifts ($request) {
	$result = array(
		'success' => false,
		'gifts' => array()
	);

	if ($id = check_token()) {
		if ($id == $request['id']) {
			$user = get_user_by('ID', $request['id']);

			$query = array(
				'numberposts'   => -1,
				'post_type'     => 'gift',
				'post_status'   => 'publish'
			);
			$all_gifts = get_posts( $query );
			foreach ($all_gifts as $giftobject) {
				$recipients = get_field( ACF_recipient, $giftobject->ID );
				if ($recipients) {
					foreach ($recipients as $recipient) {
						if ($recipient['ID'] == $user->ID) {
							$gift = prepare_gift($giftobject);
							if ($gift) {
								$result['gifts'][] = $gift;
							}
							break; // one recipient per gift?
						}
					}
				}
			}
			$result['success'] = true;
		}
	}

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v3_get_free_gift ($request) {
	$result = array(
		'success' => false,
		'gifts' => array()
	);

	$query = array(
		'numberposts'   => -1,
		'post_type'     => 'gift',
		'post_status'   => 'publish'
	);
	$all_gifts = get_posts( $query );
	foreach ($all_gifts as $giftobject) {
		$freeGift = get_field( ACF_freegift, $giftobject->ID );
		if ($freeGift) {
			$gift = prepare_gift($giftobject);
			if ($gift) {
				foreach ($gift->wraps as $wrap) {
					if ($wrap->unwrap_object && $wrap->unwrap_object->location->venue->ID == $request['venue']) {
						$result['gifts'][] = $gift;
						break;
					}
				}
			}
			if (count($result['gifts']) > 0) {
				break; // one free gift?
			}
		}
	}
	$result['success'] = true;

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v3_get_responses ($request) {
	$result = array(
		'success' => false,
		'responses' => array()
	);

	if ($id = check_token()) {
		if ($id == $request['id']) {
			$query = array(
				'numberposts'   => -1,
				'post_type'     => 'response',
				'post_status'   => 'publish'
			);
			$responses = get_posts($query);
			foreach ($responses as $response) {
				if ($response->post_author == $request['id']) { // my response
					$r = prepare_gift_response($response);
					if ($r) {
						$result['responses'][] = prepare_gift_response($response);
					}
				} else {
					$gift = get_field( ACF_gift, $response->ID );
					if ($gift->post_author == $request['id']) { // response to me
						$r = prepare_gift_response($response);
						if ($r) {
							$result['responses'][] = prepare_gift_response($response);
						}
					}
				}
			}
			$result['success'] = true;
		}
	}

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v3_setup_gift ($request) { // Unfinished
	$result = array(
		'success' => false
	);

	if ($id = check_token()) {
		if ($id == $request['id']) {
			$gift = json_decode(stripslashes($request['gift']));

			$giftcard_post = array(
				'post_title'    => 'Giftcard for '.wp_strip_all_tags( $gift->post_title ),
				'post_content'  => wp_strip_all_tags( $gift->giftcards[0]->post_content ),
				'post_status'   => 'publish',
				'post_author'   => $request['id'],
				'post_type'		=> 'giftcard'
			);
			$giftcard_id = wp_insert_post( $giftcard_post );
			if (is_wp_error($giftcard_id)) {
				// delete everything in gift and stop?
			}

			$payloads = array();
			foreach ($gift->payloads as $payload) {
				$payload_post = array(
					'post_title'    => wp_strip_all_tags( $payload->post_title ),
					'post_content'  => wp_strip_all_tags( $payload->post_content ),
					'post_status'   => 'publish',
					'post_author'   => $request['id'],
					'menu_order'	=> $payload->menu_order,
					'post_type'		=> 'payload'
				);
				$payload_id = wp_insert_post( $payload_post );
				if (is_wp_error($payload_id)) {
					// delete everything in gift and stop?
				} else {
					$payloads[] = $payload_id;
				}
			}

			$wraps = array();
			foreach ($gift->wraps as $wrap) {
				$wrap_post = array(
					'post_title'    => 'Wrap '.$wrap->menu_order.' for '.wp_strip_all_tags( $gift->post_title ),
					'post_status'   => 'publish',
					'post_author'   => $request['id'],
					'menu_order'	=> $wrap->menu_order,
					'post_type'		=> 'wrap'
				);
				$wrap_id = wp_insert_post( $wrap_post );
				if (!is_wp_error($wrap_id)) {
					update_field( ACF_object, array($wrap->unwrap_object->ID), $wrap_id );
					$wraps[] = $wrap_id;
				} else {
					// delete everything in gift and stop?
				}
			}

			$gift_post = array(
				'post_title'    => wp_strip_all_tags( $gift->post_title ),
				'post_status'   => 'publish',
				'post_author'   => $request['id'],
				'post_type'		=> 'gift'
			);
			$gift_id = wp_insert_post( $gift_post );
			if (!is_wp_error($gift_id)) {
				if (email_exists($gift->recipient->user_email)) {
					$recipient = get_user_by('id', $gift->recipient->ID);
					update_field( ACF_recipient, array($recipient->ID), $gift_id );

					update_field( ACF_giftcard, array($giftcard_id), $gift_id );

					update_field( ACF_payload, $payloads, $gift_id );
					
					update_field( ACF_wrap, $wraps, $gift_id );
					
					sendDebugEmail("Gift created", "Platform tells ".$recipient->nickname." (".$recipient->ID."; receiver) that ".$request['id']." (giver) has created a gift for them");
					sendFCMPush(
						"giftSent",
						"You've received a gift!",
						"Would you like to see your gifts now?",
						array(
							"recipientID" => $recipient->ID
						)
					);

					$result['success'] = true;
				} else {
					// delete everything in gift and stop?
				}
			} else {
				// delete everything in gift and stop?
			}
		}
	}

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v3_setup_receiver ($request) {
	$result = array(
		'success' => false,
		'user' => array()
	);

	if (check_token()) {
		$email = $request['email'];

		if (email_exists($email)) {
			$result['existing'] = get_user_by('email', $email);
		} else {
			require_once('lib/rest.php');
			$random_word_def = json_decode(curl_get('http://api.wordnik.com:80/v4/words.json/randomWord', array(
				'hasDictionaryDef' 		=> true,
				'minCorpusCount'		=> 0,
				'maxCorpusCount'		=> -1,
				'minDictionaryCount'	=> 1,
				'maxDictionaryCount'	=> 1,
				'minLength'				=> 12,
				'maxLength'				=> 12,
				'api_key'				=> WORDNIKAPI
			)));
			$random_word = $random_word_def->word;
			$password = 'abcdefgh';
			if ($random_word && is_string($random_word) && strlen($random_word) == 12) {
				$password = $random_word;
			} else {
				$password = wp_generate_password( $length=8, $include_standard_special_chars=false );
			}
			$id = wp_create_user( $email, $password, $email );
			update_user_meta($id, 'user_nicename', $request['name']);
			update_user_meta($id, 'first_name', $request['name']);
			update_user_meta($id, 'display_name', $request['name']);
			update_user_meta($id, 'nickname', $request['name']);
			$result['user'] = prepare_gift_user($id);

			$giver = prepare_gift_user($request['from']);

			sendDebugEmail("Receiver created", "Platform tells ".$result['user']['nickname']." (".$result['user']['ID']."; receiver) that ".$giver['user_email']." created has inducted them with an account with password ".$password);
			sendEmail(
				$result['user']['user_email'], 
				"New Gift account", 
				"Congratulations - another user of the Gift Exchange Tool app has just started making you a Gift!\r\n\r\n"
					."A user named ".$giver['nickname']." (with the email ".$giver['user_email'].") has started creating a personalised museum experience for you."
					."If you recognise that user and would like to receive the Gift, you can download the Gift Exchange Tool app on an Android-powered mobile phone or tablet: when ".$giver['nickname']." has finished making the Gift, you will receive a notification through the app.\r\n\r\n"
					."To log in to the Gift Exchange Tool app, you will need to use ".$result['user']['nickname']." as your username, and the word '".$password."' as your password.\r\n\r\n"
					."Best wishes - the Gift team\r\n\r\n"
					."[If you do not recognise the Gift sender or are not part of the Gift project, please ignore this notification and accept our apologies for the intrusion.]"
			);

			$result['success'] = true;
		}
	}

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v3_upload () {
	$result = array(
		'success' => false
	);

	if (check_token()) {
		define ('SITE_ROOT', realpath(dirname(__FILE__)));
		$target_path = SITE_ROOT . "/uploads/". basename( $_FILES['file']['name']);

		if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
			$result['filename'] = basename( $_FILES['file']['name']);
			$result['url'] = plugins_url( 'uploads/'.basename( $_FILES['file']['name']), __FILE__ );
			$result['success'] = true;
		}
	}

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v3_setup_object ($request) { // Unfinished
	$result = array(
		'success' => false
	);

	if ($owner = check_token()) {
		if ($owner == $request['owner']) {
			$object = json_decode(stripslashes($request['object']));

			$user = get_userdata( $request['owner'] );
			if ( $user ) {
				// Create post object
				$my_post = array(
					'post_title'    => wp_strip_all_tags( $object->post_title ),
					'post_content'  => $object->post_content,
					'post_status'   => 'publish',
					'post_author'   => $request['owner'],
					'post_type'		=> 'object'
				);
				
				// Insert the post into the database
				$post_id = wp_insert_post( $my_post );
				if(!is_wp_error($post_id)){
					// set owner to the user
					update_field( ACF_owner, $request['owner'], $post_id ); 

					// set location
					update_field( ACF_location, array($object->location->ID), $post_id ); 

					$result['object'] = get_post($post_id);

					// Set variables for storage, fix file filename for query strings.
					preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $object->post_image, $matches );
					if ( ! $matches ) {
						$result['error'] = new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
					} else {
						$file_array = array();
						$file_array['name'] = basename( $matches[0] );

						// Download file to temp location.
						require_once ABSPATH . 'wp-admin/includes/file.php';
						$file_array['tmp_name'] = download_url( $object->post_image );

						// If error storing temporarily, return the error.
						if ( is_wp_error( $file_array['tmp_name'] ) ) {
							$result['error'] = $file_array['tmp_name'];
						} else {
							// Do the validation and storage stuff.
							require_once ABSPATH . 'wp-admin/includes/image.php';
							require_once ABSPATH . 'wp-admin/includes/media.php';
							$id = media_handle_sideload( $file_array, $post_id, $request['name'] );

							// If error storing permanently, unlink.
							if ( is_wp_error( $id ) ) {
								unlink( $file_array['tmp_name'] );
								$result['error'] = $id;
							} else if (set_post_thumbnail( $post_id, $id )) {
								$result['thumbnail'] = get_the_post_thumbnail_url($post_id, 'thumbnail');
								$result['success'] = true;
							}
						}
					}
				} else {
					$result['success'] = false;
					$result['error'] = $post_id->get_error_message();
				}
			}

			// delete the image in the uploads folder
			//unlink($file_array['tmp_name']);

			if (!$result['success']) {
				// Delete failed object?
			}
		}
	}

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v3_unwrap_gift ($request) {
	$result = array(
		'success' => false
	);

	if ($recipient = check_token()) {
		if ($recipient == $request['recipient']) {
			update_field(ACF_unwrapped, 1, $request['id']);

			$gift = get_post($request['id']);
			$sender = prepare_gift_user($gift->post_author);
			$recipient = prepare_gift_user($request['recipient']);

			sendDebugEmail("Gift unwrapped", "Platform tells ".$sender['nickname']." (".$sender['ID']."; giver) that gift ".$gift->ID." has been fully unwrapped");
			sendFCMPush(
				"giftUnwrapped",
				"One of your Gifts has been unwrapped!",
				$gift->post_title." has been unwrapped by ".$recipient['nickname'],
				array(
					"senderID" => $sender['ID'],
					"sender" => $sender['nickname'],
					"giftID" => $gift->ID,
					"giftTitle" => $gift->post_title,
					"status" => "unwrapped",
					"recipientID" => $recipient['ID'],
					"recipient" => $recipient['nickname']
				)
			);

			$result = array(
				'success' => true
			);
		}
	}

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v3_received_gift ($request) {
	if ($recipient = check_token()) {
		if ($recipient == $request['recipient']) {
			update_field(ACF_received, 1, $request['id']);

			$gift = get_post($request['id']);
			$sender = prepare_gift_user($gift->post_author);
			$recipient = prepare_gift_user($request['recipient']);

			sendDebugEmail("Gift received", "Platform tells ".$sender['nickname']." (".$sender['ID']."; giver) that gift ".$gift->ID." has been received");
			sendFCMPush(
				"giftReceived",
				"One of your Gifts has been received!",
				$gift->post_title." has been received by ".$recipient['nickname'],
				array(
					"senderID" => $sender['ID'],
					"sender" => $sender['nickname'],
					"giftID" => $gift->ID,
					"giftTitle" => $gift->post_title,
					"status" => "received",
					"recipientID" => $recipient['ID'],
					"recipient" => $recipient['nickname']
				)
			);

			$result = array(
				'success' => true
			);
		}
	}

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v3_respond_to_gift ($request) {
	$result = array(
		'success' => false
	);

	if ($responder = check_token()) {
		if ($responder == $request['responder']) {
			$giftId = $request['id'];

			update_field( ACF_responded, 1, $giftId);

			$my_response = array(
				'post_content'  => $request['response'],
				'post_status'   => 'publish',
				'post_author'   => $request['responder'],
				'post_type'		=> 'response'
			);
			
			$post_id = wp_insert_post( $my_response );
			if(!is_wp_error($post_id)){
				update_field( ACF_gift, $giftId, $post_id );

				$gift = get_post($giftId);
				$response_sender = prepare_gift_user($request['responder']);
				$gift_sender = prepare_gift_user($gift->post_author);

				sendDebugEmail("Responded to gift", "Platform tells ".$gift_sender['nickname']." (".$gift_sender['ID']."; giver) that ".$response_sender['nickname']." (".$response_sender['ID']."; receiver) said: ".$request['response']);
				sendFCMPush(
					"responseSent",
					"You've received a response to your Gift",
					$response_sender['nickname']." said: ".$request['response'],
					array(
						"sender" => $response_sender['ID'],
						"owner" => $gift_sender['ID'],
						"response" => $my_response['post_content'],
						"status" => "responded"
					)
				);
				
				$result['success'] = true;
			}
		}
	}

	$response = new WP_REST_Response( $result );
	if ($result['success']) {
		$response->set_status( 200 );
	} else {
		$response->set_status( 403 );
	}
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

// Previous API versions
include_once('api-v2.php');
include_once('api-v1.php');

?>