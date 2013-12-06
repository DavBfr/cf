<?php

function send_error($code, $message = NULL, $body = NULL) {
	$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

	if ($message == NULL) {
		switch ($code) {
			case 500: $message = "Internal server error"; break;
			case 204: $message = "No Content"; break;
			case 404: $message = "Path not found"; break;
			case 401: $message = "Unauthorized"; break;
			case 417: $message = "Expectation failed"; break;
			default: $message = "Error #${code}"; break;
		}
	}

	header("$protocol $code $message");
	if (!DEBUG || $body === NULL)
		$body = $message;
	else
		$body = $message." : ".$body;

	if ($code >= 500) {
		Logger::Error("$code $message $body");
	} else {
		Logger::Info("$code $message $body");
	}
	die($body);
}


function ensure_request($array, $mandatory, $optional = array(), $strict = false) {
	foreach($mandatory as $param) {
		if (!isset($array[$param])) {
			send_error(417, NULL, "Missing parameter ${param}");
		}
		if ($array[$param] == "") {
			send_error(417, NULL, "Empty parameter ${param}");
		}
	}

	if ($strict) {
		foreach($array as $param => $val) {
			if (!(in_array($param, $mandatory) || in_array($param, $optional))) {
				send_error(417, NULL, "Parameter overly ${param}");
			}
		}
	}
}


function ensure_dir($name, $mode=0750) {
	if (! is_dir($name)) {
		if (! mkdir($name, $mode, true)) {
			send_error(500, NULL, "Unable to create directory $name");
		}
	}
}
