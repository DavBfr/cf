<?php

abstract class Rest {

	private $routes = array();
	private $jsonpost_data = Null;


	function __construct() {
		$this->getRoutes();
	}


	public function addRoute($path, $method, $callback) {
		$this->routes[$method."@".$path] = $callback;
	}


	public abstract function getRoutes();


	public function jsonpost() {
		if ($this->jsonpost_data === Null) {
			$this->jsonpost_data = decode_json_post();
		}
		return $this->jsonpost_data;
	}


	public function handle_request($method, $path) {
		if ($path == "")
			$path = "/";

		if (isset($this->routes[$method."@".$path])) {
			call_user_func(array($this, $this->routes[$method."@".$path]), $_REQUEST);
			send_error(204);
		} else {
			send_error(404, NULL, $path);
		}
	}
	
	public static function handle($method = NULL, $path = NULL) {
		if ($path == NULL) {
			$path = @$_SERVER["PATH_INFO"];
		}
		
		if ($method == NULL) {
			$method = $_SERVER["REQUEST_METHOD"];
		}

		while (strlen($path) > 0 && $path[0] == "/")
			$path = substr($path, 1);

		if ($path == "")
			$path = "index";

		$pos = strpos($path, "/");
		if ($pos === false) {
			$request = $path;
			$next_path = "";
		} else {
			$request = substr($path, 0, $pos);
			$next_path = substr($path, $pos);
		}

		$request = str_replace(".", "_", $request);

		$request_file = REQUEST_DIR . "/" . $request . ".php";
		if (file_exists($request_file)) {
			require_once($request_file);
			$class_name = ucwords($request);
			$instance = new $class_name();
			$instance->handle_request($method, $next_path);
			exit(0);
		}

		send_error(404, NULL, $path);
	}

}
