<?php

class IosPush {

	function __construct($pem, $key) {
		$this->pem = $pem;
		$this->key = $key;
	}

	function push($deviceToken, $body) {
		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', $this->pem);
		stream_context_set_option($ctx, 'ssl', 'passphrase', $this->key);

		// Open a connection to the APNS server
		$fp = stream_socket_client(
			'ssl://gateway.sandbox.push.apple.com:2195',
			$err,
			$errstr,
			60,
			STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT,
			$ctx
		);

		if (!$fp)
			return "Failed to connect: $err $errstr";

		// Encode the payload as JSON
		$payload = json_encode($body);

		// Build the binary notification
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

		// Send it to the server
		$result = fwrite($fp, $msg, strlen($msg));

		// Close the connection to the server
		fclose($fp);

		if (!$result)
			return 'Message not delivered';

		return true;
	}

	function alert($deviceToken, $message, $sound = 'default') {
		$body['aps'] = array(
			'alert' => $message,
			'sound' => 'default'
			);
		return $this->push($deviceToken, $body);
	}

}
