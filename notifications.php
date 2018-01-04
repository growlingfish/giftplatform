<?php
	require 'vendor/autoload.php';
	use Mailgun\Mailgun;

	if (isset ($_POST['type'])) {
		switch ($_POST['type']) {
			case $types['createdGift']: // happens when user of GIFT Wrapper app sends a gift
				if (isset ($_POST['sender']) && isset ($_POST['receiver'])) {
					sendDebugEmail("Gift created", "Platform tells ".$_POST['receiver']." (receiver) that ".$_POST['sender']." (giver) has created a gift for them");
					sendFCMPush(
						"giftDeliveries",
						"You've received a gift!",
						"Would you like to see your gifts now?",
						array(
							"recipientID" => $_POST['receiver']
						)
					);
				}
				break;
			case $types['newReceiver']: // happens when user of GIFT Wrapper app adds a receiver with an email that isn't already registered
				if (isset ($_POST['password']) && isset ($_POST['receiver']) && isset ($_POST['giver'])) {
					sendDebugEmail("Receiver created", "Platform tells ".$_POST['receiver']." (receiver) that they have an account with password ".$_POST['password']);
					sendEmail(
						$_POST['receiver'], 
						"New Gift account", 
						"Congratulations - another user of the Gift app has just started making you a Gift!\r\n\r\n"
							."We will let you know when they have finished it and sent it to you.\r\n\r\n"
							."When the Gift is on its way you can use the Gift app on your mobile to log in using ".$_POST['receiver']." as your username, and the word '".$_POST['password']."' as your password.\r\n\r\n"
							."Best wishes - the Gift team\r\n\r\n"
							."[If you are not part of the Gift project, please ignore this notification and accept our apologies for the intrusion.]"
					);
				}
				break;
			case $types['receivedGift']:
				if (isset ($_POST['id'])) {
					sendDebugEmail("Gift received", "Platform tells (giver) that gift ".$_POST['id']." has been received");
					sendFCMPush(
						"giftStatus",
						"One of your Gifts has been received!",
						$_POST['title']." has been ".$_POST['status']." by ".$_POST['recipient_nickname'],
						array(
							"senderID" => $_POST['sender'],
							"sender" => $_POST['sender_nickname'],
							"giftID" => $_POST['id'],
							"giftTitle" => $_POST['title'],
							"status" => $_POST['status'],
							"recipientID" => $_POST['recipient'],
							"recipient" => $_POST['recipient_nickname']
						)
					);
				}
				break;
			case $types['unwrappedGift']:
				if (isset ($_POST['id'])) {
					sendDebugEmail("Gift unwrapped", "Platform tells (giver) that gift ".$_POST['id']." has been fully unwrapped");
					sendFCMPush(
						"giftStatus",
						"One of your Gifts has been unwrapped!",
						$_POST['title']." has been ".$_POST['status']." by ".$_POST['recipient_nickname'],
						array(
							"senderID" => $_POST['sender'],
							"sender" => $_POST['sender_nickname'],
							"giftID" => $_POST['id'],
							"giftTitle" => $_POST['title'],
							"status" => $_POST['status'],
							"recipientID" => $_POST['recipient'],
							"recipient" => $_POST['recipient_nickname']
						)
					);
				}
				break;
			case $types['responseToGift']:
				if (isset ($_POST['owner']) && isset ($_POST['sender'])) {
					if (isset ($_POST['decline'])) {
						sendDebugEmail("Declined to respond", "Platform tells ".$_POST['owner']." (giver) that ".$_POST['sender']." (receiver) did not respond to their gift");
					} else if (isset ($_POST['response'])) {
						sendDebugEmail("Responded to gift", "Platform tells ".$_POST['owner']." (giver) that ".$_POST['sender']." (receiver) said: ".$_POST['response']);
						sendFCMPush(
							"giftStatus",
							"You've received a response to your Gift",
							$_POST['sender_nickname']." said: ".$_POST['response'],
							array(
								"sender" => $_POST['sender'],
								"owner" => $_POST['owner'],
								"response" => $_POST['response'],
								"status" => $_POST['status']
							)
						);
					} else {
						http_response_code(400);
					}
				} else {
					http_response_code(400);
				}
				break;
			default:
				http_response_code(400);
				break;
		}
	} else {
		http_response_code(401);
		die();
	}

	function connectToMailgun () {
		return Mailgun::create(MAILGUNAPI);
	}

	function sendEmail ($to, $subject, $text) {
		$mg = connectToMailgun();
		$mg->messages()->send('mail.gifting.digital', [
			'from'    => 'postmaster@mail.gifting.digital', 
			'to'      => $to, 
			'subject' => $subject, 
			'text'    => $text 
		]);
	}

	function sendDebugEmail ($subject, $text) {
		$mg = connectToMailgun();
		$mg->messages()->send('mail.gifting.digital', [
			'from'    => 'postmaster@mail.gifting.digital', 
			'to'      => 'benjamin.bedwell@nottingham.ac.uk', 
			'subject' => $subject, 
			'text'    => "[".date("h:i:sa d-m-Y")."]:".$text 
		]);
	}

	function sendFCMPush ($topic, $title, $body, $data) {
		$fcm = new StdClass();
		$fcm->to = "/topics/".$topic;
		$fcm->notification = new StdClass();
		$fcm->notification->title = $title;
		$fcm->notification->body = $body;
		$fcm->notification->content_available = true;
		$fcm->notification->priority = "high";
		$fcm->notification->sound = "default";
		$fcm->notification->click_action = "FCM_PLUGIN_ACTIVITY";
		$fcm->notification->icon = "fcm_push_icon";
		$fcm->data = new StdClass();
		$fcm->data->title = $title;
		$fcm->data->body = $body;
		$fcm->data->content_available = true;
		$fcm->data->priority = "high";
		$fcm->data->topic = $topic;
		foreach ($data as $key => $val) {
			$fcm->data->{$key} = $val;
		}
		$json = json_encode($fcm);
		
		$headers = array
		(
			'Authorization: key='.MAILGUNAUTH,
			'Content-Type: application/json'
		);

		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, $json );
		$result = curl_exec($ch );
		curl_close( $ch );
	}
?>
