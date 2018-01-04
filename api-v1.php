<?php
 
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
*	Custom API end-points: first Brighton sprint
*/

add_action( 'rest_api_init', 'gift_v1_register_api_hooks' );
function gift_v1_register_api_hooks () {
	global $namespace;
	$version = 1;
	
	register_rest_route( $namespace.'/v'.$version, '/auth/(?P<user>.+)/(?P<pass>.+)', array(
		'methods'  => 'GET',
		'callback' => 'v1_gift_auth',
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
	register_rest_route( $namespace.'/v'.$version, '/gifts/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v1_prepare_gifts',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				}
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/objects/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v1_get_objects',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				}
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/new/receiver/(?P<email>.+)/(?P<name>.+)/(?P<from>.+)', array(
		'methods'  => 'GET',
		'callback' => 'v1_setup_receiver',
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
	register_rest_route( $namespace.'/v'.$version, '/new/sender/(?P<email>.+)/(?P<name>.+)/(?P<pass>.+)', array(
		'methods'  => 'GET',
		'callback' => 'v1_setup_sender',
		'args' => array(
			'email' => array(
				'validate_callback' => function ($param, $request, $key) {
					return filter_var($param, FILTER_VALIDATE_EMAIL) && !email_exists($param);
				}
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/new/object/', array(
		'methods'  => 'POST',
		'callback' => 'v1_setup_object',
		'args' => array(
			'owner' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				}
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/new/gift/', array(
		'methods'  => 'POST',
		'callback' => 'v1_setup_gift',
		'args' => array(
			'sender' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && get_user_by('ID', $param);
				}
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/unwrapped/gift/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v1_unwrap_gift',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && is_string( get_post_status( $param ) );
				}
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/responded/gift/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v1_respond_to_gift',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && is_string( get_post_status( $param ) );
				}
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/received/gift/(?P<id>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v1_received_gift',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param) && is_string( get_post_status( $param ) );
				}
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/validate/receiver/(?P<email>.+)/', array(
		'methods'  => 'GET',
		'callback' => 'v1_validate_receiver',
		'args' => array(
			'email' => array(
				'validate_callback' => function ($param, $request, $key) {
					return filter_var($param, FILTER_VALIDATE_EMAIL);
				}
			)
		)
	) );
	register_rest_route( $namespace.'/v'.$version, '/upload/object/', array(
		'methods'  => 'POST',
		'callback' => 'v1_upload'
	) );
}

function v1_gift_auth ($request) {
	$user = get_user_by('email', $request['user']);
	$userdata = get_userdata($user->ID);

	$result = array(
		'name' => $userdata->nickname,
		'id' => $user->ID,
		'user' => $user,
		'success' => true
	);

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v1_prepare_gifts ($request) {
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

function v1_get_objects ($request) {
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
			$result['objects'][] = $object;
		}
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function v1_setup_sender ($request) {
	$email = $request['email'];

	$result = array(
		'success' => true,
		'new' => array()
	);

	if (email_exists($email)) {
		$result['success'] = false;
		$result['existing'] = get_user_by('email', $email);
	} else {
		$result['new'] = array(
			'id' => wp_create_user( $email, $request['pass'], $email )
		);
		update_user_meta($result['new']['id'], 'user_nicename', $request['name']);
		update_user_meta($result['new']['id'], 'first_name', $request['name']);
		update_user_meta($result['new']['id'], 'display_name', $request['name']);
		update_user_meta($result['new']['id'], 'nickname', $request['name']);
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v1_setup_receiver ($request) {
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
		update_user_meta($result['new']['id'], 'user_nicename', $request['name']);
		update_user_meta($result['new']['id'], 'first_name', $request['name']);
		update_user_meta($result['new']['id'], 'display_name', $request['name']);
		update_user_meta($result['new']['id'], 'nickname', $request['name']);

		$giver = get_user_by('ID', $request['from']);

		curl_post('https://chat.gifting.digital/api/', array(
			'type' => '001', //types->newReceiver
			'giver' => $giver->user_email,
			'receiver' => $email,
			'password' => $password
		));
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v1_unwrap_gift ($request) {
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

function v1_respond_to_gift ($request) {
	$id = $request['id'];

	$result = array(
		'success' => true
	);

	update_field('responded', 1, $id);

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v1_received_gift ($request) {
	$id = $request['id'];

	$result = array(
		'success' => true
	);

	update_field('received', 1, $id);

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v1_validate_receiver ($request) {
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

function v1_upload ($request) {
	$result = array(
		'success' => true
	);

	define ('SITE_ROOT', realpath(dirname(__FILE__)));
	$target_path = SITE_ROOT . "/uploads/". basename( $_FILES['file']['name']);

	if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
		$result['filename'] = basename( $_FILES['file']['name']);
	} else {
		$result['success'] = false;
	}

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v1_setup_object ($request) {
	$result = array(
		'success' => true
	);

	$file = plugins_url( "/uploads/". $request['filename'], __FILE__ );

	$user = get_userdata( $request['owner'] );
	if ( $user === false ) {
		$result['success'] = false;
	} else {
		// Create post object
		$my_post = array(
			'post_title'    => wp_strip_all_tags( $request['name'] ),
			'post_content'  => $request['description'],
			'post_status'   => 'publish',
			'post_author'   => $request['owner'],
			'post_type'		=> 'object'
		);
		
		// Insert the post into the database
		$post_id = wp_insert_post( $my_post );
		if(!is_wp_error($post_id)){
			// set owner to the user
			update_field( 'owner', $request['owner'], $post_id );

			$result['objectid'] = $post_id;

			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
			if ( ! $matches ) {
				$result['error'] = new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
				$result['success'] = false;
			} else {
				$file_array = array();
				$file_array['name'] = basename( $matches[0] );

				// Download file to temp location.
				require_once ABSPATH . 'wp-admin/includes/file.php';
				$file_array['tmp_name'] = download_url( $file );

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
			$result['object'] = $post_id->get_error_message();
		}
	}

	// delete the image in the uploads folder
	unlink($file);

	$response = new WP_REST_Response( $result );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function v1_setup_gift ($request) {
	$result = array(
		'success' => true
	);

	$sender = get_userdata( $request['sender'] );
	if ( $sender === false ) {
		$result['success'] = false;
	} else {
		$gift = json_decode(stripslashes($request['gift']));

		$giftcard_post = array(
			'post_title'    => wp_strip_all_tags( $gift->giftcard->title ),
			'post_content'  => wp_strip_all_tags( $gift->giftcard->content ),
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
					'post_title'    => wp_strip_all_tags( $payload->title ),
					'post_content'  => wp_strip_all_tags( $payload->content ),
					'post_status'   => 'publish',
					'post_author'   => $request['sender'],
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
					'post_title'    => wp_strip_all_tags( $wrap->title ),
					'post_status'   => 'publish',
					'post_author'   => $request['sender'],
					'post_type'		=> 'wrap'
				);
				$wrap_id = wp_insert_post( $wrap_post );
				if (!is_wp_error($wrap_id)) {
					$result['wraps'][] = $wrap_id;

					foreach ($wrap->challenges as $challenge) {
						if ($challenge->type == 'object') {
							$challenge->task = array($challenge->task);
						}
						update_field( $challenge->type, $challenge->task, $wrap_id );
					}
				} else {
					unset ($wrap_id);
					$result['success'] = false;
				}
			}

			$gift_post = array(
				'post_title'    => wp_strip_all_tags( $gift->title ),
				'post_status'   => 'publish',
				'post_author'   => $request['sender'],
				'post_type'		=> 'gift'
			);
			$gift_id = wp_insert_post( $gift_post );
			if (!is_wp_error($gift_id)) {
				$result['gift'] = $gift_id;
				if (email_exists($gift->receiver)) {
					$receiver = get_user_by('email', $gift->receiver);
					update_field( 'recipient', array($receiver->ID), $gift_id );
					$result['receiver'] = $receiver->ID;

					update_field( 'gift_card', $giftcard_id, $gift_id );

					if (count($result['payloads']) > 0) {
						update_field( 'payload', $result['payloads'], $gift_id );
					}

					if (count($result['wraps']) > 0) {
						update_field( 'wrap', $result['wraps'], $gift_id );
					}

					require_once('lib/rest.php');
					curl_post('https://chat.gifting.digital/api/', array(
						'type' => '000', //types->createdGift
						'giver' => $sender->user_email,
						'receiver' => $gift->receiver
					));
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