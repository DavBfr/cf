<?php

class Input {

	function decodeJsonPost() {
		return json_decode(file_get_contents("php://input"), true);
	}


	function ensureRequest($array, $mandatory, $optional = array(), $strict = false) {
		foreach($mandatory as $param) {
			if (!isset($array[$param])) {
				ErrorHandler::error(417, NULL, "Missing parameter ${param}");
			}
			if ($array[$param] == "") {
				ErrorHandler::error(417, NULL, "Empty parameter ${param}");
			}
		}

		if ($strict) {
			foreach($array as $param => $val) {
				if (!(in_array($param, $mandatory) || in_array($param, $optional))) {
					ErrorHandler::error(417, NULL, "Parameter overly ${param}");
				}
			}
		}
	}

}
