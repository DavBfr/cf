<?php

function send_error($code, $message, $body = NULL) {
	$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
	header("$protocol $code $message");
	if (!DEBUG || $body === NULL)
		$body = $message;
	else
		$body = $message." : ".$body;

	die($body);
}


function ensure_request($array, $mandatory, $optional = array(), $strict = false) {
	foreach($mandatory as $param) {
		if (!isset($array[$param])) {
			send_error(500, "Missing parameters", "$param");
		}
		if ($array[$param] == "") {
			send_error(500, "Empty parameter", "$param");
		}
	}

	if ($strict) {
		foreach($array as $param => $val) {
			if (!(in_array($param, $mandatory) || in_array($param, $optional))) {
				send_error(500, "Too much parameters", "$param");
			}
		}
	}
}
