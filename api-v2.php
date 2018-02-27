<?php
 
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define ( 'BRIGHTON_VENUE',	2 );

/*
*	Custom API end-points: second Brighton sprint
*/

add_action( 'rest_api_init', 'gift_v2_register_api_hooks' );
function gift_v2_register_api_hooks () {
	global $namespace;
	$version = 2;
	
	register_rest_route( $namespace.'/v'.$version, '/auth/(?P<user>.+)/(?P<pass>.+)', array(
		'methods'  => 'GET',
		'callback' => 'v2_gift_auth',
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
	register_rest_route( $namespace.'/v'.$version, '/gifts/received/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v2_get_received_gifts',
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
		'callback' => 'v2_get_sent_gifts',
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
		'callback' => 'v2_get_contacts',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/objects/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v2_get_objects',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/locations/', array(
		'methods'  => 'GET',
		'callback' => 'v2_get_locations'
	) );
	register_rest_route( $namespace.'/v'.$version, '/data/', array(
		'methods'  => 'GET',
		'callback' => 'v2_get_data'
	) );
	register_rest_route( $namespace.'/v'.$version, '/responses/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v2_get_responses',
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
		'callback' => 'v2_setup_receiver',
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
	register_rest_route( $namespace.'/v'.$version, '/new/sender/(?P<username>.+)/(?P<pass>.+)/(?P<email>.+)/(?P<name>.+)', array(
		'methods'  => 'GET',
		'callback' => 'v2_setup_sender',
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
	register_rest_route( $namespace.'/v'.$version, '/new/object/', array(
		'methods'  => 'POST',
		'callback' => 'v2_setup_object',
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
	register_rest_route( $namespace.'/v'.$version, '/new/gift/', array(
		'methods'  => 'POST',
		'callback' => 'v2_setup_gift',
		'args' => array(
			'sender' => array(
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
		'callback' => 'v2_unwrap_gift',
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
		'callback' => 'v2_respond_to_gift',
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
			'sender' => array(
				'required' => true
			),
			'owner' => array(
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/received/gift/(?P<id>.+)/(?P<recipient>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v2_received_gift',
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
		'callback' => 'v2_upload'
	) );
}

function v2_gift_auth ($request) {
	$user = get_user_by('login', $request['user']);
	unset($user->allcaps, $user->caps, $user->cap_key, $user->filter);

	$result = array(
		'user' => $user,
		'token' => null,
		'success' => true
	);

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v2_get_contacts ($request) {
	$u = get_users( array(
		'exclude'	=> array($request['id']),
		'orderby'	=> 'nicename'
	));
	$users = array();
	foreach ($u as $user) {
		$userdata = get_userdata($user->data->ID);
		$users[] = array(
			'ID'			=> $user->data->ID,
			'user_email'	=> $user->data->user_email,
			'nickname'		=> $userdata->nickname,
			'gravatar'		=> get_avatar_url( $user->data->ID, 32 )
		);
	}
	
	$result = array(
		'contacts' => $users,
		'success' => true
	);

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v2_get_data ($request) {
	$query = array(
		'numberposts'   => -1,
		'post_type'     => 'gift',
		'post_status'   => array('publish'),
		'date_query' => array(
			array(
				'after' => '2017-09-28 00:00:00',
				'before' => '2017-09-28 23:59:59'
			)
		)
	);
	$all_gifts = get_posts( $query );
	foreach ($all_gifts as $gift) {
		$hasObject = false;
		$recipients = get_field( 'field_58e4f6e88f3d7', $gift->ID );
		foreach ($recipients as $recipient) {
			$gift->recipient = $recipient;
			unset(
				$gift->recipient->user_nicename,
				$gift->recipient->user_url,
				$gift->recipient->user_registered,
				$gift->recipient->user_description,
				$gift->recipient->user_avatar
			);
			//$gift->wraps = get_field('wrap', $gift->ID);
			$gift->wraps = get_field('field_58e4f5da816ac', $gift->ID);
			foreach ($gift->wraps as &$wrap) {
				unset(
					$wrap->post_date_gmt,
					$wrap->post_title,
					$wrap->post_excerpt,
					$wrap->comment_status,
					$wrap->ping_status,
					$wrap->post_password,
					$wrap->to_ping,
					$wrap->pinged,
					$wrap->post_modified_gmt,
					$wrap->post_content_filtered,
					$wrap->post_parent,
					$wrap->post_mime_type,
					$wrap->comment_count,
					$wrap->filter
				);
				//$wrap->unwrap_object = get_field('object', $wrap->ID);
				$wrap->unwrap_object = get_field('field_595b4a2bc9c1c', $wrap->ID);
				if (is_array($wrap->unwrap_object) && count($wrap->unwrap_object) > 0) {
					$wrap->unwrap_object = $wrap->unwrap_object[0];
				} else if (is_a($wrap->unwrap_object, 'WP_Post')) {
						
				} else {
					unset($wrap->unwrap_object);
				}  
				if ($wrap->unwrap_object) {
					$hasObject = true;
					$wrap->unwrap_object->post_image = get_the_post_thumbnail_url($wrap->unwrap_object->ID, 'large');
					$wrap->unwrap_object->post_content = wpautop($wrap->unwrap_object->post_content);
				}
				unset(
					$wrap->unwrap_object->post_date_gmt,
					$wrap->unwrap_object->post_excerpt,
					$wrap->unwrap_object->comment_status,
					$wrap->unwrap_object->ping_status,
					$wrap->unwrap_object->post_password,
					$wrap->unwrap_object->to_ping,
					$wrap->unwrap_object->pinged,
					$wrap->unwrap_object->menu_order,
					$wrap->unwrap_object->post_modified_gmt,
					$wrap->unwrap_object->post_content_filtered,
					$wrap->unwrap_object->post_parent,
					$wrap->unwrap_object->post_mime_type,
					$wrap->unwrap_object->comment_count,
					$wrap->unwrap_object->filter
				);
				$location = get_field('field_59a85fff4be5a', $wrap->unwrap_object->ID);
				$wrap->unwrap_object->location = $location[0]->post_title;
			}

			if ($hasObject) {
				//$gift->payloads = get_field('payload', $gift->ID);
				$gift->payloads = get_field('field_58e4f689655ef', $gift->ID);
				foreach ($gift->payloads as &$payload) {
					unset(
						$payload->post_date_gmt,
						$payload->post_title,
						$payload->post_excerpt,
						$payload->comment_status,
						$payload->ping_status,
						$payload->post_password,
						$payload->to_ping,
						$payload->pinged,
						$payload->post_modified_gmt,
						$payload->post_content_filtered,
						$payload->post_parent,
						$payload->post_mime_type,
						$payload->comment_count,
						$payload->filter
					);
					$payload->post_content = wpautop($payload->post_content);
				}
				//$gift->giftcards = get_field('gift_card', $gift->ID);
				$gift->giftcards = get_field('field_5964a5787eb68', $gift->ID);
				foreach ($gift->giftcards as &$giftcard) {
					unset(
						$giftcard->post_date_gmt,
						$giftcard->post_title,
						$giftcard->post_excerpt,
						$giftcard->comment_status,
						$giftcard->ping_status,
						$giftcard->post_password,
						$giftcard->to_ping,
						$giftcard->pinged,
						$giftcard->post_modified_gmt,
						$giftcard->post_content_filtered,
						$giftcard->post_parent,
						$giftcard->menu_order,
						$giftcard->post_mime_type,
						$giftcard->comment_count,
						$giftcard->filter
					);
					$giftcard->post_content = wpautop($giftcard->post_content);
				}
				$gift->status = array(
					//'received' => get_field('received', $gift->ID),
					'received' => get_field('field_595e186f21668', $gift->ID),
					//'unwrapped' => get_field('unwrapped', $gift->ID),
					'unwrapped' => get_field('field_595e0593bd980', $gift->ID),
					//'responded' => get_field('responded', $gift->ID)
					'responded' => get_field('field_595e05c8bd981', $gift->ID)
				);
				unset(
					$gift->post_date_gmt,
					$gift->post_content,
					$gift->post_title,
					$gift->post_excerpt,
					$gift->comment_status,
					$gift->ping_status,
					$gift->post_password,
					$gift->to_ping,
					$gift->pinged,
					$gift->post_modified_gmt,
					$gift->post_content_filtered,
					$gift->post_parent,
					$gift->menu_order,
					$gift->post_mime_type,
					$gift->comment_count,
					$gift->filter,
					$gift->recipient->user_nicename,
					$gift->recipient->user_url,
					$gift->recipient->user_registered,
					$gift->recipient->user_description,
					$gift->recipient->user_avatar
				);
				$result['gifts'][] = $gift;
			}
			break;
		}
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

// Update to get only gifts for Brighton
function v2_get_sent_gifts ($request) {
	$user = get_user_by('ID', $request['id']);

	$result = array(
		'success' => true,
		'gifts' => array()
	);

	$query = array(
		'numberposts'   => -1,
		'post_type'     => 'gift',
		'post_status'   => array('draft', 'publish'),
		'author'	   	=> $user->ID
	);
	$all_gifts = get_posts( $query );
	foreach ($all_gifts as $gift) {
		$hasObject = false;
		//$recipients = get_field( 'recipient', $gift->ID );
		$recipients = get_field( 'field_58e4f6e88f3d7', $gift->ID );
		foreach ($recipients as $recipient) {
			$gift->recipient = $recipient;
			//$gift->wraps = get_field('wrap', $gift->ID);
			$gift->wraps = get_field('field_58e4f5da816ac', $gift->ID);
			foreach ($gift->wraps as &$wrap) {
				//$wrap->unwrap_date = get_field('date', $wrap->ID);
				$wrap->unwrap_date = get_field('field_58e4fb5c55127', $wrap->ID);
				//$wrap->unwrap_key = get_field('key', $wrap->ID);
				$wrap->unwrap_key = get_field('field_58e4fb8055128', $wrap->ID);
				//$wrap->unwrap_place = html_entity_decode(get_field('place', $wrap->ID));
				$wrap->unwrap_place = html_entity_decode(get_field('field_58e4fae755126', $wrap->ID));
				//$wrap->unwrap_artcode = get_field('artcode', $wrap->ID);
				$wrap->unwrap_artcode = get_field('field_58ff4bdf23d95', $wrap->ID);
				//$wrap->unwrap_personal = get_field('personal', $wrap->ID);
				$wrap->unwrap_personal = get_field('field_594d2552e8835', $wrap->ID);
				//$wrap->unwrap_object = get_field('object', $wrap->ID);
				$wrap->unwrap_object = get_field('field_595b4a2bc9c1c', $wrap->ID);
				if (is_array($wrap->unwrap_object) && count($wrap->unwrap_object) > 0) {
					$wrap->unwrap_object = $wrap->unwrap_object[0];
				} else if (is_a($wrap->unwrap_object, 'WP_Post')) {
						
				} else {
					unset($wrap->unwrap_object);
				}  
				if ($wrap->unwrap_object) {
					$hasObject = true;
					$wrap->unwrap_object->post_image = get_the_post_thumbnail_url($wrap->unwrap_object->ID, 'large');
					$wrap->unwrap_object->post_content = wpautop($wrap->unwrap_object->post_content);
				}
			}

			if ($hasObject) {
				//$gift->payloads = get_field('payload', $gift->ID);
				$gift->payloads = get_field('field_58e4f689655ef', $gift->ID);
				foreach ($gift->payloads as &$payload) {
					$payload->post_content = wpautop($payload->post_content);
				}
				//$gift->giftcards = get_field('gift_card', $gift->ID);
				$gift->giftcards = get_field('field_5964a5787eb68', $gift->ID);
				foreach ($gift->giftcards as &$giftcard) {
					$giftcard->post_content = wpautop($giftcard->post_content);
				}
				$gift->status = array(
					//'received' => get_field('received', $gift->ID),
					'received' => get_field('field_595e186f21668', $gift->ID),
					//'unwrapped' => get_field('unwrapped', $gift->ID),
					'unwrapped' => get_field('field_595e0593bd980', $gift->ID),
					//'responded' => get_field('responded', $gift->ID)
					'responded' => get_field('field_595e05c8bd981', $gift->ID)
				);
				$result['gifts'][] = $gift;
			}
			break;
		}
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

// Update to get only gifts for Brighton
function v2_get_received_gifts ($request) {
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
		$hasObject = false;
		//$recipients = get_field( 'recipient', $gift->ID );
		$recipients = get_field( 'field_58e4f6e88f3d7', $gift->ID );
		foreach ($recipients as $recipient) {
			if ($recipient['ID'] == $user->ID) {
				$gift->post_author_data = get_user_by('ID', $gift->post_author)->data;
				$userdata = get_userdata($gift->post_author);
				$gift->post_author_data->nickname = $userdata->nickname;
				//$gift->wraps = get_field('wrap', $gift->ID);
				$gift->wraps = get_field('field_58e4f5da816ac', $gift->ID);
				foreach ($gift->wraps as &$wrap) {
					//$wrap->unwrap_date = get_field('date', $wrap->ID);
					$wrap->unwrap_date = get_field('field_58e4fb5c55127', $wrap->ID);
					//$wrap->unwrap_key = get_field('key', $wrap->ID);
					$wrap->unwrap_key = get_field('field_58e4fb8055128', $wrap->ID);
					//$wrap->unwrap_place = html_entity_decode(get_field('place', $wrap->ID));
					$wrap->unwrap_place = html_entity_decode(get_field('field_58e4fae755126', $wrap->ID));
					//$wrap->unwrap_artcode = get_field('artcode', $wrap->ID);
					$wrap->unwrap_artcode = get_field('field_58ff4bdf23d95', $wrap->ID);
					//$wrap->unwrap_personal = get_field('personal', $wrap->ID);
					$wrap->unwrap_personal = get_field('field_594d2552e8835', $wrap->ID);
					//$wrap->unwrap_object = get_field('object', $wrap->ID);
					$wrap->unwrap_object = get_field('field_595b4a2bc9c1c', $wrap->ID);
					if (is_array($wrap->unwrap_object) && count($wrap->unwrap_object) > 0) {
						$wrap->unwrap_object = $wrap->unwrap_object[0];
					} else if (is_a($wrap->unwrap_object, 'WP_Post')) {
							
					} else {
						unset($wrap->unwrap_object);
					}  
					if ($wrap->unwrap_object) {
						$hasObject = true;
						$wrap->unwrap_object->post_image = get_the_post_thumbnail_url($wrap->unwrap_object->ID, 'large');
						$wrap->unwrap_object->post_content = wpautop($wrap->unwrap_object->post_content);
						$wrap->unwrap_object->location = get_field('field_59a85fff4be5a', $wrap->unwrap_object->ID);
					}
				}

				if ($hasObject) {
					//$gift->payloads = get_field('payload', $gift->ID);
					$gift->payloads = get_field('field_58e4f689655ef', $gift->ID);
					foreach ($gift->payloads as &$payload) {
						$payload->post_content = wpautop($payload->post_content);
					}
					//$gift->giftcards = get_field('gift_card', $gift->ID);
					$gift->giftcards = get_field('field_5964a5787eb68', $gift->ID);
					foreach ($gift->giftcards as &$giftcard) {
						$giftcard->post_content = wpautop($giftcard->post_content);
					}
					$gift->status = array(
						//'received' => get_field('received', $gift->ID),
						'received' => get_field('field_595e186f21668', $gift->ID),
						//'unwrapped' => get_field('unwrapped', $gift->ID),
						'unwrapped' => get_field('field_595e0593bd980', $gift->ID),
						//'responded' => get_field('responded', $gift->ID)
						'responded' => get_field('field_595e05c8bd981', $gift->ID)
					);
					$result['gifts'][] = $gift;
				}
				break;
			}
		}
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

// Update to get only objects for Brighton - check
function v2_get_objects ($request) {
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
		//$owner = get_field( 'owner', $object->ID );
		$owner = get_field( 'field_5969c3853f8f2', $object->ID );
		if ($owner == null || $owner['ID'] == $user->ID) { // object belongs to no-one or this user
			$object->post_image = get_the_post_thumbnail_url($object->ID, 'thumbnail');
			$object->post_content = wpautop($object->post_content);
			$object->location = get_field('field_59a85fff4be5a', $object->ID);
			if ($object->location && count($object->location) == 1) { // does object have a location?
				$venues = wp_get_post_terms( $object->location[0]->ID, 'venue' );
				foreach ($venues as $venue) {
					if ($venue->term_id == BRIGHTON_VENUE) { // is the location in Brighton?
						$result['objects'][] = $object;
						break;
					}
				}
			}
		}
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

// Update to get only locations for Brighton - check
function v2_get_locations ($request) {
	$query = array(
		'numberposts'   => -1,
		'post_type'     => 'location',
		'post_status'   => 'publish',
		'tax_query' => array(
			array(
				'taxonomy' => 'venue',
				'field' => 'term_id',
				'terms' => array(BRIGHTON_VENUE)
			)
		)
	);

	$result = array(
		'success' => true,
		'locations' => get_posts( $query )
	);

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

// Update to get only responses to gifts for Brighton
function v2_get_responses ($request) {
	$query = array(
		'numberposts'   => -1,
		'post_type'     => 'response',
		'post_status'   => 'publish'
	);

	$result = array(
		'success' => true,
		'responses' => array(
			'sent' => array(),
			'received' => array()
		)
	);

	$responses = get_posts($query);
	foreach ($responses as $response) {
		if ($response->post_author == $request['id']) {
			$response->gift = get_field( 'field_59c4cdc1f07f6', $response->ID );
			$response->post_author_data = array(
				"nickname" 	=> get_userdata($response->post_author)->nickname,
				"avatar" 	=> get_avatar_url( $response->post_author, 32 )
			);
			$result['responses']['sent'][] = $response;
		} else {
			$gift = get_field( 'field_59c4cdc1f07f6', $response->ID );
			if ($gift->post_author == $request['id']) {
				$response->gift = $gift;
				$response->post_author_data = array(
					"nickname" 	=> get_userdata($response->post_author)->nickname,
					"avatar" 	=> get_avatar_url( $response->post_author, 32 )
				);
				$result['responses']['received'][] = $response;
			}
		}
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v2_setup_sender ($request) {
	$username = $request['username'];
	$email = $request['email'];

	$result = array(
		'success' => true,
		'user' => array()
	);

	if (email_exists($email)) {
		$result['success'] = false;
		$result['existing'] = get_user_by('email', $email);
	} else if (username_exists($username)) {
		$result['success'] = false;
		$result['existing'] = get_user_by('login', $username);
	} else {
		$id = wp_create_user( $username, $request['pass'], $email );
		update_user_meta($id, 'user_nicename', $request['name']);
		update_user_meta($id, 'first_name', $request['name']);
		update_user_meta($id, 'display_name', $request['name']);
		update_user_meta($id, 'nickname', $request['name']);

		$result['user'] = get_user_by('id', $id);
		$result['user']->nickname = $request['name'];
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v2_setup_receiver ($request) {
	$email = $request['email'];

	$result = array(
		'success' => true,
		'user' => array()
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
		$id = wp_create_user( $email, $password, $email );
		update_user_meta($id, 'user_nicename', $request['name']);
		update_user_meta($id, 'first_name', $request['name']);
		update_user_meta($id, 'display_name', $request['name']);
		update_user_meta($id, 'nickname', $request['name']);
		$result['user'] = get_user_by('id', $id);
		$result['user']->nickname = $request['name'];

		$giver = get_user_by('ID', $request['from']);

		/*curl_post('https://chat.gifting.digital/api/', array(
			'type' => '001', //types->newReceiver
			'giver' => $giver->user_email,
			'receiver' => $email,
			'password' => $password
		));*/
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v2_unwrap_gift ($request) {
	$id = $request['id'];

	$result = array(
		'success' => true
	);

	update_field('unwrapped', 1, $id);

	$gift = get_post($id);
	$sender_userdata = get_userdata($gift->post_author);
	$recipient_userdata = get_userdata($request['recipient']);

	/*require_once('lib/rest.php');
	curl_post('https://chat.gifting.digital/api/', array(
		'type' => '103', //types->unwrappedGift
		'id' => $id,
		'sender' => $gift->post_author,
		'sender_nickname' => $sender_userdata->nickname,
		'recipient' => $request['recipient'],
		'recipient_nickname' => $recipient_userdata->nickname,
		'title' => $gift->post_title,
		'status' => 'unwrapped'
	));*/

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v2_respond_to_gift ($request) {
	$giftId = $request['id'];

	$result = array(
		'success' => true
	);

	update_field('responded', 1, $giftId);

	$my_response = array(
		'post_content'  => $request['response'],
		'post_status'   => 'publish',
		'post_author'   => $request['sender'],
		'post_type'		=> 'response'
	);
	
	$post_id = wp_insert_post( $my_response );
	if(!is_wp_error($post_id)){
		update_field( 'gift', $giftId, $post_id ); //field_59c4cdc1f07f6

		$result['response'] = $post_id;

		$userdata = get_userdata($request['sender']);

		/*require_once('lib/rest.php');
		curl_post('https://chat.gifting.digital/api/', array(
			'type' => '100', //types->responseToGift
			'response' => $request['response'],
			'owner' => $request['owner'],
			'sender' => $request['sender'],
			'sender_nickname' => $userdata->nickname,
			'status' => 'responded'
		));*/
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v2_received_gift ($request) {
	$id = $request['id'];

	$result = array(
		'success' => true
	);

	update_field('received', 1, $id);

	$gift = get_post($id);
	$sender_userdata = get_userdata($gift->post_author);
	$recipient_userdata = get_userdata($request['recipient']);

	/*require_once('lib/rest.php');
	curl_post('https://chat.gifting.digital/api/', array(
		'type' => '102', //types->receivedGift
		'id' => $id,
		'sender' => $gift->post_author,
		'sender_nickname' => $sender_userdata->nickname,
		'recipient' => $request['recipient'],
		'recipient_nickname' => $recipient_userdata->nickname,
		'title' => $gift->post_title,
		'status' => 'received'
	));*/

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v2_validate_receiver ($request) {
	$email = $request['email'];

	$result = array(
		'success' => true,
		'exists' => false
	);

	if (email_exists($email)) {
		$result['exists'] = get_user_by('email', $email);
		$userdata = get_userdata($result['exists']->data->ID);
        $result['exists']->data->nickname = $userdata->nickname;
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v2_upload () {
	$result = array(
		'success' => true
	);

	define ('SITE_ROOT', realpath(dirname(__FILE__)));
	$target_path = SITE_ROOT . "/uploads/". basename( $_FILES['file']['name']);

	if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
		$result['filename'] = basename( $_FILES['file']['name']);
		$result['url'] = plugins_url( 'uploads/'.basename( $_FILES['file']['name']), __FILE__ );
	} else {
		$result['success'] = false;
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v2_setup_object ($request) {
  	$object = json_decode(stripslashes($request['object']));
  
	$result = array(
		'success' => true
	);

	$user = get_userdata( $request['owner'] );
	if ( $user === false ) {
		$result['success'] = false;
	} else {
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
			update_field( 'owner', $request['owner'], $post_id ); //field_5969c3853f8f2

			// set location
			update_field( 'location', $object->location->ID, $post_id ); //field_59a85fff4be5a

			$result['object'] = get_post($post_id);

			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $object->post_image, $matches );
			if ( ! $matches ) {
				$result['error'] = new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
				$result['success'] = false;
			} else {
				$file_array = array();
				$file_array['name'] = basename( $matches[0] );

				// Download file to temp location.
				require_once ABSPATH . 'wp-admin/includes/file.php';
				$file_array['tmp_name'] = download_url( $object->post_image );

				// If error storing temporarily, return the error.
				if ( is_wp_error( $file_array['tmp_name'] ) ) {
					$result['error'] = $file_array['tmp_name'];
					$result['success'] = false;
				} else {
					// Do the validation and storage stuff.
					require_once ABSPATH . 'wp-admin/includes/image.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					$id = media_handle_sideload( $file_array, $post_id, $request['name'] );

					// If error storing permanently, unlink.
					if ( is_wp_error( $id ) ) {
						@unlink( $file_array['tmp_name'] );
						$result['error'] = $id;
						$result['success'] = false;
					} else {
						if (set_post_thumbnail( $post_id, $id )) {
							$result['thumbnail'] = get_the_post_thumbnail_url($post_id, 'thumbnail');
						}
					}
				}
			}
		} else {
			$result['success'] = false;
			$result['error'] = $post_id->get_error_message();
		}
	}

	// delete the image in the uploads folder
	unlink($object->post_image);

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v2_setup_gift ($request) {
	$result = array(
		'success' => true
	);

	$sender = get_userdata( $request['sender'] );
	if ( $sender === false ) {
		$result['success'] = false;
	} else {
		$gift = json_decode(stripslashes($request['gift']));

		$giftcard_post = array(
			'post_title'    => 'Giftcard for '.wp_strip_all_tags( $gift->post_title ),
			'post_content'  => wp_strip_all_tags( $gift->giftcards[0]->post_content ),
			'post_status'   => 'publish',
			'post_author'   => $request['sender'],
			'post_type'		=> 'giftcard'
		);
		$giftcard_id = wp_insert_post( $giftcard_post );
		if (!is_wp_error($giftcard_id)) {
			$result['giftcard'] = $giftcard_id;

			$result['payloads'] = array();
			foreach ($gift->payloads as $payload) {
				$payload_post = array(
					'post_title'    => wp_strip_all_tags( $payload->post_title ),
					'post_content'  => wp_strip_all_tags( $payload->post_content ),
					'post_status'   => 'publish',
					'post_author'   => $request['sender'],
					'menu_order'	=> $payload->menu_order,
					'post_type'		=> 'payload'
				);
				$payload_id = wp_insert_post( $payload_post );
				if (!is_wp_error($payload_id)) {
					$result['payloads'][] = $payload_id;
				} else {
					unset ($payload_id);
					$result['success'] = false;
				}
			}

			$result['wraps'] = array();
			foreach ($gift->wraps as $wrap) {
				$wrap_post = array(
					'post_title'    => 'Wrap '.$wrap->menu_order.' for '.wp_strip_all_tags( $gift->post_title ),
					'post_status'   => 'publish',
					'post_author'   => $request['sender'],
					'menu_order'	=> $wrap->menu_order,
					'post_type'		=> 'wrap'
				);
				$wrap_id = wp_insert_post( $wrap_post );
				if (!is_wp_error($wrap_id)) {
					$result['wraps'][] = $wrap_id;
					update_field( 'object', array($wrap->unwrap_object->ID), $wrap_id );
				} else {
					unset ($wrap_id);
					$result['success'] = false;
				}
			}

			$gift_post = array(
				'post_title'    => wp_strip_all_tags( $gift->post_title ),
				'post_status'   => 'publish',
				'post_author'   => $request['sender'],
				'post_type'		=> 'gift'
			);
			$gift_id = wp_insert_post( $gift_post );
			if (!is_wp_error($gift_id)) {
				$result['gift'] = $gift_id;
				if (email_exists($gift->recipient->user_email)) {
					$recipient = get_user_by('id', $gift->recipient->ID);
					update_field( 'recipient', array($recipient->ID), $gift_id );
					$result['recipient'] = $recipient->ID;

					update_field( 'gift_card', $giftcard_id, $gift_id );

					if (count($result['payloads']) > 0) {
						update_field( 'payload', $result['payloads'], $gift_id );
					}

					if (count($result['wraps']) > 0) {
						update_field( 'wrap', $result['wraps'], $gift_id );
					}

					/*require_once('lib/rest.php');
					curl_post('https://chat.gifting.digital/api/', array(
						'type' => '000', //types->createdGift
						'sender' => $sender->nickname,
						'receiver' => $gift->recipient->ID
					));*/
				} else {
					$result['success'] = false;
				}
			} else {
				$result['success'] = false;
			}
		} else {
			$result['success'] = false;
		}
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

?>