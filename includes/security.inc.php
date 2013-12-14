<?php

function send_error($code, $message = NULL, $body = NULL, $backtrace=2) {
	ErrorHandler::getInstance()->send_error($code, $message, $body, $backtrace);
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
