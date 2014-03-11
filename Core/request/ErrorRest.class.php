<?php

class ErrorRest extends Rest {

	public function getRoutes() {
		$this->addRoute("/", "GET", "error");
	}
	
	protected function error($r) {
		$code = $_SERVER["REDIRECT_STATUS"];
		if (array_key_exists($code, ErrorHandler::$messagecode))
			$message = ErrorHandler::$messagecode[$code];
		else
			$message = "";
		
		$tpt = new Template(array(
			"code"=>$code,
			"message"=>$message,
			"body"=>(DEBUG?CorePlugin::info():""),
			"baseline"=>CorePlugin::getBaseline(),
		));
		$tpt->output(ERROR_TEMPLATE);
	}

}
