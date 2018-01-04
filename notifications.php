<?php
	set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
	include_once('Net/SSH2.php');
	require 'vendor/autoload.php';
	use Mailgun\Mailgun;

	$types = parse_ini_file('../giftplatform-common/types.ini');

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
					/*sendEmail(
						$_POST['receiver'], 
						"Your Gift has arrived!", 
						"Good news - your Gift from ".$_POST['giver']." has arrived in the Gift App for you to unwrap at the Museum.\r\n\r\n"
							."To unwrap your gift please use the Gift Unwrap App on your mobile phone and log in using ".$_POST['receiver']." as your username. This has a number of steps that you need to complete in order to get your Gift.\r\n\r\n"
							."We hope you like it!\r\n\r\n"
							."[If you are not part of the Gift project, please ignore this notification and accept our apologies for the intrusion.]"
					);*/
					//echo doEjabberdCTLCommand('send_message', 'message nonadmin@chat.gifting.digital ben@chat.gifting.digital Debug "Platform tells receiver that a gift has been created for them"');
					//echo doEjabberdCTLCommand('send_message', 'message nonadmin@chat.gifting.digital ben@chat.gifting.digital Debug "Platform invites receiver to a room for further notifications"');
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
					//echo doEjabberdCTLCommand('send_message', 'message nonadmin@chat.gifting.digital ben@chat.gifting.digital Debug "Platform tells receiver that an account has been created for them"');
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
					//echo doEjabberdCTLCommand('send_message', 'message nonadmin@chat.gifting.digital ben@chat.gifting.digital Debug "Gift received", "Platform tells (giver) that gift ".$_POST['id']." has been received"');
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
					//echo doEjabberdCTLCommand('send_message', 'message nonadmin@chat.gifting.digital ben@chat.gifting.digital Debug "Gift received", "Platform tells (giver) that gift ".$_POST['id']." has been fully unwrapped"');
				}
				break;
			case $types['responseToGift']:
				if (isset ($_POST['owner']) && isset ($_POST['sender'])) {
					if (isset ($_POST['decline'])) {
						sendDebugEmail("Declined to respond", "Platform tells ".$_POST['owner']." (giver) that ".$_POST['sender']." (receiver) did not respond to their gift");
						/*sendEmail(
							$_POST['giver'], 
							"No response to your gift", 
							"Bad news - ".$_POST['receiver']." chose not to respond to the gift that you sent them.\r\n\r\n"
								."Maybe they're just waiting to respond to you in person.\r\n\r\n"
								."Best wishes - the Gift project team\r\n\r\n"
								."[If you are not part of the Gift project, please ignore this notification and accept our apologies for the intrusion.]"
						);*/
						//sendEmail($_POST['giver'], "No response to your gift", $_POST['receiver']." chose not to respond to your gift after they experienced it.");
						//echo doEjabberdCTLCommand('send_message', 'message nonadmin@chat.gifting.digital ben@chat.gifting.digital Debug "Platform tells sender that receiver did not respond to their gift"');
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
						/*sendEmail(
							$_POST['giver'], 
							"Incoming response to your gift", 
							$_POST['receiver']." sent you a message in response to the gift that you sent them.\r\n\r\n"
								."They said: ''".$_POST['responseText']."''\r\n\r\n"
								."Best wishes - the Gift project team\r\n\r\n"
								."[If you are not part of the Gift project, please ignore this notification and accept our apologies for the intrusion.]"
						);*/
						//sendEmail($_POST['giver'], "A response to your gift", $_POST['receiver']." responded to your gift with this message: ".$_POST['responseText']);
						//echo doEjabberdCTLCommand('send_message', 'message nonadmin@chat.gifting.digital ben@chat.gifting.digital Debug "Platform tells sender that the receiver said: '.$_POST['responseText'].'"');
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

	function connectToAPI () {
		$ssh = new Net_SSH2('chat.gifting.digital');
		$config = parse_ini_file('../../api.ini');
		if (!$ssh->login($config['execuser'], $config['execpass'])) {
			exit('Login Failed');
		} else {
			return $ssh;
		}
	}

	function doEjabberdCTLCommand ($command, $args) {
		$ssh = connectToAPI();
		$string = 'sudo /usr/sbin/ejabberdctl '.$command.' '.$args;
		return $ssh->exec($string);
	}

	function connectToMailgun () {
		return Mailgun::create('key-6543dea52f2b924b8f972d37f43e0729');
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
			'Authorization: key=AAAAQyrUOxo:APA91bGu8aSBHk3QEansKad9g3NCoHWZz2Aj8PUHzdW5rCff27Ru4v4J3SExqScsZwI6st7n-hghujk8FrE6JbtouAl_kYX_vx6dR7XV6F1kt8G5Rcd5-pFvJ0WE5VMSKOVZPslqLWTM',
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
