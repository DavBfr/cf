<?php

class Output {

	public static function json($object) {
		if (!DEBUG)
			header("Content-Type: text/json");
		else
			header("Content-Type: text/plain");
		
		$content = ob_get_contents();
		ob_end_clean();
		if (DEBUG && is_array($object) && strlen($content) > 0) {
			$object["__debug__"] = $content;
		}
		die(json_encode($object));
	}


	public static function success($object = array()) {
		self::json(array_merge($object, array("success"=>True)));
	}


	public static function error($message) {
		self::json(array("error"=>$message, "success"=>False));
	}

	public static function debug($message) {
		if (!DEBUG)
			return;

		ErrorHandler::error(500, "Debug", "<pre>" . $message . "</pre>", 3);
	}
	
}
