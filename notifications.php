<?php
	require 'vendor/autoload.php';
	use Mailgun\Mailgun;

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
