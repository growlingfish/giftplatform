<?php

define('TOKENLENGTH', 48);
define('TOKENTABLE', 'gift_tokens');

global $gift_token_version;
$gift_token_version = '0.0.0.1';

// Create databases to store saves
function gift_tokendb_install () {
	global $wpdb;
	global $gift_token_version;
	$charset_collate = $wpdb->get_charset_collate();
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	// Locations
	$table_name = $wpdb->prefix . TOKENTABLE; 
	$sql = "CREATE TABLE $table_name (
  		id BIGINT(20) NOT NULL AUTO_INCREMENT,
  		userId BIGINT(20) NOT NULL,
  		issuedAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
		expiresAt DATETIME NOT NULL,
  		token VARCHAR(".TOKENLENGTH.") NOT NULL,
  		apiVersion VARCHAR(10) NOT NULL,  
  		UNIQUE KEY id (id)
	) $charset_collate;";
	dbDelta( $sql );
	
	add_option( 'gift_token_version', $gift_token_version );
}
register_activation_hook( __FILE__, 'gift_tokendb_install' );

function gift_tokendb_check () {
	global $gift_token_version;
    if ( get_site_option( 'gift_token_version' ) != $gift_token_version ) {
        gift_tokendb_install();
    }
}
add_action( 'plugins_loaded', 'gift_tokendb_check' );

function generate_token ($userId, $apiVersion) {
	$token = substr(bin2hex(random_bytes(TOKENLENGTH)), 0, TOKENLENGTH);

	$expiresAt = new DateTime('+1 day');

	global $wpdb;
	if ($wpdb->insert( 
		$wpdb->prefix . TOKENTABLE, 
		array( 
			'userId' => $userId,
			'token' => $token,
			'expiresAt' => $expiresAt->format('Y-m-d H:i:s'),
			'apiVersion' => $apiVersion
		), 
		array( 
			'%d', 
			'%s',
			'%s',
			'%s'
		)
	)) {
		return $token;
	} else {
		return null;
	}	
}

function prepare_gift_user ($id) {
	$user = get_user_by('ID', $id);
	if (!$user) {
		return null;
	}
	$userdata = get_userdata($user->data->ID);
	return array(
		'ID' 			=> $user->data->ID,
		'user_email'	=> $user->data->user_email,
		'nickname'		=> $userdata->nickname,
		'gravatar'		=> get_avatar_url( $user->data->ID, 32 )
	);
}

function check_token () {
	$header = explode(" ", $_SERVER["HTTP_AUTHORIZATION"]);
	if (count($header) == 2 && $header[0] == 'GiftToken') {
		$auth = base64_decode($header[1]);
		$credentials = explode(":", $auth);
		if (count($credentials) == 2) {
			global $wpdb;
			$table = $wpdb->prefix . TOKENTABLE;
			$validTokens = $wpdb->get_results( 
				$wpdb->prepare( 
					"SELECT id FROM $table WHERE userId=%d AND token=%s AND expiresAt > NOW()",
					$credentials[0],
					$credentials[1]
				)
			);
			return count ($validTokens) > 0;
		}
	} 
	return false;
}

function prepare_gift ($post) {
	$gift = (object)array(
		'ID' => $post->ID,
		'post_date' => $post->post_date,
		'author' => prepare_gift_user($post->post_author)
	);

	if (!$gift->author) {
		return null;
	}

	$hasObject = false;

	$recipients = get_field( ACF_recipient, $gift->ID );
	if (!$recipients) {
		return null;
	}
	foreach ($recipients as $recipient) {
		$gift->recipient = prepare_gift_user($recipient['ID']);
		break; // only one recipient for now
	}
	
	if (!$gift->recipient) {
		return null;
	}
	
	$wraps = get_field( ACF_wrap, $gift->ID);
	if (!$wraps) {
		return null;
	}
	foreach ($wraps as $wrap) {
		$w = (object)array(
			'ID' => $wrap->ID
		);
		$w->unwrap_date = get_field( ACF_date, $wrap->ID);
		$w->unwrap_key = get_field( ACF_key, $wrap->ID);
		$w->unwrap_place = html_entity_decode(get_field( ACF_place, $wrap->ID));
		$w->unwrap_artcode = get_field( ACF_artcode, $wrap->ID);
		$w->unwrap_personal = get_field( ACF_personal, $wrap->ID);
		$object = get_field( ACF_object, $wrap->ID);
		if (is_array($object) && count($object) > 0) {
			$object = $object[0];
		} else if (is_a($object, 'WP_Post')) {
				
		} else {
			unset($object);
		}  
		if ($object) {
			$w->unwrap_object = prepare_gift_object($object);
			if ($w->unwrap_object) {
				$hasObject = true;
			}
		}
		$gift->wraps[] = $w;
	}

	if (!$hasObject) { // gift must have an object?
		return null;
	}

	$payloads = get_field( ACF_payload, $gift->ID);
	if (!$payloads) {
		return null;
	}
	foreach ($payloads as $payload) {
		$gift->payloads[] = (object)array(
			'ID' => $payload->ID,
			'post_content' => wpautop($payload->post_content)
		);
	}

	$giftcards = get_field( ACF_giftcard, $gift->ID);
	if (!$giftcards) {
		return null;
	}
	foreach ($giftcards as $giftcard) {
		$gift->giftcards[] = (object)array(
			'ID' => $giftcard->ID,
			'post_title' => $giftcard->post_title,
			'post_content' => wpautop($giftcard->post_content)
		);
	}

	$gift->status = array(
		'received' => get_field( ACF_received, $gift->ID),
		'unwrapped' => get_field( ACF_unwrapped, $gift->ID),
		'responded' => get_field( ACF_responded, $gift->ID)
	);
	return $gift;
}

function prepare_gift_object ($post) {
	$location = get_field( ACF_location, $post->ID );
	if (!$location || count($location) == 0) {
		return null;
	}
	$location = $location[0];
	return (object)array(
		'ID' => $post->ID,
		'author' => prepare_gift_user($post->post_author),
		'post_title' => $post->post_title,
		'post_image' => get_the_post_thumbnail_url($post->ID, 'large'),
		'post_content' => wpautop($post->post_content),
		'location' => prepare_gift_location($location)
	);
}

function prepare_gift_venue ($term) {
	return (object)array(
		'ID' => $term->term_id,
		'name' => $term->name
	);
}

function prepare_gift_location ($post) {
	$venues = wp_get_post_terms( $post->ID, 'venue' );
	$v = array();
	foreach ($venues as $venue) {
		$v[] = prepare_gift_venue($venue);
	}
	return (object)array(
		'ID' => $post->ID,
		'post_title' => $post->post_title,
		'venue' => $v[0] // only one venue per location?
	);
}

function prepare_gift_response ($post) {
	$gift = get_field( ACF_gift, $post->ID );
	if (get_user_by('ID', $post->post_author) && get_user_by('ID', $gift->post_author)) {
		return (object)array(
			'ID' => $post->ID,
			'post_date' => $post->post_date,
			'author' => prepare_gift_user($post->post_author),
			'recipient' => prepare_gift_user($gift->post_author),
			'post_content' => $post->post_content,
		);
	}
	return null;
}

?>